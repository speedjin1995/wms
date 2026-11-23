<?php
namespace App\Services;

class ProcessingTypeService extends BaseService
{
    private const TABLE = 'processing_types';

    /**
     * DataTables list within the session company
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $where = "deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types);

        $total = $this->countRows(self::TABLE, $where, $types, $params);

        $this->applySearch($where, $params, $types, ['name', 'description'], $search);

        $filtered = $this->countRows(self::TABLE, $where, $types, $params);

        $orderBy = $this->orderBy(['name' => 'name', 'description' => 'description'], $orderColumn, $orderDir, 'name');
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $data = $this->fetchAll(
            "SELECT id, name, description, deleted FROM " . self::TABLE . " WHERE $where ORDER BY $orderBy LIMIT ?, ?",
            $types,
            $params
        );

        return ['totalRecords' => $total, 'totalFiltered' => $filtered, 'data' => $data];
    }

    public function getById(int $id): ?array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        return $this->fetchOne("SELECT id, name, description, customer FROM " . self::TABLE . " WHERE $where", $types, $params);
    }

    public function create(array $data, int $company): array
    {
        $data['customer'] = $this->resolveCompany($company);
        $data['created_by'] = $this->user;

        if (!$this->insertRow(self::TABLE, $data)) {
            return ['status' => 'failed', 'message' => 'Failed to add record'];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    public function update(int $id, array $data, int $company): array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        if ($this->isSuperAdmin()) {
            $data['customer'] = $this->resolveCompany($company);
        }
        $data['modified_by'] = $this->user;

        if (!$this->updateRow(self::TABLE, $data, $where, $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to update record'];
        }

        return ['status' => 'success', 'message' => 'Updated Successfully!!'];
    }

    public function delete(array $ids): array
    {
        return $this->softDeleteRecords(self::TABLE, $ids);
    }

    /**
     * Not reactivateRecord(): it skips modified_by, so the log would show the wrong user
     */
    public function reactivate(int $id): array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        if (!$this->updateRow(self::TABLE, ['deleted' => 0, 'modified_by' => $this->user], $where, $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to reactivate record'];
        }

        return ['status' => 'success', 'message' => 'Reactivated'];
    }
}
