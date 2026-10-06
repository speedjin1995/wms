<?php
namespace App\Modules\Customer;

use App\Core\BaseService;

class CustomerService extends BaseService
{
    private const LIST_FROM = 'customers LEFT JOIN customers parent_customer ON customers.parent = parent_customer.id';

    private const FORM_COLUMNS = [
        'customer_code', 'reg_no', 'ssm', 'ic_no', 'ssm_file', 'ctos_report_no', 'customer_name',
        'customer_address', 'customer_address2', 'customer_address3', 'customer_address4', 'states',
        'billing_name', 'billing_address', 'billing_address2', 'billing_address3', 'billing_address4',
        'billing_state', 'billing_phone', 'billing_fax', 'billing_pic', 'currency', 'customer_phone',
        'pic', 'fax', 'parent', 'customer_type'
    ];

    /**
     * Get paginated customers for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $where = "customers.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'customers.customer');

        $totalRecords = $this->countRows(self::LIST_FROM, $where, $types, $params);

        $this->applySearch($where, $params, $types, ['customers.customer_name', 'customers.customer_code', 'parent_customer.customer_name'], $search);
        $totalFiltered = $this->countRows(self::LIST_FROM, $where, $types, $params);

        $orderBy = $this->orderBy([
            'id' => 'customers.id',
            'customer_code' => 'customers.customer_code',
            'reg_no' => 'customers.reg_no',
            'parent' => 'parent_customer.customer_name',
            'customer_name' => 'customers.customer_name',
            'customer_address' => 'customers.customer_address',
            'customer_phone' => 'customers.customer_phone',
            'pic' => 'customers.pic'
        ], $orderColumn, $orderDir, 'customers.id');
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $rows = $this->fetchAll(
            "SELECT customers.*, parent_customer.customer_name AS parent_name FROM " . self::LIST_FROM . "
             WHERE $where ORDER BY customers.deleted, (customers.is_manual = 'Y') DESC, $orderBy LIMIT ?, ?",
            $types, $params
        );

        $data = [];
        foreach ($rows as $row) {
            $addressParts = array_filter([
                trim((string)$row['customer_address']),
                trim((string)$row['customer_address2']),
                trim((string)$row['customer_address3']),
                trim((string)$row['customer_address4'])
            ]);

            $data[] = [
                'id' => $row['id'],
                'parent' => $row['parent_name'] ?? '',
                'customer_code' => $row['customer_code'],
                'reg_no' => $row['reg_no'],
                'customer_name' => $row['customer_name'],
                'customer_address' => implode('<br>', $addressParts),
                'customer_phone' => $row['customer_phone'],
                'pic' => $row['pic'],
                'pending_bins' => $row['pending_bins'],
                'is_manual' => $row['is_manual'],
                'deleted' => $row['deleted']
            ];
        }

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Get single customer by ID
     */
    public function getById(int $id): ?array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        return $this->fetchOne("SELECT id, " . implode(', ', self::FORM_COLUMNS) . ", customer FROM customers WHERE $where", $types, $params);
    }

    /**
     * Create new customer
     */
    public function create(array $data, int $company): array
    {
        $data['customer'] = $this->resolveCompany($company);
        $data['created_by'] = $this->user;

        if (!$this->insertRow('customers', $data)) {
            return ['status' => 'failed', 'message' => 'Failed to add record'];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    /**
     * Update existing customer
     */
    public function update(int $id, array $data): array
    {
        $data['is_manual'] = 'N';
        $data['modified_by'] = $this->user;

        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        if (!$this->updateRow('customers', $data, $where, $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to update record'];
        }

        return ['status' => 'success', 'message' => 'Updated Successfully!!'];
    }

    public function delete(array $ids): array
    {
        return $this->softDeleteRecords('customers', $ids);
    }

    public function reactivate(int $id): array
    {
        return $this->reactivateRecord('customers', $id);
    }

    /**
     * Bulk insert customers from Excel upload
     */
    public function upload(array $rows): array
    {
        return $this->uploadRecords('customers', 'customer_name', 'Customer Name', $rows, function (array $row): array {
            return [
                'parent' => !empty($row['Parent']) ? searchCustomerIdByName(trim($row['Parent']), $this->company, $this->db) : null,
                'customer_code' => !empty($row['CustomerCode']) ? trim($row['CustomerCode']) : '',
                'reg_no' => !empty($row['RegistrationNo']) ? trim($row['RegistrationNo']) : '',
                'customer_name' => !empty($row['CustomerName']) ? trim($row['CustomerName']) : '',
                'customer_address' => !empty($row['Address']) ? trim($row['Address']) : '',
                'customer_address2' => !empty($row['Address2']) ? trim($row['Address2']) : '',
                'customer_address3' => !empty($row['Address3']) ? trim($row['Address3']) : '',
                'customer_address4' => !empty($row['Address4']) ? trim($row['Address4']) : '',
                'states' => !empty($row['State']) ? searchStateIdByName(trim($row['State']), $this->company, $this->db) : null,
                'billing_name' => !empty($row['BillingName']) ? trim($row['BillingName']) : '',
                'billing_address' => !empty($row['BillingAddress']) ? trim($row['BillingAddress']) : '',
                'billing_address2' => !empty($row['BillingAddress2']) ? trim($row['BillingAddress2']) : '',
                'billing_address3' => !empty($row['BillingAddress3']) ? trim($row['BillingAddress3']) : '',
                'billing_address4' => !empty($row['BillingAddress4']) ? trim($row['BillingAddress4']) : '',
                'billing_state' => !empty($row['BillingState']) ? searchStateIdByName(trim($row['BillingState']), $this->company, $this->db) : null,
                'billing_phone' => !empty($row['BillingPhone']) ? trim($row['BillingPhone']) : '',
                'billing_pic' => !empty($row['BillingPIC']) ? trim($row['BillingPIC']) : '',
                'billing_fax' => !empty($row['BillingFax']) ? trim($row['BillingFax']) : '',
                'customer_phone' => !empty($row['Phone']) ? trim($row['Phone']) : '',
                'pic' => !empty($row['PIC']) ? trim($row['PIC']) : '',
                'fax' => !empty($row['Fax']) ? trim($row['Fax']) : ''
            ];
        });
    }

    /**
     * Active customers for the parent dropdown
     */
    public function getDropdownList(): array
    {
        $where = "deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types);

        return $this->fetchAll("SELECT id, customer_name FROM customers WHERE $where ORDER BY customer_name ASC", $types, $params);
    }

    /**
     * Resolve the SSM file ID to save: upload a new file (replacing the old one),
     * or keep the existing file when it still belongs to this record.
     */
    public function resolveSsmFile(int $id, int $company, ?array $file, string $existingFile): array
    {
        $currentFile = $id ? $this->getSsmFile($id) : null;

        if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $result = uploadFile($file, 'ssm', $this->resolveCompany($company), $this->db);
            if ($result['status'] === 'failed') {
                return ['status' => 'failed', 'message' => $result['message']];
            }

            // Delete old SSM file on update
            if ($currentFile) {
                deleteOldFile($currentFile, $this->db);
            }

            return ['status' => 'success', 'fid' => $result['fid']];
        }

        if ($existingFile !== '' && $currentFile !== null && $existingFile === $currentFile) {
            return ['status' => 'success', 'fid' => $currentFile];
        }

        return ['status' => 'success', 'fid' => null];
    }

    /**
     * Pending bin count for a customer and bin type
     */
    public function getBinPending(int $customerId, int $binTypeId): ?int
    {
        $customer = $this->findBinCustomer($customerId, false);
        if ($customer === null) {
            return null;
        }

        $pending = $this->decodePendingBins($customer['pending_bins']);

        return isset($pending[$binTypeId]) ? (int)$pending[$binTypeId] : 0;
    }

    /**
     * Bin IN/OUT history for a customer and bin type
     */
    public function getBinHistory(int $customerId, int $binTypeId, int $start, int $length, string $orderDir): array
    {
        if ($this->findBinCustomer($customerId, false) === null) {
            return ['totalRecords' => 0, 'data' => []];
        }

        $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';

        $totalRecords = $this->countRows('customer_bin_logs', 'customer_id = ? AND bin_type = ?', 'ii', [$customerId, $binTypeId]);

        $rows = $this->fetchAll(
            "SELECT l.id, l.type, l.qty, l.remark, u.name AS user_name, l.created_at
             FROM customer_bin_logs l
             LEFT JOIN users u ON u.id = l.created_by
             WHERE l.customer_id = ? AND l.bin_type = ?
             ORDER BY l.created_at $orderDir
             LIMIT ?, ?",
            'iiii', [$customerId, $binTypeId, $start, $length]
        );

        return ['totalRecords' => $totalRecords, 'data' => $rows];
    }

    /**
     * Record bins taken OUT by / returned IN from a customer
     */
    public function updateBin(int $customerId, int $binTypeId, string $action, int $qty, string $remark): array
    {
        $this->db->begin_transaction();

        try {
            $customer = $this->findBinCustomer($customerId, true);
            if ($customer === null) {
                $this->db->rollback();
                return ['status' => 'failed', 'message' => 'Invalid input'];
            }

            // pending_bins is stored as JSON e.g. {"1": 5, "2": 3} (bin_type_id => count)
            $pendingMap = $this->decodePendingBins($customer['pending_bins']);
            $currentCount = isset($pendingMap[$binTypeId]) ? (int)$pendingMap[$binTypeId] : 0;

            // Customer takes bins out - pending count goes up; returns bins in - goes down
            $newCount = $action === 'OUT' ? $currentCount + $qty : $currentCount - $qty;

            if ($newCount < 0) {
                $this->db->rollback();
                return ['status' => 'failed', 'message' => 'Pending bins cannot go below 0'];
            }

            $pendingMap[$binTypeId] = $newCount;

            if (!$this->executeWrite("UPDATE customers SET pending_bins = ? WHERE id = ?", 'si', [json_encode($pendingMap), $customerId])) {
                throw new \Exception('Failed to update pending bins');
            }

            if (!$this->executeWrite(
                "INSERT INTO customer_bin_logs (customer_id, type, qty, remark, bin_type, created_by) VALUES (?, ?, ?, ?, ?, ?)",
                'isisii', [$customerId, $action, $qty, $remark, $binTypeId, $this->user]
            )) {
                throw new \Exception('Failed to insert bin log');
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('CustomerService::updateBin - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to update bins'];
        }

        return ['status' => 'success', 'message' => 'Updated successfully', 'pending_bins' => $newCount];
    }

    private function getSsmFile(int $id): ?string
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        $row = $this->fetchOne("SELECT ssm_file FROM customers WHERE $where", $types, $params);

        return !empty($row['ssm_file']) ? (string)$row['ssm_file'] : null;
    }

    private function findBinCustomer(int $customerId, bool $forUpdate): ?array
    {
        $where = "id = ?";
        $params = [$customerId];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        return $this->fetchOne("SELECT id, pending_bins FROM customers WHERE $where" . ($forUpdate ? ' FOR UPDATE' : ''), $types, $params);
    }

    private function decodePendingBins(?string $pendingBins): array
    {
        if (empty($pendingBins)) {
            return [];
        }

        $decoded = json_decode($pendingBins, true);

        return is_array($decoded) ? $decoded : [];
    }
}
