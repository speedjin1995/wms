<?php
// Enable error reporting for debugging
require_once 'php/db_connect.php';

$compids = '1';
$compname = 'SYNCTRONIX TECHNOLOGY (M) SDN BHD';
$compaddress = 'No.34, Jalan Bagan 1, Taman Bagan, 13400 Butterworth. Penang. Malaysia.';
$compphone = '6043325822';
$compiemail = 'admin@synctronix.com.my';

$mapOfWeights = array();
$mapOfHouses = array();
$mapOfBirdsToCages = array();

$totalCount = 0;
$totalGross = 0.0;
$totalCrate = 0.0;
$totalReduce = 0.0;
$totalNet = 0.0;

$totalSGross = 0.0;
$totalSCrate = 0.0;
$totalSReduce = 0.0;
$totalSNet = 0.0;

$totalAGross = 0.0;
$totalACrate = 0.0;
$totalAReduce = 0.0;
$totalANet = 0.0;

$totalCrates = 0;
$totalBirds = 0;
$totalMaleBirds = 0;
$totalSBirds = 0;
$totalABirds = 0;
$totalSCages = 0;
$totalACages = 0;
$totalMaleCages = 0;
$totalFemaleBirds = 0;
$totalFemaleCages = 0;
$totalMixedBirds = 0;
$totalMixedCages = 0;

$gradeData = [];

// Filter the excel data 
function filterData(&$str){ 
    $str = preg_replace("/\t/", "\\t", $str); 
    $str = preg_replace("/\r?\n/", "\\n", $str); 
    if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"'; 
}

function arrangeByGrade($weighingDetails) {
    $arranged = [];
    if (isset($weighingDetails) && !empty($weighingDetails)) {
        foreach ($weighingDetails as $detail) {
            $product = $detail['product'] ?? 'Unknown';
            $grade = $detail['grade'] ?? 'Unknown';
            if (!isset($arranged[$product])) {
                $arranged[$product] = ['product_name' => $detail['product_name'] ?? $product, 'grades' => []];
            }
            if (!isset($arranged[$product]['grades'][$grade])) $arranged[$product]['grades'][$grade] = [];
            $arranged[$product]['grades'][$grade][] = $detail;
        }
    }
    return ['arranged' => $arranged];
}

function totalWeight($strings){ 
    $totalSum = 0;

    for ($i =0; $i < count($strings); $i++) {
        if (preg_match('/([\d.]+)/', $strings[$i]['grossWeight'], $matches)) {
            $value = floatval($matches[1]);
            $totalSum += $value;
        }
    }

    return $totalSum;
}

