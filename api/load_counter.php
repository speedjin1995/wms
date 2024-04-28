<?php
require_once 'db_connect.php';

$post = json_decode(file_get_contents('php://input'), true);

//$staffId = $post['userId'];
$message = '0';

$stmt = $db->prepare("SELECT count(*) as count from weighing WHERE `deleted` = '0' ORDER BY `created_datetime`");
//$stmt->bind_param('s', $staffId);
$stmt->execute();
$result = $stmt->get_result();

if($row = $result->fetch_assoc()){
    $message=$row['count'];   
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
