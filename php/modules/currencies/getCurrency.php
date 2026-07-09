<?php
require_once '../../db_connect.php';

session_start();

if(isset($_POST['userID'])){
    $id = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_STRING);

    if($stmt = $db->prepare("SELECT * FROM currency WHERE id=?")){
        $stmt->bind_param('s', $id);

        if(!$stmt->execute()){
            echo json_encode(array("status"=> "failed", "message"=> "Something went wrong"));
        } else {
            $result  = $stmt->get_result();
            $message = array();

            while($row = $result->fetch_assoc()){
                $message['id']          = $row['id'];
                $message['currency']    = $row['currency'];
                $message['description'] = $row['description'];
                $message['rate']        = $row['rate'];
                $message['is_default']  = $row['is_default'];
                $message['customer']    = $row['customer'];
            }

            echo json_encode(array("status"=> "success", "message"=> $message));
        }
    }
} else {
    echo json_encode(array("status"=> "failed", "message"=> "Missing Attribute"));
}
?>
