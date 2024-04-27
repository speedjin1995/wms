<?php
require_once 'db_connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
$post = json_decode(file_get_contents('php://input'), true);

if(isset($post['id'], $post['oldPass'], $post['newPass'], $post['conPass'])){
    $id = $post['id'];
    $oldPassword = $post['oldPass'];
	$newPassword = $post['newPass'];
	$confirmPassword = $post['conPass'];
	
	$stmt = $db->prepare("SELECT * from users where id = ?");
	$stmt->bind_param('s', $id);
	$stmt->execute();
	$result = $stmt->get_result();
	
	if(($row = $result->fetch_assoc()) !== null){
		$oldPassword = hash('sha512', $oldPassword . $row['salt']);
		
		if($oldPassword == $row['password']){
			$password = hash('sha512', $newPassword . $row['salt']);
			$stmt2 = $db->prepare("UPDATE users SET password = ? WHERE ID = ?");
			$stmt2->bind_param('ss', $password, $id);
			
			if($stmt2->execute()){
    			$stmt2->close();
    			$db->close();
    			
    			echo json_encode(
        	        array(
        	            "status"=> "success", 
        	            "message"=> "Update successfully"
        	        )
        	    );
    		} 
    		else{
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
    	            "message"=> "Old password is not matched"
    	        )
    	    );
		}
	} 
	else{
	     echo json_encode(
	        array(
	            "status"=> "failed", 
	            "message"=> "Data retrieve failed"
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