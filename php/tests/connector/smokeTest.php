<?php
// M1 smoke test. Needs the mock server (php -S 127.0.0.1:8099 php/tests/connector/mockServer.php) and the deploy tables.
// Run: php php/tests/connector/smokeTest.php
if (PHP_SAPI !== 'cli') {
    exit;
}

require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../bootstrap.php';

use App\Integrations\Connector\ConnectorClient;
use App\Integrations\Connector\ConnectorRepository;
use App\Integrations\Connector\ErrorCode;
use App\Integrations\Connector\SecretStore;

$base = 'http://127.0.0.1:8099';
$secrets = new SecretStore();
$client = new ConnectorClient(new ConnectorRepository($db, 1, 1, 'SADMIN'), $secrets);

$failed = 0;
$connIds = [];
$opIds = [];
$credIds = [];

function check(string $name, bool $ok): void
{
    global $failed;
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) {
        $failed++;
    }
}

function insertRow(mysqli $db, string $sql, string $types, array $params): int
{
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $id = $db->insert_id;
    $stmt->close();

    return $id;
}

function addConnector(mysqli $db, array &$connIds, string $code, bool $allowPrivate): int
{
    $options = json_encode(['allow_private' => $allowPrivate]);
    $id = insertRow($db, "INSERT INTO api_connectors (code, name, protocol, base_url, timeout_ms, connect_timeout_ms, allow_http, options, created_by) VALUES (?, ?, 'rest', 'http://127.0.0.1:8099', 5000, 2000, 1, ?, 1)", 'sss', [$code, $code, $options]);
    $connIds[] = $id;

    return $id;
}

function addOperation(mysqli $db, array &$opIds, int $connId, string $code, string $method, string $path, int $exposed, ?int $timeoutMs = null, array $specs = []): void
{
    $opIds[] = insertRow($db, 'INSERT INTO api_connector_operations (connector_id, code, name, http_method, path_template, timeout_ms, exposed, request_spec, response_spec, error_spec, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)', 'issssiisss', [$connId, $code, $code, $method, $path, $timeoutMs, $exposed, $specs['request'] ?? null, $specs['response'] ?? null, $specs['error'] ?? null]);
}

function addCredential(mysqli $db, SecretStore $secrets, array &$credIds, int $connId, string $authType, array $config, array $secret): void
{
    $enc = $secret ? $secrets->encrypt($secret) : ['cipher' => null, 'version' => null];
    $hint = $secret ? SecretStore::hint((string)reset($secret)) : null;
    $credIds[] = insertRow($db, 'INSERT INTO api_connector_credentials (connector_id, company, auth_type, auth_config, secret, key_version, secret_hint, created_by) VALUES (?, 0, ?, ?, ?, ?, ?, 1)', 'isssis', [$connId, $authType, json_encode($config), $enc['cipher'], $enc['version'], $hint]);
}

