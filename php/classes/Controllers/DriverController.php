<?php
namespace App\Controllers;

use App\Services\DriverService;

class DriverController extends MasterDataController
{
    public function __construct(DriverService $service)
    {
        parent::__construct($service);
    }

    protected function fields(): array
    {
        return [
            'driverName' => ['column' => 'driver_name', 'required' => true],
            'driverIC' => ['column' => 'driver_ic', 'required' => true]
        ];
    }
}
