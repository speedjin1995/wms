<?php
require_once 'db_connect.php';
require_once 'lookup.php';
require_once '../vendor/autoload.php'; 
use Mpdf\Mpdf;

session_start();
$company = $_SESSION['customer'];

// PDF file name for download
$fileName = "Report_" . date('Y-m-d') . ".pdf";

// Build search query
$searchQuery = "";

if(isset($_GET['fromDate']) && $_GET['fromDate'] != null && $_GET['fromDate'] != ''){
    $dateTime = DateTime::createFromFormat('d/m/Y', $_GET['fromDate']);
    $fromDate = $dateTime->format('d/m/Y');
    $fromDateTime = $dateTime->format('Y-m-d 00:00:00');
    $searchQuery .= " AND wholesales.created_datetime >= '".$fromDateTime."'";
}

if(isset($_GET['toDate']) && $_GET['toDate'] != null && $_GET['toDate'] != ''){
    $dateTime = DateTime::createFromFormat('d/m/Y', $_GET['toDate']);
    $toDate = $dateTime->format('d/m/Y');
    $toDateTime = $dateTime->format('Y-m-d 23:59:59');
    $searchQuery .= " AND wholesales.created_datetime <= '".$toDateTime."'";
}

if(isset($_GET['status']) && $_GET['status'] != null && $_GET['status'] != '' && $_GET['status'] != '-'){
    $searchQuery .= " AND wholesales.status = '".mysqli_real_escape_string($db, $_GET['status'])."'";
}

if(isset($_GET['product']) && $_GET['product'] != null && $_GET['product'] != '' && $_GET['product'] != '-'){
    $searchQuery .= " AND wholesales.product = '".mysqli_real_escape_string($db, $_GET['product'])."'";
}

if(isset($_GET['customer']) && $_GET['customer'] != null && $_GET['customer'] != '' && $_GET['customer'] != '-'){
    $searchQuery .= " AND wholesales.customer = '".mysqli_real_escape_string($db, $_GET['customer'])."'";
}

if(isset($_GET['supplier']) && $_GET['supplier'] != null && $_GET['supplier'] != '' && $_GET['supplier'] != '-'){
    $searchQuery .= " AND wholesales.supplier = '".mysqli_real_escape_string($db, $_GET['supplier'])."'";
}

if($_GET['vehicle'] != null && $_GET['vehicle'] != '' && $_GET['vehicle'] != '-'){
  if ($_GET['vehicle'] == 'UNKOWN NO'){
    if($_GET['otherVehicle'] != null && $_GET['otherVehicle'] != '' && $_GET['otherVehicle'] != '-'){
      $searchQuery .= " and wholesales.vehicle_no = '".mysqli_real_escape_string($db, $_GET['otherVehicle'])."'";
    }
  } else {
    $searchQuery .= " and wholesales.vehicle_no = '".mysqli_real_escape_string($db, $_GET['vehicle'])."'";
  }
}

if(isset($_GET['checkedBy']) && $_GET['checkedBy'] != null && $_GET['checkedBy'] != '' && $_GET['checkedBy'] != '-'){
  $searchQuery .= " and wholesales.checked_by = '".mysqli_real_escape_string($db, $_GET['checkedBy'])."'";
}

