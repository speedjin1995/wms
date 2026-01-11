<?php
require_once "db_connect.php";

session_start();

if(isset($_POST['code'], $_POST['product'], $_POST['company'])){
    $code = filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING);
    $product = filter_input(INPUT_POST, 'product', FILTER_SANITIZE_STRING);
    $company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_STRING);
    
    $serial = null;
    $batch = null;
    $part = null;
    $uom = null;
    $remark = null;
    $pricingType = null;
    $price = null;
    $weight = null;

    if(isset($_POST['serial']) && $_POST['serial'] != null && $_POST['serial'] != ''){
        $serial = filter_input(INPUT_POST, 'serial', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['batch']) && $_POST['batch'] != null && $_POST['batch'] != ''){
        $batch = filter_input(INPUT_POST, 'batch', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['part']) && $_POST['part'] != null && $_POST['part'] != ''){
        $part = filter_input(INPUT_POST, 'part', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['uom']) && $_POST['uom'] != null && $_POST['uom'] != ''){
        $uom = filter_input(INPUT_POST, 'uom', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['remark']) && $_POST['remark'] != null && $_POST['remark'] != ''){
        $remark = filter_input(INPUT_POST, 'remark', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['pricingType']) && $_POST['pricingType'] != null && $_POST['pricingType'] != ''){
        $pricingType = filter_input(INPUT_POST, 'pricingType', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['price']) && $_POST['price'] != null && $_POST['price'] != ''){
        $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['weight']) && $_POST['weight'] != null && $_POST['weight'] != ''){
        $weight = filter_input(INPUT_POST, 'weight', FILTER_SANITIZE_STRING);
    }

    if($_POST['id'] != null && $_POST['id'] != ''){
        if ($update_stmt = $db->prepare("UPDATE products SET product_code=?, product_name=?, product_sn=?, batch_no=?, parts_no=?, uom=?, remark=?, pricing_type=?, price=?, weight=? WHERE id=?")) {
            $update_stmt->bind_param('sssssssssss', $code, $product, $serial, $batch, $part, $uom, $remark, $pricingType, $price, $weight, $_POST['id']);
            
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
                # Product_Customers 
                if (isset($_POST['no'])){
                    $no = $_POST['no'];
                    $customers =  $_POST['customers'];
                    $customerPricingType = $_POST['customerPricingType'];
                    $customerPrice = $_POST['customerPrice'];
                    $deleteStatus = 1;
                    if(isset($no) && $no != null && count($no) > 0){
                        # Delete all existing product rawmat records tied to the product id then reinsert
                        if ($delete_stmt = $db->prepare("UPDATE product_customers SET deleted=? WHERE product_id=?")){
                            $delete_stmt->bind_param('ss', $deleteStatus, $_POST['id']);
    
                            // Execute the prepared query.
                            if (! $delete_stmt->execute()) {
                                echo json_encode(
                                    array(
                                        "status"=> "failed", 
                                        "message"=> $delete_stmt->error
                                    )
                                );
                            }
                            else{
                                foreach ($no as $key => $number) {
                                    if ($product_stmt = $db->prepare("INSERT INTO product_customers (product_id, customer_id, pricing_type, price) VALUES (?, ?, ?, ?)")){
                                        $product_stmt->bind_param('ssss', $_POST['id'], $customers[$key], $customerPricingType[$key], $customerPrice[$key]);
                                        $product_stmt->execute();
                                        $product_stmt->close();
                                    }
                                }
                            }
                        } 
                    }
                }

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
        if ($insert_stmt = $db->prepare("INSERT INTO products (product_code, product_name, product_sn, batch_no, parts_no, uom, remark, pricing_type, price, weight, customer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
            $insert_stmt->bind_param('sssssssssss', $code, $product, $serial, $batch, $part, $uom, $remark, $pricingType, $price, $weight, $company);
            
            // Execute the prepared query.
            if (! $insert_stmt->execute()) {
                echo json_encode(
                    array(
                        "status"=> "failed", 
                        "message"=> $insert_stmt->error
                    )
                );
            }
            else{
                $productId = $insert_stmt->insert_id;

                # Product_RawMat 
                if(isset($_POST['no'])){
                    $no = $_POST['no'];
                    $customers =  $_POST['customers'];
                    $customerPricingType = $_POST['customerPricingType'];
                    $customerPrice = $_POST['customerPrice'];

                    if(isset($no) && $no != null && count($no) > 0){
                        foreach ($no as $key => $number) {
                            if ($product_stmt = $db->prepare("INSERT INTO product_customers (product_id, customer_id, pricing_type, price) VALUES (?, ?, ?, ?)")){
                                $product_stmt->bind_param('ssss', $productId, $customers[$key], $customerPricingType[$key], $customerPrice[$key]);
                                $product_stmt->execute();
                                $product_stmt->close();
                            }
                        }
                    }
                }

                $insert_stmt->close();
                $db->close();
                
                echo json_encode(
                    array(
                        "status"=> "success", 
                        "message"=> "Added Successfully!!" 
                    )
                );
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