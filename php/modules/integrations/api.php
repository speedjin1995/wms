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
