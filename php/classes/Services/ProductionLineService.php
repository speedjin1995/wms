<?php
namespace App\Services;

class ProductionLineService extends MasterDataService
{
    protected function table(): string
    {
        return 'production_lines';
    }

    protected function companyColumn(): ?string
    {
        return 'customers';
    }

    protected function searchColumns(): array
    {
        return ['production_lines.production_line'];
    }

    protected function sortColumns(): array
    {
        return ['id' => 'production_lines.id', 'production_line' => 'production_lines.production_line'];
    }

    protected function formatListRow(array $row, int $rowNumber): array
    {
        return [
            'id' => $row['id'],
            'production_line' => $row['production_line'],
            'customers' => $row['customers'],
            'deleted' => $row['deleted']
        ];
    }

    protected function getColumns(): array
    {
        return ['id', 'production_line', 'customers'];
    }

    protected function updateColumns(): array
    {
        return ['production_line'];
    }

    protected function uploadNameColumn(): ?string
    {
        return 'production_line';
    }

    protected function uploadLabel(): string
    {
        return 'Production Line';
    }

    protected function mapUploadRow(array $row): array
    {
        return [
            'production_line' => !empty($row['ProductionLine']) ? trim($row['ProductionLine']) : ''
        ];
    }
}
