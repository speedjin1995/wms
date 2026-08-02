<?php
## Database configuration
require_once '../../db_connect.php';
session_start();

## Read value
$fromDate   = $_POST['fromDate'] ?? '';
$toDate     = $_POST['toDate'] ?? '';
$status     = $_POST['status'] ?? '';
$customerId = $_POST['customer'] ?? '';
$supplierId = $_POST['supplier'] ?? '';
$locationId = $_POST['location'] ?? '';

## Search
$searchQuery = " and w.records_type = 'industrial'";

if ($fromDate != null && $fromDate != '') {
  $dateTime = DateTime::createFromFormat('d/m/Y', $fromDate);
  $fromDateTime = $dateTime->format('Y-m-d 00:00:00');
  $searchQuery .= " and DATE(w.start_time) >= '".$fromDateTime."'";
}

if ($toDate != null && $toDate != '') {
  $dateTime = DateTime::createFromFormat('d/m/Y', $toDate);
  $toDateTime = $dateTime->format('Y-m-d 23:59:59');
  $searchQuery .= " and DATE(w.start_time) <= '".$toDateTime."'";
}

if ($status === 'INCOMING') {
  $searchQuery .= " and w.status IN ('RECEIVING', 'INCOMING')";
} elseif ($status === 'OUTGOING') {
  $searchQuery .= " and w.status IN ('DISPATCH', 'OUTGOING')";
} else {
  $searchQuery .= " and w.status IN ('RECEIVING', 'INCOMING', 'DISPATCH', 'OUTGOING')";
}

if ($customerId != null && $customerId != '') {
  $customerId = mysqli_real_escape_string($db, $customerId);
  $searchQuery .= " and w.customer = '".$customerId."'";
}

if ($supplierId != null && $supplierId != '') {
  $supplierId = mysqli_real_escape_string($db, $supplierId);
  $searchQuery .= " and w.supplier = '".$supplierId."'";
}

if ($locationId != null && $locationId != '') {
  $locationId = mysqli_real_escape_string($db, $locationId);
  $searchQuery .= " and w.location = '".$locationId."'";
}

$company = $_SESSION['customer'];
$user    = $_SESSION['userID'];
$role    = $_SESSION['role'];

if ($role != 'SADMIN') {
  $companyFilter = " AND w.company = '".$company."'";
} else {
  $companyFilter = '';
}

## Fetch records
$empQuery = "SELECT w.id, w.status, w.weight_details, w.supplier, w.customer,
                    s.supplier_name, c.customer_name,
                    DATE(w.start_time) AS trade_date
             FROM wholesales w
             LEFT JOIN supplies s ON w.supplier = s.id
             LEFT JOIN customers c ON w.customer = c.id
             WHERE w.deleted = 0".$companyFilter.$searchQuery;

$empRecords = mysqli_query($db, $empQuery);

## Compute totals from weight_details JSON net field
$incomingWeight = 0;
$incomingCount  = 0;
$outgoingWeight = 0;
$outgoingCount  = 0;
$supplierMap    = array();
$customerMap    = array();
$trendMap       = array();

while ($row = mysqli_fetch_assoc($empRecords)) {
  $details = json_decode($row['weight_details'], true);
  $rowNet  = 0;

  if (is_array($details)) {
    foreach ($details as $item) {
      $rowNet += floatval($item['net'] ?? 0);
    }
  }

  $isIncoming = in_array($row['status'], array('RECEIVING', 'INCOMING'));
  $isOutgoing = in_array($row['status'], array('DISPATCH', 'OUTGOING'));
  $date       = $row['trade_date'];

  if (!isset($trendMap[$date])) {
    $trendMap[$date] = array('incoming' => 0, 'outgoing' => 0);
  }

  if ($isIncoming) {
    $incomingWeight += $rowNet;
    $incomingCount++;
    $sName = $row['supplier_name'] ?: 'Unknown';
    $supplierMap[$sName] = ($supplierMap[$sName] ?? 0) + $rowNet;
    $trendMap[$date]['incoming'] += $rowNet;
  }

  if ($isOutgoing) {
    $outgoingWeight += $rowNet;
    $outgoingCount++;
    $cName = $row['customer_name'] ?: 'Unknown';
    $customerMap[$cName] = ($customerMap[$cName] ?? 0) + $rowNet;
    $trendMap[$date]['outgoing'] += $rowNet;
  }
}

## Build volume trend sorted by date asc
ksort($trendMap);
$volumeTrend = array();
foreach ($trendMap as $date => $vals) {
  $volumeTrend[] = array(
    'date'     => $date,
    'incoming' => round($vals['incoming'], 2),
    'outgoing' => round($vals['outgoing'], 2),
  );
}

## Build supplier breakdown sorted by weight desc
$supplierBreakdown = array();
if ($status !== 'OUTGOING') {
  foreach ($supplierMap as $name => $weight) {
    $supplierBreakdown[] = array('name' => $name, 'total_weight' => round($weight, 2));
  }
  usort($supplierBreakdown, function($a, $b) { return $b['total_weight'] <=> $a['total_weight']; });
}

## Build customer breakdown sorted by weight desc
$customerBreakdown = array();
if ($status !== 'INCOMING') {
  foreach ($customerMap as $name => $weight) {
    $customerBreakdown[] = array('name' => $name, 'total_weight' => round($weight, 2));
  }
  usort($customerBreakdown, function($a, $b) { return $b['total_weight'] <=> $a['total_weight']; });
}

## Response
$response = array(
  'status' => 'success',
  'summary' => array(
    'incoming_weight' => round($incomingWeight, 2),
    'incoming_count'  => $incomingCount,
    'outgoing_weight' => round($outgoingWeight, 2),
    'outgoing_count'  => $outgoingCount,
  ),
  'supplierBreakdown' => $supplierBreakdown,
  'customerBreakdown' => $customerBreakdown,
  'volumeTrend'       => $volumeTrend,
);

echo json_encode($response);

?>
