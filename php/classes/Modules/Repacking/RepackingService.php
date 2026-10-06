<?php
namespace App\Modules\Repacking;

use App\Core\BaseService;

require_once __DIR__ . '/../../../services/stockManagementService.php';

/**
 * Repacking: move stock (raw_stock_balance, per product/grade/type) from one source product
 * into one or more Repack products, kept as records that can be edited or deleted.
 */
class RepackingService extends BaseService
{
    private const STOCK_MODULE = 'repacking';

    private string $module;

    public function __construct(\mysqli $db, int $company, int $user, string $role, string $module)
    {
        parent::__construct($db, $company, $user, $role);
        $this->module = $module;
    }

    /**
     * Source and target products (with their grades) for the form
     */
    public function getOptions(): array
    {
        $sourceSelect = "SELECT p.id, p.product_name, p.category AS category_id, c.category_name
            FROM products p";
        $sourceWhere = "p.deleted = 0";

        $targetSelect = "SELECT p.id, p.product_name, p.category AS category_id, c.category_name
            FROM products p";
        $targetWhere = "p.deleted = 0";

        $sources = $this->loadProducts($sourceSelect, $sourceWhere);
        $targets = $this->loadProducts($targetSelect, $targetWhere);

        // Grades (Local and Export) per product; the form filters them by the selected type
        $grades = $this->getGradesFor(array_merge(array_column($sources, 'id'), array_column($targets, 'id')));
        foreach ($sources as &$product) {
            $product['grades'] = $grades[$product['id']] ?? [];
        }
        unset($product);
        foreach ($targets as &$product) {
            $product['grades'] = $grades[$product['id']] ?? [];
        }
        unset($product);

        return ['sources' => $sources, 'targets' => $targets];
    }

