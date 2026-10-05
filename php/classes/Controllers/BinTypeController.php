<?php
namespace App\Controllers;

use App\Services\BinTypeService;

class BinTypeController extends BaseController
{
    private BinTypeService $service;

    public function __construct(BinTypeService $service)
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
            error_log('BinTypeController::list - ' . $e->getMessage());
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
            error_log('BinTypeController::get - ' . $e->getMessage());
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
        $binType = $this->postText('binType');
        $company = $this->postId('company');

        if ($binType === '') {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        if ($id) {
            return $this->service->update($id, $binType, $company);
        }

        return $this->service->create($binType, $company);
    }

    /**
     * Soft delete single or multiple records
     */
    public function delete(): array
    {
        return $this->service->delete($this->postIds());
    }
}
