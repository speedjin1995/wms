<?php
require_once __DIR__ . '/../../../db_connect.php';
require_once __DIR__ . '/../../../bootstrap.php';

use App\Modules\StockAdjustment\StockAdjustmentController;
use App\Modules\StockAdjustment\StockAdjustmentService;

session_start();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$isDownload = $action === 'print';

if ($isDownload) {
    // Warnings must not be written into the PDF stream (they are still logged)
    ini_set('display_errors', '0');
} else {
    header('Content-Type: application/json');
}

if (!isset($_SESSION['userID'])) {
    if ($isDownload) {
        die('Unauthorized');
    }
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

$service = new StockAdjustmentService($db, (int)$_SESSION['customer'], (int)$_SESSION['userID'], (string)($_SESSION['role'] ?? ''));
$controller = new StockAdjustmentController($service);

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

    case 'update':
        echo json_encode($controller->update());
        break;

    case 'delete':
        echo json_encode($controller->delete());
        break;

    case 'balance':
        echo json_encode($controller->balance());
        break;

    case 'products':
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
        $type = $_POST['type'] ?? 'Local';
        echo json_encode($controller->products($categoryIds, $type));
        break;

    case 'print':
        require_once __DIR__ . '/../../../lookup.php';
        $controller->print(searchCompanyById((int)$_SESSION['customer'], $db));
        break;

    default:
        echo json_encode(['status' => 'failed', 'message' => 'Invalid action']);
}
