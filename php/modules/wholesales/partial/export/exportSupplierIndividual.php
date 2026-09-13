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

$fromDate   = $_GET['fromDate'] ?? '';
$toDate     = $_GET['toDate'] ?? '';
$supplierId = $_GET['supplier'] ?? '';
$partyType  = $_GET['partyType'] ?? '';

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
if ($partyType != '') {
    $partyType = mysqli_real_escape_string($db, $partyType);
    $searchQuery .= " AND s.supplier_type = '$partyType'";
}
$companyFilter = ($role != 'SADMIN') ? " AND w.company = '$company'" : '';

$query = "SELECT w.*, s.supplier_name
          FROM wholesales w
          LEFT JOIN supplies s ON w.supplier = s.id
          WHERE w.deleted = 0" . $companyFilter . $searchQuery . "
          ORDER BY s.supplier_name, w.start_time";
$result = mysqli_query($db, $query);

// data[supplierName][dateKey] = ['display'=>..., 'dopos'=>[ doPoNo => [ [product,grade,net,price,total,currency], ... ] ] ]
$data          = [];
$productCache  = [];
$currencyCache = [];

while ($row = mysqli_fetch_assoc($result)) {
    $supplierName = $row['supplier_name'] ?: 'Unknown';
    $details      = json_decode($row['weight_details'], true) ?: [];
    $startTime    = new DateTime($row['start_time']);
    $dateKey      = $startTime->format('Y-m-d');
    $dateDisplay  = $startTime->format('d-M-y');
    $doPoNo       = $row['po_no'] ?? '';

    if (!isset($data[$supplierName]))                          $data[$supplierName] = [];
    if (!isset($data[$supplierName][$dateKey]))                $data[$supplierName][$dateKey] = ['display' => $dateDisplay, 'dopos' => []];
    if (!isset($data[$supplierName][$dateKey]['dopos'][$doPoNo])) $data[$supplierName][$dateKey]['dopos'][$doPoNo] = [];

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

        $data[$supplierName][$dateKey]['dopos'][$doPoNo][] = [
            'product'  => $productName,
            'grade'    => $gradeName,
            'net'      => $net,
            'price'    => $price,
            'total'    => $total,
            'currency' => $currency,
        ];
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
$subTotalStyle = [
    'font'    => ['bold' => true, 'color' => ['rgb' => 'FF0000']],
    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FAEBD7']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$grandTotalStyle = [
    'font'    => ['bold' => true],
    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];

foreach ($data as $supplierName => $dateGroups) {
    // --- collect all unique currencies for this sheet (sorted) ---
    $sheetCurrencies = [];
    ksort($dateGroups);
    foreach ($dateGroups as $dateData) {
        foreach ($dateData['dopos'] as $rows) {
            foreach ($rows as $entry) {
                $cur = $entry['currency'];
                if ($cur != '' && !in_array($cur, $sheetCurrencies)) $sheetCurrencies[] = $cur;
            }
        }
    }
    sort($sheetCurrencies);

    $sheetTitle = mb_substr(preg_replace('/[\/\\\?\*\[\]:]/', '', $supplierName), 0, 31);
    $sheet      = $spreadsheet->createSheet();
    $sheet->setTitle($sheetTitle);

    // Fixed cols: A=Date, B=DO/PO No., C=Supplier, D=Product, E=Grade, F=Kg
    // If allowPrice: G=U.Price, H..=currency cols, last=S.Total
    // If !allowPrice: G..=currency cols, last=S.Total
    $fixedCols    = ($allowPrice == 'Y') ? 7 : 6;
    $numCur       = count($sheetCurrencies);
    $sTotalColIdx = $fixedCols + $numCur + 1;
    $lastColIdx   = $sTotalColIdx;
    $lastCol      = Coordinate::stringFromColumnIndex($lastColIdx);

    // --- Header row ---
    $r = 1;
    $sheet->setCellValue('A'.$r, 'Date');
    $sheet->setCellValue('B'.$r, 'DO/PO No.');
    $sheet->setCellValue('C'.$r, 'Supplier');
    $sheet->setCellValue('D'.$r, 'Product');
    $sheet->setCellValue('E'.$r, 'Grade');
    $sheet->setCellValue('F'.$r, 'Kg');
    if ($allowPrice == 'Y') {
        $sheet->setCellValue('G'.$r, 'U.Price');
        foreach ($sheetCurrencies as $i => $cur) {
            $colLetter = Coordinate::stringFromColumnIndex($fixedCols + $i + 1);
            $sheet->setCellValue($colLetter.$r, $cur);
        }
    } else {
        foreach ($sheetCurrencies as $i => $cur) {
            $colLetter = Coordinate::stringFromColumnIndex($fixedCols + $i + 1);
            $sheet->setCellValue($colLetter.$r, $cur);
        }
    }
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($sTotalColIdx).$r, 'S.Total');
    $sheet->getStyle('A'.$r.':'.$lastCol.$r)->applyFromArray($headerStyle);
    $r++;

    $grandTotalByCurrency = [];
    foreach ($sheetCurrencies as $cur) $grandTotalByCurrency[$cur] = 0;

    foreach ($dateGroups as $dateKey => $dateData) {
        $dateDisplay  = $dateData['display'];
        $firstDateRow = true;

        foreach ($dateData['dopos'] as $doPoNo => $rows) {
            $firstDoPoRow = true;
            $subTotalByCurrency = [];
            foreach ($sheetCurrencies as $cur) $subTotalByCurrency[$cur] = 0;

            foreach ($rows as $entry) {
                $sheet->setCellValue('A'.$r, $firstDateRow ? $dateDisplay : '');
                $sheet->setCellValue('B'.$r, $firstDoPoRow ? $doPoNo : '');
                $sheet->setCellValue('C'.$r, ($firstDateRow && $firstDoPoRow) ? $supplierName : '');
                $sheet->setCellValue('D'.$r, $entry['product']);
                $sheet->setCellValue('E'.$r, $entry['grade']);
                $sheet->setCellValue('F'.$r, $entry['net']);
                if ($allowPrice == 'Y') {
                    $sheet->setCellValue('G'.$r, $entry['price']);
                    foreach ($sheetCurrencies as $i => $cur) {
                        if ($entry['currency'] === $cur) {
                            $colLetter = Coordinate::stringFromColumnIndex($fixedCols + $i + 1);
                            $sheet->setCellValue($colLetter.$r, $entry['total']);
                        }
                    }
                }
                $sheet->getStyle('A'.$r.':'.$lastCol.$r)->applyFromArray($dataStyle);
                $subTotalByCurrency[$entry['currency']] += $entry['total'];
                $grandTotalByCurrency[$entry['currency']] += $entry['total'];
                $firstDateRow = false;
                $firstDoPoRow = false;
                $r++;
            }

            // S.Total rows — one per currency that has a non-zero value
            foreach ($sheetCurrencies as $i => $cur) {
                if ($subTotalByCurrency[$cur] == 0) continue;
                $sheet->getStyle('A'.$r.':'.$lastCol.$r)->applyFromArray($subTotalStyle);
                $sTotalCol = Coordinate::stringFromColumnIndex($sTotalColIdx);
                $sheet->setCellValue($sTotalCol.$r, $cur . ' ' . number_format($subTotalByCurrency[$cur], 2));
                $sheet->getStyle($sTotalCol.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $r++;
            }
        }
    }

    // Grand Total rows — one per currency
    foreach ($sheetCurrencies as $cur) {
        if ($grandTotalByCurrency[$cur] == 0) continue;
        $sheet->getStyle('A'.$r.':'.$lastCol.$r)->applyFromArray($grandTotalStyle);
        $sTotalCol = Coordinate::stringFromColumnIndex($sTotalColIdx);
        $sheet->setCellValue($sTotalCol.$r, 'Grand Total ' . $cur . ' ' . number_format($grandTotalByCurrency[$cur], 2));
        $sheet->getStyle($sTotalCol.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $r++;
    }

    foreach (range(1, $lastColIdx) as $colIdx) {
        $sheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
    }
    $sheet->getStyle('F2:F'.($r-1))->getNumberFormat()->setFormatCode('#,##0.00');
    if ($allowPrice == 'Y') {
        $sheet->getStyle('G2:G'.($r-1))->getNumberFormat()->setFormatCode('#,##0.00');
        foreach ($sheetCurrencies as $i => $cur) {
            $colLetter = Coordinate::stringFromColumnIndex($fixedCols + $i + 1);
            $sheet->getStyle($colLetter.'2:'.$colLetter.($r-1))->getNumberFormat()->setFormatCode('#,##0.00');
        }
    }
}

$fileName = ($partyType ? $partyType . '_' : '') . 'Supplier_Individual_' . date('Y-m-d') . '.xlsx';
$writer   = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit;
