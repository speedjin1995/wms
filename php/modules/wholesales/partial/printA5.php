<?php
// Variables expected from print.php:
// $wholesale, $companyDetail, $weighingDetails, $db

$slipTitle = ucwords(strtolower($status));

$isDispatchOrStockBal = ($wholesale['status'] == 'DISPATCH' || $wholesale['status'] == 'STOCK-BAL');
$partyName = $isDispatchOrStockBal
    ? searchCustomerNameById($wholesale['customer'], $wholesale['other_customer'], $db)
    : searchSupplierNameById($wholesale['supplier'], $wholesale['other_supplier'], $db);
$locationName = !empty($wholesale['location']) ? searchLocationById($wholesale['location'], $db) : '';
$doLabel = $isDispatchOrStockBal ? 'Delivery No.' : 'Purchase No.';

// Fetch default currency
$defaultCurrency = 'MYR';
$defCurrStmt = $db->prepare("SELECT currency FROM currency WHERE customer = ? AND is_default = 1 AND deleted = 0 LIMIT 1");
$defCurrStmt->bind_param('s', $wholesale['company']);
$defCurrStmt->execute();
if ($defCurrRow = $defCurrStmt->get_result()->fetch_assoc()) {
    $defaultCurrency = $defCurrRow['currency'];
}
$defCurrStmt->close();
$currencyNameCache = [];

// Group weight_details by product+grade_id
$groups = [];
if (!empty($weighingDetails)) {
    foreach ($weighingDetails as $detail) {
        $productId  = $detail['product'] ?? '';
        $gradeId    = $detail['grade_id'] ?? '';
        $key        = $productId . '_' . $gradeId;
        $net        = floatval($detail['net'] ?? 0);
        $price      = floatval($detail['price'] ?? 0);
        $fixedfloat = $detail['fixedfloat'] ?? 'Float';
        $total      = (strtolower($fixedfloat) == 'fixed') ? $price : $price * $net;
        $curName    = searchCurrencyNameById($detail['currency'] ?? '', $db, $currencyNameCache);
        if (empty($curName)) $curName = $defaultCurrency;

        if (!isset($groups[$key])) {
            $groups[$key] = [
                'product_id'   => $productId,
                'grade_id'     => $gradeId,
                'grade'        => $detail['grade'] ?? '',
                'count'        => 0,
                'net'          => 0.0,
                'unit'         => $detail['unit'] ?? 'kg',
                'unitPrice'    => [],  // currency => last unit price
                'totalByCur'   => [],  // currency => summed total
            ];
        }
        $groups[$key]['count']++;
        $groups[$key]['net'] += $net;
        $groups[$key]['unitPrice'][$curName]  = $price;
        if (!isset($groups[$key]['totalByCur'][$curName])) $groups[$key]['totalByCur'][$curName] = 0.0;
        $groups[$key]['totalByCur'][$curName] += $total;
    }
}

$includePrice = ($companyDetail['include_price'] == 'Y');
$grandTotalNet = 0.0;
$grandTotalItems = 0;
$grandTotalByCur = [];  // currency => total amount

$rows = '';
$rowNo = 1;
foreach ($groups as $g) {
    $productName = searchProductNameById($g['product_id'], $db);
    $gradeName = searchGradeNameById($g['grade_id'], $db);
    if (empty($gradeName)) $gradeName = $g['grade'] ?? '';
    $net = $g['net'];
    $count = $g['count'];

    $grandTotalNet += $net;
    $grandTotalItems += $count;

    // Build multi-currency price/total cell strings
    $unitPriceStr = '';
    $totalPriceStr = '';
    if ($includePrice) {
        foreach ($g['totalByCur'] as $cur => $amt) {
            $unitPriceStr  .= ($unitPriceStr  ? '<br>' : '') . $cur . '&nbsp;' . number_format($g['unitPrice'][$cur] ?? 0, 2);
            $totalPriceStr .= ($totalPriceStr ? '<br>' : '') . $cur . '&nbsp;' . number_format($amt, 2);
            if (!isset($grandTotalByCur[$cur])) $grandTotalByCur[$cur] = 0.0;
            $grandTotalByCur[$cur] += $amt;
        }
    }

    $rows .= '<tr>';
    $rows .= '<td>'.$rowNo.'</td>';
    $rows .= '<td>'.htmlspecialchars($productName).'</td>';
    $rows .= '<td>'.htmlspecialchars($gradeName).'</td>';
    $rows .= '<td>'.$count.'</td>';
    $rows .= '<td>'.number_format($net, 2).'</td>';
    $rows .= '<td>'.htmlspecialchars($g['unit']).'</td>';
    if ($includePrice) {
        $rows .= '<td>'.$unitPriceStr.'</td>';
        $rows .= '<td>'.$totalPriceStr.'</td>';
    }
    $rows .= '</tr>';
    $rowNo++;
}

