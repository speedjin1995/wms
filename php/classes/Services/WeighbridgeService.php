<?php
namespace App\Services;

/**
 * Weighbridge (Weight table) transactions. Non-SADMIN users are scoped to the session company.
 */
class WeighbridgeService extends BaseService
{
    public const TRANSACTION_STATUSES = ['Dispatch', 'Receiving', 'Sales', 'Purchase', 'Misc', 'Local'];

    private const WEIGHT_TYPE = 'Normal';
    private const INDICATOR = 'web';
    private const RECORD_TYPE = 'fruits';

    /**
     * Dropdown data for the filter / entry forms.
     * $byModule limits products to the active module's categories (and daily sales states) with a fallback to all company products.
     */
    public function getLookups(string $module = '', array $states = [], bool $byModule = false): array
    {
        $scope = $this->isSuperAdmin() ? '' : ' AND customer = ?';
        $types = $this->isSuperAdmin() ? '' : 'i';
        $params = $this->isSuperAdmin() ? [] : [$this->company];

        $products = [];
        if ($byModule && !$this->isSuperAdmin()) {
            $sql = "SELECT p.product_name, p.product_code FROM products p INNER JOIN categories c ON p.category = c.id
                    WHERE p.deleted = '0' AND p.customer = ? AND c.module = ? AND c.deleted = '0'";
            $productTypes = 'is';
            $productParams = [$this->company, $module];
            if (!empty($states)) {
                $sql .= " AND JSON_OVERLAPS(p.state, ?)";
                $productTypes .= 's';
                $productParams[] = json_encode(array_values($states));
            }
            $products = $this->fetchAll($sql . " ORDER BY p.product_name ASC", $productTypes, $productParams);
        }
        if (empty($products)) {
            $products = $this->fetchAll("SELECT product_name, product_code FROM products WHERE deleted = '0'$scope ORDER BY product_name ASC", $types, $params);
        }

        return [
            'products' => $products,
            'customers' => $this->fetchAll("SELECT customer_name, customer_code FROM customers WHERE deleted = '0'$scope ORDER BY customer_name ASC", $types, $params),
            'suppliers' => $this->fetchAll("SELECT supplier_name, supplier_code FROM supplies WHERE deleted = '0'$scope ORDER BY supplier_name ASC", $types, $params),
            'vehicles' => $this->fetchAll("SELECT veh_number FROM vehicles WHERE deleted = '0'$scope ORDER BY veh_number ASC", $types, $params)
        ];
    }

    /**
     * Get paginated weighbridge records for DataTables
     */
    public function getList(array $filters, int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $where = "status = '0'";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'company');

        $totalRecords = $this->countRows('Weight', $where, $types, $params);

        $this->applyFilters($where, $params, $types, $filters);
        $this->applySearch($where, $params, $types, ['transaction_id', 'purchase_order', 'delivery_no', 'lorry_plate_no1'], $search);
        $totalFiltered = $this->countRows('Weight', $where, $types, $params);

        $orderBy = $this->orderBy([
            'transaction_id' => 'transaction_id',
            'transaction_date' => 'transaction_date',
            'transaction_status' => 'transaction_status',
            'do_po' => 'purchase_order',
            'lorry_plate_no1' => 'lorry_plate_no1',
            'customer_supplier' => 'customer_name',
            'product_name' => 'product_name',
            'gross_weight1' => 'gross_weight1',
            'gross_weight1_date' => 'gross_weight1_date',
            'tare_weight1' => 'tare_weight1',
            'tare_weight1_date' => 'tare_weight1_date',
            'final_weight' => 'final_weight'
        ], $orderColumn, $orderDir, 'transaction_id');
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $rows = $this->fetchAll("SELECT * FROM Weight WHERE $where ORDER BY $orderBy LIMIT ?, ?", $types, $params);

