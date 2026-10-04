<?php
namespace App\Services;

class GradeService extends BaseService
{
    /**
     * Get paginated grades for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $where = "grades.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'grades.customer');

        $totalRecords = $this->countRows('grades', $where, $types, $params);

        $this->applySearch($where, $params, $types, ['grades.units'], $search);
        $totalFiltered = $this->countRows('grades', $where, $types, $params);

        $orderBy = $this->orderBy(['id' => 'grades.id', 'units' => 'grades.units'], $orderColumn, $orderDir, 'grades.id');
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $rows = $this->fetchAll("SELECT * FROM grades WHERE $where ORDER BY grades.deleted, (grades.is_manual = 'Y') DESC, $orderBy LIMIT ?, ?", $types, $params);

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id' => $row['id'],
                'units' => $row['units'],
                'is_manual' => $row['is_manual'],
                'deleted' => $row['deleted']
            ];
        }

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Get single grade by ID
     */
    public function getById(int $id): ?array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        return $this->fetchOne("SELECT id, units, customer FROM grades WHERE $where", $types, $params);
    }

    /**
     * Create new grade
     */
    public function create(array $data, int $company): array
    {
        $data['customer'] = $this->resolveCompany($company);
        $data['created_by'] = $this->user;

        if (!$this->insertRow('grades', $data)) {
            return ['status' => 'failed', 'message' => 'Failed to add record'];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    /**
     * Update existing grade
     */
    public function update(int $id, array $data): array
    {
        $data['is_manual'] = 'N';
        $data['modified_by'] = $this->user;

        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        if (!$this->updateRow('grades', $data, $where, $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to update record'];
        }

        return ['status' => 'success', 'message' => 'Updated Successfully!!'];
    }

    public function delete(array $ids): array
    {
        return $this->softDeleteRecords('grades', $ids);
    }

    public function reactivate(int $id): array
    {
        return $this->reactivateRecord('grades', $id);
    }

    /**
     * Bulk insert grades from Excel upload
     */
    public function upload(array $rows): array
    {
        return $this->uploadRecords('grades', 'units', 'Grade', $rows, function (array $row): array {
            return [
                'units' => !empty($row['Unit']) ? trim($row['Unit']) : ''
            ];
        });
    }
}
