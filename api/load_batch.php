<?php
require_once 'db_connect.php';

session_start();

$post = json_decode(file_get_contents('php://input'), true);
$now = date("Y-m-d H:i:s");
$userId = $post['uid'];

$stmt = $db->prepare("SELECT weighing.*, products.product_name, locations.locations AS location_name, units.units AS unit_name, users.name from weighing, products, locations, units, users WHERE weighing.product = products.id AND weighing.locations = locations.id AND weighing.units = units.id AND weighing.created_by = users.id AND weighing.deleted = '0' AND weighing.type = 'BATCH' AND weighted_by =?  ORDER BY weighing.created_datetime DESC");
$stmt->bind_param('s', $userId);
$stmt->execute();
$result = $stmt->get_result();
$message = array();
$poList = array();

while($row = $result->fetch_assoc()){
    if(!in_array($row['batch_serial'], $poList)){
        $message[] = array(
            'po_no'=> $row['po_no'],
            'batch_serial'=> $row['batch_serial'],
            'name'=> $row['name'],
            'weight' => array()
        );
        
        array_push($poList, $row['batch_serial']);
    }
    
    $key = array_search($row['batch_serial'], $poList);
    
    $message[$key]['weight'][] = array( 
        'id'=>$row['id'],
        'serial_no'=>$row['serial_no'],
        'po_no' => $row['po_no'],
        'product'=>$row['product'],
        'product_name'=>$row['product_name'],
        'indicator'=>$row['indicator'],
        'product_desc'=>$row['product_desc'],
        'units'=>$row['units'],
        'unit_name'=>$row['unit_name'],
        'locationsId'=>$row['locations'],
        'locations'=>$row['location_name'],
        'gross'=>$row['gross'],
        'tare'=>$row['tare'],
        'net'=>$row['net'],
        'pre_tare'=>$row['pre_tare'],
        'net'=>$row['net'],
        'high'=>$row['high'],
        'low'=>$row['low'],
        'created_datetime'=>$row['created_datetime'],
        'created_by'=>$row['name']
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
