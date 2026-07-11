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
$searchQuery = " and wholesales.records_type = 'wholesales'";

if(isset($_POST['recordType']) && $_POST['recordType'] != null && $_POST['recordType'] != ''){
  $searchQuery = " and wholesales.records_type = '".$_POST['recordType']."'";
}

if($_POST['fromDate'] != null && $_POST['fromDate'] != ''){
  $dateTime = DateTime::createFromFormat('d/m/Y', $_POST['fromDate']);
  $fromDateTime = $dateTime->format('Y-m-d 00:00:00');
  $searchQuery .= " and wholesales.start_time >= '".$fromDateTime."'";
}

if($_POST['toDate'] != null && $_POST['toDate'] != ''){
  $dateTime = DateTime::createFromFormat('d/m/Y', $_POST['toDate']);
  $toDateTime = $dateTime->format('Y-m-d 23:59:59');
	$searchQuery .= " and wholesales.start_time <= '".$toDateTime."'";
}

if($_POST['transactionStatus'] != null && $_POST['transactionStatus'] != '' && $_POST['transactionStatus'] != '-'){
  $searchQuery .= " and wholesales.status = '".$_POST['transactionStatus']."'";
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

if($_POST['vehicle'] != null && $_POST['vehicle'] != '' && $_POST['vehicle'] != '-'){
  if ($_POST['vehicle'] == 'UNKOWN NO' || $_POST['vehicle'] == 'OTHERS' || $_POST['vehicle'] == 'UNKNOWN'){
    if($_POST['otherVehicle'] != null && $_POST['otherVehicle'] != '' && $_POST['otherVehicle'] != '-'){
      $searchQuery .= " and wholesales.vehicle_no = '".$_POST['otherVehicle']."'";
    }
  } else {
    $searchQuery .= " and wholesales.vehicle_no = '".$_POST['vehicle']."'";
  }
}

if($_POST['checkedBy'] != null && $_POST['checkedBy'] != '' && $_POST['checkedBy'] != '-'){
  $searchQuery .= " and wholesales.checked_by = '".$_POST['checkedBy']."'";
}

if($_POST['weightedBy'] != null && $_POST['weightedBy'] != '' && $_POST['weightedBy'] != '-'){
  $searchQuery .= " and wholesales.weighted_by = '".$_POST['weightedBy']."'";
}

if($_POST['location'] != null && $_POST['location'] != '' && $_POST['location'] != '-'){
  $searchQuery .= " and wholesales.location = '".$_POST['location']."'";
}

if($_POST['category'] != null && $_POST['category'] != '' && $_POST['category'] != '-'){
  // Get product ids in this category first
  $catProductIds = [];
  $catStmt = $db->prepare("SELECT id FROM products WHERE category = ? AND deleted = '0'");
  $catStmt->bind_param('s', $_POST['category']);
  $catStmt->execute();
  $catResult = $catStmt->get_result();
  while ($catRow = $catResult->fetch_assoc()) {
    $catProductIds[] = $catRow['id'];
  }
  $catStmt->close();

  if (count($catProductIds) > 0) {
    $likeConditions = array_map(fn($id) => "wholesales.weight_details LIKE '%\"product\":\"".$id."\"%'", $catProductIds);
    $searchQuery .= " AND (" . implode(' OR ', $likeConditions) . ")";
  } else {
    $searchQuery .= " AND 1=0";
  }
}

if($_POST['status'] != null && $_POST['status'] != '' && $_POST['status'] != '-'){
  if ($_POST['status'] == 'active'){
    $searchQuery .= " and wholesales.deleted = '0'";
  } else if ($_POST['status'] == 'deleted'){
    $searchQuery .= " and wholesales.deleted = '1'";
  }
}

## Search 
if($searchValue != ''){
   $searchQuery .= " and (wholesales.serial_no like '%".$searchValue."%' or 
        wholesales.po_no like '%".$searchValue."%' or
        wholesales.vehicle_no like '%".$searchValue."%' or
        wholesales.driver like '%".$searchValue."%' or
        c.customer_name like '%".$searchValue."%' or
        s.supplier_name like '%".$searchValue."%') ";
}

