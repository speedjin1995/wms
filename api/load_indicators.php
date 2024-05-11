<?php
require_once 'db_connect.php';

$post = json_decode(file_get_contents('php://input'), true);
$now = date("Y-m-d H:i:s");
$staffId = $post['userId'];
$userId = $post['uid'];

$stmt = $db->prepare("SELECT * from indicators WHERE customer='".$staffId."'");
$stmt->execute();
$result = $stmt->get_result();
$message = array();

while($row = $result->fetch_assoc()){
	$message[] = array( 
        "id"=>$row['id'],
        "name"=>$row['name'],
        "mac_address"=>$row['mac_address']
    );
}

$stmt->close();
$db->close();

echo json_encode(
    array(
        "status"=> "success", 
        "message"=> $message,
    )
);
?>