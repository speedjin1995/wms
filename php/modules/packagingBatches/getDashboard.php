<?php
## Database configuration
require_once '../../db_connect.php';
session_start();

## Read value
$fromDate = $_POST['fromDate'] ?? '';
$toDate = $_POST['toDate'] ?? '';
$locationId = $_POST['location'] ?? '';
$productionLineId = $_POST['productionLine'] ?? '';

## Search
$searchQuery = '';

if ($fromDate != null && $fromDate != '') {
  $dateTime = DateTime::createFromFormat('d/m/Y', $fromDate);
  $fromDateTime = $dateTime->format('Y-m-d 00:00:00');
  $searchQuery .= " and DATE(pb.packaging_date) >= '".$fromDateTime."'";
}

if ($toDate != null && $toDate != '') {
  $dateTime = DateTime::createFromFormat('d/m/Y', $toDate);
  $toDateTime = $dateTime->format('Y-m-d 23:59:59');
  $searchQuery .= " and DATE(pb.packaging_date) <= '".$toDateTime."'";
}

if ($locationId != null && $locationId != '') {
  $locationId = mysqli_real_escape_string($db, $locationId);
  $searchQuery .= " and pb.location = '".$locationId."'";
}

if ($productionLineId != null && $productionLineId != '') {
  $productionLineId = mysqli_real_escape_string($db, $productionLineId);
  $searchQuery .= " and pb.production_line = '".$productionLineId."'";
}

$company = $_SESSION['customer'];
$user = $_SESSION['userID'];
$role = $_SESSION['role'];

if ($role != 'SADMIN') {
  $companyFilter = " AND pb.company = '".$company."'";
} else {
  $companyFilter = '';
}

## Fetch records
$empQuery = "SELECT pb.id AS batch_id, pbi.product_id, pbi.grade, pbi.packaging_size,
                    pbi.units_per_box, pbi.weight,
                    p.product_name, gr.units AS grade_name,
                    pk.packaging_name
             FROM packaging_batches pb
             INNER JOIN packaging_batch_items pbi ON pbi.packaging_batch_id = pb.id AND pbi.deleted = 0
             LEFT JOIN products p ON pbi.product_id = p.id
             LEFT JOIN grades gr ON pbi.grade = gr.id
             LEFT JOIN packaging pk ON pbi.packaging_size = pk.id
             WHERE pb.deleted = 0".$companyFilter.$searchQuery;

$empRecords = mysqli_query($db, $empQuery);

## Compute totals
$batchIds = array();
$totalWeight = 0;
$totalBoxes = 0;
$productMap = array();

while ($row = mysqli_fetch_assoc($empRecords)) {
  $batchIds[$row['batch_id']] = true;
  $weight = floatval($row['weight']);
  $totalWeight += $weight;
  $totalBoxes++;

  $productName = $row['product_name'] ?: 'Unknown';
  $gradeName = $row['grade_name'] ?: $row['grade'] ?: 'Unknown';
  $packagingName = $row['packaging_name'] ?: $row['packaging_size'] ?: 'Unknown';

  if (!isset($productMap[$productName])) {
    $productMap[$productName] = array('total_weight' => 0, 'total_boxes' => 0, 'grades' => array());
  }
  $productMap[$productName]['total_weight'] += $weight;
  $productMap[$productName]['total_boxes']++;

  $gradeKey = $gradeName . '||' . $packagingName;
  if (!isset($productMap[$productName]['grades'][$gradeKey])) {
    $productMap[$productName]['grades'][$gradeKey] = array(
      'grade_name' => $gradeName,
      'packaging_name' => $packagingName,
      'total_weight' => 0,
      'total_boxes' => 0,
    );
  }
  $productMap[$productName]['grades'][$gradeKey]['total_weight'] += $weight;
  $productMap[$productName]['grades'][$gradeKey]['total_boxes']++;
}

## Build product breakdown sorted by weight desc
$productBreakdown = array();
foreach ($productMap as $productName => $data) {
  $grades = array_values($data['grades']);
  usort($grades, function($a, $b) { return $b['total_weight'] <=> $a['total_weight']; });
  foreach ($grades as &$g) {
    $g['total_weight'] = round($g['total_weight'], 2);
  }
  unset($g);
  $productBreakdown[] = array(
    'product_name' => $productName,
    'total_weight' => round($data['total_weight'], 2),
    'total_boxes' => $data['total_boxes'],
    'grades' => $grades,
  );
}
usort($productBreakdown, function($a, $b) { return $b['total_weight'] <=> $a['total_weight']; });

## Response
$response = array(
  'status' => 'success',
  'summary' => array(
    'batch_count' => count($batchIds),
    'total_boxes' => $totalBoxes,
    'total_weight' => round($totalWeight, 2),
  ),
  'productBreakdown' => $productBreakdown,
);

echo json_encode($response);

?>
