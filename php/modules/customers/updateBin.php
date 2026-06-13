<?php
require_once '../../db_connect.php';

session_start();

if (!isset($_SESSION['userID'])) {
    echo json_encode(array("status" => "failed", "message" => "Unauthorized"));
    exit;
}

$customer_id = filter_input(INPUT_POST, 'binCustomerId', FILTER_SANITIZE_NUMBER_INT);
$type        = filter_input(INPUT_POST, 'binType', FILTER_SANITIZE_STRING);
$qty         = filter_input(INPUT_POST, 'binQty', FILTER_SANITIZE_NUMBER_INT);
$remark      = filter_input(INPUT_POST, 'binRemark', FILTER_SANITIZE_STRING);
$user        = $_SESSION['userID'];

if (!$customer_id || !$type || !$qty || $qty <= 0) {
    echo json_encode(array("status" => "failed", "message" => "Invalid input"));
    exit;
}

// Get current pending_bins
$sel = $db->prepare("SELECT pending_bins FROM customers WHERE id = ?");
$sel->bind_param('i', $customer_id);
$sel->execute();
$result = $sel->get_result()->fetch_assoc();
$sel->close();

$current = (int)$result['pending_bins'];
$new_total = ($type === 'OUT') ? $current + $qty : $current - $qty;

if ($new_total < 0) {
    echo json_encode(array("status" => "failed", "message" => "Pending bins cannot go below 0"));
    exit;
}

// Update pending_bins
$upd = $db->prepare("UPDATE customers SET pending_bins = ? WHERE id = ?");
$upd->bind_param('ii', $new_total, $customer_id);

if (!$upd->execute()) {
    echo json_encode(array("status" => "failed", "message" => $upd->error));
    exit;
}
$upd->close();

// Insert log
$log = $db->prepare("INSERT INTO customer_bin_logs (customer_id, type, qty, remark, created_by) VALUES (?, ?, ?, ?, ?)");
$log->bind_param('isisi', $customer_id, $type, $qty, $remark, $user);

if (!$log->execute()) {
    echo json_encode(array("status" => "failed", "message" => $log->error));
    exit;
}
$log->close();
$db->close();

echo json_encode(array("status" => "success", "message" => "Updated successfully", "pending_bins" => $new_total));
?>
