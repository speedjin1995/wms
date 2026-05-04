<?php
require_once "db_connect.php";
require_once "lookup.php";
$db->set_charset("utf8mb4");

session_start();

if(isset($_POST['userID'])){
	$id = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_STRING);

    if ($update_stmt = $db->prepare("SELECT * FROM weight WHERE id=?")) {
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
                $message['transaction_id'] = $row['transaction_id'];
                $message['transaction_status'] = $row['transaction_status'];
                $message['transaction_date'] = $row['transaction_date'];
                $message['lorry_plate_no1'] = $row['lorry_plate_no1'];
                $message['customer_name'] = $row['customer_name'];
                $message['supplier_name'] = $row['supplier_name'];
                $message['product_name'] = $row['product_name'];
                $message['purchase_order'] = $row['purchase_order'];
                $message['delivery_no'] = $row['delivery_no'];
                $message['gross_weight1'] = $row['gross_weight1'];
                $message['gross_weight1_date'] = $row['gross_weight1_date'];
                $message['tare_weight1'] = $row['tare_weight1'];
                $message['tare_weight1_date'] = $row['tare_weight1_date'];
                $message['nett_weight1'] = $row['nett_weight1'];
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