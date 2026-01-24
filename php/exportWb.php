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
$arrangedData = arrangeByCustomerOrSupplier($allRows, $_GET['transactionStatus']);

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
foreach($arrangedData as $customerSupplier => $rows) {
    // Column headers
    $headers = ['NO', 'DATE', 'TIME', 'WEIGHING SLIP NO', ($_GET['transactionStatus'] == 'Sales' ? 'DELIVERY' : 'PURCHASE').' No.'];
    
    if ($_GET['transactionStatus'] == 'Purchase') {
        $headers[] = 'SEC BILL NO';
    }
    
    $headers = array_merge($headers, [
        'PRODUCT DESCRIPTION', 'VEHICLE NO', 'IN WEIGHT (KG)', 'IN DATE/TIME', 
        'OUT WEIGHT (KG)', 'OUT DATE/TIME', 'REDUCE WEIGHT (KG)', 'NETT WEIGHT (KG)',
        ($_GET['transactionStatus'] == 'Sales' ? 'ORDER' : 'SUPPLY').' WEIGHT (KG)',
        'VARIANCE (KG)', 'VARIANCE (%)', 'DRIVER NAME', 'DRIVER IC', 
        'WEIGH BY', 'MODIFIED BY', 'CHECKED BY'
    ]);
    
    $lastCol = chr(64 + count($headers));
    
    // Line above header
    $sheet->getStyle('A'.$rowIndex.':'.$lastCol.$rowIndex)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    
    // Header
    $sheet->setCellValue('A'.$rowIndex, 'WEEKLY MONTHLY '.($_GET['transactionStatus'] == 'Sales' ? 'DISPATCH' : 'RECEIVING').' REPORT WEIGHING');
    $sheet->getStyle('A'.$rowIndex)->getFont()->setBold(true);
    $rowIndex++;
    
    $sheet->setCellValue('A'.$rowIndex, ($_GET['transactionStatus'] == 'Sales' ? 'TO CUSTOMER' : 'FROM SUPPLIER').': '.$customerSupplier);
    $sheet->setCellValue('B'.$rowIndex, 'From Date: '.$_GET['fromDate'].' - '.$_GET['toDate']);
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
        $subtotal_supply += ($row['transaction_status'] == 'Sales' ? $row['order_weight'] : $row['supplier_weight']);
        $subtotal_variance += $row['weight_different'];
        
        $lineData = [
            $count,
            $formattedDate,
            $formattedTime,
            $row['transaction_id'],
            ($row['transaction_status'] == 'Sales' ? $row['delivery_no'] : $row['purchase_order'])
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
            number_format(($row['transaction_status'] == 'Sales' ? $row['order_weight'] : $row['supplier_weight']), 2),
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
    $inWeightCol = $_GET['transactionStatus'] == 'Purchase' ? 'I' : 'H';
    $outWeightCol = $_GET['transactionStatus'] == 'Purchase' ? 'K' : 'J';
    $reduceWeightCol = $_GET['transactionStatus'] == 'Purchase' ? 'M' : 'L';
    $nettWeightCol = $_GET['transactionStatus'] == 'Purchase' ? 'N' : 'M';
    $supplyWeightCol = $_GET['transactionStatus'] == 'Purchase' ? 'O' : 'N';
    $varianceCol = $_GET['transactionStatus'] == 'Purchase' ? 'P' : 'O';
    
    $sheet->setCellValue('A'.$rowIndex, 'SUBTOTAL');
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