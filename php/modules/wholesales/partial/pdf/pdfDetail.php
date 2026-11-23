<?php
// Detail Report — weighing lines grouped by record (same columns as the per-row Excel export)
// Variables available: $db, $mpdf, $query, $companyDetail, $userAllowPrice, $transactionStatus, $fromDate, $toDate

$role           = $_SESSION['role'] ?? 'NORMAL';
$featureFlags   = $_SESSION['featureFlags'] ?? [];
$dAllowPrice    = $role === 'SADMIN' ? 'Y' : ($featureFlags['include_price'] ?? ($companyDetail['include_price'] ?? 'N'));
$dAllowPcs      = $role === 'SADMIN' ? 'Y' : ($featureFlags['include_pcs_basket'] ?? ($companyDetail['include_pcs_basket'] ?? 'N'));
$showPrice      = $dAllowPrice === 'Y' && $userAllowPrice === 'Y';
$languageArray  = $_SESSION['languageArray'] ?? [];
$language       = $_SESSION['language'] ?? '';
$label = static function ($key, $fallback) use ($languageArray, $language) {
    return $languageArray[$key][$language] ?? $fallback;
};

$headers = [$label('product_code', 'Product'), $label('grade_code', 'Grade'), $label('gross_code', 'Gross'), $label('tare_code', 'Tare'), $label('net_code', 'Net')];
if ($dAllowPcs === 'Y') {
    $headers[] = $label('pcs_basket_code', 'Pcs/Basket');
}
if ($showPrice) {
    array_push($headers, $label('currency_code', 'Currency'), $label('price_code', 'Price'), $label('before_disc_code', 'Before Disc'), $label('discount_code', 'Discount'), $label('total_code', 'Total'));
}
$headers[] = $label('time_code', 'Time');
$colCount = count($headers);

$headerRow = '<tr>';
foreach ($headers as $h) {
    $headerRow .= '<th>' . htmlspecialchars($h) . '</th>';
}
$headerRow .= '</tr>';

// Builds a bold totals row; cell order follows $headers
$totalsRow = static function ($text, $t, $bg) use ($dAllowPcs, $showPrice) {
    $r = '<tr style="font-weight:bold; background-color:' . $bg . ';">';
    $r .= '<td colspan="2">' . htmlspecialchars($text) . '</td>';
    $r .= '<td>' . number_format($t['gross'], 2) . '</td><td>' . number_format($t['tare'], 2) . '</td><td>' . number_format($t['net'], 2) . '</td>';
    if ($dAllowPcs === 'Y') {
        $r .= '<td>' . $t['basket'] . '</td>';
    }
    if ($showPrice) {
        $r .= '<td></td><td></td><td>' . number_format($t['before_discount'], 2) . '</td><td>' . number_format($t['discount'], 2) . '</td><td>' . number_format($t['total'], 2) . '</td>';
    }
    return $r . '<td></td></tr>';
};

$emptyTotals = ['gross' => 0.0, 'tare' => 0.0, 'net' => 0.0, 'basket' => 0, 'before_discount' => 0.0, 'discount' => 0.0, 'total' => 0.0];
$grandTotals = $emptyTotals;
$content = '';

