<?php
namespace App\Modules\LoadingOrder;

use App\Core\BaseService;

require_once __DIR__ . '/../../../services/stockManagementService.php';
require_once __DIR__ . '/../../../services/batchStatusService.php';

/**
 * Loading orders (loading_orders + loading_order_items) built from pending packaging batch items.
 * Each loaded item marks its packaging_batch_item completed and dispatches one box of packaged stock.
 * Non-SADMIN users are scoped to the session company.
 */
class LoadingOrderService extends BaseService
{
    private bool $stockEnabled;

    public function __construct(\mysqli $db, int $company, int $user, string $role, bool $stockEnabled = false)
    {
        parent::__construct($db, $company, $user, $role);
        $this->stockEnabled = $stockEnabled;
    }

    /**
     * Dropdown data for the filter / entry forms (batches: not yet completed)
     */
    public function getLookups(): array
    {
        if ($this->isSuperAdmin()) {
            return [
                'customers' => $this->fetchAll("SELECT id, customer_name FROM customers WHERE deleted = '0' ORDER BY customer_name ASC"),
                'shipmentTypes' => $this->fetchAll("SELECT id, shipment_type FROM shipment_types WHERE deleted = '0' ORDER BY shipment_type ASC"),
                'batches' => $this->fetchAll("SELECT id, batch_no FROM packaging_batches WHERE deleted = '0' AND status != 'completed' ORDER BY packaging_date DESC")
            ];
        }

        return [
            'customers' => $this->fetchAll("SELECT id, customer_name FROM customers WHERE deleted = '0' AND customer = ? ORDER BY customer_name ASC", 'i', [$this->company]),
            'shipmentTypes' => $this->fetchAll("SELECT id, shipment_type FROM shipment_types WHERE deleted = '0' AND customer = ? ORDER BY shipment_type ASC", 'i', [$this->company]),
            'batches' => $this->fetchAll("SELECT id, batch_no FROM packaging_batches WHERE deleted = '0' AND status != 'completed' AND company = ? ORDER BY packaging_date DESC", 'i', [$this->company])
        ];
    }

