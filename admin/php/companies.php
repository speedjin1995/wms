<?php
require_once "db_connect.php";
require_once 'insertDefaultTranslations.php';

session_start();

if(isset($_POST['reg_no'], $_POST['name'], $_POST['address'], $_POST['phone'], $_POST['email'], $_POST['products'])){
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $reg_no = filter_input(INPUT_POST, 'reg_no', FILTER_SANITIZE_STRING);
	$address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $address2 = null;
    $address3 = null;
    $address4 = null;
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_STRING);
    $products = $_POST['products'];
    $products = json_encode($products);
    $packages = $_POST['packages'];
    $packages = json_encode($packages);
    $company = null;

    if(isset($_POST['address2']) && $_POST['address2'] != null && $_POST['address2'] != ''){
        $address2 = filter_input(INPUT_POST, 'address2', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['address3']) && $_POST['address3'] != null && $_POST['address3'] != ''){
        $address3 = filter_input(INPUT_POST, 'address3', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['address4']) && $_POST['address4'] != null && $_POST['address4'] != ''){
        $address4 = filter_input(INPUT_POST, 'address4', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['phone']) && $_POST['phone'] != null && $_POST['phone'] != ''){
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['email']) && $_POST['email'] != null && $_POST['email'] != ''){
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['company']) && $_POST['company'] != null && $_POST['company'] != ''){
        $company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_STRING);
    }

    if($_POST['id'] != null && $_POST['id'] != ''){
        if ($update_stmt = $db->prepare("UPDATE companies SET reg_no=?, name=?, address=?, address2=?, address3=?, address4=?, phone=?, email=?, products=?, packages=?, parent=? WHERE id=?")) {
            $update_stmt->bind_param('ssssssssssss', $reg_no, $name, $address, $address2, $address3, $address4, $phone, $email, $products, $packages, $company, $_POST['id']);
            
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
        if ($insert_stmt = $db->prepare("INSERT INTO companies (reg_no, name, address, address2, address3, address4, phone, email, products, packages, parent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
            $insert_stmt->bind_param('sssssssssss', $reg_no, $name, $address, $address2, $address3, $address4, $phone, $email, $products, $packages, $company);
            
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
                $companyId = $insert_stmt->insert_id;
                $insert_stmt->close();

                insertDefaultTranslations($db, $companyId);

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