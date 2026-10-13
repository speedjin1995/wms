<?php
namespace App\Modules\Wholesale;

use App\Core\BaseService;

/**
 * Bulk price update of wholesales weight rows (weight_details JSON) by product / grade,
 * for records of Normal-type customers / suppliers.
 */
class BulkPriceUpdateService extends BaseService
{
    private const CATEGORY_MODULES = ['wholesale', 'processing'];

    private array $userModuleAccess;

    public function __construct(\mysqli $db, int $company, int $user, string $role, array $userModuleAccess = [])
    {
        parent::__construct($db, $company, $user, $role);
        $this->userModuleAccess = $userModuleAccess;
    }

    /**
     * Filter dropdowns: products, customers, suppliers and grades per product
     */
    public function getLookups(): array
    {
        $normalCustomer = "deleted = '0' AND (customer_type = 'Normal' OR customer_type IS NULL OR customer_type = '')";
        $normalSupplier = "deleted = '0' AND (supplier_type = 'Normal' OR supplier_type IS NULL OR supplier_type = '')";
        $productSelect = "SELECT p.id, p.product_name FROM products p INNER JOIN categories c ON p.category = c.id
            WHERE p.deleted = '0' AND c.module IN ('wholesale', 'processing') AND c.deleted = '0'";
        $gradeSelect = "SELECT DISTINCT g.id, g.units, pg.product_id FROM grades g INNER JOIN product_grades pg ON g.id = pg.grade_id
            WHERE g.deleted = '0' AND pg.deleted = '0'";

        if ($this->isSuperAdmin()) {
            $products = $this->fetchAll("$productSelect ORDER BY p.product_name ASC");
            $customers = $this->fetchAll("SELECT id, customer_name FROM customers WHERE $normalCustomer ORDER BY customer_name ASC");
            $suppliers = $this->fetchAll("SELECT id, supplier_name FROM supplies WHERE $normalSupplier ORDER BY supplier_name ASC");
            $grades = $this->fetchAll("$gradeSelect ORDER BY g.units ASC");
        } else {
            $categoryIds = $this->accessibleCategoryIds($this->userModuleAccess, self::CATEGORY_MODULES);
            [$categoryFilter, $categoryTypes] = $this->categoryIdFilter('c.id', $categoryIds);
            $products = $this->fetchAll("$productSelect AND p.customer = ?$categoryFilter ORDER BY p.product_name ASC", 'i' . $categoryTypes, array_merge([$this->company], $categoryIds));

            // No products in the wholesale categories: fall back to all company products
            if (empty($products)) {
                $products = $this->fetchAll("SELECT id, product_name FROM products WHERE deleted = '0' AND customer = ? ORDER BY product_name ASC", 'i', [$this->company]);
            }

            $customers = $this->fetchAll("SELECT id, customer_name FROM customers WHERE $normalCustomer AND customer = ? ORDER BY customer_name ASC", 'i', [$this->company]);
            $suppliers = $this->fetchAll("SELECT id, supplier_name FROM supplies WHERE $normalSupplier AND customer = ? ORDER BY supplier_name ASC", 'i', [$this->company]);
            $grades = $this->fetchAll("$gradeSelect AND g.customer = ? ORDER BY g.units ASC", 'i', [$this->company]);
        }

        $gradesByProduct = [];
        foreach ($grades as $grade) {
            $gradesByProduct[$grade['product_id']][] = ['id' => $grade['id'], 'units' => $grade['units']];
        }

        return ['products' => $products, 'customers' => $customers, 'suppliers' => $suppliers, 'gradesByProduct' => $gradesByProduct];
    }

    /**
     * Records with weight rows matching the filters, for DataTables (filtered and paged in PHP since rows live in JSON)
     */
    public function getList(array $filters, int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        [$where, $types, $params] = $this->recordWhere($filters);
        $this->applyCategoryFilter($where, $types, $params);
        $this->applySearch($where, $params, $types, ['w.serial_no'], $search);

        $records = $this->fetchAll(
            "SELECT w.id, w.serial_no, w.start_time, w.status, w.weight_details, w.po_no, w.customer, w.supplier, w.other_customer, w.other_supplier,
                c.customer_name, s.supplier_name
            FROM wholesales w
            LEFT JOIN customers c ON w.customer = c.id
            LEFT JOIN supplies s ON w.supplier = s.id
            WHERE $where
            ORDER BY w.start_time ASC",
            $types,
            $params
        );

        $rows = [];
        foreach ($records as $record) {
            $items = [];
            foreach ($this->matchingDetails($record, $filters) as $detail) {
                $items[] = [
                    'product_name' => $detail['product_name'] ?? '',
                    'grade' => $detail['grade'] ?? '',
                    'net' => $this->money($detail['net'] ?? 0),
                    'price' => $this->money($detail['price'] ?? 0),
                    'total' => $this->money($detail['total'] ?? 0)
                ];
            }

            if (empty($items)) {
                continue;
            }

            $rows[] = [
                'serial_no' => $record['serial_no'],
                'start_time' => $this->displayTime($record['start_time']),
                'start_time_raw' => (string)$record['start_time'],
                'status' => $record['status'],
                'po_no' => $record['po_no'] ?? '',
                'customer_supplier' => $this->partyName($record),
                'item_count' => count($items),
                'items' => $items
            ];
        }

        $sortKeys = ['serial_no' => 'serial_no', 'po_no' => 'po_no', 'customer_supplier' => 'customer_supplier', 'start_time' => 'start_time_raw', 'status' => 'status', 'item_count' => 'item_count'];
        $sortKey = $sortKeys[$orderColumn] ?? 'start_time_raw';
        $desc = strtolower($orderDir) === 'desc';
        usort($rows, function ($a, $b) use ($sortKey, $desc) {
            $cmp = is_int($a[$sortKey]) ? $a[$sortKey] <=> $b[$sortKey] : strcmp((string)$a[$sortKey], (string)$b[$sortKey]);
            return $desc ? -$cmp : $cmp;
        });

        return ['totalRecords' => count($rows), 'data' => array_slice($rows, $start, $length)];
    }

