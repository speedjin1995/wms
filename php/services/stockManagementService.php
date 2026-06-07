<?php

/**
 * Generates a unique movement number in the format SM + YYYYMMDD + 4-digit counter.
 * e.g. SM202606080001
 * Same collision-check pattern as serial_no in wholesales.
 */
function generateMovementNo($db, $company) {
    $today = date('Ymd');
    $prefix = 'SM';
    $dateStart = date('Y-m-d') . ' 00:00:00';

    // Get today's movement count for this company to determine starting counter
    $stmt = $db->prepare("SELECT COUNT(*) FROM stock_movements WHERE company = ? AND created_date >= ?");
    $stmt->bind_param('ss', $company, $dateStart);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_row()[0] + 1;
    $stmt->close();

    // Loop until a unique movement_no is found
    do {
        $movementNo = $prefix . $today . str_pad($count, 4, '0', STR_PAD_LEFT);
        $chkStmt = $db->prepare("SELECT COUNT(*) FROM stock_movements WHERE movement_no = ?");
        $chkStmt->bind_param('s', $movementNo);
        $chkStmt->execute();
        $exists = (int)$chkStmt->get_result()->fetch_row()[0];
        $chkStmt->close();
        if ($exists === 0) break;
        $count++;
    } while (true);

    return $movementNo;
}

/**
 * Inserts a single row into stock_movements.
 *
 * movement_type : ADD | MINUS | REVERSAL
 * status        : RECEIVING | DISPATCH | INCOMING | OUTGOING | etc.
 * original_movement_id : set on REVERSAL rows — points to the movement being reversed
 * edit_ref      : set on both the REVERSAL and the new entry rows during an edit,
 *                 value is the movement_no of the original row being reversed,
 *                 so both rows in an edit pair can be grouped together
 */
