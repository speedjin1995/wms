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

$searchQuery = "WHERE 1=1 AND bin_type.deleted = 0 ";

if($searchValue != ''){
    $searchQuery .= " AND (bin_type.bin_type LIKE '%".$searchValue."%')";
}

if($role != 'SADMIN'){
    $searchQuery .= " AND bin_type.customer = '".$company."'";
}

$sel = mysqli_query($db, "SELECT COUNT(*) as allcount FROM bin_type");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

$sel = mysqli_query($db, "SELECT COUNT(*) as allcount FROM bin_type ".$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

$empQuery = "SELECT bin_type.* FROM bin_type ".$searchQuery." ORDER BY deleted, ".$columnName." ".$columnSortOrder." LIMIT ".$row.",".$rowperpage;
$empRecords = mysqli_query($db, $empQuery);
$data = array();

while($row = mysqli_fetch_assoc($empRecords)){
    $data[] = array(
        "id"       => $row['id'],
        "bin_type" => $row['bin_type'],
        "deleted"  => $row['deleted']
    );
}

echo json_encode(array(
    "draw"                => intval($draw),
    "iTotalRecords"       => $totalRecords,
    "iTotalDisplayRecords"=> $totalRecordwithFilter,
    "aaData"              => $data
));
?>
