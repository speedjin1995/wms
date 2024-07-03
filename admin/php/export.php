<?php

require_once 'db_connect.php';
// // Load the database configuration file 
 
// Filter the excel data 
function filterData(&$str){ 
    $str = preg_replace("/\t/", "\\t", $str); 
    $str = preg_replace("/\r?\n/", "\\n", $str); 
    if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"'; 
} 
 
// Excel file name for download 
$fileName = "Weight-data_" . date('Y-m-d') . ".xls";
 
// Column names 
$fields = array('SERIAL NO', 'CUSTOMER', 'PRODUCT NO', 'VEHICLE NO', 'DRIVER NAME', 'FARM', 'WEIGHTED BY', 'START WEIGHT DATE', 'END WEIGHT DATE', 
                'GROSS WEIGHT', 'TARE WEIGHT', 'REDUCE WEIGHT', 'NET WEIGHT', 'NUMBER OF BIRDS', 'NUMBER OF CAGES',
                'GRADE', 'GENDER', 'HOUSE NUMBER', 'GROUP NUMBER', 'WEIGHT DATE TIME', 'REMARKS'); 


// Display column names as first row 
$excelData = implode("\t", array_values($fields)) . "\n"; 

## Search 
$searchQuery = " ";

if($_GET['fromDate'] != null && $_GET['fromDate'] != ''){
    $fromDate = new DateTime($_GET['fromDate']);
    $fromDateTime = date_format($fromDate,"Y-m-d H:i:s");
    $searchQuery = " and created_datetime >= '".$fromDateTime."'";
}

if($_GET['toDate'] != null && $_GET['toDate'] != ''){
    $toDate = new DateTime($_GET['toDate']);
    $toDateTime = date_format($toDate,"Y-m-d H:i:s");
    $searchQuery .= " and created_datetime <= '".$toDateTime."'";
}

if($_GET['farm'] != null && $_GET['farm'] != '' && $_GET['farm'] != '-'){
    $searchQuery .= " and farm_id = '".$_GET['farm']."'";
}

if($_GET['customer'] != null && $_GET['customer'] != '' && $_GET['customer'] != '-'){
    $searchQuery .= " and customer = '".$_GET['customer']."'";
}

// Fetch records from database
$query = $db->query("select * FROM weighing WHERE deleted = '0' AND start_time IS NOT NULL AND end_time IS NOT NULL".$searchQuery."");

echo $query->num_rows;
if($query->num_rows > 0){ 
    // Output each row of the data 
    while($row = $query->fetch_assoc()){ 
        $cid = $row['weighted_by'];
        $weight_data = json_decode($row['weight_data'], true);
        $weight_time = json_decode($row['weight_time'], true);
        $weighted_by = '';
            
        if ($update_stmt = $db->prepare("SELECT * FROM users WHERE id=?")) {
            $update_stmt->bind_param('s', $cid);
        
            // Execute the prepared query.
            if ($update_stmt->execute()) {
                $result = $update_stmt->get_result();
                
                if ($row2 = $result->fetch_assoc()) {
                    $weighted_by = $row2['name'];
                }
            }
        }
        
        for($i=0; $i<count($weight_data); $i++){
            $lineData = array($row['serial_no'], $row['customer'], $row['product'], $row['lorry_no'], $row['driver_name'], $row['farm_id'],
            $weighted_by, $row['start_time'], $row['end_time'], $weight_data[$i]['grossWeight'], $weight_data[$i]['tareWeight'], 
            $weight_data[$i]['reduceWeight'], $weight_data[$i]['netWeight'], $weight_data[$i]['numberOfBirds'], $weight_data[$i]['numberOfCages'], 
            $weight_data[$i]['grade'], $weight_data[$i]['sex'], $weight_data[$i]['houseNumber'], $weight_data[$i]['groupNumber'], $weight_time[$i], 
            $weight_data[$i]['remark']);
        }
        
        array_walk($lineData, 'filterData'); 
        $excelData .= implode("\t", array_values($lineData)) . "\n"; 
    } 
}else{ 
    $excelData .= 'No records found...'. "\n"; 
} 
 
// Headers for download 
header("Content-Type: application/vnd.ms-excel"); 
header("Content-Disposition: attachment; filename=\"$fileName\""); 
 
// Render excel data 
echo $excelData; 
 
exit;
?>
