<?php
header("Content-Type: text/html; charset=UTF-8");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
ini_set('display_errors', 1);
require_once 'php/db_connect.php';
require_once 'php/lookup.php';

// Sample data - replace with actual DB query
$soNo = $_GET['so_no'] ?? 'PI0000001';
$date = $_GET['date'] ?? date('d/m/Y');
$time = $_GET['time'] ?? date('H:i:s');
$slipNo = $_GET['slip_no'] ?? 'S202602020015';
$vehicleNo = $_GET['vehicle_no'] ?? 'PPP8888';
$priceStatus = $_GET['price_status'] ?? 'FLOAT';
$weightBy = $_GET['weight_by'] ?? 'EEVEN KHO';
$pages = $_GET['pages'] ?? '1 - 1';

// Company info
$companyNameCn = '劳勿榴莲';
$companyName = 'RAUB DURIAN AGRO SDN. BHD.';
$companyAddress1 = 'NO. 9, TAMAN BUNGA RAYA, SUNGAI RUAN,';
$companyAddress2 = '27500 RAUB PAHANG. MALAYSIA.';
$companyTel = '+000000000000';
$companyTin = '';
$companyEmail = '';
$companyPhone = '+6016-776 9877';

// Bill To
$billToName = $_GET['bill_to_name'] ?? 'SYNCTRONIX TECHNOLOGY (M) SDN BHD';
$billToAddr1 = $_GET['bill_to_addr1'] ?? 'NO.34, Jalan Bagan 1, Taman Bagan,';
$billToAddr2 = $_GET['bill_to_addr2'] ?? '13400 Butterworth, Penang.';
$billToAddr3 = $_GET['bill_to_addr3'] ?? 'Malaysia.';
$billToAttn = $_GET['bill_to_attn'] ?? 'MR. EEVEN KHO';
$billToTel = $_GET['bill_to_tel'] ?? '012- 4135822';
$billToFax = $_GET['bill_to_fax'] ?? '';

// Delivery To
$deliverToName = $_GET['deliver_to_name'] ?? 'SYNCTRONIX TECHNOLOGY (M) SDN BHD';
$deliverToAddr1 = $_GET['deliver_to_addr1'] ?? 'NO.34, Jalan Bagan 1, Taman Bagan,';
$deliverToAddr2 = $_GET['deliver_to_addr2'] ?? '13400 Butterworth, Penang.';
$deliverToAddr3 = $_GET['deliver_to_addr3'] ?? 'Malaysia.';
$deliverToAttn = $_GET['deliver_to_attn'] ?? '';
$deliverToTel = $_GET['deliver_to_tel'] ?? '';
$deliverToFax = $_GET['deliver_to_fax'] ?? '';

// Footer data
$totalAmountWords = $_GET['total_words'] ?? 'THIRTY-FOUR THOUSAND FOUR HUNDRED';
$totalAmount = $_GET['total_amount'] ?? '34,400.00';
$bankerName = $_GET['banker_name'] ?? '';
$bankAccount = $_GET['bank_account'] ?? '';
$bankSwift = $_GET['bank_swift'] ?? '';

