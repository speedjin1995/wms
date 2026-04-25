<?php
require_once "db_connect.php";

session_start();

if(isset($_POST['code'], $_POST['name'], $_POST['company'])){
    $code = filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_STRING);
    $reg_no = null;
	$address = null;
    $address2 = null;
    $address3 = null;
    $address4 = null;
    $states = null;
	$billingName = null;
	$billingAddress = null;
    $billingAddress2 = null;
    $billingAddress3 = null;
    $billingAddress4 = null;
    $billingState = null;
    $phone = null;
    $fax = null;
    $email = null;
    $parent = null;

    if(isset($_POST['reg_no']) && $_POST['reg_no'] != null && $_POST['reg_no'] != ''){
        $reg_no = filter_input(INPUT_POST, 'reg_no', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['address']) && $_POST['address'] != null && $_POST['address'] != ''){
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['address2']) && $_POST['address2'] != null && $_POST['address2'] != ''){
        $address2 = filter_input(INPUT_POST, 'address2', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['address3']) && $_POST['address3'] != null && $_POST['address3'] != ''){
        $address3 = filter_input(INPUT_POST, 'address3', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['address4']) && $_POST['address4'] != null && $_POST['address4'] != ''){
        $address4 = filter_input(INPUT_POST, 'address4', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['states']) && $_POST['states'] != null && $_POST['states'] != ''){
        $states = filter_input(INPUT_POST, 'states', FILTER_SANITIZE_STRING);
    }
    
    if(isset($_POST['billingName']) && $_POST['billingName'] != null && $_POST['billingName'] != ''){
        $billingName = filter_input(INPUT_POST, 'billingName', FILTER_SANITIZE_STRING);
    }
    
    if(isset($_POST['billingAddress']) && $_POST['billingAddress'] != null && $_POST['billingAddress'] != ''){
        $billingAddress = filter_input(INPUT_POST, 'billingAddress', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['billingAddress2']) && $_POST['billingAddress2'] != null && $_POST['billingAddress2'] != ''){
        $billingAddress2 = filter_input(INPUT_POST, 'billingAddress2', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['billingAddress3']) && $_POST['billingAddress3'] != null && $_POST['billingAddress3'] != ''){
        $billingAddress3 = filter_input(INPUT_POST, 'billingAddress3', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['billingAddress4']) && $_POST['billingAddress4'] != null && $_POST['billingAddress4'] != ''){
        $billingAddress4 = filter_input(INPUT_POST, 'billingAddress4', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['billingStates']) && $_POST['billingStates'] != null && $_POST['billingStates'] != ''){
        $billingStates = filter_input(INPUT_POST, 'billingStates', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['phone']) && $_POST['phone'] != null && $_POST['phone'] != ''){
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['fax']) && $_POST['fax'] != null && $_POST['fax'] != ''){
        $fax = filter_input(INPUT_POST, 'fax', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['email']) && $_POST['email'] != null && $_POST['email'] != ''){
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['parent']) && $_POST['parent'] != null && $_POST['parent'] != ''){
        $parent = filter_input(INPUT_POST, 'parent', FILTER_SANITIZE_STRING);
    }

    if($_POST['id'] != null && $_POST['id'] != ''){
        if ($update_stmt = $db->prepare("UPDATE customers SET customer_code=?, reg_no=?, customer_name=?, customer_address=?, customer_address2=?, customer_address3=?, customer_address4=?, states=?, billing_name=?, billing_address=?, billing_address2=?, billing_address3=?, billing_address4=?, billing_state=?, customer_phone=?, pic=?, fax=?, parent=? WHERE id=?")) {
            $update_stmt->bind_param('sssssssssssssssssss', $code, $reg_no, $name, $address, $address2, $address3, $address4, $states, $billingName, $billingAddress, $billingAddress2, $billingAddress3, $billingAddress4, $billingStates, $phone, $email, $fax, $parent, $_POST['id']);
            
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
        if ($insert_stmt = $db->prepare("INSERT INTO customers (customer_code, reg_no, customer_name, customer_address, customer_address2, customer_address3, customer_address4, states, billing_name, billing_address, billing_address2, billing_address3, billing_address4, billing_state, customer_phone, pic, fax, parent, customer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
            $insert_stmt->bind_param('sssssssssssssssssss', $code, $reg_no, $name, $address, $address2, $address3, $address4, $states, $billingName, $billingAddress, $billingAddress2, $billingAddress3, $billingAddress4, $billingStates, $phone, $email, $fax, $parent, $company);
            
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