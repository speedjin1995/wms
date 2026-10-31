<?php
namespace App\Integrations\Connector;

use App\Services\BaseService;

/**
 * All connector SQL. Company and user come from the session via BaseService.
 */
class ConnectorRepository extends BaseService
{
    private const CONNECTOR_COLUMNS = ['code', 'name', 'protocol', 'base_url', 'default_headers', 'timeout_ms', 'connect_timeout_ms', 'retry_max', 'retry_base_ms', 'breaker_threshold', 'breaker_cooldown_s', 'allow_http', 'tenant_configurable', 'options'];
    private const OPERATION_COLUMNS = ['code', 'name', 'http_method', 'path_template', 'retry_safe', 'timeout_ms', 'exposed', 'request_spec', 'response_spec', 'error_spec', 'options'];

    private ?SecretStore $secrets = null;

    public function findConnector(string $code): ?array
    {
        return $this->fetchOne(
            "SELECT id, code, protocol, base_url, default_headers, timeout_ms, connect_timeout_ms, allow_http, options
            FROM api_connectors WHERE code = ? AND deleted = 0",
            's',
            [$code]
        );
    }

    public function findOperation(int $connectorId, string $code): ?array
    {
        return $this->fetchOne(
            "SELECT id, code, http_method, path_template, timeout_ms, exposed, options, request_spec, response_spec, error_spec
            FROM api_connector_operations WHERE connector_id = ? AND code = ? AND deleted = 0",
            'is',
            [$connectorId, $code]
        );
    }

    /**
     * Session company's own credential first, else the platform default (company 0)
     */
    public function findCredential(int $connectorId): ?array
    {
        return $this->fetchOne(
            "SELECT id, company, auth_type, auth_config, secret, key_version
            FROM api_connector_credentials WHERE connector_id = ? AND company IN (?, 0) AND deleted = 0
            ORDER BY company DESC LIMIT 1",
            'ii',
            [$connectorId, $this->company]
        );
    }

    /**
     * Logged against the session company, whichever credential row was used
     */
    public function insertCallLog(array $row): void
    {
        $row['company'] = $this->company;
        $row['user_id'] = $this->user ?: null;

        $this->insertRow('api_call_logs', $row);
    }

    /**
     * Vendor side (SADMIN only): connectors, operations and credentials for any company
     */
    public function listConnectors(): array
    {
        $this->requireSuperAdmin();

        return $this->fetchAll("SELECT * FROM api_connectors WHERE deleted = 0 ORDER BY code");
    }

    public function saveConnector(array $data, ?int $id = null): int
    {
        $this->requireSuperAdmin();

        $row = array_intersect_key($data, array_flip(self::CONNECTOR_COLUMNS));
        foreach (['default_headers', 'options'] as $column) {
            if (array_key_exists($column, $row)) {
                $row[$column] = $this->jsonColumn($row[$column], $column);
            }
        }
        $row['modified_by'] = $this->user;

        if ($id) {
            return $this->updateRow('api_connectors', $row, 'id = ? AND deleted = 0', 'i', [$id]) ? $id : 0;
        }
        $row['created_by'] = $this->user;

        return $this->insertRow('api_connectors', $row);
    }

    /**
     * Soft deletes the connector together with its operations and credentials
     */
    public function deleteConnector(int $id): bool
    {
        $this->requireSuperAdmin();

        $this->db->begin_transaction();
        $ok = $this->softDeleteRows('api_connector_operations', 'connector_id = ?', 'i', [$id])
            && $this->softDeleteRows('api_connector_credentials', 'connector_id = ?', 'i', [$id])
            && $this->softDeleteRows('api_connectors', 'id = ?', 'i', [$id]);
        $ok ? $this->db->commit() : $this->db->rollback();

        return $ok;
    }

    public function listOperations(int $connectorId): array
    {
        $this->requireSuperAdmin();

        return $this->fetchAll("SELECT * FROM api_connector_operations WHERE connector_id = ? AND deleted = 0 ORDER BY code", 'i', [$connectorId]);
    }

    /**
     * Specs are validated with MappingEngine. Throws UnexpectedValueException listing the problems.
     */
    public function saveOperation(int $connectorId, array $data, ?int $id = null): int
    {
        $this->requireSuperAdmin();

        $row = array_intersect_key($data, array_flip(self::OPERATION_COLUMNS));
        foreach (['request_spec', 'response_spec', 'error_spec'] as $column) {
            if (array_key_exists($column, $row)) {
                $row[$column] = $this->specColumn($row[$column], $column);
            }
        }
        if (array_key_exists('options', $row)) {
            $row['options'] = $this->jsonColumn($row['options'], 'options');
        }
        $row['modified_by'] = $this->user;

        if ($id) {
            return $this->updateRow('api_connector_operations', $row, 'id = ? AND connector_id = ? AND deleted = 0', 'ii', [$id, $connectorId]) ? $id : 0;
        }
        $row += ['connector_id' => $connectorId, 'created_by' => $this->user];

        return $this->insertRow('api_connector_operations', $row);
    }

    public function deleteOperation(int $id): bool
    {
        $this->requireSuperAdmin();

        return $this->softDeleteRows('api_connector_operations', 'id = ?', 'i', [$id]);
    }

