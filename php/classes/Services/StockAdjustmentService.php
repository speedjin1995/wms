<?php
namespace App\Services;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;

class StockAdjustmentService
{
    private \mysqli $db;
    private int $company;
    private int $userId;

    public function __construct(\mysqli $db, int $company, int $userId)
    {
        $this->db = $db;
        $this->company = $company;
        $this->userId = $userId;
    }

    /**
     * Get list of stock adjustments with optional date filter
     */
    public function getList(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $params = [];
        $types = '';
        $where = "sa.deleted = 0 AND sa.company = ?";
        $params[] = $this->company;
        $types .= 'i';

        if ($dateFrom) {
            $where .= " AND sa.adjustment_date >= ?";
            $params[] = $dateFrom;
            $types .= 's';
        }

        if ($dateTo) {
            $where .= " AND sa.adjustment_date <= ?";
            $params[] = $dateTo;
            $types .= 's';
        }

        $sql = "SELECT sa.*, u.name as created_by_name 
                FROM stock_adjustments sa 
                LEFT JOIN users u ON sa.created_by = u.id 
                WHERE $where 
                ORDER BY sa.adjustment_date DESC, sa.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $adjustments = [];
        while ($row = $result->fetch_assoc()) {
            $adjustments[] = (new StockAdjustment($row))->toArray(false);
        }
        $stmt->close();
        
        return $adjustments;
    }

    /**
     * Get single stock adjustment by ID with items
     */
    public function getById(int $id): ?StockAdjustment
    {
        $stmt = $this->db->prepare(
            "SELECT sa.*, u.name as created_by_name 
             FROM stock_adjustments sa 
             LEFT JOIN users u ON sa.created_by = u.id 
             WHERE sa.id = ? AND sa.company = ? AND sa.deleted = 0"
        );
        $stmt->bind_param('ii', $id, $this->company);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return null;
        }

        $adjustment = new StockAdjustment($row);
        $adjustment->setItems($this->getItemsByAdjustmentId($id));
        
