<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

// function rearrangeList($weightDetails) {
//     global $mapOfHouses, $mapOfWeights, $mapOfBirdsToCages, $totalSGross, $totalSCrate, $totalSReduce, $totalSNet, $totalSBirds, $totalSCages, $totalAGross, $totalACrate, $totalAReduce, $totalANet, $totalABirds, $totalACages, $totalGross, $totalCrate, $totalReduce, $totalNet, $totalCrates, $totalBirds, $totalMaleBirds, $totalMaleCages, $totalFemaleBirds, $totalFemaleCages, $totalMixedBirds, $totalMixedCages, $totalCount, $gradeData;

//     if (!empty($weightDetails)) {
//         $array1 = array(); // group
//         $array2 = array(); // house
//         $array3 = array(); // houses map
//         $array4 = array(); // birds per cages

//         foreach ($weightDetails as $element) {
//             if (!in_array($element['groupNumber'], $array1)) {
//                 $mapOfWeights[] = array(
//                     'groupNumber' => $element['groupNumber'],
//                     'houseList' => array(),
//                     'houses' => array(),
//                     'weightList' => array()
//                 );
    
//                 array_push($array1, $element['groupNumber']);
//             }
            
//             $key1 = array_search($element['groupNumber'], $array1);

//             if (!in_array($element['houseNumber'], $mapOfWeights[$key1]['houseList'])) {
//                 $mapOfWeights[$key1]['houses'][] = array(
//                     'house' => $element['houseNumber'],
//                     'weightList' => array(),
//                     'gradeList' => array(),
//                     'grades' => array(),
//                 );
    
//                 array_push($mapOfWeights[$key1]['houseList'], $element['houseNumber']);
//             }
    
//             if (!in_array($element['houseNumber'], $array3)) {
//                 $mapOfHouses[] = array(
//                     'houseNumber' => $element['houseNumber'],
//                     'weightList' => array()
//                 );
    
//                 array_push($array3, $element['houseNumber']);
//             }
            
//             $key3 = array_search($element['houseNumber'], $array3);
//             $key2 = array_search($element['houseNumber'], $mapOfWeights[$key1]['houseList']);
//             array_push($mapOfWeights[$key1]['houses'][$key2]['weightList'], $element);

//             $houseGrade = $element['grade'];
//             if (!in_array($houseGrade, $mapOfWeights[$key1]['houses'][$key2]['gradeList'])) {
//                 $mapOfWeights[$key1]['houses'][$key2]['grades'][] = array(
//                     'grade' => $houseGrade,
//                     'weightList' => array(),
//                 );
//                 array_push($mapOfWeights[$key1]['houses'][$key2]['gradeList'], $houseGrade);
//             }
//             $keyG = array_search($houseGrade, $mapOfWeights[$key1]['houses'][$key2]['gradeList']);
//             array_push($mapOfWeights[$key1]['houses'][$key2]['grades'][$keyG]['weightList'], $element);

//             array_push($mapOfWeights[$key1]['weightList'], $element);
//             array_push($mapOfHouses[$key3]['weightList'], $element);

//             $totalGross += floatval($element['grossWeight']);
//             $totalCrate += floatval($element['tareWeight']);
//             $totalReduce += floatval($element['reduceWeight']);
//             $totalNet += floatval($element['netWeight']);
//             $totalCrates += intval($element['numberOfCages']);
//             $totalBirds += intval($element['numberOfBirds']);

//             if(!in_array($element['birdsPerCages'], $array4)){
//                 $mapOfBirdsToCages[] = array( 
//                     'numberOfBirds' => $element['birdsPerCages'],
//                     'maleCount' => 0,
//                     'femaleCount' => 0,
//                     'mixedCount' => 0
//                 );

//                 array_push($array4, $element['birdsPerCages']);
//             } 

//             $keyB = array_search($element['birdsPerCages'], $array4); 
//             if ($element['sex'] == 'Male') {
//                 $mapOfBirdsToCages[$keyB]['maleCount'] += (int)$element['numberOfCages'];
//             } elseif ($element['sex'] == 'Female') {
//                 $mapOfBirdsToCages[$keyB]['femaleCount'] += (int)$element['numberOfCages'];
//             } elseif ($element['sex'] == 'Mixed') {
//                 $mapOfBirdsToCages[$keyB]['mixedCount'] += (int)$element['numberOfCages'];
//             }

