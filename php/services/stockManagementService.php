<?php

// =============================================================================
// SHARED HELPERS
// =============================================================================

/**
 * Generates a unique movement number: SM + YYYYMMDD + 4-digit counter.
 * e.g. SM202606080001
 */
function generateMovementNo($db, $company) {
    $today     = date('Ymd');
    $dateStart = date('Y-m-d') . ' 00:00:00';

    $stmt = $db->prepare("SELECT COUNT(*) FROM stock_movements WHERE company = ? AND created_date >= ?");
    $stmt->bind_param('ss', $company, $dateStart);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_row()[0] + 1;
    $stmt->close();

    do {
        $movementNo = 'SM' . $today . str_pad($count, 4, '0', STR_PAD_LEFT);
        $chk = $db->prepare("SELECT COUNT(*) FROM stock_movements WHERE movement_no = ?");
        $chk->bind_param('s', $movementNo);
        $chk->execute();
        $exists = (int)$chk->get_result()->fetch_row()[0];
        $chk->close();
        if ($exists === 0) break;
        $count++;
    } while (true);

    return $movementNo;
}

/**
 * Inserts a single row into stock_movements.
 *
 * movement_type : ADD | MINUS | REVERSAL
 * status        : RECEIVING | DISPATCH | INCOMING | OUTGOING | PACKAGING | etc.
 * original_movement_id : set on REVERSAL rows — points to the movement being reversed
 * edit_ref      : shared by the REVERSAL + new entry pair on edit, equals the
 *                 movement_no of the original row, used to group the pair in reports
 */
function addStockMovement($db, $movementNo, $productId, $grade, $company, $module, $sourceId, $movementType, $status, $quantity, $balanceBefore, $balanceAfter, $customer, $supplier, $userId, $originalMovementId = null, $editRef = null) {
    $stmt = $db->prepare("INSERT INTO stock_movements (movement_no, product_id, grade, company, module, source_id, movement_type, status, quantity, balance_before, balance_after, customer, supplier, original_movement_id, edit_ref, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param('ssssssssssssssss', $movementNo, $productId, $grade, $company, $module, $sourceId, $movementType, $status, $quantity, $balanceBefore, $balanceAfter, $customer, $supplier, $originalMovementId, $editRef, $userId);
    $stmt->execute();
    $stmt->close();
}


// =============================================================================
// WHOLESALES
// Manages raw_stock_balance — weight-based stock (kg) before packaging.
// Used by: wholesales, packaging batches.
// =============================================================================

/**
 * CREATE / EDIT — writes ADD or MINUS movement and updates raw_stock_balance.
 *
 * On CREATE : single ADD or MINUS row.
 * On EDIT   : REVERSAL of previous qty + new ADD/MINUS. Both rows share edit_ref.
 *
 * Direction:
 *   RECEIVING / INCOMING → ADD
 *   everything else      → MINUS  (including PACKAGING)
 */
function processRawStock($db, $productId, $grade, $company, $newValue, $userId, $status, $isEdit = false, $beforeValue = 0, $sourceId = null, $module = 'wholesales', $customer = null, $supplier = null) {
    try {
        $isAdd = ($status === 'RECEIVING' || $status === 'INCOMING');

        $stmt = $db->prepare("SELECT id, balance FROM raw_stock_balance WHERE product_id = ? AND grade = ? AND company = ? AND deleted = '0'");
        $stmt->bind_param('sss', $productId, $grade, $company);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $currentBalance = floatval($row['balance']);

            if ($isEdit) {
                $origStmt = $db->prepare("SELECT id, movement_no FROM stock_movements WHERE source_id = ? AND module = ? AND product_id = ? AND grade = ? AND company = ? AND movement_type != 'REVERSAL' ORDER BY id DESC LIMIT 1");
                $origStmt->bind_param('sssss', $sourceId, $module, $productId, $grade, $company);
                $origStmt->execute();
                $origRow = $origStmt->get_result()->fetch_assoc();
                $origStmt->close();

                $reversalQty      = floatval($beforeValue);
                $balAfterReversal = $isAdd ? $currentBalance - $reversalQty : $currentBalance + $reversalQty;
                $newQty           = floatval($newValue);
                $finalBalance     = $isAdd ? $balAfterReversal + $newQty    : $balAfterReversal - $newQty;

                addStockMovement($db, generateMovementNo($db, $company), $productId, $grade, $company, $module, $sourceId, 'REVERSAL', $status, $reversalQty, $currentBalance, $balAfterReversal, $customer, $supplier, $userId, $origRow['id'] ?? null, $origRow['movement_no'] ?? null);
                addStockMovement($db, generateMovementNo($db, $company), $productId, $grade, $company, $module, $sourceId, $isAdd ? 'ADD' : 'MINUS', $status, $newQty, $balAfterReversal, $finalBalance, $customer, $supplier, $userId, null, $origRow['movement_no'] ?? null);
            } else {
                $qty          = floatval($newValue);
                $finalBalance = $isAdd ? $currentBalance + $qty : $currentBalance - $qty;

                addStockMovement($db, generateMovementNo($db, $company), $productId, $grade, $company, $module, $sourceId, $isAdd ? 'ADD' : 'MINUS', $status, $qty, $currentBalance, $finalBalance, $customer, $supplier, $userId);
            }

            $upd = $db->prepare("UPDATE raw_stock_balance SET balance = ?, modified_by = ? WHERE id = ?");
            $upd->bind_param('sss', $finalBalance, $userId, $row['id']);
            $upd->execute();
            $upd->close();
        } else {
            $qty          = floatval($newValue);
            $finalBalance = $isAdd ? $qty : -$qty;

            addStockMovement($db, generateMovementNo($db, $company), $productId, $grade, $company, $module, $sourceId, $isAdd ? 'ADD' : 'MINUS', $status, $qty, 0, $finalBalance, $customer, $supplier, $userId);

            $ins = $db->prepare("INSERT INTO raw_stock_balance (product_id, grade, company, balance, created_by) VALUES (?,?,?,?,?)");
            $ins->bind_param('sssss', $productId, $grade, $company, $finalBalance, $userId);
            $ins->execute();
            $ins->close();
        }

        return ['status' => 'success'];
    } catch (Exception $e) {
        return ['status' => 'failed', 'message' => $e->getMessage()];
    }
}

