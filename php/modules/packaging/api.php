<?php
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\PackagingController;
use App\Services\PackagingService;

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$service = new PackagingService(
    $db,
    (int)$_SESSION['customer'],
    (int)$_SESSION['userID'],
    (string)($_SESSION['role'] ?? '')
);
$controller = new PackagingController($service);

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

        default:
            echo json_encode(['status' => 'failed', 'message' => 'Invalid action']);
    }
} catch (\Throwable $e) {
    error_log('packaging/api.php - ' . $e->getMessage());
    echo json_encode(['status' => 'failed', 'message' => 'Something went wrong']);
}