//             if ($element['sex'] == 'Male') {
//                 $totalMaleBirds += intval($element['numberOfBirds']);
//                 $totalMaleCages += intval($element['numberOfCages']);
//             } elseif ($element['sex'] == 'Female') {
//                 $totalFemaleBirds += intval($element['numberOfBirds']);
//                 $totalFemaleCages += intval($element['numberOfCages']);
//             } elseif ($element['sex'] == 'Mixed') {
//                 $totalMixedBirds += intval($element['numberOfBirds']);
//                 $totalMixedCages += intval($element['numberOfCages']);
//             }

//             if ($element['grade'] == 'S') {
//                 $totalSBirds += intval($element['numberOfBirds']);
//                 $totalSCages += intval($element['numberOfCages']);
//                 $totalSGross += floatval($element['grossWeight']);
//                 $totalSCrate += floatval($element['tareWeight']);
//                 $totalSReduce += floatval($element['reduceWeight']);
//                 $totalSNet += floatval($element['netWeight']);
//             } elseif ($element['grade'] == 'A') {
//                 $totalABirds += intval($element['numberOfBirds']);
//                 $totalACages += intval($element['numberOfCages']);
//                 $totalAGross += floatval($element['grossWeight']);
//                 $totalACrate += floatval($element['tareWeight']);
//                 $totalAReduce += floatval($element['reduceWeight']);
//                 $totalANet += floatval($element['netWeight']);
//             }

//             // Dynamic grade totaling
//             $grade = $element['grade'];
//             if (!isset($gradeData[$grade])) {
//                 $gradeData[$grade] = [
//                     'birds' => 0,
//                     'cages' => 0,
//                     'gross' => 0,
//                     'crate' => 0,
//                     'reduce' => 0,
//                     'net' => 0
//                 ];
//             }
            
//             $gradeData[$grade]['birds'] += intval($element['numberOfBirds']);
//             $gradeData[$grade]['cages'] += intval($element['numberOfCages']);
//             $gradeData[$grade]['gross'] += floatval($element['grossWeight']);
//             $gradeData[$grade]['crate'] += floatval($element['tareWeight']);
//             $gradeData[$grade]['reduce'] += floatval($element['reduceWeight']);
//             $gradeData[$grade]['net'] += floatval($element['netWeight']);
            
//             $totalCount++;
//         }
//     }
    
