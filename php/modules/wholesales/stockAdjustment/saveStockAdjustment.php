<?php
require_once '../../../db_connect.php';
require_once '../../../../php/services/stockManagementService.php';
session_start();

$company    = $_SESSION['customer'];
$user       = $_SESSION['userID'];
$id         = $_POST['id']         ?? '';
$productId  = $_POST['product_id'] ?? '';
$grade      = $_POST['grade']      ?? '';
$newBalance = $_POST['balance']    ?? '';
$todayBalance = $_POST['today_balance'] ?? 0; // today's computed balance passed from frontend

if ($productId === '' || $newBalance === '') {
    echo json_encode(['status' => 'failed', 'message' => 'Missing required fields']);
    exit;
}

$finalBalance = floatval($newBalance);
$currentBalance = floatval($todayBalance);
$diff = $finalBalance - $currentBalance;

// Fetch or create raw_stock_balance row
if (!empty($id)) {
    $stmt = $db->prepare("SELECT id FROM raw_stock_balance WHERE id = ? AND company = ? AND deleted = '0'");
    $stmt->bind_param('ss', $id, $company);
    $stmt->execute();
    $balRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    $balRow = null;
}

if (!$balRow) {
    // Try find by product+grade
    $stmt = $db->prepare("SELECT id FROM raw_stock_balance WHERE product_id = ? AND grade = ? AND company = ? AND deleted = '0' LIMIT 1");
    $stmt->bind_param('sss', $productId, $grade, $company);
    $stmt->execute();
    $balRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$balRow) {
    $ins = $db->prepare("INSERT INTO raw_stock_balance (product_id, grade, company, balance, created_by) VALUES (?,?,?,?,?)");
    $ins->bind_param('sssss', $productId, $grade, $company, $currentBalance, $user);
    $ins->execute();
    $id = $db->insert_id;
    $ins->close();
} else {
    $id = $balRow['id'];
}

$movType = $diff > 0 ? 'ADD' : 'MINUS';
$qty = abs($diff);

addStockMovement($db, generateMovementNo($db, $company), $productId, $grade, $company, 'adjustment', 0, $movType, 'ADJUSTMENT', $qty, $currentBalance, $finalBalance, null, null, $user);

$upd = $db->prepare("UPDATE raw_stock_balance SET balance = ?, modified_by = ? WHERE id = ?");
$upd->bind_param('sss', $finalBalance, $user, $id);
$upd->execute();
$upd->close();

echo json_encode([
    'status' => 'success', 
    'message' => 'Stock adjusted successfully'
]);
