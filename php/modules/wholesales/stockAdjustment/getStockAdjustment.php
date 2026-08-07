<?php
require_once '../../../db_connect.php';
require_once '../../../lookup.php';
session_start();

$company = $_SESSION['customer'];
$role     = $_SESSION['role'] ?? 'NORMAL';
$categoryFilter = $_POST['category'] ?? '';
$productFilter  = $_POST['product']  ?? '';
$gradeFilter    = $_POST['grade']    ?? '';
$dateStr      = $_POST['date']     ?? '';

$dateObj = !empty($dateStr) ? DateTime::createFromFormat('d/m/Y', $dateStr) : new DateTime();
$fromDT  = $dateObj->format('Y-m-d') . ' 00:00:00';
$toDT    = $dateObj->format('Y-m-d') . ' 23:59:59';

// Build SQL-level filters to find wholesales records for the day
$searchQuery = "AND wholesales.deleted = '0'";
if ($role != 'SADMIN') {
    $searchQuery .= " AND wholesales.company = '$company'";
}
$searchQuery .= " AND wholesales.start_time >= '$fromDT'";
$searchQuery .= " AND wholesales.start_time <= '$toDT'";

if (!empty($categoryFilter)) {
    $catProductIds = [];
    $catStmt = $db->prepare("SELECT id FROM products WHERE category = ? AND deleted = '0'");
    $catStmt->bind_param('s', $categoryFilter);
    $catStmt->execute();
    $catResult = $catStmt->get_result();
    while ($catRow = $catResult->fetch_assoc()) {
        $catProductIds[] = $catRow['id'];
    }
    $catStmt->close();

    if (count($catProductIds) > 0) {
        $likeConditions = array_map(fn($id) => "wholesales.weight_details LIKE '%\"product\":\"" . $id . "\"%'", $catProductIds);
        $searchQuery .= " AND (" . implode(' OR ', $likeConditions) . ")";
    } else {
        $searchQuery .= " AND 1=0";
    }
}

if (!empty($productFilter)) {
    $searchQuery .= " AND wholesales.weight_details LIKE '%" . mysqli_real_escape_string($db, '"product":"' . $productFilter . '"') . "%'";
}

$query = $db->query("SELECT weight_details FROM wholesales WHERE 1=1 $searchQuery");

$productCache      = [];
$categoryNameCache = [];
$seen              = []; // unique product+grade keys from today's records

while ($wRow = $query->fetch_assoc()) {
    $details = json_decode($wRow['weight_details'], true) ?? [];
    foreach ($details as $detail) {
        $productId = $detail['product']  ?? '';
        $gradeId = $detail['grade_id'] ?? '';
        $gradeName = $detail['grade'] ?? '';
        if (empty($productId)) continue;

        // Detail-level filters
        if (!empty($productFilter) && $productFilter != $productId) continue;
        if (!empty($gradeFilter) && $gradeFilter != $gradeId) continue;
        if (!empty($categoryFilter)) {
            $pRow = getProductById($productId, $db, $productCache);
            if (($pRow['category'] ?? '') != $categoryFilter) continue;
        }

        $key = $productId . '_' . $gradeId;
        $seen[$key] = ['product_id' => $productId, 'grade_id' => $gradeId, 'grade' => $gradeName];
    }
}

// Enrich with names and fetch balance from raw_stock_balance
$data = [];
foreach ($seen as $item) {
    $pRow = getProductById($item['product_id'], $db, $productCache);
    $gradeName = searchGradeNameById($item['grade_id'], $db);
    if (empty($gradeName)) $gradeName = $item['grade'] ?? '';
    $catId   = $pRow['category'] ?? '';
    $catName = '';
    if (!empty($catId)) {
        if (!isset($categoryNameCache[$catId])) {
            $categoryNameCache[$catId] = searchCategoryById($catId, $db) ?? '';
        }
        $catName = $categoryNameCache[$catId];
    }

    $balStmt = $db->prepare("SELECT id, balance FROM raw_stock_balance WHERE product_id = ? AND grade = ? AND company = ? AND deleted = '0' LIMIT 1");
    $balStmt->bind_param('sss', $item['product_id'], $item['grade_id'], $company);
    $balStmt->execute();
    $balRow = $balStmt->get_result()->fetch_assoc();
    $balStmt->close();

    $data[] = [
        'id'            => $balRow['id']      ?? null,
        'product_id'    => $item['product_id'],
        'grade'         => $item['grade_id'],
        'product_code'  => $pRow['product_code'] ?? '',
        'product_name'  => $pRow['product_name'] ?? '',
        'grade_name'    => $gradeName,
        'category_name' => $catName,
        'balance'       => round(floatval($balRow['balance'] ?? 0), 4),
    ];
}

usort($data, fn($a, $b) =>
    [$a['category_name'], $a['product_name'], $a['grade_name']]
    <=>
    [$b['category_name'], $b['product_name'], $b['grade_name']]
);

echo json_encode(['status' => 'success', 'data' => $data]);
