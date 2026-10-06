<?php
namespace App\Services;

require_once __DIR__ . '/../../uploadFileHelper.php';
require_once __DIR__ . '/../../services/stockManagementService.php';

/**
 * Packaging batches (packaging_batches + packaging_batch_items) with grading / box stock updates.
 * Non-SADMIN users are scoped to the session company and to their accessible wholesale / processing categories.
 */
class PackagingBatchService extends BaseService
{
    public const LABELS = ['!', '@', '#', '$', '%', '^', '&', '*', '(', ')'];
    private const TYPES = ['Local', 'Export'];
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
     * Dropdown data for the filter / entry / shipment forms plus the photo and preset label feature flags
     */
    public function getLookups(): array
    {
        $modules = "'" . implode("','", self::CATEGORY_MODULES) . "'";
        $gradeSql = "SELECT DISTINCT g.id, g.units, pg.product_id, IFNULL(pg.type, 'Local') AS grade_type FROM grades g
                     LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id
                     WHERE g.deleted = '0' AND pg.deleted = '0'";

        if ($this->isSuperAdmin()) {
            return [
                'categories' => $this->fetchAll("SELECT id, category_name FROM categories WHERE deleted = '0' AND module IN ($modules) ORDER BY category_name ASC"),
                'products' => $this->fetchAll("SELECT id, product_name, category FROM products WHERE deleted = '0' ORDER BY product_name ASC"),
                'grades' => $this->fetchAll("$gradeSql ORDER BY p.product_name ASC, g.units ASC"),
                'locations' => $this->fetchAll("SELECT id, locations FROM locations WHERE deleted = '0' ORDER BY locations ASC"),
                'productionLines' => $this->fetchAll("SELECT id, production_line FROM production_lines WHERE deleted = '0' ORDER BY production_line ASC"),
                'packagings' => $this->fetchAll("SELECT id, packaging_name, weight FROM packaging WHERE deleted = '0' AND packaging_type = 'Original' ORDER BY packaging_name ASC"),
                'suppliers' => $this->fetchAll("SELECT id, supplier_name FROM supplies WHERE deleted = '0' ORDER BY supplier_name ASC"),
                'customers' => $this->fetchAll("SELECT id, customer_name FROM customers WHERE deleted = '0' ORDER BY customer_name ASC"),
                'shipmentTypes' => $this->fetchAll("SELECT id, shipment_type FROM shipment_types WHERE deleted = '0' ORDER BY shipment_type ASC"),
                'allowPhoto' => true,
                'allowPresetLabel' => true
            ];
        }

        $categoryIds = $this->accessibleCategoryIds();
        $categoryFilter = '';
        $categoryTypes = '';
        if (!empty($categoryIds)) {
            $categoryFilter = " AND c.id IN (" . $this->placeholders(count($categoryIds)) . ")";
            $categoryTypes = str_repeat('i', count($categoryIds));
        }

        $products = $this->fetchAll(
            "SELECT p.id, p.product_name, p.category FROM products p INNER JOIN categories c ON p.category = c.id
             WHERE p.deleted = '0' AND p.customer = ? AND c.module IN ($modules) AND c.deleted = '0'$categoryFilter ORDER BY p.product_name ASC",
            'i' . $categoryTypes,
            array_merge([$this->company], $categoryIds)
        );
        if (empty($products)) {
            $products = $this->fetchAll("SELECT id, product_name, category FROM products WHERE deleted = '0' AND customer = ? ORDER BY product_name ASC", 'i', [$this->company]);
        }

        $features = [];
        foreach ($this->fetchAll("SELECT feature, value FROM company_features WHERE company = ?", 'i', [$this->company]) as $row) {
            $features[$row['feature']] = $row['value'];
        }

        return [
            'categories' => $this->fetchAll(
                "SELECT c.id, c.category_name FROM categories c WHERE c.deleted = '0' AND c.customer = ? AND c.module IN ($modules)$categoryFilter ORDER BY c.category_name ASC",
                'i' . $categoryTypes,
                array_merge([$this->company], $categoryIds)
            ),
            'products' => $products,
            'grades' => $this->fetchAll("$gradeSql AND g.customer = ? ORDER BY p.product_name ASC, g.units ASC", 'i', [$this->company]),
            'locations' => $this->fetchAll("SELECT id, locations FROM locations WHERE deleted = '0' AND customer = ? ORDER BY locations ASC", 'i', [$this->company]),
            'productionLines' => $this->fetchAll("SELECT id, production_line FROM production_lines WHERE deleted = '0' AND customers = ? ORDER BY production_line ASC", 'i', [$this->company]),
            'packagings' => $this->fetchAll("SELECT id, packaging_name, weight FROM packaging WHERE deleted = '0' AND customer = ? AND packaging_type = 'Original' ORDER BY packaging_name ASC", 'i', [$this->company]),
            'suppliers' => $this->fetchAll("SELECT id, supplier_name FROM supplies WHERE deleted = '0' AND customer = ? ORDER BY supplier_name ASC", 'i', [$this->company]),
            'customers' => $this->fetchAll("SELECT id, customer_name FROM customers WHERE deleted = '0' AND customer = ? ORDER BY customer_name ASC", 'i', [$this->company]),
            'shipmentTypes' => $this->fetchAll("SELECT id, shipment_type FROM shipment_types WHERE deleted = '0' AND customer = ? ORDER BY shipment_type ASC", 'i', [$this->company]),
            'allowPhoto' => ($features['include_photo'] ?? 'N') === 'Y',
            'allowPresetLabel' => ($features['packaging_preset_label'] ?? 'N') === 'Y'
        ];
    }

