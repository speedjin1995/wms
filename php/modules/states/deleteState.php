<?php
require_once '../../db_connect.php';

session_start();

$userID = $_SESSION['userID'];
$del  = "1";
$type = "";

if(isset($_POST['type']) && $_POST['type'] != null && $_POST['type'] != ''){
    $type = $_POST['type'];
}

if(isset($_POST['userID'])){
    if($type == 'MULTI'){
        $ids = is_array($_POST['userID']) ? implode(",", $_POST['userID']) : $_POST['userID'];

        if($stmt = $db->prepare("UPDATE states SET deleted=?, modified_by=? WHERE id IN ($ids)")){
            $stmt->bind_param('ss', $del, $userID);

            if($stmt->execute()){
                $stmt->close(); $db->close();
                echo json_encode(array("status"=> "success", "message"=> "Deleted"));
            } else {
                echo json_encode(array("status"=> "failed", "message"=> $stmt->error));
            }
        } else {
            echo json_encode(array("status"=> "failed", "message"=> "Something went wrong"));
        }
    } else {
        $id = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_STRING);

        if($stmt = $db->prepare("UPDATE states SET deleted=?, modified_by=? WHERE id=?")){
            $stmt->bind_param('sss', $del, $userID, $id);

            if($stmt->execute()){
                $stmt->close(); $db->close();
                echo json_encode(array("status"=> "success", "message"=> "Deleted"));
            } else {
                echo json_encode(array("status"=> "failed", "message"=> $stmt->error));
            }
        } else {
            echo json_encode(array("status"=> "failed", "message"=> "Something went wrong"));
        }
    }
} else {
    echo json_encode(array("status"=> "failed", "message"=> "Please fill in all the fields"));
}
?>