        return $adjustment;
    }

    /**
     * Get items for an adjustment
     */
    private function getItemsByAdjustmentId(int $adjustmentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT sai.*, p.product_code, p.product_name, g.units as grade_name 
             FROM stock_adjustment_items sai 
             LEFT JOIN products p ON sai.product_id = p.id 
             LEFT JOIN grades g ON sai.grade = g.id 
             WHERE sai.adjustment_id = ? AND sai.deleted = 0 
             ORDER BY sai.id ASC"
        );
        $stmt->bind_param('i', $adjustmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = new StockAdjustmentItem($row);
        }
        $stmt->close();
        
        return $items;
    }

    /**
     * Create new stock adjustment
     */
    public function create(StockAdjustment $adjustment): array
    {
        $this->db->begin_transaction();

        try {
            $adjustmentNo = $this->generateAdjustmentNo();
            $adjustment->id = $this->insertHeader($adjustment, $adjustmentNo);

            foreach ($adjustment->items as $item) {
                $item->adjustmentId = $adjustment->id;
                $item->calculateTotalCost();
                $this->insertItem($item);
                $this->updateStockBalance($item);
                $this->addStockMovement($item, $adjustment->id);
            }

            $adjustment->recalculateTotals();
            $this->updateHeader($adjustment);

            $this->db->commit();
            
            return [
                'status' => 'success',
                'message' => 'Stock adjustment created successfully',
                'adjustment_no' => $adjustmentNo
            ];
        } catch (\Exception $e) {
            $this->db->rollback();
            return [
                'status' => 'failed',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update existing stock adjustment
     */
    public function update(StockAdjustment $adjustment): array
    {
        if (!$adjustment->id) {
            return ['status' => 'failed', 'message' => 'Adjustment ID is required for update'];
        }

        $existing = $this->getById($adjustment->id);
        if (!$existing) {
            return ['status' => 'failed', 'message' => 'Adjustment not found'];
        }

        $this->db->begin_transaction();

        try {
            // Step 1: Reverse all existing items stock
            $this->reverseExistingItems($adjustment->id);
            
            // Step 2: Soft delete ALL old items first
            $this->softDeleteItems($adjustment->id);

            // Step 3: Process items - update existing or insert new
            foreach ($adjustment->items as $item) {
                $item->adjustmentId = $adjustment->id;
                $item->calculateTotalCost();
                
                if ($item->id) {
                    // Existing item - update and reactivate
                    $this->updateItem($item);
                } else {
                    // New item - insert
                    $this->insertItem($item);
                }
                
                $this->updateStockBalance($item);
                $this->addStockMovement($item, $adjustment->id);
            }

            // Step 4: Update header totals
            $adjustment->recalculateTotals();
            $this->updateHeader($adjustment);

            $this->db->commit();
            
            return [
                'status' => 'success',
                'message' => 'Stock adjustment updated successfully',
                'adjustment_no' => $existing->adjustmentNo
            ];
        } catch (\Exception $e) {
            $this->db->rollback();
            return [
                'status' => 'failed',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete stock adjustment
     */
    public function delete(int $id): array
    {
        $adjustment = $this->getById($id);
        if (!$adjustment) {
            return ['status' => 'failed', 'message' => 'Adjustment not found'];
        }

        $this->db->begin_transaction();

        try {
            // Reverse stock for each item
            foreach ($adjustment->items as $item) {
                $this->reverseStockBalance($item);
                $this->addReversalMovement($item, $id);
            }

            // Soft delete items and header
            $this->softDeleteItems($id);
            $this->softDeleteHeader($id);

            $this->db->commit();
            
            return ['status' => 'success', 'message' => 'Stock adjustment deleted successfully'];
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['status' => 'failed', 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Get current stock balance for product/grade
     */
    public function getStockBalance(int $productId, ?string $grade): array
    {
        $stmt = $this->db->prepare(
            "SELECT balance FROM raw_stock_balance 
             WHERE product_id = ? AND grade = ? AND company = ? AND deleted = 0 
             LIMIT 1"
        );
        $stmt->bind_param('isi', $productId, $grade, $this->company);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $balance = $row ? (float)$row['balance'] : 0;

        // Get unit cost from product_grades
        $unitCost = 0;
        if ($grade) {
            $costStmt = $this->db->prepare(
                "SELECT purchasing_price FROM product_grades 
                 WHERE product_id = ? AND grade_id = ? AND deleted = 0 
                 LIMIT 1"
            );
            $costStmt->bind_param('is', $productId, $grade);
            $costStmt->execute();
            $costRow = $costStmt->get_result()->fetch_assoc();
            $costStmt->close();
            
            if ($costRow && $costRow['purchasing_price']) {
                $unitCost = (float)$costRow['purchasing_price'];
            }
        }

        return [
            'status' => 'success',
            'balance' => $balance,
            'unit_cost' => $unitCost
        ];
    }

    /**
     * Get products with grades for adjustment form
     */
    public function getProductsWithGrades(?array $categoryIds = null): array
    {
        $where = "p.deleted = 0 AND p.customer = ?";
        $params = [$this->company];
        $types = 'i';

        if (!empty($categoryIds)) {
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            $where .= " AND p.category IN ($placeholders)";
            foreach ($categoryIds as $catId) {
                $params[] = $catId;
                $types .= 'i';
            }
        }

        $sql = "SELECT p.id, p.product_code, p.product_name, p.category, c.category_name
                FROM products p 
                LEFT JOIN categories c ON p.category = c.id
                WHERE $where 
                ORDER BY c.category_name ASC, p.product_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $row['grades'] = $this->getGradesForProduct((int)$row['id']);
            $products[] = $row;
        }
        $stmt->close();

        return $products;
    }

    private function getGradesForProduct(int $productId): array
    {
        $stmt = $this->db->prepare(
            "SELECT pg.id as product_grade_id, pg.grade_id, pg.purchasing_price, g.units as grade_name
             FROM product_grades pg 
             LEFT JOIN grades g ON pg.grade_id = g.id 
             WHERE pg.product_id = ? AND pg.deleted = 0 
             ORDER BY g.units ASC"
        );
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $grades = [];
        while ($row = $result->fetch_assoc()) {
            $grades[] = $row;
        }
        $stmt->close();
        
        return $grades;
    }

    private function generateAdjustmentNo(): string
    {
        $today = date('Ymd');
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as cnt FROM stock_adjustments 
             WHERE company = ? AND DATE(created_datetime) = CURDATE()"
        );
        $stmt->bind_param('i', $this->company);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $seq = (int)$row['cnt'] + 1;
        return 'ADJ-' . $today . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function getAdjustmentNo(int $id): string
    {
        $stmt = $this->db->prepare("SELECT adjustment_no FROM stock_adjustments WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row['adjustment_no'] ?? '';
    }

    private function insertHeader(StockAdjustment $adjustment, string $adjustmentNo): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO stock_adjustments 
             (adjustment_no, adjustment_date, remark, total_items, total_qty, total_cost, company, created_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $totalQty = (string)$adjustment->totalQty;
        $totalCost = (string)$adjustment->totalCost;
        $stmt->bind_param(
            'sssissii',
            $adjustmentNo,
            $adjustment->adjustmentDate,
            $adjustment->remark,
            $adjustment->totalItems,
            $totalQty,
            $totalCost,
            $this->company,
            $this->userId
        );
        $stmt->execute();
        $id = $this->db->insert_id;
        $stmt->close();
        return $id;
    }

    private function updateHeader(StockAdjustment $adjustment): void
    {
        $stmt = $this->db->prepare(
            "UPDATE stock_adjustments 
             SET adjustment_date = ?, remark = ?, total_items = ?, total_qty = ?, total_cost = ?, modified_by = ? 
             WHERE id = ?"
        );
        $totalQty = (string)$adjustment->totalQty;
        $totalCost = (string)$adjustment->totalCost;
        $stmt->bind_param(
            'ssissii',
            $adjustment->adjustmentDate,
            $adjustment->remark,
            $adjustment->totalItems,
            $totalQty,
            $totalCost,
            $this->userId,
            $adjustment->id
        );
        $stmt->execute();
        $stmt->close();
    }

    private function insertItem(StockAdjustmentItem $item): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO stock_adjustment_items 
             (adjustment_id, product_id, grade, quantity_before, adjustment_qty, quantity_after, unit_cost, total_cost, reason, created_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $qtyBefore = (string)$item->quantityBefore;
        $adjQty = (string)$item->adjustmentQty;
        $qtyAfter = (string)$item->quantityAfter;
        $unitCost = (string)$item->unitCost;
        $totalCost = (string)$item->totalCost;
        $stmt->bind_param(
            'iisssssssi',
            $item->adjustmentId,
            $item->productId,
            $item->grade,
            $qtyBefore,
            $adjQty,
            $qtyAfter,
            $unitCost,
            $totalCost,
            $item->reason,
            $this->userId
        );
        $stmt->execute();
        $item->id = $this->db->insert_id;
        $stmt->close();
    }

    private function updateItem(StockAdjustmentItem $item): void
    {
        $stmt = $this->db->prepare(
            "UPDATE stock_adjustment_items 
             SET product_id = ?, grade = ?, quantity_before = ?, adjustment_qty = ?, 
                 quantity_after = ?, unit_cost = ?, total_cost = ?, reason = ?, 
                 deleted = 0, modified_by = ?
             WHERE id = ?"
        );
        $qtyBefore = (string)$item->quantityBefore;
        $adjQty = (string)$item->adjustmentQty;
        $qtyAfter = (string)$item->quantityAfter;
        $unitCost = (string)$item->unitCost;
        $totalCost = (string)$item->totalCost;
        $stmt->bind_param(
            'isssssssii',
            $item->productId,
            $item->grade,
            $qtyBefore,
            $adjQty,
            $qtyAfter,
            $unitCost,
            $totalCost,
            $item->reason,
            $this->userId,
            $item->id
        );
        $stmt->execute();
        $stmt->close();
    }

    private function softDeleteItems(int $adjustmentId): void
    {
        $stmt = $this->db->prepare("UPDATE stock_adjustment_items SET deleted = 1, modified_by = ? WHERE adjustment_id = ? AND deleted = 0");
        $stmt->bind_param('ii', $this->userId, $adjustmentId);
        $stmt->execute();
        $stmt->close();
    }

    private function softDeleteHeader(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE stock_adjustments SET deleted = 1, modified_by = ? WHERE id = ?");
        $stmt->bind_param('ii', $this->userId, $id);
        $stmt->execute();
        $stmt->close();
    }

    private function reverseExistingItems(int $adjustmentId): void
    {
        $items = $this->getItemsByAdjustmentId($adjustmentId);
        foreach ($items as $item) {
            $this->reverseStockBalance($item);
        }
    }

    private function updateStockBalance(StockAdjustmentItem $item): void
    {
        $stmt = $this->db->prepare(
            "SELECT id, balance FROM raw_stock_balance 
             WHERE product_id = ? AND grade = ? AND company = ? AND deleted = 0"
        );
        $stmt->bind_param('isi', $item->productId, $item->grade, $this->company);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $updateStmt = $this->db->prepare(
                "UPDATE raw_stock_balance SET balance = ?, modified_by = ? WHERE id = ?"
            );
            $updateStmt->bind_param('dii', $item->quantityAfter, $this->userId, $row['id']);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            $insertStmt = $this->db->prepare(
                "INSERT INTO raw_stock_balance (product_id, grade, company, balance, created_by) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $insertStmt->bind_param('isidi', $item->productId, $item->grade, $this->company, $item->quantityAfter, $this->userId);
            $insertStmt->execute();
            $insertStmt->close();
        }
    }

    private function reverseStockBalance(StockAdjustmentItem $item): void
    {
        $stmt = $this->db->prepare(
            "SELECT id, balance FROM raw_stock_balance 
             WHERE product_id = ? AND grade = ? AND company = ? AND deleted = 0"
        );
        $stmt->bind_param('isi', $item->productId, $item->grade, $this->company);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $newBalance = (float)$row['balance'] - $item->adjustmentQty;
            $updateStmt = $this->db->prepare(
                "UPDATE raw_stock_balance SET balance = ?, modified_by = ? WHERE id = ?"
            );
            $updateStmt->bind_param('dii', $newBalance, $this->userId, $row['id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }

    private function addStockMovement(StockAdjustmentItem $item, int $adjustmentId): void
    {
        require_once __DIR__ . '/../../services/stockManagementService.php';
        
        $movType = $item->adjustmentQty >= 0 ? 'ADD' : 'MINUS';
        addStockMovement(
            $this->db,
            generateMovementNo($this->db, $this->company),
            $item->productId,
            $item->grade,
            $this->company,
            'adjustment',
            $adjustmentId,
            $movType,
            'ADJUSTMENT',
            abs($item->adjustmentQty),
            $item->quantityBefore,
            $item->quantityAfter,
            null,
            null,
            $this->userId
        );
    }

    private function addReversalMovement(StockAdjustmentItem $item, int $adjustmentId): void
    {
        require_once __DIR__ . '/../../services/stockManagementService.php';
        
        $stmt = $this->db->prepare(
            "SELECT balance FROM raw_stock_balance 
             WHERE product_id = ? AND grade = ? AND company = ? AND deleted = 0"
        );
        $stmt->bind_param('isi', $item->productId, $item->grade, $this->company);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $currentBalance = $row ? (float)$row['balance'] : 0;
        $newBalance = $currentBalance - $item->adjustmentQty;
        
        addStockMovement(
            $this->db,
            generateMovementNo($this->db, $this->company),
            $item->productId,
            $item->grade,
            $this->company,
            'adjustment',
            $adjustmentId,
            'REVERSAL',
            'ADJUSTMENT',
            abs($item->adjustmentQty),
            $currentBalance,
            $newBalance,
            null,
            null,
            $this->userId
        );
    }

    /**
     * Get print data for adjustment
     */
    public function getPrintData(int $id): ?array
    {
        $adjustment = $this->getById($id);
        
        if (!$adjustment) {
            return null;
        }

        return $adjustment->toArray(true);
    }

    /**
     * Generate print HTML for adjustment
     */
    public function generatePdf(array $data, array $companyDetail): void
    {
        require_once __DIR__ . '/../../modules/wholesales/stockAdjustment/partial/pdfStockAdjustment.php';
        
        generateStockAdjustmentPdf($data, $companyDetail);
    }
}
