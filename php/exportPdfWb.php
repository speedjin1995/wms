<?php
require_once 'db_connect.php';
require_once 'lookup.php';
require_once '../vendor/autoload.php'; 
use Mpdf\Mpdf;

session_start();
$company = $_SESSION['customer'];

// PDF file name for download
$fileName = "WB_Report_" . date('Y-m-d') . ".pdf";

// Build search query
$searchQuery = "";

if($_GET['fromDate'] != null && $_GET['fromDate'] != ''){
  $dateTime = DateTime::createFromFormat('d/m/Y', $_GET['fromDate']);
  $fromDateTime = $dateTime->format('Y-m-d 00:00:00');
  $searchQuery .= " and Weight.transaction_date >= '".$fromDateTime."'";
}

if($_GET['toDate'] != null && $_GET['toDate'] != ''){
  $dateTime = DateTime::createFromFormat('d/m/Y', $_GET['toDate']);
  $toDateTime = $dateTime->format('Y-m-d 23:59:59');
	$searchQuery .= " and Weight.transaction_date <= '".$toDateTime."'";
}

if($_GET['transactionStatus'] != null && $_GET['transactionStatus'] != '' && $_GET['transactionStatus'] != '-'){
  $searchQuery .= " and Weight.transaction_status = '".$_GET['transactionStatus']."'";
}

if($_GET['product'] != null && $_GET['product'] != '' && $_GET['product'] != '-'){
  $searchQuery .= " and Weight.product_name = '".$_GET['product']."'";
}

if($_GET['customer'] != null && $_GET['customer'] != '' && $_GET['customer'] != '-'){
  $searchQuery .= " and Weight.customer_name = '".$_GET['customer']."'";
}

if($_GET['supplier'] != null && $_GET['supplier'] != '' && $_GET['supplier'] != '-'){
  $searchQuery .= " and Weight.supplier_name = '".$_GET['supplier']."'";
}

if($_GET['vehicle'] != null && $_GET['vehicle'] != '' && $_GET['vehicle'] != '-'){
  $searchQuery .= " and Weight.lorry_plate_no1 = '".$_GET['vehicle']."'";
}

if($_GET['status'] != null && $_GET['status'] != '' && $_GET['status'] != '-'){
  if($_GET['status'] == 'Pending'){
    $searchQuery .= " and Weight.is_complete = 'N' AND Weight.is_cancel <> 'Y'";
  }else{
    $searchQuery .= " and Weight.is_complete = 'Y' AND Weight.is_cancel <> 'Y'";
  }
}

if($_GET['transactionId'] != null && $_GET['transactionId'] != '' && $_GET['transactionId'] != '-'){
  $searchQuery .= " and Weight.transaction_id like '%".$_GET['transactionId']."%'";
}

$isMulti = '';
if(isset($_GET['isMulti']) && $_GET['isMulti'] != null && $_GET['isMulti'] != '' && $_GET['isMulti'] != '-'){
    $isMulti = $_GET['isMulti'];
}

// Get Company Detail
$companyDetail = searchCompanyById($company, $db);

// Fetch records from database
if($isMulti == 'Y'){
    if(isset($_GET['ids']) && $_GET['ids'] != null && $_GET['ids'] != '' && $_GET['ids'] != '-'){
        $ids = $_GET['ids'];
    }
    $query = $db->query("SELECT Weight.* FROM Weight WHERE Weight.id IN (".$ids.")");
}else{
    $query = $db->query("SELECT Weight.* FROM Weight WHERE Weight.status = '0' AND Weight.company = '$company'".$searchQuery);
}

