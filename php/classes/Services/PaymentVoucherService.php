<?php
namespace App\Services;

/**
 * Payment vouchers (payment_vouchers) grouping wholesale weighings of a parent customer / supplier.
 * Linked weighings carry pv_id and their own pv_unit_price. Non-SADMIN users are scoped to the session company.
 */
class PaymentVoucherService extends BaseService
{
    private const STATUSES = ['DISPATCH', 'RECEIVING', 'INCOMING', 'OUTGOING'];
    private const INCOMING_STATUSES = ['RECEIVING', 'INCOMING'];

    private string $recordType;
    private string $defaultStatus;

    public function __construct(\mysqli $db, int $company, int $user, string $role, string $module = 'wholesales')
    {
        parent::__construct($db, $company, $user, $role);
        $this->recordType = $module === 'industrial' ? 'industrial' : 'wholesales';
        $this->defaultStatus = $module === 'industrial' ? 'INCOMING' : 'RECEIVING';
    }

    /**
     * Parent customers / suppliers (those with child records) for the filters
     */
    public function getLookups(): array
    {
        $customerWhere = "cp.deleted = 0";
        $supplierWhere = "sp.deleted = 0";
        $params = [];
        $types = '';
        if (!$this->isSuperAdmin()) {
            $customerWhere .= " AND cp.customer = ?";
            $supplierWhere .= " AND sp.customer = ?";
            $params = [$this->company];
            $types = 'i';
        }

        return [
            'parentCustomers' => $this->fetchAll(
                "SELECT DISTINCT cp.id, cp.customer_name FROM customers cp INNER JOIN customers cc ON cc.parent = cp.id WHERE $customerWhere ORDER BY cp.customer_name ASC",
                $types,
                $params
            ),
            'parentSuppliers' => $this->fetchAll(
                "SELECT DISTINCT sp.id, sp.supplier_name FROM supplies sp INNER JOIN supplies sc ON sc.parent = sp.id WHERE $supplierWhere ORDER BY sp.supplier_name ASC",
                $types,
                $params
            )
        ];
    }

    /**
     * Valid transaction status (falls back to the module default)
     */
    public function resolveStatus(string $status): string
    {
        return in_array($status, self::STATUSES, true) ? $status : $this->defaultStatus;
    }

