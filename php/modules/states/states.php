<?php
require_once '../../db_connect.php';

session_start();

$user = $_SESSION['userID'];

if(isset($_POST['state'], $_POST['company'])){
    $state   = filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING);
    $company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_NUMBER_INT);

    if($_POST['id'] != null && $_POST['id'] != ''){
        if ($stmt = $db->prepare("UPDATE states SET states=?, customer=?, modified_by=? WHERE id=?")) {
            $stmt->bind_param('ssss', $state, $company, $user, $_POST['id']);

            if(!$stmt->execute()){
                echo json_encode(array("status"=> "failed", "message"=> $stmt->error));
            } else {
                $stmt->close(); $db->close();
                echo json_encode(array("status"=> "success", "message"=> "Updated Successfully!!"));
            }
        }
    }
    else{
        if ($stmt = $db->prepare("INSERT INTO states (states, customer, created_by) VALUES (?, ?, ?)")) {
            $stmt->bind_param('sss', $state, $company, $user);

            if(!$stmt->execute()){
                echo json_encode(array("status"=> "failed", "message"=> $stmt->error));
            } else {
                $stmt->close(); $db->close();
                echo json_encode(array("status"=> "success", "message"=> "Added Successfully!!"));
            }
        }
    }
}
else{
    echo json_encode(array("status"=> "failed", "message"=> "Please fill in all the fields"));
}
?>