        $data = [];
        foreach ($rows as $row) {
            $status = $row['transaction_status'];
            $isReceiving = in_array($status, ['Receiving', 'Purchase', 'Local'], true);

            $data[] = [
                'id' => $row['id'],
                'transaction_id' => $row['transaction_id'],
                'transaction_date' => $row['transaction_date'],
                'transaction_status' => $this->statusLabel($status),
                'do_po' => $isReceiving ? $row['purchase_order'] : $row['delivery_no'],
                'lorry_plate_no1' => $row['lorry_plate_no1'],
                'customer_supplier' => ($status == 'Dispatch' || $status == 'Misc') ? $row['customer_name'] : $row['supplier_name'],
                'product_name' => $row['product_name'],
                'gross_weight1' => $row['gross_weight1'],
                'gross_weight1_date' => $row['gross_weight1_date'],
                'tare_weight1' => $row['tare_weight1'],
                'tare_weight1_date' => $row['tare_weight1_date'],
                'nett_weight1' => $row['nett_weight1'],
                'reduce_weight' => $row['reduce_weight'],
                'final_weight' => $row['final_weight'],
                'company' => $row['company']
            ];
        }

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Get single record (with weigher names)
     */
    public function getById(int $id): ?array
    {
        $where = "w.id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types, 'w.company');

        return $this->fetchOne(
            "SELECT w.id, w.transaction_id, w.transaction_status, w.transaction_date, w.lorry_plate_no1, w.customer_name, w.supplier_name,
                    w.product_name, w.purchase_order, w.delivery_no, w.gross_weight_by1, g.name AS grossWeightBy, w.gross_weight1,
                    w.gross_weight1_date, w.tare_weight_by1, t.name AS tareWeightBy, w.tare_weight1, w.tare_weight1_date, w.nett_weight1
             FROM Weight w
             LEFT JOIN users g ON g.id = w.gross_weight_by1
             LEFT JOIN users t ON t.id = w.tare_weight_by1
             WHERE $where",
            $types,
            $params
        );
    }

    /**
     * Create or update a record. Weigher is the current user whenever a weight is entered or changed.
     */
    public function save(int $id, array $data): array
    {
        $existing = null;
        if ($id) {
            $existing = $this->fetchOne("SELECT * FROM Weight WHERE id = ?" . ($this->isSuperAdmin() ? '' : " AND company = ?"),
                $this->isSuperAdmin() ? 'i' : 'ii', $this->isSuperAdmin() ? [$id] : [$id, $this->company]);
            if (!$existing) {
                return ['status' => 'failed', 'message' => 'Record not found'];
            }
        }

        $gross = $data['gross_weight1'];
        $tare = $data['tare_weight1'];
        $nett = abs((float)$gross - (float)($tare ?? 0));
        $now = date("Y-m-d H:i:s");

        $data['gross_weight_by1'] = ($existing && $existing['gross_weight1'] == $gross) ? $existing['gross_weight_by1'] : (string)$this->user;
        $data['tare_weight_by1'] = $tare === null ? null
            : (($existing && $existing['tare_weight1'] == $tare) ? $existing['tare_weight_by1'] : (string)$this->user);
        $data['nett_weight1'] = (string)$nett;
        $data['final_weight'] = (string)$nett;
        $data['weight_type'] = self::WEIGHT_TYPE;
        $data['indicator_id'] = self::INDICATOR;
        $data['records_type'] = self::RECORD_TYPE;
        $data['is_complete'] = ($gross !== null && $tare !== null) ? 'Y' : 'N';
        $data['modified_by'] = (string)$this->user;
        $data['modified_date'] = $now;

        if ($existing) {
            if (!$this->updateRow('Weight', $data, "id = ?", 'i', [$id])) {
                return ['status' => 'failed', 'message' => 'Failed to update record'];
            }

            return ['status' => 'success', 'message' => 'Updated Successfully!!'];
        }

        $data['transaction_id'] = $this->nextTransactionId($data['transaction_status']);
        $data['created_date'] = $now;
        $data['created_by'] = (string)$this->user;
        $data['company'] = $this->company;

        if (!$this->insertRow('Weight', $data)) {
            return ['status' => 'failed', 'message' => 'Failed to add record'];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    /**
     * Cancel (soft delete) a record with a reason
     */
    public function cancel(int $id, string $reason): array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types, 'company');

        if (!$this->fetchOne("SELECT id FROM Weight WHERE $where", $types, $params)) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        if (!$this->executeWrite("UPDATE Weight SET is_cancel = 'Y', cancelled_reason = ?, modified_by = ? WHERE $where", 'ss' . $types, array_merge([$reason, (string)$this->user], $params))) {
            return ['status' => 'failed', 'message' => 'Failed to delete record'];
        }

        return ['status' => 'success', 'message' => 'Deleted'];
    }

