<?php
namespace App\Modules\Indicator;

use App\Core\BaseController;

class IndicatorController extends BaseController
{
    private IndicatorService $service;

    public function __construct(IndicatorService $service)
    {
        $this->service = $service;
    }

    /**
     * Get single indicator by ID
     */
    public function get(): array
    {
        $id = $this->postId();

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }

        $record = $this->service->getById($id);

        if (!$record) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success', 'message' => $record];
    }

    /**
     * Save the company's selected indicator
     */
    public function save(): array
    {
        $id = $this->postId('indicatorSelect');

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Please fill in all fields'];
        }

        return $this->service->setCurrent($id);
    }
}
