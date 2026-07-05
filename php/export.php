<?php
require_once 'db_connect.php';
require_once 'lookup.php';
require_once '../vendor/autoload.php'; 
use PhpOffice\PhpSpreadsheet\Spreadsheet; 
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();
$company = $_SESSION['customer'];
$allowPrice = 'N';
// Company Detail 
$companyDetail = searchCompanyById($company, $db);
$allowPrice = $companyDetail['include_price'];
 
// Filter the excel data 
function filterData(&$str){ 
    $str = preg_replace("/\t/", "\\t", $str); 
    $str = preg_replace("/\r?\n/", "\\n", $str); 
    if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"'; 
} 

function arrangeByProductGrade($weighingDetails) {
    $arranged = [];
    if(isset($weighingDetails) && !empty($weighingDetails)) {
        foreach($weighingDetails as $detail) {
            if(empty($detail['product_name'])) continue;
            $product = $detail['product_name'];
            $grade = $detail['grade'] ?? 'Unknown';
            $arranged[$product][$grade][] = $detail;
        }
    }
    return $arranged;
}
 
// Excel file name for download 
$fileName = "Report_" . date('Y-m-d') . ".xlsx";

// Build search query
$searchQuery = "";

$fromDate = '';
$toDate = '';
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

if(isset($_GET['transactionStatus']) && $_GET['transactionStatus'] != null && $_GET['transactionStatus'] != '' && $_GET['transactionStatus'] != '-'){
    $searchQuery .= " AND wholesales.status = '".mysqli_real_escape_string($db, $_GET['transactionStatus'])."'";
}

if(isset($_GET['product']) && $_GET['product'] != null && $_GET['product'] != '' && $_GET['product'] != '-'){
    $searchQuery .= " AND wholesales.product = '".mysqli_real_escape_string($db, $_GET['product'])."'";
}

if(isset($_GET['category']) && $_GET['category'] != null && $_GET['category'] != '' && $_GET['category'] != '-'){
  // Get product ids in this category first
  $catProductIds = [];
  $catStmt = $db->prepare("SELECT id FROM products WHERE category = ? AND deleted = '0'");
  $catStmt->bind_param('s', $_GET['category']);
  $catStmt->execute();
  $catResult = $catStmt->get_result();
  while ($catRow = $catResult->fetch_assoc()) {
    $catProductIds[] = $catRow['id'];
  }
  $catStmt->close();

  if (count($catProductIds) > 0) {
    $likeConditions = array_map(fn($id) => "wholesales.weight_details LIKE '%\"product\":\"".$id."\"%'", $catProductIds);
    $searchQuery .= " AND (" . implode(' OR ', $likeConditions) . ")";
  } else {
    $searchQuery .= " AND 1=0";
  }
}

if(isset($_GET['customer']) && $_GET['customer'] != null && $_GET['customer'] != '' && $_GET['customer'] != '-'){
    $searchQuery .= " AND wholesales.customer = '".mysqli_real_escape_string($db, $_GET['customer'])."'";
}

if(isset($_GET['supplier']) && $_GET['supplier'] != null && $_GET['supplier'] != '' && $_GET['supplier'] != '-'){
    $searchQuery .= " AND wholesales.supplier = '".mysqli_real_escape_string($db, $_GET['supplier'])."'";
}

