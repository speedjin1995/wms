<?php
require_once 'db_connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
$post = json_decode(file_get_contents('php://input'), true);

if(isset($post['product'], $post['productCode'], $post['units'], $post['gross'], $post['tare'], $post['net']
, $post['pre_tare'], $post['high'], $post['low'], $post['staffName'], $post['location'], $post['createdDatetime'])){

	$product = $post['product'];
	$productDesc = $post['productCode'];
	$units = $post['units'];
	$gross= $post['gross'];
	$tare = $post['tare'];
	$net = $post['net'];
	$high = $post['high'];
	$low = $post['low'];
	$pre_tare = $post['pre_tare'];
	$staffName = $post['staffName'];
	$location = $post['location'];
	$createdDatetime = $post['createdDatetime'];
	$status = '0';

	$remark = null;
	$today = date("Y-m-d 00:00:00");
	
	if(isset($post['remarks']) && $post['remarks'] != null && $post['remarks'] != ''){
		$remark = $post['remarks'];
	}

	if(!isset($post['serialNo']) || $post['serialNo'] == null || $post['serialNo'] == ''){
	    $serialNo = date("Ymd");

		if ($select_stmt = $db->prepare("SELECT COUNT(*) FROM weighing WHERE created_datetime >= ? AND deleted = ?")) {
            $select_stmt->bind_param('ss', $today, $status);
            
            // Execute the prepared query.
            if (! $select_stmt->execute()) {
                echo json_encode(
                    array(
                        "status" => "failed",
                        "message" => "Failed to get latest count"
                    )); 
            }
            else{
                $result = $select_stmt->get_result();
                $count = 1;
                
                if ($row = $result->fetch_assoc()) {
                    $count = (int)$row['COUNT(*)'] + 1;
                    $select_stmt->close();
                }

                $charSize = strlen(strval($count));

                for($i=0; $i<(4-(int)$charSize); $i++){
                    $serialNo.='0';  // S0000
                }
        
                $serialNo .= strval($count);  //S00009
			}
		}
	}

	if ($insert_stmt = $db->prepare("INSERT INTO weighing (serial_no, product, product_desc, units, gross, tare, net, 
	pre_tare, remark, created_datetime, created_by, weighted_by, locations, high, low) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")){	
	    $insert_stmt->bind_param('sssssssssssssss', $serialNo, $product, $productDesc, $units, $gross, $tare, 
		$net, $pre_tare, $remark, $createdDatetime, $staffName, $staffName, $location, $high, $low);		
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
			$insert_stmt->close();
			
			echo json_encode(
				array(
					"status"=> "success", 
					"message"=> "Added Successfully!!",
					"serialNo"=> $serialNo
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
else{
    echo json_encode(
        array(
            "status"=> "failed", 
            "message"=> "Please fill in all the fields"
        )
    );     
}
?>