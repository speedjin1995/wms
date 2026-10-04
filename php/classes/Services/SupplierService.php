<?php
namespace App\Services;

class SupplierService extends BaseService
{
    private const LIST_FROM = 'supplies LEFT JOIN supplies parent_supplier ON supplies.parent = parent_supplier.id';

    private const FORM_COLUMNS = [
        'supplier_code', 'reg_no', 'ssm', 'ic_no', 'ssm_file', 'ctos_report_no', 'supplier_name',
        'supplier_address', 'supplier_address2', 'supplier_address3', 'supplier_address4', 'states',
        'supplier_phone', 'fax', 'pic', 'parent', 'billing_name', 'billing_address', 'billing_address2',
        'billing_address3', 'billing_address4', 'billing_state', 'billing_phone', 'billing_fax',
        'billing_pic', 'currency', 'supplier_type'
    ];

    /**
     * Get paginated suppliers for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $where = "supplies.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'supplies.customer');

        $totalRecords = $this->countRows(self::LIST_FROM, $where, $types, $params);

        $this->applySearch($where, $params, $types, ['supplies.supplier_name', 'supplies.supplier_code', 'parent_supplier.supplier_name'], $search);
        $totalFiltered = $this->countRows(self::LIST_FROM, $where, $types, $params);

        $orderBy = $this->orderBy([
            'id' => 'supplies.id',
            'supplier_code' => 'supplies.supplier_code',
            'reg_no' => 'supplies.reg_no',
            'parent' => 'parent_supplier.supplier_name',
            'supplier_name' => 'supplies.supplier_name',
            'supplier_address' => 'supplies.supplier_address',
            'supplier_phone' => 'supplies.supplier_phone',
            'pic' => 'supplies.pic'
        ], $orderColumn, $orderDir, 'supplies.id');
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $rows = $this->fetchAll(
            "SELECT supplies.*, parent_supplier.supplier_name AS parent_name FROM " . self::LIST_FROM . "
             WHERE $where ORDER BY supplies.deleted, (supplies.is_manual = 'Y') DESC, $orderBy LIMIT ?, ?",
            $types, $params
        );

        $data = [];
        foreach ($rows as $row) {
            $addressParts = array_filter([
                trim((string)$row['supplier_address']),
                trim((string)$row['supplier_address2']),
                trim((string)$row['supplier_address3']),
                trim((string)$row['supplier_address4'])
            ]);

            $data[] = [
                'id' => $row['id'],
                'parent' => $row['parent_name'] ?? '',
                'supplier_code' => $row['supplier_code'],
                'reg_no' => $row['reg_no'],
                'supplier_name' => $row['supplier_name'],
                'supplier_address' => implode('<br>', $addressParts),
                'supplier_phone' => $row['supplier_phone'],
                'pic' => $row['pic'],
                'is_manual' => $row['is_manual'],
                'deleted' => $row['deleted']
            ];
        }

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Get single supplier by ID
     */
    public function getById(int $id): ?array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        return $this->fetchOne("SELECT id, " . implode(', ', self::FORM_COLUMNS) . ", customer FROM supplies WHERE $where", $types, $params);
    }

    /**
     * Create new supplier
     */
    public function create(array $data, int $company): array
    {
        $data['customer'] = $this->resolveCompany($company);
        $data['created_by'] = $this->user;

        if (!$this->insertRow('supplies', $data)) {
            return ['status' => 'failed', 'message' => 'Failed to add record'];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    /**
     * Update existing supplier
     */
    public function update(int $id, array $data): array
    {
        $data['is_manual'] = 'N';
        $data['modified_by'] = $this->user;

        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        if (!$this->updateRow('supplies', $data, $where, $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to update record'];
        }

        return ['status' => 'success', 'message' => 'Updated Successfully!!'];
    }

    public function delete(array $ids): array
    {
        return $this->softDeleteRecords('supplies', $ids);
    }

    public function reactivate(int $id): array
    {
        return $this->reactivateRecord('supplies', $id);
    }

    /**
     * Bulk insert suppliers from Excel upload
     */
    public function upload(array $rows): array
    {
        return $this->uploadRecords('supplies', 'supplier_name', 'Supplier Name', $rows, function (array $row): array {
            return [
                'parent' => !empty($row['Parent']) ? $this->findSupplierIdByName(trim($row['Parent'])) : null,
                'supplier_code' => !empty($row['SupplierCode']) ? trim($row['SupplierCode']) : '',
                'reg_no' => !empty($row['RegistrationNo']) ? trim($row['RegistrationNo']) : '',
                'supplier_name' => !empty($row['SupplierName']) ? trim($row['SupplierName']) : '',
                'supplier_address' => !empty($row['Address']) ? trim($row['Address']) : '',
                'supplier_address2' => !empty($row['Address2']) ? trim($row['Address2']) : '',
                'supplier_address3' => !empty($row['Address3']) ? trim($row['Address3']) : '',
                'supplier_address4' => !empty($row['Address4']) ? trim($row['Address4']) : '',
                'states' => !empty($row['State']) ? $this->findStateIdByName(trim($row['State'])) : null,
                'billing_name' => !empty($row['BillingName']) ? trim($row['BillingName']) : '',
                'billing_address' => !empty($row['BillingAddress']) ? trim($row['BillingAddress']) : '',
                'billing_address2' => !empty($row['BillingAddress2']) ? trim($row['BillingAddress2']) : '',
                'billing_address3' => !empty($row['BillingAddress3']) ? trim($row['BillingAddress3']) : '',
                'billing_address4' => !empty($row['BillingAddress4']) ? trim($row['BillingAddress4']) : '',
                'billing_state' => !empty($row['BillingState']) ? $this->findStateIdByName(trim($row['BillingState'])) : null,
                'supplier_phone' => !empty($row['Phone']) ? trim($row['Phone']) : '',
                'pic' => !empty($row['PIC']) ? trim($row['PIC']) : '',
                'fax' => !empty($row['Fax']) ? trim($row['Fax']) : '',
                'billing_phone' => !empty($row['BillingPhone']) ? trim($row['BillingPhone']) : '',
                'billing_pic' => !empty($row['BillingPIC']) ? trim($row['BillingPIC']) : '',
                'billing_fax' => !empty($row['BillingFax']) ? trim($row['BillingFax']) : ''
            ];
        });
    }

    /**
     * Active suppliers for the parent dropdown
     */
    public function getDropdownList(): array
    {
        $where = "deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types);

        return $this->fetchAll("SELECT id, supplier_name FROM supplies WHERE $where ORDER BY supplier_name ASC", $types, $params);
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

    private function getSsmFile(int $id): ?string
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        $row = $this->fetchOne("SELECT ssm_file FROM supplies WHERE $where", $types, $params);

        return !empty($row['ssm_file']) ? (string)$row['ssm_file'] : null;
    }

    private function findSupplierIdByName(string $name): ?int
    {
        $row = $this->fetchOne("SELECT id FROM supplies WHERE supplier_name = ? AND customer = ? AND deleted = 0", 'si', [$name, $this->company]);

        return $row ? (int)$row['id'] : null;
    }

    private function findStateIdByName(string $name): ?int
    {
        $row = $this->fetchOne("SELECT id FROM states WHERE states = ?", 's', [$name]);

        return $row ? (int)$row['id'] : null;
    }
}
