<?php
require_once 'db_connect.php';
require_once 'uploadFileHelper.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

if(isset($_POST['status'], $_POST['startTime'])){
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    $startTime = filter_input(INPUT_POST, 'startTime', FILTER_SANITIZE_STRING);
    $doPoNo = null;
    $securityBillNo = null;
    $customer = null;
    $customerOther = null;
    $supplier = null;
    $supplierOther = null;
    $vehicle = null;
    $otherVehicleNo = null;
    $driver = null;
    $totalReject = 0.00;
    $weightDetails = [];
    $rejectDetails = [];
    $totalItem = 0;
    $totalNet = 0;
    $totalPrice = 0;
    $userID = $_SESSION['userID'];
    $company = $_SESSION['customer'];
    $indicator = 'web';
	$recordType = 'wholesales';
    $serialNo = null;
    $remarks = null;
    $remarks2 = null;

    $startDateTimeObj = DateTime::createFromFormat('d/m/Y H:i', $startTime);
    $startDateTime = $startDateTimeObj->format("d/m/Y 00:00:00");
    $startDateTime2 = $startDateTimeObj->format("Ymd");
    $startDateTime3 = $startDateTimeObj->format("Y-m-d H:i:s");

    if(isset($_POST['endTime']) && $_POST['endTime'] != null && $_POST['endTime'] != ''){
        $endTime = $_POST['endTime'];
        $endTimeObj = DateTime::createFromFormat('d/m/Y H:i', $endTime);
        $endDateTime = $endTimeObj->format("Y-m-d H:i:s");
    }

    if(isset($_POST['doPoNo']) && $_POST['doPoNo'] != null && $_POST['doPoNo'] != ''){
		$doPoNo = $_POST['doPoNo'];
	}

    if(isset($_POST['securityBillNo']) && $_POST['securityBillNo'] != null && $_POST['securityBillNo'] != ''){
		$securityBillNo = $_POST['securityBillNo'];
	}

    if(isset($_POST['customer']) && $_POST['customer'] != null && $_POST['customer'] != ''){
		$customer = $_POST['customer'];
	}

    if(isset($_POST['customerOther']) && $_POST['customerOther'] != null && $_POST['customerOther'] != ''){
		$customerOther = $_POST['customerOther'];
	}

    if(isset($_POST['supplier']) && $_POST['supplier'] != null && $_POST['supplier'] != ''){
		$supplier = $_POST['supplier'];
	}

    if(isset($_POST['supplierOther']) && $_POST['supplierOther'] != null && $_POST['supplierOther'] != ''){
		$supplierOther = $_POST['supplierOther'];
	}

    if(isset($_POST['vehicle']) && $_POST['vehicle'] != null && $_POST['vehicle'] != ''){
        if ($_POST['vehicle'] == 'UNKNOWN'){
            if(isset($_POST['otherVehicleNo']) && $_POST['otherVehicleNo'] != null && $_POST['otherVehicleNo'] != ''){
                $vehicle = $_POST['otherVehicleNo'];
            }else{
                $vehicle = null;
            }
        }else{
            $vehicle = $_POST['vehicle'];
        }
	}

    if(isset($_POST['driver']) && $_POST['driver'] != null && $_POST['driver'] != ''){
		$driver = $_POST['driver'];
	}

    if(isset($_POST['remarks']) && $_POST['remarks'] != null && $_POST['remarks'] != ''){
		$remarks = $_POST['remarks'];
	}

    if(isset($_POST['remarks2']) && $_POST['remarks2'] != null && $_POST['remarks2'] != ''){
		$remarks2 = $_POST['remarks2'];
	}

    if(isset($_POST['weightDetails']) && $_POST['weightDetails'] != null && $_POST['weightDetails'] != ''){
		$data = $_POST['weightDetails'];
        foreach($data as $key => $weightDetail){
            $weightDetails[] = [
                'gross' => $weightDetail['gross'] ?? '',
                'tare' => $weightDetail['tare'] ?? '',
                'pretare' => $weightDetail['pretare'] ?? '0.0',
                'net' => $weightDetail['net'] ?? '',
                'reject' => $weightDetail['reject'] ?? '',
                'isRejected' => $weightDetail['isRejected'] ?? 'N',
                'product' => $weightDetail['product'] ?? '',
                'product_name' => $weightDetail['product_name'] ?? '',
                'product_desc' => $weightDetail['product_desc'] ?? '',
                'price' => $weightDetail['price'] ?? '',
                'unit' => $weightDetail['unit'] ?? '',
                'package' => $weightDetail['package'] ?? '',
                'total' => $weightDetail['total'] ?? '',
                'fixedfloat' => $weightDetail['fixedfloat'] ?? '',
                'time' => $weightDetail['time'] ?? '',
                'grade' => $weightDetail['grade'] ?? '',
                'isedit' => $weightDetail['isedit'] ?? 'N',
                'photoPath' => (function() use ($key, $db, $company) {
                    if (isset($_FILES['photoFiles']['name'][$key]) && $_FILES['photoFiles']['error'][$key] === UPLOAD_ERR_OK) {
                        $oldPhoto = $_POST['weightDetails'][$key]['photoPath'] ?? '';
                        if ($oldPhoto) {
                            deleteOldFile($oldPhoto, $db, 'photoPath');
                        }
                        $f = [
                            'name' => $_FILES['photoFiles']['name'][$key],
                            'tmp_name' => $_FILES['photoFiles']['tmp_name'][$key],
                            'size' => $_FILES['photoFiles']['size'][$key],
                            'type' => $_FILES['photoFiles']['type'][$key],
                            'error' => $_FILES['photoFiles']['error'][$key],
                        ];
                        $result = uploadFile($f, 'photo', $company, $db, 'photoPath');
                        if ($result['status'] === 'success' && $result['fid']) {
                            return (string)$result['fid'];
                        }
                    }
                    return $_POST['weightDetails'][$key]['photoPath'] ?? '';
                })(),
            ];

            $totalItem++;
            $totalNet += floatval($weightDetail['net'] ?? 0.0);
            $totalPrice += floatval($weightDetail['price'] ?? 0.0);
        }
    }

    if(isset($_POST['rejectDetails']) && $_POST['rejectDetails'] != null && $_POST['rejectDetails'] != ''){
		$data = $_POST['rejectDetails'];
        foreach($data as $key => $rejectDetail){
            $rejectDetails[] = [
                'gross' => $rejectDetail['gross'] ?? '',
                'tare' => $rejectDetail['tare'] ?? '',
                'pretare' => $rejectDetail['pretare'] ?? '0.0',
                'net' => $rejectDetail['net'] ?? '',
                'reject' => $rejectDetail['reject'] ?? '',
                'isRejected' => $rejectDetail['isRejected'] ?? 'N',
                'product' => $rejectDetail['product'] ?? '',
                'product_name' => $rejectDetail['product_name'] ?? '',
                'product_desc' => $rejectDetail['product_desc'] ?? '',
                'price' => $rejectDetail['price'] ?? '',
                'unit' => $rejectDetail['unit'] ?? '',
                'package' => $rejectDetail['package'] ?? '',
                'total' => $rejectDetail['total'] ?? '',
                'fixedfloat' => $rejectDetail['fixedfloat'] ?? '',
                'time' => $rejectDetail['time'] ?? '',
                'grade' => $rejectDetail['grade'] ?? '',
                'isedit' => $rejectDetail['isedit'] ?? 'N',
                'photoPath' => (function() use ($key, $db, $company) {
                    if (isset($_FILES['rejectPhotoFiles']['name'][$key]) && $_FILES['rejectPhotoFiles']['error'][$key] === UPLOAD_ERR_OK) {
                        $oldPhoto = $_POST['rejectDetails'][$key]['photoPath'] ?? '';
                        if ($oldPhoto) {
                            deleteOldFile($oldPhoto, $db, 'photoPath');
                        }
                        $f = [
                            'name' => $_FILES['rejectPhotoFiles']['name'][$key],
                            'tmp_name' => $_FILES['rejectPhotoFiles']['tmp_name'][$key],
                            'size' => $_FILES['rejectPhotoFiles']['size'][$key],
                            'type' => $_FILES['rejectPhotoFiles']['type'][$key],
                            'error' => $_FILES['rejectPhotoFiles']['error'][$key],
                        ];
                        $result = uploadFile($f, 'photo', $company, $db, 'photoPath');
                        if ($result['status'] === 'success' && $result['fid']) {
                            return (string)$result['fid'];
                        }
                    }
                    return $_POST['rejectDetails'][$key]['photoPath'] ?? '';
                })(),
            ];

            $totalReject += floatval($rejectDetail['net'] ?? 0.0);
        }
    }

    if(!isset($_POST['serialNo']) || $_POST['serialNo'] == null || $_POST['serialNo'] == ''){
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
                        $serialNo = $prefix.$startDateTime2;  // Reset the serial number
                        
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
	    $serialNo = $_POST['serialNo'];
	}

    if(isset($_POST['id']) && $_POST['id'] != null && $_POST['id'] != ''){
        if ($update_stmt = $db->prepare("UPDATE wholesales SET serial_no=?, po_no=?, security_bills=?, status=?, customer=?, other_customer=?, supplier=?, other_supplier=?, vehicle_no=?, driver=?, weight_details=?, reject_details=?, total_item=?, total_weight=?, total_reject=?, total_price=?, remark=?, remarks2=?, end_time=?, modified_by=? WHERE id=?")){
            $weightDetailsJson = json_encode($weightDetails);
            $rejectDetailsJson = json_encode($rejectDetails);
            $update_stmt->bind_param('sssssssssssssssssssss', $serialNo, $doPoNo, $securityBillNo, $status, $customer, $customerOther, $supplier, $supplierOther, $vehicle, $driver, $weightDetailsJson, $rejectDetailsJson, $totalItem, $totalNet, $totalReject, $totalPrice, $remarks, $remarks2, $endDateTime, $userID, $_POST['id']);
            
            // Execute the prepared query.
            if (! $update_stmt->execute()){
                echo json_encode(
                    array(
                        "status"=> "failed", 
                        "message"=> $update_stmt->error
                    )
                );

            } 
            else{
                $update_stmt->close();
                $db->close();
                
                echo json_encode(
                    array(
                        "status"=> "success", 
                        "message"=> "Updated Successfully!!" 
                    )
                );
            }
        } else{

            echo json_encode(
                array(
                    "status"=> "failed", 
                    "message"=> $update_stmt->error
                )
            );
        }
    
    }
    else{
        if ($insert_stmt = $db->prepare("INSERT INTO wholesales (serial_no, po_no, security_bills, status, customer, other_customer, supplier, other_supplier, vehicle_no, driver, weight_details, reject_details, total_item, total_weight, total_reject, total_price, remark, remarks2, created_datetime, created_by, end_time, checked_by, company, weighted_by, indicator, records_type) VALUES  (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")){
            $weightDetailsJson = json_encode($weightDetails);
            $rejectDetailsJson = json_encode($rejectDetails);
            $insert_stmt->bind_param('ssssssssssssssssssssssssss', $serialNo, $doPoNo, $securityBillNo, $status, $customer, $customerOther, $supplier, $supplierOther, $vehicle, $driver, $weightDetailsJson, $rejectDetailsJson, $totalItem, $totalNet, $totalReject, $totalPrice, $remarks, $remarks2, $startDateTime3, $userID, $endDateTime, $userID, $company, $userID, $indicator, $recordType);
                        
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
                $db->close();
                
                echo json_encode(
                    array(
                        "status"=> "success", 
                        "message"=> "Added Successfully!!" 
                    )
                );

            }
        } 
        else{
            echo json_encode(
                array(
                    "status"=> "failed", 
                    "message"=> "Failed to prepare statement"
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