/**
 * DELETE — finds the latest non-REVERSAL movement per product/grade for the
 * given source and writes a REVERSAL row to undo its effect on raw_stock_balance.
 */
function processDeleteRawStock($db, $sourceId, $module, $company, $userId) {
    try {
        $stmt = $db->prepare("SELECT s.id, s.movement_no, s.product_id, s.grade, s.movement_type, s.status, s.quantity, s.customer, s.supplier FROM stock_movements s INNER JOIN (SELECT product_id, grade, MAX(id) as max_id FROM stock_movements WHERE source_id = ? AND module = ? AND company = ? AND movement_type != 'REVERSAL' GROUP BY product_id, grade) latest ON s.id = latest.max_id");
        $stmt->bind_param('sss', $sourceId, $module, $company);
        $stmt->execute();
        $movements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($movements as $m) {
            $balStmt = $db->prepare("SELECT id, balance FROM raw_stock_balance WHERE product_id = ? AND grade = ? AND company = ? AND deleted = '0'");
            $balStmt->bind_param('sss', $m['product_id'], $m['grade'], $company);
            $balStmt->execute();
            $balRow = $balStmt->get_result()->fetch_assoc();
            $balStmt->close();

            if (!$balRow) continue;

            $currentBalance = floatval($balRow['balance']);
            $qty            = floatval($m['quantity']);
            $newBalance     = ($m['movement_type'] === 'ADD') ? $currentBalance - $qty : $currentBalance + $qty;

            addStockMovement($db, generateMovementNo($db, $company), $m['product_id'], $m['grade'], $company, $module, $sourceId, 'REVERSAL', $m['status'], $qty, $currentBalance, $newBalance, $m['customer'], $m['supplier'], $userId, $m['id'], $m['movement_no']);

            $upd = $db->prepare("UPDATE raw_stock_balance SET balance = ?, modified_by = ? WHERE id = ?");
            $upd->bind_param('sss', $newBalance, $userId, $balRow['id']);
            $upd->execute();
            $upd->close();
        }

        return ['status' => 'success'];
    } catch (Exception $e) {
        return ['status' => 'failed', 'message' => $e->getMessage()];
    }
}


