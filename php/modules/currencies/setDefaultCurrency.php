<?php
require_once '../../db_connect.php';
session_start();

$id = $_POST['id'];
$company = $_SESSION['customer'];

// Get the customer of this currency
$stmt = $db->prepare("SELECT customer FROM currency WHERE id = ?");
$stmt->bind_param('s', $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(["status" => "failed", "message" => "Currency not found"]);
    exit();
}

$targetCompany = $row['customer'];

// Reset all currencies for this company
$reset = $db->prepare("UPDATE currency SET is_default = 0 WHERE customer = ? AND deleted = 0");
$reset->bind_param('s', $targetCompany);
$reset->execute();
$reset->close();

// Set selected as default
$set = $db->prepare("UPDATE currency SET is_default = 1 WHERE id = ?");
$set->bind_param('s', $id);
$set->execute();
$set->close();

echo json_encode(["status" => "success", "message" => "Default currency updated"]);
?>
