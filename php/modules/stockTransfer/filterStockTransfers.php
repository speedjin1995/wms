<?php
require_once '../../db_connect.php';
session_start();

$draw          = $_POST['draw'];
$row           = $_POST['start'];
$rowperpage    = $_POST['length'];
$columnIndex   = $_POST['order'][0]['column'];
$columnName    = $_POST['columns'][$columnIndex]['data'];
$columnSortOrder = $_POST['order'][0]['dir'];
$searchValue   = mysqli_real_escape_string($db, $_POST['search']['value']);

$searchQuery = '';

if($_POST['fromDate'] != null && $_POST['fromDate'] != ''){
    $fromDateTime = DateTime::createFromFormat('d/m/Y', $_POST['fromDate'])->format('Y-m-d 00:00:00');
    $searchQuery .= " AND st.created_date >= '$fromDateTime'";
}

if($_POST['toDate'] != null && $_POST['toDate'] != ''){
    $toDateTime = DateTime::createFromFormat('d/m/Y', $_POST['toDate'])->format('Y-m-d 23:59:59');
    $searchQuery .= " AND st.created_date <= '$toDateTime'";
}

if ($searchValue != '') {
    $searchQuery .= " AND st.transfer_no LIKE '%$searchValue%'";
}

$company = $_SESSION['customer'];
$role    = $_SESSION['role'];
$companyFilter = ($role != 'SADMIN') ? " AND st.company = '$company'" : '';

$totalRecords = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as c FROM stock_transfers st WHERE st.deleted=0$companyFilter"))['c'];
$totalRecordwithFilter = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as c FROM stock_transfers st WHERE st.deleted=0$companyFilter$searchQuery"))['c'];

$empQuery = "SELECT st.*, pb1.batch_no as from_batch_no, pb2.batch_no as to_batch_no
             FROM stock_transfers st
             LEFT JOIN packaging_batches pb1 ON st.from_batch_id = pb1.id
             LEFT JOIN packaging_batches pb2 ON st.to_batch_id = pb2.id
             WHERE st.deleted=0$companyFilter$searchQuery
             ORDER BY $columnName $columnSortOrder
             LIMIT $row,$rowperpage";

$empRecords = mysqli_query($db, $empQuery);
$data = [];

while ($r = mysqli_fetch_assoc($empRecords)) {
    $data[] = [
        'id'            => $r['id'],
        'transfer_no'   => $r['transfer_no'],
        'from_batch_no' => $r['from_batch_no'],
        'to_batch_no'   => $r['to_batch_no'],
        'created_date'  => date('d/m/Y H:i', strtotime($r['created_date'])),
        'remarks'       => $r['remarks'],
    ];
}

echo json_encode([
    'draw'                => intval($draw),
    'iTotalRecords'       => $totalRecords,
    'iTotalDisplayRecords'=> $totalRecordwithFilter,
    'aaData'              => $data,
]);