    /**
     * Get paginated records for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search, array $filters = []): array
    {
        $sortColumns = [
            'repacking_no' => 'r.repacking_no',
            'repacking_date' => 'r.repacking_date',
            'source_product_name' => 'sp.product_name',
            'source_weight' => 'r.source_weight',
            'type' => 'r.type',
            'created_by_name' => 'u.name'
        ];
        $orderExpr = $sortColumns[$orderColumn] ?? 'r.repacking_date';
        $orderDir = strtolower($orderDir) === 'asc' ? 'ASC' : 'DESC';

        $from = "repacking r
            LEFT JOIN products sp ON r.source_product = sp.id
            LEFT JOIN grades sg ON r.source_grade = sg.id
            LEFT JOIN users u ON r.created_by = u.id";
        $where = "r.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'r.company');

        $total = $this->countRows($from, $where, $types, $params);

        $this->applyFilters($where, $params, $types, $filters);

        if ($search !== '') {
            $where .= " AND (r.repacking_no LIKE ? OR sp.product_name LIKE ? OR EXISTS (
                SELECT 1 FROM repacking_items ri INNER JOIN products tp ON ri.product_id = tp.id
                WHERE ri.repacking_id = r.id AND ri.deleted = 0 AND tp.product_name LIKE ?))";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $types .= 'sss';
        }

        $filtered = $this->countRows($from, $where, $types, $params);

        $sql = "SELECT r.id, r.repacking_no, r.repacking_date, r.type, r.source_weight, sp.product_name AS source_product_name, sg.units AS source_grade_name, u.name AS created_by_name
            FROM $from WHERE $where ORDER BY $orderExpr $orderDir, r.id DESC LIMIT ?, ?";
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new \Exception('Failed to prepare repacking list query');
        }
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new \Exception('Failed to load repacking records');
        }
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $items = $this->getItemsFor(array_column($rows, 'id'));

        $data = [];
        foreach ($rows as $row) {
            $targets = [];
            foreach ($items[$row['id']] ?? [] as $item) {
                $targets[] = $this->productLabel($item['product_name'], $item['grade_name']) . ' (' . $this->formatWeight($item['weight']) . ' kg)';
            }

            $data[] = [
                'id' => $row['id'],
                'repacking_no' => $row['repacking_no'],
                'repacking_date' => date('d/m/Y', strtotime($row['repacking_date'])),
                'type' => $row['type'],
                'source_product_name' => $this->productLabel($row['source_product_name'], $row['source_grade_name']),
                'source_weight' => $this->formatWeight($row['source_weight']),
                'targets' => implode(', ', $targets),
                'created_by_name' => $row['created_by_name']
            ];
        }

        return ['totalRecords' => $total, 'totalFiltered' => $filtered, 'data' => $data];
    }

    /**
     * Dashboard summary: repacked weight (total / Local / Export) and a breakdown of
     * source product + grade into the target products + grades it was repacked into
     */
    public function getDashboard(array $filters): array
    {
        $where = "r.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'r.company');
        $this->applyFilters($where, $params, $types, $filters);

        $stmt = $this->db->prepare("SELECT r.id, r.type, r.source_product, r.source_grade, r.source_weight, sp.product_name AS source_product_name, sg.units AS source_grade_name
            FROM repacking r
            LEFT JOIN products sp ON r.source_product = sp.id
            LEFT JOIN grades sg ON r.source_grade = sg.id
            WHERE $where");
        if (!$stmt) {
            throw new \Exception('Failed to prepare repacking dashboard query');
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $items = $this->getItemsFor(array_column($rows, 'id'));

        $summary = ['record_count' => count($rows), 'total_weight' => 0.0, 'local_weight' => 0.0, 'export_weight' => 0.0];
        $breakdown = [];
        foreach ($rows as $row) {
            $weight = (float)$row['source_weight'];
            $summary['total_weight'] += $weight;
            $summary[$row['type'] === 'Export' ? 'export_weight' : 'local_weight'] += $weight;

            $sourceKey = $row['source_product'] . ':' . $row['source_grade'];
            if (!isset($breakdown[$sourceKey])) {
                $breakdown[$sourceKey] = [
                    'name' => $this->productLabel($row['source_product_name'], $row['source_grade_name']),
                    'total_weight' => 0.0,
                    'record_count' => 0,
                    'targets' => []
                ];
            }
            $breakdown[$sourceKey]['total_weight'] += $weight;
            $breakdown[$sourceKey]['record_count']++;

            foreach ($items[$row['id']] ?? [] as $item) {
                $targetKey = $item['product_id'] . ':' . $item['grade_id'];
                if (!isset($breakdown[$sourceKey]['targets'][$targetKey])) {
                    $breakdown[$sourceKey]['targets'][$targetKey] = [
                        'name' => $this->productLabel($item['product_name'], $item['grade_name']),
                        'total_weight' => 0.0
                    ];
                }
                $breakdown[$sourceKey]['targets'][$targetKey]['total_weight'] += (float)$item['weight'];
            }
        }

        $byWeight = function ($a, $b) {
            return $b['total_weight'] <=> $a['total_weight'];
        };
        foreach ($breakdown as &$source) {
            $source['targets'] = array_values($source['targets']);
            usort($source['targets'], $byWeight);
        }
        unset($source);
        $breakdown = array_values($breakdown);
        usort($breakdown, $byWeight);

        return ['summary' => $summary, 'breakdown' => $breakdown];
    }

    /**
     * Get single record with its target items
     */
    public function getById(int $id): ?array
    {
        $where = "r.id = ? AND r.deleted = 0";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types, 'r.company');

        $stmt = $this->db->prepare("SELECT r.id, r.repacking_no, r.repacking_date, r.type, r.source_category, r.target_category, r.source_product, r.source_grade, r.source_weight, r.company,
                sp.product_name AS source_product_name, sp.category AS source_category_id, c.category_name AS source_category_name
            FROM repacking r
            LEFT JOIN products sp ON r.source_product = sp.id
            LEFT JOIN categories c ON sp.category = c.id
            WHERE $where");
        if (!$stmt) {
            throw new \Exception('Failed to prepare repacking query');
        }
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new \Exception('Failed to load repacking record');
        }
        $record = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$record) {
            return null;
        }

        $items = $this->getItemsFor([$id])[$id] ?? [];
        // Grades of the record's products, for when they are no longer in the form's product lists
        $grades = $this->getGradesFor(array_merge([$record['source_product']], array_column($items, 'product_id')));

        $record['repacking_date'] = date('d/m/Y', strtotime($record['repacking_date']));
        $record['source_weight'] = $this->formatWeight($record['source_weight']);
        $record['source_grades'] = $grades[$record['source_product']] ?? [];
        $record['items'] = [];
        foreach ($items as $item) {
            $record['items'][] = [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'category_id' => $item['category_id'],
                'category_name' => $item['category_name'],
                'grade_id' => $item['grade_id'],
                'grades' => $grades[$item['product_id']] ?? [],
                'weight' => $this->formatWeight($item['weight'])
            ];
        }

        return $record;
    }

