<?php
namespace App\Services;

class PackagingService extends BaseService
{
    /**
     * Get paginated packaging for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $where = "packaging.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'packaging.customer');

        $totalRecords = $this->countRows('packaging', $where, $types, $params);

        $this->applySearch($where, $params, $types, ['packaging.packaging_name'], $search);
        $totalFiltered = $this->countRows('packaging', $where, $types, $params);

        $orderBy = $this->orderBy(
            ['id' => 'packaging.id', 'packaging_name' => 'packaging.packaging_name', 'packaging_type' => 'packaging.packaging_type', 'weight' => 'packaging.weight', 'is_by_weight' => 'packaging.is_by_weight'],
            $orderColumn, $orderDir, 'packaging.id'
        );
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $rows = $this->fetchAll("SELECT * FROM packaging WHERE $where ORDER BY packaging.deleted, $orderBy LIMIT ?, ?", $types, $params);

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id' => $row['id'],
                'packaging_name' => $row['packaging_name'],
                'packaging_type' => $row['packaging_type'],
                'weight' => $row['weight'],
                'is_by_weight' => $row['is_by_weight'],
                'deleted' => $row['deleted']
            ];
        }

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Get single packaging by ID
     */
    public function getById(int $id): ?array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        return $this->fetchOne("SELECT id, packaging_name, packaging_type, weight, is_by_weight, customer FROM packaging WHERE $where", $types, $params);
    }

    /**
     * Create new packaging
     */
    public function create(array $data, int $company): array
    {
        $data['customer'] = $this->resolveCompany($company);
        $data['created_by'] = $this->user;

        if (!$this->insertRow('packaging', $data)) {
            return ['status' => 'failed', 'message' => 'Failed to add record'];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    /**
     * Update existing packaging
     */
    public function update(int $id, array $data): array
    {
        $data['modified_by'] = $this->user;

        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        if (!$this->updateRow('packaging', $data, $where, $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to update record'];
        }

        return ['status' => 'success', 'message' => 'Updated Successfully!!'];
    }

    public function delete(array $ids): array
    {
        return $this->softDeleteRecords('packaging', $ids);
    }

    public function reactivate(int $id): array
    {
        return $this->reactivateRecord('packaging', $id);
    }

    /**
     * Bulk insert packaging from Excel upload
     */
    public function upload(array $rows): array
    {
        return $this->uploadRecords('packaging', 'packaging_name', 'Packaging', $rows, function (array $row): array {
            return [
                'packaging_name' => !empty($row['PackagingName']) ? trim($row['PackagingName']) : '',
                'packaging_type' => !empty($row['PackagingType']) ? trim($row['PackagingType']) : 'Original',
                'weight' => !empty($row['PackagingWeight']) ? trim($row['PackagingWeight']) : 0,
                'is_by_weight' => !empty($row['ByWeight']) ? trim($row['ByWeight']) : 'N'
            ];
        });
    }
}
