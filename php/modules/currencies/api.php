<?php
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\CurrencyController;
use App\Services\CurrencyService;

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$service = new CurrencyService(
    $db,
    (int)$_SESSION['customer'],
    (int)$_SESSION['userID'],
    (string)($_SESSION['role'] ?? '')
);
$controller = new CurrencyController($service);

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

        case 'setDefault':
            echo json_encode($controller->setDefault());
            break;

        default:
            echo json_encode(['status' => 'failed', 'message' => 'Invalid action']);
    }
} catch (\Throwable $e) {
    error_log('currencies/api.php - ' . $e->getMessage());
    echo json_encode(['status' => 'failed', 'message' => 'Something went wrong']);
}
