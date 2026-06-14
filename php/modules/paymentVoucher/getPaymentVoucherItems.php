<?php
require_once '../../db_connect.php';
require_once '../../lookup.php';
$db->set_charset('utf8mb4');
session_start();

if (!isset($_POST['parent_id'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Missing Attribute']);
    exit;
}

$parentId = filter_input(INPUT_POST, 'parent_id', FILTER_SANITIZE_NUMBER_INT);
$pvId     = (isset($_POST['pv_id']) && $_POST['pv_id'] != '') ? $_POST['pv_id'] : null;

$company  = $_SESSION['customer'];
$role     = $_SESSION['role'];
$module   = $_SESSION['module'] ?? 'wholesales';

$incomingStatus = ($module == 'industrial') ? 'INCOMING' : 'RECEIVING';
$recordType     = ($module == 'industrial') ? 'industrial' : 'wholesales';
$companyFilter  = ($role != 'SADMIN') ? " AND w.company = '$company'" : '';

if ($pvId) {
    // Editing existing PV — load all records tied to this PV
    if ($stmt = $db->prepare("SELECT w.* FROM wholesales w
                               WHERE w.pv_id = ? AND w.deleted = 0
                               $companyFilter
                               ORDER BY w.created_datetime ASC")) {
        $stmt->bind_param('s', $pvId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    }
} else {
    // New PV — load all unlinked records for all child suppliers under this parent
    if ($stmt = $db->prepare("SELECT w.* FROM wholesales w
                               INNER JOIN supplies s ON CAST(w.supplier AS UNSIGNED) = s.id
                               WHERE s.parent = ?
                               AND w.deleted = 0
                               AND w.status = '$incomingStatus'
                               AND w.records_type = '$recordType'
                               AND w.pv_id IS NULL
                               $companyFilter
                               ORDER BY w.created_datetime ASC")) {
        $stmt->bind_param('s', $parentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    }
}

if (!isset($result)) {
    echo json_encode(['status' => 'failed', 'message' => 'Query failed']);
    exit;
}

$items     = [];
$totalNett = 0;

while ($row = $result->fetch_assoc()) {
    $weightDetails = json_decode($row['weight_details'] ?? '[]', true) ?? [];
    $nett  = 0;
    $gross = 0;

    foreach ($weightDetails as $wd) {
        $nett  += floatval($wd['net']   ?? 0);
        $gross += floatval($wd['gross'] ?? 0);
    }

    // Fallback to total_weight if weight_details is empty
    if ($nett  == 0) $nett  = floatval($row['total_weight']);
    if ($gross == 0) $gross = floatval($row['total_weight']);

    $unitPrice = floatval($row['unit_price'] ?? 0);
    $nettAmt   = $unitPrice * $nett;
    $totalNett += $nett;

    $items[] = [
        'id'            => $row['id'],
        'serial_no'     => $row['serial_no'],
        'supplier_id'   => $row['supplier'],
        'supplier_name' => searchSupplierNameById($row['supplier'], $row['other_supplier'] ?? '', $db),
        'vehicle_no'    => $row['vehicle_no'],
        'gross'         => number_format($gross, 2),
        'nett'          => number_format($nett, 2),
        'nett_raw'      => $nett,
        'unit_price'    => number_format($unitPrice, 2),
        'nett_amount'   => number_format($nettAmt, 2),
        'pv_id'         => $row['pv_id'],
    ];
}

// Get existing PV header data if editing
$pvData = [];
if ($pvId) {
    if ($pvStmt = $db->prepare("SELECT * FROM payment_vouchers WHERE id = ? AND deleted = 0")) {
        $pvStmt->bind_param('s', $pvId);
        $pvStmt->execute();
        $pvData = $pvStmt->get_result()->fetch_assoc() ?? [];
        $pvStmt->close();
    }
}

echo json_encode([
    'status'            => 'success',
    'items'             => $items,
    'total_nett_weight' => number_format($totalNett, 2),
    'paymentVoucher'    => $pvData,
]);
