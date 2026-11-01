<?php
if (PHP_SAPI !== 'cli') {
    exit;
}

use App\Integrations\Connector\ConnectorClient;
use App\Integrations\Connector\ConnectorRepository;
use App\Integrations\Connector\ErrorCode;
use App\Integrations\Connector\NormalizedResult;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Every login type against real public APIs: httpbin.org, and dummyjson.com for JWT.
 * emilys / emilyspass is dummyjson's public demo account. Needs internet and the deploy tables.
 */
#[Group('live')]
class LiveAuthTest extends TestCase
{
    private const HTTPBIN = 'https://httpbin.org';
    private const DUMMYJSON = 'https://dummyjson.com';
    private const API_KEY = 'PHPUNIT-API-KEY-1';
    private const BEARER = 'PHPUNIT-BEARER-TOKEN-1';
    private const BASIC_PASSWORD = 'PHPUNIT-BASIC-PASS-1';
    private const HEADER = 'PHPUNIT-HEADER-VALUE-1';
    private const JWT_PASSWORD = 'emilyspass';

    private static mysqli $db;
    private static ConnectorRepository $admin;
    private static ConnectorClient $client;
    /** @var int[] */
    private static array $connectorIds = [];

    public static function setUpBeforeClass(): void
    {
        self::$db = connectorTestDb();
        self::$admin = new ConnectorRepository(self::$db, 1, 1, 'SADMIN');
        self::$client = new ConnectorClient(self::$admin);

        // Leftovers from an earlier failed run
        self::$db->query("UPDATE api_connectors SET deleted = 1, deleted_at = NOW() WHERE code LIKE 'phpunit\\_%' AND deleted = 0");
    }

    public static function tearDownAfterClass(): void
    {
        foreach (self::$connectorIds as $id) {
            self::$admin->deleteConnector($id);
        }
    }

    private static function connector(string $code, string $baseUrl, string $method, string $path, array $extra = []): int
    {
        $id = self::$admin->saveConnector($extra + ['code' => $code, 'name' => $code, 'protocol' => 'rest', 'base_url' => $baseUrl, 'timeout_ms' => 20000, 'connect_timeout_ms' => 10000]);
        self::$connectorIds[] = $id;
        self::$admin->saveOperation($id, ['code' => 'call', 'name' => 'Call', 'http_method' => $method, 'path_template' => $path]);

        return $id;
    }

    /**
     * Public APIs go down: that says nothing about the connector, so the test is skipped, not failed
     */
    private function call(ConnectorClient $client, string $code): NormalizedResult
    {
        $result = $client->execute($code, 'call');
        if (in_array($result->errorCode, [ErrorCode::UPSTREAM_ERROR, ErrorCode::TIMEOUT, ErrorCode::UNREACHABLE, ErrorCode::RATE_LIMITED], true)) {
            $this->markTestSkipped('Public API unavailable: ' . $code . ' returned ' . $result->errorCode);
        }

        return $result;
    }

    private function tokenRow(int $connectorId): array
    {
        return self::$db->query('SELECT token_cipher, token_expires_at FROM api_connector_credentials WHERE connector_id = ' . $connectorId . ' AND deleted = 0')->fetch_assoc() ?: [];
    }

    private function jwtConfig(array $extra = []): array
    {
        return $extra + ['login_path' => 'auth/login', 'token_path' => 'accessToken', 'username' => 'emilys'];
    }

    public function testNone(): void
    {
        $id = self::connector('phpunit_none', self::HTTPBIN, 'GET', 'get');
        self::$admin->saveCredential($id, 0, 'none', [], []);

        $r = $this->call(self::$client, 'phpunit_none');
        $this->assertTrue($r->ok, 'none: public path succeeds');
    }

    public function testNoneOnProtectedPathIsRefused(): void
    {
        $id = self::connector('phpunit_none_protected', self::HTTPBIN, 'GET', 'bearer');
        self::$admin->saveCredential($id, 0, 'none', [], []);

        $r = $this->call(self::$client, 'phpunit_none_protected');
        $this->assertSame(ErrorCode::AUTH_FAILED, $r->errorCode, 'none: protected path is refused');
    }

    public function testApiKeyInHeader(): void
    {
        $id = self::connector('phpunit_apikey_h', self::HTTPBIN, 'GET', 'headers');
        self::$admin->saveCredential($id, 0, 'api_key', ['in' => 'header', 'name' => 'x-api-key'], ['key' => self::API_KEY]);

        $r = $this->call(self::$client, 'phpunit_apikey_h');
        $this->assertTrue($r->ok, 'api_key header: call succeeds');
        $this->assertSame('***', $r->data['headers']['X-Api-Key'] ?? '', 'api_key header: reached the API and is masked');
    }

    public function testApiKeyInQuery(): void
    {
        $id = self::connector('phpunit_apikey_q', self::HTTPBIN, 'GET', 'get');
        self::$admin->saveCredential($id, 0, 'api_key', ['in' => 'query', 'name' => 'key'], ['key' => self::API_KEY]);

        $r = $this->call(self::$client, 'phpunit_apikey_q');
        $this->assertTrue($r->ok, 'api_key query: call succeeds');
        $this->assertSame('***', $r->data['args']['key'] ?? '', 'api_key query: reached the API and is masked');
    }

    public function testBearer(): void
    {
        $id = self::connector('phpunit_bearer', self::HTTPBIN, 'GET', 'bearer');
        self::$admin->saveCredential($id, 0, 'bearer', [], ['token' => self::BEARER]);

        $r = $this->call(self::$client, 'phpunit_bearer');
        $this->assertTrue($r->ok && ($r->data['authenticated'] ?? false) === true, 'bearer: API accepts the token');
        $this->assertSame('***', $r->data['token'] ?? '', 'bearer: token masked in the reply');
    }

