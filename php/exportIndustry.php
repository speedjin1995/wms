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

function arrangeByProduct($weighingDetails) {
    $arranged = [];

    if (!empty($weighingDetails)) {
        foreach ($weighingDetails as $detail) {
            if (empty($detail['product_name'])) continue;

            $product = $detail['product_name'];
            $arranged[$product][] = $detail;
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

if(isset($_GET['transactionStatus']) && $_GET['transactionStatus'] != null && $_GET['transactionStatus'] != '' && $_GET['transactionStatus'] != '-'){
    $searchQuery .= " AND wholesales.status = '".mysqli_real_escape_string($db, $_GET['transactionStatus'])."'";
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

// $productColumns[ product_name ] = [ grade, ... ]
$productColumns = [];
$allRows = [];

if ($query->num_rows > 0) {
    $count = 1;
    while ($row = $query->fetch_assoc()) {
        $createdDateTime = new DateTime($row['created_datetime']);
        $formattedDate = $createdDateTime->format('d/m/Y');
        $formattedTime = $createdDateTime->format('H:i:s');

        $weighingDetails = json_decode($row['weight_details'], true);
        $arrangedDetails = arrangeByProduct($weighingDetails);

        $totalWeight = 0;
        $totalBinWeight = 0;
        $totalRejectWeight = 0;
        $totalPrice = 0;
        $actualPrice = 0;
        $productWeights = [];

        foreach ($arrangedDetails as $product => $details) {

            if (!in_array($product, $productColumns)) {
                $productColumns[] = $product;
            }

            $productNetWeight = 0;

            foreach ($details as $detail) {

                $productNetWeight += floatval($detail['net'] ?? 0);

                $totalWeight += floatval($detail['gross'] ?? 0);
                $totalBinWeight += floatval($detail['tare'] ?? 0);
                $totalRejectWeight += floatval($detail['reject'] ?? 0);

                if ($detail['fixedfloat'] == 'fixed') {
                    $totalPrice += floatval($detail['price'] ?? 0);
                    $actualPrice += floatval($detail['price'] ?? 0);
                } else {
                    $totalPrice += floatval($detail['gross'] ?? 0) * floatval($detail['price'] ?? 0);
                    $actualPrice += (floatval($detail['net'] ?? 0) - floatval($detail['reject'] ?? 0))
                        * floatval($detail['price'] ?? 0);
                }
            }

            $productWeights[$product] = $productNetWeight;
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
            'productWeights' => $productWeights,
            'totalWeight' => $totalWeight,
            'totalBinWeight' => $totalBinWeight,
            'total_reject' => $totalRejectWeight,
            'actualWeight' => $actualWeight,
            'totalPrice' => $totalPrice,
            'actualPrice' => $actualPrice,
            'vehicle_no' => $row['vehicle_no'],
            'driver' => $row['driver'],
            'checked_by' => $row['checked_by'],
            'weighted_by' => searchUserNameById($row['weighted_by'], $db),
            'remark' => $row['remark'],
        ];
        $count++;
    }
}

// Calculate subtotals
$subtotals = ['productWeights' => [], 'totalWeight' => 0, 'totalBinWeight' => 0, 'total_reject' => 0, 'actualWeight' => 0, 'totalPrice' => 0, 'actualPrice' => 0];
foreach ($allRows as $rowData) {
    foreach ($productColumns as $product) {
        if (!isset($subtotals['productWeights'][$product])) {
            $subtotals['productWeights'][$product] = 0;
        }

        $subtotals['productWeights'][$product] += ($rowData['productWeights'][$product] ?? 0);
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
    $fixedHeaders = ['No', 'Date', 'Time', 'Weigh Slip No.', 'Purchase No.', 'Supplier'];
}
$trailingHeaders = ['Total Weight', 'Total Bin Weight', 'Reject Weight', 'Actual Weight'];
if ($allowPrice == 'Y') {
    $trailingHeaders[] = 'Total Price (RM)';
    $trailingHeaders[] = 'Actual Price (RM)';
}
if($_GET['transactionStatus'] == 'DISPATCH' || $_GET['transactionStatus'] == 'OUTGOING') {
    $trailingHeaders = array_merge($trailingHeaders, ['Vehicle No.', 'Driver Name', 'Weigh By', 'Checked By', 'Remark']);
} else {
    $trailingHeaders = array_merge($trailingHeaders, ['Vehicle No.', 'Weigh By', 'Checked By', 'Remark']);
}

$borderStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        ]
    ]
];

$colIndex = 1;

// Row 2 only: fixed headers
foreach ($fixedHeaders as $header) {
    $cl = colLetter($colIndex);
    $sheet->setCellValue($cl.'1', $header);
    $sheet->getStyle($cl.'1')->applyFromArray($borderStyle);
    $sheet->getStyle($cl.'1')->getFont()->setBold(true);
    $colIndex++;
}

foreach ($productColumns as $product) {
    $cl = colLetter($colIndex);

    $sheet->setCellValue($cl.'1', $product);
    $sheet->getStyle($cl.'1')->applyFromArray($borderStyle);
    $sheet->getStyle($cl.'1')->getFont()->setBold(true);

    $colIndex++;
}

// Row 2 only: trailing headers
foreach ($trailingHeaders as $header) {
    $cl = colLetter($colIndex);
    $sheet->setCellValue($cl.'1', $header);
    $sheet->getStyle($cl.'1')->applyFromArray($borderStyle);
    $sheet->getStyle($cl.'1')->getFont()->setBold(true);
    $colIndex++;
}

$totalCols = $colIndex - 1;
$rowIndex = 2; // data starts at row 3

if (!empty($allRows)) {
    foreach ($allRows as $rowData) {
        $lineData = [
            $rowData['count'],
            $rowData['formattedDate'],
            $rowData['formattedTime'],
            $rowData['serial_no'],
            $rowData['po_no']
        ];

        // if($_GET['transactionStatus'] == 'RECEIVING' || $_GET['transactionStatus'] == 'INCOMING'){
        //     $lineData[] = $rowData['security_bills'];
        // }

        $lineData[] = ($rowData['status'] == 'DISPATCH' || $rowData['status'] == 'STOCK-BAL' || $_GET['transactionStatus'] == 'OUTGOING')
            ? searchCustomerNameById($rowData['customer'], $rowData['other_customer'], $db)
            : searchSupplierNameById($rowData['supplier'], $rowData['other_supplier'], $db);

        foreach ($productColumns as $product) {
            $lineData[] =
                number_format(
                    ($rowData['productWeights'][$product] ?? 0),
                    2
                );
        }

        $lineData[] = number_format($rowData['totalWeight'], 2);
        $lineData[] = number_format($rowData['totalBinWeight'], 2);
        $lineData[] = number_format($rowData['total_reject'], 2);
        $lineData[] = number_format($rowData['actualWeight'], 2);
        if ($allowPrice == 'Y') {
            $lineData[] = number_format($rowData['totalPrice'], 2);
            $lineData[] = number_format($rowData['actualPrice'], 2);
        }

        array_push($lineData, $rowData['vehicle_no']);
        if ($rowData['status'] == 'DISPATCH' || $rowData['status'] == 'OUTGOING'){
            array_push($lineData, $rowData['driver']);
        }
        array_push($lineData, $rowData['weighted_by'], $rowData['checked_by'], $rowData['remark']);

        array_walk($lineData, 'filterData');
        $sheet->fromArray($lineData, NULL, 'A'.$rowIndex);
        $rowIndex++;
    }

    // Subtotal row
    $subtotalData = ['SUBTOTAL', '', '', '', ''];
    // if($_GET['transactionStatus'] == 'RECEIVING' || $_GET['transactionStatus'] == 'INCOMING') {
    //     $subtotalData[] = '';
    // }
    $subtotalData[] = '';

    foreach ($productColumns as $product) {
        $subtotalData[] =
            number_format(
                $subtotals['productWeights'][$product] ?? 0,
                2
            );
    }

    $subtotalData[] = number_format($subtotals['totalWeight'], 2);
    $subtotalData[] = number_format($subtotals['totalBinWeight'], 2);
    $subtotalData[] = number_format($subtotals['total_reject'], 2);
    $subtotalData[] = number_format($subtotals['actualWeight'], 2);

    if ($allowPrice == 'Y') {
        $subtotalData[] = number_format($subtotals['totalPrice'], 2);
        $subtotalData[] = number_format($subtotals['actualPrice'], 2);
    }

    $subtotalData[] = ''; // Vehicle No.

    if (
        $_GET['transactionStatus'] == 'DISPATCH' ||
        $_GET['transactionStatus'] == 'OUTGOING'
    ) {
        $subtotalData[] = ''; // Driver Name
    }

    $subtotalData[] = ''; // Weigh By
    $subtotalData[] = ''; // Checked By
    $subtotalData[] = ''; // Remark

    $sheet->fromArray($subtotalData, NULL, 'A'.$rowIndex);
    $sheet->getStyle('A'.$rowIndex.':'.colLetter($totalCols).$rowIndex)
        ->getFont()
        ->setBold(true);
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