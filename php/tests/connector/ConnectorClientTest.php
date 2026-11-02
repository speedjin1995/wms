<?php
if (PHP_SAPI !== 'cli') {
    exit;
}

use App\Integrations\Connector\ConnectorClient;
use App\Integrations\Connector\ConnectorRepository;
use App\Integrations\Connector\ErrorCode;
use App\Integrations\Connector\NormalizedResult;
use App\Integrations\Connector\SecretStore;
use PHPUnit\Framework\TestCase;

/**
 * Full calls against mockServer.php, which this test starts and stops. Needs the deploy tables.
 */
class ConnectorClientTest extends TestCase
{
    private const MOCK_HOST = '127.0.0.1';
    private const MOCK_PORT = 8099;
    private const API_KEY = 'KEY-SECRET-1234';
    private const BEARER = 'BEARER-SECRET-99';
    private const BASIC_PASSWORD = 'PASS-SECRET-77';
    private const HEADER = 'HDR-SECRET-555';
    private const JWT_PASSWORD = 'JWT-PASS-SECRET-1';
    private const WRONG_PASSWORD = 'WRONG-PASS-SECRET';

    /** @var resource|null */
    private static $server = null;
    private static mysqli $db;
    private static SecretStore $secrets;
    private static ConnectorClient $client;
    private static array $connIds = [];
    private static array $opIds = [];
    private static array $credIds = [];
    /** @var NormalizedResult[] checked for leaked secrets by the last test */
    private static array $results = [];

    public static function setUpBeforeClass(): void
    {
        self::startMockServer();

        self::$db = connectorTestDb();
        self::$secrets = new SecretStore();
        self::$client = new ConnectorClient(new ConnectorRepository(self::$db, 1, 1, 'SADMIN'), self::$secrets);

        // Leftovers from an earlier failed run
        self::$db->query("UPDATE api_connectors SET deleted = 1, deleted_at = NOW() WHERE code LIKE 'smoke\\_%' AND deleted = 0");

        // Plain connector, no auth
        $none = self::addConnector('smoke_none', true);
        self::addCredential($none, 'none', [], []);
        self::addOperation($none, 'echo', 'GET', 'echo', 1);
        self::addOperation($none, 'echo_post', 'POST', 'echo', 0);
        self::addOperation($none, 'status401', 'GET', 'status/401', 1);
        self::addOperation($none, 'status429', 'GET', 'status/429', 1);
        self::addOperation($none, 'status404', 'GET', 'status/404', 1);
        self::addOperation($none, 'status503', 'GET', 'status/503', 1);
        self::addOperation($none, 'slow', 'GET', 'slow', 1, 1000);
        self::addOperation($none, 'redirect', 'GET', 'redirect', 1);
        self::addOperation($none, 'utf16', 'GET', 'utf16', 1);
        self::addOperation($none, 'binary', 'GET', 'binary', 1);
        self::addOperation($none, 'large', 'GET', 'large', 1);

        // Connector with no credential row
        $noCred = self::addConnector('smoke_nocred', true);
        self::addOperation($noCred, 'echo', 'GET', 'echo', 1);

        // Connector that blocks private IPs
        $private = self::addConnector('smoke_private', false);
        self::addCredential($private, 'none', [], []);
        self::addOperation($private, 'echo', 'GET', 'echo', 1);

        // One connector per auth type
        $apiKeyHeader = self::addConnector('smoke_apikey_h', true);
        self::addCredential($apiKeyHeader, 'api_key', ['in' => 'header', 'name' => 'x-api-key'], ['key' => self::API_KEY]);
        self::addOperation($apiKeyHeader, 'echo', 'GET', 'echo', 1);

        $apiKeyQuery = self::addConnector('smoke_apikey_q', true);
        self::addCredential($apiKeyQuery, 'api_key', ['in' => 'query', 'name' => 'key'], ['key' => self::API_KEY]);
        self::addOperation($apiKeyQuery, 'echo', 'GET', 'echo', 1);

        $bearer = self::addConnector('smoke_bearer', true);
        self::addCredential($bearer, 'bearer', [], ['token' => self::BEARER]);
        self::addOperation($bearer, 'echo', 'GET', 'echo', 1);

        $basic = self::addConnector('smoke_basic', true);
        self::addCredential($basic, 'basic', ['username' => 'alice'], ['password' => self::BASIC_PASSWORD]);
        self::addOperation($basic, 'echo', 'GET', 'echo', 1);

        $header = self::addConnector('smoke_header', true);
        self::addCredential($header, 'header', [], ['X-Header-A' => self::HEADER]);
        self::addOperation($header, 'echo', 'GET', 'echo', 1);
    }

