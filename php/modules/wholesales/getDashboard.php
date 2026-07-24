<?php
## Database configuration
require_once '../../db_connect.php';
require_once '../../lookup.php';
session_start();

## Read value
// Get filter inputs from the AJAX POST request
$fromDate   = $_POST['fromDate']  ?? '';
$toDate     = $_POST['toDate']    ?? '';
$status     = $_POST['status']    ?? '';  // RECEIVING, DISPATCH, or empty (all)
$customerId = $_POST['customer']  ?? '';
$supplierId = $_POST['supplier']  ?? '';
$locationId = $_POST['location']  ?? '';

// Get current user's company and role from session
$company = $_SESSION['customer'];
$role    = $_SESSION['role'];

## Build filters
// Build the dynamic WHERE clause based on the filters provided
$searchQuery = '';

// Filter by date range — convert from d/m/Y display format to Y-m-d for SQL
if ($fromDate != '') {
  $fromDate = DateTime::createFromFormat('d/m/Y', $fromDate)->format('Y-m-d');
  $searchQuery .= " AND DATE(w.start_time) >= '$fromDate'";
}

if ($toDate != '') {
  $toDate = DateTime::createFromFormat('d/m/Y', $toDate)->format('Y-m-d');
  $searchQuery .= " AND DATE(w.start_time) <= '$toDate'";
}

// Filter by transaction type — RECEIVING/INCOMING are the same direction, as are DISPATCH/OUTGOING
if ($status === 'RECEIVING') {
  $searchQuery .= " AND w.status IN ('RECEIVING','INCOMING')";
} elseif ($status === 'DISPATCH') {
  $searchQuery .= " AND w.status IN ('DISPATCH','OUTGOING')";
} else {
  // No status filter — include all transaction types
  $searchQuery .= " AND w.status IN ('RECEIVING','INCOMING','DISPATCH','OUTGOING')";
}

// Filter by specific customer (used when status = DISPATCH)
if ($customerId != '') {
  $customerId = mysqli_real_escape_string($db, $customerId);
  $searchQuery .= " AND w.customer = '$customerId'";
}

// Filter by specific supplier (used when status = RECEIVING)
if ($supplierId != '') {
  $supplierId = mysqli_real_escape_string($db, $supplierId);
  $searchQuery .= " AND w.supplier = '$supplierId'";
}

// Filter by location
if ($locationId != '') {
  $locationId = mysqli_real_escape_string($db, $locationId);
  $searchQuery .= " AND w.location = '$locationId'";
}

// SADMIN sees all companies; other roles are restricted to their own company
if ($role != 'SADMIN') {
  $companyFilter = " AND w.company = '$company'";
} else {
  $companyFilter = '';
}

## Fetch records
// Fetch all wholesale records matching the filters, joining supplier and customer names
$empQuery = "SELECT w.status, w.weight_details, w.supplier, w.customer,
                    s.supplier_name, c.customer_name,
                    w.start_time, DATE(w.start_time) AS trade_date
             FROM wholesales w
             LEFT JOIN supplies s  ON w.supplier = s.id
             LEFT JOIN customers c ON w.customer = c.id
             WHERE w.deleted = 0" . $companyFilter . $searchQuery;

$empRecords = mysqli_query($db, $empQuery);

## Compute totals
// Initialise all accumulators before looping through records
$receivingWeight = 0;
$receivingCount  = 0;
$receivingValue  = array();  // total value grouped by currency
$dispatchWeight  = 0;
$dispatchCount   = 0;
$dispatchValue   = array();  // total value grouped by currency
$supplierMap     = array();  // total receiving weight per supplier name
$customerMap     = array();  // total dispatch weight per customer name
$trendMap        = array();  // daily receiving/dispatch weight for the trend chart
$gradeMapRecv    = array();  // receiving grade weight: [productName][gradeName] => weight
$gradeMapDisp    = array();  // dispatch grade weight:  [productName][gradeName] => weight
$hourlyRecv      = array_fill(0, 24, 0);  // net weight per hour (0-23) for receiving
$hourlyDisp      = array_fill(0, 24, 0);  // net weight per hour (0-23) for dispatch
$currencyCache   = array();  // cache currency name lookups to avoid repeated DB queries
$productCache    = array();  // cache product name lookups to avoid repeated DB queries

