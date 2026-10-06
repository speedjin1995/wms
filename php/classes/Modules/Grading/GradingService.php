<?php
namespace App\Modules\Grading;

use App\Core\BaseService;

require_once __DIR__ . '/../../../uploadFileHelper.php';
require_once __DIR__ . '/../../../services/stockManagementService.php';

/**
 * Grading records (grading + grading_items) with grading stock balance updates.
 * Non-SADMIN users are scoped to the session company and to their accessible wholesale / processing categories.
 */
class GradingService extends BaseService
{
    public const REJECT_GRADE = 'REJ';
    private const CATEGORY_MODULES = ['wholesale', 'processing'];

    private array $userModuleAccess;
    private bool $stockEnabled;

    public function __construct(\mysqli $db, int $company, int $user, string $role, array $userModuleAccess = [], bool $stockEnabled = false)
    {
        parent::__construct($db, $company, $user, $role);
        $this->userModuleAccess = $userModuleAccess;
        $this->stockEnabled = $stockEnabled;
    }

    /**
     * Dropdown data for the filter / entry forms
     */
    public function getLookups(): array
    {
        $categoryIds = $this->accessibleCategoryIds($this->userModuleAccess, self::CATEGORY_MODULES);
        $modules = "'" . implode("','", self::CATEGORY_MODULES) . "'";

        if ($this->isSuperAdmin()) {
            return [
                'categories' => $this->fetchAll("SELECT id, category_name FROM categories WHERE deleted = '0' AND module IN ($modules) ORDER BY category_name ASC"),
                'products' => $this->fetchAll("SELECT id, product_name, category FROM products WHERE deleted = '0' ORDER BY product_name ASC"),
                'grades' => $this->fetchAll(
                    "SELECT DISTINCT g.id, g.units, pg.product_id FROM grades g
                     LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id
                     WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC"
                ),
                'locations' => $this->fetchAll("SELECT id, locations FROM locations WHERE deleted = '0' ORDER BY locations ASC"),
                'allowPhoto' => 'Y'
            ];
        }

        [$categoryFilter, $categoryTypes] = $this->categoryIdFilter('c.id', $categoryIds);

        $products = $this->fetchAll(
            "SELECT p.id, p.product_name, p.category FROM products p INNER JOIN categories c ON p.category = c.id
             WHERE p.deleted = '0' AND p.customer = ? AND c.module IN ($modules) AND c.deleted = '0'$categoryFilter ORDER BY p.product_name ASC",
            'i' . $categoryTypes,
            array_merge([$this->company], $categoryIds)
        );
        if (empty($products)) {
            $products = $this->fetchAll("SELECT id, product_name, category FROM products WHERE deleted = '0' AND customer = ? ORDER BY product_name ASC", 'i', [$this->company]);
        }

        $company = $this->fetchOne("SELECT include_photo FROM companies WHERE id = ?", 'i', [$this->company]);

        return [
            'categories' => $this->fetchAll(
                "SELECT c.id, c.category_name FROM categories c WHERE c.deleted = '0' AND c.customer = ? AND c.module IN ($modules)$categoryFilter ORDER BY c.category_name ASC",
                'i' . $categoryTypes,
                array_merge([$this->company], $categoryIds)
            ),
            'products' => $products,
            'grades' => $this->fetchAll(
                "SELECT DISTINCT g.id, g.units, pg.product_id FROM grades g
                 LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id
                 WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = ? ORDER BY p.product_name ASC, g.units ASC",
                'i',
                [$this->company]
            ),
            'locations' => $this->fetchAll("SELECT id, locations FROM locations WHERE deleted = '0' AND customer = ? ORDER BY locations ASC", 'i', [$this->company]),
            'allowPhoto' => $company['include_photo'] ?? 'N'
        ];
    }

