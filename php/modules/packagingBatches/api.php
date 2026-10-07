<?php
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Modules\PackagingBatch\PackagingBatchController;
use App\Modules\PackagingBatch\PackagingBatchDashboardService;
use App\Modules\PackagingBatch\PackagingBatchReportService;
use App\Modules\PackagingBatch\PackagingBatchService;

session_start();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

$company = (int)$_SESSION['customer'];
$userId = (int)$_SESSION['userID'];
$role = (string)($_SESSION['role'] ?? '');

$controller = new PackagingBatchController(
    new PackagingBatchService(
        $db,
        $company,
        $userId,
        $role,
        (array)($_SESSION['userModuleAccess'] ?? []),
        in_array('stocks', (array)($_SESSION['products'] ?? []), true)
    ),
    new PackagingBatchReportService($db, $company, $userId, $role),
    new PackagingBatchDashboardService($db, $company, $userId, $role)
);

try {
    switch ($action) {
        case 'dashboard':
            echo json_encode($controller->dashboard());
            break;

        case 'list':
            echo json_encode($controller->list());
            break;

        case 'get':
            echo json_encode($controller->get());
            break;

        case 'save':
            echo json_encode($controller->save());
            break;

        case 'cancel':
            echo json_encode($controller->cancel());
            break;

        case 'printSlip':
            echo json_encode($controller->printSlip());
            break;

        default:
            echo json_encode(['status' => 'failed', 'message' => 'Invalid action']);
    }
} catch (\Throwable $e) {
    error_log('packagingBatches/api.php - ' . $e->getMessage());
    echo json_encode(['status' => 'failed', 'message' => 'Something went wrong']);
}
