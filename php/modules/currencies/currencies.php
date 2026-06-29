<?php
require_once '../../db_connect.php';

session_start();

$user = $_SESSION['userID'];

if(isset($_POST['currency'], $_POST['company'])){
    $currency = filter_input(INPUT_POST, 'currency', FILTER_SANITIZE_STRING);
    $description = null;
    $rate = null;
    $company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_NUMBER_INT);

    if(isset($_POST['description']) && $_POST['description'] != null && $_POST['description'] != ''){
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['rate']) && $_POST['rate'] != null && $_POST['rate'] != ''){
        $rate = filter_input(INPUT_POST, 'rate', FILTER_SANITIZE_STRING);
    }

    if($_POST['id'] != null && $_POST['id'] != ''){
        if ($stmt = $db->prepare("UPDATE currency SET currency=?, description=?, rate=?, customer=?, modified_by=? WHERE id=?")) {
            $stmt->bind_param('ssssss', $currency, $description, $rate, $company, $user, $_POST['id']);

            if(!$stmt->execute()){
                echo json_encode(array("status"=> "failed", "message"=> $stmt->error));
            } else {
                $stmt->close(); $db->close();
                echo json_encode(array("status"=> "success", "message"=> "Updated Successfully!!"));
            }
        }
    }
    else{
        if ($stmt = $db->prepare("INSERT INTO currency (currency, description, rate, customer, created_by) VALUES (?, ?, ?, ?, ?)")) {
            $stmt->bind_param('sssss', $currency, $description, $rate, $company, $user);

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
