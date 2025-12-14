<?php
require_once 'db_connect.php';

$post = json_decode(file_get_contents('php://input'), true);

$staffId = $post['userId'];

$staff = $db->query("SELECT * FROM vehicles WHERE deleted = '0' AND customer = '".$staffId."'");

$data6 = array();

while($row6=mysqli_fetch_assoc($staff)){
    $data6[] = array( 
        'id'=>$row6['id'],
        'staff'=>$row6['veh_number']
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