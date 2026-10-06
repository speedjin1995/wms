<?php
namespace App\Modules\Location;

use App\Core\BaseService;

class LocationService extends BaseService
{
    /**
     * Get paginated locations for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $where = "locations.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'locations.customer');

        $totalRecords = $this->countRows('locations', $where, $types, $params);

        $this->applySearch($where, $params, $types, ['locations.locations'], $search);
        $totalFiltered = $this->countRows('locations', $where, $types, $params);

        $orderBy = $this->orderBy(['id' => 'locations.id', 'locations' => 'locations.locations'], $orderColumn, $orderDir, 'locations.id');
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $rows = $this->fetchAll("SELECT * FROM locations WHERE $where ORDER BY locations.deleted, $orderBy LIMIT ?, ?", $types, $params);

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id' => $row['id'],
                'locations' => $row['locations'],
                'deleted' => $row['deleted']
            ];
        }

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Get single location by ID
     */
    public function getById(int $id): ?array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        return $this->fetchOne("SELECT id, locations, customer FROM locations WHERE $where", $types, $params);
    }

    /**
     * Create new location
     */
    public function create(array $data, int $company): array
    {
        $data['customer'] = $this->resolveCompany($company);
        $data['created_by'] = $this->user;

        if (!$this->insertRow('locations', $data)) {
            return ['status' => 'failed', 'message' => 'Failed to add record'];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    /**
     * Update existing location
     */
    public function update(int $id, array $data): array
    {
        $data['modified_by'] = $this->user;

        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        if (!$this->updateRow('locations', $data, $where, $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to update record'];
        }

        return ['status' => 'success', 'message' => 'Updated Successfully!!'];
    }

    public function delete(array $ids): array
    {
        return $this->softDeleteRecords('locations', $ids);
    }

    public function reactivate(int $id): array
    {
        return $this->reactivateRecord('locations', $id);
    }

    /**
     * Bulk insert locations from Excel upload
     */
    public function upload(array $rows): array
    {
        return $this->uploadRecords('locations', 'locations', 'Location', $rows, function (array $row): array {
            return [
                'locations' => !empty($row['Location']) ? trim($row['Location']) : ''
            ];
        });
    }
}
