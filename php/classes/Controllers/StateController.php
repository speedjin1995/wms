<?php
namespace App\Controllers;

use App\Services\StateService;

class StateController extends MasterDataController
{
    public function __construct(StateService $service)
    {
        parent::__construct($service);
    }

    protected function fields(): array
    {
        return [
            'state' => ['column' => 'states', 'required' => true]
        ];
    }
}
