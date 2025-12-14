<?php
require_once 'db_connect.php';

session_start();

$post = json_decode(file_get_contents('php://input'), true);
$now = date("Y-m-d H:i:s");
$userId = $post['uid'];

$stmt = $db->prepare("SELECT waste.*, users.name from waste, users WHERE waste.created_by = users.id AND waste.deleted = '0' AND waste.weighted_by =? ORDER BY waste.created_datetime DESC");
$stmt->bind_param('s', $userId);
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
        'weighted_by'=> $row['weighted_by'],
        'staffName'=> $row['name'],
        'indicator'=> $row['indicator'],
        'weight' => json_decode($row['weight_details'], true)
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
