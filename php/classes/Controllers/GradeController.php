<?php
namespace App\Controllers;

use App\Services\GradeService;

class GradeController extends MasterDataController
{
    public function __construct(GradeService $service)
    {
        parent::__construct($service);
    }

    protected function fields(): array
    {
        return [
            'unit' => ['column' => 'units', 'required' => true]
        ];
    }
}
