<?php
require_once '../../db_connect.php';
require_once '../../services/batchStatusService.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

if (!isset($_POST['fromBatchId'], $_POST['toBatchId'], $_POST['items'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Please fill in all required fields']);
    exit;
}

$userID      = $_SESSION['userID'];
$company     = $_SESSION['customer'];
$fromBatchId = $_POST['fromBatchId'];
$toBatchId   = $_POST['toBatchId'];
$remarks     = isset($_POST['remarks']) && $_POST['remarks'] != '' ? $_POST['remarks'] : null;
$items       = $_POST['items']; // array of packaging_batch_item_ids being moved from -> to

if (empty($items)) {
    echo json_encode(['status' => 'failed', 'message' => 'No items to transfer']);
    exit;
}

// Generate transfer_no: ST + YYYYMMDD + 4-digit counter
function generateTransferNo($db, $company) {
    $today     = date('Ymd');
    $dateStart = date('Y-m-d') . ' 00:00:00';
    $stmt = $db->prepare("SELECT COUNT(*) FROM stock_transfers WHERE company = ? AND created_date >= ?");
    $stmt->bind_param('ss', $company, $dateStart);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_row()[0] + 1;
    $stmt->close();
    do {
        $no  = 'ST' . $today . str_pad($count, 4, '0', STR_PAD_LEFT);
        $chk = $db->prepare("SELECT COUNT(*) FROM stock_transfers WHERE transfer_no = ?");
        $chk->bind_param('s', $no);
        $chk->execute();
        $exists = (int)$chk->get_result()->fetch_row()[0];
        $chk->close();
        if ($exists === 0) break;
        $count++;
    } while (true);
    return $no;
}

$transferNo = generateTransferNo($db, $company);

if ($insert_stmt = $db->prepare("INSERT INTO stock_transfers (transfer_no, from_batch_id, to_batch_id, remarks, company, created_by) VALUES (?,?,?,?,?,?)")) {
    $insert_stmt->bind_param('ssssss', $transferNo, $fromBatchId, $toBatchId, $remarks, $company, $userID);
    if (!$insert_stmt->execute()) {
        echo json_encode(['status' => 'failed', 'message' => $insert_stmt->error]);
        exit;
    }
    $transferId = $insert_stmt->insert_id;
    $insert_stmt->close();

    foreach ($items as $item) {
        $pbiId       = $item['packaging_batch_item_id'];
        $itemFromId  = $item['from_batch_id'];
        $itemToId    = $item['to_batch_id'];

        // Insert transfer item record
        if ($insItem = $db->prepare("INSERT INTO stock_transfer_items (stock_transfer_id, packaging_batch_item_id, from_batch_id, to_batch_id) VALUES (?,?,?,?)")) {
            $insItem->bind_param('ssss', $transferId, $pbiId, $itemFromId, $itemToId);
            $insItem->execute();
            $insItem->close();
        }

        // Move the item to target batch
        $updItem = $db->prepare("UPDATE packaging_batch_items SET packaging_batch_id = ?, modified_by = ? WHERE id = ?");
        $updItem->bind_param('sss', $itemToId, $userID, $pbiId);
        $updItem->execute();
        $updItem->close();
    }

    // Sync both batch statuses
    syncBatchStatus($db, $fromBatchId, $userID);
    syncBatchStatus($db, $toBatchId, $userID);

    $db->close();
    echo json_encode(['status' => 'success', 'message' => 'Transfer saved successfully!!']);
} else {
    echo json_encode(['status' => 'failed', 'message' => 'Failed to prepare statement']);
}
