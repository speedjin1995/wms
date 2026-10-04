<?php
namespace App\Services;

/**
 * Units are shared by all companies (no company scope / audit columns).
 */
class UnitService extends BaseService
{
    /**
     * Get paginated units for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $where = "units.deleted = 0";
        $params = [];
        $types = '';

        $totalRecords = $this->countRows('units', $where, $types, $params);

        $this->applySearch($where, $params, $types, ['units.units'], $search);
        $totalFiltered = $this->countRows('units', $where, $types, $params);

        $orderBy = $this->orderBy(['id' => 'units.id', 'no' => 'units.id', 'units' => 'units.units'], $orderColumn, $orderDir, 'units.id');
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $rows = $this->fetchAll("SELECT * FROM units WHERE $where ORDER BY $orderBy LIMIT ?, ?", $types, $params);

        $data = [];
        $rowNumber = $start + 1;
        foreach ($rows as $row) {
            $data[] = [
                'id' => $row['id'],
                'no' => $rowNumber++,
                'units' => $row['units']
            ];
        }

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Get single unit by ID
     */
    public function getById(int $id): ?array
    {
        return $this->fetchOne("SELECT id, units FROM units WHERE id = ?", 'i', [$id]);
    }

    /**
     * Create new unit
     */
    public function create(array $data): array
    {
        if (!$this->insertRow('units', $data)) {
            return ['status' => 'failed', 'message' => 'Failed to add record'];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    /**
     * Update existing unit
     */
    public function update(int $id, array $data): array
    {
        if (!$this->updateRow('units', $data, "id = ?", 'i', [$id])) {
            return ['status' => 'failed', 'message' => 'Failed to update record'];
        }

        return ['status' => 'success', 'message' => 'Updated Successfully!!'];
    }

    public function delete(array $ids): array
    {
        return $this->softDeleteRecords('units', $ids, null, false);
    }

    public function reactivate(int $id): array
    {
        return $this->reactivateRecord('units', $id, null);
    }
}