// =============================================================================
// GRADING
// Manages grading_stock_balance — weight-based stock per product/grade after grading.
// Used by: grading module.
// =============================================================================

/**
 * CREATE / EDIT — writes ADD movement and updates grading_stock_balance.
 * Grading always ADDs stock (output of grading process).
 * On EDIT: REVERSAL of previous qty + new ADD. Both rows share edit_ref.
 */
function processGradingStock($db, $productId, $grade, $company, $newValue, $userId, $isEdit = false, $beforeValue = 0, $sourceId = null, $isAdd = true, $module = 'grading', $status = 'GRADING') {
    try {
        $stmt = $db->prepare("SELECT id, balance FROM grading_stock_balance WHERE product_id = ? AND grade = ? AND company = ? AND deleted = '0'");
        $stmt->bind_param('sss', $productId, $grade, $company);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $currentBalance = floatval($row['balance']);

            if ($isEdit) {
                $origStmt = $db->prepare("SELECT id, movement_no FROM stock_movements WHERE source_id = ? AND module = ? AND product_id = ? AND grade = ? AND company = ? AND movement_type != 'REVERSAL' ORDER BY id DESC LIMIT 1");
                $origStmt->bind_param('sssss', $sourceId, $module, $productId, $grade, $company);
                $origStmt->execute();
                $origRow = $origStmt->get_result()->fetch_assoc();
                $origStmt->close();

                $reversalQty      = floatval($beforeValue);
                $balAfterReversal = $isAdd ? $currentBalance - $reversalQty : $currentBalance + $reversalQty;
                $newQty           = floatval($newValue);
                $finalBalance     = $isAdd ? $balAfterReversal + $newQty : $balAfterReversal - $newQty;

                addStockMovement($db, generateMovementNo($db, $company), $productId, $grade, $company, $module, $sourceId, 'REVERSAL', $status, $reversalQty, $currentBalance, $balAfterReversal, null, null, $userId, $origRow['id'] ?? null, $origRow['movement_no'] ?? null);
                addStockMovement($db, generateMovementNo($db, $company), $productId, $grade, $company, $module, $sourceId, $isAdd ? 'ADD' : 'MINUS', $status, $newQty, $balAfterReversal, $finalBalance, null, null, $userId, null, $origRow['movement_no'] ?? null);
            } else {
                $qty          = floatval($newValue);
                $finalBalance = $isAdd ? $currentBalance + $qty : $currentBalance - $qty;

                addStockMovement($db, generateMovementNo($db, $company), $productId, $grade, $company, $module, $sourceId, $isAdd ? 'ADD' : 'MINUS', $status, $qty, $currentBalance, $finalBalance, null, null, $userId);
            }

            $upd = $db->prepare("UPDATE grading_stock_balance SET balance = ?, modified_by = ? WHERE id = ?");
            $upd->bind_param('sss', $finalBalance, $userId, $row['id']);
            $upd->execute();
            $upd->close();
        } else {
            $qty          = floatval($newValue);
            $finalBalance = $isAdd ? $qty : -$qty;

            addStockMovement($db, generateMovementNo($db, $company), $productId, $grade, $company, $module, $sourceId, $isAdd ? 'ADD' : 'MINUS', $status, $qty, 0, $finalBalance, null, null, $userId);

            $ins = $db->prepare("INSERT INTO grading_stock_balance (product_id, grade, company, balance, created_by) VALUES (?,?,?,?,?)");
            $ins->bind_param('sssss', $productId, $grade, $company, $finalBalance, $userId);
            $ins->execute();
            $ins->close();
        }

        return ['status' => 'success'];
    } catch (Exception $e) {
        return ['status' => 'failed', 'message' => $e->getMessage()];
    }
}

/**
 * DELETE — reverses all grading stock movements for the given grading record.
 */
