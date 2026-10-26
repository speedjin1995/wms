<?php
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../bootstrap.php';

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

        case 'template':
            require_once __DIR__ . '/../../../vendor/autoload.php';
            $opts = $service->getOptions($categoryIds);

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('DO');
            $lists = $spreadsheet->createSheet();
            $lists->setTitle('Lists');

            $sheet->setCellValue('A1', 'DATE:');
            $sheet->setCellValue('B1', date('d/m/Y'));
            $sheet->setCellValue('A2', 'DO NO:');
            $sheet->setCellValue('A26', 'TOTAL');

            $supplierRow = 1;
            foreach ($opts['suppliers'] as $s) {
                $lists->setCellValue('A' . $supplierRow++, $s['supplier_code'] . ' - ' . $s['supplier_name']);
            }
            $productRow = 1;
            foreach ($opts['products'] as $p) {
                foreach ($p['grades'] as $g) {
                    $lists->setCellValue('B' . $productRow++, $p['product_code'] . ' - ' . $g['grade_name']);
                }
            }
            $lists->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
            $spreadsheet->setActiveSheetIndex(0);

            $addList = function ($coord, $range) use ($sheet) {
                $validation = $sheet->getCell($coord)->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1($range);
            };
            if ($supplierRow > 1) {
                $addList('A4', 'Lists!$A$1:$A$' . ($supplierRow - 1));
            }
            if ($productRow > 1) {
                foreach (range('A', 'J') as $col) {
                    $addList($col . '5', 'Lists!$B$1:$B$' . ($productRow - 1));
                }
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="delivery_order_template.xlsx"');
            header('Cache-Control: max-age=0');
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
            exit;

        default:
            echo json_encode(['status' => 'failed', 'message' => 'Invalid action']);
    }
} catch (\Throwable $e) {
    error_log('deliveryOrder/api.php - ' . $e->getMessage());
    echo json_encode(['status' => 'failed', 'message' => 'Something went wrong']);
}
