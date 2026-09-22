<?php
namespace App\Controllers;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Services\StockAdjustmentService;

class StockAdjustmentController
{
    private StockAdjustmentService $service;

    public function __construct(StockAdjustmentService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /list - List all adjustments
     */
    public function list(): array
    {
        $dateFrom = $this->parseDateInput($_POST['date_from'] ?? '');
        $dateTo = $this->parseDateInput($_POST['date_to'] ?? '');

        $data = $this->service->getList($dateFrom, $dateTo);

        return ['status' => 'success', 'data' => $data];
    }

    /**
     * GET /get - Get single adjustment by ID
     */
    public function get(): array
    {
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Missing adjustment ID'];
        }

        $adjustment = $this->service->getById($id);

        if (!$adjustment) {
            return ['status' => 'failed', 'message' => 'Adjustment not found'];
        }

        return ['status' => 'success', 'data' => $adjustment->toArray()];
    }

    /**
     * POST /save - Create new adjustment
     */
    public function save(): array
    {
        $adjustment = $this->buildAdjustmentFromRequest();
        
        if (is_array($adjustment)) {
            return $adjustment; // Error response
        }

        return $this->service->create($adjustment);
    }

    /**
     * POST /update - Update existing adjustment
     */
    public function update(): array
    {
        $id = (int)($_POST['id'] ?? 0);
        
        if (!$id) {
            return ['status' => 'failed', 'message' => 'Adjustment ID is required for update'];
        }

        $adjustment = $this->buildAdjustmentFromRequest();
        
        if (is_array($adjustment)) {
            return $adjustment; // Error response
        }

        $adjustment->id = $id;

        return $this->service->update($adjustment);
    }

    /**
     * Build StockAdjustment model from POST request
     */
    private function buildAdjustmentFromRequest(): StockAdjustment|array
    {
        // Validate required fields
        $adjustmentDate = $_POST['adjustment_date'] ?? '';
        $itemsJson = $_POST['items'] ?? '';

        if (empty($adjustmentDate)) {
            return ['status' => 'failed', 'message' => 'Adjustment date is required'];
        }

        $dateObj = $this->parseDateInput($adjustmentDate);
        if (!$dateObj) {
            return ['status' => 'failed', 'message' => 'Invalid date format'];
        }

        $items = json_decode($itemsJson, true);
        if (empty($items) || !is_array($items)) {
            return ['status' => 'failed', 'message' => 'At least one item is required'];
        }

        // Build model
        $adjustment = new StockAdjustment([
            'adjustment_date' => $dateObj,
            'type' => $_POST['type'] ?? 'Local',
            'remark' => $_POST['remark'] ?? '',
        ]);

        // Add items
        foreach ($items as $itemData) {
            if (empty($itemData['product_id'])) {
                continue;
            }

            $item = new StockAdjustmentItem([
                'id' => !empty($itemData['id']) ? (int)$itemData['id'] : null,
                'product_id' => (int)$itemData['product_id'],
                'grade' => $itemData['grade'] ?? null,
                'quantity_before' => (float)($itemData['quantity_before'] ?? 0),
                'adjustment_qty' => (float)($itemData['adjustment_qty'] ?? 0),
                'quantity_after' => (float)($itemData['quantity_after'] ?? 0),
                'unit_cost' => (float)($itemData['unit_cost'] ?? 0),
                'reason' => $itemData['reason'] ?? '',
            ]);
            $item->calculateTotalCost();
            $adjustment->addItem($item);
        }

        if (empty($adjustment->items)) {
            return ['status' => 'failed', 'message' => 'At least one valid item is required'];
        }

        return $adjustment;
    }

    /**
     * POST /delete - Delete adjustment
     */
    public function delete(): array
    {
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Missing adjustment ID'];
        }

        return $this->service->delete($id);
    }

    /**
     * GET /balance - Get stock balance for product/grade
     */
    public function balance(): array
    {
        $productId = (int)($_POST['product_id'] ?? 0);
        $grade = $_POST['grade'] ?? null;

        if (!$productId) {
            return ['status' => 'failed', 'message' => 'Missing product ID'];
        }

        return $this->service->getStockBalance($productId, $grade);
    }

    /**
     * GET /products - Get products with grades for form
     */
    public function products(array $categoryIds = [], string $type = 'Local'): array
    {
        $data = $this->service->getProductsWithGrades(!empty($categoryIds) ? $categoryIds : null, $type);

        return ['status' => 'success', 'data' => $data];
    }

    /**
     * GET /print - Print adjustment PDF
     */
    public function print(array $companyDetail): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            die('Invalid ID');
        }

        $data = $this->service->getPrintData($id);

        if (!$data) {
            die('Adjustment not found');
        }

        $this->service->generatePdf($data, $companyDetail);
    }

    /**
     * Parse date from d/m/Y format to Y-m-d
     */
    private function parseDateInput(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        $dateObj = \DateTime::createFromFormat('d/m/Y', $date);
        return $dateObj ? $dateObj->format('Y-m-d') : null;
    }
}
