<?php
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\ProductController;
use App\Services\ProductService;

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$service = new ProductService($db, (int)$_SESSION['customer']);
$controller = new ProductController($service);

switch ($action) {
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
