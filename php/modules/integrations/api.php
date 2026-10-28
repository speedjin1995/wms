<?php
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\IntegrationController;
use App\Services\IntegrationService;

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

if (!in_array($_SESSION['role'] ?? '', ['ADMIN', 'SADMIN'], true)) {
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

if (($_SESSION['featureFlags']['include_do_ai'] ?? 'N') != 'Y') {
    echo json_encode(['status' => 'failed', 'message' => 'AI scan is not enabled']);
    exit;
}

$action = $_POST['action'] ?? '';
$controller = new IntegrationController(new IntegrationService(
    $db,
    (int)$_SESSION['customer'],
    (int)$_SESSION['userID'],
    (string)($_SESSION['role'] ?? '')
));

try {
    switch ($action) {
        case 'get':
            echo json_encode($controller->get());
            break;

        case 'save':
            echo json_encode($controller->save());
            break;

        case 'remove':
            echo json_encode($controller->remove());
            break;

        case 'test':
            echo json_encode($controller->test());
            break;

        default:
            echo json_encode(['status' => 'failed', 'message' => 'Invalid action']);
    }
} catch (\Throwable $e) {
    error_log('integrations/api.php - ' . $e->getMessage());
    echo json_encode(['status' => 'failed', 'message' => 'Something went wrong']);
}
