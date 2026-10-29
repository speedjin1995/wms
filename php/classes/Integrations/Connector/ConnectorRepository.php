<?php
namespace App\Integrations\Connector;

use App\Services\BaseService;

/**
 * All connector SQL. Company and user come from the session via BaseService.
 */
class ConnectorRepository extends BaseService
{
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
            "SELECT id, code, http_method, path_template, timeout_ms, exposed, options
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
}
