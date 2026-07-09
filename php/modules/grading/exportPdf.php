<?php
require_once '../../db_connect.php';
require_once '../../lookup.php';
require_once '../../../vendor/autoload.php';
use Mpdf\Mpdf;

session_start();
$company = $_SESSION['customer'];
$companyDetail = searchCompanyById($company, $db);

$fileName = "Grading_Report_" . date('Y-m-d') . ".pdf";

$searchQuery = "";

if (isset($_GET['fromDate']) && $_GET['fromDate'] != '') {
    $dateTime = DateTime::createFromFormat('d/m/Y', $_GET['fromDate']);
    $fromDate = $dateTime->format('d/m/Y');
    $fromDateTime = $dateTime->format('Y-m-d 00:00:00');
    $searchQuery .= " AND g.start_date >= '$fromDateTime'";
} else {
    $fromDate = '';
}

if (isset($_GET['toDate']) && $_GET['toDate'] != '') {
    $dateTime = DateTime::createFromFormat('d/m/Y', $_GET['toDate']);
    $toDate = $dateTime->format('d/m/Y');
    $toDateTime = $dateTime->format('Y-m-d 23:59:59');
    $searchQuery .= " AND g.start_date <= '$toDateTime'";
} else {
    $toDate = '';
}

if (isset($_GET['location']) && $_GET['location'] != '') {
    $loc = mysqli_real_escape_string($db, $_GET['location']);
    $searchQuery .= " AND g.location = '$loc'";
}

if (isset($_GET['category']) && $_GET['category'] != '') {
    $cat = mysqli_real_escape_string($db, $_GET['category']);
    $searchQuery .= " AND g.product_category = '$cat'";
}

$isMulti = isset($_GET['isMulti']) ? $_GET['isMulti'] : '';
if ($isMulti == 'Y' && isset($_GET['ids']) && $_GET['ids'] != '') {
    $ids = mysqli_real_escape_string($db, $_GET['ids']);
    $gradingResult = $db->query("SELECT g.* FROM grading g WHERE g.id IN ($ids) AND g.deleted = 0");
} else {
    $gradingResult = $db->query("SELECT g.* FROM grading g WHERE g.deleted = 0 AND g.company = '$company'$searchQuery ORDER BY g.start_date ASC");
}

