<?php
namespace App\Services;

class StateService extends MasterDataService
{
    protected function table(): string
    {
        return 'states';
    }

    protected function searchColumns(): array
    {
        return ['states.states'];
    }

    protected function sortColumns(): array
    {
        return ['id' => 'states.id', 'states' => 'states.states'];
    }

    protected function formatListRow(array $row, int $rowNumber): array
    {
        return [
            'id' => $row['id'],
            'states' => $row['states'],
            'deleted' => $row['deleted']
        ];
    }

    protected function getColumns(): array
    {
        return ['id', 'states', 'customer'];
    }

    protected function updateColumns(): array
    {
        return ['states'];
    }

    protected function updatesCompany(): bool
    {
        return true;
    }
}
