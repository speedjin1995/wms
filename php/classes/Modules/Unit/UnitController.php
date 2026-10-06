<?php
namespace App\Modules\Unit;

use App\Core\BaseController;

class UnitController extends BaseController
{
    private UnitService $service;

    public function __construct(UnitService $service)
    {
        $this->service = $service;
    }

    /**
     * DataTables server-side list
     */
    public function list(): array
    {
        $p = $this->dataTableParams();

        try {
            $result = $this->service->getList($p['start'], $p['length'], $p['orderColumn'], $p['orderDir'], $p['search']);
        } catch (\Exception $e) {
            error_log('UnitController::list - ' . $e->getMessage());
            $result = $this->emptyListResult();
        }

        return $this->dataTableResponse($p['draw'], $result);
    }

    /**
     * Get single record by ID
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
            error_log('UnitController::get - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        if (!$record) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success', 'message' => $record];
    }

    /**
     * Create or update record
     */
    public function save(): array
    {
        $id = $this->postId();

        $data = $this->collectFields([
            'code' => ['column' => 'units', 'required' => true]
        ]);

        if ($data === null) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        if ($id) {
            return $this->service->update($id, $data);
        }

        return $this->service->create($data);
    }

    /**
     * Soft delete single or multiple records
     */
    public function delete(): array
    {
        return $this->service->delete($this->postIds());
    }

    /**
     * Reactivate soft deleted record
     */
    public function reactivate(): array
    {
        $id = $this->postId();

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        return $this->service->reactivate($id);
    }
}
