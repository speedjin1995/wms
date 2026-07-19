<?php
## Database configuration
require_once '../../db_connect.php';
session_start();

## Read value
$fromDate   = $_POST['fromDate'] ?? '';
$toDate     = $_POST['toDate'] ?? '';
$locationId = $_POST['location'] ?? '';

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

$company = $_SESSION['customer'];
$user    = $_SESSION['userID'];
$role    = $_SESSION['role'];

if ($role != 'SADMIN') {
  $companyFilter = " AND pb.company = '".$company."'";
} else {
  $companyFilter = '';
}

## Summary totals
$sel = mysqli_query($db, "
  SELECT
    COUNT(DISTINCT pb.id) AS batch_count,
    COUNT(pbi.id) AS total_boxes,
    SUM(CAST(pbi.weight AS DECIMAL(15,2))) AS total_weight
  FROM packaging_batches pb
  INNER JOIN packaging_batch_items pbi ON pbi.packaging_batch_id = pb.id AND pbi.deleted = 0
  WHERE pb.deleted = 0".$companyFilter.$searchQuery
);
$summary = mysqli_fetch_assoc($sel);

## Breakdown by product
$productBreakdown = array();
$empRecords = mysqli_query($db, "
  SELECT p.product_name AS name,
         SUM(CAST(pbi.weight AS DECIMAL(15,2))) AS total_weight,
         COUNT(pbi.id) AS total_boxes
  FROM packaging_batches pb
  INNER JOIN packaging_batch_items pbi ON pbi.packaging_batch_id = pb.id AND pbi.deleted = 0
  LEFT JOIN products p ON pbi.product_id = p.id
  WHERE pb.deleted = 0".$companyFilter.$searchQuery."
  GROUP BY pbi.product_id, p.product_name
  ORDER BY total_weight DESC
");
while ($row = mysqli_fetch_assoc($empRecords)) {
  $productBreakdown[] = $row;
}

## Response
$response = array(
  'status'           => 'success',
  'summary'          => $summary,
  'productBreakdown' => $productBreakdown,
);

echo json_encode($response);

?>
