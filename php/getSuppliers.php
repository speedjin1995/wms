<?php
require_once 'db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
    echo json_encode([]);
    exit;
}

$company = $_SESSION['customer'];
$user = $_SESSION['userID'];
$role = $_SESSION['role'];

if ($role != 'SADMIN'){
    $suppliers = $db->query("SELECT id, supplier_name FROM supplies WHERE deleted = 0 AND customer = '$company' ORDER BY supplier_name ASC");
} else {
    $suppliers = $db->query("SELECT id, supplier_name FROM supplies WHERE deleted = 0 ORDER BY supplier_name ASC");
}

$supplierList = [];
while($row = mysqli_fetch_assoc($suppliers)) {
    $supplierList[] = $row;
}

echo json_encode($supplierList);
?>