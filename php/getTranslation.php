<?php
require_once "db_connect.php";

session_start();

if(isset($_POST['messageId'])){
	$id = filter_input(INPUT_POST, 'messageId', FILTER_SANITIZE_STRING);

    if ($update_stmt = $db->prepare("SELECT * FROM message_resource WHERE id=?")) {
        $update_stmt->bind_param('s', $id);
        
        // Execute the prepared query.
        if (! $update_stmt->execute()) {
            echo json_encode(
                array(
                    "status" => "failed",
                    "message" => "Something went wrong"
                )); 
        }
        else{
            $result = $update_stmt->get_result();
            $message = array();
            
            while ($row = $result->fetch_assoc()) {
                $message['id'] = $row['id'];
                $message['message_key_code'] = $row['message_key_code'];
                $message['en'] = $row['en'];
                $message['zh'] = $row['zh'];
                $message['my'] = $row['my'];
                $message['ne'] = $row['ne'];
                $message['ja'] = $row['ja'];
                $message['company'] = $row['company'];
            }
            
            echo json_encode(
                array(
                    "status" => "success",
                    "message" => $message
                ));   
        }
    }
}
else{
    echo json_encode(
        array(
            "status" => "failed",
            "message" => "Missing Attribute"
            )); 
}
?>