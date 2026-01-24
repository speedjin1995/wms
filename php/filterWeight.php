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
  $searchQuery .= " and Weight.transaction_date >= '".$fromDateTime."'";
}

if($_POST['toDate'] != null && $_POST['toDate'] != ''){
  $dateTime = DateTime::createFromFormat('d/m/Y', $_POST['toDate']);
  $toDateTime = $dateTime->format('Y-m-d 23:59:59');
	$searchQuery .= " and Weight.transaction_date <= '".$toDateTime."'";
}

if($_POST['transactionStatus'] != null && $_POST['transactionStatus'] != '' && $_POST['transactionStatus'] != '-'){
  $searchQuery .= " and Weight.transaction_status = '".$_POST['transactionStatus']."'";
}

if($_POST['product'] != null && $_POST['product'] != '' && $_POST['product'] != '-'){
  $searchQuery .= " and Weight.product_name = '".$_POST['product']."'";
}

if($_POST['customer'] != null && $_POST['customer'] != '' && $_POST['customer'] != '-'){
  $searchQuery .= " and Weight.customer_name = '".$_POST['customer']."'";
}

if($_POST['supplier'] != null && $_POST['supplier'] != '' && $_POST['supplier'] != '-'){
  $searchQuery .= " and Weight.supplier_name = '".$_POST['supplier']."'";
}

if($_POST['vehicle'] != null && $_POST['vehicle'] != '' && $_POST['vehicle'] != '-'){
  $searchQuery .= " and Weight.lorry_plate_no1 = '".$_POST['vehicle']."'";
}

if($_POST['status'] != null && $_POST['status'] != '' && $_POST['status'] != '-'){
  if($_POST['status'] == 'Pending'){
    $searchQuery .= " and Weight.is_complete = 'N' AND Weight.is_cancel <> 'Y'";
  }else{
    $searchQuery .= " and Weight.is_complete = 'Y' AND Weight.is_cancel <> 'Y'";
  }
}

if($_POST['transactionId'] != null && $_POST['transactionId'] != '' && $_POST['transactionId'] != '-'){
  $searchQuery .= " and Weight.transaction_id like '%".$_POST['transactionId']."%'";
}

## Search 
if($searchValue != ''){
   $searchQuery = " and (Weight.serial_no like '%".$searchValue."%' or 
        Weight.po_no like '%".$searchValue."%' or
        Weight.lorry_plate_no1 like '%".$searchValue."%') ";
}

$company = $_SESSION['customer'];
$user = $_SESSION['userID'];

if ($user != 2){
  $searchQuery .= " AND Weight.company = '".$company."'";
}

## Total number of records without filtering
$sel = mysqli_query($db,"select count(*) as allcount from Weight where Weight.status = '0'");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of record with filtering
$sel = mysqli_query($db,"select count(*) as allcount from Weight where Weight.status = '0'".$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
$empQuery = "select Weight.* from Weight where Weight.status = '0'".$searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
$empRecords = mysqli_query($db, $empQuery);
$data = array();

while($row = mysqli_fetch_assoc($empRecords)) {
  if ($row['transaction_status'] == 'Sales' || $row['transaction_status'] == 'Misc'){
    $customerSupplier = $row['customer_name'];
  }else{
    $customerSupplier = $row['supplier_name'];
  }

  $transactionStatus = '';
  if($row['transaction_status'] == 'Sales'){
    $transactionStatus = 'Dispatch';
  }
  else if($row['transaction_status'] == 'Purchase'){
    $transactionStatus = 'Receiving';
  }
  else if($row['transaction_status'] == 'Misc'){
    $transactionStatus = 'Miscellaneous';
  }
  else{
    $transactionStatus = 'Internal Transfer';
  }

  $data[] = array( 
    "id"=>$row['id'],
    "transaction_id"=>$row['transaction_id'],
    "transaction_date"=>$row['transaction_date'],
    "transaction_status"=>$transactionStatus,
    "do_po"=>($row['transaction_status'] == 'Purchase' || $row['transaction_status'] == 'Local') ? $row['purchase_order'] : $row['delivery_no'],
    "lorry_plate_no1"=>$row['lorry_plate_no1'],
    "customer_supplier"=>$customerSupplier,
    "product_name"=>$row['product_name'],
    "gross_weight1"=>$row['gross_weight1'],
    "gross_weight1_date"=>$row['gross_weight1_date'],
    "tare_weight1"=>$row['tare_weight1'],
    "tare_weight1_date"=>$row['tare_weight1_date'],
    "nett_weight1"=>$row['nett_weight1'],
    "reduce_weight"=>$row['reduce_weight'],
    "final_weight"=>$row['final_weight'],
    "company"=>$row['company'],
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