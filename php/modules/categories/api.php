<?php
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\CategoryController;
use App\Services\CategoryService;

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$service = new CategoryService(
    $db,
    (int)$_SESSION['customer'],
    (int)$_SESSION['userID'],
    (string)($_SESSION['role'] ?? ''),
    (string)($_SESSION['module'] ?? '')
);
$controller = new CategoryController($service);

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

        case 'upload':
            echo json_encode($controller->upload());
            break;

        default:
            echo json_encode(['status' => 'failed', 'message' => 'Invalid action']);
    }
} catch (\Throwable $e) {
    error_log('categories/api.php - ' . $e->getMessage());
    echo json_encode(['status' => 'failed', 'message' => 'Something went wrong']);
}
