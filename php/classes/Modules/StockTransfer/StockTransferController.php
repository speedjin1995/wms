<?php
namespace App\Modules\StockTransfer;

use App\Core\BaseController;

class StockTransferController extends BaseController
{
    private StockTransferService $service;

    public function __construct(StockTransferService $service)
    {
        $this->service = $service;
    }

    /**
     * DataTables server-side list
     */
    public function list(): array
    {
        $p = $this->dataTableParams('created_date');

        try {
            $result = $this->service->getList(
                ['fromDate' => (string)($_POST['fromDate'] ?? ''), 'toDate' => (string)($_POST['toDate'] ?? '')],
                $p['start'],
                $p['length'],
                $p['orderColumn'],
                $p['orderDir'],
                $p['search']
            );
        } catch (\Exception $e) {
            error_log('StockTransferController::list - ' . $e->getMessage());
            $result = $this->emptyListResult();
        }

        return $this->dataTableResponse($p['draw'], $result);
    }

    /**
     * Get single record with items
     */
    public function get(): array
    {
        $id = $this->postId();

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }

        try {
            $record = $this->service->getById($id);
        } catch (\Exception $e) {
            error_log('StockTransferController::get - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        if (!$record) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success', 'message' => $record];
    }

    /**
     * Pending items of a packaging batch
     */
    public function batchItems(): array
    {
        $batchId = $this->postId('batchId');

        if (!$batchId) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }

        $items = $this->service->getBatchItems($batchId);
        if ($items === null) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success', 'items' => $items];
    }

    /**
     * Create a transfer (requires allow_add)
     */
    public function save(): array
    {
        if (!$this->service->getPermissions()['allowAdd']) {
            return ['status' => 'failed', 'message' => 'You do not have permission to perform this action'];
        }

        $batchA = $this->postId('batchA');
        $batchB = $this->postId('batchB');

        if (!$batchA || !$batchB) {
            return ['status' => 'failed', 'message' => 'Please select both batches'];
        }

        $moves = [];
        $rows = $_POST['items'] ?? [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $itemId = (int)($row['packaging_batch_item_id'] ?? 0);
            $target = (int)($row['to_batch_id'] ?? 0);
            if (!$itemId || !$target || isset($moves[$itemId])) {
                return ['status' => 'failed', 'message' => 'Invalid transfer items'];
            }
            $moves[$itemId] = $target;
        }

        if (empty($moves)) {
            return ['status' => 'failed', 'message' => 'No items have been transferred'];
        }

        return $this->service->save($batchA, $batchB, $this->optionalText('remarks'), $moves);
    }

    /**
     * Undo a transfer with reason (requires allow_delete)
     */
    public function cancel(): array
    {
        if (!$this->service->getPermissions()['allowDelete']) {
            return ['status' => 'failed', 'message' => 'You do not have permission to perform this action'];
        }

        $id = $this->postId();
        $reason = $this->postText('cancelReason');

        if (!$id || $reason === '') {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        return $this->service->cancel($id, $reason);
    }
}
