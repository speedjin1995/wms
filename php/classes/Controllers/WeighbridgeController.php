<?php
namespace App\Controllers;

use App\Services\WeighbridgeReportService;
use App\Services\WeighbridgeService;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class WeighbridgeController extends BaseController
{
    private WeighbridgeService $service;
    private ?WeighbridgeReportService $reportService;

    public function __construct(WeighbridgeService $service, ?WeighbridgeReportService $reportService = null)
    {
        $this->service = $service;
        $this->reportService = $reportService;
    }

    /**
     * DataTables server-side list
     */
    public function list(): array
    {
        $p = $this->dataTableParams('transaction_id');

        try {
            $result = $this->service->getList($this->filters($_POST), $p['start'], $p['length'], $p['orderColumn'], $p['orderDir'], $p['search']);
        } catch (\Exception $e) {
            error_log('WeighbridgeController::list - ' . $e->getMessage());
            $result = $this->emptyListResult();
        }

        return $this->dataTableResponse($p['draw'], $result);
    }

    /**
     * Get single record by ID
     */
    public function get(): array
    {
        $id = $this->postId();

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }

        try {
            $record = $this->service->getById($id);
        } catch (\Exception $e) {
            error_log('WeighbridgeController::get - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        if (!$record) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success', 'message' => $record];
    }

    /**
     * Create or update record (requires allow_add / allow_edit)
     */
    public function save(): array
    {
        $id = $this->postId();
        $permissions = $this->service->getPermissions();

        if (($id && !$permissions['allowEdit']) || (!$id && !$permissions['allowAdd'])) {
            return ['status' => 'failed', 'message' => 'You do not have permission to perform this action'];
        }

        $transactionStatus = trim((string)($_POST['transactionStatus'] ?? ''));
        $transactionDate = $this->postDateTime('transactionDate');
        $gross = $this->postWeight('grossIncoming');
        $tare = $this->postWeight('tareOutgoing');

        if (!in_array($transactionStatus, WeighbridgeService::TRANSACTION_STATUSES, true) || $transactionDate === null || $gross === null) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        if ($gross === false || $tare === false) {
            return ['status' => 'failed', 'message' => 'Invalid weight'];
        }

        $data = [
            'transaction_status' => $transactionStatus,
            'transaction_date' => $transactionDate,
            'purchase_order' => $this->postOptional('poNo'),
            'delivery_no' => $this->postOptional('doNo'),
            'customer_name' => $this->postOptional('customer'),
            'customer_code' => $this->postOptional('customerCode'),
            'supplier_name' => $this->postOptional('supplier'),
            'supplier_code' => $this->postOptional('supplierCode'),
            'product_name' => $this->postOptional('product'),
            'product_code' => $this->postOptional('productCode'),
            'lorry_plate_no1' => $this->postOptional('vehicle'),
            'gross_weight1' => $gross,
            'gross_weight1_date' => $this->postDateTime('grossIncomingDate'),
            'tare_weight1' => $tare,
            'tare_weight1_date' => $tare === null ? null : $this->postDateTime('tareOutgoingDate')
        ];

        return $this->service->save($id, $data);
    }

    /**
     * Cancel record with reason (requires allow_delete)
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
     * Weighing slip HTML for printing
     */
    public function printSlip(): array
    {
        $id = $this->postId();
        $row = $id ? $this->service->getSlipRow($id) : null;

        if (!$row) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success', 'message' => $this->reportService->buildSlipHtml($row)];
    }

    /**
     * Stream the grouped report as an Excel download (GET filters, or isMulti=Y with ids)
     */
    public function exportExcel(): void
    {
        $spreadsheet = $this->reportService->buildExcel($this->reportRows());

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="WB_Report_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');

        (new Xlsx($spreadsheet))->save('php://output');
    }

    /**
     * Stream the grouped report as a PDF download (GET filters, or isMulti=Y with ids)
     */
    public function exportPdf(): void
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'tempDir' => sys_get_temp_dir(),
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 5
        ]);
        $mpdf->WriteHTML($this->reportService->buildPdfHtml($this->reportRows()));
        $mpdf->Output('WB_Report_' . date('Y-m-d') . '.pdf', 'D');
    }

    /**
     * Report rows for the export request
     */
    private function reportRows(): array
    {
        $ids = (($_GET['isMulti'] ?? '') === 'Y') ? explode(',', (string)($_GET['ids'] ?? '')) : [];

        return $this->service->getReportRows($this->filters($_GET), $ids);
    }

    /**
     * List / report filter values from a request array
     */
    public function filters(array $source): array
    {
        $keys = ['fromDate', 'toDate', 'transactionStatus', 'product', 'customer', 'supplier', 'vehicle', 'status', 'transactionId'];
        $filters = [];
        foreach ($keys as $key) {
            $filters[$key] = (string)($source[$key] ?? '');
        }

        return $filters;
    }

    /**
     * Trimmed POST value, null when empty
     */
    private function postOptional(string $key): ?string
    {
        $value = trim((string)($_POST[$key] ?? ''));

        return $value === '' ? null : $value;
    }

    /**
     * Numeric weight: null when empty, false when not a non-negative number
     * @return string|null|false
     */
    private function postWeight(string $key)
    {
        $value = trim((string)($_POST[$key] ?? ''));

        if ($value === '') {
            return null;
        }

        return (is_numeric($value) && (float)$value >= 0) ? $value : false;
    }
}
