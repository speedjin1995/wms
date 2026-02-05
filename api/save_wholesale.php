<?php
require_once 'db_connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
$post = json_decode(file_get_contents('php://input'), true);

$services = 'Save_Wholesales';
$requests = json_encode($post);

$stmtL = $db->prepare("INSERT INTO api_requests (services, request) VALUES (?, ?)");
$stmtL->bind_param('ss', $services, $requests);
$stmtL->execute();

if(isset($post['status'], $post['do_no'], $post['vehicleNumber'], $post['driverName'], $post['driverIc'], $post['startTime']
, $post['UID'], $post['company'], $post['indicator'], $post['totalItem'], $post['totalWeight'], $post['totalReject']
, $post['totalPrice'], $post['weightList'])){
    $wholesaleId = (isset($post['id']) && $post['id'] != '') ? $post['id'] : null;
    $isEdit = ($wholesaleId != null);
    
	$status = $post['status'];
	$do_no = $post['do_no'];
	$vehicleNumber = $post['vehicleNumber'];
	$driverName = $post['driverName'];
	$driverIc = $post['driverIc'];
	$UID = $post['UID'];
	$company = $post['company'];
	$indicator = $post['indicator'];
	$weightDetails = $post['weightList'];
	$rejectDetails = $post['rejectList'];
	$startTime = $post['startTime'];
	$endTime = $post['createdDatetime'];
	$totalItem = $post['totalItem'];
	$totalWeight = $post['totalWeight'];
	$totalReject = $post['totalReject'];
	$totalPrice = $post['totalPrice'];
	
	$currentDateTimeObj = new DateTime();
    $currentDateTime = $currentDateTimeObj->format("Y-m-d H:i:s");
	$startDateTimeObj = new DateTime($startTime);
    $startDateTime = $startDateTimeObj->format("Y-m-d 00:00:00");
    $startDateTime2 = $startDateTimeObj->format("Ymd");

    $security_bill = null;
    $customerName = null;
	$supplierName = null;
	$customerName2 = null;
	$supplierName2 = null;
	$remark = null;
	$checkedStaff = null;
	$serialNo = "";
	$today = date("Y-m-d 00:00:00");
	
	if($status == 'DISPATCH'){
	    if($post['customerName'] == 'OTHERS'){
			$customerName = $post['customerName'];
	        $customerName2 = $post['customerName2'];
	    }
		else{
		    $customerName = $post['customerName'];
		    $customerName2 = null;
		}
	}
	
	if($status == 'RECEIVING'){
		if($post['supplierName'] == 'OTHERS'){
			$supplierName = $post['supplierName'];
	        $supplierName2 = $post['supplierName2'];
	    }
		else{
		    $supplierName = $post['supplierName'];
		    $supplierName2 = null;
		}
	}
	
	if($post['vehicleNumber'] == 'OTHERS'){
        $vehicleNumber = $post['vehicleNumber2'];
    }
	else{
	    $vehicleNumber = $post['vehicleNumber'];
	}
	
	if($post['driverName'] == 'OTHERS'){
        $driverName = $post['driverName2'];
    }
	else{
	    $driverName = $post['driverName'];
	}
	
	if(isset($post['remark']) && $post['remark'] != null && $post['remark'] != ''){
		$remark = $post['remark'];
	}
	
	if(isset($post['security_bill']) && $post['security_bill'] != null && $post['security_bill'] != ''){
		$security_bill = $post['security_bill'];
	}
	
	if(isset($post['checkedStaff']) && $post['checkedStaff'] != null && $post['checkedStaff'] != ''){
		$checkedStaff = ($post['checkedStaff'] == "JACKY" ? "" : $post['checkedStaff']);
	}

	if(!isset($post['serialNo']) || $post['serialNo'] == null || $post['serialNo'] == ''){
		$prefix = ($status === 'DISPATCH') ? 'S' : (($status === 'RECEIVING') ? 'P' : 'SB');
		$serialNo = $prefix.$startDateTime2;

		if ($select_stmt = $db->prepare("SELECT COUNT(*) FROM wholesales WHERE created_datetime >= ? AND status = ? AND deleted='0'")) {
            $select_stmt->bind_param('ss', $startDateTime, $status);
            
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
                
                // Check serial
                do {
                    // Generate the serial number
                    if ($select_stmt2 = $db->prepare("SELECT COUNT(*) FROM wholesales WHERE serial_no = ?")) {
                        $select_stmt2->bind_param('s', $serialNo);
                        
                        // Execute the prepared query to check if the serial number exists
                        if (! $select_stmt2->execute()) {
                            break; // Exit the loop if there's an error
                        }
                        
                        $result = $select_stmt2->get_result();
                        $row = $result->fetch_assoc();
                        $existing_count = (int)$row['COUNT(*)'];
                        
                        if ($existing_count == 0) {
                            // If the serial number does not exist in the table, exit the loop
                            break;
                        }
                        
                        // If the serial number already exists, increment the count and generate a new serial number
                        $count++; // Increment the count
                        $charSize = strlen(strval($count));
                        $serialNo = 'S'.$startDateTime2; // Reset the serial number
                        
                        // Generate the new serial number
                        for($ind = 0; $ind < (4 - (int)$charSize); $ind++) {
                            $serialNo .= '0'; // Append leading zeros
                        }
                        $serialNo .= strval($count); // Append the count
                    }
                } while (true);
			}
		}
		
		$select_stmt->close();
	}
	else{
	    $serialNo = $post['serialNo'];
	}

    if ($isEdit) {
        if ($update_stmt = $db->prepare("UPDATE wholesales SET po_no = ?, security_bills = ?, status = ?, customer = ?, supplier = ?, vehicle_no = ?, driver = ?, driver_ic = ?, 
        weight_details = ?, reject_details = ?, remark = ?, end_time = ?, indicator = ?, other_customer = ?, other_supplier = ?, checked_by = ?, total_item = ?, total_weight = ?, 
        total_reject = ?, total_price = ? WHERE id = ?")) {
            $data  = json_encode($weightDetails);
            $data2 = json_encode($rejectDetails);
            $update_stmt->bind_param('ssssssssssssssssssssi', $do_no, $security_bill, $status, $customerName, $supplierName, $vehicleNumber, $driverName, $driverIc, $data, $data2, $remark,
            $endTime, $indicator, $customerName2, $supplierName2, $checkedStaff, $totalItem, $totalWeight, $totalReject, $totalPrice, $wholesaleId);
    
            if (!$update_stmt->execute()) {
                echo json_encode([
                    "status" => "failed",
                    "message" => $update_stmt->error
                ]);
            }
            else{
                echo json_encode([
                    "status"   => "success",
                    "message"  => "Updated Successfully",
                    "serialNo" => $serialNo,
                    "weightId" => $wholesaleId
                ]);
            }
        }
    }
    else{
        if ($insert_stmt = $db->prepare("INSERT INTO wholesales (serial_no, po_no, security_bills, status, customer, supplier, vehicle_no, driver, driver_ic, 
	    weight_details, reject_details, remark, created_datetime, created_by, end_time, company, weighted_by, indicator, other_customer, other_supplier, 
    	    checked_by, total_item, total_weight, total_reject, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")){
            $data = json_encode($weightDetails);
            $data2 = json_encode($rejectDetails);
    		$insert_stmt->bind_param('sssssssssssssssssssssssss', $serialNo, $do_no, $security_bill, $status, $customerName, $supplierName, $vehicleNumber, 
    		$driverName, $driverIc, $data, $data2, $remark, $startTime, $UID, $endTime, $company, $UID, $indicator, $customerName2, $supplierName2, $checkedStaff, 
    		$totalItem, $totalWeight, $totalReject, $totalPrice);		
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
    			$db->close();
    			
    			echo json_encode(
    				array(
    					"status"=> "success", 
    					"message"=> "Added Successfully!!",
    					"serialNo"=> $serialNo,
    					"weightId" => $id
    				)
    			);
    		}
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