    /**
     * Get paginated packaging batches for DataTables (dates filter on packaging_date)
     */
    public function getList(array $filters, int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $from = "packaging_batches pb LEFT JOIN production_lines pl ON pb.production_line = pl.id LEFT JOIN locations l ON pb.location = l.id";
        $where = "pb.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'pb.company');

        $totalRecords = $this->countRows('packaging_batches pb', $where, $types, $params);

        $this->applyFilters($where, $params, $types, $filters);
        $this->applySearch($where, $params, $types, ['pb.batch_no'], $search);
        $totalFiltered = $this->countRows($from, $where, $types, $params);

        $orderBy = $this->orderBy([
            'batch_no' => 'pb.batch_no',
            'packaging_date' => 'pb.packaging_date',
            'locations' => 'l.locations',
            'production_line' => 'pl.production_line',
            'status' => 'pb.status'
        ], $orderColumn, $orderDir, 'pb.packaging_date');
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $data = $this->fetchAll(
            "SELECT pb.id, pb.batch_no, pl.production_line, l.locations, IFNULL(pb.remarks, '') AS remarks,
                    pb.packaging_date, pb.company, pb.status
             FROM $from WHERE $where ORDER BY $orderBy LIMIT ?, ?",
            $types,
            $params
        );

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Single batch with its items
     */
    public function getById(int $id): ?array
    {
        $batch = $this->findBatch($id);
        if (!$batch) {
            return null;
        }

        $names = $this->fetchOne(
            "SELECT l.locations, pl.production_line FROM packaging_batches pb
             LEFT JOIN locations l ON pb.location = l.id LEFT JOIN production_lines pl ON pb.production_line = pl.id WHERE pb.id = ?",
            'i',
            [$id]
        );

        $items = $this->fetchAll(
            "SELECT pbi.id, pbi.packaging_batch_id, pbi.supplier_id, pbi.category_id, pbi.product_id, IFNULL(p.product_name, '') AS product_name,
                    pbi.grade, IFNULL(g.units, '') AS grade_name, pbi.packaging_size, IFNULL(pkg.packaging_name, '') AS packaging_size_name,
                    IFNULL(pbi.label, '') AS label, pbi.units_per_box, pbi.gross, pbi.tare, pbi.weight,
                    DATE_FORMAT(pbi.packing_time, '%H:%i:%s') AS packing_time, pbi.photo_path, pbi.status
             FROM packaging_batch_items pbi
             LEFT JOIN products p ON pbi.product_id = p.id
             LEFT JOIN grades g ON pbi.grade = g.id
             LEFT JOIN packaging pkg ON pbi.packaging_size = pkg.id
             WHERE pbi.packaging_batch_id = ? AND pbi.deleted = 0",
            'i',
            [$id]
        );

        return [
            'id' => $batch['id'],
            'batch_no' => $batch['batch_no'],
            'packaging_date' => $batch['packaging_date'],
            'location' => $batch['location'],
            'locations' => $names['locations'] ?? '',
            'production_line' => $batch['production_line'],
            'production_lines' => $names['production_line'] ?? '',
            'remarks' => $batch['remarks'],
            'label_remark' => $batch['label_remark'],
            'status' => $batch['status'],
            'company' => $batch['company'],
            'type' => $batch['type'] ?: 'Local',
            'weightDetails' => $items
        ];
    }

    /**
     * Create or update a batch with its items, photos and stock movements (one transaction).
     * $items: [['batchItemId', 'supplier', 'category', 'product', 'grade', 'packaging_size', 'label', 'unit_per_box', 'gross', 'tare', 'time', 'photo' => uploaded file|null], ...]
     */
    public function save(int $id, array $header, array $items): array
    {
        $existing = null;
        if ($id) {
            $existing = $this->findBatch($id);
            if (!$existing) {
                return ['status' => 'failed', 'message' => 'Record not found'];
            }
        }
        $recordCompany = $existing ? (int)$existing['company'] : $this->company;

        if (!in_array($header['type'], self::TYPES, true)) {
            $header['type'] = 'Local';
        }

        $error = $this->validate($header, $items, $recordCompany);
        if ($error !== null) {
            return ['status' => 'failed', 'message' => $error];
        }

        $oldPhotos = [];
        $this->db->begin_transaction();

        try {
            $prevItems = [];
            $existingItems = [];

            if ($existing) {
                if (!$this->updateRow('packaging_batches', $header + ['modified_by' => $this->user], "id = ?", 'i', [$id])) {
                    throw new \Exception('Failed to update packaging batch');
                }
                $prevItems = $this->fetchAll("SELECT * FROM packaging_batch_items WHERE packaging_batch_id = ? AND deleted = 0", 'i', [$id]);
                foreach ($prevItems as $row) {
                    $existingItems[(int)$row['id']] = $row;
                }
            } else {
                $id = $this->insertRow('packaging_batches', $header + [
                    'batch_no' => $this->nextBatchNo($header['packaging_date'], $recordCompany),
                    'company' => $recordCompany,
                    'created_by' => $this->user,
                    'status' => 'pending'
                ]);
                if (!$id) {
                    throw new \Exception('Failed to insert packaging batch');
                }
            }

            $keptItemIds = [];
            $stockItems = [];
            foreach ($items as $item) {
                $itemId = (int)$item['batchItemId'];
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
                    'supplier_id' => $item['supplier'] ?: null,
                    'category_id' => $item['category'],
                    'product_id' => $item['product'],
                    'grade' => $item['grade'],
                    'packaging_size' => $item['packaging_size'],
                    'label' => $item['label'],
                    'units_per_box' => $item['unit_per_box'],
                    'gross' => $item['gross'],
                    'tare' => $item['tare'],
                    'weight' => $item['weight'],
                    'packing_time' => date('Y-m-d') . ' ' . $item['time'],
                    'photo_path' => $photoPath
                ];

                if ($isUpdate) {
                    if (!$this->updateRow('packaging_batch_items', $data, "id = ? AND packaging_batch_id = ?", 'ii', [$itemId, $id])) {
                        throw new \Exception('Failed to update packaging batch item');
                    }
                    $keptItemIds[] = $itemId;
                } elseif (!$this->insertRow('packaging_batch_items', $data + ['packaging_batch_id' => $id, 'status' => 'pending'])) {
                    throw new \Exception('Failed to insert packaging batch item');
                }

                $stockItems[] = ['product' => $item['product'], 'grade' => $item['grade'], 'packaging_size' => $item['packaging_size'], 'weight' => $item['weight']];
            }

            // Items removed from the form
            foreach (array_diff(array_keys($existingItems), $keptItemIds) as $removedId) {
                if (!$this->executeWrite("UPDATE packaging_batch_items SET deleted = '1' WHERE id = ?", 'i', [$removedId])) {
                    throw new \Exception('Failed to remove packaging batch item');
                }
            }

            if ($this->stockEnabled) {
                $result = processPackagingBatch($this->db, $id, $recordCompany, $this->user, $existing ? 'EDIT' : 'CREATE', $stockItems, $prevItems);
                if (($result['status'] ?? '') !== 'success') {
                    throw new \Exception('Failed to update packaging stock: ' . ($result['message'] ?? ''));
                }
            }

            $this->db->commit();
        } catch (\RuntimeException $e) {
            $this->db->rollback();
            return ['status' => 'failed', 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('PackagingBatchService::save - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to save record'];
        }

        foreach ($oldPhotos as $oldPhoto) {
            deleteOldFile($oldPhoto, $this->db, 'photoPath');
        }

        return ['status' => 'success', 'message' => $existing ? 'Updated Successfully!!' : 'Added Successfully!!'];
    }

    /**
     * Soft delete a batch with a reason and reverse its stock
     */
    public function cancel(int $id, string $reason): array
    {
        $batch = $this->findBatch($id);
        if (!$batch) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        $this->db->begin_transaction();

        try {
            if ($this->stockEnabled) {
                $prevItems = $this->fetchAll("SELECT * FROM packaging_batch_items WHERE packaging_batch_id = ? AND deleted = 0", 'i', [$id]);
                $result = processPackagingBatch($this->db, $id, (int)$batch['company'], $this->user, 'DELETE', [], $prevItems);
                if (($result['status'] ?? '') !== 'success') {
                    throw new \Exception('Failed to reverse packaging stock: ' . ($result['message'] ?? ''));
                }
            }

            if (!$this->executeWrite("UPDATE packaging_batches SET deleted = '1', delete_reason = ?, modified_by = ? WHERE id = ?", 'sii', [$reason, $this->user, $id])) {
                throw new \Exception('Failed to delete packaging batch');
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('PackagingBatchService::cancel - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to delete record'];
        }

        return ['status' => 'success', 'message' => 'Deleted'];
    }

    /**
     * Batch header (with company details), items and loading customers for the packing list
     */
    public function getPrintData(int $id): ?array
    {
        if (!$this->findBatch($id)) {
            return null;
        }

        $header = $this->fetchOne(
            "SELECT pb.*, l.locations, pl.production_line AS production_line_name, IFNULL(u.name, '') AS created_by_name,
                    co.name AS company_name, co.address AS company_address, co.address2 AS company_address2, co.address3 AS company_address3,
                    co.phone AS company_phone, co.email AS company_email, co.company_logo
             FROM packaging_batches pb
             LEFT JOIN locations l ON pb.location = l.id
             LEFT JOIN production_lines pl ON pb.production_line = pl.id
             LEFT JOIN users u ON pb.created_by = u.id
             LEFT JOIN companies co ON pb.company = co.id
             WHERE pb.id = ?",
            'i',
            [$id]
        );

        $items = $this->fetchAll(
            "SELECT pbi.*, p.product_name, g.units AS grade_name, pkg.packaging_name, pkg.weight AS pkg_weight
             FROM packaging_batch_items pbi
             LEFT JOIN products p ON pbi.product_id = p.id
             LEFT JOIN grades g ON pbi.grade = g.id
             LEFT JOIN packaging pkg ON pbi.packaging_size = pkg.id
             WHERE pbi.packaging_batch_id = ? AND pbi.deleted = 0",
            'i',
            [$id]
        );

        $customers = [];
        $itemIds = array_map('intval', array_column($items, 'id'));
        if (!empty($itemIds)) {
            $rows = $this->fetchAll(
                "SELECT DISTINCT c.customer_name FROM loading_order_items loi INNER JOIN customers c ON loi.customer_id = c.id
                 WHERE loi.packaging_batch_item_id IN (" . $this->placeholders(count($itemIds)) . ")",
                str_repeat('i', count($itemIds)),
                $itemIds
            );
            $customers = array_column($rows, 'customer_name');
        }

        return ['header' => $header, 'items' => $items, 'customers' => $customers];
    }

    /**
     * Batch row within the session company (SADMIN: any)
     */
    private function findBatch(int $id): ?array
    {
        $where = "id = ? AND deleted = 0";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types, 'company');

        return $this->fetchOne("SELECT * FROM packaging_batches WHERE $where", $types, $params);
    }

    /**
     * Location / production line and every item's lookups must belong to the record's company
     */
    private function validate(array $header, array $items, int $company): ?string
    {
        if (!$this->fetchOne("SELECT id FROM locations WHERE id = ? AND customer = ? AND deleted = '0'", 'ii', [$header['location'], $company])) {
            return 'Invalid location';
        }

        if ($header['production_line'] !== null
            && !$this->fetchOne("SELECT id FROM production_lines WHERE id = ? AND customers = ? AND deleted = '0'", 'ii', [$header['production_line'], $company])) {
            return 'Invalid production line';
        }

        $checks = [
            'supplier' => ['supplies', 'Invalid supplier'],
            'category' => ['categories', 'Invalid category'],
            'product' => ['products', 'Invalid product'],
            'grade' => ['grades', 'Invalid grade'],
            'packaging_size' => ['packaging', 'Invalid packaging size']
        ];
        foreach ($checks as $key => [$table, $message]) {
            $ids = array_values(array_unique(array_filter(array_map('intval', array_column($items, $key)))));
            if (empty($ids)) {
                continue;
            }
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total FROM $table WHERE customer = ? AND id IN (" . $this->placeholders(count($ids)) . ")",
                'i' . str_repeat('i', count($ids)),
                array_merge([$company], $ids)
            );
            if ((int)$row['total'] !== count($ids)) {
                return $message;
            }
        }

        return null;
    }

