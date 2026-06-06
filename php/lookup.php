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

function searchCustomerParentById($value, $db) {
    $id = '';

    if(isset($value)){
        if ($select_stmt = $db->prepare("SELECT * FROM customers WHERE id=?")) {
            $select_stmt->bind_param('s', $value);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $id = $row['parent'];
            }
            $select_stmt->close();
        }
    }

    return $id;
}

function searchCustomerIdByName($value, $company, $db) {
    $id = null;

    if(isset($value)){
        if ($select_stmt = $db->prepare("SELECT * FROM customers WHERE customer_name=? AND customer=? AND deleted = 0")) {
            $select_stmt->bind_param('ss', $value, $company);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $id = $row['id'];
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

function searchSupplierParentById($value, $db) {
    $id = '';

    if(isset($value)){
        if ($select_stmt = $db->prepare("SELECT * FROM supplies WHERE id=?")) {
            $select_stmt->bind_param('s', $value);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $id = $row['parent'];
            }
            $select_stmt->close();
        }
    }

    return $id;
}

function searchSupplierIdByName($value, $company, $db) {
    $id = null;

    if(isset($value)){
        if ($select_stmt = $db->prepare("SELECT * FROM supplies WHERE supplier_name=? AND customer=? AND deleted = 0")) {
            $select_stmt->bind_param('ss', $value, $company);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $id = $row['id'];
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

function searchDriverIcByDriverName($value, $company, $db) {
    $id = '';

    if(isset($value)){
        if ($select_stmt = $db->prepare("SELECT * FROM drivers WHERE driver_name=? AND customer=? AND deleted = 0")) {
            $select_stmt->bind_param('ss', $value, $company);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $id = $row['driver_ic'];
            }
            $select_stmt->close();
        }
    }

    return $id;
}

function searchDriverIdByDriverName($value, $company, $db) {
    $id = '';

    if(isset($value)){
        if ($select_stmt = $db->prepare("SELECT * FROM drivers WHERE driver_name=? AND customer=? AND deleted = 0")) {
            $select_stmt->bind_param('ss', $value, $company);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $id = $row['id'];
            }
            $select_stmt->close();
        }
    }

    return $id;
}

function checkMasterDataVehicle($value, $company, $db) {
    $exists = true;

    if(isset($value)){
        if ($select_stmt = $db->prepare("SELECT * FROM vehicles WHERE veh_number=? AND customer=? AND deleted = 0")) {
            $select_stmt->bind_param('ss', $value, $company);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $exists = false;
            }
            $select_stmt->close();
        }
    }

    return $exists;
}

function searchStateIdByName($value, $db) {
    $id = '';

    if(isset($value)){
        if ($select_stmt = $db->prepare("SELECT * FROM states WHERE states=?")) {
            $select_stmt->bind_param('s', $value);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $id = $row['id'];
            }
            $select_stmt->close();
        }
    }

    return $id;
}

function getStatesByIds($stateJson, $db) {
    $ids = json_decode($stateJson, true);
    if (empty($ids)) {
        return null;
    }

    $idList = implode(',', array_map('intval', $ids));
    $result = $db->query("SELECT states FROM states WHERE id IN ($idList)");
    $names = [];
    while ($r = $result->fetch_assoc()) {
        $names[] = $r['states'];
    }

    return $names;
}

function formatModules($value, $languageArray, $language) {
    $id = '';

    if(isset($value)){
        switch ($value) {
            case 'industrial':
                $id = $languageArray['pulp_and_paste_code'][$language];
                break;
            case 'weighing':
                $id = $languageArray['weighbridge_code'][$language];
                break;
            case 'wholesales':
                $id = $languageArray['wholesales_code'][$language];
                break;
            case 'packing':
                $id = $languageArray['packing_code'][$language];
                break;
            case 'pricing':
                $id = $languageArray['pricing_code'][$language];
                break;
            default:
                $id = 'N/A';
        }
    }

    return $id;
}

function searchCategoryById($value, $db) {
    $id = '';

    if(isset($value)){
        if ($select_stmt = $db->prepare("SELECT * FROM categories WHERE id=?")) {
            $select_stmt->bind_param('s', $value);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $id = $row['category_name'];
            }
            $select_stmt->close();
        }
    }

    return $id;
}

function searchLocationById($value, $db) {
    $id = '';

    if(isset($value)){
        if ($select_stmt = $db->prepare("SELECT * FROM locations WHERE id=?")) {
            $select_stmt->bind_param('s', $value);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $id = $row['locations'];
            }
            $select_stmt->close();
        }
    }

    return $id;
}