$summaryAmountCell = '';
if ($includePrice) {
    $amountLines = implode('<br>', array_map(
        fn($cur, $amt) => $cur . '&nbsp;' . number_format($amt, 2),
        array_keys($grandTotalByCur), array_values($grandTotalByCur)
    ));
    $summaryAmountCell = '<td style="border:1px solid #000;padding:6px 8px;font-weight:bold;">'.$amountLines.'</td>';
}

$qrUrl = 'https://synctronix-wms.com/print.php?id=' . urlencode($id) . '&withPhoto=N&paperSize=A4';
$qrSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=' . urlencode($qrUrl);

$message = '
<html>
<head>
<style>
    @page { size: A5 landscape; margin: 8mm; }
    body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; }
    .slip-border { border: 2px solid #000; padding: 8px; box-sizing: border-box; width: 100%; min-height: calc(142mm); position: relative; }
    .header-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; }
    .company-name { font-size: 16px; font-weight: bold; }
    .slip-title { font-size: 18px; font-weight: bold; text-decoration: underline; text-align: right; margin-right: 50px; }
    .info-block { display: flex; justify-content: space-between; margin-bottom: 8px; border-bottom: 1px solid #000; padding-bottom: 6px; }
    .info-left { flex: 1; }
    .info-right { text-align: left; }
    .info-row { display: flex; margin-bottom: 3px; }
    .info-label { font-weight: bold; width: 90px; flex-shrink: 0; }
    .info-value { flex: 1; }
    table.items { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.items th { border-bottom: 2px solid #000; padding: 4px 6px; text-decoration: underline; font-weight: bold; text-align: center; }
    table.items td { padding: 3px 6px; text-align: center; }
    .footer-row { position: absolute; bottom: 8px; right: 8px; }
    .qr-block { position: absolute; bottom: 8px; left: 8px; }
    table.summary { border-collapse: collapse; }
    table.summary th, table.summary td { border: 1px solid #000; padding: 6px 8px; text-align: center; }
    table.summary th { font-weight: bold; }
    @media print {
        @page { size: A5 landscape; margin: 2mm; }
    }
</style>
</head>
<body>
<div class="slip-border">
    <div class="header-top">
        <div class="company-name">'.htmlspecialchars($wholesale['name']).'</div>
        <div class="slip-title">'.htmlspecialchars($slipTitle).'</div>
    </div>
    <div class="info-block">
        <div class="info-left">
            <div class="info-row"><span class="info-label">'.($isDispatchOrStockBal ? 'Customer' : 'Supplier').'</span><span class="info-value">: '.htmlspecialchars($partyName).'</span></div>
            <div class="info-row"><span class="info-label">Vehicle No.</span><span class="info-value">: '.htmlspecialchars($wholesale['vehicle_no']).'</span></div>
            <div class="info-row"><span class="info-label">Location</span><span class="info-value">: '.htmlspecialchars($locationName).'</span></div>
        </div>
        <div class="info-right">
            <div class="info-row"><span class="info-label">Weight Slip No.</span><span class="info-value">: '.htmlspecialchars($wholesale['serial_no']).'</span></div>
            <div class="info-row"><span class="info-label">'.htmlspecialchars($doLabel).'</span><span class="info-value">: '.htmlspecialchars($wholesale['po_no']).'</span></div>
            <div class="info-row"><span class="info-label">Date</span><span class="info-value">: '.date('d/m/Y', strtotime($wholesale['start_time'])).'</span></div>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="text-align:center;">No</th>
                <th style="text-align:center;">Item Desc</th>
                <th style="text-align:center;">Grade</th>
                <th style="text-align:center;">Total Items</th>
                <th style="text-align:center;">Nett Weight</th>
                <th style="text-align:center;">Unit</th>
                '.($includePrice ? '<th style="text-align:center;">Unit Price</th><th style="text-align:center;">Total Price</th>' : '').'
            </tr>
        </thead>
        <tbody>
            '.$rows.'
        </tbody>
    </table>

    <div class="footer-row">
        <table class="summary">
            <thead>
                <tr>
                    <th>Total Weight</th>
                    <th>Total Items</th>
                    '.($includePrice ? '<th>Total Amount</th>' : '').'
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight:bold;">'.number_format($grandTotalNet, 2).' kg</td>
                    <td style="font-weight:bold;">'.$grandTotalItems.'</td>
                    '.$summaryAmountCell.'
                </tr>
            </tbody>
        </table>
    </div>
    <div class="qr-block">
        <img src="'.$qrSrc.'" width="70" height="70">
    </div>
</div>
</body>
</html>';
