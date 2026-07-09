<?php
require_once '../../db_connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

if (!isset($_POST['id'], $_POST['cancelReason'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Missing Attribute']);
    exit;
}

$id     = $_POST['id'];
$reason = $_POST['cancelReason'];
$userID = $_SESSION['userID'];

// Unlink all wholesales tied to this PV
$unlinkStmt = $db->prepare("UPDATE wholesales SET pv_id = NULL, unit_price = 0 WHERE pv_id = ?");
$unlinkStmt->bind_param('s', $id);
$unlinkStmt->execute();
$unlinkStmt->close();

// Soft-delete the PV
$delStmt = $db->prepare("UPDATE payment_vouchers SET deleted = 1, delete_reason = ?, modified_by = ? WHERE id = ?");
$delStmt->bind_param('sss', $reason, $userID, $id);
$delStmt->execute();
$delStmt->close();

$db->close();
echo json_encode(['status' => 'success', 'message' => 'Deleted Successfully!!']);
