<?php
require_once 'db_connect.php';
require_once 'lookup.php';
require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();
$company = $_SESSION['customer'];

// Excel file name for download
$fileName = "WB_Report_" . date('Y-m-d') . ".xlsx";

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

$allRows = [];

// Collect all data
if ($query->num_rows > 0) { 
    while ($row = $query->fetch_assoc()) { 
        $allRows[] = $row;
    } 
}

// Arrange by customer or supplier
$result = arrangeByCustomerOrSupplier($allRows, $_GET['transactionStatus']);
$arrangedData = $result['data'];
$dateRanges = $result['dateRanges'];

// Create a new Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$rowIndex = 1;

// Company header
$sheet->setCellValue('A'.$rowIndex, $companyDetail['name']);
$sheet->getStyle('A'.$rowIndex)->getFont()->setBold(true);
$rowIndex++;
$sheet->setCellValue('A'.$rowIndex, $companyDetail['address']);
$rowIndex++;
$sheet->setCellValue('A'.$rowIndex, $companyDetail['address2']);
$rowIndex++;
$sheet->setCellValue('A'.$rowIndex, $companyDetail['address3']);
$rowIndex++;
$sheet->setCellValue('A'.$rowIndex, $companyDetail['address4']);
$rowIndex += 2;

// Generate grouped sections
foreach($arrangedData as $status => $customerSuppliers) {
    if ($status == 'Sales') {
        $reportType = 'DISPATCH';
    } elseif ($status == 'Purchase') {
        $reportType = 'RECEIVING';
    } elseif ($status == 'Local') {
        $reportType = 'INTERNAL TRANSFER';
    } elseif ($status == 'Misc') {
        $reportType = 'MISCELLANEOUS';
    } else {
        $reportType = strtoupper($status);
    }

    // Header
    $sheet->setCellValue('A'.$rowIndex, 'WEEKLY MONTHLY '.$reportType.' REPORT WEIGHING');
    $sheet->getStyle('A'.$rowIndex)->getFont()->setBold(true);
    $rowIndex++;
    foreach($customerSuppliers as $customerSupplier => $rows) {
        $key = $status.'_'.$customerSupplier;
        $fromDate = date('d/m/Y', strtotime($dateRanges[$key]['from']));
        $toDate = date('d/m/Y', strtotime($dateRanges[$key]['to']));
        
        // Column headers
        $headers = ['NO', 'DATE', 'TIME', 'WEIGHING SLIP NO', ($status == 'Sales' || $status == 'Misc' ? 'DELIVERY' : 'PURCHASE').' No.'];
        
        if ($status == 'Purchase') {
            $headers[] = 'SEC BILL NO';
        }
        
        $headers = array_merge($headers, [
            'PRODUCT DESCRIPTION', 'VEHICLE NO', 'IN WEIGHT (KG)', 'IN DATE/TIME', 
            'OUT WEIGHT (KG)', 'OUT DATE/TIME', 'REDUCE WEIGHT (KG)', 'NETT WEIGHT (KG)',
            ($status == 'Sales' || $status == 'Misc' ? 'ORDER' : 'SUPPLY').' WEIGHT (KG)',
            'VARIANCE (KG)', 'VARIANCE (%)', 'DRIVER NAME', 'DRIVER IC', 
            'WEIGH BY', 'MODIFIED BY', 'CHECKED BY'
        ]);
        
        $lastCol = chr(64 + count($headers));
        
        // Line above header
        $sheet->getStyle('A'.$rowIndex.':'.$lastCol.$rowIndex)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->setCellValue('A'.$rowIndex, ($status == 'Sales' || $status == 'Misc' ? 'TO CUSTOMER' : 'FROM SUPPLIER').': '.$customerSupplier);
        $sheet->setCellValue('B'.$rowIndex, 'From Date: '.$fromDate.' - '.$toDate);
        $rowIndex += 2;
        
        // Line above table headers
        $sheet->getStyle('A'.$rowIndex.':'.$lastCol.$rowIndex)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        $sheet->fromArray($headers, NULL, 'A'.$rowIndex);
        $sheet->getStyle('A'.$rowIndex.':'.$lastCol.$rowIndex)->getFont()->setBold(true);
        $rowIndex++;
        
        // Data rows
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
            $subtotal_supply += ($row['transaction_status'] == 'Sales' || $row['transaction_status'] == 'Misc' ? $row['order_weight'] : $row['supplier_weight']);
            $subtotal_variance += $row['weight_different'];
            
            $lineData = [
                $count,
                $formattedDate,
                $formattedTime,
                $row['transaction_id'],
                ($row['transaction_status'] == 'Sales' || $row['transaction_status'] == 'Misc' ? $row['delivery_no'] : $row['purchase_order'])
            ];
            
            if ($row['transaction_status'] == 'Purchase') {
                $lineData[] = '';
            }
            
            $lineData = array_merge($lineData, [
                $row['product_name'],
                $row['lorry_plate_no1'],
                number_format($row['gross_weight1'], 2),
                $row['gross_weight1_date'],
                number_format($row['tare_weight1'], 2),
                $row['tare_weight1_date'],
                number_format($row['reduce_weight'], 2),
                number_format($row['final_weight'], 2),
                number_format(($row['transaction_status'] == 'Sales' || $row['transaction_status'] == 'Misc' ? $row['order_weight'] : $row['supplier_weight']), 2),
                number_format($row['weight_different'], 2),
                $row['weight_different_perc'],
                $row['driver_name'],
                searchDriverIcByDriverName($row['driver_name'], $company, $db),
                searchUserNameById($row['created_by'], $db),
                searchUserNameById($row['modified_by'], $db),
                ''
            ]);
            
            $sheet->fromArray($lineData, NULL, 'A'.$rowIndex);
            $rowIndex++;
            $count++;
        }
        
        // Subtotal row
        $inWeightCol = $status == 'Purchase' ? 'I' : 'H';
        $outWeightCol = $status == 'Purchase' ? 'K' : 'J';
        $reduceWeightCol = $status == 'Purchase' ? 'M' : 'L';
        $nettWeightCol = $status == 'Purchase' ? 'N' : 'M';
        $supplyWeightCol = $status == 'Purchase' ? 'O' : 'N';
        $varianceCol = $status == 'Purchase' ? 'P' : 'O';
        
        $sheet->setCellValue('A'.$rowIndex, 'SUBTOTAL');
        $sheet->getStyle('A'.$rowIndex)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue($inWeightCol.$rowIndex, number_format($subtotal_in, 2));
        $sheet->setCellValue($outWeightCol.$rowIndex, number_format($subtotal_out, 2));
        $sheet->setCellValue($reduceWeightCol.$rowIndex, number_format($subtotal_reduce, 2));
        $sheet->setCellValue($nettWeightCol.$rowIndex, number_format($subtotal_nett, 2));
        $sheet->setCellValue($supplyWeightCol.$rowIndex, number_format($subtotal_supply, 2));
        $sheet->setCellValue($varianceCol.$rowIndex, number_format($subtotal_variance, 2));
        $sheet->getStyle('A'.$rowIndex.':'.$lastCol.$rowIndex)->getFont()->setBold(true);
        $rowIndex++;
        
        $rowIndex += 2;
    }
}

