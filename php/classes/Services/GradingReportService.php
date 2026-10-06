<?php
namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Builds grading report documents (Excel workbook, PDF report HTML, grading print HTML).
 * Rows come from GradingService; this class only formats them.
 */
class GradingReportService extends BaseService
{
    private const FIXED_HEADERS = ['No', 'Date', 'Start Time', 'End Time', 'Grading No', 'Location', 'Machine', 'Category'];
    private const TRAILING_HEADERS = ['Total Gross', 'Total Tare', 'Total Nett', 'Created By', 'Remark'];
    private const BORDER = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];

    /**
     * Workbook with an ALL sheet plus one sheet per location
     */
    public function buildExcel(array $rows, string $fromDate, string $toDate): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        $allSheet = $spreadsheet->getActiveSheet();
        $allSheet->setTitle('ALL');
        $this->writeSheet($allSheet, $rows, $this->productGradeColumns($rows), $fromDate, $toDate);

        $byLocation = [];
        foreach ($rows as $row) {
            $byLocation[$row['location']][] = $row;
        }

        foreach ($byLocation as $location => $locationRows) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(substr(preg_replace('#[\\\\/:*?\[\]]#', '_', $location ?: 'Unknown'), 0, 31));
            $this->writeSheet($sheet, $locationRows, $this->productGradeColumns($locationRows), $fromDate, $toDate);
        }

        return $spreadsheet;
    }

    /**
     * Grading report as HTML for mPDF
     */
    public function buildPdfHtml(array $rows, string $fromDate, string $toDate): string
    {
        $e = [$this, 'escape'];
        $company = $this->fetchOne("SELECT name, address, address2, address3 FROM companies WHERE id = ?", 'i', [$this->company]) ?? [];
        $columns = $this->productGradeColumns($rows);
        $fixedCols = count(self::FIXED_HEADERS);

        $subtotals = ['gradeWeights' => [], 'totalGross' => 0.0, 'totalTare' => 0.0, 'totalNett' => 0.0];
        $body = '';
        foreach ($rows as $row) {
            $subtotals['totalGross'] += $row['totalGross'];
            $subtotals['totalTare'] += $row['totalTare'];
            $subtotals['totalNett'] += $row['totalNett'];

            $body .= '<tr>';
            foreach ($this->fixedValues($row) as $value) {
                $body .= '<td>' . $e($value) . '</td>';
            }
            foreach ($columns as $product => $grades) {
                foreach ($grades as $grade) {
                    $key = $product . '|' . $grade;
                    $weight = $row['gradeWeights'][$key] ?? 0;
                    $subtotals['gradeWeights'][$key] = ($subtotals['gradeWeights'][$key] ?? 0) + $weight;
                    $body .= '<td>' . number_format($weight, 2) . '</td>';
                }
            }
            $body .= '<td>' . number_format($row['totalGross'], 2) . '</td>'
                . '<td>' . number_format($row['totalTare'], 2) . '</td>'
                . '<td>' . number_format($row['totalNett'], 2) . '</td>'
                . '<td>' . $e($row['created_by']) . '</td>'
                . '<td>' . $e($row['remark']) . '</td>'
                . '</tr>';
        }
        if ($body === '') {
            $body = '<tr><td colspan="20">No records found...</td></tr>';
        }

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
        <div class="fw-bold" style="font-size:14px;">' . $e($company['name'] ?? '') . '</div>
        <div class="text-muted" style="font-size:10px;">
            ' . $e($company['address'] ?? '') . '<br>
            ' . $e($company['address2'] ?? '') . '<br>
            ' . $e($company['address3'] ?? '') . '
        </div>
        <hr>
        <table style="border:none; margin-bottom:4px;">
            <tr>
                <td style="border:none; text-align:left; font-size:13px;" class="fw-bold">GRADING REPORT</td>
                <td style="border:none; text-align:right; font-size:13px;" class="fw-bold">From: ' . $e($fromDate) . ' To: ' . $e($toDate) . '</td>
            </tr>
        </table>
        <hr>
        <table>
            <thead>
                <tr><th colspan="' . $fixedCols . '"></th>';
        foreach ($columns as $product => $grades) {
            $html .= '<th colspan="' . count($grades) . '">' . $e($product) . '</th>';
        }
        $html .= '<th colspan="' . count(self::TRAILING_HEADERS) . '"></th></tr><tr>';
        foreach (self::FIXED_HEADERS as $header) {
            $html .= '<th>' . $header . '</th>';
        }
        foreach ($columns as $grades) {
            foreach ($grades as $grade) {
                $html .= '<th>' . $e($grade) . '</th>';
            }
        }
        foreach (self::TRAILING_HEADERS as $header) {
            $html .= '<th>' . $header . '</th>';
        }
        $html .= '</tr>
            </thead>
            <tbody>' . $body . '</tbody>
            <tfoot>
                <tr style="font-weight:bold; background-color:#f0f0f0;">
                    <td colspan="' . $fixedCols . '">SUBTOTAL</td>';
        foreach ($columns as $product => $grades) {
            foreach ($grades as $grade) {
                $html .= '<td>' . number_format($subtotals['gradeWeights'][$product . '|' . $grade] ?? 0, 2) . '</td>';
            }
        }

        return $html . '
                    <td>' . number_format($subtotals['totalGross'], 2) . '</td>
                    <td>' . number_format($subtotals['totalTare'], 2) . '</td>
                    <td>' . number_format($subtotals['totalNett'], 2) . '</td>
                    <td></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        </body></html>';
    }

    /**
     * Grading print layout (paged.js, A4) with optional photo page
     */
    public function buildPrintHtml(array $data, bool $withPhoto): string
    {
        $e = [$this, 'escape'];
        $grading = $data['header'];
        $logoSrc = !empty(trim((string)($grading['company_logo'] ?? '')))
            ? 'php/viewPhoto.php?file=' . urlencode(trim($grading['company_logo'])) . '&type=file_table'
            : '';

        // Group items by product + grade, then split into chunks of 10 per grade table
        $grouped = [];
        $totalCages = 0;
        $totalCagesWeight = 0.0;
        foreach ($data['items'] as $item) {
            $key = $item['product_id'] . ' - ' . $item['to_grade'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['product_name' => $item['product_name'], 'grade_name' => $item['grade_name'], 'items' => []];
            }
            $grouped[$key]['items'][] = $item;
            $totalCages++;
            $totalCagesWeight += (float)$item['tare_weight'];
        }

        $tables = [];
        foreach ($grouped as $group) {
            foreach (array_chunk($group['items'], 10) as $chunk) {
                $tables[] = ['product_name' => $group['product_name'], 'grade_name' => $group['grade_name'], 'items' => $chunk];
            }
        }

        $totalNetWeight = 0.0;
        $weightDetails = '';
        foreach (array_chunk($tables, 3) as $rowIndex => $rowTables) {
            $weightDetails .= ($rowIndex > 0 && $rowIndex % 2 == 0) ? '<div class="row mb-3 page-break">' : '<div class="row mb-3">';

            foreach ($rowTables as $table) {
                $totalGross = $totalTare = $totalNet = 0.0;
                $weightDetails .= '<div class="col-4"><table class="grade-table">'
                    . '<tr style="font-weight:bold;background-color:#f0f0f0;"><td colspan="4">' . $e($table['product_name']) . ' GRADE: ' . $e($table['grade_name']) . '</td></tr>'
                    . '<tr><th>No</th><th>Gross Weight</th><th>Tare Weight</th><th>Net Weight</th></tr>';

                for ($i = 0; $i < 10; $i++) {
                    if (!isset($table['items'][$i])) {
                        $weightDetails .= '<tr><td>' . ($i + 1) . '</td><td></td><td></td><td></td></tr>';
                        continue;
                    }
                    $gross = (float)$table['items'][$i]['gross_weight'];
                    $tare = (float)$table['items'][$i]['tare_weight'];
                    $net = (float)$table['items'][$i]['nett_weight'];
                    $totalGross += $gross;
                    $totalTare += $tare;
                    $totalNet += $net;
                    $weightDetails .= '<tr><td>' . ($i + 1) . '</td><td>' . number_format($gross, 2) . ' kg</td><td>' . number_format($tare, 2) . ' kg</td><td>' . number_format($net, 2) . ' kg</td></tr>';
                }

                $totalNetWeight += $totalNet;
                $weightDetails .= '<tr style="font-weight:bold;"><td style="border-right:none;">T</td><td style="border-left:none;border-right:none;">' . number_format($totalGross, 2) . ' kg</td>'
                    . '<td style="border-left:none;border-right:none;">' . number_format($totalTare, 2) . ' kg</td><td style="border-left:none;">' . number_format($totalNet, 2) . ' kg</td></tr>'
                    . '</table></div>';
            }

            $weightDetails .= '</div>';
        }

        $html = '
        <html>
        <head>
            <script src="https://unpkg.com/pagedjs/dist/paged.polyfill.js"></script>
            <style>
                .container-fluid { width:100%; padding-right:10px; padding-left:10px; margin-right:auto; margin-left:auto; }
                .row { display:flex; flex-wrap:wrap; margin-right:-5px; margin-left:-5px; }
                .col-4 { position:relative; width:100%; padding-right:5px; padding-left:5px; flex:0 0 33.333333%; max-width:33.333333%; box-sizing:border-box; }
                .col-8 { position:relative; width:100%; padding-right:5px; padding-left:5px; flex:0 0 66.666667%; max-width:66.666667%; box-sizing:border-box; }
                .mb-1 { margin-bottom:0.1rem !important; }
                .mb-3 { margin-bottom:0.5rem !important; }
                body { font-family:Arial, sans-serif; margin-left:10px; margin-right:30px; }
                .company-name { font-weight:bold; font-size:16px; }
                .address { font-size:14px; line-height:1.2; }
                .info-row { margin-bottom:2px; font-size:14px; display:flex; }
                .info-label { width:120px; flex-shrink:0; }
                .info-label2 { width:100px; flex-shrink:0; }
                .info-value { flex:1; }
                .grade-table { width:100%; border-collapse:collapse; margin-bottom:15px; }
                .grade-table th, .grade-table td { border:1px solid black; padding:5px; text-align:center; font-size:10px; }
                .grade-table th { background-color:#f0f0f0; }
                @page {
                    size: A4;
                    margin: ' . ($logoSrc ? '70mm' : '55mm') . ' 5mm 5mm 5mm;
                    @top-left { content: element(running-header); }
                }
                .running-header { position:running(running-header); width:100%; text-align:left; }
                .page-break { page-break-before:always; break-before:page; }
            </style>
        </head>
        <body>
            <div class="running-header">
                <div class="row mb-1">
                    <div class="col-8" style="display:flex;align-items:flex-start;gap:10px;">
                        ' . ($logoSrc ? '<img src="' . $e($logoSrc) . '" alt="Logo" style="width:130px;height:auto;flex-shrink:0;">' : '') . '
                        <div>
                            <div class="company-name">' . $e($grading['company_name']) . '</div>
                            <div class="address">' . $e($grading['company_address']) . '</div>
                            <div class="address">' . $e($grading['company_address2']) . '</div>
                            <div class="address">' . $e($grading['company_address3']) . '</div>
                            <div class="address">' . $e($grading['company_address4']) . '</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="info-row"><span class="info-label2">Grading No</span><span class="info-value">: ' . $e($grading['grading_no']) . '</span></div>
                        <div class="info-row"><span class="info-label2">Status</span><span class="info-value">: Grading</span></div>
                        <div class="info-row"><span class="info-label2">Date</span><span class="info-value">: ' . date('d/m/Y', strtotime($grading['start_date'])) . '</span></div>
                    </div>
                </div>
                <hr>
                <div class="row mb-1">
                    <div class="col-8">
                        <div class="info-row"><span class="info-label">Category</span><span class="info-value">: ' . $e($grading['category_name']) . '</span></div>
                        <div class="info-row"><span class="info-label">Location</span><span class="info-value">: ' . $e($grading['location_name']) . '</span></div>
                        <div class="info-row"><span class="info-label">Total Net Weight</span><span class="info-value">: ' . number_format($totalNetWeight, 2) . ' kg</span></div>
                        <div class="info-row"><span class="info-label">Remark</span><span class="info-value">: ' . $e($grading['remark']) . '</span></div>
                    </div>
                    <div class="col-4">
                        <div class="info-row"><span class="info-label">Total Cages</span><span class="info-value">: ' . number_format($totalCages) . '</span></div>
                        <div class="info-row"><span class="info-label">Cages Weight</span><span class="info-value">: ' . number_format($totalCagesWeight, 2) . ' kg</span></div>
                        <div class="info-row"><span class="info-label">Created By</span><span class="info-value">: ' . $e($grading['created_by_name']) . '</span></div>
                        <div class="info-row"><span class="info-label">Time Start</span><span class="info-value">: ' . date('H:i:s', strtotime($grading['start_date'])) . '</span></div>
                        <div class="info-row"><span class="info-label">Time End</span><span class="info-value">: ' . ($grading['end_date'] ? date('H:i:s', strtotime($grading['end_date'])) : '') . '</span></div>
                    </div>
                </div>
                <hr>
            </div>
            <div class="container-fluid">
                <div class="page-content">' . $weightDetails . '</div>
            </div>';

        if ($withPhoto) {
            $photos = array_filter($data['items'], function ($item) {
                return !empty($item['photo_path']);
            });

            if (!empty($photos)) {
                $html .= '<div class="page-break"><h3 style="font-size:14px;margin-bottom:10px;">Photos</h3><div class="row">';
                foreach ($photos as $item) {
                    $grade = $item['grade_name'] === GradingService::REJECT_GRADE ? 'REJECT' : $item['grade_name'];
                    $html .= '<div class="col-4" style="margin-bottom:10px;text-align:center;">'
                        . '<img src="php/viewPhoto.php?file=' . urlencode($item['photo_path']) . '&type=photo" style="width:100%;height:auto;border:1px solid #ccc;">'
                        . '<div style="font-size:12px;margin-top:4px;">Product: ' . $e($item['product_name']) . ', Grade: ' . $e($grade) . '</div></div>';
                }
                $html .= '</div></div>';
            }
        }

        return $html . '</body></html>';
    }

    public function escape($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    private function writeSheet(Worksheet $sheet, array $rows, array $columns, string $fromDate, string $toDate): void
    {
        $gradeCols = 0;
        foreach ($columns as $grades) {
            $gradeCols += count($grades);
        }
        $totalCols = count(self::FIXED_HEADERS) + $gradeCols + count(self::TRAILING_HEADERS);

        // Row 1: date range
        $sheet->setCellValue('A1', 'Date: ' . ($fromDate ?: '-') . ' to ' . ($toDate ?: '-'));
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->mergeCells('A1:' . $this->colLetter($totalCols) . '1');

        // Row 2: product name spans
        $col = count(self::FIXED_HEADERS) + 1;
        foreach ($columns as $product => $grades) {
            $range = $this->colLetter($col) . '2:' . $this->colLetter($col + count($grades) - 1) . '2';
            $sheet->setCellValue($this->colLetter($col) . '2', $product);
            if (count($grades) > 1) {
                $sheet->mergeCells($range);
            }
            $sheet->getStyle($range)->applyFromArray(self::BORDER);
            $sheet->getStyle($range)->getFont()->setBold(true);
            $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col += count($grades);
        }

        // Row 3: column headers
        $headers = self::FIXED_HEADERS;
        foreach ($columns as $grades) {
            $headers = array_merge($headers, $grades);
        }
        $headers = array_merge($headers, self::TRAILING_HEADERS);
        foreach ($headers as $index => $header) {
            $cell = $this->colLetter($index + 1) . '3';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->applyFromArray(self::BORDER);
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }

        if (empty($rows)) {
            $sheet->setCellValue('A4', 'No records found...');
            return;
        }

        // Data rows
        $rowIndex = 4;
        foreach ($rows as $row) {
            $line = $this->fixedValues($row);
            foreach ($columns as $product => $grades) {
                foreach ($grades as $grade) {
                    $line[] = (float)($row['gradeWeights'][$product . '|' . $grade] ?? 0);
                }
            }
            $line = array_merge($line, [(float)$row['totalGross'], (float)$row['totalTare'], (float)$row['totalNett'], $row['created_by'], $row['remark']]);
            $sheet->fromArray($line, null, 'A' . $rowIndex);
            $rowIndex++;
        }

        // Subtotal row: SUM formulas over grade + total weight columns
        $sheet->setCellValue($this->colLetter(count(self::FIXED_HEADERS)) . $rowIndex, 'SUBTOTAL');
        $firstNumeric = count(self::FIXED_HEADERS) + 1;
        $lastNumeric = $firstNumeric + $gradeCols + 2;
        for ($c = $firstNumeric; $c <= $lastNumeric; $c++) {
            $letter = $this->colLetter($c);
            $sheet->setCellValue($letter . $rowIndex, '=SUM(' . $letter . '4:' . $letter . ($rowIndex - 1) . ')');
            $sheet->getStyle($letter . '4:' . $letter . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
        }
        $sheet->getStyle('A' . $rowIndex . ':' . $this->colLetter($totalCols) . $rowIndex)->getFont()->setBold(true);
    }

    /**
     * No, Date, Start Time, End Time, Grading No, Location, Machine, Category
     */
    private function fixedValues(array $row): array
    {
        $start = strtotime($row['start_date']);

        return [
            $row['count'],
            date('d/m/Y', $start),
            date('H:i:s', $start),
            $row['end_date'] ? date('H:i:s', strtotime($row['end_date'])) : '',
            $row['grading_no'],
            $row['location'],
            $row['indicator'],
            $row['category']
        ];
    }

    /**
     * [product => [grade, ...]] (grades sorted) used by the given rows
     */
    private function productGradeColumns(array $rows): array
    {
        $columns = [];
        foreach ($rows as $row) {
            foreach (array_keys($row['gradeWeights']) as $key) {
                [$product, $grade] = explode('|', $key, 2);
                if (!in_array($grade, $columns[$product] ?? [], true)) {
                    $columns[$product][] = $grade;
                }
            }
        }
        foreach ($columns as &$grades) {
            sort($grades);
        }

        return $columns;
    }

    private function colLetter(int $n): string
    {
        $letter = '';
        while ($n > 0) {
            $n--;
            $letter = chr(65 + ($n % 26)) . $letter;
            $n = intdiv($n, 26);
        }

        return $letter;
    }
}
