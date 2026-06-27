<?php
require_once '../../db_connect.php';

session_start();

if(isset($_POST['packagingName'],$_POST['company'],$_POST['packagingType'],$_POST['packagingByWeight'])){
    $userID = $_SESSION['userID'];
    $packagingName = filter_input(INPUT_POST, 'packagingName', FILTER_SANITIZE_STRING);
    $company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_STRING);
    $packagingType = filter_input(INPUT_POST, 'packagingType', FILTER_SANITIZE_STRING);
    $packagingByWeight = filter_input(INPUT_POST, 'packagingByWeight', FILTER_SANITIZE_STRING);
    $packagingWeight = null;

    if(isset($_POST['packagingWeight']) && $_POST['packagingWeight'] != null && $_POST['packagingWeight'] != ''){
        $packagingWeight = filter_input(INPUT_POST, 'packagingWeight', FILTER_SANITIZE_STRING);
	}

    if($_POST['id'] != null && $_POST['id'] != ''){
        if ($update_stmt = $db->prepare("UPDATE packaging SET packaging_name=?, packaging_type=?, weight=?, is_by_weight=?, modified_by=? WHERE id=?")) {
            $update_stmt->bind_param('ssssss', $packagingName, $packagingType, $packagingWeight, $packagingByWeight, $userID, $_POST['id']);
            
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
        if ($insert_stmt = $db->prepare("INSERT INTO packaging (packaging_name, packaging_type, weight, is_by_weight, customer, created_by) VALUES (?, ?, ?, ?, ?, ?)")) {
            $insert_stmt->bind_param('ssssss', $packagingName, $packagingType, $packagingWeight, $packagingByWeight, $company, $userID);
            
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