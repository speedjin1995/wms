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
        $type = $_POST['type'] ?? 'Local';
        
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
}
