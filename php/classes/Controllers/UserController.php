<?php
namespace App\Controllers;

use App\Services\UserService;

class UserController extends BaseController
{
    private UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    /**
     * DataTables server-side list
     */
    public function list(): array
    {
        $p = $this->dataTableParams('name');

        try {
            $result = $this->service->getList($p['start'], $p['length'], $p['orderColumn'], $p['orderDir'], $p['search']);
        } catch (\Exception $e) {
            error_log('UserController::list - ' . $e->getMessage());
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
            error_log('UserController::get - ' . $e->getMessage());
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
        $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);
        $location = $this->postId('location');

        $data = [
            'username' => $this->postText('username'),
            'name' => $this->postText('name'),
            'email' => ($email === '' || $email === false) ? null : $email,
            'role_code' => trim((string)($_POST['userRole'] ?? '')),
            'allow_add' => $this->postYesNo('allowAdd'),
            'allow_edit' => $this->postYesNo('allowEdit'),
            'allow_delete' => $this->postYesNo('allowDelete'),
            'allow_price' => $this->postYesNo('allowPrice'),
            'location' => $location ?: null
        ];

        if ($data['username'] === '' || $data['name'] === '' || $data['role_code'] === '') {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        if ($id) {
            return $this->service->update($id, $data);
        }

        return $this->service->create($data);
    }

    /**
     * Soft delete single record
     */
    public function delete(): array
    {
        $id = $this->postId();

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        return $this->service->delete($id);
    }

    /**
     * Module access settings for a user
     */
    public function getModuleAccess(): array
    {
        $id = $this->postId();

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }

        $result = $this->service->getModuleAccess($id);

        if ($result === null) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success', 'message' => $result];
    }

    /**
     * Save module access settings for a user
     */
    public function saveModuleAccess(): array
    {
        $id = $this->postId();
        $moduleAccess = (string)($_POST['moduleAccess'] ?? '');

        if (!$id || !is_array(json_decode($moduleAccess, true))) {
            return ['status' => 'failed', 'message' => 'Invalid data format'];
        }

        return $this->service->saveModuleAccess($id, $moduleAccess);
    }

    private function postYesNo(string $key): string
    {
        return (($_POST[$key] ?? '') === 'Y') ? 'Y' : 'N';
    }
}