    public static function tearDownAfterClass(): void
    {
        // Soft delete only
        self::softDeleteIds('api_connector_credentials', self::$credIds);
        self::softDeleteIds('api_connector_operations', self::$opIds);
        self::softDeleteIds('api_connectors', self::$connIds);

        if (self::$server) {
            proc_terminate(self::$server);
            proc_close(self::$server);
            self::$server = null;
        }
    }

    private static function startMockServer(): void
    {
        $busy = @fsockopen(self::MOCK_HOST, self::MOCK_PORT, $errno, $error, 0.2);
        if ($busy) {
            fclose($busy);
            throw new RuntimeException('Port ' . self::MOCK_PORT . ' is already in use. Stop whatever is running on it, such as an old mock server.');
        }

        $null = DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null';
        self::$server = proc_open(
            [PHP_BINARY, '-S', self::MOCK_HOST . ':' . self::MOCK_PORT, __DIR__ . '/mockServer.php'],
            [0 => ['file', $null, 'r'], 1 => ['file', $null, 'w'], 2 => ['file', $null, 'w']],
            $pipes
        );

        for ($i = 0; $i < 50; $i++) {
            $socket = @fsockopen(self::MOCK_HOST, self::MOCK_PORT, $errno, $error, 0.2);
            if ($socket) {
                fclose($socket);
                return;
            }
            usleep(100000);
        }

        throw new RuntimeException('Mock server did not start');
    }

    private static function insertRow(string $sql, string $types, array $params): int
    {
        $stmt = self::$db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $id = self::$db->insert_id;
        $stmt->close();

        return $id;
    }

    private static function addConnector(string $code, bool $allowPrivate): int
    {
        $options = json_encode(['allow_private' => $allowPrivate]);
        $id = self::insertRow("INSERT INTO api_connectors (code, name, protocol, base_url, timeout_ms, connect_timeout_ms, allow_http, options, created_by) VALUES (?, ?, 'rest', 'http://127.0.0.1:8099', 5000, 2000, 1, ?, 1)", 'sss', [$code, $code, $options]);
        self::$connIds[] = $id;

        return $id;
    }

    private static function addOperation(int $connId, string $code, string $method, string $path, int $exposed, ?int $timeoutMs = null, array $specs = []): void
    {
        self::$opIds[] = self::insertRow('INSERT INTO api_connector_operations (connector_id, code, name, http_method, path_template, timeout_ms, exposed, request_spec, response_spec, error_spec, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)', 'issssiisss', [$connId, $code, $code, $method, $path, $timeoutMs, $exposed, $specs['request'] ?? null, $specs['response'] ?? null, $specs['error'] ?? null]);
    }

    private static function addCredential(int $connId, string $authType, array $config, array $secret): void
    {
        $enc = $secret ? self::$secrets->encrypt($secret) : ['cipher' => null, 'version' => null];
        $hint = $secret ? SecretStore::hint((string)reset($secret)) : null;
        self::$credIds[] = self::insertRow('INSERT INTO api_connector_credentials (connector_id, company, auth_type, auth_config, secret, key_version, secret_hint, created_by) VALUES (?, 0, ?, ?, ?, ?, ?, 1)', 'isssis', [$connId, $authType, json_encode($config), $enc['cipher'], $enc['version'], $hint]);
    }

