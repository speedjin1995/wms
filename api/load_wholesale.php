<?php
require_once 'db_connect.php';

session_start();

$post = json_decode(file_get_contents('php://input'), true);
$now = date("Y-m-d H:i:s");
$userId = $post['uid'];
$company = $post['userId'];

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;

if ($page < 1) $page = 1;
if ($limit < 1) $limit = 20;

$offset = ($page - 1) * $limit;

// ==============================
// Filters
// ==============================
$serial_no= $_GET['serial_no'] ?? '';
$vehicle  = $_GET['vehicle'] ?? '';
$status   = $_GET['status'] ?? '';
$customer = $_GET['customer'] ?? '';
$supplier = $_GET['supplier'] ?? '';
$start    = $_GET['start'] ?? '';
$end      = $_GET['end'] ?? '';

// ==============================
// Build WHERE conditions
// ==============================
$where = [];
$params = [];
$types = "";

// Mandatory conditions
$where[] = "wholesales.deleted = '0'";
$where[] = "wholesales.company = '$company'";

// Optional filters
if ($serial_no != '') {
    $where[] = "wholesales.po_no LIKE ?";
    $params[] = "%$serial_no%";
    $types .= "s";
}

if ($vehicle != '') {
    $where[] = "wholesales.vehicle_no LIKE ?";
    $params[] = "%$vehicle%";
    $types .= "s";
}

if ($status != '') {
    $where[] = "wholesales.status LIKE ?";
    $params[] = "%$status%";
    $types .= "s";
}

if ($customer != '') {
    $where[] = "wholesales.customer LIKE ?";
    $params[] = "%$customer%";
    $types .= "s";
}

if ($supplier != '') {
    $where[] = "wholesales.supplier LIKE ?";
    $params[] = "%$supplier%";
    $types .= "s";
}

if ($start !== '' && $end !== '') {
    // Convert millis → UTC DateTime
    $startUTC = new DateTime("@".($start/1000));
    $endUTC   = new DateTime("@".($end/1000));

    // Convert UTC → KL timezone
    $tz = new DateTimeZone("Asia/Kuala_Lumpur");
    $startUTC->setTimezone($tz);
    $endUTC->setTimezone($tz);

    // Extract KL calendar dates
    $startDay = $startUTC->format("Y-m-d");
    $endDay   = $endUTC->format("Y-m-d");

    // Build full-day KL range
    $startDate = $startDay . " 00:00:00";
    $endDate   = $endDay   . " 23:59:59";
    
    // booking_date stored as DATETIME
    $where[] = "wholesales.created_datetime BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= "ss";
}

$whereSql = "WHERE " . implode(" AND ", $where);

$dataSql = "
    SELECT wholesales.*, users.name
    FROM wholesales
    JOIN users ON wholesales.created_by = users.id
    $whereSql
    ORDER BY wholesales.created_datetime DESC
    LIMIT ? OFFSET ?
";

$stmt = $db->prepare($dataSql);
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$message = array();

while($row = $result->fetch_assoc()){
    $customerName = '';
    $supplierName = '';
    
    if($row['status'] == 'DISPATCH'){
        $farmId=$row['customer'];
        
        if($farmId != 'OTHERS' && $farmId != null){
            if ($update_stmt = $db->prepare("SELECT * FROM customers WHERE id=?")) {
                $update_stmt->bind_param('s', $farmId);
                
                if ($update_stmt->execute()) {
                    $result3 = $update_stmt->get_result();
                    
                    if ($row3 = $result3->fetch_assoc()) {
                        $customerName=$row3['customer_name'];
                    }
                }
            }
        }
        else{
            $customerName=$row['other_customer'];
        }
    }
    else{
        $farmId=$row['supplier'];
        
        if($farmId != 'OTHERS' && $farmId != null){
            if ($update_stmt = $db->prepare("SELECT * FROM supplies WHERE id=?")) {
                $update_stmt->bind_param('s', $farmId);
                
                if ($update_stmt->execute()) {
                    $result3 = $update_stmt->get_result();
                    
                    if ($row3 = $result3->fetch_assoc()) {
                        $supplierName=$row3['supplier_name'];
                    }
                }
            }
        }
        else{
            $supplierName=$row['other_supplier'];
        }
    }
    
    $message[] = array(
        'id'=> $row['id'],
        'serial_no'=> $row['serial_no'],
        'po_no'=> $row['po_no'],
        'security_bills'=> $row['security_bills'],
        'status'=> $row['status'],
        'customer'=> $row['customer'],
        'customer_name'=> $customerName,
        'supplier'=> $row['supplier'],
        'supplier_name'=> $supplierName,
        'vehicle_no'=> $row['vehicle_no'],
        'driver'=> $row['driver'],
        'driver_ic'=> $row['driver_ic'],
        'remark'=> $row['remark'],
        'remark2'=> $row['remarks2'],
        'created_datetime'=> $row['created_datetime'],
        'end_time'=> $row['end_time'],
        'checked_by'=> $row['checked_by'],
        'weighted_by'=> $row['weighted_by'],
        'staffName'=> $row['name'],
        'indicator'=> $row['indicator'],
        'total_item'=> $row['total_item'],
        'total_weight'=> $row['total_weight'],
        'total_reject'=> $row['total_reject'],
        'total_price'=> $row['total_price'],
        'weight' => json_decode($row['weight_details'], true),
        'rejects' => json_decode($row['reject_details'], true)
    );
}

$stmt->close();
$db->close();

echo json_encode(
    array(
        "status"=> "success", 
        "message"=> $message,
        "page" => $page,
        "limit" => $limit,
        "count" => count($message),
        "param" => $params
    )
);
?>