function processDeleteGradingStock($db, $sourceId, $company, $userId, $module = 'grading') {
    try {
        $stmt = $db->prepare("SELECT s.id, s.movement_no, s.product_id, s.grade, s.movement_type, s.quantity FROM stock_movements s INNER JOIN (SELECT product_id, grade, MAX(id) as max_id FROM stock_movements WHERE source_id = ? AND module = ? AND company = ? AND movement_type != 'REVERSAL' GROUP BY product_id, grade) latest ON s.id = latest.max_id");
        $stmt->bind_param('sss', $sourceId, $module, $company);
        $stmt->execute();
        $movements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($movements as $m) {
            $balStmt = $db->prepare("SELECT id, balance FROM grading_stock_balance WHERE product_id = ? AND grade = ? AND company = ? AND deleted = '0'");
            $balStmt->bind_param('sss', $m['product_id'], $m['grade'], $company);
            $balStmt->execute();
            $balRow = $balStmt->get_result()->fetch_assoc();
            $balStmt->close();

            if (!$balRow) continue;

            $currentBalance = floatval($balRow['balance']);
            $qty            = floatval($m['quantity']);
            $newBalance     = ($m['movement_type'] === 'ADD') ? $currentBalance - $qty : $currentBalance + $qty;

            addStockMovement($db, generateMovementNo($db, $company), $m['product_id'], $m['grade'], $company, 'grading', $sourceId, 'REVERSAL', 'GRADING', $qty, $currentBalance, $newBalance, null, null, $userId, $m['id'], $m['movement_no']);

            $upd = $db->prepare("UPDATE grading_stock_balance SET balance = ?, modified_by = ? WHERE id = ?");
            $upd->bind_param('sss', $newBalance, $userId, $balRow['id']);
            $upd->execute();
            $upd->close();
        }

        return ['status' => 'success'];
    } catch (Exception $e) {
        return ['status' => 'failed', 'message' => $e->getMessage()];
    }
}


// =============================================================================
// PACKAGING BATCH
// Manages stock_balances — box-count stock per product/grade/packaging_size.
// Used by: packaging batches (produce boxes), loading orders (dispatch boxes).
// =============================================================================

/**
 * Single entry point for packaging batch stock processing.
 *
 * $action    : 'CREATE' | 'EDIT' | 'DELETE'
 * $newItems  : $_POST['weightDetails'] rows  (keys: product, grade, packaging_size, weight)
 * $prevItems : DB rows from packaging_batch_items (keys: product_id, grade, packaging_size, weight)
 *
 * CREATE : MINUS grading_stock_balance by total weight per product/grade,
 *          ADD box counts to stock_balances per product/grade/packaging_size.
 * EDIT   : reverse previous → apply new.
 * DELETE : REVERSAL on raw_stock_balance, decrement stock_balances.
 */
function processPackagingBatch($db, $batchId, $company, $userId, $action, $newItems = [], $prevItems = []) {
    try {
        if (($action === 'EDIT' || $action === 'DELETE') && !empty($prevItems)) {
            processDeleteGradingStock($db, $batchId, $company, $userId, 'packaging');
            _decrementStockBalances($db, $company, $userId, $prevItems);
        }

        if ($action === 'CREATE' || $action === 'EDIT') {
            $grouped = [];
            foreach ($newItems as $item) {
                $key = $item['product'] . '_' . $item['grade'];
                $grouped[$key]['product'] = $item['product'];
                $grouped[$key]['grade']   = $item['grade'];
                $grouped[$key]['net']     = ($grouped[$key]['net'] ?? 0) + floatval($item['weight']);
            }
            foreach ($grouped as $g) {
                processGradingStock($db, $g['product'], $g['grade'], $company, $g['net'], $userId, false, 0, $batchId, false, 'packaging', 'PACKAGING');
            }
            _incrementStockBalances($db, $company, $userId, $newItems);
        }

        return ['status' => 'success'];
    } catch (Exception $e) {
        return ['status' => 'failed', 'message' => $e->getMessage()];
    }
}

/**
 * Increment stock_balances box_quantity.
 * Each item = 1 box. Groups by product + grade + packaging_size.
 * $items keys: product, grade, packaging_size
 */
function _incrementStockBalances($db, $company, $userId, $items) {
    foreach (_groupByPackaging($items, 'new') as $g) {
        $row = _getStockBalanceRow($db, $g['product_id'], $g['grade'], $g['packaging_size'], $company);
        if ($row) {
            $newQty = intval($row['box_quantity']) + $g['box_count'];
            $upd = $db->prepare("UPDATE stock_balances SET box_quantity = ?, modified_by = ? WHERE id = ?");
            $upd->bind_param('sss', $newQty, $userId, $row['id']);
            $upd->execute();
            $upd->close();
        } else {
            $ins = $db->prepare("INSERT INTO stock_balances (product_id, grade, packaging_size, box_quantity, company, created_by) VALUES (?,?,?,?,?,?)");
            $ins->bind_param('ssssss', $g['product_id'], $g['grade'], $g['packaging_size'], $g['box_count'], $company, $userId);
            $ins->execute();
            $ins->close();
        }
    }
}

