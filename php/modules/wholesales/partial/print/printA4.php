<?php
// Variables expected from print.php:
// $wholesale, $companyDetail, $companyLogoSrc, $weighingDetails, $status, $withPhoto, $db

// Fetch default currency (same as printA5)
$defaultCurrency = 'MYR';
$defCurrStmt = $db->prepare("SELECT currency FROM currency WHERE customer = ? AND is_default = 1 AND deleted = 0 LIMIT 1");
$defCurrStmt->bind_param('s', $wholesale['company']);
$defCurrStmt->execute();
if ($defCurrRow = $defCurrStmt->get_result()->fetch_assoc()) {
    $defaultCurrency = $defCurrRow['currency'];
}
$defCurrStmt->close();
$currencyNameCache = [];

// Group by product_id + grade_id (same logic as printA5)
$groups = [];
if (!empty($weighingDetails)) {
    foreach ($weighingDetails as $detail) {
        $productId  = $detail['product'] ?? '';
        $gradeId    = $detail['grade_id'] ?? '';
        $gradeKey   = !empty($gradeId) ? $gradeId : ($detail['grade'] ?? '');
        $key        = $productId . '_' . $gradeKey;
        $net        = floatval($detail['net'] ?? 0);
        $tare       = floatval($detail['tare'] ?? 0);
        $price      = floatval($detail['price'] ?? 0);
        $fixedfloat = $detail['fixedfloat'] ?? 'Float';
        $total      = (strtolower($fixedfloat) == 'fixed') ? $price : $price * $net;
        $curName    = searchCurrencyNameById($detail['currency'] ?? '', $db, $currencyNameCache);
        if (empty($curName)) $curName = $defaultCurrency;

        if (!isset($groups[$key])) {
            $groups[$key] = [
                'product_id' => $productId,
                'grade_id'   => $gradeId,
                'grade'      => $detail['grade'] ?? '',
                'count'      => 0,
                'net'        => 0.0,
                'tare'       => 0.0,
                'unitPrice'  => [],
                'totalByCur' => [],
                'nets'       => [],
            ];
        }
        $groups[$key]['count']++;
        $groups[$key]['net']  += $net;
        $groups[$key]['tare'] += $tare;
        $groups[$key]['unitPrice'][$curName] = $price;
        if (!isset($groups[$key]['totalByCur'][$curName])) $groups[$key]['totalByCur'][$curName] = 0.0;
        $groups[$key]['totalByCur'][$curName] += $total;
        $groups[$key]['nets'][] = $net;
    }
}

$includePrice    = ($companyDetail['include_price'] == 'Y');
$weightDetails   = '';
$totalCages      = 0;
$totalCagesWeight = 0;
$grandTotalByCur = [];

foreach ($groups as $g) {
    $productName = searchProductNameById($g['product_id'], $db);
    $gradeName   = searchGradeNameById($g['grade_id'], $db);
    if (empty($gradeName)) $gradeName = $g['grade'] ?? '';

    $totalCages       += $g['count'];
    $totalCagesWeight += $g['tare'];

    // Accumulate grand totals by currency
    foreach ($g['totalByCur'] as $cur => $amt) {
        if (!isset($grandTotalByCur[$cur])) $grandTotalByCur[$cur] = 0.0;
        $grandTotalByCur[$cur] += $amt;
    }

    // Build net weight rows — 10 per row, sequential numbering
    $chunks  = array_chunk($g['nets'], 10);
    $netRows = '';
    $seqNum  = 1;
    foreach ($chunks as $chunk) {
        $netRows .= '<tr>';
        foreach ($chunk as $n) {
            $netRows .= '<td>' . $seqNum++ . ').&nbsp;' . number_format($n, 2) . '&nbsp;kg</td>';
        }
        for ($p = count($chunk); $p < 10; $p++) { $netRows .= '<td></td>'; }
        $netRows .= '</tr>';
    }

    // Build multi-currency price cells
    $unitPriceStr = '';
    $totalAmtStr  = '';
    if ($includePrice) {
        foreach ($g['totalByCur'] as $cur => $amt) {
            $unitPriceStr .= ($unitPriceStr ? '<br>' : '') . $cur . '&nbsp;' . number_format($g['unitPrice'][$cur] ?? 0, 2);
            $totalAmtStr  .= ($totalAmtStr  ? '<br>' : '') . $cur . '&nbsp;' . number_format($amt, 2);
        }
    }

    $unitPriceStr2 = $includePrice ? '&nbsp;&nbsp;&nbsp;Unit Price : ' . $unitPriceStr . '&nbsp;&nbsp;&nbsp;Total Amount : ' . $totalAmtStr : '';

    $weightDetails .= '
    <table class="grade-table">
        <thead>
            <tr class="grade-header">
                <td colspan="10"><strong>Item Decs : ' . htmlspecialchars($productName) . '&nbsp;&nbsp;&nbsp;Grade : ' . htmlspecialchars($gradeName) . '&nbsp;&nbsp;&nbsp;Total Bin : ' . $g['count'] . '&nbsp;&nbsp;&nbsp;Total Weight : ' . number_format($g['net'], 2) . 'kg.' . $unitPriceStr2 . '</strong></td>
            </tr>
            <tr class="net-header"><td colspan="10">Item Weight</td></tr>
        </thead>
        <tbody>' . $netRows . '</tbody>
    </table>';
}

