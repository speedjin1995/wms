<?php
require_once '../../db_connect.php';
require_once '../../services/batchStatusService.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

if (!isset($_POST['id'], $_POST['cancelReason'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Missing Attribute']);
    exit;
}

$id      = $_POST['id'];
$reason  = $_POST['cancelReason'];
$userID  = $_SESSION['userID'];
$company = $_SESSION['customer'];

// Fetch transfer header
$hdrStmt = $db->prepare("SELECT * FROM stock_transfers WHERE id = ? AND deleted = 0");
$hdrStmt->bind_param('s', $id);
$hdrStmt->execute();
$transfer = $hdrStmt->get_result()->fetch_assoc();
$hdrStmt->close();

if (!$transfer) {
    echo json_encode(['status' => 'failed', 'message' => 'Transfer not found']);
    exit;
}

// Fetch transfer items
$itemStmt = $db->prepare("SELECT * FROM stock_transfer_items WHERE stock_transfer_id = ? AND deleted = 0");
$itemStmt->bind_param('s', $id);
$itemStmt->execute();
$items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$itemStmt->close();

// Revert each item back to from_batch_id
foreach ($items as $item) {
    $revertStmt = $db->prepare("UPDATE packaging_batch_items SET packaging_batch_id = ?, modified_by = ? WHERE id = ?");
    $revertStmt->bind_param('sss', $item['from_batch_id'], $userID, $item['packaging_batch_item_id']);
    $revertStmt->execute();
    $revertStmt->close();

    $delItemStmt = $db->prepare("UPDATE stock_transfer_items SET deleted = 1 WHERE id = ?");
    $delItemStmt->bind_param('s', $item['id']);
    $delItemStmt->execute();
    $delItemStmt->close();
}

// Soft-delete the transfer
$delStmt = $db->prepare("UPDATE stock_transfers SET deleted = 1, delete_reason = ?, modified_by = ? WHERE id = ?");
$delStmt->bind_param('sss', $reason, $userID, $id);
$delStmt->execute();
$delStmt->close();

// Sync both batch statuses
syncBatchStatus($db, $transfer['from_batch_id'], $userID);
syncBatchStatus($db, $transfer['to_batch_id'], $userID);

$db->close();
echo json_encode(['status' => 'success', 'message' => 'Transfer reverted successfully!!']);