try {
    // Initialize mPDF with a custom temporary directory
    $mpdfConfig = [
        'mode' => 'utf-8',
        'format' => 'A4-L',
        'tempDir' => sys_get_temp_dir(),
        'margin_left' => 5,
        'margin_right' => 5,
        'margin_top' => 5,
        'margin_bottom' => 5
    ];
    $mpdf = new Mpdf($mpdfConfig);

    $content = '';
    $allRows = [];
    
    // First pass: collect all data
    if ($query->num_rows > 0) { 
        while ($row = $query->fetch_assoc()) { 
            $allRows[] = $row;
        } 
    }
    
    // Arrange by customer or supplier
    $arrangedData = arrangeByCustomerOrSupplier($allRows, $_GET['transactionStatus']);
    
    // Set PDF header with logo and dynamic report title
    $html = '
        <html>
        <head>
            <title>Weekly Monthly Dispatch Report Weighing</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 10px; margin: 0; padding: 0; }
                .container-fluid { width: 100%; padding: 0; }
                .row { display: flex; flex-wrap: wrap; }
                .col-6 { flex: 0 0 50%; max-width: 50%; }
                .mb-1 { margin-bottom: 0.25rem; }
                .mb-2 { margin-bottom: 0.5rem; }
                .fw-bold { font-weight: bold; }
                .text-muted { color: #6c757d; }
                .text-end { text-align: right; }
                .border-dark { border-color: #343a40; }
                .table { width: 100%; margin-bottom: 1rem; color: #212529; }
                .table-bordered { border: 1px solid #dee2e6; }
                .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
                .header { font-size: 18px; margin-bottom: 20px; }
                .company-info { font-size: 14px; margin-bottom: 10px; }
                .table-container { margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; font-size: 9px; }
                th, td { border: 1px solid black; padding: 2px; text-align: center; }
                th { background-color: #f0f0f0; font-weight: bold; }
                hr { margin: 1rem 0; color: inherit; background-color: currentColor; border: 0; opacity: 0.25; }
                hr:not([size]) { height: 1px; }
            </style>
        </head>
        <body class="container-fluid">
            <div class="company-info mb-1">
                <div class="fw-bold">'.$companyDetail['name'].'</div>
                <div class="text-muted">
                    <div>'.$companyDetail['address'].'</div>
                    <div>'.$companyDetail['address2'].'</div>
                    <div>'.$companyDetail['address3'].'</div>
                    <div>'.$companyDetail['address4'].'</div>
                </div>
            </div>
            <hr class="border-dark">
    ';
    
    // Generate grouped sections
    $groupIndex = 0;
    $totalGroups = count($arrangedData);
    foreach($arrangedData as $customerSupplier => $rows) {
        $html .= '
            <div class="header mb-1">
                <table style="width: 100%; border: none;">
                    <tr>
                        <td style="border: none; text-align: left; padding: 0 0 5px 0; font-size: 14px;">
                            <div class="fw-bold">WEEKLY MONTHLY '.($_GET['transactionStatus'] == 'Sales' ? 'DISPATCH' : 'RECEIVING').' REPORT WEIGHING</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: none; text-align: left; font-size: 12px;">
                            <div>'.($_GET['transactionStatus'] == 'Sales' ? 'TO CUSTOMER' : 'FROM SUPPLIER').': '.$customerSupplier.'</div>
                        </td>
                        <td style="border: none; text-align: left; font-size: 12px;">
                            <div>From Date: '.$_GET['fromDate'].' - '.$_GET['toDate'].'</div>
                        </td>
                    </tr>
                </table>
            </div>
            <hr class="border-dark">
            <div class="table-container">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>DATE</th>
                            <th>TIME</th>
                            <th>WEIGHING SLIP NO</th>
                            <th>'.($_GET['transactionStatus'] == 'Sales' ? 'DELIVERY' : 'PURCHASE').' No.</th>';

                            if ($_GET['transactionStatus'] == 'Purchase') {
                                $html .= '
                                    <th>SEC BILL NO</th>
                                ';
                            }

                            $html .= '
                            <th>PRODUCT DESCRIPTION</th>
                            <th>VEHICLE NO</th>
                            <th>IN WEIGHT (KG)</th>
                            <th>IN DATE/TIME</th>
                            <th>OUT WEIGHT (KG)</th>
                            <th>OUT DATE/TIME</th>
                            <th>REDUCE WEIGHT (KG)</th>
                            <th>NETT WEIGHT (KG)</th>
                            <th>'.($_GET['transactionStatus'] == 'Sales' ? 'ORDER' : 'SUPPLY').' WEIGHT (KG)</th>
                            <th>VARIANCE (KG)</th>
                            <th>VARIANCE (%)</th>
                            <th>DRIVER NAME</th>
                            <th>DRIVER IC</th>
                            <th>WEIGH BY</th>
                            <th>MODIFIED BY</th>
                            <th>CHECKED BY</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        $count = 1;
        $subtotal_in = 0;
        $subtotal_out = 0;
        $subtotal_reduce = 0;
        $subtotal_nett = 0;
        $subtotal_supply = 0;
        $subtotal_variance = 0;
        
        foreach($rows as $row) {
            $transaction_date = new DateTime($row['transaction_date']);
            $formattedDate = $transaction_date->format('d/m/Y');
            $formattedTime = $transaction_date->format('H:i:s');
            
            $subtotal_in += $row['gross_weight1'];
            $subtotal_out += $row['tare_weight1'];
            $subtotal_reduce += $row['reduce_weight'];
            $subtotal_nett += $row['final_weight'];
            $subtotal_supply += ($row['transaction_status'] == 'Sales' ? $row['order_weight'] : $row['supplier_weight']);
            $subtotal_variance += $row['weight_different'];

            $html .= '
                <tr>
                    <td>'.$count.'</td>
                    <td>'.$formattedDate.'</td>
                    <td>'.$formattedTime.'</td>
                    <td>'.$row['transaction_id'].'</td>
                    <td>'.($row['transaction_status'] == 'Sales' ? $row['delivery_no'] : $row['purchase_order']).'</td>';

                    if ($row['transaction_status'] == 'Purchase') {
                        $html .= '<td></td>';
                    }

                    $html .= '
                    <td>'.$row['product_name'].'</td>
                    <td>'.$row['lorry_plate_no1'].'</td>
                    <td>'.number_format($row['gross_weight1'], 2).'</td>
                    <td>'.$row['gross_weight1_date'].'</td>
                    <td>'.number_format($row['tare_weight1'], 2).'</td>
                    <td>'.$row['tare_weight1_date'].'</td>
                    <td>'.number_format($row['reduce_weight'], 2).'</td>
                    <td>'.number_format($row['final_weight'], 2).'</td>
                    <td>'.number_format(($row['transaction_status'] == 'Sales' ? $row['order_weight'] : $row['supplier_weight']), 2).'</td>
                    <td>'.number_format($row['weight_different'], 2).'</td>
                    <td>'.$row['weight_different_perc'].'</td>
                    <td>'.$row['driver_name'].'</td>
                    <td>'.searchDriverIcByDriverName($row['driver_name'], $company, $db).'</td>
                    <td>'.searchUserNameById($row['created_by'], $db).'</td>
                    <td>'.searchUserNameById($row['modified_by'], $db).'</td>
                    <td></td>
                </tr>
            ';
            $count++;
        }
        
        // Subtotal row
        $html .= '
            <tr style="font-weight: bold;">
                <td colspan="'.($_GET['transactionStatus'] == 'Purchase' ? '8' : '7').'" style="text-align: right;">SUBTOTAL</td>
                <td>'.number_format($subtotal_in, 2).'</td>
                <td></td>
                <td>'.number_format($subtotal_out, 2).'</td>
                <td></td>
                <td>'.number_format($subtotal_reduce, 2).'</td>
                <td>'.number_format($subtotal_nett, 2).'</td>
                <td>'.number_format($subtotal_supply, 2).'</td>
                <td>'.number_format($subtotal_variance, 2).'</td>
                <td colspan="6"></td>
            </tr>
        ';
        
        $html .= '
                    </tbody>
                </table>
            </div>
        ';
        
        // Add hr only if not last row
        if($groupIndex < $totalGroups - 1) {
            $html .= '<hr class="border-dark">';
        }
        
        $groupIndex++;
    }

    $html .= '
        </body>
        </html>
    ';

    // echo $html;die;

    // Write PDF content
    $mpdf->WriteHTML($html);

    // Output to browser
    $mpdf->Output($fileName, 'D');
} catch (\Mpdf\MpdfException $e) {
    echo $e->getMessage();
}

function arrangeByCustomerOrSupplier($data, $status) {
    $arranged = [];
    
    if(isset($data) && !empty($data)) {
        foreach($data as $row) {
            $key = ($status == 'Sales') ? $row['customer_name'] : $row['supplier_name'];
            if(!isset($arranged[$key])) {
                $arranged[$key] = [];
            }
            $arranged[$key][] = $row;
        }
    }
    
    return $arranged;
}
exit;
?>
