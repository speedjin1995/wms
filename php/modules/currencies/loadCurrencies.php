<?php
session_start();
require_once '../../db_connect.php';

$draw         = $_POST['draw'];
$row          = $_POST['start'];
$rowperpage   = $_POST['length'];
$columnIndex  = $_POST['order'][0]['column'];
$columnName   = $_POST['columns'][$columnIndex]['data'];
$columnSortOrder = $_POST['order'][0]['dir'];
$searchValue  = mysqli_real_escape_string($db, $_POST['search']['value']);

$company = $_SESSION['customer'];
$role    = $_SESSION['role'];

$searchQuery = "WHERE 1=1 AND currency.deleted = 0 ";

if($searchValue != ''){
    $searchQuery .= " AND (currency.currency LIKE '%".$searchValue."%' OR currency.description LIKE '%".$searchValue."%')";
}

if($role != 'SADMIN'){
    $searchQuery .= " AND currency.customer = '".$company."'";
}

$sel = mysqli_query($db, "SELECT COUNT(*) as allcount FROM currency");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

$sel = mysqli_query($db, "SELECT COUNT(*) as allcount FROM currency ".$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

$empQuery = "SELECT currency.* FROM currency ".$searchQuery." ORDER BY deleted, ".$columnName." ".$columnSortOrder." LIMIT ".$row.",".$rowperpage;
$empRecords = mysqli_query($db, $empQuery);
$data = array();

while($row = mysqli_fetch_assoc($empRecords)){
    $data[] = array(
        "id"          => $row['id'],
        "currency"    => $row['currency'],
        "description" => $row['description'],
        "rate"        => $row['rate'],
        "is_default"  => $row['is_default'],
        "deleted"     => $row['deleted']
    );
}

echo json_encode(array(
    "draw"                => intval($draw),
    "iTotalRecords"       => $totalRecords,
    "iTotalDisplayRecords"=> $totalRecordwithFilter,
    "aaData"              => $data
));
?>
