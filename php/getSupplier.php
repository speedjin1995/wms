<?php
require_once "db_connect.php";

session_start();

if(isset($_POST['userID'])){
	$id = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_STRING);

    if ($update_stmt = $db->prepare("SELECT * FROM supplies WHERE id=?")) {
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
                $message['supplier_code'] = $row['supplier_code'];
                $message['reg_no'] = $row['reg_no'];
                $message['supplier_name'] = $row['supplier_name'];
                $message['supplier_address'] = $row['supplier_address'];
                $message['supplier_address2'] = $row['supplier_address2'];
                $message['supplier_address3'] = $row['supplier_address3'];
                $message['supplier_address4'] = $row['supplier_address4'];
                $message['supplier_phone'] = $row['supplier_phone'];
                $message['pic'] = $row['pic'];
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