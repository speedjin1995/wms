<?php
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Modules\PaymentVoucher\PaymentVoucherController;
use App\Modules\PaymentVoucher\PaymentVoucherReportService;
use App\Modules\PaymentVoucher\PaymentVoucherService;

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
$language = $_SESSION['language'] ?? 'en';
$languageArray = $_SESSION['languageArray'] ?? [];

$controller = new PaymentVoucherController(
    new PaymentVoucherService($db, $company, $userId, $role, (string)($_SESSION['module'] ?? 'wholesales')),
    new PaymentVoucherReportService($db, $company, $userId, $role),
    function ($key, $default = '') use ($languageArray, $language) {
        return $languageArray[$key][$language] ?? $default;
    }
);

try {
    switch ($action) {
        case 'list':
            echo json_encode($controller->list());
            break;

        case 'items':
            echo json_encode($controller->items());
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

        case 'exportReport':
            echo json_encode($controller->exportReport());
            break;

        default:
            echo json_encode(['status' => 'failed', 'message' => 'Invalid action']);
    }
} catch (\Throwable $e) {
    error_log('paymentVoucher/api.php - ' . $e->getMessage());
    echo json_encode(['status' => 'failed', 'message' => 'Something went wrong']);
}
