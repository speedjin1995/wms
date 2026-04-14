<?php
session_start();
## Database configuration
require_once 'db_connect.php';
require_once 'lookup.php';

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
$searchValue = mysqli_real_escape_string($db,$_POST['search']['value']); // Search value

## Search 
$searchQuery = "WHERE 1=1 AND deleted = 0 ";
$company = $_SESSION['customer'];
$user = $_SESSION['userID'];
$role = $_SESSION['role'];

if ($role != 'SADMIN'){
  $searchQuery .= " AND customer = '".$company."'";
}

if($searchValue != ''){
  // Lookup parent customer IDs based on search value
  $supplierId = searchSupplierIdByName($searchValue, $company, $db);

  $searchQuery .= " AND (supplier_name like '%".$searchValue."%' or supplier_code like '%".$searchValue."%' or parent = '".$supplierId."')";
}

## Total number of records without filtering
$sel = mysqli_query($db,"select count(*) as allcount from supplies");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of record with filtering
$sel = mysqli_query($db,"select count(*) as allcount from supplies ".$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
$empQuery = "select * from supplies ".$searchQuery." order by deleted, ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
$empRecords = mysqli_query($db, $empQuery);
$data = array();

while($row = mysqli_fetch_assoc($empRecords)) {
  // 1. Put all address fields into an array and trim whitespace
  $address_parts = array(
    trim($row['supplier_address']),
    trim($row['supplier_address2']),
    trim($row['supplier_address3']),
    trim($row['supplier_address4'])
  );

  // 2. Filter out empty values
  $filtered_address = array_filter($address_parts);

  // 3. Join non-empty parts with <br>
  $display_address = implode('<br>', $filtered_address);

  $data[] = array( 
    'id'=>$row['id'],
    "parent"=>searchSupplierNameById($row['parent'], '', $db),
    'supplier_code'=>$row['supplier_code'],
    "reg_no"=>$row['reg_no'],
    'supplier_name'=>$row['supplier_name'],
    'supplier_address'=>$display_address,
    // 'supplier_address'=>$row['supplier_address'].$row['supplier_address2'].$row['supplier_address3'].$row['supplier_address4'],
    'supplier_phone'=>$row['supplier_phone'],
    'pic'=>$row['pic'],
    'is_manual'=>$row['is_manual'],
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