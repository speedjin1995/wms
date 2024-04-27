<?php

require_once 'php/db_connect.php';

$compids = '1';
$compname = 'SYNCTRONIX TECHNOLOGY (M) SDN BHD';
$compaddress = 'No.34, Jalan Bagan 1, Taman Bagan, 13400 Butterworth. Penang. Malaysia.';
$compphone = '6043325822';
$compiemail = 'admin@synctronix.com.my';

$mapOfWeights = array();

$totalGross = 0.0;
$totalCrate = 0.0;
$totalReduce = 0.0;
$totalNet = 0.0;
$totalCrates = 0;
$totalBirds = 0;
$totalMaleBirds = 0;
$totalMaleCages = 0;
$totalFemaleBirds = 0;
$totalFemaleCages = 0;
$totalMixedBirds = 0;
$totalMixedCages = 0;
 
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

function rearrangeList($weightDetails) {
    global $mapOfWeights, $totalGross, $totalCrate, $totalReduce, $totalNet, $totalCrates, $totalBirds, $totalMaleBirds, $totalMaleCages, $totalFemaleBirds, $totalFemaleCages, $totalMixedBirds, $totalMixedCages;

    if (!empty($weightDetails)) {
        $array1 = array(); // group
        $array2 = array(); // house

        foreach ($weightDetails as $element) {
            if(!in_array($element['groupNumber'], $array1)){
                $mapOfWeights[] = array( 
                    'groupNumber' => $element['groupNumber'],
                    'weightList' => array()
                );

                array_push($array1, $element['groupNumber']);
            }

            $key = array_search($element['groupNumber'], $array1);
            array_push($mapOfWeights[$key]['weightList'], $element);
            

            $totalGross += floatval($element['grossWeight']);
            $totalCrate += floatval($element['tareWeight']);
            $totalReduce += floatval($element['reduceWeight']);
            $totalNet += floatval($element['netWeight']);
            $totalCrates += intval($element['numberOfCages']);
            $totalBirds += intval($element['numberOfBirds']);

            if ($element['sex'] == 'Male') {
                $totalMaleBirds += intval($element['numberOfBirds']);
                $totalMaleCages += intval($element['numberOfCages']);
            } elseif ($element['sex'] == 'Female') {
                $totalFemaleBirds += intval($element['numberOfBirds']);
                $totalFemaleCages += intval($element['numberOfCages']);
            } elseif ($element['sex'] == 'Mixed') {
                $totalMixedBirds += intval($element['numberOfBirds']);
                $totalMixedCages += intval($element['numberOfCages']);
            }
        }
    }
    
    // Now you can work with $mapOfWeights and the calculated totals as needed.
}


