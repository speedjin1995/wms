<?php
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Modules\StockTransfer\StockTransferController;
use App\Modules\StockTransfer\StockTransferService;

session_start();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

$controller = new StockTransferController(
    new StockTransferService($db, (int)$_SESSION['customer'], (int)$_SESSION['userID'], (string)($_SESSION['role'] ?? ''))
);

try {
    switch ($action) {
        case 'list':
            echo json_encode($controller->list());
            break;

        case 'get':
            echo json_encode($controller->get());
            break;

        case 'batchItems':
            echo json_encode($controller->batchItems());
            break;

        case 'save':
            echo json_encode($controller->save());
            break;

        case 'cancel':
            echo json_encode($controller->cancel());
            break;

        default:
            echo json_encode(['status' => 'failed', 'message' => 'Invalid action']);
    }
} catch (\Throwable $e) {
    error_log('stockTransfer/api.php - ' . $e->getMessage());
    echo json_encode(['status' => 'failed', 'message' => 'Something went wrong']);
}
