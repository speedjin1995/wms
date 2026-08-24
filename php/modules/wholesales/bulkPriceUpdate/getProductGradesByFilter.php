<?php
session_start();
require_once '../../../db_connect.php';

if (!isset($_SESSION['userID'])) {
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit;
}

$company        = $_SESSION['customer'];
$role           = $_SESSION['role'];
$dateInput      = $_POST['date']     ?? '';
$statusInput    = $_POST['status']   ?? '';
$customerFilter = isset($_POST['customer']) ? mysqli_real_escape_string($db, $_POST['customer']) : '';
$supplierFilter = isset($_POST['supplier']) ? mysqli_real_escape_string($db, $_POST['supplier']) : '';
$productFilter  = isset($_POST['product'])  ? mysqli_real_escape_string($db, $_POST['product'])  : '';
$gradeFilter    = isset($_POST['grade'])    ? mysqli_real_escape_string($db, $_POST['grade'])    : '';

$dateFilter = '';
if (!empty($dateInput)) {
  $dateTime = DateTime::createFromFormat('d/m/Y', $dateInput);
  if ($dateTime) {
    $fromDate   = $dateTime->format('Y-m-d 00:00:00');
    $toDate     = $dateTime->format('Y-m-d 23:59:59');
    $dateFilter = " AND start_time >= '$fromDate' AND start_time <= '$toDate'";
  }
}

$statusFilter = '';
if (!empty($statusInput)) {
  $s = mysqli_real_escape_string($db, $statusInput);
  $statusFilter = " AND status = '$s'";
}

$companyFilter = ($role != 'SADMIN') ? " AND company = '$company'" : '';

$partyFilter = '';
if ($customerFilter !== '') {
  $partyFilter = " AND customer = '$customerFilter'";
} elseif ($supplierFilter !== '') {
  $partyFilter = " AND supplier = '$supplierFilter'";
}

$sql    = "SELECT w.weight_details FROM wholesales w
        LEFT JOIN customers c ON w.customer = c.id
        LEFT JOIN supplies s ON w.supplier = s.id
        WHERE w.deleted = '0' AND w.records_type = 'wholesales'
        AND (c.customer_type IS NULL OR c.customer_type = '' OR c.customer_type = 'Normal' OR w.customer IS NULL OR w.customer = '')
        AND (s.supplier_type IS NULL OR s.supplier_type = '' OR s.supplier_type = 'Normal' OR w.supplier IS NULL OR w.supplier = '')
        $companyFilter $dateFilter $statusFilter $partyFilter";
$result = mysqli_query($db, $sql);

$combos = [];
while ($row = mysqli_fetch_assoc($result)) {
  $details = json_decode($row['weight_details'], true);
  if (!is_array($details)) continue;
  foreach ($details as $d) {
    if (($d['isRejected'] ?? '') === 'YES') continue;
    if ($productFilter !== '' && ($d['product'] ?? '') != $productFilter) continue;
    if ($gradeFilter   !== '' && ($d['grade']   ?? '') != $gradeFilter)   continue;
    $key = ($d['product'] ?? '') . '||' . ($d['grade'] ?? '');
    if (!isset($combos[$key])) {
      $combos[$key] = [
        'product_id'   => $d['product']      ?? '',
        'product_name' => $d['product_name'] ?? '',
        'grade'        => $d['grade']        ?? '',
      ];
    }
  }
}

// Sort by product name then grade
$list = array_values($combos);
usort($list, function($a, $b) {
  $cmp = strcmp($a['product_name'], $b['product_name']);
  return $cmp !== 0 ? $cmp : strcmp($a['grade'], $b['grade']);
});

echo json_encode(['status' => 'success', 'combos' => $list]);