$company = $_SESSION['customer'];
$user = $_SESSION['userID'];
$role = $_SESSION['role'];

if ($role != 'SADMIN'){
  $companyFilter = " AND company = '".$company."'";
  // $searchQuery .= " AND company = '".$company."'";
}else{
  $companyFilter = '';
}

## Total number of records without filtering
$sel = mysqli_query($db,"select count(*) as allcount from wholesales where 1=1".$companyFilter);
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of record with filtering
$sel = mysqli_query($db,"select count(*) as allcount from wholesales LEFT JOIN customers c ON wholesales.customer = c.id LEFT JOIN supplies s ON wholesales.supplier = s.id where 1=1".$companyFilter.$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
$empQuery = "select wholesales.* from wholesales LEFT JOIN customers c ON wholesales.customer = c.id LEFT JOIN supplies s ON wholesales.supplier = s.id where 1=1".$companyFilter.$searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
$empRecords = mysqli_query($db, $empQuery);
$data = array();

while($row = mysqli_fetch_assoc($empRecords)) {
  if ($row['status'] == 'DISPATCH'){
    $customerSupplier = searchCustomerNameById($row['customer'], $row['other_customer'], $db);
    $parentId = searchCustomerParentById($row['customer'], $db);
    $parent = searchCustomerNameById($parentId, '', $db);
  }else{
    $customerSupplier = searchSupplierNameById($row['supplier'], $row['other_supplier'], $db);
    $parentId = searchSupplierParentById($row['supplier'], $db);
    $parent = searchSupplierNameById($parentId, '', $db);
  }

  $totalGross = 0;
  $totalTare = 0;
  $totalNett = 0;
  $totalVariance = 0;
  $totalVariancePerc = 0;

  if(isset($_POST['recordType']) && $_POST['recordType'] == 'industrial'){
    $weightDetails = json_decode($row['weight_details'], true);
    if($weightDetails && count($weightDetails) > 0){
      foreach($weightDetails as $detail){
        $totalGross += floatval($detail['gross'] ?? 0);
        $totalTare += floatval($detail['tare'] ?? 0);
        $totalNett += floatval($detail['net'] ?? 0);
        $totalVariance += floatval($detail['variance'] ?? 0);
        $totalVariancePerc += floatval($detail['varPerc'] ?? 0);
      }
    }
  }

  $data[] = array( 
    "id"=>$row['id'],
    "indicator"=>$row['indicator'],
    "serial_no"=>$row['serial_no'],
    "security_bills"=>$row['security_bills'] ?? '',
    "po_no"=>$row['po_no'] ?? '',
    "status"=>$row['status'],
    "parent"=>$parent,
    "customer_supplier"=>$customerSupplier,
    "product"=>searchProductNameById($row['product'], $db) ?? '',
    "vehicle_no"=>$row['vehicle_no'],
    "driver"=>$row['driver'] ?? '',
    "driver_ic"=>$row['driver_ic'] ?? '',
    "total_item"=>$row['total_item'],
    "total_weight"=>number_format($row['total_weight'], 2, '.', ','),
    "total_gross"=>number_format($totalGross, 2, '.', ','),
    "total_tare"=>number_format($totalTare, 2, '.', ','),
    "total_nett"=>number_format($totalNett, 2, '.', ','),
    "total_variance"=>number_format($totalVariance, 2, '.', ','),
    "total_variance_perc"=>number_format($totalVariancePerc, 2, '.', ','),
    "total_reject"=>number_format($row['total_reject'], 2, '.', ','),
    "total_price"=>number_format($row['total_price'], 2, '.', ','),
    "remark"=>$row['remark'] ?? '',
    "created_datetime"=>$row['created_datetime'],
    "start_time"=>$row['start_time'],
    "end_time"=>$row['end_time'],
    "created_by"=>$row['created_by'],
    "company"=>$row['company'],
    "weighted_by"=>searchUserNameById($row['weighted_by'], $db),
    "checked_by"=>($row['checked_by'] == 'JACKY' ? '' : $row['checked_by']),
    'remarks2'=>$row['remarks2'] ?? '',
    "location"=>searchLocationById($row['location'], $db),
    "indicator"=>$row['indicator']?? ''
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