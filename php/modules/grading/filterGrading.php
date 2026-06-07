<?php
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
  $searchQuery .= " and grading.created_date >= '".$fromDateTime."'";
}

if($_POST['toDate'] != null && $_POST['toDate'] != ''){
  $dateTime = DateTime::createFromFormat('d/m/Y', $_POST['toDate']);
  $toDateTime = $dateTime->format('Y-m-d 23:59:59');
	$searchQuery .= " and grading.created_date <= '".$toDateTime."'";
}

if($_POST['category'] != null && $_POST['category'] != '' && $_POST['category'] != 'all'){
  $searchQuery .= " and grading.product_category = '".$_POST['category']."'";
}

if($_POST['location'] != null && $_POST['location'] != '' && $_POST['location'] != 'all'){
  $searchQuery .= " and grading.location_id = '".$_POST['location']."'";
}

## Search 
if($searchValue != ''){
  $searchQuery .= " and (grading.grading_no like '%".$searchValue."%') ";
}

$company = $_SESSION['customer'];
$user = $_SESSION['userID'];
$role = $_SESSION['role'];

if ($role != 'SADMIN'){
  $companyFilter = " AND grading.company = '".$company."'";
}else{
  $companyFilter = '';
}

## Total number of records without filtering
$sel = mysqli_query($db,"select count(*) as allcount from grading where deleted=0".$companyFilter);
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of record with filtering
$sel = mysqli_query($db,"select count(*) as allcount from grading left join categories on grading.product_category = categories.id left join locations on grading.location = locations.id where grading.deleted=0".$companyFilter.$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
$empQuery = "select grading.*, categories.category_name as category, locations.locations as locations from grading left join categories on grading.product_category = categories.id left join locations on grading.location = locations.id where grading.deleted=0".$companyFilter.$searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
$empRecords = mysqli_query($db, $empQuery);
$data = array();

while($row = mysqli_fetch_assoc($empRecords)) {
  $totalGross = 0;
  $totalTare = 0;
  $totalNett = 0;

  // 

  $data[] = array( 
    "id"=>$row['id'],
    "grading_no"=>$row['grading_no'],
    "category"=>$row['category'],
    "locations"=>$row['locations'],
    "remark"=>$row['remark'] ?? '',
    "created_date"=>$row['created_date'],
    "start_date"=>$row['start_date'],
    "end_date"=>$row['end_date'],
    "created_by"=>$row['created_by'],
    "customers"=>$row['customers'],
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