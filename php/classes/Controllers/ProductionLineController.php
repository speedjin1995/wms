<?php
namespace App\Controllers;

use App\Services\ProductionLineService;

class ProductionLineController extends MasterDataController
{
    public function __construct(ProductionLineService $service)
    {
        parent::__construct($service);
    }

    protected function fields(): array
    {
        return [
            'productionLine' => ['column' => 'production_line', 'required' => true]
        ];
    }
}
