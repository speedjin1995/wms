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

    case 'setup':
        if (!in_array($_SESSION['role'] ?? '', ['ADMIN', 'SADMIN'], true)) {
            echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
            break;
        }
        echo json_encode(['status' => 'success', 'message' => 'Done', 'data' => $repo->listTenantSetup()]);
        break;

    case 'saveCredential':
    case 'deleteCredential':
        // Tenant admins manage only their own company's login; the secret is never echoed back
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

            // The login type is the vendor's; anything the browser sends as auth_type is ignored
            $fields = is_array($_POST['fields'] ?? null) ? $_POST['fields'] : [];
            $repo->saveTenantLogin($connectorId, $fields);
            echo json_encode(['status' => 'success', 'message' => 'Login saved']);
        } catch (\mysqli_sql_exception $e) {
            error_log('integrations ' . $action . ' - ' . $e->getMessage());
            echo json_encode(['status' => 'failed', 'message' => 'Unable to save credential']);
        } catch (\RuntimeException $e) {
            // Our own messages only, such as "Fill in: password"
            echo json_encode(['status' => 'failed', 'message' => $e->getMessage()]);
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
        // A file in the reply is not valid UTF-8 and would otherwise encode to an empty response
        echo json_encode($result->toArray(), JSON_INVALID_UTF8_SUBSTITUTE);
        break;

    default:
        echo json_encode(['status' => 'failed', 'message' => 'Invalid action']);
}
