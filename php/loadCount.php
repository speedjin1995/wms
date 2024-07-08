<?php
## Database configuration
require_once 'db_connect.php';
session_start();
$company = $_SESSION['customer'];

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
$searchValue = mysqli_real_escape_string($db,$_POST['search']['value']); // Search value

## Search 
$searchQuery = " ";
if($searchValue != ''){
   $searchQuery = " and (products.product_name like '%".$searchValue."%' or 
        counting.serial_no like '%".$searchValue."%') ";
}

## Total number of records without filtering
$sel = mysqli_query($db,"select count(*) as allcount from counting, products where counting.product = products.id AND counting.deleted = '0' AND counting.company = '$company'");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of record with filtering
$sel = mysqli_query($db,"select count(*) as allcount from counting, products where counting.product = products.id AND counting.deleted = '0' AND counting.company = '$company'".$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
$empQuery = "select counting.*, products.product_name from counting, products where counting.product = products.id AND counting.deleted = '0' AND counting.company = '$company'".$searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;

$empRecords = mysqli_query($db, $empQuery);
$data = array();

while($row = mysqli_fetch_assoc($empRecords)) {
  $data[] = array( 
    "id"=>$row['id'],
    "serial_no"=>$row['serial_no'],
    "batch_no"=>$row['batch_no'] ?? '',
    "product_name"=>$row['product_name'],
    "product_desc"=>$row['product_desc'],
    "product"=>$row['product'],
    "gross"=>$row['gross'],
    "unit"=>$row['unit'],
    "count"=>$row['count'],
    "remark"=>$row['remark'],
    "created_datetime"=>$row['created_datetime'],
    "created_by"=>$row['created_by'],
    "company"=>$row['company']
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