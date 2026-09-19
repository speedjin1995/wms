<?php
namespace App\Models;

class StockAdjustmentItem
{
    public ?int $id = null;
    public ?int $adjustmentId = null;
    public int $productId;
    public ?string $grade = null;
    public float $quantityBefore = 0;
    public float $adjustmentQty = 0;
    public float $quantityAfter = 0;
    public float $unitCost = 0;
    public float $totalCost = 0;
    public ?string $reason = null;
    
    // Joined fields for display
    public ?string $productCode = null;
    public ?string $productName = null;
    public ?string $gradeName = null;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->fill($data);
        }
    }

    public function fill(array $data): self
    {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->adjustmentId = isset($data['adjustment_id']) ? (int)$data['adjustment_id'] : null;
        $this->productId = (int)($data['product_id'] ?? 0);
        $this->grade = $data['grade'] ?? null;
        $this->quantityBefore = (float)($data['quantity_before'] ?? 0);
        $this->adjustmentQty = (float)($data['adjustment_qty'] ?? 0);
        $this->quantityAfter = (float)($data['quantity_after'] ?? 0);
        $this->unitCost = (float)($data['unit_cost'] ?? 0);
        $this->totalCost = (float)($data['total_cost'] ?? 0);
        $this->reason = $data['reason'] ?? null;
        
        // Joined fields
        $this->productCode = $data['product_code'] ?? null;
        $this->productName = $data['product_name'] ?? null;
        $this->gradeName = $data['grade_name'] ?? null;
        
        return $this;
    }

    public function calculateTotalCost(): float
    {
        $this->totalCost = abs($this->adjustmentQty) * $this->unitCost;
        return $this->totalCost;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'adjustment_id' => $this->adjustmentId,
            'product_id' => $this->productId,
            'grade' => $this->grade,
            'quantity_before' => $this->quantityBefore,
            'adjustment_qty' => $this->adjustmentQty,
            'quantity_after' => $this->quantityAfter,
            'unit_cost' => $this->unitCost,
            'total_cost' => $this->totalCost,
            'reason' => $this->reason,
            'product_code' => $this->productCode,
            'product_name' => $this->productName,
            'grade_name' => $this->gradeName,
        ];
    }
}
