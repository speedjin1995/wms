<?php
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../lookup.php';
require_once __DIR__ . '/../../uploadFileHelper.php';
require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\CustomerController;
use App\Services\CustomerService;
use App\Services\EntityRunningNoService;

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$service = new CustomerService(
    $db,
    (int)$_SESSION['customer'],
    (int)$_SESSION['userID'],
    (string)($_SESSION['role'] ?? '')
);
$runningNoService = new EntityRunningNoService(
    $db,
    (int)$_SESSION['customer'],
    (int)$_SESSION['userID'],
    (string)($_SESSION['role'] ?? ''),
    (string)($_SESSION['module'] ?? ''),
    'Customer'
);
$controller = new CustomerController($service, $runningNoService);

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

        case 'upload':
            echo json_encode($controller->upload());
            break;

        case 'dropdown':
            echo json_encode($controller->dropdown());
            break;

        case 'getRunningNo':
            echo json_encode($controller->getRunningNo());
            break;

        case 'saveRunningNo':
            echo json_encode($controller->saveRunningNo());
            break;

        case 'binPending':
            echo json_encode($controller->binPending());
            break;

        case 'binHistory':
            echo json_encode($controller->binHistory());
            break;

        case 'updateBin':
            echo json_encode($controller->updateBin());
            break;

        default:
            echo json_encode(['status' => 'failed', 'message' => 'Invalid action']);
    }
} catch (\Throwable $e) {
    error_log('customers/api.php - ' . $e->getMessage());
    echo json_encode(['status' => 'failed', 'message' => 'Something went wrong']);
}
