<?php
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Controllers\DeliveryOrderController;
use App\Services\DeliveryOrderService;

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

if (($_SESSION['featureFlags']['include_delivery_order'] ?? 'N') != 'Y') {
    echo json_encode(['status' => 'failed', 'message' => 'Delivery order is not enabled']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$service = new DeliveryOrderService(
    $db,
    (int)$_SESSION['customer'],
    (int)$_SESSION['userID'],
    (string)($_SESSION['role'] ?? '')
);
$controller = new DeliveryOrderController($service);

$categoryIds = [];
$userModuleAccess = $_SESSION['userModuleAccess'] ?? [];
if (!empty($userModuleAccess['categories'])) {
    $allowedModules = ['wholesale', 'processing'];
    foreach ($userModuleAccess['categories'] as $module => $moduleCategories) {
        if (in_array($module, $allowedModules)) {
            $categoryIds = array_merge($categoryIds, $moduleCategories);
        }
    }
    $categoryIds = array_values(array_unique(array_map('intval', $categoryIds)));
}

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

        case 'options':
            echo json_encode($controller->options($categoryIds));
            break;

        case 'import':
            echo json_encode($controller->import($categoryIds));
            break;
            
        case 'scan':
            if (($_SESSION['featureFlags']['include_api_integration'] ?? 'N') != 'Y') {
                echo json_encode(['status' => 'failed', 'message' => 'API integration is not enabled']);
                break;
            }

            echo json_encode($controller->scan($categoryIds, (string)($_POST['provider'] ?? '')));
            break;

        case 'template':
            $controller->template($categoryIds);
            exit;

        case 'report':
            echo json_encode($controller->report());
            break;

        case 'report_excel':
            $controller->reportExcel();
            exit;

        case 'report_pdf':
            $controller->reportPdf();
            exit;

        default:
            echo json_encode(['status' => 'failed', 'message' => 'Invalid action']);
    }
} catch (\Throwable $e) {
    error_log('deliveryOrder/api.php - ' . $e->getMessage());
    echo json_encode(['status' => 'failed', 'message' => 'Something went wrong']);
}
