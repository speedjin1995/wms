<?php
require_once "db_connect.php";
require_once "lookup.php";
$db->set_charset("utf8mb4");

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
                $message['security_bills'] = $row['security_bills'];
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
                $message['weighted_by'] = searchUserNameById($row['weighted_by'], $db);
                $message['checked_by'] = $row['checked_by'];
                $message['remark'] = $row['remark'];

                if ($row['status'] == 'DISPATCH'){
                    $message['customer_supplier'] = searchCustomerNameById($row['customer'], $row['other_customer'], $db);
                    $parentId = searchCustomerParentById($row['customer'], $db);
                    $message['parent'] = searchCustomerNameById($parentId, '', $db);
                }else{
                    $message['customer_supplier'] = searchSupplierNameById($row['supplier'], $row['other_supplier'], $db);
                    $parentId = searchSupplierParentById($row['supplier'], $db);
                    $message['parent'] = searchSupplierNameById($parentId, '', $db);
                }

                if (isset($row['weight_details']) && !empty($row['weight_details'])){
                    $weightDetails = json_decode($row['weight_details'], true);
                }

                $message['weightDetails'] = $weightDetails;

                if (isset($row['reject_details']) && !empty($row['reject_details'])){
                    $rejectDetails = json_decode($row['reject_details'], true);
                }

                $message['rejectDetails'] = $rejectDetails;
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