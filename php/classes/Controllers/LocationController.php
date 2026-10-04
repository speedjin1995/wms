<?php
namespace App\Controllers;

use App\Services\LocationService;

class LocationController extends MasterDataController
{
    public function __construct(LocationService $service)
    {
        parent::__construct($service);
    }

    protected function fields(): array
    {
        return [
            'location' => ['column' => 'locations', 'required' => true]
        ];
    }
}
