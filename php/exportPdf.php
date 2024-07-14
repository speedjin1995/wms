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
$query = $db->query("SELECT counting.*, products.product_name, products.uom as puom, supplies.supplier_name 
                     FROM counting 
                     JOIN products ON counting.product = products.id 
                     JOIN supplies ON counting.supplier = supplies.id 
                     WHERE counting.deleted = '0' 
                     AND counting.company = '$company'".$searchQuery);

try {
    // Initialize mPDF with a custom temporary directory
    $mpdfConfig = [
        'mode' => 'utf-8',
        'format' => 'A4-L',
        'tempDir' => __DIR__ . '/pdf' // Ensure this directory is writable
    ];
    $mpdf = new Mpdf($mpdfConfig);

    // Get the logo image
    $logo = '<img src="../assets/logo_customer.png" width="30%" height="auto" style="vertical-align: middle;">';

    // Get the date range for the report title
    $fromDate = !empty($_GET['fromDate']) ? $_GET['fromDate'] : 'N/A';
    $toDate = !empty($_GET['toDate']) ? $_GET['toDate'] : 'N/A';

    // Set PDF header with logo and dynamic report title
    $header = '<h2 style="display: inline-block; vertical-align: middle;">'.$logo.'&nbsp;&nbsp;&nbsp; Report from '.$fromDate.' to '.$toDate.'</h2>';
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
            $createdDateTime = new DateTime($row['created_datetime']);
            $createdDateTime->modify('+8 hours');
            $formattedDateTime = $createdDateTime->format('d/m/Y H:i:s');

            $uom = 'g';
            $puom = 'g';
            $gross = $row['gross'];
            $unit = $row['unit'];
            
            if($row['uom']!=null && $row['uom']!=''){
                $id = $row['uom'];

                if ($update_stmt = $db->prepare("SELECT * FROM units WHERE id=?")) {
                $update_stmt->bind_param('s', $id);
                
                // Execute the prepared query.
                if ($update_stmt->execute()) {
                    $result1 = $update_stmt->get_result();
                    
                    if ($row1 = $result1->fetch_assoc()) {
                        $uom = $row1['units'];
                        }
                    }
                }
            }
            
            if($row['puom']!=null && $row['puom']!=''){
                $id = $row['puom'];

                if ($update_stmt2 = $db->prepare("SELECT * FROM units WHERE id=?")) {
                $update_stmt2->bind_param('s', $id);
                
                // Execute the prepared query.
                if ($update_stmt2->execute()) {
                    $result2 = $update_stmt2->get_result();
                    
                    if ($row2 = $result2->fetch_assoc()) {
                        $puom = $row2['units'];
                        }
                    }
                }
            }
            
            if(strtoupper($uom) == 'KG'){
                $gross = (float)$gross * 1000;
            }
            
            if(strtoupper($puom) == 'KG'){
                $unit = (float)$unit * 1000;
            }

            $content .= '<tr>';
            $content .= '<td>'.$count.'</td>';
            $content .= '<td>'.$formattedDateTime.'</td>';
            $content .= '<td>'.$row['serial_no'].'</td>';
            $content .= '<td>'.$row['batch_no'].'</td>';
            $content .= '<td>'.$row['article_code'].'</td>';
            $content .= '<td>'.$row['iqc_no'].'</td>';
            $content .= '<td>'.$row['supplier_name'].'</td>';
            $content .= '<td>'.$row['product_name'].'</td>';
            $content .= '<td>'.$gross.' g</td>';
            $content .= '<td>'.$unit.' g</td>';
            $content .= '<td>'.$row['count'].' PCS</td>';
            $content .= '</tr>';

            $totalGross += $row['gross'];
            $totalUnit += $row['unit'];
            $totalCount += $row['count'];

            $count++;
        } 
    } else { 
        $content .= '<tr><td colspan="11">No records found...</td></tr>';
    }

    // Footer with summation of gross, unit, and count
    $content .= '</tbody>';
    $content .= '<tfoot>';
    $content .= '<tr>';
    $content .= '<td colspan="8" style="text-align:right;"><strong>Total</strong></td>';
    $content .= '<td><strong>'.$totalGross.' g</strong></td>';
    $content .= '<td><strong>'.$totalUnit.' g</strong></td>';
    $content .= '<td><strong>'.$totalCount.' PCS</strong></td>';
    $content .= '</tr>';
    $content .= '</tfoot>';
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
