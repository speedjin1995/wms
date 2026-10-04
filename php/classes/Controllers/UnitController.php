<?php
namespace App\Controllers;

use App\Services\UnitService;

class UnitController extends MasterDataController
{
    public function __construct(UnitService $service)
    {
        parent::__construct($service);
    }

    protected function fields(): array
    {
        return [
            'code' => ['column' => 'units', 'required' => true]
        ];
    }
}
