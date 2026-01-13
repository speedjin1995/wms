<?php
require_once 'db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
    echo json_encode([]);
    exit;
}

$company = $_SESSION['customer'];
$user = $_SESSION['userID'];

if ($user != 2){
    $customers = $db->query("SELECT id, customer_name FROM customers WHERE deleted = 0 AND customer = '$company'");
} else {
    $customers = $db->query("SELECT id, customer_name FROM customers WHERE deleted = 0");
}

$customerList = [];
while($row = mysqli_fetch_assoc($customers)) {
    $customerList[] = $row;
}

echo json_encode($customerList);
?>