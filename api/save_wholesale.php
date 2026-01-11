<?php
require_once 'db_connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
$post = json_decode(file_get_contents('php://input'), true);

if(isset($post['product'], $post['productCode'], $post['units'], $post['gross'], $post['tare'], $post['net'], $post['company']
, $post['pre_tare'], $post['high'], $post['low'], $post['staffName'], $post['location'], $post['createdDatetime'], $post['indicator'])){

    $company = $post['company'];
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
	$indicator = $post['indicator'];
	$status = '0';
	$type = 'INDIVIDUAL';

	$do_no = null;
	$remark = null;
	$today = date("Y-m-d 00:00:00");
	
	if(isset($post['do_no']) && $post['do_no'] != null && $post['do_no'] != ''){
		$do_no = $post['do_no'];
	}
	
	if(isset($post['remarks']) && $post['remarks'] != null && $post['remarks'] != ''){
		$remark = $post['remarks'];
	}

	if(!isset($post['serialNo']) || $post['serialNo'] == null || $post['serialNo'] == ''){
	    $serialNo = date("Ymd");

		if ($select_stmt = $db->prepare("SELECT COUNT(*) FROM weighing WHERE created_datetime >= ? AND deleted = ? AND type = 'INDIVIDUAL'")) {
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

	if ($insert_stmt = $db->prepare("INSERT INTO weighing (serial_no, po_no, product, product_desc, units, gross, tare, net, 
	pre_tare, remark, created_datetime, created_by, company, weighted_by, locations, high, low, type, indicator) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")){	
	    $insert_stmt->bind_param('sssssssssssssssssss', $serialNo, $do_no, $product, $productDesc, $units, $gross, $tare, 
            $net, $pre_tare, $remark, $createdDatetime, $staffName, $company, $staffName, $location, $high, $low, $type, $indicator);	
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