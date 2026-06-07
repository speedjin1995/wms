<?php

function processRawStock($db, $productId, $grade, $company, $newValue, $userId, $status, $isEdit = false, $beforeValue = 0) {
    try {
        $stmt = $db->prepare("SELECT id, balance FROM raw_stock_balance WHERE product_id = ? AND grade = ? AND company = ? AND deleted = '0'");
        $stmt->bind_param('sss', $productId, $grade, $company);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if ($isEdit) {
                if ($status == 'RECEIVING' || $status == 'INCOMING') {
                    $newQty = (floatval($row['balance']) - floatval($beforeValue)) + floatval($newValue);
                } else {
                    $newQty = (floatval($row['balance']) + floatval($beforeValue)) - floatval($newValue);
                }
            } else {
                if ($status == 'RECEIVING' || $status == 'INCOMING') {
                    $newQty = floatval($row['balance']) + floatval($newValue);
                } else {
                    $newQty = floatval($row['balance']) - floatval($newValue);
                }
            }

            $updateStmt = $db->prepare("UPDATE raw_stock_balance SET balance = ?, modified_by = ? WHERE id = ?");
            $updateStmt->bind_param('sss', $newQty, $userId, $row['id']);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            if ($status == 'RECEIVING' || $status == 'INCOMING') {
                $newValue = floatval($newValue);
            }else{
                $newValue = -floatval($newValue);
            }
            $insertStmt = $db->prepare("INSERT INTO raw_stock_balance (product_id, grade, company, balance, created_by) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->bind_param('sssss', $productId, $grade, $company, $newValue, $userId);
            $insertStmt->execute();
            $insertStmt->close();
        }

        $stmt->close();
        return ['status' => 'success'];
    } catch (Exception $e) {
        return ['status' => 'failed', 'message' => $e->getMessage()];
    }
}

?>
