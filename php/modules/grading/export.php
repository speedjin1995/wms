<?php
require_once '../../db_connect.php';
require_once '../../lookup.php';
require_once '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();

$company       = $_SESSION['customer'];
$companyDetail = searchCompanyById($company, $db);
$fileName      = 'Grading_Report_' . date('Y-m-d') . '.xlsx';

// ─── Helpers ──────────────────────────────────────────────────────────────────

function colLetter($n) {
    $l = '';
    while ($n > 0) {
        $n--;
        $l = chr(65 + ($n % 26)) . $l;
        $n = floor($n / 26);
    }
    return $l;
}

function writeGradingSheet($sheet, $rows, $sheetProductGradeColumns, $borderStyle, $fromDate, $toDate, $db) {

    $fixedHeaders    = ['No', 'Date', 'Start Time', 'End Time', 'Grading No', 'Location', 'Machine', 'Category'];
    $trailingHeaders = ['Total Gross', 'Total Tare', 'Total Nett', 'Created By', 'Remark'];

    $totalGradeCols = 0;
    foreach ($sheetProductGradeColumns as $grades) $totalGradeCols += count($grades);
    $totalCols = count($fixedHeaders) + $totalGradeCols + count($trailingHeaders);

    // Row 1 – date range
    $sheet->setCellValue('A1', 'Date: ' . ($fromDate ?: '-') . ' to ' . ($toDate ?: '-'));
    $sheet->getStyle('A1')->getFont()->setBold(true);
    $sheet->mergeCells('A1:' . colLetter($totalCols) . '1');

    // Row 2 – product name spans
    $colIndex = count($fixedHeaders) + 1;
    foreach ($sheetProductGradeColumns as $product => $grades) {
        $startLetter = colLetter($colIndex);
        $endLetter   = colLetter($colIndex + count($grades) - 1);
        $sheet->setCellValue($startLetter . '2', $product);
        if (count($grades) > 1) {
            $sheet->mergeCells($startLetter . '2:' . $endLetter . '2');
        }
        $sheet->getStyle($startLetter . '2:' . $endLetter . '2')->applyFromArray($borderStyle);
        $sheet->getStyle($startLetter . '2:' . $endLetter . '2')->getFont()->setBold(true);
        $sheet->getStyle($startLetter . '2:' . $endLetter . '2')->getAlignment()
              ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $colIndex += count($grades);
    }

    // Row 3 – column headers
    $colIndex = 1;
    foreach ($fixedHeaders as $h) {
        $cl = colLetter($colIndex);
        $sheet->setCellValue($cl . '3', $h);
        $sheet->getStyle($cl . '3')->applyFromArray($borderStyle);
        $sheet->getStyle($cl . '3')->getFont()->setBold(true);
        $colIndex++;
    }
    $gradeStartColIndex = $colIndex;
    foreach ($sheetProductGradeColumns as $product => $grades) {
        foreach ($grades as $grade) {
            $cl = colLetter($colIndex);
            $sheet->setCellValue($cl . '3', $grade);
            $sheet->getStyle($cl . '3')->applyFromArray($borderStyle);
            $sheet->getStyle($cl . '3')->getFont()->setBold(true);
            $colIndex++;
        }
    }
    foreach ($trailingHeaders as $h) {
        $cl = colLetter($colIndex);
        $sheet->setCellValue($cl . '3', $h);
        $sheet->getStyle($cl . '3')->applyFromArray($borderStyle);
        $sheet->getStyle($cl . '3')->getFont()->setBold(true);
        $colIndex++;
    }

    if (empty($rows)) {
        $sheet->setCellValue('A4', 'No records found...');
        return;
    }

    // Data rows
    $rowIndex        = 4;
    $numericColIdxs  = [];

    foreach ($rows as $r) {
        $startDt = new DateTime($r['start_date']);
        $endDt   = $r['end_date'] ? new DateTime($r['end_date']) : null;

        $lineData = [
            $r['count'],
            $startDt->format('d/m/Y'),
            $startDt->format('H:i:s'),
            $endDt ? $endDt->format('H:i:s') : '',
            $r['grading_no'],
            $r['location'],
            $r['indicator'],
            $r['category'],
        ];

        $ci = $gradeStartColIndex;
        foreach ($sheetProductGradeColumns as $product => $grades) {
            foreach ($grades as $grade) {
                $numericColIdxs[] = $ci;
                $lineData[] = floatval($r['gradeWeights'][$product . '|' . $grade] ?? 0);
                $ci++;
            }
        }

        $numericColIdxs[] = $ci++;
        $lineData[] = floatval($r['totalGross']);
        $numericColIdxs[] = $ci++;
        $lineData[] = floatval($r['totalTare']);
        $numericColIdxs[] = $ci++;
        $lineData[] = floatval($r['totalNett']);

        $lineData[] = $r['created_by'];
        $lineData[] = $r['remark'] ?? '';

        $sheet->fromArray($lineData, NULL, 'A' . $rowIndex);
        $rowIndex++;
    }

    // Subtotal row
    $dataStartRow = 4;
    $dataEndRow   = $rowIndex - 1;

    $subtotalData = array_fill(0, count($fixedHeaders) - 1, '');
    $subtotalData[] = 'SUBTOTAL';
    $sheet->fromArray($subtotalData, NULL, 'A' . $rowIndex);

    $ci = $gradeStartColIndex;
    foreach ($sheetProductGradeColumns as $product => $grades) {
        foreach ($grades as $grade) {
            $cl = colLetter($ci);
            $sheet->setCellValue($cl . $rowIndex, '=SUM(' . $cl . $dataStartRow . ':' . $cl . $dataEndRow . ')');
            $ci++;
        }
    }
    foreach (['totalGross', 'totalTare', 'totalNett'] as $ignored) {
        $cl = colLetter($ci);
        $sheet->setCellValue($cl . $rowIndex, '=SUM(' . $cl . $dataStartRow . ':' . $cl . $dataEndRow . ')');
        $ci++;
    }

    $sheet->getStyle('A' . $rowIndex . ':' . colLetter($totalCols) . $rowIndex)->getFont()->setBold(true);

    // Number formatting
    foreach (array_unique($numericColIdxs) as $ci) {
        $cl = colLetter($ci);
        $sheet->getStyle($cl . '4:' . $cl . $rowIndex)->getNumberFormat()
              ->setFormatCode('#,##0.00');
    }
}

