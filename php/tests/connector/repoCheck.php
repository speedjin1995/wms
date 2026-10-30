<?php
// ConnectorRepository CRUD check. Needs the deploy tables. Run: php php/tests/connector/repoCheck.php
if (PHP_SAPI !== 'cli') {
    exit;
}

require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../bootstrap.php';

use App\Integrations\Connector\ConnectorRepository;
use App\Integrations\Connector\SecretStore;

$failed = 0;

function check(string $name, bool $ok): void
{
    global $failed;
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) {
        $failed++;
    }
}

function throwsOn(callable $fn, string $class = \Throwable::class): bool
{
    try {
        $fn();
    } catch (\Throwable $e) {
        return $e instanceof $class;
    }

    return false;
}

$admin = new ConnectorRepository($db, 1, 1, 'SADMIN');
$tenant = new ConnectorRepository($db, 7, 2, 'ADMIN');
$secrets = new SecretStore();

// Connector CRUD
$connId = $admin->saveConnector(['code' => 'smoke_repo', 'name' => 'Repo check', 'protocol' => 'rest', 'base_url' => 'https://example.com', 'options' => ['allow_private' => false], 'tenant_configurable' => 1]);
check('saveConnector inserts', $connId > 0);
check('saveConnector updates', $admin->saveConnector(['name' => 'Renamed'], $connId) === $connId);
check('connector options stored as JSON', $admin->listConnectors() !== [] && in_array('{"allow_private":false}', array_column($admin->listConnectors(), 'options'), true));

// Operations and spec validation
$opId = $admin->saveOperation($connId, ['code' => 'item', 'name' => 'Item', 'http_method' => 'POST', 'path_template' => 'items/{id}', 'exposed' => 1,
    'request_spec' => ['path' => ['id' => '{{input.id}}'], 'body' => ['n' => '{{input.n | upper}}']],
    'response_spec' => ['extract' => 'data', 'map' => ['x' => '{{response.x}}']],
    'error_spec' => ['message' => 'error.detail']]);
check('saveOperation inserts with specs', $opId > 0);
check('valid specs stored', count($admin->listOperations($connId)) === 1);
check('unknown root in request_spec rejected', throwsOn(fn () => $admin->saveOperation($connId, ['code' => 'bad', 'name' => 'Bad', 'request_spec' => ['body' => '{{response.x}}']]), \UnexpectedValueException::class));
check('unknown transform in response_spec rejected', throwsOn(fn () => $admin->saveOperation($connId, ['code' => 'bad2', 'name' => 'Bad', 'response_spec' => ['then' => ['exec']]]), \UnexpectedValueException::class));
check('non-object JSON rejected', throwsOn(fn () => $admin->saveOperation($connId, ['code' => 'bad3', 'name' => 'Bad', 'options' => '"text"']), \UnexpectedValueException::class));

// Credentials: secret stored encrypted, never listed
$admin->saveCredential($connId, 0, 'api_key', ['in' => 'header', 'name' => 'x-api-key'], ['key' => 'REPO-SECRET-1']);
$list = $admin->listCredentials($connId);
check('listCredentials has hint, no secret column', count($list) === 1 && !array_key_exists('secret', $list[0]) && $list[0]['secret_hint'] === SecretStore::hint('REPO-SECRET-1'));
$admin->saveCredential($connId, 0, 'api_key', ['in' => 'header', 'name' => 'x-api-key'], null);
$stored = $admin->listCredentials($connId)[0];
check('null secret keeps stored hint', $stored['secret_hint'] === SecretStore::hint('REPO-SECRET-1'));

// Tenant side
check('tenant lists tenant_configurable connectors', in_array('smoke_repo', array_column($tenant->listTenantConnectors(), 'code'), true));
$tenant->saveTenantCredential($connId, 'bearer', [], ['token' => 'TENANT-TOKEN-22']);
$row = array_values(array_filter($tenant->listTenantConnectors(), fn ($r) => $r['code'] === 'smoke_repo'))[0];
check('tenant sees own credential hint', $row['auth_type'] === 'bearer' && $row['secret_hint'] === SecretStore::hint('TENANT-TOKEN-22'));
check('tenant cannot write connectors', throwsOn(fn () => $tenant->saveConnector(['name' => 'x'], $connId), \RuntimeException::class));
check('tenant cannot use admin listing', throwsOn(fn () => $tenant->listCredentials($connId), \RuntimeException::class));
$adminCompanyRow = $db->query("SELECT company FROM api_connector_credentials WHERE connector_id = $connId AND deleted = 0 ORDER BY company")->fetch_all(MYSQLI_ASSOC);
check('tenant credential stored under its company, not 0', count($adminCompanyRow) === 2 && (int)$adminCompanyRow[1]['company'] === 7);

// Non-tenant-configurable connector refuses tenant writes
$lockedId = $admin->saveConnector(['code' => 'smoke_locked', 'name' => 'Locked', 'protocol' => 'rest', 'base_url' => 'https://example.com', 'tenant_configurable' => 0]);
check('tenant write refused on locked connector', throwsOn(fn () => $tenant->saveTenantCredential($lockedId, 'none', [], null), \RuntimeException::class));

// Cascade soft delete
check('deleteConnector soft deletes', $admin->deleteConnector($connId) && $admin->deleteConnector($lockedId));
$left = $db->query("SELECT (SELECT COUNT(*) FROM api_connector_operations WHERE connector_id = $connId AND deleted = 0) + (SELECT COUNT(*) FROM api_connector_credentials WHERE connector_id = $connId AND deleted = 0) AS n")->fetch_assoc();
check('cascade left no active child rows', (int)$left['n'] === 0);

echo PHP_EOL . ($failed === 0 ? 'ALL PASSED' : "$failed FAILED") . PHP_EOL;
exit($failed === 0 ? 0 : 1);
