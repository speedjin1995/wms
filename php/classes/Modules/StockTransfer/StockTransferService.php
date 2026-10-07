<?php
namespace App\Modules\StockTransfer;

use App\Core\BaseService;

require_once __DIR__ . '/../../../services/batchStatusService.php';

/**
 * Stock transfers (stock_transfers + stock_transfer_items): move pending packaging batch items between two batches.
 * Undo moves every item back to the batch it came from. Non-SADMIN users are scoped to the session company.
 */
class StockTransferService extends BaseService
{
    /**
     * Batches that can take part in a transfer (not completed)
     */
    public function getLookups(): array
    {
        if ($this->isSuperAdmin()) {
            return ['batches' => $this->fetchAll("SELECT id, batch_no FROM packaging_batches WHERE deleted = '0' AND status != 'completed' ORDER BY packaging_date DESC")];
        }

        return ['batches' => $this->fetchAll("SELECT id, batch_no FROM packaging_batches WHERE deleted = '0' AND status != 'completed' AND company = ? ORDER BY packaging_date DESC", 'i', [$this->company])];
    }

    /**
     * Get paginated transfers for DataTables (dates filter on created_date)
     */
    public function getList(array $filters, int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $from = "stock_transfers st LEFT JOIN packaging_batches pb1 ON st.from_batch_id = pb1.id LEFT JOIN packaging_batches pb2 ON st.to_batch_id = pb2.id";
        $where = "st.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'st.company');

        $totalRecords = $this->countRows('stock_transfers st', $where, $types, $params);

        if (($fromDate = \DateTime::createFromFormat('d/m/Y', trim((string)($filters['fromDate'] ?? '')))) !== false) {
            $where .= " AND st.created_date >= ?";
            $params[] = $fromDate->format('Y-m-d 00:00:00');
            $types .= 's';
        }
        if (($toDate = \DateTime::createFromFormat('d/m/Y', trim((string)($filters['toDate'] ?? '')))) !== false) {
            $where .= " AND st.created_date <= ?";
            $params[] = $toDate->format('Y-m-d 23:59:59');
            $types .= 's';
        }
        $this->applySearch($where, $params, $types, ['st.transfer_no'], $search);
        $totalFiltered = $this->countRows($from, $where, $types, $params);

        $orderBy = $this->orderBy([
            'transfer_no' => 'st.transfer_no',
            'from_batch_no' => 'pb1.batch_no',
            'to_batch_no' => 'pb2.batch_no',
            'created_date' => 'st.created_date',
            'remarks' => 'st.remarks'
        ], $orderColumn, $orderDir, 'st.created_date');
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $data = $this->fetchAll(
            "SELECT st.id, st.transfer_no, IFNULL(pb1.batch_no, '') AS from_batch_no, IFNULL(pb2.batch_no, '') AS to_batch_no,
                    DATE_FORMAT(st.created_date, '%d/%m/%Y %H:%i') AS created_date, IFNULL(st.remarks, '') AS remarks, st.company
             FROM $from WHERE $where ORDER BY $orderBy LIMIT ?, ?",
            $types,
            $params
        );

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Single transfer with its items
     */
    public function getById(int $id): ?array
    {
        $transfer = $this->findTransfer($id);
        if (!$transfer) {
            return null;
        }

        $header = $this->fetchOne(
            "SELECT IFNULL(pb1.batch_no, '') AS from_batch_no, IFNULL(pb2.batch_no, '') AS to_batch_no, IFNULL(u.name, '') AS created_by_name
             FROM stock_transfers st
             LEFT JOIN packaging_batches pb1 ON st.from_batch_id = pb1.id
             LEFT JOIN packaging_batches pb2 ON st.to_batch_id = pb2.id
             LEFT JOIN users u ON st.created_by = u.id
             WHERE st.id = ?",
            'i',
            [$id]
        );

        $items = $this->fetchAll(
            "SELECT sti.id, sti.packaging_batch_item_id, IFNULL(fb.batch_no, '') AS from_batch_no, IFNULL(tb.batch_no, '') AS to_batch_no,
                    IFNULL(p.product_name, '') AS product_name, IFNULL(g.units, '') AS grade_name, IFNULL(pkg.packaging_name, pbi.packaging_size) AS packaging_size_name,
                    pbi.units_per_box, pbi.weight
             FROM stock_transfer_items sti
             LEFT JOIN packaging_batch_items pbi ON sti.packaging_batch_item_id = pbi.id
             LEFT JOIN packaging_batches fb ON sti.from_batch_id = fb.id
             LEFT JOIN packaging_batches tb ON sti.to_batch_id = tb.id
             LEFT JOIN products p ON pbi.product_id = p.id
             LEFT JOIN grades g ON pbi.grade = g.id
             LEFT JOIN packaging pkg ON pbi.packaging_size = pkg.id
             WHERE sti.stock_transfer_id = ? AND sti.deleted = 0
             ORDER BY sti.id ASC",
            'i',
            [$id]
        );

        return [
            'id' => $transfer['id'],
            'transfer_no' => $transfer['transfer_no'],
            'from_batch_no' => $header['from_batch_no'] ?? '',
            'to_batch_no' => $header['to_batch_no'] ?? '',
            'created_date' => $transfer['created_date'],
            'created_by_name' => $header['created_by_name'] ?? '',
            'remarks' => $transfer['remarks'],
            'items' => $items
        ];
    }

    /**
     * Pending items of a batch within the session company (null when the batch is not found)
     */
    public function getBatchItems(int $batchId): ?array
    {
        if (!$this->findBatch($batchId)) {
            return null;
        }

        return $this->fetchAll(
            "SELECT pbi.id, pbi.packaging_batch_id, IFNULL(p.product_name, '') AS product_name, IFNULL(g.units, '') AS grade_name,
                    IFNULL(pkg.packaging_name, pbi.packaging_size) AS packaging_size_name, pbi.units_per_box, pbi.weight
             FROM packaging_batch_items pbi
             LEFT JOIN products p ON pbi.product_id = p.id
             LEFT JOIN grades g ON pbi.grade = g.id
             LEFT JOIN packaging pkg ON pbi.packaging_size = pkg.id
             WHERE pbi.packaging_batch_id = ? AND pbi.deleted = 0 AND pbi.status = 'pending'
             ORDER BY pbi.id ASC",
            'i',
            [$batchId]
        );
    }

    /**
     * Create a transfer between batch A (from_batch_id) and batch B (to_batch_id) in one transaction.
     * $moves: [packaging_batch_item_id => target batch id]; each item must be pending and currently in the other batch.
     */
    public function save(int $batchA, int $batchB, ?string $remarks, array $moves): array
    {
        if ($batchA === $batchB) {
            return ['status' => 'failed', 'message' => 'Batch A and Batch B must be different'];
        }

        $a = $this->findBatch($batchA);
        $b = $this->findBatch($batchB);
        if (!$a || !$b || $a['status'] === 'completed' || $b['status'] === 'completed' || $a['company'] !== $b['company']) {
            return ['status' => 'failed', 'message' => 'Invalid batch'];
        }

        $itemIds = array_keys($moves);
        $rows = $this->fetchAll(
            "SELECT id, packaging_batch_id FROM packaging_batch_items
             WHERE deleted = 0 AND status = 'pending' AND packaging_batch_id IN (?, ?) AND id IN (" . $this->placeholders(count($itemIds)) . ")",
            'ii' . str_repeat('i', count($itemIds)),
            array_merge([$batchA, $batchB], $itemIds)
        );

        $current = array_column($rows, 'packaging_batch_id', 'id');
        foreach ($moves as $itemId => $target) {
            if (!isset($current[$itemId]) || !in_array($target, [$batchA, $batchB], true) || (int)$current[$itemId] === $target) {
                return ['status' => 'failed', 'message' => 'Some items are no longer available for transfer'];
            }
        }

        $this->db->begin_transaction();

        try {
            $transferId = $this->insertRow('stock_transfers', [
                'transfer_no' => $this->nextTransferNo((int)$a['company']),
                'from_batch_id' => $batchA,
                'to_batch_id' => $batchB,
                'remarks' => $remarks,
                'company' => (int)$a['company'],
                'created_by' => $this->user
            ]);
            if (!$transferId) {
                throw new \Exception('Failed to insert stock transfer');
            }

            foreach ($moves as $itemId => $target) {
                $source = (int)$current[$itemId];
                if (!$this->insertRow('stock_transfer_items', [
                    'stock_transfer_id' => $transferId,
                    'packaging_batch_item_id' => $itemId,
                    'from_batch_id' => $source,
                    'to_batch_id' => $target
                ]) || !$this->executeWrite("UPDATE packaging_batch_items SET packaging_batch_id = ? WHERE id = ?", 'ii', [$target, $itemId])) {
                    throw new \Exception('Failed to move packaging batch item');
                }
            }

            syncBatchStatus($this->db, $batchA, $this->user);
            syncBatchStatus($this->db, $batchB, $this->user);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('StockTransferService::save - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to save record'];
        }

        return ['status' => 'success', 'message' => 'Transfer saved successfully!!'];
    }

    /**
     * Undo a transfer: move every item back to its source batch and soft delete the transfer
     */
    public function cancel(int $id, string $reason): array
    {
        $transfer = $this->findTransfer($id);
        if (!$transfer) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        $items = $this->fetchAll(
            "SELECT sti.*, pbi.packaging_batch_id AS current_batch_id FROM stock_transfer_items sti
             LEFT JOIN packaging_batch_items pbi ON sti.packaging_batch_item_id = pbi.id
             WHERE sti.stock_transfer_id = ? AND sti.deleted = 0",
            'i',
            [$id]
        );

        // An item moved again by a later transfer can only be undone from that transfer first
        foreach ($items as $item) {
            if ((int)$item['current_batch_id'] !== (int)$item['to_batch_id']) {
                return ['status' => 'failed', 'message' => 'Some items have been moved by a later transfer. Undo that transfer first.'];
            }
        }

        $this->db->begin_transaction();

        try {
            foreach ($items as $item) {
                if (!$this->executeWrite("UPDATE packaging_batch_items SET packaging_batch_id = ? WHERE id = ?", 'ii', [(int)$item['from_batch_id'], (int)$item['packaging_batch_item_id']])
                    || !$this->executeWrite("UPDATE stock_transfer_items SET deleted = 1 WHERE id = ?", 'i', [(int)$item['id']])) {
                    throw new \Exception('Failed to revert packaging batch item');
                }
            }

            if (!$this->executeWrite("UPDATE stock_transfers SET deleted = 1, delete_reason = ?, modified_by = ? WHERE id = ?", 'sii', [$reason, $this->user, $id])) {
                throw new \Exception('Failed to delete stock transfer');
            }

            syncBatchStatus($this->db, (int)$transfer['from_batch_id'], $this->user);
            syncBatchStatus($this->db, (int)$transfer['to_batch_id'], $this->user);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('StockTransferService::cancel - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to undo transfer'];
        }

        return ['status' => 'success', 'message' => 'Transfer reverted successfully!!'];
    }

    /**
     * Transfer row within the session company (SADMIN: any)
     */
    private function findTransfer(int $id): ?array
    {
        $where = "id = ? AND deleted = 0";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types, 'company');

        return $this->fetchOne("SELECT * FROM stock_transfers WHERE $where", $types, $params);
    }

    /**
     * Packaging batch row within the session company (SADMIN: any)
     */
    private function findBatch(int $id): ?array
    {
        $where = "id = ? AND deleted = 0";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types, 'company');

        return $this->fetchOne("SELECT id, status, company FROM packaging_batches WHERE $where", $types, $params);
    }

    /**
     * ST + today (Ymd) + running number starting at the company's transfers created today + 1
     */
    private function nextTransferNo(int $company): string
    {
        $row = $this->fetchOne("SELECT COUNT(*) AS total FROM stock_transfers WHERE company = ? AND created_date >= ?", 'is', [$company, date('Y-m-d 00:00:00')]);

        // The free-number check covers every company, as before
        return $this->nextRunningNo('stock_transfers', 'transfer_no', 'ST' . date('Ymd'), null, (int)($row['total'] ?? 0) + 1);
    }
}
