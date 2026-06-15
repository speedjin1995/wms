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

    // Re-insert customer rows
    if (isset($_POST['no']) && count($_POST['no']) > 0) {
        $no = $_POST['no'];
        $customers = (array)$_POST['customers'];
        $customerProductId = (array)$_POST['customerProductId'];
        $customerGrade = isset($_POST['customerGrade']) ? (array)$_POST['customerGrade'] : [];
        $customerPricingType = (array)$_POST['customerPricingType'];
        $customerPrice = (array)$_POST['customerPrice'];

        foreach ($no as $key => $number) {
            $customerId = $customers[$key];
            $cGradeId = isset($customerGrade[$key]) && $customerGrade[$key] != '' ? $customerGrade[$key] : null;
            $cPricingType = $customerPricingType[$key];
            $cPrice = $customerPrice[$key];

            if (isset($customerProductId[$key]) && $customerProductId[$key] != '') {
                $cProductId = $customerProductId[$key];
                if ($stmt = $db->prepare("UPDATE product_customers SET product_id=?, customer_id=?, grade_id=?, pricing_type=?, price=?, deleted='0' WHERE id=?")) {
                    $stmt->bind_param('ssssss', $productId, $customerId, $cGradeId, $cPricingType, $cPrice, $cProductId);
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                if ($stmt = $db->prepare("INSERT INTO product_customers (product_id, customer_id, grade_id, pricing_type, price) VALUES (?, ?, ?, ?, ?)")) {
                    $stmt->bind_param('sssss', $productId, $customerId, $cGradeId, $cPricingType, $cPrice);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }

    // Soft-delete all existing product_suppliers for this product
    if ($delete_stmt = $db->prepare("UPDATE product_suppliers SET deleted='1' WHERE product_id=?")) {
        $delete_stmt->bind_param('s', $productId);
        if (!$delete_stmt->execute()) {
            echo json_encode(["status" => "failed", "message" => $delete_stmt->error]);
            exit();
        }
        $delete_stmt->close();
    }

    // Re-insert supplier rows
    if (isset($_POST['supplierNo']) && count($_POST['supplierNo']) > 0) {
        $supplierNo = $_POST['supplierNo'];
        $suppliers = (array)$_POST['suppliers'];
        $supplierProductId = (array)$_POST['supplierProductId'];
        $supplierGrade = isset($_POST['supplierGrade']) ? (array)$_POST['supplierGrade'] : [];
        $supplierPricingType = (array)$_POST['supplierPricingType'];
        $supplierPrice = (array)$_POST['supplierPrice'];

        foreach ($supplierNo as $key => $number) {
            $supplierId = $suppliers[$key];
            $sGradeId = isset($supplierGrade[$key]) && $supplierGrade[$key] != '' ? $supplierGrade[$key] : null;
            $sPricingType = $supplierPricingType[$key];
            $sPrice = $supplierPrice[$key];

            if (isset($supplierProductId[$key]) && $supplierProductId[$key] != '') {
                $sProductId = $supplierProductId[$key];
                if ($stmt = $db->prepare("UPDATE product_suppliers SET product_id=?, supplier_id=?, grade_id=?, purchasing_pricing_type=?, purchasing_price=?, deleted='0' WHERE id=?")) {
                    $stmt->bind_param('ssssss', $productId, $supplierId, $sGradeId, $sPricingType, $sPrice, $sProductId);
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                if ($stmt = $db->prepare("INSERT INTO product_suppliers (product_id, supplier_id, grade_id, purchasing_pricing_type, purchasing_price) VALUES (?, ?, ?, ?, ?)")) {
                    $stmt->bind_param('sssss', $productId, $supplierId, $sGradeId, $sPricingType, $sPrice);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }

    echo json_encode(["status" => "success", "message" => "Saved successfully."]);
} else {
    echo json_encode(["status" => "failed", "message" => "Missing product ID."]);
}
?>
