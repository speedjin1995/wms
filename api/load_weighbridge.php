<?php
require_once 'db_connect.php';

$post = json_decode(file_get_contents('php://input'), true);
$now = date("Y-m-d H:i:s");
$userId = $post['userId'];

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
$weight   = $_GET['weight'] ?? '';
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
$where[] = "is_cancel = 'N'";
$where[] = "company = '$userId'";

// Optional filters
if ($serial_no != '') {
    $where[] = "po_no LIKE ?";
    $params[] = "%$serial_no%";
    $types .= "s";
}

if ($vehicle != '') {
    $where[] = "lorry_plate_no1 LIKE ?";
    $params[] = "%$vehicle%";
    $types .= "s";
}

if ($status != '') {
    $where[] = "transaction_status LIKE ?";
    $params[] = "%$status%";
    $types .= "s";
}

if ($customer != '') {
    $where[] = "customer_name LIKE ?";
    $params[] = "%$customer%";
    $types .= "s";
}

if ($supplier != '') {
    $where[] = "supplier_name LIKE ?";
    $params[] = "%$supplier%";
    $types .= "s";
}

if ($weight != '') {
    if($weight == 'Pending'){
        $where[] = "is_complete = ?";
        $params[] = "N";
        $types .= "s";
    }
    else{
        $where[] = "is_complete = ?";
        $params[] = "Y";
        $types .= "s";
    }
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
    $where[] = "transaction_date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= "ss";
}

$whereSql = "WHERE " . implode(" AND ", $where);

$dataSql = "
    SELECT * from Weight 
    $whereSql
    ORDER BY transaction_date DESC
    LIMIT ? OFFSET ?
";

$stmt = $db->prepare($dataSql);
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

/*$stmt = $db->prepare("SELECT * from Weight WHERE is_cancel = 'N' AND company = ? ORDER BY transaction_date DESC");
$stmt->bind_param('s', $userId);
$stmt->execute();
$result = $stmt->get_result();*/
$message = array();

while($row = $result->fetch_assoc()){
    $message[] = array( 
        'id'=>$row['id'],
        'transaction_id'=>$row['transaction_id'],
        'transaction_status' => $row['transaction_status'],
        'customer_name'=>$row['customer_name'],
        'supplier_name'=>$row['supplier_name'],
        'product_name'=>$row['product_name'],
        'lorry_plate_no1'=>$row['lorry_plate_no1'],
        'transporter'=>$row['transporter'],
        'driver_name'=>$row['driver_name'],
        'destination'=>$row['destination'],
        'invoice_no'=>$row['invoice_no'],
        'gross_weight1'=>$row['gross_weight1'],
        'gross_weight1_date'=>$row['gross_weight1_date'],
        'tare_weight1'=>$row['tare_weight1'],
        'tare_weight1_date'=>$row['tare_weight1_date'],
        'nett_weight1'=>$row['nett_weight1'],
        'reduce_weight'=>$row['reduce_weight'],
        'final_weight'=>$row['final_weight'],
        'order_weight'=>$row['order_weight'],
        'weight_different'=>$row['weight_different'],
        'purchase_order'=>$row['purchase_order'],
        'delivery_no'=>$row['delivery_no'],
        'container_no'=>$row['container_no'],
        'seal_no'=>$row['seal_no'],
        'created_by'=>$row['created_by'],
        'created_datetime'=>$row['created_date'],
        'remark'=>$row['remarks'],
        'status'=>$row['is_complete'] == 'Y' ? 'Complete' : 'Pending',
        'manual_weight'=>$row['manual_weight'],
        'unit_price'=>$row['unit_price'],
        'total_price'=>$row['total_price'],
        'sub_total'=>$row['sub_total']
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
