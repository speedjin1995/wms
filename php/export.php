<?php
require_once 'db_connect.php';
require_once 'lookup.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();

$company      = $_SESSION['customer'];
$companyDetail = searchCompanyById($company, $db);
$allowPrice   = $companyDetail['include_price'];
$fileName     = 'Report_' . date('Y-m-d') . '.xlsx';

// ─── Helper functions ────────────────────────────────────────────────────────

function arrangeByProductGrade($weighingDetails) {
    $arranged = [];
    if (!empty($weighingDetails)) {
        foreach ($weighingDetails as $detail) {
            if (empty($detail['product_name'])) continue;
            $product  = $detail['product_name'];
            $grade    = $detail['grade'] ?? 'Unknown';
            $arranged[$product][$grade][] = $detail;
        }
    }
    return $arranged;
}

function colLetter($n) {
    $l = '';
    while ($n > 0) {
        $n--;
        $l = chr(65 + ($n % 26)) . $l;
        $n = floor($n / 26);
    }
    return $l;
}

function writeSheet($sheet, $rows, $sheetProductGradeColumns, $fixedHeaders, $trailingHeaders, $borderStyle, $allowPrice, $defaultCurrency, $fromDate, $toDate, $transactionStatus, $db) {

    // ── Headers ──────────────────────────────────────────────────────────────

    $colIndex = 1;

    foreach ($fixedHeaders as $header) {
        $cl = colLetter($colIndex);
        $sheet->setCellValue($cl . '3', $header);
        $sheet->getStyle($cl . '3')->applyFromArray($borderStyle);
        $sheet->getStyle($cl . '3')->getFont()->setBold(true);
        $colIndex++;
    }

    $gradeStartColIndex = $colIndex;

    foreach ($sheetProductGradeColumns as $product => $grades) {
        $gradeCount  = count($grades);
        $startLetter = colLetter($colIndex);
        $endLetter   = colLetter($colIndex + $gradeCount - 1);

        $sheet->setCellValue($startLetter . '2', $product);
        if ($gradeCount > 1) {
            $sheet->mergeCells($startLetter . '2:' . $endLetter . '2');
        }
        $sheet->getStyle($startLetter . '2:' . $endLetter . '2')->applyFromArray($borderStyle);
        $sheet->getStyle($startLetter . '2:' . $endLetter . '2')->getFont()->setBold(true);
        $sheet->getStyle($startLetter . '2:' . $endLetter . '2')->getAlignment()
              ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach ($grades as $grade) {
            $cl = colLetter($colIndex);
            $sheet->setCellValue($cl . '3', $grade);
            $sheet->getStyle($cl . '3')->applyFromArray($borderStyle);
            $sheet->getStyle($cl . '3')->getFont()->setBold(true);
            $colIndex++;
        }
    }

    foreach ($trailingHeaders as $header) {
        $cl = colLetter($colIndex);
        $sheet->setCellValue($cl . '3', $header);
        $sheet->getStyle($cl . '3')->applyFromArray($borderStyle);
        $sheet->getStyle($cl . '3')->getFont()->setBold(true);
        $colIndex++;
    }

    $totalCols = $colIndex - 1;
    $rowIndex  = 4;

    $dateRangeText = 'Date: ' . ($fromDate ?: '-') . ' to ' . ($toDate ?: '-');
    $sheet->setCellValue('A1', $dateRangeText);
    $sheet->getStyle('A1')->getFont()->setBold(true);
    $sheet->mergeCells('A1:' . colLetter($totalCols) . '1');

    // ── Subtotals (scoped to this sheet's rows) ───────────────────────────────

    $subtotals = [
        'gradeWeights'  => [],
        'totalWeight'   => 0,
        'totalBinWeight'=> 0,
        'total_reject'  => 0,
        'actualWeight'  => 0,
        'totalPrice'    => 0,
        'actualPrice'   => 0,
    ];
    $subtotalGradePrice      = [];
    $subtotalGradeActualPrice = [];
    $subtotalCurrencyTotals  = [];

    foreach ($rows as $rowData) {
        foreach ($sheetProductGradeColumns as $product => $grades) {
            foreach ($grades as $grade) {
                $key = $product . '|' . $grade;

                if (!isset($subtotals['gradeWeights'][$key])) {
                    $subtotals['gradeWeights'][$key] = 0;
                }
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
            $subtotalCurrencyTotals[$cur]['totalPrice']  += $totals['totalPrice'];
            $subtotalCurrencyTotals[$cur]['actualPrice'] += $totals['actualPrice'];
        }

        $subtotals['totalWeight']    += $rowData['totalWeight'];
        $subtotals['totalBinWeight'] += $rowData['totalBinWeight'];
        $subtotals['total_reject']   += $rowData['total_reject'];
        $subtotals['actualWeight']   += $rowData['actualWeight'];
        $subtotals['totalPrice']     += $rowData['totalPrice'];
        $subtotals['actualPrice']    += $rowData['actualPrice'];
    }

    // ── Data rows ─────────────────────────────────────────────────────────────

    if (empty($rows)) {
        $sheet->setCellValue('A3', 'No records found...');
        return;
    }

    $numericColIndices = [];

    foreach ($rows as $rowData) {
        $lineData = [
            $rowData['count'],
            $rowData['formattedDate'],
            $rowData['formattedTime'],
            $rowData['indicator'],
            $rowData['serial_no'],
            $rowData['po_no'],
        ];

        if ($transactionStatus == 'RECEIVING' || $transactionStatus == 'INCOMING') {
            $lineData[] = $rowData['security_bills'];
        }

        $isDispatch = ($rowData['status'] == 'DISPATCH' || $rowData['status'] == 'STOCK-BAL' || $transactionStatus == 'OUTGOING');
        $lineData[] = $isDispatch
            ? searchCustomerNameById($rowData['customer'], $rowData['other_customer'], $db)
            : searchSupplierNameById($rowData['supplier'], $rowData['other_supplier'], $db);

        foreach ($sheetProductGradeColumns as $product => $grades) {
            foreach ($grades as $grade) {
                $numericColIndices[] = count($lineData) + 1;
                $lineData[] = floatval($rowData['gradeWeights'][$product . '|' . $grade] ?? 0);
            }
        }

        $numericColIndices[] = count($lineData) + 1;
        $lineData[] = floatval($rowData['totalWeight']);

        $numericColIndices[] = count($lineData) + 1;
        $lineData[] = floatval($rowData['totalBinWeight']);

        $numericColIndices[] = count($lineData) + 1;
        $lineData[] = floatval($rowData['total_reject']);

        $numericColIndices[] = count($lineData) + 1;
        $lineData[] = floatval($rowData['actualWeight']);

        if ($allowPrice == 'Y') {
            $lineData[] = !empty($rowData['currency']) ? $rowData['currency'] : $defaultCurrency;

            $numericColIndices[] = count($lineData) + 1;
            $lineData[] = floatval($rowData['totalPrice']);

            $numericColIndices[] = count($lineData) + 1;
            $lineData[] = floatval($rowData['actualPrice']);
        }

        array_push($lineData, $rowData['vehicle_no'], $rowData['driver'], $rowData['weighted_by'], $rowData['checked_by'], $rowData['remark']);

        $numericColIndices = array_unique($numericColIndices);
        $sheet->fromArray($lineData, NULL, 'A' . $rowIndex);
        $rowIndex++;
    }

    // ── SUBTOTAL row ──────────────────────────────────────────────────────────

    $dataStartRow = 4;
    $dataEndRow   = $rowIndex - 1;

    $subtotalData = ['', '', '', '', '', ''];
    if ($transactionStatus == 'RECEIVING' || $transactionStatus == 'INCOMING') {
        $subtotalData[] = '';
    }
    $subtotalData[] = 'SUBTOTAL';
    $sheet->fromArray($subtotalData, NULL, 'A' . $rowIndex);

    $colIndex = $gradeStartColIndex;

    foreach ($sheetProductGradeColumns as $product => $grades) {
        foreach ($grades as $grade) {
            $cl = colLetter($colIndex);
            $sheet->setCellValue($cl . $rowIndex, '=SUM(' . $cl . $dataStartRow . ':' . $cl . $dataEndRow . ')');
            $colIndex++;
        }
    }

    $totalWeightCol  = colLetter($colIndex);
    $sheet->setCellValue($totalWeightCol . $rowIndex, '=SUM(' . $totalWeightCol . $dataStartRow . ':' . $totalWeightCol . $dataEndRow . ')');
    $colIndex++;

    $totalBinCol = colLetter($colIndex);
    $sheet->setCellValue($totalBinCol . $rowIndex, '=SUM(' . $totalBinCol . $dataStartRow . ':' . $totalBinCol . $dataEndRow . ')');
    $colIndex++;

    $rejectCol = colLetter($colIndex);
    $sheet->setCellValue($rejectCol . $rowIndex, '=SUM(' . $rejectCol . $dataStartRow . ':' . $rejectCol . $dataEndRow . ')');
    $colIndex++;

    $actualWeightCol = colLetter($colIndex);
    $sheet->setCellValue($actualWeightCol . $rowIndex, '=SUM(' . $actualWeightCol . $dataStartRow . ':' . $actualWeightCol . $dataEndRow . ')');
    $colIndex++;

    if ($allowPrice == 'Y') {
        $colIndex++; // skip Currency column
    }

    $sheet->getStyle('A' . $rowIndex . ':' . colLetter($totalCols) . $rowIndex)->getFont()->setBold(true);
    $rowIndex++;

    // ── TOTAL PRICE rows (per currency) ───────────────────────────────────────

    if ($allowPrice == 'Y') {
        foreach ($subtotalCurrencyTotals as $cur => $curTotals) {
            $totalPriceData   = array_fill(0, count($fixedHeaders) - 1, '');
            $totalPriceData[] = 'TOTAL PRICE (' . $cur . ')';
            $sheet->fromArray($totalPriceData, NULL, 'A' . $rowIndex);

            $ci = $gradeStartColIndex;
            foreach ($sheetProductGradeColumns as $product => $grades) {
                foreach ($grades as $grade) {
                    $key = $product . '|' . $grade;
                    $sheet->setCellValue(colLetter($ci) . $rowIndex, floatval($subtotalGradePrice[$key][$cur] ?? 0));
                    $ci++;
                }
            }

            $ci += 4; // skip totalWeight, totalBinWeight, reject, actualWeight
            $sheet->setCellValue(colLetter($ci) . $rowIndex, $cur);
            $ci++;
            $sheet->setCellValue(colLetter($ci) . $rowIndex, floatval($curTotals['totalPrice']));
            $ci++;
            $sheet->setCellValue(colLetter($ci) . $rowIndex, floatval($curTotals['actualPrice']));

            $sheet->getStyle('A' . $rowIndex . ':' . colLetter($totalCols) . $rowIndex)->getFont()->setBold(true);
            $rowIndex++;
        }
    }

    // ── Number formatting ─────────────────────────────────────────────────────

    foreach ($numericColIndices as $ci) {
        $cl = colLetter($ci);
        $sheet->getStyle($cl . '4:' . $cl . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
    }
}

// ─── Build search query ───────────────────────────────────────────────────────

$searchQuery = '';
$fromDate    = '';
$toDate      = '';

if (!empty($_GET['fromDate'])) {
    $dateTime     = DateTime::createFromFormat('d/m/Y', $_GET['fromDate']);
    $fromDate     = $dateTime->format('d/m/Y');
    $fromDateTime = $dateTime->format('Y-m-d 00:00:00');
    $searchQuery .= " AND wholesales.created_datetime >= '" . $fromDateTime . "'";
}

if (!empty($_GET['toDate'])) {
    $dateTime   = DateTime::createFromFormat('d/m/Y', $_GET['toDate']);
    $toDate     = $dateTime->format('d/m/Y');
    $toDateTime = $dateTime->format('Y-m-d 23:59:59');
    $searchQuery .= " AND wholesales.created_datetime <= '" . $toDateTime . "'";
}

if (!empty($_GET['transactionStatus']) && $_GET['transactionStatus'] != '-') {
    $searchQuery .= " AND wholesales.status = '" . mysqli_real_escape_string($db, $_GET['transactionStatus']) . "'";
}

if (!empty($_GET['product']) && $_GET['product'] != '-') {
    $searchQuery .= " AND wholesales.product = '" . mysqli_real_escape_string($db, $_GET['product']) . "'";
}

if (!empty($_GET['category']) && $_GET['category'] != '-') {
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
        $likeConditions = array_map(fn($id) => "wholesales.weight_details LIKE '%\"product\":\"" . $id . "\"%'", $catProductIds);
        $searchQuery .= " AND (" . implode(' OR ', $likeConditions) . ")";
    } else {
        $searchQuery .= " AND 1=0";
    }
}

if (!empty($_GET['customer']) && $_GET['customer'] != '-') {
    $searchQuery .= " AND wholesales.customer = '" . mysqli_real_escape_string($db, $_GET['customer']) . "'";
}

if (!empty($_GET['supplier']) && $_GET['supplier'] != '-') {
    $searchQuery .= " AND wholesales.supplier = '" . mysqli_real_escape_string($db, $_GET['supplier']) . "'";
}

if (!empty($_GET['vehicle']) && $_GET['vehicle'] != '-') {
    if ($_GET['vehicle'] == 'UNKOWN NO' || $_GET['vehicle'] == 'OTHERS' || $_GET['vehicle'] == 'UNKNOWN') {
        if (!empty($_GET['otherVehicle']) && $_GET['otherVehicle'] != '-') {
            $searchQuery .= " AND wholesales.vehicle_no = '" . mysqli_real_escape_string($db, $_GET['otherVehicle']) . "'";
        }
    } else {
        $searchQuery .= " AND wholesales.vehicle_no = '" . mysqli_real_escape_string($db, $_GET['vehicle']) . "'";
    }
}

if (!empty($_GET['checkedBy']) && $_GET['checkedBy'] != '-') {
    $searchQuery .= " AND wholesales.checked_by = '" . mysqli_real_escape_string($db, $_GET['checkedBy']) . "'";
}

if (!empty($_GET['weightedBy']) && $_GET['weightedBy'] != '-') {
    $searchQuery .= " AND wholesales.weighted_by = '" . mysqli_real_escape_string($db, $_GET['weightedBy']) . "'";
}

if (!empty($_GET['status']) && $_GET['status'] != '-') {
    if ($_GET['status'] == 'active') {
        $searchQuery .= " AND wholesales.deleted = '0'";
    } else if ($_GET['status'] == 'deleted') {
        $searchQuery .= " AND wholesales.deleted = '1'";
    }
}

// ─── Fetch records ────────────────────────────────────────────────────────────

$isMulti = $_GET['isMulti'] ?? '';

if ($isMulti == 'Y') {
    $ids   = $_GET['ids'] ?? '';
    $query = $db->query("SELECT wholesales.* FROM wholesales WHERE wholesales.id IN (" . $ids . ")");
} else {
    $query = $db->query("SELECT wholesales.* FROM wholesales WHERE wholesales.deleted = '0' AND wholesales.company = '$company'" . $searchQuery);
}

// ─── Default currency ─────────────────────────────────────────────────────────

$defaultCurrency = 'MYR';
$defCurrStmt = $db->prepare("SELECT currency FROM currency WHERE customer = ? AND is_default = 1 AND deleted = 0 LIMIT 1");
$defCurrStmt->bind_param('s', $company);
$defCurrStmt->execute();
$defCurrResult = $defCurrStmt->get_result();
if ($defCurrRow = $defCurrResult->fetch_assoc()) {
    $defaultCurrency = $defCurrRow['currency'];
}
$defCurrStmt->close();

// ─── Build $allRows ───────────────────────────────────────────────────────────

$productGradeColumns = [];
$allRows             = [];

if ($query->num_rows > 0) {
    $count = 1;
    while ($row = $query->fetch_assoc()) {
        $createdDateTime = new DateTime($row['created_datetime']);
        $formattedDate   = $createdDateTime->format('d/m/Y');
        $formattedTime   = $createdDateTime->format('H:i:s');

        $weighingDetails = json_decode($row['weight_details'], true);
        $arrangedDetails = arrangeByProductGrade($weighingDetails);

        $totalWeight      = 0;
        $totalBinWeight   = 0;
        $totalRejectWeight = 0;
        $totalPrice       = 0;
        $actualPrice      = 0;
        $currency         = '';
        $gradeWeights     = [];
        $gradePrice       = [];
        $gradeActualPrice = [];
        $currencyTotals   = [];

        foreach ($arrangedDetails as $product => $grades) {
            foreach ($grades as $grade => $details) {
                if (!isset($productGradeColumns[$product])) {
                    $productGradeColumns[$product] = [];
                }
                if (!in_array($grade, $productGradeColumns[$product])) {
                    $productGradeColumns[$product][] = $grade;
                }

                $gradeNettWeight = 0;
                foreach ($details as $detail) {
                    $gradeNettWeight   += floatval($detail['net']    ?? 0);
                    $totalWeight       += floatval($detail['gross']  ?? 0);
                    $totalBinWeight    += floatval($detail['tare']   ?? 0);
                    $totalRejectWeight += floatval($detail['reject'] ?? 0);

                    if (empty($currency) && !empty($detail['currency'])) {
                        $currency = searchCurrencyNameById($detail['currency'], $db);
                    }

                    $detailCurrencyName = !empty($detail['currency'])
                        ? searchCurrencyNameById($detail['currency'], $db)
                        : $defaultCurrency;
                    if (empty($detailCurrencyName)) {
                        $detailCurrencyName = $defaultCurrency;
                    }

                    $gradeKey = $product . '|' . $grade;

                    if ($detail['fixedfloat'] == 'fixed') {
                        $detailTotal  = floatval($detail['price'] ?? 0);
                        $detailActual = floatval($detail['price'] ?? 0);
                    } else {
                        $detailTotal  = floatval($detail['gross']  ?? 0) * floatval($detail['price'] ?? 0);
                        $detailActual = (floatval($detail['net'] ?? 0) - floatval($detail['reject'] ?? 0)) * floatval($detail['price'] ?? 0);
                    }

                    $totalPrice  += $detailTotal;
                    $actualPrice += $detailActual;

                    if (!isset($gradePrice[$gradeKey][$detailCurrencyName])) {
                        $gradePrice[$gradeKey][$detailCurrencyName] = 0;
                    }
                    if (!isset($gradeActualPrice[$gradeKey][$detailCurrencyName])) {
                        $gradeActualPrice[$gradeKey][$detailCurrencyName] = 0;
                    }
                    $gradePrice[$gradeKey][$detailCurrencyName]       += $detailTotal;
                    $gradeActualPrice[$gradeKey][$detailCurrencyName] += $detailActual;

                    if (!isset($currencyTotals[$detailCurrencyName])) {
                        $currencyTotals[$detailCurrencyName] = ['totalPrice' => 0, 'actualPrice' => 0];
                    }
                    $currencyTotals[$detailCurrencyName]['totalPrice']  += $detailTotal;
                    $currencyTotals[$detailCurrencyName]['actualPrice'] += $detailActual;
                }

                $gradeWeights[$product . '|' . $grade] = $gradeNettWeight;
            }
        }

        $actualWeight = $totalWeight - $totalBinWeight - $totalRejectWeight;

        $allRows[] = [
            'count'          => $count,
            'formattedDate'  => $formattedDate,
            'formattedTime'  => $formattedTime,
            'serial_no'      => $row['serial_no'],
            'po_no'          => $row['po_no'],
            'security_bills' => $row['security_bills'],
            'indicator'      => $row['indicator'],
            'status'         => $row['status'],
            'customer'       => $row['customer'],
            'other_customer' => $row['other_customer'],
            'supplier'       => $row['supplier'],
            'other_supplier' => $row['other_supplier'],
            'product'        => searchProductNameById($row['product'], $db),
            'gradeWeights'   => $gradeWeights,
            'gradePrice'     => $gradePrice,
            'gradeActualPrice' => $gradeActualPrice,
            'currencyTotals' => $currencyTotals,
            'totalWeight'    => $totalWeight,
            'totalBinWeight' => $totalBinWeight,
            'total_reject'   => $totalRejectWeight,
            'actualWeight'   => $actualWeight,
            'totalPrice'     => $totalPrice,
            'actualPrice'    => $actualPrice,
            'currency'       => $currency,
            'vehicle_no'     => $row['vehicle_no'],
            'driver'         => $row['driver'],
            'checked_by'     => $row['checked_by'],
            'weighted_by'    => searchUserNameById($row['weighted_by'], $db),
            'remark'         => $row['remark'],
        ];
        $count++;
    }
}

// Sort grades alphabetically for each product
foreach ($productGradeColumns as $product => &$grades) {
    sort($grades);
}
unset($grades);

// ─── Build headers ────────────────────────────────────────────────────────────

$transactionStatus = $_GET['transactionStatus'] ?? '';

if ($transactionStatus == 'DISPATCH' || $transactionStatus == 'STOCK-BAL' || $transactionStatus == 'OUTGOING') {
    $fixedHeaders = ['No', 'Date', 'Time', 'Machine Nickname', 'Weigh Slip No.', 'Delivery No.', 'Customer'];
} else {
    $fixedHeaders = ['No', 'Date', 'Time', 'Machine Nickname', 'Weigh Slip No.', 'Purchase No.', 'Security Bill No.', 'Supplier'];
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
        ],
    ],
];

