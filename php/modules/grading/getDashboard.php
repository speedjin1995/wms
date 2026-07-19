<?php
## Database configuration
require_once '../../db_connect.php';
session_start();

## Read value
$fromDate = $_POST['fromDate'] ?? '';
$toDate = $_POST['toDate'] ?? '';
$locationId = $_POST['location'] ?? '';

## Search
$searchQuery = '';

if ($fromDate != null && $fromDate != '') {
  $dateTime = DateTime::createFromFormat('d/m/Y', $fromDate);
  $fromDateTime = $dateTime->format('Y-m-d 00:00:00');
  $searchQuery .= " and DATE(g.start_date) >= '".$fromDateTime."'";
}

if ($toDate != null && $toDate != '') {
  $dateTime = DateTime::createFromFormat('d/m/Y', $toDate);
  $toDateTime = $dateTime->format('Y-m-d 23:59:59');
  $searchQuery .= " and DATE(g.start_date) <= '".$toDateTime."'";
}

if ($locationId != null && $locationId != '') {
  $locationId = mysqli_real_escape_string($db, $locationId);
  $searchQuery .= " and g.location = '".$locationId."'";
}

$company = $_SESSION['customer'];
$user = $_SESSION['userID'];
$role = $_SESSION['role'];

if ($role != 'SADMIN') {
  $companyFilter = " AND g.company = '".$company."'";
} else {
  $companyFilter = '';
}

## Fetch records
$empQuery = "SELECT g.id, gi.product_id, gi.to_grade, gi.nett_weight, gi.gross_weight, gi.tare_weight,
                    p.product_name, gr.units AS grade_name
             FROM grading g
             INNER JOIN grading_items gi ON gi.grading_id = g.id AND gi.deleted = 0
             LEFT JOIN products p ON gi.product_id = p.id
             LEFT JOIN grades gr ON gi.to_grade = gr.id
             WHERE g.deleted = 0".$companyFilter.$searchQuery."
             AND gi.to_grade != 'REJ'";

$empRecords = mysqli_query($db, $empQuery);

## Compute totals
$sessionIds = array();
$totalNet = 0;
$totalGross = 0;
$totalTare = 0;
$productGradeMap = array();

while ($row = mysqli_fetch_assoc($empRecords)) {
  $sessionIds[$row['id']] = true;
  $net = floatval($row['nett_weight']);
  $gross = floatval($row['gross_weight']);
  $tare = floatval($row['tare_weight']);

  $totalNet += $net;
  $totalGross += $gross;
  $totalTare += $tare;

  $productName = $row['product_name'] ?: 'Unknown';
  $gradeName = $row['grade_name'] ?: 'Unknown';
  $key = $productName . '||' . $gradeName;

  if (!isset($productGradeMap[$key])) {
    $productGradeMap[$key] = array('product_name' => $productName, 'grade_name' => $gradeName, 'total_weight' => 0, 'item_count' => 0);
  }
  $productGradeMap[$key]['total_weight'] += $net;
  $productGradeMap[$key]['item_count']++;
}

## Build product+grade breakdown sorted by product asc, weight desc
$productGradeBreakdown = array_values($productGradeMap);
usort($productGradeBreakdown, function($a, $b) {
  $cmp = strcmp($a['product_name'], $b['product_name']);
  return $cmp !== 0 ? $cmp : $b['total_weight'] <=> $a['total_weight'];
});

foreach ($productGradeBreakdown as &$item) {
  $item['total_weight'] = round($item['total_weight'], 2);
}
unset($item);

## Response
$response = array(
  'status' => 'success',
  'summary' => array(
    'session_count' => count($sessionIds),
    'total_net' => round($totalNet, 2),
    'total_gross' => round($totalGross, 2),
    'total_tare' => round($totalTare, 2),
  ),
  'productGradeBreakdown' => $productGradeBreakdown,
);

echo json_encode($response);

?>
