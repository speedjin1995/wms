<?php
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\StateController;
use App\Services\StateService;

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$service = new StateService(
    $db,
    (int)$_SESSION['customer'],
    (int)$_SESSION['userID'],
    (string)($_SESSION['role'] ?? '')
);
$controller = new StateController($service);

try {
    switch ($action) {
        case 'list':
            echo json_encode($controller->list());
            break;

        case 'get':
            echo json_encode($controller->get());
            break;

        case 'save':
            echo json_encode($controller->save());
            break;

        case 'delete':
            echo json_encode($controller->delete());
            break;

        case 'reactivate':
            echo json_encode($controller->reactivate());
            break;

        default:
            echo json_encode(['status' => 'failed', 'message' => 'Invalid action']);
    }
} catch (\Throwable $e) {
    error_log('states/api.php - ' . $e->getMessage());
    echo json_encode(['status' => 'failed', 'message' => 'Something went wrong']);
}