// ─── Build search query ───────────────────────────────────────────────────────

$searchQuery = '';
$fromDate    = '';
$toDate      = '';

if (!empty($_GET['fromDate'])) {
    $dt           = DateTime::createFromFormat('d/m/Y', $_GET['fromDate']);
    $fromDate     = $dt->format('d/m/Y');
    $searchQuery .= " AND g.start_date >= '" . $dt->format('Y-m-d 00:00:00') . "'";
}

if (!empty($_GET['toDate'])) {
    $dt           = DateTime::createFromFormat('d/m/Y', $_GET['toDate']);
    $toDate       = $dt->format('d/m/Y');
    $searchQuery .= " AND g.start_date <= '" . $dt->format('Y-m-d 23:59:59') . "'";
}

if (!empty($_GET['location']) && $_GET['location'] != '-') {
    $searchQuery .= " AND g.location = '" . mysqli_real_escape_string($db, $_GET['location']) . "'";
}

if (!empty($_GET['category']) && $_GET['category'] != '-') {
    $searchQuery .= " AND g.product_category = '" . mysqli_real_escape_string($db, $_GET['category']) . "'";
}

// ─── Fetch grading records ────────────────────────────────────────────────────

$isMulti = $_GET['isMulti'] ?? '';
if ($isMulti == 'Y' && !empty($_GET['ids'])) {
    $ids    = mysqli_real_escape_string($db, $_GET['ids']);
    $result = $db->query("SELECT * FROM grading g WHERE id IN ($ids) AND deleted = 0");
} else {
    $result = $db->query("SELECT * FROM grading g WHERE deleted = 0 AND company = '$company'$searchQuery ORDER BY start_date ASC");
}

