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

$searchQuery = "WHERE 1=1 AND states.deleted = 0 ";

if($searchValue != ''){
    $searchQuery .= " AND (states.states LIKE '%".$searchValue."%')";
}

if($role != 'SADMIN'){
    $searchQuery .= " AND states.customer = '".$company."'";
}

$sel = mysqli_query($db, "SELECT COUNT(*) as allcount FROM states");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

$sel = mysqli_query($db, "SELECT COUNT(*) as allcount FROM states ".$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

$empQuery = "SELECT states.* FROM states ".$searchQuery." ORDER BY deleted, ".$columnName." ".$columnSortOrder." LIMIT ".$row.",".$rowperpage;
$empRecords = mysqli_query($db, $empQuery);
$data = array();

while($row = mysqli_fetch_assoc($empRecords)){
    $data[] = array(
        "id"      => $row['id'],
        "states"  => $row['states'],
        "deleted" => $row['deleted']
    );
}

echo json_encode(array(
    "draw"                => intval($draw),
    "iTotalRecords"       => $totalRecords,
    "iTotalDisplayRecords"=> $totalRecordwithFilter,
    "aaData"              => $data
));
?>
