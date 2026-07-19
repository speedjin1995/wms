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
$user    = $_SESSION['userID'];
$role    = $_SESSION['role'];

if ($role != 'SADMIN') {
  $companyFilter = " AND g.company = '".$company."'";
} else {
  $companyFilter = '';
}

## Summary totals from grading_items
$sel = mysqli_query($db, "
  SELECT
    COUNT(DISTINCT g.id) AS session_count,
    SUM(CAST(gi.nett_weight AS DECIMAL(15,2))) AS total_net,
    SUM(CAST(gi.gross_weight AS DECIMAL(15,2))) AS total_gross,
    SUM(CAST(gi.tare_weight AS DECIMAL(15,2))) AS total_tare
  FROM grading g
  INNER JOIN grading_items gi ON gi.grading_id = g.id AND gi.deleted = 0
  WHERE g.deleted = 0".$companyFilter.$searchQuery."
  AND gi.to_grade != 'REJ'
");
$summary = mysqli_fetch_assoc($sel);

## Reject totals
$selReject = mysqli_query($db, "
  SELECT SUM(CAST(gi.nett_weight AS DECIMAL(15,2))) AS total_reject
  FROM grading g
  INNER JOIN grading_items gi ON gi.grading_id = g.id AND gi.deleted = 0
  WHERE g.deleted = 0".$companyFilter.$searchQuery."
  AND gi.to_grade = 'REJ'
");
$rejectRow = mysqli_fetch_assoc($selReject);
$summary['total_reject'] = $rejectRow['total_reject'] ?? 0;

## Breakdown by product
$productBreakdown = array();
$empRecords = mysqli_query($db, "
  SELECT p.product_name AS name, SUM(CAST(gi.nett_weight AS DECIMAL(15,2))) AS total_weight
  FROM grading g
  INNER JOIN grading_items gi ON gi.grading_id = g.id AND gi.deleted = 0
  LEFT JOIN products p ON gi.product_id = p.id
  WHERE g.deleted = 0".$companyFilter.$searchQuery."
  AND gi.to_grade != 'REJ'
  GROUP BY gi.product_id, p.product_name
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
