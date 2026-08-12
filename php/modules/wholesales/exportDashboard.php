<?php
require_once '../../db_connect.php';
require_once '../../lookup.php';
require_once '../../../vendor/autoload.php';
session_start();

$type = $_GET['type'] ?? '';

switch ($type) {
    case 'customer':
        require_once 'partial/export/exportCustomerBreakdown.php';
        break;
    case 'supplier':
        require_once 'partial/export/exportSupplierBreakdown.php';
        break;
    case 'customer_individual':
        require_once 'partial/export/exportCustomerIndividual.php';
        break;
    case 'supplier_individual':
        require_once 'partial/export/exportSupplierIndividual.php';
        break;
    case 'grade':
        require_once 'partial/export/exportGradeDistribution.php';
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid export type']);
        exit;
}
