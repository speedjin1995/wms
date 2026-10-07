<?php
namespace App\Modules\Wholesale;

use App\Core\BaseService;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

require_once __DIR__ . '/../../../lookup.php';
require_once __DIR__ . '/partial/print/printHelpers.php';

/**
 * Report page exports of the wholesales table: summary Excel, summary / invoice listing PDF, integration Excel
 * and the stock balance PDF (templates in partial/pdf). Records come from WholesaleService.
 */
class WholesaleExportService extends BaseService
{
    private const PDF_DIR = __DIR__ . '/partial/pdf/';
    private const PDF_TEMPLATES = [
        'summary' => 'pdfSummary.php',
        'invoice' => 'pdfInvoiceListing.php'
    ];
    private const DISPATCH_STATUSES = ['DISPATCH', 'STOCK-BAL', 'OUTGOING'];
    private const RECEIVING_STATUSES = ['RECEIVING', 'INCOMING'];

    /**
     * Summary Excel: an ALL sheet plus one sheet per location, weights by product / grade with subtotals.
     * Price columns need the company price feature and $userAllowPrice.
     */
    public function buildReportExcel(array $records, array $filters, bool $userAllowPrice): array
    {
        $companyDetail = searchCompanyById($this->company, $this->db);
        $options = [
            'allowPrice' => ($companyDetail['include_price'] ?? 'N') === 'Y' && $userAllowPrice,
            'allowPcsBasket' => ($companyDetail['include_pcs_basket'] ?? 'N') === 'Y',
            'defaultCurrency' => $this->defaultCurrency(),
            'transactionStatus' => (string)($filters['transactionStatus'] ?? ''),
            'fromDate' => $this->displayDate($filters['fromDate'] ?? '') ?? '',
            'toDate' => $this->displayDate($filters['toDate'] ?? '') ?? ''
        ];

        [$allRows, $productGradeColumns] = $this->summaryRows($records, $options['defaultCurrency']);

        if (in_array($options['transactionStatus'], self::DISPATCH_STATUSES, true)) {
            $options['fixedHeaders'] = ['No', 'Date', 'Time', 'Location', 'Machine Nickname', 'Weigh Slip No.', 'Delivery No.', 'Customer'];
        } else {
            $options['fixedHeaders'] = ['No', 'Date', 'Time', 'Location', 'Machine Nickname', 'Weigh Slip No.', 'Purchase No.', 'Security Bill No.', 'Supplier'];
        }

        $trailingHeaders = ['Total Weight', 'Total Bin Weight', 'Actual Weight', 'Reject Weight'];
        if ($options['allowPcsBasket']) {
            $trailingHeaders[] = 'Total Pcs/Basket';
        }
        if ($options['allowPrice']) {
            array_push($trailingHeaders, 'Currency', 'Total Price (RM)', 'Actual Price (RM)');
        }
        $options['trailingHeaders'] = array_merge($trailingHeaders, ['Vehicle No.', 'Driver Name', 'Weigh By', 'Checked By', 'Remark']);

        $spreadsheet = new Spreadsheet();
        $allSheet = $spreadsheet->getActiveSheet();
        $allSheet->setTitle('ALL');
        $this->writeSummarySheet($allSheet, $allRows, $productGradeColumns, $options);

        // One sheet per location (machine)
        $machineGroups = [];
        foreach ($allRows as $rowData) {
            $machineGroups[$rowData['locationName']][] = $rowData;
        }

        foreach ($machineGroups as $locationName => $machineRows) {
            $machineColumns = [];
            foreach ($machineRows as $rowData) {
                foreach (array_keys($rowData['gradeWeights']) as $key) {
                    $parts = explode('|', $key, 2);
                    $grade = $parts[1] ?? 'Unknown';
                    if (!in_array($grade, $machineColumns[$parts[0]] ?? [])) {
                        $machineColumns[$parts[0]][] = $grade;
                    }
                }
            }
            foreach ($machineColumns as &$grades) {
                sort($grades);
            }
            unset($grades);

            $sheetTitle = substr(preg_replace('#[\\\\/:*?\[\]]#', '_', $locationName), 0, 31);
            if (trim($sheetTitle) === '') {
                $sheetTitle = 'Unknown';
            }

            $machineSheet = $spreadsheet->createSheet();
            $machineSheet->setTitle($sheetTitle);
            $this->writeSummarySheet($machineSheet, $machineRows, $machineColumns, $options);
        }

        return ['spreadsheet' => $spreadsheet, 'fileName' => 'Report_' . date('Y-m-d') . '.xlsx'];
    }