if(isset($_GET['weightedBy']) && $_GET['weightedBy'] != null && $_GET['weightedBy'] != '' && $_GET['weightedBy'] != '-'){
  $searchQuery .= " and wholesales.weighted_by = '".mysqli_real_escape_string($db, $_GET['weightedBy'])."'";
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
    $query = $db->query("SELECT wholesales.* FROM wholesales WHERE wholesales.id IN (".$ids.")");
}else{
    $query = $db->query("SELECT wholesales.* FROM wholesales WHERE wholesales.deleted = '0' AND wholesales.company = '$company'".$searchQuery);
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

    $gradeColumns = [];
    $allRows = [];
    
    // First pass: collect all data and unique grades
    if ($query->num_rows > 0) { 
        $count = 1;
        while ($row = $query->fetch_assoc()) { 
            $createdDateTime = new DateTime($row['created_datetime']);
            $formattedDate = $createdDateTime->format('d/m/Y');
            $formattedTime = $createdDateTime->format('H:i:s');

            // Reserve for Weighing Details
            $weighingDetails = json_decode($row['weight_details'], true);
            $arrangedDetails = arrangeByGrade($weighingDetails);

            $totalWeight = 0;
            $totalBinWeight = 0;
            $totalRejectWeight = 0;
            $actualWeight = 0;
            $totalPrice = 0;
            $actualPrice = 0;
            $gradeWeights = [];
            
            foreach ($arrangedDetails as $grade => $details) {
                $gradeColumns[] = 'Grade '.$grade;
                $gradeNettWeight = 0;
                foreach ($details as $detail) {
                    $gradeNettWeight += floatval($detail['net'] ?? 0);

                    $totalWeight += floatval($detail['gross'] ?? 0);
                    $totalBinWeight += floatval($detail['tare'] ?? 0);
                    $totalRejectWeight += floatval($detail['reject'] ?? 0);

                    if ($detail['fixedfloat'] == 'fixed'){
                        $totalPrice += floatval($detail['price'] ?? 0);
                        $actualPrice += floatval($detail['price'] ?? 0);
                    }else{
                        $totalPrice += floatval($detail['gross'] ?? 0) * floatval($detail['price'] ?? 0);
                        $actualPrice += (floatval($detail['net'] ?? 0) - floatval($detail['reject'] ?? 0)) * floatval($detail['price'] ?? 0);
                    }
                }
                $gradeWeights['Grade '.$grade] = $gradeNettWeight;
            }

            $actualWeight = $totalWeight - $totalBinWeight - $totalRejectWeight;

            $allRows[] = [
                'count' => $count,
                'formattedDate' => $formattedDate,
                'formattedTime' => $formattedTime,
                'serial_no' => $row['serial_no'],
                'security_bills' => $row['security_bills'],
                'po_no' => $row['po_no'],
                'status' => $row['status'],
                'customer' => $row['customer'],
                'other_customer' => $row['other_customer'],
                'supplier' => $row['supplier'],
                'other_supplier' => $row['other_supplier'],
                'product' => searchProductNameById($row['product'], $db),
                'gradeWeights' => $gradeWeights,
                'totalWeight' => $totalWeight,
                'totalBinWeight' => $totalBinWeight,
                'total_reject' => $totalRejectWeight,
                'actualWeight' => $actualWeight,
                'totalPrice' => $totalPrice,
                'actualPrice' => $actualPrice,
                'vehicle_no' => $row['vehicle_no'],
                'driver' => $row['driver'],
                'weighted_by' => searchUserNameById($row['weighted_by'], $db)
            ];

            $count++;
        } 
    }

    $gradeColumns = array_unique($gradeColumns);
    
    // Calculate subtotals
    $subtotals = ['gradeWeights' => [], 'totalWeight' => 0, 'totalBinWeight' => 0, 'total_reject' => 0, 'actualWeight' => 0, 'totalPrice' => 0, 'actualPrice' => 0];
    foreach ($allRows as $rowData) {
        foreach ($gradeColumns as $gradeCol) {
            if (!isset($subtotals['gradeWeights'][$gradeCol])) $subtotals['gradeWeights'][$gradeCol] = 0;
            $subtotals['gradeWeights'][$gradeCol] += ($rowData['gradeWeights'][$gradeCol] ?? 0);
        }
        $subtotals['totalWeight'] += $rowData['totalWeight'];
        $subtotals['totalBinWeight'] += $rowData['totalBinWeight'];
        $subtotals['total_reject'] += $rowData['total_reject'];
        $subtotals['actualWeight'] += $rowData['actualWeight'];
        $subtotals['totalPrice'] += $rowData['totalPrice'];
        $subtotals['actualPrice'] += $rowData['actualPrice'];
    }
    
    // Second pass: generate content with consistent grade columns
    $content = '';
    if (!empty($allRows)) {
        foreach ($allRows as $rowData) {
            $content .= '<tr>';
            $content .= '<td>'.$rowData['count'].'</td>';
            $content .= '<td>'.$rowData['formattedDate'].'</td>';
            $content .= '<td>'.$rowData['formattedTime'].'</td>';
            $content .= '<td>'.$rowData['serial_no'].'</td>';
            // $content .= '<td>'.$rowData['po_no'].'</td>';

            if ($_GET['status'] == 'RECEIVING') {
                $content .= '<td>'.$rowData['security_bills'].'</td>';
            }

            $content .= '<td>'.(($rowData['status'] == 'DISPATCH') ? searchCustomerNameById($rowData['customer'], $rowData['other_customer'],$db) : searchSupplierNameById($rowData['supplier'], $rowData['other_supplier'], $db)) .'</td>';

            // Output grade columns in correct order
            foreach ($gradeColumns as $gradeCol) {
                $content .= '<td>'.number_format(($rowData['gradeWeights'][$gradeCol] ?? 0), 2).'</td>';
            }

            $content .= '<td>'.number_format($rowData['totalWeight'], 2).'</td>';
            $content .= '<td>'.number_format($rowData['totalBinWeight'], 2).'</td>';
            $content .= '<td>'.number_format($rowData['total_reject'], 2).'</td>';
            $content .= '<td>'.number_format($rowData['actualWeight'], 2).'</td>';
            $content .= '<td>'.number_format($rowData['totalPrice'], 2).'</td>';
            $content .= '<td>'.number_format($rowData['actualPrice'], 2).'</td>';
            $content .= '<td>'.$rowData['vehicle_no'].'</td>';
            $content .= '<td>'.$rowData['driver'].'</td>';
            $content .= '<td>'.$rowData['weighted_by'].'</td>';
            $content .= '</tr>';
        }
    } else { 
        $content .= '<tr><td colspan="15">No records found...</td></tr>';
    }

    if ($_GET['status'] == 'DISPATCH') {
        $status = 'DISPATCH';
    } else if ($_GET['status'] == 'RECEIVING') {
        $status = 'RECEIVING';
    } else {
        $status = 'SALE BALANCE';
    }

    // Set PDF header with logo and dynamic report title
    $html = '
        <html>
        <head>
            <title>Weekly Monthly Sales Report Weighing</title>
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
                .company-info { font-size: 16px; margin-bottom: 10px; }
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
            <div class="header mb-1">
                <table style="width: 100%; border: none;">
                    <tr>
                        <td style="width: 50%; border: none; text-align: left; padding: 0; font-size: 14px;">
                            <div class="fw-bold">WEEKLY MONTHLY '.$status.' REPORT WEIGHING</div>
                        </td>
                        <td style="width: 50%; border: none; text-align: right; padding: 0; font-size: 14px;">
                            <div class="fw-bold">From Date: '.$fromDate.' - '.$toDate.'</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 50%; border: none; text-align: left; padding: 0; font-size: 14px;">
                            <!--div class="fw-bold">From Customer: '.($_GET['status'] == 'DISPATCH' || $_GET['status'] == 'SALE-BAL' ? searchCustomerNameById($_GET['customer'], '', $db) : searchSupplierNameById($_GET['supplier'], '', $db)).'</div-->
                        </td>
                        <td style="width: 50%; border: none; text-align: right; padding: 0; font-size: 14px;">
                            <div class="fw-bold">Weight Status: '.$status.'</div>
                        </td>
                    </tr>
                </table>
            </div>
            <hr class="border-dark">
            <div class="table-container">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Weigh Slip No.</th>
                            <!--th>'.$status.' No.</th-->';

                            if ($_GET['status'] == 'RECEIVING') {
                                $html .= '
                                    <th>Security Bill</th>
                                ';
                            }

                            $html .= '
                            <th>'.($_GET['status'] == 'DISPATCH' || $_GET['status'] == 'SALE-BAL' ? 'Customer' : 'Supplier').' Name</th>';

                            if (!empty($gradeColumns) && count($gradeColumns) > 0){
                                foreach ($gradeColumns as $gradeCol){
                                    $html .= '<th>'.$gradeCol.'</th>';
                                }
                            }

                            $html .= '
                            <th>Total Weight</th>
                            <th>Total Bin Weight</th>
                            <th>Reject Weight</th>
                            <th>Actual Weight</th>
                            <th>Total Price (RM)</th>
                            <th>Actual Price (RM)</th>
                            <th>Vehicle No.</th>
                            <th>Driver Name</th>
                            <th>Weigh By</th>
                        </tr>
                    </thead>
                    <tbody>
                        '.$content.'
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: bold; background-color: #f0f0f0;">
                            <td colspan="'.($_GET['status'] == 'RECEIVING' ? '6' : '5').'">SUBTOTAL</td>';
                            
                            foreach ($gradeColumns as $gradeCol) {
                                $html .= '<td>'.number_format($subtotals['gradeWeights'][$gradeCol], 2).'</td>';
                            }
                            
                            $html .= '
                            <td>'.number_format($subtotals['totalWeight'], 2).'</td>
                            <td>'.number_format($subtotals['totalBinWeight'], 2).'</td>
                            <td>'.number_format($subtotals['total_reject'], 2).'</td>
                            <td>'.number_format($subtotals['actualWeight'], 2).'</td>
                            <td>'.number_format($subtotals['totalPrice'], 2).'</td>
                            <td>'.number_format($subtotals['actualPrice'], 2).'</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
    ';

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

function arrangeByGrade($weighingDetails) {
    $arranged = [];
    $earliest_time = null;
    $latest_time = null;
    
    if(isset($weighingDetails) && !empty($weighingDetails)) {
        foreach($weighingDetails as $detail) {
            $grade = $detail['grade'] ?? 'Unknown';
            if(!isset($arranged[$grade])) {
                $arranged[$grade] = [];
            }
            $arranged[$grade][] = $detail;
        }
    }
    
    return $arranged;
}
exit;
?>