    /**
     * Create repacking: deduct source stock, add target stock, save record
     */
    public function create(string $repackingDate, string $type, int $sourceCategory, int $targetCategory, int $sourceProduct, int $sourceGrade, float $sourceWeight, array $items): array
    {
        $this->db->begin_transaction();

        try {
            $date = $this->parseDate($repackingDate);
            $company = $this->validate($type, $sourceProduct, $sourceGrade, $sourceWeight, $items);
            $sourceCategory = $this->checkCategory($sourceCategory, $company);
            $targetCategory = $this->checkCategory($targetCategory, $company);

            $repackingNo = $this->generateRepackingNo($company);

            $stmt = $this->db->prepare("INSERT INTO repacking (repacking_no, repacking_date, source_category, target_category, source_product, source_grade, source_weight, type, company, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssiiiidsii', $repackingNo, $date, $sourceCategory, $targetCategory, $sourceProduct, $sourceGrade, $sourceWeight, $type, $company, $this->user);
            $stmt->execute();
            $repackingId = (int)$stmt->insert_id;
            $stmt->close();

            $this->insertItems($repackingId, $items);

            $newLines = $this->stockLines($company, $type, $sourceProduct, $sourceGrade, $sourceWeight, $items);
            $this->checkStock([], $newLines);
            $this->applyStock($repackingId, [], $newLines);

            $this->db->commit();
        } catch (\DomainException $e) {
            $this->db->rollback();
            return ['status' => 'failed', 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('RepackingService::create - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to save repacking'];
        }

        return ['status' => 'success', 'message' => 'Repacking successful'];
    }

    /**
     * Update repacking: reverse the original stock movement, then apply the new one
     */
    public function update(int $id, string $repackingDate, string $type, int $sourceCategory, int $targetCategory, int $sourceProduct, int $sourceGrade, float $sourceWeight, array $items): array
    {
        $this->db->begin_transaction();

        try {
            $date = $this->parseDate($repackingDate);
            $existing = $this->lockRecord($id);

            $company = $this->validate($type, $sourceProduct, $sourceGrade, $sourceWeight, $items);
            $sourceCategory = $this->checkCategory($sourceCategory, $company);
            $targetCategory = $this->checkCategory($targetCategory, $company);

            $oldLines = $this->recordStockLines($existing);
            $newLines = $this->stockLines($company, $type, $sourceProduct, $sourceGrade, $sourceWeight, $items);
            $this->checkStock($oldLines, $newLines);
            $this->applyStock($id, $oldLines, $newLines);

            $stmt = $this->db->prepare("UPDATE repacking SET repacking_date = ?, source_category = ?, target_category = ?, source_product = ?, source_grade = ?, source_weight = ?, type = ?, company = ?, modified_by = ? WHERE id = ?");
            $stmt->bind_param('siiiidsiii', $date, $sourceCategory, $targetCategory, $sourceProduct, $sourceGrade, $sourceWeight, $type, $company, $this->user, $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $this->db->prepare("UPDATE repacking_items SET deleted = 1, modified_by = ? WHERE repacking_id = ? AND deleted = 0");
            $stmt->bind_param('ii', $this->user, $id);
            $stmt->execute();
            $stmt->close();

            $this->insertItems($id, $items);

            $this->db->commit();
        } catch (\DomainException $e) {
            $this->db->rollback();
            return ['status' => 'failed', 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('RepackingService::update - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to update repacking'];
        }

        return ['status' => 'success', 'message' => 'Updated Successfully!!'];
    }

    /**
     * Delete repacking: reverse its stock movement and soft delete the record
     */
    public function delete(int $id): array
    {
        $this->db->begin_transaction();

        try {
            $existing = $this->lockRecord($id);

            $this->checkStock($this->recordStockLines($existing), []);
            $this->assertStockResult(processDeleteRawStock($this->db, $id, self::STOCK_MODULE, $existing['company'], $this->user, $existing['type']));

            $stmt = $this->db->prepare("UPDATE repacking SET deleted = 1, modified_by = ? WHERE id = ?");
            $stmt->bind_param('ii', $this->user, $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $this->db->prepare("UPDATE repacking_items SET deleted = 1, modified_by = ? WHERE repacking_id = ? AND deleted = 0");
            $stmt->bind_param('ii', $this->user, $id);
            $stmt->execute();
            $stmt->close();

            $this->db->commit();
        } catch (\DomainException $e) {
            $this->db->rollback();
            return ['status' => 'failed', 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('RepackingService::delete - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to delete repacking'];
        }

        return ['status' => 'success', 'message' => 'Deleted'];
    }

    /**
     * Validate products, company and weights. Returns the record's company.
     */
    private function validate(string $type, int $sourceProduct, int $sourceGrade, float $sourceWeight, array $items): int
    {
        if (!in_array($type, ['Local', 'Export'], true)) {
            throw new \DomainException('Invalid type');
        }

        if (!$sourceProduct || !$sourceGrade || $sourceWeight <= 0 || empty($items)) {
            throw new \DomainException('Please fill in all the fields');
        }

        $totalTarget = 0;
        foreach ($items as $item) {
            if (!$item['product_id'] || !$item['grade_id'] || $item['weight'] <= 0) {
                throw new \DomainException('Please fill in all the fields');
            }
            $totalTarget += $item['weight'];
        }

        if (round($totalTarget * 100) !== round($sourceWeight * 100)) {
            throw new \DomainException('Total target weight (' . $this->formatWeight($totalTarget) . ' kg) must equal source weight (' . $this->formatWeight($sourceWeight) . ' kg).');
        }

        $productIds = array_unique(array_merge([$sourceProduct], array_column($items, 'product_id')));
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $this->db->prepare("SELECT p.id, p.customer
            FROM products p
            WHERE p.deleted = 0 AND p.id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($productIds)), ...$productIds);
        $stmt->execute();
        $products = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $products[(int)$row['id']] = $row;
        }
        $stmt->close();

        $source = $products[$sourceProduct] ?? null;
        if (!$source) {
            throw new \DomainException('Invalid source product');
        }

        $company = (int)$source['customer'];
        if (!$this->isSuperAdmin() && $company !== $this->company) {
            throw new \DomainException('Invalid source product');
        }

        foreach ($items as $item) {
            if ($item['product_id'] === $sourceProduct) {
                throw new \DomainException('Source product cannot be a target product');
            }

            $target = $products[$item['product_id']] ?? null;
            if (!$target || (int)$target['customer'] !== $company) {
                throw new \DomainException('Invalid target product');
            }
        }

        // Each grade must be an active grade of its product for the selected type
        $validGrades = [];
        foreach ($this->getGradesFor($productIds) as $productId => $productGrades) {
            foreach ($productGrades as $grade) {
                if ($grade['type'] === $type) {
                    $validGrades[$productId . ':' . $grade['grade_id']] = true;
                }
            }
        }

        if (!isset($validGrades[$sourceProduct . ':' . $sourceGrade])) {
            throw new \DomainException('Invalid source grade');
        }
        foreach ($items as $item) {
            if (!isset($validGrades[$item['product_id'] . ':' . $item['grade_id']])) {
                throw new \DomainException('Invalid target grade');
            }
        }

        return $company;
    }

    /**
     * Convert the form date (DD/MM/YYYY) to Y-m-d
     */
    private function parseDate(string $value): string
    {
        $date = \DateTime::createFromFormat('d/m/Y', $value);
        if (!$date || $date->format('d/m/Y') !== $value) {
            throw new \DomainException('Please fill in all the fields');
        }

        return $date->format('Y-m-d');
    }

    /**
     * Generate repacking_no: RP + YYYYMMDD + 4-digit counter
     */
    private function generateRepackingNo(int $company): string
    {
        $row = $this->fetchOne("SELECT COUNT(*) AS total FROM repacking WHERE company = ? AND created_datetime >= ?", 'is', [$company, date('Y-m-d 00:00:00')]);

        // repacking_no is unique across all companies, so the free-number check is not company scoped
        return $this->nextRunningNo('repacking', 'repacking_no', 'RP' . date('Ymd'), null, (int)($row['total'] ?? 0) + 1);
    }

    /**
     * Lock an active record (company scoped) with its items for edit/delete
     */
    private function lockRecord(int $id): array
    {
        $where = "id = ? AND deleted = 0";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types, 'company');

        $stmt = $this->db->prepare("SELECT id, source_product, source_grade, source_weight, type, company FROM repacking WHERE $where FOR UPDATE");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$record) {
            throw new \DomainException('Record not found');
        }

        $stmt = $this->db->prepare("SELECT product_id, grade_id, weight FROM repacking_items WHERE repacking_id = ? AND deleted = 0");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $record['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $record;
    }

    /**
     * Stock lines of a save: source goes OUT, targets come IN (summed per product + grade)
     */
    private function stockLines(int $company, string $type, int $sourceProduct, int $sourceGrade, float $sourceWeight, array $items): array
    {
        $lines = [];
        $this->addStockLine($lines, $company, $type, $sourceProduct, $sourceGrade, 'OUTGOING', $sourceWeight);
        foreach ($items as $item) {
            $this->addStockLine($lines, $company, $type, (int)$item['product_id'], (int)$item['grade_id'], 'INCOMING', (float)$item['weight']);
        }

        return $lines;
    }

    private function recordStockLines(array $record): array
    {
        return $this->stockLines((int)$record['company'], (string)$record['type'], (int)$record['source_product'], (int)$record['source_grade'], (float)$record['source_weight'], $record['items']);
    }

    private function addStockLine(array &$lines, int $company, string $type, int $productId, int $gradeId, string $status, float $weight): void
    {
        $key = implode(':', [$company, $type, $productId, $gradeId, $status]);
        if (!isset($lines[$key])) {
            $lines[$key] = ['company' => $company, 'type' => $type, 'product_id' => $productId, 'grade_id' => $gradeId, 'status' => $status, 'weight' => 0.0];
        }
        $lines[$key]['weight'] += $weight;
    }

    /**
     * Make sure no raw_stock_balance goes below zero after undoing $oldLines and applying $newLines
     */
    private function checkStock(array $oldLines, array $newLines): void
    {
        $changes = [];
        foreach ([[$oldLines, -1], [$newLines, 1]] as [$lines, $sign]) {
            foreach ($lines as $line) {
                $key = implode(':', [$line['company'], $line['type'], $line['product_id'], $line['grade_id']]);
                if (!isset($changes[$key])) {
                    $changes[$key] = $line + ['change' => 0.0, 'deduct' => 0.0];
                }
                $direction = $line['status'] === 'INCOMING' ? 1 : -1;
                $changes[$key]['change'] += $sign * $direction * $line['weight'];
                if ($sign === 1 && $line['status'] === 'OUTGOING') {
                    $changes[$key]['deduct'] += $line['weight'];
                }
            }
        }

        foreach ($changes as $change) {
            if (round($change['change'] * 100) >= 0) {
                continue;
            }

            $grade = (string)$change['grade_id'];
            $stmt = $this->db->prepare("SELECT balance FROM raw_stock_balance WHERE product_id = ? AND grade = ? AND type = ? AND company = ? AND deleted = 0 LIMIT 1 FOR UPDATE");
            $stmt->bind_param('issi', $change['product_id'], $grade, $change['type'], $change['company']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $balance = $row ? (float)$row['balance'] : 0;
            if (round(($balance + $change['change']) * 100) >= 0) {
                continue;
            }

            $label = $this->productLabel($this->getProductName($change['product_id']), $this->getGradeName($change['grade_id']));
            if ($change['deduct'] > 0) {
                // Available = balance plus whatever this save gives back first
                $available = $balance + $change['change'] + $change['deduct'];
                throw new \DomainException('Insufficient stock for ' . $label . '. Available: ' . $this->formatWeight($available) . ' kg');
            }
            throw new \DomainException('Cannot reverse repacking: ' . $label . ' only has ' . $this->formatWeight($balance) . ' kg left (needs ' . $this->formatWeight(-$change['change']) . ' kg). The repacked stock may already be used.');
        }
    }

    /**
     * Post stock through stockManagementService::processRawStock.
     * Lines only in the old save are edited down to 0 first, so the latest movement
     * per product/grade/type always belongs to the current save (used by processDeleteRawStock).
     */
    private function applyStock(int $recordId, array $oldLines, array $newLines): void
    {
        foreach ($oldLines as $key => $old) {
            if (!isset($newLines[$key])) {
                $this->postRawStock($recordId, $old, 0, true, $old['weight']);
            }
        }

        foreach ($newLines as $key => $new) {
            $isEdit = isset($oldLines[$key]);
            $this->postRawStock($recordId, $new, $new['weight'], $isEdit, $isEdit ? $oldLines[$key]['weight'] : 0);
        }
    }

    private function postRawStock(int $recordId, array $line, float $newValue, bool $isEdit, float $beforeValue): void
    {
        $this->assertStockResult(processRawStock(
            $this->db,
            $line['product_id'],
            (string)$line['grade_id'],
            $line['company'],
            $newValue,
            $this->user,
            $line['status'],
            $isEdit,
            $beforeValue,
            $recordId,
            self::STOCK_MODULE,
            null,
            null,
            $line['type']
        ));
    }

    private function assertStockResult(array $result): void
    {
        if (($result['status'] ?? '') !== 'success') {
            throw new \RuntimeException('Stock update failed: ' . ($result['message'] ?? ''));
        }
    }

    /**
     * Selected category (optional): must be an active category of the record's company. Returns null when not selected.
     */
    private function checkCategory(int $categoryId, int $company): ?int
    {
        if (!$categoryId) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT customer FROM categories WHERE id = ? AND deleted = 0");
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || (int)$row['customer'] !== $company) {
            throw new \DomainException('Invalid category');
        }

        return $categoryId;
    }

    /**
     * Shared list / dashboard filters (expects r = repacking, sp = source product)
     */
    private function applyFilters(string &$where, array &$params, string &$types, array $filters): void
    {
        $fromDate = \DateTime::createFromFormat('d/m/Y', $filters['fromDate'] ?? '');
        if ($fromDate) {
            $where .= " AND r.repacking_date >= ?";
            $params[] = $fromDate->format('Y-m-d');
            $types .= 's';
        }

        $toDate = \DateTime::createFromFormat('d/m/Y', $filters['toDate'] ?? '');
        if ($toDate) {
            $where .= " AND r.repacking_date <= ?";
            $params[] = $toDate->format('Y-m-d');
            $types .= 's';
        }

        if (in_array($filters['type'] ?? '', ['Local', 'Export'], true)) {
            $where .= " AND r.type = ?";
            $params[] = $filters['type'];
            $types .= 's';
        }

        // Category matches the source product or any target product
        if (!empty($filters['category'])) {
            $where .= " AND (sp.category = ? OR EXISTS (
                SELECT 1 FROM repacking_items ri INNER JOIN products tp ON ri.product_id = tp.id
                WHERE ri.repacking_id = r.id AND ri.deleted = 0 AND tp.category = ?))";
            $params[] = $filters['category'];
            $params[] = $filters['category'];
            $types .= 'ii';
        }

        if (!empty($filters['sourceProduct'])) {
            $where .= " AND r.source_product = ?";
            $params[] = $filters['sourceProduct'];
            $types .= 'i';
        }

        if (!empty($filters['targetProduct'])) {
            $where .= " AND EXISTS (SELECT 1 FROM repacking_items ri WHERE ri.repacking_id = r.id AND ri.deleted = 0 AND ri.product_id = ?)";
            $params[] = $filters['targetProduct'];
            $types .= 'i';
        }
    }

    private function getGradeName(int $gradeId): ?string
    {
        $stmt = $this->db->prepare("SELECT units FROM grades WHERE id = ?");
        $stmt->bind_param('i', $gradeId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row['units'] ?? null;
    }

    private function insertItems(int $repackingId, array $items): void
    {
        $stmt = $this->db->prepare("INSERT INTO repacking_items (repacking_id, product_id, grade_id, weight, created_by) VALUES (?, ?, ?, ?, ?)");
        foreach ($items as $item) {
            $productId = $item['product_id'];
            $gradeId = $item['grade_id'];
            $weight = $item['weight'];
            $stmt->bind_param('iiidi', $repackingId, $productId, $gradeId, $weight, $this->user);
            $stmt->execute();
        }
        $stmt->close();
    }

    /**
     * Active items for the given records, grouped by repacking_id
     */
    private function getItemsFor(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT ri.repacking_id, ri.product_id, ri.grade_id, ri.weight, p.product_name, p.category AS category_id, c.category_name, g.units AS grade_name
            FROM repacking_items ri
            LEFT JOIN products p ON ri.product_id = p.id
            LEFT JOIN categories c ON p.category = c.id
            LEFT JOIN grades g ON ri.grade_id = g.id
            WHERE ri.deleted = 0 AND ri.repacking_id IN ($placeholders)
            ORDER BY ri.id ASC");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $grouped = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $grouped[$row['repacking_id']][] = $row;
        }
        $stmt->close();

        return $grouped;
    }

    /**
     * Load product options limited to the categories shown in the page's category dropdowns:
     * for non-SADMIN, the company's categories of the current module, or all company
     * categories when the module has none.
     */
    private function loadProducts(string $select, string $where): array
    {
        if ($this->isSuperAdmin()) {
            return $this->queryProducts("$select INNER JOIN categories c ON p.category = c.id WHERE $where AND c.deleted = 0 ORDER BY p.product_name ASC", '', []);
        }

        if ($this->hasModuleCategories()) {
            return $this->queryProducts(
                "$select INNER JOIN categories c ON p.category = c.id WHERE $where AND p.customer = ? AND c.customer = ? AND c.module = ? AND c.deleted = 0 ORDER BY p.product_name ASC",
                'iis',
                [$this->company, $this->company, $this->module]
            );
        }

        return $this->queryProducts(
            "$select INNER JOIN categories c ON p.category = c.id WHERE $where AND p.customer = ? AND c.customer = ? AND c.deleted = 0 ORDER BY p.product_name ASC",
            'ii',
            [$this->company, $this->company]
        );
    }

    private function hasModuleCategories(): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM categories WHERE deleted = 0 AND customer = ? AND module = ? LIMIT 1");
        $stmt->bind_param('is', $this->company, $this->module);
        $stmt->execute();
        $exists = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();

        return $exists;
    }

