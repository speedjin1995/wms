<?php
require_once 'db_connect.php';
require_once '../vendor/autoload.php'; 
use PhpOffice\PhpSpreadsheet\Spreadsheet; 
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();
$company = $_SESSION['customer'];
 
// Filter the excel data 
function filterData(&$str){ 
    $str = preg_replace("/\t/", "\\t", $str); 
    $str = preg_replace("/\r?\n/", "\\n", $str); 
    if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"'; 
} 
 
// Excel file name for download 
$fileName = "Report_" . date('Y-m-d') . ".xlsx";
 
// Column names 
// Create a new Spreadsheet
$spreadsheet = new Spreadsheet();

// Get the active worksheet
$sheet = $spreadsheet->getActiveSheet();

// Column names 
$fields = array('No', 'Date', 'Serial No.', 'Batch No.', 'Article No.', 'Iqc No.', 'Supplier', 'Product', 'Gross Weight', 'Unit Weight', 'Qty'); 

// Display column names as first row 
$sheet->fromArray($fields, NULL, 'A1');

## Search 
$searchQuery = "";

if($_GET['fromDate'] != null && $_GET['fromDate'] != ''){
    $dateTime = DateTime::createFromFormat('d/m/Y', $_GET['fromDate']);
    $fromDateTime = $dateTime->format('Y-m-d 00:00:00');
    $searchQuery .= " and counting.created_datetime >= '".$fromDateTime."'";
}

if($_GET['toDate'] != null && $_GET['toDate'] != ''){
    $dateTime = DateTime::createFromFormat('d/m/Y', $_GET['toDate']);
    $toDateTime = $dateTime->format('Y-m-d 23:59:59');
    $searchQuery .= " and counting.created_datetime <= '".$toDateTime."'";
}

if($_GET['product'] != null && $_GET['product'] != '' && $_GET['product'] != '-'){
    $searchQuery .= " and products.id = '".$_GET['product']."'";
}

if($_GET['supplier'] != null && $_GET['supplier'] != '' && $_GET['supplier'] != '-'){
    $searchQuery .= " and counting.supplier = '".$_GET['supplier']."'";
}

// Fetch records from database
$query = $db->query("select counting.*, products.product_name, supplies.supplier_name from counting, products, supplies where counting.product = products.id AND counting.supplier = supplies.id AND counting.deleted = '0' AND counting.company = '$company'".$searchQuery);
$rowIndex = 2; // Start from the second row
$count = 1;

if($query->num_rows > 0){ 
    // Output each row of the data 
    while($row = $query->fetch_assoc()){ 
        $lineData = array($count, substr($row['created_datetime'], 0, 10), $row['serial_no'], $row['batch_no'], $row['article_code'], 
        $row['iqc_no'], $row['supplier_name'], $row['product_name'], $row['gross'], $row['unit'], $row['count']);

        array_walk($lineData, 'filterData'); 
        $sheet->fromArray($lineData, NULL, 'A'.$rowIndex);
        $rowIndex++;
        $count++;
    } 
}else{ 
    $sheet->setCellValue('A'.$rowIndex, 'No records found...');
} 

// Create a writer object
$writer = new Xlsx($spreadsheet);

// Output to browser
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$fileName.'"');
header('Cache-Control: max-age=0');

// Save the spreadsheet
$writer->save('php://output');
exit;
?>