// Grand total price string for info section
$grandTotalPriceStr = implode(' / ', array_map(
    fn($cur, $amt) => $cur . ' ' . number_format($amt, 2),
    array_keys($grandTotalByCur), array_values($grandTotalByCur)
));

// Reject block
if (isset($wholesale['reject_details']) && !empty($wholesale['reject_details']) && $wholesale['reject_details'] != '[]') {
    $rejectDetails     = json_decode($wholesale['reject_details'], true);
    $rejectNet         = 0.0;
    $rejectByCur       = [];
    $rejectUnitByCur   = [];
    $rejectWeights     = [];
    $rejectCacheLocal  = [];

    foreach ($rejectDetails as $item) {
        $net        = floatval($item['net']   ?? 0);
        $price      = floatval($item['price'] ?? 0);
        $fixedfloat = $item['fixedfloat'] ?? 'Float';
        $total      = (strtolower($fixedfloat) == 'fixed') ? $price : $net * $price;
        $curName    = searchCurrencyNameById($item['currency'] ?? '', $db, $currencyNameCache);
        if (empty($curName)) $curName = $defaultCurrency;
        $rejectNet += $net;
        $rejectUnitByCur[$curName] = $price;
        if (!isset($rejectByCur[$curName])) $rejectByCur[$curName] = 0.0;
        $rejectByCur[$curName] += $total;
        $rejectWeights[] = $net;
    }

    $chunks  = array_chunk($rejectWeights, 10);
    $netRows = '';
    $seqNum  = 1;
    foreach ($chunks as $chunk) {
        $netRows .= '<tr>';
        foreach ($chunk as $n) {
            $netRows .= '<td>' . $seqNum++ . ').&nbsp;' . number_format($n, 2) . '&nbsp;kg</td>';
        }
        for ($p = count($chunk); $p < 10; $p++) { $netRows .= '<td></td>'; }
        $netRows .= '</tr>';
    }

    $unitPriceStr = '';
    $totalAmtStr  = '';
    if ($includePrice) {
        foreach ($rejectByCur as $cur => $amt) {
            $unitPriceStr .= ($unitPriceStr ? '<br>' : '') . $cur . '&nbsp;' . number_format($rejectUnitByCur[$cur] ?? 0, 2);
            $totalAmtStr  .= ($totalAmtStr  ? '<br>' : '') . $cur . '&nbsp;' . number_format($amt, 2);
            if (!isset($grandTotalByCur[$cur])) $grandTotalByCur[$cur] = 0.0;
            $grandTotalByCur[$cur] += $amt;
        }
    }

    $unitPriceStr2 = $includePrice ? '&nbsp;&nbsp;&nbsp;Unit Price : ' . $unitPriceStr . '&nbsp;&nbsp;&nbsp;Total Amount : ' . $totalAmtStr : '';

    $weightDetails .= '
    <table class="grade-table">
        <thead>
            <tr class="grade-header">
                <td colspan="10"><strong>Item Decs : REJECT&nbsp;&nbsp;&nbsp;Grade : -&nbsp;&nbsp;&nbsp;Total Bin : ' . count($rejectWeights) . '&nbsp;&nbsp;&nbsp;Total Weight : ' . number_format($rejectNet, 2) . 'kg.' . $unitPriceStr2 . '</strong></td>
            </tr>
            <tr class="net-header"><td colspan="10">Item Weight</td></tr>
        </thead>
        <tbody>' . $netRows . '</tbody>
    </table>';
}

