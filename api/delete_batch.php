<?php
require_once 'db_connect.php';

$post = json_decode(file_get_contents('php://input'), true);

$id = $post['id'];
$deleted = '1';

$stmt = $db->prepare("UPDATE weighing SET deleted =? WHERE batch_serial =?");
$stmt->bind_param('ss', $deleted, $id);


if($stmt->execute()){
    $stmt->close();
    $db->close();
    
    echo json_encode(
        array(
            "status"=> "success", 
            "message"=> "deleted"
        )
    );
}
else{
    $stmt->close();
    $db->close();
    
    echo json_encode(
        array(
            "status"=> 'failed', 
            "message"=> 'Failed to delete'
        )
    );
}
?>