    /**
     * Get paginated loading orders for DataTables (dates filter on loading_date)
     */
    public function getList(array $filters, int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $from = "loading_orders lo LEFT JOIN shipment_types st ON lo.shipment_type = st.id";
        $where = "lo.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'lo.company');

        $totalRecords = $this->countRows('loading_orders lo', $where, $types, $params);

        $this->applyFilters($where, $params, $types, $filters);
        $this->applySearch($where, $params, $types, ['lo.loading_no'], $search);
        $totalFiltered = $this->countRows($from, $where, $types, $params);

        $orderBy = $this->orderBy([
            'loading_no' => 'lo.loading_no',
            'loading_date' => 'lo.loading_date',
            'status' => 'lo.status',
            'shipmentType' => 'st.shipment_type'
        ], $orderColumn, $orderDir, 'lo.loading_date');
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $data = $this->fetchAll(
            "SELECT lo.id, lo.loading_no, lo.loading_date, IFNULL(st.shipment_type, '') AS shipmentType, lo.status, lo.company
             FROM $from WHERE $where ORDER BY $orderBy LIMIT ?, ?",
            $types,
            $params
        );

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Single loading order with its items
     */
    public function getById(int $id): ?array
    {
        $order = $this->findOrder($id);
        if (!$order) {
            return null;
        }

        $shipment = $this->fetchOne("SELECT shipment_type FROM shipment_types WHERE id = ?", 'i', [$order['shipment_type']]);

        $items = $this->fetchAll(
            "SELECT loi.id, loi.packaging_batch_item_id, pbi.packaging_batch_id, IFNULL(pb.batch_no, '') AS batch_no,
                    loi.customer_id, IFNULL(c.customer_name, '') AS customer_name, loi.product_id, IFNULL(p.product_name, '') AS product_name,
                    loi.grade, IFNULL(g.units, '') AS grade_name, loi.packaging_size, IFNULL(pkg.packaging_name, '') AS packaging_size_name,
                    loi.units_per_box, loi.weight, DATE_FORMAT(loi.loading_time, '%H:%i') AS loading_time, IFNULL(loi.remarks, '') AS remarks
             FROM loading_order_items loi
             LEFT JOIN packaging_batch_items pbi ON loi.packaging_batch_item_id = pbi.id
             LEFT JOIN packaging_batches pb ON pbi.packaging_batch_id = pb.id
             LEFT JOIN customers c ON loi.customer_id = c.id
             LEFT JOIN products p ON loi.product_id = p.id
             LEFT JOIN grades g ON loi.grade = g.id
             LEFT JOIN packaging pkg ON loi.packaging_size = pkg.id
             WHERE loi.loading_order_id = ? AND loi.deleted = 0
             ORDER BY loi.id ASC",
            'i',
            [$id]
        );

        return [
            'id' => $order['id'],
            'loading_no' => $order['loading_no'],
            'loading_date' => $order['loading_date'],
            'shipment_type' => $order['shipment_type'],
            'shipmentType' => $shipment['shipment_type'] ?? '',
            'remarks' => $order['remarks'],
            'status' => $order['status'],
            'company' => $order['company'],
            'items' => $items
        ];
    }

    /**
     * Items of a batch that can be loaded: pending items plus, when editing, the items already on that loading order
     */
    public function getBatchItems(int $batchId, int $orderId = 0): ?array
    {
        $where = "id = ? AND deleted = 0";
        $params = [$batchId];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types, 'company');
        if (!$this->fetchOne("SELECT id FROM packaging_batches WHERE $where", $types, $params)) {
            return null;
        }

        return $this->fetchAll(
            "SELECT pbi.id, pbi.packaging_batch_id, pb.batch_no, pbi.product_id, IFNULL(p.product_name, '') AS product_name,
                    pbi.grade, IFNULL(g.units, '') AS grade_name, pbi.packaging_size, IFNULL(pkg.packaging_name, '') AS packaging_size_name,
                    pbi.units_per_box, pbi.weight, loi.customer_id, DATE_FORMAT(loi.loading_time, '%H:%i') AS loading_time, IFNULL(loi.remarks, '') AS remarks
             FROM packaging_batch_items pbi
             INNER JOIN packaging_batches pb ON pbi.packaging_batch_id = pb.id
             LEFT JOIN loading_order_items loi ON loi.packaging_batch_item_id = pbi.id AND loi.loading_order_id = ? AND loi.deleted = 0
             LEFT JOIN products p ON pbi.product_id = p.id
             LEFT JOIN grades g ON pbi.grade = g.id
             LEFT JOIN packaging pkg ON pbi.packaging_size = pkg.id
             WHERE pbi.packaging_batch_id = ? AND pbi.deleted = 0 AND (pbi.status = 'pending' OR loi.id IS NOT NULL)
             ORDER BY pbi.id ASC",
            'ii',
            [$orderId, $batchId]
        );
    }