    /**
     * Get paginated grading records for DataTables (dates filter on created_date)
     */
    public function getList(array $filters, int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $from = "grading g LEFT JOIN categories c ON g.product_category = c.id LEFT JOIN locations l ON g.location = l.id";
        $where = "g.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'g.company');

        $totalRecords = $this->countRows('grading g', $where, $types, $params);

        $this->applyFilters($where, $params, $types, $filters, 'created_date');
        $this->applySearch($where, $params, $types, ['g.grading_no'], $search);
        $totalFiltered = $this->countRows($from, $where, $types, $params);

        $orderBy = $this->orderBy([
            'grading_no' => 'g.grading_no',
            'category' => 'c.category_name',
            'locations' => 'l.locations',
            'start_date' => 'g.start_date',
            'end_date' => 'g.end_date'
        ], $orderColumn, $orderDir, 'g.grading_no');
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $data = $this->fetchAll(
            "SELECT g.id, g.grading_no, c.category_name AS category, l.locations AS locations, IFNULL(g.remark, '') AS remark,
                    g.created_date, g.start_date, g.end_date, g.created_by, g.company
             FROM $from WHERE $where ORDER BY $orderBy LIMIT ?, ?",
            $types,
            $params
        );

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Single grading with its weight and reject items
     */
    public function getById(int $id): ?array
    {
        $grading = $this->findGrading($id);
        if (!$grading) {
            return null;
        }

        $names = $this->fetchOne(
            "SELECT c.category_name, l.locations FROM grading g
             LEFT JOIN categories c ON g.product_category = c.id LEFT JOIN locations l ON g.location = l.id WHERE g.id = ?",
            'i',
            [$id]
        );

        $items = $this->fetchAll(
            "SELECT gi.id, gi.grading_id, gi.product_id, p.product_name, gi.wholesales_id, gi.from_grade, gi.to_grade,
                    COALESCE(gr.units, gi.to_grade) AS to_grade_unit, gi.gross_weight, gi.tare_weight, gi.nett_weight,
                    DATE_FORMAT(gi.weighing_time, '%H:%i:%s') AS weighing_time, gi.photo_path
             FROM grading_items gi
             LEFT JOIN products p ON gi.product_id = p.id
             LEFT JOIN grades gr ON gr.id = gi.to_grade
             WHERE gi.grading_id = ? AND gi.deleted = 0",
            'i',
            [$id]
        );

        $weightDetails = [];
        $rejectDetails = [];
        foreach ($items as $item) {
            if ($item['to_grade'] === self::REJECT_GRADE) {
                $rejectDetails[] = $item;
            } else {
                $weightDetails[] = $item;
            }
        }

        return [
            'id' => $grading['id'],
            'grading_no' => $grading['grading_no'],
            'location' => $grading['location'],
            'locations' => $names['locations'] ?? '',
            'indicator' => $grading['indicator'],
            'start_date' => $grading['start_date'],
            'end_date' => $grading['end_date'],
            'product_category' => $grading['product_category'],
            'category' => $names['category_name'] ?? '',
            'remark' => $grading['remark'],
            'company' => $grading['company'],
            'weightDetails' => $weightDetails,
            'rejectDetails' => $rejectDetails
        ];
    }

    /**
     * Create or update a grading with its items, photos and grading stock movements (one transaction).
     * $items: [['gradingItemId', 'product', 'grade', 'gross', 'tare', 'time', 'photo' => uploaded file|null], ...]
     */
    public function save(int $id, array $header, array $items): array
    {
        $existing = null;
        if ($id) {
            $existing = $this->findGrading($id);
            if (!$existing) {
                return ['status' => 'failed', 'message' => 'Record not found'];
            }
        }
        $recordCompany = $existing ? (int)$existing['company'] : $this->company;

        $error = $this->validate($header, $items, $recordCompany);
        if ($error !== null) {
            return ['status' => 'failed', 'message' => $error];
        }

        $oldPhotos = [];
        $this->db->begin_transaction();

        try {
            $before = $existing ? $this->groupedWeights($id) : [];
            $existingItems = [];

            if ($existing) {
                if (!$this->updateRow('grading', $header + ['modified_by' => $this->user], "id = ?", 'i', [$id])) {
                    throw new \Exception('Failed to update grading');
                }
                foreach ($this->fetchAll("SELECT id, photo_path FROM grading_items WHERE grading_id = ? AND deleted = 0", 'i', [$id]) as $row) {
                    $existingItems[(int)$row['id']] = $row;
                }
            } else {
                $id = $this->insertRow('grading', $header + [
                    'grading_no' => $this->nextGradingNo($header['start_date']),
                    'indicator' => '',
                    'company' => $recordCompany,
                    'created_by' => $this->user,
                    'created_date' => date('Y-m-d H:i:s')
                ]);
                if (!$id) {
                    throw new \Exception('Failed to insert grading');
                }
            }

            $keptItemIds = [];
            foreach ($items as $item) {
                $itemId = (int)$item['gradingItemId'];
                $isUpdate = isset($existingItems[$itemId]);
                $photoPath = $isUpdate ? (string)$existingItems[$itemId]['photo_path'] : '';

                if (!empty($item['photo'])) {
                    $upload = uploadFile($item['photo'], 'photo', (string)$recordCompany, $this->db, 'photoPath');
                    if ($upload['status'] !== 'success') {
                        throw new \RuntimeException($upload['message']);
                    }
                    if ($photoPath !== '') {
                        $oldPhotos[] = $photoPath;
                    }
                    $photoPath = $upload['fid'];
                }

                $data = [
                    'product_id' => $item['product'],
                    'to_grade' => $item['grade'],
                    'gross_weight' => $item['gross'],
                    'tare_weight' => $item['tare'],
                    'nett_weight' => (string)abs((float)$item['gross'] - (float)$item['tare']),
                    'weighing_time' => date('Y-m-d') . ' ' . $item['time'],
                    'photo_path' => $photoPath
                ];

                if ($isUpdate) {
                    if (!$this->updateRow('grading_items', $data + ['deleted' => '0'], "id = ? AND grading_id = ?", 'ii', [$itemId, $id])) {
                        throw new \Exception('Failed to update grading item');
                    }
                    $keptItemIds[] = $itemId;
                } elseif (!$this->insertRow('grading_items', $data + ['grading_id' => $id])) {
                    throw new \Exception('Failed to insert grading item');
                }
            }

            // Items removed from the form
            foreach (array_diff(array_keys($existingItems), $keptItemIds) as $removedId) {
                if (!$this->executeWrite("UPDATE grading_items SET deleted = '1' WHERE id = ?", 'i', [$removedId])) {
                    throw new \Exception('Failed to remove grading item');
                }
            }

            if ($this->stockEnabled) {
                $after = $this->groupedWeights($id);
                foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $key) {
                    $beforeNet = $before[$key]['net'] ?? 0;
                    $afterNet = $after[$key]['net'] ?? 0;
                    if (floatval($afterNet) == floatval($beforeNet)) {
                        continue;
                    }
                    $group = $after[$key] ?? $before[$key];
                    $result = processGradingStock($this->db, $group['product'], $group['grade'], $recordCompany, $afterNet, $this->user, (bool)$existing, $beforeNet, $id);
                    if (($result['status'] ?? '') !== 'success') {
                        throw new \Exception('Failed to update grading stock: ' . ($result['message'] ?? ''));
                    }
                }
            }

            $this->db->commit();
        } catch (\RuntimeException $e) {
            $this->db->rollback();
            return ['status' => 'failed', 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('GradingService::save - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to save record'];
        }

        foreach ($oldPhotos as $oldPhoto) {
            deleteOldFile($oldPhoto, $this->db, 'photoPath');
        }

        return ['status' => 'success', 'message' => $existing ? 'Updated Successfully!!' : 'Added Successfully!!'];
    }

    /**
     * Soft delete a grading with a reason and reverse its grading stock
     */
    public function cancel(int $id, string $reason): array
    {
        $grading = $this->findGrading($id);
        if (!$grading) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        $this->db->begin_transaction();

        try {
            if (!$this->executeWrite("UPDATE grading SET deleted = '1', delete_reason = ?, modified_by = ? WHERE id = ?", 'sii', [$reason, $this->user, $id])) {
                throw new \Exception('Failed to delete grading');
            }

            if ($this->stockEnabled) {
                $result = processDeleteGradingStock($this->db, $id, $grading['company'], $this->user);
                if (($result['status'] ?? '') !== 'success') {
                    throw new \Exception('Failed to reverse grading stock: ' . ($result['message'] ?? ''));
                }
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('GradingService::cancel - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to delete record'];
        }

        return ['status' => 'success', 'message' => 'Deleted'];
    }

    /**
     * Gradings with aggregated item weights for Excel / PDF export (dates filter on start_date).
     * Selected IDs, or all session-company records matching the filters.
     */
    public function getReportRows(array $filters, array $ids = []): array
    {
        $ids = $this->cleanIds($ids);

        if (!empty($ids)) {
            $where = "g.deleted = 0 AND g.id IN (" . $this->placeholders(count($ids)) . ")";
            $params = $ids;
            $types = str_repeat('i', count($ids));
            $this->applyCompanyScope($where, $params, $types, 'g.company');
        } else {
            $where = "g.deleted = 0 AND g.company = ?";
            $params = [$this->company];
            $types = 'i';
            $this->applyFilters($where, $params, $types, $filters, 'start_date');
        }

        $gradings = $this->fetchAll(
            "SELECT g.id, g.grading_no, g.start_date, g.end_date, g.indicator, IFNULL(g.remark, '') AS remark,
                    IFNULL(l.locations, '') AS location, IFNULL(c.category_name, '') AS category, IFNULL(u.name, '') AS created_by
             FROM grading g
             LEFT JOIN locations l ON g.location = l.id
             LEFT JOIN categories c ON g.product_category = c.id
             LEFT JOIN users u ON g.created_by = u.id
             WHERE $where ORDER BY g.start_date ASC",
            $types,
            $params
        );

        if (empty($gradings)) {
            return [];
        }

        $gradingIds = array_map('intval', array_column($gradings, 'id'));
        $items = $this->fetchAll(
            "SELECT gi.grading_id, IFNULL(p.product_name, 'Unknown') AS product_name, IFNULL(gr.units, '') AS grade_name,
                    gi.gross_weight, gi.tare_weight, gi.nett_weight
             FROM grading_items gi
             LEFT JOIN products p ON gi.product_id = p.id
             LEFT JOIN grades gr ON gr.id = gi.to_grade
             WHERE gi.deleted = 0 AND gi.grading_id IN (" . $this->placeholders(count($gradingIds)) . ")",
            str_repeat('i', count($gradingIds)),
            $gradingIds
        );

        $itemsByGrading = [];
        foreach ($items as $item) {
            $itemsByGrading[$item['grading_id']][] = $item;
        }

        $rows = [];
        foreach ($gradings as $index => $grading) {
            $gradeWeights = [];
            $totals = ['totalGross' => 0.0, 'totalTare' => 0.0, 'totalNett' => 0.0];

            foreach ($itemsByGrading[$grading['id']] ?? [] as $item) {
                $key = $item['product_name'] . '|' . $item['grade_name'];
                $gradeWeights[$key] = ($gradeWeights[$key] ?? 0) + (float)$item['nett_weight'];
                $totals['totalGross'] += (float)$item['gross_weight'];
                $totals['totalTare'] += (float)$item['tare_weight'];
                $totals['totalNett'] += (float)$item['nett_weight'];
            }

            $rows[] = $grading + $totals + ['count' => $index + 1, 'gradeWeights' => $gradeWeights];
        }

        return $rows;
    }

    /**
     * Grading header (with company details) and items for the print slip
     */
    public function getPrintData(int $id): ?array
    {
        $grading = $this->findGrading($id);
        if (!$grading) {
            return null;
        }

        $header = $this->fetchOne(
            "SELECT g.*, co.name AS company_name, co.address AS company_address, co.address2 AS company_address2,
                    co.address3 AS company_address3, co.address4 AS company_address4, co.company_logo,
                    IFNULL(c.category_name, '') AS category_name, IFNULL(l.locations, '') AS location_name, IFNULL(u.name, '') AS created_by_name
             FROM grading g
             LEFT JOIN companies co ON g.company = co.id
             LEFT JOIN categories c ON g.product_category = c.id
             LEFT JOIN locations l ON g.location = l.id
             LEFT JOIN users u ON g.created_by = u.id
             WHERE g.id = ?",
            'i',
            [$id]
        );

        $items = $this->fetchAll(
            "SELECT gi.product_id, gi.to_grade, gi.gross_weight, gi.tare_weight, gi.nett_weight, gi.photo_path, p.product_name,
                    CASE WHEN gi.to_grade = 'REJ' THEN 'REJ' ELSE COALESCE(gr.units, gi.to_grade) END AS grade_name
             FROM grading_items gi
             LEFT JOIN products p ON gi.product_id = p.id
             LEFT JOIN grades gr ON gi.to_grade = gr.id
             WHERE gi.grading_id = ? AND gi.deleted = '0'
             ORDER BY p.product_name ASC, grade_name ASC",
            'i',
            [$id]
        );

        return ['header' => $header, 'items' => $items];
    }

    /**
     * Grading row within the session company (SADMIN: any)
     */
    private function findGrading(int $id): ?array
    {
        $where = "id = ? AND deleted = 0";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types, 'company');

        return $this->fetchOne("SELECT * FROM grading WHERE $where", $types, $params);
    }

    /**
     * Location / category / products / grades must belong to the record's company
     */
    private function validate(array $header, array $items, int $company): ?string
    {
        if (!$this->fetchOne("SELECT id FROM locations WHERE id = ? AND customer = ? AND deleted = '0'", 'ii', [$header['location'], $company])) {
            return 'Invalid location';
        }

        if ($header['product_category'] !== null
            && !$this->fetchOne("SELECT id FROM categories WHERE id = ? AND customer = ? AND deleted = '0'", 'ii', [$header['product_category'], $company])) {
            return 'Invalid category';
        }

        $productIds = array_values(array_unique(array_column($items, 'product')));
        if (!empty($productIds)) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total FROM products WHERE customer = ? AND id IN (" . $this->placeholders(count($productIds)) . ")",
                'i' . str_repeat('i', count($productIds)),
                array_merge([$company], $productIds)
            );
            if ((int)$row['total'] !== count($productIds)) {
                return 'Invalid product';
            }
        }

