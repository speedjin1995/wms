<?php
require_once '../../db_connect.php';
require_once '../../lookup.php';

session_start();

if(isset($_GET['id'])){
    $languageArray = $_SESSION['languageArray'];
    $language = 'en';
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
                $companyRegNo = $wholesale['reg_no'] ?? '';
                $companyAddress1 = $wholesale['address'] ?? '';
                $companyAddress2 = $wholesale['address2'] ?? '';
                $companyAddress3 = $wholesale['address3'] ?? '';
                $companyAddress4 = $wholesale['address4'] ?? '';
                $companyTel = $wholesale['phone'] ?? '';
                $companyTin = $wholesale['tin_no'] ?? '';
                $companyEmail = $wholesale['email'] ?? '';
                $companyPhone = $wholesale['phone'] ?? '';
                $companyBankerName = $wholesale['banker_name'] ?? '';
                $companyBankAcctNo = $wholesale['bank_acct_no'] ?? '';
                $companyBankSwiftCode = $wholesale['bank_swift_code'] ?? '';
                $companyLogo = $wholesale['company_logo'];
                $companyIncludePayment = $wholesale['include_payment'] ?? 'N';
                $companyLogoSrc = '';
                if (!empty($companyLogo)) {
                    $companyLogoSrc = 'php/viewPhoto.php?file=' . urlencode($companyLogo) . '&type=file_table';
                }

                // SO details from wholesales table
                $soNo = $wholesale['po_no'];
                $date = date('d/m/Y', strtotime($wholesale['start_time']));
                $time = date('H:i:s', strtotime($wholesale['start_time']));
                $slipNo = $wholesale['serial_no'];
                $vehicleNo = $wholesale['vehicle_no'] ?? '';
                $priceStatus = (floatval($wholesale['total_price']) > 0) ? 'FIXED' : 'FLOAT';
                $weightBy = searchUserNameById($wholesale['weighted_by'], $db);

                if ($wholesale['status'] == 'RECEIVING'){
                    // Supplier info for Bill To
                    $supplierData = [];
                    if (!empty($wholesale['supplier'])) {
                        if ($supp_stmt = $db->prepare("SELECT * FROM supplies WHERE id = ?")) {
                            $supp_stmt->bind_param('s', $wholesale['supplier']);
                            $supp_stmt->execute();
                            $supp_result = $supp_stmt->get_result();
                            $supplierData = $supp_result->fetch_assoc() ?: [];
                            $supp_stmt->close();
                        }
                    }

                    $deliverToName = $supplierData['supplier_name'] ?? '';
                    $deliverToAddr1 = $supplierData['supplier_address'] ?? '';
                    $deliverToAddr2 = $supplierData['supplier_address2'] ?? '';
                    $deliverToAddr3 = $supplierData['supplier_address3'] ?? '';
                    $deliverToAddr4 = $supplierData['supplier_address4'] ?? '';
                    $deliverToAttn = $supplierData['pic'] ?? '';
                    $deliverToTel = $supplierData['supplier_phone'] ?? '';
                    $deliverToFax = $supplierData['fax'] ?? '';

                    // Delivery To (same as Bill To)
                    $billToName = $supplierData['billing_name'] ?? '';
                    $billToAddr1 = $supplierData['billing_address'] ?? '';
                    $billToAddr2 = $supplierData['billing_address2'] ?? '';
                    $billToAddr3 = $supplierData['billing_address3'] ?? '';
                    $billToAddr4 = $supplierData['billing_address4'] ?? '';
                    $billToAttn = $supplierData['billing_pic'] ?? '';
                    $billToTel = $supplierData['billing_phone'] ?? '';
                    $billToFax = $supplierData['billing_fax'] ?? '';
                }else{
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

                    $deliverToName = $customerData['customer_name'] ?? '';
                    $deliverToAddr1 = $customerData['customer_address'] ?? '';
                    $deliverToAddr2 = $customerData['customer_address2'] ?? '';
                    $deliverToAddr3 = $customerData['customer_address3'] ?? '';
                    $deliverToAddr4 = $customerData['customer_address4'] ?? '';
                    $deliverToAttn = $customerData['pic'] ?? '';
                    $deliverToTel = $customerData['customer_phone'] ?? '';
                    $deliverToFax = $customerData['fax'] ?? '';

                    // Delivery To (same as Bill To)
                    $billToName = $customerData['billing_name'] ?? '';
                    $billToAddr1 = $customerData['billing_address'] ?? '';
                    $billToAddr2 = $customerData['billing_address2'] ?? '';
                    $billToAddr3 = $customerData['billing_address3'] ?? '';
                    $billToAddr4 = $customerData['billing_address4'] ?? '';
                    $billToAttn = $customerData['billing_pic'] ?? '';
                    $billToTel = $customerData['billing_phone'] ?? '';
                    $billToFax = $customerData['billing_fax'] ?? '';
                }

                // Summary data
                $startWeightTime = date('g:i:s A', strtotime($wholesale['start_time']));
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
                            'tare' => [],
                            'qty' => 0,
                            'uom' => strtoupper($detail['unit'] ?? 'KG'),
                            'unit_price' => floatval($detail['price'] ?? 0),
                            'total_price' => 0,
                        ];
                    }
                    $net = floatval($detail['net'] ?? 0);
                    $tare = floatval($detail['tare'] ?? 0);
                    $grouped[$key]['weights'][] = $net;
                    $grouped[$key]['tare'][] = $tare;
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
                $totalTareWeight = 0;
                $totalAmount = 0;
                foreach ($items as $index => $item) {
                    // echo json_encode($item);exit;

                    $weightLines = '';
                    $chunks = array_chunk($item['weights'], 7);
                    foreach ($chunks as $chunk) {
                        $weightLines .= implode('&nbsp;&nbsp;', array_map(function($w) { return number_format($w, 2); }, $chunk)) . '<br>';
                    }
                    $no = $index + 1;
                    $qty = number_format($item['qty'], 2);
                    $unitPrice = number_format($item['unit_price'], 2);
                    $totalPrice = number_format($item['total_price'], 2);
                    $totalAmount += floatval($item['total_price']);
                    $tareWeight = 0;
                    foreach ($item['tare'] as $tare) {
                        $tareWeight += floatval($tare);
                        $totalTareWeight += floatval($tare);
                    }

                    $tareOverBin = number_format($tareWeight/floatval($item['bin']), 2);
                    $itemRowsHtml .= '
                                    <div class="item-row">
                                        <div class="item-col-no">' . $no . '</div>
                                        <div class="item-col-desc">
                                            <span class="item-col-desc-product">' . $item['product'] . '</span><br><span class="item-col-desc-grade">GRADE : ' . $item['grade'] . '</span><br>
                                            <span class="item-col-desc-product">TARE / BIN : ' . $tareOverBin . $item['uom'] . '</span><span class="item-col-desc-grade">BIN : ' . $item['bin'] . '</span><br>
                                            <span>' . $weightLines . '</span>
                                        </div>
                                        <div class="item-col-qty">' . $qty . '</div>
                                        <div class="item-col-uom">' . $item['uom'] . '</div>
                                        <div class="item-col-unit-price">RM&nbsp;&nbsp;' . $unitPrice . '</div>
                                        <div class="item-col-total-price">RM' . $totalPrice . '</div>
                                    </div>';
                }

                // Footer data
                $formatter = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                $total = $totalAmount;
                $ringgit = intval($total);
                $cents = intval(round(($total - $ringgit) * 100));
                $totalAmount = number_format($totalAmount, 2);
                $totalAmountWords = strtoupper($formatter->format($ringgit)) . ' RINGGIT'
                    . ($cents > 0 ? ' AND ' . strtoupper($formatter->format($cents)) . ' CENTS' : ' ONLY');

                // Summary calculations
                $totalBinCount = array_sum(array_column($items, 'bin'));
                $totalActualWeight = number_format(array_sum(array_column($items, 'qty')), 2);
                $message = '
                    <html>
                    <head>
                        <meta charset="UTF-8">
                        <title>Sales Order - ' . $soNo . '</title>
                        <script src="https://unpkg.com/pagedjs/dist/paged.polyfill.js"></script>
                        <style>
                            * { margin: 0; padding: 0; box-sizing: border-box; }
                            body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #000; }

                            /* Paged.js */
                            @page {
                                size: A4;
                                margin: 85mm 10mm 10mm 10mm;
                                @top-left { content: element(running-header); }
                            }
                            .running-header { position: running(running-header); width: 100%; }
                            .running-footer { break-before: avoid; margin-top: 10px; }

                            /* Wrapper to push footer to bottom */
                            .content-wrapper { display: flex; flex-direction: column; min-height: calc(297mm - 85mm - 10mm - 10mm); }
                            .body-section { flex: 1; }

                            /* Header */
                            .header-block { padding: 10px 0; border-bottom: 2px dashed #000; text-align: center; }
                            .header-inner { display: inline-flex; align-items: center; gap: 15px; }
                            .logo img { width: 110px; height: auto; }
                            .company-info { text-align: left; }
                            .company-cn { font-size: 32px; font-weight: bold; color: black; letter-spacing: 8px; }
                            .company-en { font-size: 18px; font-weight: bold; margin: 2px 0; }
                            .company-addr { font-size: 13px; }
                            .company-contact { font-size: 13px; }

                            /* Bill/Delivery Section */
                            .info-section { display: flex; flex-wrap: wrap; padding: 4px 0; }
                            .bill-to, .deliver-to { width: 35%; padding-right: 10px; }
                            .so-section { width: 30%; margin-left: auto; }
                            .payment-method-row { width: 66%; padding-right: 10px; margin-top: 4px; }
                            .section-title { font-weight: bold; margin-bottom: 3px; font-size: 12px; }
                            .so-title { font-size: 20px; font-weight: bold; text-align: center; margin-bottom: 6px; letter-spacing: 3px; white-space: nowrap; }
                            .so-detail { display: flex; font-size: 11px; line-height: 1.5; }
                            .so-label { width: 100px; flex-shrink: 0; }
                            .so-colon { width: 10px; flex-shrink: 0; }
                            .so-value { flex: 1; }
                            .addr-name { font-weight: bold; font-size: 10px; }
                            .addr-line { font-size: 10px; line-height: 1.3; }

                            /* Contact row */
                            .contact-row { display: flex; font-size: 11px; line-height: 1.5; }
                            .contact-label { width: 60px; flex-shrink: 0; }
                            .contact-label-wide { width: 100px; flex-shrink: 0; }
                            .contact-colon { width: 15px; flex-shrink: 0; }
                            .contact-value { flex: 1; }

                            /* Body table */
                            .body-section { padding: 0; }
                            table.items { width: 100%; border-collapse: collapse; font-size: 12px; }
                            table.items th, table.items td { border: 1px solid #000; padding: 4px 6px; }
                            table.items th { text-align: center; }
                            table.items .border-top-bottom-dashed { border-top: 1px dashed #000; border-bottom: 1px dashed #000; border-left: none; border-right: none; }
                            .border-top-bottom-dashed { border:0; border-top: 1px solid black; border-bottom: 1px dashed black; }

                            /* Item rows */
                            .item-row { display: flex; break-inside: avoid; page-break-inside: avoid; padding-top: 8px; width: 100%; box-sizing: border-box; }
                            .item-col-no { width: 30px; min-width: 30px; max-width: 30px; text-align: center; overflow: hidden; }
                            .item-col-desc { flex: 1; min-width: 0; text-align: left; overflow: hidden; }
                            .item-col-desc-product { vertical-align: top; display: inline-block; width: 55%; font-weight: bold; }
                            .item-col-desc-grade { display: inline-block; width: 44%; }
                            .item-col-qty { width: 72px; min-width: 72px; max-width: 72px; text-align: center; overflow: hidden; }
                            .item-col-uom { width: 58px; min-width: 58px; max-width: 58px; text-align: center; overflow: hidden; }
                            .item-col-unit-price { width: 120px; min-width: 120px; max-width: 120px; text-align: center; overflow: hidden; }
                            .item-col-total-price { width: 120px; min-width: 120px; max-width: 120px; text-align: center; overflow: hidden; }
                            .item-col-no-empty { width: 30px; min-width: 30px; max-width: 30px; flex-shrink: 0; }
                            .item-col-qty-empty { width: 72px; min-width: 72px; max-width: 72px; flex-shrink: 0; }
                            .item-col-uom-empty { width: 58px; min-width: 58px; max-width: 58px; flex-shrink: 0; }
                            .item-col-price-empty { width: 120px; min-width: 120px; max-width: 120px; flex-shrink: 0; }
                            .summary-desc { flex: 1; min-width: 0; padding-top: 8px; }
                            .summary-title { font-weight: bold; text-decoration: underline; font-size: 12px; }
                            .summary-list { font-size: 11px; line-height: 1.6; }
                            .summary-list-row { display: flex; }
                            .summary-list-bullet { width: 10px; }
                            .summary-list-label { width: 140px; }
                            .summary-list-colon { width: 10px; }

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
                            <div class="header-block" style="text-align:left;">
                                <div class="header-inner" style="display:flex; align-items:center; gap:15px;">
                                    ' . ($companyLogoSrc ? '<div class="logo"><img src="' . $companyLogoSrc . '" alt="Logo"></div>' : '') . '
                                    <div class="company-info">
                                        <div class="company-cn">' . $companyNameCn . '</div>
                                        <div class="company-en">' . $companyName . '</div>
                                        <div class="company-addr">' . $companyAddress1 . ' ' . $companyAddress2 . '</div>
                                        <div class="company-addr">' . $companyAddress3 . ' ' . $companyAddress4 . '</div>
                                        <div class="company-contact">Tel: ' . $companyTel . '<span style="display:none">&nbsp;&nbsp;&nbsp;E-INVOICE TIN No. : ' . $companyTin . '<span></div>
                                        <div class="company-contact">EMAIL : ' . $companyEmail . '</div>
                                    </div>
                                </div>
                            </div>
                            <div class="info-section">
                                <div class="bill-to">';

                                if ($wholesale['status'] == 'RECEIVING'){
                                    $message .= '
                                    <div class="section-title">PAYMENT TO :</div>
                                    ';
                                }else{
                                    $message .= '
                                    <div class="section-title">BILL TO :</div>
                                    ';
                                }

                                $message .= '
                                    <div class="addr-name">' . $billToName . '</div>
                                    <div class="addr-line">' . $billToAddr1 . '</div>
                                    <div class="addr-line">' . $billToAddr2 . '</div>
                                    <div class="addr-line">' . $billToAddr3 . '</div>
                                    <br>
                                    <br>
                                    <div class="contact-row"><span class="contact-label">Attn</span><span class="contact-colon">:</span><span class="contact-value">' . $billToAttn . '</span></div>
                                    <div class="contact-row"><span class="contact-label">Tel</span><span class="contact-colon">:</span><span class="contact-value">' . $billToTel . '</span></div>
                                    <div class="contact-row"><span class="contact-label">Email</span><span class="contact-colon">:</span><span class="contact-value">' . $billToFax . '</span></div>';

                                    if ($companyIncludePayment == 'Y') {
                                        $message .= '
                                        <div class="contact-row"><span class="contact-label">Payment <br>Method</span><span class="contact-colon">:</span><span class="contact-value">' . $wholesale['payment_method'] . '</span></div>';
                                    }
                                $message .= '
                                </div>
                                <div class="deliver-to" style="' . ($wholesale['status'] == 'RECEIVING' ? 'display:none;' : '') . '">
                                    <div class="section-title">DELIVERY TO :</div>
                                    <div class="addr-name">' . $deliverToName . '</div>
                                    <div class="addr-line">' . $deliverToAddr1 . '</div>
                                    <div class="addr-line">' . $deliverToAddr2 . '</div>
                                    <div class="addr-line">' . $deliverToAddr3 . '</div>
                                    <br>
                                    <br>
                                    <div class="contact-row"><span class="contact-label">Attn</span><span class="contact-colon">:</span><span class="contact-value">' . $deliverToAttn . '</span></div>
                                    <div class="contact-row"><span class="contact-label">Tel</span><span class="contact-colon">:</span><span class="contact-value">' . $deliverToTel . '</span></div>
                                    <div class="contact-row"><span class="contact-label">Email</span><span class="contact-colon">:</span><span class="contact-value">' . $deliverToFax . '</span></div>
                                </div>
                                <div class="so-section">'; 
                                    if ($wholesale['status'] == 'RECEIVING'){
                                        $message .= '
                                            <div class="so-title"><span style="border-bottom: 1px solid black;">'.$languageArray['purchase_invoice_code'][$language].'</span></div>
                                            <div class="so-detail"><span class="so-label">'.$languageArray['pi_no_code'][$language].'</span><span class="so-colon">:</span><span class="so-value">' . $soNo . '</span></div>
                                        ';
                                    }else{
                                        $message .= '
                                            <div class="so-title"><span style="border-bottom: 1px solid black;">'.$languageArray['invoice_title_code'][$language].'</span></div>
                                            <div class="so-detail"><span class="so-label">'.$languageArray['so_no_code'][$language].'</span><span class="so-colon">:</span><span class="so-value">' . $soNo . '</span></div>
                                        ';
                                    }
                                    
                                    $message .= '
                                    <div class="so-detail"><span class="so-label">Date</span><span class="so-colon">:</span><span class="so-value">' . $date . '</span></div>
                                    <div class="so-detail"><span class="so-label">Weight Time</span><span class="so-colon">:</span><span class="so-value">' . $time . '</span></div>
                                    <div class="so-detail"><span class="so-label">Weight Slip No</span><span class="so-colon">:</span><span class="so-value">' . $slipNo . '</span></div>
                                    <div class="so-detail"><span class="so-label">Vehicle No</span><span class="so-colon">:</span><span class="so-value">' . $vehicleNo . '</span></div>
                                    <!--div class="so-detail"><span class="so-label">Price Status</span><span class="so-colon">:</span><span class="so-value">' . $priceStatus . '</span></div-->
                                    <div class="so-detail"><span class="so-label">Weight by</span><span class="so-colon">:</span><span class="so-value">' . $weightBy . '</span></div>
                                    <div class="so-detail"><span class="so-label">Pages</span><span class="so-colon">:</span><span class="so-value"><span class="page-current"></span> - <span class="page-total"></span></span></div>
                                </div>
                            </div>
                            <table class="items" style="margin-top:8px;">
                                <tr>
                                    <th style="width:30px;" class="border-top-bottom-dashed">NO</th>
                                    <th style="width:400px; text-align:left;" class="border-top-bottom-dashed">DESCRIPTION ITEM / GRADE</th>
                                    <th style="width:72px;" class="border-top-bottom-dashed">QTY/kg</th>
                                    <th style="width:58px;" class="border-top-bottom-dashed">UOM</th>
                                    <th style="width:145px;" class="border-top-bottom-dashed">UNIT PRICE</th>
                                    <th style="width:145px;" class="border-top-bottom-dashed">TOTAL PRICE</th>
                                </tr>
                            </table>
                        </div>

                        <div class="content-wrapper">
                            <!-- BODY - Items Table -->
                            <div class="body-section">
                                ' . $itemRowsHtml . '
                                <div class="item-row">
                                    <div class="item-col-no-empty"></div>
                                    <div class="summary-desc">
                                        <div class="summary-title">SUMMARY DETAILS</div>
                                        <div class="summary-list">
                                            <div class="summary-list-row"><span class="summary-list-bullet">*</span><span class="summary-list-label">Total Bin Count</span><span class="summary-list-colon">:</span><span>' . $totalBinCount . '</span></div>
                                            <div class="summary-list-row"><span class="summary-list-bullet">*</span><span class="summary-list-label">Total Actual Weight</span><span class="summary-list-colon">:</span><span>' . $totalActualWeight . ' kg</span></div>
                                            <div class="summary-list-row"><span class="summary-list-bullet">*</span><span class="summary-list-label">Total Tare Weight</span><span class="summary-list-colon">:</span><span>' . $totalTareWeight . ' kg</span></div>
                                            <div class="summary-list-row"><span class="summary-list-bullet">*</span><span class="summary-list-label">Start Weight Time</span><span class="summary-list-colon">:</span><span>' . $startWeightTime . '</span></div>
                                            <div class="summary-list-row"><span class="summary-list-bullet">*</span><span class="summary-list-label">End Weight Time</span><span class="summary-list-colon">:</span><span>' . $endWeightTime . '</span></div>
                                        </div>
                                    </div>
                                    <div class="item-col-qty-empty"></div>
                                    <div class="item-col-uom-empty"></div>
                                    <div class="item-col-price-empty"></div>
                                    <div class="item-col-price-empty"></div>
                                </div>
                            </div>

                            <!-- FOOTER -->
                            <div class="running-footer">
                                <div class="footer-block">
                                    <div class="footer-separator"></div>
                                    <div class="footer-total-row">
                                        <div class="footer-total-words">RINGGIT MALAYSIA : ' . $totalAmountWords . '</div>
                                        <div class="footer-total-amount">RM' . $totalAmount . '</div>
                                    </div>
                                    ' . ($wholesale['status'] != 'RECEIVING' ? '<div class="footer-note">* We confirm acceptance of the above item description billing with goods sold are not returnable nor refundable.</div>
                                    <div class="footer-note">* All payment cheque &amp; cash should be crossed and made payable to &#39;<b>' . $companyName . '</b>&#39;</div>
                                    <div class="footer-note">* Banker Name&nbsp;&nbsp;&nbsp;&nbsp;: ' . $companyBankerName . '</div>
                                    <div class="footer-note">* Bank Account No: ' . $companyBankAcctNo . '</div>
                                    <div class="footer-note">* Bank Swift Code: ' . $companyBankSwiftCode . '</div>
                                    <div class="footer-section-title">Remark :</div>
                                    <div class="footer-note">* Goods Sold Are Neither returnable nor refundable.</div>
                                    <div class="footer-note">* Goods are the buyer’s risk once they leave the packaging house.</div>' : '') . '
                                    <div class="footer-section-title">Notes :</div>
                                    <div class="footer-note">* This Invoice is generated by "<b>' . $companyName . '</b>" Computer Administrators and does not require a signature.</div>
                                    <div class="footer-note">* If any concern &amp; required, kindly contact "<b>' . $companyName . '</b>" Account Department (' . $companyPhone . ')</div>
                                </div>
                            </div>
                        </div>

                        <div id="printBtnWrapper" data-pagedjs-ignore style="position:fixed; bottom:20px; left:0; right:0; text-align:center; z-index:9999;">
                            <button onclick="document.getElementById(&#39;printBtnWrapper&#39;).style.display=&#39;none&#39;; document.title=&#39;' . $soNo . '&#39;; setTimeout(function(){ window.print(); document.getElementById(&#39;printBtnWrapper&#39;).style.display=&#39;&#39;; }, 200);" style="background:#007bff; color:#fff; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; font-size:14px; box-shadow:0 2px 6px rgba(0,0,0,0.15);">🖨️ Print</button>
                        </div>
                    </body>
                    </html>
                ';

                echo json_encode(
                    array(
                        "status" => "success",
                        "message" => $message
                    ),
                    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
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