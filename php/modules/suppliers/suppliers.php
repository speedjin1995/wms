<?php
require_once '../../db_connect.php';

session_start();

$userID = $_SESSION['userID'];

if(isset($_POST['code'], $_POST['name'], $_POST['company'])){
    $code = filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_STRING);
    $regNo = null;
    $ssmNo = null;
    $icNo = null;
    $ssmFile = null;
    $ctosReportNo = null;
	$address = null;
    $address2 = null;
    $address3 = null;
    $address4 = null;
    $states = null;
    $phone = null;
    $fax = null;
    $email = null;
    $parent = null;
    $billingName = null;
    $billingAddress = null;
    $billingAddress2 = null;
    $billingAddress3 = null;
    $billingAddress4 = null;
    $billingStates = null;
    $billingPhone = null;
    $billingFax = null;
    $billingPic = null;
    $currency = null;
    $supplierType = 'Normal';
    $isManual = 'N';

    if(isset($_POST['regNo']) && $_POST['regNo'] != null && $_POST['regNo'] != ''){
        $regNo = filter_input(INPUT_POST, 'regNo', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['ssmNo']) && $_POST['ssmNo'] != null && $_POST['ssmNo'] != ''){
        $ssmNo = filter_input(INPUT_POST, 'ssmNo', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['ctosReportNo']) && $_POST['ctosReportNo'] != null && $_POST['ctosReportNo'] != ''){
        $ctosReportNo = filter_input(INPUT_POST, 'ctosReportNo', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['icNo']) && $_POST['icNo'] != null && $_POST['icNo'] != ''){
        $icNo = filter_input(INPUT_POST, 'icNo', FILTER_SANITIZE_STRING);
    }

    // Handle SSM file upload
    if(isset($_FILES['ssmFile']) && $_FILES['ssmFile']['error'] === UPLOAD_ERR_OK){
        require_once '../../uploadFileHelper.php';
        $result = uploadFile($_FILES['ssmFile'], 'ssm', $company, $db);
        if ($result['status'] === 'failed') {
            echo json_encode(array("status"=> "failed", "message"=> $result['message']));
            exit;
        }
        $ssmFile = $result['fid'];

        // Delete old SSM file on update
        if(isset($_POST['id']) && $_POST['id'] != null && $_POST['id'] != ''){
            $stmt = $db->prepare("SELECT ssm_file FROM supplies WHERE id = ? AND customer = ?");
            $stmt->bind_param('ss', $_POST['id'], $company);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                if ($row['ssm_file']) {
                    deleteOldFile($row['ssm_file'], $db);
                }
            }
            $stmt->close();
        }
    } elseif(isset($_POST['ssmFilePath']) && $_POST['ssmFilePath'] != ''){
        $ssmFile = filter_input(INPUT_POST, 'ssmFilePath', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['address']) && $_POST['address'] != null && $_POST['address'] != ''){
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['address2']) && $_POST['address2'] != null && $_POST['address2'] != ''){
        $address2 = filter_input(INPUT_POST, 'address2', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['address3']) && $_POST['address3'] != null && $_POST['address3'] != ''){
        $address3 = filter_input(INPUT_POST, 'address3', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['address4']) && $_POST['address4'] != null && $_POST['address4'] != ''){
        $address4 = filter_input(INPUT_POST, 'address4', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['states']) && $_POST['states'] != null && $_POST['states'] != ''){
        $states = filter_input(INPUT_POST, 'states', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['phone']) && $_POST['phone'] != null && $_POST['phone'] != ''){
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['fax']) && $_POST['fax'] != null && $_POST['fax'] != ''){
        $fax = filter_input(INPUT_POST, 'fax', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['email']) && $_POST['email'] != null && $_POST['email'] != ''){
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['parent']) && $_POST['parent'] != null && $_POST['parent'] != ''){
        $parent = filter_input(INPUT_POST, 'parent', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['billingName']) && $_POST['billingName'] != null && $_POST['billingName'] != ''){
        $billingName = filter_input(INPUT_POST, 'billingName', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['billingAddress']) && $_POST['billingAddress'] != null && $_POST['billingAddress'] != ''){
        $billingAddress = filter_input(INPUT_POST, 'billingAddress', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['billingAddress2']) && $_POST['billingAddress2'] != null && $_POST['billingAddress2'] != ''){
        $billingAddress2 = filter_input(INPUT_POST, 'billingAddress2', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['billingAddress3']) && $_POST['billingAddress3'] != null && $_POST['billingAddress3'] != ''){
        $billingAddress3 = filter_input(INPUT_POST, 'billingAddress3', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['billingAddress4']) && $_POST['billingAddress4'] != null && $_POST['billingAddress4'] != ''){
        $billingAddress4 = filter_input(INPUT_POST, 'billingAddress4', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['billingStates']) && $_POST['billingStates'] != null && $_POST['billingStates'] != ''){
        $billingStates = filter_input(INPUT_POST, 'billingStates', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['billingPhone']) && $_POST['billingPhone'] != null && $_POST['billingPhone'] != ''){
        $billingPhone = filter_input(INPUT_POST, 'billingPhone', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['billingFax']) && $_POST['billingFax'] != null && $_POST['billingFax'] != ''){
        $billingFax = filter_input(INPUT_POST, 'billingFax', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['billingPic']) && $_POST['billingPic'] != null && $_POST['billingPic'] != ''){
        $billingPic = filter_input(INPUT_POST, 'billingPic', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['currency']) && $_POST['currency'] != null && $_POST['currency'] != ''){
        $currency = filter_input(INPUT_POST, 'currency', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['supplierType']) && $_POST['supplierType'] != null && $_POST['supplierType'] != ''){
        $supplierType = filter_input(INPUT_POST, 'supplierType', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['id']) && $_POST['id'] != null && $_POST['id'] != ''){
        if ($update_stmt = $db->prepare("UPDATE supplies SET supplier_code=?, reg_no=?, ssm=?, ic_no=?, ssm_file=?, ctos_report_no=?, supplier_name=?, supplier_address=?, supplier_address2=?, supplier_address3=?, supplier_address4=?, states=?, supplier_phone=?, fax=?, pic=?, parent=?, billing_name=?, billing_address=?, billing_address2=?, billing_address3=?, billing_address4=?, billing_state=?, billing_phone=?, billing_fax=?, billing_pic=?, currency=?, supplier_type=?, is_manual=?, modified_by=? WHERE id=?")) {
            $update_stmt->bind_param('ssssssssssssssssssssssssssssss', $code, $regNo, $ssmNo, $icNo, $ssmFile, $ctosReportNo, $name, $address, $address2, $address3, $address4, $states, $phone, $fax, $email, $parent, $billingName, $billingAddress, $billingAddress2, $billingAddress3, $billingAddress4, $billingStates, $billingPhone, $billingFax, $billingPic, $currency, $supplierType, $isManual, $userID, $_POST['id']);
            
            // Execute the prepared query.
            if (! $update_stmt->execute()) {
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
    }
    else{
        if ($insert_stmt = $db->prepare("INSERT INTO supplies (supplier_code, reg_no, ssm, ic_no, ssm_file, ctos_report_no, supplier_name, supplier_address, supplier_address2, supplier_address3, supplier_address4, states, supplier_phone, fax, pic, customer, parent, billing_name, billing_address, billing_address2, billing_address3, billing_address4, billing_state, billing_phone, billing_fax, billing_pic, currency, supplier_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
            $insert_stmt->bind_param('sssssssssssssssssssssssssssss', $code, $regNo, $ssmNo, $icNo, $ssmFile, $ctosReportNo, $name, $address, $address2, $address3, $address4, $states, $phone, $fax, $email, $company, $parent, $billingName, $billingAddress, $billingAddress2, $billingAddress3, $billingAddress4, $billingStates, $billingPhone, $billingFax, $billingPic, $currency, $supplierType, $userID);
            
            // Execute the prepared query.
            if (! $insert_stmt->execute()) {
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