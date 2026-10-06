<?php
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Modules\Grading\GradingController;
use App\Modules\Grading\GradingReportService;
use App\Modules\Grading\GradingService;

session_start();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$isDownload = in_array($action, ['exportExcel', 'exportPdf'], true);

if ($isDownload) {
    // Warnings must not be written into the file stream (they are still logged)
    ini_set('display_errors', '0');
} else {
    header('Content-Type: application/json');
}

if (!isset($_SESSION['userID'])) {
    if ($isDownload) {
        http_response_code(401);
        exit('Unauthorized');
    }
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

$company = (int)$_SESSION['customer'];
$userId = (int)$_SESSION['userID'];
$role = (string)($_SESSION['role'] ?? '');

$controller = new GradingController(
    new GradingService(
        $db,
        $company,
        $userId,
        $role,
        (array)($_SESSION['userModuleAccess'] ?? []),
        in_array('stocks', (array)($_SESSION['products'] ?? []), true)
    ),
    new GradingReportService($db, $company, $userId, $role)
);

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

        case 'cancel':
            echo json_encode($controller->cancel());
            break;

        case 'printSlip':
            echo json_encode($controller->printSlip());
            break;

        case 'exportExcel':
            $controller->exportExcel();
            break;

        case 'exportPdf':
            $controller->exportPdf();
            break;

        default:
            echo json_encode(['status' => 'failed', 'message' => 'Invalid action']);
    }
} catch (\Throwable $e) {
    error_log('grading/api.php - ' . $e->getMessage());
    if ($isDownload) {
        http_response_code(500);
        exit('Something went wrong');
    }
    echo json_encode(['status' => 'failed', 'message' => 'Something went wrong']);
}
