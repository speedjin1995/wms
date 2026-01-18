<?php
require_once 'db_connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

if(isset($_POST['status'])){
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    $customer = null;
    $customerOther = null;
    $supplier = null;
    $supplierOther = null;
    $vehicle = null;
    $driver = null;
    $totalReject = 0.00;
    $weightDetails = [];
    $rejectDetails = [];
    $totalItem = 0;
    $totalNet = 0;
    $totalPrice = 0;


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
		$vehicle = $_POST['vehicle'];
	}

    if(isset($_POST['driver']) && $_POST['driver'] != null && $_POST['driver'] != ''){
		$driver = $_POST['driver'];
	}

    if(isset($_POST['weightDetails']) && $_POST['weightDetails'] != null && $_POST['weightDetails'] != ''){
		$data = $_POST['weightDetails'];
        foreach($data as $weightDetail){
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
            ];

            $totalItem++;
            $totalNet += floatval($weightDetail['net'] ?? 0.0);
            $totalPrice += floatval($weightDetail['price'] ?? 0.0);
        }
    }

    if(isset($_POST['rejectDetails']) && $_POST['rejectDetails'] != null && $_POST['rejectDetails'] != ''){
		$data = $_POST['rejectDetails'];
        foreach($data as $rejectDetail){
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
            ];

            $totalReject += floatval($rejectDetail['net'] ?? 0.0);
        }
    }

    if(isset($_POST['id']) && $_POST['id'] != null && $_POST['id'] != ''){
        if ($update_stmt = $db->prepare("UPDATE wholesales SET status=?, customer=?, other_customer=?, supplier=?, other_supplier=?, vehicle_no=?, driver=?, total_reject=?, weight_details=?, reject_details=?, total_item=?, total_weight=?, total_price=? WHERE id=?")){
            $weightDetailsJson = json_encode($weightDetails);
            $rejectDetailsJson = json_encode($rejectDetails);
            $update_stmt->bind_param('ssssssssssssss', $status, $customer, $customerOther, $supplier, $supplierOther, $vehicle, $driver, $totalReject, $weightDetailsJson, $rejectDetailsJson, $totalItem, $totalNet, $totalPrice, $_POST['id']);
            
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
        if ($insert_stmt = $db->prepare("INSERT INTO counting (serial_no, product, product_desc, gross, unit, count, remark, created_by, company, supplier, batch_no, article_code, iqc_no, uom) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")){
            $insert_stmt->bind_param('ssssssssssssss', $serialNo, $product, $productDesc, $currentWeight, $unitWeight, $actualCount, $remark, $user, $company, $supplies, $batchNumber, $articleNumber, $iqcNumber, $uom);
                        
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