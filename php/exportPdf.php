<?php
require_once 'db_connect.php';
require_once '../vendor/autoload.php'; 
use Mpdf\Mpdf;

session_start();
$company = $_SESSION['customer'];

// PDF file name for download
$fileName = "Report_" . date('Y-m-d') . ".pdf";

// Build search query
$searchQuery = "";

if (!empty($_GET['fromDate'])) {
    $dateTime = DateTime::createFromFormat('d/m/Y', $_GET['fromDate']);
    $fromDateTime = $dateTime->format('Y-m-d 00:00:00');
    $searchQuery .= " AND counting.created_datetime >= '".$fromDateTime."'";
}

if (!empty($_GET['toDate'])) {
    $dateTime = DateTime::createFromFormat('d/m/Y', $_GET['toDate']);
    $toDateTime = $dateTime->format('Y-m-d 23:59:59');
    $searchQuery .= " AND counting.created_datetime <= '".$toDateTime."'";
}

if (!empty($_GET['product']) && $_GET['product'] != '-') {
    $searchQuery .= " AND products.id = '".mysqli_real_escape_string($db, $_GET['product'])."'";
}

if (!empty($_GET['supplier']) && $_GET['supplier'] != '-') {
    $searchQuery .= " AND counting.supplier = '".mysqli_real_escape_string($db, $_GET['supplier'])."'";
}

// Fetch records from database
$query = $db->query("SELECT counting.*, products.product_name, supplies.supplier_name 
                     FROM counting 
                     JOIN products ON counting.product = products.id 
                     JOIN supplies ON counting.supplier = supplies.id 
                     WHERE counting.deleted = '0' 
                     AND counting.company = '$company'".$searchQuery);

try {
    // Initialize mPDF with a custom temporary directory
    $mpdfConfig = [
        'tempDir' => __DIR__ . '/pdf' // Ensure this directory is writable
    ];
    $mpdf = new Mpdf($mpdfConfig);

    // Set PDF header
    $header = '<h2>Report</h2>';
    $header .= '<table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse;">';
    $header .= '<thead>';
    $header .= '<tr>';
    $header .= '<th>No</th><th>Date</th><th>Serial No.</th><th>Batch No.</th><th>Article No.</th><th>Iqc No.</th><th>Supplier</th><th>Product</th><th>Gross Weight</th><th>Unit Weight</th><th>Qty</th>';
    $header .= '</tr>';
    $header .= '</thead>';
    $header .= '<tbody>';

    // Set PDF content
    $content = '';

    if ($query->num_rows > 0) { 
        $count = 1;
        while ($row = $query->fetch_assoc()) { 
            $content .= '<tr>';
            $content .= '<td>'.$count.'</td>';
            $content .= '<td>'.substr($row['created_datetime'], 0, 10).'</td>';
            $content .= '<td>'.$row['serial_no'].'</td>';
            $content .= '<td>'.$row['batch_no'].'</td>';
            $content .= '<td>'.$row['article_code'].'</td>';
            $content .= '<td>'.$row['iqc_no'].'</td>';
            $content .= '<td>'.$row['supplier_name'].'</td>';
            $content .= '<td>'.$row['product_name'].'</td>';
            $content .= '<td>'.$row['gross'].'</td>';
            $content .= '<td>'.$row['unit'].'</td>';
            $content .= '<td>'.$row['count'].'</td>';
            $content .= '</tr>';
            $count++;
        } 
    } else { 
        $content .= '<tr><td colspan="11">No records found...</td></tr>';
    }

    $content .= '</tbody>';
    $content .= '</table>';

    // Write PDF content
    $mpdf->WriteHTML($header . $content);

    // Output to browser
    $mpdf->Output($fileName, 'D');
} catch (\Mpdf\MpdfException $e) {
    echo $e->getMessage();
}

exit;
?>
