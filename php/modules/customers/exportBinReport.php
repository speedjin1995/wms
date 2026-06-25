<?php

require_once '../../db_connect.php';
require_once '../../lookup.php';
require_once '../../../vendor/autoload.php';
use Mpdf\Mpdf;

session_start();

if (!isset($_SESSION['userID'])) {
    exit('Unauthorized');
}

$company  = $_SESSION['customer'];
$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];

// ─── Fetch Company Detail ─────────────────────────────────────────────────────

$companyDetail = searchCompanyById($company, $db);
$companyName   = $companyDetail['name'] ?? '';

// ─── Fetch Bin Types for This Company ────────────────────────────────────────

$binTypeMap = [];
$btResult = $db->query("SELECT id, bin_type FROM bin_type WHERE deleted = 0 AND customer = '$company' ORDER BY bin_type ASC");
while ($bt = $btResult->fetch_assoc()) {
    $binTypeMap[(int)$bt['id']] = $bt['bin_type'];
}

// ─── Fetch All Customers for This Company ────────────────────────────────────

$customers = [];
$custResult = $db->query("SELECT id, customer_code, customer_name, pending_bins FROM customers WHERE deleted = 0 AND customer = '$company' ORDER BY customer_name ASC");
while ($row = $custResult->fetch_assoc()) {
    $customers[] = $row;
}

// ─── Build HTML ───────────────────────────────────────────────────────────────

$generatedDate = date('d/m/Y H:i');
$totalByType   = array_fill_keys(array_keys($binTypeMap), 0);

// Build customer rows HTML and accumulate totals
$customerRowsHtml = '';
foreach ($customers as $cust) {
    $pendingMap = [];
    if (!empty($cust['pending_bins'])) {
        $decoded = json_decode($cust['pending_bins'], true);
        if (is_array($decoded)) {
            $pendingMap = $decoded;
        }
    }

    // Sum all pending bins for this customer
    $custTotal = 0;
    $typeCells = '';
    foreach ($binTypeMap as $typeId => $typeName) {
        $count = isset($pendingMap[$typeId]) ? (int)$pendingMap[$typeId] : 0;
        $custTotal += $count;
        $totalByType[$typeId] += $count;

        $cellStyle = $count > 0
            ? 'background:#fff8e1; color:#856404; font-weight:700;'
            : 'color:#aaa;';

        $typeCells .= '<td style="text-align:center; padding:8px 6px; ' . $cellStyle . '">' . $count . '</td>';
    }

    $rowBg = $custTotal > 0 ? '#ffffff' : '#fafafa';

    $customerRowsHtml .= '
    <tr style="background:' . $rowBg . '; border-bottom:1px solid #eee;">
        <td style="padding:8px 10px; font-weight:600; color:#333;">' . htmlspecialchars($cust['customer_name']) . '</td>
        <td style="padding:8px 6px; color:#666; font-size:0.85em;">' . htmlspecialchars($cust['customer_code']) . '</td>
        ' . $typeCells . '
        <td style="text-align:center; padding:8px 6px; font-weight:700; color:' . ($custTotal > 0 ? '#e65100' : '#aaa') . ';">' . $custTotal . '</td>
    </tr>';
}

// Build totals footer row
$totalCells = '';
foreach ($binTypeMap as $typeId => $typeName) {
    $t = $totalByType[$typeId];
    $totalCells .= '<td style="text-align:center; padding:8px 6px; font-weight:700; color:' . ($t > 0 ? '#e65100' : '#aaa') . ';">' . $t . '</td>';
}
$grandTotal = array_sum($totalByType);

// Build bin type header columns
$typeHeaders = '';
foreach ($binTypeMap as $typeId => $typeName) {
    $typeHeaders .= '<th style="background:#f6d365; color:#7a4400; padding:10px 6px; text-align:center; font-size:0.8em; white-space:nowrap;">' . htmlspecialchars($typeName) . '</th>';
}

