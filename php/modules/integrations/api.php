<?php
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../bootstrap.php';

use App\Integrations\Connector\ConnectorClient;
use App\Integrations\Connector\ConnectorRepository;

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

if (($_SESSION['featureFlags']['include_api_integration'] ?? 'N') != 'Y') {
    echo json_encode(['status' => 'failed', 'message' => 'API integration is not enabled']);
    exit;
}

$repo = new ConnectorRepository($db, (int)$_SESSION['customer'], (int)$_SESSION['userID'], (string)$_SESSION['role']);
$client = new ConnectorClient($repo);
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        echo json_encode(['status' => 'success', 'message' => 'Done', 'data' => $repo->listTenantConnectors()]);
        break;

    case 'operations':
        echo json_encode(['status' => 'success', 'message' => 'Done', 'data' => $repo->listTenantOperations((string)($_POST['connector'] ?? ''))]);
        break;

    case 'saveCredential':
    case 'deleteCredential':
        // Tenant admins manage only their own company's key; the secret is never echoed back
        if (!in_array($_SESSION['role'] ?? '', ['ADMIN', 'SADMIN'], true)) {
            echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
            break;
        }

        try {
            $connectorId = (int)($_POST['connector_id'] ?? 0);
            if ($action === 'deleteCredential') {
                $repo->deleteTenantCredential($connectorId);
                echo json_encode(['status' => 'success', 'message' => 'Credential deleted']);
                break;
            }

            $authType = (string)($_POST['auth_type'] ?? '');
            $key = trim((string)($_POST['key'] ?? ''));
            if (!in_array($authType, ['bearer', 'api_key'], true) || $key === '') {
                echo json_encode(['status' => 'failed', 'message' => 'Key is required']);
                break;
            }

            $secret = $authType === 'bearer' ? ['token' => $key] : ['key' => $key];
            $headerName = (string)($_POST['header_name'] ?? 'x-api-key');
            if (!preg_match('/^[A-Za-z0-9-]{1,50}$/', $headerName)) {
                echo json_encode(['status' => 'failed', 'message' => 'Invalid header name']);
                break;
            }
            $authConfig = $authType === 'api_key' ? ['in' => 'header', 'name' => $headerName] : [];
            $repo->saveTenantCredential($connectorId, $authType, $authConfig, $secret);
            echo json_encode(['status' => 'success', 'message' => 'Credential saved']);
        } catch (\Throwable $e) {
            error_log('integrations ' . $action . ' - ' . $e->getMessage());
            echo json_encode(['status' => 'failed', 'message' => 'Unable to save credential']);
        }
        break;

    case 'execute':
    case 'preview':
        $payload = json_decode($_POST['payload'] ?? '{}', true);
        if (!is_array($payload)) {
            echo json_encode(['status' => 'failed', 'message' => 'Payload must be a JSON object']);
            break;
        }

        $ctx = ['exposed_only' => true, 'preview' => $action === 'preview'];
        $result = $client->execute((string)($_POST['connector'] ?? ''), (string)($_POST['operation'] ?? ''), $payload, $ctx);
        echo json_encode($result->toArray());
        break;

    default:
        echo json_encode(['status' => 'failed', 'message' => 'Invalid action']);
}
