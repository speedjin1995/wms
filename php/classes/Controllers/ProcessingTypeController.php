<?php
namespace App\Controllers;

use App\Services\ProcessingTypeService;

class ProcessingTypeController extends BaseController
{
    private ProcessingTypeService $service;

    public function __construct(ProcessingTypeService $service)
    {
        $this->service = $service;
    }

    /**
     * DataTables server-side list
     */
    public function list(): array
    {
        $p = $this->dataTableParams('name', 'asc');

        try {
            $result = $this->service->getList($p['start'], $p['length'], $p['orderColumn'], $p['orderDir'], $p['search']);
        } catch (\Exception $e) {
            error_log('ProcessingTypeController::list - ' . $e->getMessage());
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
            error_log('ProcessingTypeController::get - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        if (!$record) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success', 'message' => $record];
    }

    /**
     * Create or update processing type
     */
    public function save(): array
    {
        $data = $this->collectFields([
            'name' => ['column' => 'name', 'required' => true],
            'description' => ['column' => 'description']
        ]);

        if ($data === null) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        $id = $this->postId();
        $company = (int)($_POST['company'] ?? 0);

        if ($id) {
            return $this->service->update($id, $data, $company);
        }

        return $this->service->create($data, $company);
    }

    /**
     * Soft delete one or many
     */
    public function delete(): array
    {
        return $this->service->delete($this->postIds());
    }

    public function reactivate(): array
    {
        $id = $this->postId();

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        return $this->service->reactivate($id);
    }
}
