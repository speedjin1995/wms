<?php
require_once 'db_connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
$post = json_decode(file_get_contents('php://input'), true);

if(isset($post['do_no'], $post['customer'], $post['sizes'], $post['specs'], $post['gross'], $post['tare'], $post['net'], $post['company']
, $post['pre_tare'], $post['high'], $post['low'], $post['staffName'], $post['createdDatetime'], $post['indicator'])){

    $company = $post['company'];
	$do_no= $post['do_no'];
	$customer = $post['customer'];
	$sizes = $post['sizes'];
	$specs = $post['specs'];
	$gross= $post['gross'];
	$tare = $post['tare'];
	$net = $post['net'];
	$high = $post['high'];
	$low = $post['low'];
	$pre_tare = $post['pre_tare'];
	$staffName = $post['staffName'];
	$createdDatetime = $post['createdDatetime'];
	$indicator = $post['indicator'];
	$status = '0';
	$type = 'INDIVIDUAL';

	$pwo_no = null;
	$coil_no = null;
	$no_of_coil = null;
	$strip_no = null;
	$remark = null;
	$today = date("Y-m-d 00:00:00");
	
	if(isset($post['pwo_no']) && $post['pwo_no'] != null && $post['pwo_no'] != ''){
		$pwo_no = $post['pwo_no'];
	}
	
	if(isset($post['coil_no']) && $post['coil_no'] != null && $post['coil_no'] != ''){
		$coil_no = $post['coil_no'];
	}
	
	if(isset($post['no_of_coil']) && $post['no_of_coil'] != null && $post['no_of_coil'] != ''){
		$no_of_coil = $post['no_of_coil'];
	}
	
	if(isset($post['strip_no']) && $post['strip_no'] != null && $post['strip_no'] != ''){
		$strip_no = $post['strip_no'];
	}
	
	if(isset($post['remarks']) && $post['remarks'] != null && $post['remarks'] != ''){
		$remark = $post['remarks'];
	}

	if(!isset($post['serialNo']) || $post['serialNo'] == null || $post['serialNo'] == ''){
	    $serialNo = date("Ymd");

		if ($select_stmt = $db->prepare("SELECT COUNT(*) FROM packing WHERE created_datetime >= ? AND deleted = ?")) {
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

	if ($insert_stmt = $db->prepare("INSERT INTO packing (serial_no, packing_no, customer, spec, size, gross, tare, net, 
	pre_tare, remark, created_datetime, created_by, company, weighted_by, high, low, indicator, pwo_no, coil_no, 
	no_of_coil, strip_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")){	
	    $insert_stmt->bind_param('sssssssssssssssssssss', $serialNo, $do_no, $customer, $specs, $sizes, $gross, $tare, 
            $net, $pre_tare, $remark, $createdDatetime, $staffName, $company, $staffName, $high, $low, $indicator, $pwo_no, 
            $coil_no, $no_of_coil, $strip_no);	
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