<?php
namespace App\Controllers;

use App\Services\DailySalesSetupService;

class DailySalesSetupController
{
    private DailySalesSetupService $service;

    public function __construct(DailySalesSetupService $service)
    {
        $this->service = $service;
    }

    /**
     * DataTables server-side list
     */
    public function list(array $languageArray, string $language): array
    {
        $draw = (int)($_POST['draw'] ?? 0);
        $start = (int)($_POST['start'] ?? 0);
        $length = (int)($_POST['length'] ?? 10);
        $columnIndex = $_POST['order'][0]['column'] ?? 0;
        $orderColumn = $_POST['columns'][$columnIndex]['data'] ?? 'id';
        $orderDir = $_POST['order'][0]['dir'] ?? 'asc';

        try {
            $result = $this->service->getList($start, $length, (string)$orderColumn, (string)$orderDir);
        } catch (\Exception $e) {
            error_log('DailySalesSetupController::list - ' . $e->getMessage());
            $result = ['totalRecords' => 0, 'totalFiltered' => 0, 'data' => []];
        }

        foreach ($result['data'] as &$row) {
            $row['module'] = formatModules($row['module'], $languageArray, $language);
        }
        unset($row);

        return [
            'draw' => $draw,
            'iTotalRecords' => $result['totalRecords'],
            'iTotalDisplayRecords' => $result['totalFiltered'],
            'aaData' => $result['data']
        ];
    }

    /**
     * Get single setup by ID
     */
    public function get(): array
    {
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }

        try {
            $setup = $this->service->getById($id);
        } catch (\Exception $e) {
            error_log('DailySalesSetupController::get - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        if (!$setup) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success', 'message' => $setup];
    }

    /**
     * Create or update setup
     */
    public function save(): array
    {
        $id = (int)($_POST['id'] ?? 0);
        $module = trim($_POST['module'] ?? '');
        $company = (int)($_POST['company'] ?? 0);
        $states = $_POST['state'] ?? [];

        if (!is_array($states)) {
            $states = [$states];
        }
        // Stored as JSON array of string IDs (read by the weighing modules)
        $states = array_values(array_filter(array_map('intval', $states)));
        $states = array_map('strval', $states);

        if ($module === '' || empty($states)) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        if (!in_array($module, DailySalesSetupService::MODULES, true)) {
            return ['status' => 'failed', 'message' => 'Invalid module'];
        }

        if ($id) {
            return $this->service->update($id, $module, $states);
        }

        return $this->service->create($module, $states, $company);
    }

    /**
     * Soft delete setup
     */
    public function delete(): array
    {
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        return $this->service->delete($id);
    }
}
