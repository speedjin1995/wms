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
$searchQuery = '';

// if($_POST['fromDate'] != null && $_POST['fromDate'] != ''){
//   $dateTime = DateTime::createFromFormat('d/m/Y', $_POST['fromDate']);
//   $fromDateTime = $dateTime->format('Y-m-d 00:00:00');
//   $searchQuery .= " and sales.created_datetime >= '".$fromDateTime."'";
// }

// if($_POST['toDate'] != null && $_POST['toDate'] != ''){
//   $dateTime = DateTime::createFromFormat('d/m/Y', $_POST['toDate']);
//   $toDateTime = $dateTime->format('Y-m-d 23:59:59');
// 	$searchQuery .= " and sales.created_datetime <= '".$toDateTime."'";
// }

// if($_POST['receiptNo'] != null && $_POST['receiptNo'] != ''){
//   $searchQuery .= " and sales.receipt_no = '".$_POST['receiptNo']."'";
// }

## Search 
$company = $_SESSION['customer'];
$user = $_SESSION['userID'];
$role = $_SESSION['role'];

// Language
$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];

if ($role != 'SADMIN'){
  $companyFilter = " AND company = '".$company."'";
  // $searchQuery .= " AND company = '".$company."'";
}else{
  $companyFilter = '';
}

## Total number of records without filtering
$sel = mysqli_query($db,"select count(*) as allcount from daily_sales_setup where deleted=0".$companyFilter);
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of record with filtering
$sel = mysqli_query($db,"select count(*) as allcount from daily_sales_setup where deleted=0".$companyFilter.$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
$empQuery = "select * from daily_sales_setup where deleted=0".$companyFilter.$searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
$empRecords = mysqli_query($db, $empQuery);
$data = array();

while($row = mysqli_fetch_assoc($empRecords)) {
  $data[] = array( 
    "id"=>$row['id'],
    "module"=> formatModules($row['module'], $languageArray, $language),
    "state" => implode(', ', getStatesByIds($row['state'], $db) ?? [])
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