    /**
     * Create or update a loading order (one transaction).
     * $items: [['packaging_batch_item_id', 'customer_id', 'loading_time' (H:i), 'remarks'], ...]
     * Product / grade / packaging / weight are copied from the packaging batch item.
     */
    public function save(int $id, array $header, array $items): array
    {
        $existing = null;
        if ($id) {
            $existing = $this->findOrder($id);
            if (!$existing) {
                return ['status' => 'failed', 'message' => 'Record not found'];
            }
        }
        $recordCompany = $existing ? (int)$existing['company'] : $this->company;

        $prevItems = $existing ? $this->fetchAll("SELECT * FROM loading_order_items WHERE loading_order_id = ? AND deleted = 0", 'i', [$id]) : [];
        $prevMap = [];
        foreach ($prevItems as $prev) {
            $prevMap[(int)$prev['packaging_batch_item_id']] = $prev;
        }

        $batchItems = $this->validate($header, $items, $recordCompany, $prevMap);
        if (is_string($batchItems)) {
            return ['status' => 'failed', 'message' => $batchItems];
        }

        $loadingDay = date('Y-m-d', strtotime($header['loading_date']));
        $this->db->begin_transaction();

        try {
            if ($existing) {
                if (!$this->updateRow('loading_orders', $header + ['modified_by' => $this->user], "id = ?", 'i', [$id])) {
                    throw new \Exception('Failed to update loading order');
                }
            } else {
                $id = $this->insertRow('loading_orders', $header + [
                    'loading_no' => $this->nextLoadingNo($recordCompany),
                    'company' => $recordCompany,
                    'created_by' => $this->user,
                    'status' => 'pending'
                ]);
                if (!$id) {
                    throw new \Exception('Failed to insert loading order');
                }
            }

            // Release every previous item and reverse its stock before applying the new list
            $affectedBatches = [];
            foreach ($prevItems as $prev) {
                $affectedBatches[] = $this->releaseBatchItem((int)$prev['packaging_batch_item_id']);
                $this->moveStock($prev, $recordCompany, $id, 'REVERSAL');
            }

            $newIds = array_column($items, 'packaging_batch_item_id');
            foreach ($prevMap as $batchItemId => $prev) {
                if (!in_array($batchItemId, $newIds, true)
                    && !$this->executeWrite("UPDATE loading_order_items SET deleted = 1 WHERE id = ?", 'i', [(int)$prev['id']])) {
                    throw new \Exception('Failed to remove loading order item');
                }
            }

            foreach ($items as $item) {
                $batchItem = $batchItems[$item['packaging_batch_item_id']];
                $data = [
                    'customer_id' => $item['customer_id'],
                    'loading_time' => $loadingDay . ' ' . $item['loading_time'] . ':00',
                    'remarks' => $item['remarks']
                ];

                if (isset($prevMap[$item['packaging_batch_item_id']])) {
                    if (!$this->updateRow('loading_order_items', $data, "id = ?", 'i', [(int)$prevMap[$item['packaging_batch_item_id']]['id']])) {
                        throw new \Exception('Failed to update loading order item');
                    }
                } elseif (!$this->insertRow('loading_order_items', $data + [
                    'loading_order_id' => $id,
                    'packaging_batch_item_id' => $item['packaging_batch_item_id'],
                    'product_id' => $batchItem['product_id'],
                    'grade' => $batchItem['grade'],
                    'packaging_size' => $batchItem['packaging_size'],
                    'units_per_box' => $batchItem['units_per_box'],
                    'weight' => $batchItem['weight']
                ])) {
                    throw new \Exception('Failed to insert loading order item');
                }

                if (!$this->executeWrite("UPDATE packaging_batch_items SET status = 'completed' WHERE id = ?", 'i', [$item['packaging_batch_item_id']])) {
                    throw new \Exception('Failed to complete packaging batch item');
                }
                $this->moveStock($batchItem + ['customer_id' => $item['customer_id']], $recordCompany, $id, 'DISPATCH');
                $affectedBatches[] = (int)$batchItem['packaging_batch_id'];
            }

            foreach (array_unique(array_filter($affectedBatches)) as $batchId) {
                syncBatchStatus($this->db, $batchId, $this->user);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('LoadingOrderService::save - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to save record'];
        }

        return ['status' => 'success', 'message' => $existing ? 'Updated Successfully!!' : 'Added Successfully!!'];
    }

    /**
     * Soft delete a loading order with a reason, release its batch items and reverse its stock
     */
    public function cancel(int $id, string $reason): array
    {
        $order = $this->findOrder($id);
        if (!$order) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        $this->db->begin_transaction();

        try {
            $affectedBatches = [];
            foreach ($this->fetchAll("SELECT * FROM loading_order_items WHERE loading_order_id = ? AND deleted = 0", 'i', [$id]) as $item) {
                $affectedBatches[] = $this->releaseBatchItem((int)$item['packaging_batch_item_id']);
                $this->moveStock($item, (int)$order['company'], $id, 'REVERSAL');
            }

            if (!$this->executeWrite("UPDATE loading_orders SET deleted = 1, delete_reason = ?, modified_by = ? WHERE id = ?", 'sii', [$reason, $this->user, $id])
                || !$this->executeWrite("UPDATE loading_order_items SET deleted = 1 WHERE loading_order_id = ?", 'i', [$id])) {
                throw new \Exception('Failed to delete loading order');
            }

            foreach (array_unique(array_filter($affectedBatches)) as $batchId) {
                syncBatchStatus($this->db, $batchId, $this->user);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('LoadingOrderService::cancel - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to delete record'];
        }

        return ['status' => 'success', 'message' => 'Deleted Successfully!!'];
    }

    /**
     * Loading order row within the session company (SADMIN: any)
     */
    private function findOrder(int $id): ?array
    {
        $where = "id = ? AND deleted = 0";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types, 'company');

        return $this->fetchOne("SELECT * FROM loading_orders WHERE $where", $types, $params);
    }

    /**
     * Shipment type, customers and batch items must belong to the record's company; batch items must be pending
     * or already on this order. Returns the batch item rows keyed by id, or an error message.
     *
     * @return array|string
     */
    private function validate(array $header, array $items, int $company, array $prevMap)
    {
        if (empty($items)) {
            return 'Please select at least one batch with items';
        }

        if (!$this->fetchOne("SELECT id FROM shipment_types WHERE id = ? AND customer = ? AND deleted = '0'", 'ii', [$header['shipment_type'], $company])) {
            return 'Invalid shipment type';
        }

        $customerIds = array_values(array_unique(array_column($items, 'customer_id')));
        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total FROM customers WHERE customer = ? AND deleted = '0' AND id IN (" . $this->placeholders(count($customerIds)) . ")",
            'i' . str_repeat('i', count($customerIds)),
            array_merge([$company], $customerIds)
        );
        if ((int)$row['total'] !== count($customerIds)) {
            return 'Invalid customer';
        }

        $batchItemIds = array_column($items, 'packaging_batch_item_id');
        if (count($batchItemIds) !== count(array_unique($batchItemIds))) {
            return 'Duplicate batch items';
        }

        $rows = $this->fetchAll(
            "SELECT pbi.* FROM packaging_batch_items pbi INNER JOIN packaging_batches pb ON pbi.packaging_batch_id = pb.id
             WHERE pb.company = ? AND pb.deleted = '0' AND pbi.deleted = '0' AND pbi.id IN (" . $this->placeholders(count($batchItemIds)) . ")",
            'i' . str_repeat('i', count($batchItemIds)),
            array_merge([$company], $batchItemIds)
        );

        $batchItems = [];
        foreach ($rows as $batchItem) {
            if ($batchItem['status'] !== 'pending' && !isset($prevMap[(int)$batchItem['id']])) {
                return 'Some batch items have already been loaded';
            }
            $batchItems[(int)$batchItem['id']] = $batchItem;
        }
        if (count($batchItems) !== count($batchItemIds)) {
            return 'Invalid batch item';
        }

        return $batchItems;
    }

