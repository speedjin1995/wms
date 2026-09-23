<?php
namespace App\Models;

class StockAdjustment
{
    public ?int $id = null;
    public ?string $adjustmentNo = null;
    public ?string $adjustmentDate = null;
    public string $type = 'Local';
    public ?string $remark = null;
    public int $totalItems = 0;
    public float $totalQty = 0;
    public float $totalCost = 0;
    public int $company;
    public ?int $createdBy = null;
    public ?string $createdDatetime = null;
    public ?string $createdByName = null;
    
    /** @var StockAdjustmentItem[] */
    public array $items = [];

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->fill($data);
        }
    }

    public function fill(array $data): self
    {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->adjustmentNo = $data['adjustment_no'] ?? null;
        $this->adjustmentDate = $data['adjustment_date'] ?? null;
        $this->type = $data['type'] ?? 'Local';
        $this->remark = $data['remark'] ?? null;
        $this->totalItems = (int)($data['total_items'] ?? 0);
        $this->totalQty = (float)($data['total_qty'] ?? 0);
        $this->totalCost = (float)($data['total_cost'] ?? 0);
        $this->company = (int)($data['company'] ?? 0);
        $this->createdBy = isset($data['created_by']) ? (int)$data['created_by'] : null;
        $this->createdDatetime = $data['created_datetime'] ?? null;
        $this->createdByName = $data['created_by_name'] ?? null;
        
        return $this;
    }

    public function addItem(StockAdjustmentItem $item): self
    {
        $this->items[] = $item;
        $this->recalculateTotals();
        return $this;
    }

    public function setItems(array $items): self
    {
        $this->items = [];
        foreach ($items as $item) {
            if ($item instanceof StockAdjustmentItem) {
                $this->items[] = $item;
            } elseif (is_array($item)) {
                $this->items[] = new StockAdjustmentItem($item);
            }
        }
        $this->recalculateTotals();
        return $this;
    }

    public function recalculateTotals(): self
    {
        $this->totalItems = count($this->items);
        $this->totalQty = 0;
        $this->totalCost = 0;
        foreach ($this->items as $item) {
            $this->totalQty += $item->adjustmentQty;
            $this->totalCost += $item->totalCost;
        }
        return $this;
    }

    public function getAdjustmentDateFormatted(string $format = 'd/m/Y'): string
    {
        if (empty($this->adjustmentDate)) {
            return '';
        }
        $date = \DateTime::createFromFormat('Y-m-d', $this->adjustmentDate);
        return $date ? $date->format($format) : $this->adjustmentDate;
    }

    public function toArray(bool $includeItems = true): array
    {
        $data = [
            'id' => $this->id,
            'adjustment_no' => $this->adjustmentNo,
            'adjustment_date' => $this->adjustmentDate,
            'adjustment_date_display' => $this->getAdjustmentDateFormatted(),
            'type' => $this->type,
            'remark' => $this->remark,
            'total_items' => $this->totalItems,
            'total_qty' => $this->totalQty,
            'total_cost' => $this->totalCost,
            'company' => $this->company,
            'created_by' => $this->createdBy,
            'created_datetime' => $this->createdDatetime,
            'created_by_name' => $this->createdByName,
        ];
        
        if ($includeItems) {
            $data['items'] = array_map(fn($item) => $item->toArray(), $this->items);
        }
        
        return $data;
    }
}
