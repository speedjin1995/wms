<?php
namespace App\Services;

class ShipmentTypeService extends MasterDataService
{
    protected function table(): string
    {
        return 'shipment_types';
    }

    protected function searchColumns(): array
    {
        return ['shipment_types.shipment_type'];
    }

    protected function sortColumns(): array
    {
        return ['id' => 'shipment_types.id', 'shipment_type' => 'shipment_types.shipment_type'];
    }

    protected function formatListRow(array $row, int $rowNumber): array
    {
        return [
            'id' => $row['id'],
            'shipment_type' => $row['shipment_type'],
            'deleted' => $row['deleted']
        ];
    }

    protected function getColumns(): array
    {
        return ['id', 'shipment_type', 'customer'];
    }

    protected function updateColumns(): array
    {
        return ['shipment_type'];
    }

    protected function updatesCompany(): bool
    {
        return true;
    }

    protected function uploadNameColumn(): ?string
    {
        return 'shipment_type';
    }

    protected function uploadLabel(): string
    {
        return 'Shipment Type';
    }

    protected function mapUploadRow(array $row): array
    {
        return [
            'shipment_type' => !empty($row['ShipmentType']) ? trim($row['ShipmentType']) : ''
        ];
    }
}
