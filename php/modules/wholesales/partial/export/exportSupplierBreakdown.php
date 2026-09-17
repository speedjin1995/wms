<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$company = $_SESSION['customer'];
$role    = $_SESSION['role'];
$companyDetail = searchCompanyById($company, $db);
$allowPrice = $companyDetail['include_price'];

// Get filter params
$fromDate   = $_GET['fromDate'] ?? '';
$toDate     = $_GET['toDate'] ?? '';
$supplierId = $_GET['supplier'] ?? '';
$locationId = $_GET['location'] ?? '';
$partyType  = $_GET['partyType'] ?? '';

// Build search query
$searchQuery = " AND w.status IN ('RECEIVING','INCOMING')";

if ($fromDate != '') {
    $fromDateObj = DateTime::createFromFormat('d/m/Y', $fromDate);
    $searchQuery .= " AND DATE(w.start_time) >= '" . $fromDateObj->format('Y-m-d') . "'";
}
if ($toDate != '') {
    $toDateObj = DateTime::createFromFormat('d/m/Y', $toDate);
    $searchQuery .= " AND DATE(w.start_time) <= '" . $toDateObj->format('Y-m-d') . "'";
}
if ($supplierId != '') {
    $supplierId = mysqli_real_escape_string($db, $supplierId);
    $searchQuery .= " AND w.supplier = '$supplierId'";
}
if ($locationId != '') {
    $locationId = mysqli_real_escape_string($db, $locationId);
    $searchQuery .= " AND w.location = '$locationId'";
}
if ($partyType != '') {
    $partyType = mysqli_real_escape_string($db, $partyType);
    $searchQuery .= " AND s.supplier_type = '$partyType'";
}

$companyFilter = ($role != 'SADMIN') ? " AND w.company = '$company'" : '';

// Fetch records
$query = "SELECT w.*, s.supplier_name
          FROM wholesales w
          LEFT JOIN supplies s ON w.supplier = s.id
          WHERE w.deleted = 0" . $companyFilter . $searchQuery . "
          ORDER BY s.supplier_name, w.start_time";
$result = mysqli_query($db, $query);

// Build hierarchical data: Supplier > Product > Grade > Records
$data = [];
$productCache = [];
$currencyCache = [];
$allCurrencies = [];

while ($row = mysqli_fetch_assoc($result)) {
    $supplierName = $row['supplier_name'] ?: 'Unknown';
    $details = json_decode($row['weight_details'], true) ?: [];
    
    if (!isset($data[$supplierName])) {
        $data[$supplierName] = ['products' => [], 'totals' => ['net' => 0, 'gross' => 0, 'tare' => 0, 'price' => []]];
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
        if (!in_array($currency, $allCurrencies)) $allCurrencies[] = $currency;
        
        if (!isset($data[$supplierName]['products'][$productName])) {
            $data[$supplierName]['products'][$productName] = ['grades' => [], 'totals' => ['net' => 0, 'gross' => 0, 'tare' => 0, 'price' => []]];
        }
        if (!isset($data[$supplierName]['products'][$productName]['grades'][$gradeName])) {
            $data[$supplierName]['products'][$productName]['grades'][$gradeName] = ['records' => [], 'totals' => ['net' => 0, 'gross' => 0, 'tare' => 0, 'price' => []]];
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
        
        $data[$supplierName]['products'][$productName]['grades'][$gradeName]['records'][] = $record;
        
        // Grade totals
        $data[$supplierName]['products'][$productName]['grades'][$gradeName]['totals']['net']   += $net;
        $data[$supplierName]['products'][$productName]['grades'][$gradeName]['totals']['gross'] += $gross;
        $data[$supplierName]['products'][$productName]['grades'][$gradeName]['totals']['tare']  += $tare;
        if (!isset($data[$supplierName]['products'][$productName]['grades'][$gradeName]['totals']['price'][$currency]))
            $data[$supplierName]['products'][$productName]['grades'][$gradeName]['totals']['price'][$currency] = 0;
        $data[$supplierName]['products'][$productName]['grades'][$gradeName]['totals']['price'][$currency] += $total;
        
        // Product totals
        $data[$supplierName]['products'][$productName]['totals']['net']   += $net;
        $data[$supplierName]['products'][$productName]['totals']['gross'] += $gross;
        $data[$supplierName]['products'][$productName]['totals']['tare']  += $tare;
        if (!isset($data[$supplierName]['products'][$productName]['totals']['price'][$currency]))
            $data[$supplierName]['products'][$productName]['totals']['price'][$currency] = 0;
        $data[$supplierName]['products'][$productName]['totals']['price'][$currency] += $total;
        
        // Supplier totals
        $data[$supplierName]['totals']['net']   += $net;
        $data[$supplierName]['totals']['gross'] += $gross;
        $data[$supplierName]['totals']['tare']  += $tare;
        if (!isset($data[$supplierName]['totals']['price'][$currency]))
            $data[$supplierName]['totals']['price'][$currency] = 0;
        $data[$supplierName]['totals']['price'][$currency] += $total;
    }
}

sort($allCurrencies);

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Supplier Breakdown');

// Styles
$headerStyle = [
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17a2b8']],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
];
$supplierStyle = [
    'font'    => ['bold' => true, 'size' => 12],
    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1ECF1']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$productStyle = [
    'font'    => ['bold' => true],
    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E3E5']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$gradeStyle = [
    'font'    => ['bold' => true, 'italic' => true],
    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3CD']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$dataStyle = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$grandTotalStyle = [
    'font'    => ['bold' => true],
    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

// Column layout:
// A=Serial No, B=Date, C=Time, D=Gross, E=Tare, F=Net
// if allowPrice: G=U.Price, H..=currency cols
// if !allowPrice: G..=currency cols
$fixedCols  = ($allowPrice == 'Y') ? 7 : 6;
$numCur     = count($allCurrencies);
$lastColIdx = $fixedCols + $numCur;
$lastCol    = Coordinate::stringFromColumnIndex($lastColIdx);

// Title row
$row = 1;
$sheet->setCellValue('A' . $row, ($partyType ? $partyType . ' ' : '') . 'Supplier Weighing Breakdown Report');
$sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
$row++;

// Date range
$sheet->setCellValue('A' . $row, 'Date: ' . ($fromDate ?: 'All') . ' to ' . ($toDate ?: 'All'));
$row++;

// Generated timestamp
$sheet->setCellValue('A' . $row, 'Generated: ' . date('d/m/Y H:i'));
$row += 2;

// Headers
$sheet->setCellValue('A' . $row, 'Serial No');
$sheet->setCellValue('B' . $row, 'Date');
$sheet->setCellValue('C' . $row, 'Time');
$sheet->setCellValue('D' . $row, 'Gross (kg)');
$sheet->setCellValue('E' . $row, 'Tare (kg)');
$sheet->setCellValue('F' . $row, 'Net (kg)');
if ($allowPrice == 'Y') {
    $sheet->setCellValue('G' . $row, 'U.Price');
    foreach ($allCurrencies as $i => $cur) {
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($fixedCols + $i + 1) . $row, $cur);
    }
} else {
    foreach ($allCurrencies as $i => $cur) {
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($fixedCols + $i + 1) . $row, $cur);
    }
}
$sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($headerStyle);
$row++;

