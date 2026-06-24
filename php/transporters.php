<?php
require_once "db_connect.php";

session_start();

if(isset($_POST['transporterName'], $_POST['company'])){
    $transporterName = filter_input(INPUT_POST, 'transporterName', FILTER_SANITIZE_STRING);
    $company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_STRING);

    $transporterCode = '';
    $transporterIC = '';

    if(isset($_POST['transporterCode']) && $_POST['transporterCode'] != null && $_POST['transporterCode'] != ''){
        $transporterCode = filter_input(INPUT_POST, 'transporterCode', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['transporterIC']) && $_POST['transporterIC'] != null && $_POST['transporterIC'] != ''){
        $transporterIC = filter_input(INPUT_POST, 'transporterIC', FILTER_SANITIZE_STRING);
    }



    if($_POST['id'] != null && $_POST['id'] != ''){
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
        if ($insert_stmt = $db->prepare("INSERT INTO transporters (transporter_code, transporter_name, transporter_ic, customer) VALUES (?, ?, ?, ?)")) {
            $insert_stmt->bind_param('ssss', $transporterCode, $transporterName, $transporterIC, $company);
            
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