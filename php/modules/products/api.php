<?php
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../uploadFileHelper.php';
require_once __DIR__ . '/../../bootstrap.php';

use App\Modules\Product\ProductController;
use App\Modules\Product\ProductService;

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$service = new ProductService(
    $db,
    (int)$_SESSION['customer'],
    (int)$_SESSION['userID'],
    (string)($_SESSION['role'] ?? '')
);
$controller = new ProductController($service);

try {
    switch ($action) {
        case 'list':
            echo json_encode($controller->list());
            break;

        case 'get':
            echo json_encode($controller->get());
            break;

        case 'getPrice':
            echo json_encode($controller->getPrice());
            break;

        case 'save':
            echo json_encode($controller->save());
            break;

        case 'saveCustomerSupplier':
            echo json_encode($controller->saveCustomerSupplier());
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

        case 'getProductsByType':
            $categoryIds = [];
            $userModuleAccess = $_SESSION['userModuleAccess'] ?? [];
            if (!empty($userModuleAccess['categories'])) {
                $allowedModules = ['wholesale', 'processing'];
                foreach ($userModuleAccess['categories'] as $module => $moduleCategories) {
                    if (in_array($module, $allowedModules)) {
                        $categoryIds = array_merge($categoryIds, $moduleCategories);
                    }
                }
                $categoryIds = array_unique(array_map('intval', $categoryIds));
            }
            echo json_encode($controller->getProductsByType($categoryIds));
            break;

        default:
            echo json_encode(['status' => 'failed', 'message' => 'Invalid action']);
    }
} catch (\Throwable $e) {
    error_log('products/api.php - ' . $e->getMessage());
    echo json_encode(['status' => 'failed', 'message' => 'Something went wrong']);
}
