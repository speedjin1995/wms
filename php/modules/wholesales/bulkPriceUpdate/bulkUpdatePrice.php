<?php
session_start();
require_once '../../../db_connect.php';

$company = $_SESSION['customer'];
$role    = $_SESSION['role'];
$userId  = $_SESSION['userID'];

$mode          = $_POST['mode']       ?? 'save';
$newPrice      = floatval($_POST['newPrice'] ?? 0);
$pricingType   = $_POST['pricingType'] ?? 'Float';
$dateInput     = $_POST['date']        ?? '';
$productFilter = $_POST['product']     ?? '';
$gradeFilter   = $_POST['grade']       ?? '';
$statusInput   = $_POST['status']      ?? '';

if ($newPrice < 0) {
  echo json_encode(['status' => 'failed', 'message' => 'Price cannot be negative.']);
  exit;
}

## Build date filter
$dateFilter = '';
if (!empty($dateInput)) {
  $dateTime = DateTime::createFromFormat('d/m/Y', $dateInput);
  if ($dateTime) {
    $fromDate = $dateTime->format('Y-m-d 00:00:00');
    $toDate   = $dateTime->format('Y-m-d 23:59:59');
    $dateFilter = " AND start_time >= '$fromDate' AND start_time <= '$toDate'";
  }
}

$statusFilter = '';
if (!empty($statusInput)) {
  $status = mysqli_real_escape_string($db, $statusInput);
  $statusFilter = " AND status = '$status'";
}

$companyFilter = '';
if ($role != 'SADMIN') {
  $companyFilter = " AND company = '$company'";
}

$sql = "SELECT id, serial_no, start_time, weight_details FROM wholesales
        WHERE deleted = '0' AND records_type = 'wholesales'
        $companyFilter $dateFilter $statusFilter";

$result = mysqli_query($db, $sql);
if (!$result) {
  echo json_encode(['status' => 'failed', 'message' => 'Query failed.']);
  exit;
}

if ($mode === 'preview') {
  $previewRows = [];
  while ($row = mysqli_fetch_assoc($result)) {
    $details = json_decode($row['weight_details'], true);
    if (!is_array($details)) continue;
    foreach ($details as $detail) {
      if ($detail['isRejected'] === 'YES') continue;
      if ($productFilter !== '' && $detail['product'] != $productFilter) continue;
      if ($gradeFilter !== '' && $detail['grade'] != $gradeFilter) continue;
      $net      = floatval($detail['net'] ?? 0);
      $newTotal = ($pricingType === 'Float') ? $newPrice * $net : $newPrice;
      $previewRows[] = [
        'serial_no'    => $row['serial_no'],
        'start_time'   => $row['start_time'] ? date('d/m/Y H:i', strtotime($row['start_time'])) : '',
        'product_name' => $detail['product_name'] ?? '',
        'grade'        => $detail['grade'] ?? '',
        'net'          => number_format($net, 2, '.', ','),
        'old_price'    => number_format(floatval($detail['price'] ?? 0), 2, '.', ','),
        'old_total'    => number_format(floatval($detail['total'] ?? 0), 2, '.', ','),
        'new_price'    => number_format($newPrice, 2, '.', ','),
        'new_total'    => number_format($newTotal, 2, '.', ','),
      ];
    }
  }
  echo json_encode(['status' => 'success', 'rows' => $previewRows]);
  exit;
}

## Save mode
$updatedRecords = 0;
$updatedItems   = 0;

while ($row = mysqli_fetch_assoc($result)) {
  $details = json_decode($row['weight_details'], true);
  if (!is_array($details)) continue;

  $changed = false;
  foreach ($details as &$detail) {
    if ($detail['isRejected'] === 'YES') continue;
    if ($productFilter !== '' && $detail['product'] != $productFilter) continue;
    if ($gradeFilter !== '' && $detail['grade'] != $gradeFilter) continue;

    $net   = floatval($detail['net'] ?? 0);
    $total = ($pricingType === 'Float') ? $newPrice * $net : $newPrice;

    $detail['price']      = number_format($newPrice, 2, '.', '');
    $detail['total']      = number_format($total, 2, '.', '');
    $detail['fixedfloat'] = $pricingType;
    $changed = true;
    $updatedItems++;
  }
  unset($detail);

  if (!$changed) continue;

  ## Recalculate totals
  $totalPrice = 0;
  foreach ($details as $d) {
    $totalPrice += floatval($d['total'] ?? 0);
  }

  $newJson = mysqli_real_escape_string($db, json_encode($details));
  $id = intval($row['id']);

  $updateSql = "UPDATE wholesales SET
    weight_details = '$newJson',
    total_price = $totalPrice,
    modified_by = '$userId'
    WHERE id = $id";

  if (mysqli_query($db, $updateSql)) {
    $updatedRecords++;
  }
}

echo json_encode([
  'status'  => 'success',
  'message' => "Updated $updatedItems item(s) across $updatedRecords record(s)."
]);
?>
