<?php
if (PHP_SAPI !== 'cli') {
    exit;
}

use App\Integrations\Connector\ConnectorRepository;
use App\Integrations\Connector\SecretStore;
use PHPUnit\Framework\TestCase;

/**
 * Connector, operation and credential rows, tenant rules and customer logins. Needs the deploy tables.
 */
class ConnectorRepositoryTest extends TestCase
{
    private mysqli $db;
    private ConnectorRepository $admin;
    private ConnectorRepository $tenant;
    /** @var int[] */
    private array $connectorIds = [];

    public static function setUpBeforeClass(): void
    {
        // Leftovers from an earlier failed run
        connectorTestDb()->query("UPDATE api_connectors SET deleted = 1, deleted_at = NOW() WHERE code LIKE 'smoke\\_repo%' AND deleted = 0");
    }

    protected function setUp(): void
    {
        $this->db = connectorTestDb();
        $this->admin = new ConnectorRepository($this->db, 1, 1, 'SADMIN');
        $this->tenant = new ConnectorRepository($this->db, 7, 2, 'ADMIN');
    }

    protected function tearDown(): void
    {
        foreach ($this->connectorIds as $id) {
            $this->admin->deleteConnector($id);
        }
    }

    private function newConnector(string $suffix, array $extra = []): int
    {
        $id = $this->admin->saveConnector($extra + ['code' => 'smoke_repo_' . $suffix, 'name' => 'Repo check', 'protocol' => 'rest', 'base_url' => 'https://example.com', 'tenant_configurable' => 1]);
        $this->connectorIds[] = $id;

        return $id;
    }