    /**
     * Never selects the encrypted secret, only the hint
     */
    public function listCredentials(int $connectorId): array
    {
        $this->requireSuperAdmin();

        return $this->fetchAll("SELECT id, connector_id, company, auth_type, auth_config, secret_hint, key_version FROM api_connector_credentials WHERE connector_id = ? AND deleted = 0 ORDER BY company", 'i', [$connectorId]);
    }

    /**
     * $secret null keeps the stored secret. Otherwise it replaces it.
     */
    public function saveCredential(int $connectorId, int $company, string $authType, array $authConfig, ?array $secret): int
    {
        $this->requireSuperAdmin();

        return $this->writeCredential($connectorId, $company, $authType, $authConfig, $secret);
    }

    public function deleteCredential(int $id): bool
    {
        $this->requireSuperAdmin();

        return $this->softDeleteRows('api_connector_credentials', 'id = ?', 'i', [$id]);
    }

    /**
     * Tenant side: connectors flagged tenant_configurable, with the session company's own credential hint
     */
    public function listTenantConnectors(): array
    {
        return $this->fetchAll(
            "SELECT c.id, c.code, c.name, c.protocol, k.id AS credential_id, k.auth_type, k.secret_hint
            FROM api_connectors c
            LEFT JOIN api_connector_credentials k ON k.connector_id = c.id AND k.company = ? AND k.deleted = 0
            WHERE c.tenant_configurable = 1 AND c.deleted = 0
            ORDER BY c.code",
            'i',
            [$this->company]
        );
    }

    /**
     * Tenant side: exposed operations of a tenant_configurable connector, without specs or options
     */
    public function listTenantOperations(string $connectorCode): array
    {
        return $this->fetchAll(
            "SELECT o.code, o.name, o.http_method
            FROM api_connector_operations o
            JOIN api_connectors c ON c.id = o.connector_id
            WHERE c.code = ? AND c.tenant_configurable = 1 AND c.deleted = 0
              AND o.exposed = 1 AND o.deleted = 0
            ORDER BY o.code",
            's',
            [$connectorCode]
        );
    }

    /**
     * Tenant credentials only for connectors flagged tenant_configurable, always for the session company
     */
    public function saveTenantCredential(int $connectorId, string $authType, array $authConfig, ?array $secret): int
    {
        if (!$this->isTenantConfigurable($connectorId)) {
            throw new \RuntimeException('Connector is not tenant configurable');
        }

        return $this->writeCredential($connectorId, $this->company, $authType, $authConfig, $secret);
    }

    public function deleteTenantCredential(int $connectorId): bool
    {
        if (!$this->isTenantConfigurable($connectorId)) {
            throw new \RuntimeException('Connector is not tenant configurable');
        }

        return $this->softDeleteRows('api_connector_credentials', 'connector_id = ? AND company = ?', 'ii', [$connectorId, $this->company]);
    }

    private function writeCredential(int $connectorId, int $company, string $authType, array $authConfig, ?array $secret): int
    {
        $existing = $this->fetchOne("SELECT id FROM api_connector_credentials WHERE connector_id = ? AND company = ? AND deleted = 0", 'ii', [$connectorId, $company]);

        $row = ['auth_type' => $authType, 'auth_config' => json_encode($authConfig)];
        if ($secret !== null) {
            $encrypted = $this->secretStore()->encrypt($secret);
            $row['secret'] = $encrypted['cipher'];
            $row['key_version'] = $encrypted['version'];
            $row['secret_hint'] = $secret ? SecretStore::hint((string)reset($secret)) : null;
        }

        if ($existing) {
            $row['modified_by'] = $this->user;

            return $this->updateRow('api_connector_credentials', $row, 'id = ?', 'i', [$existing['id']]) ? (int)$existing['id'] : 0;
        }
        $row += ['connector_id' => $connectorId, 'company' => $company, 'created_by' => $this->user];

        return $this->insertRow('api_connector_credentials', $row);
    }

    private function isTenantConfigurable(int $connectorId): bool
    {
        return $this->fetchOne("SELECT id FROM api_connectors WHERE id = ? AND tenant_configurable = 1 AND deleted = 0", 'i', [$connectorId]) !== null;
    }

    private function softDeleteRows(string $table, string $where, string $types, array $params): bool
    {
        return $this->executeWrite(
            "UPDATE $table SET deleted = 1, deleted_at = NOW(), modified_by = ? WHERE $where",
            'i' . $types,
            array_merge([$this->user], $params)
        );
    }

    private function specColumn(mixed $value, string $kind): ?string
    {
        $json = $this->jsonColumn($value, $kind);
        if ($json === null) {
            return null;
        }

        $spec = json_decode($json, true);
        $errors = match ($kind) {
            'request_spec' => MappingEngine::validateRequest($spec),
            'response_spec' => MappingEngine::validateResponse($spec),
            default => is_string($spec['message'] ?? '') ? [] : ['message must be a path string']
        };
        if ($errors) {
            throw new \UnexpectedValueException($kind . ': ' . implode('; ', $errors));
        }

        return $json;
    }

    private function jsonColumn(mixed $value, string $name): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = is_array($value) ? $value : json_decode((string)$value, true);
        if (!is_array($decoded)) {
            throw new \UnexpectedValueException($name . ' must be a JSON object');
        }

        return json_encode($decoded);
    }

    private function requireSuperAdmin(): void
    {
        if (!$this->isSuperAdmin()) {
            throw new \RuntimeException('Only SADMIN can manage connectors');
        }
    }

    private function secretStore(): SecretStore
    {
        return $this->secrets ??= new SecretStore();
    }
}
