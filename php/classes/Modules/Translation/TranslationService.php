<?php
namespace App\Modules\Translation;

use App\Core\BaseService;

/**
 * Message resources are scoped by the `company` column; company 0 holds the default set (SADMIN only).
 * Records are hard deleted (no `deleted` column).
 */
class TranslationService extends BaseService
{
    /**
     * Get paginated translations for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $where = "1 = 1";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'company');

        $totalRecords = $this->countRows('message_resource', $where, $types, $params);

        $this->applySearch($where, $params, $types, ['message_key_code'], $search);
        $totalFiltered = $this->countRows('message_resource', $where, $types, $params);

        $orderBy = $this->orderBy([
            'counter' => 'id',
            'message_key_code' => 'message_key_code',
            'en' => 'en',
            'zh' => 'zh',
            'my' => 'my',
            'ne' => 'ne',
            'ja' => 'ja'
        ], $orderColumn, $orderDir, 'id');
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $rows = $this->fetchAll("SELECT * FROM message_resource WHERE $where ORDER BY $orderBy LIMIT ?, ?", $types, $params);

        $data = [];
        $rowNumber = $start + 1;
        foreach ($rows as $row) {
            $data[] = [
                'id' => $row['id'],
                'counter' => $rowNumber++,
                'message_key_code' => $row['message_key_code'],
                'en' => $row['en'],
                'zh' => $row['zh'],
                'my' => $row['my'],
                'ne' => $row['ne'],
                'ja' => $row['ja'],
                'company' => $row['company']
            ];
        }

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Get single translation by ID
     */
    public function getById(int $id): ?array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types, 'company');

        return $this->fetchOne("SELECT id, message_key_code, en, zh, my, ne, ja, company FROM message_resource WHERE $where", $types, $params);
    }

    /**
     * Create new translation
     */
    public function create(array $data): array
    {
        $data['company'] = $this->resolveCompany((int)$data['company']);

        if (!$this->insertRow('message_resource', $data)) {
            return ['status' => 'failed', 'message' => 'Failed to add record'];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    /**
     * Update existing translation
     */
    public function update(int $id, array $data): array
    {
        $data['company'] = $this->resolveCompany((int)$data['company']);

        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types, 'company');

        if (!$this->updateRow('message_resource', $data, $where, $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to update record'];
        }

        return ['status' => 'success', 'message' => 'Updated Successfully!!'];
    }

    /**
     * Hard delete translation within the session company
     */
    public function delete(int $id): array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types, 'company');

        if (!$this->executeWrite("DELETE FROM message_resource WHERE $where", $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to delete record'];
        }

        return ['status' => 'success', 'message' => 'Deleted'];
    }
}
