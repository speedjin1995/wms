<?php
require_once 'db_connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

if(isset($_POST['supplies'], $_POST['productDesc'], $_POST['product'], $_POST['batchNumber'], $_POST['currentWeight'], 
$_POST['unitWeight'], $_POST['actualCount'], $_POST['articleNumber'], $_POST['iqcNumber'], $_POST['uom'])){
	$productDesc = filter_input(INPUT_POST, 'productDesc', FILTER_SANITIZE_STRING);
	$product = filter_input(INPUT_POST, 'product', FILTER_SANITIZE_STRING);
	$currentWeight = filter_input(INPUT_POST, 'currentWeight', FILTER_SANITIZE_STRING);
	$unitWeight = filter_input(INPUT_POST, 'unitWeight', FILTER_SANITIZE_STRING);
	$actualCount = filter_input(INPUT_POST, 'actualCount', FILTER_SANITIZE_STRING);
    $supplies = filter_input(INPUT_POST, 'supplies', FILTER_SANITIZE_STRING);
	$batchNumber = filter_input(INPUT_POST, 'batchNumber', FILTER_SANITIZE_STRING);
	$articleNumber = filter_input(INPUT_POST, 'articleNumber', FILTER_SANITIZE_STRING);
	$iqcNumber = filter_input(INPUT_POST, 'iqcNumber', FILTER_SANITIZE_STRING);
	$uom = filter_input(INPUT_POST, 'uom', FILTER_SANITIZE_STRING);

    $today = date("Y-m-d 00:00:00");

    $user = $_SESSION['userID'];
    $company = $_SESSION['customer'];
    $status = '0';
    $remark = null;
    $serialNo = '';

    if(isset($_POST['remark']) && $_POST['remark'] != null && $_POST['remark'] != ''){
		$remark = $_POST['remark'];
	}

    if(!isset($_POST['serialNumber']) || $_POST['serialNumber'] == null || $_POST['serialNumber'] == ''){
	    $serialNo = date("Ymd");

		if ($select_stmt = $db->prepare("SELECT COUNT(*) FROM counting WHERE created_datetime >= ? AND deleted = ?")) {
            $select_stmt->bind_param('ss', $today, $status);
            
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

    if(isset($_POST['id']) && $_POST['id'] != null && $_POST['id'] != ''){
        if ($update_stmt = $db->prepare("UPDATE counting SET product=?, product_desc=?, gross=?, unit=?, count=?, remark=?, supplier=?, batch_no=?, article_code=?, iqc_no=?, uom=? WHERE id=?")){
            $update_stmt->bind_param('ssssssssssss', $product, $productDesc, $currentWeight, $unitWeight, $actualCount, $remark, $supplies, $batchNumber, $articleNumber, $iqcNumber, $uom, $_POST['id']);
            
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
                        "message"=> "Added Successfully!!" 
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