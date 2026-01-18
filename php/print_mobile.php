<?php
header("Content-Type: text/html; charset=UTF-8");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
ini_set('display_errors', 0);

require_once 'db_connect.php';
require_once 'lookup.php';

$message = '<html>';

function arrangeByGrade($weighingDetails) {
    $arranged = [];
    $earliest_time = null;
    $latest_time = null;
    
    if(isset($weighingDetails) && !empty($weighingDetails)) {
        foreach($weighingDetails as $detail) {
            $grade = $detail['grade'] ?? 'Unknown';
            if(!isset($arranged[$grade])) {
                $arranged[$grade] = [];
            }
            $arranged[$grade][] = $detail;
            
            // Track earliest and latest times
            if(isset($detail['time'])) {
                if($earliest_time == null || $detail['time'] < $earliest_time) {
                    $earliest_time = $detail['time'];
                }
                if($latest_time == null || $detail['time'] > $latest_time) {
                    $latest_time = $detail['time'];
                }
            }
        }
    }
    
    return ['arranged' => $arranged, 'earliest_time' => $earliest_time, 'latest_time' => $latest_time];
}

if(isset($_GET['userID'])){
    $id = $_GET['userID'];

    if ($select_stmt = $db->prepare("SELECT * FROM wholesales LEFT JOIN companies ON wholesales.company = companies.id WHERE wholesales.id = ?")) {
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

            if ($wholesale = $result->fetch_assoc()) {
                $weighingDetails = json_decode($wholesale['weight_details'], true);
                $arrangedData = arrangeByGrade($weighingDetails);

                $message .= '<head>
                    <style>
                        /* Bootstrap CSS */
                        .container-fluid { width: 100%; padding-right: 10px; padding-left: 10px; margin-right: auto; margin-left: auto; }
                        .row { display: flex; flex-wrap: wrap; margin-right: -5px; margin-left: -5px; }
                        .col-4 { position: relative; width: 100%; padding-right: 5px; padding-left: 5px; flex: 0 0 33.333333%; max-width: 33.333333%; box-sizing: border-box; }
                        .d-flex { display: flex !important; }
                        .justify-content-between { justify-content: space-between !important; }
                        .align-items-center { align-items: center !important; }
                        .mb-2 { margin-bottom: 0.5rem !important; }
                        .mb-3 { margin-bottom: 1rem !important; }
                        .text-center { text-align: center !important; }
                        .font-weight-bold { font-weight: 700 !important; }
                        .text-danger { color: #dc3545 !important; }
                        
                        /* Custom styles */
                        body { font-family: Arial, sans-serif; margin-left: 10px; margin-right: 30px; }
                        .company-name { font-weight: bold; font-size: 16px; }
                        .address { font-size: 16px; }
                        .title { font-size: 18px; }
                        .transaction-id { font-size: 14px; }
                        .info-row { margin-bottom: 5px; font-size: 12px; display: flex; }
                        .col-4:nth-child(1) .info-label { width: 120px; flex-shrink: 0; }
                        .col-4:nth-child(2) .info-label { width: 130px; flex-shrink: 0; }
                        .col-4:nth-child(3) .info-label { width: 90px; flex-shrink: 0; }
                        .info-value { flex: 1; }
                        .grade-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
                        .grade-table th, .grade-table td { border: 1px solid black; padding: 5px; text-align: center; font-size: 10px; }
                        .grade-table th { background-color: #f0f0f0; }
                        .grade-table .no-border-sides { border-left: none; border-right: none; }

                        .page-content {
                            margin-top: 0;
                        }
                        
                        .page-break {
                            page-break-before: always;
                            break-before: page;
                        }
                    </style>
                </head>
                <body>
                    <div class="running-header">
                        <div class="mb-1">
                            <div class="company-name">'.$wholesale['name'].'</div>
                            <div class="address">'.$wholesale['address'].' '.$wholesale['address2'].'</div>
                            <div class="address">'.$wholesale['address3'].' '.$wholesale['address4'].'</div>
                            <hr>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="title font-weight-bold">'.($wholesale['status'] == 'DISPATCH' ? 'Dispatch' : 'Receiving').' REPORT WEIGHING</div>
                            <div class="transaction-id text-danger font-weight-bold">Transaction ID : '.$wholesale['serial_no'].'</div>
                        </div>

                        <div class="row">
                            <div class="col-12" style="padding-left: 5px;">
                                <div class="info-row">From Date : '.date('d/m/Y', strtotime($wholesale['created_datetime'])).'</div>
                            </div>
                        </div>
                        
                        <div class="row mb-1">
                            <div class="col-4">
                                <div class="info-row"><span class="info-label">From '.($wholesale['status'] == 'DISPATCH' ? 'Customer' : 'Supplier').'</span><span class="info-value">: '.($wholesale['status'] == 'DISPATCH' ? searchCustomerNameById($wholesale['customer'], $wholesale['other_customer'], $db) : searchSupplierNameById($wholesale['supplier'], $wholesale['other_supplier'], $db)).'</span></div>
                                <div class="info-row"><span class="info-label">Product Description</span><span class="info-value">: '.searchProductNameById($wholesale['product'], $db).'</span></div>
                                <div class="info-row"><span class="info-label">To Vehicle Plate</span><span class="info-value">: '.$wholesale['vehicle_no'].'</span></div>
                                <div class="info-row"><span class="info-label">Driver Name</span><span class="info-value">: '.$wholesale['driver'].'</span></div>
                            </div>
                            <div class="col-4">
                                <div class="info-row"><span class="info-label">Actual Weight</span><span class="info-value">: '.number_format(floatval($wholesale['total_weight']) + floatval($wholesale['total_reject']), 2).' kg</span></div>
                                <div class="info-row"><span class="info-label">Reject Weight (kg)</span><span class="info-value">: '.number_format($wholesale['total_reject'], 2).' kg</span></div>
                                <div class="info-row"><span class="info-label">Total Weight (kg)</span><span class="info-value">: '.number_format($wholesale['total_weight'], 2).' kg</span></div>
                                <div class="info-row"><span class="info-label">Sub Total Amount</span><span class="info-value">: RM'.number_format($wholesale['total_price'], 2).'</span></div>
                            </div>
                            <div class="col-4">
                                <div class="info-row"><span class="info-label">Purchase No</span><span class="info-value">: '.$wholesale['po_no'].'</span></div>
                                <div class="info-row"><span class="info-label">Weigh By</span><span class="info-value">: '.searchUserNameById($wholesale['weighted_by'], $db).'</span></div>
                                <div class="info-row"><span class="info-label">Time Start</span><span class="info-value">: '.$arrangedData['earliest_time'].'</span></div>
                                <div class="info-row"><span class="info-label">Time End</span><span class="info-value">: '.$arrangedData['latest_time'].'</span></div>
                            </div>
                        </div>
                        <hr>
                    </div>

                    <div class="container-fluid">
                        <div class="grade-section page-content">';
                
                // Get unique grades from the arranged data
                $grades = array_keys($arrangedData['arranged']);
                
                // Display tables dynamically based on number of grades
                $totalGrades = count($grades);
                $rowsNeeded = ceil($totalGrades / 3);
                
                for($row = 0; $row < $rowsNeeded; $row++) {
                    // Add page break after every 6 grades (every 2 rows)
                    if($row > 0 && $row % 2 == 0) {
                        $message .= '<div class="row mb-3 page-break">';
                    } else {
                        $message .= '<div class="row mb-3">';
                    }
                    
                    for($col = 0; $col < 3; $col++) {
                        $gradeIndex = $row * 3 + $col;
                        if($gradeIndex < $totalGrades) {
                            $grade = $grades[$gradeIndex];
                            $items = $arrangedData['arranged'][$grade]; 
                            
                            $message .= '<div class="col-4">';
                            $message .= '<table class="grade-table">';
                            $message .= '<tr style="font-weight: bold; background-color: #f0f0f0;"><td colspan="4">GRADE : ' . $grade . '</td></tr>';
                            $message .= '<tr><th>No</th><th>Gross Weight</th><th>Tare Weight</th><th>Net Weight</th></tr>';
                            
                            $totalGross = 0;
                            $totalTare = 0;
                            $totalNet = 0;
                            $totalPrice = 0;
                            
                            // Display up to 10 items per grade
                            for($i = 0; $i < 10; $i++) {
                                if($i < count($items)) {
                                    $item = $items[$i];
                                    $gross = floatval($item['gross'] ?? 0);
                                    $tare = floatval($item['tare'] ?? 0);
                                    $net = floatval($item['net'] ?? 0);
                                    $price = floatval($item['price'] ?? 0);
                                    $pricingType = $item['fixedfloat'];

                                    if ($pricingType == 'fixed') {
                                        $totalPrice += $price ?? 0;
                                    } else {
                                        $totalPrice += $net * ($price ?? 0);
                                    }
                                } else {
                                    $gross = $tare = $net = $price = 0;
                                    $totalPrice += 0;
                                }

                                $totalGross += $gross;
                                $totalTare += $tare;
                                $totalNet += $net;
                                
                                $message .= '<tr>';
                                $message .= '<td>' . ($i + 1) . '</td>';
                                $message .= '<td>' . number_format($gross, 1) . ' kg</td>';
                                $message .= '<td>' . number_format($tare, 1) . ' kg</td>';
                                $message .= '<td>' . number_format($net, 1) . ' kg</td>';
                                $message .= '</tr>';
                            }
                            
                            $message .= '<tr style="font-weight: bold;">';
                            $message .= '<td style="border-right: none;">T</td>';
                            $message .= '<td style="border-left: none; border-right: none;">' . number_format($totalGross, 1) . ' kg</td>';
                            $message .= '<td style="border-left: none; border-right: none;">' . number_format($totalTare, 1) . ' kg</td>';
                            $message .= '<td style="border-left: none;">' . number_format($totalNet, 1) . ' kg</td>';
                            $message .= '</tr>';
                            $message .= '<tr>';
                            $message .= '<td colspan="2">Price /kg</td>';
                            $message .= '<td colspan="2">RM ' . number_format($totalPrice, 2) . '</td>';
                            $message .= '</tr>';
                            $message .= '</table>';
                            $message .= '</div>';
                        }
                    }
                    $message .= '</div>';
                }
                
                $message .= '</div>
                    </div>
                </body>
                </html>';

                echo $message;
            }
            else{
                echo "Data not found";
            }
        }
    }
    else{
        echo "Something went wrong"; 
    }
}
else{
    echo "Please fill in all the fields"; 
}

?>