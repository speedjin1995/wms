<?php
require_once "db_connect.php";

session_start();

if(isset($_POST['userID'])){
	$id = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_STRING);

    if ($update_stmt = $db->prepare("SELECT * FROM wholesales WHERE id=?")) {
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
                $message['serial_no'] = $row['serial_no'];
                $message['po_no'] = $row['po_no'];
                $message['status'] = $row['status'];
                $message['customer'] = $row['customer'];
                $message['supplier'] = $row['supplier'];
                $message['product'] = $row['product'];
                $message['package'] = $row['package'];
                $message['vehicle_no'] = $row['vehicle_no'];
                $message['driver'] = $row['driver'];
                $message['other_customer'] = $row['other_customer'];
                $message['other_supplier'] = $row['other_supplier'];
                $message['units'] = $row['units'];
                $message['total_item'] = $row['total_item'];
                $message['total_weight'] = $row['total_weight'];
                $message['total_reject'] = $row['total_reject'];
                $message['total_price'] = $row['total_price'];
                $message['remark'] = $row['remark'];

                if (isset($row['weight_details']) && !empty($row['weight_details'])){
                    $weightDetails = json_decode($row['weight_details'], true);
                }

                $message['weightDetails'] = $weightDetails;
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