//     // Now you can work with $mapOfWeights and the calculated totals as needed.
// }

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
                // $totalWeight = totalWeight($weightData);
                // rearrangeList($weightData);
                // $weightTime = json_decode($row['weight_time'], true);
                // $cage_data = json_decode($row['cage_data'], true);
                $userName = "-";
                $pages = ceil($totalCount / 180);
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
                        $uid = json_decode($row['weighted_by'], true)[0];
                        $select_stmt2->bind_param('s', $uid);
    
                        if ($select_stmt2->execute()) {
                            $result2 = $select_stmt2->get_result();
    
                            if ($row2= $result2->fetch_assoc()) { 
                                $userName = $row2['name'];
                            }
                        }

                        $select_stmt2->close();
                    }
                }
                
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
                                    margin-top: 3in;
                                    margin-bottom: 3in;

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
                                                                            <span style="font-size: 12px;"> (' . $compreg . ')</span><br>';
                                                        } else {
                                                            $message .= '
                                                                            <span style="font-weight: bold; font-size: ' . $companyFontSize . ';">' . $companyNameUpper . '</span>
                                                                            <span style="font-size: 12px;"> (' . $compreg . ')</span><br>';
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
                                    
                                    $message .= '
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td colspan="2" style="width: 60%;border-top:0px;padding: 0 0.7rem;">';

                                                if($row['status'] == 'SALES'){
                                                    $message .= '<p>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Customer &nbsp;&nbsp;&nbsp;: </span>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">'.$row['customer'].'</span>
                                                    </p>';
                                                }
                                                else{
                                                    $message .= '<p>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Supplier &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: </span>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">'.$row['supplier'].'</span>
                                                    </p>';
                                                }
                                                    
                                                $message .= '</td>
                                                <td style="width: 40%;border-top:0px;padding: 0 0.7rem;">
                                                    <p>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">DO No. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: </span>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;color: red;">'.$row['po_no'].'</span>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 30%;border-top:0px;padding: 0 0.7rem;">
                                                    <p>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Serial No &nbsp;&nbsp;&nbsp;&nbsp;: </span>
                                                        <span style="font-size: 12px;font-family: sans-serif;">'.$row['serial_no'].'</span>
                                                    </p>
                                                </td>
                                                <td style="width: 40%;border-top:0px;padding: 0 0.7rem;">
                                                    <p>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Date &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: </span>
                                                        <span style="font-size: 12px;font-family: sans-serif;">'.$row['start_time'].'</span>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 30%;border-top:0px;padding: 0 0.7rem;">
                                                    <p>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Lorry No. &nbsp;&nbsp;&nbsp;&nbsp;: </span>
                                                        <span style="font-size: 12px;font-family: sans-serif;">'.$row['lorry_no'].'</span>
                                                    </p>
                                                </td>
                                                <td style="width: 30%;border-top:0px;padding: 0 0.7rem;">
                                                    <p>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Product &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: </span>
                                                        <span style="font-size: 12px;font-family: sans-serif;">'.$row['product'].'</span>
                                                    </p>
                                                </td>
                                                <td style="width: 40%;border-top:0px;padding: 0 0.7rem;">
                                                    <p>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Issued By &nbsp;&nbsp;&nbsp;&nbsp;: </span>
                                                        <span style="font-size: 12px;font-family: sans-serif;">'.$userName.'</span>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 30%;border-top:0px;padding: 0 0.7rem;">
                                                    <p>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Driver &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: </span>
                                                        <span style="font-size: 12px;font-family: sans-serif;">'.$row['driver_name'].'</span>
                                                    </p>
                                                </td>
                                                <td style="width: 30%;border-top:0px;padding: 0 0.7rem;">
                                                    <p>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Driver 2 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: </span>
                                                        <span style="font-size: 12px;font-family: sans-serif;">'.$row['driver_name2'].'</span>
                                                    </p>
                                                </td>
                                                <td style="width: 40%;border-top:0px;padding: 0 0.7rem;">
                                                    <p>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">First Record : </span>
                                                        <span style="font-size: 12px;font-family: sans-serif;">'.$row['start_time'].'</span>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 30%;border-top:0px;padding: 0 0.7rem;">
                                                    <p>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Total Count&nbsp;&nbsp;&nbsp;&nbsp;: </span>
                                                        <span style="font-size: 12px;font-family: sans-serif;">'.$totalCrates.'</span>
                                                    </p>
                                                </td>
                                                <td style="width: 40%;border-top:0px;padding: 0 0.7rem;">
                                                    <p>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Last Record : </span>
                                                        <span style="font-size: 12px;font-family: sans-serif;">'.$row['end_time'].'</span>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 30%;border-top:0px;padding: 0 0.7rem;">
                                                    <p>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Crate Wt (kg) : </span>
                                                        <span style="font-size: 12px;font-family: sans-serif;">'.($totalCrates > 0 ? (string)number_format(($totalCrate / $totalCrates), 2) : '0.00').'</span>
                                                    </p>
                                                </td>
                                                <td style="width: 40%;border-top:0px;padding: 0 0.7rem;">
                                                    <p>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Duration &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: </span>
                                                        <span style="font-size: 12px;font-family: sans-serif;">'.$time.'</span>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 30%;border-top:0px;padding: 0 0.7rem;">
                                                    <p>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Remark &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: </span>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">'.$row['remark'].'</span>
                                                    </p>
                                                </td>
                                                <td style="width: 30%;border-top:0px;padding: 0 0.7rem;">
                                                    <p>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Nett Wt (kg) &nbsp;&nbsp;: </span>
                                                        <span style="font-size: 12px;font-family: sans-serif;">'.(string)number_format(($totalGross - $totalCrate), 2).'</span>
                                                    </p>
                                                </td>
                                                <td style="width: 40%;border-top:0px;padding: 0 0.7rem;">
                                                    <p>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Page No. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: </span>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;" class="page-number"></span>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;"> of </span>
                                                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;" class="total-pages"></span>
                                                    </p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table><br>
                                </div>
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