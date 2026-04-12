<?php
require_once 'db_connect.php';

if(isset($_POST['id'], $_POST['regNo'], $_POST['name'], $_POST['address1'])){
	$regNo = filter_input(INPUT_POST, 'regNo', FILTER_SANITIZE_STRING);
	$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
	$address = filter_input(INPUT_POST, 'address1', FILTER_SANITIZE_STRING);
	$address2 = null;
	$address3 = null;
	$address4 = null;
	$phone = null;
	$email = null;
	$fax = null;
	$photoUploadMode = 'local';
	$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);

	if($_POST['address2'] != null && $_POST['address2'] != ""){
		$address2 = filter_input(INPUT_POST, 'address2', FILTER_SANITIZE_STRING);
	}

	if($_POST['address3'] != null && $_POST['address3'] != ""){
		$address3 = filter_input(INPUT_POST, 'address3', FILTER_SANITIZE_STRING);
	}

	if($_POST['address4'] != null && $_POST['address4'] != ""){
		$address4 = filter_input(INPUT_POST, 'address4', FILTER_SANITIZE_STRING);
	}

	if($_POST['phone'] != null && $_POST['phone'] != ""){
		$phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
	}
	
	if($_POST['email'] != null && $_POST['email'] != ""){
		$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_STRING);
	}

	if($_POST['fax'] != null && $_POST['fax'] != ""){
		$fax = filter_input(INPUT_POST, 'fax', FILTER_SANITIZE_STRING);
	} 

	if($_POST['includePrice'] != null && $_POST['includePrice'] != ""){
		$includePrice = filter_input(INPUT_POST, 'includePrice', FILTER_SANITIZE_STRING);
	} 

	if($_POST['includePhoto'] != null && $_POST['includePhoto'] != ""){
		$includePhoto = filter_input(INPUT_POST, 'includePhoto', FILTER_SANITIZE_STRING);
	} 

	if($_POST['includeBarcode'] != null && $_POST['includeBarcode'] != ""){
		$includeBarcode = filter_input(INPUT_POST, 'includeBarcode', FILTER_SANITIZE_STRING);
	} 

	if($_POST['includeSecRemark'] != null && $_POST['includeSecRemark'] != ""){
		$includeSecRemark = filter_input(INPUT_POST, 'includeSecRemark', FILTER_SANITIZE_STRING);
	}

	if(isset($_POST['photoUploadMode']) && $_POST['photoUploadMode'] != ""){
		$photoUploadMode = filter_input(INPUT_POST, 'photoUploadMode', FILTER_SANITIZE_STRING);
	}
	
	if ($stmt2 = $db->prepare("UPDATE companies SET reg_no=?, name=?, address=?, address2=?, address3=?, address4=?, phone=?, email=?, fax=?, include_price=?, include_photo=?, include_barcode=?, include_sec_remark=?, photo_upload_mode=? WHERE id=?")) {
		$stmt2->bind_param('sssssssssssssss', $regNo, $name, $address, $address2, $address3, $address4, $phone, $email, $fax, $includePrice, $includePhoto, $includeBarcode, $includeSecRemark, $photoUploadMode, $id);
		
		if($stmt2->execute()){
			$stmt2->close();
			$db->close();
			
			echo json_encode(
				array(
					"status"=> "success", 
					"message"=> "Your company profile is updated successfully!" 
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
