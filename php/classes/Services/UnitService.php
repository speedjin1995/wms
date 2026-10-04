<?php
namespace App\Services;

class UnitService extends MasterDataService
{
    protected function table(): string
    {
        return 'units';
    }

    protected function companyColumn(): ?string
    {
        return null;
    }

    protected function hasAudit(): bool
    {
        return false;
    }

    protected function searchColumns(): array
    {
        return ['units.units'];
    }

    protected function sortColumns(): array
    {
        return ['id' => 'units.id', 'no' => 'units.id', 'units' => 'units.units'];
    }

    protected function orderPrefix(): string
    {
        return "";
    }

    protected function formatListRow(array $row, int $rowNumber): array
    {
        return [
            'id' => $row['id'],
            'no' => $rowNumber,
            'units' => $row['units']
        ];
    }

    protected function getColumns(): array
    {
        return ['id', 'units'];
    }

    protected function updateColumns(): array
    {
        return ['units'];
    }
}
