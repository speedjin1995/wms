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
$customerId = $_GET['customer'] ?? '';
$locationId = $_GET['location'] ?? '';

// Build search query
$searchQuery = " AND w.status IN ('DISPATCH','OUTGOING')";

if ($fromDate != '') {
    $fromDateObj = DateTime::createFromFormat('d/m/Y', $fromDate);
    $searchQuery .= " AND DATE(w.start_time) >= '" . $fromDateObj->format('Y-m-d') . "'";
}
if ($toDate != '') {
    $toDateObj = DateTime::createFromFormat('d/m/Y', $toDate);
    $searchQuery .= " AND DATE(w.start_time) <= '" . $toDateObj->format('Y-m-d') . "'";
}
if ($customerId != '') {
    $customerId = mysqli_real_escape_string($db, $customerId);
    $searchQuery .= " AND w.customer = '$customerId'";
}
if ($locationId != '') {
    $locationId = mysqli_real_escape_string($db, $locationId);
    $searchQuery .= " AND w.location = '$locationId'";
}

$companyFilter = ($role != 'SADMIN') ? " AND w.company = '$company'" : '';

// Fetch records
$query = "SELECT w.*, c.customer_name
          FROM wholesales w
          LEFT JOIN customers c ON w.customer = c.id
          WHERE w.deleted = 0" . $companyFilter . $searchQuery . "
          ORDER BY c.customer_name, w.start_time";
$result = mysqli_query($db, $query);

// Build hierarchical data: Customer > Product > Grade > Records
$data = [];
$productCache = [];
$currencyCache = [];

while ($row = mysqli_fetch_assoc($result)) {
    $customerName = $row['customer_name'] ?: 'Unknown';
    $details = json_decode($row['weight_details'], true) ?: [];
    
    if (!isset($data[$customerName])) {
        $data[$customerName] = ['products' => [], 'totals' => ['net' => 0, 'gross' => 0, 'tare' => 0, 'price' => []]];
    }
    
    foreach ($details as $item) {
        $productId = $item['product'] ?? '';
        $productName = 'Unknown';
        if ($productId != '') {
            $pRow = getProductById($productId, $db, $productCache);
            $productName = $pRow['product_name'] ?? 'Unknown';
        }
        
        $gradeName = $item['grade'] ?? 'Unknown';
        $net   = floatval($item['net'] ?? 0);
        $gross = floatval($item['gross'] ?? 0);
        $tare  = floatval($item['tare'] ?? 0);
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
        
        if (!isset($data[$customerName]['products'][$productName])) {
            $data[$customerName]['products'][$productName] = ['grades' => [], 'totals' => ['net' => 0, 'gross' => 0, 'tare' => 0, 'price' => []]];
        }
        if (!isset($data[$customerName]['products'][$productName]['grades'][$gradeName])) {
            $data[$customerName]['products'][$productName]['grades'][$gradeName] = ['records' => [], 'totals' => ['net' => 0, 'gross' => 0, 'tare' => 0, 'price' => []]];
        }
        
        $startTime = new DateTime($row['start_time']);
        $record = [
            'serial_no' => $row['serial_no'],
            'date'      => $startTime->format('d/m/Y'),
            'time'      => $startTime->format('H:i'),
            'gross'     => $gross,
            'tare'      => $tare,
            'net'       => $net,
            'currency'  => $currency,
            'price'     => $price,
            'total'     => $total
        ];
        
        $data[$customerName]['products'][$productName]['grades'][$gradeName]['records'][] = $record;
        
        // Grade totals
        $data[$customerName]['products'][$productName]['grades'][$gradeName]['totals']['net'] += $net;
        $data[$customerName]['products'][$productName]['grades'][$gradeName]['totals']['gross'] += $gross;
        $data[$customerName]['products'][$productName]['grades'][$gradeName]['totals']['tare'] += $tare;
        if (!isset($data[$customerName]['products'][$productName]['grades'][$gradeName]['totals']['price'][$currency])) {
            $data[$customerName]['products'][$productName]['grades'][$gradeName]['totals']['price'][$currency] = 0;
        }
        $data[$customerName]['products'][$productName]['grades'][$gradeName]['totals']['price'][$currency] += $total;
        
        // Product totals
        $data[$customerName]['products'][$productName]['totals']['net'] += $net;
        $data[$customerName]['products'][$productName]['totals']['gross'] += $gross;
        $data[$customerName]['products'][$productName]['totals']['tare'] += $tare;
        if (!isset($data[$customerName]['products'][$productName]['totals']['price'][$currency])) {
            $data[$customerName]['products'][$productName]['totals']['price'][$currency] = 0;
        }
        $data[$customerName]['products'][$productName]['totals']['price'][$currency] += $total;
        
        // Customer totals
        $data[$customerName]['totals']['net'] += $net;
        $data[$customerName]['totals']['gross'] += $gross;
        $data[$customerName]['totals']['tare'] += $tare;
        if (!isset($data[$customerName]['totals']['price'][$currency])) {
            $data[$customerName]['totals']['price'][$currency] = 0;
        }
        $data[$customerName]['totals']['price'][$currency] += $total;
    }
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Customer Breakdown');

// Styles
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '28a745']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
];
$customerStyle = [
    'font' => ['bold' => true, 'size' => 12],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4EDDA']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$productStyle = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E3E5']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$gradeStyle = [
    'font' => ['bold' => true, 'italic' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3CD']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$dataStyle = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

// Title row
$row = 1;
$sheet->setCellValue('A' . $row, 'Customer Weighing Breakdown Report');
$sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
$row++;

// Date range
$dateRange = 'Date: ' . ($fromDate ?: 'All') . ' to ' . ($toDate ?: 'All');
$sheet->setCellValue('A' . $row, $dateRange);
$row++;

// Generated timestamp
$sheet->setCellValue('A' . $row, 'Generated: ' . date('d/m/Y H:i'));
$row += 2;

// Headers
$headers = ['Serial No', 'Date', 'Time', 'Gross (kg)', 'Tare (kg)', 'Net (kg)'];
if ($allowPrice == 'Y') {
    $headers = array_merge($headers, ['Currency', 'Price', 'Total']);
}
$lastCol = chr(64 + count($headers));

$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . $row, $header);
    $col++;
}
$sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($headerStyle);
$row++;

