<?php
namespace App\Services;

/**
 * Per-customer / per-supplier running number prefixes (running_no_entity)
 * and invoice code.
 */
class EntityRunningNoService extends BaseService
{
    private const TABLES = [
        'Customer' => 'customers',
        'Supplier' => 'supplies'
    ];

    private string $module;
    private string $entityType;
    private string $table;

    public function __construct(\mysqli $db, int $company, int $user, string $role, string $module, string $entityType)
    {
        if (!isset(self::TABLES[$entityType])) {
            throw new \InvalidArgumentException('Unsupported entity type: ' . $entityType);
        }

        parent::__construct($db, $company, $user, $role);
        $this->module = $module;
        $this->entityType = $entityType;
        $this->table = self::TABLES[$entityType];
    }

    /**
     * Load statuses with saved prefix / value and the invoice code for an entity
     */
    public function get(int $entityId): array
    {
        $entity = $this->findEntity($entityId);
        if (!$entity) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        // Build exclusion list based on products and company
        $companyRow = $this->fetchOne("SELECT products FROM companies WHERE id = ?", 'i', [$this->company]);
        $products = ($companyRow && !empty($companyRow['products'])) ? (array)json_decode($companyRow['products']) : [];

        $excludeStatuses = [];
        if (!in_array('stocks', $products)) {
            $excludeStatuses[] = 'STOCK-BAL';
        }
        if ($this->company != 14) {
            $excludeStatuses[] = 'NITROGEN';
            $excludeStatuses[] = 'REJECT';
        }

        // Load statuses for this module + entity type
        $statuses = [];
        foreach ($this->fetchAll("SELECT id, status, prefix FROM statuses WHERE module = ? AND entity_type = ?", 'ss', [$this->module, $this->entityType]) as $row) {
            if (!in_array($row['status'], $excludeStatuses)) {
                $statuses[] = $row;
            }
        }

        // Load existing running_no_entity rows for this entity
        $existing = [];
        $rows = $this->fetchAll(
            "SELECT transaction_status, prefix, value FROM running_no_entity WHERE company_id = ? AND module = ? AND entity_id = ?",
            'isi', [$this->company, $this->module, $entityId]
        );
        foreach ($rows as $row) {
            $existing[$row['transaction_status']] = $row;
        }

        // Merge
        foreach ($statuses as &$s) {
            if (isset($existing[$s['status']])) {
                $s['saved_prefix'] = $existing[$s['status']]['prefix'];
                $s['value'] = $existing[$s['status']]['value'];
            } else {
                $s['saved_prefix'] = $s['prefix'];
                $s['value'] = 1;
            }
        }
        unset($s);

        return ['status' => 'success', 'data' => $statuses, 'invoice_code' => $entity['invoice_code'] ?? ''];
    }

    /**
     * Save invoice code and running number prefixes for an entity
     */
    public function save(int $entityId, string $invoiceCode, array $rows): array
    {
        if (!$this->findEntity($entityId)) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        $this->db->begin_transaction();

        try {
            // Update invoice_code on entity table
            if (!$this->executeWrite("UPDATE {$this->table} SET invoice_code = ? WHERE id = ? AND customer = ?", 'sii', [$invoiceCode, $entityId, $this->company])) {
                throw new \Exception('Failed to update invoice code');
            }

            foreach ($rows as $row) {
                $status = (string)($row['transaction_status'] ?? '');
                $prefix = (string)($row['prefix'] ?? '');
                $value = (int)($row['value'] ?? 0);

                $exists = $this->fetchOne(
                    "SELECT id FROM running_no_entity WHERE company_id = ? AND module = ? AND entity_id = ? AND transaction_status = ?",
                    'isis', [$this->company, $this->module, $entityId, $status]
                );

                if ($exists) {
                    $ok = $this->executeWrite("UPDATE running_no_entity SET prefix = ?, value = ? WHERE id = ?", 'sii', [$prefix, $value, (int)$exists['id']]);
                } else {
                    $ok = $this->executeWrite(
                        "INSERT INTO running_no_entity (company_id, module, transaction_status, entity_id, prefix, value) VALUES (?, ?, ?, ?, ?, ?)",
                        'issisi', [$this->company, $this->module, $status, $entityId, $prefix, $value]
                    );
                }

                if (!$ok) {
                    throw new \Exception('Failed to save running number');
                }
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('EntityRunningNoService::save - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to save running number'];
        }

        return ['status' => 'success', 'message' => 'Saved Successfully!!'];
    }

    /**
     * Find entity within the session company
     */
    private function findEntity(int $entityId): ?array
    {
        return $this->fetchOne("SELECT id, invoice_code FROM {$this->table} WHERE id = ? AND customer = ?", 'ii', [$entityId, $this->company]);
    }
}
