<?php
require_once 'db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
	echo '<script type="text/javascript">location.href = "../login.html";</script>'; 
} else{
	$id = $_SESSION['userID'];
}

if(isset($_POST['userName'], $_POST['userEmail'], $_POST['language'])){
	$name = filter_input(INPUT_POST, 'userName', FILTER_SANITIZE_STRING);
	$username = filter_input(INPUT_POST, 'userEmail', FILTER_SANITIZE_STRING);
	$language = filter_input(INPUT_POST, 'language', FILTER_SANITIZE_STRING);
	
	$_SESSION['language'] = $language;
	
	if ($stmt2 = $db->prepare("UPDATE users SET name=?, username=?, languages=? WHERE id=?")) {
		$stmt2->bind_param('ssss', $name, $username, $language, $id);
		
		if($stmt2->execute()){
			$stmt2->close();
			$db->close();
			
			echo json_encode(
				array(
					"status"=> "success", 
					"message"=> "Your Name / Username / Language is updated successfully!" 
				)
			);
		} else{
			echo json_encode(
				array(
					"status"=> "failed", 
					"message"=> $stmt->error
				)
			);
		}
	} 
	else{
		echo json_encode(
			array(
				"status"=> "failed", 
				"message"=> "Something went wrong!"
			)
		);
	}
} 
else{
	echo json_encode(
        array(
            "status"=> "failed", 
            "message"=> "Please fill in all fields"
        )
    ); 
}
?>
