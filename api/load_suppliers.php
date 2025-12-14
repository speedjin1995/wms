<?php
require_once 'db_connect.php';

$post = json_decode(file_get_contents('php://input'), true);

$staffId = $post['userId'];

$staff = $db->query("SELECT * FROM supplies WHERE deleted = '0' AND customer = '".$staffId."'");

$data6 = array();

while($row6=mysqli_fetch_assoc($staff)){
    $data6[] = array( 
        'id'=>$row6['id'],
        'staff'=>$row6['supplier_name'],
        'regNo'=>$row6['reg_no'],
        'address'=>$row6['supplier_address'],
        'address2'=>$row6['supplier_address2'],
        'address3'=>$row6['supplier_address3'],
        'address4'=>$row6['supplier_address4'],
        'phone'=>$row6['supplier_phone'],
        'email'=>$row6['pic']
    );
}

$db->close();

echo json_encode(
    array(
        "status"=> "success", 
        "message"=> $data6
    )
);
?>