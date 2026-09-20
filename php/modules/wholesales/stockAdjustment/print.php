<?php
require_once __DIR__ . '/../../../db_connect.php';
require_once __DIR__ . '/../../../lookup.php';
require_once __DIR__ . '/../../../bootstrap.php';

use App\Controllers\StockAdjustmentController;
use App\Services\StockAdjustmentService;

session_start();

if (!isset($_SESSION['userID'])) {
    die('Unauthorized');
}

$company = (int)$_SESSION['customer'];
$userId = (int)$_SESSION['userID'];

$companyDetail = searchCompanyById($company, $db);

$service = new StockAdjustmentService($db, $company, $userId);
$controller = new StockAdjustmentController($service);

$controller->print($companyDetail);