$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { font-family: Arial, sans-serif; }
    body { margin: 0; padding: 0; color: #333; }

    .report-header {
        background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
        padding: 20px 24px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .report-header .company { font-size: 1.1em; color: #7a4400; font-weight: 600; margin-bottom: 2px; }
    .report-header .title   { font-size: 1.6em; font-weight: 700; color: #1a1a2e; margin-bottom: 4px; }
    .report-header .meta    { font-size: 0.78em; color: #555; }

    .summary-bar {
        display: table;
        width: 100%;
        margin-bottom: 18px;
    }
    .summary-card {
        display: table-cell;
        width: 33%;
        background: #fff;
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 12px 16px;
        text-align: center;
    }
    .summary-card .num  { font-size: 1.8em; font-weight: 700; color: #fda085; }
    .summary-card .lbl  { font-size: 0.75em; color: #888; text-transform: uppercase; letter-spacing: 1px; }

    table.main {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.82em;
    }
    table.main thead tr {
        background: #1a1a2e;
    }
    table.main thead th {
        color: #fff;
        padding: 10px 10px;
        text-align: left;
        font-size: 0.85em;
        letter-spacing: 0.5px;
    }
    table.main tbody tr:hover { background: #fffbf5; }
    table.main tfoot tr {
        background: #1a1a2e;
    }
    table.main tfoot td {
        color: #fff;
        padding: 10px 6px;
        font-weight: 700;
        font-size: 0.85em;
    }

    .footer {
        margin-top: 16px;
        font-size: 0.72em;
        color: #aaa;
        text-align: center;
        border-top: 1px solid #eee;
        padding-top: 8px;
    }
</style>
</head>
<body>

<div class="report-header">
    <div class="company">' . htmlspecialchars($companyName) . '</div>
    <div class="title">&#x1F9FA; Pending Bins Report</div>
    <div class="meta">Generated: ' . $generatedDate . ' &nbsp;&bull;&nbsp; Total Customers: ' . count($customers) . '</div>
</div>

<table style="width:100%; border-collapse:separate; border-spacing:8px; margin-bottom:18px;">
    <tr>
        <td style="background:#fff; border:1px solid #f0e0c8; border-radius:8px; padding:12px 16px; text-align:center; width:33%;">
            <div style="font-size:1.8em; font-weight:700; color:#fda085;">' . count($customers) . '</div>
            <div style="font-size:0.75em; color:#888; text-transform:uppercase; letter-spacing:1px;">Total Customers</div>
        </td>
        <td style="background:#fff; border:1px solid #f0e0c8; border-radius:8px; padding:12px 16px; text-align:center; width:33%;">
            <div style="font-size:1.8em; font-weight:700; color:#fda085;">' . count($binTypeMap) . '</div>
            <div style="font-size:0.75em; color:#888; text-transform:uppercase; letter-spacing:1px;">Bin Types</div>
        </td>
        <td style="background:#fff; border:1px solid #f0e0c8; border-radius:8px; padding:12px 16px; text-align:center; width:33%;">
            <div style="font-size:1.8em; font-weight:700; color:#e65100;">' . $grandTotal . '</div>
            <div style="font-size:0.75em; color:#888; text-transform:uppercase; letter-spacing:1px;">Total Pending Bins</div>
        </td>
    </tr>
</table>

<table class="main">
    <thead>
        <tr>
            <th style="width:35%; padding:10px 10px;">Customer Name</th>
            <th style="width:12%; padding:10px 6px;">Code</th>
            ' . $typeHeaders . '
            <th style="text-align:center; padding:10px 6px; background:#e65100;">Total</th>
        </tr>
    </thead>
    <tbody>
        ' . $customerRowsHtml . '
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" style="padding:10px 10px; text-align:right;">Grand Total</td>
            ' . $totalCells . '
            <td style="text-align:center; padding:10px 6px; color:#fda085;">' . $grandTotal . '</td>
        </tr>
    </tfoot>
</table>

<div class="footer">
    ' . htmlspecialchars($companyName) . ' &bull; Pending Bins Report &bull; ' . $generatedDate . '
</div>

</body>
</html>';

// ─── Generate PDF ─────────────────────────────────────────────────────────────
try {
    $mpdf = new Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4-L',
        'tempDir'       => __DIR__ . '/../../pdf/mpdf',
        'margin_left'   => 8,
        'margin_right'  => 8,
        'margin_top'    => 8,
        'margin_bottom' => 12,
    ]);

    $mpdf->SetFooter('Page {PAGENO} of {nb}');
    $mpdf->WriteHTML($html);
    $mpdf->Output('PendingBinsReport_' . date('Y-m-d') . '.pdf', 'D');

} catch (\Mpdf\MpdfException $e) {
    echo 'PDF Error: ' . $e->getMessage();
}
?>
