<?php
namespace App\Modules\BinType;

use App\Core\BaseService;

class BinTypeService extends BaseService
{
    /**
     * Get paginated bin types for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $where = "deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types);

        $totalRecords = $this->countRows('bin_type', $where, $types, $params);

        $this->applySearch($where, $params, $types, ['bin_type'], $search);
        $totalFiltered = $this->countRows('bin_type', $where, $types, $params);

        $orderBy = $this->orderBy(['id' => 'id', 'bin_type' => 'bin_type'], $orderColumn, $orderDir, 'id');
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $data = $this->fetchAll("SELECT id, bin_type, deleted FROM bin_type WHERE $where ORDER BY $orderBy LIMIT ?, ?", $types, $params);

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Get single bin type by ID
     */
    public function getById(int $id): ?array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        return $this->fetchOne("SELECT id, bin_type, customer FROM bin_type WHERE $where", $types, $params);
    }

    /**
     * Create new bin type
     */
    public function create(string $binType, int $company): array
    {
        $data = [
            'bin_type' => $binType,
            'customer' => $this->resolveCompany($company),
            'created_by' => $this->user
        ];

        if (!$this->insertRow('bin_type', $data)) {
            return ['status' => 'failed', 'message' => 'Failed to add record'];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    /**
     * Update existing bin type
     */
    public function update(int $id, string $binType, int $company): array
    {
        $data = [
            'bin_type' => $binType,
            'customer' => $this->resolveCompany($company),
            'modified_by' => $this->user
        ];

        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        if (!$this->updateRow('bin_type', $data, $where, $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to update record'];
        }

        return ['status' => 'success', 'message' => 'Updated Successfully!!'];
    }

    public function delete(array $ids): array
    {
        return $this->softDeleteRecords('bin_type', $ids);
    }
}
