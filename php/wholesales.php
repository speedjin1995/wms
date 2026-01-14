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
            // Calculate total reject
            $totalReject += floatval($weightDetail['reject'] ?? 0.0);

            $weightDetails[] = [
                'gross' => $weightDetail['gross'] ?? '',
                'tare' => $weightDetail['tare'] ?? '',
                'pretare' => $weightDetail['pretare'] ?? '0.0',
                'net' => $weightDetail['net'] ?? '',
                'product' => $weightDetail['product'] ?? '',
                'product_name' => $weightDetail['product_name'] ?? '',
                'product_desc' => $weightDetail['product_desc'] ?? '',
                'price' => $weightDetail['price'] ?? '',
                'unit' => $weightDetail['unit'] ?? '',
                'package' => $weightDetail['package'] ?? '',
                'time' => $weightDetail['time'] ?? '',
                'reject' => $weightDetail['reject'] ?? '',
                'total' => $weightDetail['total'] ?? '',
                'fixedfloat' => $weightDetail['fixedfloat'] ?? '',
                'isedit' => $weightDetail['isedit'] ?? 'N',
                'grade' => $weightDetail['grade'] ?? ''
            ];
        }
    }

    if(isset($_POST['id']) && $_POST['id'] != null && $_POST['id'] != ''){
        if ($update_stmt = $db->prepare("UPDATE wholesales SET status=?, customer=?, other_customer=?, supplier=?, other_supplier=?, vehicle_no=?, driver=?, total_reject=?, weight_details=? WHERE id=?")){
            $weightDetailsJson = json_encode($weightDetails);
            $update_stmt->bind_param('ssssssssss', $status, $customer, $customerOther, $supplier, $supplierOther, $vehicle, $driver, $totalReject, $weightDetailsJson, $_POST['id']);
            
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