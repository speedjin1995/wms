<?php
## Database configuration
require_once 'db_connect.php';
require_once 'lookup.php';
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
$searchQuery = " ";

if($_POST['fromDate'] != null && $_POST['fromDate'] != ''){
  $dateTime = DateTime::createFromFormat('d/m/Y', $_POST['fromDate']);
  $fromDateTime = $dateTime->format('Y-m-d 00:00:00');
  $searchQuery .= " and wholesales.created_datetime >= '".$fromDateTime."'";
}

if($_POST['toDate'] != null && $_POST['toDate'] != ''){
  $dateTime = DateTime::createFromFormat('d/m/Y', $_POST['toDate']);
  $toDateTime = $dateTime->format('Y-m-d 23:59:59');
	$searchQuery .= " and wholesales.created_datetime <= '".$toDateTime."'";
}

if($_POST['status'] != null && $_POST['status'] != '' && $_POST['status'] != '-'){
  $searchQuery .= " and wholesales.status = '".$_POST['status']."'";
}

if($_POST['product'] != null && $_POST['product'] != '' && $_POST['product'] != '-'){
  $searchQuery .= " and wholesales.product = '".$_POST['product']."'";
}

if($_POST['customer'] != null && $_POST['customer'] != '' && $_POST['customer'] != '-'){
  $searchQuery .= " and wholesales.customer = '".$_POST['customer']."'";
}

if($_POST['supplier'] != null && $_POST['supplier'] != '' && $_POST['supplier'] != '-'){
  $searchQuery .= " and wholesales.supplier = '".$_POST['supplier']."'";
}

## Search 
if($searchValue != ''){
   $searchQuery = " and (wholesales.serial_no like '%".$searchValue."%' or 
        wholesales.po_no like '%".$searchValue."%' or
        wholesales.vehicle_no like '%".$searchValue."%') ";
}

$company = $_SESSION['customer'];
$user = $_SESSION['userID'];

if ($user != 2){
  $searchQuery .= " AND company = '".$company."'";
}

## Total number of records without filtering
$sel = mysqli_query($db,"select count(*) as allcount from wholesales where wholesales.deleted = '0'");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of record with filtering
$sel = mysqli_query($db,"select count(*) as allcount from wholesales where wholesales.deleted = '0'".$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
$empQuery = "select wholesales.* from wholesales where wholesales.deleted = '0'".$searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
$empRecords = mysqli_query($db, $empQuery);
$data = array();

while($row = mysqli_fetch_assoc($empRecords)) {
  $data[] = array( 
    "id"=>$row['id'],
    "serial_no"=>$row['serial_no'],
    "po_no"=>$row['po_no'] ?? '',
    "status"=>$row['status'],
    "customer_supplier"=>($row['status'] == 'DISPATCH') ? searchCustomerNameById($row['customer'], $row['other_customer'], $db) : searchSupplierNameById($row['supplier'], $row['other_supplier'], $db),
    "product"=>searchProductNameById($row['product'], $db) ?? '',
    "vehicle_no"=>$row['vehicle_no'],
    "driver"=>$row['driver'] ?? '',
    "driver_ic"=>$row['driver_ic'] ?? '',
    "total_item"=>$row['total_item'],
    "total_weight"=>$row['total_weight'],
    "total_reject"=>$row['total_reject'],
    "total_price"=>$row['total_price'],
    "remark"=>$row['remark'] ?? '',
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