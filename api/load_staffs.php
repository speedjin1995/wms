<?php
require_once 'db_connect.php';

$post = json_decode(file_get_contents('php://input'), true);

$staffId = $post['userId'];

$staff = $db->query("SELECT * FROM users WHERE deleted = '0' AND customer = '".$staffId."'");

$data6 = array();

while($row6=mysqli_fetch_assoc($staff)){
    $data6[] = array( 
        'id'=>$row6['id'],
        'staff'=>$row6['staff_name'],
        'username'=>$row6['username'],
        'role_code'=>$row6['role_code']
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