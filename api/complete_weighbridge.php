<?php
require_once 'db_connect.php';

$post = json_decode(file_get_contents('php://input'), true);

$id = $post['id'];
$deleted = 'Y';

$stmt = $db->prepare("UPDATE Weight SET is_complete =? WHERE id =?");
$stmt->bind_param('ss', $deleted, $id);


if($stmt->execute()){
    $stmt->close();
    $db->close();
    
    echo json_encode(
        array(
            "status"=> "success", 
            "message"=> "completed"
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