<?php
namespace App\Controllers;

use App\Services\CurrencyService;

class CurrencyController extends MasterDataController
{
    public function __construct(CurrencyService $service)
    {
        parent::__construct($service);
    }

    protected function fields(): array
    {
        return [
            'currency' => ['column' => 'currency', 'required' => true],
            'description' => ['column' => 'description', 'required' => false],
            'rate' => ['column' => 'rate', 'required' => false]
        ];
    }

    /**
     * Set default currency
     */
    public function setDefault(): array
    {
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }

        return $this->service->setDefault($id);
    }
}
