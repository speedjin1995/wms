<?php
namespace App\Services;

class CategoryService extends BaseService
{
    private string $module;

    public function __construct(\mysqli $db, int $company, int $user, string $role, string $module)
    {
        parent::__construct($db, $company, $user, $role);
        $this->module = $module;
    }

    /**
     * Get paginated categories for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $allowedColumns = ['id', 'category_name', 'deleted'];
        if (!in_array($orderColumn, $allowedColumns, true)) {
            $orderColumn = 'id';
        }
        $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';

        // Base filter (module + company scope)
        $where = "deleted = 0";
        $params = [];
        $types = '';

        if ($this->module == 'processing') {
            $where .= " AND module IN ('processing', 'wholesale')";
        } else {
            $where .= " AND module = ?";
            $params[] = $this->module;
            $types .= 's';
        }

        if (!$this->isSuperAdmin()) {
            $where .= " AND customer = ?";
            $params[] = $this->company;
            $types .= 'i';
        }

        $totalRecords = $this->countRows('categories', $where, $types, $params);

        // Search filter
        if ($search !== '') {
            $where .= " AND category_name LIKE ?";
            $params[] = '%' . $search . '%';
            $types .= 's';
        }

        $totalFiltered = $this->countRows('categories', $where, $types, $params);

        $sql = "SELECT id, category_name, deleted FROM categories WHERE $where ORDER BY deleted, $orderColumn $orderDir LIMIT ?, ?";
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new \Exception('Failed to prepare category list query');
        }
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new \Exception('Failed to load categories');
        }
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();

        return [
            'totalRecords' => $totalRecords,
            'totalFiltered' => $totalFiltered,
            'data' => $data
        ];
    }

    /**
     * Get single category by ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT id, category_name, customer FROM categories WHERE id = ?";
        $params = [$id];
        $types = 'i';

        if (!$this->isSuperAdmin()) {
            $sql .= " AND customer = ?";
            $params[] = $this->company;
            $types .= 'i';
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new \Exception('Failed to prepare category query');
        }
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new \Exception('Failed to load category');
        }
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    /**
     * Create new category
     */
    public function create(string $categoryName, string $module, int $company): array
    {
        // Only SADMIN may create categories for another company
        if (!$this->isSuperAdmin()) {
            $company = $this->company;
        }

        $stmt = $this->db->prepare("INSERT INTO categories (category_name, module, customer, created_by) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }
        $stmt->bind_param('ssii', $categoryName, $module, $company, $this->user);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['status' => 'failed', 'message' => 'Failed to add category'];
        }
        $stmt->close();

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    /**
     * Update existing category
     */
    public function update(int $id, string $categoryName, string $module): array
    {
        $sql = "UPDATE categories SET category_name = ?, module = ?, modified_by = ? WHERE id = ?";
        $params = [$categoryName, $module, $this->user, $id];
        $types = 'ssii';

        if (!$this->isSuperAdmin()) {
            $sql .= " AND customer = ?";
            $params[] = $this->company;
            $types .= 'i';
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }
        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['status' => 'failed', 'message' => 'Failed to update category'];
        }
        $stmt->close();

        return ['status' => 'success', 'message' => 'Updated Successfully!!'];
    }

    /**
     * Soft delete one or more categories
     */
    public function delete(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE categories SET deleted = 1, modified_by = ? WHERE id IN ($placeholders)";
        $params = array_merge([$this->user], $ids);
        $types = 'i' . str_repeat('i', count($ids));

        if (!$this->isSuperAdmin()) {
            $sql .= " AND customer = ?";
            $params[] = $this->company;
            $types .= 'i';
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['status' => 'failed', 'message' => 'Somthings wrong'];
        }
        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            $stmt->close();
            return ['status' => 'failed', 'message' => 'Failed to delete category'];
        }
        $stmt->close();

        return ['status' => 'success', 'message' => 'Deleted'];
    }

    /**
     * Bulk insert categories from Excel upload, skipping existing names
     */
    public function upload(array $rows): array
    {
        $errors = [];

        $checkStmt = $this->db->prepare("SELECT id FROM categories WHERE category_name = ? AND customer = ? AND deleted = 0");
        $insertStmt = $this->db->prepare("INSERT INTO categories (category_name, customer, module, created_by) VALUES (?, ?, ?, ?)");
        if (!$checkStmt || !$insertStmt) {
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        $this->db->begin_transaction();

        try {
            foreach ($rows as $row) {
                $categoryName = !empty($row['CategoryName']) ? trim($row['CategoryName']) : '';

                $checkStmt->bind_param('si', $categoryName, $this->company);
                if (!$checkStmt->execute()) {
                    throw new \Exception('Failed to check category');
                }
                $exists = $checkStmt->get_result()->fetch_assoc();

                if ($exists) {
                    $errors[] = "Category: " . $categoryName . " already exists.";
                    continue;
                }

                $insertStmt->bind_param('sisi', $categoryName, $this->company, $this->module, $this->user);
                if (!$insertStmt->execute()) {
                    throw new \Exception('Failed to insert category');
                }
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            $checkStmt->close();
            $insertStmt->close();
            return ['status' => 'failed', 'message' => 'Failed to upload categories'];
        }

        $checkStmt->close();
        $insertStmt->close();

        if (!empty($errors)) {
            return ['status' => 'error', 'message' => $errors];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }
}