    /**
     * B + packaging date (Ymd) + first free running number for the company (min 4 digits)
     */
    private function nextBatchNo(string $packagingDate, int $company): string
    {
        $prefix = 'B' . date('Ymd', strtotime($packagingDate));
        $row = $this->fetchOne("SELECT COUNT(*) AS total FROM packaging_batches WHERE company = ? AND batch_no LIKE ?", 'is', [$company, $prefix . '%']);
        $count = (int)($row['total'] ?? 0) + 1;

        do {
            $batchNo = $prefix . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
            $exists = $this->fetchOne("SELECT id FROM packaging_batches WHERE batch_no = ? AND company = ?", 'si', [$batchNo, $company]);
            $count++;
        } while ($exists);

        return $batchNo;
    }

    /**
     * Category IDs the user may access in the packaging modules (empty = no restriction)
     */
    private function accessibleCategoryIds(): array
    {
        $ids = [];
        foreach ($this->userModuleAccess['categories'] ?? [] as $module => $moduleCategories) {
            if (in_array($module, self::CATEGORY_MODULES, true)) {
                $ids = array_merge($ids, (array)$moduleCategories);
            }
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * List filters ('', '-' or 'all' means no filter)
     */
    private function applyFilters(string &$where, array &$params, string &$types, array $filters): void
    {
        $value = function (string $key) use ($filters): string {
            $v = trim((string)($filters[$key] ?? ''));
            return in_array($v, ['-', 'all'], true) ? '' : $v;
        };

        if (($from = \DateTime::createFromFormat('d/m/Y', $value('fromDate'))) !== false) {
            $where .= " AND pb.packaging_date >= ?";
            $params[] = $from->format('Y-m-d 00:00:00');
            $types .= 's';
        }

        if (($to = \DateTime::createFromFormat('d/m/Y', $value('toDate'))) !== false) {
            $where .= " AND pb.packaging_date <= ?";
            $params[] = $to->format('Y-m-d 23:59:59');
            $types .= 's';
        }

        if ($value('location') !== '') {
            $where .= " AND pb.location = ?";
            $params[] = (int)$value('location');
            $types .= 'i';
        }

        if ($value('productionLine') !== '') {
            $where .= " AND pb.production_line = ?";
            $params[] = (int)$value('productionLine');
            $types .= 'i';
        }

        // Batches containing a product in the chosen category, otherwise in the user's accessible categories
        $itemsWithProduct = "pb.id IN (SELECT pbi.packaging_batch_id FROM packaging_batch_items pbi INNER JOIN products p ON p.id = pbi.product_id WHERE pbi.deleted = 0 AND p.deleted = '0'";
        if ($value('category') !== '') {
            $where .= " AND $itemsWithProduct AND p.category = ?)";
            $params[] = (int)$value('category');
            $types .= 'i';
        } elseif (!empty($this->userModuleAccess['categories'])) {
            $categoryIds = $this->accessibleCategoryIds();
            if (!empty($categoryIds)) {
                $where .= " AND $itemsWithProduct AND p.category IN (" . $this->placeholders(count($categoryIds)) . "))";
                $params = array_merge($params, $categoryIds);
                $types .= str_repeat('i', count($categoryIds));
            } else {
                $where .= " AND $itemsWithProduct)";
            }
        }
    }
}
