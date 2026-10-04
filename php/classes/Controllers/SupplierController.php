<?php
namespace App\Controllers;

use App\Services\EntityRunningNoService;
use App\Services\SupplierService;

class SupplierController extends BaseController
{
    private const FIELDS = [
        'code' => ['column' => 'supplier_code', 'required' => true],
        'name' => ['column' => 'supplier_name', 'required' => true],
        'regNo' => ['column' => 'reg_no', 'required' => false],
        'ssmNo' => ['column' => 'ssm', 'required' => false],
        'icNo' => ['column' => 'ic_no', 'required' => false],
        'ctosReportNo' => ['column' => 'ctos_report_no', 'required' => false],
        'address' => ['column' => 'supplier_address', 'required' => false],
        'address2' => ['column' => 'supplier_address2', 'required' => false],
        'address3' => ['column' => 'supplier_address3', 'required' => false],
        'address4' => ['column' => 'supplier_address4', 'required' => false],
        'states' => ['column' => 'states', 'required' => false],
        'phone' => ['column' => 'supplier_phone', 'required' => false],
        'fax' => ['column' => 'fax', 'required' => false],
        'email' => ['column' => 'pic', 'required' => false],
        'parent' => ['column' => 'parent', 'required' => false],
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
        'supplierType' => ['column' => 'supplier_type', 'required' => false]
    ];

    private SupplierService $supplierService;
    private EntityRunningNoService $runningNoService;

    public function __construct(SupplierService $service, EntityRunningNoService $runningNoService)
    {
        $this->supplierService = $service;
        $this->runningNoService = $runningNoService;
    }

    /**
     * DataTables server-side list
     */
    public function list(): array
    {
        $p = $this->dataTableParams();

        try {
            $result = $this->supplierService->getList($p['start'], $p['length'], $p['orderColumn'], $p['orderDir'], $p['search']);
        } catch (\Exception $e) {
            error_log('SupplierController::list - ' . $e->getMessage());
            $result = $this->emptyListResult();
        }

        return $this->dataTableResponse($p['draw'], $result);
    }

    /**
     * Get single supplier by ID
     */
    public function get(): array
    {
        $id = $this->postId();

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }

        try {
            $record = $this->supplierService->getById($id);
        } catch (\Exception $e) {
            error_log('SupplierController::get - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        if (!$record) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success', 'message' => $record];
    }

    /**
     * Create or update supplier, including SSM file upload
     */
    public function save(): array
    {
        $id = $this->postId();
        $company = $this->postId('company');

        $data = $this->collectFields(self::FIELDS);
        if ($data === null) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        if ($data['supplier_type'] === null) {
            $data['supplier_type'] = 'Normal';
        }

        $ssm = $this->supplierService->resolveSsmFile($id, $company, $_FILES['ssmFile'] ?? null, trim($_POST['ssmFilePath'] ?? ''));
        if ($ssm['status'] === 'failed') {
            return $ssm;
        }
        $data['ssm_file'] = $ssm['fid'];

        if ($id) {
            return $this->supplierService->update($id, $data);
        }

        return $this->supplierService->create($data, $company);
    }

    /**
     * Soft delete single or multiple suppliers
     */
    public function delete(): array
    {
        return $this->supplierService->delete($this->postIds());
    }

    /**
     * Reactivate soft deleted supplier
     */
    public function reactivate(): array
    {
        $id = $this->postId();

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        return $this->supplierService->reactivate($id);
    }

    /**
     * Bulk upload suppliers from JSON body
     */
    public function upload(): array
    {
        $rows = $this->jsonBody();

        if ($rows === null) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        return $this->supplierService->upload($rows);
    }

    /**
     * Active suppliers for the parent dropdown
     */
    public function dropdown(): array
    {
        try {
            return $this->supplierService->getDropdownList();
        } catch (\Exception $e) {
            error_log('SupplierController::dropdown - ' . $e->getMessage());
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
}
