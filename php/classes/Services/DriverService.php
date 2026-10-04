<?php
namespace App\Services;

class DriverService extends MasterDataService
{
    protected function table(): string
    {
        return 'drivers';
    }

    protected function searchColumns(): array
    {
        return ['drivers.driver_name', 'drivers.driver_ic'];
    }

    protected function sortColumns(): array
    {
        return ['id' => 'drivers.id', 'driver_name' => 'drivers.driver_name', 'driver_ic' => 'drivers.driver_ic'];
    }

    protected function orderPrefix(): string
    {
        return "drivers.deleted, (drivers.is_manual = 'Y') DESC";
    }

    protected function formatListRow(array $row, int $rowNumber): array
    {
        return [
            'id' => $row['id'],
            'driver_name' => $row['driver_name'],
            'driver_ic' => $row['driver_ic'],
            'is_manual' => $row['is_manual'],
            'deleted' => $row['deleted']
        ];
    }

    protected function getColumns(): array
    {
        return ['id', 'driver_name', 'driver_ic', 'customer'];
    }

    protected function updateColumns(): array
    {
        return ['driver_name', 'driver_ic'];
    }

    protected function updateExtras(): array
    {
        return ['is_manual' => 'N'];
    }

    protected function uploadNameColumn(): ?string
    {
        return 'driver_name';
    }

    protected function uploadLabel(): string
    {
        return 'Driver Name';
    }

    protected function mapUploadRow(array $row): array
    {
        return [
            'driver_name' => !empty($row['DriverName']) ? trim($row['DriverName']) : '',
            'driver_ic' => !empty($row['DriverIC']) ? trim($row['DriverIC']) : ''
        ];
    }
}
