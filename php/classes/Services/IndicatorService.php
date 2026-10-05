<?php
namespace App\Services;

/**
 * Session company's indicators and the company's selected indicator.
 */
class IndicatorService extends BaseService
{
    /**
     * Indicators owned by the session company
     */
    public function getList(): array
    {
        return $this->fetchAll("SELECT id, name, nickname FROM indicators WHERE customer = ? ORDER BY name ASC", 'i', [$this->company]);
    }

    /**
     * Single indicator owned by the session company
     */
    public function getById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT id, name, nickname, serial_no, mac_address, indicator FROM indicators WHERE id = ? AND customer = ?",
            'ii',
            [$id, $this->company]
        );
    }

    /**
     * Indicator currently selected for the session company
     */
    public function getCurrent(): ?array
    {
        return $this->fetchOne(
            "SELECT i.id, i.name, i.nickname, i.serial_no, i.mac_address, i.indicator
             FROM companies c JOIN indicators i ON c.indicator = i.id AND i.customer = c.id
             WHERE c.id = ?",
            'i',
            [$this->company]
        );
    }

    /**
     * Set the session company's indicator
     */
    public function setCurrent(int $id): array
    {
        if (!$this->getById($id)) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        if (!$this->executeWrite("UPDATE companies SET indicator = ? WHERE id = ?", 'ii', [$id, $this->company])) {
            return ['status' => 'failed', 'message' => 'Failed to update indicator setup'];
        }

        return ['status' => 'success', 'message' => 'Your indicator setup is updated successfully!'];
    }
}
