<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);


## Database configuration
require_once '../../db_connect.php';
require_once '../../lookup.php';
session_start();

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
$searchValue = mysqli_real_escape_string($db,$_POST['search']['value']); // Search value

## Search 
$searchQuery = "";

if($_POST['fromDate'] != null && $_POST['fromDate'] != ''){
  $dateTime = DateTime::createFromFormat('d/m/Y', $_POST['fromDate']);
  $fromDateTime = $dateTime->format('Y-m-d 00:00:00');
  $searchQuery .= " and pb.packaging_date >= '".$fromDateTime."'";
}

if($_POST['toDate'] != null && $_POST['toDate'] != ''){
  $dateTime = DateTime::createFromFormat('d/m/Y', $_POST['toDate']);
  $toDateTime = $dateTime->format('Y-m-d 23:59:59');
	$searchQuery .= " and pb.packaging_date <= '".$toDateTime."'";
}

if($_POST['location'] != null && $_POST['location'] != '' && $_POST['location'] != 'all'){
  $searchQuery .= " and pb.location = '".$_POST['location']."'";
}

if($_POST['productionLine'] != null && $_POST['productionLine'] != '' && $_POST['productionLine'] != 'all'){
  $searchQuery .= " and pb.production_line = '".$_POST['productionLine']."'";
}

## Search 
if($searchValue != ''){
  $searchQuery .= " and (pb.batch_no like '%".$searchValue."%') ";
}

$company = $_SESSION['customer'];
$user = $_SESSION['userID'];
$role = $_SESSION['role'];

if ($role != 'SADMIN'){
  $companyFilter = " AND pb.company = '".$company."'";
}else{
  $companyFilter = '';
}

## Total number of records without filtering
$sel = mysqli_query($db,"select count(*) as allcount from packaging_batches pb where deleted=0".$companyFilter);
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of record with filtering
$sel = mysqli_query($db,"select count(*) as allcount from packaging_batches pb left join production_lines pl on pb.production_line = pl.id left join locations l on pb.location = l.id where pb.deleted=0".$companyFilter.$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
$empQuery = "select pb.*, pl.production_line as production_line, l.locations as locations from packaging_batches pb left join production_lines pl on pb.production_line = pl.id left join locations l on pb.location = l.id where pb.deleted=0".$companyFilter.$searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
$empRecords = mysqli_query($db, $empQuery);
$data = array();

while($row = mysqli_fetch_assoc($empRecords)) {
  $data[] = array( 
    "id"=>$row['id'],
    "batch_no"=>$row['batch_no'],
    "production_line"=>$row['production_line'],
    "locations"=>$row['locations'],
    "remarks"=>$row['remarks'] ?? '',
    "packaging_date"=>$row['packaging_date'],
    "company"=>$row['company'],
    "status"=>$row['status']
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