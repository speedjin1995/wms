<?php
// Loaded by phpunit.xml. Run: php tools/phpunit.phar -c php/tests/connector/phpunit.xml
if (PHP_SAPI !== 'cli') {
    exit;
}

require_once __DIR__ . '/../../bootstrap.php';

// The connector logs every failed call, which the tests cause on purpose
ini_set('error_log', sys_get_temp_dir() . '/wms_connector_phpunit.log');

/**
 * The tests write rows, so they refuse any database that is not on this machine.
 * shortcut: can't tell a Hostinger server's own localhost apart, so tools/ must never be deployed.
 */
function connectorTestDb(): mysqli
{
    static $db = null;

    if ($db === null) {
        require __DIR__ . '/../../db_connect.php';
        if (!preg_match('/^(localhost|127\.0\.0\.1|::1)\b/i', (string)$db->host_info)) {
            throw new RuntimeException('Connector tests only run on a local database, php/db_connect.php points at: ' . $db->host_info);
        }
    }

    return $db;
}
