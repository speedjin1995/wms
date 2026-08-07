<?php
require_once '../../db_connect.php';
require_once '../../lookup.php';
require_once '../../../vendor/autoload.php';
session_start();

$type = $_GET['type'] ?? '';

switch ($type) {
    case 'customer':
        require_once 'partial/exportCustomerBreakdown.php';
        break;
    case 'supplier':
        require_once 'partial/exportSupplierBreakdown.php';
        break;
    case 'grade':
        require_once 'partial/exportGradeDistribution.php';
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid export type']);
        exit;
}