    /**
     * Summary (A4 landscape) or invoice listing (A4) PDF. $reportType: summary / invoice.
     */
    public function buildReportPdf(array $records, array $filters, string $reportType, bool $userAllowPrice): array
    {
        $isInvoice = $reportType === 'invoice';
        $mpdf = $this->newPdf([
            'format' => $isInvoice ? 'A4' : 'A4-L',
            'margin_top' => $isInvoice ? 45 : 10,
            'margin_header' => $isInvoice ? 5 : 0
        ]);

        // Variables used by the templates
        $db = $this->db;
        $companyDetail = searchCompanyById($this->company, $db);
        $allowPrice = $companyDetail['include_price'] ?? 'N';
        $allowPcsBasket = $companyDetail['include_pcs_basket'] ?? 'N';
        $userAllowPrice = $userAllowPrice ? 'Y' : 'N';
        $defaultCurrency = $this->defaultCurrency();
        $transactionStatus = (string)($filters['transactionStatus'] ?? '');
        // Null when not filtered (the invoice listing then shows today's date)
        $fromDate = $this->displayDate($filters['fromDate'] ?? '');
        $toDate = $this->displayDate($filters['toDate'] ?? '');

        require self::PDF_DIR . (self::PDF_TEMPLATES[$reportType] ?? self::PDF_TEMPLATES['summary']);

        return ['pdf' => $mpdf, 'fileName' => 'Report_' . date('Y-m-d') . '.pdf'];
    }

    /**
     * Active integration config of the session company, null when not found / without columns
     */
    public function getIntegrationConfig(int $configId): ?array
    {
        $config = $this->fetchOne("SELECT * FROM integration_configs WHERE id = ? AND company_id = ? AND deleted = 0", 'ii', [$configId, $this->company]);
        if (!$config) {
            return null;
        }

        $mapping = json_decode((string)$config['config_json'], true);
        if (empty($mapping['columns'])) {
            return null;
        }

        return ['name' => $config['name'], 'label' => $mapping['label'] ?? $config['name'], 'columns' => $mapping['columns']];
    }