    /**
     * Rows for Excel / PDF export: selected IDs, or all session-company records matching the filters
     */
    public function getReportRows(array $filters, array $ids = []): array
    {
        $ids = $this->cleanIds($ids);

        if (!empty($ids)) {
            $where = "id IN (" . $this->placeholders(count($ids)) . ")";
            $params = $ids;
            $types = str_repeat('i', count($ids));
            $this->applyCompanyScope($where, $params, $types, 'company');
        } else {
            $where = "status = '0' AND company = ?";
            $params = [$this->company];
            $types = 'i';
            $this->applyFilters($where, $params, $types, $filters);
        }

        return $this->fetchAll("SELECT * FROM Weight WHERE $where", $types, $params);
    }

    /**
     * Single record with its company details for the weighing slip, within the session company (SADMIN: any)
     */
    public function getSlipRow(int $id): ?array
    {
        $where = "w.id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types, 'w.company');

        return $this->fetchOne(
            "SELECT w.*, c.name AS company_name, c.reg_no AS company_reg_no, c.address AS company_address1, c.address2 AS company_address2,
                    c.address3 AS company_address3, c.phone AS company_phone, c.fax AS company_fax
             FROM Weight w LEFT JOIN companies c ON w.company = c.id
             WHERE $where",
            $types,
            $params
        );
    }

    /**
     * Shared list / report filters (dates in DD/MM/YYYY; '-' or '' means no filter)
     */
    private function applyFilters(string &$where, array &$params, string &$types, array $filters): void
    {
        $value = function (string $key) use ($filters): string {
            $v = trim((string)($filters[$key] ?? ''));
            return $v === '-' ? '' : $v;
        };

        if (($from = \DateTime::createFromFormat('d/m/Y', $value('fromDate'))) !== false) {
            $where .= " AND transaction_date >= ?";
            $params[] = $from->format('Y-m-d 00:00:00');
            $types .= 's';
        }

        if (($to = \DateTime::createFromFormat('d/m/Y', $value('toDate'))) !== false) {
            $where .= " AND transaction_date <= ?";
            $params[] = $to->format('Y-m-d 23:59:59');
            $types .= 's';
        }

        $exact = [
            'transactionStatus' => 'transaction_status',
            'product' => 'product_name',
            'customer' => 'customer_name',
            'supplier' => 'supplier_name',
            'vehicle' => 'lorry_plate_no1'
        ];
        foreach ($exact as $key => $column) {
            if ($value($key) !== '') {
                $where .= " AND $column = ?";
                $params[] = $value($key);
                $types .= 's';
            }
        }

        $status = $value('status');
        if ($status === 'Pending') {
            $where .= " AND is_complete = 'N' AND is_cancel <> 'Y'";
        } elseif ($status === 'Complete') {
            $where .= " AND is_complete = 'Y' AND is_cancel <> 'Y'";
        } elseif ($status === 'Cancelled') {
            $where .= " AND is_cancel = 'Y'";
        }

        if ($value('transactionId') !== '') {
            $where .= " AND transaction_id LIKE ?";
            $params[] = '%' . $value('transactionId') . '%';
            $types .= 's';
        }
    }

    /**
     * Prefix (S / P / M) + Ymd + running number of today's records for the company (min 4 digits)
     */
    private function nextTransactionId(string $transactionStatus): string
    {
        $prefix = 'S';
        if ($transactionStatus == 'Purchase' || $transactionStatus == 'Receiving') {
            $prefix = 'P';
        } elseif ($transactionStatus == 'Misc') {
            $prefix = 'M';
        }

        // One counter for today's records across S / P / M
        $row = $this->fetchOne("SELECT COUNT(*) AS total FROM Weight WHERE created_date >= ? AND company = ?", 'si', [date("Y-m-d 00:00:00"), $this->company]);

        return $this->nextRunningNo('Weight', 'transaction_id', $prefix . date("Ymd"), $this->company, (int)($row['total'] ?? 0) + 1);
    }

    private function statusLabel(string $status): string
    {
        if ($status == 'Dispatch' || $status == 'Sales') {
            return 'Dispatch';
        }
        if ($status == 'Receiving' || $status == 'Purchase') {
            return 'Receiving';
        }
        if ($status == 'Misc') {
            return 'Miscellaneous';
        }

        return 'Internal Transfer';
    }
}
