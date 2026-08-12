<?php
require_once '../../../../php/db_connect.php';
session_start();

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$company = $_SESSION['customer'];
$role = $_SESSION['role'] ?? 'NORMAL';
$productId = isset($_POST['product_id']) ? $_POST['product_id'] : '';

if (empty($productId)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing product_id']);
    exit();
}

// Get grades linked to this product via product_grades
if ($role != 'SADMIN') {
    $stmt = $db->prepare("SELECT DISTINCT g.id, g.units FROM grades g INNER JOIN product_grades pg ON g.id = pg.grade_id WHERE pg.product_id = ? AND pg.deleted = '0' AND g.deleted = '0' AND g.customer = ? ORDER BY g.units ASC");
    $stmt->bind_param('ss', $productId, $company);
} else {
    $stmt = $db->prepare("SELECT DISTINCT g.id, g.units FROM grades g INNER JOIN product_grades pg ON g.id = pg.grade_id WHERE pg.product_id = ? AND pg.deleted = '0' AND g.deleted = '0' ORDER BY g.units ASC");
    $stmt->bind_param('s', $productId);
}

$stmt->execute();
$result = $stmt->get_result();
$grades = [];
while ($row = $result->fetch_assoc()) {
    $grades[] = ['id' => $row['id'], 'units' => $row['units']];
}
$stmt->close();

// If no grades linked via product_grades, fall back to all company grades
if (empty($grades)) {
    if ($role != 'SADMIN') {
        $fallback = $db->query("SELECT id, units FROM grades WHERE deleted = '0' AND customer = '$company' ORDER BY units ASC");
    } else {
        $fallback = $db->query("SELECT id, units FROM grades WHERE deleted = '0' ORDER BY units ASC");
    }
    while ($row = mysqli_fetch_assoc($fallback)) {
        $grades[] = ['id' => $row['id'], 'units' => $row['units']];
    }
}

echo json_encode(['status' => 'success', 'grades' => $grades]);
