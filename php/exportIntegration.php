<?php
require_once 'db_connect.php';
require_once 'lookup.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();

$company       = $_SESSION['customer'];
$companyDetail = searchCompanyById($company, $db);
$integrationList = $companyDetail['integration_list'] ?? '';

$docType           = $_GET['docType'] ?? '';
$transactionStatus = $_GET['transactionStatus'] ?? '';

if (empty($docType) || empty($integrationList)) {
    die('Invalid request.');
}

// ─── Resolve mapping folder ───────────────────────────────────────────────────

$folder = ($transactionStatus === 'RECEIVING') ? 'Receiving' : 'Dispatch';
$mappingFile = __DIR__ . '/../export-mapping/' . strtolower($integrationList) . '/' . $folder . '/' . $docType . '.json';

if (!file_exists($mappingFile)) {
    die('Mapping not found for: ' . htmlspecialchars($docType));
}

$mapping = json_decode(file_get_contents($mappingFile), true);
$columns = $mapping['columns'];

// ─── Build search query (same as export.php) ──────────────────────────────────

$searchQuery = '';

if (!empty($_GET['fromDate'])) {
    $dt = DateTime::createFromFormat('d/m/Y', $_GET['fromDate']);
    $searchQuery .= " AND wholesales.created_datetime >= '" . $dt->format('Y-m-d 00:00:00') . "'";
}

if (!empty($_GET['toDate'])) {
    $dt = DateTime::createFromFormat('d/m/Y', $_GET['toDate']);
    $searchQuery .= " AND wholesales.created_datetime <= '" . $dt->format('Y-m-d 23:59:59') . "'";
}

if (!empty($_GET['transactionStatus']) && $_GET['transactionStatus'] != '-') {
    $searchQuery .= " AND wholesales.status = '" . mysqli_real_escape_string($db, $_GET['transactionStatus']) . "'";
}

if (!empty($_GET['product']) && $_GET['product'] != '-') {
    $searchQuery .= " AND wholesales.product = '" . mysqli_real_escape_string($db, $_GET['product']) . "'";
}

if (!empty($_GET['category']) && $_GET['category'] != '-') {
    $catProductIds = [];
    $catStmt = $db->prepare("SELECT id FROM products WHERE category = ? AND deleted = '0'");
    $catStmt->bind_param('s', $_GET['category']);
    $catStmt->execute();
    $catResult = $catStmt->get_result();
    while ($catRow = $catResult->fetch_assoc()) {
        $catProductIds[] = $catRow['id'];
    }
    $catStmt->close();
    if (count($catProductIds) > 0) {
        $likeConditions = array_map(fn($id) => "wholesales.weight_details LIKE '%\"product\":\"" . $id . "\"%'", $catProductIds);
        $searchQuery .= " AND (" . implode(' OR ', $likeConditions) . ")";
    } else {
        $searchQuery .= " AND 1=0";
    }
}

if (!empty($_GET['customer']) && $_GET['customer'] != '-') {
    $searchQuery .= " AND wholesales.customer = '" . mysqli_real_escape_string($db, $_GET['customer']) . "'";
}

if (!empty($_GET['supplier']) && $_GET['supplier'] != '-') {
    $searchQuery .= " AND wholesales.supplier = '" . mysqli_real_escape_string($db, $_GET['supplier']) . "'";
}

if (!empty($_GET['vehicle']) && $_GET['vehicle'] != '-') {
    if (in_array($_GET['vehicle'], ['UNKOWN NO', 'OTHERS', 'UNKNOWN'])) {
        if (!empty($_GET['otherVehicle']) && $_GET['otherVehicle'] != '-') {
            $searchQuery .= " AND wholesales.vehicle_no = '" . mysqli_real_escape_string($db, $_GET['otherVehicle']) . "'";
        }
    } else {
        $searchQuery .= " AND wholesales.vehicle_no = '" . mysqli_real_escape_string($db, $_GET['vehicle']) . "'";
    }
}

if (!empty($_GET['checkedBy']) && $_GET['checkedBy'] != '-') {
    $searchQuery .= " AND wholesales.checked_by = '" . mysqli_real_escape_string($db, $_GET['checkedBy']) . "'";
}