    /**
     * Distinct product / grade pairs in the matching records, for the price input step
     */
    public function getProductGrades(array $filters): array
    {
        $combos = [];
        foreach ($this->fetchRecords($filters) as $record) {
            foreach ($this->matchingDetails($record, $filters) as $detail) {
                $key = ($detail['product'] ?? '') . '||' . ($detail['grade'] ?? '');
                if (!isset($combos[$key])) {
                    $combos[$key] = [
                        'product_id' => $detail['product'] ?? '',
                        'product_name' => $detail['product_name'] ?? '',
                        'grade' => $detail['grade'] ?? ''
                    ];
                }
            }
        }

        $list = array_values($combos);
        usort($list, function ($a, $b) {
            $cmp = strcmp($a['product_name'], $b['product_name']);
            return $cmp !== 0 ? $cmp : strcmp($a['grade'], $b['grade']);
        });

        return $list;
    }

    /**
     * Old vs new price / total of every weight row the update would change
     */
    public function preview(array $filters, array $priceRows): array
    {
        $rows = [];
        foreach ($this->fetchRecords($filters) as $record) {
            foreach ($this->matchingDetails($record, $filters) as $detail) {
                $key = ($detail['product'] ?? '') . '||' . ($detail['grade'] ?? '');
                if (!isset($priceRows[$key])) {
                    continue;
                }

                $newPrice = $priceRows[$key]['newPrice'];
                [, $newTotal] = $this->priceTotals($detail, $newPrice, $priceRows[$key]['pricingType']);

                $rows[] = [
                    'serial_no' => $record['serial_no'],
                    'start_time' => $this->displayTime($record['start_time']),
                    'product_name' => $detail['product_name'] ?? '',
                    'grade' => $detail['grade'] ?? '',
                    'net' => $this->money($detail['net'] ?? 0),
                    'old_price' => $this->money($detail['price'] ?? 0),
                    'old_total' => $this->money($detail['total'] ?? 0),
                    'new_price' => $this->money($newPrice),
                    'new_total' => $this->money($newTotal)
                ];
            }
        }

        return $rows;
    }

