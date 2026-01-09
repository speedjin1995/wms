<?php
require_once "db_connect.php";

session_start();

if(isset($_POST['driverName'], $_POST['driverIC'], $_POST['company'])){
    $driverName = filter_input(INPUT_POST, 'driverName', FILTER_SANITIZE_STRING);
    $driverIC = filter_input(INPUT_POST, 'driverIC', FILTER_SANITIZE_STRING);
    $company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_STRING);

    if($_POST['id'] != null && $_POST['id'] != ''){
        if ($update_stmt = $db->prepare("UPDATE drivers SET driver_name=?, driver_ic=? WHERE id=?")) {
            $update_stmt->bind_param('sss', $driverName, $driverIC, $_POST['id']);
            
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
        if ($insert_stmt = $db->prepare("INSERT INTO drivers (driver_name, driver_ic, customer) VALUES (?, ?, ?)")) {
            $insert_stmt->bind_param('sss', $driverName, $driverIC, $company);
            
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