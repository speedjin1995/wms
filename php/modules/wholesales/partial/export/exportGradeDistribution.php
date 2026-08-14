<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$company = $_SESSION['customer'];
$role    = $_SESSION['role'];
$companyDetail = searchCompanyById($company, $db);
$allowPrice = $companyDetail['include_price'];

// Get filter params
$fromDate   = $_GET['fromDate'] ?? '';
$toDate     = $_GET['toDate'] ?? '';
$status     = $_GET['status'] ?? '';
$locationId = $_GET['location'] ?? '';

// Build search query
$searchQuery = '';

if ($fromDate != '') {
    $fromDateObj = DateTime::createFromFormat('d/m/Y', $fromDate);
    $searchQuery .= " AND DATE(w.start_time) >= '" . $fromDateObj->format('Y-m-d') . "'";
}
if ($toDate != '') {
    $toDateObj = DateTime::createFromFormat('d/m/Y', $toDate);
    $searchQuery .= " AND DATE(w.start_time) <= '" . $toDateObj->format('Y-m-d') . "'";
}
if ($status === 'RECEIVING') {
    $searchQuery .= " AND w.status IN ('RECEIVING','INCOMING')";
} elseif ($status === 'DISPATCH') {
    $searchQuery .= " AND w.status IN ('DISPATCH','OUTGOING')";
} else {
    $searchQuery .= " AND w.status IN ('RECEIVING','INCOMING','DISPATCH','OUTGOING')";
}
if ($locationId != '') {
    $locationId = mysqli_real_escape_string($db, $locationId);
    $searchQuery .= " AND w.location = '$locationId'";
}

$companyFilter = ($role != 'SADMIN') ? " AND w.company = '$company'" : '';

// Fetch records
$query = "SELECT w.weight_details, w.status, DATE(w.start_time) as trade_date
          FROM wholesales w
          WHERE w.deleted = 0" . $companyFilter . $searchQuery . "
          ORDER BY w.start_time";
$result = mysqli_query($db, $query);

// Build hierarchical data: Product > Grade > Date+Price
$dataRecv = [];
$dataDisp = [];
$productCache = [];
$currencyCache = [];