// Sort customers by total net weight descending (highest kg first)
uasort($data, function($a, $b) {
    return $b['totals']['net'] <=> $a['totals']['net'];
});

// Data rows
foreach ($data as $customerName => $customerData) {
    // Sort products by total net weight descending
    uasort($customerData['products'], function($a, $b) {
        return $b['totals']['net'] <=> $a['totals']['net'];
    });
    // Customer header row
    $sheet->setCellValue('A' . $row, $customerName);
    $sheet->mergeCells('A' . $row . ':C' . $row);
    $sheet->setCellValue('D' . $row, $customerData['totals']['gross']);
    $sheet->setCellValue('E' . $row, $customerData['totals']['tare']);
    $sheet->setCellValue('F' . $row, $customerData['totals']['net']);
    if ($allowPrice == 'Y') {
        $priceStr = '';
        foreach ($customerData['totals']['price'] as $cur => $amt) {
            $priceStr .= ($priceStr ? ', ' : '') . $cur . ' ' . number_format($amt, 2);
        }
        $sheet->setCellValue('I' . $row, $priceStr);
    }
    $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($customerStyle);
    $row++;
    
    foreach ($customerData['products'] as $productName => $productData) {
        // Sort grades by total net weight descending
        uasort($productData['grades'], function($a, $b) {
            return $b['totals']['net'] <=> $a['totals']['net'];
        });
        // Product header row
        $sheet->setCellValue('A' . $row, '  ' . $productName);
        $sheet->mergeCells('A' . $row . ':C' . $row);
        $sheet->setCellValue('D' . $row, $productData['totals']['gross']);
        $sheet->setCellValue('E' . $row, $productData['totals']['tare']);
        $sheet->setCellValue('F' . $row, $productData['totals']['net']);
        if ($allowPrice == 'Y') {
            $priceStr = '';
            foreach ($productData['totals']['price'] as $cur => $amt) {
                $priceStr .= ($priceStr ? ', ' : '') . $cur . ' ' . number_format($amt, 2);
            }
            $sheet->setCellValue('I' . $row, $priceStr);
        }
        $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($productStyle);
        $row++;
        
        foreach ($productData['grades'] as $gradeName => $gradeData) {
            // Grade header row
            $sheet->setCellValue('A' . $row, '    ' . $gradeName);
            $sheet->mergeCells('A' . $row . ':C' . $row);
            $sheet->setCellValue('D' . $row, $gradeData['totals']['gross']);
            $sheet->setCellValue('E' . $row, $gradeData['totals']['tare']);
            $sheet->setCellValue('F' . $row, $gradeData['totals']['net']);
            if ($allowPrice == 'Y') {
                $priceStr = '';
                foreach ($gradeData['totals']['price'] as $cur => $amt) {
                    $priceStr .= ($priceStr ? ', ' : '') . $cur . ' ' . number_format($amt, 2);
                }
                $sheet->setCellValue('I' . $row, $priceStr);
            }
            $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($gradeStyle);
            $row++;
            
            // Record rows
            foreach ($gradeData['records'] as $record) {
                $sheet->setCellValue('A' . $row, $record['serial_no']);
                $sheet->setCellValue('B' . $row, $record['date']);
                $sheet->setCellValue('C' . $row, $record['time']);
                $sheet->setCellValue('D' . $row, $record['gross']);
                $sheet->setCellValue('E' . $row, $record['tare']);
                $sheet->setCellValue('F' . $row, $record['net']);
                if ($allowPrice == 'Y') {
                    $sheet->setCellValue('G' . $row, $record['currency']);
                    $sheet->setCellValue('H' . $row, $record['price']);
                    $sheet->setCellValue('I' . $row, $record['total']);
                }
                $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($dataStyle);
                $row++;
            }
        }
    }
}

// Auto-size columns
foreach (range('A', $lastCol) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Number format for weight and price columns
$sheet->getStyle('D6:F' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
if ($allowPrice == 'Y') {
    $sheet->getStyle('H6:I' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
}

// Output
$fileName = 'Customer_Breakdown_' . date('Y-m-d') . '.xlsx';
$writer = new Xlsx($spreadsheet);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