/**
 * Decrement stock_balances box_quantity (floor 0).
 * $items keys: product_id, grade, packaging_size
 */
function _decrementStockBalances($db, $company, $userId, $items) {
    foreach (_groupByPackaging($items, 'prev') as $g) {
        $row = _getStockBalanceRow($db, $g['product_id'], $g['grade'], $g['packaging_size'], $company);
        if ($row) {
            $newQty = max(0, intval($row['box_quantity']) - $g['box_count']);
            $upd = $db->prepare("UPDATE stock_balances SET box_quantity = ?, modified_by = ? WHERE id = ?");
            $upd->bind_param('sss', $newQty, $userId, $row['id']);
            $upd->execute();
            $upd->close();
        }
    }
}

/**
 * Groups items by product + grade + packaging_size and counts boxes.
 * $source 'new'  → item keys are product, grade, packaging_size  (from POST)
 * $source 'prev' → item keys are product_id, grade, packaging_size (from DB)
 */
function _groupByPackaging($items, $source) {
    $grouped = [];
    foreach ($items as $item) {
        $productId = ($source === 'new') ? $item['product'] : $item['product_id'];
        $key = $productId . '_' . $item['grade'] . '_' . $item['packaging_size'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = ['product_id' => $productId, 'grade' => $item['grade'], 'packaging_size' => $item['packaging_size'], 'box_count' => 0];
        }
        $grouped[$key]['box_count']++;
    }
    return $grouped;
}

/** Fetches a single stock_balances row by product + grade + packaging_size + company. */
function _getStockBalanceRow($db, $productId, $grade, $packagingSize, $company) {
    $stmt = $db->prepare("SELECT id, box_quantity FROM stock_balances WHERE product_id = ? AND grade = ? AND packaging_size = ? AND company = ? AND deleted = 0");
    $stmt->bind_param('ssss', $productId, $grade, $packagingSize, $company);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}


// =============================================================================
// STOCK TRANSFER
// =============================================================================


// =============================================================================
// LOADING ORDER
// Manages stock_balances — dispatches packaged boxes to customers.
// =============================================================================

/**
 * DISPATCH / REVERSAL for loading orders.
 * Writes a stock_movements row and adjusts stock_balances box_quantity.
 *
 * $action : 'DISPATCH' | 'REVERSAL'
 */
function processPackagedStock($db, $productId, $grade, $packagingSize, $boxQty, $company, $userId, $loadingOrderId, $customerId, $action = 'DISPATCH') {
    try {
        $row        = _getStockBalanceRow($db, $productId, $grade, $packagingSize, $company);
        $currentQty = $row ? intval($row['box_quantity']) : 0;
        $newQty     = ($action === 'DISPATCH') ? $currentQty - intval($boxQty) : $currentQty + intval($boxQty);
        $movType    = ($action === 'DISPATCH') ? 'MINUS' : 'REVERSAL';

        addStockMovement($db, generateMovementNo($db, $company), $productId, $grade, $company, 'loading', $loadingOrderId, $movType, 'DISPATCH', $boxQty, $currentQty, $newQty, $customerId, null, $userId);

        if ($row) {
            $upd = $db->prepare("UPDATE stock_balances SET box_quantity = ?, modified_by = ? WHERE id = ?");
            $upd->bind_param('sss', $newQty, $userId, $row['id']);
            $upd->execute();
            $upd->close();
        } else {
            $ins = $db->prepare("INSERT INTO stock_balances (product_id, grade, packaging_size, box_quantity, company, created_by) VALUES (?,?,?,?,?,?)");
            $ins->bind_param('ssssss', $productId, $grade, $packagingSize, $newQty, $company, $userId);
            $ins->execute();
            $ins->close();
        }

        return ['status' => 'success'];
    } catch (Exception $e) {
        return ['status' => 'failed', 'message' => $e->getMessage()];
    }
}
