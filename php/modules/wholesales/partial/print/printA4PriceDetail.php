<?php
// Variables expected from print.php:
// $wholesale, $companyDetail, $weighingDetails, $db, $withDetails

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
        $gradeKey   = !empty($gradeId) ? $gradeId : ($detail['grade'] ?? '');
        $key        = $productId . '_' . $gradeKey;
        $net        = floatval($detail['net'] ?? 0);
        $price      = floatval($detail['price'] ?? 0);
        $fixedfloat = $detail['fixedfloat'] ?? 'Float';
        $total      = floatval($detail['total'] ?? 0);
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
                'rows'         => [],  // raw detail rows for breakdown
            ];
        }
        $groups[$key]['count']++;
        $groups[$key]['net'] += $net;
        $groups[$key]['unitPrice'][$curName]  = $price;
        if (!isset($groups[$key]['totalByCur'][$curName])) $groups[$key]['totalByCur'][$curName] = 0.0;
        $groups[$key]['totalByCur'][$curName] += $total;
        $groups[$key]['rows'][] = ['gross' => floatval($detail['gross'] ?? 0), 'tare' => floatval($detail['tare'] ?? 0), 'net' => $net, 'unit' => $detail['unit'] ?? 'kg', 'time' => $detail['time'] ?? ''];
    }
}

$includePrice = ($companyDetail['include_price'] == 'Y');
$grandTotalNet = 0.0;
$grandTotalItems = 0;
$grandTotalByCur = [];  // currency => total amount

$rows = '';
$rowNo = 1;

// TEMPORARY DUMMY DATA FOR TESTING - Remove after testing
$dummyProducts = ['Durian Musang King', 'Durian D24', 'Durian Black Thorn', 'Durian Red Prawn', 'Durian XO', 'Durian Kampung', 'Durian IOI', 'Durian Tekka', 'Durian Golden Phoenix', 'Durian Green Bamboo', 'Durian Udang Merah', 'Durian Sultan', 'Durian D101', 'Durian Monthong', 'Durian Chanee'];
$dummyGrades = ['A+', 'A', 'B+', 'B', 'C'];
$dummyItems = [];
for ($i = 0; $i < 150; $i++) {
    $price = rand(15, 70);
    $net = rand(30, 300) + (rand(0, 99) / 100);
    $dummyItems[] = [
        'product' => $dummyProducts[$i % count($dummyProducts)] . ' #' . ($i + 1),
        'grade' => $dummyGrades[$i % count($dummyGrades)],
        'count' => rand(5, 35),
        'net' => $net,
        'unit' => 'kg',
        'price' => $price,
        'total' => $net * $price
    ];
}

foreach ($dummyItems as $item) {
    $grandTotalNet += $item['net'];
    $grandTotalItems += $item['count'];
    if (!isset($grandTotalByCur['MYR'])) $grandTotalByCur['MYR'] = 0.0;
    $grandTotalByCur['MYR'] += $item['total'];

    $rows .= '<tr>';
    $rows .= '<td>'.$rowNo.'</td>';
    $rows .= '<td>'.htmlspecialchars($item['product']).'</td>';
    $rows .= '<td>'.htmlspecialchars($item['grade']).'</td>';
    $rows .= '<td>'.$item['count'].'</td>';
    $rows .= '<td>'.number_format($item['net'], 2).'</td>';
    $rows .= '<td>'.htmlspecialchars($item['unit']).'</td>';
    if ($includePrice) {
        $rows .= '<td>MYR&nbsp;'.number_format($item['price'], 2).'</td>';
        $rows .= '<td>MYR&nbsp;'.number_format($item['total'], 2).'</td>';
    }
    $rows .= '</tr>';
    $rowNo++;
}
// END TEMPORARY DUMMY DATA

/* ORIGINAL CODE - Uncomment after testing
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

    // Breakdown rows
    if (!empty($withDetails) && $withDetails == 'Y') {
        $colSpan = $includePrice ? 7 : 5;
        $chunks = array_chunk($g['rows'], 10, true);
        foreach ($chunks as $chunk) {
            $parts = [];
            foreach ($chunk as $ri => $r) {
                $parts[] = ($ri + 1).'. '.number_format($r['net'],2).' '.$r['unit'];
            }
            $rows .= '<tr style="background:#f9f9f9;font-size:10px;">';
            $rows .= '<td style="border-top:none;"></td>';
            $rows .= '<td colspan="'.$colSpan.'" style="border-top:none;padding-left:20px;text-align:left;">'.implode(' &nbsp; ', $parts).'</td>';
            $rows .= '</tr>';
        }
    }

    $rowNo++;
}
END ORIGINAL CODE */