    /**
     * Integration Excel: one row per weight row, columns mapped by the integration config
     */
    public function buildIntegrationExcel(array $config, array $records): array
    {
        $columns = $config['columns'];
        $caches = ['customer' => [], 'supplier' => [], 'product' => []];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $colIndex = 1;
        foreach (array_keys($columns) as $colName) {
            $coord = Coordinate::stringFromColumnIndex($colIndex++) . '1';
            $sheet->setCellValue($coord, $colName);
            $sheet->getStyle($coord)->getFont()->setBold(true);
        }

        $rowIndex = 2;
        foreach ($records as $row) {
            foreach (json_decode((string)$row['weight_details'], true) ?: [] as $detail) {
                $colIndex = 1;
                foreach ($columns as $colMap) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex++) . $rowIndex, $this->integrationValue($colMap, $row, $detail, $caches));
                }
                $rowIndex++;
            }
        }

        return ['spreadsheet' => $spreadsheet, 'fileName' => $config['label'] . '_' . date('Y-m-d') . '.xlsx'];
    }

    /**
     * Stock balance PDF (A4 landscape) by location / category / product / grade
     */
    public function buildStockBalancePdf(array $records, array $filters): array
    {
        $mpdf = $this->newPdf(['format' => 'A4-L', 'margin_top' => 30, 'margin_header' => 5]);

        // Variables used by the template
        $db = $this->db;
        $company = $this->company;
        $companyDetail = searchCompanyById($company, $db);
        $defaultCurrency = $this->defaultCurrency();
        $asAtDate = $this->displayDate($filters['asAtDate'] ?? '') ?? date('d/m/Y');

        require self::PDF_DIR . 'pdfStockBalance.php';

        return ['pdf' => $mpdf, 'fileName' => 'StockBalance_' . date('Y-m-d') . '.pdf'];
    }

    /**
     * mPDF with the shared report font; $config overrides the format / margins
     */
    protected function newPdf(array $config)
    {
        return new Mpdf(array_merge([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'tempDir' => sys_get_temp_dir(),
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_header' => 0,
            'fontDir' => [__DIR__ . '/../../../../vendor/mpdf/mpdf/ttfonts/'],
            'fontdata' => ['sunexta' => ['R' => 'Sun-ExtA.ttf']],
            'default_font' => 'sunexta'
        ], $config));
    }

    /**
     * Summary rows (weights / prices by product|grade) and the sorted product => grades columns
     */
    private function summaryRows(array $records, string $defaultCurrency): array
    {
        $productGradeColumns = [];
        $allRows = [];
        $count = 1;

        foreach ($records as $row) {
            $startTime = new \DateTime($row['start_time']);
            $totals = ['totalWeight' => 0, 'totalBinWeight' => 0, 'totalPcsBasket' => 0, 'totalPrice' => 0, 'actualPrice' => 0];
            $currency = '';
            $gradeWeights = [];
            $gradePrice = [];
            $gradeActualPrice = [];
            $currencyTotals = [];

            foreach (arrangeByProductGrade(json_decode((string)$row['weight_details'], true)) as $product => $grades) {
                foreach ($grades as $grade => $details) {
                    if (!in_array($grade, $productGradeColumns[$product] ?? [])) {
                        $productGradeColumns[$product][] = $grade;
                    }

                    $gradeKey = $product . '|' . $grade;
                    $gradeNettWeight = 0;
                    foreach ($details as $detail) {
                        $gradeNettWeight += floatval($detail['net'] ?? 0);
                        $totals['totalWeight'] += floatval($detail['gross'] ?? 0);
                        $totals['totalBinWeight'] += floatval($detail['tare'] ?? 0);
                        $totals['totalPcsBasket'] += floatval($detail['no_per_basket'] ?? 0);

                        if (empty($currency) && !empty($detail['currency'])) {
                            $currency = searchCurrencyNameById($detail['currency'], $this->db);
                        }
                        $detailCurrency = !empty($detail['currency']) ? searchCurrencyNameById($detail['currency'], $this->db) : $defaultCurrency;
                        if (empty($detailCurrency)) {
                            $detailCurrency = $defaultCurrency;
                        }

                        $price = floatval($detail['price'] ?? 0);
                        if (($detail['fixedfloat'] ?? '') == 'fixed') {
                            $detailTotal = $price;
                            $detailActual = $price;
                        } else {
                            $detailTotal = floatval($detail['gross'] ?? 0) * $price;
                            $detailActual = (floatval($detail['net'] ?? 0) - floatval($detail['reject'] ?? 0)) * $price;
                        }

                        $totals['totalPrice'] += $detailTotal;
                        $totals['actualPrice'] += $detailActual;
                        $gradePrice[$gradeKey][$detailCurrency] = ($gradePrice[$gradeKey][$detailCurrency] ?? 0) + $detailTotal;
                        $gradeActualPrice[$gradeKey][$detailCurrency] = ($gradeActualPrice[$gradeKey][$detailCurrency] ?? 0) + $detailActual;
                        $currencyTotals[$detailCurrency]['totalPrice'] = ($currencyTotals[$detailCurrency]['totalPrice'] ?? 0) + $detailTotal;
                        $currencyTotals[$detailCurrency]['actualPrice'] = ($currencyTotals[$detailCurrency]['actualPrice'] ?? 0) + $detailActual;
                    }

                    $gradeWeights[$gradeKey] = $gradeNettWeight;
                }
            }

            $totalReject = 0;
            foreach (json_decode((string)$row['reject_details'], true) ?: [] as $reject) {
                $totalReject += floatval($reject['net'] ?? 0);
            }

            $allRows[] = array_merge($totals, [
                'count' => $count++,
                'formattedDate' => $startTime->format('d/m/Y'),
                'formattedTime' => $startTime->format('H:i'),
                'serial_no' => $row['serial_no'],
                'po_no' => $row['po_no'],
                'security_bills' => $row['security_bills'],
                'indicator' => $row['indicator'],
                'status' => $row['status'],
                'customer' => $row['customer'],
                'other_customer' => $row['other_customer'],
                'supplier' => $row['supplier'],
                'other_supplier' => $row['other_supplier'],
                'gradeWeights' => $gradeWeights,
                'gradePrice' => $gradePrice,
                'gradeActualPrice' => $gradeActualPrice,
                'currencyTotals' => $currencyTotals,
                'total_reject' => $totalReject,
                'actualWeight' => $totals['totalWeight'] - $totals['totalBinWeight'],
                'currency' => $currency,
                'locationName' => !empty($row['location']) ? (searchLocationById($row['location'], $this->db) ?: 'Unknown') : 'Unknown',
                'vehicle_no' => $row['vehicle_no'],
                'driver' => $row['driver'],
                'checked_by' => $row['checked_by'],
                'weighted_by' => searchUserNameById($row['weighted_by'], $this->db),
                'remark' => $row['remark']
            ]);
        }

        foreach ($productGradeColumns as &$grades) {
            sort($grades);
        }
        unset($grades);

        return [$allRows, $productGradeColumns];
    }

    /**
     * One summary sheet: date range (row 1), product headers (row 2), column headers (row 3), data rows,
     * SUBTOTAL row and TOTAL PRICE rows per currency
     */
    private function writeSummarySheet(Worksheet $sheet, array $rows, array $productGradeColumns, array $options): void
    {
        $borderStyle = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];
        $col = function (int $index): string {
            return Coordinate::stringFromColumnIndex($index);
        };
        $isReceiving = in_array($options['transactionStatus'], self::RECEIVING_STATUSES, true);

        $colIndex = 1;
        foreach ($options['fixedHeaders'] as $header) {
            $this->writeHeaderCell($sheet, $col($colIndex++) . '3', $header, $borderStyle);
        }

        $gradeStartColIndex = $colIndex;
        foreach ($productGradeColumns as $product => $grades) {
            $range = $col($colIndex) . '2:' . $col($colIndex + count($grades) - 1) . '2';
            $sheet->setCellValue($col($colIndex) . '2', $product);
            if (count($grades) > 1) {
                $sheet->mergeCells($range);
            }
            $sheet->getStyle($range)->applyFromArray($borderStyle);
            $sheet->getStyle($range)->getFont()->setBold(true);
            $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            foreach ($grades as $grade) {
                $this->writeHeaderCell($sheet, $col($colIndex++) . '3', $grade, $borderStyle);
            }
        }

        foreach ($options['trailingHeaders'] as $header) {
            $this->writeHeaderCell($sheet, $col($colIndex++) . '3', $header, $borderStyle);
        }

        $lastCol = $col($colIndex - 1);
        $sheet->setCellValue('A1', 'Date: ' . ($options['fromDate'] ?: '-') . ' to ' . ($options['toDate'] ?: '-'));
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->mergeCells('A1:' . $lastCol . '1');

        if (empty($rows)) {
            $sheet->setCellValue('A3', 'No records found...');
            return;
        }

        $currencySubtotals = [];
        foreach ($rows as $rowData) {
            foreach ($rowData['currencyTotals'] as $cur => $totals) {
                $currencySubtotals[$cur]['totalPrice'] = ($currencySubtotals[$cur]['totalPrice'] ?? 0) + $totals['totalPrice'];
                $currencySubtotals[$cur]['actualPrice'] = ($currencySubtotals[$cur]['actualPrice'] ?? 0) + $totals['actualPrice'];
            }
        }

        // Data rows
        $rowIndex = 4;
        $numericCols = [];
        foreach ($rows as $rowData) {
            $line = [
                $rowData['count'], $rowData['formattedDate'], $rowData['formattedTime'], $rowData['locationName'],
                $rowData['indicator'], $rowData['serial_no'], $rowData['po_no']
            ];
            if ($isReceiving) {
                $line[] = $rowData['security_bills'];
            }

            $isDispatch = $rowData['status'] == 'DISPATCH' || $rowData['status'] == 'STOCK-BAL' || $options['transactionStatus'] == 'OUTGOING';
            $line[] = $isDispatch
                ? searchCustomerNameById($rowData['customer'], $rowData['other_customer'], $this->db)
                : searchSupplierNameById($rowData['supplier'], $rowData['other_supplier'], $this->db);

            $numbers = [];
            foreach ($productGradeColumns as $product => $grades) {
                foreach ($grades as $grade) {
                    $numbers[] = floatval($rowData['gradeWeights'][$product . '|' . $grade] ?? 0);
                }
            }
            array_push($numbers, floatval($rowData['totalWeight']), floatval($rowData['totalBinWeight']), floatval($rowData['actualWeight']), floatval($rowData['total_reject']));
            if ($options['allowPcsBasket']) {
                $numbers[] = $rowData['totalPcsBasket'];
            }
            foreach ($numbers as $number) {
                $line[] = $number;
                $numericCols[count($line)] = true;
            }

            if ($options['allowPrice']) {
                $line[] = !empty($rowData['currency']) ? $rowData['currency'] : $options['defaultCurrency'];
                $line[] = floatval($rowData['totalPrice']);
                $numericCols[count($line)] = true;
                $line[] = floatval($rowData['actualPrice']);
                $numericCols[count($line)] = true;
            }

            array_push($line, $rowData['vehicle_no'], $rowData['driver'], $rowData['weighted_by'], $rowData['checked_by'], $rowData['remark']);
            $sheet->fromArray($line, null, 'A' . $rowIndex);
            $rowIndex++;
        }

        // SUBTOTAL row: sums of the grade columns and the four weight columns
        $dataEndRow = $rowIndex - 1;
        $sheet->setCellValue($col($isReceiving ? 9 : 8) . $rowIndex, 'SUBTOTAL');
        $weightColCount = array_sum(array_map('count', $productGradeColumns)) + 4;
        for ($i = 0; $i < $weightColCount; $i++) {
            $letter = $col($gradeStartColIndex + $i);
            $sheet->setCellValue($letter . $rowIndex, '=SUM(' . $letter . '4:' . $letter . $dataEndRow . ')');
        }
        $sheet->getStyle('A' . $rowIndex . ':' . $lastCol . $rowIndex)->getFont()->setBold(true);
        $rowIndex++;

        // TOTAL PRICE rows per currency
        if ($options['allowPrice']) {
            foreach ($currencySubtotals as $cur => $curTotals) {
                $sheet->setCellValue($col(count($options['fixedHeaders'])) . $rowIndex, 'TOTAL PRICE (' . $cur . ')');

                $ci = $gradeStartColIndex;
                foreach ($productGradeColumns as $product => $grades) {
                    foreach ($grades as $grade) {
                        $actual = 0;
                        foreach ($rows as $rowData) {
                            $actual += $rowData['gradeActualPrice'][$product . '|' . $grade][$cur] ?? 0;
                        }
                        $sheet->setCellValue($col($ci++) . $rowIndex, floatval($actual));
                    }
                }

                // Skips the four weight columns only (as before, so with Pcs/Basket on these land one column early)
                $ci += 4;
                $sheet->setCellValue($col($ci++) . $rowIndex, $cur);
                $sheet->setCellValue($col($ci++) . $rowIndex, floatval($curTotals['totalPrice']));
                $sheet->setCellValue($col($ci) . $rowIndex, floatval($curTotals['actualPrice']));

                $sheet->getStyle('A' . $rowIndex . ':' . $lastCol . $rowIndex)->getFont()->setBold(true);
                $rowIndex++;
            }
        }

        foreach (array_keys($numericCols) as $ci) {
            $sheet->getStyle($col($ci) . '4:' . $col($ci) . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
        }
    }

    private function writeHeaderCell(Worksheet $sheet, string $coord, string $value, array $borderStyle): void
    {
        $sheet->setCellValue($coord, $value);
        $sheet->getStyle($coord)->applyFromArray($borderStyle);
        $sheet->getStyle($coord)->getFont()->setBold(true);
    }

    /**
     * Value of one integration column for a weight row. $colMap: source, field, value, format, case (upper / lower / title)
     */
    private function integrationValue(array $colMap, array $row, array $detail, array &$caches)
    {
        $field = $colMap['field'] ?? '';

        switch ($colMap['source'] ?? '') {
            case 'wholesales':
                if ($field === 'created_datetime' && !empty($colMap['format'])) {
                    $result = (new \DateTime($row[$field]))->format($colMap['format']);
                } else {
                    $result = $row[$field] ?? '';
                }
                break;
            case 'customer_lookup':
                $result = getCustomerById($row['customer'], $this->db, $caches['customer'])[$field] ?? '';
                break;
            case 'supplier_lookup':
                $result = getSupplierById($row['supplier'], $this->db, $caches['supplier'])[$field] ?? '';
                break;
            case 'currency_lookup':
                $result = searchCurrencyNameById($detail[$field] ?? '', $this->db);
                break;
            case 'location_lookup':
                $result = searchLocationById($row['location'], $this->db);
                break;
            case 'product_lookup':
                $result = getProductById($detail['product'] ?? '', $this->db, $caches['product'])[$field] ?? '';
                break;
            case 'detail':
                $result = $detail[$field] ?? '';
                break;
            case 'computed':
                $result = $field === 'net_minus_reject' ? floatval($detail['net'] ?? 0) - floatval($detail['reject'] ?? 0) : '';
                break;
            case 'static':
                $result = $colMap['value'] ?? '';
                break;
            default:
                $result = '';
        }

        if (!empty($colMap['case']) && is_string($result)) {
            if ($colMap['case'] === 'upper') {
                $result = mb_strtoupper($result);
            } elseif ($colMap['case'] === 'lower') {
                $result = mb_strtolower($result);
            } elseif ($colMap['case'] === 'title') {
                $result = mb_convert_case($result, MB_CASE_TITLE);
            }
        }

        return $result;
    }

    private function defaultCurrency(): string
    {
        $row = $this->fetchOne("SELECT currency FROM currency WHERE customer = ? AND is_default = 1 AND deleted = 0 LIMIT 1", 'i', [$this->company]);

        return $row['currency'] ?? 'MYR';
    }

    /**
     * d/m/Y request date normalised to dd/mm/yyyy, null when empty / invalid
     */
    private function displayDate(string $value): ?string
    {
        $date = $value !== '' ? \DateTime::createFromFormat('d/m/Y', $value) : false;

        return $date ? $date->format('d/m/Y') : null;
    }
}
