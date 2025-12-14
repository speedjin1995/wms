<?php
require_once 'db_connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
$post = json_decode(file_get_contents('php://input'), true);

if(isset($post['staffName'], $post['customer'], $post['address'], $post['phone'])){
	$staffName = $post['staffName'];
	$customer = $post['customer'];
	$address = $post['address'];
	$phone = $post['phone'];
	
	$regNo = null;
	$address2 = null;
	$address3 = null;
	$address4 = null;
	$email = null;
	
	if(isset($post['regNo']) && $post['regNo'] != null && $post['regNo'] != ''){
	    $regNo = $post['regNo'];
	}
	
	if(isset($post['address2']) && $post['address2'] != null && $post['address2'] != ''){
	    $address2 = $post['address2'];
	}
	
	if(isset($post['address3']) && $post['address3'] != null && $post['address3'] != ''){
	    $address3 = $post['address3'];
	}
	
	if(isset($post['address4']) && $post['address4'] != null && $post['address4'] != ''){
	    $address4 = $post['address4'];
	}
	
	if(isset($post['email']) && $post['email'] != null && $post['email'] != ''){
	    $email = $post['email'];
	}

	if(isset($post['userId']) && $post['userId'] != null && $post['userId'] != ''){
	    if ($update_stmt = $db->prepare("UPDATE supplies SET reg_no = ?, supplier_name = ?, supplier_address = ?, supplier_address2 = ?, supplier_address3 = ?, supplier_address4 = ?, supplier_phone = ?, pic = ? WHERE id = ?")) {
            $update_stmt->bind_param('sssssssss', $regNo, $staffName, $address, $address2, $address3, $address4, $phone, $email, $post['userId']);
            
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
	    if ($insert_stmt = $db->prepare("INSERT INTO supplies (reg_no, supplier_name, supplier_address, supplier_address2, supplier_address3, supplier_address4, supplier_phone, pic, customer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")){	
    	    $insert_stmt->bind_param('sssssssss', $regNo, $staffName, $address, $address2, $address3, $address4, $phone, $email, $customer);		
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