    private static function softDeleteIds(string $table, array $ids): void
    {
        if (!$ids) {
            return;
        }
        $stmt = self::$db->prepare("UPDATE $table SET deleted = 1, deleted_at = NOW() WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ')');
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $stmt->close();
    }

    private static function mockJwt(string $path): array
    {
        return json_decode((string)file_get_contents('http://127.0.0.1:8099/jwt/' . $path), true) ?: [];
    }

    private static function jwtRow(int $connId): array
    {
        $stmt = self::$db->prepare('SELECT id, token_cipher, token_expires_at FROM api_connector_credentials WHERE connector_id = ? AND deleted = 0');
        $stmt->bind_param('i', $connId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: [];
    }

    private static function addJwtConnector(string $code, string $loginVariant, array $config = [], string $password = self::JWT_PASSWORD): int
    {
        $connId = self::addConnector($code, true);
        self::addCredential($connId, 'jwt', $config + ['login_path' => 'jwt/login/' . $loginVariant, 'token_path' => 'data.token', 'username' => 'alice', 'username_field' => 'user', 'password_field' => 'pass'], ['password' => $password]);
        self::addOperation($connId, 'data', 'GET', 'jwt/data', 1);

        return $connId;
    }

    private function call(string $connector, string $operation, array $payload = [], array $ctx = []): NormalizedResult
    {
        $result = self::$client->execute($connector, $operation, $payload, $ctx);
        self::$results[] = $result;

        return $result;
    }

    public function testAuthTypes(): void
    {
        $r = self::$client->execute('smoke_none', 'echo');
        $this->assertTrue($r->ok && ($r->data['method'] ?? '') === 'GET', 'none: GET succeeds');

        $r = $this->call('smoke_apikey_h', 'echo');
        $this->assertTrue($r->ok && ($r->data['headers']['x-api-key'] ?? '') === '***', 'api_key header: sent and masked');

        $r = $this->call('smoke_apikey_q', 'echo');
        $this->assertTrue($r->ok && ($r->data['query']['key'] ?? '') === '***', 'api_key query: sent and masked');

        $r = $this->call('smoke_bearer', 'echo');
        $this->assertTrue($r->ok && isset($r->data['headers']['authorization']) && $r->data['headers']['authorization'] === '***', 'bearer: authorization sent and masked');

        $r = $this->call('smoke_basic', 'echo');
        $this->assertTrue($r->ok && isset($r->data['headers']['authorization']) && $r->data['headers']['authorization'] === '***', 'basic: authorization sent and masked');

        $r = $this->call('smoke_header', 'echo');
        $this->assertTrue($r->ok && ($r->data['headers']['x-header-a'] ?? '') === '***', 'header: sent and masked');
    }

    public function testBodyAndMethodPassThrough(): void
    {
        $r = self::$client->execute('smoke_none', 'echo_post', ['a' => 1]);
        $this->assertTrue($r->ok && ($r->data['method'] ?? '') === 'POST' && ($r->data['body'] ?? '') === '{"a":1}', 'POST sends JSON body');
    }

    public function testRepliesThatAreNotUtf8(): void
    {
        $r = $this->call('smoke_none', 'utf16');
        $this->assertTrue($r->ok && ($r->data['doc'] ?? '') === "caf\u{e9}", 'utf-16 reply: decoded as JSON');

        $r = $this->call('smoke_none', 'binary');
        $this->assertTrue($r->ok && strpos((string)$r->data, '%PDF') === 0, 'file reply: bytes reach the caller');
        $this->assertNotFalse(json_encode($r->toArray(), JSON_INVALID_UTF8_SUBSTITUTE), 'file reply: still encodes for the response');

        $stmt = self::$db->prepare('SELECT response_body FROM api_call_logs WHERE call_id = ?');
        $stmt->bind_param('s', $r->callId);
        $stmt->execute();
        $log = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $this->assertStringStartsWith('%PDF', (string)($log['response_body'] ?? ''), 'file reply: log row is saved');
    }

    public function testLargeReplyIsLoggedInFull(): void
    {
        $r = $this->call('smoke_none', 'large');
        $this->assertTrue($r->ok && count($r->data['items'] ?? []) === 2000, 'large reply: reaches the caller');

        $stmt = self::$db->prepare('SELECT response_body FROM api_call_logs WHERE call_id = ?');
        $stmt->bind_param('s', $r->callId);
        $stmt->execute();
        $log = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $this->assertCount(2000, json_decode((string)($log['response_body'] ?? ''), true)['items'] ?? [], 'large reply: log keeps complete JSON');
    }

    public function testUpstreamErrors(): void
    {
        $cases = [
            'status401' => ErrorCode::AUTH_FAILED,
            'status429' => ErrorCode::RATE_LIMITED,
            'status404' => ErrorCode::CLIENT_ERROR,
            'status503' => ErrorCode::UPSTREAM_ERROR,
        ];
        foreach ($cases as $op => $expected) {
            $r = $this->call('smoke_none', $op);
            $this->assertTrue(!$r->ok && $r->errorCode === $expected, "upstream $op -> $expected");
        }

        $r = $this->call('smoke_none', 'slow');
        $this->assertTrue(!$r->ok && $r->errorCode === 'TIMEOUT', 'slow upstream -> TIMEOUT');

        $r = self::$client->execute('smoke_none', 'redirect');
        $this->assertSame(302, $r->httpStatus, 'redirect not followed');
    }

    public function testRoutingAndConfigErrors(): void
    {
        $r = self::$client->execute('smoke_none', 'echo_post', ['a' => 1], ['exposed_only' => true]);
        $this->assertSame('NOT_EXPOSED', $r->errorCode, 'exposed_only blocks unexposed op -> NOT_EXPOSED');

        $r = self::$client->execute('smoke_none', 'no_such_op');
        $this->assertSame('CONFIG_NOT_FOUND', $r->errorCode, 'unknown op -> CONFIG_NOT_FOUND');

        $r = self::$client->execute('smoke_missing', 'echo');
        $this->assertSame('CONFIG_NOT_FOUND', $r->errorCode, 'unknown connector -> CONFIG_NOT_FOUND');

        $r = self::$client->execute('smoke_nocred', 'echo');
        $this->assertSame('CREDENTIALS_MISSING', $r->errorCode, 'no credential row -> CREDENTIALS_MISSING');

        $r = self::$client->execute('smoke_private', 'echo');
        $this->assertSame('URL_BLOCKED', $r->errorCode, 'private IP blocked -> URL_BLOCKED');
    }

    public function testMapping(): void
    {
        $mapConn = self::addConnector('smoke_map', true);
        self::addCredential($mapConn, 'api_key', ['in' => 'header', 'name' => 'x-api-key'], ['key' => self::API_KEY]);
        self::addOperation($mapConn, 'item', 'POST', 'echo/{id}', 1, null, [
            'request' => json_encode(['path' => ['id' => '{{input.id}}'], 'query' => ['v' => '{{config.allow_private}}'], 'headers' => ['X-Trace' => '{{input.trace}}'], 'body' => ['name' => '{{input.name | upper}}', 'tags' => ['$each' => 'input.tags', 'as' => 't', 'template' => '{{t}}']]]),
            'response' => json_encode(['extract' => 'body', 'then' => ['json_decode'], 'map' => ['n' => '{{response.name}}']]),
        ]);
        self::addOperation($mapConn, 'fail', 'GET', 'status/401', 1, null, ['error' => json_encode(['message' => 'error.detail'])]);
        self::addOperation($mapConn, 'badpath', 'POST', 'echo/{missing}', 1, null, ['request' => json_encode(['body' => []])]);

        $payload = ['id' => 42, 'trace' => 'abc', 'name' => 'alice', 'tags' => ['x', 'y']];

        $r = $this->call('smoke_map', 'item', $payload);
        $this->assertTrue($r->ok && ($r->data['n'] ?? '') === 'ALICE', 'mapped request and response');

        $r = $this->call('smoke_map', 'item', $payload, ['preview' => true]);
        $this->assertTrue($r->ok && str_ends_with($r->data['url'], '/echo/42?v=1') && ($r->data['headers']['X-Trace'] ?? '') === 'abc' && ($r->data['body'] ?? '') === '{"name":"ALICE","tags":["x","y"]}', 'preview: mapped url, headers and body');
        $this->assertSame('***', $r->data['headers']['x-api-key'] ?? '', 'preview: api_key masked');

        $r = $this->call('smoke_map', 'fail');
        $this->assertTrue($r->errorCode === 'AUTH_FAILED' && $r->message === 'status 401 ***', 'error_spec picks upstream message, redacted');

        $r = $this->call('smoke_map', 'badpath', $payload);
        $this->assertSame('MAPPING_ERROR', $r->errorCode, 'missing path value -> MAPPING_ERROR');
    }

    public function testJwtLoginCacheExpiryAndLogout(): void
    {
        self::mockJwt('reset');

        $jwt = self::addJwtConnector('smoke_jwt', 'plain', ['expires_path' => 'data.expires_at', 'ttl_s' => 1800, 'logout_path' => 'jwt/logout']);

        $r = $this->call('smoke_jwt', 'data', [], ['preview' => true]);
        $this->assertTrue($r->ok && (self::mockJwt('state')['logins'] ?? 0) === 0 && ($r->data['headers']['Authorization'] ?? '') === '***', 'jwt preview: no login, header masked');

        $r = $this->call('smoke_jwt', 'data');
        $this->assertTrue($r->ok && self::mockJwt('state')['logins'] === 1 && $r->attempts === 1, 'jwt: first call logs in');
        $this->assertSame('***', $r->data['token_echo'] ?? '', 'jwt: token masked in result');

        $row = self::jwtRow($jwt);
        $this->assertTrue(!empty($row['token_cipher']) && strpos($row['token_cipher'], 'JWT-TOKEN-') === false, 'jwt: cached token is encrypted');
        $ttl = strtotime($row['token_expires_at']) - time();
        $this->assertTrue($ttl > 1700 && $ttl <= 1800, 'jwt expiry: ttl_s used when nothing else');

        $r = $this->call('smoke_jwt', 'data');
        $this->assertTrue($r->ok && self::mockJwt('state')['logins'] === 1, 'jwt: second call reuses the token');

        self::$db->query('UPDATE api_connector_credentials SET token_expires_at = DATE_SUB(NOW(), INTERVAL 1 HOUR) WHERE id = ' . (int)$row['id']);
        $r = $this->call('smoke_jwt', 'data');
        $this->assertTrue($r->ok && self::mockJwt('state')['logins'] === 2, 'jwt: expired token logs in again');

        self::$db->query('UPDATE api_connector_credentials SET token_expires_at = DATE_ADD(NOW(), INTERVAL 30 SECOND) WHERE id = ' . (int)$row['id']);
        $r = $this->call('smoke_jwt', 'data');
        $this->assertTrue($r->ok && self::mockJwt('state')['logins'] === 3, 'jwt: token within 60 s of expiry logs in again');

        self::mockJwt('revoke');
        $r = $this->call('smoke_jwt', 'data');
        $this->assertTrue($r->ok && $r->attempts === 2 && self::mockJwt('state')['logins'] === 4, 'jwt: 401 logs in once and retries');

        $r = self::$client->logout('smoke_jwt');
        self::$results[] = $r;
        $this->assertTrue($r->ok && self::mockJwt('state')['logouts'] === 1, 'jwt logout: calls logout_path');
        $this->assertEmpty(self::jwtRow($jwt)['token_cipher'], 'jwt logout: clears the cache');

        $r = self::$client->logout('smoke_jwt');
        $this->assertTrue($r->ok && self::mockJwt('state')['logouts'] === 1, 'jwt logout: no token, no request');

        $r = $this->call('smoke_jwt', 'data');
        $this->assertTrue($r->ok && self::mockJwt('state')['logins'] === 5, 'jwt: logs in again after logout');

        // No logout_path: integrations with login and expiry only
        $jwtNoLogout = self::addJwtConnector('smoke_jwt_nologout', 'seconds', ['expires_path' => 'data.expires_at']);
        $r = $this->call('smoke_jwt_nologout', 'data');
        $ttl = strtotime(self::jwtRow($jwtNoLogout)['token_expires_at']) - time();
        $this->assertTrue($r->ok && $ttl > 7100 && $ttl <= 7200, 'jwt expiry: seconds at expires_path');
        $before = self::mockJwt('state')['logouts'];
        $r = self::$client->logout('smoke_jwt_nologout');
        $this->assertTrue($r->ok && self::mockJwt('state')['logouts'] === $before && empty(self::jwtRow($jwtNoLogout)['token_cipher']), 'jwt logout: no logout_path, no request, cache cleared');

        $jwtDate = self::addJwtConnector('smoke_jwt_date', 'date', ['expires_path' => 'data.expires_at']);
        $r = $this->call('smoke_jwt_date', 'data');
        $this->assertTrue($r->ok && self::jwtRow($jwtDate)['token_expires_at'] === '2099-01-02 03:04:05', 'jwt expiry: date at expires_path');

        $jwtExp = self::addJwtConnector('smoke_jwt_exp', 'exp');
        $r = $this->call('smoke_jwt_exp', 'data');
        $this->assertTrue($r->ok && strtotime(self::jwtRow($jwtExp)['token_expires_at']) === 4070908800, 'jwt expiry: exp claim of the token');

        // Login failures
        self::addJwtConnector('smoke_jwt_badpass', 'plain', [], self::WRONG_PASSWORD);
        $r = $this->call('smoke_jwt_badpass', 'data');
        $this->assertSame('AUTH_FAILED', $r->errorCode, 'jwt: wrong password -> AUTH_FAILED');

        self::addJwtConnector('smoke_jwt_notoken', 'notoken');
        $r = $this->call('smoke_jwt_notoken', 'data');
        $this->assertSame('AUTH_FAILED', $r->errorCode, 'jwt: no token in reply -> AUTH_FAILED');

        $jwtNoConfig = self::addConnector('smoke_jwt_noconfig', true);
        self::addCredential($jwtNoConfig, 'jwt', [], ['password' => self::JWT_PASSWORD]);
        self::addOperation($jwtNoConfig, 'data', 'GET', 'jwt/data', 1);
        $r = self::$client->execute('smoke_jwt_noconfig', 'data');
        $this->assertSame('CREDENTIALS_MISSING', $r->errorCode, 'jwt: missing config -> CREDENTIALS_MISSING');

        $r = self::$client->logout('smoke_bearer');
        $this->assertSame('AUTH_UNSUPPORTED', $r->errorCode, 'logout on a non-jwt connector -> AUTH_UNSUPPORTED');

        // A second 401 ends the call, with no login loop
        $logins = self::mockJwt('state')['logins'];
        self::mockJwt('refuse_all');
        self::$db->query('UPDATE api_connector_credentials SET token_cipher = NULL WHERE connector_id = ' . (int)$jwt);
        $r = $this->call('smoke_jwt', 'data');
        $this->assertTrue($r->errorCode === 'AUTH_FAILED' && $r->attempts === 2 && self::mockJwt('state')['logins'] === $logins + 2, 'jwt: second 401 -> AUTH_FAILED, no loop');

        // Saving a credential forgets the cached token
        $repo = new ConnectorRepository(self::$db, 1, 1, 'SADMIN');
        $cachedBefore = !empty(self::jwtRow($jwt)['token_cipher']);
        $repo->saveCredential($jwt, 0, 'jwt', ['login_path' => 'jwt/login/plain', 'token_path' => 'data.token', 'username' => 'alice', 'username_field' => 'user', 'password_field' => 'pass'], null);
        $this->assertTrue($cachedBefore && empty(self::jwtRow($jwt)['token_cipher']), 'jwt: saving the credential clears the token');
        self::mockJwt('reset');
    }

    /**
     * Runs last, over every result the tests above collected
     */
    public function testSecretsNeverLeak(): void
    {
        if (!self::$results) {
            $this->markTestSkipped('Only meaningful when the whole class runs');
        }

        $secrets = [self::API_KEY, self::BEARER, self::BASIC_PASSWORD, self::HEADER, base64_encode('alice:' . self::BASIC_PASSWORD), self::JWT_PASSWORD, self::WRONG_PASSWORD, 'JWT-TOKEN-'];

        // Secrets must not appear in any returned result
        foreach (self::$results as $res) {
            $json = json_encode($res->toArray());
            foreach ($secrets as $secret) {
                $this->assertStringNotContainsString($secret, $json, 'no secret in result: ' . substr($secret, 0, 6));
            }
        }

        // Secrets must not appear in call logs
        $ids = self::$connIds;
        $stmt = self::$db->prepare('SELECT CONCAT_WS(" ", error_message, request_body, response_body) AS error_message FROM api_call_logs WHERE connector_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $this->assertNotEmpty($logs, 'calls were logged');
        foreach ($logs as $log) {
            foreach ($secrets as $secret) {
                $this->assertStringNotContainsString($secret, (string)$log['error_message'], 'no secret in api_call_logs');
            }
        }
    }
}
