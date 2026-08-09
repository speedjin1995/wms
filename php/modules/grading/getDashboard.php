<?php
## Database configuration
require_once '../../db_connect.php';
session_start();

## Read value
$fromDate   = $_POST['fromDate'] ?? '';
$toDate     = $_POST['toDate'] ?? '';
$locationId = $_POST['location'] ?? '';

$company = $_SESSION['customer'];
$role    = $_SESSION['role'];

$companyFilter = ($role != 'SADMIN') ? " AND g.company = '" . $company . "'" : '';
$wsCompanyFilter = ($role != 'SADMIN') ? " AND w.company = '" . $company . "'" : '';

## Date / location search
$searchQuery = '';
$wsSearchQuery = '';

if ($fromDate != '') {
    $dt = DateTime::createFromFormat('d/m/Y', $fromDate);
    $searchQuery   .= " AND DATE(g.start_date) >= '" . $dt->format('Y-m-d') . "'";
    $wsSearchQuery .= " AND DATE(w.start_time) >= '" . $dt->format('Y-m-d') . "'";
}
if ($toDate != '') {
    $dt = DateTime::createFromFormat('d/m/Y', $toDate);
    $searchQuery   .= " AND DATE(g.start_date) <= '" . $dt->format('Y-m-d') . "'";
    $wsSearchQuery .= " AND DATE(w.start_time) <= '" . $dt->format('Y-m-d') . "'";
}
if ($locationId != '') {
    $locationId   = mysqli_real_escape_string($db, $locationId);
    $searchQuery .= " AND g.location = '" . $locationId . "'";
}

## ── 1. GRADING breakdown (grading_items) ──────────────────────────────────
$gradingQuery = "SELECT gi.product_id, gi.nett_weight, gi.gross_weight, gi.tare_weight,
                        p.product_name, gr.units AS grade_name
                 FROM grading g
                 INNER JOIN grading_items gi ON gi.grading_id = g.id AND gi.deleted = 0
                 LEFT JOIN products p ON gi.product_id = p.id
                 LEFT JOIN grades gr ON gi.to_grade = gr.id
                 WHERE g.deleted = 0 AND gi.to_grade != 'REJ'" . $companyFilter . $searchQuery;

$sessionIds      = [];
$totalNet        = 0;
$gradingMap      = [];
$gradingResult   = mysqli_query($db, $gradingQuery);

while ($row = mysqli_fetch_assoc($gradingResult)) {
    $net         = floatval($row['nett_weight']);
    $totalNet   += $net;
    $productName = $row['product_name'] ?: 'Unknown';
    $gradeName   = $row['grade_name']   ?: 'Unknown';
    $key         = $productName . '||' . $gradeName;
    if (!isset($gradingMap[$key])) {
        $gradingMap[$key] = ['product_name' => $productName, 'grade_name' => $gradeName, 'total_weight' => 0];
    }
    $gradingMap[$key]['total_weight'] += $net;
}

$gradingBreakdown = array_values($gradingMap);
usort($gradingBreakdown, function($a, $b) {
    $cmp = strcmp($a['product_name'], $b['product_name']);
    return $cmp !== 0 ? $cmp : $b['total_weight'] <=> $a['total_weight'];
});
foreach ($gradingBreakdown as &$item) { $item['total_weight'] = round($item['total_weight'], 2); }
unset($item);

## ── 2. RECEIVING breakdown (wholesales weight_details JSON) ───────────────
$wsQuery = "SELECT w.weight_details
            FROM wholesales w
            WHERE w.deleted = 0 AND w.status IN ('RECEIVING','INCOMING')" . $wsCompanyFilter . $wsSearchQuery;

$receivingMap    = [];
$productCache    = [];
$wsResult        = mysqli_query($db, $wsQuery);

while ($row = mysqli_fetch_assoc($wsResult)) {
    $details = json_decode($row['weight_details'], true) ?: [];
    foreach ($details as $item) {
        $productId = $item['product'] ?? '';
        if ($productId == '') continue;
        if (!isset($productCache[$productId])) {
            $pRes = mysqli_query($db, "SELECT product_name FROM products WHERE id = '" . mysqli_real_escape_string($db, $productId) . "'");
            $pRow = mysqli_fetch_assoc($pRes);
            $productCache[$productId] = $pRow['product_name'] ?? 'Unknown';
        }
        $productName = $productCache[$productId];
        $gradeName   = $item['grade'] ?? 'Unknown';
        $net         = floatval($item['net'] ?? 0);
        $key         = $productName . '||' . $gradeName;
        if (!isset($receivingMap[$key])) {
            $receivingMap[$key] = ['product_name' => $productName, 'grade_name' => $gradeName, 'total_weight' => 0];
        }
        $receivingMap[$key]['total_weight'] += $net;
    }
}

$receivingBreakdown = array_values($receivingMap);
usort($receivingBreakdown, function($a, $b) {
    $cmp = strcmp($a['product_name'], $b['product_name']);
    return $cmp !== 0 ? $cmp : $b['total_weight'] <=> $a['total_weight'];
});
foreach ($receivingBreakdown as &$item) { $item['total_weight'] = round($item['total_weight'], 2); }
unset($item);

## Response
echo json_encode([
    'status' => 'success',
    'summary' => [
        'session_count' => count($sessionIds),
        'total_net'     => round($totalNet, 2),
    ],
    'gradingBreakdown'   => $gradingBreakdown,
    'receivingBreakdown' => $receivingBreakdown,
]);
?>
