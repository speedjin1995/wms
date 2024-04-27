<?php
require_once "db_connect.php";

session_start();

if(isset($_POST['userID'])){
	$id = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_STRING);

    if ($update_stmt = $db->prepare("SELECT * FROM customers WHERE id=?")) {
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
                $message['customer_code'] = $row['customer_code'];
                $message['reg_no'] = $row['reg_no'];
                $message['customer_name'] = $row['customer_name'];
                $message['customer_address'] = $row['customer_address'];
                $message['customer_address2'] = $row['customer_address2'];
                $message['customer_address3'] = $row['customer_address3'];
                $message['customer_address4'] = $row['customer_address4'];
                $message['customer_phone'] = $row['customer_phone'];
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