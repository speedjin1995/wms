<?php
require_once "db_connect.php";

session_start();

if(isset($_POST['userID'])){
	$id = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_STRING);

    if ($update_stmt = $db->prepare("SELECT * FROM products WHERE id=?")) {
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
                $message['product_code'] = $row['product_code'];
                $message['product_name'] = $row['product_name'];
                $message['product_sn'] = $row['product_sn'];
                $message['batch_no'] = $row['batch_no'];
                $message['parts_no'] = $row['parts_no'];
                $message['uom'] = $row['uom'];
                $message['remark'] = $row['remark'];
                $message['pricing_type'] = $row['pricing_type'];
                $message['price'] = $row['price'];
                $message['weight'] = $row['weight'];
                $message['customer'] = $row['customer'];

                // retrieve product customers
                $empQuery = "SELECT * FROM Product_Customers WHERE product_id = $id AND deleted = '0' ORDER BY id ASC";
                $empRecords = mysqli_query($db, $empQuery);
                $productCustomers = array();
                $productCustomerCount = 1;

                while($row2 = mysqli_fetch_assoc($empRecords)) {
                    $productCustomers[] = array(
                        "no" => $productCustomerCount,
                        "id" => $row2['id'],
                        "product_id" => $row2['product_id'],
                        "customer_id" => $row2['customer_id'],
                        "pricing_type" => $row2['pricing_type'],
                        "price" => $row2['price']
                    );
                    $productCustomerCount++;
                }

                $message['productCustomers'] = $productCustomers;
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