function addStockMovement($db, $movementNo, $productId, $grade, $company, $module, $sourceId, $movementType, $status, $quantity, $balanceBefore, $balanceAfter, $customer, $supplier, $userId, $originalMovementId = null, $editRef = null) {
    $stmt = $db->prepare("INSERT INTO stock_movements (movement_no, product_id, grade, company, module, source_id, movement_type, status, quantity, balance_before, balance_after, customer, supplier, original_movement_id, edit_ref, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param('ssssssssssssssss', $movementNo, $productId, $grade, $company, $module, $sourceId, $movementType, $status, $quantity, $balanceBefore, $balanceAfter, $customer, $supplier, $originalMovementId, $editRef, $userId);
    $stmt->execute();
    $stmt->close();
}

/**
 * Called when a source record (e.g. wholesale) is deleted.
 * Finds the latest non-REVERSAL movement per product/grade for the given source,
 * then writes a REVERSAL row to undo its effect on raw_stock_balance.
 *
 * Only the latest movement per product/grade is reversed because that row
 * already reflects the net effect of all previous edits.
 */
function processDeleteStock($db, $sourceId, $module, $company, $userId) {
    try {
        // Fetch latest non-REVERSAL movement per product/grade for this source record
        $stmt = $db->prepare("SELECT s.id, s.movement_no, s.product_id, s.grade, s.movement_type, s.status, s.quantity, s.customer, s.supplier FROM stock_movements s INNER JOIN (SELECT product_id, grade, MAX(id) as max_id FROM stock_movements WHERE source_id = ? AND module = ? AND company = ? AND movement_type != 'REVERSAL' GROUP BY product_id, grade) latest ON s.id = latest.max_id");
        $stmt->bind_param('sss', $sourceId, $module, $company);
        $stmt->execute();
        $movements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($movements as $movement) {
            // Get current balance for this product/grade
            $balStmt = $db->prepare("SELECT id, balance FROM raw_stock_balance WHERE product_id = ? AND grade = ? AND company = ? AND deleted = '0'");
            $balStmt->bind_param('sss', $movement['product_id'], $movement['grade'], $company);
            $balStmt->execute();
            $balRow = $balStmt->get_result()->fetch_assoc();
            $balStmt->close();

            if (!$balRow) continue;

            $currentBalance = floatval($balRow['balance']);
            $qty = floatval($movement['quantity']);

            // Reverse direction: ADD becomes subtract, MINUS becomes add back
            $isAdd = ($movement['movement_type'] === 'ADD');
            $newBalance = $isAdd ? $currentBalance - $qty : $currentBalance + $qty;

            // Write REVERSAL row pointing back to the movement being undone
            $reversalNo = generateMovementNo($db, $company);
            addStockMovement($db, $reversalNo, $movement['product_id'], $movement['grade'], $company, $module, $sourceId, 'REVERSAL', $movement['status'], $qty, $currentBalance, $newBalance, $movement['customer'], $movement['supplier'], $userId, $movement['id'], $movement['movement_no']);

            // Update raw_stock_balance
            $updStmt = $db->prepare("UPDATE raw_stock_balance SET balance = ?, modified_by = ? WHERE id = ?");
            $updStmt->bind_param('sss', $newBalance, $userId, $balRow['id']);
            $updStmt->execute();
            $updStmt->close();
        }

        return ['status' => 'success'];
    } catch (Exception $e) {
        return ['status' => 'failed', 'message' => $e->getMessage()];
    }
}

/**
 * Core stock processing function — called on create and edit of any module record.
 *
 * On CREATE : writes a single ADD or MINUS movement and updates raw_stock_balance.
 * On EDIT   : writes a REVERSAL row to undo the previous movement, then a new ADD/MINUS
 *             row for the updated quantity. Both share edit_ref = original movement_no
 *             so they can be grouped as one edit event in reports.
 *             If qty is unchanged the caller should skip calling this function entirely.
 *
 * status determines direction:
 *   RECEIVING / INCOMING → ADD  (stock comes in)
 *   anything else        → MINUS (stock goes out)
 */
function processRawStock($db, $productId, $grade, $company, $newValue, $userId, $status, $isEdit = false, $beforeValue = 0, $sourceId = null, $module = 'wholesales', $customer = null, $supplier = null) {
    try {
        $isAdd = ($status == 'RECEIVING' || $status == 'INCOMING');

        // Fetch current balance row for this product/grade/company
        $stmt = $db->prepare("SELECT id, balance FROM raw_stock_balance WHERE product_id = ? AND grade = ? AND company = ? AND deleted = '0'");
        $stmt->bind_param('sss', $productId, $grade, $company);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($row = $result->fetch_assoc()) {
            $currentBalance = floatval($row['balance']);

            if ($isEdit) {
                // Step 1: find the latest non-REVERSAL movement for this source to reverse
                $origStmt = $db->prepare("SELECT id, movement_no, quantity FROM stock_movements WHERE source_id = ? AND module = ? AND product_id = ? AND grade = ? AND company = ? AND movement_type != 'REVERSAL' ORDER BY id DESC LIMIT 1");
                $origStmt->bind_param('sssss', $sourceId, $module, $productId, $grade, $company);
                $origStmt->execute();
                $origRow = $origStmt->get_result()->fetch_assoc();
                $origStmt->close();

                // Step 2: write REVERSAL row to undo the previous quantity effect
                $reversalNo = generateMovementNo($db, $company);
                $reversalQty = floatval($beforeValue);
                $balanceAfterReversal = $isAdd
                    ? $currentBalance - $reversalQty
                    : $currentBalance + $reversalQty;

                addStockMovement($db, $reversalNo, $productId, $grade, $company, $module, $sourceId, 'REVERSAL', $status, $reversalQty, $currentBalance, $balanceAfterReversal, $customer, $supplier, $userId, $origRow['id'] ?? null, $origRow['movement_no'] ?? null);

                // Step 3: write new ADD/MINUS row for the updated quantity
                $newMovementNo = generateMovementNo($db, $company);
                $newMovementType = $isAdd ? 'ADD' : 'MINUS';
                $newQty = floatval($newValue);
                $balanceAfterNew = $isAdd
                    ? $balanceAfterReversal + $newQty
                    : $balanceAfterReversal - $newQty;

                // edit_ref links this new row to the reversal as one edit pair
                addStockMovement($db, $newMovementNo, $productId, $grade, $company, $module, $sourceId, $newMovementType, $status, $newQty, $balanceAfterReversal, $balanceAfterNew, $customer, $supplier, $userId, null, $origRow['movement_no'] ?? null);

                $finalBalance = $balanceAfterNew;
            } else {
                // CREATE: single ADD or MINUS movement
                $movementNo = generateMovementNo($db, $company);
                $movementType = $isAdd ? 'ADD' : 'MINUS';
                $qty = floatval($newValue);
                $balanceBefore = $currentBalance;
                $finalBalance = $isAdd ? $balanceBefore + $qty : $balanceBefore - $qty;

                addStockMovement($db, $movementNo, $productId, $grade, $company, $module, $sourceId, $movementType, $status, $qty, $balanceBefore, $finalBalance, $customer, $supplier, $userId);
            }

            // Update raw_stock_balance with final computed balance
            $updateStmt = $db->prepare("UPDATE raw_stock_balance SET balance = ?, modified_by = ? WHERE id = ?");
            $updateStmt->bind_param('sss', $finalBalance, $userId, $row['id']);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            // No existing balance row — insert fresh with balance starting from 0
            $qty = floatval($newValue);
            $balanceBefore = 0;
            $finalBalance = $isAdd ? $qty : -$qty;

            $movementNo = generateMovementNo($db, $company);
            $movementType = $isAdd ? 'ADD' : 'MINUS';
            addStockMovement($db, $movementNo, $productId, $grade, $company, $module, $sourceId, $movementType, $status, $qty, $balanceBefore, $finalBalance, $customer, $supplier, $userId);

            $insertStmt = $db->prepare("INSERT INTO raw_stock_balance (product_id, grade, company, balance, created_by) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->bind_param('sssss', $productId, $grade, $company, $finalBalance, $userId);
            $insertStmt->execute();
            $insertStmt->close();
        }

        return ['status' => 'success'];
    } catch (Exception $e) {
        return ['status' => 'failed', 'message' => $e->getMessage()];
    }
}

?>
