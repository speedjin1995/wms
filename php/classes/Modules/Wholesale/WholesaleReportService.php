<?php
namespace App\Modules\Wholesale;

use App\Core\BaseService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

require_once __DIR__ . '/../../../lookup.php';
require_once __DIR__ . '/partial/print/printHelpers.php';

/**
 * Weighing slip / invoice HTML (templates in partial/print) and the per-record Excel export.
 */
class WholesaleReportService extends BaseService
{
    private const TEMPLATE_DIR = __DIR__ . '/partial/print/';
    private const PRINT_TEMPLATES = [
        'A4' => 'printA4.php',
        'A4Classic' => 'printA4Classic.php',
        'A4Price' => 'printA4Price.php',
        'A4PriceDetail' => 'printA4Price.php'
    ];

    private string $recordType;
    private array $languageArray;
    private string $language;

    public function __construct(\mysqli $db, int $company, int $user, string $role, string $recordType = 'wholesales', array $languageArray = [], string $language = 'en')
    {
        parent::__construct($db, $company, $user, $role);
        $this->recordType = $recordType === 'industrial' ? 'industrial' : 'wholesales';
        $this->languageArray = $languageArray;
        $this->language = $language;
    }

    /**
     * Weighing slip HTML. $options: paperSize (A4 / A5), a4Template, withPhoto, withDetails (Y / N), mode (full / content)
     */
    public function buildPrintHtml(int $id, array $options): ?string
    {
        $wholesale = $this->findPrintRecord($id);
        if (!$wholesale) {
            return null;
        }

        $paperSize = $options['paperSize'] === 'A5' ? 'A5' : 'A4';
        $a4Template = isset(self::PRINT_TEMPLATES[$options['a4Template']]) ? $options['a4Template'] : 'A4';
        $template = $paperSize === 'A5' ? 'printA5.php' : self::PRINT_TEMPLATES[$a4Template];

        // Variables used by the templates
        $db = $this->db;
        $mode = $options['mode'] === 'content' ? 'content' : 'full';
        $withPhoto = $options['withPhoto'] === 'Y' ? 'Y' : 'N';
        $withDetails = ($options['withDetails'] === 'Y' || $a4Template === 'A4PriceDetail') ? 'Y' : 'N';
        $companyDetail = searchCompanyById($wholesale['company'], $db);
        $companyLogoSrc = !empty($wholesale['company_logo']) ? 'php/viewPhoto.php?file=' . urlencode($wholesale['company_logo']) . '&type=file_table' : '';
        $weighingDetails = json_decode((string)$wholesale['weight_details'], true);
        $status = $wholesale['status'] === 'STOCK-BAL' ? 'Stock Balance' : ucwords(strtolower($wholesale['status']));
        $message = '';

        require self::TEMPLATE_DIR . $template;

        return $message;
    }

    /**
     * Invoice HTML (English labels, as before)
     */
    public function buildInvoiceHtml(int $id): ?string
    {
        $wholesale = $this->findPrintRecord($id);
        if (!$wholesale) {
            return null;
        }

        // Variables used by the template
        $db = $this->db;
        $languageArray = $this->languageArray;
        $language = 'en';
        $message = '';

        require self::TEMPLATE_DIR . 'invoice.php';

        return $message;
    }

