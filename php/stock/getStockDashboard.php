<?php
require_once '../db_connect.php';
session_start();

$company = $_SESSION['customer'];
$role    = $_SESSION['role'] ?? 'NORMAL';
$type    = $_POST['type'] ?? '';
$product  = $_POST['product'] ?? '';
$grade    = $_POST['grade'] ?? '';
$category = $_POST['category'] ?? '';

$companyWhere  = ($role != 'SADMIN') ? "AND t.company = '$company'" : '';
$productWhere  = $product  ? "AND t.product_id = '$product'"  : '';
$gradeWhere    = $grade    ? "AND t.grade = '$grade'"          : '';
$categoryWhere = $category ? "AND c.id = '$category'"         : '';

$data = [];

if ($type === 'raw') {
    $sql = "SELECT t.product_id, t.grade, t.balance,
                   p.product_name, c.id as category_id, c.category_name,
                   g.units as grade_name
            FROM raw_stock_balance t
            LEFT JOIN products p  ON p.id = t.product_id
            LEFT JOIN categories c ON c.id = p.category
            LEFT JOIN grades g    ON g.id = t.grade
            WHERE t.deleted = 0 AND t.balance > 0
            $companyWhere $productWhere $gradeWhere $categoryWhere
            ORDER BY c.category_name, p.product_name, g.units";

    $result = $db->query($sql);
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

} elseif ($type === 'graded') {
    $sql = "SELECT t.product_id, t.grade, t.balance,
                   p.product_name, c.id as category_id, c.category_name,
                   g.units as grade_name
            FROM grading_stock_balance t
            LEFT JOIN products p   ON p.id = t.product_id
            LEFT JOIN categories c ON c.id = p.category
            LEFT JOIN grades g     ON g.id = t.grade
            WHERE t.deleted = 0 AND t.balance > 0
            $companyWhere $productWhere $gradeWhere $categoryWhere
            ORDER BY c.category_name, p.product_name, g.units";

    $result = $db->query($sql);
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

} elseif ($type === 'packed') {
    $sql = "SELECT t.product_id, t.grade, t.packaging_size, t.box_quantity,
                   p.product_name, c.id as category_id, c.category_name,
                   g.units as grade_name, pk.packaging_name
            FROM stock_balances t
            LEFT JOIN products p   ON p.id = t.product_id
            LEFT JOIN categories c ON c.id = p.category
            LEFT JOIN grades g     ON g.id = t.grade
            LEFT JOIN packaging pk ON pk.id = t.packaging_size
            WHERE t.deleted = 0 AND t.box_quantity > 0
            $companyWhere $productWhere $gradeWhere $categoryWhere
            ORDER BY c.category_name, p.product_name, g.units, pk.packaging_name";

    $result = $db->query($sql);
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

} else {
    echo json_encode(['status' => 'failed', 'message' => 'Invalid type']);
    exit;
}

echo json_encode(['status' => 'success', 'data' => $data]);