if (!empty($_GET['weightedBy']) && $_GET['weightedBy'] != '-') {
    $searchQuery .= " AND wholesales.weighted_by = '" . mysqli_real_escape_string($db, $_GET['weightedBy']) . "'";
}

if (!empty($_GET['location']) && $_GET['location'] != '-') {
    $searchQuery .= " AND wholesales.location = '" . mysqli_real_escape_string($db, $_GET['location']) . "'";
}

if (!empty($_GET['status']) && $_GET['status'] != '-') {
    $searchQuery .= " AND wholesales.deleted = '" . ($_GET['status'] === 'deleted' ? '1' : '0') . "'";
}

// ─── Fetch records ────────────────────────────────────────────────────────────

$isMulti = $_GET['isMulti'] ?? 'N';

if ($isMulti === 'Y') {
    $ids   = $_GET['ids'] ?? '';
    $query = $db->query("SELECT * FROM wholesales WHERE id IN (" . $ids . ")");
} else {
    $query = $db->query("SELECT * FROM wholesales WHERE deleted = '0' AND company = '$company'" . $searchQuery);
}

// ─── Lookup caches ────────────────────────────────────────────────────────────

$customerCache = [];
$supplierCache = [];
$productCache  = [];
$currencyCache = [];

// ─── Resolve a single mapping column value ────────────────────────────────────

function resolveValue($colMap, $row, $detail, $db, &$customerCache, &$supplierCache, &$productCache) {
    $source = $colMap['source'];
    $field  = $colMap['field'] ?? '';
    $value  = $colMap['value'] ?? '';

    switch ($source) {
        case 'wholesales':
            if ($field === 'created_datetime' && !empty($colMap['format'])) {
                $dt = new DateTime($row[$field]);
                return $dt->format($colMap['format']);
            }
            return $row[$field] ?? '';

        case 'customer_lookup':
            $customer = getCustomerById($row['customer'], $db, $customerCache);
            return $customer[$field] ?? '';

        case 'supplier_lookup':
            $supplier = getSupplierById($row['supplier'], $db, $supplierCache);
            return $supplier[$field] ?? '';

        case 'currency_lookup':
            return searchCurrencyNameById($detail[$field] ?? '', $db);

        case 'product_lookup':
            $productId = $detail['product'] ?? '';
            $product   = getProductById($productId, $db, $productCache);
            return $product[$field] ?? '';

        case 'detail':
            return $detail[$field] ?? '';

        case 'computed':
            if ($field === 'net_minus_reject') {
                $net    = floatval($detail['net']    ?? 0);
                $reject = floatval($detail['reject'] ?? 0);
                return $net - $reject;
            }
            return '';

        case 'static':
            return $value;

        default:
            return '';
    }
}

// ─── Build spreadsheet rows ───────────────────────────────────────────────────

$allColumns = array_keys($columns);

$dataRows = [];

if ($query && $query->num_rows > 0) {
    while ($row = $query->fetch_assoc()) {
        $weightDetails = json_decode($row['weight_details'], true) ?: [];

        foreach ($weightDetails as $detail) {
            $dataRow = [];

            foreach ($columns as $col => $colMap) {
                $dataRow[$col] = resolveValue($colMap, $row, $detail, $db, $customerCache, $supplierCache, $productCache);
            }

            $dataRows[] = $dataRow;
        }
    }
}

// ─── Write spreadsheet ────────────────────────────────────────────────────────

$spreadsheet = new Spreadsheet();
$sheet       = $spreadsheet->getActiveSheet();

// Header row
foreach ($allColumns as $colIndex => $colName) {
    $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . '1';
    $sheet->setCellValue($coord, $colName);
    $sheet->getStyle($coord)->getFont()->setBold(true);
}

// Data rows
foreach ($dataRows as $rowIndex => $dataRow) {
    foreach ($allColumns as $colIndex => $colName) {
        $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . ($rowIndex + 2);
        $sheet->setCellValue($coord, $dataRow[$colName] ?? '');
    }
}

// ─── Output ───────────────────────────────────────────────────────────────────

$label    = $mapping['label'] ?? $docType;
$fileName = $label . '_' . date('Y-m-d') . '.xlsx';

$writer = new Xlsx($spreadsheet);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
?>
