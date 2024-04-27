<?php
require_once 'db_connect.php';

$post = json_decode(file_get_contents('php://input'), true);

$staffId = $post['userId'];
$status = $post['status'];
$message = array();

if($status == 'Sales'){
    $stmt = $db->prepare("SELECT * from customers WHERE customer_name = ?");
    $stmt->bind_param('s', $staffId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($row = $result->fetch_assoc()){
        $message[] = array( 
            'customer_address'=>$row['customer_address'],
            'customer_address2'=>$row['customer_address2'],
            'customer_address3'=>$row['customer_address3'],
            'customer_address4'=>$row['customer_address4'],
            'customer_phone'=>$row['customer_phone'],
            'status' => $status
        );
    }
}
else{
    $stmt = $db->prepare("SELECT * from supplies WHERE supplier_name = ?");
    $stmt->bind_param('s', $staffId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($row = $result->fetch_assoc()){
        $message[] = array( 
            'supplier_address'=>$row['supplier_address'],
            'supplier_address2'=>$row['supplier_address2'],
            'supplier_address3'=>$row['supplier_address3'],
            'supplier_address4'=>$row['supplier_address4'],
            'supplier_phone'=>$row['supplier_phone'],
            'status' => $status
        );
    }
}

$stmt->close();
$db->close();

echo json_encode(
    array(
        "status"=> "success", 
        "message"=> $message
    )
);
?>