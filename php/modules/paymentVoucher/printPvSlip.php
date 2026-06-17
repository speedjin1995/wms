<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

session_start();
require_once '../../db_connect.php';
require_once '../../lookup.php';
$_POST = array_merge($_GET, $_POST);

if(isset($_POST['pvId'], $_POST['slipType'])){
    $company = $_SESSION['customer'];
    $pvId = $_POST['pvId'];
    $slipType = $_POST['slipType'];
    $paymentVoucherNo = null;

    // Language
    $language = $_SESSION['language'];
    $languageArray = $_SESSION['languageArray'];

    // Check if payment voucher exists
    if (empty($pvId)) {
        echo json_encode(array(
                "status" => "failed",
                "message" => "No payment voucher found for this date and supplier"
            ));
            exit;
    }
    
    // Get company details
    $compname = '';
    $compreg = '';
    $compaddress = '';
    $compphone = '';
    
    $stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->bind_param('s', $company);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $compname = $row['name'];
        $compreg = $row['reg_no'];
        $compaddress = $row['address'] . ', ' . $row['address2'] . ', ' . $row['address3'] . ', ' . $row['address4'];
        $compaddress1 = $row['address'];
        $compaddress2 = $row['address2'];
        $compaddress3 = $row['address3'];
        $compaddress4 = $row['address4'];
        $compphone = $row['phone'];
    }
    
    // Get payment voucher details
    $sql = "SELECT * FROM payment_vouchers WHERE id=?";
    if ($stmt = $db->prepare($sql)) {
        $stmt->bind_param('s', $pvId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $isIncoming = in_array($row['status'], ['RECEIVING', 'INCOMING']);
            $entityName = $isIncoming
                ? searchSupplierNameById($row['entity_id'], '', $db)
                : searchCustomerNameById($row['entity_id'], '', $db);
            $voucherDate = date('d/m/Y', strtotime($row['voucher_date']));
            $invoiceNo = $row['invoice_no'] ?? '';
            $paymentVoucherNo = $row['voucher_no'] ?? '';

            $isPdfDownload = (isset($_POST['printType']) && $_POST['printType'] == 'exportDownload');

            // Format date to Malay month and year
            $malayMonths = array(
                1 => $languageArray['january_code'][$language], 2 => $languageArray['february_code'][$language], 3 => $languageArray['march_code'][$language], 4 => $languageArray['april_code'][$language],
                5 => $languageArray['may_code'][$language], 6 => $languageArray['june_code'][$language], 7 => $languageArray['july_code'][$language], 8 => $languageArray['august_code'][$language],
                9 => $languageArray['september_code'][$language], 10 => $languageArray['october_code'][$language], 11 => $languageArray['november_code'][$language], 12 => $languageArray['december_code'][$language]
            );
            $month = date('n', strtotime($row['voucher_date']));
            $year = date('Y', strtotime($row['voucher_date']));
            $formatVoucherDate = $malayMonths[$month] . ' ' . $year;
            
            $accountNo = $row['account_no'] ?? '';
            $unitPrice = floatval($row['unit_price']);
            $tax = floatval($row['tax']);
            $totalNettWeight = floatval(str_replace(',', '', $row['total_nett_weight']));
            $totalNettAmount = floatval(str_replace('RM ', '', $row['nett_amount']));
            $totalTaxAmount = floatval(str_replace('RM ', '', $row['tax_amount']));
            $totalAmount = floatval(str_replace('RM ', '', $row['total_amount']));
            $totalDeductions = floatval($row['deduction_amount']);
            $totalAdditions = floatval($row['addition_amount']);
            $finalAmount = floatval($row['final_amount']);
            
            if ($slipType == "pv"){
                $message = '
                <html>
                <head>
                    <style>
                        @media print {
                            @page {
                                size: A5 landscape;
                                margin: 0px;
                            }
                        }
                        body {
                            font-family: Arial, sans-serif;
                            font-size: 11px;
                            margin: 20px;
                            padding: 0;
                        }
                        .header {
                            text-align: center;
                            margin-bottom: 15px;
                        }
                        .header h3 {
                            margin: 0;
                            font-size: 13px;
                            font-weight: bold;
                        }
                        .header p {
                            margin: 2px 0;
                            font-size: 10px;
                        }
                        .title {
                            text-align: center;
                            font-size: 14px;
                            font-weight: bold;
                            margin: 10px 0;
                            text-decoration: underline;
                        }
                        .info-row {
                            display: flex;
                            justify-content: space-between;
                            margin-bottom: 10px;
                            gap: 20px;
                        }
                        .info-item {
                            display: flex;
                            align-items: baseline;
                            flex: 1;
                        }
                        .info-label {
                            font-weight: bold;
                            width: 100px;
                            display: inline-block;
                        }
                        .info-value {
                            border-bottom: 1px solid #000;
                            flex: 1;
                            display: inline-block;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin: 10px 0;
                        }
                        th, td {
                            border: 1px solid #000;
                            padding: 5px;
                            text-align: center;
                            font-size: 10px;
                        }
                        th {
                            font-weight: bold;
                            background-color: #f0f0f0;
                        }
                        .text-right { text-align: right; }
                        .text-left { text-align: left; }
                        .total-row { font-weight: bold; }
                        .footer { margin-top: 15px; font-size: 10px; }
                        .signature {
                            margin-top: 50px;
                            display: flex;
                            flex-direction: column;
                            align-items: flex-end;
                        }
                        .signature p { text-align: left; width: 200px; margin: 0; }
                        .signature-line { border-top: 1px solid #000; width: 200px; margin-top: 50px; }
                        .text-caps { text-transform: uppercase; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h3>'.$compname.' (No. Daftar: '.$compreg.')</h3>
                        <p>'.$compaddress.'</p>
                        <p>TEL: '.$compphone.'</p>
                    </div>
                    
                    <div class="title text-caps">'.$languageArray['payment_voucher_code'][$language].'</div>
                    
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label text-caps">'.$languageArray['name_code'][$language].' :</span>
                            <span class="info-value">'.$entityName.'</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label text-caps">'.$languageArray['date_code'][$language].' :</span>
                            <span class="info-value">'.$voucherDate.'</span>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label text-caps">'.$languageArray['voucher_no_code'][$language].' :</span>
                            <span class="info-value">'.$paymentVoucherNo.'</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label text-caps">'.$languageArray['invoice_no_code'][$language].' :</span>
                            <span class="info-value">'.$invoiceNo.'</span>
                        </div>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th class="text-caps">'.$languageArray['number_short_code'][$language].'</th>
                                <th class="text-caps">'.$languageArray['item_code'][$language].'</th>
                                <th class="text-caps">'.$languageArray['unit_code'][$language].'/KG</th>
                                <th class="text-caps">'.$languageArray['price_code'][$language].' (RM)</th>
                                <th class="text-caps">'.$languageArray['total_code'][$language].' (RM)</th>
                            </tr>
                        </thead>
                        <tbody>';

                $bil = 1;
                $message .= '
                    <tr>
                        <td class="text-center">'.$bil.'</td>
                        <td class="text-left">'.$formatVoucherDate.'</td>
                        <td>'.number_format($totalNettWeight, 2).'</td>
                        <td class="text-center">RM'.number_format($unitPrice, 2).'</td>
                        <td class="text-center">RM'.number_format($totalAmount, 2).'</td>
                    </tr>
                ';
                $bil++;
                
                $message .= '
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-left text-caps" style="border-right:none"><strong>'.$languageArray['bank_account_no_code'][$language].' : '.$accountNo.'</strong></td>
                                <td class="text-right text-caps" style="border-left:none"><strong>'.$languageArray['total_code'][$language].' (RM)</strong></td>
                                <td class="text-center"><strong>RM'.number_format($finalAmount, 2).'</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <div class="signature">
                        <p class="text-caps">'.$languageArray['received_by_code'][$language].' :</p>
                        <div class="signature-line"></div>
                    </div>
                    </div>
                </body>
                </html>';
            } else {
                // Fetch weighing records for this PV
                $pvItems = [];
                if ($wStmt = $db->prepare("SELECT * FROM wholesales WHERE pv_id = ? AND deleted = 0 ORDER BY created_datetime ASC")) {
                    $wStmt->bind_param('s', $pvId);
                    $wStmt->execute();
                    $wResult = $wStmt->get_result();
                    while ($wRow = $wResult->fetch_assoc()) {
                        $weightDetails = json_decode($wRow['weight_details'] ?? '[]', true) ?? [];
                        $nett = 0;
                        foreach ($weightDetails as $wd) { 
                            $nett += floatval($wd['net'] ?? 0); 
                        }
                        if ($nett == 0) {
                            $nett = floatval($wRow['total_weight']);
                        }

                        $pvItems[] = [
                            'date' => date('d/m/Y', strtotime($wRow['start_time'])),
                            'serial_no' => $wRow['serial_no'],
                            'nett' => $nett,
                        ];
                    }
                    $wStmt->close();
                }

                $message = '
                <html>
                <head>
                    <meta charset="UTF-8">
                    <style>
                        @media print {
                            @page { size: A4; margin: 15mm; }
                        }
                        body { font-family: "Times New Roman", serif; font-size: 14px; margin: 20px; padding: 0; }
                        .page-header { text-align: center; font-size: 13px; line-height: 1.3; margin-bottom: 20px; }
                        .page-header h2 { margin: 0 0 5px 0; font-size: 18px; }
                        .divider { border-bottom: 1px solid #000; margin: 10px 0; }
                        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                        td { padding: 4px 8px; font-size: 13px; border: none; }
                        .table-border td { border-top: 1px solid #000; border-bottom: 1px solid #000; }
                        .border-top { border-top: 1px solid #000 !important; }
                        .border-bottom { border-bottom: 1px solid #000 !important; }
                        .text-right { text-align: right; }
                        .text-center { text-align: center; }
                        .footer-signatures { display: flex; justify-content: space-between; border-top: 1px solid #000; margin-top: 30px; padding-top: 10px; }
                        .footer-signatures > div { width: 45%; text-align: center; }
                        .signature-line { border-top: 1px solid #000; width: 200px; margin: 50px auto 0 auto; }
                        .text-caps { text-transform: uppercase; }
                    </style>
                </head>
                <body>
                    <div class="page-header">
                        <h2>'.$compname.'</h2>
                        ('.$compreg.')<br>
                        '.$compaddress1.'<br>
                        '.(!empty($compaddress2) ? $compaddress2.'<br>' : '').'
                        '.(!empty($compaddress3) ? $compaddress3.'<br>' : '').'
                        '.(!empty($compaddress4) ? $compaddress4.'<br>' : '').' 
                        Tel: '.$compphone.'
                        <div style="margin-top: 15px; font-weight: bold; font-size: 18px;">
                            <span class="text-caps">'.$languageArray['statement_code'][$language].'</span><br>
                            <span class="text-caps" style="font-weight: normal; font-size: 14px;">'.$languageArray['for_the_month_of_code'][$language].' '.$formatVoucherDate.'</span>
                        </div>
                        <div class="divider"></div>
                    </div>

                    <table>
                        <tr>
                            <td style="width:60%; font-weight:bold; font-size:16px;">'.$entityName.'</td>
                            <td style="width:20%;">'.$languageArray['invoice_no_code'][$language].':</td>
                            <td style="width:20%;">'.$invoiceNo.'</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td>'.$languageArray['date_code'][$language].':</td>
                            <td>'.$voucherDate.'</td>
                        </tr>
                    </table>

                    <table>
                        <tr class="table-border">
                            <td>'.$languageArray['date_code'][$language].'</td>
                            <td class="text-center">'.$languageArray['serial_no_code'][$language].'</td>
                            <td class="text-center">'.$languageArray['nett_weight_code'][$language].' (KG)</td>
                            <td class="text-center">'.$languageArray['price_code'][$language].' (RM)</td>
                            <!--td class="text-center">'.$languageArray['amount_code'][$language].' (RM)</td-->
                            <!--td class="text-center">'.$languageArray['tax_amount_code'][$language].' (RM)</td-->
                            <td class="text-center">'.$languageArray['total_amount_code'][$language].' (RM)</td>
                        </tr>

                ';
                foreach ($pvItems as $item) {
                    $amount = $unitPrice * $item['nett'];
                    $taxAmount = $amount * ($tax / 100);
                    $totalItemAmount = $amount + $taxAmount;

                    $message .= '<tr>
                            <td>'.$item['date'].'</td>
                            <td class="text-center">'.$item['serial_no'].'</td>
                            <td class="text-center">'.number_format($item['nett'], 2).'</td>
                            <td class="text-center">'.number_format($unitPrice, 2).'</td>
                            <!--td class="text-center">'.number_format($amount, 2).'</td-->
                            <!--td class="text-center">'.number_format($taxAmount, 2).'</td-->
                            <td class="text-center">'.number_format($totalItemAmount, 2).'</td>
                        </tr>';
                }

                $message .= '
                        <tr>
                            <td></td>
                            <td><b>Total</b></td>
                            <td class="text-center border-top">'.number_format($totalNettWeight, 2).'</td>
                            <td class="text-center border-top"></td>
                            <!--td class="text-center border-top">'.number_format($totalNettAmount, 2).'</td-->
                            <!--td class="text-center border-top">'.number_format($totalTaxAmount, 2).'</td-->
                            <td class="text-center border-top">'.number_format($totalAmount, 2).'</td>
                        </tr>
                    </table>

                    <div style="position:fixed; bottom:0; left:0; right:0; padding: 0 15mm;">
                        <table>
                            <tr>
                                <td>'.$languageArray['payment_date_code'][$language].':</td>
                                <td class="border-bottom" style="width: 200px;"></td>
                                <td></td>
                                <td>'.$languageArray['net_amount_code'][$language].' (RM):</td>
                                <td class="text-right border-bottom" style="width: 150px;">'.number_format($finalAmount, 2).'</td>
                            </tr>
                        </table>

                        <div class="footer-signatures">
                            <div>
                                <p style="padding-bottom: 30px;">'.$compname.'</p>
                                <div class="signature-line"></div>
                                <p style="margin-top: 5px;">'.$languageArray['authorised_signature_code'][$language].'</p>
                            </div>
                            <div>
                                <p style="padding-bottom: 30px;">'.$languageArray['kindly_acknowledge_receipt_code'][$language].'</p>
                                <div class="signature-line"></div>
                                <p style="margin-top: 5px;">'.$languageArray['received_by_code'][$language].'</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>';
            }
            
            echo json_encode(array(
                "status" => "success",
                "message" => $message,
                "paymentVoucherNo" => $paymentVoucherNo
            ));
        } else {
            echo json_encode(array(
                "status" => "failed",
                "message" => "Payment voucher not found"
            ));
        }
    }
} else {
    echo json_encode(array(
        "status" => "failed",
        "message" => "Missing parameters"
    ));
}
?>
