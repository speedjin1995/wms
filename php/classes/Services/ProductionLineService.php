<?php
namespace App\Services;

class ProductionLineService extends BaseService
{
    /**
     * Get paginated production lines for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $where = "production_lines.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'production_lines.customers');

        $totalRecords = $this->countRows('production_lines', $where, $types, $params);

        $this->applySearch($where, $params, $types, ['production_lines.production_line'], $search);
        $totalFiltered = $this->countRows('production_lines', $where, $types, $params);

        $orderBy = $this->orderBy(
            ['id' => 'production_lines.id', 'production_line' => 'production_lines.production_line'],
            $orderColumn, $orderDir, 'production_lines.id'
        );
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $rows = $this->fetchAll("SELECT * FROM production_lines WHERE $where ORDER BY production_lines.deleted, $orderBy LIMIT ?, ?", $types, $params);

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id' => $row['id'],
                'production_line' => $row['production_line'],
                'customers' => $row['customers'],
                'deleted' => $row['deleted']
            ];
        }

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Get single production line by ID
     */
    public function getById(int $id): ?array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types, 'customers');

        return $this->fetchOne("SELECT id, production_line, customers FROM production_lines WHERE $where", $types, $params);
    }

    /**
     * Create new production line
     */
    public function create(array $data, int $company): array
    {
        $data['customers'] = $this->resolveCompany($company);
        $data['created_by'] = $this->user;

        if (!$this->insertRow('production_lines', $data)) {
            return ['status' => 'failed', 'message' => 'Failed to add record'];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    /**
     * Update existing production line
     */
    public function update(int $id, array $data): array
    {
        $data['modified_by'] = $this->user;

        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types, 'customers');

        if (!$this->updateRow('production_lines', $data, $where, $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to update record'];
        }

        return ['status' => 'success', 'message' => 'Updated Successfully!!'];
    }

    public function delete(array $ids): array
    {
        return $this->softDeleteRecords('production_lines', $ids, 'customers');
    }

    public function reactivate(int $id): array
    {
        return $this->reactivateRecord('production_lines', $id, 'customers');
    }

    /**
     * Bulk insert production lines from Excel upload
     */
    public function upload(array $rows): array
    {
        return $this->uploadRecords('production_lines', 'production_line', 'Production Line', $rows, function (array $row): array {
            return [
                'production_line' => !empty($row['ProductionLine']) ? trim($row['ProductionLine']) : ''
            ];
        }, 'customers');
    }
}
