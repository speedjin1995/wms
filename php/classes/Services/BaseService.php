<?php
namespace App\Services;

/**
 * Shared state and helpers for all services.
 */
abstract class BaseService
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

    /**
     * Current user's add / edit / delete flags (from the users table)
     */
    public function getPermissions(): array
    {
        $row = $this->fetchOne("SELECT allow_add, allow_edit, allow_delete FROM users WHERE id = ?", 'i', [$this->user]);

        return [
            'allowAdd' => ($row['allow_add'] ?? 'N') === 'Y',
            'allowEdit' => ($row['allow_edit'] ?? 'N') === 'Y',
            'allowDelete' => ($row['allow_delete'] ?? 'N') === 'Y'
        ];
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
    protected function applyCompanyScope(string &$where, array &$params, string &$types, string $column = 'customer'): void
    {
        if ($this->isSuperAdmin()) {
            return;
        }

        $where .= " AND $column = ?";
        $params[] = $this->company;
        $types .= 'i';
    }

    /**
     * Category IDs the user may access in the given category modules ($_SESSION['userModuleAccess']; empty = no restriction)
     */
    protected function accessibleCategoryIds(array $userModuleAccess, array $modules): array
    {
        $ids = [];
        foreach ($userModuleAccess['categories'] ?? [] as $module => $moduleCategories) {
            if (in_array($module, $modules, true)) {
                $ids = array_merge($ids, (array)$moduleCategories);
            }
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * [" AND $column IN (?,...)", types] for the category IDs, or ['', ''] when unrestricted
     */
    protected function categoryIdFilter(string $column, array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return ['', ''];
        }

        return [" AND $column IN (" . $this->placeholders(count($categoryIds)) . ")", str_repeat('i', count($categoryIds))];
    }

    /**
     * Records with an item product in the chosen category, otherwise in the user's accessible categories.
     * $itemsWithProduct: unclosed "x.id IN (SELECT ... INNER JOIN products p ... WHERE ..." subquery.
     */
    protected function applyItemCategoryFilter(string &$where, array &$params, string &$types, string $itemsWithProduct, string $category, array $userModuleAccess, array $modules): void
    {
        if ($category !== '') {
            $where .= " AND $itemsWithProduct AND p.category = ?)";
            $params[] = (int)$category;
            $types .= 'i';
            return;
        }

        if (empty($userModuleAccess['categories'])) {
            return;
        }

        $categoryIds = $this->accessibleCategoryIds($userModuleAccess, $modules);
        [$filter, $filterTypes] = $this->categoryIdFilter('p.category', $categoryIds);
        $where .= " AND $itemsWithProduct$filter)";
        $params = array_merge($params, $categoryIds);
        $types .= $filterTypes;
    }

    /**
     * Append a LIKE search over the given columns
     */
    protected function applySearch(string &$where, array &$params, string &$types, array $columns, string $search): void
    {
        if ($search === '' || empty($columns)) {
            return;
        }

        $likes = [];
        foreach ($columns as $column) {
            $likes[] = "$column LIKE ?";
            $params[] = '%' . $search . '%';
            $types .= 's';
        }
        $where .= ' AND (' . implode(' OR ', $likes) . ')';
    }

    /**
     * Whitelisted ORDER BY expression and direction
     */
    protected function orderBy(array $sortColumns, string $orderColumn, string $orderDir, string $default): string
    {
        $expr = $sortColumns[$orderColumn] ?? $default;
        $dir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';

        return "$expr $dir";
    }

    /**
     * First unused "$prefix + zero-padded number" in $table.$column (e.g. G202610060001).
     * Counting starts at $start, or by default after the numbers already using the prefix.
     * $company scopes the count / uniqueness check to one company; null checks the whole table (globally unique column).
     */
    protected function nextRunningNo(string $table, string $column, string $prefix, ?int $company, ?int $start = null, int $digits = 4): string
    {
        $where = "$column = ?";
        $types = 's';
        $params = [];
        if ($company !== null) {
            $where .= " AND company = ?";
            $types .= 'i';
            $params[] = $company;
        }

        if ($start === null) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total FROM $table WHERE $column LIKE ?" . ($company !== null ? " AND company = ?" : ''),
                $types,
                array_merge([$prefix . '%'], $params)
            );
            $start = (int)($row['total'] ?? 0) + 1;
        }

        $count = $start;
        do {
            $number = $prefix . str_pad((string)$count, $digits, '0', STR_PAD_LEFT);
            $exists = $this->fetchOne("SELECT 1 FROM $table WHERE $where", $types, array_merge([$number], $params));
            $count++;
        } while ($exists);

        return $number;
    }

    protected function cleanIds(array $ids): array
    {
        return array_values(array_filter(array_map('intval', $ids)));
    }

    protected function placeholders(int $count): string
    {
        return implode(',', array_fill(0, $count, '?'));
    }

    protected function fetchOne(string $sql, string $types = '', array $params = []): ?array
    {
        $stmt = $this->runQuery($sql, $types, $params);
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    protected function fetchAll(string $sql, string $types = '', array $params = []): array
    {
        $stmt = $this->runQuery($sql, $types, $params);
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    protected function countRows(string $from, string $where, string $types, array $params): int
    {
        $row = $this->fetchOne("SELECT COUNT(*) AS allcount FROM $from WHERE $where", $types, $params);

        return (int)($row['allcount'] ?? 0);
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

    /**
     * Insert column => value row, returns the new ID or 0 on failure
     */
    protected function insertRow(string $table, array $data): int
    {
        $columns = array_keys($data);
        $stmt = $this->db->prepare("INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . $this->placeholders(count($columns)) . ")");
        if (!$stmt) {
            return 0;
        }
        $values = array_values($data);
        $stmt->bind_param(str_repeat('s', count($values)), ...$values);
        $id = $stmt->execute() ? (int)$stmt->insert_id : 0;
        $stmt->close();

        return $id;
    }

    /**
     * Update column => value data on rows matching $where
     */
    protected function updateRow(string $table, array $data, string $where, string $types, array $params): bool
    {
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = "$column = ?";
        }

        return $this->executeWrite(
            "UPDATE $table SET " . implode(', ', $sets) . " WHERE $where",
            str_repeat('s', count($data)) . $types,
            array_merge(array_values($data), $params)
        );
    }

    /**
     * Soft delete one or more records within the session company
     */
    protected function softDeleteRecords(string $table, array $ids, ?string $companyColumn = 'customer', bool $audit = true): array
    {
        $ids = $this->cleanIds($ids);

        if (empty($ids)) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        $sets = "deleted = 1";
        $params = [];
        $types = '';
        if ($audit) {
            $sets .= ", modified_by = ?";
            $params[] = $this->user;
            $types .= 'i';
        }

        $where = "id IN (" . $this->placeholders(count($ids)) . ")";
        $params = array_merge($params, $ids);
        $types .= str_repeat('i', count($ids));
        if ($companyColumn !== null) {
            $this->applyCompanyScope($where, $params, $types, $companyColumn);
        }

        if (!$this->executeWrite("UPDATE $table SET $sets WHERE $where", $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to delete record'];
        }

        return ['status' => 'success', 'message' => 'Deleted'];
    }

    /**
     * Reactivate a soft deleted record within the session company
     */
    protected function reactivateRecord(string $table, int $id, ?string $companyColumn = 'customer'): array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        if ($companyColumn !== null) {
            $this->applyCompanyScope($where, $params, $types, $companyColumn);
        }

        if (!$this->executeWrite("UPDATE $table SET deleted = 0 WHERE $where", $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to reactivate record'];
        }

        return ['status' => 'success', 'message' => 'Reactivated'];
    }

    /**
     * Bulk insert Excel rows into the session company, skipping names that already exist.
     * $mapRow converts an uploaded row into column => value.
     */
    protected function uploadRecords(string $table, string $nameColumn, string $label, array $rows, callable $mapRow, string $companyColumn = 'customer', bool $audit = true): array
    {
        $errors = [];

        $checkStmt = $this->db->prepare("SELECT id FROM $table WHERE $nameColumn = ? AND $companyColumn = ? AND deleted = 0");
        if (!$checkStmt) {
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        $this->db->begin_transaction();

        try {
            foreach ($rows as $row) {
                $data = $mapRow($row);
                $name = (string)($data[$nameColumn] ?? '');

                $checkStmt->bind_param('si', $name, $this->company);
                if (!$checkStmt->execute()) {
                    throw new \Exception('Failed to check ' . $table);
                }

                if ($checkStmt->get_result()->fetch_assoc()) {
                    $errors[] = $label . ": " . $name . " already exists.";
                    continue;
                }

                $data[$companyColumn] = $this->company;
                if ($audit) {
                    $data['created_by'] = $this->user;
                }

                if (!$this->insertRow($table, $data)) {
                    throw new \Exception('Failed to insert ' . $table);
                }
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            $checkStmt->close();
            error_log(static::class . '::upload - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to upload records'];
        }

        $checkStmt->close();

        if (!empty($errors)) {
            return ['status' => 'error', 'message' => $errors];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    private function runQuery(string $sql, string $types, array $params): \mysqli_stmt
    {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new \Exception(static::class . ' - failed to prepare query');
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            $stmt->close();
            throw new \Exception(static::class . ' - failed to execute query');
        }

        return $stmt;
    }
}
