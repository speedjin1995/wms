<?php
require_once "db_connect.php";

session_start();

if(isset($_POST['vehicleNumber'], $_POST['driver'], $_POST['company'])){
    $vehicleNumber = filter_input(INPUT_POST, 'vehicleNumber', FILTER_SANITIZE_STRING);
    $driver = filter_input(INPUT_POST, 'driver', FILTER_SANITIZE_STRING);
    $company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_STRING);
    $attendence1 = null;
	$attendence2 = null;
	$vehicleWeight = null;

    if(isset($_POST['attendence1']) && $_POST['attendence1'] != null && $_POST['attendence1'] != ''){
        $attendence1 = filter_input(INPUT_POST, 'attendence1', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['attendence2']) && $_POST['attendence2'] != null && $_POST['attendence2'] != ''){
        $attendence2 = filter_input(INPUT_POST, 'attendence2', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['vehicleWeight']) && $_POST['vehicleWeight'] != null && $_POST['vehicleWeight'] != ''){
        $vehicleWeight = filter_input(INPUT_POST, 'vehicleWeight', FILTER_SANITIZE_STRING);
    }

    if($_POST['id'] != null && $_POST['id'] != ''){
        if ($update_stmt = $db->prepare("UPDATE vehicles SET veh_number=?, vehicle_weight=?, driver=?, attandence_1=?, attandence_2=? WHERE id=?")) {
            $update_stmt->bind_param('ssssss', $vehicleNumber, $vehicleWeight, $driver, $attendence1, $attendence2, $_POST['id']);
            
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
        if ($insert_stmt = $db->prepare("INSERT INTO vehicles (veh_number, vehicle_weight, driver, attandence_1, attandence_2, customer) VALUES (?, ?, ?, ?, ?, ?)")) {
            $insert_stmt->bind_param('ssssss', $vehicleNumber, $vehicleWeight, $driver, $attendence1, $attendence2, $company);
            
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