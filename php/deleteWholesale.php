<?php
require_once 'db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
	echo '<script type="text/javascript">location.href = "../login.html";</script>'; 
}

if(isset($_POST['id'], $_POST['cancelReason'])){
	$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);
	$deleteReason = filter_input(INPUT_POST, 'cancelReason', FILTER_SANITIZE_STRING);
	$del = "1";
	if ($stmt2 = $db->prepare("UPDATE wholesales SET deleted=?, delete_reason=? WHERE id=?")) {
		$stmt2->bind_param('sss', $del, $deleteReason, $id);
		
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
else{
    echo json_encode(
        array(
            "status"=> "failed", 
            "message"=> "Please fill in all the fields"
        )
    ); 
}
?>
