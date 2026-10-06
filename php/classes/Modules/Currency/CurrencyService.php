<?php
namespace App\Modules\Currency;

use App\Core\BaseService;

class CurrencyService extends BaseService
{
    /**
     * Get paginated currencies for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $where = "currency.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'currency.customer');

        $totalRecords = $this->countRows('currency', $where, $types, $params);

        $this->applySearch($where, $params, $types, ['currency.currency', 'currency.description'], $search);
        $totalFiltered = $this->countRows('currency', $where, $types, $params);

        $orderBy = $this->orderBy(
            ['id' => 'currency.id', 'currency' => 'currency.currency', 'description' => 'currency.description'],
            $orderColumn, $orderDir, 'currency.id'
        );
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $rows = $this->fetchAll("SELECT * FROM currency WHERE $where ORDER BY currency.deleted, $orderBy LIMIT ?, ?", $types, $params);

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id' => $row['id'],
                'currency' => $row['currency'],
                'description' => $row['description'],
                'rate' => $row['rate'],
                'is_default' => $row['is_default'],
                'deleted' => $row['deleted']
            ];
        }

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Get single currency by ID
     */
    public function getById(int $id): ?array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        return $this->fetchOne("SELECT id, currency, description, rate, is_default, customer FROM currency WHERE $where", $types, $params);
    }

    /**
     * Create new currency
     */
    public function create(array $data, int $company): array
    {
        $data['customer'] = $this->resolveCompany($company);
        $data['created_by'] = $this->user;

        if (!$this->insertRow('currency', $data)) {
            return ['status' => 'failed', 'message' => 'Failed to add record'];
        }

        return ['status' => 'success', 'message' => 'Added Successfully!!'];
    }

    /**
     * Update existing currency (SADMIN may move it to another company)
     */
    public function update(int $id, array $data, int $company): array
    {
        $data['customer'] = $this->resolveCompany($company);
        $data['modified_by'] = $this->user;

        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        if (!$this->updateRow('currency', $data, $where, $types, $params)) {
            return ['status' => 'failed', 'message' => 'Failed to update record'];
        }

        return ['status' => 'success', 'message' => 'Updated Successfully!!'];
    }

    public function delete(array $ids): array
    {
        return $this->softDeleteRecords('currency', $ids);
    }

    public function reactivate(int $id): array
    {
        return $this->reactivateRecord('currency', $id);
    }

    /**
     * Set a currency as the default for its company
     */
    public function setDefault(int $id): array
    {
        $currency = $this->getById($id);
        if (!$currency) {
            return ['status' => 'failed', 'message' => 'Currency not found'];
        }

        $targetCompany = (int)$currency['customer'];

        $this->db->begin_transaction();

        try {
            if (!$this->executeWrite("UPDATE currency SET is_default = 0 WHERE customer = ? AND deleted = 0", 'i', [$targetCompany])) {
                throw new \Exception('Failed to reset default currency');
            }

            if (!$this->executeWrite("UPDATE currency SET is_default = 1 WHERE id = ?", 'i', [$id])) {
                throw new \Exception('Failed to set default currency');
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('CurrencyService::setDefault - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to update default currency'];
        }

        return ['status' => 'success', 'message' => 'Default currency updated'];
    }
}
