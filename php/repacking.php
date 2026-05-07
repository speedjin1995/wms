<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

if(isset($_POST['sourceProduct'], $_POST['productWeight'], $_POST['itemsRepack'],$_POST['itemWeight'])){
    $sourceProduct = filter_input(INPUT_POST, 'sourceProduct', FILTER_SANITIZE_STRING);    
    $productWeight = filter_input(INPUT_POST, 'productWeight', FILTER_SANITIZE_STRING);
    $success = true;
    $errorArray = [];

    // Deduct from source inventory
    if ($deductStmt = $db->prepare("UPDATE inventory SET quantity = quantity - ? WHERE product_id = ?")) {
        $deductStmt->bind_param('ss', $productWeight, $sourceProduct);

        if (!$deductStmt->execute()) {
            echo json_encode([
                'status' => 'failed', 
                'message' => 'Failed to deduct from source inventory: ' . $deductStmt->error
            ]);

            $deductStmt->close();
            $db->close();
            exit;
        }else{
            $deductStmt->close();
            
            // add to each target inventory
            if (isset($_POST['itemsRepack'])){
                $itemsRepack = $_POST['itemsRepack'];
                $itemWeight = $_POST['itemWeight'];
                if(isset($itemsRepack) && $itemsRepack != null && count($itemsRepack) > 0){
                    foreach ($itemsRepack as $key => $itemId) {
                        // Query Inventory to see if the product exist, if exist update stock, else insert new record with stock
                        if ($select_stmt = $db->prepare("SELECT id FROM inventory WHERE product_id = ? AND status = 0")) {
                            $select_stmt->bind_param('s', $itemId);
                            
                            // Execute the prepared query.
                            if (! $select_stmt->execute()) {
                                $errorMsg = $select_stmt->error;
                                $errorArray[] = "Failed to check product existence for product ID $itemId: $errorMsg";
                                $success = false;
                            }
                            else{
                                $result = $select_stmt->get_result();
                                
                                if ($row = $result->fetch_assoc()) {
                                    $inventoryId = $row['id'];
                                    // Product exist, update stock
                                    if ($update_stock_stmt = $db->prepare("UPDATE inventory SET quantity = quantity + ? WHERE id = ?")){
                                        $update_stock_stmt->bind_param('ss', $itemWeight[$key], $inventoryId);
                                        $update_stock_stmt->execute();
                                        $update_stock_stmt->close();
                                    }else{
                                        $errorMsg = $update_stock_stmt->error;
                                        $update_stock_stmt->close();
                                        $errorArray[] = "Failed to update stock for product ID $itemId: $errorMsg";
                                        $success = false;
                                    }
                                }else{
                                    // Product not exist, insert new record with stock
                                    if ($insert_product_stmt = $db->prepare("INSERT INTO inventory (product_id, quantity) VALUES (?, ?)")){
                                        $insert_product_stmt->bind_param('ss', $itemId, $itemWeight[$key]);
                                        $insert_product_stmt->execute();
                                        $insert_product_stmt->close();
                                    }else{
                                        $errorMsg = $insert_product_stmt->error;
                                        $insert_product_stmt->close();
                                        $errorArray[] = "Failed to insert new product for product ID $itemId: $errorMsg";
                                        $success = false;
                                    }
                                }
                            }

                            $select_stmt->close();
                        }
                    }
                }
            }

            if ($success) {
                echo json_encode(
                    array(
                        "status" => "success", 
                        "message" => "Repacking successful"
                    )
                );
            } else {
                echo json_encode(
                    array(
                        "status" => "failed", 
                        "message" => "Repacking completed with errors: " . implode("; ", $errorArray)
                    )
                );
            }
        }
    } else {
        echo json_encode(
            ['status' => 'failed', 
            'message' => 'Database error: ' . $db->error
        ]);
        exit;
    }
}else{
    echo json_encode(
        array(
            "status"=> "failed", 
            "message"=> "Please fill in all the fields"
        )
    );
}
?>
