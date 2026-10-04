<?php
namespace App\Services;

class GradeService extends MasterDataService
{
    protected function table(): string
    {
        return 'grades';
    }

    protected function searchColumns(): array
    {
        return ['grades.units'];
    }

    protected function sortColumns(): array
    {
        return ['id' => 'grades.id', 'units' => 'grades.units'];
    }

    protected function formatListRow(array $row, int $rowNumber): array
    {
        return [
            'id' => $row['id'],
            'units' => $row['units'],
            'is_manual' => $row['is_manual'],
            'deleted' => $row['deleted']
        ];
    }

    protected function getColumns(): array
    {
        return ['id', 'units', 'customer'];
    }

    protected function updateColumns(): array
    {
        return ['units'];
    }

    protected function updateExtras(): array
    {
        return ['is_manual' => 'N'];
    }

    protected function uploadNameColumn(): ?string
    {
        return 'units';
    }

    protected function uploadLabel(): string
    {
        return 'Grade';
    }

    protected function mapUploadRow(array $row): array
    {
        return [
            'units' => !empty($row['Unit']) ? trim($row['Unit']) : ''
        ];
    }
}
