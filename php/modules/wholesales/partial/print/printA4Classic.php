<?php
// Variables expected from print.php:
// $wholesale, $companyDetail, $companyLogoSrc, $weighingDetails, $status, $withPhoto, $db

$arrangedData = arrangeByGrade($weighingDetails);

$expandedGrades = [];
foreach($arrangedData['arranged'] as $key => $items) {
    $chunks = array_chunk($items, 10);
    foreach($chunks as $chunk) {
        $expandedGrades[] = ['key' => $key, 'items' => $chunk];
    }
}

if (isset($wholesale['reject_details']) && !empty($wholesale['reject_details']) && $wholesale['reject_details'] != '[]') {
    $rejectDetails = json_decode($wholesale['reject_details'], true);
    $rejectGross = $rejectTare = $rejectNet = $rejectPrice = $rejectUnitPrice = 0;
    $rejectPricingType = null;
    $rejectHtml = '<table class="grade-table">';
    $rejectHtml .= '<tr style="font-weight: bold; background-color: #f0f0f0;"><td colspan="4">REJECT</td></tr>';
    $rejectHtml .= '<tr><th>No</th><th>Gross Weight</th><th>Tare Weight</th><th>Net Weight</th></tr>';
    for ($i = 0; $i < 10; $i++) {
        if ($i < count($rejectDetails)) {
            $item = $rejectDetails[$i];
            $gross = floatval($item['gross'] ?? 0);
            $tare = floatval($item['tare'] ?? 0);
            $net = floatval($item['net'] ?? 0);
            $price = floatval($item['price'] ?? 0);
            $rejectUnitPrice = $price;
            $rejectPricingType = $item['fixedfloat'];
            $rejectPrice += (strtolower($rejectPricingType) == 'fixed') ? $price : $net * $price;
        } else { $gross = $tare = $net = ''; }
        $rejectGross += $gross != '' ? $gross : 0;
        $rejectTare += $tare != '' ? $tare : 0;
        $rejectNet += $net != '' ? $net : 0;
        $rejectHtml .= '<tr><td>'.($i+1).'</td><td>'.($gross != '' ? number_format($gross,2).' kg' : '').'</td><td>'.($tare != '' ? number_format($tare,2).' kg' : '').'</td><td>'.($net != '' ? number_format($net,2).' kg' : '').'</td></tr>';
    }
    $rejectHtml .= '<tr style="font-weight:bold;"><td style="border-right:none;">T</td><td style="border-left:none;border-right:none;">'.number_format($rejectGross,2).' kg</td><td style="border-left:none;border-right:none;">'.number_format($rejectTare,2).' kg</td><td style="border-left:none;">'.number_format($rejectNet,2).' kg</td></tr>';
    if ($companyDetail['include_price'] == 'Y') {
        $rejectHtml .= '<tr><td colspan="2">Unit Price</td><td colspan="2">RM '.number_format($rejectUnitPrice,2).'</td></tr>';
        $rejectHtml .= '<tr><td colspan="2">Total Price</td><td colspan="2">RM '.number_format($rejectPrice,2).(!empty($rejectPricingType) && $rejectPricingType !== 'null' ? ' ('.$rejectPricingType.')' : '').'</td></tr>';
    } else {
        $rejectHtml .= '<tr style="visibility:hidden;border:none;"><td colspan="2" style="border:none;">Unit Price</td><td colspan="2" style="border:none;">RM 0.00</td></tr>';
        $rejectHtml .= '<tr style="visibility:hidden;border:none;"><td colspan="2" style="border:none;">Total Price</td><td colspan="2" style="border:none;">RM 0.00</td></tr>';
    }
    $rejectHtml .= '</table>';
    $expandedGrades[] = ['html' => $rejectHtml];
}

$totalExpandedGrades = count($expandedGrades);
$rowsNeeded = ceil($totalExpandedGrades / 3);
$weightDetails = '';
$totalCages = 0;
$totalCagesWeight = 0;
$totalPcsBasket = 0;
$grandTotalPrice = 0;