// Sort suppliers by total net weight descending
uasort($data, function($a, $b) {
    return $b['totals']['net'] <=> $a['totals']['net'];
});

$grandTotalByCurrency = [];
foreach ($allCurrencies as $cur) $grandTotalByCurrency[$cur] = 0;

// Data rows
foreach ($data as $supplierName => $supplierData) {
    uasort($supplierData['products'], function($a, $b) {
        return $b['totals']['net'] <=> $a['totals']['net'];
    });

    // Supplier header row
    $sheet->setCellValue('A' . $row, $supplierName);
    $sheet->mergeCells('A' . $row . ':C' . $row);
    $sheet->setCellValue('D' . $row, $supplierData['totals']['gross']);
    $sheet->setCellValue('E' . $row, $supplierData['totals']['tare']);
    $sheet->setCellValue('F' . $row, $supplierData['totals']['net']);
    if ($allowPrice == 'Y') {
        foreach ($allCurrencies as $i => $cur) {
            $amt = $supplierData['totals']['price'][$cur] ?? 0;
            if ($amt != 0) $sheet->setCellValue(Coordinate::stringFromColumnIndex($fixedCols + $i + 1) . $row, $amt);
        }
    }
    $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($supplierStyle);
    $row++;
    
    foreach ($supplierData['products'] as $productName => $productData) {
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
            foreach ($allCurrencies as $i => $cur) {
                $amt = $productData['totals']['price'][$cur] ?? 0;
                if ($amt != 0) $sheet->setCellValue(Coordinate::stringFromColumnIndex($fixedCols + $i + 1) . $row, $amt);
            }
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
                foreach ($allCurrencies as $i => $cur) {
                    $amt = $gradeData['totals']['price'][$cur] ?? 0;
                    if ($amt != 0) $sheet->setCellValue(Coordinate::stringFromColumnIndex($fixedCols + $i + 1) . $row, $amt);
                }
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
                    $sheet->setCellValue('G' . $row, $record['price']);
                    foreach ($allCurrencies as $i => $cur) {
                        if ($record['currency'] === $cur) {
                            $sheet->setCellValue(Coordinate::stringFromColumnIndex($fixedCols + $i + 1) . $row, $record['total']);
                        }
                    }
                }
                $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($dataStyle);
                $row++;
            }
        }
    }

    // Accumulate grand totals
    foreach ($allCurrencies as $cur) {
        $grandTotalByCurrency[$cur] += $supplierData['totals']['price'][$cur] ?? 0;
    }
}

// Grand Total row
$sheet->setCellValue('A' . $row, 'Grand Total');
$sheet->mergeCells('A' . $row . ':C' . $row);
if ($allowPrice == 'Y') {
    foreach ($allCurrencies as $i => $cur) {
        if ($grandTotalByCurrency[$cur] != 0)
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($fixedCols + $i + 1) . $row, $grandTotalByCurrency[$cur]);
    }
}
$sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($grandTotalStyle);
$row++;

// Auto-size columns
foreach (range(1, $lastColIdx) as $colIdx) {
    $sheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
}

// Number format
$sheet->getStyle('D6:F' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
if ($allowPrice == 'Y') {
    $sheet->getStyle('G6:G' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
    foreach ($allCurrencies as $i => $cur) {
        $colLetter = Coordinate::stringFromColumnIndex($fixedCols + $i + 1);
        $sheet->getStyle($colLetter . '6:' . $colLetter . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
    }
}

// Output
$fileName = ($partyType ? $partyType . '_' : '') . 'Supplier_Breakdown_' . date('Y-m-d') . '.xlsx';
$writer = new Xlsx($spreadsheet);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
