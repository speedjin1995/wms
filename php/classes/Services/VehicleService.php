<?php
namespace App\Services;

class VehicleService extends BaseService
{
    private const LIST_FROM = 'vehicles LEFT JOIN drivers ON vehicles.driver = drivers.id';

    /**
     * Get paginated vehicles for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $where = "vehicles.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'vehicles.customer');

        $totalRecords = $this->countRows(self::LIST_FROM, $where, $types, $params);

        $this->applySearch($where, $params, $types, ['vehicles.veh_number'], $search);
        $totalFiltered = $this->countRows(self::LIST_FROM, $where, $types, $params);

        $orderBy = $this->orderBy(
            ['id' => 'vehicles.id', 'veh_number' => 'vehicles.veh_number', 'vehicle_weight' => 'vehicles.vehicle_weight', 'driver_name' => 'drivers.driver_name'],
            $orderColumn, $orderDir, 'vehicles.id'
        );
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $rows = $this->fetchAll(
            "SELECT vehicles.*, drivers.driver_name AS driver_name FROM " . self::LIST_FROM . "
             WHERE $where ORDER BY vehicles.deleted, (vehicles.is_manual = 'Y') DESC, $orderBy LIMIT ?, ?",
            $types, $params
        );

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id' => $row['id'],
                'veh_number' => $row['veh_number'],
                'vehicle_weight' => $row['vehicle_weight'],
                'driver_name' => $row['driver_name'],
                'attandence_1' => $row['attandence_1'],
                'attandence_2' => $row['attandence_2'],
                'is_manual' => $row['is_manual'],
                'deleted' => $row['deleted']
            ];
        }

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Get single vehicle by ID
     */
    public function getById(int $id): ?array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        return $this->fetchOne("SELECT id, veh_number, vehicle_weight, driver, attandence_1, attandence_2, customer FROM vehicles WHERE $where", $types, $params);
    }

    /**
     * Create new vehicle
     */
    public function create(array $data, int $company): array
    {
        $data['customer'] = $this->resolveCompany($company);
        $data['created_by'] = $this->user;

        if (!$this->insertRow('vehicles', $data)) {
            return ['status' => 'failed', 'message' => 'Failed to add record'];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    /**
     * Update existing vehicle
     */
    public function update(int $id, array $data): array
    {
        $data['is_manual'] = 'N';
        $data['modified_by'] = $this->user;

        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        if (!$this->updateRow('vehicles', $data, $where, $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to update record'];
        }

        return ['status' => 'success', 'message' => 'Updated Successfully!!'];
    }

    public function delete(array $ids): array
    {
        return $this->softDeleteRecords('vehicles', $ids);
    }

    public function reactivate(int $id): array
    {
        return $this->reactivateRecord('vehicles', $id);
    }

    /**
     * Bulk insert vehicles from Excel upload
     */
    public function upload(array $rows): array
    {
        return $this->uploadRecords('vehicles', 'veh_number', 'Vehicle Number', $rows, function (array $row): array {
            return [
                'veh_number' => !empty($row['VehicleNumber']) ? trim($row['VehicleNumber']) : '',
                'vehicle_weight' => !empty($row['VehicleWeight']) ? trim($row['VehicleWeight']) : '',
                'driver' => !empty($row['DriverName']) ? $this->findDriverId(trim($row['DriverName'])) : null
            ];
        });
    }

    /**
     * Find active driver ID by name within the session company
     */
    private function findDriverId(string $driverName): ?int
    {
        $row = $this->fetchOne("SELECT id FROM drivers WHERE driver_name = ? AND customer = ? AND deleted = 0", 'si', [$driverName, $this->company]);

        return $row ? (int)$row['id'] : null;
    }
}
