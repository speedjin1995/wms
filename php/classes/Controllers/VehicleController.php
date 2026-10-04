<?php
namespace App\Controllers;

use App\Services\VehicleService;

class VehicleController extends MasterDataController
{
    public function __construct(VehicleService $service)
    {
        parent::__construct($service);
    }

    protected function fields(): array
    {
        return [
            'vehicleNumber' => ['column' => 'veh_number', 'required' => true],
            'vehicleWeight' => ['column' => 'vehicle_weight', 'required' => false],
            'driver' => ['column' => 'driver', 'required' => false],
            'attendence1' => ['column' => 'attandence_1', 'required' => false],
            'attendence2' => ['column' => 'attandence_2', 'required' => false]
        ];
    }
}
