<?php
require_once 'db_connect.php';
require_once 'uploadFileHelper.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

if (isset($_POST['transactionId'], $_POST['transactionStatus'], $_POST['transactionDate'], $_POST['grossIncoming'], $_POST['grossIncomingDate'])) {
    $userID = $_SESSION['userID'];
    $company = $_SESSION['customer'];
	$today = date("Y-m-d 00:00:00");
	$now = date("Y-m-d H:i:s");
    $indicator = "web";
    $weightType = "Normal";
    $recordType = "fruits";
    $transactionStatus = null;
    $transactionDateTime = null;
    $transactionId = null;
    $poNo = null;
    $doNo = null;
    $customer = null;
    $customerCode = null;
    $supplier = null;
    $supplierCode = null;
    $product = null;
    $productCode = null;
    $vehicle = null;
    $grossIncoming = null;
    $grossIncomingDate = null;
    $tareOutgoing = null;
    $tareOutgoingDate = null;
    $nettWeight = null;

    if(isset($_POST['transactionStatus']) && $_POST['transactionStatus'] != null && $_POST['transactionStatus'] != ''){
		$transactionStatus = $_POST['transactionStatus'];
	}

    if(isset($_POST['transactionDate']) && $_POST['transactionDate'] != null && $_POST['transactionDate'] != ''){
        $transactionDate = $_POST['transactionDate'];
        $transactionDateObj = DateTime::createFromFormat('d/m/Y H:i', $transactionDate);
        $transactionDateTime = $transactionDateObj->format("Y-m-d H:i:s");
    }

    if(isset($_POST['transactionId']) && $_POST['transactionId'] != null && $_POST['transactionId'] != ''){
		$transactionId = $_POST['transactionId'];
	}else{
        $transactionId = 'S';
            
        if($transactionStatus == 'Purchase' || $transactionStatus == 'Receiving'){
            $transactionId = 'P';
        }
        else if($transactionStatus == 'Misc'){
            $transactionId = 'M';
        }

        $transactionId .= date("Ymd");
    
        if ($select_stmt = $db->prepare("SELECT COUNT(*) FROM Weight WHERE created_date >= ? AND company =?")) {
            $select_stmt->bind_param('ss', $today, $company);
            
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
                    $transactionId.='0';  // S0000
                }
        
                $transactionId .= strval($count);  //S00009
            }
        }
    }

    if(isset($_POST['poNo']) && $_POST['poNo'] != null && $_POST['poNo'] != ''){
		$poNo = $_POST['poNo'];
	}
    
    if(isset($_POST['doNo']) && $_POST['doNo'] != null && $_POST['doNo'] != ''){
		$doNo = $_POST['doNo'];
	}

    if(isset($_POST['customer']) && $_POST['customer'] != null && $_POST['customer'] != ''){
		$customer = $_POST['customer'];
	}
    
    if(isset($_POST['customerCode']) && $_POST['customerCode'] != null && $_POST['customerCode'] != ''){
		$customerCode = $_POST['customerCode'];
	}
    
    if(isset($_POST['supplier']) && $_POST['supplier'] != null && $_POST['supplier'] != ''){
		$supplier = $_POST['supplier'];
	}
    
    if(isset($_POST['supplierCode']) && $_POST['supplierCode'] != null && $_POST['supplierCode'] != ''){
		$supplierCode = $_POST['supplierCode'];
	}
    
    if(isset($_POST['vehicle']) && $_POST['vehicle'] != null && $_POST['vehicle'] != ''){
		$vehicle = $_POST['vehicle'];
	}
    
    if(isset($_POST['product']) && $_POST['product'] != null && $_POST['product'] != ''){
		$product = $_POST['product'];
	}
    
    if(isset($_POST['productCode']) && $_POST['productCode'] != null && $_POST['productCode'] != ''){
		$productCode = $_POST['productCode'];
	}

    if(isset($_POST['grossIncoming']) && $_POST['grossIncoming'] != null && $_POST['grossIncoming'] != ''){
		$grossIncoming = $_POST['grossIncoming'];
	}
    
    if(isset($_POST['grossIncomingDate']) && $_POST['grossIncomingDate'] != null && $_POST['grossIncomingDate'] != ''){
        $grossIncomingDate = $_POST['grossIncomingDate'];
        $grossIncomingDateObj = DateTime::createFromFormat('d/m/Y H:i', $grossIncomingDate);
        $grossIncomingDateTime = $grossIncomingDateObj->format("Y-m-d H:i:s");
    }

    if(isset($_POST['tareOutgoing']) && $_POST['tareOutgoing'] != null && $_POST['tareOutgoing'] != ''){
		$tareOutgoing = $_POST['tareOutgoing'];
	}
    
    if(isset($_POST['tareOutgoingDate']) && $_POST['tareOutgoingDate'] != null && $_POST['tareOutgoingDate'] != ''){
        $tareOutgoingDate = $_POST['tareOutgoingDate'];
        $tareOutgoingDateObj = DateTime::createFromFormat('d/m/Y H:i', $tareOutgoingDate);
        $tareOutgoingDateTime = $tareOutgoingDateObj->format("Y-m-d H:i:s");
    }
    
    if(isset($_POST['nettWeight']) && $_POST['nettWeight'] != null && $_POST['nettWeight'] != ''){
		$nettWeight = $_POST['nettWeight'];
	}

    if(($weightType == 'Normal' || $weightType == 'Empty Container') && ($grossIncoming != null && $tareOutgoing != null)){
        $isComplete = 'Y';
    }
    else{
        $isComplete = 'N';
    }


    if(isset($_POST['id']) && $_POST['id'] != null && $_POST['id'] != ''){
        if ($update_stmt = $db->prepare("UPDATE weight SET transaction_id=?, weight_type=?, transaction_status=?, transaction_date=?, purchase_order=?, delivery_no=?, customer_name=?, customer_code=?, supplier_name=?, supplier_code=?, product_name=?, product_code=?, lorry_plate_no1=?, gross_weight1=?, gross_weight1_date=?, tare_weight1=?, tare_weight1_date=?, nett_weight1=?, final_weight=?, modified_by=?, modified_date=?, indicator_id=?, records_type=?, is_complete=? WHERE id=?")){
            $update_stmt->bind_param('sssssssssssssssssssssssss', $transactionId, $weightType, $transactionStatus, $transactionDateTime, $poNo, $doNo, $customer, $customerCode, $supplier, $supplierCode, $product, $productCode, $vehicle, $grossIncoming, $grossIncomingDateTime, $tareOutgoing, $tareOutgoingDateTime, $nettWeight, $nettWeight, $userID, $now, $indicator, $recordType, $isComplete, $_POST['id']);
            
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
        if ($insert_stmt = $db->prepare("INSERT INTO weight (transaction_id, weight_type, transaction_status, transaction_date, purchase_order, delivery_no, customer_name, customer_code, supplier_name, supplier_code, product_name, product_code, lorry_plate_no1, gross_weight1, gross_weight1_date, tare_weight1, tare_weight1_date, nett_weight1, final_weight, indicator_id, created_date, created_by, company, records_type, is_complete, modified_by) VALUES  (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")){
            $insert_stmt->bind_param('ssssssssssssssssssssssssss', $transactionId, $weightType, $transactionStatus, $transactionDateTime, $poNo, $doNo, $customer, $customerCode, $supplier, $supplierCode, $product, $productCode, $vehicle, $grossIncoming, $grossIncomingDateTime, $tareOutgoing, $tareOutgoingDateTime, $nettWeight, $nettWeight, $indicator, $now, $userID, $company, $recordType, $isComplete, $userID);
                        
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