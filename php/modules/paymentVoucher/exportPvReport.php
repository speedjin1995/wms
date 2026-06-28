<?php
session_start();
require_once '../../db_connect.php';
require_once '../../lookup.php';

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

$company       = $_SESSION['customer'];
$role          = $_SESSION['role'];
$module        = $_SESSION['module'] ?? 'wholesales';
$language      = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];

// Get company details
$compname     = '';
$compreg      = '';
$compaddress1 = '';
$compaddress2 = '';
$compaddress3 = '';
$compaddress4 = '';
$compphone    = '';

$stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->bind_param('s', $company);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $compname     = $row['name'];
    $compreg      = $row['reg_no'];
    $compaddress1 = $row['address'];
    $compaddress2 = $row['address2'];
    $compaddress3 = $row['address3'];
    $compaddress4 = $row['address4'];
    $compphone    = $row['phone'];
}
$stmt->close();

// Build filters
if (isset($_POST['transactionStatus']) && $_POST['transactionStatus'] != '') {
    $incomingStatus = $_POST['transactionStatus'];
} else {
    if ($module == 'industrial') {
        $incomingStatus = 'INCOMING';
    } else {
        $incomingStatus = 'RECEIVING';
    }
}

if ($module == 'industrial') {
    $recordType = 'industrial';
} else {
    $recordType = 'wholesales';
}

$isIncoming = in_array($incomingStatus, ['RECEIVING', 'INCOMING']);

if ($role != 'SADMIN') {
    $companyFilter = " AND w.company = '$company'";
} else {
    $companyFilter = '';
}

$where = " AND pv.id IS NOT NULL AND pv.deleted = 0 AND w.deleted = 0 AND w.status = '$incomingStatus' AND w.records_type = '$recordType'";

if (!empty($_POST['fromDate'])) {
    $from   = DateTime::createFromFormat('d/m/Y', $_POST['fromDate'])->format('Y-m-d 00:00:00');
    $where .= " AND pv.voucher_date >= '$from'";
}

if (!empty($_POST['toDate'])) {
    $to     = DateTime::createFromFormat('d/m/Y', $_POST['toDate'])->format('Y-m-d 23:59:59');
    $where .= " AND pv.voucher_date <= '$to'";
}

if ($isIncoming) {
    if (!empty($_POST['supplierId'])) {
        $where .= " AND CAST(w.supplier AS UNSIGNED) = " . (int)$_POST['supplierId'];
    }
    if (!empty($_POST['parentSupplierId'])) {
        $where .= " AND s.parent = " . (int)$_POST['parentSupplierId'];
    }
} else {
    if (!empty($_POST['customerId'])) {
        $where .= " AND CAST(w.customer AS UNSIGNED) = " . (int)$_POST['customerId'];
    }
    if (!empty($_POST['parentCustomerId'])) {
        $where .= " AND c.parent = " . (int)$_POST['parentCustomerId'];
    }
}

// Fetch PV records
if ($isIncoming) {
    $sql = "SELECT pv.id as pv_id, pv.voucher_no, pv.voucher_date, pv.invoice_no,
                   pv.unit_price, pv.final_amount, pv.total_nett_weight,
                   sp.supplier_name as entity_name
            FROM wholesales w
            INNER JOIN supplies s ON CAST(w.supplier AS UNSIGNED) = s.id
            INNER JOIN supplies sp ON s.parent = sp.id
            INNER JOIN payment_vouchers pv ON w.pv_id = pv.id
            WHERE 1=1 $companyFilter $where
            GROUP BY pv.id
            ORDER BY pv.voucher_date ASC, pv.voucher_no ASC";
} else {
    $sql = "SELECT pv.id as pv_id, pv.voucher_no, pv.voucher_date, pv.invoice_no,
                   pv.unit_price, pv.final_amount, pv.total_nett_weight,
                   cp.customer_name as entity_name
            FROM wholesales w
            INNER JOIN customers c ON CAST(w.customer AS UNSIGNED) = c.id
            INNER JOIN customers cp ON c.parent = cp.id
            INNER JOIN payment_vouchers pv ON w.pv_id = pv.id
            WHERE 1=1 $companyFilter $where
            GROUP BY pv.id
            ORDER BY pv.voucher_date ASC, pv.voucher_no ASC";
}

