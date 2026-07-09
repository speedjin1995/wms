<?php
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);

require_once '../../db_connect.php';
session_start();

// ─── Authentication ───────────────────────────────────────────────────────────
if (!isset($_SESSION['userID'])) {
    echo json_encode([
        "status"  => "failed",
        "message" => "Unauthorized"
    ]);
    exit;
}

// ─── Input ────────────────────────────────────────────────────────────────────
$customer_id = filter_input(INPUT_POST, 'binCustomerId', FILTER_SANITIZE_NUMBER_INT);
$bin_type_id = filter_input(INPUT_POST, 'binTypeId', FILTER_SANITIZE_NUMBER_INT);
$action = filter_input(INPUT_POST, 'binAction', FILTER_SANITIZE_STRING);
$qty = filter_input(INPUT_POST, 'binQty', FILTER_SANITIZE_NUMBER_INT);
$remark = filter_input(INPUT_POST, 'binRemark', FILTER_SANITIZE_STRING);
$user = $_SESSION['userID'];

// ─── Validation ───────────────────────────────────────────────────────────────

if (!$customer_id || !$bin_type_id || !$action || !$qty || $qty <= 0) {
    echo json_encode([
        "status"  => "failed",
        "message" => "Invalid input"
    ]);
    exit;
}

// ─── Fetch Current Pending Bins ───────────────────────────────────────────────
$stmt = $db->prepare("SELECT pending_bins FROM customers WHERE id = ?");
$stmt->bind_param('i', $customer_id);
$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

// pending_bins is stored as JSON e.g. {"1": 5, "2": 3}
// where the key is bin_type_id and the value is the count
$pendingMap = [];

if (!empty($row['pending_bins'])) {
    $decoded = json_decode($row['pending_bins'], true);

    if (is_array($decoded)) {
        $pendingMap = $decoded;
    } else {
        $pendingMap = [];
    }
}

// ─── Calculate New Total ──────────────────────────────────────────────────────
if (isset($pendingMap[$bin_type_id])) {
    $currentCount = (int) $pendingMap[$bin_type_id];
} else {
    $currentCount = 0;
}

if ($action === 'OUT') {
    // Customer takes bins out — pending count goes up
    $newCount = $currentCount + $qty;
} else {
    // Customer returns bins in — pending count goes down
    $newCount = $currentCount - $qty;
}

if ($newCount < 0) {
    echo json_encode([
        "status"  => "failed",
        "message" => "Pending bins cannot go below 0"
    ]);
    exit;
}

// ─── Update Pending Bins ──────────────────────────────────────────────────────
$pendingMap[$bin_type_id] = $newCount;
$pendingJson = json_encode($pendingMap);

$updateStmt = $db->prepare("UPDATE customers SET pending_bins = ? WHERE id = ?");
$updateStmt->bind_param('ss', $pendingJson, $customer_id);

if (!$updateStmt->execute()) {
    echo json_encode([
        "status"  => "failed",
        "message" => $updateStmt->error
    ]);
    exit;
}

$updateStmt->close();

// ─── Insert Log Entry ─────────────────────────────────────────────────────────
$logStmt = $db->prepare("
    INSERT INTO customer_bin_logs (customer_id, type, qty, remark, bin_type, created_by)
    VALUES (?, ?, ?, ?, ?, ?)
");
$logStmt->bind_param('ssssss', $customer_id, $action, $qty, $remark, $bin_type_id, $user);

if (!$logStmt->execute()) {
    echo json_encode([
        "status"  => "failed",
        "message" => $logStmt->error
    ]);
    exit;
}

$logStmt->close();
$db->close();

// ─── Response ─────────────────────────────────────────────────────────────────
echo json_encode([
    "status" => "success",
    "message" => "Updated successfully",
    "pending_bins" => $newCount
]);
?>
