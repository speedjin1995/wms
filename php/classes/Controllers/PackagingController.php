<?php
namespace App\Controllers;

use App\Services\PackagingService;

class PackagingController extends MasterDataController
{
    public function __construct(PackagingService $service)
    {
        parent::__construct($service);
    }

    protected function fields(): array
    {
        return [
            'packagingName' => ['column' => 'packaging_name', 'required' => true],
            'packagingType' => ['column' => 'packaging_type', 'required' => true],
            'packagingWeight' => ['column' => 'weight', 'required' => false],
            'packagingByWeight' => ['column' => 'is_by_weight', 'required' => true]
        ];
    }
}
