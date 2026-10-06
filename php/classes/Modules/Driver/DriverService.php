<?php
namespace App\Modules\Driver;

use App\Core\BaseService;

class DriverService extends BaseService
{
    /**
     * Get paginated drivers for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $where = "drivers.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'drivers.customer');

        $totalRecords = $this->countRows('drivers', $where, $types, $params);

        $this->applySearch($where, $params, $types, ['drivers.driver_name', 'drivers.driver_ic'], $search);
        $totalFiltered = $this->countRows('drivers', $where, $types, $params);

        $orderBy = $this->orderBy(
            ['id' => 'drivers.id', 'driver_name' => 'drivers.driver_name', 'driver_ic' => 'drivers.driver_ic'],
            $orderColumn, $orderDir, 'drivers.id'
        );
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $rows = $this->fetchAll("SELECT * FROM drivers WHERE $where ORDER BY drivers.deleted, (drivers.is_manual = 'Y') DESC, $orderBy LIMIT ?, ?", $types, $params);

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id' => $row['id'],
                'driver_name' => $row['driver_name'],
                'driver_ic' => $row['driver_ic'],
                'is_manual' => $row['is_manual'],
                'deleted' => $row['deleted']
            ];
        }

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Get single driver by ID
     */
    public function getById(int $id): ?array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        return $this->fetchOne("SELECT id, driver_name, driver_ic, customer FROM drivers WHERE $where", $types, $params);
    }

    /**
     * Create new driver
     */
    public function create(array $data, int $company): array
    {
        $data['customer'] = $this->resolveCompany($company);
        $data['created_by'] = $this->user;

        if (!$this->insertRow('drivers', $data)) {
            return ['status' => 'failed', 'message' => 'Failed to add record'];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    /**
     * Update existing driver
     */
    public function update(int $id, array $data): array
    {
        $data['is_manual'] = 'N';
        $data['modified_by'] = $this->user;

        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        if (!$this->updateRow('drivers', $data, $where, $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to update record'];
        }

        return ['status' => 'success', 'message' => 'Updated Successfully!!'];
    }

    public function delete(array $ids): array
    {
        return $this->softDeleteRecords('drivers', $ids);
    }

    public function reactivate(int $id): array
    {
        return $this->reactivateRecord('drivers', $id);
    }

    /**
     * Bulk insert drivers from Excel upload
     */
    public function upload(array $rows): array
    {
        return $this->uploadRecords('drivers', 'driver_name', 'Driver Name', $rows, function (array $row): array {
            return [
                'driver_name' => !empty($row['DriverName']) ? trim($row['DriverName']) : '',
                'driver_ic' => !empty($row['DriverIC']) ? trim($row['DriverIC']) : ''
            ];
        });
    }
}
