<?php
require_once "db_connect.php";

session_start();

if(!isset($_SESSION['userID'])){
	echo '<script type="text/javascript">location.href = "../login.html";</script>'; 
}else{
    $user = $_SESSION['userID'];
}

if(isset($_POST['module'],$_POST['state'],$_POST['company'])){
    $module = filter_input(INPUT_POST, 'module', FILTER_SANITIZE_STRING);
    $company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_STRING);
    $state = isset($_POST['state']) ? $_POST['state'] : [];

    if($_POST['id'] != null && $_POST['id'] != ''){
        if ($update_stmt = $db->prepare("UPDATE daily_sales_setup SET module=?, state=?, modified_by=? WHERE id=?")) {
            $stateJson = json_encode($state);
            $update_stmt->bind_param('ssss', $module, $stateJson, $user, $_POST['id']);
            
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
        // Check duplicate module for same company
        $check_stmt = $db->prepare("SELECT id FROM daily_sales_setup WHERE module=? AND company=? AND deleted=0 AND id!=?");
        $checkId = $_POST['id'] != null && $_POST['id'] != '' ? $_POST['id'] : 0;
        $check_stmt->bind_param('ssi', $module, $company, $checkId);
        $check_stmt->execute();
        $check_stmt->store_result();

        if($check_stmt->num_rows > 0){
            echo json_encode(array("status"=> "failed", "message"=> "Module already exists for this company!"));
            $check_stmt->close();
            $db->close();
            exit;
        }
        $check_stmt->close();

        if ($insert_stmt = $db->prepare("INSERT INTO daily_sales_setup (module, state, company, created_by) VALUES (?, ?, ?, ?)")) {
            $stateJson = json_encode($state);
            $insert_stmt->bind_param('ssss', $module, $stateJson, $company, $user);
            
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