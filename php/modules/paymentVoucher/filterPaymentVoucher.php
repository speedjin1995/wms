<?php
## Database configuration
require_once '../../db_connect.php';
require_once '../../lookup.php';
session_start();

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length'];
$columnIndex = $_POST['order'][0]['column'];
$columnName = $_POST['columns'][$columnIndex]['data'];
$columnSortOrder = $_POST['order'][0]['dir'];
$searchValue = mysqli_real_escape_string($db, $_POST['search']['value']);

$module = $_SESSION['module'] ?? 'wholesales';
$incomingStatus = isset($_POST['transactionStatus']) && $_POST['transactionStatus'] != '' ? mysqli_real_escape_string($db, $_POST['transactionStatus']) : (($module === 'industrial') ? 'INCOMING' : 'RECEIVING');
$recordType = ($module === 'industrial') ? 'industrial' : 'wholesales';

## Search
$isIncoming = in_array($incomingStatus, ['RECEIVING', 'INCOMING']);

$searchQuery = " AND w.deleted = 0"
             . " AND w.status = '$incomingStatus'"
             . " AND w.records_type = '$recordType'";

if ($isIncoming) {
    $searchQuery .= " AND w.supplier IS NOT NULL AND s.parent IS NOT NULL";
} else {
    $searchQuery .= " AND w.customer IS NOT NULL AND c.parent IS NOT NULL";
}

if($_POST['fromDate'] != null && $_POST['fromDate'] != ''){
    $dateTime = DateTime::createFromFormat('d/m/Y', $_POST['fromDate']);
    $fromDateTime = $dateTime->format('Y-m-d 00:00:00');
    $searchQuery .= " AND w.created_datetime >= '".$fromDateTime."'";
}

if($_POST['toDate'] != null && $_POST['toDate'] != ''){
    $dateTime = DateTime::createFromFormat('d/m/Y', $_POST['toDate']);
    $toDateTime = $dateTime->format('Y-m-d 23:59:59');
    $searchQuery .= " AND w.created_datetime <= '".$toDateTime."'";
}

if ($isIncoming) {
    if(isset($_POST['supplierId']) && $_POST['supplierId'] != null && $_POST['supplierId'] != ''){
        $searchQuery .= " AND CAST(w.supplier AS UNSIGNED) = '".(int)$_POST['supplierId']."'";
    }
    if(isset($_POST['parentSupplierId']) && $_POST['parentSupplierId'] != null && $_POST['parentSupplierId'] != ''){
        $searchQuery .= " AND s.parent = '".(int)$_POST['parentSupplierId']."'";
    }
} else {
    if(isset($_POST['customerId']) && $_POST['customerId'] != null && $_POST['customerId'] != ''){
        $searchQuery .= " AND CAST(w.customer AS UNSIGNED) = '".(int)$_POST['customerId']."'";
    }
    if(isset($_POST['parentCustomerId']) && $_POST['parentCustomerId'] != null && $_POST['parentCustomerId'] != ''){
        $searchQuery .= " AND c.parent = '".(int)$_POST['parentCustomerId']."'";
    }
}

## Search value
if($searchValue != ''){
    if ($isIncoming) {
        $searchQuery .= " AND (w.serial_no LIKE '%".$searchValue."%' OR sp.supplier_name LIKE '%".$searchValue."%')";
    } else {
        $searchQuery .= " AND (w.serial_no LIKE '%".$searchValue."%' OR cp.customer_name LIKE '%".$searchValue."%')";
    }
}

$company = $_SESSION['customer'];
$user = $_SESSION['userID'];
$role = $_SESSION['role'];

if($role != 'SADMIN'){
    $companyFilter = " AND w.company = '".$company."'";
}else{
    $companyFilter = '';
}

## Total number of records without filtering
if ($isIncoming) {
    $noFilterJoin = "FROM wholesales w
                INNER JOIN supplies s ON CAST(w.supplier AS UNSIGNED) = s.id
                INNER JOIN supplies sp ON s.parent = sp.id
                LEFT JOIN payment_vouchers pv ON w.pv_id = pv.id AND pv.deleted = 0";
    $noFilterWhere = "WHERE w.deleted = 0 AND w.status = '$incomingStatus' AND w.records_type = '$recordType' AND w.supplier IS NOT NULL AND s.parent IS NOT NULL ".$companyFilter;
    $groupBy = "COALESCE(CAST(pv.id AS CHAR), CAST(s.parent AS CHAR))";
} else {
    $noFilterJoin = "FROM wholesales w
                INNER JOIN customers c ON CAST(w.customer AS UNSIGNED) = c.id
                INNER JOIN customers cp ON c.parent = cp.id
                LEFT JOIN payment_vouchers pv ON w.pv_id = pv.id AND pv.deleted = 0";
    $noFilterWhere = "WHERE w.deleted = 0 AND w.status = '$incomingStatus' AND w.records_type = '$recordType' AND w.customer IS NOT NULL AND c.parent IS NOT NULL ".$companyFilter;
    $groupBy = "COALESCE(CAST(pv.id AS CHAR), CAST(c.parent AS CHAR))";
}

