<?php
## Database configuration
require_once '../../db_connect.php';
require_once '../../lookup.php';
session_start();

## Read value
$draw            = $_POST['draw'];
$row             = $_POST['start'];
$rowperpage      = $_POST['length']; // Rows display per page
$columnIndex     = $_POST['order'][0]['column']; // Column index
$columnName      = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
$searchValue     = mysqli_real_escape_string($db, $_POST['search']['value']); // Search value

$module         = $_SESSION['module'] ?? 'wholesales';
$incomingStatus = ($module === 'industrial') ? 'INCOMING' : 'RECEIVING';
$recordType     = ($module === 'industrial') ? 'industrial' : 'wholesales';

## Search
$searchQuery = " AND w.deleted = 0"
             . " AND w.status = '$incomingStatus'"
             . " AND w.records_type = '$recordType'"
             . " AND w.supplier IS NOT NULL"
             . " AND s.parent IS NOT NULL";

if($_POST['fromDate'] != null && $_POST['fromDate'] != ''){
    $dateTime     = DateTime::createFromFormat('d/m/Y', $_POST['fromDate']);
    $fromDateTime = $dateTime->format('Y-m-d 00:00:00');
    $searchQuery .= " AND w.created_datetime >= '".$fromDateTime."'";
}

if($_POST['toDate'] != null && $_POST['toDate'] != ''){
    $dateTime   = DateTime::createFromFormat('d/m/Y', $_POST['toDate']);
    $toDateTime = $dateTime->format('Y-m-d 23:59:59');
    $searchQuery .= " AND w.created_datetime <= '".$toDateTime."'";
}

if(isset($_POST['supplierId']) && $_POST['supplierId'] != null && $_POST['supplierId'] != ''){
    $searchQuery .= " AND CAST(w.supplier AS UNSIGNED) = '".(int)$_POST['supplierId']."'";
}

if(isset($_POST['parentSupplierId']) && $_POST['parentSupplierId'] != null && $_POST['parentSupplierId'] != ''){
    $searchQuery .= " AND s.parent = '".(int)$_POST['parentSupplierId']."'";
}

## Search value
if($searchValue != ''){
    $searchQuery .= " AND (w.serial_no LIKE '%".$searchValue."%'"
                  . " OR sp.supplier_name LIKE '%".$searchValue."%')";
}

$company = $_SESSION['customer'];
$user    = $_SESSION['userID'];
$role    = $_SESSION['role'];

if($role != 'SADMIN'){
    $companyFilter = " AND w.company = '".$company."'";
}else{
    $companyFilter = '';
}

## Total number of records without filtering
$sel          = mysqli_query($db, "SELECT COUNT(DISTINCT COALESCE(CAST(pv.id AS CHAR), CAST(s.parent AS CHAR))) as allcount
                FROM wholesales w
                INNER JOIN supplies s ON CAST(w.supplier AS UNSIGNED) = s.id
                INNER JOIN supplies sp ON s.parent = sp.id
                LEFT JOIN payment_vouchers pv ON w.pv_id = pv.id AND pv.deleted = 0
                WHERE w.deleted = 0
                AND w.status = '$incomingStatus'
                AND w.records_type = '$recordType'
                AND w.supplier IS NOT NULL
                AND s.parent IS NOT NULL
                ".$companyFilter);
$records      = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of records with filtering
$sel = mysqli_query($db, "SELECT COUNT(DISTINCT COALESCE(CAST(pv.id AS CHAR), CAST(s.parent AS CHAR))) as allcount
        FROM wholesales w
        INNER JOIN supplies s ON CAST(w.supplier AS UNSIGNED) = s.id
        INNER JOIN supplies sp ON s.parent = sp.id
        LEFT JOIN payment_vouchers pv ON w.pv_id = pv.id AND pv.deleted = 0
        WHERE 1=1".$companyFilter.$searchQuery);
$records              = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
$empQuery = "SELECT
                MAX(w.id)                                  as id,
                MAX(w.status)                              as status,
                MAX(w.weight_type)                         as weight_type,
                MAX(s.parent)                              as parent_id,
                MAX(sp.supplier_name)                      as supplier_name,
                MAX(w.created_datetime)                    as latest_date,
                MAX(pv.id)                                 as pv_id,
                MAX(pv.voucher_no)                         as voucher_no,
                MAX(pv.voucher_date)                       as pv_voucher_date,
                MAX(pv.invoice_no)                         as invoice_no,
                MAX(pv.final_amount)                       as final_amount,
                SUM(CAST(w.total_weight AS DECIMAL(10,2))) as total_nett_weight
             FROM wholesales w
             INNER JOIN supplies s ON CAST(w.supplier AS UNSIGNED) = s.id
             INNER JOIN supplies sp ON s.parent = sp.id
             LEFT JOIN payment_vouchers pv ON w.pv_id = pv.id AND pv.deleted = 0
             WHERE 1=1".$companyFilter.$searchQuery."
             GROUP BY COALESCE(CAST(pv.id AS CHAR), CAST(s.parent AS CHAR))
             ORDER BY ".$columnName." ".$columnSortOrder."
             LIMIT ".$row.",".$rowperpage;

$empRecords = mysqli_query($db, $empQuery);
$data       = array();

while($r = mysqli_fetch_assoc($empRecords)){
    $data[] = array(
        "id"                => $r['id'],
        "parent_id"         => $r['parent_id'],
        "supplier_name"     => $r['supplier_name'],
        "voucher_date"      => ($r['pv_voucher_date'] != null
                                    ? date('d/m/Y', strtotime($r['pv_voucher_date']))
                                    : null),
        "voucher_no"        => $r['voucher_no'] ?? '-',
        "invoice_no"        => $r['invoice_no'] ?? '-',
        "final_amount"=> ($r['final_amount'] ? number_format($r['final_amount'], 2) : '0.00'),
        "total_nett_weight" => number_format($r['total_nett_weight'], 2),
        "pv_id"             => $r['pv_id'] ?? '',
    );
}

## Response
$response = array(
    "draw"                => intval($draw),
    "iTotalRecords"       => $totalRecords,
    "iTotalDisplayRecords"=> $totalRecordwithFilter,
    "aaData"              => $data
);

echo json_encode($response);

?>
