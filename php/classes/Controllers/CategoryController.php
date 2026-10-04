<?php
namespace App\Controllers;

use App\Services\CategoryService;

class CategoryController
{
    private CategoryService $service;

    public function __construct(CategoryService $service)
    {
        $this->service = $service;
    }

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
            error_log('CategoryController::list - ' . $e->getMessage());
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
     * Get single category by ID
     */
    public function get(): array
    {
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }

        try {
            $category = $this->service->getById($id);
        } catch (\Exception $e) {
            error_log('CategoryController::get - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        if (!$category) {
            return ['status' => 'failed', 'message' => 'Category not found'];
        }

        return ['status' => 'success', 'message' => $category];
    }

    /**
     * Create or update category
     */
    public function save(): array
    {
        $id = (int)($_POST['id'] ?? 0);
        $categoryName = trim($_POST['categoryName'] ?? '');
        $company = (int)($_POST['company'] ?? 0);
        $module = trim($_POST['module'] ?? '');

        if ($categoryName === '' || !$company || $module === '') {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        if ($id) {
            return $this->service->update($id, $categoryName, $module);
        }

        return $this->service->create($categoryName, $module, $company);
    }

    /**
     * Soft delete single or multiple categories
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
     * Bulk upload categories from JSON body
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
