<?php
namespace App\Services;

class VehicleService extends MasterDataService
{
    protected function table(): string
    {
        return 'vehicles';
    }

    protected function listFrom(): string
    {
        return 'vehicles LEFT JOIN drivers ON vehicles.driver = drivers.id';
    }

    protected function listSelect(): string
    {
        return 'vehicles.*, drivers.driver_name AS driver_name';
    }

    protected function searchColumns(): array
    {
        return ['vehicles.veh_number'];
    }

    protected function sortColumns(): array
    {
        return ['id' => 'vehicles.id', 'veh_number' => 'vehicles.veh_number', 'vehicle_weight' => 'vehicles.vehicle_weight', 'driver_name' => 'drivers.driver_name'];
    }

    protected function orderPrefix(): string
    {
        return "vehicles.deleted, (vehicles.is_manual = 'Y') DESC";
    }

    protected function formatListRow(array $row, int $rowNumber): array
    {
        return [
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

    protected function getColumns(): array
    {
        return ['id', 'veh_number', 'vehicle_weight', 'driver', 'attandence_1', 'attandence_2', 'customer'];
    }

    protected function updateColumns(): array
    {
        return ['veh_number', 'vehicle_weight', 'driver', 'attandence_1', 'attandence_2'];
    }

    protected function updateExtras(): array
    {
        return ['is_manual' => 'N'];
    }

    protected function uploadNameColumn(): ?string
    {
        return 'veh_number';
    }

    protected function uploadLabel(): string
    {
        return 'Vehicle Number';
    }

    protected function mapUploadRow(array $row): array
    {
        return [
            'veh_number' => !empty($row['VehicleNumber']) ? trim($row['VehicleNumber']) : '',
            'vehicle_weight' => !empty($row['VehicleWeight']) ? trim($row['VehicleWeight']) : '',
            'driver' => !empty($row['DriverName']) ? $this->findDriverId(trim($row['DriverName'])) : null
        ];
    }

    /**
     * Find active driver ID by name within the session company
     */
    private function findDriverId(string $driverName): ?int
    {
        $stmt = $this->db->prepare("SELECT id FROM drivers WHERE driver_name = ? AND customer = ? AND deleted = 0");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('si', $driverName, $this->company);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ? (int)$row['id'] : null;
    }
}