    /**
     * Apply the new prices to the matching weight rows and recalculate each record's total_price
     */
    public function update(array $filters, array $priceRows): array
    {
        $updatedRecords = 0;
        $updatedItems = 0;

        $this->db->begin_transaction();
        try {
            foreach ($this->fetchRecords($filters) as $record) {
                $details = json_decode($record['weight_details'], true);
                if (!is_array($details)) {
                    continue;
                }

                $changed = false;
                foreach ($details as &$detail) {
                    if (!$this->detailMatches($detail, $filters)) {
                        continue;
                    }
                    $key = ($detail['product'] ?? '') . '||' . ($detail['grade'] ?? '');
                    if (!isset($priceRows[$key])) {
                        continue;
                    }

                    $newPrice = $priceRows[$key]['newPrice'];
                    $pricingType = $priceRows[$key]['pricingType'];
                    [$beforeDiscount, $total] = $this->priceTotals($detail, $newPrice, $pricingType);

                    $detail['price'] = number_format($newPrice, 2, '.', '');
                    $detail['before_discount'] = number_format($beforeDiscount, 2, '.', '');
                    $detail['total'] = number_format($total, 2, '.', '');
                    $detail['fixedfloat'] = $pricingType;
                    $changed = true;
                    $updatedItems++;
                }
                unset($detail);

                if (!$changed) {
                    continue;
                }

                $totalPrice = 0;
                foreach ($details as $item) {
                    $totalPrice += floatval($item['total'] ?? 0);
                }

                $saved = $this->updateRow('wholesales', [
                    'weight_details' => json_encode($details),
                    'total_price' => $totalPrice,
                    'modified_by' => $this->user
                ], 'id = ?', 'i', [(int)$record['id']]);

                if (!$saved) {
                    throw new \RuntimeException('Failed to update wholesales ' . $record['id']);
                }
                $updatedRecords++;
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log('BulkPriceUpdateService::update - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        return ['status' => 'success', 'message' => "Updated $updatedItems item(s) across $updatedRecords record(s)."];
    }

    private function fetchRecords(array $filters): array
    {
        [$where, $types, $params] = $this->recordWhere($filters);

        return $this->fetchAll(
            "SELECT w.id, w.serial_no, w.start_time, w.weight_details FROM wholesales w
            LEFT JOIN customers c ON w.customer = c.id
            LEFT JOIN supplies s ON w.supplier = s.id
            WHERE $where",
            $types,
            $params
        );
    }

    /**
     * Wholesales records of Normal-type (or no) customer / supplier, scoped to the company, date, status and party
     */
    private function recordWhere(array $filters): array
    {
        $where = "w.deleted = '0' AND w.records_type = 'wholesales'
            AND (c.customer_type IS NULL OR c.customer_type = '' OR c.customer_type = 'Normal' OR w.customer IS NULL OR w.customer = '')
            AND (s.supplier_type IS NULL OR s.supplier_type = '' OR s.supplier_type = 'Normal' OR w.supplier IS NULL OR w.supplier = '')";
        $types = '';
        $params = [];

        $this->applyCompanyScope($where, $params, $types, 'w.company');

        $date = $filters['date'] !== '' ? \DateTime::createFromFormat('d/m/Y', $filters['date']) : false;
        if ($date) {
            $where .= " AND w.start_time >= ? AND w.start_time <= ?";
            $params[] = $date->format('Y-m-d 00:00:00');
            $params[] = $date->format('Y-m-d 23:59:59');
            $types .= 'ss';
        }

        if ($filters['status'] !== '') {
            $where .= " AND w.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }

        if ($filters['customer'] !== '') {
            $where .= " AND w.customer = ?";
            $params[] = $filters['customer'];
            $types .= 's';
        } elseif ($filters['supplier'] !== '') {
            $where .= " AND w.supplier = ?";
            $params[] = $filters['supplier'];
            $types .= 's';
        }

        return [$where, $types, $params];
    }

    /**
     * Records containing a product of the user's accessible categories (list only, as before)
     */
    private function applyCategoryFilter(string &$where, string &$types, array &$params): void
    {
        if (empty($this->userModuleAccess['categories'])) {
            return;
        }

        $categoryIds = $this->accessibleCategoryIds($this->userModuleAccess, self::CATEGORY_MODULES);
        [$filter, $filterTypes] = $this->categoryIdFilter('category', $categoryIds);
        $productIds = array_column($this->fetchAll("SELECT id FROM products WHERE deleted = '0'$filter", $filterTypes, $categoryIds), 'id');

        if (empty($productIds)) {
            return;
        }

        $where .= ' AND (' . implode(' OR ', array_fill(0, count($productIds), 'w.weight_details LIKE ?')) . ')';
        foreach ($productIds as $productId) {
            $params[] = '%"product":"' . $productId . '"%';
            $types .= 's';
        }
    }

    private function matchingDetails(array $record, array $filters): array
    {
        $details = json_decode($record['weight_details'], true);
        if (!is_array($details)) {
            return [];
        }

        return array_filter($details, function ($detail) use ($filters) {
            return $this->detailMatches($detail, $filters);
        });
    }

    /**
     * Non-rejected weight row of the filtered product / grade
     */
    private function detailMatches($detail, array $filters): bool
    {
        if (!is_array($detail) || ($detail['isRejected'] ?? '') === 'YES') {
            return false;
        }
        if ($filters['product'] !== '' && ($detail['product'] ?? '') != $filters['product']) {
            return false;
        }

        return $filters['grade'] === '' || ($detail['grade'] ?? '') == $filters['grade'];
    }

    /**
     * [before discount, total] for a new price: Float = price per kg of net, Fixed = flat price
     */
    private function priceTotals(array $detail, float $newPrice, string $pricingType): array
    {
        $beforeDiscount = $pricingType === 'Float' ? $newPrice * floatval($detail['net'] ?? 0) : $newPrice;
        $discount = floatval($detail['discount'] ?? 0);
        $total = ($detail['discount_type'] ?? 'fixed') === 'percent'
            ? $beforeDiscount * (1 - $discount / 100)
            : $beforeDiscount - $discount;

        return [$beforeDiscount, $total];
    }

    /**
     * Customer name for DISPATCH, otherwise supplier name ('OTHERS' uses the typed-in name)
     */
    private function partyName(array $record): string
    {
        if ($record['status'] == 'DISPATCH') {
            return $record['customer'] == 'OTHERS' ? (string)$record['other_customer'] : (string)($record['customer_name'] ?? '');
        }

        return $record['supplier'] == 'OTHERS' ? (string)$record['other_supplier'] : (string)($record['supplier_name'] ?? '');
    }

    private function money($value): string
    {
        return number_format(floatval($value), 2, '.', ',');
    }

    private function displayTime(?string $dateTime): string
    {
        return $dateTime ? date('d/m/Y H:i', strtotime($dateTime)) : '';
    }
}
