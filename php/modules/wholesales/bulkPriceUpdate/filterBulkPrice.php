<?php
session_start();
require_once '../../../db_connect.php';
require_once '../../../lookup.php';

## Read value
$draw = $_POST['draw'];
$start = $_POST['start'];
$rowperpage = $_POST['length'];
$columnIndex = $_POST['order'][0]['column'];
$columnSortOrder = $_POST['order'][0]['dir'];
$searchValue = mysqli_real_escape_string($db, $_POST['search']['value']);

$company = $_SESSION['customer'];
$role = $_SESSION['role'];

## Build date filter
$dateFilter = '';
if (!empty($_POST['date'])) {
  $dateTime = DateTime::createFromFormat('d/m/Y', $_POST['date']);
  if ($dateTime) {
    $fromDate = $dateTime->format('Y-m-d 00:00:00');
    $toDate   = $dateTime->format('Y-m-d 23:59:59');
    $dateFilter = " AND w.start_time >= '$fromDate' AND w.start_time <= '$toDate'";
  }
}

$statusFilter = '';
if (!empty($_POST['status'])) {
  $status = mysqli_real_escape_string($db, $_POST['status']);
  $statusFilter = " AND w.status = '$status'";
}

$companyFilter = '';
if ($role != 'SADMIN') {
  $companyFilter = " AND w.company = '$company'";
}

$productFilter = isset($_POST['product']) ? $_POST['product'] : '';
$gradeFilter   = isset($_POST['grade'])   ? $_POST['grade']   : '';

## Fetch all matching wholesales records
$sql = "SELECT w.id, w.serial_no, w.start_time, w.status, w.weight_details, w.po_no, w.customer, w.supplier, w.other_customer, w.other_supplier
        FROM wholesales w
        WHERE w.deleted = '0' AND w.records_type = 'wholesales'
        $companyFilter $dateFilter $statusFilter
        ORDER BY w.start_time ASC";

$result = mysqli_query($db, $sql);

$allRows = [];
while ($row = mysqli_fetch_assoc($result)) {
  $details = json_decode($row['weight_details'], true);
  if (!is_array($details)) continue;

  $matchedItems = [];
  foreach ($details as $detail) {
    if (($detail['isRejected'] ?? '') === 'YES') continue;
    if ($productFilter !== '' && $detail['product'] != $productFilter) continue;
    if ($gradeFilter !== '' && $detail['grade'] != $gradeFilter) continue;

    $matchedItems[] = [
      'product_name' => $detail['product_name'] ?? '',
      'grade'        => $detail['grade'] ?? '',
      'net'          => number_format(floatval($detail['net'] ?? 0), 2, '.', ','),
      'price'        => number_format(floatval($detail['price'] ?? 0), 2, '.', ','),
      'total'        => number_format(floatval($detail['total'] ?? 0), 2, '.', ','),
    ];
  }

  if (empty($matchedItems)) continue;

  ## Apply search against serial_no
  if ($searchValue !== '' && stripos($row['serial_no'], $searchValue) === false) continue;

  ## Get customer/supplier name based on status
  if ($row['status'] == 'DISPATCH') {
    $customerSupplier = searchCustomerNameById($row['customer'], $row['other_customer'], $db);
  } else {
    $customerSupplier = searchSupplierNameById($row['supplier'], $row['other_supplier'], $db);
  }

  $allRows[] = [
    'serial_no'         => $row['serial_no'],
    'start_time'        => $row['start_time'] ? date('d/m/Y H:i', strtotime($row['start_time'])) : '',
    'status'            => $row['status'],
    'po_no'             => $row['po_no'] ?? '',
    'customer_supplier' => $customerSupplier,
    'item_count'        => count($matchedItems),
    'items'             => $matchedItems,
  ];
}

$totalRecords = count($allRows);

## Sort
$sortColumns = ['serial_no', 'start_time', 'status', 'item_count'];
$sortKey = $sortColumns[$columnIndex] ?? 'start_time';
usort($allRows, function($a, $b) use ($sortKey, $columnSortOrder) {
  $cmp = strcmp($a[$sortKey], $b[$sortKey]);
  return $columnSortOrder === 'desc' ? -$cmp : $cmp;
});

## Paginate
$paged = array_slice($allRows, intval($start), intval($rowperpage));

echo json_encode([
  'draw'                 => intval($draw),
  'iTotalRecords'        => $totalRecords,
  'iTotalDisplayRecords' => $totalRecords,
  'aaData'               => $paged
]);
?>
