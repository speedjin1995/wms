<?php
require_once "db_connect.php";

session_start();

if(isset($_POST['product'])){
    $product = filter_input(INPUT_POST, 'product', FILTER_SANITIZE_STRING);
    $company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_STRING);
    $remark = null;
    $price = null;
    $weight = null;

    if(isset($_POST['remark']) && $_POST['remark'] != null && $_POST['remark'] != ''){
        $remark = filter_input(INPUT_POST, 'remark', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['price']) && $_POST['price'] != null && $_POST['price'] != ''){
        $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['weight']) && $_POST['weight'] != null && $_POST['weight'] != ''){
        $weight = filter_input(INPUT_POST, 'weight', FILTER_SANITIZE_STRING);
    }

    if($_POST['id'] != null && $_POST['id'] != ''){
        if ($update_stmt = $db->prepare("UPDATE products SET product_name=?, remark=?, price=?, weight=? WHERE id=?")) {
            $update_stmt->bind_param('sssss', $product, $remark, $price, $weight, $_POST['id']);
            
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
        if ($insert_stmt = $db->prepare("INSERT INTO products (product_name, remark, price, weight, customer) VALUES (?, ?, ?, ?, ?)")) {
            $insert_stmt->bind_param('sssss', $product, $remark, $price, $weight, $company);
            
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