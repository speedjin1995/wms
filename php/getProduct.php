<?php
require_once "db_connect.php";

session_start();

if(isset($_POST['userID'])){
	$id = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_STRING);
    $type = null;

    if(isset($_POST['type']) && $_POST['type'] != null && $_POST['type'] != ''){
        $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
    }

    if ($type == 'getPrice'){
        $customerID = null;
        $grade = null;
        
        if(isset($_POST['customerID']) && $_POST['customerID'] != null && $_POST['customerID'] != ''){
            $customerID = filter_input(INPUT_POST, 'customerID', FILTER_SANITIZE_STRING);
        }

        if(isset($_POST['grade']) && $_POST['grade'] != null && $_POST['grade'] != ''){
            $grade = filter_input(INPUT_POST, 'grade', FILTER_SANITIZE_STRING);
        }

        // Final Pricing Detail
        $resultPricingType = null;
        $resultPrice = 0;

        // Product Pricing Detail
        $productPricingType = null;
        $productPrice = 0;

        $product_stmt = $db->prepare("SELECT * FROM products WHERE id=?");
        $product_stmt->bind_param('s', $id);
        $product_stmt->execute();
        $product_result = $product_stmt->get_result();
        if ($product_result->num_rows > 0) {
            while ($row = $product_result->fetch_assoc()) {
                $productPricingType = $row['pricing_type'];
                $productPrice = $row['price'];
            }
        }else{
            echo json_encode(
                array(
                    "status" => "failed",
                    "message" => "Cannot find product"
                ));
        }

        if (!empty($customerID)){
            $pricingType = null;
            $price = 0;
            // Query product_customers
            $productCustomerStmt = $db->prepare("SELECT * FROM product_customers WHERE product_id=? AND customer_id=? AND deleted=0");
            $productCustomerStmt->bind_param('ss', $id, $customerID);
            $productCustomerStmt->execute();
            $result = $productCustomerStmt->get_result();

            if ($result->num_rows > 0) {
                // If customer have pricing
                while ($row = $result->fetch_assoc()) {
                    $pricingType = $row['pricing_type'];
                    $price = $row['price'];
                }

                // If pricing type is Standard then need to take product price
                if ($pricingType == 'Standard'){
                    $resultPricingType = $productPricingType;
                    $resultPrice = $productPrice;
                }else{
                    $resultPricingType = $pricingType;
                    $resultPrice = $price;
                }

                $pricingDetail = [
                    'pricingType' => $resultPricingType,
                    'price' => $resultPrice,
                ];

                echo json_encode(
                    array(
                        "status" => "success",
                        "message" => $pricingDetail
                    ));
            } else {
                // If customer have no pricing, check grade
                if (!empty($grade)){
                    $productGradeStmt = $db->prepare("SELECT * FROM product_grades WHERE product_id=? AND grade_id=? AND deleted=0");
                    $productGradeStmt->bind_param('ss', $id, $grade);
                    $productGradeStmt->execute();
                    $productGradeResult = $productGradeStmt->get_result();
                    if ($productGradeResult->num_rows > 0) {
                        // If grade has pricing
                        while ($row = $productGradeResult->fetch_assoc()) {
                            $pricingType = $row['pricing_type'];
                            $price = $row['price'];
                        }

                        // If pricing type is Standard then need to take product price
                        if ($pricingType == 'Standard'){
                            $resultPricingType = $productPricingType;
                            $resultPrice = $productPrice;
                        }else{
                            $resultPricingType = $pricingType;
                            $resultPrice = $price;
                        }

                        $pricingDetail = [
                            'pricingType' => $resultPricingType,
                            'price' => $resultPrice,
                        ];

                        echo json_encode(
                            array(
                                "status" => "success",
                                "message" => $pricingDetail
                            ));
                    }else{
                        // If grade no pricing, take product pricing
                        $pricingDetail = [
                            'pricingType' => $productPricingType,
                            'price' => $productPrice,
                        ];

                        echo json_encode(
                            array(
                                "status" => "success",
                                "message" => $pricingDetail
                            ));
                    }
                }else{
                    // If customer no pricing and grade also no pricing, take product pricing
                    $pricingDetail = [
                        'pricingType' => $productPricingType,
                        'price' => $productPrice,
                    ];

                    echo json_encode(
                        array(
                            "status" => "success",
                            "message" => $pricingDetail
                        ));
                }
                
            }
        } else if (empty($customerID) && !empty($grade)){
            $productGradeStmt = $db->prepare("SELECT * FROM product_grades WHERE product_id=? AND grade_id=? AND deleted=0");
            $productGradeStmt->bind_param('ss', $id, $grade);
            $productGradeStmt->execute();
            $productGradeResult = $productGradeStmt->get_result();
            if ($productGradeResult->num_rows > 0) {
                // If grade has pricing
                while ($row = $productGradeResult->fetch_assoc()) {
                    $pricingType = $row['pricing_type'];
                    $price = $row['price'];
                }

                // If pricing type is Standard then need to take product price
                if ($pricingType == 'Standard'){
                    $resultPricingType = $productPricingType;
                    $resultPrice = $productPrice;
                }else{
                    $resultPricingType = $pricingType;
                    $resultPrice = $price;
                }

                $pricingDetail = [
                    'pricingType' => $resultPricingType,
                    'price' => $resultPrice,
                ];

                echo json_encode(
                    array(
                        "status" => "success",
                        "message" => $pricingDetail
                    ));
            }else{
                // If grade no pricing, take product pricing
                $pricingDetail = [
                    'pricingType' => $productPricingType,
                    'price' => $productPrice,
                ];

                echo json_encode(
                    array(
                        "status" => "success",
                        "message" => $pricingDetail
                    ));
            }
        } else {
            $pricingDetail = [
                'pricingType' => $productPricingType,
                'price' => $productPrice,
            ];

            echo json_encode(
                array(
                    "status" => "success",
                    "message" => $pricingDetail
                ));
        }
    }else{
        if ($update_stmt = $db->prepare("SELECT * FROM products WHERE id=?")) {
            $update_stmt->bind_param('s', $id);
            
            // Execute the prepared query.
            if (! $update_stmt->execute()) {
                echo json_encode(
                    array(
                        "status" => "failed",
                        "message" => "Something went wrong"
                    )); 
            }
            else{
                $result = $update_stmt->get_result();
                $message = array();
                
                while ($row = $result->fetch_assoc()) {
                    $message['id'] = $row['id'];
                    $message['product_code'] = $row['product_code'];
                    $message['product_name'] = $row['product_name'];
                    $message['product_sn'] = $row['product_sn'];
                    $message['batch_no'] = $row['batch_no'];
                    $message['parts_no'] = $row['parts_no'];
                    $message['uom'] = $row['uom'];
                    $message['remark'] = $row['remark'];
                    $message['pricing_type'] = $row['pricing_type'];
                    $message['price'] = $row['price'];
                    $message['weight'] = $row['weight'];
                    $message['customer'] = $row['customer'];
                    $message['range_set'] = $row['range_set'];
                    $message['ok_weight'] = $row['ok_weight'];
                    $message['ok_weight_unit'] = $row['ok_weight_unit'];
                    $message['lo_weight'] = $row['lo_weight'];
                    $message['lo_weight_unit'] = $row['lo_weight_unit'];
                    $message['hi_weight'] = $row['hi_weight'];
                    $message['hi_weight_unit'] = $row['hi_weight_unit'];

                    // retrieve product customers
                    $empQuery = "SELECT * FROM product_customers WHERE product_id = $id AND deleted = '0' ORDER BY id ASC";
                    $empRecords = mysqli_query($db, $empQuery);
                    $productCustomers = array();
                    $productCustomerCount = 1;

                    while($row2 = mysqli_fetch_assoc($empRecords)) {
                        $productCustomers[] = array(
                            "no" => $productCustomerCount,
                            "id" => $row2['id'],
                            "product_id" => $row2['product_id'],
                            "customer_id" => $row2['customer_id'],
                            "pricing_type" => $row2['pricing_type'],
                            "price" => $row2['price']
                        );
                        $productCustomerCount++;
                    }

                    $message['productCustomers'] = $productCustomers;

                    // retrieve product grades
                    $empQuery = "SELECT * FROM product_grades WHERE product_id = $id AND deleted = '0' ORDER BY id ASC";
                    $empRecords = mysqli_query($db, $empQuery);
                    $productGrades = array();
                    $productGradeCount = 1;

                    while($row2 = mysqli_fetch_assoc($empRecords)) {
                        $productGrades[] = array(
                            "no" => $productGradeCount,
                            "id" => $row2['id'],
                            "product_id" => $row2['product_id'],
                            "grade_id" => $row2['grade_id'],
                            "pricing_type" => $row2['pricing_type'],
                            "price" => $row2['price']
                        );
                        $productGradeCount++;
                    }
                    $message['productGrades'] = $productGrades;
                }
                
                echo json_encode(
                    array(
                        "status" => "success",
                        "message" => $message
                    ));   
            }
        }
    }


    
}
else{
    echo json_encode(
        array(
            "status" => "failed",
            "message" => "Missing Attribute"
            )); 
}
?>