if($_GET['vehicle'] != null && $_GET['vehicle'] != '' && $_GET['vehicle'] != '-'){
  if ($_GET['vehicle'] == 'UNKOWN NO' || $_GET['vehicle'] == 'OTHERS' || $_GET['vehicle'] == 'UNKNOWN'){
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

if($_GET['status'] != null && $_GET['status'] != '' && $_GET['status'] != '-'){
  if ($_GET['status'] == 'active'){
    $searchQuery .= " and wholesales.deleted = '0'";
  } else if ($_GET['status'] == 'deleted'){
    $searchQuery .= " and wholesales.deleted = '1'";
  }
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

// $productGradeColumns[ product_name ] = [ grade, ... ]
$productGradeColumns = [];
$allRows = [];

if ($query->num_rows > 0) {
    $count = 1;
    while ($row = $query->fetch_assoc()) {
        $createdDateTime = new DateTime($row['created_datetime']);
        $formattedDate = $createdDateTime->format('d/m/Y');
        $formattedTime = $createdDateTime->format('H:i:s');

        $weighingDetails = json_decode($row['weight_details'], true);
        $arrangedDetails = arrangeByProductGrade($weighingDetails);

        $totalWeight = 0;
        $totalBinWeight = 0;
        $totalRejectWeight = 0;
        $totalPrice = 0;
        $actualPrice = 0;
        $currency = '';
        // gradeWeights keyed as "product_name|grade"
        $gradeWeights = [];
        $gradePrice = [];
        $gradeActualPrice = [];
        $currencyTotals = [];

        foreach ($arrangedDetails as $product => $grades) {
            foreach ($grades as $grade => $details) {
                if (!isset($productGradeColumns[$product])) $productGradeColumns[$product] = [];
                if (!in_array($grade, $productGradeColumns[$product])) $productGradeColumns[$product][] = $grade;

                $gradeNettWeight = 0;
                foreach ($details as $detail) {
                    $gradeNettWeight += floatval($detail['net'] ?? 0);
                    $totalWeight += floatval($detail['gross'] ?? 0);
                    $totalBinWeight += floatval($detail['tare'] ?? 0);
                    $totalRejectWeight += floatval($detail['reject'] ?? 0);
                    if (empty($currency) && !empty($detail['currency'])) {
                        $currency = searchCurrencyNameById($detail['currency'], $db);
                    }
                    $detailCurrencyName = !empty($detail['currency']) ? searchCurrencyNameById($detail['currency'], $db) : 'MYR';
                    if (empty($detailCurrencyName)) {
                        $detailCurrencyName = 'MYR';
                    }
                    $gradeKey = $product.'|'.$grade;
                    if ($detail['fixedfloat'] == 'fixed') {
                        $detailTotal = floatval($detail['price'] ?? 0);
                        $detailActual = floatval($detail['price'] ?? 0);
                        $totalPrice += $detailTotal;
                        $actualPrice += $detailActual;
                    } else {
                        $detailTotal = floatval($detail['gross'] ?? 0) * floatval($detail['price'] ?? 0);
                        $detailActual = (floatval($detail['net'] ?? 0) - floatval($detail['reject'] ?? 0)) * floatval($detail['price'] ?? 0);
                        $totalPrice += $detailTotal;
                        $actualPrice += $detailActual;
                    }
                    if (!isset($gradePrice[$gradeKey][$detailCurrencyName])) {
                        $gradePrice[$gradeKey][$detailCurrencyName] = 0;
                    }
                    if (!isset($gradeActualPrice[$gradeKey][$detailCurrencyName])) {
                        $gradeActualPrice[$gradeKey][$detailCurrencyName] = 0;
                    }
                    $gradePrice[$gradeKey][$detailCurrencyName] += $detailTotal;
                    $gradeActualPrice[$gradeKey][$detailCurrencyName] += $detailActual;
                    if (!isset($currencyTotals[$detailCurrencyName])) {
                        $currencyTotals[$detailCurrencyName] = ['totalPrice' => 0, 'actualPrice' => 0];
                    }
                    $currencyTotals[$detailCurrencyName]['totalPrice'] += $detailTotal;
                    $currencyTotals[$detailCurrencyName]['actualPrice'] += $detailActual;
                }
                $gradeWeights[$product.'|'.$grade] = $gradeNettWeight;
            }
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
            'gradePrice' => $gradePrice,
            'gradeActualPrice' => $gradeActualPrice,
            'currencyTotals' => $currencyTotals,
            'totalWeight' => $totalWeight,
            'totalBinWeight' => $totalBinWeight,
            'total_reject' => $totalRejectWeight,
            'actualWeight' => $actualWeight,
            'totalPrice' => $totalPrice,
            'actualPrice' => $actualPrice,
            'currency' => $currency,
            'vehicle_no' => $row['vehicle_no'],
            'driver' => $row['driver'],
            'checked_by' => $row['checked_by'],
            'weighted_by' => searchUserNameById($row['weighted_by'], $db),
            'remark' => $row['remark'],
        ];
        $count++;
    }
}

// Sort grades alphabetically for each product
foreach ($productGradeColumns as $product => &$grades) {
    sort($grades);
}
unset($grades);

// Calculate subtotals
$subtotals = ['gradeWeights' => [], 'totalWeight' => 0, 'totalBinWeight' => 0, 'total_reject' => 0, 'actualWeight' => 0, 'totalPrice' => 0, 'actualPrice' => 0];
$subtotalGradePrice = [];
$subtotalGradeActualPrice = [];
$subtotalCurrencyTotals = [];
foreach ($allRows as $rowData) {
    foreach ($productGradeColumns as $product => $grades) {
        foreach ($grades as $grade) {
            $key = $product.'|'.$grade;
            if (!isset($subtotals['gradeWeights'][$key])) $subtotals['gradeWeights'][$key] = 0;
            $subtotals['gradeWeights'][$key] += ($rowData['gradeWeights'][$key] ?? 0);
            foreach (($rowData['gradePrice'][$key] ?? []) as $cur => $amt) {
                if (!isset($subtotalGradePrice[$key][$cur])) {
                    $subtotalGradePrice[$key][$cur] = 0;
                }
                $subtotalGradePrice[$key][$cur] += $amt;
            }
            foreach (($rowData['gradeActualPrice'][$key] ?? []) as $cur => $amt) {
                if (!isset($subtotalGradeActualPrice[$key][$cur])) {
                    $subtotalGradeActualPrice[$key][$cur] = 0;
                }
                $subtotalGradeActualPrice[$key][$cur] += $amt;
            }
        }
    }
    foreach (($rowData['currencyTotals'] ?? []) as $cur => $totals) {
        if (!isset($subtotalCurrencyTotals[$cur])) {
            $subtotalCurrencyTotals[$cur] = ['totalPrice' => 0, 'actualPrice' => 0];
        }
        $subtotalCurrencyTotals[$cur]['totalPrice'] += $totals['totalPrice'];
        $subtotalCurrencyTotals[$cur]['actualPrice'] += $totals['actualPrice'];
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

// Helper: convert 1-based column index to letter(s)
function colLetter($n) {
    $l = '';
    while ($n > 0) { $n--; $l = chr(65 + ($n % 26)) . $l; $n = floor($n / 26); }
    return $l;
}

if($_GET['transactionStatus'] == 'DISPATCH' || $_GET['transactionStatus'] == 'STOCK-BAL' || $_GET['transactionStatus'] == 'OUTGOING') {
    $fixedHeaders = ['No', 'Date', 'Time', 'Weigh Slip No.', 'Delivery No.', 'Customer'];
} else {
    $fixedHeaders = ['No', 'Date', 'Time', 'Weigh Slip No.', 'Purchase No.', 'Security Bill No.', 'Supplier'];
}
$trailingHeaders = ['Total Weight', 'Total Bin Weight', 'Reject Weight', 'Actual Weight'];
if ($allowPrice == 'Y') {
    $trailingHeaders[] = 'Currency';
    $trailingHeaders[] = 'Total Price (RM)';
    $trailingHeaders[] = 'Actual Price (RM)';
}
$trailingHeaders = array_merge($trailingHeaders, ['Vehicle No.', 'Driver Name', 'Weigh By', 'Checked By', 'Remark']);
$borderStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        ]
    ]
];

$colIndex = 1;

// Row 3 only: fixed headers
foreach ($fixedHeaders as $header) {
    $cl = colLetter($colIndex);
    $sheet->setCellValue($cl.'3', $header);
    $sheet->getStyle($cl.'3')->applyFromArray($borderStyle);
    $sheet->getStyle($cl.'3')->getFont()->setBold(true);
    $colIndex++;
}

// Row 2: product name merged across grades (centered+bold); Row 3: grade labels
foreach ($productGradeColumns as $product => $grades) {
    $gradeCount  = count($grades);
    $startLetter = colLetter($colIndex);
    $endLetter   = colLetter($colIndex + $gradeCount - 1);

    $sheet->setCellValue($startLetter.'2', $product);
    if ($gradeCount > 1) {
        $sheet->mergeCells($startLetter.'2:'.$endLetter.'2');
    }
    $sheet->getStyle($startLetter.'2:'.$endLetter.'2')->applyFromArray($borderStyle);
    $sheet->getStyle($startLetter.'2:'.$endLetter.'2')->getFont()->setBold(true);
    $sheet->getStyle($startLetter.'2:'.$endLetter.'2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    foreach ($grades as $grade) {
        $cl = colLetter($colIndex);
        $sheet->setCellValue($cl.'3', $grade);
        $sheet->getStyle($cl.'3')->applyFromArray($borderStyle);
        $sheet->getStyle($cl.'3')->getFont()->setBold(true);
        $colIndex++;
    }
}

// Row 3 only: trailing headers
foreach ($trailingHeaders as $header) {
    $cl = colLetter($colIndex);
    $sheet->setCellValue($cl.'3', $header);
    $sheet->getStyle($cl.'3')->applyFromArray($borderStyle);
    $sheet->getStyle($cl.'3')->getFont()->setBold(true);
    $colIndex++;
}

$totalCols = $colIndex - 1;
$rowIndex = 4; // data starts at row 4

// Row 1: date range header (merged across all columns)
$dateRangeText = 'Date: ' . ($fromDate ?: '-') . ' to ' . ($toDate ?: '-');
$sheet->setCellValue('A1', $dateRangeText);
$sheet->getStyle('A1')->getFont()->setBold(true);
$sheet->mergeCells('A1:' . colLetter($totalCols) . '1');

// Track numeric column indices (1-based) for number formatting
$numericColIndices = [];

if (!empty($allRows)) {
    foreach ($allRows as $rowData) {
        $lineData = [
            $rowData['count'],
            $rowData['formattedDate'],
            $rowData['formattedTime'],
            $rowData['serial_no'],
            $rowData['po_no']
        ];

        if($_GET['transactionStatus'] == 'RECEIVING' || $_GET['transactionStatus'] == 'INCOMING'){
            $lineData[] = $rowData['security_bills'];
        }

        $lineData[] = ($rowData['status'] == 'DISPATCH' || $rowData['status'] == 'STOCK-BAL' || $_GET['transactionStatus'] == 'OUTGOING')
            ? searchCustomerNameById($rowData['customer'], $rowData['other_customer'], $db)
            : searchSupplierNameById($rowData['supplier'], $rowData['other_supplier'], $db);

        foreach ($productGradeColumns as $product => $grades) {
            foreach ($grades as $grade) {
                $numericColIndices[] = count($lineData) + 1;
                $lineData[] = floatval($rowData['gradeWeights'][$product.'|'.$grade] ?? 0);
            }
        }

        $numericColIndices[] = count($lineData) + 1; $lineData[] = floatval($rowData['totalWeight']);
        $numericColIndices[] = count($lineData) + 1; $lineData[] = floatval($rowData['totalBinWeight']);
        $numericColIndices[] = count($lineData) + 1; $lineData[] = floatval($rowData['total_reject']);
        $numericColIndices[] = count($lineData) + 1; $lineData[] = floatval($rowData['actualWeight']);
        if ($allowPrice == 'Y') {
            $lineData[] = $rowData['currency'];
            $numericColIndices[] = count($lineData) + 1; $lineData[] = floatval($rowData['totalPrice']);
            $numericColIndices[] = count($lineData) + 1; $lineData[] = floatval($rowData['actualPrice']);
        }

        array_push($lineData, $rowData['vehicle_no'], $rowData['driver'], $rowData['weighted_by'], $rowData['checked_by'], $rowData['remark']);

        $numericColIndices = array_unique($numericColIndices);
        $sheet->fromArray($lineData, NULL, 'A'.$rowIndex);
        $rowIndex++;
    }

    // Subtotal row
    $subtotalData = ['', '', '', '', ''];
    if($_GET['transactionStatus'] == 'RECEIVING' || $_GET['transactionStatus'] == 'INCOMING') {
        $subtotalData[] = '';
    }
    $subtotalData[] = 'SUBTOTAL';

    foreach ($productGradeColumns as $product => $grades) {
        foreach ($grades as $grade) {
            $subtotalData[] = floatval($subtotals['gradeWeights'][$product.'|'.$grade] ?? 0);
        }
    }

    $subtotalData[] = floatval($subtotals['totalWeight']);
    $subtotalData[] = floatval($subtotals['totalBinWeight']);
    $subtotalData[] = floatval($subtotals['total_reject']);
    $subtotalData[] = floatval($subtotals['actualWeight']);
    if ($allowPrice == 'Y') {
        $subtotalData[] = '';
        $subtotalData[] = floatval($subtotals['totalPrice']);
        $subtotalData[] = floatval($subtotals['actualPrice']);
    }
    array_push($subtotalData, '', '', '');

    $sheet->fromArray($subtotalData, NULL, 'A'.$rowIndex);
    $sheet->getStyle('A'.$rowIndex.':'.colLetter($totalCols).$rowIndex)->getFont()->setBold(true);
    $rowIndex++;

    // Total Price rows per currency (only when price is enabled)
    if ($allowPrice == 'Y') {
        foreach ($subtotalCurrencyTotals as $cur => $curTotals) {
            $totalPriceData = array_fill(0, count($fixedHeaders) - 1, '');
            $totalPriceData[] = 'TOTAL PRICE ('.$cur.')';
            foreach ($productGradeColumns as $product => $grades) {
                foreach ($grades as $grade) {
                    $key = $product.'|'.$grade;
                    $totalPriceData[] = floatval($subtotalGradeActualPrice[$key][$cur] ?? 0);
                }
            }
            // trailing: totalWeight, totalBinWeight, reject, actualWeight
            $totalPriceData[] = '';
            $totalPriceData[] = '';
            $totalPriceData[] = '';
            $totalPriceData[] = '';
            // currency, totalPrice, actualPrice
            $totalPriceData[] = $cur;
            $totalPriceData[] = floatval($curTotals['totalPrice']);
            $totalPriceData[] = floatval($curTotals['actualPrice']);
            array_push($totalPriceData, '', '', '', '', '');
            $sheet->fromArray($totalPriceData, NULL, 'A'.$rowIndex);
            $sheet->getStyle('A'.$rowIndex.':'.colLetter($totalCols).$rowIndex)->getFont()->setBold(true);
            $rowIndex++;
        }
    }

    // Apply #,##0.00 number format to all numeric columns (data rows + subtotal)
    foreach ($numericColIndices as $ci) {
        $cl = colLetter($ci);
        $sheet->getStyle($cl.'4:'.$cl.$rowIndex)
              ->getNumberFormat()
              ->setFormatCode('#,##0.00');
    }
} else {
    $sheet->setCellValue('A3', 'No records found...');
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