<?php
namespace App\Modules\Weighbridge;

use App\Core\BaseService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Builds weighbridge report documents (Excel workbook, PDF report HTML, weighing slip HTML).
 * Rows come from WeighbridgeService; this class only formats them.
 */
class WeighbridgeReportService extends BaseService
{
    private array $userNames = [];
    private array $driverIcs = [];

    /**
     * Grouped weighing report as a spreadsheet
     */
    public function buildExcel(array $rows): Spreadsheet
    {
        $company = $this->companyDetail();
        $arranged = $this->arrangeByCustomerOrSupplier($rows);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $rowIndex = 1;

        // Company header
        $sheet->setCellValue('A' . $rowIndex, $company['name'] ?? '');
        $sheet->getStyle('A' . $rowIndex)->getFont()->setBold(true);
        foreach (['address', 'address2', 'address3', 'address4'] as $field) {
            $rowIndex++;
            $sheet->setCellValue('A' . $rowIndex, $company[$field] ?? '');
        }
        $rowIndex += 2;

        foreach ($arranged['data'] as $status => $customerSuppliers) {
            $sheet->setCellValue('A' . $rowIndex, 'WEEKLY MONTHLY ' . $this->reportType($status) . ' REPORT WEIGHING');
            $sheet->getStyle('A' . $rowIndex)->getFont()->setBold(true);
            $rowIndex++;

            foreach ($customerSuppliers as $customerSupplier => $groupRows) {
                $headers = $this->columnHeaders($status);
                $lastCol = chr(64 + count($headers));
                $range = $arranged['dateRanges'][$status . '_' . $customerSupplier];

                $sheet->getStyle('A' . $rowIndex . ':' . $lastCol . $rowIndex)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                $sheet->setCellValue('A' . $rowIndex, $this->partyLabel($status) . ': ' . $customerSupplier);
                $sheet->setCellValue('B' . $rowIndex, 'From Date: ' . $this->formatDate($range['from']) . ' - ' . $this->formatDate($range['to']));
                $rowIndex += 2;

                $sheet->getStyle('A' . $rowIndex . ':' . $lastCol . $rowIndex)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                $sheet->fromArray($headers, null, 'A' . $rowIndex);
                $sheet->getStyle('A' . $rowIndex . ':' . $lastCol . $rowIndex)->getFont()->setBold(true);
                $rowIndex++;

                $totals = $this->emptyTotals();
                foreach ($groupRows as $index => $row) {
                    $this->addToTotals($totals, $row);
                    $sheet->fromArray($this->lineData($index + 1, $row), null, 'A' . $rowIndex);
                    $rowIndex++;
                }

                // Subtotal row (weight columns shift by one when SEC BILL NO is present)
                $shift = $this->isReceiving($status) ? 1 : 0;
                $sheet->setCellValue('A' . $rowIndex, 'SUBTOTAL');
                $sheet->getStyle('A' . $rowIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $columns = ['in' => 'H', 'out' => 'J', 'reduce' => 'L', 'nett' => 'M', 'supply' => 'N', 'variance' => 'O'];
                foreach ($columns as $key => $column) {
                    $sheet->setCellValue(chr(ord($column) + $shift) . $rowIndex, number_format($totals[$key], 2));
                }
                $sheet->getStyle('A' . $rowIndex . ':' . $lastCol . $rowIndex)->getFont()->setBold(true);
                $rowIndex += 3;
            }
        }

        return $spreadsheet;
    }

    /**
     * Grouped weighing report as HTML for mPDF
     */
    public function buildPdfHtml(array $rows): string
    {
        $e = [$this, 'escape'];
        $company = $this->companyDetail();
        $arranged = $this->arrangeByCustomerOrSupplier($rows);

        $totalGroups = 0;
        foreach ($arranged['data'] as $customerSuppliers) {
            $totalGroups += count($customerSuppliers);
        }

        $html = '
        <html>
        <head>
            <title>Weekly Monthly Dispatch Report Weighing</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 10px; margin: 0; padding: 0; }
                .mb-1 { margin-bottom: 0.25rem; }
                .fw-bold { font-weight: bold; }
                .text-muted { color: #6c757d; }
                .header { font-size: 18px; margin-bottom: 20px; }
                .company-info { font-size: 14px; margin-bottom: 10px; }
                .table-container { margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; font-size: 9px; }
                th, td { border: 1px solid black; padding: 2px; text-align: center; }
                th { background-color: #f0f0f0; font-weight: bold; }
                hr { margin: 1rem 0; border: 0; height: 1px; background-color: #343a40; opacity: 0.25; }
            </style>
        </head>
        <body>
            <div class="company-info mb-1">
                <div class="fw-bold">' . $e($company['name'] ?? '') . '</div>
                <div class="text-muted">
                    <div>' . $e($company['address'] ?? '') . '</div>
                    <div>' . $e($company['address2'] ?? '') . '</div>
                    <div>' . $e($company['address3'] ?? '') . '</div>
                    <div>' . $e($company['address4'] ?? '') . '</div>
                </div>
            </div>
            <hr>';

        $groupIndex = 0;
        foreach ($arranged['data'] as $status => $customerSuppliers) {
            $html .= '
            <div class="header mb-1">
                <table style="width: 100%; border: none;">
                    <tr>
                        <td style="border: none; text-align: left; padding: 0 0 5px 0; font-size: 14px;">
                            <div class="fw-bold">WEEKLY MONTHLY ' . $this->reportType($status) . ' REPORT WEIGHING</div>
                        </td>
                    </tr>
                </table>
            </div>
            <hr>';

            foreach ($customerSuppliers as $customerSupplier => $groupRows) {
                $range = $arranged['dateRanges'][$status . '_' . $customerSupplier];
                $headers = $this->columnHeaders($status);

                $html .= '
                <div class="header mb-1">
                    <table style="width: 100%; border: none;">
                        <tr>
                            <td style="border: none; text-align: left; font-size: 12px;">' . $this->partyLabel($status) . ': ' . $e($customerSupplier) . '</td>
                            <td style="border: none; text-align: left; font-size: 12px;">From Date: ' . $this->formatDate($range['from']) . ' - ' . $this->formatDate($range['to']) . '</td>
                        </tr>
                    </table>
                </div>
                <hr>
                <div class="table-container">
                    <table>
                        <thead><tr>';
                foreach ($headers as $header) {
                    $html .= '<th>' . $header . '</th>';
                }
                $html .= '</tr></thead>
                        <tbody>';

                $totals = $this->emptyTotals();
                foreach ($groupRows as $index => $row) {
                    $this->addToTotals($totals, $row);
                    $html .= '<tr>';
                    foreach ($this->lineData($index + 1, $row) as $value) {
                        $html .= '<td>' . $e($value) . '</td>';
                    }
                    $html .= '</tr>';
                }

                $html .= '
                            <tr style="font-weight: bold;">
                                <td colspan="' . ($this->isReceiving($status) ? '8' : '7') . '" style="text-align: right;">SUBTOTAL</td>
                                <td>' . number_format($totals['in'], 2) . '</td>
                                <td></td>
                                <td>' . number_format($totals['out'], 2) . '</td>
                                <td></td>
                                <td>' . number_format($totals['reduce'], 2) . '</td>
                                <td>' . number_format($totals['nett'], 2) . '</td>
                                <td>' . number_format($totals['supply'], 2) . '</td>
                                <td>' . number_format($totals['variance'], 2) . '</td>
                                <td colspan="6"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>';

                if ($groupIndex < $totalGroups - 1) {
                    $html .= '<hr>';
                }
                $groupIndex++;
            }
        }

        return $html . '
        </body>
        </html>';
    }

    /**
     * Weighing slip (A5 landscape) for a single record
     */
    public function buildSlipHtml(array $row): string
    {
        $e = [$this, 'escape'];
        $status = $row['transaction_status'];
        $isReceivingSide = in_array($status, ['Purchase', 'Receiving', 'Local'], true);
        $party = $isReceivingSide ? $row['supplier_name'] : $row['customer_name'];
        $partyLabel = in_array($status, ['Dispatch', 'Sales', 'Misc'], true) ? 'Customer' : 'Supplier';

        return '<html>
            <head>
                <style>
                    @media print {
                        @page {
                            size: A5 landscape;
                            margin-left: 0.5in;
                            margin-right: 0.5in;
                            margin-top: 0.1in;
                            margin-bottom: 0.1in;
                            padding-left: 0.2in;
                            padding-right: 0.2in;
                        }
                    }
                    table { width: 100%; border-collapse: collapse; }
                    .cell { border: 1px solid black; font-size: 12px; text-align: center; }
                    .label { font-size: 12px; }
                </style>
            </head>
            <body>
                <table style="width:100%;">
                    <tr>
                        <td style="width: 60%;">
                            <p style="font-size: 14px;">
                                <span style="font-weight: bold;font-size: 16px; margin-bottom: 10px; display: inline-block;">' . $e($row['company_name']) . '</span><br>
                                <span> Reg No.: ' . $e($row['company_reg_no']) . '</span><br>
                                <span>' . $e($row['company_address1']) . '</span><br>
                                <span>' . $e($row['company_address2']) . '</span><br>
                                <span>' . $e($row['company_address3']) . '</span><br>
                                <span>Tel/Fax: ' . $e($row['company_phone']) . ' / ' . $e($row['company_fax']) . '</span>
                            </p>
                        </td>
                        <td style="vertical-align: top;">
                            <p style="vertical-align: top; font-size: 14px;">
                                <span style="font-size: 24px; font-weight: bold; margin-bottom: 10px; display: inline-block;">*** ' . $this->slipType($status) . ' Slip ***</span><br>
                                <span>Transaction ID. </span><span style="margin-left: 10px;">:&nbsp;&nbsp;<b>' . $e($row['transaction_id']) . '</b></span><br>
                                <span>Date </span><span style="margin-left: 70px;">:&nbsp;&nbsp;' . $this->formatDate($row['transaction_date']) . '</span><br>
                                <span>PO No. </span><span style="margin-left:55px">:&nbsp;&nbsp;' . $e($row['purchase_order']) . '</span><br>
                                <span>Security Bill No. </span><span style="margin-left: 2px;">:&nbsp;&nbsp;' . $e($row['invoice_no']) . '</span><br>
                                <span>Checked By </span><span style="margin-left: 29px;">:&nbsp;&nbsp;' . $e($this->userName($row['approved_by'])) . '</span><br>
                            </p>
                        </td>
                    </tr>
                    <tr style="visibility:hidden;">
                        <td style="font-size: 3px;">Placeholder for empty space</td>
                    </tr>
                </table>
                <br>
                <table style="width:100%; border:0px solid black; margin-top: -10px;">
                    <tr>
                        <td class="label" width="15%">' . $partyLabel . ' Name</td>
                        <td class="label" width="2%">:</td>
                        <td class="label" width="33%">' . $e($party) . '</td>
                        <td class="cell" style="font-weight:bold;" width="12%">Status</td>
                        <td class="cell" style="font-weight:bold;" width="18%">Date / Time</td>
                        <td class="cell" style="font-weight:bold;" width="12%">Weight</td>
                        <td class="cell" style="font-weight:bold;" width="8%">Weight By</td>
                    </tr>
                    <tr>
                        <td class="label">Vehicle No</td>
                        <td class="label">:</td>
                        <td class="label">' . $e($row['lorry_plate_no1']) . '</td>
                        <td class="cell">In</td>
                        <td class="cell">' . $this->formatDateTime($row['gross_weight1_date']) . '</td>
                        <td class="cell">' . $this->formatWeight($row['gross_weight1']) . 'kg</td>
                        <td class="cell">' . $e($this->userName($row['gross_weight_by1'])) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Product</td>
                        <td class="label">:</td>
                        <td class="label">' . $e($row['product_name']) . '</td>
                        <td class="cell">Out</td>
                        <td class="cell">' . $this->formatDateTime($row['tare_weight1_date']) . '</td>
                        <td class="cell">' . $this->formatWeight($row['tare_weight1']) . 'kg</td>
                        <td class="cell">' . $e($this->userName($row['tare_weight_by1'])) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Remark</td>
                        <td class="label">:</td>
                        <td class="label">' . $e($row['remarks']) . '</td>
                        <td colspan="2" class="cell" style="font-weight:bold;">Net</td>
                        <td class="cell" style="font-weight:bold;">' . $this->formatWeight($row['final_weight']) . 'kg</td>
                        <td class="cell"></td>
                    </tr>
                </table><br>
            </body>
        </html>';
    }

    public function escape($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Group rows by transaction status then customer (dispatch side) / supplier (receiving side),
     * tracking each group's date range
     */
    private function arrangeByCustomerOrSupplier(array $rows): array
    {
        $arranged = [];
        $dateRanges = [];

        foreach ($rows as $row) {
            $status = $row['transaction_status'];
            $party = (string)($this->isDispatchSide($status) ? $row['customer_name'] : $row['supplier_name']);
            $arranged[$status][$party][] = $row;

            $key = $status . '_' . $party;
            if (!isset($dateRanges[$key])) {
                $dateRanges[$key] = ['from' => $row['transaction_date'], 'to' => $row['transaction_date']];
            } else {
                $dateRanges[$key]['from'] = min($dateRanges[$key]['from'], $row['transaction_date']);
                $dateRanges[$key]['to'] = max($dateRanges[$key]['to'], $row['transaction_date']);
            }
        }

        return ['data' => $arranged, 'dateRanges' => $dateRanges];
    }

    private function columnHeaders(string $status): array
    {
        $dispatch = $this->isDispatchSide($status);
        $headers = ['NO', 'DATE', 'TIME', 'WEIGHING SLIP NO', ($dispatch ? 'DELIVERY' : 'PURCHASE') . ' No.'];

        if ($this->isReceiving($status)) {
            $headers[] = 'SEC BILL NO';
        }

        return array_merge($headers, [
            'PRODUCT DESCRIPTION', 'VEHICLE NO', 'IN WEIGHT (KG)', 'IN DATE/TIME',
            'OUT WEIGHT (KG)', 'OUT DATE/TIME', 'REDUCE WEIGHT (KG)', 'NETT WEIGHT (KG)',
            ($dispatch ? 'ORDER' : 'SUPPLY') . ' WEIGHT (KG)',
            'VARIANCE (KG)', 'VARIANCE (%)', 'DRIVER NAME', 'DRIVER IC',
            'WEIGH BY', 'MODIFIED BY', 'CHECKED BY'
        ]);
    }

    private function lineData(int $count, array $row): array
    {
        $status = $row['transaction_status'];
        $dispatch = $this->isDispatchSide($status);
        $date = new \DateTime($row['transaction_date']);

        $line = [$count, $date->format('d/m/Y'), $date->format('H:i:s'), $row['transaction_id'], $dispatch ? $row['delivery_no'] : $row['purchase_order']];

        if ($this->isReceiving($status)) {
            $line[] = $row['invoice_no'];
        }

        return array_merge($line, [
            $row['product_name'],
            $row['lorry_plate_no1'],
            number_format((float)$row['gross_weight1'], 2),
            $row['gross_weight1_date'],
            number_format((float)$row['tare_weight1'], 2),
            $row['tare_weight1_date'],
            number_format((float)$row['reduce_weight'], 2),
            number_format((float)$row['final_weight'], 2),
            number_format((float)($dispatch ? $row['order_weight'] : $row['supplier_weight']), 2),
            number_format((float)$row['weight_different'], 2),
            $row['weight_different_perc'],
            $row['driver_name'],
            $this->driverIc($row['driver_name']),
            $this->userName($row['created_by']),
            $this->userName($row['modified_by']),
            $this->userName($row['approved_by'])
        ]);
    }

    private function emptyTotals(): array
    {
        return ['in' => 0.0, 'out' => 0.0, 'reduce' => 0.0, 'nett' => 0.0, 'supply' => 0.0, 'variance' => 0.0];
    }

    private function addToTotals(array &$totals, array $row): void
    {
        $totals['in'] += (float)$row['gross_weight1'];
        $totals['out'] += (float)$row['tare_weight1'];
        $totals['reduce'] += (float)$row['reduce_weight'];
        $totals['nett'] += (float)$row['final_weight'];
        $totals['supply'] += (float)($this->isDispatchSide($row['transaction_status']) ? $row['order_weight'] : $row['supplier_weight']);
        $totals['variance'] += (float)$row['weight_different'];
    }

    private function isDispatchSide(string $status): bool
    {
        return in_array($status, ['Sales', 'Dispatch', 'Misc'], true);
    }

    private function isReceiving(string $status): bool
    {
        return in_array($status, ['Receiving', 'Purchase'], true);
    }

    private function reportType(string $status): string
    {
        if ($status == 'Sales' || $status == 'Dispatch') {
            return 'DISPATCH';
        }
        if ($status == 'Receiving' || $status == 'Purchase') {
            return 'RECEIVING';
        }
        if ($status == 'Local') {
            return 'INTERNAL TRANSFER';
        }
        if ($status == 'Misc') {
            return 'MISCELLANEOUS';
        }

        return strtoupper($status);
    }

    private function slipType(string $status): string
    {
        if ($status == 'Sales' || $status == 'Dispatch') {
            return 'Dispatch';
        }
        if ($status == 'Purchase' || $status == 'Receiving') {
            return 'Receiving';
        }
        if ($status == 'Local') {
            return 'Internal Transfer';
        }

        return 'Miscellaneous';
    }

    private function partyLabel(string $status): string
    {
        return $this->isDispatchSide($status) ? 'TO CUSTOMER' : 'FROM SUPPLIER';
    }

    private function formatDate(?string $value): string
    {
        return $value ? date('d/m/Y', strtotime($value)) : '';
    }

    private function formatDateTime(?string $value): string
    {
        return $value ? date('d/m/Y - H:i:s', strtotime($value)) : '';
    }

    /**
     * Thousands separated, 2 decimals, trailing .00 dropped
     */
    private function formatWeight($weight): string
    {
        if (empty($weight)) {
            return '0';
        }

        return preg_replace('/\.00$/', '', number_format((float)$weight, 2, '.', ','));
    }

    private function companyDetail(): array
    {
        return $this->fetchOne("SELECT name, address, address2, address3, address4 FROM companies WHERE id = ?", 'i', [$this->company]) ?? [];
    }

    private function userName($id): string
    {
        if ($id === null || $id === '') {
            return '';
        }
        if (!array_key_exists($id, $this->userNames)) {
            $row = $this->fetchOne("SELECT name FROM users WHERE id = ?", 's', [(string)$id]);
            $this->userNames[$id] = $row['name'] ?? '';
        }

        return $this->userNames[$id];
    }

    private function driverIc($driverName): string
    {
        if ($driverName === null || $driverName === '') {
            return '';
        }
        if (!array_key_exists($driverName, $this->driverIcs)) {
            $row = $this->fetchOne("SELECT driver_ic FROM drivers WHERE driver_name = ? AND customer = ? AND deleted = 0", 'si', [$driverName, $this->company]);
            $this->driverIcs[$driverName] = $row['driver_ic'] ?? '';
        }

        return $this->driverIcs[$driverName];
    }
}
