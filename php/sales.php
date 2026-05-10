<?php
require_once "db_connect.php";

session_start();

if(isset($_POST['taxAmount'], $_POST['taxRate'],$_POST['subTotalPricing'], $_POST['totalDiscount'], $_POST['totalPricing'], $_POST['company'])){
    $userID = $_SESSION['userID'];    
    $company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_STRING);    
    $subTotalPricing = filter_input(INPUT_POST, 'subTotalPricing', FILTER_SANITIZE_STRING);
    $taxAmount = filter_input(INPUT_POST, 'taxAmount', FILTER_SANITIZE_STRING);
    $taxRate = filter_input(INPUT_POST, 'taxRate', FILTER_SANITIZE_STRING);
    $totalDiscount = filter_input(INPUT_POST, 'totalDiscount', FILTER_SANITIZE_STRING);
    $totalPricing = filter_input(INPUT_POST, 'totalPricing', FILTER_SANITIZE_STRING);
    $success = true;
    $today = date("Y-m-d 00:00:00");
    $now = date("Y-m-d H:i:s");

    # Payments json
    $payments = [];
    if (isset($_POST['payments']) && !empty($_POST['payments']) && count($_POST['payments']) > 0){
        foreach ($_POST['payments'] as $payment){
            $payments[] = [
                'method' => $payment['method'],
                'amount' => $payment['amount']
            ];
        }
    }

    if(isset($_POST['id']) && $_POST['id'] != null && $_POST['id'] != ''){
        if ($update_stmt = $db->prepare("UPDATE transporters SET transporter_code=?, transporter_name=?, transporter_ic=? WHERE id=?")) {
            $update_stmt->bind_param('ssss', $transporterCode, $transporterName, $transporterIC, $_POST['id']);
            
            // Execute the prepared query.
            if (! $update_stmt->execute()) {
                echo json_encode(
                    array(
                        "status"=> "failed", 
                        "message"=> $update_stmt->error
                    )
                );
            }
            else{
                $update_stmt->close();
                $db->close();
                
                echo json_encode(
                    array(
                        "status"=> "success", 
                        "message"=> "Updated Successfully!!" 
                    )
                );
            }
        }
    }
    else{
        if ($select_stmt = $db->prepare("SELECT COUNT(*) FROM sales WHERE created_datetime >= ?")) {
            $select_stmt->bind_param('s', $today);
            
            // Execute the prepared query.
            if (! $select_stmt->execute()) {
                echo json_encode(
                    array(
                        "status" => "failed",
                        "message" => "Failed to get latest count"
                    )); 
            }
            else{
                $result = $select_stmt->get_result();
                $count = 1;
                $receiptNo = 'S'.date("Ymd");
                
                if ($row = $result->fetch_assoc()) {
                    $count = (int)$row['COUNT(*)'] + 1;
                    $select_stmt->close();
                }

                $charSize = strlen(strval($count));

                for($i=0; $i<(4-(int)$charSize); $i++){
                    $receiptNo.='0';  // S0000
                }
        
                $receiptNo .= strval($count);  //S00009

                if ($insert_stmt = $db->prepare("INSERT INTO sales (receipt_no, subtotal, tax, tax_amount, discount, total_price, payments, created_by, created_datetime, company) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
                    $paymentsJson = json_encode($payments);
                    $insert_stmt->bind_param('ssssssssss', $receiptNo, $subTotalPricing, $taxRate, $taxAmount, $totalDiscount, $totalPricing, $paymentsJson, $userID, $now, $company);

                    // Execute the prepared query.
                    if (! $insert_stmt->execute()) {
                        echo json_encode(
                            array(
                                "status"=> "failed", 
                                "message"=> "Failed to created sales records due to ".$insert_stmt->error
                            )
                        );
                    }
                    else{
                        $id = $insert_stmt->insert_id;;
                        $insert_stmt->close();

                        # sales_cart 
                        if (isset($_POST['items'])){
                            $items = $_POST['items'];
                            $itemPrice = $_POST['itemPrice'];
                            $itemWeight =  $_POST['itemWeight'];
                            $totalPrice = $_POST['totalPrice'];
                            $deleteStatus = 1;
                            if(isset($items) && $items != null && count($items) > 0){
                                # Delete all existing product rawmat records tied to the product id then reinsert
                                if ($delete_stmt = $db->prepare("UPDATE sales_cart SET status=? WHERE sales_id=?")){
                                    $delete_stmt->bind_param('ss', $deleteStatus, $id);
            
                                    // Execute the prepared query.
                                    if (! $delete_stmt->execute()) {
                                        $success = false;
                                    }
                                    else{
                                        foreach ($items as $key => $itemId) {
                                            // Insert new records
                                            if ($sales_stmt = $db->prepare("INSERT INTO sales_cart (sales_id, product_id, weight, price, total_price) VALUES (?, ?, ?, ?, ?)")){
                                                $sales_stmt->bind_param('sssss', $id, $itemId, $itemWeight[$key], $itemPrice[$key], $totalPrice[$key]);
                                                $sales_stmt->execute();
                                                $sales_stmt->close();
                                            }

                                            // Query Inventory to see if the product exist, if exist update stock, else insert new record with stock
                                            if ($select_stmt = $db->prepare("SELECT id FROM inventory WHERE product_id = ? AND status = 0")) {
                                                $select_stmt->bind_param('s', $itemId);
                                                
                                                // Execute the prepared query.
                                                if (! $select_stmt->execute()) {
                                                    echo json_encode(
                                                        array(
                                                            "status" => "failed",
                                                            "message" => "Failed to check product existence"
                                                        )); 
                                                }
                                                else{
                                                    $result = $select_stmt->get_result();
                                                    
                                                    if ($row = $result->fetch_assoc()) {
                                                        $inventoryId = $row['id'];
                                                        // Product exist, update stock
                                                        if ($update_stock_stmt = $db->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ?")){
                                                            $update_stock_stmt->bind_param('ss', $itemWeight[$key], $inventoryId);
                                                            $update_stock_stmt->execute();
                                                            $update_stock_stmt->close();
                                                        }
                                                    }
                                                }

                                                $select_stmt->close();
                                            }
                                        }
                                    }
                                } 
                            }
                        }

                        if($success){
                            $db->close();

                            echo json_encode(
                                array(
                                    "status"=> "success", 
                                    "message"=> "Added Successfully!!"
                                )
                            );
                        }
                        else{
                            $db->close();

                            echo json_encode(
                                array(
                                    "status"=> "failed", 
                                    "message"=> "Failed to created sales cart records due to ".$insert_stmt->error 
                                )
                            );
                        }
                    }
                }
            }
        }
    }
}
else{
    echo json_encode(
        array(
            "status"=> "failed", 
            "message"=> "Please fill in all the fields"
        )
    );
}
?>