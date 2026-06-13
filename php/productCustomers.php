<?php
require_once "db_connect.php";
session_start();

if (isset($_POST['product_id']) && $_POST['product_id'] != '') {
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_STRING);

    // Soft-delete all existing product_customers for this product
    if ($delete_stmt = $db->prepare("UPDATE product_customers SET deleted='1' WHERE product_id=?")) {
        $delete_stmt->bind_param('s', $productId);
        if (!$delete_stmt->execute()) {
            echo json_encode(["status" => "failed", "message" => $delete_stmt->error]);
            exit();
        }
        $delete_stmt->close();
    }

    // Re-insert / upsert rows if submitted
    if (isset($_POST['no']) && count($_POST['no']) > 0) {
        $no                          = $_POST['no'];
        $customers                   = (array)$_POST['customers'];
        $customerProductId           = (array)$_POST['customerProductId'];
        $customerGrade               = isset($_POST['customerGrade']) ? (array)$_POST['customerGrade'] : [];
        $customerPricingType         = (array)$_POST['customerPricingType'];
        $customerPrice               = (array)$_POST['customerPrice'];
        $customerPurchasingPricingType = (array)$_POST['customerPurchasingPricingType'];
        $customerPurchasingPrice     = (array)$_POST['customerPurchasingPrice'];

        foreach ($no as $key => $number) {
            $customerId        = $customers[$key];
            $cGradeId          = isset($customerGrade[$key]) && $customerGrade[$key] != '' ? $customerGrade[$key] : null;
            $cPricingType      = $customerPricingType[$key];
            $cPrice            = $customerPrice[$key];
            $cPurchasingType   = $customerPurchasingPricingType[$key];
            $cPurchasingPrice  = $customerPurchasingPrice[$key];

            if (isset($customerProductId[$key]) && $customerProductId[$key] != '') {
                $cProductId = $customerProductId[$key];
                if ($stmt = $db->prepare("UPDATE product_customers SET product_id=?, customer_id=?, grade_id=?, pricing_type=?, price=?, purchasing_pricing_type=?, purchasing_price=?, deleted='0' WHERE id=?")) {
                    $stmt->bind_param('ssssssss', $productId, $customerId, $cGradeId, $cPricingType, $cPrice, $cPurchasingType, $cPurchasingPrice, $cProductId);
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                if ($stmt = $db->prepare("INSERT INTO product_customers (product_id, customer_id, grade_id, pricing_type, price, purchasing_pricing_type, purchasing_price) VALUES (?, ?, ?, ?, ?, ?, ?)")) {
                    $stmt->bind_param('sssssss', $productId, $customerId, $cGradeId, $cPricingType, $cPrice, $cPurchasingType, $cPurchasingPrice);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }

    echo json_encode(["status" => "success", "message" => "Customers updated successfully."]);
} else {
    echo json_encode(["status" => "failed", "message" => "Missing product ID."]);
}
?>