// ─── Write spreadsheet ────────────────────────────────────────────────────────

$spreadsheet = new Spreadsheet();

// ALL sheet
$allSheet = $spreadsheet->getActiveSheet();
$allSheet->setTitle('ALL');
writeSheet($allSheet, $allRows, $productGradeColumns, $fixedHeaders, $trailingHeaders, $borderStyle, $allowPrice, $defaultCurrency, $fromDate, $toDate, $transactionStatus, $db);

// Per-machine sheets
$machineGroups = [];
foreach ($allRows as $rowData) {
    $machineName = !empty($rowData['indicator']) ? $rowData['indicator'] : 'Unknown';
    $machineGroups[$machineName][] = $rowData;
}

foreach ($machineGroups as $machineName => $machineRows) {
    $machineProductGradeColumns = [];
    foreach ($machineRows as $rowData) {
        foreach ($rowData['gradeWeights'] as $key => $weight) {
            $parts   = explode('|', $key, 2);
            $product = $parts[0];
            $grade   = $parts[1] ?? 'Unknown';
            if (!isset($machineProductGradeColumns[$product])) {
                $machineProductGradeColumns[$product] = [];
            }
            if (!in_array($grade, $machineProductGradeColumns[$product])) {
                $machineProductGradeColumns[$product][] = $grade;
            }
        }
    }
    foreach ($machineProductGradeColumns as $product => &$grades) {
        sort($grades);
    }
    unset($grades);

    $sheetTitle = substr(preg_replace('#[\\/:*?\[\]]#', '_', $machineName), 0, 31);
    if (empty(trim($sheetTitle))) {
        $sheetTitle = 'Unknown';
    }

    $machineSheet = $spreadsheet->createSheet();
    $machineSheet->setTitle($sheetTitle);
    writeSheet($machineSheet, $machineRows, $machineProductGradeColumns, $fixedHeaders, $trailingHeaders, $borderStyle, $allowPrice, $defaultCurrency, $fromDate, $toDate, $transactionStatus, $db);
}

// ─── Output ───────────────────────────────────────────────────────────────────

$writer = new Xlsx($spreadsheet);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
?>