try {
    $mpdf = new Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4-L',
        'tempDir'       => sys_get_temp_dir(),
        'margin_left'   => 5,
        'margin_right'  => 5,
        'margin_top'    => 5,
        'margin_bottom' => 5,
        'fontDir'       => [dirname(dirname(__DIR__, 2)) . '/vendor/mpdf/mpdf/ttfonts/'],
        'fontdata'      => ['sunexta' => ['R' => 'Sun-ExtA.ttf']],
        'default_font'  => 'sunexta',
    ]);

    // Collect all grading records with their items
    $allRows = [];
    $productGradeColumns = []; // [product_name => [from_grade|to_grade, ...]]

    if ($gradingResult && $gradingResult->num_rows > 0) {
        $count = 1;
        while ($grading = $gradingResult->fetch_assoc()) {
            $itemsResult = $db->query(
                "SELECT gi.*, p.product_name FROM grading_items gi
                 LEFT JOIN products p ON gi.product_id = p.id
                 WHERE gi.grading_id = {$grading['id']} AND gi.deleted = 0"
            );

            $items = [];
            $totalGross = 0;
            $totalTare  = 0;
            $totalNett  = 0;
            $gradeWeights = []; // [product|from>to => nett]

            while ($item = $itemsResult->fetch_assoc()) {
                $productName = $item['product_name'] ?? 'Unknown';
                $gradeKey    = searchGradeNameById($item['to_grade'], $db);
                $colKey      = $productName . '|' . $gradeKey;

                if (!isset($productGradeColumns[$productName])) {
                    $productGradeColumns[$productName] = [];
                }
                if (!in_array($gradeKey, $productGradeColumns[$productName])) {
                    $productGradeColumns[$productName][] = $gradeKey;
                }

                $nett = floatval($item['nett_weight']);
                if (!isset($gradeWeights[$colKey])) $gradeWeights[$colKey] = 0;
                $gradeWeights[$colKey] += $nett;

                $totalGross += floatval($item['gross_weight']);
                $totalTare  += floatval($item['tare_weight']);
                $totalNett  += $nett;

                $items[] = $item;
            }

            $allRows[] = [
                'count'       => $count++,
                'grading_no'  => $grading['grading_no'],
                'start_date'  => $grading['start_date'],
                'end_date'    => $grading['end_date'],
                'location'    => searchLocationById($grading['location'], $db) ?: '',
                'indicator'   => $grading['indicator'],
                'category'    => searchCategoryById($grading['product_category'], $db) ?: '',
                'remark'      => $grading['remark'],
                'created_by'  => searchUserNameById($grading['created_by'], $db),
                'gradeWeights'=> $gradeWeights,
                'totalGross'  => $totalGross,
                'totalTare'   => $totalTare,
                'totalNett'   => $totalNett,
                'items'       => $items,
            ];
        }
    }

    // Sort grade columns per product
    foreach ($productGradeColumns as $p => &$grades) sort($grades);
    unset($grades);

    // Subtotals
    $subtotals = ['gradeWeights' => [], 'totalGross' => 0, 'totalTare' => 0, 'totalNett' => 0];
    foreach ($allRows as $r) {
        $subtotals['totalGross'] += $r['totalGross'];
        $subtotals['totalTare']  += $r['totalTare'];
        $subtotals['totalNett']  += $r['totalNett'];
        foreach ($productGradeColumns as $p => $grades) {
            foreach ($grades as $grade) {
                $key = $p . '|' . $grade;
                if (!isset($subtotals['gradeWeights'][$key])) $subtotals['gradeWeights'][$key] = 0;
                $subtotals['gradeWeights'][$key] += ($r['gradeWeights'][$key] ?? 0);
            }
        }
    }

    // Build table rows
    $content = '';
    if (!empty($allRows)) {
        foreach ($allRows as $r) {
            $startDt = new DateTime($r['start_date']);
            $endDt   = $r['end_date'] ? new DateTime($r['end_date']) : null;
            $content .= '<tr>';
            $content .= '<td>' . $r['count'] . '</td>';
            $content .= '<td>' . $startDt->format('d/m/Y') . '</td>';
            $content .= '<td>' . $startDt->format('H:i:s') . '</td>';
            $content .= '<td>' . ($endDt ? $endDt->format('H:i:s') : '') . '</td>';
            $content .= '<td>' . htmlspecialchars($r['grading_no']) . '</td>';
            $content .= '<td>' . htmlspecialchars($r['location']) . '</td>';
            $content .= '<td>' . htmlspecialchars($r['indicator']) . '</td>';
            $content .= '<td>' . htmlspecialchars($r['category']) . '</td>';
            foreach ($productGradeColumns as $p => $grades) {
                foreach ($grades as $grade) {
                    $key = $p . '|' . $grade;
                    $content .= '<td>' . number_format($r['gradeWeights'][$key] ?? 0, 2) . '</td>';
                }
            }
            $content .= '<td>' . number_format($r['totalGross'], 2) . '</td>';
            $content .= '<td>' . number_format($r['totalTare'], 2) . '</td>';
            $content .= '<td>' . number_format($r['totalNett'], 2) . '</td>';
            $content .= '<td>' . htmlspecialchars($r['created_by']) . '</td>';
            $content .= '<td>' . htmlspecialchars($r['remark'] ?? '') . '</td>';
            $content .= '</tr>';
        }
    } else {
        $content = '<tr><td colspan="20">No records found...</td></tr>';
    }

    // Build header columns
    $fixedCols = 8; // No, Date, Start Time, End Time, Grading No, Location, Machine, Category
    $totalGradeCols = 0;
    foreach ($productGradeColumns as $p => $grades) $totalGradeCols += count($grades);
    $trailingCols = 5; // Total Gross, Total Tare, Total Nett, Created By, Remark

    $html = '
    <html><head><title>Grading Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        th, td { border: 1px solid black; padding: 2px; text-align: center; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .fw-bold { font-weight: bold; }
        .text-muted { color: #6c757d; }
        hr { border: 0; border-top: 1px solid #343a40; margin: 6px 0; }
    </style>
    </head><body>
    <div class="fw-bold" style="font-size:14px;">' . htmlspecialchars($companyDetail['name']) . '</div>
    <div class="text-muted" style="font-size:10px;">
        ' . htmlspecialchars($companyDetail['address']) . '<br>
        ' . htmlspecialchars($companyDetail['address2']) . '<br>
        ' . htmlspecialchars($companyDetail['address3']) . '
    </div>
    <hr>
    <table style="border:none; margin-bottom:4px;">
        <tr>
            <td style="border:none; text-align:left; font-size:13px;" class="fw-bold">GRADING REPORT</td>
            <td style="border:none; text-align:right; font-size:13px;" class="fw-bold">From: ' . $fromDate . ' To: ' . $toDate . '</td>
        </tr>
    </table>
    <hr>
    <table>
        <thead>
            <tr>
                <th colspan="' . $fixedCols . '"></th>';

    foreach ($productGradeColumns as $p => $grades) {
        $html .= '<th colspan="' . count($grades) . '">' . htmlspecialchars($p) . '</th>';
    }

    $html .= '<th colspan="' . $trailingCols . '"></th>
            </tr>
            <tr>
                <th>No</th>
                <th>Date</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Grading No</th>
                <th>Location</th>
                <th>Machine</th>
                <th>Category</th>';

    foreach ($productGradeColumns as $p => $grades) {
        foreach ($grades as $grade) {
            $html .= '<th>' . htmlspecialchars($grade) . '</th>';
        }
    }

    $html .= '
                <th>Total Gross</th>
                <th>Total Tare</th>
                <th>Total Nett</th>
                <th>Created By</th>
                <th>Remark</th>
            </tr>
        </thead>
        <tbody>' . $content . '</tbody>
        <tfoot>
            <tr style="font-weight:bold; background-color:#f0f0f0;">
                <td colspan="' . $fixedCols . '">SUBTOTAL</td>';

    foreach ($productGradeColumns as $p => $grades) {
        foreach ($grades as $grade) {
            $key = $p . '|' . $grade;
            $html .= '<td>' . number_format($subtotals['gradeWeights'][$key] ?? 0, 2) . '</td>';
        }
    }

    $html .= '
                <td>' . number_format($subtotals['totalGross'], 2) . '</td>
                <td>' . number_format($subtotals['totalTare'], 2) . '</td>
                <td>' . number_format($subtotals['totalNett'], 2) . '</td>
                <td></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    </body></html>';

    $mpdf->WriteHTML($html);
    $mpdf->Output($fileName, 'D');

} catch (\Mpdf\MpdfException $e) {
    echo $e->getMessage();
}
exit;
