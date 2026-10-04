<?php
namespace App\Controllers;

use App\Services\ShipmentTypeService;

class ShipmentTypeController extends MasterDataController
{
    public function __construct(ShipmentTypeService $service)
    {
        parent::__construct($service);
    }

    protected function fields(): array
    {
        return [
            'shipmentType' => ['column' => 'shipment_type', 'required' => true]
        ];
    }
}
