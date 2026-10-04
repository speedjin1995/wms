<?php
namespace App\Services;

class LocationService extends MasterDataService
{
    protected function table(): string
    {
        return 'locations';
    }

    protected function searchColumns(): array
    {
        return ['locations.locations'];
    }

    protected function sortColumns(): array
    {
        return ['id' => 'locations.id', 'locations' => 'locations.locations'];
    }

    protected function formatListRow(array $row, int $rowNumber): array
    {
        return [
            'id' => $row['id'],
            'locations' => $row['locations'],
            'deleted' => $row['deleted']
        ];
    }

    protected function getColumns(): array
    {
        return ['id', 'locations', 'customer'];
    }

    protected function updateColumns(): array
    {
        return ['locations'];
    }

    protected function uploadNameColumn(): ?string
    {
        return 'locations';
    }

    protected function uploadLabel(): string
    {
        return 'Location';
    }

    protected function mapUploadRow(array $row): array
    {
        return [
            'locations' => !empty($row['Location']) ? trim($row['Location']) : ''
        ];
    }
}
