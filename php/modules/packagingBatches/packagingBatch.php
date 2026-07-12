<?php
require_once '../../db_connect.php';
require_once '../../uploadFileHelper.php';
require_once '../../services/stockManagementService.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

function groupWeightDetails($weightDetails) {
    $grouped = [];
    foreach ($weightDetails as $detail) {
        $product = $detail['product'] ?? '';
        $grade = $detail['grade'] ?? '';
        $key = $product . '_' . $grade;

        if (isset($grouped[$key])) {
            $grouped[$key]['net'] += floatval($detail['net'] ?? 0);
        } else {
            $grouped[$key] = [
                'product' => $product,
                'grade' => $grade,
                'net' => floatval($detail['net'] ?? 0)
            ];
        }
    }
    return $grouped;
}

if(isset($_POST['packagingDate'], $_POST['location'])){
    $userID = $_SESSION['userID'];
    $company = $_SESSION['customer'];
    $packagingDate = filter_input(INPUT_POST, 'packagingDate', FILTER_SANITIZE_STRING);
    $remarks = null;
    $batchNo = null;
    $productionLines = null;
    $location = null;

    $packagingDateTimeObj = DateTime::createFromFormat('d/m/Y H:i', $packagingDate);
    $packagingDateTime = $packagingDateTimeObj->format("d/m/Y 00:00:00");
    $packagingDateTime2 = $packagingDateTimeObj->format("Ymd");
    $packagingDateTime3 = $packagingDateTimeObj->format("Y-m-d H:i:s");
    $currentDateTimeObj = new DateTime();
	$year = $currentDateTimeObj->format("y");

    if(isset($_POST['remarks']) && $_POST['remarks'] != null && $_POST['remarks'] != ''){
		$remarks = $_POST['remarks'];
	}

    if(isset($_POST['productionLines']) && $_POST['productionLines'] != null && $_POST['productionLines'] != ''){
		$productionLines = $_POST['productionLines'];
	}

    if(isset($_POST['location']) && $_POST['location'] != null && $_POST['location'] != ''){
		$location = $_POST['location'];
	}

    if(!isset($_POST['batchNo']) || $_POST['batchNo'] == null || $_POST['batchNo'] == ''){
		$batchNo = 'B'.$packagingDateTime2;

		if ($select_stmt = $db->prepare("SELECT COUNT(*) FROM packaging_batches WHERE created_date >= ? AND deleted='0' AND company = ?")) {
            $select_stmt->bind_param('ss', $packagingDateTime, $company);
            
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
                    $batchNo.='0';  // S0000
                }
        
                $batchNo .= strval($count);  //S00009
                
                // Check grading
                do {
                    // Generate the grading number
                    if ($select_stmt2 = $db->prepare("SELECT COUNT(*) FROM packaging_batches WHERE batch_no = ? AND company =?")) {
                        $select_stmt2->bind_param('ss', $batchNo, $company);
                        
                        // Execute the prepared query to check if the grading number exists
                        if (! $select_stmt2->execute()) {
                            break; // Exit the loop if there's an error
                        }
                        
                        $result = $select_stmt2->get_result();
                        $row = $result->fetch_assoc();
                        $existing_count = (int)$row['COUNT(*)'];
                        
                        if ($existing_count == 0) {
                            // If the grading number does not exist in the table, exit the loop
                            break;
                        }
                        
                        // If the grading number already exists, increment the count and generate a new grading number
                        $count++; // Increment the count
                        $charSize = strlen(strval($count));
                        $batchNo = 'B'.$packagingDateTime2;  // Reset the sergradingial number
                        
                        // Generate the new grading number
                        for($ind = 0; $ind < (4 - (int)$charSize); $ind++) {
                            $batchNo .= '0'; // Append leading zeros
                        }
                        $batchNo .= strval($count); // Append the count
                    }
                } while (true);
			}
		}
		
		$select_stmt->close();
	}
	else{
	    $batchNo = $_POST['batchNo'];
	}

    if(isset($_POST['id']) && $_POST['id'] != null && $_POST['id'] != ''){
        $batchId = $_POST['id'];

        if ($update_stmt = $db->prepare("UPDATE packaging_batches SET batch_no=?, packaging_date=?, location=?, production_line=?, remarks=?, modified_by=? WHERE id=?")){
            $update_stmt->bind_param('sssssss', $batchNo, $packagingDateTime3, $location, $productionLines, $remarks, $userID, $batchId);
            
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

                // Update packaging_batch_items
                if(isset($_POST['weightDetails']) && $_POST['weightDetails'] != null && $_POST['weightDetails'] != ''){
                    $data = $_POST['weightDetails'];

                    // Fetch existing items BEFORE soft-deleting, for stock reversal
                    if (in_array('stocks', $_SESSION['products'])) {
                        $prevStmt = $db->prepare("SELECT * FROM packaging_batch_items WHERE packaging_batch_id = ? AND deleted = 0");
                        $prevStmt->bind_param('s', $batchId);
                        $prevStmt->execute();
                        $prevItems = $prevStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        $prevStmt->close();
                    }

                    // Soft-delete old items
                    if ($delete_stmt = $db->prepare("UPDATE packaging_batch_items SET deleted='1' WHERE packaging_batch_id=?")){
                        $delete_stmt->bind_param('s', $batchId);
                        $delete_stmt->execute();
                        $delete_stmt->close();

                        foreach($data as $key => $weightDetail){
                            $time = date('Y-m-d') . ' ' . $weightDetail['time'];
                            if (isset($weightDetail['batchId']) && $weightDetail['batchId'] != null && $weightDetail['batchId'] != ''){
                                if ($update_stmt2 = $db->prepare("UPDATE packaging_batch_items SET category_id=?, product_id=?, grade=?, packaging_size=?, label=?, units_per_box=?, gross=?, tare=?, weight=?, packing_time=?, photo_path=?, deleted='0' WHERE id=?")){
                                    $update_stmt2->bind_param('ssssssssssss', $weightDetail['category'], $weightDetail['product'], $weightDetail['grade'], $weightDetail['packaging_size'], $weightDetail['label'], $weightDetail['unit_per_box'], $weightDetail['gross'], $weightDetail['tare'], $weightDetail['weight'], $time, $weightDetail['photoPath'], $weightDetail['batchId']);
                                    $update_stmt2->execute();
                                    $update_stmt2->close();
                                }
                            }else{
                                if ($insert_stmt2 = $db->prepare("INSERT INTO packaging_batch_items (packaging_batch_id, category_id, product_id, grade, packaging_size, label, units_per_box, gross, tare, weight, packing_time, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")){
                                    $insert_stmt2->bind_param('ssssssssssss', $batchId, $weightDetail['category'], $weightDetail['product'], $weightDetail['grade'], $weightDetail['packaging_size'], $weightDetail['label'], $weightDetail['unit_per_box'], $weightDetail['gross'], $weightDetail['tare'], $weightDetail['weight'], $time, $weightDetail['photoPath']);
                                    $insert_stmt2->execute();
                                    $insert_stmt2->close();
                                }
                            }
                        }

                        // Reverse previous + apply new stock in one call
                        if (in_array('stocks', $_SESSION['products'])) {
                            processPackagingBatch($db, $batchId, $company, $userID, 'EDIT', $data, $prevItems);
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
        if ($insert_stmt = $db->prepare("INSERT INTO packaging_batches (batch_no, packaging_date, location, production_line, remarks, company, created_by, status) VALUES  (?, ?, ?, ?, ?, ?, ?, 'pending')")){
            $insert_stmt->bind_param('sssssss', $batchNo, $packagingDateTime3, $location, $productionLines, $remarks, $company, $userID);
            
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
                $packagingBatchId = $insert_stmt->insert_id;
                $insert_stmt->close();

                # Insert into packaging_batch_items table
                if(isset($_POST['weightDetails']) && $_POST['weightDetails'] != null && $_POST['weightDetails'] != ''){
                    $data = $_POST['weightDetails'];
                    foreach($data as $key => $weightDetail){
                        $time = date('Y-m-d') . ' ' . $weightDetail['time'];
                        if ($insert_stmt2 = $db->prepare("INSERT INTO packaging_batch_items (packaging_batch_id, category_id, product_id, grade, packaging_size, label, units_per_box, gross, tare, weight, packing_time, photo_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')")){
                            $insert_stmt2->bind_param('ssssssssssss', $packagingBatchId, $weightDetail['category'], $weightDetail['product'], $weightDetail['grade'], $weightDetail['packaging_size'], $weightDetail['label'], $weightDetail['unit_per_box'], $weightDetail['gross'], $weightDetail['tare'], $weightDetail['weight'], $time, $weightDetail['photoPath']);
                            $insert_stmt2->execute();
                            $insert_stmt2->close();
                        }
                    }

                    // If Stock Management is enabled, process the stock changes based on the weight details
                    if (in_array('stocks', $_SESSION['products'])) {
                        processPackagingBatch($db, $packagingBatchId, $company, $userID, 'CREATE', $data);
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