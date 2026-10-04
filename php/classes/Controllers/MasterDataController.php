<?php
namespace App\Controllers;

use App\Services\MasterDataService;

/**
 * Base controller for simple master data screens.
 */
abstract class MasterDataController
{
    protected MasterDataService $service;

    public function __construct(MasterDataService $service)
    {
        $this->service = $service;
    }

    /**
     * Form fields: POST name => ['column' => db column, 'required' => bool]
     */
    abstract protected function fields(): array;

    /**
     * DataTables server-side list
     */
    public function list(): array
    {
        $draw = (int)($_POST['draw'] ?? 0);
        $start = (int)($_POST['start'] ?? 0);
        $length = (int)($_POST['length'] ?? 10);
        $columnIndex = $_POST['order'][0]['column'] ?? 0;
        $orderColumn = $_POST['columns'][$columnIndex]['data'] ?? 'id';
        $orderDir = $_POST['order'][0]['dir'] ?? 'asc';
        $search = trim($_POST['search']['value'] ?? '');

        try {
            $result = $this->service->getList($start, $length, (string)$orderColumn, (string)$orderDir, $search);
        } catch (\Exception $e) {
            error_log(static::class . '::list - ' . $e->getMessage());
            $result = ['totalRecords' => 0, 'totalFiltered' => 0, 'data' => []];
        }

        return [
            'draw' => $draw,
            'iTotalRecords' => $result['totalRecords'],
            'iTotalDisplayRecords' => $result['totalFiltered'],
            'aaData' => $result['data']
        ];
    }

    /**
     * Get single record by ID
     */
    public function get(): array
    {
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }

        try {
            $record = $this->service->getById($id);
        } catch (\Exception $e) {
            error_log(static::class . '::get - ' . $e->getMessage());
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
        $id = (int)($_POST['id'] ?? 0);
        $company = (int)($_POST['company'] ?? 0);

        $data = [];
        foreach ($this->fields() as $name => $field) {
            $value = trim((string)($_POST[$name] ?? ''));

            if ($value === '') {
                if (!empty($field['required'])) {
                    return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
                }
                $value = null;
            }

            $data[$field['column']] = $value;
        }

        if ($id) {
            return $this->service->update($id, $data, $company);
        }

        return $this->service->create($data, $company);
    }

    /**
     * Soft delete single or multiple records
     */
    public function delete(): array
    {
        $ids = $_POST['ids'] ?? [];

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        return $this->service->delete($ids);
    }

    /**
     * Reactivate soft deleted record
     */
    public function reactivate(): array
    {
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        return $this->service->reactivate($id);
    }

    /**
     * Bulk upload records from JSON body
     */
    public function upload(): array
    {
        $rows = json_decode(file_get_contents('php://input'), true);

        if (empty($rows) || !is_array($rows)) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        return $this->service->upload($rows);
    }
}
