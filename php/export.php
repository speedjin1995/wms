<?php
require_once 'db_connect.php';
require_once 'lookup.php';
require_once '../vendor/autoload.php'; 
use PhpOffice\PhpSpreadsheet\Spreadsheet; 
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();
$company = $_SESSION['customer'];
 
// Filter the excel data 
function filterData(&$str){ 
    $str = preg_replace("/\t/", "\\t", $str); 
    $str = preg_replace("/\r?\n/", "\\n", $str); 
    if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"'; 
} 

function arrangeByGrade($weighingDetails) {
    $arranged = [];
    
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
 
// Excel file name for download 
$fileName = "Report_" . date('Y-m-d') . ".xlsx";

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

// Fetch records from database
if($isMulti == 'Y'){
    if(isset($_GET['ids']) && $_GET['ids'] != null && $_GET['ids'] != '' && $_GET['ids'] != '-'){
        $ids = $_GET['ids'];
    }
    $query = $db->query("SELECT wholesales.* FROM wholesales WHERE wholesales.id IN (".$ids.")");
}else{
    $query = $db->query("SELECT wholesales.* FROM wholesales WHERE wholesales.deleted = '0' AND wholesales.company = '$company'".$searchQuery);
}

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
            'po_no' => $row['po_no'],
            'security_bills' => $row['security_bills'],
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

// Create a new Spreadsheet
$spreadsheet = new Spreadsheet();

// Get the active worksheet
$sheet = $spreadsheet->getActiveSheet();

// Column names 
if($_GET['status'] == 'DISPATCH' || $_GET['status'] == 'Sale Balance') {
    $fields = array('No', 'Date', 'Time', 'Weigh Slip No.', 'Customer');
}else{
    $fields = array('No', 'Date', 'Time', 'Weigh Slip No.', 'Security Bill No.', 'Supplier');
}

// Add grade columns
foreach ($gradeColumns as $gradeCol) {
    $fields[] = $gradeCol;
}

$fields = array_merge($fields, array('Total Weight', 'Total Bin Weight', 'Reject Weight', 'Actual Weight', 'Total Price (RM)', 'Actual Price (RM)', 'Vehicle No.', 'Driver Name', 'Weigh By'));

// Display column names as first row 
$sheet->fromArray($fields, NULL, 'A1');

$rowIndex = 2; // Start from the second row

if (!empty($allRows)) {
    // Output each row of the data 
    foreach ($allRows as $rowData) {
        $lineData = array(
            $rowData['count'],
            $rowData['formattedDate'],
            $rowData['formattedTime'],
            $rowData['serial_no']
        );
        
        if($_GET['status'] == 'RECEIVING') {
            $lineData[] = $rowData['security_bills'];
        }
        
        $lineData[] = ($rowData['status'] == 'DISPATCH' || $rowData['status'] == 'Sale Balance') ? searchCustomerNameById($rowData['customer'], $rowData['other_customer'],$db) : searchSupplierNameById($rowData['supplier'], $rowData['other_supplier'], $db);

        // Add grade weights in correct order
        foreach ($gradeColumns as $gradeCol) {
            $lineData[] = number_format(($rowData['gradeWeights'][$gradeCol] ?? 0), 2);
        }

        // Add remaining data
        $lineData = array_merge($lineData, array(
            number_format($rowData['totalWeight'], 2),
            number_format($rowData['totalBinWeight'], 2),
            number_format($rowData['total_reject'], 2),
            number_format($rowData['actualWeight'], 2),
            number_format($rowData['totalPrice'], 2),
            number_format($rowData['actualPrice'], 2),
            $rowData['vehicle_no'],
            $rowData['driver'],
            $rowData['weighted_by']
        ));

        array_walk($lineData, 'filterData'); 
        $sheet->fromArray($lineData, NULL, 'A'.$rowIndex);
        $rowIndex++;
    }
    
    // Add subtotal row
    $subtotalData = array('SUBTOTAL', '', '', '');
    if($_GET['status'] == 'RECEIVING') {
        $subtotalData[] = '';
    }
    $subtotalData[] = '';
    
    foreach ($gradeColumns as $gradeCol) {
        $subtotalData[] = number_format($subtotals['gradeWeights'][$gradeCol], 2);
    }
    
    $subtotalData = array_merge($subtotalData, array(
        number_format($subtotals['totalWeight'], 2),
        number_format($subtotals['totalBinWeight'], 2),
        number_format($subtotals['total_reject'], 2),
        number_format($subtotals['actualWeight'], 2),
        number_format($subtotals['totalPrice'], 2),
        number_format($subtotals['actualPrice'], 2),
        '', '', ''
    ));
    
    $sheet->fromArray($subtotalData, NULL, 'A'.$rowIndex);
    
    // Calculate last column letter
    $lastCol = count($subtotalData);
    $colLetter = '';
    while ($lastCol > 0) {
        $lastCol--;
        $colLetter = chr(65 + ($lastCol % 26)) . $colLetter;
        $lastCol = floor($lastCol / 26);
    }
    $sheet->getStyle('A'.$rowIndex.':'.$colLetter.$rowIndex)->getFont()->setBold(true);
} else {
    $sheet->setCellValue('A'.$rowIndex, 'No records found...');
}

// Create a writer object
$writer = new Xlsx($spreadsheet);

// Output to browser
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$fileName.'"');
header('Cache-Control: max-age=0');

// Save the spreadsheet
$writer->save('php://output');


exit;
?>