<?php
require_once '../../db_connect.php';
require_once '../../uploadFileHelper.php';
require_once '../../lookup.php';
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

if(isset($_POST['startTime'])){
    $userID = $_SESSION['userID'];
    $company = $_SESSION['customer'];
    $startTime = filter_input(INPUT_POST, 'startTime', FILTER_SANITIZE_STRING);
    $endTime = null;
    $remarks = null;
    $gradingNo = null;
    $category = null;
    $location = null;

    $startDateTimeObj = DateTime::createFromFormat('d/m/Y H:i', $startTime);
    $startDateTime = $startDateTimeObj->format("d/m/Y 00:00:00");
    $startDateTime2 = $startDateTimeObj->format("Ymd");
    $startDateTime3 = $startDateTimeObj->format("Y-m-d H:i:s");
    $currentDateTimeObj = new DateTime();
	$year = $currentDateTimeObj->format("y");

    if(isset($_POST['endTime']) && $_POST['endTime'] != null && $_POST['endTime'] != ''){
        $endTime = $_POST['endTime'];
        $endTimeObj = DateTime::createFromFormat('d/m/Y H:i', $endTime);
        $endDateTime = $endTimeObj->format("Y-m-d H:i:s");
    }

    if(isset($_POST['remarks']) && $_POST['remarks'] != null && $_POST['remarks'] != ''){
		$remarks = $_POST['remarks'];
	}

    if(isset($_POST['category']) && $_POST['category'] != null && $_POST['category'] != ''){
		$category = $_POST['category'];
	}

    if(isset($_POST['location']) && $_POST['location'] != null && $_POST['location'] != ''){
		$location = $_POST['location'];
	}

    if(!isset($_POST['gradingNo']) || $_POST['gradingNo'] == null || $_POST['gradingNo'] == ''){
		$gradingNo = 'G'.$startDateTime2;

		if ($select_stmt = $db->prepare("SELECT COUNT(*) FROM grading WHERE created_date >= ? AND deleted='0' AND company = ?")) {
            $select_stmt->bind_param('ss', $startDateTime, $company);
            
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
                    $gradingNo.='0';  // S0000
                }
        
                $gradingNo .= strval($count);  //S00009
                
                // Check grading
                do {
                    // Generate the grading number
                    if ($select_stmt2 = $db->prepare("SELECT COUNT(*) FROM grading WHERE grading_no = ? AND company =?")) {
                        $select_stmt2->bind_param('ss', $gradingNo, $company);
                        
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
                        $gradingNo = 'G'.$startDateTime2;  // Reset the sergradingial number
                        
                        // Generate the new grading number
                        for($ind = 0; $ind < (4 - (int)$charSize); $ind++) {
                            $gradingNo .= '0'; // Append leading zeros
                        }
                        $gradingNo .= strval($count); // Append the count
                    }
                } while (true);
			}
		}
		
		$select_stmt->close();
	}
	else{
	    $gradingNo = $_POST['gradingNo'];
	}

    if(isset($_POST['id']) && $_POST['id'] != null && $_POST['id'] != ''){
        // If Stock Management is enabled, process the stock changes based on the weight details
        // if (in_array('processing', $_SESSION['products'])) {
        //     // Query current record to get existing data
        //     if ($currentRecordStmt = $db->prepare("SELECT * FROM wholesales WHERE id = ?")){
        //         $currentRecordStmt->bind_param('s', $_POST['id']);
        //         $currentRecordStmt->execute();
        //         $result = $currentRecordStmt->get_result();

        //         if ($row = $result->fetch_assoc()) {
        //             $existingWeights = json_decode($row['weight_details'], true);
        //             $existingGroupedWeights = groupWeightDetails($existingWeights); 
        //         }

        //         $currentRecordStmt->close();
        //     }
        // }

        if ($update_stmt = $db->prepare("UPDATE grading SET grading_no=?, location=?, start_date=?, end_date=?, product_category=?, remark=?, modified_by=? WHERE id=?")){
            $update_stmt->bind_param('ssssssss', $gradingNo, $location, $startDateTime3, $endDateTime, $category, $remarks, $userID, $_POST['id']);
            
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

                // Update grading_items
                if(isset($_POST['weightDetails']) && $_POST['weightDetails'] != null && $_POST['weightDetails'] != ''){
                    $data = $_POST['weightDetails'];

                    // Update grading_items deleted to 0 first
                    if ($delete_stmt = $db->prepare("UPDATE grading_items SET deleted='1' WHERE grading_id=? AND to_grade <> 'REJ'")){
                        $delete_stmt->bind_param('s', $_POST['id']);
                        $delete_stmt->execute();
                        $delete_stmt->close();

                        foreach($data as $key => $weightDetail){
                            $time = date('Y-m-d') . ' ' . $weightDetail['time'];
                            $toGrade = $weightDetail['to_grade'] ?? '';
                            if (isset($weightDetail['gradingItemId']) && $weightDetail['gradingItemId'] != null && $weightDetail['gradingItemId'] != ''){
                                if ($update_stmt2 = $db->prepare("UPDATE grading_items SET product_id=?, to_grade=?, gross_weight=?, tare_weight=?, nett_weight=?, weighing_time=?, photo_path=?, deleted='0' WHERE id=?")){
                                    $update_stmt2->bind_param('ssssssss', $weightDetail['product'], $toGrade, $weightDetail['gross'], $weightDetail['tare'], $weightDetail['net'], $time, $weightDetail['photoPath'], $weightDetail['gradingItemId']);
                                    $update_stmt2->execute();
                                    $update_stmt2->close();
                                }
                            }else{
                                if ($insert_stmt2 = $db->prepare("INSERT INTO grading_items (grading_id, product_id, to_grade, gross_weight, tare_weight, nett_weight, weighing_time, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")){
                                    $insert_stmt2->bind_param('ssssssss', $_POST['id'], $weightDetail['product'], $toGrade, $weightDetail['gross'], $weightDetail['tare'], $weightDetail['net'], $time, $weightDetail['photoPath']);
                                    $insert_stmt2->execute();
                                    $insert_stmt2->close();
                                }
                            }
                        }
                    }
                }

                if(isset($_POST['rejectDetails']) && $_POST['rejectDetails'] != null && $_POST['rejectDetails'] != ''){
                    $rejectData = $_POST['rejectDetails'];

                    // Update grading_items deleted for REJ items
                    if ($delete_stmt = $db->prepare("UPDATE grading_items SET deleted='1' WHERE grading_id=? AND to_grade = 'REJ'")){
                        $delete_stmt->bind_param('s', $_POST['id']);
                        $delete_stmt->execute();
                        $delete_stmt->close();

                        foreach($rejectData as $key => $rejectDetail){
                            $rejectTime = date('Y-m-d') . ' ' . $rejectDetail['time'];
                            if (isset($rejectDetail['gradingItemId']) && $rejectDetail['gradingItemId'] != null && $rejectDetail['gradingItemId'] != ''){
                                if ($update_stmt3 = $db->prepare("UPDATE grading_items SET product_id=?, to_grade=?, gross_weight=?, tare_weight=?, nett_weight=?, weighing_time=?, photo_path=?, deleted='0' WHERE id=?")){
                                    $update_stmt3->bind_param('ssssssss', $rejectDetail['product'], $rejectDetail['grade'], $rejectDetail['gross'], $rejectDetail['tare'], $rejectDetail['net'], $rejectTime, $rejectDetail['photoPath'], $rejectDetail['gradingItemId']);
                                    $update_stmt3->execute();
                                    $update_stmt3->close();
                                }
                            }else{
                                if ($insert_stmt3 = $db->prepare("INSERT INTO grading_items (grading_id, product_id, to_grade, gross_weight, tare_weight, nett_weight, weighing_time, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")){
                                    $insert_stmt3->bind_param('ssssssss', $_POST['id'], $rejectDetail['product'], $rejectDetail['grade'], $rejectDetail['gross'], $rejectDetail['tare'], $rejectDetail['net'], $rejectTime, $rejectDetail['photoPath']);
                                    $insert_stmt3->execute();
                                    $insert_stmt3->close();
                                }
                            }
                        }
                    }
                }

                // If Stock Management is enabled, process the stock changes based on the weight details
                // if (in_array('processing', $_SESSION['products'])) {
                //     $productWeights = groupWeightDetails($weightDetails);
                    
                //     foreach ($productWeights as $key => $productWeight){
                //         $productId = $productWeight['product'];
                //         $grade = $productWeight['grade'];
                //         $afterValue = $productWeight['net'];
                //         $beforeValue = $existingGroupedWeights[$key]['net'] ?? 0;

                //         processRawStock($db, $productId, $grade, $company, $afterValue, $userID, $status, true, $beforeValue);
                //     }
                // }

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
        if ($insert_stmt = $db->prepare("INSERT INTO grading (grading_no, location, start_date, end_date, product_category, remark, company, created_by) VALUES  (?, ?, ?, ?, ?, ?, ?, ?)")){
            $insert_stmt->bind_param('ssssssss', $gradingNo, $location, $startDateTime3, $endDateTime, $category, $remarks, $company, $userID);
                        
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
                $gradingId = $insert_stmt->insert_id;
                $insert_stmt->close();

                // If Stock Management is enabled, process the stock changes based on the weight details
                // if (in_array('processing', $_SESSION['products'])) {
                //     $productWeights = groupWeightDetails($weightDetails);
                //     foreach ($productWeights as $weight) {
                //         $productId = $weight['product'];
                //         $grade = $weight['grade'];
                //         $nettWeight = $weight['net'];
                //         processRawStock($db, $productId, $grade, $company, $nettWeight, $userID, $status);
                //     }
                // }

                # Insert into grading_items table
                if(isset($_POST['weightDetails']) && $_POST['weightDetails'] != null && $_POST['weightDetails'] != ''){
                    $data = $_POST['weightDetails'];
                    foreach($data as $key => $weightDetail){
                        $time = date('Y-m-d') . ' ' . $weightDetail['time'];
                        $toGrade = $weightDetail['to_grade'] ?? '';
                        if ($insert_stmt2 = $db->prepare("INSERT INTO grading_items (grading_id, product_id, to_grade, gross_weight, tare_weight, nett_weight, weighing_time, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")){
                            $insert_stmt2->bind_param('ssssssss', $gradingId, $weightDetail['product'], $toGrade, $weightDetail['gross'], $weightDetail['tare'], $weightDetail['net'], $time, $weightDetail['photoPath']);
                            $insert_stmt2->execute();
                            $insert_stmt2->close();
                        }
                    }
                }

                if(isset($_POST['rejectDetails']) && $_POST['rejectDetails'] != null && $_POST['rejectDetails'] != ''){
                    $data = $_POST['rejectDetails']; 
                    foreach($data as $key => $rejectDetail){
                        $rejectTime = date('Y-m-d') . ' ' . $rejectDetail['time'];
                        if ($insert_stmt3 = $db->prepare("INSERT INTO grading_items (grading_id, product_id, to_grade, gross_weight, tare_weight, nett_weight, weighing_time, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")){
                            $insert_stmt3->bind_param('ssssssss', $gradingId, $rejectDetail['product'], $rejectDetail['grade'], $rejectDetail['gross'], $rejectDetail['tare'], $rejectDetail['net'], $rejectTime, $rejectDetail['photoPath']);
                            $insert_stmt3->execute();
                            $insert_stmt3->close();
                        }
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