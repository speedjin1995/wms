<?php
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);
require_once "db_connect.php";
require_once "uploadFileHelper.php";

session_start();

if(isset($_POST['code'], $_POST['product'], $_POST['company'])){
    $userID = $_SESSION['userID'];
    $code = filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING);
    $product = filter_input(INPUT_POST, 'product', FILTER_SANITIZE_STRING);
    $company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_STRING);
    
    $serial = null;
    $batch = null;
    $part = null;
    $uom = null;
    $remark = null;
    $pricingType = null;
    $price = null;
    $purchasingPricingType = null;
    $purchasingPrice = null;
    $weight = null;
    $rangeSet = 0;
    $okWeight = null;
    $okWeightUnit = null;
    $loWeight = null;
    $loWeightUnit = null;
    $hiWeight = null;
    $hiWeightUnit = null;
    $productCategory = null;
    $productPackaging = null;
    $state = null;
    $isManual = 'N';
    $deleteStatus = 1;

    if(isset($_POST['serial']) && $_POST['serial'] != null && $_POST['serial'] != ''){
        $serial = filter_input(INPUT_POST, 'serial', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['batch']) && $_POST['batch'] != null && $_POST['batch'] != ''){
        $batch = filter_input(INPUT_POST, 'batch', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['part']) && $_POST['part'] != null && $_POST['part'] != ''){
        $part = filter_input(INPUT_POST, 'part', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['uom']) && $_POST['uom'] != null && $_POST['uom'] != '' && $_POST['uom'] != '-'){
        $uom = filter_input(INPUT_POST, 'uom', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['remark']) && $_POST['remark'] != null && $_POST['remark'] != ''){
        $remark = filter_input(INPUT_POST, 'remark', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['pricingType']) && $_POST['pricingType'] != null && $_POST['pricingType'] != ''){
        $pricingType = filter_input(INPUT_POST, 'pricingType', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['price']) && $_POST['price'] != null && $_POST['price'] != ''){
        $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['purchasingPrice']) && $_POST['purchasingPrice'] != null && $_POST['purchasingPrice'] != ''){
        $purchasingPrice = filter_input(INPUT_POST, 'purchasingPrice', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['purchasingPricingType']) && $_POST['purchasingPricingType'] != null && $_POST['purchasingPricingType'] != ''){
        $purchasingPricingType = filter_input(INPUT_POST, 'purchasingPricingType', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['weight']) && $_POST['weight'] != null && $_POST['weight'] != ''){
        $weight = filter_input(INPUT_POST, 'weight', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['rangeSet']) && $_POST['rangeSet'] != null && $_POST['rangeSet'] != ''){
        $rangeSet = intval($_POST['rangeSet']);
    }

    if(isset($_POST['okWeight']) && $_POST['okWeight'] != null && $_POST['okWeight'] != ''){
        $okWeight = filter_input(INPUT_POST, 'okWeight', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['okWeightUnit']) && $_POST['okWeightUnit'] != null && $_POST['okWeightUnit'] != ''){
        $okWeightUnit = filter_input(INPUT_POST, 'okWeightUnit', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['loWeight']) && $_POST['loWeight'] != null && $_POST['loWeight'] != ''){
        $loWeight = filter_input(INPUT_POST, 'loWeight', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['loWeightUnit']) && $_POST['loWeightUnit'] != null && $_POST['loWeightUnit'] != ''){
        $loWeightUnit = filter_input(INPUT_POST, 'loWeightUnit', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['hiWeight']) && $_POST['hiWeight'] != null && $_POST['hiWeight'] != ''){
        $hiWeight = filter_input(INPUT_POST, 'hiWeight', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['hiWeightUnit']) && $_POST['hiWeightUnit'] != null && $_POST['hiWeightUnit'] != ''){
        $hiWeightUnit = filter_input(INPUT_POST, 'hiWeightUnit', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['productCategory']) && $_POST['productCategory'] != null && $_POST['productCategory'] != ''){
        $productCategory = filter_input(INPUT_POST, 'productCategory', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['productPackaging']) && $_POST['productPackaging'] != null && $_POST['productPackaging'] != ''){
        $productPackaging = filter_input(INPUT_POST, 'productPackaging', FILTER_SANITIZE_STRING);
    }

    if(isset($_POST['state']) && $_POST['state'] != null && $_POST['state'] != ''){
        $state = json_encode($_POST['state']);
    }

    if($_POST['id'] != null && $_POST['id'] != ''){
        if ($update_stmt = $db->prepare("UPDATE products SET product_code=?, product_name=?, product_sn=?, batch_no=?, parts_no=?, uom=?, remark=?, pricing_type=?, price=?, purchasing_pricing_type=?, purchasing_price=?, weight=?, range_set=?, ok_weight=?, ok_weight_unit=?, lo_weight=?, lo_weight_unit=?, hi_weight=?, hi_weight_unit=?, category=?, packaging=?, state=?, is_manual=?, modified_by=? WHERE id=?")) {
            $update_stmt->bind_param('sssssssssssssssssssssssss', $code, $product, $serial, $batch, $part, $uom, $remark, $pricingType, $price, $purchasingPricingType, $purchasingPrice, $weight, $rangeSet, $okWeight, $okWeightUnit, $loWeight, $loWeightUnit, $hiWeight, $hiWeightUnit, $productCategory, $productPackaging, $state, $isManual, $userID, $_POST['id']);

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
                # product_customers
                if (isset($_POST['no'])){
                    $no = $_POST['no'];
                    $customers = (array)$_POST['customers'];
                    $customerProductId = (array)$_POST['customerProductId'];
                    $customerPricingType = (array)$_POST['customerPricingType'];
                    $customerPrice = (array)$_POST['customerPrice'];
                    $customerPurchasingPricingType = (array)$_POST['customerPurchasingPricingType'];
                    $customerPurchasingPrice = (array)$_POST['customerPurchasingPrice'];

                    if(isset($no) && $no != null && count($no) > 0){
                        #  Soft-delete old product_customers
                        if ($delete_stmt = $db->prepare("UPDATE product_customers SET deleted='1' WHERE product_id=?")){
                            $delete_stmt->bind_param('s', $_POST['id']);

                            // Execute the prepared query.
                            if (! $delete_stmt->execute()) {
                                echo json_encode(
                                    array(
                                        "status"=> "failed", 
                                        "message"=> $delete_stmt->error
                                    )
                                );
                            }else{
                                foreach($no as $key => $number) {
                                    $customerId = $customers[$key];
                                    $cPricingType = $customerPricingType[$key];
                                    $cPrice = $customerPrice[$key];
                                    $cPurchasingPricingType = $customerPurchasingPricingType[$key];
                                    $cPurchasingPrice = $customerPurchasingPrice[$key];

                                    if (isset($customerProductId[$key]) && $customerProductId[$key] != null && $customerProductId[$key] != ''){
                                        $cProductId = $customerProductId[$key];

                                        if ($update_stmt2 = $db->prepare("UPDATE product_customers SET product_id=?, customer_id=?, pricing_type=?, price=?, purchasing_pricing_type=?, purchasing_price=?, deleted='0' WHERE id=?")){
                                            $update_stmt2->bind_param('sssssss', $_POST['id'], $customerId, $cPricingType, $cPrice, $cPurchasingPricingType, $cPurchasingPrice, $cProductId);
                                            $update_stmt2->execute();
                                            $update_stmt2->close();
                                        }
                                    }else{
                                        if ($product_stmt = $db->prepare("INSERT INTO product_customers (product_id, customer_id, pricing_type, price, purchasing_pricing_type, purchasing_price) VALUES (?, ?, ?, ?, ?, ?)")){
                                            $product_stmt->bind_param('ssssss', $_POST['id'], $customerId, $cPricingType, $cPrice, $cPurchasingPricingType, $cPurchasingPrice);
                                            $product_stmt->execute();
                                            $product_stmt->close();
                                        }
                                    }
                                }
                            }
                            $delete_stmt->close();
                        }
                    }
                }

                # product_grade 
                if (isset($_POST['gradeNo'])){
                    $no = $_POST['gradeNo'];
                    $productGradeId = (array)$_POST['productGradeId'];
                    $grades =  (array)$_POST['grades'];
                    $gradePricingType = (array)$_POST['gradePricingType'];
                    $gradePrice = (array)$_POST['gradePrice'];
                    $gradePurchasingPricingType = (array)$_POST['gradePurchasingPricingType'];
                    $gradePurchasingPrice = (array)$_POST['gradePurchasingPrice'];

                    if(isset($no) && $no != null && count($no) > 0){
                        #  Soft-delete old product_grades
                        $db->query("SET @skip_grade_log = 1");
                        if ($delete_stmt = $db->prepare("UPDATE product_grades SET deleted='1', modified_by=? WHERE product_id=? AND deleted='0'")){
                            $delete_stmt->bind_param('ss', $userID, $_POST['id']);
    
                            // Execute the prepared query.
                            if (! $delete_stmt->execute()) {
                                $db->query("SET @skip_grade_log = NULL");
                                echo json_encode(
                                    array(
                                        "status"=> "failed", 
                                        "message"=> $delete_stmt->error
                                    )
                                );
                            }
                            else{
                                $db->query("SET @skip_grade_log = NULL");
                                foreach($no as $key => $number) {
                                    $gradeId = $grades[$key];
                                    $gPricingType = $gradePricingType[$key];
                                    $gPrice = $gradePrice[$key];
                                    $gPurchasingPricingType = $gradePurchasingPricingType[$key];
                                    $gPurchasingPrice = $gradePurchasingPrice[$key];

                                    if (isset($productGradeId[$key]) && $productGradeId[$key] != null && $productGradeId[$key] != ''){
                                        $gProductId = $productGradeId[$key];

                                        if ($update_stmt2 = $db->prepare("UPDATE product_grades SET product_id=?, grade_id=?, pricing_type=?, price=?, purchasing_pricing_type=?, purchasing_price=?, modified_by=?, deleted='0' WHERE id=?")){
                                            $update_stmt2->bind_param('ssssssss', $_POST['id'], $gradeId, $gPricingType, $gPrice, $gPurchasingPricingType, $gPurchasingPrice, $userID, $gProductId);
                                            $update_stmt2->execute();
                                            $update_stmt2->close();
                                        }
                                    }else{
                                        if ($product_stmt = $db->prepare("INSERT INTO product_grades (product_id, grade_id, pricing_type, price, purchasing_pricing_type, purchasing_price, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)")){
                                            $product_stmt->bind_param('sssssss', $_POST['id'], $gradeId, $gPricingType, $gPrice, $gPurchasingPricingType, $gPurchasingPrice, $userID);
                                            $product_stmt->execute();
                                            $product_stmt->close();
                                        }
                                    }
                                }
                            }
                        } 

                        $delete_stmt->close();
                    }
                }else{
                    #  Soft-delete old product_grades
                    if ($delete_stmt = $db->prepare("UPDATE product_grades SET deleted='1', modified_by=? WHERE product_id=? AND deleted='0'")){
                        $delete_stmt->bind_param('ss', $userID, $_POST['id']);
                        $delete_stmt->execute();
                        $delete_stmt->close();
                    }

                }

                $update_stmt->close();

                // Handle image upload for UPDATE
                if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
                    $imgCheck = $db->prepare("SELECT product_image FROM products WHERE id=?");
                    $imgCheck->bind_param('s', $_POST['id']);
                    $imgCheck->execute();
                    $imgRow = $imgCheck->get_result()->fetch_assoc();
                    $imgCheck->close();
                    if ($imgRow && $imgRow['product_image']) {
                        deleteOldFile($imgRow['product_image'], $db);
                    }
                    $result = uploadFile($_FILES['productImage'], 'photo', $_POST['id'], $db);
                    if ($result['status'] === 'success' && $result['fid']) {
                        $fid = (string)$result['fid'];
                        $imgStmt = $db->prepare("UPDATE products SET product_image=? WHERE id=?");
                        $imgStmt->bind_param('ss', $fid, $_POST['id']);
                        $imgStmt->execute();
                        $imgStmt->close();
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
        }
    }
    else{
        if ($insert_stmt = $db->prepare("INSERT INTO products (product_code, product_name, product_sn, batch_no, parts_no, uom, remark, pricing_type, price, purchasing_pricing_type, purchasing_price, weight, customer, range_set, ok_weight, ok_weight_unit, lo_weight, lo_weight_unit, hi_weight, hi_weight_unit, category, packaging, state, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
            $insert_stmt->bind_param('ssssssssssssssssssssssss', $code, $product, $serial, $batch, $part, $uom, $remark, $pricingType, $price, $purchasingPricingType, $purchasingPrice, $weight, $company, $rangeSet, $okWeight, $okWeightUnit, $loWeight, $loWeightUnit, $hiWeight, $hiWeightUnit, $productCategory, $productPackaging, $state, $userID);
            
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
                $productId = $insert_stmt->insert_id;

                // Handle image upload for INSERT
                if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
                    $result = uploadFile($_FILES['productImage'], 'photo', $productId, $db);
                    if ($result['status'] === 'success' && $result['fid']) {
                        $fid = (string)$result['fid'];
                        $imgStmt = $db->prepare("UPDATE products SET product_image=? WHERE id=?");
                        $imgStmt->bind_param('ss', $fid, $productId);
                        $imgStmt->execute();
                        $imgStmt->close();
                    }
                }

                # product_customers
                if(isset($_POST['no'])){
                    $no = $_POST['no'];
                    $customers =  $_POST['customers'];
                    $customerPricingType = $_POST['customerPricingType'];
                    $customerPrice = $_POST['customerPrice'];
                    $customerPurchasingPricingType = $_POST['customerPurchasingPricingType'];
                    $customerPurchasingPrice = $_POST['customerPurchasingPrice'];

                    if(isset($no) && $no != null && count($no) > 0){
                        foreach ($no as $key => $number) {
                            if ($product_stmt = $db->prepare("INSERT INTO product_customers (product_id, customer_id, pricing_type, price, purchasing_pricing_type, purchasing_price) VALUES (?, ?, ?, ?, ?, ?)")){
                                $product_stmt->bind_param('ssssss', $productId, $customers[$key], $customerPricingType[$key], $customerPrice[$key], $customerPurchasingPricingType[$key], $customerPurchasingPrice[$key]);
                                $product_stmt->execute();
                                $product_stmt->close();
                            }
                        }
                    }
                }

                # product_customers
                if(isset($_POST['gradeNo'])){
                    $no = $_POST['gradeNo'];
                    $grades = $_POST['grades'];
                    $gradePricingType = $_POST['gradePricingType'];
                    $gradePrice = $_POST['gradePrice'];
                    $gradePurchasingPricingType = $_POST['gradePurchasingPricingType'];
                    $gradePurchasingPrice = $_POST['gradePurchasingPrice'];

                    if(isset($no) && $no != null && count($no) > 0){
                        foreach ($no as $key => $number) {
                            if ($product_stmt = $db->prepare("INSERT INTO product_grades (product_id, grade_id, pricing_type, price, purchasing_pricing_type, purchasing_price, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)")){
                                $product_stmt->bind_param('sssssss', $productId, $grades[$key], $gradePricingType[$key], $gradePrice[$key], $gradePurchasingPricingType[$key], $gradePurchasingPrice[$key], $userID);
                                $product_stmt->execute();
                                $product_stmt->close();
                            }
                        }
                    }
                }

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