    private function assertThrows(callable $fn, string $class, string $message): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            $this->assertInstanceOf($class, $e, $message);
            return;
        }
        $this->fail($message . ': nothing was thrown');
    }

    public function testConnectorCrud(): void
    {
        $connId = $this->newConnector('crud', ['options' => ['allow_private' => false]]);
        $this->assertGreaterThan(0, $connId, 'saveConnector inserts');
        $this->assertSame($connId, $this->admin->saveConnector(['name' => 'Renamed'], $connId), 'saveConnector updates');
        $this->assertContains('{"allow_private":false}', array_column($this->admin->listConnectors(), 'options'), 'connector options stored as JSON');
    }

    public function testOperationsAndSpecValidation(): void
    {
        $connId = $this->newConnector('ops');
        $opId = $this->admin->saveOperation($connId, ['code' => 'item', 'name' => 'Item', 'http_method' => 'POST', 'path_template' => 'items/{id}', 'exposed' => 1,
            'request_spec' => ['path' => ['id' => '{{input.id}}'], 'body' => ['n' => '{{input.n | upper}}']],
            'response_spec' => ['extract' => 'data', 'map' => ['x' => '{{response.x}}']],
            'error_spec' => ['message' => 'error.detail']]);
        $this->assertGreaterThan(0, $opId, 'saveOperation inserts with specs');
        $this->assertCount(1, $this->admin->listOperations($connId), 'valid specs stored');

        $this->assertThrows(fn () => $this->admin->saveOperation($connId, ['code' => 'bad', 'name' => 'Bad', 'request_spec' => ['body' => '{{response.x}}']]), \UnexpectedValueException::class, 'unknown root in request_spec rejected');
        $this->assertThrows(fn () => $this->admin->saveOperation($connId, ['code' => 'bad2', 'name' => 'Bad', 'response_spec' => ['then' => ['exec']]]), \UnexpectedValueException::class, 'unknown transform in response_spec rejected');
        $this->assertThrows(fn () => $this->admin->saveOperation($connId, ['code' => 'bad3', 'name' => 'Bad', 'options' => '"text"']), \UnexpectedValueException::class, 'non-object JSON rejected');
    }

    public function testCredentialSecretIsNeverListed(): void
    {
        $connId = $this->newConnector('cred');
        $this->admin->saveCredential($connId, 0, 'api_key', ['in' => 'header', 'name' => 'x-api-key'], ['key' => 'REPO-SECRET-1']);
        $list = $this->admin->listCredentials($connId);
        $this->assertTrue(count($list) === 1 && !array_key_exists('secret', $list[0]) && $list[0]['secret_hint'] === SecretStore::hint('REPO-SECRET-1'), 'listCredentials has hint, no secret column');

        $this->admin->saveCredential($connId, 0, 'api_key', ['in' => 'header', 'name' => 'x-api-key'], null);
        $this->assertSame(SecretStore::hint('REPO-SECRET-1'), $this->admin->listCredentials($connId)[0]['secret_hint'], 'null secret keeps stored hint');
    }

    public function testTenantSide(): void
    {
        $connId = $this->newConnector('tenant');
        $this->admin->saveCredential($connId, 0, 'api_key', ['in' => 'header', 'name' => 'x-api-key'], ['key' => 'REPO-SECRET-1']);

        $this->assertContains('smoke_repo_tenant', array_column($this->tenant->listTenantConnectors(), 'code'), 'tenant lists tenant_configurable connectors');

        $this->tenant->saveTenantCredential($connId, 'bearer', [], ['token' => 'TENANT-TOKEN-22']);
        $row = array_values(array_filter($this->tenant->listTenantConnectors(), fn ($r) => $r['code'] === 'smoke_repo_tenant'))[0];
        $this->assertTrue($row['auth_type'] === 'bearer' && $row['secret_hint'] === SecretStore::hint('TENANT-TOKEN-22'), 'tenant sees own credential hint');

        $this->assertThrows(fn () => $this->tenant->saveConnector(['name' => 'x'], $connId), \RuntimeException::class, 'tenant cannot write connectors');
        $this->assertThrows(fn () => $this->tenant->listCredentials($connId), \RuntimeException::class, 'tenant cannot use admin listing');

        $rows = $this->db->query("SELECT company FROM api_connector_credentials WHERE connector_id = $connId AND deleted = 0 ORDER BY company")->fetch_all(MYSQLI_ASSOC);
        $this->assertTrue(count($rows) === 2 && (int)$rows[1]['company'] === 7, 'tenant credential stored under its company, not 0');
    }

    public function testLockedConnectorRefusesTenantWrites(): void
    {
        $lockedId = $this->newConnector('locked', ['tenant_configurable' => 0]);
        $this->assertThrows(fn () => $this->tenant->saveTenantCredential($lockedId, 'none', [], null), \RuntimeException::class, 'tenant write refused on locked connector');
    }

    private function loginSetup(int $connectorId): array
    {
        return array_values(array_filter($this->tenant->listTenantSetup(), fn ($r) => $r['id'] === $connectorId))[0] ?? [];
    }

    public function testCustomerLogins(): void
    {
        $noTypeId = $this->newConnector('notype');
        $loginId = $this->newConnector('login', ['tenant_auth_type' => 'basic']);

        $this->assertThrows(fn () => $this->admin->saveConnector(['tenant_auth_type' => 'oauth'], $loginId), \UnexpectedValueException::class, 'unknown login type refused');
        $this->assertThrows(fn () => $this->tenant->saveTenantLogin($noTypeId, ['username' => 'a', 'password' => 'b']), \RuntimeException::class, 'no login type set by vendor: customer cannot save');

        $setup = $this->loginSetup($loginId);
        $this->assertTrue(array_column($setup['fields'] ?? [], 'name') === ['username', 'password'] && $setup['secret_hint'] === null, 'basic setup shows username and password boxes, no hint yet');
        $this->assertThrows(fn () => $this->tenant->saveTenantLogin($loginId, ['username' => 'alice', 'password' => '']), \RuntimeException::class, 'missing password refused');

        $this->tenant->saveTenantLogin($loginId, ['username' => 'alice', 'password' => 'LOGIN-SECRET-5']);
        $setup = $this->loginSetup($loginId);
        $this->assertTrue($setup['username'] === 'alice' && $setup['secret_hint'] === SecretStore::hint('LOGIN-SECRET-5'), 'saved basic login shows username and hint');

        $cred = $this->tenant->findCredential($loginId);
        $this->assertTrue($cred !== null && $cred['auth_type'] === 'basic' && json_decode($cred['auth_config'], true)['username'] === 'alice', 'findCredential uses the customer basic login');

        $this->tenant->saveTenantLogin($loginId, ['username' => 'bob', 'password' => '']);
        $setup = $this->loginSetup($loginId);
        $this->assertTrue($setup['username'] === 'bob' && $setup['secret_hint'] === SecretStore::hint('LOGIN-SECRET-5'), 'blank password keeps the saved one for the same type');

        // Vendor changes the type: the old customer login is not used until the customer saves again
        $this->admin->saveConnector(['tenant_auth_type' => 'bearer'], $loginId);
        $this->assertNull($this->tenant->findCredential($loginId), 'type change: old customer login not used');
        $this->assertNull($this->loginSetup($loginId)['secret_hint'], 'type change: setup shows no saved hint');

        // JWT: the vendor's preset is merged under the customer's username
        $this->admin->saveConnector(['tenant_auth_type' => 'jwt', 'tenant_auth_config' => ['login_path' => 'auth/login', 'token_path' => 'data.token']], $loginId);
        $this->tenant->saveTenantLogin($loginId, ['username' => 'bob', 'password' => 'LOGIN-SECRET-6']);
        $merged = json_decode($this->tenant->findCredential($loginId)['auth_config'], true);
        $this->assertTrue(($merged['login_path'] ?? '') === 'auth/login' && ($merged['username'] ?? '') === 'bob', 'jwt: vendor preset merged with customer username');
        $this->assertArrayNotHasKey('tenant_auth_config', $this->loginSetup($loginId), 'jwt: preset is not sent to the browser');

        // Header: one box per header name from the preset
        $this->admin->saveConnector(['tenant_auth_type' => 'header', 'tenant_auth_config' => ['headers' => ['X-Client-Id', 'X-Secret']]], $loginId);
        $this->assertSame(['X-Client-Id', 'X-Secret'], array_column($this->loginSetup($loginId)['fields'] ?? [], 'name'), 'header setup shows one box per header name');
    }

    public function testCascadeSoftDelete(): void
    {
        $connId = $this->newConnector('cascade');
        $this->admin->saveOperation($connId, ['code' => 'item', 'name' => 'Item', 'http_method' => 'GET', 'path_template' => 'items']);
        $this->admin->saveCredential($connId, 0, 'none', [], []);

        $this->assertTrue($this->admin->deleteConnector($connId), 'deleteConnector soft deletes');
        $this->connectorIds = [];

        $left = $this->db->query("SELECT (SELECT COUNT(*) FROM api_connector_operations WHERE connector_id = $connId AND deleted = 0) + (SELECT COUNT(*) FROM api_connector_credentials WHERE connector_id = $connId AND deleted = 0) AS n")->fetch_assoc();
        $this->assertSame(0, (int)$left['n'], 'cascade left no active child rows');
    }
}
