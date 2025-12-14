<?php
require_once 'db_connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
$post = json_decode(file_get_contents('php://input'), true);

$services = 'Save_Waste';
$requests = json_encode($post);

$stmtL = $db->prepare("INSERT INTO api_requests (services, request) VALUES (?, ?)");
$stmtL->bind_param('ss', $services, $requests);
$stmtL->execute();

if(isset($post['id'], $post['do_no'], $post['vehicleNumber']
, $post['driverName'], $post['driverIc'])){
	$status = $post['status'];
	$do_no = $post['do_no'];
	$vehicleNumber = $post['vehicleNumber'];
	$driverName = $post['driverName'];
	$driverIc = $post['driverIc'];
	
	if($status == 'DISPATCH'){
	    if($post['customerName'] == 'OTHERS'){
	        $customerName2 = $post['customerName2'];
	    }
		else{
		    $customerName = $post['customerName'];
		    $customerName2 = null;
		}
	}
	
	if($status == 'RECEIVING'){
		if($post['supplierName'] == 'OTHERS'){
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

    if((isset($post['id']) && $post['id'] != null && $post['id'] != '')){
		$id = $post['id'];

		if ($update_stmt = $db->prepare("UPDATE waste SET po_no=?, customer=?, other_customer=?, supplier=?, other_supplier=?, driver=?, driver_ic=?, 
		vehicle_no=?, remark=? WHERE id=?")){
			$update_stmt->bind_param('ssssssssss', $do_no, $customerName, $customerName2, $supplierName, $supplierName2, $driverName, $driverIc, $vehicleNumber, $remark, $id);
		
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