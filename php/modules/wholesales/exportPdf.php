<?php
require_once '../../db_connect.php';
require_once '../../lookup.php';
require_once '../../../vendor/autoload.php'; 
use Mpdf\Mpdf;

session_start();
$company = $_SESSION['customer'];
$allowPrice = 'N';
// Company Detail 
$companyDetail = searchCompanyById($company, $db);
$allowPrice = $companyDetail['include_price'];
$defaultCurrency = 'MYR';
$defCurrStmt = $db->prepare("SELECT currency FROM currency WHERE customer = ? AND is_default = 1 AND deleted = 0 LIMIT 1");
$defCurrStmt->bind_param('s', $company);
$defCurrStmt->execute();
$defCurrResult = $defCurrStmt->get_result();
if ($defCurrRow = $defCurrResult->fetch_assoc()) {
    $defaultCurrency = $defCurrRow['currency'];
}
$defCurrStmt->close();

$reportType = $_GET['reportType'] ?? 'summary';
$transactionStatus = $_GET['transactionStatus'] ?? '';
$fileName = "Report_" . date('Y-m-d') . ".pdf";

// Build search query
$searchQuery = "";

if(isset($_GET['fromDate']) && $_GET['fromDate'] != null && $_GET['fromDate'] != ''){
    $dateTime = DateTime::createFromFormat('d/m/Y', $_GET['fromDate']);
    $fromDate = $dateTime->format('d/m/Y');
    $fromDateTime = $dateTime->format('Y-m-d 00:00:00');
    $searchQuery .= " AND wholesales.start_time >= '".$fromDateTime."'";
}

if(isset($_GET['toDate']) && $_GET['toDate'] != null && $_GET['toDate'] != ''){
    $dateTime = DateTime::createFromFormat('d/m/Y', $_GET['toDate']);
    $toDate = $dateTime->format('d/m/Y');
    $toDateTime = $dateTime->format('Y-m-d 23:59:59');
    $searchQuery .= " AND wholesales.start_time <= '".$toDateTime."'";
}

if($transactionStatus != null && $transactionStatus != '' && $transactionStatus != '-'){
  $searchQuery .= " and wholesales.status = '".$transactionStatus."'";
}

if(isset($_GET['product']) && $_GET['product'] != null && $_GET['product'] != '' && $_GET['product'] != '-'){
    $searchQuery .= " AND wholesales.product = '".mysqli_real_escape_string($db, $_GET['product'])."'";
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

if(isset($_GET['customer']) && $_GET['customer'] != null && $_GET['customer'] != '' && $_GET['customer'] != '-'){
    $searchQuery .= " AND wholesales.customer = '".mysqli_real_escape_string($db, $_GET['customer'])."'";
}

if(isset($_GET['supplier']) && $_GET['supplier'] != null && $_GET['supplier'] != '' && $_GET['supplier'] != '-'){
    $searchQuery .= " AND wholesales.supplier = '".mysqli_real_escape_string($db, $_GET['supplier'])."'";
}

if($_GET['vehicle'] != null && $_GET['vehicle'] != '' && $_GET['vehicle'] != '-'){
  if ($_GET['vehicle'] == 'UNKOWN NO' || $_GET['vehicle'] == 'OTHERS' || $_GET['vehicle'] == 'UNKNOWN'){
    if($_GET['otherVehicle'] != null && $_GET['otherVehicle'] != '' && $_GET['otherVehicle'] != '-'){
      $searchQuery .= " and wholesales.vehicle_no = '".mysqli_real_escape_string($db, $_GET['otherVehicle'])."'";
    }
  } else {
    $searchQuery .= " and wholesales.vehicle_no = '".mysqli_real_escape_string($db, $_GET['vehicle'])."'";
  }
}

if(isset($_GET['checkedBy']) && $_GET['checkedBy'] != null && $_GET['checkedBy'] != '' && $_GET['checkedBy'] != '-'){
  $searchQuery .= " and wholesales.checked_by = '".mysqli_real_escape_string($db, $_GET['checkedBy'])."'";
}

if(isset($_GET['weightedBy']) && $_GET['weightedBy'] != null && $_GET['weightedBy'] != '' && $_GET['weightedBy'] != '-'){
  $searchQuery .= " and wholesales.weighted_by = '".mysqli_real_escape_string($db, $_GET['weightedBy'])."'";
}

if(isset($_GET['location']) && $_GET['location'] != null && $_GET['location'] != '' && $_GET['location'] != '-'){
  $searchQuery .= " and wholesales.location = '".mysqli_real_escape_string($db, $_GET['location'])."'";
}

if($_GET['status'] != null && $_GET['status'] != '' && $_GET['status'] != '-'){
  if ($_GET['status'] == 'active'){
    $searchQuery .= " and wholesales.deleted = '0'";
  } else if ($_GET['status'] == 'deleted'){
    $searchQuery .= " and wholesales.deleted = '1'";
  }
}

$isMulti = '';
if(isset($_GET['isMulti']) && $_GET['isMulti'] != null && $_GET['isMulti'] != '' && $_GET['isMulti'] != '-'){
    $isMulti = $_GET['isMulti'];
}

// Fetch records from database
if($isMulti == 'Y'){
    if(isset($_GET['ids']) && $_GET['ids'] != null && $_GET['ids'] != '' && $_GET['ids'] != '-'){
        $ids = $_GET['ids'];
    }
    $query = $db->query("SELECT wholesales.* FROM wholesales WHERE wholesales.id IN (".$ids.")");
}else{
    $query = $db->query("SELECT wholesales.* FROM wholesales WHERE wholesales.deleted = '0' AND wholesales.company = '$company'".$searchQuery);
}

try {
    // Initialize mPDF with a custom temporary directory
    $mpdf = new Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4-L',
        'tempDir'       => sys_get_temp_dir(),
        'margin_left'   => 5,
        'margin_right'  => 5,
        'margin_top'    => 5,
        'margin_bottom' => 5,
        'fontDir'       => [__DIR__ . '/../../../vendor/mpdf/mpdf/ttfonts/'],
        'fontdata'      => ['sunexta' => ['R' => 'Sun-ExtA.ttf']],
        'default_font'  => 'sunexta',
    ]);

    $partialMap = [
        'summary' => __DIR__ . '/partial/pdfSummary.php',
        'invoice' => __DIR__ . '/partial/pdfInvoiceListing.php',
    ];

    $partial = $partialMap[$reportType] ?? $partialMap['summary'];
    require $partial;

    $mpdf->Output($fileName, 'D');
} catch (\Mpdf\MpdfException $e) {
    echo $e->getMessage();
}

function arrangeByProductGrade($weighingDetails) {
    $arranged = [];
    if(isset($weighingDetails) && !empty($weighingDetails)) {
        foreach($weighingDetails as $detail) {
            if(empty($detail['product_name'])) continue;
            $product = $detail['product_name'];
            $grade = $detail['grade'] ?? 'Unknown';
            $arranged[$product][$grade][] = $detail;
        }
    }
    return $arranged;
}
exit;
