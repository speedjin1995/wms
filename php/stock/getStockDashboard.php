<?php
require_once '../db_connect.php';
session_start();

$company = $_SESSION['customer'];
$role    = $_SESSION['role'] ?? 'NORMAL';
$type    = $_POST['type'] ?? '';
$product     = $_POST['product'] ?? '';
$grade       = $_POST['grade'] ?? '';
$category    = $_POST['category'] ?? '';
$productType = $_POST['productType'] ?? '';

$stockLevel = 'GRADE';
$levelStmt = $db->prepare("SELECT stock_level FROM companies WHERE id = ?");
$levelStmt->bind_param('s', $company);
$levelStmt->execute();
$levelRow = $levelStmt->get_result()->fetch_assoc();
$levelStmt->close();
if (($levelRow['stock_level'] ?? '') === 'PRODUCT') {
    $stockLevel = 'PRODUCT';
}

// Each filter is [sql condition, bound value]
function runDashboardQuery($db, $sql, $filters) {
    $where  = array_map(function ($f) { return $f[0]; }, $filters);
    $params = array_map(function ($f) { return $f[1]; }, $filters);
    $sql    = str_replace('{FILTERS}', $where ? ' AND ' . implode(' AND ', $where) : '', $sql);

    $stmt = $db->prepare($sql);
    if ($params) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

$baseFilters = [];
if ($role != 'SADMIN') $baseFilters[] = ['t.company = ?', $company];
if ($product)          $baseFilters[] = ['t.product_id = ?', $product];
if ($category)         $baseFilters[] = ['c.id = ?', $category];

$gradeFilters = $grade ? [['t.grade = ?', $grade]] : [];
$typeFilters  = in_array($productType, ['Local', 'Export']) ? [['t.type = ?', $productType]] : [];

$data = [];

if ($type === 'raw') {
    if ($stockLevel === 'PRODUCT') {
        // Product-level companies: one row per product + type, grade ignored
        $sql = "SELECT t.product_id, t.type, SUM(t.balance) AS balance,
                       p.product_name, c.id as category_id, c.category_name,
                       NULL AS grade, NULL AS grade_name
                FROM raw_stock_balance t
                LEFT JOIN products p   ON p.id = t.product_id
                LEFT JOIN categories c ON c.id = p.category
                WHERE t.deleted = 0{FILTERS}
                GROUP BY t.product_id, t.type, p.product_name, c.id, c.category_name
                ORDER BY c.category_name, p.product_name, t.type";
        $data = runDashboardQuery($db, $sql, array_merge($baseFilters, $typeFilters));
    } else {
        $sql = "SELECT t.product_id, t.grade, t.balance, t.type,
                       p.product_name, c.id as category_id, c.category_name,
                       g.units as grade_name
                FROM raw_stock_balance t
                LEFT JOIN products p  ON p.id = t.product_id
                LEFT JOIN categories c ON c.id = p.category
                LEFT JOIN grades g    ON g.id = t.grade
                WHERE t.deleted = 0{FILTERS}
                ORDER BY c.category_name, p.product_name, g.units, t.type";
        $data = runDashboardQuery($db, $sql, array_merge($baseFilters, $gradeFilters, $typeFilters));
    }

} elseif ($type === 'graded') {
    $sql = "SELECT t.product_id, t.grade, t.balance, t.type,
                   p.product_name, c.id as category_id, c.category_name,
                   g.units as grade_name
            FROM grading_stock_balance t
            LEFT JOIN products p   ON p.id = t.product_id
            LEFT JOIN categories c ON c.id = p.category
            LEFT JOIN grades g     ON g.id = t.grade
            WHERE t.deleted = 0{FILTERS}
            ORDER BY c.category_name, p.product_name, g.units, t.type";
    $data = runDashboardQuery($db, $sql, array_merge($baseFilters, $gradeFilters, $typeFilters));

} elseif ($type === 'compare') {
    // Raw vs graded per product + type (company scope applied to both sides)
    $companyWhere = ($role != 'SADMIN') ? "AND t.company = ?" : '';
    $sql = "SELECT p.id AS product_id, p.product_name, c.id AS category_id, c.category_name, x.type,
                   SUM(x.raw_balance) AS raw_balance, SUM(x.graded_balance) AS graded_balance
            FROM (
                SELECT t.product_id, t.type, SUM(t.balance) AS raw_balance, 0 AS graded_balance
                FROM raw_stock_balance t
                WHERE t.deleted = 0 $companyWhere
                GROUP BY t.product_id, t.type
                UNION ALL
                SELECT t.product_id, t.type, 0, SUM(t.balance)
                FROM grading_stock_balance t
                WHERE t.deleted = 0 $companyWhere
                GROUP BY t.product_id, t.type
            ) x
            LEFT JOIN products p   ON p.id = x.product_id
            LEFT JOIN categories c ON c.id = p.category
            GROUP BY x.product_id, x.type, p.id, p.product_name, c.id, c.category_name
            ORDER BY c.category_name, p.product_name, x.type";
    $stmt = $db->prepare($sql);
    if ($role != 'SADMIN') {
        $stmt->bind_param('ss', $company, $company);
    }
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} elseif ($type === 'packed') {
    $sql = "SELECT t.product_id, t.grade, t.packaging_size, t.box_quantity,
                   p.product_name, c.id as category_id, c.category_name,
                   g.units as grade_name, pk.packaging_name
            FROM stock_balances t
            LEFT JOIN products p   ON p.id = t.product_id
            LEFT JOIN categories c ON c.id = p.category
            LEFT JOIN grades g     ON g.id = t.grade
            LEFT JOIN packaging pk ON pk.id = t.packaging_size
            WHERE t.deleted = 0{FILTERS}
            ORDER BY c.category_name, p.product_name, g.units, pk.packaging_name";
    $data = runDashboardQuery($db, $sql, array_merge($baseFilters, $gradeFilters));

} else {
    echo json_encode(['status' => 'failed', 'message' => 'Invalid type']);
    exit;
}

echo json_encode(['status' => 'success', 'stock_level' => $stockLevel, 'data' => $data]);
