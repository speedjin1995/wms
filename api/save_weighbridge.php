<?php
require_once 'db_connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
$post = json_decode(file_get_contents('php://input'), true);

$services = 'Save_Weighbridge';
$requests = json_encode($post);

$stmtL = $db->prepare("INSERT INTO api_requests (services, request) VALUES (?, ?)");
$stmtL->bind_param('ss', $services, $requests);
$stmtL->execute();
$invid = $stmtL->insert_id;

if(isset($post['transaction_status'], $post['gross'], $post['incoming_datetime'], $post['indicator'], $post['company'], $post['staffName'], $post['createdDatetime'])){
    $transaction_status = $post['transaction_status'];
    $gross= $post['gross'];
    $incoming_datetime = $post['incoming_datetime'];
    $company = $post['company'];
    $indicator = $post['indicator'];
    $staffName = $post['staffName'];
	$createdDatetime = $post['createdDatetime'];
	$transaction_date = $createdDatetime;
	$weight_type = 'Normal';
	$today = date("Y-m-d 00:00:00");
	$is_manual = 'N';

    $customer = null;
    $supplier = null;
    $vehicle = null;
    $invoice_no = null;
    $transporter = null;
    $driver = null;
    $destination = null;
    $tare = null;
    $outgoing_datetime = null;
    $net = null;
    $reduce = '0';
    $final_weight = null;
    $po_no = null;
    $do_no = null;
    $order_weight = null;
    $weight_difference = null;
    $container_no = null;
    $seal_no = null;
	$remark = null;
	$price_type = 'FIXED';
	$unit_price = null;
    $total_price = null;
	$remark = null;
	$remark2 = null;
	$product = null;
	$is_complete = 'N';
	$record_type = 'weighbridge';
	
	if(isset($post['product']) && $post['product'] != null && $post['product'] != ''){
		$product = $post['product'];
	}
	
	if(isset($post['record_type']) && $post['record_type'] != null && $post['record_type'] != ''){
		$record_type = $post['record_type'];
	}
	
	if(isset($post['is_manual']) && $post['is_manual'] != null && $post['is_manual'] != ''){
		$is_manual = $post['is_manual'];
	}
	
	if(isset($post['customer']) && $post['customer'] != null && $post['customer'] != ''){
		$customer = $post['customer'];
	}
	
	if(isset($post['supplier']) && $post['supplier'] != null && $post['supplier'] != ''){
		$supplier = $post['supplier'];
	}
	
	if(isset($post['vehicle']) && $post['vehicle'] != null && $post['vehicle'] != ''){
		$vehicle = $post['vehicle'];
	}
	
	if(isset($post['transporter']) && $post['transporter'] != null && $post['transporter'] != ''){
		$transporter = $post['transporter'];
	}
	
	if(isset($post['driver']) && $post['driver'] != null && $post['driver'] != ''){
		$driver = $post['driver'];
	}
	
	if(isset($post['destination']) && $post['destination'] != null && $post['destination'] != ''){
		$destination = $post['destination'];
	}
	
	if(isset($post['tare']) && $post['tare'] != null && $post['tare'] != ''){
		$tare = $post['tare'];
	}
	
	if(isset($post['outgoing_datetime']) && $post['outgoing_datetime'] != null && $post['outgoing_datetime'] != ''){
		$outgoing_datetime = $post['outgoing_datetime'];
	}
	
	if(isset($post['net']) && $post['net'] != null && $post['net'] != ''){
		$net = $post['net'];
	}
	
	if(isset($post['reduce']) && $post['reduce'] != null && $post['reduce'] != ''){
		$reduce = $post['reduce'];
	}
	
	if(isset($post['final_weight']) && $post['final_weight'] != null && $post['final_weight'] != ''){
		$final_weight = $post['final_weight'];
	}
	
	if(isset($post['po_no']) && $post['po_no'] != null && $post['po_no'] != ''){
		$po_no = $post['po_no'];
	}
	
	if(isset($post['do_no']) && $post['do_no'] != null && $post['do_no'] != ''){
		$do_no = $post['do_no'];
	}
	
	if(isset($post['invoice_no']) && $post['invoice_no'] != null && $post['invoice_no'] != ''){
		$invoice_no = $post['invoice_no'];
	}
	
	if(isset($post['order_weight']) && $post['order_weight'] != null && $post['order_weight'] != ''){
		$order_weight = $post['order_weight'];
	}
	
	if(isset($post['weight_difference']) && $post['weight_difference'] != null && $post['weight_difference'] != ''){
		$weight_difference = $post['weight_difference'];
	}
	
	if(isset($post['container_no']) && $post['container_no'] != null && $post['container_no'] != ''){
		$container_no = $post['container_no'];
	}
	
	if(isset($post['seal_no']) && $post['seal_no'] != null && $post['seal_no'] != ''){
		$seal_no = $post['seal_no'];
	}
	
	if(isset($post['price_type']) && $post['price_type'] != null && $post['price_type'] != ''){
		$price_type = $post['price_type'];
	}
	
	if(isset($post['unit_price']) && $post['unit_price'] != null && $post['unit_price'] != ''){
		$unit_price = $post['unit_price'];
	}
	
	if(isset($post['total_price']) && $post['total_price'] != null && $post['total_price'] != ''){
		$total_price = $post['total_price'];
	}
	
	if(isset($post['remarks']) && $post['remarks'] != null && $post['remarks'] != ''){
		$remark = $post['remarks'];
	}
	
	if(isset($post['second_remark']) && $post['second_remark'] != null && $post['second_remark'] != ''){
		$remark2 = $post['second_remark'];
	}
	
	if($weight_type == 'Normal' && ($gross != null && $tare != null)){
		if($record_type == 'weighbridge'){
			$is_complete = 'Y';
		}
		else{
			if($transaction_status == 'Receiving' && $invoice_no != null && $invoice_no != '' && $supplier != null && $vehicle != null && (float)$tare > 0){ 
			    $is_complete = 'Y';
			}
			else if($transaction_status == 'Dispatch' && $customer != null && $vehicle != null && (float)$gross > 0){
				$is_complete = 'Y';
			}
			else{
				$is_complete = 'N';
			}
			
		}
        
    }
    /*else if($weight_type == 'Container' && ($gross != null && $tareOutgoing != null && $gross2 != null && $tareOutgoing2 != null)){
        $isComplete = 'Y';
    }*/
    else{
        $is_complete = 'N';
    }
    
    try{
        if(isset($post['id']) && $post['id'] != null && $post['id'] != ''){
            $serialNo = '';
            
            if($update_stmt = $db->prepare("UPDATE Weight SET transaction_status=?, lorry_plate_no1=?, order_weight=?, customer_name=?, supplier_name=?, product_name=?, container_no=?, 
            seal_no=?, purchase_order=?, delivery_no=?, transporter=?, driver_name=?, destination=?, remarks=?, gross_weight1=?, gross_weight1_date=?, tare_weight1=?, tare_weight1_date=?,
            nett_weight1=?, reduce_weight=?, final_weight=?, weight_different=?, is_complete=?, modified_date=?, modified_by=?, indicator_id=?, manual_weight=?, sub_total=?, unit_price=?, 
            total_price=?, invoice_no=? WHERE id = ?")){
                $update_stmt->bind_param('ssssssssssssssssssssssssssssssss', $transaction_status, $vehicle, $order_weight, $customer, $supplier, $product, $container_no, $seal_no, 
                $po_no, $do_no, $transporter, $driver, $destination, $remark, $gross, $incoming_datetime, $tare, $outgoing_datetime, $net, $reduce, $final_weight, $weight_difference, 
                $is_complete, $createdDatetime, $staffName, $indicator, $is_manual, $price_type, $unit_price, $total_price, $invoice_no, $post['id']);	
                
                if (! $update_stmt->execute()){ // Execute the prepared query.
					$response = json_encode(
        				array(
        					"status"=> "failed", 
        					"message"=> $update_stmt->error
        				)
        			);

					$stmtU = $db->prepare("UPDATE api_requests SET response = ? WHERE id = ?");
					$stmtU->bind_param('ss', $response, $invid);
					$stmtU->execute();

					$update_stmt->close();
					$stmtU->close();
					echo $response;
        		} 
        		else{
        		    $weightId = $post['id'];
        		    $select_stmt = $db->prepare("SELECT transaction_id from Weight WHERE id = ?");
                    $select_stmt->bind_param('s', $weightId);
                    $select_stmt->execute();
                    $select_result = $select_stmt->get_result();
                    
                    if($select_row = $select_result->fetch_assoc()){
        		        $serialNo = $select_row['transaction_id'];
                    }
        		    
					$response = json_encode(
        				array(
        					"status"=> "success", 
        					"message"=> "Updated Successfully!!",
        					"serialNo"=> $serialNo,
        					"id"=> $weightId
        				)
        			);

					$stmtU = $db->prepare("UPDATE api_requests SET response = ? WHERE id = ?");
					$stmtU->bind_param('ss', $response, $invid);
					$stmtU->execute();

					$select_stmt->close();
					$update_stmt->close();
					$stmtU->close();
					echo $response;
        		}
        
        		$db->close();
            }
            else{
				$response = json_encode(
        			array(
        				"status"=> "failed", 
        				"message"=> "cannot prepare update statement"
        			)
        		);

				$stmtU = $db->prepare("UPDATE api_requests SET response = ? WHERE id = ?");
				$stmtU->bind_param('ss', $response, $invid);
				$stmtU->execute();

				$update_stmt->close();
				$stmtU->close();
				echo $response;
        	}
        }
        else{
            $serialNo = '';
            
            if(!isset($post['serialNo']) || $post['serialNo'] == null || $post['serialNo'] == ''){
                $serialNo = 'S';
                
                if($transaction_status == 'Purchase' || $transaction_status == 'Receiving'){
                    $serialNo = 'P';
                }
                else if($transaction_status == 'Misc'){
                    $serialNo = 'M';
                }
                
        	    $serialNo .= date("Ymd");
        
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
                            $serialNo.='0';  // S0000
                        }
                
                        $serialNo .= strval($count);  //S00009
        			}
        		}
        	}
        	else{
        	    $serialNo = $post['serialNo'];
        	}
        
        	if ($insert_stmt = $db->prepare("INSERT INTO Weight (transaction_id, transaction_status, weight_type, transaction_date, lorry_plate_no1, order_weight, customer_name, 
        	supplier_name, product_name, container_no, seal_no, purchase_order, delivery_no, transporter, driver_name, destination, remarks, gross_weight1, gross_weight1_date, 
        	tare_weight1, tare_weight1_date, nett_weight1, reduce_weight, final_weight, weight_different, is_complete, created_date, created_by, company, modified_date, modified_by, 
        	indicator_id, manual_weight, sub_total, unit_price, total_price, records_type, invoice_no) 
        	VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")){	
        	    $insert_stmt->bind_param('ssssssssssssssssssssssssssssssssssssss', $serialNo, $transaction_status, $weight_type, $transaction_date, $vehicle, $order_weight, $customer, 
        	    $supplier, $product, $container_no, $seal_no, $po_no, $do_no, $transporter, $driver, $destination, $remark, $gross, $incoming_datetime, $tare, $outgoing_datetime, $net, $reduce, 
        	    $final_weight, $weight_difference, $is_complete, $createdDatetime, $staffName, $company, $createdDatetime, $staffName, $indicator, $is_manual, $price_type, $unit_price, 
        	    $total_price, $record_type, $invoice_no);	
        		
        		if (! $insert_stmt->execute()){ // Execute the prepared query.
					$response = json_encode(
        				array(
        					"status"=> "failed", 
        					"message"=> $insert_stmt->error
        				)
        			);

					$stmtU = $db->prepare("UPDATE api_requests SET response = ? WHERE id = ?");
					$stmtU->bind_param('ss', $response, $invid);
					$stmtU->execute();

					$insert_stmt->close();
					$stmtU->close();
					echo $response;
        		} 
        		else{
        		    $weightId = $insert_stmt->insert_id;

					$response = json_encode(
        				array(
        					"status"=> "success", 
        					"message"=> "Added Successfully!!",
        					"serialNo"=> $serialNo,
        					"id"=> $weightId
        				)
        			);

					$stmtU = $db->prepare("UPDATE api_requests SET response = ? WHERE id = ?");
					$stmtU->bind_param('ss', $response, $invid);
					$stmtU->execute();

					$insert_stmt->close();
					$stmtU->close();
					echo $response;
        		}
        
        		$db->close();
        	}
        	else{
				$response = json_encode(
        			array(
        				"status"=> "failed", 
        				"message"=> "cannot prepare insert statement"
        			)
        		); 

				$stmtU = $db->prepare("UPDATE api_requests SET response = ? WHERE id = ?");
				$stmtU->bind_param('ss', $response, $invid);
				$stmtU->execute();

				$insert_stmt->close();
				$stmtU->close();
				echo $response;
        	}
        }
    }
    catch(Exception $e){
		$response = json_encode(
            array(
                "status"=> "failed", 
                "message"=> $e->getMessage()
            )
        ); 

		$stmtU = $db->prepare("UPDATE api_requests SET response = ? WHERE id = ?");
		$stmtU->bind_param('ss', $response, $invid);
		$stmtU->execute();

		$stmtU->close();
		echo $response;
    }
} 
else{
	$response = json_encode(
        array(
            "status"=> "failed", 
            "message"=> "Please fill in all the fields"
        )
    ); 

	$stmtU = $db->prepare("UPDATE api_requests SET response = ? WHERE id = ?");
	$stmtU->bind_param('ss', $response, $invid);
	$stmtU->execute();

	$stmtU->close();
	echo $response;    
}
?>