<?php
require_once __DIR__ . '/../../../db_connect.php';
require_once __DIR__ . '/../../../bootstrap.php';

use App\Modules\Wholesale\BulkPriceUpdateController;
use App\Modules\Wholesale\BulkPriceUpdateService;

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$service = new BulkPriceUpdateService(
    $db,
    (int)$_SESSION['customer'],
    (int)$_SESSION['userID'],
    (string)($_SESSION['role'] ?? ''),
    (array)($_SESSION['userModuleAccess'] ?? [])
);
$controller = new BulkPriceUpdateController($service);

try {
    switch ($action) {
        case 'list':
            echo json_encode($controller->list());
            break;

        case 'productGrades':
            echo json_encode($controller->productGrades());
            break;

        case 'preview':
            echo json_encode($controller->preview());
            break;

        case 'update':
            echo json_encode($controller->update());
            break;

        default:
            echo json_encode(['status' => 'failed', 'message' => 'Invalid action']);
    }
} catch (\Throwable $e) {
    error_log('wholesales/bulkPriceUpdate/api.php - ' . $e->getMessage());
    echo json_encode(['status' => 'failed', 'message' => 'Something went wrong']);
}
