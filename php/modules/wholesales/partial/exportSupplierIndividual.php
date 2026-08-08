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

$fromDate   = $_GET['fromDate'] ?? '';
$toDate     = $_GET['toDate'] ?? '';
$supplierId = $_GET['supplier'] ?? '';

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
$companyFilter = ($role != 'SADMIN') ? " AND w.company = '$company'" : '';

$query = "SELECT w.*, s.supplier_name
          FROM wholesales w
          LEFT JOIN supplies s ON w.supplier = s.id
          WHERE w.deleted = 0" . $companyFilter . $searchQuery . "
          ORDER BY s.supplier_name, w.start_time";
$result = mysqli_query($db, $query);

$data          = [];
$productCache  = [];
$currencyCache = [];

while ($row = mysqli_fetch_assoc($result)) {
    $supplierName = $row['supplier_name'] ?: 'Unknown';
    $details      = json_decode($row['weight_details'], true) ?: [];
    $startTime    = new DateTime($row['start_time']);
    $dateKey      = $startTime->format('Y-m-d');  // sort key
    $dateDisplay  = $startTime->format('d-M-y');  // display label

    if (!isset($data[$supplierName]))           $data[$supplierName] = [];
    if (!isset($data[$supplierName][$dateKey])) $data[$supplierName][$dateKey] = ['display' => $dateDisplay, 'products' => []];

    foreach ($details as $item) {
        $productId = $item['product'] ?? '';
        if ($productId != '') {
            $pRow        = getProductById($productId, $db, $productCache);
            $productName = $pRow['product_name'] ?? 'Unknown';
        } else {
            $productName = 'Unknown';
        }
        $gradeName = $item['grade'] ?? '';
        $net       = floatval($item['net']   ?? 0);
        $price     = floatval($item['price'] ?? 0);
        $total     = floatval($item['total'] ?? 0);
        $curId     = $item['currency'] ?? '';
        if ($curId != '') {
            if (!isset($currencyCache[$curId])) $currencyCache[$curId] = searchCurrencyNameById($curId, $db) ?: 'MYR';
            $currency = $currencyCache[$curId];
        } else {
            $currency = 'MYR';
        }
        $cpKey = $currency . '|' . number_format($price, 2);

        $p = &$data[$supplierName][$dateKey]['products'];
        if (!isset($p[$productName]))                     $p[$productName] = [];
        if (!isset($p[$productName][$gradeName]))         $p[$productName][$gradeName] = [];
        if (!isset($p[$productName][$gradeName][$cpKey])) {
            $p[$productName][$gradeName][$cpKey] = ['net' => 0, 'price' => $price, 'total' => 0, 'currency' => $currency];
        }
        $p[$productName][$gradeName][$cpKey]['net']   += $net;
        $p[$productName][$gradeName][$cpKey]['total'] += $total;
        unset($p);
    }
}

$spreadsheet = new Spreadsheet();
$spreadsheet->removeSheetByIndex(0);

$headerStyle = [
    'font'      => ['bold' => true],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17A2B8']],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
];
$dataStyle = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$grandTotalStyle = [
    'font'    => ['bold' => true],
    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];

foreach ($data as $supplierName => $dateGroups) {
    $sheetTitle = mb_substr(preg_replace('/[\/\\\?\*\[\]:]/', '', $supplierName), 0, 31);
    $sheet      = $spreadsheet->createSheet();
    $sheet->setTitle($sheetTitle);

    $lastCol = ($allowPrice == 'Y') ? 'H' : 'F';

    $r = 1;
    $sheet->setCellValue('A'.$r, 'Date');
    $sheet->setCellValue('B'.$r, 'Supplier');
    $sheet->setCellValue('C'.$r, 'Product');
    $sheet->setCellValue('D'.$r, 'Grade');
    $sheet->setCellValue('E'.$r, 'Kg');
    $sheet->setCellValue('F'.$r, 'Currency');
    if ($allowPrice == 'Y') {
        $sheet->setCellValue('G'.$r, 'U.Price');
        $sheet->setCellValue('H'.$r, 'Total');
    }
    $sheet->getStyle('A'.$r.':'.$lastCol.$r)->applyFromArray($headerStyle);
    $r++;

    $grandTotalKgByCurrency = [];
    $grandTotalByCurrency    = [];

    ksort($dateGroups);
    foreach ($dateGroups as $dateKey => $dateData) {
        $products = $dateData['products'];
        $dayRows  = [];
        $firstRow = true;

        foreach ($products as $productName => $grades) {
            foreach ($grades as $gradeName => $cpEntries) {
                foreach ($cpEntries as $entry) {
                    $dayRows[] = [
                        'date'     => $firstRow ? $dateData['display'] : '',
                        'product'  => $productName,
                        'grade'    => $gradeName,
                        'currency' => $entry['currency'],
                        'net'      => $entry['net'],
                        'price'    => $entry['price'],
                        'total'    => $entry['total'],
                    ];
                    $cur = $entry['currency'];
                    if (!isset($grandTotalByCurrency[$cur]))   $grandTotalByCurrency[$cur]   = 0;
                    if (!isset($grandTotalKgByCurrency[$cur])) $grandTotalKgByCurrency[$cur] = 0;
                    $grandTotalByCurrency[$cur]   += $entry['total'];
                    $grandTotalKgByCurrency[$cur] += $entry['net'];
                    $firstRow = false;
                }
            }
        }

        $firstDataRow = true;
        foreach ($dayRows as $dr) {
            $sheet->setCellValue('A'.$r, $dr['date']);
            $sheet->setCellValue('B'.$r, ($firstDataRow && $dr['date'] != '') ? $supplierName : '');
            $sheet->setCellValue('C'.$r, $dr['product']);
            $firstDataRow = false;
            $sheet->setCellValue('D'.$r, $dr['grade']);
            $sheet->setCellValue('E'.$r, $dr['net']);
            $sheet->setCellValue('F'.$r, $dr['currency']);
            if ($allowPrice == 'Y') {
                $sheet->setCellValue('G'.$r, $dr['price']);
                $sheet->setCellValue('H'.$r, $dr['total']);
            }
            $sheet->getStyle('A'.$r.':'.$lastCol.$r)->applyFromArray($dataStyle);
            $r++;
        }
    }

    // Grand total rows — one per currency
    $firstGrandRow = true;
    foreach ($grandTotalByCurrency as $cur => $amt) {
        $sheet->setCellValue('A'.$r, $firstGrandRow ? 'Grand Total' : '');
        $sheet->setCellValue('E'.$r, $grandTotalKgByCurrency[$cur]);
        $sheet->setCellValue('F'.$r, $cur);
        if ($allowPrice == 'Y') $sheet->setCellValue('H'.$r, $amt);
        $sheet->getStyle('A'.$r.':'.$lastCol.$r)->applyFromArray($grandTotalStyle);
        $r++;
        $firstGrandRow = false;
    }
    $r--; // point back to last written row for number format range

    foreach (range('A', $lastCol) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $sheet->getStyle('E2:E'.$r)->getNumberFormat()->setFormatCode('#,##0.00');
    if ($allowPrice == 'Y') {
        $sheet->getStyle('G2:H'.$r)->getNumberFormat()->setFormatCode('#,##0.00');
    }
}

$fileName = 'Supplier_Individual_' . date('Y-m-d') . '.xlsx';
$writer   = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit;
