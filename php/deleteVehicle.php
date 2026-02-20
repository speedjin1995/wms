<?php
require_once 'db_connect.php';

session_start();

if(isset($_POST['userID'])){
	$id = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_STRING);
	$del = "1";
	$type = "";

	if(isset($_POST['type']) && $_POST['type']!=null && $_POST['type']!=""){
		$type = $_POST['type'];
	}

	if ($type == 'MULTI'){
		if(is_array($_POST['userID'])){
			$ids = implode(",", $_POST['userID']);
		}else{
			$ids = $_POST['userID'];
		}

		if ($stmt2 = $db->prepare("UPDATE vehicles SET deleted=? WHERE id IN ($ids)")) {
			$stmt2->bind_param('s', $del);
			
			if($stmt2->execute()){
				$stmt2->close();
				$db->close();
				
				echo json_encode(
					array(
						"status"=> "success", 
						"message"=> "Deleted"
					)
				);
			} else{
				echo json_encode(
					array(
						"status"=> "failed", 
						"message"=> $stmt2->error
					)
				);
			}
		} 
		else{
			echo json_encode(
				array(
					"status"=> "failed", 
					"message"=> "Somthings wrong"
				)
			);
		}
	}else{
		if ($stmt2 = $db->prepare("UPDATE vehicles SET deleted=? WHERE id=?")) {
			$stmt2->bind_param('ss', $del , $id);
			
			if($stmt2->execute()){
				$stmt2->close();
				$db->close();
				
				echo json_encode(
					array(
						"status"=> "success", 
						"message"=> "Deleted"
					)
				);
			} else{
				echo json_encode(
					array(
						"status"=> "failed", 
						"message"=> $stmt2->error
					)
				);
			}
		} 
		else{
			echo json_encode(
				array(
					"status"=> "failed", 
					"message"=> "Somthings wrong"
				)
			);
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
