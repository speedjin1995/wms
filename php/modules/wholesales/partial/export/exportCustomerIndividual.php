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
$customerId = $_GET['customer'] ?? '';
$partyType  = $_GET['partyType'] ?? '';

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
if ($partyType != '') {
    $partyType = mysqli_real_escape_string($db, $partyType);
    $searchQuery .= " AND c.customer_type = '$partyType'";
}
$companyFilter = ($role != 'SADMIN') ? " AND w.company = '$company'" : '';

$query = "SELECT w.*, c.customer_name
          FROM wholesales w
          LEFT JOIN customers c ON w.customer = c.id
          WHERE w.deleted = 0" . $companyFilter . $searchQuery . "
          ORDER BY c.customer_name, w.start_time";
$result = mysqli_query($db, $query);

$data          = [];
$productCache  = [];
$currencyCache = [];

while ($row = mysqli_fetch_assoc($result)) {
    $customerName = $row['customer_name'] ?: 'Unknown';
    $details      = json_decode($row['weight_details'], true) ?: [];
    $startTime    = new DateTime($row['start_time']);
    $dateKey      = $startTime->format('Y-m-d');  // sort key
    $dateDisplay  = $startTime->format('d-M-y');  // display label
    $serialNo     = $row['serial_no'] ?? '';

    if (!isset($data[$customerName]))           $data[$customerName] = [];
    if (!isset($data[$customerName][$dateKey])) $data[$customerName][$dateKey] = ['display' => $dateDisplay, 'products' => []];

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

        $p = &$data[$customerName][$dateKey]['products'];
        if (!isset($p[$productName]))                     $p[$productName] = [];
        if (!isset($p[$productName][$gradeName]))         $p[$productName][$gradeName] = [];
        if (!isset($p[$productName][$gradeName][$cpKey])) {
                $p[$productName][$gradeName][$cpKey] = ['net' => 0, 'price' => $price, 'total' => 0, 'currency' => $currency, 'do_po_no' => []];
        }
        $p[$productName][$gradeName][$cpKey]['net']   += $net;
        $p[$productName][$gradeName][$cpKey]['total'] += $total;
        $poNo = $row['po_no'] ?? '';
        if ($poNo != '' && !in_array($poNo, $p[$productName][$gradeName][$cpKey]['do_po_no'])) {
            $p[$productName][$gradeName][$cpKey]['do_po_no'][] = $poNo;
        }
        unset($p);
    }
}

$spreadsheet = new Spreadsheet();
$spreadsheet->removeSheetByIndex(0);

$headerStyle = [
    'font'      => ['bold' => true],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '92D050']],
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

foreach ($data as $customerName => $dateGroups) {
    $sheetTitle = mb_substr(preg_replace('/[\/\\\?\*\[\]:]/', '', $customerName), 0, 31);
    $sheet      = $spreadsheet->createSheet();
    $sheet->setTitle($sheetTitle);

    $lastCol = ($allowPrice == 'Y') ? 'I' : 'G';

    $r = 1;
    $sheet->setCellValue('A'.$r, 'Date');
    $sheet->setCellValue('B'.$r, 'DO/PO No.');
    $sheet->setCellValue('C'.$r, 'Customer');
    $sheet->setCellValue('D'.$r, 'Product');
    $sheet->setCellValue('E'.$r, 'Grade');
    $sheet->setCellValue('F'.$r, 'Kg');
    $sheet->setCellValue('G'.$r, 'Currency');
    if ($allowPrice == 'Y') {
        $sheet->setCellValue('H'.$r, 'U.Price');
        $sheet->setCellValue('I'.$r, 'Total');
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
                    $doPoList = [];
                    foreach ($entry['do_po_no'] as $i => $s) {
                        $doPoList[] = ($i + 1) . '. ' . $s;
                    }
                    $dayRows[] = [
                        'date'     => $firstRow ? $dateData['display'] : '',
                        'product'  => $productName,
                        'grade'    => $gradeName,
                        'currency' => $entry['currency'],
                        'net'      => $entry['net'],
                        'price'    => $entry['price'],
                        'total'    => $entry['total'],
                        'do_po_no' => implode("
", $doPoList),
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
            $sheet->setCellValue('B'.$r, $dr['do_po_no']);
            $sheet->setCellValue('C'.$r, ($firstDataRow && $dr['date'] != '') ? $customerName : '');
            $sheet->setCellValue('D'.$r, $dr['product']);
            $firstDataRow = false;
            $sheet->setCellValue('E'.$r, $dr['grade']);
            $sheet->setCellValue('F'.$r, $dr['net']);
            $sheet->setCellValue('G'.$r, $dr['currency']);
            if ($allowPrice == 'Y') {
                $sheet->setCellValue('H'.$r, $dr['price']);
                $sheet->setCellValue('I'.$r, $dr['total']);
            }
            $sheet->getStyle('B'.$r)->getAlignment()->setWrapText(true);
            $sheet->getStyle('A'.$r.':'.$lastCol.$r)->applyFromArray($dataStyle);
            $r++;
        }
    }

    // Grand total rows — one per currency
    $firstGrandRow = true;
    foreach ($grandTotalByCurrency as $cur => $amt) {
        $sheet->setCellValue('A'.$r, $firstGrandRow ? 'Grand Total' : '');
        $sheet->setCellValue('F'.$r, $grandTotalKgByCurrency[$cur]);
        $sheet->setCellValue('G'.$r, $cur);
        if ($allowPrice == 'Y') $sheet->setCellValue('I'.$r, $amt);
        $sheet->getStyle('A'.$r.':'.$lastCol.$r)->applyFromArray($grandTotalStyle);
        $r++;
        $firstGrandRow = false;
    }
    $r--; // point back to last written row for number format range

    foreach (range('A', $lastCol) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $sheet->getStyle('F2:F'.$r)->getNumberFormat()->setFormatCode('#,##0.00');
    if ($allowPrice == 'Y') {
        $sheet->getStyle('H2:I'.$r)->getNumberFormat()->setFormatCode('#,##0.00');
    }
}

$fileName = ($partyType ? $partyType . '_' : '') . 'Customer_Individual_' . date('Y-m-d') . '.xlsx';
$writer   = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit;