if(isset($_GET['id'])){
    $id = $_GET['id'];

    if ($select_stmt = $db->prepare("select * FROM food_packaging WHERE id=?")) {
        $select_stmt->bind_param('s', $id);

        if (! $select_stmt->execute()) {
            echo json_encode(
                array(
                    "status" => "failed",
                    "message" => "Something went wrong went execute"
                )); 
        }
        else{
            $result = $select_stmt->get_result();

            if ($row = $result->fetch_assoc()) { 
                $fileName = 'F-'.$row['po_no'].'_'.substr($row['customer'], 0, 15).'_'.$row['serial_no'];
                $startTime = strtotime ( $row['created_datetime'] );
                $endTime = strtotime ( $row['end_time'] );
                $duration = $endTime - $startTime;

                // Convert duration to minutes and seconds
                $minutes = floor($duration / 60);
                $seconds = $duration % 60;
                
                // Format as "xxx mins and xxx secs"
                $time = sprintf('%d mins and %d secs', $minutes, $seconds);
                $weightData = json_decode($row['weight_details'], true);
                $arranged = arrangeByGrade($weightData);
                $groupedData = $arranged['arranged'];
                foreach ($weightData as $item) {
                    $totalGross += floatval($item['gross']);
                    $totalCrate += floatval($item['tare']);
                    $totalNet += floatval($item['net']);
                    $totalCrates++;
                }
                $userName = "-";
                $pages = ceil($totalCrates / 180);
                $page = 1;

                $stmtcomp = $db->prepare("SELECT * FROM companies WHERE id=?");
                $stmtcomp->bind_param('s', $row['company']);
                $stmtcomp->execute();
                $resultc = $stmtcomp->get_result();
                        
                if ($rowc = $resultc->fetch_assoc()) {
                    $compname = $rowc['name'];
                    $compreg = $rowc['reg_no'] ?? '';
                    $compaddress = $rowc['address'];
                    $compaddress2 = $rowc['address2'] ?? '';
                    $compaddress3 = $rowc['address3'] ?? '';
                    $compaddress4 = $rowc['address4'] ?? '';
                    $compphone = $rowc['phone'] ?? '';
                    $compfax = $rowc['fax'] ?? '';
                    $compiemail = $rowc['email'] ?? '';
                }
                $stmtcomp->close();

                if($row['weighted_by'] != null){
                    if ($select_stmt2 = $db->prepare("select * FROM users WHERE id=?")) {
                        $select_stmt2->bind_param('s', $row['weighted_by']);
    
                        if ($select_stmt2->execute()) {
                            $result2 = $select_stmt2->get_result();
    
                            if ($row2= $result2->fetch_assoc()) { 
                                $userName = $row2['name'];
                            }
                        }

                        $select_stmt2->close();
                    }
                }

                // Footer Processing
                $totalBags = floatval($row['total_item']);
                $totalWeight = floatval($row['total_weight']);
                $totalItems = 0;
                foreach ($groupedData as $productId => $productData) {
                    foreach ($productData['grades'] as $grade => $items) {
                        foreach($items as $item){
                            $totalItems += floatval($item['itemPerPack']) ?? 0;
                        }
                    }
                }
                
                $average = $totalWeight/$totalItems;
                
                $companyNameUpper = strtoupper($compname);
                $showInlineReg = strlen($compname) <= 20;
                $message = '
                    <html>
                        <head>
                            <script src="https://unpkg.com/pagedjs/dist/paged.polyfill.js"></script>
                            <style>
                                @page {
                                    margin-left: .3in;
                                    margin-right: .3in;
                                    margin-top: 2.5in;
                                    margin-bottom: 2.5in;

                                    @top-center {
                                        content: element(page-header);
                                    }

                                    @bottom-center {
                                        content: element(page-footer);
                                    }
                                }

                                .record {
                                    page-break-after: always;
                                }

                                .page-header {
                                    position: running(page-header);
                                    font-size: 12px;
                                }

                                .page-footer {
                                    position: running(page-footer);
                                    font-size: 12px;
                                }

                                .page-number::after {
                                    content: counter(page);
                                }

                                .total-pages::after {
                                    content: counter(pages);
                                }

                                .keep-with-next {
                                    break-after: avoid-page;
                                    page-break-after: avoid;
                                }

                                .avoid-break {
                                    break-inside: avoid;
                                    page-break-inside: avoid;
                                }

                                table.avoid-break {
                                    break-inside: avoid;
                                }

                                .page-number, .total-pages {
                                    font-weight: bold;
                                    color: #000;
                                    display: inline;
                                }

                                .group-container {
                                    break-inside: avoid;
                                    page-break-inside: avoid;
                                    margin-bottom: 5px;
                                }

                                .house-container {
                                    margin-bottom: 5px;
                                }

                                .house-table {
                                    break-inside: auto;
                                }

                                /* Ensure group stays together but allow page breaks between groups */
                                .group-container + .group-container {
                                    break-before: auto;
                                    page-break-before: auto;
                                }
                                
                                table {
                                    width: 100%;
                                    border-collapse: collapse;
                                } 
                                
                                .table th, .table td {
                                    padding: 0.70rem;
                                    vertical-align: top;
                                    border-top: 1px solid #dee2e6;
                                } 
                                
                                .table-bordered {
                                    border: 1px solid #000000;
                                } 
                                
                                .table-bordered th, .table-bordered td {
                                    border: 1px solid #000000;
                                    font-family: sans-serif;
                                } 
                                
                                .row {
                                    display: flex;
                                    flex-wrap: wrap;
                                    margin-top: 20px;
                                } 
                                
                                .col-md-3{
                                    position: relative;
                                    width: 25%;
                                }
                                
                                .col-md-9{
                                    position: relative;
                                    width: 75%;
                                }
                                
                                .col-md-7{
                                    position: relative;
                                    width: 58.333333%;
                                }
                                
                                .col-md-5{
                                    position: relative;
                                    width: 41.666667%;
                                }
                                
                                .col-md-6{
                                    position: relative;
                                    width: 50%;
                                }
                                
                                .col-md-4{
                                    position: relative;
                                    width: 33.333333%;
                                }
                                
                                .col-md-8{
                                    position: relative;
                                    width: 66.666667%;
                                }
                                
                                /* Force consistent column widths for weight tables */
                                .table tr td:first-child {
                                    width: 10% !important;
                                    text-align: left !important;
                                }
                                .table tr td[colspan="10"] {
                                    width: 90% !important;
                                }
                                /* Ensure uniform 10-column grid layout */
                                .table tr td {
                                    width: 9% !important;
                                    min-width: 9%;
                                    box-sizing: border-box;
                                }
                                .table tr td:first-child {
                                    width: 10% !important;
                                    min-width: 10%;
                                }
                            </style>
                        </head>
                        
                        <body>';
                        // HEADER SECTION - Fixed on every page
                        $message .= '
                            <section class="record">
                                <div class="page-header">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td style="width: 60%;border-top: 0px;">
                                                    <p>';
                                                        $companyFontSize = (mb_strlen($companyNameUpper) > 20) ? '16px' : '20px';

                                                        if ($showInlineReg) {
                                                            $message .= '
                                                                <span style="font-weight: bold; font-size: ' . $companyFontSize . ';">' . $companyNameUpper . '</span>
                                                                <span style="font-size: 12px;"> (' . $compreg . ')</span><br>
                                                            ';
                                                        } else {
                                                            $message .= '
                                                                <span style="font-weight: bold; font-size: ' . $companyFontSize . ';">' . $companyNameUpper . '</span>
                                                                <span style="font-size: 12px;"> (' . $compreg . ')</span><br>
                                                            ';
                                                        }
                                                        
                                                        // Address & contact info
                                                        $message .= '
                                                        <span style="font-size: 14px;">' . $compaddress . ' ' . ($compaddress2 ?? '') . '</span><br>
                                                        <span style="font-size: 14px;">' . ($compaddress3 ?? '') . ' ' . ($compaddress4 ?? '') . '</span><br>
                                                        <span style="font-size: 14px;">Tel: ' . ($compphone ?? '') . '  Fax: ' . ($compfax ?? '') . '</span><br>
                                                        <span style="font-size: 14px;">Email: ' . ($compiemail ?? '') . '</span><br>
                                                    </p>
                                                </td>
                                                <td style="vertical-align: top; text-align: right;border-top: 0px;">
                                                    <p style="margin-left: 50px;">
                                                        <span style="font-size: 20px; font-weight: bold;">DELIVERY ORDER</span><br>
                                                    </p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>';

                                    $csLabel = $row['status'] == 'SALES' ? 'Customer' : 'Supplier';
                                    $csValue = '-';
                                    if ($row['status'] == 'SALES' && !empty($row['customer'])) {
                                        if ($stmtcs = $db->prepare("select * FROM customers WHERE id=?")) {
                                            $stmtcs->bind_param('s', $row['customer']);
                        
                                            if ($stmtcs->execute()) {
                                                $rcs = $stmtcs->get_result();
                        
                                                if ($rcsRow= $rcs->fetch_assoc()) { var_dump($rcsRow);
                                                    $csValue = $rcsRow['customer_name'];
                                                }
                                            }

                                            $stmtcs->close();
                                        }
                                    } elseif ($row['status'] != 'SALES' && !empty($row['supplier'])) {
                                        if ($stmtcs = $db->prepare("select * FROM supplies WHERE id=?")) {
                                            $stmtcs->bind_param('s', $row['supplier']);
                        
                                            if ($stmtcs->execute()) {
                                                $rcs = $stmtcs->get_result();
                        
                                                if ($rcsRow= $rcs->fetch_assoc()) { 
                                                    $csValue = $rcsRow['supplier_name'];
                                                }
                                            }

                                            $stmtcs->close();
                                        }
                                    }
                                    $crateAvg = $totalCrates > 0 ? number_format($totalCrate / $totalCrates, 2) : '0.00';
                                    $nettWt = number_format($totalGross - $totalCrate, 2);
                                    $tdS = 'width:50%;border-top:0;padding:0 0.7rem;';
                                    $lS = 'font-size:12px;font-family:sans-serif;font-weight:bold;display:inline-block;width:110px;';
                                    $vS = 'font-size:12px;font-family:sans-serif;';
                                    $vBS = 'font-size:12px;font-family:sans-serif;font-weight:bold;';

                                    $tdS = 'width:33.33%;border-top:0;padding:0 0.7rem;';

                                    $message .= '
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td style="'.$tdS.'"><p><span style="'.$lS.'">'.$csLabel.'</span><span style="'.$vBS.'">'.$csValue.'</span></p></td>
                                                <td style="'.$tdS.'"><p><span style="'.$lS.'">Serial No</span><span style="'.$vS.'">'.$row['serial_no'].'</span></p></td>
                                                <td style="'.$tdS.'"><p><span style="'.$lS.'">DO No.</span><span style="'.$vBS.'color:red;">'.$row['po_no'].'</span></p></td>
                                            </tr>
                                            <tr>
                                                <td style="'.$tdS.'"><p><span style="'.$lS.'">Lorry No.</span><span style="'.$vS.'">'.$row['vehicle_no'].'</span></p></td>
                                                <td style="'.$tdS.'"><p><span style="'.$lS.'">Driver</span><span style="'.$vS.'">'.$row['driver'].'</span></p></td>
                                                <td style="'.$tdS.'"><p><span style="'.$lS.'">Date</span><span style="'.$vS.'">'.$row['created_datetime'].'</span></p></td>
                                            </tr>
                                            <tr>
                                                <td style="'.$tdS.'"><p><span style="'.$lS.'">Issued By</span><span style="'.$vS.'">'.$userName.'</span></p></td>
                                                <td style="'.$tdS.'"><p><span style="'.$lS.'">First Record</span><span style="'.$vS.'">'.$row['created_datetime'].'</span></p></td>
                                                <td style="'.$tdS.'"><p><span style="'.$lS.'">Last Record</span><span style="'.$vS.'">'.$row['end_time'].'</span></p></td>
                                            </tr>
                                            <tr>
                                                <td style="'.$tdS.'"><p><span style="'.$lS.'">Total Count</span><span style="'.$vS.'">'.$totalCrates.'</span></p></td>
                                                <td style="'.$tdS.'"><p><span style="'.$lS.'">Nett Wt (kg)</span><span style="'.$vS.'">'.$nettWt.'</span></p></td>
                                                <td style="'.$tdS.'"><p><span style="'.$lS.'">Duration</span><span style="'.$vS.'">'.$time.'</span></p></td>
                                            </tr>
                                            <tr>
                                                <td style="'.$tdS.'"><p><span style="'.$lS.'">Remark</span><span style="'.$vBS.'">'.$row['remark'].'</span></p></td>
                                                <td style="'.$tdS.'"></td>
                                                <td style="'.$tdS.'">
                                                    <p>
                                                        <span style="'.$lS.'">Page No.</span>
                                                        <span style="'.$vBS.'" class="page-number"></span>
                                                        <span style="'.$vBS.'"> of </span>
                                                        <span style="'.$vBS.'" class="total-pages"></span>
                                                    </p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="page-footer">
                                    <hr>
                                    <table class="table" style="width:100%; border:0">
                                        <tbody>
                                            <tr>
                                                <td style="width:50%;vertical-align:top;">
                                                    <p style="font-size:12px;font-family:sans-serif;"><b>SUMMARY - TOTAL</b></p>
                                                    <table class="table">
                                                        <tbody>
                                                            <tr>
                                                                <td style="width: 40%;border-top:0px;padding: 0 0.7rem;font-size: 12px;font-family: sans-serif;font-weight: bold;">Total Bags</td>
                                                                <td style="width: 20%;border-top:0px;padding: 0 0.7rem;border: 1px solid #000000;font-size: 12px;font-family: sans-serif;text-align: center;">'.$totalBags.'</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width: 40%;border-top:0px;padding: 0 0.7rem;font-size: 12px;font-family: sans-serif;font-weight: bold;">Total Weight (kg)</td>
                                                                <td style="width: 20%;border-top:0px;padding: 0 0.7rem;border: 1px solid #000000;font-size: 12px;font-family: sans-serif;text-align: center;">'.number_format($totalWeight, 2).'</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width: 40%;border-top:0px;padding: 0 0.7rem;font-size: 12px;font-family: sans-serif;font-weight: bold;">Total Items</td>
                                                                <td style="width: 20%;border-top:0px;padding: 0 0.7rem;border: 1px solid #000000;font-size: 12px;font-family: sans-serif;text-align: center;">'.$totalItems.'</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width: 40%;border-top:0px;padding: 0 0.7rem;font-size: 12px;font-family: sans-serif;font-weight: bold;">Average (kg)</td>
                                                                <td style="width: 20%;border-top:0px;padding: 0 0.7rem;border: 1px solid #000000;font-size: 12px;font-family: sans-serif;text-align: center;">'.number_format($average, 2).'</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </td>
                                                <td style="width:50%;vertical-align:top;">
                                                    <p style="font-size:12px;font-family:sans-serif;"><b>SUMMARY - Grade</b></p>
                                                    <table class="table">
                                                        <tbody>
                                                            <tr>
                                                                <th style="width: 20%;border-top:0px;padding: 0 0.7rem;border: 1px solid #000000;font-size: 12px;font-family: sans-serif;background-color: silver;">Grade</th>
                                                                <th style="width: 20%;border-top:0px;padding: 0 0.7rem;border: 1px solid #000000;font-size: 12px;font-family: sans-serif;background-color: silver;">Bags</th>
                                                                <th style="width: 20%;border-top:0px;padding: 0 0.7rem;border: 1px solid #000000;font-size: 12px;font-family: sans-serif;background-color: silver;">Weight (kg)</th>
                                                                <th style="width: 20%;border-top:0px;padding: 0 0.7rem;border: 1px solid #000000;font-size: 12px;font-family: sans-serif;background-color: silver;">Items</th>
                                                                <th style="width: 20%;border-top:0px;padding: 0 0.7rem;border: 1px solid #000000;font-size: 12px;font-family: sans-serif;background-color: silver;">Average (kg)</th>
                                                            </tr>';
                                                        $gradeSummary = [];
                                                        foreach ($groupedData as $productId => $productData) {
                                                            foreach ($productData['grades'] as $grade => $items) {
                                                                if (!isset($gradeSummary[$grade])) $gradeSummary[$grade] = ['bags' => 0, 'itemsPerPack' => 0, 'net' => 0.0];
                                                                foreach ($items as $item) {
                                                                    $gradeSummary[$grade]['bags']++;
                                                                    $gradeSummary[$grade]['itemsPerPack'] += floatval($item['itemPerPack'] ?? 0);
                                                                    $gradeSummary[$grade]['net'] += floatval($item['net'] ?? 0);
                                                                }
                                                            }
                                                        }
                                                        foreach ($gradeSummary as $grade => $data) {
                                                            $avg = $data['itemsPerPack'] > 0 ? number_format($data['net'] / $data['itemsPerPack'], 3, '.', '') : '-';
                                                            $message .= '<tr>';
                                                            $message .= '<td style="border-top:0px;padding:0 0.7rem;border:1px solid #000000;font-size:12px;font-family:sans-serif;">'.$grade.'</td>';
                                                            $message .= '<td style="border-top:0px;padding:0 0.7rem;border:1px solid #000000;font-size:12px;font-family:sans-serif;text-align:center;">'.$data['bags'].'</td>';
                                                            $message .= '<td style="border-top:0px;padding:0 0.7rem;border:1px solid #000000;font-size:12px;font-family:sans-serif;text-align:center;">'.number_format($data['net'], 2, '.', '').'</td>';
                                                            $message .= '<td style="border-top:0px;padding:0 0.7rem;border:1px solid #000000;font-size:12px;font-family:sans-serif;text-align:center;">'.$data['itemsPerPack'].'</td>';
                                                            $message .= '<td style="border-top:0px;padding:0 0.7rem;border:1px solid #000000;font-size:12px;font-family:sans-serif;text-align:center;">'.number_format($avg,2).'</td>';
                                                            $message .= '</tr>';
                                                        }

                                                $message .='
                                                        </tbody>
                                                    </table> 
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="page-content">';
                                    if (!empty($groupedData)) {
                                        foreach ($groupedData as $productId => $productData) {
                                            $message .= '<div class="avoid-break group-container">';
                                            $message .= '<p style="margin:0px;"><u style="color:blue;">Product: ' . $productData['product_name'] . '</u></p>';

                                            foreach ($productData['grades'] as $grade => $items) {
                                                $message .= '<p style="margin:0px;">Grade: ' . $grade . '</p>';
                                                $message .= '<table class="table house-table" style="margin-bottom:10px"><tbody>';
                                                $message .= '<tr style="border-top:1px solid #000;border-bottom:1px solid #000;">';
                                                $message .= '<td style="width:20%;border-top:0;padding:0 0.7rem;"><p><span style="font-size:12px;font-family:sans-serif;font-weight:bold;">No</span></p></td>';
                                                $message .= '<td colspan="10" style="width:80%;border-top:0;padding:0 0.7rem;"><p><span style="font-size:12px;font-family:sans-serif;font-weight:bold;">Gross (kg) / Items Per Pack</span></p></td></tr>';

                                                $count = 0; $indexCount2 = 11;
                                                $indexString = '<tr><td style="border-top:0;padding:0 0.7rem;"><p><span style="font-size:12px;font-family:sans-serif;font-weight:bold;">1</span></p></td>';

                                                foreach ($items as $element) {
                                                    $cellVal = $element['gross'] . '/' . ($element['itemPerPack'] ?? '-');
                                                    if ($count < 10) {
                                                        $indexString .= '<td style="border-top:0;padding:0 0.7rem;width:10%;"><p><span style="font-size:12px;font-family:sans-serif;">' . $cellVal . '</span></p></td>';
                                                        $count++;
                                                    } else {
                                                        $indexString .= '</tr><tr><td style="border-top:0;padding:0 0.7rem;width:20%;"><p><span style="font-size:12px;font-family:sans-serif;font-weight:bold;">' . $indexCount2 . '</span></p></td>';
                                                        $indexCount2 += 10;
                                                        $indexString .= '<td style="border-top:0;padding:0 0.7rem;width:10%;"><p><span style="font-size:12px;font-family:sans-serif;">' . $cellVal . '</span></p></td>';
                                                        $count = 1;
                                                    }
                                                }
                                                for ($k = 0; $k < (10 - $count); $k++) {
                                                    $indexString .= '<td style="border-top:0;padding:0 0.7rem;width:10%;"></td>';
                                                }
                                                $indexString .= '</tr>';
                                                $message .= $indexString . '</tbody></table>';
                                            }
                                            $message .= '</div>';
                                        }
                                    }

                                $message .= '</div>
                            </section>
                        </body>
                    </html>';

                echo $message;
                echo '
                    <script src="plugins/jquery/jquery.min.js"></script>
                    <script src="plugins/jquery-validation/jquery.validate.min.js"></script>

                    <script>
                        $(document).ready(function () {
                            PagedPolyfill.preview().then(() => {
                                const buttonWrapper = document.createElement("div");
                                buttonWrapper.className = "print-button-wrapper";
                                buttonWrapper.setAttribute("data-pagedjs-ignore", "");
                                buttonWrapper.style.position = "fixed";
                                buttonWrapper.style.bottom = "20px";
                                buttonWrapper.style.left = "50%";
                                buttonWrapper.style.transform = "translateX(-50%)";
                                buttonWrapper.style.zIndex = "9999";

                                const printButton = document.createElement("button");
                                printButton.textContent = "🖨️ Print Preview";
                                printButton.style.background = "#007bff"; // Bootstrap blue
                                printButton.style.color = "#fff";
                                printButton.style.border = "none";
                                printButton.style.padding = "10px 20px";
                                printButton.style.borderRadius = "6px";
                                printButton.style.cursor = "pointer";
                                printButton.style.fontSize = "14px";
                                printButton.style.fontWeight = "500";
                                printButton.style.fontFamily = "Segoe UI, sans-serif";
                                printButton.style.boxShadow = "0 2px 6px rgba(0,0,0,0.15)";
                                printButton.style.transition = "background 0.3s ease";

                                printButton.onmouseover = () => {
                                    printButton.style.background = "#0056b3"; // darker on hover
                                };
                                printButton.onmouseout = () => {
                                    printButton.style.background = "#007bff";
                                };

                                printButton.onclick = function () {
                                    buttonWrapper.style.display = "none";
                                    setTimeout(() => {
                                        document.title = "'.$fileName.'";
                                        window.print();
                                        window.close();
                                    }, 100);
                                };

                                buttonWrapper.appendChild(printButton);
                                document.body.appendChild(buttonWrapper);
                            });
                        });
                    </script>
                ';
            }
            else{
                echo json_encode(
                    array(
                        "status" => "failed",
                        "message" => "Data Not Found"
                    )); 
            }
        }
    }
    else{
        echo json_encode(
            array(
                "status" => "failed",
                "message" => "Something went wrong"
            )); 
    }
    
}
else{
    echo json_encode(
        array(
            "status"=> "failed", 
            "message"=> "Please fill in all the fields"
        )
    ); 
}

?>