// ─── Build $allRows ───────────────────────────────────────────────────────────

$productGradeColumns = [];
$allRows             = [];

if ($result && $result->num_rows > 0) {
    $count = 1;
    while ($grading = $result->fetch_assoc()) {
        $itemsResult = $db->query(
            "SELECT gi.*, p.product_name FROM grading_items gi
             LEFT JOIN products p ON gi.product_id = p.id
             WHERE gi.grading_id = {$grading['id']} AND gi.deleted = 0"
        );

        $gradeWeights = [];
        $totalGross   = 0;
        $totalTare    = 0;
        $totalNett    = 0;

        while ($item = $itemsResult->fetch_assoc()) {
            $productName = $item['product_name'] ?? 'Unknown';
            $gradeKey    = searchGradeNameById($item['to_grade'], $db);
            $colKey      = $productName . '|' . $gradeKey;

            if (!isset($productGradeColumns[$productName])) $productGradeColumns[$productName] = [];
            if (!in_array($gradeKey, $productGradeColumns[$productName])) {
                $productGradeColumns[$productName][] = $gradeKey;
            }

            $nett = floatval($item['nett_weight']);
            if (!isset($gradeWeights[$colKey])) $gradeWeights[$colKey] = 0;
            $gradeWeights[$colKey] += $nett;

            $totalGross += floatval($item['gross_weight']);
            $totalTare  += floatval($item['tare_weight']);
            $totalNett  += $nett;
        }

        $allRows[] = [
            'count'       => $count++,
            'grading_no'  => $grading['grading_no'],
            'start_date'  => $grading['start_date'],
            'end_date'    => $grading['end_date'],
            'location'    => searchLocationById($grading['location'], $db) ?: '',
            'indicator'   => $grading['indicator'],
            'category'    => searchCategoryById($grading['product_category'], $db) ?: '',
            'remark'      => $grading['remark'] ?? '',
            'created_by'  => searchUserNameById($grading['created_by'], $db),
            'gradeWeights'=> $gradeWeights,
            'totalGross'  => $totalGross,
            'totalTare'   => $totalTare,
            'totalNett'   => $totalNett,
        ];
    }
}

foreach ($productGradeColumns as $p => &$grades) sort($grades);
unset($grades);

// ─── Border style ─────────────────────────────────────────────────────────────

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
writeGradingSheet($allSheet, $allRows, $productGradeColumns, $borderStyle, $fromDate, $toDate, $db);

// Per-location sheets
$locationGroups = [];
foreach ($allRows as $r) {
    $locationGroups[$r['location']][] = $r;
}

foreach ($locationGroups as $locationName => $locRows) {
    $locProductGradeColumns = [];
    foreach ($locRows as $r) {
        foreach ($r['gradeWeights'] as $colKey => $w) {
            [$product, $grade] = explode('|', $colKey, 2);
            if (!isset($locProductGradeColumns[$product])) $locProductGradeColumns[$product] = [];
            if (!in_array($grade, $locProductGradeColumns[$product])) {
                $locProductGradeColumns[$product][] = $grade;
            }
        }
    }
    foreach ($locProductGradeColumns as $p => &$grades) sort($grades);
    unset($grades);

    $sheetTitle = substr(preg_replace('#[\\\\/:*?\[\]]#', '_', $locationName ?: 'Unknown'), 0, 31);
    $locSheet   = $spreadsheet->createSheet();
    $locSheet->setTitle($sheetTitle);
    writeGradingSheet($locSheet, $locRows, $locProductGradeColumns, $borderStyle, $fromDate, $toDate, $db);
}

// ─── Output ───────────────────────────────────────────────────────────────────

$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit;