    /**
     * Set a packaging batch item back to pending; returns its batch id
     */
    private function releaseBatchItem(int $batchItemId): int
    {
        if (!$this->executeWrite("UPDATE packaging_batch_items SET status = 'pending' WHERE id = ?", 'i', [$batchItemId])) {
            throw new \Exception('Failed to release packaging batch item');
        }
        $row = $this->fetchOne("SELECT packaging_batch_id FROM packaging_batch_items WHERE id = ?", 'i', [$batchItemId]);

        return (int)($row['packaging_batch_id'] ?? 0);
    }

    /**
     * Dispatch / reverse one box of packaged stock for an item (when stock is enabled).
     * Older batch items store the packaging size as text, which has no box stock balance, so they are skipped.
     */
    private function moveStock(array $item, int $company, int $orderId, string $action): void
    {
        if (!$this->stockEnabled || !ctype_digit((string)$item['packaging_size'])) {
            return;
        }

        $result = processPackagedStock($this->db, $item['product_id'], $item['grade'], $item['packaging_size'], 1, $company, $this->user, $orderId, $item['customer_id'], $action);
        if (($result['status'] ?? '') !== 'success') {
            throw new \Exception('Failed to update packaged stock: ' . ($result['message'] ?? ''));
        }
    }

    /**
     * LO + today (Ymd) + running number starting at the company's orders created today + 1
     */
    private function nextLoadingNo(int $company): string
    {
        $row = $this->fetchOne("SELECT COUNT(*) AS total FROM loading_orders WHERE company = ? AND created_date >= ?", 'is', [$company, date('Y-m-d 00:00:00')]);

        // The free-number check covers every company, as before
        return $this->nextRunningNo('loading_orders', 'loading_no', 'LO' . date('Ymd'), null, (int)($row['total'] ?? 0) + 1);
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
            $where .= " AND lo.loading_date >= ?";
            $params[] = $from->format('Y-m-d 00:00:00');
            $types .= 's';
        }

        if (($to = \DateTime::createFromFormat('d/m/Y', $value('toDate'))) !== false) {
            $where .= " AND lo.loading_date <= ?";
            $params[] = $to->format('Y-m-d 23:59:59');
            $types .= 's';
        }

        if ($value('status') !== '') {
            $where .= " AND lo.status = ?";
            $params[] = $value('status');
            $types .= 's';
        }

        if ($value('shipmentType') !== '') {
            $where .= " AND lo.shipment_type = ?";
            $params[] = (int)$value('shipmentType');
            $types .= 'i';
        }
    }
}
