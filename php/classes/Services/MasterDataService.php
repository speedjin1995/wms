<?php
namespace App\Services;

/**
 * Base service for simple company-scoped master data tables
 * (list / get / save / soft delete / reactivate / Excel upload).
 */
abstract class MasterDataService
{
    protected \mysqli $db;
    protected int $company;
    protected int $user;
    protected string $role;

    public function __construct(\mysqli $db, int $company, int $user, string $role)
    {
        $this->db = $db;
        $this->company = $company;
        $this->user = $user;
        $this->role = $role;
    }

    /** Table name */
    abstract protected function table(): string;

    /** Map a DB row to the DataTables output row */
    abstract protected function formatListRow(array $row, int $rowNumber): array;

    /** Columns returned by get() */
    abstract protected function getColumns(): array;

    /** Company column, or null when the table is not company-scoped */
    protected function companyColumn(): ?string
    {
        return 'customer';
    }

    /** Whether the table has created_by / modified_by columns */
    protected function hasAudit(): bool
    {
        return true;
    }

    /** FROM clause for list (override to add joins) */
    protected function listFrom(): string
    {
        return $this->table();
    }

    /** SELECT clause for list */
    protected function listSelect(): string
    {
        return $this->table() . '.*';
    }

    /** Columns searched by the DataTables search box */
    protected function searchColumns(): array
    {
        return [];
    }

    /** DataTables column name => SQL sort expression */
    protected function sortColumns(): array
    {
        return ['id' => $this->table() . '.id'];
    }

    /** Fixed ORDER BY prefix applied before the user-selected sort */
    protected function orderPrefix(): string
    {
        return $this->table() . '.deleted';
    }

    /** Columns written on update (keys of the save data) */
    protected function updateColumns(): array
    {
        return [];
    }

    /** Extra fixed values written on update */
    protected function updateExtras(): array
    {
        return [];
    }

    /** Whether update also changes the company column */
    protected function updatesCompany(): bool
    {
        return false;
    }

    /** Name column used for Excel upload duplicate checks */
    protected function uploadNameColumn(): ?string
    {
        return null;
    }

    /** Label used in Excel upload duplicate messages */
    protected function uploadLabel(): string
    {
        return '';
    }

    /** Map an uploaded Excel row to column => value */
    protected function mapUploadRow(array $row): array
    {
        return [];
    }