while ($row = mysqli_fetch_assoc($empRecords)) {
  // Decode the JSON weight_details column — each record holds an array of line items
  $details     = json_decode($row['weight_details'], true);
  $isReceiving = in_array($row['status'], array('RECEIVING', 'INCOMING'));
  $isDispatch  = in_array($row['status'], array('DISPATCH',  'OUTGOING'));
  $date        = $row['trade_date'];
  $rowNet      = 0;       // total net weight for this record
  $rowValue    = array(); // total value by currency for this record

  // Guard against null or malformed weight_details
  if (!is_array($details)) {
    $details = array();
  }

  // Sum up net weight and value across all line items in this record
  foreach ($details as $item) {
    $rowNet += floatval($item['net'] ?? 0);

    // Resolve currency ID to currency name, using cache to avoid repeated lookups
    $curId = $item['currency'] ?? '';
    if ($curId == '') {
      $cur = 'MYR';
    } elseif (isset($currencyCache[$curId])) {
      $cur = $currencyCache[$curId];
    } else {
      $cur = searchCurrencyNameById($curId, $db);
      if (!$cur) {
        $cur = 'MYR';
      }
      $currencyCache[$curId] = $cur;
    }

    $rowValue[$cur] = ($rowValue[$cur] ?? 0) + floatval($item['total'] ?? 0);
  }

  // Ensure this date exists in the trend map before accumulating
  if (!isset($trendMap[$date])) {
    $trendMap[$date] = array('receiving' => 0, 'dispatch' => 0);
  }

  // Accumulate receiving totals
  if ($isReceiving) {
    $receivingWeight += $rowNet;
    $receivingCount++;

    // Add this record's value to the running currency totals
    foreach ($rowValue as $cur => $val) {
      $receivingValue[$cur] = ($receivingValue[$cur] ?? 0) + $val;
    }

    // Add to supplier weight map for the supplier breakdown chart
    $sName = $row['supplier_name'] ?: 'Unknown';
    $supplierMap[$sName] = ($supplierMap[$sName] ?? 0) + $rowNet;

    // Add to the daily trend
    $trendMap[$date]['receiving'] += $rowNet;

    // Add net weight to the receiving hour bucket using the record's start_time
    $hour = (int) date('G', strtotime($row['start_time']));
    $hourlyRecv[$hour] += $rowNet;

    // Build grade distribution — group net weight by product then by grade
    foreach ($details as $item) {
      // Resolve product ID to product name using cache
      $pId = $item['product'] ?? '';
      if ($pId != '') {
        $pRow  = getProductById($pId, $db, $productCache);
        $pName = $pRow['product_name'] ?? 'Unknown';
      } else {
        $pName = 'Unknown';
      }

      $gName = $item['grade'] ?? 'Unknown';
      $gNet  = floatval($item['net'] ?? 0);

      if (!isset($gradeMapRecv[$pName])) {
        $gradeMapRecv[$pName] = array();
      }
      $gradeMapRecv[$pName][$gName] = ($gradeMapRecv[$pName][$gName] ?? 0) + $gNet;
    }
  }

  // Accumulate dispatch totals
  if ($isDispatch) {
    $dispatchWeight += $rowNet;
    $dispatchCount++;

    // Add this record's value to the running currency totals
    foreach ($rowValue as $cur => $val) {
      $dispatchValue[$cur] = ($dispatchValue[$cur] ?? 0) + $val;
    }

    // Add to customer weight map for the customer breakdown chart
    $cName = $row['customer_name'] ?: 'Unknown';
    $customerMap[$cName] = ($customerMap[$cName] ?? 0) + $rowNet;

    // Add to the daily trend
    $trendMap[$date]['dispatch'] += $rowNet;

    // Add net weight to the dispatch hour bucket using the record's start_time
    $hour = (int) date('G', strtotime($row['start_time']));
    $hourlyDisp[$hour] += $rowNet;

    // Build grade distribution — group net weight by product then by grade
    foreach ($details as $item) {
      // Resolve product ID to product name using cache
      $pId = $item['product'] ?? '';
      if ($pId != '') {
        $pRow  = getProductById($pId, $db, $productCache);
        $pName = $pRow['product_name'] ?? 'Unknown';
      } else {
        $pName = 'Unknown';
      }

      $gName = $item['grade'] ?? 'Unknown';
      $gNet  = floatval($item['net'] ?? 0);

      if (!isset($gradeMapDisp[$pName])) {
        $gradeMapDisp[$pName] = array();
      }
      $gradeMapDisp[$pName][$gName] = ($gradeMapDisp[$pName][$gName] ?? 0) + $gNet;
    }
  }
}

