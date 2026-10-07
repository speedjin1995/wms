<?php
namespace App\Modules\LoadingOrder;

use App\Core\BaseController;

class LoadingOrderController extends BaseController
{
    private LoadingOrderService $service;

    public function __construct(LoadingOrderService $service)
    {
        $this->service = $service;
    }

    /**
     * DataTables server-side list
     */
    public function list(): array
    {
        $p = $this->dataTableParams('loading_date');

        try {
            $result = $this->service->getList($this->filters($_POST), $p['start'], $p['length'], $p['orderColumn'], $p['orderDir'], $p['search']);
        } catch (\Exception $e) {
            error_log('LoadingOrderController::list - ' . $e->getMessage());
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
            error_log('LoadingOrderController::get - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        if (!$record) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success', 'message' => $record];
    }

    /**
     * Loadable items of a packaging batch (orderId: include the items already on that order)
     */
    public function batchItems(): array
    {
        $batchId = $this->postId('batchId');

        if (!$batchId) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }

        $items = $this->service->getBatchItems($batchId, $this->postId('orderId'));
        if ($items === null) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success', 'items' => $items];
    }

    /**
     * Create or update record with its items (requires allow_add / allow_edit)
     */
    public function save(): array
    {
        $id = $this->postId();
        $permissions = $this->service->getPermissions();

        if (($id && !$permissions['allowEdit']) || (!$id && !$permissions['allowAdd'])) {
            return ['status' => 'failed', 'message' => 'You do not have permission to perform this action'];
        }

        $loadingDate = $this->postDateTime('loadingDate');
        $shipmentType = $this->postId('shipmentType');

        if ($loadingDate === null || !$shipmentType) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        $items = $this->postItems();
        if ($items === null) {
            return ['status' => 'failed', 'message' => 'Please select a customer and time for every item'];
        }

        $header = [
            'loading_date' => $loadingDate,
            'shipment_type' => $shipmentType,
            'remarks' => $this->optionalText('remarks')
        ];

        return $this->service->save($id, $header, $items);
    }

    /**
     * Cancel record with reason (requires allow_delete)
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

    /**
     * List filter values from a request array
     */
    public function filters(array $source): array
    {
        $filters = [];
        foreach (['fromDate', 'toDate', 'status', 'shipmentType'] as $key) {
            $filters[$key] = (string)($source[$key] ?? '');
        }

        return $filters;
    }

    /**
     * Item rows from POST. Null when a row has no batch item, customer or valid time.
     */
    private function postItems(): ?array
    {
        $rows = $_POST['items'] ?? [];
        if (!is_array($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $batchItemId = (int)($row['packaging_batch_item_id'] ?? 0);
            $customerId = (int)($row['customer_id'] ?? 0);
            $time = trim((string)($row['loading_time'] ?? ''));
            $remarks = trim((string)($row['remarks'] ?? ''));

            if (!$batchItemId || !$customerId || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', substr($time, 0, 5))) {
                return null;
            }

            $items[] = [
                'packaging_batch_item_id' => $batchItemId,
                'customer_id' => $customerId,
                'loading_time' => substr($time, 0, 5),
                'remarks' => $remarks === '' ? null : $remarks
            ];
        }

        return $items;
    }
}