$pvRecords   = $db->query($sql);
$rows        = [];
$grandNett   = 0;
$grandAmount = 0;

while ($r = $pvRecords->fetch_assoc()) {
    $rows[]      = $r;
    $grandNett   += floatval(str_replace(',', '', $r['total_nett_weight']));
    $grandAmount += floatval($r['final_amount']);
}

$fromLabel = $_POST['fromDate'] ?? '-';
$toLabel   = $_POST['toDate']   ?? '-';

if ($isIncoming) {
    $typeLabel = $languageArray['receiving_code'][$language] ?? 'Receiving';
} else {
    $typeLabel = $languageArray['dispatch_code'][$language] ?? 'Dispatch';
}

// Build HTML message
$message = '
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @media print {
            @page { size: A4 landscape; margin: 10mm; }
        }
        body { font-family: "Times New Roman", serif; font-size: 12px; margin: 20px; padding: 0; }
        .page-header { text-align: center; margin-bottom: 15px; }
        .page-header h2 { margin: 0 0 3px 0; font-size: 17px; }
        .page-header p { margin: 2px 0; font-size: 12px; }
        .divider { border-bottom: 2px solid #000; margin: 8px 0; }
        .report-title { font-size: 15px; font-weight: bold; text-transform: uppercase; margin: 8px 0 3px 0; }
        .report-subtitle { font-size: 12px; margin-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        td, th { padding: 4px 6px; font-size: 11px; border: none; }
        .table-border th { border-top: 1px solid #000; border-bottom: 1px solid #000; }
        .border-top { border-top: 1px solid #000 !important; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body>
    <div class="page-header">
        <h2>'.$compname.'</h2>
        <p>('.$compreg.')</p>
        <p>'.$compaddress1.(!empty($compaddress2) ? ', '.$compaddress2 : '').(!empty($compaddress3) ? ', '.$compaddress3 : '').'</p>
        <p>Tel: '.$compphone.'</p>
        <div class="divider"></div>
        <div class="report-title">'.$languageArray['payment_voucher_code'][$language].' Report</div>
        <div class="report-subtitle">'.$typeLabel.' &nbsp;|&nbsp; '.$fromLabel.' - '.$toLabel.'</div>
    </div>

    <table>
        <thead>
            <tr class="table-border">
                <th class="text-left">#</th>
                <th class="text-left">'.$languageArray['voucher_date_code'][$language].'</th>
                <th class="text-left">'.$languageArray['voucher_no_code'][$language].'</th>
                <th class="text-left">'.$languageArray['name_code'][$language].'</th>
                <th class="text-left">'.$languageArray['invoice_no_code'][$language].'</th>
                <th class="text-center">'.$languageArray['total_nett_weight_code'][$language].' (KG)</th>
                <th class="text-center">'.$languageArray['unit_price_code'][$language].' (RM)</th>
                <th class="text-center">'.$languageArray['total_price_code'][$language].' (RM)</th>
            </tr>
        </thead>
        <tbody>';

foreach ($rows as $i => $r) {
    $message .= '
            <tr>
                <td>'.($i + 1).'</td>
                <td>'.date('d/m/Y', strtotime($r['voucher_date'])).'</td>
                <td>'.htmlspecialchars($r['voucher_no'] ?? '-').'</td>
                <td>'.htmlspecialchars($r['entity_name']).'</td>
                <td>'.htmlspecialchars($r['invoice_no'] ?? '-').'</td>
                <td class="text-center">'.number_format(floatval(str_replace(',', '', $r['total_nett_weight'])), 2).'</td>
                <td class="text-center">'.number_format(floatval($r['unit_price']), 2).'</td>
                <td class="text-center">'.number_format(floatval($r['final_amount']), 2).'</td>
            </tr>';
}

$message .= '
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right font-bold border-top">Total</td>
                <td class="text-center border-top font-bold">'.number_format($grandNett, 2).'</td>
                <td class="border-top"></td>
                <td class="text-center border-top font-bold">'.number_format($grandAmount, 2).'</td>
            </tr>
        </tfoot>
    </table>
    <script>window.onload = function() { setTimeout(function() { window.print(); window.close(); }, 500); }</script>
</body>
</html>';

echo json_encode([
    'status'  => 'success',
    'message' => $message,
]);
?>