for($row = 0; $row < $rowsNeeded; $row++) {
    $weightDetails .= ($row > 0 && $row % 2 == 0)
        ? '<div class="row mb-3 page-break">'
        : '<div class="row mb-3">';

    for($col = 0; $col < 3; $col++) {
        $gradeIndex = $row * 3 + $col;
        if($gradeIndex < $totalExpandedGrades) {
            $gradeData = $expandedGrades[$gradeIndex];
            $weightDetails .= '<div class="col-4">';
            if (isset($gradeData['html'])) {
                $weightDetails .= $gradeData['html'];
                $weightDetails .= '</div>';
                continue;
            }

            $key = $gradeData['key'];
            $items = $gradeData['items'];
            $product = searchProductNameById(explode(' - ', $key)[0], $db);
            $grade = explode(' - ', $key)[1];
            $weightDetails .= '<table class="grade-table">';
            $weightDetails .= '<tr style="font-weight: bold; background-color: #f0f0f0;"><td colspan="4">'.$product.' GRADE : ' . $grade . '</td></tr>';
            $weightDetails .= '<tr><th>No</th><th>Gross Weight</th><th>Tare Weight</th><th>Net Weight</th></tr>';

            $totalGross = $totalTare = $totalNet = $totalPrice = $unitPrice = $pcsBasket = 0 ;
            $pricingType = null;

            for($i = 0; $i < 10; $i++) {
                if($i < count($items)) {
                    $totalCages += 1;
                    $item = $items[$i];
                    $gross = floatval($item['gross'] ?? 0);
                    $tare = floatval($item['tare'] ?? 0);
                    $net = floatval($item['net'] ?? 0);
                    $pcsPerBasket = floatval($item['no_per_basket'] ?? 0);
                    $price = floatval($item['price'] ?? 0);
                    $unitPrice = floatval($item['price'] ?? 0);
                    $pricingType = $item['fixedfloat'];
                    $totalPrice += (strtolower($pricingType) == 'fixed') ? $price : $net * ($price ?? 0);
                    $totalCagesWeight += $tare;
                } else {
                    $gross = $tare = $net = $price = $pcsPerBasket ='';
                }

                $totalGross += $gross != '' ? $gross : 0;
                $totalTare += $tare != '' ? $tare : 0;
                $totalNet += $net != '' ? $net : 0;
                $pcsBasket += $pcsPerBasket != '' ? $pcsPerBasket : 0;

                $weightDetails .= '<tr>';
                $weightDetails .= '<td>' . ($i + 1) . '</td>';
                $weightDetails .= '<td>' . ($gross != '' ? number_format($gross, 2) . ' kg' : '') . '</td>';
                $weightDetails .= '<td>' . ($tare != '' ? number_format($tare, 2) . ' kg' : '') . '</td>';
                $weightDetails .= '<td>' . ($net != '' ? number_format($net, 2) . ' kg' : '') . '</td>';
                $weightDetails .= '</tr>';
            }

            $weightDetails .= '<tr style="font-weight: bold;">';
            $weightDetails .= '<td style="border-right: none;">T</td>';
            $weightDetails .= '<td style="border-left: none; border-right: none;">' . number_format($totalGross, 2) . ' kg</td>';
            $weightDetails .= '<td style="border-left: none; border-right: none;">' . number_format($totalTare, 2) . ' kg</td>';
            $weightDetails .= '<td style="border-left: none;">' . number_format($totalNet, 2) . ' kg</td>';
            $weightDetails .= '</tr>';

            if ($companyDetail['include_price'] == 'Y') {
                $weightDetails .= '<tr><td colspan="2">Unit Price</td><td colspan="2">RM ' . number_format($unitPrice, 2) . '</td></tr>';
                $weightDetails .= '<tr><td colspan="2">Total Price</td><td colspan="2">RM ' . number_format($totalPrice, 2) . (!empty($pricingType) && $pricingType !== 'null' ? ' (' . $pricingType . ')' : '') . '</td></tr>';
            } else {
                $weightDetails .= '<tr style="visibility: hidden; border: none;"><td colspan="2" style="border: none;">Unit Price</td><td colspan="2" style="border: none;">RM ' . number_format($unitPrice, 2) . '</td></tr>';
                $weightDetails .= '<tr style="visibility: hidden; border: none;"><td colspan="2" style="border: none;">Total Price</td><td colspan="2" style="border: none;">RM ' . number_format($totalPrice, 2) . (!empty($pricingType) && $pricingType !== 'null' ? ' (' . $pricingType . ')' : '') . '</td></tr>';
            }

            if ($companyDetail['include_pcs_basket'] == 'Y') {
                $weightDetails .= '<tr><td colspan="2">Total Pcs/Basket</td><td colspan="2">' . $pcsBasket . '</td></tr>';
            }

            $grandTotalPrice += $totalPrice;
            $totalPcsBasket += $pcsBasket;
            $weightDetails .= '</table>';
            $weightDetails .= '</div>';
        }
    }
    $weightDetails .= '</div>';
}