while ($row = $query->fetch_assoc()) {
    $isDispatch = ($row['status'] == 'DISPATCH' || $row['status'] == 'STOCK-BAL' || $row['status'] == 'OUTGOING');
    $partyName  = $isDispatch
        ? searchCustomerNameById($row['customer'], $row['other_customer'], $db)
        : searchSupplierNameById($row['supplier'], $row['other_supplier'], $db);
    $startTime  = !empty($row['start_time']) ? (new DateTime($row['start_time']))->format('d/m/Y H:i') : '';

    $content .= '<tr><td colspan="' . $colCount . '" style="text-align:left; font-weight:bold; background-color:#d6eaf8;">'
        . htmlspecialchars($label('serial_no_code', 'Serial No.') . ': ' . $row['serial_no'] . '   |   Date: ' . $startTime . '   |   ' . ($isDispatch ? 'Customer' : 'Supplier') . ': ' . $partyName . '   |   Vehicle: ' . $row['vehicle_no'] . '   |   Status: ' . $row['status'] . ($row['status'] == 'PROCESSING' ? '   |   Processing Type: ' . searchProcessingTypeById($row['processing_type'], $db) : ''))
        . '</td></tr>';
    $content .= $headerRow;

    $weightDetails = json_decode($row['weight_details'] ?? '[]', true);
    if (!is_array($weightDetails)) {
        $weightDetails = [];
    }

    $totals = $emptyTotals;
    foreach ($weightDetails as $detail) {
        $content .= '<tr>';
        $content .= '<td>' . htmlspecialchars(searchProductNameById($detail['product'] ?? '', $db)) . '</td>';
        $content .= '<td>' . htmlspecialchars($detail['grade'] ?? '') . '</td>';
        $content .= '<td>' . number_format((float)($detail['gross'] ?? 0), 2) . '</td>';
        $content .= '<td>' . number_format((float)($detail['tare'] ?? 0), 2) . '</td>';
        $content .= '<td>' . number_format((float)($detail['net'] ?? 0), 2) . '</td>';
        $totals['gross'] += (float)($detail['gross'] ?? 0);
        $totals['tare']  += (float)($detail['tare'] ?? 0);
        $totals['net']   += (float)($detail['net'] ?? 0);

        if ($dAllowPcs === 'Y') {
            $basketCount = (int)($detail['no_per_basket'] ?? 0);
            $content .= '<td>' . $basketCount . '</td>';
            $totals['basket'] += $basketCount;
        }
        if ($showPrice) {
            $beforeDiscount = (float)($detail['before_discount'] ?? 0);
            $discount = (float)($detail['discount'] ?? 0);
            $isPercent = ($detail['discount_type'] ?? '') === 'percent';
            $content .= '<td>' . htmlspecialchars(searchCurrencyNameById($detail['currency'] ?? '', $db)) . '</td>';
            $content .= '<td>' . number_format((float)($detail['price'] ?? 0), 2) . '</td>';
            $content .= '<td>' . number_format($beforeDiscount, 2) . '</td>';
            $content .= '<td>' . number_format($discount, 2) . ($isPercent ? '%' : '') . '</td>';
            $content .= '<td>' . number_format((float)($detail['total'] ?? 0), 2) . '</td>';
            $totals['before_discount'] += $beforeDiscount;
            $totals['discount'] += $isPercent ? $beforeDiscount * $discount / 100 : $discount;
            $totals['total'] += (float)($detail['total'] ?? 0);
        }
        $content .= '<td>' . htmlspecialchars($detail['time'] ?? '') . '</td>';
        $content .= '</tr>';
    }

    $content .= $totalsRow($label('subtotal_code', 'Subtotal'), $totals, '#f0f0f0');
    $content .= '<tr><td colspan="' . $colCount . '" style="border:none; height:8px;"></td></tr>';
    foreach ($grandTotals as $k => $v) {
        $grandTotals[$k] += $totals[$k];
    }
}

if ($content === '') {
    $content = '<tr><td colspan="' . $colCount . '">No records found...</td></tr>';
} else {
    $content .= $totalsRow($label('grand_total_code', 'Grand Total'), $grandTotals, '#e8f4e8');
}

$statusLabel = $transactionStatus == 'STOCK-BAL' ? 'STOCK BALANCE' : $transactionStatus;

$html = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; font-size: 10px; margin: 0; padding: 0; }
            .fw-bold { font-weight: bold; }
            .text-muted { color: #6c757d; }
            .company-info { font-size: 16px; margin-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; font-size: 9px; }
            th, td { border: 1px solid black; padding: 2px; text-align: center; }
            th { background-color: #f0f0f0; font-weight: bold; }
            hr { margin: 1rem 0; border: 0; height: 1px; background-color: #343a40; opacity: 0.25; }
        </style>
    </head>
    <body>
        <div class="company-info">
            <div class="fw-bold">' . $companyDetail['name'] . '</div>
            <div class="text-muted">
                <div>' . $companyDetail['address'] . '</div>
                <div>' . $companyDetail['address2'] . '</div>
                <div>' . $companyDetail['address3'] . '</div>
                <div>' . $companyDetail['address4'] . '</div>
            </div>
        </div>
        <hr>
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 50%; border: none; text-align: left; padding: 0; font-size: 14px;"><div class="fw-bold">' . htmlspecialchars($statusLabel) . ' DETAIL REPORT WEIGHING</div></td>
                <td style="width: 50%; border: none; text-align: right; padding: 0; font-size: 14px;"><div class="fw-bold">From Date: ' . ($fromDate ?? '') . ' - ' . ($toDate ?? '') . '</div></td>
            </tr>
        </table>
        <hr>
        <table>' . $content . '</table>
    </body>
    </html>';

$mpdf->WriteHTML($html);
