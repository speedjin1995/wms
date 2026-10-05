<?php
namespace App\Services;

/**
 * Company users management. Only NORMAL / MANAGER users of the session company are managed here;
 * ADMIN / SADMIN accounts are never listed or editable from this screen.
 */
class UserService extends BaseService
{
    private const DEFAULT_PASSWORD = '123456';
    private const PROTECTED_ROLES = ['ADMIN', 'SADMIN'];
    private const MAIN_MODULES = ['wholesale', 'weighing', 'industrial', 'processing'];

    /**
     * Get paginated users for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $from = "users LEFT JOIN roles ON users.role_code = roles.role_code LEFT JOIN locations ON users.location = locations.id";
        $where = "users.deleted = 0 AND users.customer = ? AND users.role_code NOT IN ('ADMIN', 'SADMIN')";
        $params = [$this->company];
        $types = 'i';

        $totalRecords = $this->countRows($from, $where, $types, $params);

        $this->applySearch($where, $params, $types, ['users.name', 'users.username', 'roles.role_name'], $search);
        $totalFiltered = $this->countRows($from, $where, $types, $params);

        $orderBy = $this->orderBy([
            'name' => 'users.name',
            'role_name' => 'roles.role_name',
            'allow_add' => 'users.allow_add',
            'allow_edit' => 'users.allow_edit',
            'allow_delete' => 'users.allow_delete',
            'allow_price' => 'users.allow_price',
            'location' => 'locations.locations',
            'created_date' => 'users.created_date'
        ], $orderColumn, $orderDir, 'users.name');
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $rows = $this->fetchAll(
            "SELECT users.id, users.username, users.name, users.created_date, users.allow_add, users.allow_edit, users.allow_delete, users.allow_price,
                    roles.role_name, locations.locations AS location
             FROM $from WHERE $where ORDER BY $orderBy LIMIT ?, ?",
            $types,
            $params
        );

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'username' => $row['username'],
                'role_name' => $row['role_name'],
                'allow_add' => ($row['allow_add'] == 'Y') ? 'YES' : 'NO',
                'allow_edit' => ($row['allow_edit'] == 'Y') ? 'YES' : 'NO',
                'allow_delete' => ($row['allow_delete'] == 'Y') ? 'YES' : 'NO',
                'allow_price' => ($row['allow_price'] == 'Y') ? 'YES' : 'NO',
                'location' => $row['location'],
                'created_date' => !empty($row['created_date']) ? date("d-m-Y", strtotime($row['created_date'])) : '-'
            ];
        }

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Get single manageable user by ID
     */
    public function getById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT id, username, name, IFNULL(email, '') AS email, role_code, allow_add, allow_edit, allow_delete, allow_price, location, customer
             FROM users WHERE id = ? AND customer = ? AND deleted = 0 AND role_code NOT IN ('ADMIN', 'SADMIN')",
            'ii',
            [$id, $this->company]
        );
    }

    /**
     * Create new user with the default password
     */
    public function create(array $data): array
    {
        $error = $this->validate($data);
        if ($error !== null) {
            return ['status' => 'failed', 'message' => $error];
        }

        $salt = hash('sha512', uniqid(openssl_random_pseudo_bytes(16), true));
        $data['password'] = hash('sha512', self::DEFAULT_PASSWORD . $salt);
        $data['salt'] = $salt;
        $data['created_by'] = $this->user;
        $data['customer'] = $this->company;

        // Serial port columns are NOT NULL without defaults; '' matches what non-strict MySQL stored implicitly
        $data['baudrate'] = '';
        $data['databits'] = '';
        $data['parity'] = '';
        $data['stopbits'] = '';

        if (!$this->insertRow('users', $data)) {
            return ['status' => 'failed', 'message' => 'Failed to add record'];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    /**
     * Update existing user
     */
    public function update(int $id, array $data): array
    {
        if (!$this->getById($id)) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        $error = $this->validate($data);
        if ($error !== null) {
            return ['status' => 'failed', 'message' => $error];
        }

        if (!$this->updateRow('users', $data, "id = ? AND customer = ?", 'ii', [$id, $this->company])) {
            return ['status' => 'failed', 'message' => 'Failed to update record'];
        }

        return ['status' => 'success', 'message' => 'Updated Successfully'];
    }

    /**
     * Soft delete user
     */
    public function delete(int $id): array
    {
        if (!$this->getById($id)) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        if (!$this->executeWrite("UPDATE users SET deleted = 1 WHERE id = ? AND customer = ?", 'ii', [$id, $this->company])) {
            return ['status' => 'failed', 'message' => 'Failed to delete record'];
        }

        return ['status' => 'success', 'message' => 'Deleted'];
    }

    /**
     * User's module access plus the company's available modules and categories
     */
    public function getModuleAccess(int $id): ?array
    {
        $user = $this->fetchOne(
            "SELECT module_access FROM users WHERE id = ? AND customer = ? AND deleted = 0 AND role_code NOT IN ('ADMIN', 'SADMIN')",
            'ii',
            [$id, $this->company]
        );
        if (!$user) {
            return null;
        }

        $moduleAccess = $user['module_access'] ? json_decode($user['module_access'], true) : ['modules' => [], 'categories' => []];

        $companyData = $this->fetchOne("SELECT products FROM companies WHERE id = ?", 'i', [$this->company]);
        $companyModules = !empty($companyData['products']) ? json_decode($companyData['products'], true) : [];

        // Only main modules (exclude feature flags like 'fruits', 'second_remarks')
        $availableModules = array_values(array_intersect($companyModules, self::MAIN_MODULES));

        $categories = [];
        $rows = $this->fetchAll("SELECT id, category_name, module FROM categories WHERE customer = ? AND deleted = 0 ORDER BY module, category_name", 'i', [$this->company]);
        foreach ($rows as $row) {
            $categories[$row['module']][] = ['id' => $row['id'], 'name' => $row['category_name']];
        }

        return [
            'moduleAccess' => $moduleAccess,
            'availableModules' => $availableModules,
            'categories' => $categories
        ];
    }

    /**
     * Save user's module access JSON
     */
    public function saveModuleAccess(int $id, string $moduleAccess): array
    {
        if (!$this->getById($id)) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        if (!$this->executeWrite("UPDATE users SET module_access = ? WHERE id = ? AND customer = ?", 'sii', [$moduleAccess, $id, $this->company])) {
            return ['status' => 'failed', 'message' => 'Failed to save'];
        }

        return ['status' => 'success', 'message' => 'Module access updated successfully'];
    }

    /**
     * Role must be an active non-admin role; location must belong to the session company
     */
    private function validate(array $data): ?string
    {
        if (in_array($data['role_code'], self::PROTECTED_ROLES, true)
            || !$this->fetchOne("SELECT id FROM roles WHERE role_code = ? AND deleted = 0", 's', [$data['role_code']])) {
            return 'Invalid role';
        }

        if ($data['location'] !== null
            && !$this->fetchOne("SELECT id FROM locations WHERE id = ? AND customer = ? AND deleted = 0", 'ii', [$data['location'], $this->company])) {
            return 'Invalid location';
        }

        return null;
    }
}
