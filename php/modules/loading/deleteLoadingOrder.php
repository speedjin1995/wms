<?php
require_once '../../db_connect.php';
require_once '../../services/stockManagementService.php';
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

$itemStmt = $db->prepare("SELECT * FROM loading_order_items WHERE loading_order_id = ? AND deleted = 0");
$itemStmt->bind_param('s', $id);
$itemStmt->execute();
$items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$itemStmt->close();

$affectedBatches = [];

foreach ($items as $item) {
    $revertStmt = $db->prepare("UPDATE packaging_batch_items SET status = 'pending' WHERE id = ?");
    $revertStmt->bind_param('s', $item['packaging_batch_item_id']);
    $revertStmt->execute();
    $revertStmt->close();

    if (in_array('stocks', $_SESSION['products'])) {
        processPackagedStock($db, $item['product_id'], $item['grade'], $item['packaging_size'], 1, $company, $userID, $id, $item['customer_id'], 'REVERSAL');
    }

    $batchStmt = $db->prepare("SELECT packaging_batch_id FROM packaging_batch_items WHERE id = ?");
    $batchStmt->bind_param('s', $item['packaging_batch_item_id']);
    $batchStmt->execute();
    $batchRow = $batchStmt->get_result()->fetch_assoc();
    $batchStmt->close();
    if ($batchRow) $affectedBatches[] = $batchRow['packaging_batch_id'];
}

$delStmt = $db->prepare("UPDATE loading_orders SET deleted = 1, delete_reason = ?, modified_by = ? WHERE id = ?");
$delStmt->bind_param('sss', $reason, $userID, $id);
$delStmt->execute();
$delStmt->close();

$delItemsStmt = $db->prepare("UPDATE loading_order_items SET deleted = 1 WHERE loading_order_id = ?");
$delItemsStmt->bind_param('s', $id);
$delItemsStmt->execute();
$delItemsStmt->close();

foreach (array_unique($affectedBatches) as $batchId) {
    syncBatchStatus($db, $batchId, $userID);
}

$db->close();
echo json_encode(['status' => 'success', 'message' => 'Deleted Successfully!!']);