$isDispatch   = ($wholesale['status'] == 'DISPATCH' || $wholesale['status'] == 'STOCK-BAL');
$partyLabel   = $isDispatch ? 'To Customer' : 'To Supplier';
$partyName    = $isDispatch
    ? searchCustomerNameById($wholesale['customer'], $wholesale['other_customer'], $db)
    : searchSupplierNameById($wholesale['supplier'], $wholesale['other_supplier'], $db);
$poLabel      = $isDispatch ? 'Delivery No' : 'Purchase No';
$weightedBy   = searchUserNameById($wholesale['weighted_by'], $db);
$checkedBy    = ($wholesale['checked_by'] == 'JACKY') ? '' : $wholesale['checked_by'];
$actualWeight = number_format(floatval($wholesale['total_weight']) + floatval($wholesale['total_reject']), 2);

$message = '
<html>
<head>
    <script src="https://unpkg.com/pagedjs/dist/paged.polyfill.js"></script>
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .running-header { position: running(running-header); width: 100%; }
        .header-top { display: flex; width: 100%; margin-bottom: 4px; align-items: center; border-bottom: 1px solid black; }
        .header-logo { width: 150px; min-height: 80px; display: flex; align-items: center; justify-content: flex-start; padding: 0 8px 0 0; flex-shrink: 0; }
        .header-company { flex: 0 0 320px; display: flex; align-items: center; padding: 0 10px 0 0; font-size: 12px; text-align: left; }
        .header-status { padding: 6px 10px; flex: 1; }
        .status-title { font-size: 22px; font-weight: bold; text-align: center; margin-bottom: 4px; }
        .hrow { display: flex; font-size: 11px; margin-bottom: 2px; }
        .hlabel { width: 90px; flex-shrink: 0; }
        .hvalue { flex: 1; }
        .info-section { display: flex; width: 100%; margin-bottom: 3px; border-bottom: 1px solid #000; padding-bottom: 3px; }
        .info-col { flex: 1; padding-right: 6px; }
        .info-col:nth-child(1) { flex: 1.5; }
        .irow { display: flex; margin-bottom: 1px; font-size: 11px; }
        .ilabel { width: 90px; flex-shrink: 0; font-weight: bold; }
        .ivalue { flex: 1; }
        table.grade-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; font-size: 10px; }
        table.grade-table td { border: 1px solid #000; padding: 3px 5px; }
        tr.grade-header { background: #e8e8e8; font-size: 10px; }
        tr.grade-header td { border: 1px solid #000; padding: 4px 6px; }
        tr.net-header td { border: 1px solid #000; padding: 2px 6px; font-weight: bold; text-align: center; background: #f5f5f5; font-size: 10px; }
        table.grade-table tbody td { text-align: center; font-size: 10px; width: 10%; }
        @page {
            size: A4 portrait;
            margin: 58mm 10mm 18mm 10mm;
            @top-left { content: element(running-header); }
            @bottom-center { content: "Powered by SYNCTRONIX"; font-size: 9px; color: #555; letter-spacing: 1px; border-top: 1px solid #ccc; padding-top: 4px; }
        }
        .page-break { page-break-before: always; break-before: page; }
        @media print { @page { size: A4 portrait; margin: 58mm 8mm 18mm 8mm; } body { margin: 0; } }
    </style>
    <script>document.title = ""; window.onbeforeprint = function() { document.title = ""; };</script>
</head>
<body>
    <div class="running-header">
        <div class="header-top">
            <div class="header-logo">
                ' . ($companyLogoSrc ? '<img src="' . $companyLogoSrc . '" alt="Logo" style="width:100%;height:100%;object-fit:cover;">' : '<span style="font-size:10px;color:#aaa;">LOGO</span>') . '
            </div>
            <div class="header-company">
                <div>
                    <div style="font-weight:bold;font-size:13px;">' . htmlspecialchars($wholesale['name']) . '</div>
                    <div>' . htmlspecialchars($wholesale['address']) . '</div>
                    <div>' . htmlspecialchars($wholesale['address2']) . '</div>
                    <div>' . htmlspecialchars($wholesale['address3']) . '</div>
                    <div>' . htmlspecialchars($wholesale['address4']) . '</div>
                    ' . (!empty($wholesale['phone']) ? '<div>Tel: ' . htmlspecialchars($wholesale['phone']) . '</div>' : '') . '
                    ' . (!empty($wholesale['email']) ? '<div>Email: ' . htmlspecialchars($wholesale['email']) . '</div>' : '') . '
                </div>
            </div>
            <div class="header-status">
                <div class="status-title">' . htmlspecialchars($status) . '</div>
                <div class="hrow"><span class="hlabel">Transaction ID</span><span class="hvalue">: ' . htmlspecialchars($wholesale['serial_no']) . '</span></div>
                <div class="hrow"><span class="hlabel">Status</span><span class="hvalue">: ' . htmlspecialchars($wholesale['status']) . '</span></div>
                <div class="hrow"><span class="hlabel">From Date</span><span class="hvalue">: ' . date('d/m/Y', strtotime($wholesale['start_time'])) . '</span></div>
                <div class="hrow"><span class="hlabel">' . htmlspecialchars($poLabel) . '</span><span class="hvalue">: ' . htmlspecialchars($wholesale['po_no']) . '</span></div>
                ' . ($wholesale['status'] == 'RECEIVING' ? '<div class="hrow"><span class="hlabel">Security Bill No</span><span class="hvalue">: ' . htmlspecialchars($wholesale['security_bills']) . '</span></div>' : '') . '
            </div>
        </div>
        <div class="info-section">
            <div class="info-col">
                <div class="irow"><span class="ilabel">' . $partyLabel . '</span><span class="ivalue">: ' . htmlspecialchars($partyName) . '</span></div>
                <div class="irow"><span class="ilabel">Driver Name</span><span class="ivalue">: ' . htmlspecialchars($wholesale['driver']) . '</span></div>
                <div class="irow"><span class="ilabel">Driver IC</span><span class="ivalue">: ' . htmlspecialchars($wholesale['driver_ic']) . '</span></div>
                <div class="irow"><span class="ilabel">Vehicle No</span><span class="ivalue">: ' . htmlspecialchars($wholesale['vehicle_no']) . '</span></div>
                <div class="irow"><span class="ilabel">Cages Weight</span><span class="ivalue">: ' . number_format($totalCagesWeight, 2) . ' kg</span></div>
            </div>
            <div class="info-col">
                ' . ($includePrice ? '<div class="irow"><span class="ilabel">Total Price</span><span class="ivalue">: ' . $grandTotalPriceStr . '</span></div>' : '') . '
                <div class="irow"><span class="ilabel">Actual Weight</span><span class="ivalue">: ' . $actualWeight . ' kg</span></div>
                <div class="irow"><span class="ilabel">Total Cages</span><span class="ivalue">: ' . number_format($totalCages) . '</span></div>
                <div class="irow"><span class="ilabel">Weight By</span><span class="ivalue">: ' . htmlspecialchars($weightedBy) . '</span></div>
                <div class="irow"><span class="ilabel">Total Weight (kg)</span><span class="ivalue">: ' . number_format($wholesale['total_weight'], 2) . ' kg</span></div>
            </div>
            <div class="info-col">
                <div class="irow"><span class="ilabel">Time Start</span><span class="ivalue">: ' . date('h:i:s A', strtotime($wholesale['start_time'])) . '</span></div>
                <div class="irow"><span class="ilabel">Time End</span><span class="ivalue">: ' . date('h:i:s A', strtotime($wholesale['end_time'])) . '</span></div>
                <div class="irow"><span class="ilabel">Check By</span><span class="ivalue">: ' . htmlspecialchars($checkedBy) . '</span></div>
                <div class="irow"><span class="ilabel">Remark</span><span class="ivalue">: ' . htmlspecialchars($wholesale['remark']) . '</span></div>
            </div>
        </div>
    </div>

    ' . $weightDetails . '

    ';

if ($withPhoto == 'Y') {
    $photoItems = array_filter($weighingDetails ?? [], fn($d) => !empty($d['photoPath']));
    if (!empty($photoItems)) {
        $message .= '<div class="page-break"><h3 style="font-size:14px;margin-bottom:10px;">Photos</h3><div style="display:flex;flex-wrap:wrap;">';
        foreach ($photoItems as $item) {
            $photoSrc = 'php/viewPhoto.php?file=' . urlencode($item['photoPath']) . '&type=photo';
            $label    = 'Product: ' . searchProductNameById($item['product'], $db) . ', Grade: ' . searchGradeNameById($item['grade_id'], $db);
            $message .= '
                <div style="width:33%;margin-bottom:10px;text-align:center;padding:4px;">
                    <img src="' . $photoSrc . '" style="width:100%;height:auto;border:1px solid #ccc;">
                    <div style="font-size:12px;margin-top:4px;">' . htmlspecialchars($label) . '</div>
                </div>';
        }
        $message .= '</div></div>';
    }
}

$message .= '
</body>
</html>';