## Build grade distribution: [{product, grades:[{name, weight}]}] sorted by total weight desc
// Converts the raw [productName][gradeName] => weight map into a structured array
// suitable for the frontend grade distribution cards
function buildGradeDist(array $map) {
  $result = array();

  foreach ($map as $pName => $grades) {
    // Sort grades within each product by weight descending
    arsort($grades);

    $gradeList = array();
    foreach ($grades as $gName => $gWeight) {
      $gradeList[] = array('name' => $gName, 'weight' => round($gWeight, 2));
    }

    $result[] = array('product' => $pName, 'grades' => $gradeList);
  }

  // Sort products by their total grade weight descending
  usort($result, function($a, $b) {
    $aTotal = array_sum(array_column($a['grades'], 'weight'));
    $bTotal = array_sum(array_column($b['grades'], 'weight'));
    return $bTotal <=> $aTotal;
  });

  return $result;
}

## Build name/total_weight breakdown sorted by weight desc
// Converts a [name => weight] map into a flat array for the bar chart breakdowns
function buildBreakdown(array $map) {
  $result = array();

  foreach ($map as $name => $weight) {
    $result[] = array('name' => $name, 'total_weight' => round($weight, 2));
  }

  // Sort by weight descending so the largest bar appears first
  usort($result, function($a, $b) {
    return $b['total_weight'] <=> $a['total_weight'];
  });

  return $result;
}

## Build hourly breakdown — round each bucket to 2 decimal places
// Array index = hour (0-23), value = total net weight in that hour
$hourlyRecvRounded = array();
foreach ($hourlyRecv as $h => $w) {
  $hourlyRecvRounded[$h] = round($w, 2);
}

$hourlyDispRounded = array();
foreach ($hourlyDisp as $h => $w) {
  $hourlyDispRounded[$h] = round($w, 2);
}

## Build volume trend sorted by date asc
// Sort by date key then convert to an indexed array for the trend chart
ksort($trendMap);
$volumeTrend = array();
foreach ($trendMap as $date => $vals) {
  $volumeTrend[] = array(
    'date'      => $date,
    'receiving' => round($vals['receiving'], 2),
    'dispatch'  => round($vals['dispatch'],  2),
  );
}

## Build receiving value rounded
// Round each currency total to 2 decimal places for the summary card
$receivingValueRounded = array();
foreach ($receivingValue as $cur => $val) {
  $receivingValueRounded[$cur] = round($val, 2);
}

## Build dispatch value rounded
// Round each currency total to 2 decimal places for the summary card
$dispatchValueRounded = array();
foreach ($dispatchValue as $cur => $val) {
  $dispatchValueRounded[$cur] = round($val, 2);
}

## Build breakdowns
// Only include supplier breakdown when not filtering to dispatch-only
if ($status !== 'DISPATCH') {
  $supplierBreakdown = buildBreakdown($supplierMap);
} else {
  $supplierBreakdown = array();
}

// Only include customer breakdown when not filtering to receiving-only
if ($status !== 'RECEIVING') {
  $customerBreakdown = buildBreakdown($customerMap);
} else {
  $customerBreakdown = array();
}

## Response
$response = array(
  'status'  => 'success',
  'summary' => array(
    'receiving_weight' => round($receivingWeight, 2),
    'receiving_count'  => $receivingCount,
    'receiving_value'  => $receivingValueRounded,
    'dispatch_weight'  => round($dispatchWeight, 2),
    'dispatch_count'   => $dispatchCount,
    'dispatch_value'   => $dispatchValueRounded,
  ),
  'supplierBreakdown'         => $supplierBreakdown,
  'customerBreakdown'         => $customerBreakdown,
  'gradeDistribution'         => buildGradeDist($gradeMapRecv),  // receiving: grouped by product
  'gradeDistributionDispatch' => buildGradeDist($gradeMapDisp),  // dispatch:  grouped by product
  'volumeTrend'               => $volumeTrend,
  'hourlyReceiving'           => $hourlyRecvRounded,  // index 0-23 => kg
  'hourlyDispatch'            => $hourlyDispRounded,  // index 0-23 => kg
);

echo json_encode($response);
