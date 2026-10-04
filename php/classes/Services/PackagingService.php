<?php
namespace App\Services;

class PackagingService extends MasterDataService
{
    protected function table(): string
    {
        return 'packaging';
    }

    protected function searchColumns(): array
    {
        return ['packaging.packaging_name'];
    }

    protected function sortColumns(): array
    {
        return ['id' => 'packaging.id', 'packaging_name' => 'packaging.packaging_name', 'packaging_type' => 'packaging.packaging_type', 'weight' => 'packaging.weight', 'is_by_weight' => 'packaging.is_by_weight'];
    }

    protected function formatListRow(array $row, int $rowNumber): array
    {
        return [
            'id' => $row['id'],
            'packaging_name' => $row['packaging_name'],
            'packaging_type' => $row['packaging_type'],
            'weight' => $row['weight'],
            'is_by_weight' => $row['is_by_weight'],
            'deleted' => $row['deleted']
        ];
    }

    protected function getColumns(): array
    {
        return ['id', 'packaging_name', 'packaging_type', 'weight', 'is_by_weight', 'customer'];
    }

    protected function updateColumns(): array
    {
        return ['packaging_name', 'packaging_type', 'weight', 'is_by_weight'];
    }

    protected function uploadNameColumn(): ?string
    {
        return 'packaging_name';
    }

    protected function uploadLabel(): string
    {
        return 'Packaging';
    }

    protected function mapUploadRow(array $row): array
    {
        return [
            'packaging_name' => !empty($row['PackagingName']) ? trim($row['PackagingName']) : '',
            'packaging_type' => !empty($row['PackagingType']) ? trim($row['PackagingType']) : 'Original',
            'weight' => !empty($row['PackagingWeight']) ? trim($row['PackagingWeight']) : 0,
            'is_by_weight' => !empty($row['ByWeight']) ? trim($row['ByWeight']) : 'N'
        ];
    }
}
