<?php
require_once 'db_connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
$json_data = json_decode(file_get_contents('php://input'), true);
$today = date("Y-m-d 00:00:00");

if ($json_data === null) {
    echo json_encode(
        array(
            "status"=> "failed", 
            "message"=> "Please fill in all the fields"
        )
    );  
} 
else {
    $i = 0;
    $serialBatchNo = 'B'.$today;
    
    // Prepare and execute a query to get the highest existing serial number
    if ($select_stmt = $db->prepare("SELECT COUNT(DISTINCT(batch_serial)) FROM weighing WHERE type = 'BATCH'")) {
        //$select_stmt->bind_param('ss', $today, $status);
        
        // Execute the prepared query.
        if (! $select_stmt->execute()) {
            echo json_encode(
                array(
                    "status" => "failed",
                    "message" => "Failed to get latest count"
                )
            ); 
        } else {
            $result = $select_stmt->get_result();
            $count = 1;
            
            if ($row = $result->fetch_assoc()) {
                // Get the current count
                $count = (int)$row['COUNT(DISTINCT(batch_serial))'] + 1;
            }
    
            // Format the count to have leading zeros
            $serialBatchNo = 'B' . date('Ymd') . str_pad($count, 3, '0', STR_PAD_LEFT);
    
            // Now $serialBatchNo contains the formatted serial batch number
        }
    }
    
    foreach ($json_data as $post) {
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
    	$indicator = $post['indicator'];
    	$staffName = $post['staffName'];
    	$location = $post['location'];
    	$createdDatetime = $post['createdDatetime'];
    	$status = '0';
    	$stype = 'BATCH';
    
    	$do_no = null;
    	$remark = null;
    	
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
    	pre_tare, remark, created_datetime, created_by, company, weighted_by, locations, high, low, type, batch_serial, indicator) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")){	
    	    $insert_stmt->bind_param('ssssssssssssssssssss', $serialNo, $do_no, $product, $productDesc, $units, $gross, $tare, 
    		$net, $pre_tare, $remark, $createdDatetime, $staffName, $company, $staffName, $location, $high, $low, $stype, $serialBatchNo, $indicator);		
    		$insert_stmt->execute();
    	}
    }
    
    $select_stmt->close();
    $insert_stmt->close();
    $db->close();
    
    echo json_encode(
		array(
			"status"=> "success", 
			"message"=> "Added Successfully!!",
			"serialNo"=> $serialBatchNo
		)
	);
}


?>