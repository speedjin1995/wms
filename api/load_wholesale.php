<?php
require_once 'db_connect.php';

session_start();

$post = json_decode(file_get_contents('php://input'), true);
$now = date("Y-m-d H:i:s");
$userId = $post['uid'];
$company = $post['userId'];

$stmt = $db->prepare("SELECT wholesales.*, users.name from wholesales, users WHERE wholesales.created_by = users.id AND wholesales.deleted = '0' 
AND wholesales.company =? ORDER BY wholesales.created_datetime DESC");
$stmt->bind_param('s', $company);
$stmt->execute();
$result = $stmt->get_result();
$message = array();
$poList = array();

while($row = $result->fetch_assoc()){
    $customerName = '';
    $supplierName = '';
    
    if($row['status'] == 'DISPATCH'){
        $farmId=$row['customer'];
        
        if($farmId != 'OTHERS' && $farmId != null){
            if ($update_stmt = $db->prepare("SELECT * FROM customers WHERE id=?")) {
                $update_stmt->bind_param('s', $farmId);
                
                if ($update_stmt->execute()) {
                    $result3 = $update_stmt->get_result();
                    
                    if ($row3 = $result3->fetch_assoc()) {
                        $customerName=$row3['customer_name'];
                    }
                }
            }
        }
        else{
            $customerName=$row['other_customer'];
        }
    }
    else{
        $farmId=$row['supplier'];
        
        if($farmId != 'OTHERS' && $farmId != null){
            if ($update_stmt = $db->prepare("SELECT * FROM supplies WHERE id=?")) {
                $update_stmt->bind_param('s', $farmId);
                
                if ($update_stmt->execute()) {
                    $result3 = $update_stmt->get_result();
                    
                    if ($row3 = $result3->fetch_assoc()) {
                        $supplierName=$row3['supplier_name'];
                    }
                }
            }
        }
        else{
            $supplierName=$row['other_supplier'];
        }
    }
    
    $message[] = array(
        'id'=> $row['id'],
        'serial_no'=> $row['serial_no'],
        'po_no'=> $row['po_no'],
        'security_bills'=> $row['security_bills'],
        'status'=> $row['status'],
        'customer'=> $row['customer'],
        'customer_name'=> $customerName,
        'supplier'=> $row['supplier'],
        'supplier_name'=> $supplierName,
        'vehicle_no'=> $row['vehicle_no'],
        'driver'=> $row['driver'],
        'driver_ic'=> $row['driver_ic'],
        'remark'=> $row['remark'],
        'created_datetime'=> $row['created_datetime'],
        'end_time'=> $row['end_time'],
        'checked_by'=> $row['checked_by'],
        'weighted_by'=> $row['weighted_by'],
        'staffName'=> $row['name'],
        'indicator'=> $row['indicator'],
        'total_item'=> $row['total_item'],
        'total_weight'=> $row['total_weight'],
        'total_reject'=> $row['total_reject'],
        'total_price'=> $row['total_price'],
        'weight' => json_decode($row['weight_details'], true),
        'rejects' => json_decode($row['reject_details'], true)
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
