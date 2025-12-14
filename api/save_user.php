<?php
require_once 'db_connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
$post = json_decode(file_get_contents('php://input'), true);

if(isset($post['staffName'], $post['staffIc'], $post['roles'], $post['customer'])){
	$username = $post['staffName'];
	$staffName = $post['staffIc'];
	$roleCode = $post['roles'];
	$customer = $post['customer'];

    if(isset($post['userId']) && $post['userId'] != null && $post['userId'] != ''){
	    if ($update_stmt = $db->prepare("UPDATE users SET username=?, name=?, role_code=? WHERE id = ?")) {
            $update_stmt->bind_param('ssss', $username, $staffName, $roleCode, $post['userId']);
            
            // Execute the prepared query.
            if (! $update_stmt->execute()) {
                echo json_encode(
                    array(
                        "status" => "failed",
                        "message" => $update_stmt->error
                    )); 
            }
            else{
                echo json_encode(
    				array(
    					"status"=> "success", 
    					"message"=> "Updated Successfully!!",
    					"id" => $post['userId']
    				)
    			);
			}
		}
	}
	else{
	    $random_salt = hash('sha512', uniqid(openssl_random_pseudo_bytes(16), TRUE));
        $password = '123456';
        $password = hash('sha512', $password . $random_salt);
	    
	    if ($insert_stmt = $db->prepare("INSERT INTO users (username, name, password, salt, role_code, customer) VALUES (?, ?, ?, ?, ?, ?)")){	
    	    $insert_stmt->bind_param('ssssss', $username, $staffName, $password, $random_salt, $roleCode, $customer);		
    		// Execute the prepared query.
    		if (! $insert_stmt->execute()){
    			echo json_encode(
    				array(
    					"status"=> "failed", 
    					"message"=> $insert_stmt->error
    				)
    			);
    		} 
    		else{
    			$id = $insert_stmt->insert_id;
				$insert_stmt->close();
    			
    			echo json_encode(
    				array(
    					"status"=> "success", 
    					"message"=> "Added Successfully!!",
    					"id"=> $id
    				)
    			);
    		}
    
    		$db->close();
    	}
    	else{
    		echo json_encode(
    			array(
    				"status"=> "failed", 
    				"message"=> "cannot prepare statement"
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