    /**
     * DataTables rows: one per payment voucher, plus one per parent entity for weighings not yet in a voucher
     */
    public function getList(array $filters, int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $status = $this->resolveStatus($filters['transactionStatus'] ?? '');
        $isIncoming = in_array($status, self::INCOMING_STATUSES, true);
        $from = $this->entityFrom($isIncoming);
        $parentColumn = $isIncoming ? 's.parent' : 'c.parent';
        $nameColumn = $isIncoming ? 'sp.supplier_name' : 'cp.customer_name';
        $groupBy = "COALESCE(CAST(pv.id AS CHAR), CAST($parentColumn AS CHAR))";

        $where = "w.deleted = 0 AND w.status = ? AND w.records_type = ?";
        $params = [$status, $this->recordType];
        $types = 'ss';
        $this->applyCompanyScope($where, $params, $types, 'w.company');

        $totalRecords = $this->countGroups($from, $where, $types, $params, $groupBy);

        if (($fromDate = \DateTime::createFromFormat('d/m/Y', (string)($filters['fromDate'] ?? ''))) !== false) {
            $where .= " AND w.created_datetime >= ?";
            $params[] = $fromDate->format('Y-m-d 00:00:00');
            $types .= 's';
        }
        if (($toDate = \DateTime::createFromFormat('d/m/Y', (string)($filters['toDate'] ?? ''))) !== false) {
            $where .= " AND w.created_datetime <= ?";
            $params[] = $toDate->format('Y-m-d 23:59:59');
            $types .= 's';
        }
        $parentId = (int)($isIncoming ? ($filters['parentSupplierId'] ?? 0) : ($filters['parentCustomerId'] ?? 0));
        if ($parentId) {
            $where .= " AND $parentColumn = ?";
            $params[] = $parentId;
            $types .= 'i';
        }
        $this->applySearch($where, $params, $types, ['w.serial_no', $nameColumn], $search);

        $totalFiltered = $this->countGroups($from, $where, $types, $params, $groupBy);

        $orderBy = $this->orderBy([
            'voucher_date' => 'pv_voucher_date',
            'voucher_no' => 'voucher_no',
            'entity_name' => 'entity_name',
            'invoice_no' => 'invoice_no',
            'total_nett_weight' => 'total_nett_weight',
            'unit_price' => 'unit_price',
            'final_amount' => 'final_amount'
        ], $orderColumn, $orderDir, 'pv_voucher_date');
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $rows = $this->fetchAll(
            "SELECT MAX(w.id) AS id, MAX($parentColumn) AS parent_id, MAX($nameColumn) AS entity_name,
                    MAX(pv.id) AS pv_id, MAX(pv.voucher_no) AS voucher_no, MAX(pv.voucher_date) AS pv_voucher_date,
                    MAX(pv.invoice_no) AS invoice_no, MAX(pv.final_amount) AS final_amount, MAX(pv.unit_price) AS unit_price,
                    SUM(CAST(w.total_weight AS DECIMAL(10,2))) AS total_nett_weight
             FROM $from WHERE $where GROUP BY $groupBy ORDER BY $orderBy LIMIT ?, ?",
            $types,
            $params
        );

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id' => $row['id'],
                'parent_id' => $row['parent_id'],
                'entity_name' => $row['entity_name'],
                'voucher_date' => $row['pv_voucher_date'] !== null ? date('d/m/Y', strtotime($row['pv_voucher_date'])) : null,
                'voucher_no' => $row['voucher_no'] ?? '-',
                'invoice_no' => $row['invoice_no'] ?? '-',
                'final_amount' => number_format((float)$row['final_amount'], 2),
                'unit_price' => number_format((float)$row['unit_price'], 2),
                'total_nett_weight' => number_format((float)$row['total_nett_weight'], 2),
                'pv_id' => $row['pv_id'] ?? ''
            ];
        }

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Weighings for a voucher (editing) or the parent's unlinked weighings within the start_time range (new voucher), with the voucher header
     */
    public function getItems(int $parentId, int $pvId, string $status, string $fromDate = '', string $toDate = ''): ?array
    {
        $pv = [];
        if ($pvId) {
            $pv = $this->findVoucher($pvId);
            if (!$pv) {
                return null;
            }
            $status = $this->voucherStatus($pv, $status);
        }

        $isIncoming = in_array($status, self::INCOMING_STATUSES, true);
        $rows = $pvId ? $this->linkedWeighings($pvId) : $this->unlinkedWeighings($parentId, $status, $isIncoming, $this->company, $fromDate, $toDate);
        $categories = $this->categoryNames($rows);

        $items = [];
        $totalNett = 0.0;
        foreach ($rows as $row) {
            $nett = $this->nettWeight($row);
            // Vouchers saved before per-row pricing have no row price: default to the voucher's unit price
            $unitPrice = (float)(($row['pv_unit_price'] ?? '') !== '' ? $row['pv_unit_price'] : ($pv['unit_price'] ?? 0));
            $totalNett += $nett;

            $items[] = [
                'id' => $row['id'],
                'serial_no' => $row['serial_no'],
                'start_time' => $row['start_time'],
                'entity_name' => $isIncoming
                    ? ($row['supplier'] === 'OTHERS' ? (string)$row['other_supplier'] : (string)$row['supplier_name'])
                    : ($row['customer'] === 'OTHERS' ? (string)$row['other_customer'] : (string)$row['customer_name']),
                'vehicle_no' => $row['vehicle_no'],
                'nett' => number_format($nett, 2),
                'nett_raw' => $nett,
                'unit_price' => number_format($unitPrice, 2, '.', ''),
                'categories' => implode(', ', $this->rowCategories($row, $categories))
            ];
        }

        return [
            'items' => $items,
            'total_nett_weight' => number_format($totalNett, 2),
            'paymentVoucher' => $pv ?: new \stdClass()
        ];
    }

    /**
     * Create or update a voucher and link its weighings with their unit prices (one transaction).
     * Totals are recalculated from the linked weighings; $prices: weighing id => unit price.
     */
    public function save(int $pvId, int $entityId, string $status, array $header, array $prices): array
    {
        $existing = null;
        if ($pvId) {
            $existing = $this->findVoucher($pvId);
            if (!$existing) {
                return ['status' => 'failed', 'message' => 'Record not found'];
            }
            $status = $this->voucherStatus($existing, $status);
        }

        $isIncoming = in_array($status, self::INCOMING_STATUSES, true);
        $recordCompany = $existing ? (int)$existing['company'] : $this->company;
        $allowed = [];
        foreach ($existing ? $this->linkedWeighings($pvId) : $this->unlinkedWeighings($entityId, $status, $isIncoming, $recordCompany) as $row) {
            $allowed[(int)$row['id']] = $row;
        }

        if (empty($prices)) {
            return ['status' => 'failed', 'message' => 'No records selected'];
        }
        if (array_diff(array_keys($prices), array_keys($allowed))) {
            return ['status' => 'failed', 'message' => 'Invalid weighing record'];
        }

        $totalNett = 0.0;
        $nettAmount = 0.0;
        foreach ($prices as $id => $price) {
            $nett = $this->nettWeight($allowed[$id]);
            $totalNett += $nett;
            $nettAmount += $nett * $price;
        }
        $taxAmount = $nettAmount * ($header['tax'] / 100);
        $totalAmount = $nettAmount + $taxAmount;
        $money = function (float $value): string {
            return number_format($value, 2, '.', '');
        };

        $data = [
            'voucher_date' => $header['voucher_date'],
            'invoice_no' => $header['invoice_no'],
            'unit_price' => $money($header['unit_price']),
            'tax' => $money($header['tax']),
            'total_nett_weight' => $money($totalNett),
            'nett_amount' => $money($nettAmount),
            'tax_amount' => $money($taxAmount),
            'total_amount' => $money($totalAmount),
            'final_amount' => $money($totalAmount)
        ];

        $this->db->begin_transaction();

        try {
            if ($existing) {
                if (!$this->updateRow('payment_vouchers', $data + ['modified_by' => $this->user], "id = ?", 'i', [$pvId])) {
                    throw new \Exception('Failed to update payment voucher');
                }
            } else {
                if (!$this->fetchOne($isIncoming ? "SELECT id FROM supplies WHERE id = ? AND customer = ?" : "SELECT id FROM customers WHERE id = ? AND customer = ?", 'ii', [$entityId, $recordCompany])) {
                    throw new \RuntimeException('Invalid ' . ($isIncoming ? 'supplier' : 'customer'));
                }
                $pvId = $this->insertRow('payment_vouchers', $data + [
                    'entity_id' => $entityId,
                    'status' => $status,
                    'voucher_no' => $this->nextVoucherNo($recordCompany),
                    'deduction_amount' => '0',
                    'addition_amount' => '0',
                    'company' => $recordCompany,
                    'created_by' => $this->user
                ]);
                if (!$pvId) {
                    throw new \Exception('Failed to insert payment voucher');
                }
            }

            foreach ($prices as $id => $price) {
                if (!$this->executeWrite("UPDATE wholesales SET pv_id = ?, pv_unit_price = ?, modified_by = ? WHERE id = ?", 'isii', [$pvId, $money($price), $this->user, $id])) {
                    throw new \Exception('Failed to link weighing ' . $id);
                }
            }

            $this->db->commit();
        } catch (\RuntimeException $e) {
            $this->db->rollback();
            return ['status' => 'failed', 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('PaymentVoucherService::save - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to save record'];
        }

        return ['status' => 'success', 'message' => $existing ? 'Updated Successfully!!' : 'Saved Successfully!!'];
    }

    /**
     * Soft delete a voucher with a reason and release its weighings
     */
    public function cancel(int $id, string $reason): array
    {
        if (!$this->findVoucher($id)) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        $this->db->begin_transaction();

        try {
            if (!$this->executeWrite("UPDATE wholesales SET pv_id = NULL, pv_unit_price = NULL WHERE pv_id = ?", 'i', [$id])) {
                throw new \Exception('Failed to release weighings');
            }
            if (!$this->executeWrite("UPDATE payment_vouchers SET deleted = 1, delete_reason = ?, modified_by = ? WHERE id = ?", 'sii', [$reason, $this->user, $id])) {
                throw new \Exception('Failed to delete payment voucher');
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('PaymentVoucherService::cancel - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to delete record'];
        }

        return ['status' => 'success', 'message' => 'Deleted Successfully!!'];
    }

    /**
     * Voucher, its company / entity and (for the statement) its weighings
     */
    public function getPrintData(int $pvId): ?array
    {
        $pv = $this->findVoucher($pvId);
        if (!$pv) {
            return null;
        }

        $isIncoming = in_array($this->voucherStatus($pv, ''), self::INCOMING_STATUSES, true);
        $entity = $this->fetchOne(
            $isIncoming ? "SELECT supplier_name AS name FROM supplies WHERE id = ?" : "SELECT customer_name AS name FROM customers WHERE id = ?",
            'i',
            [(int)$pv['entity_id']]
        );

        $rows = $this->linkedWeighings($pvId);
        $categories = $this->categoryNames($rows);
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'date' => date('d/m/Y', strtotime($row['start_time'])),
                'serial_no' => $row['serial_no'],
                'nett' => $this->nettWeight($row),
                'categories' => implode(', ', $this->rowCategories($row, $categories))
            ];
        }

        return [
            'pv' => $pv,
            'company' => $this->fetchOne("SELECT name, reg_no, address, address2, address3, address4, phone FROM companies WHERE id = ?", 'i', [(int)$pv['company']]) ?? [],
            'entityName' => $entity['name'] ?? '',
            'items' => $items
        ];
    }

    /**
     * Vouchers matching the filters for the PV report (dates filter on voucher_date), with the company details
     */
    public function getReportData(array $filters): array
    {
        $status = $this->resolveStatus($filters['transactionStatus'] ?? '');
        $isIncoming = in_array($status, self::INCOMING_STATUSES, true);
        $parentColumn = $isIncoming ? 's.parent' : 'c.parent';
        $nameColumn = $isIncoming ? 'sp.supplier_name' : 'cp.customer_name';

        $where = "pv.id IS NOT NULL AND pv.deleted = 0 AND w.deleted = 0 AND w.status = ? AND w.records_type = ?";
        $params = [$status, $this->recordType];
        $types = 'ss';
        $this->applyCompanyScope($where, $params, $types, 'w.company');

        if (($fromDate = \DateTime::createFromFormat('d/m/Y', (string)($filters['fromDate'] ?? ''))) !== false) {
            $where .= " AND pv.voucher_date >= ?";
            $params[] = $fromDate->format('Y-m-d');
            $types .= 's';
        }
        if (($toDate = \DateTime::createFromFormat('d/m/Y', (string)($filters['toDate'] ?? ''))) !== false) {
            $where .= " AND pv.voucher_date <= ?";
            $params[] = $toDate->format('Y-m-d');
            $types .= 's';
        }
        $parentId = (int)($isIncoming ? ($filters['parentSupplierId'] ?? 0) : ($filters['parentCustomerId'] ?? 0));
        if ($parentId) {
            $where .= " AND $parentColumn = ?";
            $params[] = $parentId;
            $types .= 'i';
        }

        $rows = $this->fetchAll(
            "SELECT pv.id AS pv_id, MAX(pv.voucher_no) AS voucher_no, MAX(pv.voucher_date) AS voucher_date, MAX(pv.invoice_no) AS invoice_no,
                    MAX(pv.unit_price) AS unit_price, MAX(pv.final_amount) AS final_amount, MAX(pv.total_nett_weight) AS total_nett_weight,
                    MAX($nameColumn) AS entity_name
             FROM " . $this->entityFrom($isIncoming, 'INNER') . " WHERE $where
             GROUP BY pv.id ORDER BY voucher_date ASC, voucher_no ASC",
            $types,
            $params
        );

        return [
            'rows' => $rows,
            'isIncoming' => $isIncoming,
            'company' => $this->fetchOne("SELECT name, reg_no, address, address2, address3, phone FROM companies WHERE id = ?", 'i', [$this->company]) ?? []
        ];
    }

    /**
     * Voucher within the session company (SADMIN: any)
     */
    private function findVoucher(int $id): ?array
    {
        $where = "id = ? AND deleted = 0";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types, 'company');

        return $this->fetchOne("SELECT * FROM payment_vouchers WHERE $where", $types, $params);
    }

    /**
     * Voucher's transaction status; older vouchers saved without one use the requested / module default status
     */
    private function voucherStatus(array $pv, string $fallback): string
    {
        return in_array($pv['status'], self::STATUSES, true) ? $pv['status'] : $this->resolveStatus($fallback);
    }

    /**
     * Weighings joined to their child entity and its parent (voucher joined when present)
     */
    private function entityFrom(bool $isIncoming, string $pvJoin = 'LEFT'): string
    {
        $entity = $isIncoming
            ? "INNER JOIN supplies s ON CAST(w.supplier AS UNSIGNED) = s.id INNER JOIN supplies sp ON s.parent = sp.id"
            : "INNER JOIN customers c ON CAST(w.customer AS UNSIGNED) = c.id INNER JOIN customers cp ON c.parent = cp.id";

        return "wholesales w $entity $pvJoin JOIN payment_vouchers pv ON w.pv_id = pv.id AND pv.deleted = 0";
    }

    private function countGroups(string $from, string $where, string $types, array $params, string $groupBy): int
    {
        $row = $this->fetchOne("SELECT COUNT(DISTINCT $groupBy) AS allcount FROM $from WHERE $where", $types, $params);

        return (int)($row['allcount'] ?? 0);
    }

    private function weighingColumns(): string
    {
        return "w.id, w.serial_no, w.start_time, w.vehicle_no, w.supplier, w.other_supplier, w.customer, w.other_customer,
                w.weight_details, w.total_weight, w.pv_unit_price, s.supplier_name, c.customer_name";
    }

    private function linkedWeighings(int $pvId): array
    {
        $where = "w.pv_id = ? AND w.deleted = 0";
        $params = [$pvId];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types, 'w.company');

        return $this->fetchAll(
            "SELECT " . $this->weighingColumns() . " FROM wholesales w
             LEFT JOIN supplies s ON CAST(w.supplier AS UNSIGNED) = s.id LEFT JOIN customers c ON CAST(w.customer AS UNSIGNED) = c.id
             WHERE $where ORDER BY w.start_time ASC",
            $types,
            $params
        );
    }

    /**
     * Weighings of the parent's children not yet in a voucher, optionally within a DD/MM/YYYY start_time range
     */
    private function unlinkedWeighings(int $parentId, string $status, bool $isIncoming, int $company, string $fromDate = '', string $toDate = ''): array
    {
        $where = ($isIncoming ? "s.parent = ?" : "c.parent = ?") . " AND w.deleted = 0 AND w.status = ? AND w.records_type = ? AND w.pv_id IS NULL";
        $params = [$parentId, $status, $this->recordType];
        $types = 'iss';
        if (!$this->isSuperAdmin()) {
            $where .= " AND w.company = ?";
            $params[] = $company;
            $types .= 'i';
        }
        if (($from = \DateTime::createFromFormat('d/m/Y', $fromDate)) !== false) {
            $where .= " AND w.start_time >= ?";
            $params[] = $from->format('Y-m-d 00:00:00');
            $types .= 's';
        }
        if (($to = \DateTime::createFromFormat('d/m/Y', $toDate)) !== false) {
            $where .= " AND w.start_time <= ?";
            $params[] = $to->format('Y-m-d 23:59:59');
            $types .= 's';
        }

        return $this->fetchAll(
            "SELECT " . $this->weighingColumns() . " FROM wholesales w
             LEFT JOIN supplies s ON CAST(w.supplier AS UNSIGNED) = s.id LEFT JOIN customers c ON CAST(w.customer AS UNSIGNED) = c.id
             WHERE $where ORDER BY w.start_time ASC",
            $types,
            $params
        );
    }

    /**
     * Sum of weight_details nett, falling back to total_weight
     */
    private function nettWeight(array $row): float
    {
        $nett = 0.0;
        foreach ($this->weightDetails($row) as $detail) {
            $nett += (float)($detail['net'] ?? 0);
        }

        return $nett == 0 ? (float)$row['total_weight'] : $nett;
    }

    private function weightDetails(array $row): array
    {
        $details = json_decode((string)($row['weight_details'] ?? '[]'), true);

        return is_array($details) ? $details : [];
    }

    /**
     * Category names keyed by product ID for every product in the rows' weight_details
     */
    private function categoryNames(array $rows): array
    {
        $productIds = [];
        foreach ($rows as $row) {
            foreach ($this->weightDetails($row) as $detail) {
                if (!empty($detail['product'])) {
                    $productIds[] = (int)$detail['product'];
                }
            }
        }
        $productIds = array_values(array_unique($productIds));
        if (empty($productIds)) {
            return [];
        }

        $names = [];
        foreach ($this->fetchAll(
            "SELECT p.id, c.category_name FROM products p JOIN categories c ON p.category = c.id WHERE p.deleted = 0 AND p.id IN (" . $this->placeholders(count($productIds)) . ")",
            str_repeat('i', count($productIds)),
            $productIds
        ) as $row) {
            $names[(int)$row['id']] = $row['category_name'];
        }

        return $names;
    }

    private function rowCategories(array $row, array $categories): array
    {
        $names = [];
        foreach ($this->weightDetails($row) as $detail) {
            $name = $categories[(int)($detail['product'] ?? 0)] ?? null;
            if ($name !== null && !in_array($name, $names, true)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * PV + today (Ymd) + first free running number for the company (min 4 digits)
     */
    private function nextVoucherNo(int $company): string
    {
        $prefix = 'PV' . date('Ymd');
        $row = $this->fetchOne("SELECT COUNT(*) AS total FROM payment_vouchers WHERE company = ? AND voucher_no LIKE ?", 'is', [$company, $prefix . '%']);
        $count = (int)($row['total'] ?? 0) + 1;

        do {
            $voucherNo = $prefix . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
            $exists = $this->fetchOne("SELECT id FROM payment_vouchers WHERE voucher_no = ? AND company = ?", 'si', [$voucherNo, $company]);
            $count++;
        } while ($exists);

        return $voucherNo;
    }
}