$message = '
    <html>
    <head>
        <script src="https://unpkg.com/pagedjs/dist/paged.polyfill.js"></script>
        <style>
            .container-fluid { width: 100%; padding-right: 10px; padding-left: 10px; margin-right: auto; margin-left: auto; }
            .row { display: flex; flex-wrap: wrap; margin-right: -5px; margin-left: -5px; }
            .col-4 { position: relative; width: 100%; padding-right: 5px; padding-left: 5px; flex: 0 0 33.333333%; max-width: 33.333333%; box-sizing: border-box; }
            .col-8 { position: relative; width: 100%; padding-right: 5px; padding-left: 5px; flex: 0 0 66.666667%; max-width: 66.666667%; box-sizing: border-box; }
            .mb-1 { margin-bottom: 0.25rem !important; }
            .mb-3 { margin-bottom: 1rem !important; }
            body { font-family: Arial, sans-serif; margin-left: 10px; margin-right: 30px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .company-name { font-weight: bold; font-size: 16px; }
            .address { font-size: 14px; }
            .header-row { margin-bottom: 5px; font-size: 14px; display: flex; }
            .header-label { width: 120px; flex-shrink: 0; }
            .header-value { flex: 1; }
            .info-row { margin-bottom: 5px; font-size: 14px; display: flex; }
            .info-label { width: 120px; flex-shrink: 0; }
            .info-value { flex: 1; }
            .grade-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
            .grade-table th, .grade-table td { border: 1px solid black; padding: 5px; text-align: center; font-size: 10px; }
            .grade-table th { background-color: #f0f0f0; }
            @page {
                size: A4 portrait;
                margin: 90mm 5mm 5mm 5mm;
                @top-left { content: element(running-header); }
            }
            .running-header { position: running(running-header); width: 100%; text-align: left; }
            .page-content { margin-top: 0; }
            .page-break { page-break-before: always; break-before: page; }
        </style>
        <script>document.title = ""; window.onbeforeprint = function() { document.title = ""; };</script>
    </head>
    <body>
        <div class="running-header">
            <div class="row mb-1">
                <div class="col-8" style="display:flex;align-items:flex-start;gap:10px;">
                    '.($companyLogoSrc ? '<img src="'.$companyLogoSrc.'" alt="Logo" style="width:130px;height:auto;flex-shrink:0;">' : '').'
                    <div>
                        <div class="company-name">'.$wholesale['name'].'</div>
                        <div class="address">'.$wholesale['address'].'</div>
                        <div class="address">'.$wholesale['address2'].'</div>
                        <div class="address">'.$wholesale['address3'].'</div>
                        <div class="address">'.$wholesale['address4'].'</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="header-row"><span class="header-label">Transaction ID</span><span class="header-value">: '.$wholesale['serial_no'].'</span></div>
                    <div class="header-row"><span class="header-label">Status</span><span class="header-value">: '.$status.'</span></div>
                    <div class="header-row"><span class="header-label">From Date</span><span class="header-value">: '.date('d/m/Y', strtotime($wholesale['start_time'])).'</span></div>
                    <div class="header-row"><span class="header-label">'.($wholesale['status'] == 'DISPATCH' || $wholesale['status'] == 'STOCK-BAL' ? 'Delivery' : 'Purchase').' No</span><span class="header-value">: '.$wholesale['po_no'].'</span></div>';

                    if ($wholesale['status'] == 'RECEIVING') {
                        $message .= '
                            <div class="header-row"><span class="header-label">Security Bill No</span><span class="header-value">: '.$wholesale['security_bills'].'</span></div>
                        ';
                    }

                    $message .= '
                </div>
            </div>
            <hr>
            <div class="row mb-1">
                <div class="col-8">
                    <div class="info-row"><span class="info-label">To '.($wholesale['status'] == 'DISPATCH' || $wholesale['status'] == 'STOCK-BAL' ? 'Customer' : 'Supplier').'</span><span class="info-value">: '.($wholesale['status'] == 'DISPATCH' || $wholesale['status'] == 'STOCK-BAL' ? searchCustomerNameById($wholesale['customer'], $wholesale['other_customer'], $db) : searchSupplierNameById($wholesale['supplier'], $wholesale['other_supplier'], $db)).'</span></div>
                    <div class="info-row"><span class="info-label">Driver Name</span><span class="info-value">: '.$wholesale['driver'].'</span></div>
                    <div class="info-row"><span class="info-label">Driver IC</span><span class="info-value">: '.$wholesale['driver_ic'].'</span></div>
                    <div class="info-row"><span class="info-label">Actual Weight</span><span class="info-value">: '.number_format(floatval($wholesale['total_weight']) + floatval($wholesale['total_reject']), 2).' kg</span></div>
                    <div class="info-row"><span class="info-label">Reject Weight (kg)</span><span class="info-value">: '.number_format($wholesale['total_reject'], 2).' kg</span></div>
                    <div class="info-row"><span class="info-label">Total Weight (kg)</span><span class="info-value">: '.number_format($wholesale['total_weight'], 2).' kg</span></div>
                    '.($companyDetail['include_price'] == 'Y' ? '<div class="info-row"><span class="info-label">Total Price</span><span class="info-value">: RM '.number_format($grandTotalPrice, 2).'</span></div>' : '').'
                    <div class="info-row"><span class="info-label">Remark</span><span class="info-value">: '.$wholesale['remark'].'</span></div>
                </div>
                <div class="col-4">
                    <div class="info-row"><span class="info-label">To Vehicle No</span><span class="info-value">: '.$wholesale['vehicle_no'].'</span></div>
                    <div class="info-row"><span class="info-label">Total Cages</span><span class="info-value">: '.number_format($totalCages).'</span></div>
                    '.($companyDetail['include_pcs_basket'] == 'Y' ? '<div class="info-row"><span class="info-label">Total Pcs/Basket</span><span class="info-value">: '.$totalPcsBasket.'</span></div>' : '').'
                    <div class="info-row"><span class="info-label">Cages Weight</span><span class="info-value">: '.number_format($totalCagesWeight, 2).' kg</span></div>
                    <div class="info-row"><span class="info-label">Weight By</span><span class="info-value">: '.searchUserNameById($wholesale['weighted_by'], $db).'</span></div>
                    <div class="info-row"><span class="info-label">Check By</span><span class="info-value">: '.($wholesale['checked_by'] == 'JACKY' ? '' : $wholesale['checked_by']).'</span></div>
                    <div class="info-row"><span class="info-label">Time Start</span><span class="info-value">: '.date('H:i:s', strtotime($wholesale['start_time'])).'</span></div>
                    <div class="info-row"><span class="info-label">Time End</span><span class="info-value">: '.date('H:i:s', strtotime($wholesale['end_time'])).'</span></div>
                </div>
            </div>
            <hr>
        </div>

        <div class="container-fluid">
            <div class="grade-section page-content">'.$weightDetails.'</div>
        </div>';

if ($withPhoto == 'Y') {
    $photoItems = array_filter($weighingDetails ?? [], fn($d) => !empty($d['photoPath']));
    if (!empty($photoItems)) {
        $message .= '<div class="page-break"><h3 style="font-size:14px;margin-bottom:10px;">Photos</h3><div class="row">';
        foreach ($photoItems as $item) {
            $photoSrc = 'php/viewPhoto.php?file=' . urlencode($item['photoPath']) . '&type=photo';
            $label = 'Product: ' . searchProductNameById($item['product'], $db) . ', Grade: ' . searchGradeNameById($item['grade_id'], $db);
            $message .= '
                <div class="col-4" style="margin-bottom:10px;text-align:center;">
                    <img src="'.$photoSrc.'" style="width:100%;height:auto;border:1px solid #ccc;">
                    <div style="font-size:12px;margin-top:4px;">'.htmlspecialchars($label).'</div>
                </div>';
        }
        $message .= '</div></div>';
    }
}

$message .= '
    </body>
    </html>';
