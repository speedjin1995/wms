<?php
require_once 'db_connect.php';

session_start();

$post = json_decode(file_get_contents('php://input'), true);
$now = date("Y-m-d H:i:s");
$userId = $post['uid'];

$stmt = $db->prepare("SELECT pricing.*, users.name from pricing, users WHERE pricing.created_by = users.id AND pricing.deleted = '0' AND pricing.created_by =? ORDER BY pricing.created_datetime DESC");
$stmt->bind_param('s', $userId);
$stmt->execute();
$result = $stmt->get_result();
$message = array();
$poList = array();

while($row = $result->fetch_assoc()){
    $customerName = '';
    /*$farmId=$row['customer'];
    
    if ($update_stmt = $db->prepare("SELECT * FROM customers WHERE id=?")) {
        $update_stmt->bind_param('s', $farmId);
        
        if ($update_stmt->execute()) {
            $result3 = $update_stmt->get_result();
            
            if ($row3 = $result3->fetch_assoc()) {
                $customerName=$row3['customer_name'];
            }
        }
    }*/
    
    $message[] = array(
        'id'=> $row['id'],
        'receipt_no'=> $row['receipt_no'],
        'status'=> $row['status'],
        'customer'=> $row['customer'],
        'customer_name'=> $customerName,
        'supplier'=> $row['supplier'],
        'supplier_name'=> $customerName,
        'items' => json_decode($row['items'], true),
        'sub_price'=> $row['sub_price'],
        'sst'=> $row['sst'],
        'total_price'=> $row['total_price'],
        'created_datetime'=> $row['created_datetime'],
        'weighted_by'=> $row['created_by'],
        'staff_name'=> $row['name'],
        'indicator'=> $row['indicator']
    );
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
