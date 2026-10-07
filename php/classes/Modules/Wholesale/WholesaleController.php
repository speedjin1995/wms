<?php
namespace App\Modules\Wholesale;

use App\Core\BaseController;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class WholesaleController extends BaseController
{
    private const OTHER_VEHICLES = ['OTHERS', 'UNKNOWN', 'UNKOWN NO'];
    private const LIST_FILTERS = [
        'fromDate', 'toDate', 'transactionStatus', 'status', 'category', 'customer', 'supplier', 'vehicle', 'otherVehicle',
        'checkedBy', 'weightedBy', 'location', 'partyType', 'indicator'
    ];

    private WholesaleService $service;
    private ?WholesaleReportService $reportService;
    private ?WholesaleExportService $exportService;
    private ?WholesaleDashboardService $dashboardService;

    public function __construct(
        WholesaleService $service,
        ?WholesaleReportService $reportService = null,
        ?WholesaleExportService $exportService = null,
        ?WholesaleDashboardService $dashboardService = null
    ) {
        $this->service = $service;
        $this->reportService = $reportService;
        $this->exportService = $exportService;
        $this->dashboardService = $dashboardService;
    }

    /**
     * DataTables list
     */
    public function list(): array
    {
        $params = $this->dataTableParams('id', 'asc');
        $filters = [];
        foreach (self::LIST_FILTERS as $key) {
            $filters[$key] = trim((string)($_POST[$key] ?? ''));
        }

        $result = $this->service->getList($filters, $params['start'], $params['length'], $params['orderColumn'], $params['orderDir'], $params['search']);

        return $this->dataTableResponse($params['draw'], $result);
    }

    /**
     * Single record with weight / reject rows
     */
    public function get(): array
    {
        $id = $this->postId();
        $record = $id ? $this->service->getById($id) : null;

        if (!$record) {
            return ['status' => 'failed', 'message' => 'Data Not Found'];
        }

        return ['status' => 'success', 'message' => $record];
    }

    /**
     * Create or update a record with weight / reject rows and photos (requires allow_add / allow_edit)
     */
    public function save(): array
    {
        $id = $this->postId();
        $permissions = $this->service->getPermissions();

        if (($id && !$permissions['allowEdit']) || (!$id && !$permissions['allowAdd'])) {
            return ['status' => 'failed', 'message' => 'You do not have permission to perform this action'];
        }

        $status = trim((string)($_POST['status'] ?? ''));
        $startTime = $this->postDateTime('startTime');

        if ($status === '' || $startTime === null) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        $vehicle = $this->optionalText('vehicle');
        if ($vehicle !== null && in_array($vehicle, self::OTHER_VEHICLES, true)) {
            $vehicle = $this->optionalText('otherVehicleNo');
        }

        $driver = $this->optionalText('driver');
        if ($driver === 'OTHERS') {
            $driver = $this->optionalText('otherDriver');
        }

        $header = [
            'status' => $status,
            'start_time' => $startTime,
            'end_time' => $this->postDateTime('endTime'),
            'po_no' => $this->optionalText('doPoNo'),
            'security_bills' => $this->optionalText('securityBillNo'),
            'customer' => $this->optionalText('customer'),
            'other_customer' => $this->optionalText('customerOther'),
            'supplier' => $this->optionalText('supplier'),
            'other_supplier' => $this->optionalText('supplierOther'),
            'vehicle_no' => $vehicle,
            'driver' => $driver,
            'location' => $this->optionalText('location'),
            'remark' => $this->optionalText('remarks'),
            'remarks2' => $this->optionalText('remarks2'),
            'category' => $this->optionalText('category'),
            'payment_method' => $this->optionalText('paymentMethod'),
            'empty_baskets_weight' => $this->optionalNumber('emptyBasketWeight'),
            'basket_count' => $this->optionalNumber('basketCount', true),
            'avg_basket_weight' => $this->optionalNumber('avgBasketWeight'),
            'type' => ($_POST['productType'] ?? '') === 'Export' ? 'Export' : 'Local'
        ];

        $weights = $this->postWeights();
        $rejects = $this->postRejects();

        if ($weights === null) {
            return ['status' => 'failed', 'message' => 'Please fill in Product, Grade and Gross for all weight detail rows.'];
        }
        if ($rejects === null) {
            return ['status' => 'failed', 'message' => 'Nett weight cannot be negative. Please check your gross and tare values.'];
        }

        return $this->service->save($id, $header, $weights, $rejects);
    }

    /**
     * Soft delete with reason (requires allow_delete)
     */
    public function cancel(): array
    {
        if (!$this->service->getPermissions()['allowDelete']) {
            return ['status' => 'failed', 'message' => 'You do not have permission to perform this action'];
        }

        $id = $this->postId();
        $reason = $this->postText('cancelReason');

        if (!$id || $reason === '') {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        return $this->service->cancel($id, $reason);
    }

    /**
     * Weighing slip HTML (A4 templates / A5; mode=content returns the page body for multi-print)
     */
    public function printSlip(): array
    {
        $id = $this->postId();
        $html = $id ? $this->reportService->buildPrintHtml($id, [
            'paperSize' => (string)($_POST['paperSize'] ?? 'A4'),
            'a4Template' => (string)($_POST['a4Template'] ?? 'A4'),
            'withPhoto' => (string)($_POST['withPhoto'] ?? 'N'),
            'withDetails' => (string)($_POST['withDetails'] ?? 'N'),
            'mode' => (string)($_POST['mode'] ?? 'full')
        ]) : null;

        if ($html === null) {
            return ['status' => 'failed', 'message' => 'Data Not Found'];
        }

        return ['status' => 'success', 'message' => $html];
    }

    /**
     * Invoice HTML (requires the invoice feature and price permission)
     */
    public function printInvoice(): array
    {
        if (!$this->service->getFeatureFlags()['invoice'] || !$this->service->getPermissions()['allowPrice']) {
            return ['status' => 'failed', 'message' => 'You do not have permission to perform this action'];
        }

        $id = $this->postId();
        $html = $id ? $this->reportService->buildInvoiceHtml($id) : null;

        if ($html === null) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success', 'message' => $html];
    }

    /**
     * Stream one record's weighing details as an Excel download
     */
    public function exportExcel(): void
    {
        $flags = $this->service->getFeatureFlags();
        $export = $this->reportService->buildExcel($this->postId(), [
            'photo' => $flags['photo'],
            'price' => $flags['price'] && $this->service->getPermissions()['allowPrice'],
            'pcsBasket' => $flags['pcsBasket']
        ]);

        if (!$export) {
            http_response_code(404);
            exit('Record not found');
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $export['fileName'] . '"');
        header('Cache-Control: max-age=0');

        (new Xlsx($export['spreadsheet']))->save('php://output');
    }

    /**
     * Stream the report page summary as an Excel download (GET filters, or isMulti=Y with ids)
     */
    public function exportReport(): void
    {
        $filters = $this->reportFilters();
        $export = $this->exportService->buildReportExcel(
            $this->service->getReportRecords($filters, $this->selectedIds()),
            $filters,
            $this->service->getPermissions()['allowPrice']
        );

        $this->streamExcel($export);
    }

    /**
     * Stream the report page summary / invoice listing PDF (reportType = summary / invoice)
     */
    public function exportReportPdf(): void
    {
        $filters = $this->reportFilters();
        $export = $this->exportService->buildReportPdf(
            $this->service->getReportRecords($filters, $this->selectedIds()),
            $filters,
            (string)($_GET['reportType'] ?? 'summary'),
            $this->service->getPermissions()['allowPrice']
        );

        $export['pdf']->Output($export['fileName'], 'D');
    }

    /**
     * Stream the integration Excel of the chosen config (configId); dates filter on created_datetime
     */
    public function exportIntegration(): void
    {
        $config = $this->exportService->getIntegrationConfig((int)($_GET['configId'] ?? 0));
        if (!$config) {
            http_response_code(404);
            exit('Integration config not found.');
        }

        $records = $this->service->getReportRecords($this->reportFilters(), $this->selectedIds(), 'created_datetime');
        $this->streamExcel($this->exportService->buildIntegrationExcel($config, $records));
    }

    /**
     * Show the stock balance PDF inline (asAtDate, category, location, product, type)
     */
    public function exportStockBalance(): void
    {
        $filters = [];
        foreach (['asAtDate', 'category', 'location', 'product', 'type'] as $key) {
            $filters[$key] = trim((string)($_GET[$key] ?? ''));
        }

        $export = $this->exportService->buildStockBalancePdf($this->service->getStockBalanceRecords($filters), $filters);
        $export['pdf']->Output($export['fileName'], 'I');
    }

    /**
     * Wholesales dashboard tab data (POST filters)
     */
    public function dashboard(): array
    {
        $filters = [];
        foreach (['fromDate', 'toDate', 'status', 'customer', 'supplier', 'location', 'partyType', 'category'] as $key) {
            $filters[$key] = trim((string)($_POST[$key] ?? ''));
        }

        return ['status' => 'success', 'message' => $this->dashboardService->getSummary($filters)];
    }

    /**
     * Stream a dashboard breakdown Excel (type = customer / supplier / customer_individual / supplier_individual / grade)
     */
    public function exportDashboard(): void
    {
        $filters = [];
        foreach (['fromDate', 'toDate', 'customer', 'supplier', 'location', 'partyType', 'status'] as $key) {
            $filters[$key] = trim((string)($_GET[$key] ?? ''));
        }

        $export = $this->dashboardService->buildExport((string)($_GET['type'] ?? ''), $filters);
        if (!$export) {
            http_response_code(400);
            exit('Invalid export type');
        }

        $this->streamExcel($export);
    }

    /**
     * Report filters from GET (list filters plus product)
     */
    private function reportFilters(): array
    {
        $filters = [];
        foreach (array_merge(self::LIST_FILTERS, ['product']) as $key) {
            $filters[$key] = trim((string)($_GET[$key] ?? ''));
        }

        return $filters;
    }

    /**
     * Ticked record IDs (isMulti=Y with comma separated ids), empty when exporting by filters
     */
    private function selectedIds(): array
    {
        return ($_GET['isMulti'] ?? '') === 'Y' ? explode(',', (string)($_GET['ids'] ?? '')) : [];
    }

    private function streamExcel(array $export): void
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $export['fileName'] . '"');
        header('Cache-Control: max-age=0');

        (new Xlsx($export['spreadsheet']))->save('php://output');
    }

    /**
     * Numeric POST value as a string, null when empty / not numeric
     */
    private function optionalNumber(string $key, bool $integer = false): ?string
    {
        $value = trim((string)($_POST[$key] ?? ''));
        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return $integer ? (string)intval($value) : (string)floatval($value);
    }

    /**
     * Weight rows in the stored JSON shape. Null when a row has no product, or (wholesales) no grade / gross or a negative net.
     */
    private function postWeights(): ?array
    {
        $isIndustrial = $this->service->getRecordType() === 'industrial';
        $rows = $this->postRows('weightDetails');
        $items = [];

        foreach ($rows as $index => $row) {
            $text = $this->rowText($row);
            if ($text('product') === '') {
                return null;
            }
            // Industrial rows have no grade and net = |gross - tare|
            if (!$isIndustrial && ($text('grade_id') === '' || floatval($text('gross')) <= 0 || floatval($text('net')) < 0)) {
                return null;
            }

            $items[] = [
                'gross' => $text('gross'),
                'tare' => $text('tare'),
                'pretare' => $text('pretare', '0.0'),
                'net' => $text('net'),
                'variance' => $isIndustrial ? $text('variance') : '',
                'varPerc' => $isIndustrial ? $text('variancePerc') : '',
                'reject' => $text('reject'),
                'isRejected' => $text('isRejected', 'N'),
                'product' => $text('product'),
                'product_name' => $text('product_name'),
                'product_desc' => $text('product_desc'),
                'price' => $text('price'),
                'unit' => $text('unit'),
                'package' => $text('package'),
                'total' => $text('total'),
                'before_discount' => $text('before_discount'),
                'discount' => $text('discount', '0'),
                'discount_type' => $text('discount_type') === 'percent' ? 'percent' : 'fixed',
                'fixedfloat' => $text('fixedfloat'),
                'time' => $text('time'),
                'grade' => $text('grade'),
                'grade_id' => $text('grade_id'),
                'currency' => $text('currency'),
                'no_per_basket' => $text('no_basket'),
                'isedit' => $text('isedit', 'N'),
                'photoPath' => $text('photoPath'),
                'photoFile' => $this->uploadedFile('photoFiles', $index)
            ];
        }

        return $items;
    }

    /**
     * Reject rows in the stored JSON shape. Null when a row has a negative net.
     */
    private function postRejects(): ?array
    {
        $rows = $this->postRows('rejectDetails');
        $items = [];

        foreach ($rows as $index => $row) {
            $text = $this->rowText($row);
            if (floatval($text('net')) < 0) {
                return null;
            }

            $items[] = [
                'gross' => $text('gross'),
                'tare' => $text('tare'),
                'pretare' => $text('pretare', '0.0'),
                'net' => $text('net'),
                'reject' => $text('reject'),
                'isRejected' => $text('isRejected', 'N'),
                'product' => $text('product'),
                'product_name' => $text('product_name'),
                'product_desc' => $text('product_desc'),
                'price' => $text('price'),
                'unit' => $text('unit'),
                'package' => $text('package'),
                'total' => $text('total'),
                'before_discount' => $text('before_discount'),
                'discount' => $text('discount', '0'),
                'discount_type' => $text('discount_type') === 'percent' ? 'percent' : 'fixed',
                'fixedfloat' => $text('fixedfloat'),
                'time' => $text('time'),
                'grade' => $text('grade'),
                'currency' => $text('currency'),
                'isedit' => $text('isedit', 'N'),
                'photoPath' => $text('photoPath'),
                'photoFile' => $this->uploadedFile('rejectPhotoFiles', $index)
            ];
        }

        return $items;
    }

    private function postRows(string $key): array
    {
        $rows = $_POST[$key] ?? [];

        return is_array($rows) ? array_filter($rows, 'is_array') : [];
    }

    /**
     * Reader for one posted row: trimmed text with tags stripped, $default when missing
     */
    private function rowText(array $row): callable
    {
        return function (string $key, string $default = '') use ($row): string {
            return isset($row[$key]) && !is_array($row[$key]) ? strip_tags(trim((string)$row[$key])) : $default;
        };
    }
}
