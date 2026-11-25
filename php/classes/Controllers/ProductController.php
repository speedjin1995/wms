<?php
namespace App\Controllers;

use App\Services\ProductService;

class ProductController
{
    private ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    /**
     * Get products with grades filtered by type
     */
    public function getProductsByType(?array $categoryIds = null): array
    {
        $type = 'Local';

        try {
            $products = $this->service->getProductsWithGrades($categoryIds, $type);
            return [
                'status' => 'success',
                'data' => $products
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Bulk set DO weight tolerance for all active products of the company
     */
    public function bulkUpdateDoTolerance(array $input, int $userId): array
    {
        $gain = $input['doWeightTolerance'] ?? null;
        $loss = $input['doWeightToleranceMinus'] ?? null;
        $type = $input['doWeightToleranceType'] ?? null;

        if (!is_numeric($gain) || !is_numeric($loss) || $gain < 0 || $loss < 0 || !in_array($type, ['KG', 'PERCENT'], true)) {
            return ['status' => 'failed', 'message' => 'Invalid DO tolerance values'];
        }

        try {
            $count = $this->service->bulkUpdateDoTolerance((float)$gain, (float)$loss, $type, $userId);
            return ['status' => 'success', 'message' => $count . ' product(s) updated'];
        } catch (\Exception $e) {
            return ['status' => 'failed', 'message' => 'Failed to update DO tolerance'];
        }
    }
}
