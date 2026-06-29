<?php
require_once '../../db_connect.php';
session_start();

if (!isset($_SESSION['userID'])) {
    echo json_encode(["status" => "failed", "message" => "Unauthorized"]);
    exit;
}

$customer_id  = filter_input(INPUT_POST, 'customer_id', FILTER_SANITIZE_NUMBER_INT);
$bin_type_id  = filter_input(INPUT_POST, 'bin_type_id', FILTER_SANITIZE_NUMBER_INT);

if (!$customer_id || !$bin_type_id) {
    echo json_encode(["status" => "failed", "message" => "Missing parameters"]);
    exit;
}

$sel = $db->prepare("SELECT pending_bins FROM customers WHERE id = ?");
$sel->bind_param('i', $customer_id);
$sel->execute();
$row = $sel->get_result()->fetch_assoc();
$sel->close();

$pending = [];
if (!empty($row['pending_bins'])) {
    $decoded = json_decode($row['pending_bins'], true);
    $pending = is_array($decoded) ? $decoded : [];
}

$count = isset($pending[$bin_type_id]) ? (int)$pending[$bin_type_id] : 0;

echo json_encode(["status" => "success", "pending_bins" => $count]);
?>
