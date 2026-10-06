<?php
namespace App\Modules\Category;

use App\Core\BaseController;

class CategoryController extends BaseController
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
        $p = $this->dataTableParams();

        try {
            $result = $this->service->getList($p['start'], $p['length'], $p['orderColumn'], $p['orderDir'], $p['search']);
        } catch (\Exception $e) {
            error_log('CategoryController::list - ' . $e->getMessage());
            $result = $this->emptyListResult();
        }

        return $this->dataTableResponse($p['draw'], $result);
    }

    /**
     * Get single category by ID
     */
    public function get(): array
    {
        $id = $this->postId();

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
        $id = $this->postId();
        $categoryName = trim($_POST['categoryName'] ?? '');
        $company = $this->postId('company');
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
        return $this->service->delete($this->postIds());
    }

    /**
     * Bulk upload categories from JSON body
     */
    public function upload(): array
    {
        $rows = $this->jsonBody();

        if ($rows === null) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        return $this->service->upload($rows);
    }
}
