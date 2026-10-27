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

        case 'import':
            require_once __DIR__ . '/../../../vendor/autoload.php';
            echo json_encode($controller->import($categoryIds));
            break;

        case 'template':
            require_once __DIR__ . '/../../../vendor/autoload.php';
            $opts = $service->getOptions($categoryIds);

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('DO');
            $lists = $spreadsheet->createSheet();
            $lists->setTitle('Lists');

            // Table layout: one row per DO weight; rows with the same supplier + product + grade become one item
            $lastRow = 504;
            $sheet->setCellValue('A1', 'DATE:');
            $sheet->setCellValue('B1', date('d/m/Y'));
            $sheet->setCellValue('A2', 'DO NO:');
            $sheet->fromArray(['SUPPLIER', 'PRODUCT', 'GRADE', 'DO WEIGHT'], null, 'A4');
            $sheet->getStyle('A4:D4')->getFont()->setBold(true);
            $sheet->getStyle('A4:D4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
            $sheet->getStyle('D5:D' . $lastRow)->getNumberFormat()->setFormatCode('0.00');
            $sheet->freezePane('A5');
            foreach (['A' => 40, 'B' => 36, 'C' => 14, 'D' => 16] as $col => $width) {
                $sheet->getColumnDimension($col)->setWidth($width);
            }

            $lists->fromArray(array_map(function ($s) {
                return [$s['supplier_code'] . ' - ' . $s['supplier_name']];
            }, $opts['suppliers']), null, 'A1');
            $lists->fromArray(array_map(function ($p) {
                return [$p['product_code'] . ' - ' . $p['product_name']];
            }, $opts['products']), null, 'B1');

            $gradeNames = [];
            foreach ($opts['products'] as $p) {
                foreach ($p['grades'] as $g) {
                    $gradeNames[$g['grade_name']] = [$g['grade_name']];
                }
            }
            $lists->fromArray(array_values($gradeNames), null, 'C1');
            $lists->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
            $spreadsheet->setActiveSheetIndex(0);

            $addList = function ($col, $count) use ($sheet, $lastRow) {
                if ($count === 0) {
                    return;
                }

                $validation = new \PhpOffice\PhpSpreadsheet\Cell\DataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('Lists!$' . $col . '$1:$' . $col . '$' . $count);
                $sheet->setDataValidation($col . '5:' . $col . $lastRow, $validation);
            };
            $addList('A', count($opts['suppliers']));
            $addList('B', count($opts['products']));
            $addList('C', count($gradeNames));

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