    private function queryProducts(string $sql, string $types, array $params): array
    {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new \Exception('Failed to prepare product options query');
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    /**
     * Active grades (Local and Export) for the given products, grouped by product_id
     */
    private function getGradesFor(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if (empty($productIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $this->db->prepare("SELECT pg.product_id, pg.grade_id, pg.type, g.units AS grade_name
            FROM product_grades pg
            INNER JOIN grades g ON pg.grade_id = g.id
            WHERE pg.deleted = 0 AND pg.product_id IN ($placeholders)
            ORDER BY g.units ASC");
        $stmt->bind_param(str_repeat('i', count($productIds)), ...$productIds);
        $stmt->execute();
        $grouped = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $grouped[$row['product_id']][] = [
                'grade_id' => $row['grade_id'],
                'grade_name' => $row['grade_name'],
                'type' => $row['type']
            ];
        }
        $stmt->close();

        return $grouped;
    }

    private function productLabel(?string $productName, ?string $gradeName): string
    {
        return $gradeName ? $productName . ' - ' . $gradeName : (string)$productName;
    }

    private function getProductName(int $productId): string
    {
        $stmt = $this->db->prepare("SELECT product_name FROM products WHERE id = ?");
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row['product_name'] ?? ('#' . $productId);
    }

    private function formatWeight($weight): string
    {
        return rtrim(rtrim(number_format((float)$weight, 2, '.', ''), '0'), '.');
    }
}
