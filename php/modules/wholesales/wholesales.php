<?php
require_once '../../db_connect.php';
require_once '../../lookup.php';
require_once '../../uploadFileHelper.php';
require_once '../../services/stockManagementService.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

function groupWeightDetails($weightDetails) {
    $grouped = [];
    foreach ($weightDetails as $detail) {
        $product = $detail['product'] ?? '';
        $gradeId = $detail['grade_id'] ?? '';
        $key = $product . '_' . $gradeId;

        if (isset($grouped[$key])) {
            $grouped[$key]['net'] += floatval($detail['net'] ?? 0);
        } else {
            $grouped[$key] = [
                'product' => $product,
                'grade_id' => $gradeId,
                'net' => floatval($detail['net'] ?? 0)
            ];
        }
    }
    return $grouped;
}

if(isset($_POST['status'], $_POST['startTime'])){
    // Company Data
    $runningNoType = 0;
    if ($company_stmt = $db->prepare("SELECT * FROM companies WHERE id = ?")) {
        $company_stmt->bind_param("s", $_SESSION['customer']);
        $company_stmt->execute();
        $result = $company_stmt->get_result();
        $companyData = $result->fetch_assoc();
        $company_stmt->close();
        $runningNoType = $companyData['running_no_type'] ?? 0;
    }

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
    $module = 'wholesale';
    $serialNo = null;
    $location = null;
    $remarks = null;
    $remarks2 = null;
    $category = null;
    $paymentMethod = null;

    $startDateTimeObj = DateTime::createFromFormat('d/m/Y H:i', $startTime);
    $startDateTime = $startDateTimeObj->format("d/m/Y 00:00:00");
    $startDateTime2 = $startDateTimeObj->format("Ymd");
    $startDateTime3 = $startDateTimeObj->format("Y-m-d H:i:s");
    $currentDateTimeObj = new DateTime();
	$year = $currentDateTimeObj->format("y");
	$yearMonth = $currentDateTimeObj->format("ym");

    if(isset($_POST['endTime']) && $_POST['endTime'] != null && $_POST['endTime'] != ''){
        $endTime = $_POST['endTime'];
        $endTimeObj = DateTime::createFromFormat('d/m/Y H:i', $endTime);
        $endDateTime = $endTimeObj->format("Y-m-d H:i:s");
    }

    if(isset($_POST['recordType']) && $_POST['recordType'] != null && $_POST['recordType'] != ''){
		$recordType = $_POST['recordType'];

        if ($recordType == 'wholesales'){
            $module = 'wholesale';
        }else{
            $module = $_POST['recordType'];
        }
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

    if(isset($_POST['doPoNo']) && $_POST['doPoNo'] != null && $_POST['doPoNo'] != ''){
        $doPoNo = $_POST['doPoNo'];
    }else{
        if ($runningNoType == 1){
            if ($status == 'RECEIVING' || $status == 'INCOMING') {
                // Use Supplier table
                $entityId = $_POST['supplier']; // supplier ID from form
                $entityType = 'Supplier';
                $entityTable = 'supplies';
                $invoiceCodeField = 'invoice_code';
            } else {
                // Use Customer table
                $entityId = $_POST['customer']; // customer ID from form
                $entityType = 'Customer';
                $entityTable = 'customers';
                $invoiceCodeField = 'invoice_code';
            }
            
            // Get prefix and value from running_no_entity table
            $runningNoPrefix = '';
            $curval = 1;
            $invoiceCode = '';
            $runningNoEntityExists = false;
            $stmt = $db->prepare("SELECT prefix, value FROM running_no_entity WHERE company_id = ? AND module = ? AND entity_id = ? AND transaction_status = ?");
            $stmt->bind_param('ssis', $company, $module, $entityId, $status);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $runningNoPrefix = $row['prefix'];
                $curval = (int)$row['value'];
                $runningNoEntityExists = true;
                
                // Get invoice_code from entity table
                $stmt2 = $db->prepare("SELECT invoice_code FROM $entityTable WHERE id = ? AND customer = ?");
                $stmt2->bind_param('ii', $entityId, $company);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                if ($r2 = $res2->fetch_assoc()) {
                    $invoiceCode = $r2['invoice_code'] ?? '';
                }
                $stmt2->close();
            } else {
                // Fallback: get default prefix from statuses table
                $stmt2 = $db->prepare("SELECT prefix FROM statuses WHERE module = ? AND status = ? AND entity_type = ?");
                $stmt2->bind_param('sss', $module, $status, $entityType);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                if ($r2 = $res2->fetch_assoc()) {
                    $runningNoPrefix = $r2['prefix'];
                }
                $stmt2->close();
                
                // Fallback: get customer_code or supplier_code if invoice_code is empty
                $codeField = ($entityType == 'Supplier') ? 'supplier_code' : 'customer_code';
                $stmt3 = $db->prepare("SELECT invoice_code, $codeField FROM $entityTable WHERE id = ? AND customer = ?");
                $stmt3->bind_param('ii', $entityId, $company);
                $stmt3->execute();
                $res3 = $stmt3->get_result();
                if ($r3 = $res3->fetch_assoc()) {
                    $invoiceCode = !empty($r3['invoice_code']) ? $r3['invoice_code'] : ($r3[$codeField] ?? '');
                }
                $stmt3->close();
            }
            $stmt->close();
            
            // Build doPoNo: [Prefix]-[Invoice Code]-[YYMM]/[Value]
            // e.g. D-INV-2506/001
            $paddedValue = str_pad($curval, 3, '0', STR_PAD_LEFT); // 001, 002, etc.
            
            if (!empty($invoiceCode)) {
                $doPoNo = $runningNoPrefix . '-' . $invoiceCode . '-' . $yearMonth . '/' . $paddedValue;
            } else {
                // Fallback if no invoice code set
                $doPoNo = $runningNoPrefix . '-' . $yearMonth . '/' . $paddedValue;
            }
        } else {
            // Original logic for runningNoType != 1
            $doPoNo = $year;
            if ($select_stmt2 = $db->prepare("SELECT * FROM running_no_setup WHERE module = ? AND company_id = ? AND transaction_status = ?")) {
                $select_stmt2->bind_param('sss', $recordType, $company, $status);
                
                if (! $select_stmt2->execute()) {
                    echo json_encode(
                        array(
                            "status" => "failed",
                            "message" => "Failed to get latest count"
                        )); 
                } else {
                    $result2 = $select_stmt2->get_result();
                    $count = 1;
                    
                    if ($row = $result2->fetch_assoc()) {
                        $doPoNo = $row['name'].$doPoNo;
                        $count = (int)$row['value'];
                        $curval = $count;
                    }

                    $charSize = strlen(strval($count));

                    for($i=0; $i<(6-(int)$charSize); $i++){
                        $doPoNo.='0';
                    }
            
                    $doPoNo .= strval($count);
                }
            }
            
            $select_stmt2->close();
        }
    }

    if(isset($_POST['vehicle']) && $_POST['vehicle'] != null && $_POST['vehicle'] != ''){
        if ($_POST['vehicle'] == 'UNKNOWN' || $_POST['vehicle'] == 'OTHERS'){
            if(isset($_POST['otherVehicleNo']) && $_POST['otherVehicleNo'] != null && $_POST['otherVehicleNo'] != ''){
                $vehicle = $_POST['otherVehicleNo'];
            }else{
                $vehicle = null;
            }
        }else{
            $vehicle = $_POST['vehicle'];
        }
	}else{
        if ($recordType == 'industrial'){
            $vehicle = "-";
        }
    }

    if(isset($_POST['driver']) && $_POST['driver'] != null && $_POST['driver'] != ''){
		$driver = $_POST['driver'];
	}

    if(isset($_POST['location']) && $_POST['location'] != null && $_POST['location'] != ''){
		$location = $_POST['location'];
	}

    if(isset($_POST['remarks']) && $_POST['remarks'] != null && $_POST['remarks'] != ''){
		$remarks = $_POST['remarks'];
	}

    if(isset($_POST['remarks2']) && $_POST['remarks2'] != null && $_POST['remarks2'] != ''){
		$remarks2 = $_POST['remarks2'];
	}

    if(isset($_POST['category']) && $_POST['category'] != null && $_POST['category'] != ''){
		$category = $_POST['category'];
	}

    if(isset($_POST['paymentMethod']) && $_POST['paymentMethod'] != null && $_POST['paymentMethod'] != ''){
		$paymentMethod = $_POST['paymentMethod'];
	}

    if(isset($_POST['weightDetails']) && $_POST['weightDetails'] != null && $_POST['weightDetails'] != ''){
		$data = $_POST['weightDetails'];
        foreach($data as $key => $weightDetail){
            $weightDetails[] = [
                'gross' => $weightDetail['gross'] ?? '',
                'tare' => $weightDetail['tare'] ?? '',
                'pretare' => $weightDetail['pretare'] ?? '0.0',
                'net' => $weightDetail['net'] ?? '',
                'variance' => $recordType == 'industrial' ? ($weightDetail['variance'] ?? '') : '',
                'varPerc' => $recordType == 'industrial' ? ($weightDetail['variancePerc'] ?? '') : '',
                'reject' => $weightDetail['reject'] ?? '',
                'isRejected' => $weightDetail['isRejected'] ?? 'N',
                'product' => $weightDetail['product'] ?? '',
                'product_name' => $weightDetail['product_name'] ?? '',
                'product_desc' => $weightDetail['product_desc'] ?? '',
                'price' => $weightDetail['price'] ?? '',
                'unit' => $weightDetail['unit'] ?? '',
                'package' => $weightDetail['package'] ?? '',
                'total' => $weightDetail['total'] ?? '',
                'before_discount' => $weightDetail['before_discount'] ?? '',
                'discount' => $weightDetail['discount'] ?? '0',
                'discount_type' => $weightDetail['discount_type'] ?? 'fixed',
                'fixedfloat' => $weightDetail['fixedfloat'] ?? '',
                'time' => $weightDetail['time'] ?? '',
                'grade' => $weightDetail['grade'] ?? '',
                'grade_id' => $weightDetail['grade_id'] ?? '',
                'currency' => $weightDetail['currency'] ?? '',
                'no_per_basket' => $weightDetail['no_basket'] ?? '',
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
                'before_discount' => $rejectDetail['before_discount'] ?? '',
                'discount' => $rejectDetail['discount'] ?? '0',
                'discount_type' => $rejectDetail['discount_type'] ?? 'fixed',
                'fixedfloat' => $rejectDetail['fixedfloat'] ?? '',
                'time' => $rejectDetail['time'] ?? '',
                'grade' => $rejectDetail['grade'] ?? '',
                'currency' => $rejectDetail['currency'] ?? '',
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
        if ($recordType == 'industrial') {
            $prefix = ($status == 'INCOMING') ? 'I' : 'O';
        } else {
            $prefix = ($status == 'DISPATCH') ? 'S' : (($status == 'RECEIVING') ? 'P' : (($status == 'NITROGEN') ? 'N' : (($status == 'REJECT') ? 'REJ' : 'SB')));
        }
		$serialNo = $prefix.$startDateTime2;

		if ($select_stmt = $db->prepare("SELECT COUNT(*) FROM wholesales WHERE created_datetime >= ? AND status = ? AND deleted='0' AND company = ? AND records_type = ?")) {
            $select_stmt->bind_param('ssss', $startDateTime, $status, $company, $record_type);
            
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
        // If Stock Management is enabled, process the stock changes based on the weight details
        if (in_array('stocks', $_SESSION['products'])) {
            // Query current record to get existing data
            if ($currentRecordStmt = $db->prepare("SELECT * FROM wholesales WHERE id = ?")){
                $currentRecordStmt->bind_param('s', $_POST['id']);
                $currentRecordStmt->execute();
                $result = $currentRecordStmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $existingWeights = json_decode($row['weight_details'], true);
                    $existingGroupedWeights = groupWeightDetails($existingWeights); 
                }

                $currentRecordStmt->close();
            }
        }

        if ($update_stmt = $db->prepare("UPDATE wholesales SET serial_no=?, po_no=?, security_bills=?, status=?, customer=?, other_customer=?, supplier=?, other_supplier=?, vehicle_no=?, driver=?, weight_details=?, reject_details=?, total_item=?, total_weight=?, total_reject=?, total_price=?, remark=?, remarks2=?, category=?, payment_method=?, start_time=?, end_time=?, modified_by=?, location=? WHERE id=?")){
            $weightDetailsJson = json_encode($weightDetails);
            $rejectDetailsJson = json_encode($rejectDetails);
            $update_stmt->bind_param('sssssssssssssssssssssssss', $serialNo, $doPoNo, $securityBillNo, $status, $customer, $customerOther, $supplier, $supplierOther, $vehicle, $driver, $weightDetailsJson, $rejectDetailsJson, $totalItem, $totalNet, $totalReject, $totalPrice, $remarks, $remarks2, $category, $paymentMethod, $startDateTime3, $endDateTime, $userID, $location, $_POST['id']);
            
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

                // If Stock Management is enabledand customer/supplier selected is normal, process the stock changes based on the weight details
                if (in_array('stocks', $_SESSION['products'])) {
                    // Query customer/supplier type to determine if stock management should be applied
                    $stockEnable = false;
                    if ($status == 'RECEIVING' || $status == 'INCOMING') {
                        $supplierDetails = getSupplierDetailsById($supplier, $db);
                        if ($supplierDetails && isset($supplierDetails['supplier_type']) && $supplierDetails['supplier_type'] == 'Normal') {
                            $stockEnable = true;
                        }
                    }else{
                        $customerDetails = getCustomerDetailsById($customer, $db);
                        if ($customerDetails && isset($customerDetails['customer_type']) && $customerDetails['customer_type'] == 'Normal') {
                            $stockEnable = true;
                        }
                    }

                    if ($stockEnable){
                        $productWeights = groupWeightDetails($weightDetails);
                        
                        foreach ($productWeights as $key => $productWeight){
                            $productId = $productWeight['product'];
                            $grade = $productWeight['grade_id'];
                            $afterValue = $productWeight['net'];
                            $beforeValue = $existingGroupedWeights[$key]['net'] ?? 0;

                            if (floatval($afterValue) == floatval($beforeValue)) continue;

                            processRawStock($db, $productId, $grade, $company, $afterValue, $userID, $status, true, $beforeValue, $_POST['id'], 'wholesales', $customer, $supplier);
                        }
                    }
                }

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
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM wholesales WHERE serial_no = ? AND company = ? AND deleted = '0'");
        $checkStmt->bind_param('ss', $serialNo, $company);
        $checkStmt->execute();
        $checkStmt->bind_result($serialExists);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($serialExists > 0) {
            echo json_encode(
                array(
                    "status" => "failed", 
                    "message" => "Serial No. '$serialNo' already exists."
                )
            );
            exit;
        }

        if ($insert_stmt = $db->prepare("INSERT INTO wholesales (serial_no, po_no, security_bills, status, customer, other_customer, supplier, other_supplier, vehicle_no, driver, weight_details, reject_details, total_item, total_weight, total_reject, total_price, remark, remarks2, category, payment_method, created_by, start_time, end_time, company, weighted_by, indicator, records_type, location) VALUES  (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")){
            $weightDetailsJson = json_encode($weightDetails);
            $rejectDetailsJson = json_encode($rejectDetails);
            $insert_stmt->bind_param('ssssssssssssssssssssssssssss', $serialNo, $doPoNo, $securityBillNo, $status, $customer, $customerOther, $supplier, $supplierOther, $vehicle, $driver, $weightDetailsJson, $rejectDetailsJson, $totalItem, $totalNet, $totalReject, $totalPrice, $remarks, $remarks2, $category, $paymentMethod, $userID, $startDateTime3, $endDateTime, $company, $userID, $indicator, $recordType, $location);
                        
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
                $wholesaleId = $insert_stmt->insert_id;
                $insert_stmt->close();

                // If Stock Management is enabled and customer/supplier selected is normal, process the stock changes based on the weight details
                if (in_array('stocks', $_SESSION['products'])) {
                    // Query customer/supplier type to determine if stock management should be applied
                    $stockEnable = false;
                    if ($status == 'RECEIVING' || $status == 'INCOMING') {
                        $supplierDetails = getSupplierDetailsById($supplier, $db);
                        if ($supplierDetails && isset($supplierDetails['supplier_type']) && $supplierDetails['supplier_type'] == 'Normal') {
                            $stockEnable = true;
                        }
                    }else{
                        $customerDetails = getCustomerDetailsById($customer, $db);
                        if ($customerDetails && isset($customerDetails['customer_type']) && $customerDetails['customer_type'] == 'Normal') {
                            $stockEnable = true;
                        }
                    }

                    if ($stockEnable){
                        $productWeights = groupWeightDetails($weightDetails);
                        foreach ($productWeights as $weight) {
                            $productId = $weight['product'];
                            $grade = $weight['grade_id'];
                            $nettWeight = $weight['net'];
                            processRawStock($db, $productId, $grade, $company, $nettWeight, $userID, $status, false, 0, $wholesaleId, 'wholesales', $customer, $supplier);
                        }
                    }
                }

                if(!isset($_POST['doPoNo']) || $_POST['doPoNo'] == null || $_POST['doPoNo'] == ''){
                    $curval = $curval + 1;
                    $curval = strval($curval);
                    
                    if($runningNoType == 1){
                        if($runningNoEntityExists){
                            // Update existing running_no_entity record
                            $stmtUS = $db->prepare("UPDATE running_no_entity SET value = ? WHERE company_id = ? AND module = ? AND entity_id = ? AND transaction_status = ?");
                            $stmtUS->bind_param('sssis', $curval, $company, $module, $entityId, $status);
                            $stmtUS->execute();
                            $stmtUS->close();
                        } else {
                            // Insert new running_no_entity record with prefix from statuses table
                            $stmtUS = $db->prepare("INSERT INTO running_no_entity (company_id, module, transaction_status, entity_id, prefix, value) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmtUS->bind_param('sssiss', $company, $module, $status, $entityId, $runningNoPrefix, $curval);
                            $stmtUS->execute();
                            $stmtUS->close();
                            
                            // Update entity table invoice_code to customer_code/supplier_code
                            $codeField = ($entityType == 'Supplier') ? 'supplier_code' : 'customer_code';
                            $stmtUE = $db->prepare("UPDATE $entityTable SET invoice_code = $codeField WHERE id = ? AND customer = ? AND (invoice_code IS NULL OR invoice_code = '')");
                            $stmtUE->bind_param('is', $entityId, $company);
                            $stmtUE->execute();
                            $stmtUE->close();
                        }
                    } else {
                        // Update running_no_setup table (original logic)
                        $stmtUS = $db->prepare("UPDATE running_no_setup SET value = ? WHERE module = ? AND company_id = ? AND transaction_status = ?");
                        $stmtUS->bind_param('ssss', $curval, $recordType, $company, $status);
                        $stmtUS->execute();
                        $stmtUS->close();
                    }
                }
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