    /**
     * Weighing details of one record as a spreadsheet. $show: photo, price, pcsBasket (bool). Null when not found.
     */
    public function buildExcel(int $id, array $show): ?array
    {
        $where = "id = ? AND records_type = ?";
        $params = [$id, $this->recordType];
        $types = 'is';
        $this->applyCompanyScope($where, $params, $types, 'company');
        $record = $this->fetchOne("SELECT id, company, serial_no, weight_details FROM wholesales WHERE $where", $types, $params);
        if (!$record) {
            return null;
        }

        $weightDetails = json_decode((string)$record['weight_details'], true);
        if (!is_array($weightDetails)) {
            $weightDetails = [];
        }
        $productNames = $this->namesById('products', 'product_name', array_column($weightDetails, 'product'));
        $currencyNames = $this->namesById('currency', 'currency', array_column($weightDetails, 'currency'));

        $headers = [$this->label('product_code', 'Product'), $this->label('grade_code', 'Grade'), $this->label('gross_code', 'Gross'), $this->label('tare_code', 'Tare'), $this->label('net_code', 'Net')];
        if ($show['pcsBasket']) {
            $headers[] = $this->label('pcs_basket_code', 'Pcs/Basket');
        }
        if ($show['price']) {
            $headers[] = $this->label('currency_code', 'Currency');
            $headers[] = $this->label('price_code', 'Price');
            $headers[] = $this->label('before_disc_code', 'Before Disc');
            $headers[] = $this->label('discount_code', 'Discount');
            $headers[] = $this->label('total_code', 'Total');
        }
        $headers[] = $this->label('time_code', 'Time');
        if ($show['photo']) {
            $headers[] = $this->label('photo_code', 'Photo');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Weighing Details');
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->setCellValue('A1', $this->label('serial_no_code', 'Serial No.') . ': ' . ($record['serial_no'] ?? ''));
        $sheet->mergeCells('A1:' . $lastColumn . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->fromArray($headers, null, 'A3');
        $sheet->getStyle('A3:' . $lastColumn . '3')->getFont()->setBold(true);
        $sheet->getStyle('A3:' . $lastColumn . '3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE9ECEF');
        $sheet->getStyle('A3:' . $lastColumn . '3')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->freezePane('A4');
        $sheet->setAutoFilter('A3:' . $lastColumn . '3');

        $totals = ['gross' => 0.0, 'tare' => 0.0, 'net' => 0.0, 'basket' => 0, 'before_discount' => 0.0, 'discount' => 0.0, 'total' => 0.0];

        foreach ($weightDetails as $detail) {
            $rowIndex = $sheet->getHighestRow() + 1;
            $rowData = [
                (string)($productNames[$detail['product'] ?? ''] ?? ''),
                (string)($detail['grade'] ?? ''),
                (float)($detail['gross'] ?? 0),
                (float)($detail['tare'] ?? 0),
                (float)($detail['net'] ?? 0)
            ];
            $totals['gross'] += (float)($detail['gross'] ?? 0);
            $totals['tare'] += (float)($detail['tare'] ?? 0);
            $totals['net'] += (float)($detail['net'] ?? 0);

            if ($show['pcsBasket']) {
                $basketCount = (int)($detail['no_per_basket'] ?? 0);
                $rowData[] = $basketCount;
                $totals['basket'] += $basketCount;
            }
            if ($show['price']) {
                $beforeDiscount = (float)($detail['before_discount'] ?? 0);
                $discount = (float)($detail['discount'] ?? 0);
                $isPercent = ($detail['discount_type'] ?? '') === 'percent';
                $rowData[] = (string)($currencyNames[$detail['currency'] ?? ''] ?? '');
                $rowData[] = (float)($detail['price'] ?? 0);
                $rowData[] = $beforeDiscount;
                $rowData[] = $isPercent ? number_format($discount, 2) . '%' : $discount;
                $rowData[] = (float)($detail['total'] ?? 0);
                $totals['before_discount'] += $beforeDiscount;
                $totals['discount'] += $isPercent ? $beforeDiscount * $discount / 100 : $discount;
                $totals['total'] += (float)($detail['total'] ?? 0);
            }

            $rowData[] = (string)($detail['time'] ?? '');
            if ($show['photo']) {
                $rowData[] = (string)($detail['photoPath'] ?? '');
            }

            foreach ($rowData as $columnIndex => $value) {
                $cell = Coordinate::stringFromColumnIndex($columnIndex + 1) . $rowIndex;
                if (is_string($value)) {
                    $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($cell, $value);
                }
            }
        }

        $totalRow = $sheet->getHighestRow() + 1;
        $sheet->setCellValue('A' . $totalRow, $this->label('total_code', 'Total'));
        $sheet->setCellValue('C' . $totalRow, $totals['gross']);
        $sheet->setCellValue('D' . $totalRow, $totals['tare']);
        $sheet->setCellValue('E' . $totalRow, $totals['net']);
        $columnIndex = 6;
        if ($show['pcsBasket']) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex++) . $totalRow, $totals['basket']);
        }
        if ($show['price']) {
            $columnIndex += 2;
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex++) . $totalRow, $totals['before_discount']);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex++) . $totalRow, $totals['discount']);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex) . $totalRow, $totals['total']);
        }
        $sheet->getStyle('A' . $totalRow . ':' . $lastColumn . $totalRow)->getFont()->setBold(true);

        foreach (range(1, count($headers)) as $index) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setAutoSize(true);
        }
        $sheet->getStyle('C4:' . $lastColumn . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');

        return [
            'spreadsheet' => $spreadsheet,
            'fileName' => 'Weighing_Details_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $record['serial_no'] ?: (string)$id) . '.xlsx'
        ];
    }

    /**
     * Record joined with its company row (the templates read both), within the session company
     */
    private function findPrintRecord(int $id): ?array
    {
        $where = "wholesales.id = ? AND wholesales.records_type = ?";
        $params = [$id, $this->recordType];
        $types = 'is';
        $this->applyCompanyScope($where, $params, $types, 'wholesales.company');

        // companies columns overwrite same-named wholesales columns, as in the legacy print endpoints
        return $this->fetchOne("SELECT * FROM wholesales LEFT JOIN companies ON wholesales.company = companies.id WHERE $where", $types, $params);
    }

    private function label(string $key, string $fallback): string
    {
        return $this->languageArray[$key][$this->language] ?? $fallback;
    }

    private function namesById(string $table, string $column, array $ids): array
    {
        $ids = array_values(array_unique($this->cleanIds($ids)));
        if (empty($ids)) {
            return [];
        }

        $names = [];
        foreach ($this->fetchAll("SELECT id, $column FROM $table WHERE id IN (" . $this->placeholders(count($ids)) . ")", str_repeat('i', count($ids)), $ids) as $row) {
            $names[$row['id']] = $row[$column];
        }

        return $names;
    }
}
