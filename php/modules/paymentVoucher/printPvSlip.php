<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../../db_connect.php';
require_once '../../lookup.php';
$_POST = array_merge($_GET, $_POST);

if(isset($_POST['pvId'])){
    $company = $_SESSION['customer'];
    $pvId = $_POST['pvId'];
    $paymentVoucherNo = null;
    
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
        $compphone = $row['phone'];
    }
    
    // Get payment voucher details
    $sql = "SELECT * FROM payment_vouchers WHERE id=?";
    if ($stmt = $db->prepare($sql)) {
        $stmt->bind_param('s', $pvId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $supplierName = searchSupplierNameById($row['supplier_id'], '', $db);
            $voucherDate = date('d/m/Y', strtotime($row['voucher_date']));
            $invoiceNo = $row['invoice_no'] ?? '';
            $paymentVoucherNo = $row['voucher_no'] ?? '';

            $isPdfDownload = (isset($_POST['printType']) && $_POST['printType'] == 'exportDownload');

            // Format date to Malay month and year
            $malayMonths = array(
                1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MAC', 4 => 'APRIL',
                5 => 'MEI', 6 => 'JUN', 7 => 'JULAI', 8 => 'OGOS',
                9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DISEMBER'
            );
            $month = date('n', strtotime($row['voucher_date']));
            $year = date('Y', strtotime($row['voucher_date']));
            $formatVoucherDate = $malayMonths[$month] . ' ' . $year;
            
            $accountNo = $row['account_no'] ?? '';
            $unitPrice = floatval($row['unit_price']);
            $totalNettWeight = floatval($row['total_nett_weight']);
            $totalAmount = floatval(str_replace('RM ', '', $row['total_amount']));
            $totalDeductions = floatval($row['deduction_amount']);
            $totalAdditions = floatval($row['addition_amount']);
            $finalAmount = floatval($row['final_amount']);
            
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
                    .text-right {
                        text-align: right;
                    }
                    .text-left {
                        text-align: left;
                    }
                    .total-row {
                        font-weight: bold;
                    }
                    .footer {
                        margin-top: 15px;
                        font-size: 10px;
                    }
                    .signature {
                        margin-top: 50px;
                        display: flex;
                        flex-direction: column;
                        align-items: flex-end;
                    }
                    .signature p {
                        text-align: left;
                        width: 200px;
                        margin: 0;
                    }
                    .signature-line {
                        border-top: 1px solid #000;
                        width: 200px;
                        margin-top: 50px;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h3>'.$compname.' (No. Daftar: '.$compreg.')</h3>
                    <p>'.$compaddress.'</p>
                    <p>TEL: '.$compphone.'</p>
                </div>
                
                <div class="title">BAUCER BAYARAN</div>
                
                <div class="info-row">
                    <div class="info-item">
                        <span class="info-label">NAMA :</span>
                        <span class="info-value">'.$supplierName.'</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">TARIKH :</span>
                        <span class="info-value">'.$voucherDate.'</span>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-item">
                        <span class="info-label">NO AKAUN :</span>
                        <span class="info-value">'.$invoiceNo.'</span>
                    </div>
                    <div class="info-item" style="visibility: hidden;">
                        <span class="info-label">TARIKH :</span>
                        <span class="info-value">'.$voucherDate.'</span>
                    </div>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>BIL</th>
                            <th>PERKARA</th>
                            <th>UNIT (UNIT/KG)</th>
                            <th>HARGA (RM)</th>
                            <th>JUMLAH (RM)</th>
                        </tr>
                    </thead>
                    <tbody>';

            $bil = 1;
            $message .= '
                <tr>
                    <td class="text-center">'.$bil.'</td>
                    <td class="text-left">BTS '.$formatVoucherDate.'</td>
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
                            <td colspan="3" class="text-left" style="border-right:none"><strong>NO AKAUN BANK : '.$accountNo.'</strong></td>
                            <td class="text-right" style="border-left:none"><strong>JUMLAH (RM)</strong></td>
                            <td class="text-center"><strong>RM'.number_format($finalAmount, 2).'</strong></td>
                        </tr>
                    </tfoot>
                </table>
                
                <div class="signature">
                    <p>DITERIMA OLEH :</p>
                    <div class="signature-line"></div>
                </div>
            </body>
            </html>';
            
            
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
