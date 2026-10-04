<?php
namespace App\Services;

class DailySalesSetupService
{
    public const MODULES = ['industrial', 'weighing', 'wholesales', 'packing', 'pricing'];

    private \mysqli $db;
    private int $company;
    private int $user;
    private string $role;

    public function __construct(\mysqli $db, int $company, int $user, string $role)
    {
        $this->db = $db;
        $this->company = $company;
        $this->user = $user;
        $this->role = $role;
    }

    /**
     * Get paginated setups for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir): array
    {
        $allowedColumns = ['id', 'module', 'state'];
        if (!in_array($orderColumn, $allowedColumns, true)) {
            $orderColumn = 'id';
        }
        $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';

        $where = "deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types);

        $total = $this->count($where, $types, $params);

        $sql = "SELECT id, module, state FROM daily_sales_setup WHERE $where ORDER BY $orderColumn $orderDir LIMIT ?, ?";
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new \Exception('Failed to prepare daily sales setup list query');
        }
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new \Exception('Failed to load daily sales setups');
        }
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'id' => $row['id'],
                'module' => $row['module'],
                'state' => implode(', ', getStatesByIds($row['state'], $this->db) ?? [])
            ];
        }
        $stmt->close();

        return [
            'totalRecords' => $total,
            'totalFiltered' => $total,
            'data' => $data
        ];
    }

    /**
     * Get single setup by ID
     */
    public function getById(int $id): ?array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        $stmt = $this->db->prepare("SELECT id, module, state, company FROM daily_sales_setup WHERE $where");
        if (!$stmt) {
            throw new \Exception('Failed to prepare daily sales setup query');
        }
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new \Exception('Failed to load daily sales setup');
        }
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return null;
        }

        $row['state'] = json_decode($row['state'], true);

        return $row;
    }

    /**
     * Create new setup (one per module per company)
     */
    public function create(string $module, array $states, int $company): array
    {
        $company = $this->resolveCompany($company);

        if ($this->moduleExists($module, $company, 0)) {
            return ['status' => 'failed', 'message' => 'Module already exists for this company!'];
        }

        $stateJson = json_encode($states);

        $stmt = $this->db->prepare("INSERT INTO daily_sales_setup (module, state, company, created_by) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }
        $stmt->bind_param('ssii', $module, $stateJson, $company, $this->user);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['status' => 'failed', 'message' => 'Failed to add daily sales setup'];
        }
        $stmt->close();

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    /**
     * Update existing setup
     */
    public function update(int $id, string $module, array $states): array
    {
        $existing = $this->getById($id);
        if (!$existing) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        if ($this->moduleExists($module, (int)$existing['company'], $id)) {
            return ['status' => 'failed', 'message' => 'Module already exists for this company!'];
        }

        $stateJson = json_encode($states);

        $stmt = $this->db->prepare("UPDATE daily_sales_setup SET module = ?, state = ?, modified_by = ? WHERE id = ?");
        if (!$stmt) {
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }
        $stmt->bind_param('ssii', $module, $stateJson, $this->user, $id);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['status' => 'failed', 'message' => 'Failed to update daily sales setup'];
        }
        $stmt->close();

        return ['status' => 'success', 'message' => 'Updated Successfully!!'];
    }

    /**
     * Soft delete setup
     */
    public function delete(int $id): array
    {
        $where = "id = ?";
        $params = [$this->user, $id];
        $types = 'ii';
        $this->applyCompanyScope($where, $params, $types);

        $stmt = $this->db->prepare("UPDATE daily_sales_setup SET deleted = 1, modified_by = ? WHERE $where");
        if (!$stmt) {
            return ['status' => 'failed', 'message' => 'Somthings wrong'];
        }
        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['status' => 'failed', 'message' => 'Failed to delete daily sales setup'];
        }
        $stmt->close();

        return ['status' => 'success', 'message' => 'Deleted'];
    }

    private function moduleExists(string $module, int $company, int $excludeId): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM daily_sales_setup WHERE module = ? AND company = ? AND deleted = 0 AND id != ?");
        if (!$stmt) {
            throw new \Exception('Failed to prepare duplicate module check');
        }
        $stmt->bind_param('sii', $module, $company, $excludeId);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    private function isSuperAdmin(): bool
    {
        return $this->role === 'SADMIN';
    }

    /**
     * Only SADMIN may write setups for another company
     */
    private function resolveCompany(int $company): int
    {
        if (!$this->isSuperAdmin() || !$company) {
            return $this->company;
        }

        return $company;
    }

    private function applyCompanyScope(string &$where, array &$params, string &$types): void
    {
        if ($this->isSuperAdmin()) {
            return;
        }

        $where .= " AND company = ?";
        $params[] = $this->company;
        $types .= 'i';
    }

    private function count(string $where, string $types, array $params): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS allcount FROM daily_sales_setup WHERE $where");
        if (!$stmt) {
            throw new \Exception('Failed to prepare daily sales setup count query');
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            $stmt->close();
            throw new \Exception('Failed to count daily sales setups');
        }
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)$row['allcount'];
    }
}
