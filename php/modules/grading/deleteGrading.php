<?php
require_once '../../db_connect.php';
require_once '../../services/stockManagementService.php';

session_start();

if(!isset($_SESSION['userID'])){
	echo '<script type="text/javascript">location.href = "../login.html";</script>'; 
}

if(isset($_POST['id'], $_POST['cancelReason'])){
	$userID = $_SESSION['userID'];
	$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);
	$deleteReason = filter_input(INPUT_POST, 'cancelReason', FILTER_SANITIZE_STRING);
	$del = "1";
	if ($stmt2 = $db->prepare("UPDATE grading SET deleted=?, delete_reason=?, modified_by=? WHERE id=?")) {
		$stmt2->bind_param('ssss', $del, $deleteReason, $userID, $id);
		
		if($stmt2->execute()){
			$stmt2->close();

			if (in_array('stocks', $_SESSION['products'])) {
				$typeStmt = $db->prepare("SELECT type FROM grading WHERE id = ?");
				$typeStmt->bind_param('s', $id);
				$typeStmt->execute();
				$typeRow = $typeStmt->get_result()->fetch_assoc();
				$typeStmt->close();
				processDeleteGradingStock($db, $id, $_SESSION['customer'], $userID, 'grading', $typeRow['type'] ?? 'Local');
			}

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
else{
    echo json_encode(
        array(
            "status"=> "failed", 
            "message"=> "Please fill in all the fields"
        )
    ); 
}
?>
