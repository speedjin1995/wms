<?php
function searchCompanyById($value, $db) {
    $id = '';

    if(isset($value)){
        if ($select_stmt = $db->prepare("SELECT * FROM companies WHERE id=?")) {
            $select_stmt->bind_param('s', $value);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $id = $row;
            }
            $select_stmt->close();
        }
    }

    return $id;
}

function searchCustomerNameById($value, $otherValue, $db) {
    $id = '';

    if(isset($value)){
        if ($value == 'OTHERS'){
            return $otherValue;
        }

        if ($select_stmt = $db->prepare("SELECT * FROM customers WHERE id=?")) {
            $select_stmt->bind_param('s', $value);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $id = $row['customer_name'];
            }
            $select_stmt->close();
        }
    }

    return $id;
}
function searchSupplierNameById($value, $otherValue, $db) {
    $id = '';

    if(isset($value)){
        if ($value == 'OTHERS'){
            return $otherValue;
        }

        if ($select_stmt = $db->prepare("SELECT * FROM supplies WHERE id=?")) {
            $select_stmt->bind_param('s', $value);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $id = $row['supplier_name'];
            }
            $select_stmt->close();
        }
    }

    return $id;
}

function searchProductNameById($value, $db) {
    $id = '';

    if(isset($value)){
        if ($select_stmt = $db->prepare("SELECT * FROM products WHERE id=?")) {
            $select_stmt->bind_param('s', $value);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $id = $row['product_name'];
            }
            $select_stmt->close();
        }
    }

    return $id;
}

function searchUserNameById($value, $db) {
    $id = '';

    if(isset($value)){
        if ($select_stmt = $db->prepare("SELECT * FROM users WHERE id=?")) {
            $select_stmt->bind_param('s', $value);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $id = $row['name'];
            }
            $select_stmt->close();
        }
    }

    return $id;
}

function searchGradesByCompanyId($value, $db) {
    $id = '';

    if(isset($value)){
        if ($select_stmt = $db->prepare("SELECT * FROM grades WHERE id=?")) {
            $select_stmt->bind_param('s', $value);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $id = $row;
            }
            $select_stmt->close();
        }
    }

    return $id;
}