// Create a writer object
$writer = new Xlsx($spreadsheet);

// Output to browser
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$fileName.'"');
header('Cache-Control: max-age=0');

// Save the spreadsheet
$writer->save('php://output');

function arrangeByCustomerOrSupplier($data, $status) {
    $arranged = [];
    $dateRanges = [];
    
    if(isset($data) && !empty($data)) {
        foreach($data as $row) {
            $statusKey = $row['transaction_status'];
            $customerSupplierKey = ($statusKey == 'Sales') ? $row['customer_name'] : $row['supplier_name'];
            
            if(!isset($arranged[$statusKey])) {
                $arranged[$statusKey] = [];
            }
            if(!isset($arranged[$statusKey][$customerSupplierKey])) {
                $arranged[$statusKey][$customerSupplierKey] = [];
            }
            $arranged[$statusKey][$customerSupplierKey][] = $row;
            
            $key = $statusKey.'_'.$customerSupplierKey;
            if(!isset($dateRanges[$key])) {
                $dateRanges[$key] = ['from' => $row['transaction_date'], 'to' => $row['transaction_date']];
            } else {
                if($row['transaction_date'] < $dateRanges[$key]['from']) {
                    $dateRanges[$key]['from'] = $row['transaction_date'];
                }
                if($row['transaction_date'] > $dateRanges[$key]['to']) {
                    $dateRanges[$key]['to'] = $row['transaction_date'];
                }
            }
        }
    }
    
    return ['data' => $arranged, 'dateRanges' => $dateRanges];
}
exit;
?>