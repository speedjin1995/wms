<?php
require_once 'db_connect.php';

session_start();

$post = json_decode(file_get_contents('php://input'), true);
$now = date("Y-m-d H:i:s");

$stmt = $db->prepare("SELECT * from weighing WHERE deleted = '0' ORDER BY `created_datetime` DESC");
$stmt->execute();
$result = $stmt->get_result();
$message = array();

while($row = $result->fetch_assoc()){
    $message[] = array( 
        'id'=>$row['id'],
        'serial_no'=>$row['serial_no'],
        'status'=>$row['status'],
        'po_no'=>$row['po_no'],
        'customer'=>$row['customer'],
        'supplier'=>$row['supplier'],
        'product'=>$row['product'],
        'driver_name'=>$row['driver_name'],
        'lorry_no'=>$row['lorry_no'],
        'farm_id'=>$row['farm_id'],
        'remark'=>$row['remark'],
        'average_bird'=>$row['average_bird'],
        'weight_data'=>$row['weight_data'],
        'total_cages_weight'=>$row['total_cages_weight'],
        'total_cages_count'=>$row['total_cages_count'],
        'total_bird_weight'=>$row['total_bird_weight'],
        'total_bird_count'=>$row['total_bird_count'],
        'created_datetime'=>$row['created_datetime'],
        'status'=>$row['status'],
        'start_time'=>$row['start_time'],
        'end_time'=>$row['end_time']
        
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
