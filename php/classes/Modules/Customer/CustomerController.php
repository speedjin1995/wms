<?php
namespace App\Modules\Customer;

use App\Core\BaseController;
use App\Shared\EntityRunningNoService;

class CustomerController extends BaseController
{
    private const FIELDS = [
        'code' => ['column' => 'customer_code', 'required' => true],
        'name' => ['column' => 'customer_name', 'required' => true],
        'regNo' => ['column' => 'reg_no', 'required' => false],
        'ssmNo' => ['column' => 'ssm', 'required' => false],
        'icNo' => ['column' => 'ic_no', 'required' => false],
        'ctosReportNo' => ['column' => 'ctos_report_no', 'required' => false],
        'address' => ['column' => 'customer_address', 'required' => false],
        'address2' => ['column' => 'customer_address2', 'required' => false],
        'address3' => ['column' => 'customer_address3', 'required' => false],
        'address4' => ['column' => 'customer_address4', 'required' => false],
        'states' => ['column' => 'states', 'required' => false],
        'billingName' => ['column' => 'billing_name', 'required' => false],
        'billingAddress' => ['column' => 'billing_address', 'required' => false],
        'billingAddress2' => ['column' => 'billing_address2', 'required' => false],
        'billingAddress3' => ['column' => 'billing_address3', 'required' => false],
        'billingAddress4' => ['column' => 'billing_address4', 'required' => false],
        'billingStates' => ['column' => 'billing_state', 'required' => false],
        'billingPhone' => ['column' => 'billing_phone', 'required' => false],
        'billingFax' => ['column' => 'billing_fax', 'required' => false],
        'billingPic' => ['column' => 'billing_pic', 'required' => false],
        'currency' => ['column' => 'currency', 'required' => false],
        'phone' => ['column' => 'customer_phone', 'required' => false],
        'email' => ['column' => 'pic', 'required' => false],
        'fax' => ['column' => 'fax', 'required' => false],
        'parent' => ['column' => 'parent', 'required' => false],
        'customerType' => ['column' => 'customer_type', 'required' => false]
    ];

    private CustomerService $customerService;
    private EntityRunningNoService $runningNoService;

    public function __construct(CustomerService $service, EntityRunningNoService $runningNoService)
    {
        $this->customerService = $service;
        $this->runningNoService = $runningNoService;
    }

    /**
     * DataTables server-side list
     */
    public function list(): array
    {
        $p = $this->dataTableParams();

        try {
            $result = $this->customerService->getList($p['start'], $p['length'], $p['orderColumn'], $p['orderDir'], $p['search']);
        } catch (\Exception $e) {
            error_log('CustomerController::list - ' . $e->getMessage());
            $result = $this->emptyListResult();
        }

        return $this->dataTableResponse($p['draw'], $result);
    }

    /**
     * Get single customer by ID
     */
    public function get(): array
    {
        $id = $this->postId();

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }

        try {
            $record = $this->customerService->getById($id);
        } catch (\Exception $e) {
            error_log('CustomerController::get - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        if (!$record) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success', 'message' => $record];
    }

    /**
     * Create or update customer, including SSM file upload
     */
    public function save(): array
    {
        $id = $this->postId();
        $company = $this->postId('company');

        $data = $this->collectFields(self::FIELDS);
        if ($data === null) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        if ($data['customer_type'] === null) {
            $data['customer_type'] = 'Normal';
        }

        $ssm = $this->customerService->resolveSsmFile($id, $company, $_FILES['ssmFile'] ?? null, trim($_POST['ssmFilePath'] ?? ''));
        if ($ssm['status'] === 'failed') {
            return $ssm;
        }
        $data['ssm_file'] = $ssm['fid'];

        if ($id) {
            return $this->customerService->update($id, $data);
        }

        return $this->customerService->create($data, $company);
    }

    /**
     * Soft delete single or multiple customers
     */
    public function delete(): array
    {
        return $this->customerService->delete($this->postIds());
    }

    /**
     * Reactivate soft deleted customer
     */
    public function reactivate(): array
    {
        $id = $this->postId();

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        return $this->customerService->reactivate($id);
    }

    /**
     * Bulk upload customers from JSON body
     */
    public function upload(): array
    {
        $rows = $this->jsonBody();

        if ($rows === null) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        return $this->customerService->upload($rows);
    }

    /**
     * Active customers for the parent dropdown
     */
    public function dropdown(): array
    {
        try {
            return $this->customerService->getDropdownList();
        } catch (\Exception $e) {
            error_log('CustomerController::dropdown - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Running number prefixes and invoice code
     */
    public function getRunningNo(): array
    {
        $entityId = $this->postId('entity_id');

        if (!$entityId) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }

        return $this->runningNoService->get($entityId);
    }

    /**
     * Save running number prefixes and invoice code
     */
    public function saveRunningNo(): array
    {
        $entityId = $this->postId('entity_id');
        $invoiceCode = trim($_POST['invoice_code'] ?? '');
        $rows = $_POST['rows'] ?? [];

        if (!$entityId || !is_array($rows)) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        return $this->runningNoService->save($entityId, $invoiceCode, $rows);
    }

    /**
     * Pending bin count for a customer and bin type
     */
    public function binPending(): array
    {
        $customerId = $this->postId('customer_id');
        $binTypeId = $this->postId('bin_type_id');

        if (!$customerId || !$binTypeId) {
            return ['status' => 'failed', 'message' => 'Missing parameters'];
        }

        $count = $this->customerService->getBinPending($customerId, $binTypeId);
        if ($count === null) {
            return ['status' => 'failed', 'message' => 'Customer not found'];
        }

        return ['status' => 'success', 'pending_bins' => $count];
    }

    /**
     * Bin IN/OUT history (DataTables format)
     */
    public function binHistory(): array
    {
        $customerId = $this->postId('customer_id');
        $binTypeId = $this->postId('bin_type_id');

        if (!$customerId || !$binTypeId) {
            return ['status' => 'failed', 'message' => 'Missing parameters'];
        }

        $p = $this->dataTableParams('created_at', 'desc');
        $result = $this->customerService->getBinHistory($customerId, $binTypeId, $p['start'], $p['length'], $p['orderDir']);

        return $this->dataTableResponse($p['draw'], $result);
    }

    /**
     * Record bins OUT / IN for a customer
     */
    public function updateBin(): array
    {
        $customerId = $this->postId('binCustomerId');
        $binTypeId = $this->postId('binTypeId');
        $action = trim($_POST['binAction'] ?? '');
        $qty = (int)($_POST['binQty'] ?? 0);
        $remark = trim($_POST['binRemark'] ?? '');

        if (!$customerId || !$binTypeId || !in_array($action, ['IN', 'OUT'], true) || $qty <= 0) {
            return ['status' => 'failed', 'message' => 'Invalid input'];
        }

        return $this->customerService->updateBin($customerId, $binTypeId, $action, $qty, $remark);
    }
}
