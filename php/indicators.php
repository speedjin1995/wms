<?php
require_once "db_connect.php";

session_start();

if(isset($_POST['code'], $_POST['mac'], $_POST['udid'], $_POST['customer'], $_POST['users'])){
    $name = filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING);
    $mac_address = filter_input(INPUT_POST, 'mac', FILTER_SANITIZE_STRING);
    $udid = filter_input(INPUT_POST, 'udid', FILTER_SANITIZE_STRING);
    $customer = filter_input(INPUT_POST, 'customer', FILTER_SANITIZE_STRING);
    $users = filter_input(INPUT_POST, 'users', FILTER_SANITIZE_STRING);

    if($_POST['id'] != null && $_POST['id'] != ''){
        if ($update_stmt = $db->prepare("UPDATE indicators SET name=?, mac_address=?, udid=?, customer=?, users=? WHERE id=?")) {
            $update_stmt->bind_param('ssssss', $name, $mac_address, $udid, $customer, $users, $_POST['id']);
            
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
        if ($insert_stmt = $db->prepare("INSERT INTO indicators (name, mac_address, udid, customer, users) VALUES (?, ?, ?, ?, ?)")) {
            $insert_stmt->bind_param('sssss', $name, $mac_address, $udid, $customer, $users);
            
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