// Sample items
$items = [
    [
        'product' => 'MUSANG KING (猫山王)',
        'grade' => 'ZM',
        'unit' => 'KG',
        'bin' => '18',
        'weights' => [50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00],
        'qty' => 900.00,
        'uom' => 'BIN',
        'unit_price' => 15.00,
        'total_price' => 13500.00,
    ],
    [
        'product' => 'MUSANG KING (猫山王)',
        'grade' => 'CCCM',
        'unit' => 'KG',
        'bin' => '12',
        'weights' => [50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00],
        'qty' => 600.00,
        'uom' => 'BIN',
        'unit_price' => 12.00,
        'total_price' => 7200.00,
    ],
    [
        'product' => 'MUSANG KING (猫山王)',
        'grade' => 'AM',
        'unit' => 'KG',
        'bin' => '14',
        'weights' => [50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00],
        'qty' => 700.00,
        'uom' => 'BIN',
        'unit_price' => 11.00,
        'total_price' => 7700.00,
    ],
    [
        'product' => 'BLACK THORN (黑刺)',
        'grade' => 'BT',
        'unit' => 'KG',
        'bin' => '8',
        'weights' => [50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00],
        'qty' => 400.00,
        'uom' => 'BIN',
        'unit_price' => 15.00,
        'total_price' => 6000.00,
    ],
    [
        'product' => 'MUSANG KING (猫山王)',
        'grade' => 'ZM',
        'unit' => 'KG',
        'bin' => '18',
        'weights' => [50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00],
        'qty' => 900.00,
        'uom' => 'BIN',
        'unit_price' => 15.00,
        'total_price' => 13500.00,
    ],
    [
        'product' => 'MUSANG KING (猫山王)',
        'grade' => 'CCCM',
        'unit' => 'KG',
        'bin' => '12',
        'weights' => [50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00],
        'qty' => 600.00,
        'uom' => 'BIN',
        'unit_price' => 12.00,
        'total_price' => 7200.00,
    ],
    [
        'product' => 'MUSANG KING (猫山王)',
        'grade' => 'AM',
        'unit' => 'KG',
        'bin' => '14',
        'weights' => [50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00],
        'qty' => 700.00,
        'uom' => 'BIN',
        'unit_price' => 11.00,
        'total_price' => 7700.00,
    ],
    [
        'product' => 'BLACK THORN (黑刺)',
        'grade' => 'BT',
        'unit' => 'KG',
        'bin' => '8',
        'weights' => [50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00],
        'qty' => 400.00,
        'uom' => 'BIN',
        'unit_price' => 15.00,
        'total_price' => 6000.00,
    ],
    [
        'product' => 'MUSANG KING (猫山王)',
        'grade' => 'ZM',
        'unit' => 'KG',
        'bin' => '18',
        'weights' => [50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00],
        'qty' => 900.00,
        'uom' => 'BIN',
        'unit_price' => 15.00,
        'total_price' => 13500.00,
    ],
    [
        'product' => 'MUSANG KING (猫山王)',
        'grade' => 'CCCM',
        'unit' => 'KG',
        'bin' => '12',
        'weights' => [50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00],
        'qty' => 600.00,
        'uom' => 'BIN',
        'unit_price' => 12.00,
        'total_price' => 7200.00,
    ],
    [
        'product' => 'MUSANG KING (猫山王)',
        'grade' => 'AM',
        'unit' => 'KG',
        'bin' => '14',
        'weights' => [50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00],
        'qty' => 700.00,
        'uom' => 'BIN',
        'unit_price' => 11.00,
        'total_price' => 7700.00,
    ],
    [
        'product' => 'BLACK THORN (黑刺)',
        'grade' => 'BT',
        'unit' => 'KG',
        'bin' => '8',
        'weights' => [50.00,50.00,50.00,50.00,50.00,50.00,50.00,50.00],
        'qty' => 400.00,
        'uom' => 'BIN',
        'unit_price' => 15.00,
        'total_price' => 6000.00,
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Order - <?= htmlspecialchars($soNo) ?></title>
    <script src="https://unpkg.com/pagedjs/dist/paged.polyfill.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', Courier, monospace; font-size: 14px; color: #000; }

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
        .so-label { width: 80px; flex-shrink: 0; }
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
        .body-section { padding: 0; margin-top: 20px;}
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
                    <div class="company-cn"><?= $companyNameCn ?></div>
                    <div class="company-en"><?= htmlspecialchars($companyName) ?></div>
                    <div class="company-addr"><?= htmlspecialchars($companyAddress1) ?></div>
                    <div class="company-addr"><?= htmlspecialchars($companyAddress2) ?></div>
                    <div class="company-contact">Tel: <?= htmlspecialchars($companyTel) ?></div>
                    <div class="company-contact">E-INVOICE TIN No. : <?= htmlspecialchars($companyTin) ?>&nbsp;&nbsp;&nbsp;EMAIL : <?= htmlspecialchars($companyEmail) ?></div>
                </div>
            </div>
        </div>
        <div class="info-section">
            <div class="bill-to">
                <div class="section-title">BILL TO :</div>
                <div class="addr-name"><?= htmlspecialchars($billToName) ?></div>
                <div class="addr-line"><?= htmlspecialchars($billToAddr1) ?></div>
                <div class="addr-line"><?= htmlspecialchars($billToAddr2) ?></div>
                <div class="addr-line"><?= htmlspecialchars($billToAddr3) ?></div>
                <br>
                <div class="contact-row"><span class="contact-label">Attn</span><span class="contact-colon">:</span><span class="contact-value"><?= htmlspecialchars($billToAttn) ?></span></div>
                <div class="contact-row"><span class="contact-label">Tel</span><span class="contact-colon">:</span><span class="contact-value"><?= htmlspecialchars($billToTel) ?></span></div>
                <div class="contact-row"><span class="contact-label">Fax</span><span class="contact-colon">:</span><span class="contact-value"><?= htmlspecialchars($billToFax) ?></span></div>
            </div>
            <div class="deliver-to">
                <div class="section-title">DELIVERY TO :</div>
                <div class="addr-name"><?= htmlspecialchars($deliverToName) ?></div>
                <div class="addr-line"><?= htmlspecialchars($deliverToAddr1) ?></div>
                <div class="addr-line"><?= htmlspecialchars($deliverToAddr2) ?></div>
                <div class="addr-line"><?= htmlspecialchars($deliverToAddr3) ?></div>
                <br>
                <div class="contact-row"><span class="contact-label">Attn</span><span class="contact-colon">:</span><span class="contact-value"><?= htmlspecialchars($deliverToAttn) ?></span></div>
                <div class="contact-row"><span class="contact-label">Tel</span><span class="contact-colon">:</span><span class="contact-value"><?= htmlspecialchars($deliverToTel) ?></span></div>
                <div class="contact-row"><span class="contact-label">Fax</span><span class="contact-colon">:</span><span class="contact-value"><?= htmlspecialchars($deliverToFax) ?></span></div>
            </div>
            <div class="so-section">
                <div class="so-title"><span style="border-bottom: 1px solid black;">SALES ORDER</span></div>
                <div class="so-detail"><span class="so-label">SO No.</span><span class="so-colon">:</span><span class="so-value"><?= htmlspecialchars($soNo) ?></span></div>
                <div class="so-detail"><span class="so-label">Date</span><span class="so-colon">:</span><span class="so-value"><?= htmlspecialchars($date) ?></span></div>
                <div class="so-detail"><span class="so-label">Time</span><span class="so-colon">:</span><span class="so-value"><?= htmlspecialchars($time) ?></span></div>
                <div class="so-detail"><span class="so-label">Slip No</span><span class="so-colon">:</span><span class="so-value"><?= htmlspecialchars($slipNo) ?></span></div>
                <div class="so-detail"><span class="so-label">Vehicle No</span><span class="so-colon">:</span><span class="so-value"><?= htmlspecialchars($vehicleNo) ?></span></div>
                <div class="so-detail"><span class="so-label">Price Sta</span><span class="so-colon">:</span><span class="so-value"><?= htmlspecialchars($priceStatus) ?></span></div>
                <div class="so-detail"><span class="so-label">Weight by</span><span class="so-colon">:</span><span class="so-value"><?= htmlspecialchars($weightBy) ?></span></div>
                <div class="so-detail"><span class="so-label">Pages</span><span class="so-colon">:</span><span class="so-value"><?= htmlspecialchars($pages) ?></span></div>
            </div>
        </div>
        <table class="items" style="margin-top:8px;">
            <tr>
                <th style="width:4%;" class="border-top-bottom-dashed">NO</th>
                <th style="width:36%; text-align:left;" class="border-top-bottom-dashed">DESCRIPTION ITEM / GRADE</th>
                <th style="width:10%;" class="border-top-bottom-dashed">QTY/kg</th>
                <th style="width:8%;" class="border-top-bottom-dashed">UOM</th>
                <th style="width:18%;" class="border-top-bottom-dashed">UNIT PRICE (RM)</th>
                <th style="width:18%;" class="border-top-bottom-dashed">TOTAL PRICE (RM)</th>
            </tr>
        </table>
    </div>

    <!-- RUNNING FOOTER -->
    <div class="running-footer">
        <div class="footer-block">
            <div class="footer-separator"></div>
            <div class="footer-total-row">
                <div class="footer-total-words">RINGGIT MALAYSIA : <?= htmlspecialchars($totalAmountWords) ?></div>
                <div class="footer-total-amount">RM<?= htmlspecialchars($totalAmount) ?></div>
            </div>
            <div class="footer-note">* We confirm acceptance of the above item description billing with goods sold are not returnable.</div>
            <div class="footer-note">* All payment cheque &amp; cash should be crossed and made payable to '<b><?= htmlspecialchars($companyName) ?></b>'</div>
            <div class="footer-note">* Banker Name&nbsp;&nbsp;&nbsp;&nbsp;: <?= htmlspecialchars($bankerName) ?></div>
            <div class="footer-note">* Bank Account No: <?= htmlspecialchars($bankAccount) ?></div>
            <div class="footer-note">* Bank Swift Code: <?= htmlspecialchars($bankSwift) ?></div>
            <div class="footer-section-title">Remark :</div>
            <div class="footer-note">* We reserve the right to charge interest base on invoice date overdue bills at the rate of 2.5% per days and refer to payment Franz.</div>
            <div class="footer-note">* If the remaining payment for 30 Days is not fully paid, We have the right to recover all of the above description of goods.</div>
            <div class="footer-section-title">Notes :</div>
            <div class="footer-note">* This Invoice is generated by "<b><?= htmlspecialchars($companyName) ?></b>" Computer Administrators and does not require a signature.</div>
            <div class="footer-note">* If any concern &amp; required, kindly contact "<b><?= htmlspecialchars($companyName) ?></b>" Account Department (<?= htmlspecialchars($companyPhone) ?>)</div>
        </div>
    </div>

    <!-- BODY - Items Table -->
    <div class="body-section">
        <table class="items">
            <tbody>
                <?php
                foreach ($items as $index => $item):
                    $weightLines = '';
                    $chunks = array_chunk($item['weights'], 5);
                    foreach ($chunks as $chunk) {
                        $weightLines .= implode('&nbsp;&nbsp;', array_map(function($w) { return number_format($w, 2); }, $chunk)) . '<br>';
                    }
                ?>
                <tr style="break-inside:avoid; page-break-inside:avoid;">
                    <td style="text-align:center; border:0; vertical-align:top; padding-top:8px;"><?= $index + 1 ?></td>
                    <td style="text-align:left; border:0; vertical-align:top; padding-top:8px; ">
                        <span style="display:inline-block; width:220px; font-weight:bold;"><?= $item['product'] ?></span><span style="display:inline-block; width:80px;">UNIT : <?= $item['unit'] ?></span><br>
                        <span style="display:inline-block; width:220px;">GRADE : <?= $item['grade'] ?></span><span style="display:inline-block; width:80px;">BIN : <?= $item['bin'] ?></span><br>
                        <span style="font-size:10px;"><?= $weightLines ?></span>
                    </td>
                    <td style="text-align:center; border:0; vertical-align:top; padding-top:8px;"><?= number_format($item['qty'], 2) ?></td>
                    <td style="text-align:center; border:0; vertical-align:top; padding-top:8px;"><?= $item['uom'] ?></td>
                    <td style="text-align:center; border:0; vertical-align:top; padding-top:8px;">RM&nbsp;&nbsp;<?= number_format($item['unit_price'], 2) ?></td>
                    <td style="text-align:center; border:0; vertical-align:top; padding-top:8px;">RM<?= number_format($item['total_price'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Print Button -->
    <div id="printBtnWrapper" data-pagedjs-ignore style="position:fixed; bottom:20px; left:0; right:0; text-align:center; z-index:9999;">
        <button onclick="document.getElementById('printBtnWrapper').style.display='none'; document.title='<?= htmlspecialchars($soNo) ?>'; setTimeout(function(){ window.print(); document.getElementById('printBtnWrapper').style.display=''; }, 200);" style="background:#007bff; color:#fff; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; font-size:14px; box-shadow:0 2px 6px rgba(0,0,0,0.15);">🖨️ Print</button>
    </div>

    <script>
        window.PagedConfig = { auto: true };
    </script>

</body>
</html>