        $gradeIds = [];
        foreach ($items as $item) {
            if ($item['grade'] !== self::REJECT_GRADE) {
                $gradeIds[] = (int)$item['grade'];
            }
        }
        $gradeIds = array_values(array_unique($gradeIds));
        if (!empty($gradeIds)) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total FROM grades WHERE customer = ? AND id IN (" . $this->placeholders(count($gradeIds)) . ")",
                'i' . str_repeat('i', count($gradeIds)),
                array_merge([$company], $gradeIds)
            );
            if ((int)$row['total'] !== count($gradeIds)) {
                return 'Invalid grade';
            }
        }

        return null;
    }

    /**
     * Non-reject nett weight per product + grade for stock balance
     */
    private function groupedWeights(int $gradingId): array
    {
        $rows = $this->fetchAll(
            "SELECT product_id, to_grade, SUM(nett_weight) AS net FROM grading_items
             WHERE grading_id = ? AND deleted = '0' AND to_grade <> 'REJ' GROUP BY product_id, to_grade",
            'i',
            [$gradingId]
        );

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['product_id'] . '_' . $row['to_grade']] = ['product' => $row['product_id'], 'grade' => $row['to_grade'], 'net' => (float)$row['net']];
        }

        return $grouped;
    }

    /**
     * G + start date (Ymd) + first free running number for the company (min 4 digits)
     */
    private function nextGradingNo(string $startDate): string
    {
        return $this->nextRunningNo('grading', 'grading_no', 'G' . date('Ymd', strtotime($startDate)), $this->company);
    }

    /**
     * Shared list / report filters ('', '-' or 'all' means no filter)
     */
    private function applyFilters(string &$where, array &$params, string &$types, array $filters, string $dateColumn): void
    {
        $value = function (string $key) use ($filters): string {
            $v = trim((string)($filters[$key] ?? ''));
            return in_array($v, ['-', 'all'], true) ? '' : $v;
        };

        if (($from = \DateTime::createFromFormat('d/m/Y', $value('fromDate'))) !== false) {
            $where .= " AND g.$dateColumn >= ?";
            $params[] = $from->format('Y-m-d 00:00:00');
            $types .= 's';
        }

        if (($to = \DateTime::createFromFormat('d/m/Y', $value('toDate'))) !== false) {
            $where .= " AND g.$dateColumn <= ?";
            $params[] = $to->format('Y-m-d 23:59:59');
            $types .= 's';
        }

        if ($value('location') !== '') {
            $where .= " AND g.location = ?";
            $params[] = (int)$value('location');
            $types .= 'i';
        }

        // Gradings containing a product in the chosen category, otherwise in the user's accessible categories
        $this->applyItemCategoryFilter(
            $where,
            $params,
            $types,
            "g.id IN (SELECT gi.grading_id FROM grading_items gi INNER JOIN products p ON p.id = gi.product_id WHERE gi.deleted = 0 AND p.deleted = '0'",
            $value('category'),
            $this->userModuleAccess,
            self::CATEGORY_MODULES
        );
    }
}
