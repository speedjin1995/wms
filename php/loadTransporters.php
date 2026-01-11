<?php
session_start();
## Database configuration
require_once 'db_connect.php';

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
$searchValue = mysqli_real_escape_string($db,$_POST['search']['value']); // Search value

## Search 
$searchQuery = "WHERE 1=1";
$company = $_SESSION['customer'];
$user = $_SESSION['userID'];
if ($user != 2){
  $searchQuery .= " AND customer = '".$company."'";
}

if($searchValue != ''){
  $searchQuery .= " AND (transporter_code like '%".$searchValue."%' OR transporter_name like '%".$searchValue."%' OR transporter_ic like '%".$searchValue."%')";
}

## Total number of records without filtering
$sel = mysqli_query($db,"select count(*) as allcount from transporters");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of record with filtering
$sel = mysqli_query($db,"select count(*) as allcount from transporters ".$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
$empQuery = "select * from transporters ".$searchQuery." order by deleted, ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
$empRecords = mysqli_query($db, $empQuery);
$data = array();

while($row = mysqli_fetch_assoc($empRecords)) {
  $data[] = array( 
    "id"=>$row['id'],
    "transporter_code"=>$row['transporter_code'],
    "transporter_name"=>$row['transporter_name'],
    "transporter_ic"=>$row['transporter_ic'],
    "deleted"=>$row['deleted']
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