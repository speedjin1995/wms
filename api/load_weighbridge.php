<?php
require_once 'db_connect.php';

$post = json_decode(file_get_contents('php://input'), true);
$now = date("Y-m-d H:i:s");
$userId = $post['userId'];

$stmt = $db->prepare("SELECT * from Weight WHERE is_cancel = 'N' AND company = ? ORDER BY transaction_date DESC");
$stmt->bind_param('s', $userId);
$stmt->execute();
$result = $stmt->get_result();
$message = array();

while($row = $result->fetch_assoc()){
    $message[] = array( 
        'id'=>$row['id'],
        'transaction_id'=>$row['transaction_id'],
        'transaction_status' => $row['transaction_status'],
        'customer_name'=>$row['customer_name'],
        'supplier_name'=>$row['supplier_name'],
        'product_name'=>$row['product_name'],
        'lorry_plate_no1'=>$row['lorry_plate_no1'],
        'transporter'=>$row['transporter'],
        'driver_name'=>$row['driver_name'],
        'destination'=>$row['destination'],
        'gross_weight1'=>$row['gross_weight1'],
        'gross_weight1_date'=>$row['gross_weight1_date'],
        'tare_weight1'=>$row['tare_weight1'],
        'tare_weight1_date'=>$row['tare_weight1_date'],
        'nett_weight1'=>$row['nett_weight1'],
        'reduce_weight'=>$row['reduce_weight'],
        'final_weight'=>$row['final_weight'],
        'order_weight'=>$row['order_weight'],
        'weight_different'=>$row['weight_different'],
        'container_no'=>$row['container_no'],
        'seal_no'=>$row['seal_no'],
        'created_by'=>$row['created_by'],
        'created_datetime'=>$row['created_date'],
        'remark'=>$row['remarks'],
        'status'=>$row['is_complete'] == 'Y' ? 'Complete' : 'Pending'
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
