<?php
require_once '../../db_connect.php';
require_once '../../lookup.php';
require_once '../../../vendor/autoload.php';
use Mpdf\Mpdf;

session_start();
// Company Detail 
$company = $_SESSION['customer'];
$companyDetail = searchCompanyById($company, $db);
$fileName = 'StockBalance_' . date('Y-m-d') . '.pdf';

$defaultCurrency = 'MYR';
$defCurrStmt = $db->prepare("SELECT currency FROM currency WHERE customer = ? AND is_default = 1 AND deleted = 0 LIMIT 1");
$defCurrStmt->bind_param('s', $company);
$defCurrStmt->execute();
if ($defCurrRow = $defCurrStmt->get_result()->fetch_assoc()) {
  $defaultCurrency = $defCurrRow['currency'];
}
$defCurrStmt->close();

// Parse date
$asAtDate = date('d/m/Y');
$toDateTime = '';
if (isset($_GET['asAtDate']) && $_GET['asAtDate'] != null && $_GET['asAtDate'] != '') {
  $dtObj = DateTime::createFromFormat('d/m/Y', $_GET['asAtDate']);
  $asAtDate = $dtObj->format('d/m/Y');
  $toDateTime = $dtObj->format('Y-m-d 23:59:59');
}

// Build search query
$searchQuery = "AND wholesales.deleted = '0' AND wholesales.company = '$company'";

if (!empty($toDateTime)) {
  $fromDateTime = $dtObj->format('Y-m-d 00:00:00');
  $searchQuery .= " AND wholesales.start_time >= '$fromDateTime'";
  $searchQuery .= " AND wholesales.start_time <= '$toDateTime'";
}

if(isset($_GET['location']) && $_GET['location'] != null && $_GET['location'] != '' && $_GET['location'] != '-'){
  $searchQuery .= " AND wholesales.location = '".mysqli_real_escape_string($db, $_GET['location'])."'";
}

if(isset($_GET['category']) && $_GET['category'] != null && $_GET['category'] != '' && $_GET['category'] != '-'){
  // Get product ids in this category first
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
    $likeConditions = array_map(fn($id) => "wholesales.weight_details LIKE '%\"product\":\"".$id."\"%'", $catProductIds);
    $searchQuery .= " AND (" . implode(' OR ', $likeConditions) . ")";
  } else {
    $searchQuery .= " AND 1=0";
  }
}

if(isset($_GET['product']) && $_GET['product'] != null && $_GET['product'] != '' && $_GET['product'] != '-'){
  $searchQuery .= " AND wholesales.weight_details LIKE '%\"product\":\"" . mysqli_real_escape_string($db, $_GET['product']) . "\"%'";
}

// Fetch records
$query = $db->query("SELECT * FROM wholesales WHERE 1=1 $searchQuery");

try {
    $mpdf = new Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4-L',
        'tempDir'       => sys_get_temp_dir(),
        'margin_left'   => 10,
        'margin_right'  => 10,
        'margin_top'    => 30,
        'margin_bottom' => 10,
        'margin_header' => 5,
        'fontDir'       => [__DIR__ . '/../../../vendor/mpdf/mpdf/ttfonts/'],
        'fontdata'      => ['sunexta' => ['R' => 'Sun-ExtA.ttf']],
        'default_font'  => 'sunexta',
    ]);

    require __DIR__ . '/partial/pdf/pdfStockBalance.php';

    $mpdf->Output($fileName, 'I');
} catch (\Mpdf\MpdfException $e) {
    echo $e->getMessage();
}
exit;
