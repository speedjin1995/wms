<?php
require_once 'db_connect.php';

$post = json_decode(file_get_contents('php://input'), true);
$now = date("Y-m-d H:i:s");
$userId = $post['uid'];

$stmt = $db->prepare("SELECT packing.*, customers.customer_name, size.size AS size_name, spec.spec AS spec_name, users.name from packing, customers, size, spec, users WHERE packing.customer = customers.id AND packing.size = size.id AND packing.spec = spec.id AND packing.created_by = users.id AND packing.deleted = '0' AND packing.weighted_by =? ORDER BY packing.created_datetime DESC");
$stmt->bind_param('s', $userId);
$stmt->execute();
$result = $stmt->get_result();
$message = array();

while($row = $result->fetch_assoc()){
    $message[] = array( 
        'id'=>$row['id'],
        'serial_no'=>$row['serial_no'],
        'packing_no' => $row['packing_no'],
        'customer'=>$row['customer'],
        'customer_name'=>$row['customer_name'],
        'size'=>$row['size'],
        'size_name'=>$row['size_name'],
        'spec'=>$row['spec'],
        'spec_name'=>$row['spec_name'],
        'gross'=>$row['gross'],
        'tare'=>$row['tare'],
        'net'=>$row['net'],
        'pre_tare'=>$row['pre_tare'],
        'net'=>$row['net'],
        'high'=>$row['high'],
        'low'=>$row['low'],
        'pwo_no'=>$row['pwo_no'],
        'coil_no'=>$row['coil_no'],
        'no_of_coil'=>$row['no_of_coil'],
        'strip_no'=>$row['strip_no'],
        'remark'=>$row['remark'],
        'created_datetime'=>$row['created_datetime'],
        'created_by'=>$row['name'],
        'indicator'=>$row['indicator']
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
