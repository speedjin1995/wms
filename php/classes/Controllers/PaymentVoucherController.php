<?php
namespace App\Controllers;

use App\Services\PaymentVoucherReportService;
use App\Services\PaymentVoucherService;

class PaymentVoucherController extends BaseController
{
    private PaymentVoucherService $service;
    private ?PaymentVoucherReportService $reportService;
    /** @var callable */
    private $translate;

    public function __construct(PaymentVoucherService $service, ?PaymentVoucherReportService $reportService = null, ?callable $translate = null)
    {
        $this->service = $service;
        $this->reportService = $reportService;
        $this->translate = $translate ?? function ($key, $default = '') {
            return $default;
        };
    }

    /**
     * DataTables server-side list
     */
    public function list(): array
    {
        $p = $this->dataTableParams('voucher_date', 'desc');

        try {
            $result = $this->service->getList($this->filters($_POST), $p['start'], $p['length'], $p['orderColumn'], $p['orderDir'], $p['search']);
        } catch (\Exception $e) {
            error_log('PaymentVoucherController::list - ' . $e->getMessage());
            $result = $this->emptyListResult();
        }

        return $this->dataTableResponse($p['draw'], $result);
    }

    /**
     * Weighings (and voucher header when editing) for the voucher form
     */
    public function items(): array
    {
        $parentId = $this->postId('parentId');
        $pvId = $this->postId('pvId');

        if (!$parentId && !$pvId) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }

        try {
            $result = $this->service->getItems($parentId, $pvId, (string)($_POST['transactionStatus'] ?? ''), (string)($_POST['fromDate'] ?? ''), (string)($_POST['toDate'] ?? ''));
        } catch (\Exception $e) {
            error_log('PaymentVoucherController::items - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        if ($result === null) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success'] + $result;
    }

    /**
     * Create or update a voucher (requires allow_edit, same as the form's entry button)
     */
    public function save(): array
    {
        if (!$this->service->getPermissions()['allowEdit']) {
            return ['status' => 'failed', 'message' => 'You do not have permission to perform this action'];
        }

        $pvId = $this->postId('pvId');
        $entityId = $this->postId('entityId');
        $voucherDate = \DateTime::createFromFormat('d/m/Y', trim((string)($_POST['voucherDate'] ?? '')));
        $unitPrice = trim((string)($_POST['unitPrice'] ?? '0'));
        $tax = trim((string)($_POST['tax'] ?? '0'));

        if ((!$pvId && !$entityId) || !$voucherDate || !is_numeric($unitPrice) || !is_numeric($tax ?: '0')) {
            return ['status' => 'failed', 'message' => 'Please fill in all required fields'];
        }

        $prices = [];
        foreach ((array)($_POST['wholesales'] ?? []) as $row) {
            $id = (int)($row['id'] ?? 0);
            $price = trim((string)($row['pv_unit_price'] ?? ''));
            if (!$id || !is_numeric($price) || (float)$price < 0) {
                return ['status' => 'failed', 'message' => 'Please enter a valid unit price for every row'];
            }
            $prices[$id] = (float)$price;
        }

        $invoiceNo = $this->postText('invoiceNo');
        $header = [
            'voucher_date' => $voucherDate->format('Y-m-d'),
            'invoice_no' => $invoiceNo === '' ? null : mb_substr($invoiceNo, 0, 50),
            'unit_price' => (float)$unitPrice,
            'tax' => (float)($tax ?: 0)
        ];

        return $this->service->save($pvId, $entityId, $this->service->resolveStatus((string)($_POST['transactionStatus'] ?? '')), $header, $prices);
    }

    /**
     * Cancel voucher with reason (requires allow_delete)
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
     * Voucher slip / statement print HTML
     */
    public function printSlip(): array
    {
        $pvId = $this->postId('pvId');
        $data = $pvId ? $this->service->getPrintData($pvId) : null;

        if (!$data) {
            return ['status' => 'failed', 'message' => 'Payment voucher not found'];
        }

        return [
            'status' => 'success',
            'message' => $this->reportService->buildPrintHtml($data, (string)($_POST['slipType'] ?? 'pv'), $this->translate),
            'paymentVoucherNo' => $data['pv']['voucher_no']
        ];
    }

    /**
     * PV report print HTML for the current filters
     */
    public function exportReport(): array
    {
        $filters = $this->filters($_POST);
        $data = $this->service->getReportData($filters);

        return [
            'status' => 'success',
            'message' => $this->reportService->buildReportHtml($data, $filters['fromDate'], $filters['toDate'], $this->translate)
        ];
    }

    /**
     * List / report filter values from a request array
     */
    public function filters(array $source): array
    {
        $filters = [];
        foreach (['fromDate', 'toDate', 'transactionStatus', 'parentCustomerId', 'parentSupplierId'] as $key) {
            $filters[$key] = (string)($source[$key] ?? '');
        }

        return $filters;
    }
}
