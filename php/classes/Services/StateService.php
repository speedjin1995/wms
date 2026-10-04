<?php
namespace App\Services;

class StateService extends BaseService
{
    /**
     * Get paginated states for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $where = "states.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'states.customer');

        $totalRecords = $this->countRows('states', $where, $types, $params);

        $this->applySearch($where, $params, $types, ['states.states'], $search);
        $totalFiltered = $this->countRows('states', $where, $types, $params);

        $orderBy = $this->orderBy(['id' => 'states.id', 'states' => 'states.states'], $orderColumn, $orderDir, 'states.id');
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $rows = $this->fetchAll("SELECT * FROM states WHERE $where ORDER BY states.deleted, $orderBy LIMIT ?, ?", $types, $params);

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id' => $row['id'],
                'states' => $row['states'],
                'deleted' => $row['deleted']
            ];
        }

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Get single state by ID
     */
    public function getById(int $id): ?array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        return $this->fetchOne("SELECT id, states, customer FROM states WHERE $where", $types, $params);
    }

    /**
     * Create new state
     */
    public function create(array $data, int $company): array
    {
        $data['customer'] = $this->resolveCompany($company);
        $data['created_by'] = $this->user;

        if (!$this->insertRow('states', $data)) {
            return ['status' => 'failed', 'message' => 'Failed to add record'];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    /**
     * Update existing state (SADMIN may move it to another company)
     */
    public function update(int $id, array $data, int $company): array
    {
        $data['customer'] = $this->resolveCompany($company);
        $data['modified_by'] = $this->user;

        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        if (!$this->updateRow('states', $data, $where, $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to update record'];
        }

        return ['status' => 'success', 'message' => 'Updated Successfully!!'];
    }

    public function delete(array $ids): array
    {
        return $this->softDeleteRecords('states', $ids);
    }

    public function reactivate(int $id): array
    {
        return $this->reactivateRecord('states', $id);
    }
}