if(isset($_GET['userID'])){
    $id = $_GET['userID'];

    if ($select_stmt = $db->prepare("select weighing.*, farms.name FROM weighing, farms WHERE weighing.farm_id = farms.id AND weighing.id=?")) {
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
                $assigned_seconds = strtotime ( $row['start_time'] );
                $completed_seconds = strtotime ( $row['end_time'] );
                $duration = $completed_seconds - $assigned_seconds;
                //$time = date ( 'j g:i:s', $duration );
                $minutes = floor($duration / 60);
                $seconds = $duration % 60;
                
                // Format minutes and seconds
                $time = sprintf('%d mins %d secs', $minutes, $seconds);
                $weightData = json_decode($row['weight_data'], true);
                $totalWeight = totalWeight($weightData);
                rearrangeList($weightData);
                $weightTime = json_decode($row['weight_time'], true);
                $userName = "Pri Name";

                if ($select_stmt2 = $db->prepare("select * FROM users WHERE id=?")) {
                    $uid = $row['weighted_by'];
                    $select_stmt2->bind_param('s', $uid);

                    if ($select_stmt2->execute()) {
                        $result2 = $select_stmt2->get_result();

                        if ($row2= $result2->fetch_assoc()) { 
                            $userName = $row2['name'];
                        }
                    }
                }

                $message = '<html>
    <head>
        <style>
            @media print {
                @page {
                    margin-left: 0.5in;
                    margin-right: 0.5in;
                    margin-top: 0.1in;
                    margin-bottom: 0.1in;
                }
                
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
                border: 1px dashed black;
                border-collapse: collapse;
            } 
            
            .table-bordered th, .table-bordered td {
                border: 1px dashed black;
                font-family: sans-serif;
            } 

            .table-full {
                border: 1px solid black;
                border-collapse: collapse;
                padding: 0 0.7rem;
            } 
            
            .table-full th, .table-full td {
                border: 1px solid black;
                font-family: sans-serif;
                padding: 0 0.7rem;
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
            
            #footer {
                position: fixed;
                padding: 10px 10px 0px 10px;
                bottom: 0;
                width: 100%;
                height: 25%;
            }
        </style>
    </head>
    
    <body>
        <table class="table">
            <tbody>
                <tr>
                    <td style="width: 100%;border-top:0px;text-align:center;"><img src="https://ccb.syncweigh.com/assets/header.png" width="100%" height="auto" /></td>
                </tr>
            </tbody>
        </table>
        
        <table class="table">
            <tbody>
                <tr>
                    <td style="width: 50%;border-top:0px;">';

                    $message .= '<p>
                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Customer : </span>
                        <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">'.$row['customer'].'</span>
                    </p>';
                        
                    $message .= '</td>
                    <td style="width: 50%;border-top:0px;">
                        <p>
                            <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">CCBSB No.: </span>
                            <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">'.$row['serial_no'].'</span>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="width: 50%;border-top:0px;padding: 0 0.7rem;">
                        <p>
                            <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Farm : </span>
                            <span style="font-size: 12px;font-family: sans-serif;">'.$row['name'].'</span>
                        </p>
                    </td>
                    <td style="width: 50%;border-top:0px;padding: 0 0.7rem;">
                        <p>
                            <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Date : </span>
                            <span style="font-size: 12px;font-family: sans-serif;">'.$row['start_time'].'</span>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="width: 50%;border-top:0px;padding: 0 0.7rem;">
                        <p>
                            <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Total Crates : </span>
                            <span style="font-size: 12px;font-family: sans-serif;">'.$row['total_cage'].'</span>
                        </p>
                    </td>
                    <td style="width: 50%;border-top:0px;padding: 0 0.7rem;">
                        <p>
                            <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Lorry No : </span>
                            <span style="font-size: 12px;font-family: sans-serif;">'.$row['lorry_no'].'</span>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="width: 50%;border-top:0px;padding: 0 0.7rem;"></td>
                    <td style="width: 40%;border-top:0px;padding: 0 0.7rem;">
                        <p>
                            <span style="font-size: 12px;font-family: sans-serif;font-weight: bold;">Average Crate Wt. : </span>
                            <span style="font-size: 12px;font-family: sans-serif;">'.number_format($row['average_cage'], 2, '.', '').'</span>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table><br>';
        

        for ($j = 0; $j < count($mapOfWeights); $j++) {
            $message .= '<p style="margin: 0px;"><u style="color: blue;">Group No. ' . $mapOfWeights[$j]['groupNumber'] . '</u></p>';
            $message .= '<table class="table-bordered"><tbody>';
            $weightData = $mapOfWeights[$j]['weightList'];

            $count = 1;
            $rowCount = 0;
            $rowTotal = 0;
            $allTotal = 0;
            $indexString = '<tr>';
            
            for ($i = 0; $i < count($weightData); $i++) {
                $indexString .= '<td style="width: 4%;text-align: center;"><b>'.$count.'</b></td><td style="width: 5%;text-align: center;">'.$weightData[$i]['grossWeight'].'</td>';
                $rowTotal += (float)$weightData[$i]['grossWeight'];
                $allTotal += (float)$weightData[$i]['grossWeight'];

                if($count % 10 == 0){
                    $indexString .= '<td style="width: 10%;text-align: center;">'.$rowTotal.'</td></tr>';
                    $rowTotal = 0;
                    $rowCount = 0;

                    if($count < count($weightData)){
                        $indexString .= '<tr>';
                    }
                }
                else{
                    $rowCount++;
                }
                
                $count++;
            }

            if ($rowCount > 0) {
                for ($k = 0; $k < (10 - $rowCount); $k++) {
                    if($k == ((10 - $rowCount) - 1)){
                        $indexString .= '<td style="width: 4%;text-align: center;"></td><td style="width: 5%;text-align: center;"></td><td style="width: 10%;text-align: center;">'.$rowTotal.'</td>';
                    }
                    else{
                        $indexString .= '<td></td><td></td>';
                    }
                }
                $indexString .= '</tr>';
            }
            
            $message .= $indexString;
            $message .= '</tbody><tfoot><th colspan="20" style="text-align: right;">Total</th><th>'.$allTotal.'</th></tfoot></table><br>';
        }
        
        $message .= '<div id="footer"><table class="table">
                    <tbody>
                        <tr>
                            <td style="width: 40%;">
                                <table class="table-full" style="width: 90%;">
                                    <tbody>
                                        <tr>
                                            <td>Total Gross Wt.</td>
                                            <td>'.number_format($totalWeight, 1, '.', '').'</td>
                                        </tr>
                                        <tr>
                                            <td>Total Crate Wt.</td>
                                            <td>'.number_format($totalCrate, 1, '.', '').'</td>
                                        </tr>
                                        <tr>
                                            <td>Total Net Wt. </td>
                                            <td>'.number_format(($totalWeight - $totalCrate), 1, '.', '').'</td>
                                        </tr>
                                        <tr>
                                            <td>Unit Price</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td>Amount</td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td style="width: 30%;">
                                <table class="table-full" style="width: 90%;">
                                    <tbody>
                                        <tr>
                                            <td>Mix.</td>
                                            <td>'.$totalMixedBirds.'</td>
                                        </tr>
                                        <tr>
                                            <td>Male</td>
                                            <td>'.$totalMaleBirds.'</td>
                                        </tr>
                                        <tr>
                                            <td>Female</td>
                                            <td>'.$totalFemaleBirds.'</td>
                                        </tr>
                                        <tr>
                                            <td>Total Birds</td>
                                            <td>'.$totalBirds.'</td>
                                        </tr>
                                        <tr>
                                            <td>Avg. Bird Wt.</td>
                                            <td>'.number_format((float)$row['average_bird'], 2, '.', '').'</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td style="width: 30%;">
                                <table class="table-full" style="width: 90%;">
                                    <tbody>
                                        <tr>
                                            <td>Loading Start</td>
                                        </tr>
                                        <tr>
                                            <td>'.$row['start_time'].'</td>
                                        </tr>
                                        <tr>
                                            <td>Loading End</td>
                                        </tr>
                                        <tr>
                                            <td>'.$row['end_time'].'</td>
                                        </tr>
                                        <tr>
                                            <td>'.$time.'</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td> 
                        </tr>
                    </tbody>
                </table></div></html>';

                echo $message;
                echo '<script>
                    setTimeout(function(){
                        window.print();
                        window.close();
                  }, 1000);
               </script>';
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