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
    $customers = $db->query("SELECT id, customer_name FROM customers WHERE deleted = 0 AND customer = '$company' ORDER BY customer_name ASC");
} else {
    $customers = $db->query("SELECT id, customer_name FROM customers WHERE deleted = 0 ORDER BY customer_name ASC");
}

$customerList = [];
while($row = mysqli_fetch_assoc($customers)) {
    $customerList[] = $row;
}

echo json_encode($customerList);
?>