$sel = mysqli_query($db, "SELECT COUNT(DISTINCT $groupBy) as allcount $noFilterJoin $noFilterWhere");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of records with filtering
if ($isIncoming) {
    $filterJoin = "FROM wholesales w
        INNER JOIN supplies s ON CAST(w.supplier AS UNSIGNED) = s.id
        INNER JOIN supplies sp ON s.parent = sp.id
        LEFT JOIN payment_vouchers pv ON w.pv_id = pv.id AND pv.deleted = 0";
} else {
    $filterJoin = "FROM wholesales w
        INNER JOIN customers c ON CAST(w.customer AS UNSIGNED) = c.id
        INNER JOIN customers cp ON c.parent = cp.id
        LEFT JOIN payment_vouchers pv ON w.pv_id = pv.id AND pv.deleted = 0";
}

$sel = mysqli_query($db, "SELECT COUNT(DISTINCT $groupBy) as allcount $filterJoin WHERE 1=1".$companyFilter.$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
if ($isIncoming) {
    $empQuery = "SELECT
                MAX(w.id) as id,
                MAX(w.status) as status,
                MAX(w.weight_type) as weight_type,
                MAX(s.parent) as parent_id,
                MAX(sp.supplier_name) as entity_name,
                MAX(w.created_datetime) as latest_date,
                MAX(pv.id) as pv_id,
                MAX(pv.voucher_no) as voucher_no,
                MAX(pv.voucher_date) as pv_voucher_date,
                MAX(pv.invoice_no) as invoice_no,
                MAX(pv.final_amount) as final_amount,
                SUM(CAST(w.total_weight AS DECIMAL(10,2))) as total_nett_weight
             FROM wholesales w
             INNER JOIN supplies s ON CAST(w.supplier AS UNSIGNED) = s.id
             INNER JOIN supplies sp ON s.parent = sp.id
             LEFT JOIN payment_vouchers pv ON w.pv_id = pv.id AND pv.deleted = 0
             WHERE 1=1".$companyFilter.$searchQuery."
             GROUP BY $groupBy
             ORDER BY ".$columnName." ".$columnSortOrder."
             LIMIT ".$row.",".$rowperpage;
} else {
    $empQuery = "SELECT
                MAX(w.id) as id,
                MAX(w.status) as status,
                MAX(w.weight_type) as weight_type,
                MAX(c.parent) as parent_id,
                MAX(cp.customer_name) as entity_name,
                MAX(w.created_datetime) as latest_date,
                MAX(pv.id) as pv_id,
                MAX(pv.voucher_no) as voucher_no,
                MAX(pv.voucher_date) as pv_voucher_date,
                MAX(pv.invoice_no) as invoice_no,
                MAX(pv.final_amount) as final_amount,
                SUM(CAST(w.total_weight AS DECIMAL(10,2))) as total_nett_weight
             FROM wholesales w
             INNER JOIN customers c ON CAST(w.customer AS UNSIGNED) = c.id
             INNER JOIN customers cp ON c.parent = cp.id
             LEFT JOIN payment_vouchers pv ON w.pv_id = pv.id AND pv.deleted = 0
             WHERE 1=1".$companyFilter.$searchQuery."
             GROUP BY $groupBy
             ORDER BY ".$columnName." ".$columnSortOrder."
             LIMIT ".$row.",".$rowperpage;
}

$empRecords = mysqli_query($db, $empQuery);
$data = array();

while($r = mysqli_fetch_assoc($empRecords)){
    $data[] = array(
        "id" => $r['id'],
        "parent_id" => $r['parent_id'],
        "entity_name" => $r['entity_name'],
        "voucher_date" => ($r['pv_voucher_date'] != null ? date('d/m/Y', strtotime($r['pv_voucher_date'])) : null),
        "voucher_no" => $r['voucher_no'] ?? '-',
        "invoice_no" => $r['invoice_no'] ?? '-',
        "final_amount" => ($r['final_amount'] ? number_format($r['final_amount'], 2) : '0.00'),
        "total_nett_weight" => number_format($r['total_nett_weight'], 2),
        "pv_id" => $r['pv_id'] ?? '',
    );
}

## Response
$response = array(
    "draw" => intval($draw),
    "iTotalRecords" => $totalRecords,
    "iTotalDisplayRecords" => $totalRecordwithFilter,
    "aaData" => $data
);

echo json_encode($response);

?>
