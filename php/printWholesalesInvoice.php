<?php
require_once 'db_connect.php';
require_once 'lookup.php';

if(isset($_GET['id'])){
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);

    if ($select_stmt = $db->prepare("SELECT * FROM wholesales LEFT JOIN companies ON wholesales.company = companies.id WHERE wholesales.id = ?")) {
        $select_stmt->bind_param('s', $id);

        if (! $select_stmt->execute()) {
            echo json_encode(
                array(
                    "status" => "failed",
                    "message" => "Something went wrong went execute"
                )); 
        }
        else{
            $result = $select_stmt->get_result();

            if ($wholesale = $result->fetch_assoc()) {
                // Company info from companies table (joined)
                $companyNameCn = $wholesale['chinese_name'] ?? '';
                $companyName = $wholesale['name'] ?? '';
                $companyAddress1 = $wholesale['address'] ?? '';
                $companyAddress2 = $wholesale['address2'] ?? '';
                $companyAddress3 = $wholesale['address3'] ?? '';
                $companyAddress4 = $wholesale['address4'] ?? '';
                $companyTel = $wholesale['phone'] ?? '';
                $companyTin = $wholesale['reg_no'] ?? '';
                $companyEmail = $wholesale['email'] ?? '';
                $companyPhone = $wholesale['phone'] ?? '';

                // SO details from wholesales table
                $soNo = $wholesale['po_no'];
                $date = date('d/m/Y', strtotime($wholesale['created_datetime']));
                $time = date('H:i:s', strtotime($wholesale['created_datetime']));
                $slipNo = $wholesale['serial_no'];
                $vehicleNo = $wholesale['vehicle_no'] ?? '';
                $priceStatus = (floatval($wholesale['total_price']) > 0) ? 'FIXED' : 'FLOAT';
                $weightBy = searchUserNameById($wholesale['weighted_by'], $db);

                // Customer info for Bill To
                $customerData = [];
                if (!empty($wholesale['customer'])) {
                    if ($cust_stmt = $db->prepare("SELECT * FROM customers WHERE id = ?")) {
                        $cust_stmt->bind_param('s', $wholesale['customer']);
                        $cust_stmt->execute();
                        $cust_result = $cust_stmt->get_result();
                        $customerData = $cust_result->fetch_assoc() ?: [];
                        $cust_stmt->close();
                    }
                }

                $billToName = $customerData['customer_name'] ?? '';
                $billToAddr1 = $customerData['address'] ?? '';
                $billToAddr2 = $customerData['address2'] ?? '';
                $billToAddr3 = $customerData['address3'] ?? '';
                $billToAttn = $customerData['contact_person'] ?? '';
                $billToTel = $customerData['phone'] ?? '';
                $billToFax = $customerData['fax'] ?? '';

                // Delivery To (same as Bill To)
                $deliverToName = $billToName;
                $deliverToAddr1 = $billToAddr1;
                $deliverToAddr2 = $billToAddr2;
                $deliverToAddr3 = $billToAddr3;
                $deliverToAttn = $billToAttn;
                $deliverToTel = $billToTel;
                $deliverToFax = $billToFax;

                // Footer data
                $totalAmount = number_format(floatval($wholesale['total_price']), 2);
                $totalAmountWords = '';
                $bankerName = '';
                $bankAccount = '';
                $bankSwift = '';

                // Summary data
                $startWeightTime = date('g:i:s A', strtotime($wholesale['created_datetime']));
                $endWeightTime = !empty($wholesale['end_time']) ? date('g:i:s A', strtotime($wholesale['end_time'])) : '';

                // Build items from weight_details JSON - group by product + grade
                $weightDetails = json_decode($wholesale['weight_details'], true) ?: [];
                $grouped = [];
                foreach ($weightDetails as $detail) {
                    $key = ($detail['product_name'] ?? '') . '|' . ($detail['grade'] ?? '');
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'product' => $detail['product_name'] ?? '',
                            'grade' => $detail['grade'] ?? '',
                            'unit' => strtoupper($detail['unit'] ?? 'KG'),
                            'weights' => [],
                            'qty' => 0,
                            'uom' => strtoupper($detail['unit'] ?? 'KG'),
                            'unit_price' => floatval($detail['price'] ?? 0),
                            'total_price' => 0,
                        ];
                    }
                    $net = floatval($detail['net'] ?? 0);
                    $grouped[$key]['weights'][] = $net;
                    $grouped[$key]['qty'] += $net;
                    $grouped[$key]['total_price'] += floatval($detail['total'] ?? 0);
                }
                $items = [];
                foreach ($grouped as $item) {
                    $item['bin'] = count($item['weights']);
                    $items[] = $item;
                }

                // Build item rows HTML
                $itemRowsHtml = '';
                foreach ($items as $index => $item) {
                    $weightLines = '';
                    $chunks = array_chunk($item['weights'], 5);
                    foreach ($chunks as $chunk) {
                        $weightLines .= implode('&nbsp;&nbsp;', array_map(function($w) { return number_format($w, 2); }, $chunk)) . '<br>';
                    }
                    $no = $index + 1;
                    $qty = number_format($item['qty'], 2);
                    $unitPrice = number_format($item['unit_price'], 2);
                    $totalPrice = number_format($item['total_price'], 2);
                    $itemRowsHtml .= '
                                    <tr style="break-inside:avoid; page-break-inside:avoid;">
                                        <td style="width:30px; text-align:center; border:0; vertical-align:top; padding-top:8px;">' . $no . '</td>
                                        <td style="width:268px; text-align:left; border:0; vertical-align:top; padding-top:8px;">
                                            <span style="display:inline-block; width:220px; font-weight:bold;">' . $item['product'] . '</span><span style="display:inline-block; width:80px;">UNIT : ' . $item['unit'] . '</span><br>
                                            <span style="display:inline-block; width:220px;">GRADE : ' . $item['grade'] . '</span><span style="display:inline-block; width:80px;">BIN : ' . $item['bin'] . '</span><br>
                                            <span style="font-size:10px;">' . $weightLines . '</span>
                                        </td>
                                        <td style="width:72px; text-align:center; border:0; vertical-align:top; padding-top:8px;">' . $qty . '</td>
                                        <td style="width:58px; text-align:center; border:0; vertical-align:top; padding-top:8px;">' . $item['uom'] . '</td>
                                        <td style="width:145px; text-align:center; border:0; vertical-align:top; padding-top:8px;">RM&nbsp;&nbsp;' . $unitPrice . '</td>
                                        <td style="width:145px; text-align:center; border:0; vertical-align:top; padding-top:8px;">RM' . $totalPrice . '</td>
                                    </tr>';
                }

                // Summary calculations
                $totalBinCount = array_sum(array_column($items, 'bin'));
                $totalActualWeight = number_format(array_sum(array_column($items, 'qty')), 2);
                $totalTareWeight = 0;
                foreach ($items as $it) { $totalTareWeight += count($it['weights']) * 0.60; }
                $totalTareWeight = number_format($totalTareWeight, 2);

                $message = '
                    <html>
                    <head>
                        <meta charset="UTF-8">
                        <title>Sales Order - ' . $soNo . '</title>
                        <script src="https://unpkg.com/pagedjs/dist/paged.polyfill.js"><\/script>
                        <style>
                            * { margin: 0; padding: 0; box-sizing: border-box; }
                            body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #000; }

                            /* Paged.js */
                            @page {
                                size: A4;
                                margin: 95mm 10mm 78mm 10mm;
                                @top-left { content: element(running-header); }
                                @bottom-left { content: element(running-footer); }
                            }
                            .running-header { position: running(running-header); width: 100%; }
                            .running-footer { position: running(running-footer); width: 100%; }

                            /* Header */
                            .header-block { padding: 10px 0; border-bottom: 2px dashed #000; text-align: center; }
                            .header-inner { display: inline-flex; align-items: center; gap: 15px; }
                            .logo img { width: 90px; height: auto; }
                            .company-info { text-align: left; }
                            .company-cn { font-size: 32px; font-weight: bold; color: #2a6e2a; letter-spacing: 8px; }
                            .company-en { font-size: 18px; font-weight: bold; margin: 2px 0; }
                            .company-addr { font-size: 13px; }
                            .company-contact { font-size: 13px; }

                            /* Bill/Delivery Section */
                            .info-section { display: flex; padding: 6px 0; }
                            .bill-to, .deliver-to { width: 33%; padding-right: 10px; }
                            .so-section { width: 34%; }
                            .section-title { font-weight: bold; margin-bottom: 3px; font-size: 12px; }
                            .so-title { font-size: 24px; font-weight: bold; text-align: center; margin-bottom: 6px; letter-spacing: 3px; }
                            .so-detail { display: flex; font-size: 11px; line-height: 1.5; }
                            .so-label { width: 100px; flex-shrink: 0; }
                            .so-colon { width: 10px; flex-shrink: 0; }
                            .so-value { flex: 1; }
                            .addr-name { font-weight: bold; font-size: 11px; }
                            .addr-line { font-size: 11px; line-height: 1.3; }

                            /* Contact row */
                            .contact-row { display: flex; font-size: 11px; line-height: 1.5; }
                            .contact-label { width: 40px; flex-shrink: 0; }
                            .contact-colon { width: 15px; flex-shrink: 0; }
                            .contact-value { flex: 1; }

                            /* Body table */
                            .body-section { padding: 0; }
                            table.items { width: 100%; border-collapse: collapse; font-size: 12px; }
                            table.items th, table.items td { border: 1px solid #000; padding: 4px 6px; }
                            table.items th { text-align: center; }
                            table.items .border-top-bottom-dashed { border-top: 1px dashed #000; border-bottom: 1px dashed #000; border-left: none; border-right: none; }
                            .border-top-bottom-dashed { border:0; border-top: 1px solid black; border-bottom: 1px dashed black; }

                            /* Footer */
                            .footer-block { font-size: 11px; line-height: 1.4; }
                            .footer-separator { border-top: 2px dotted #000; margin-bottom: 6px; }
                            .footer-total-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; }
                            .footer-total-words { font-weight: bold; font-size: 14px; }
                            .footer-total-amount { font-weight: bold; font-size: 14px; border-top: 1px solid #000; border-bottom: 3px double #000; padding: 2px 8px; text-align: right; }
                            .footer-note { margin-bottom: 1px; }
                            .footer-section-title { font-weight: bold; margin-top: 5px; margin-bottom: 1px; }

                            .page-current::after { content: counter(page); }
                            .page-total::after { content: counter(pages); }

                            /* Print button */
                            .print-btn-wrapper { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; }
                            .print-btn { background: #007bff; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
                            .print-btn:hover { background: #0056b3; }
                            .no-print { position: fixed; bottom: 20px; left: 0; right: 0; margin: 0 auto; width: fit-content; z-index: 9999; }
                            @media print { .no-print { display: none !important; } }
                        </style>
                    </head>
                    <body>
                        <!-- RUNNING HEADER -->
                        <div class="running-header">
                            <div class="header-block">
                                <div class="header-inner">
                                    <div class="logo">
                                        <img src="assets/company_logo.jpeg" alt="Logo">
                                    </div>
                                    <div class="company-info">
                                        <div class="company-cn">' . $companyNameCn . '</div>
                                        <div class="company-en">' . $companyName . '</div>
                                        <div class="company-addr">' . $companyAddress1 . ' ' . $companyAddress2 . '</div>
                                        <div class="company-addr">' . $companyAddress3 . ' ' . $companyAddress4 . '</div>
                                        <div class="company-contact">Tel: ' . $companyTel . '</div>
                                        <div class="company-contact">E-INVOICE TIN No. : ' . $companyTin . '&nbsp;&nbsp;&nbsp;EMAIL : ' . $companyEmail . '</div>
                                    </div>
                                </div>
                            </div>
                            <div class="info-section">
                                <div class="bill-to">
                                    <div class="section-title">BILL TO :</div>
                                    <div class="addr-name">' . $billToName . '</div>
                                    <div class="addr-line">' . $billToAddr1 . '</div>
                                    <div class="addr-line">' . $billToAddr2 . '</div>
                                    <div class="addr-line">' . $billToAddr3 . '</div>
                                    <br>
                                    <br>
                                    <br>
                                    <div class="contact-row"><span class="contact-label">Attn</span><span class="contact-colon">:</span><span class="contact-value">' . $billToAttn . '</span></div>
                                    <div class="contact-row"><span class="contact-label">Tel</span><span class="contact-colon">:</span><span class="contact-value">' . $billToTel . '</span></div>
                                    <div class="contact-row"><span class="contact-label">Fax</span><span class="contact-colon">:</span><span class="contact-value">' . $billToFax . '</span></div>
                                </div>
                                <div class="deliver-to">
                                    <div class="section-title">DELIVERY TO :</div>
                                    <div class="addr-name">' . $deliverToName . '</div>
                                    <div class="addr-line">' . $deliverToAddr1 . '</div>
                                    <div class="addr-line">' . $deliverToAddr2 . '</div>
                                    <div class="addr-line">' . $deliverToAddr3 . '</div>
                                    <br>
                                    <br>
                                    <br>
                                    <div class="contact-row"><span class="contact-label">Attn</span><span class="contact-colon">:</span><span class="contact-value">' . $deliverToAttn . '</span></div>
                                    <div class="contact-row"><span class="contact-label">Tel</span><span class="contact-colon">:</span><span class="contact-value">' . $deliverToTel . '</span></div>
                                    <div class="contact-row"><span class="contact-label">Fax</span><span class="contact-colon">:</span><span class="contact-value">' . $deliverToFax . '</span></div>
                                </div>
                                <div class="so-section">
                                    <div class="so-title"><span style="border-bottom: 1px solid black;">SALES ORDER</span></div>
                                    <div class="so-detail"><span class="so-label">SO No.</span><span class="so-colon">:</span><span class="so-value">' . $soNo . '</span></div>
                                    <div class="so-detail"><span class="so-label">Date</span><span class="so-colon">:</span><span class="so-value">' . $date . '</span></div>
                                    <div class="so-detail"><span class="so-label">Weight Time</span><span class="so-colon">:</span><span class="so-value">' . $time . '</span></div>
                                    <div class="so-detail"><span class="so-label">Weight Slip No</span><span class="so-colon">:</span><span class="so-value">' . $slipNo . '</span></div>
                                    <div class="so-detail"><span class="so-label">Vehicle No</span><span class="so-colon">:</span><span class="so-value">' . $vehicleNo . '</span></div>
                                    <div class="so-detail"><span class="so-label">Price Status</span><span class="so-colon">:</span><span class="so-value">' . $priceStatus . '</span></div>
                                    <div class="so-detail"><span class="so-label">Weight by</span><span class="so-colon">:</span><span class="so-value">' . $weightBy . '</span></div>
                                    <div class="so-detail"><span class="so-label">Pages</span><span class="so-colon">:</span><span class="so-value"><span class="page-current"></span> - <span class="page-total"></span></span></div>
                                </div>
                            </div>
                            <table class="items" style="margin-top:8px;">
                                <tr>
                                    <th style="width:30px;" class="border-top-bottom-dashed">NO</th>
                                    <th style="width:268px; text-align:left;" class="border-top-bottom-dashed">DESCRIPTION ITEM / GRADE</th>
                                    <th style="width:72px;" class="border-top-bottom-dashed">QTY/kg</th>
                                    <th style="width:58px;" class="border-top-bottom-dashed">UOM</th>
                                    <th style="width:145px;" class="border-top-bottom-dashed">UNIT PRICE (RM)</th>
                                    <th style="width:145px;" class="border-top-bottom-dashed">TOTAL PRICE (RM)</th>
                                </tr>
                            </table>
                        </div>

                        <!-- RUNNING FOOTER -->
                        <div class="running-footer">
                            <div class="footer-block">
                                <div class="footer-separator"></div>
                                <div class="footer-total-row">
                                    <div class="footer-total-words">RINGGIT MALAYSIA : ' . $totalAmountWords . '</div>
                                    <div class="footer-total-amount">RM' . $totalAmount . '</div>
                                </div>
                                <div class="footer-note">* We confirm acceptance of the above item description billing with goods sold are not returnable.</div>
                                <div class="footer-note">* All payment cheque &amp; cash should be crossed and made payable to \'<b>' . $companyName . '</b>\'</div>
                                <div class="footer-note">* Banker Name&nbsp;&nbsp;&nbsp;&nbsp;: ' . $bankerName . '</div>
                                <div class="footer-note">* Bank Account No: ' . $bankAccount . '</div>
                                <div class="footer-note">* Bank Swift Code: ' . $bankSwift . '</div>
                                <div class="footer-section-title">Remark :</div>
                                <div class="footer-note">* We reserve the right to charge interest base on invoice date overdue bills at the rate of 2.5% per days and refer to payment Franz.</div>
                                <div class="footer-note">* If the remaining payment for 30 Days is not fully paid, We have the right to recover all of the above description of goods.</div>
                                <div class="footer-section-title">Notes :</div>
                                <div class="footer-note">* This Invoice is generated by "<b>' . $companyName . '</b>" Computer Administrators and does not require a signature.</div>
                                <div class="footer-note">* If any concern &amp; required, kindly contact "<b>' . $companyName . '</b>" Account Department (' . $companyPhone . ')</div>
                            </div>
                        </div>

                        <!-- BODY - Items Table -->
                        <div class="body-section">
                            <table class="items">
                                <tbody>
                                    ' . $itemRowsHtml . '
                                    <tr style="break-inside:avoid; page-break-inside:avoid;">
                                        <td style="width:30px; border:0;"></td>
                                        <td style="width:268px; border:0; padding-top:8px;">
                                            <div style="font-weight:bold; text-decoration:underline; font-size:12px;">SUMMERY DETAILS</div>
                                            <div style="font-size:11px; line-height:1.6;">
                                                <div style="display:flex;"><span style="width:10px;">*</span><span style="width:140px;">Total Bin Count</span><span style="width:10px;">:</span><span>' . $totalBinCount . '</span></div>
                                                <div style="display:flex;"><span style="width:10px;">*</span><span style="width:140px;">Total Actual Weight</span><span style="width:10px;">:</span><span>' . $totalActualWeight . ' kg</span></div>
                                                <div style="display:flex;"><span style="width:10px;">*</span><span style="width:140px;">Total Tare Weight</span><span style="width:10px;">:</span><span>' . $totalTareWeight . ' t</span></div>
                                                <div style="display:flex;"><span style="width:10px;">*</span><span style="width:140px;">Start Weight Time</span><span style="width:10px;">:</span><span>' . $startWeightTime . '</span></div>
                                                <div style="display:flex;"><span style="width:10px;">*</span><span style="width:140px;">End Weight Time</span><span style="width:10px;">:</span><span>' . $endWeightTime . '</span></div>
                                            </div>
                                        </td>
                                        <td style="width:72px; border:0;"></td>
                                        <td style="width:58px; border:0;"></td>
                                        <td style="width:145px; border:0;"></td>
                                        <td style="width:145px; border:0;"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Print Button -->
                        <div id="printBtnWrapper" data-pagedjs-ignore style="position:fixed; bottom:20px; left:0; right:0; text-align:center; z-index:9999;">
                            <button onclick="document.getElementById(\'printBtnWrapper\').style.display=\'none\'; document.title=\'' . $soNo . '\'; setTimeout(function(){ window.print(); document.getElementById(\'printBtnWrapper\').style.display=\'\'; }, 200);" style="background:#007bff; color:#fff; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; font-size:14px; box-shadow:0 2px 6px rgba(0,0,0,0.15);">🖨️ Print</button>
                        </div>

                        <script>
                            window.PagedConfig = { auto: true };
                        <\/script>

                    </body>
                    </html>
                ';

                echo json_encode(
                    array(
                        "status" => "success",
                        "message" => $message
                    )
                );
            } else {
                echo json_encode(
                    array(
                        "status" => "failed",
                        "message" => "Record not found"
                    )
                );
            }
        }
    }
    else{
        echo json_encode(
            array(
                "status" => "failed",
                "message" => "Something went wrong"
            )); 
    }
}
else{
    echo json_encode(
        array(
            "status"=> "failed", 
            "message"=> "Please fill in all the fields"
        )
    ); 
}