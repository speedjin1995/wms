<?php
namespace App\Controllers;

use App\Services\DailySalesSetupService;

class DailySalesSetupController extends BaseController
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
        $p = $this->dataTableParams();

        try {
            $result = $this->service->getList($p['start'], $p['length'], $p['orderColumn'], $p['orderDir']);
        } catch (\Exception $e) {
            error_log('DailySalesSetupController::list - ' . $e->getMessage());
            $result = $this->emptyListResult();
        }

        foreach ($result['data'] as &$row) {
            $row['module'] = formatModules($row['module'], $languageArray, $language);
        }
        unset($row);

        return $this->dataTableResponse($p['draw'], $result);
    }

    /**
     * Get single setup by ID
     */
    public function get(): array
    {
        $id = $this->postId();

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
        $id = $this->postId();
        $module = trim($_POST['module'] ?? '');
        $company = $this->postId('company');
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
        $id = $this->postId();

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        return $this->service->delete($id);
    }
}
