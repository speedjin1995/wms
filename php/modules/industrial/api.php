<?php
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Modules\Wholesale\WholesaleController;
use App\Modules\Wholesale\WholesaleExportService;
use App\Modules\Wholesale\WholesaleReportService;
use App\Modules\Wholesale\WholesaleService;

session_start();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$isDownload = in_array($action, ['exportReport', 'exportReportPdf', 'exportIntegration'], true);

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

// Industrial records live in the wholesales table (records_type = industrial)
$controller = new WholesaleController(
    new WholesaleService(
        $db,
        $company,
        $userId,
        $role,
        'industrial',
        (array)($_SESSION['userModuleAccess'] ?? []),
        in_array('stocks', (array)($_SESSION['products'] ?? []), true)
    ),
    new WholesaleReportService(
        $db,
        $company,
        $userId,
        $role,
        'industrial',
        (array)($_SESSION['languageArray'] ?? []),
        (string)($_SESSION['language'] ?? 'en')
    ),
    new WholesaleExportService($db, $company, $userId, $role)
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
            // Print HTML may hold invalid UTF-8 from master data
            echo json_encode($controller->printSlip(), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            break;

        case 'exportReport':
            $controller->exportReport();
            break;

        case 'exportReportPdf':
            $controller->exportReportPdf();
            break;

        case 'exportIntegration':
            $controller->exportIntegration();
            break;

        default:
            echo json_encode(['status' => 'failed', 'message' => 'Invalid action']);
    }
} catch (\Throwable $e) {
    error_log('industrial/api.php - ' . $e->getMessage());
    if ($isDownload) {
        http_response_code(500);
        exit('Something went wrong');
    }
    echo json_encode(['status' => 'failed', 'message' => 'Something went wrong']);
}
