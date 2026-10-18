<?php
namespace App\Modules\StockAdjustment;

use App\Core\BaseService;

class StockAdjustmentService extends BaseService
{
    private const BALANCE_TABLES = [
        'raw' => 'raw_stock_balance',
        'graded' => 'grading_stock_balance'
    ];

    /**
     * Get company stock level setting
     */
    private function getStockLevel(): string
    {
        $stmt = $this->db->prepare("SELECT stock_level FROM companies WHERE id = ?");
        $stmt->bind_param('i', $this->company);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row['stock_level'] ?? 'GRADE';
    }

    /**
     * Get balance table name from whitelist
     */
    private function balanceTable(string $stockTable): string
    {
        return self::BALANCE_TABLES[$stockTable] ?? self::BALANCE_TABLES['raw'];
    }

    /**
     * Get list of stock adjustments with optional date filter
     */
    public function getList(?string $dateFrom = null, ?string $dateTo = null, string $stockTable = 'raw'): array
    {
        $params = [];
        $types = '';
        $where = "sa.deleted = 0 AND sa.company = ? AND sa.stock_table = ?";
        $params[] = $this->company;
        $params[] = $stockTable;
        $types .= 'is';

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
            // Set stock_level based on stock_table
            if ($adjustment->stockTable === 'graded') {
                $adjustment->stockLevel = 'GRADE';
            } else {
                $adjustment->stockLevel = $this->getStockLevel();
            }

            // Force grade to '0' for product-level raw adjustments
            $isProductLevel = ($adjustment->stockTable === 'raw' && $adjustment->stockLevel === 'PRODUCT');
            if ($isProductLevel) {
                foreach ($adjustment->items as $item) {
                    $item->grade = '0';
                }
            }

            $adjustmentNo = $this->generateAdjustmentNo();
            $adjustment->id = $this->insertHeader($adjustment, $adjustmentNo);

            foreach ($adjustment->items as $item) {
                $item->adjustmentId = $adjustment->id;
                $item->calculateTotalCost();
                
                // Get live balance and update with delta
                $balanceData = $this->updateStockBalanceDelta($item, $adjustment->type, $adjustment->stockTable, $adjustment->stockLevel);
                $item->quantityBefore = $balanceData['before'];
                $item->quantityAfter = $balanceData['after'];
                
                $this->insertItem($item);
                $this->addStockMovement($item, $adjustment->id, $adjustment->type, $adjustment->stockTable);
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

        // Use stored stock_table/stock_level (kind is locked on edit)
        $stockTable = $existing->stockTable;
        $stockLevel = $existing->stockLevel;
        $adjustment->stockTable = $stockTable;
        $adjustment->stockLevel = $stockLevel;

        // Force grade to '0' for product-level raw adjustments
        $isProductLevel = ($stockTable === 'raw' && $stockLevel === 'PRODUCT');
        if ($isProductLevel) {
            foreach ($adjustment->items as $item) {
                $item->grade = '0';
            }
        }

        $this->db->begin_transaction();

        try {
            // Step 1: Reverse all existing items stock
            $this->reverseExistingItems($adjustment->id, $existing->type, $stockTable);
            
            // Step 2: Soft delete ALL old items first
            $this->softDeleteItems($adjustment->id);

            // Step 3: Process items - update existing or insert new
            foreach ($adjustment->items as $item) {
                $item->adjustmentId = $adjustment->id;
                $item->calculateTotalCost();
                
                // Get live balance and update with delta
                $balanceData = $this->updateStockBalanceDelta($item, $adjustment->type, $stockTable, $stockLevel);
                $item->quantityBefore = $balanceData['before'];
                $item->quantityAfter = $balanceData['after'];
                
                if ($item->id) {
                    // Existing item - update and reactivate
                    $this->updateItem($item, $adjustment->id);
                } else {
                    // New item - insert
                    $this->insertItem($item);
                }
                
                $this->addStockMovement($item, $adjustment->id, $adjustment->type, $stockTable);
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
                $this->reverseStockBalance($item, $adjustment->type, $adjustment->stockTable);
                $this->addReversalMovement($item, $id, $adjustment->type, $adjustment->stockTable);
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
    public function getStockBalance(int $productId, ?string $grade, string $type = 'Local', string $stockTable = 'raw'): array
    {
        $tableName = $this->balanceTable($stockTable);
        $stockLevel = ($stockTable === 'raw') ? $this->getStockLevel() : 'GRADE';
        $isProductLevel = ($stockTable === 'raw' && $stockLevel === 'PRODUCT');

        if ($isProductLevel) {
            // Product-level: SUM across all grades
            $sql = "SELECT COALESCE(SUM(balance), 0) as balance FROM {$tableName} 
                    WHERE product_id = ? AND type = ? AND company = ? AND deleted = 0";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('isi', $productId, $type, $this->company);
        } else {
            // Grade-level: exact product + grade row
            $sql = "SELECT balance FROM {$tableName} 
                    WHERE product_id = ? AND grade = ? AND type = ? AND company = ? AND deleted = 0 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('issi', $productId, $grade, $type, $this->company);
        }
        
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $balance = $row ? (float)$row['balance'] : 0;

        // Get unit cost from product_grades
        $unitCost = 0;
        if ($isProductLevel) {
            // For product-level, use first grade's purchasing_price
            $costStmt = $this->db->prepare(
                "SELECT purchasing_price FROM product_grades 
                 WHERE product_id = ? AND deleted = 0 AND type = ?
                 ORDER BY id ASC LIMIT 1"
            );
            $costStmt->bind_param('is', $productId, $type);
        } else if ($grade) {
            $costStmt = $this->db->prepare(
                "SELECT purchasing_price FROM product_grades 
                 WHERE product_id = ? AND grade_id = ? AND deleted = 0 
                 LIMIT 1"
            );
            $costStmt->bind_param('is', $productId, $grade);
        }
        
        if (isset($costStmt)) {
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
            'unit_cost' => $unitCost,
            'stock_level' => $stockLevel
        ];
    }

    /**
     * Get products with grades for adjustment form
     */
    public function getProductsWithGrades(?array $categoryIds = null, string $type = 'Local'): array
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

        // Only get products that have grades of the specified type
        $where .= " AND EXISTS (SELECT 1 FROM product_grades pg WHERE pg.product_id = p.id AND pg.deleted = 0 AND pg.type = ?)";
        $params[] = $type;
        $types .= 's';

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
            $row['grades'] = $this->getGradesForProduct((int)$row['id'], $type);
            $products[] = $row;
        }
        $stmt->close();

        return $products;
    }

    private function getGradesForProduct(int $productId, string $type = 'Local'): array
    {
        $stmt = $this->db->prepare(
            "SELECT pg.id as product_grade_id, pg.grade_id, pg.purchasing_price, g.units as grade_name
             FROM product_grades pg 
             LEFT JOIN grades g ON pg.grade_id = g.id 
             WHERE pg.product_id = ? AND pg.deleted = 0 AND pg.type = ?
             ORDER BY g.units ASC"
        );
        $stmt->bind_param('is', $productId, $type);
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
        $row = $this->fetchOne("SELECT COUNT(*) AS cnt FROM stock_adjustments WHERE company = ? AND DATE(created_datetime) = CURDATE()", 'i', [$this->company]);

        return $this->nextRunningNo('stock_adjustments', 'adjustment_no', 'ADJ-' . date('Ymd') . '-', $this->company, (int)($row['cnt'] ?? 0) + 1);
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
             (adjustment_no, adjustment_date, type, stock_table, stock_level, remark, total_items, total_qty, total_cost, company, created_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $totalQty = (string)$adjustment->totalQty;
        $totalCost = (string)$adjustment->totalCost;
        $stmt->bind_param(
            'ssssssissii',
            $adjustmentNo,
            $adjustment->adjustmentDate,
            $adjustment->type,
            $adjustment->stockTable,
            $adjustment->stockLevel,
            $adjustment->remark,
            $adjustment->totalItems,
            $totalQty,
            $totalCost,
            $this->company,
            $this->user
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
             SET adjustment_date = ?, type = ?, remark = ?, total_items = ?, total_qty = ?, total_cost = ?, modified_by = ? 
             WHERE id = ? AND company = ?"
        );
        $totalQty = (string)$adjustment->totalQty;
        $totalCost = (string)$adjustment->totalCost;
        $stmt->bind_param(
            'sssissiii',
            $adjustment->adjustmentDate,
            $adjustment->type,
            $adjustment->remark,
            $adjustment->totalItems,
            $totalQty,
            $totalCost,
            $this->user,
            $adjustment->id,
            $this->company
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
            $this->user
        );
        $stmt->execute();
        $item->id = $this->db->insert_id;
        $stmt->close();
    }

    private function updateItem(StockAdjustmentItem $item, int $adjustmentId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE stock_adjustment_items 
             SET product_id = ?, grade = ?, quantity_before = ?, adjustment_qty = ?, 
                 quantity_after = ?, unit_cost = ?, total_cost = ?, reason = ?, 
                 deleted = 0, modified_by = ?
             WHERE id = ? AND adjustment_id = ?"
        );
        $qtyBefore = (string)$item->quantityBefore;
        $adjQty = (string)$item->adjustmentQty;
        $qtyAfter = (string)$item->quantityAfter;
        $unitCost = (string)$item->unitCost;
        $totalCost = (string)$item->totalCost;
        $stmt->bind_param(
            'isssssssiii',
            $item->productId,
            $item->grade,
            $qtyBefore,
            $adjQty,
            $qtyAfter,
            $unitCost,
            $totalCost,
            $item->reason,
            $this->user,
            $item->id,
            $adjustmentId
        );
        $stmt->execute();
        $stmt->close();
    }

    private function softDeleteItems(int $adjustmentId): void
    {
        $stmt = $this->db->prepare("UPDATE stock_adjustment_items SET deleted = 1, modified_by = ? WHERE adjustment_id = ? AND deleted = 0");
        $stmt->bind_param('ii', $this->user, $adjustmentId);
        $stmt->execute();
        $stmt->close();
    }

    private function softDeleteHeader(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE stock_adjustments SET deleted = 1, modified_by = ? WHERE id = ?");
        $stmt->bind_param('ii', $this->user, $id);
        $stmt->execute();
        $stmt->close();
    }

    private function reverseExistingItems(int $adjustmentId, string $type = 'Local', string $stockTable = 'raw'): void
    {
        $items = $this->getItemsByAdjustmentId($adjustmentId);
        foreach ($items as $item) {
            $this->reverseStockBalance($item, $type, $stockTable);
        }
    }

    /**
     * Update stock balance using delta (balance + adjustment_qty)
     * Returns before/after for movement record
     */
    private function updateStockBalanceDelta(StockAdjustmentItem $item, string $type, string $stockTable, string $stockLevel): array
    {
        $tableName = $this->balanceTable($stockTable);
        $isProductLevel = ($stockTable === 'raw' && $stockLevel === 'PRODUCT');
        $gradeToUse = $isProductLevel ? '0' : $item->grade;

        // Lock row for update
        $sql = "SELECT id, balance FROM {$tableName} 
                WHERE product_id = ? AND grade = ? AND type = ? AND company = ? AND deleted = 0 
                FOR UPDATE";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('issi', $item->productId, $gradeToUse, $type, $this->company);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $balanceBefore = $row ? (float)$row['balance'] : 0;
        $balanceAfter = $balanceBefore + $item->adjustmentQty;

        if ($row) {
            $updateStmt = $this->db->prepare(
                "UPDATE {$tableName} SET balance = ?, modified_by = ? WHERE id = ?"
            );
            $updateStmt->bind_param('dii', $balanceAfter, $this->user, $row['id']);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            $insertStmt = $this->db->prepare(
                "INSERT INTO {$tableName} (product_id, grade, type, company, balance, created_by) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $insertStmt->bind_param('issidi', $item->productId, $gradeToUse, $type, $this->company, $balanceAfter, $this->user);
            $insertStmt->execute();
            $insertStmt->close();
        }

        return ['before' => $balanceBefore, 'after' => $balanceAfter];
    }

    private function reverseStockBalance(StockAdjustmentItem $item, string $type = 'Local', string $stockTable = 'raw'): void
    {
        $tableName = $this->balanceTable($stockTable);
        
        $stmt = $this->db->prepare(
            "SELECT id, balance FROM {$tableName} 
             WHERE product_id = ? AND grade = ? AND type = ? AND company = ? AND deleted = 0"
        );
        $stmt->bind_param('issi', $item->productId, $item->grade, $type, $this->company);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $newBalance = (float)$row['balance'] - $item->adjustmentQty;
            $updateStmt = $this->db->prepare(
                "UPDATE {$tableName} SET balance = ?, modified_by = ? WHERE id = ?"
            );
            $updateStmt->bind_param('dii', $newBalance, $this->user, $row['id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }

    private function addStockMovement(StockAdjustmentItem $item, int $adjustmentId, string $type = 'Local', string $stockTable = 'raw'): void
    {
        require_once __DIR__ . '/../../../services/stockManagementService.php';
        
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
            $this->user,
            null,
            null,
            $type,
            $stockTable
        );
    }

    private function addReversalMovement(StockAdjustmentItem $item, int $adjustmentId, string $type = 'Local', string $stockTable = 'raw'): void
    {
        require_once __DIR__ . '/../../../services/stockManagementService.php';
        
        $tableName = $this->balanceTable($stockTable);
        
        $stmt = $this->db->prepare(
            "SELECT balance FROM {$tableName} 
             WHERE product_id = ? AND grade = ? AND type = ? AND company = ? AND deleted = 0"
        );
        $stmt->bind_param('issi', $item->productId, $item->grade, $type, $this->company);
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
            $this->user,
            null,
            null,
            $type,
            $stockTable
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
        require_once __DIR__ . '/../../../modules/wholesales/stockAdjustment/partial/pdfStockAdjustment.php';
        
        generateStockAdjustmentPdf($data, $companyDetail);
    }
}
