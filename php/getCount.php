<?php
require_once "db_connect.php";

session_start();

if(isset($_POST['userID'])){
	$id = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_STRING);
    $del = "1";

    if ($update_stmt = $db->prepare("SELECT * FROM counting WHERE id=?")) {
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
            
            if ($row = $result->fetch_assoc()) {
                $message['serial_no'] = $row['serial_no'];
                $message['batch_no'] = $row['batch_no'];
                $message['product'] = $row['product'];
                $message['product_desc'] = $row['product_desc'];
                $message['gross'] = $row['gross'];
                $message['unit'] = $row['unit'];
                $message['count'] = $row['count'];
                $message['remark'] = $row['remark'];
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