    /**
     * Get paginated records for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $table = $this->table();
        $sortColumns = $this->sortColumns();
        $orderExpr = $sortColumns[$orderColumn] ?? $table . '.id';
        $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';

        // Base filter (active + company scope)
        $where = "$table.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, $table);

        $totalRecords = $this->count($where, $types, $params);

        // Search filter
        $searchColumns = $this->searchColumns();
        if ($search !== '' && !empty($searchColumns)) {
            $likes = [];
            foreach ($searchColumns as $column) {
                $likes[] = "$column LIKE ?";
                $params[] = '%' . $search . '%';
                $types .= 's';
            }
            $where .= ' AND (' . implode(' OR ', $likes) . ')';
        }

        $totalFiltered = $this->count($where, $types, $params);

        $orderBy = $this->orderPrefix();
        $orderBy = ($orderBy !== '' ? $orderBy . ', ' : '') . "$orderExpr $orderDir";

        $sql = "SELECT " . $this->listSelect() . " FROM " . $this->listFrom() . " WHERE $where ORDER BY $orderBy LIMIT ?, ?";
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new \Exception('Failed to prepare list query for ' . $table);
        }
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new \Exception('Failed to load ' . $table);
        }
        $result = $stmt->get_result();

        $data = [];
        $rowNumber = $start + 1;
        while ($row = $result->fetch_assoc()) {
            $data[] = $this->formatListRow($row, $rowNumber++);
        }
        $stmt->close();

        return [
            'totalRecords' => $totalRecords,
            'totalFiltered' => $totalFiltered,
            'data' => $data
        ];
    }

    /**
     * Get single record by ID
     */
    public function getById(int $id): ?array
    {
        $table = $this->table();
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        $stmt = $this->db->prepare("SELECT " . implode(', ', $this->getColumns()) . " FROM $table WHERE $where");
        if (!$stmt) {
            throw new \Exception('Failed to prepare get query for ' . $table);
        }
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new \Exception('Failed to load ' . $table);
        }
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    /**
     * Create new record
     */
    public function create(array $data, int $company): array
    {
        $companyColumn = $this->companyColumn();
        if ($companyColumn !== null) {
            $data[$companyColumn] = $this->resolveCompany($company);
        }
        if ($this->hasAudit()) {
            $data['created_by'] = $this->user;
        }

        $columns = array_keys($data);
        $sql = "INSERT INTO " . $this->table() . " (" . implode(', ', $columns) . ") VALUES (" . implode(', ', array_fill(0, count($columns), '?')) . ")";

        if (!$this->executeWrite($sql, str_repeat('s', count($data)), array_values($data))) {
            return ['status' => 'failed', 'message' => 'Failed to add record'];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    /**
     * Update existing record
     */
    public function update(int $id, array $data, int $company): array
    {
        $values = [];
        foreach ($this->updateColumns() as $column) {
            $values[$column] = $data[$column] ?? null;
        }
        foreach ($this->updateExtras() as $column => $value) {
            $values[$column] = $value;
        }

        $companyColumn = $this->companyColumn();
        if ($companyColumn !== null && $this->updatesCompany()) {
            $values[$companyColumn] = $this->resolveCompany($company);
        }
        if ($this->hasAudit()) {
            $values['modified_by'] = $this->user;
        }

        $sets = [];
        foreach (array_keys($values) as $column) {
            $sets[] = "$column = ?";
        }

        $where = "id = ?";
        $params = array_values($values);
        $params[] = $id;
        $types = str_repeat('s', count($values)) . 'i';
        $this->applyCompanyScope($where, $params, $types);

        $sql = "UPDATE " . $this->table() . " SET " . implode(', ', $sets) . " WHERE $where";

        if (!$this->executeWrite($sql, $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to update record'];
        }

        return ['status' => 'success', 'message' => 'Updated Successfully!!'];
    }

    /**
     * Soft delete one or more records
     */
    public function delete(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        $sets = "deleted = 1";
        $params = [];
        $types = '';
        if ($this->hasAudit()) {
            $sets .= ", modified_by = ?";
            $params[] = $this->user;
            $types .= 'i';
        }

        $where = "id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
        $params = array_merge($params, $ids);
        $types .= str_repeat('i', count($ids));
        $this->applyCompanyScope($where, $params, $types);

        if (!$this->executeWrite("UPDATE " . $this->table() . " SET $sets WHERE $where", $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to delete record'];
        }

        return ['status' => 'success', 'message' => 'Deleted'];
    }

    /**
     * Reactivate soft deleted record
     */
    public function reactivate(int $id): array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        if (!$this->executeWrite("UPDATE " . $this->table() . " SET deleted = 0 WHERE $where", $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to reactivate record'];
        }

        return ['status' => 'success', 'message' => 'Reactivated'];
    }

    /**
     * Bulk insert records from Excel upload, skipping existing names
     */
    public function upload(array $rows): array
    {
        $nameColumn = $this->uploadNameColumn();
        $companyColumn = $this->companyColumn();
        if ($nameColumn === null || $companyColumn === null) {
            return ['status' => 'failed', 'message' => 'Upload is not supported'];
        }

        $table = $this->table();
        $errors = [];

        $checkStmt = $this->db->prepare("SELECT id FROM $table WHERE $nameColumn = ? AND $companyColumn = ? AND deleted = 0");
        if (!$checkStmt) {
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        $this->db->begin_transaction();

        try {
            foreach ($rows as $row) {
                $data = $this->mapUploadRow($row);
                $name = (string)($data[$nameColumn] ?? '');

                $checkStmt->bind_param('si', $name, $this->company);
                if (!$checkStmt->execute()) {
                    throw new \Exception('Failed to check ' . $table);
                }

                if ($checkStmt->get_result()->fetch_assoc()) {
                    $errors[] = $this->uploadLabel() . ": " . $name . " already exists.";
                    continue;
                }

                $data[$companyColumn] = $this->company;
                if ($this->hasAudit()) {
                    $data['created_by'] = $this->user;
                }

                $columns = array_keys($data);
                $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', array_fill(0, count($columns), '?')) . ")";
                if (!$this->executeWrite($sql, str_repeat('s', count($data)), array_values($data))) {
                    throw new \Exception('Failed to insert ' . $table);
                }
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            $checkStmt->close();
            error_log('MasterDataService::upload ' . $table . ' - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to upload records'];
        }

        $checkStmt->close();

        if (!empty($errors)) {
            return ['status' => 'error', 'message' => $errors];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    protected function isSuperAdmin(): bool
    {
        return $this->role === 'SADMIN';
    }

    /**
     * Only SADMIN may write records for another company
     */
    protected function resolveCompany(int $company): int
    {
        if (!$this->isSuperAdmin() || !$company) {
            return $this->company;
        }

        return $company;
    }

    /**
     * Restrict to the session company for non-SADMIN users
     */
    protected function applyCompanyScope(string &$where, array &$params, string &$types, ?string $prefix = null): void
    {
        $companyColumn = $this->companyColumn();
        if ($companyColumn === null || $this->isSuperAdmin()) {
            return;
        }

        $where .= " AND " . ($prefix !== null ? "$prefix." : '') . "$companyColumn = ?";
        $params[] = $this->company;
        $types .= 'i';
    }

    protected function executeWrite(string $sql, string $types, array $params): bool
    {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    protected function count(string $where, string $types, array $params): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS allcount FROM " . $this->listFrom() . " WHERE $where");
        if (!$stmt) {
            throw new \Exception('Failed to prepare count query for ' . $this->table());
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            $stmt->close();
            throw new \Exception('Failed to count ' . $this->table());
        }
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)$row['allcount'];
    }
}
