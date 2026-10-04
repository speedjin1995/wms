<?php
namespace App\Services;

class CurrencyService extends MasterDataService
{
    protected function table(): string
    {
        return 'currency';
    }

    protected function searchColumns(): array
    {
        return ['currency.currency', 'currency.description'];
    }

    protected function sortColumns(): array
    {
        return ['id' => 'currency.id', 'currency' => 'currency.currency', 'description' => 'currency.description'];
    }

    protected function formatListRow(array $row, int $rowNumber): array
    {
        return [
            'id' => $row['id'],
            'currency' => $row['currency'],
            'description' => $row['description'],
            'rate' => $row['rate'],
            'is_default' => $row['is_default'],
            'deleted' => $row['deleted']
        ];
    }

    protected function getColumns(): array
    {
        return ['id', 'currency', 'description', 'rate', 'is_default', 'customer'];
    }

    protected function updateColumns(): array
    {
        return ['currency', 'description', 'rate'];
    }

    protected function updatesCompany(): bool
    {
        return true;
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
