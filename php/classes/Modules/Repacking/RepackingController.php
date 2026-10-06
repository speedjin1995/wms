<?php
namespace App\Modules\Repacking;

use App\Core\BaseController;

class RepackingController extends BaseController
{
    private RepackingService $service;

    public function __construct(RepackingService $service)
    {
        $this->service = $service;
    }

    /**
     * DataTables server-side list
     */
    public function list(): array
    {
        $p = $this->dataTableParams('repacking_date', 'desc');
        $filters = [
            'fromDate' => trim($_POST['fromDate'] ?? ''),
            'toDate' => trim($_POST['toDate'] ?? ''),
            'type' => trim($_POST['typeFilter'] ?? ''),
            'category' => (int)($_POST['categoryFilter'] ?? 0),
            'sourceProduct' => (int)($_POST['sourceProductFilter'] ?? 0),
            'targetProduct' => (int)($_POST['targetProductFilter'] ?? 0)
        ];

        try {
            $result = $this->service->getList($p['start'], $p['length'], $p['orderColumn'], $p['orderDir'], $p['search'], $filters);
        } catch (\Exception $e) {
            error_log('RepackingController::list - ' . $e->getMessage());
            $result = $this->emptyListResult();
        }

        return $this->dataTableResponse($p['draw'], $result);
    }

    /**
     * Dashboard summary for the wholesales dashboard tab
     */
    public function dashboard(): array
    {
        $filters = [
            'fromDate' => trim($_POST['fromDate'] ?? ''),
            'toDate' => trim($_POST['toDate'] ?? ''),
            'category' => (int)($_POST['category'] ?? 0)
        ];

        try {
            return ['status' => 'success', 'message' => $this->service->getDashboard($filters)];
        } catch (\Exception $e) {
            error_log('RepackingController::dashboard - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }
    }

    /**
     * Source and target product options for the form
     */
    public function options(): array
    {
        try {
            return ['status' => 'success', 'message' => $this->service->getOptions()];
        } catch (\Exception $e) {
            error_log('RepackingController::options - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }
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
            error_log('RepackingController::get - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        if (!$record) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success', 'message' => $record];
    }

    /**
     * Create or update repacking
     */
    public function save(): array
    {
        $id = $this->postId();
        $repackingDate = trim($_POST['repackingDate'] ?? '');
        $type = trim($_POST['productType'] ?? 'Local');
        $sourceCategory = (int)($_POST['sourceCategory'] ?? 0);
        $targetCategory = (int)($_POST['targetCategory'] ?? 0);
        $sourceProduct = (int)($_POST['sourceProduct'] ?? 0);
        $sourceGrade = (int)($_POST['sourceGrade'] ?? 0);
        $sourceWeight = (float)($_POST['productWeight'] ?? 0);
        $itemProducts = $_POST['itemsRepack'] ?? [];
        $itemGrades = $_POST['itemGrade'] ?? [];
        $itemWeights = $_POST['itemWeight'] ?? [];

        if (!is_array($itemProducts) || !is_array($itemGrades) || !is_array($itemWeights)) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        $items = [];
        foreach ($itemProducts as $key => $productId) {
            $items[] = [
                'product_id' => (int)$productId,
                'grade_id' => (int)($itemGrades[$key] ?? 0),
                'weight' => (float)($itemWeights[$key] ?? 0)
            ];
        }

        if ($id) {
            return $this->service->update($id, $repackingDate, $type, $sourceCategory, $targetCategory, $sourceProduct, $sourceGrade, $sourceWeight, $items);
        }

        return $this->service->create($repackingDate, $type, $sourceCategory, $targetCategory, $sourceProduct, $sourceGrade, $sourceWeight, $items);
    }

    /**
     * Delete repacking and reverse its stock movement
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