while ($row = mysqli_fetch_assoc($result)) {
    $details = json_decode($row['weight_details'], true) ?: [];
    $isReceiving = in_array($row['status'], ['RECEIVING', 'INCOMING']);
    $tradeDate = $row['trade_date'];
    
    foreach ($details as $item) {
        $productId = $item['product'] ?? '';
        $productName = 'Unknown';
        if ($productId != '') {
            $pRow = getProductById($productId, $db, $productCache);
            $productName = $pRow['product_name'] ?? 'Unknown';
        }
        
        $gradeName = $item['grade'] ?? 'Unknown';
        $net   = floatval($item['net'] ?? 0);
        $price = floatval($item['price'] ?? 0);
        $total = floatval($item['total'] ?? 0);
        
        $curId = $item['currency'] ?? '';
        $currency = 'MYR';
        if ($curId != '') {
            if (isset($currencyCache[$curId])) {
                $currency = $currencyCache[$curId];
            } else {
                $currency = searchCurrencyNameById($curId, $db) ?: 'MYR';
                $currencyCache[$curId] = $currency;
            }
        }
        
        // Key for date+price combination
        $datePriceKey = $tradeDate . '|' . $price . '|' . $currency;
        
        // Select target array based on status
        if ($isReceiving) {
            if (!isset($dataRecv[$productName])) {
                $dataRecv[$productName] = ['grades' => [], 'totals' => ['net' => 0, 'price' => []]];
            }
            if (!isset($dataRecv[$productName]['grades'][$gradeName])) {
                $dataRecv[$productName]['grades'][$gradeName] = ['datePrices' => [], 'totals' => ['net' => 0, 'price' => []]];
            }
            if (!isset($dataRecv[$productName]['grades'][$gradeName]['datePrices'][$datePriceKey])) {
                $dataRecv[$productName]['grades'][$gradeName]['datePrices'][$datePriceKey] = [
                    'date' => $tradeDate,
                    'price' => $price,
                    'currency' => $currency,
                    'net' => 0,
                    'total' => 0
                ];
            }
            
            // Date+Price totals
            $dataRecv[$productName]['grades'][$gradeName]['datePrices'][$datePriceKey]['net'] += $net;
            $dataRecv[$productName]['grades'][$gradeName]['datePrices'][$datePriceKey]['total'] += $total;
            
            // Grade totals
            $dataRecv[$productName]['grades'][$gradeName]['totals']['net'] += $net;
            if (!isset($dataRecv[$productName]['grades'][$gradeName]['totals']['price'][$currency])) {
                $dataRecv[$productName]['grades'][$gradeName]['totals']['price'][$currency] = 0;
            }
            $dataRecv[$productName]['grades'][$gradeName]['totals']['price'][$currency] += $total;
            
            // Product totals
            $dataRecv[$productName]['totals']['net'] += $net;
            if (!isset($dataRecv[$productName]['totals']['price'][$currency])) {
                $dataRecv[$productName]['totals']['price'][$currency] = 0;
            }
            $dataRecv[$productName]['totals']['price'][$currency] += $total;
        } else {
            if (!isset($dataDisp[$productName])) {
                $dataDisp[$productName] = ['grades' => [], 'totals' => ['net' => 0, 'price' => []]];
            }
            if (!isset($dataDisp[$productName]['grades'][$gradeName])) {
                $dataDisp[$productName]['grades'][$gradeName] = ['datePrices' => [], 'totals' => ['net' => 0, 'price' => []]];
            }
            if (!isset($dataDisp[$productName]['grades'][$gradeName]['datePrices'][$datePriceKey])) {
                $dataDisp[$productName]['grades'][$gradeName]['datePrices'][$datePriceKey] = [
                    'date' => $tradeDate,
                    'price' => $price,
                    'currency' => $currency,
                    'net' => 0,
                    'total' => 0
                ];
            }
            
            // Date+Price totals
            $dataDisp[$productName]['grades'][$gradeName]['datePrices'][$datePriceKey]['net'] += $net;
            $dataDisp[$productName]['grades'][$gradeName]['datePrices'][$datePriceKey]['total'] += $total;
            
            // Grade totals
            $dataDisp[$productName]['grades'][$gradeName]['totals']['net'] += $net;
            if (!isset($dataDisp[$productName]['grades'][$gradeName]['totals']['price'][$currency])) {
                $dataDisp[$productName]['grades'][$gradeName]['totals']['price'][$currency] = 0;
            }
            $dataDisp[$productName]['grades'][$gradeName]['totals']['price'][$currency] += $total;
            
            // Product totals
            $dataDisp[$productName]['totals']['net'] += $net;
            if (!isset($dataDisp[$productName]['totals']['price'][$currency])) {
                $dataDisp[$productName]['totals']['price'][$currency] = 0;
            }
            $dataDisp[$productName]['totals']['price'][$currency] += $total;
        }
    }
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();

// Styles
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '343a40']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
];
$productStyle = [
    'font' => ['bold' => true, 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4EDDA']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$gradeStyle = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3CD']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$dateRowStyle = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$grandTotalStyle = [
    'font' => ['bold' => true, 'size' => 12],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC107']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

// Function to write sheet
function writeGradeSheet($sheet, $data, $allowPrice, $fromDate, $toDate, $sheetTitle, $headerStyle, $productStyle, $gradeStyle, $dateRowStyle, $grandTotalStyle, $headerColor) {
    $headerStyle['fill']['startColor']['rgb'] = $headerColor;
    
    $row = 1;
    $sheet->setCellValue('A' . $row, $sheetTitle . ' - Grade Distribution Report');
    $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
    $row++;
    
    $dateRange = 'Date: ' . ($fromDate ?: 'All') . ' to ' . ($toDate ?: 'All');
    $sheet->setCellValue('A' . $row, $dateRange);
    $row++;
    
    $sheet->setCellValue('A' . $row, 'Generated: ' . date('d/m/Y H:i'));
    $row += 2;
    
    // Headers
    $headers = ['Product', 'Grade'];
    if ($allowPrice == 'Y') {
        $headers[] = 'Date';
        $headers[] = 'Price';
    }
    $headers[] = 'Net Weight (kg)';
    if ($allowPrice == 'Y') {
        $headers[] = 'Total Value';
    }
    $headers[] = '% of Product';
    $lastCol = chr(64 + count($headers));
    
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $col++;
    }
    $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($headerStyle);
    $headerRow = $row;
    $row++;
    
    // Sort products by total net weight descending
    uasort($data, function($a, $b) {
        return $b['totals']['net'] <=> $a['totals']['net'];
    });
    
    // Calculate grand total
    $grandTotalNet = 0;
    $grandTotalPrice = [];
    foreach ($data as $productData) {
        $grandTotalNet += $productData['totals']['net'];
        foreach ($productData['totals']['price'] as $cur => $amt) {
            if (!isset($grandTotalPrice[$cur])) $grandTotalPrice[$cur] = 0;
            $grandTotalPrice[$cur] += $amt;
        }
    }
    
    // Data rows
    foreach ($data as $productName => $productData) {
        // Sort grades by net weight descending
        uasort($productData['grades'], function($a, $b) {
            return $b['totals']['net'] <=> $a['totals']['net'];
        });
        
        $productNet = $productData['totals']['net'];
        $productPct = $grandTotalNet > 0 ? ($productNet / $grandTotalNet * 100) : 0;
        
        // Product header row
        $sheet->setCellValue('A' . $row, $productName);
        $colIdx = 'B';
        $sheet->setCellValue($colIdx++ . $row, '');
        if ($allowPrice == 'Y') {
            $sheet->setCellValue($colIdx++ . $row, '');
            $sheet->setCellValue($colIdx++ . $row, '');
        }
        $sheet->setCellValue($colIdx++ . $row, $productNet);
        if ($allowPrice == 'Y') {
            $priceStr = '';
            foreach ($productData['totals']['price'] as $cur => $amt) {
                $priceStr .= ($priceStr ? ', ' : '') . $cur . ' ' . number_format($amt, 2);
            }
            $sheet->setCellValue($colIdx++ . $row, $priceStr);
        }
        $sheet->setCellValue($colIdx . $row, number_format($productPct, 1) . '%');
        $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($productStyle);
        $row++;
        
        // Grade rows
        foreach ($productData['grades'] as $gradeName => $gradeData) {
            $gradeNet = $gradeData['totals']['net'];
            $gradePct = $productNet > 0 ? ($gradeNet / $productNet * 100) : 0;
            
            // Sort date+price entries by date
            $datePrices = $gradeData['datePrices'];
            uasort($datePrices, function($a, $b) {
                return strcmp($a['date'], $b['date']);
            });
            
            // Check if we need to show date breakdown (multiple dates/prices)
            $showDateBreakdown = $allowPrice == 'Y' && count($datePrices) > 1;
            
            // Grade subtotal row
            $sheet->setCellValue('A' . $row, '');
            $colIdx = 'B';
            $sheet->setCellValue($colIdx++ . $row, $gradeName);
            if ($allowPrice == 'Y') {
                $sheet->setCellValue($colIdx++ . $row, '');
                $sheet->setCellValue($colIdx++ . $row, '');
            }
            $sheet->setCellValue($colIdx++ . $row, $gradeNet);
            if ($allowPrice == 'Y') {
                $priceStr = '';
                foreach ($gradeData['totals']['price'] as $cur => $amt) {
                    $priceStr .= ($priceStr ? ', ' : '') . $cur . ' ' . number_format($amt, 2);
                }
                $sheet->setCellValue($colIdx++ . $row, $priceStr);
            }
            $sheet->setCellValue($colIdx . $row, number_format($gradePct, 1) . '%');
            $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($gradeStyle);
            $row++;
            
            // Date+Price breakdown rows (only if multiple entries and price is enabled)
            if ($showDateBreakdown) {
                foreach ($datePrices as $dpData) {
                    $dateFormatted = DateTime::createFromFormat('Y-m-d', $dpData['date'])->format('d/m/Y');
                    $dpPct = $gradeNet > 0 ? ($dpData['net'] / $gradeNet * 100) : 0;
                    
                    $sheet->setCellValue('A' . $row, '');
                    $colIdx = 'B';
                    $sheet->setCellValue($colIdx++ . $row, '');
                    $sheet->setCellValue($colIdx++ . $row, $dateFormatted);
                    $sheet->setCellValue($colIdx++ . $row, $dpData['currency'] . ' ' . number_format($dpData['price'], 2));
                    $sheet->setCellValue($colIdx++ . $row, $dpData['net']);
                    $sheet->setCellValue($colIdx++ . $row, $dpData['currency'] . ' ' . number_format($dpData['total'], 2));
                    $sheet->setCellValue($colIdx . $row, number_format($dpPct, 1) . '%');
                    $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($dateRowStyle);
                    $row++;
                }
            }
        }
    }
    
    // Grand total row
    $row++;
    $sheet->setCellValue('A' . $row, 'GRAND TOTAL');
    $colIdx = 'B';
    $sheet->setCellValue($colIdx++ . $row, '');
    if ($allowPrice == 'Y') {
        $sheet->setCellValue($colIdx++ . $row, '');
        $sheet->setCellValue($colIdx++ . $row, '');
    }
    $sheet->setCellValue($colIdx++ . $row, $grandTotalNet);
    if ($allowPrice == 'Y') {
        $priceStr = '';
        foreach ($grandTotalPrice as $cur => $amt) {
            $priceStr .= ($priceStr ? ', ' : '') . $cur . ' ' . number_format($amt, 2);
        }
        $sheet->setCellValue($colIdx++ . $row, $priceStr);
    }
    $sheet->setCellValue($colIdx . $row, '100%');
    $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($grandTotalStyle);
    
    // Auto-size columns
    foreach (range('A', $lastCol) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Number format for weight column
    $weightCol = $allowPrice == 'Y' ? 'E' : 'C';
    $sheet->getStyle($weightCol . ($headerRow + 1) . ':' . $weightCol . $row)->getNumberFormat()->setFormatCode('#,##0.00');
}

// Determine which sheet to create based on status filter
if ($status === 'RECEIVING') {
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Receiving');
    writeGradeSheet($sheet, $dataRecv, $allowPrice, $fromDate, $toDate, 'Receiving', $headerStyle, $productStyle, $gradeStyle, $dateRowStyle, $grandTotalStyle, '17a2b8');
} elseif ($status === 'DISPATCH') {
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Dispatch');
    writeGradeSheet($sheet, $dataDisp, $allowPrice, $fromDate, $toDate, 'Dispatch', $headerStyle, $productStyle, $gradeStyle, $dateRowStyle, $grandTotalStyle, '28a745');
} else {
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('No Data');
    $sheet->setCellValue('A1', 'No data found for the selected filters.');
}

// Output
$statusLabel = ($status === 'RECEIVING') ? 'Receiving' : 'Dispatch';
$fileName = 'Grade_Distribution_' . $statusLabel . '_' . date('Y-m-d') . '.xlsx';
$writer = new Xlsx($spreadsheet);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