    public function testBasicRightPassword(): void
    {
        $id = self::connector('phpunit_basic', self::HTTPBIN, 'GET', 'basic-auth/alice/' . self::BASIC_PASSWORD);
        self::$admin->saveCredential($id, 0, 'basic', ['username' => 'alice'], ['password' => self::BASIC_PASSWORD]);

        $r = $this->call(self::$client, 'phpunit_basic');
        $this->assertTrue($r->ok && ($r->data['authenticated'] ?? false) === true, 'basic: API accepts the login');
    }

    public function testBasicWrongPassword(): void
    {
        $id = self::connector('phpunit_basic_bad', self::HTTPBIN, 'GET', 'basic-auth/alice/' . self::BASIC_PASSWORD);
        self::$admin->saveCredential($id, 0, 'basic', ['username' => 'alice'], ['password' => 'PHPUNIT-WRONG-PASS-1']);

        $r = $this->call(self::$client, 'phpunit_basic_bad');
        $this->assertSame(ErrorCode::AUTH_FAILED, $r->errorCode, 'basic: wrong password is refused');
    }

    public function testCustomHeaders(): void
    {
        $id = self::connector('phpunit_header', self::HTTPBIN, 'GET', 'headers');
        self::$admin->saveCredential($id, 0, 'header', [], ['X-Test-Auth' => self::HEADER]);

        $r = $this->call(self::$client, 'phpunit_header');
        $this->assertTrue($r->ok, 'header: call succeeds');
        $this->assertSame('***', $r->data['headers']['X-Test-Auth'] ?? '', 'header: reached the API and is masked');
    }

    public function testJwtLoginCacheAndLogout(): void
    {
        $id = self::connector('phpunit_jwt', self::DUMMYJSON, 'GET', 'auth/me');
        self::$admin->saveCredential($id, 0, 'jwt', $this->jwtConfig(), ['password' => self::JWT_PASSWORD]);

        $r = $this->call(self::$client, 'phpunit_jwt');
        $this->assertTrue($r->ok && ($r->data['username'] ?? '') === 'emilys', 'jwt: logs in and the API accepts the token');

        $row = $this->tokenRow($id);
        $this->assertNotEmpty($row['token_cipher'], 'jwt: token cached');
        $this->assertStringNotContainsString('eyJ', (string)$row['token_cipher'], 'jwt: cached token is encrypted');
        $ttl = strtotime($row['token_expires_at']) - time();
        $this->assertTrue($ttl > 3000 && $ttl <= 3700, 'jwt: expiry comes from the exp claim, about 1 hour ahead');

        $r = $this->call(self::$client, 'phpunit_jwt');
        $this->assertTrue($r->ok, 'jwt: second call succeeds');
        $this->assertSame($row['token_cipher'], $this->tokenRow($id)['token_cipher'], 'jwt: second call reuses the token, no new login');

        // dummyjson has no logout URL, the same as integrations with login and expiry only
        $r = self::$client->logout('phpunit_jwt');
        $this->assertTrue($r->ok, 'jwt logout: no logout_path is fine');
        $this->assertEmpty($this->tokenRow($id)['token_cipher'], 'jwt logout: clears the cache');
    }

    public function testJwtWrongPassword(): void
    {
        $id = self::connector('phpunit_jwt_bad', self::DUMMYJSON, 'GET', 'auth/me');
        self::$admin->saveCredential($id, 0, 'jwt', $this->jwtConfig(), ['password' => 'PHPUNIT-WRONG-PASS-2']);

        $r = $this->call(self::$client, 'phpunit_jwt_bad');
        $this->assertFalse($r->ok, 'jwt: wrong password fails the call');
        $this->assertEmpty($this->tokenRow($id)['token_cipher'], 'jwt: wrong password caches nothing');
    }

    public function testCustomerJwtLogin(): void
    {
        $id = self::connector('phpunit_jwt_tenant', self::DUMMYJSON, 'GET', 'auth/me', [
            'tenant_configurable' => 1,
            'tenant_auth_type' => 'jwt',
            'tenant_auth_config' => ['login_path' => 'auth/login', 'token_path' => 'accessToken']
        ]);

        $tenant = new ConnectorRepository(self::$db, 7, 2, 'ADMIN');
        $tenant->saveTenantLogin($id, ['username' => 'emilys', 'password' => self::JWT_PASSWORD]);

        $r = $this->call(new ConnectorClient($tenant), 'phpunit_jwt_tenant');
        $this->assertTrue($r->ok && ($r->data['username'] ?? '') === 'emilys', 'customer jwt: vendor preset plus customer login works on a real API');
    }

    /**
     * Runs last, over every call the tests above logged
     */
    public function testSecretsNotInLogs(): void
    {
        if (!self::$connectorIds) {
            $this->markTestSkipped('Only meaningful when the whole class runs');
        }

        $ids = implode(',', array_map('intval', self::$connectorIds));
        $logs = self::$db->query("SELECT CONCAT_WS(' ', error_message, request_body, response_body) AS text FROM api_call_logs WHERE connector_id IN ($ids)")->fetch_all(MYSQLI_ASSOC);

        $this->assertNotEmpty($logs, 'calls were logged');
        foreach ($logs as $log) {
            // eyJ is how every JWT starts
            foreach ([self::API_KEY, self::BEARER, self::BASIC_PASSWORD, self::HEADER, self::JWT_PASSWORD, 'eyJ'] as $secret) {
                $this->assertStringNotContainsString($secret, (string)$log['text'], 'no secret in api_call_logs');
            }
        }
    }
}
