<?php
namespace App\Services;

class ShipmentTypeService extends BaseService
{
    /**
     * Get paginated shipment types for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $where = "shipment_types.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'shipment_types.customer');

        $totalRecords = $this->countRows('shipment_types', $where, $types, $params);

        $this->applySearch($where, $params, $types, ['shipment_types.shipment_type'], $search);
        $totalFiltered = $this->countRows('shipment_types', $where, $types, $params);

        $orderBy = $this->orderBy(
            ['id' => 'shipment_types.id', 'shipment_type' => 'shipment_types.shipment_type'],
            $orderColumn, $orderDir, 'shipment_types.id'
        );
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $rows = $this->fetchAll("SELECT * FROM shipment_types WHERE $where ORDER BY shipment_types.deleted, $orderBy LIMIT ?, ?", $types, $params);

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id' => $row['id'],
                'shipment_type' => $row['shipment_type'],
                'deleted' => $row['deleted']
            ];
        }

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Get single shipment type by ID
     */
    public function getById(int $id): ?array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        return $this->fetchOne("SELECT id, shipment_type, customer FROM shipment_types WHERE $where", $types, $params);
    }

    /**
     * Create new shipment type
     */
    public function create(array $data, int $company): array
    {
        $data['customer'] = $this->resolveCompany($company);
        $data['created_by'] = $this->user;

        if (!$this->insertRow('shipment_types', $data)) {
            return ['status' => 'failed', 'message' => 'Failed to add record'];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    /**
     * Update existing shipment type (SADMIN may move it to another company)
     */
    public function update(int $id, array $data, int $company): array
    {
        $data['customer'] = $this->resolveCompany($company);
        $data['modified_by'] = $this->user;

        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        if (!$this->updateRow('shipment_types', $data, $where, $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to update record'];
        }

        return ['status' => 'success', 'message' => 'Updated Successfully!!'];
    }

    public function delete(array $ids): array
    {
        return $this->softDeleteRecords('shipment_types', $ids);
    }

    public function reactivate(int $id): array
    {
        return $this->reactivateRecord('shipment_types', $id);
    }

    /**
     * Bulk insert shipment types from Excel upload
     */
    public function upload(array $rows): array
    {
        return $this->uploadRecords('shipment_types', 'shipment_type', 'Shipment Type', $rows, function (array $row): array {
            return [
                'shipment_type' => !empty($row['ShipmentType']) ? trim($row['ShipmentType']) : ''
            ];
        });
    }
}