function softDeleteIds(mysqli $db, string $table, array $ids): void
{
    if (!$ids) {
        return;
    }
    $stmt = $db->prepare("UPDATE $table SET deleted = 1, deleted_at = NOW() WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ')');
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $stmt->close();
}

// Leftovers from an earlier failed run
$db->query("UPDATE api_connectors SET deleted = 1, deleted_at = NOW() WHERE code LIKE 'smoke\\_%' AND deleted = 0");

$apiKeySecret = 'KEY-SECRET-1234';
$bearerSecret = 'BEARER-SECRET-99';
$basicPassword = 'PASS-SECRET-77';
$headerSecret = 'HDR-SECRET-555';
$allSecrets = [$apiKeySecret, $bearerSecret, $basicPassword, $headerSecret, base64_encode('alice:' . $basicPassword)];

// Plain connector, no auth
$none = addConnector($db, $connIds, 'smoke_none', true);
addCredential($db, $secrets, $credIds, $none, 'none', [], []);
addOperation($db, $opIds, $none, 'echo', 'GET', 'echo', 1);
addOperation($db, $opIds, $none, 'echo_post', 'POST', 'echo', 0);
addOperation($db, $opIds, $none, 'status401', 'GET', 'status/401', 1);
addOperation($db, $opIds, $none, 'status429', 'GET', 'status/429', 1);
addOperation($db, $opIds, $none, 'status404', 'GET', 'status/404', 1);
addOperation($db, $opIds, $none, 'status503', 'GET', 'status/503', 1);
addOperation($db, $opIds, $none, 'slow', 'GET', 'slow', 1, 1000);
addOperation($db, $opIds, $none, 'redirect', 'GET', 'redirect', 1);

// Connector with no credential row
$noCred = addConnector($db, $connIds, 'smoke_nocred', true);
addOperation($db, $opIds, $noCred, 'echo', 'GET', 'echo', 1);

// Connector that blocks private IPs
$private = addConnector($db, $connIds, 'smoke_private', false);
addCredential($db, $secrets, $credIds, $private, 'none', [], []);
addOperation($db, $opIds, $private, 'echo', 'GET', 'echo', 1);

// One connector per auth type
$apiKeyHeader = addConnector($db, $connIds, 'smoke_apikey_h', true);
addCredential($db, $secrets, $credIds, $apiKeyHeader, 'api_key', ['in' => 'header', 'name' => 'x-api-key'], ['key' => $apiKeySecret]);
addOperation($db, $opIds, $apiKeyHeader, 'echo', 'GET', 'echo', 1);

$apiKeyQuery = addConnector($db, $connIds, 'smoke_apikey_q', true);
addCredential($db, $secrets, $credIds, $apiKeyQuery, 'api_key', ['in' => 'query', 'name' => 'key'], ['key' => $apiKeySecret]);
addOperation($db, $opIds, $apiKeyQuery, 'echo', 'GET', 'echo', 1);

$bearer = addConnector($db, $connIds, 'smoke_bearer', true);
addCredential($db, $secrets, $credIds, $bearer, 'bearer', [], ['token' => $bearerSecret]);
addOperation($db, $opIds, $bearer, 'echo', 'GET', 'echo', 1);

$basic = addConnector($db, $connIds, 'smoke_basic', true);
addCredential($db, $secrets, $credIds, $basic, 'basic', ['username' => 'alice'], ['password' => $basicPassword]);
addOperation($db, $opIds, $basic, 'echo', 'GET', 'echo', 1);

$header = addConnector($db, $connIds, 'smoke_header', true);
addCredential($db, $secrets, $credIds, $header, 'header', [], ['X-Header-A' => $headerSecret]);
addOperation($db, $opIds, $header, 'echo', 'GET', 'echo', 1);

$results = [];

// Auth: the request must carry the credential, and the echoed copy must be masked
$r = $client->execute('smoke_none', 'echo');
check('none: GET succeeds', $r->ok && ($r->data['method'] ?? '') === 'GET');

$r = $client->execute('smoke_apikey_h', 'echo');
$results[] = $r;
check('api_key header: sent and masked', $r->ok && ($r->data['headers']['x-api-key'] ?? '') === '***');

$r = $client->execute('smoke_apikey_q', 'echo');
$results[] = $r;
check('api_key query: sent and masked', $r->ok && ($r->data['query']['key'] ?? '') === '***');

$r = $client->execute('smoke_bearer', 'echo');
$results[] = $r;
check('bearer: authorization sent and masked', $r->ok && isset($r->data['headers']['authorization']) && $r->data['headers']['authorization'] === '***');

$r = $client->execute('smoke_basic', 'echo');
$results[] = $r;
check('basic: authorization sent and masked', $r->ok && isset($r->data['headers']['authorization']) && $r->data['headers']['authorization'] === '***');

$r = $client->execute('smoke_header', 'echo');
$results[] = $r;
check('header: sent and masked', $r->ok && ($r->data['headers']['x-header-a'] ?? '') === '***');

// Body and method pass through
$r = $client->execute('smoke_none', 'echo_post', ['a' => 1]);
check('POST sends JSON body', $r->ok && ($r->data['method'] ?? '') === 'POST' && ($r->data['body'] ?? '') === '{"a":1}');

// Upstream errors
$cases = [
    'status401' => ErrorCode::AUTH_FAILED,
    'status429' => ErrorCode::RATE_LIMITED,
    'status404' => ErrorCode::CLIENT_ERROR,
    'status503' => ErrorCode::UPSTREAM_ERROR,
];
foreach ($cases as $op => $expected) {
    $r = $client->execute('smoke_none', $op);
    $results[] = $r;
    check("upstream $op -> $expected", !$r->ok && $r->errorCode === $expected);
}

$r = $client->execute('smoke_none', 'slow');
$results[] = $r;
check('slow upstream -> TIMEOUT', !$r->ok && $r->errorCode === 'TIMEOUT');

$r = $client->execute('smoke_none', 'redirect');
check('redirect not followed', $r->httpStatus === 302);

// Routing and config errors
$r = $client->execute('smoke_none', 'echo_post', ['a' => 1], ['exposed_only' => true]);
check('exposed_only blocks unexposed op -> NOT_EXPOSED', $r->errorCode === 'NOT_EXPOSED');

$r = $client->execute('smoke_none', 'no_such_op');
check('unknown op -> CONFIG_NOT_FOUND', $r->errorCode === 'CONFIG_NOT_FOUND');

$r = $client->execute('smoke_missing', 'echo');
check('unknown connector -> CONFIG_NOT_FOUND', $r->errorCode === 'CONFIG_NOT_FOUND');

$r = $client->execute('smoke_nocred', 'echo');
check('no credential row -> CREDENTIALS_MISSING', $r->errorCode === 'CREDENTIALS_MISSING');

$r = $client->execute('smoke_private', 'echo');
check('private IP blocked -> URL_BLOCKED', $r->errorCode === 'URL_BLOCKED');

// Mapping: request_spec, response_spec, error_spec and preview
$mapConn = addConnector($db, $connIds, 'smoke_map', true);
addCredential($db, $secrets, $credIds, $mapConn, 'api_key', ['in' => 'header', 'name' => 'x-api-key'], ['key' => $apiKeySecret]);
addOperation($db, $opIds, $mapConn, 'item', 'POST', 'echo/{id}', 1, null, [
    'request' => json_encode(['path' => ['id' => '{{input.id}}'], 'query' => ['v' => '{{config.allow_private}}'], 'headers' => ['X-Trace' => '{{input.trace}}'], 'body' => ['name' => '{{input.name | upper}}', 'tags' => ['$each' => 'input.tags', 'as' => 't', 'template' => '{{t}}']]]),
    'response' => json_encode(['extract' => 'body', 'then' => ['json_decode'], 'map' => ['n' => '{{response.name}}']]),
]);
addOperation($db, $opIds, $mapConn, 'fail', 'GET', 'status/401', 1, null, ['error' => json_encode(['message' => 'error.detail'])]);
addOperation($db, $opIds, $mapConn, 'badpath', 'POST', 'echo/{missing}', 1, null, ['request' => json_encode(['body' => []])]);

$mapPayload = ['id' => 42, 'trace' => 'abc', 'name' => 'alice', 'tags' => ['x', 'y']];

$r = $client->execute('smoke_map', 'item', $mapPayload);
$results[] = $r;
check('mapped request and response', $r->ok && ($r->data['n'] ?? '') === 'ALICE');

$r = $client->execute('smoke_map', 'item', $mapPayload, ['preview' => true]);
$results[] = $r;
check('preview: mapped url, headers and body', $r->ok && str_ends_with($r->data['url'], '/echo/42?v=1') && ($r->data['headers']['X-Trace'] ?? '') === 'abc' && ($r->data['body'] ?? '') === '{"name":"ALICE","tags":["x","y"]}');
check('preview: api_key masked', ($r->data['headers']['x-api-key'] ?? '') === '***');

$r = $client->execute('smoke_map', 'fail');
$results[] = $r;
check('error_spec picks upstream message, redacted', $r->errorCode === 'AUTH_FAILED' && $r->message === 'status 401 ***');

$r = $client->execute('smoke_map', 'badpath', $mapPayload);
$results[] = $r;
check('missing path value -> MAPPING_ERROR', $r->errorCode === 'MAPPING_ERROR');

// Secrets must not appear in any returned result
foreach ($results as $res) {
    $json = json_encode($res->toArray());
    foreach ($allSecrets as $secret) {
        check('no secret in result: ' . substr($secret, 0, 6), strpos($json, $secret) === false);
    }
}

// Secrets must not appear in call logs
$logStmt = $db->prepare('SELECT error_message FROM api_call_logs WHERE connector_id IN (' . implode(',', array_fill(0, count($connIds), '?')) . ') AND error_message IS NOT NULL');
$logStmt->bind_param(str_repeat('i', count($connIds)), ...$connIds);
$logStmt->execute();
$logs = $logStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$logStmt->close();
foreach ($logs as $log) {
    foreach ($allSecrets as $secret) {
        check('no secret in api_call_logs', strpos($log['error_message'], $secret) === false);
    }
}

// Cleanup: soft delete only
softDeleteIds($db, 'api_connector_credentials', $credIds);
softDeleteIds($db, 'api_connector_operations', $opIds);
softDeleteIds($db, 'api_connectors', $connIds);

echo PHP_EOL . ($failed === 0 ? 'ALL PASSED' : "$failed FAILED") . PHP_EOL;
exit($failed === 0 ? 0 : 1);