$summaryAmountCell = '';
if ($includePrice) {
    $amountLines = implode('<br>', array_map(
        fn($cur, $amt) => $cur . '&nbsp;' . number_format($amt, 2),
        array_keys($grandTotalByCur), array_values($grandTotalByCur)
    ));
    $summaryAmountCell = '<td>'.$amountLines.'</td>';
}

$message = '
<html>
<head>
    <script src="https://unpkg.com/pagedjs/dist/paged.polyfill.js"></script>
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; }
        
        /* Running header - appears on every page */
        .running-header { position: running(runningHeader); width: 100%; border-bottom: 1px solid #000; padding-bottom: 5px; }
        
        .header-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 5px; }
        .company-name { font-size: 18px; font-weight: bold; }
        .slip-title { font-size: 20px; font-weight: bold; text-decoration: underline;}
        
        .info-block { display: flex; justify-content: space-between; }
        .info-left { }
        .info-right { text-align: right; }
        .info-row { display: flex; margin-bottom: 1px; font-size: 11px; }
        .info-left .info-label { font-weight: bold; width: 70px; display: inline-block; }
        .info-right .info-label { font-weight: bold; width: 95px; display: inline-block; text-align: left; }
        .info-value { }
        
        /* Table styles */
        table.items { width: 100%; border-collapse: collapse; }
        table.items th { 
            background: #f0f0f0; 
            border: 1px solid #000; 
            padding: 6px 4px; 
            font-weight: bold; 
            text-align: center; 
            font-size: 12px; 
        }
        table.items td { 
            border: 1px solid #000; 
            padding: 4px; 
            text-align: center; 
            font-size: 11px; 
        }
        table.items tbody tr:nth-child(even) { background: #fafafa; }
        
        /* Summary table */
        .summary-section { margin-top: 20px; display: flex; justify-content: flex-end; }
        table.summary { border-collapse: collapse; }
        table.summary th, table.summary td { border: 1px solid #000; padding: 8px 12px; text-align: center; }
        table.summary th { background: #f0f0f0; font-weight: bold; }
        table.summary td { font-weight: bold; font-size: 13px; }
        
        /* Page setup with paged.js */
        @page {
            size: A4 portrait;
            margin: 28mm 10mm 15mm 10mm;
            @top-center {
                content: element(runningHeader);
            }
            @bottom-center {
                content: "Page " counter(page) " of " counter(pages);
                font-size: 9px;
                color: #666;
            }
        }
        
        @media print {
            body { margin: 0; }
        }
    </style>
    <script>document.title = ""; window.onbeforeprint = function() { document.title = ""; };</script>
</head>
<body>
    <!-- Running Header - will repeat on every page -->
    <div class="running-header">
        <div class="header-top">
            <div class="company-name">'.htmlspecialchars($wholesale['name']).'</div>
            <div class="slip-title">'.htmlspecialchars($slipTitle).'</div>
        </div>
        <div class="info-block">
            <div class="info-right">
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
    </div>

    <!-- Main Content -->
    <table class="items">
        <thead>
            <tr>
                <th style="width:5%;">No</th>
                <th style="width:25%;">Item Desc</th>
                <th style="width:10%;">Grade</th>
                <th style="width:10%;">Total Items</th>
                <th style="width:15%;">Nett Weight</th>
                <th style="width:8%;">Unit</th>
                '.($includePrice ? '<th style="width:12%;">Unit Price</th><th style="width:15%;">Total Price</th>' : '').'
            </tr>
        </thead>
        <tbody>
            '.$rows.'
        </tbody>
    </table>

    <!-- Summary Section -->
    <div class="summary-section">
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
                    <td>'.number_format($grandTotalNet, 2).' kg</td>
                    <td>'.$grandTotalItems.'</td>
                    '.$summaryAmountCell.'
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>';
