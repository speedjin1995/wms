<?php
require_once 'db_connect.php';

$post = json_decode(file_get_contents('php://input'), true);
 
$ids = json_decode($post['id'], true);
$deleted = '1';
$success = true;

for($i=0; $i<count($ids); $i++){
    $id = $ids[$i];
    
    $stmt = $db->prepare("UPDATE transporters SET deleted =? WHERE id =?");
    $stmt->bind_param('ss', $deleted, $id);
    
    
    if(!$stmt->execute()){
        $success = false;
    }
}

$stmt->close();
$db->close();

if($success){
    echo json_encode(
        array(
            "status"=> "success", 
            "message"=> "deleted"
        )
    );
}
else{
    echo json_encode(
        array(
            "status"=> 'failed', 
            "message"=> 'Failed to delete'
        )
    );   
}
?>