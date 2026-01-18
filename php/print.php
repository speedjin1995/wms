<?php
require_once 'db_connect.php';
require_once 'lookup.php';

function arrangeByGrade($weighingDetails) {
    $arranged = [];
    $earliest_time = null;
    $latest_time = null;
    
    if(isset($weighingDetails) && !empty($weighingDetails)) {
        foreach($weighingDetails as $detail) {
            $product = $detail['product'] ?? 'Unknown';
            $grade = $detail['grade'] ?? 'Unknown';
            $key = $product . ' - ' . $grade;
            
            if(!isset($arranged[$key])) {
                $arranged[$key] = [];
            }
            $arranged[$key][] = $detail;
            
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

if(isset($_POST['userID'])){
    $id = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_STRING);

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

                // Build weight details tables
                $weightDetails = '';
                $grades = array_keys($arrangedData['arranged']);
                $totalGrades = count($grades);
                $rowsNeeded = ceil($totalGrades / 3);
                
                $totalCages = 0;
                $totalCagesWeight = 0;
                for($row = 0; $row < $rowsNeeded; $row++) {
                    if($row > 0 && $row % 2 == 0) {
                        $weightDetails .= '<div class="row mb-3 page-break">';
                    } else {
                        $weightDetails .= '<div class="row mb-3">';
                    }
                    
                    for($col = 0; $col < 3; $col++) {
                        $gradeIndex = $row * 3 + $col;
                        if($gradeIndex < $totalGrades) {
                            $key = $grades[$gradeIndex];
                            $product = searchProductNameById(explode(' - ', $key)[0], $db);
                            $grade = explode(' - ', $key)[1];
                            $items = $arrangedData['arranged'][$key]; 
                            
                            $weightDetails .= '<div class="col-4">';
                            $weightDetails .= '<table class="grade-table">';
                            $weightDetails .= '<tr style="font-weight: bold; background-color: #f0f0f0;"><td colspan="4">'.$product.' GRADE : ' . $grade . '</td></tr>';
                            $weightDetails .= '<tr><th>No</th><th>Gross Weight</th><th>Tare Weight</th><th>Net Weight</th></tr>';
                            
                            $totalGross = 0;
                            $totalTare = 0;
                            $totalNet = 0;
                            $totalPrice = 0;
                            
                            for($i = 0; $i < 10; $i++) {
                                if($i < count($items)) {
                                    $totalCages += 1;
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

                                    $totalCagesWeight += $tare;
                                } else {
                                    $gross = $tare = $net = $price = '';
                                    $totalPrice += 0;
                                    $totalCagesWeight += 0;
                                }

                                $totalGross += $gross != '' ? $gross : 0;
                                $totalTare += $tare != '' ? $tare : 0;
                                $totalNet += $net != '' ? $net : 0;
                                
                                $weightDetails .= '<tr>';
                                $weightDetails .= '<td>' . ($i + 1) . '</td>';
                                $weightDetails .= '<td>' . ($gross != '' ? number_format($gross, 1) . ' kg' : '') . '</td>';
                                $weightDetails .= '<td>' . ($tare != '' ? number_format($tare, 1) . ' kg' : '') . '</td>';
                                $weightDetails .= '<td>' . ($net != '' ? number_format($net, 1) . ' kg' : '') . '</td>';
                                $weightDetails .= '</tr>';
                            }
                            
                            $weightDetails .= '<tr style="font-weight: bold;">';
                            $weightDetails .= '<td style="border-right: none;">T</td>';
                            $weightDetails .= '<td style="border-left: none; border-right: none;">' . number_format($totalGross, 1) . ' kg</td>';
                            $weightDetails .= '<td style="border-left: none; border-right: none;">' . number_format($totalTare, 1) . ' kg</td>';
                            $weightDetails .= '<td style="border-left: none;">' . number_format($totalNet, 1) . ' kg</td>';
                            $weightDetails .= '</tr>';
                            $weightDetails .= '<tr>';
                            $weightDetails .= '<td colspan="2">Price /kg</td>';
                            $weightDetails .= '<td colspan="2">RM ' . number_format($totalPrice, 2) . '</td>';
                            $weightDetails .= '</tr>';
                            $weightDetails .= '</table>';
                            $weightDetails .= '</div>';
                        }
                    }
                    
                    $weightDetails .= '</div>';
                }
                
                // Add reject table as the last table
                if (isset($wholesale['reject_details']) && !empty($wholesale['reject_details'])) {
                    $rejectDetails = json_decode($wholesale['reject_details'], true);
                    $lastRowCols = $totalGrades % 3;
                    if($lastRowCols == 0) $lastRowCols = 3;
                    $weightDetails .= '<div class="row">';
                    $weightDetails .= '<div class="col-4">';
                    $weightDetails .= '<table class="grade-table">';
                    $weightDetails .= '<tr style="font-weight: bold; background-color: #f0f0f0;"><td colspan="4">REJECT</td></tr>';
                    $weightDetails .= '<tr><th>No</th><th>Gross Weight</th><th>Tare Weight</th><th>Net Weight</th></tr>';
                    
                    $rejectGross = 0;
                    $rejectTare = 0;
                    $rejectNet = 0;
                    $rejectPrice = 0;
                    
                    for($i = 0; $i < 10; $i++) {
                        if($i < count($rejectDetails)) {
                            $item = $rejectDetails[$i];
                            $gross = floatval($item['gross'] ?? 0);
                            $tare = floatval($item['tare'] ?? 0);
                            $net = floatval($item['net'] ?? 0);
                            $price = floatval($item['price'] ?? 0);
                            $pricingType = $item['fixedfloat'];
                            
                            if ($pricingType == 'fixed') {
                                $rejectPrice += $price ?? 0;
                            } else {
                                $rejectPrice += $net * ($price ?? 0);
                            }
                        } else {
                            $gross = $tare = $net = '';
                        }
                        
                        $rejectGross += $gross != '' ? $gross : 0;
                        $rejectTare += $tare != '' ? $tare : 0;
                        $rejectNet += $net != '' ? $net : 0;
                        
                        $weightDetails .= '<tr>';
                        $weightDetails .= '<td>' . ($i + 1) . '</td>';
                        $weightDetails .= '<td>' . ($gross != '' ? number_format($gross, 1) . ' kg' : '') . '</td>';
                        $weightDetails .= '<td>' . ($tare != '' ? number_format($tare, 1) . ' kg' : '') . '</td>';
                        $weightDetails .= '<td>' . ($net != '' ? number_format($net, 1) . ' kg' : '') . '</td>';
                        $weightDetails .= '</tr>';
                    }
                    
                    $weightDetails .= '<tr style="font-weight: bold;">';
                    $weightDetails .= '<td style="border-right: none;">T</td>';
                    $weightDetails .= '<td style="border-left: none; border-right: none;">' . number_format($rejectGross, 1) . ' kg</td>';
                    $weightDetails .= '<td style="border-left: none; border-right: none;">' . number_format($rejectTare, 1) . ' kg</td>';
                    $weightDetails .= '<td style="border-left: none;">' . number_format($rejectNet, 1) . ' kg</td>';
                    $weightDetails .= '</tr>';
                    $weightDetails .= '<tr><td colspan="2">Price /kg</td><td colspan="2">RM ' . number_format($rejectPrice, 2) . '</td></tr>';
                    $weightDetails .= '</table>';
                    $weightDetails .= '</div>';
                    $weightDetails .= '</div>';
                }

            $message = '
                <html>
                <head>
                    <script src="https://unpkg.com/pagedjs/dist/paged.polyfill.js"></script>
                    <style>
                        /* Bootstrap CSS */
                        .container-fluid { width: 100%; padding-right: 10px; padding-left: 10px; margin-right: auto; margin-left: auto; }
                        .row { display: flex; flex-wrap: wrap; margin-right: -5px; margin-left: -5px; }
                        .col-1 { position: relative; width: 100%; padding-right: 5px; padding-left: 5px; flex: 0 0 8.333333%; max-width: 8.333333%; box-sizing: border-box; }
                        .col-2 { position: relative; width: 100%; padding-right: 5px; padding-left: 5px; flex: 0 0 16.666667%; max-width: 16.666667%; box-sizing: border-box; }
                        .col-3 { position: relative; width: 100%; padding-right: 5px; padding-left: 5px; flex: 0 0 25%; max-width: 25%; box-sizing: border-box; }
                        .col-4 { position: relative; width: 100%; padding-right: 5px; padding-left: 5px; flex: 0 0 33.333333%; max-width: 33.333333%; box-sizing: border-box; }
                        .col-5 { position: relative; width: 100%; padding-right: 5px; padding-left: 5px; flex: 0 0 41.666667%; max-width: 41.666667%; box-sizing: border-box; }
                        .col-6 { position: relative; width: 100%; padding-right: 5px; padding-left: 5px; flex: 0 0 50%; max-width: 50%; box-sizing: border-box; }
                        .col-7 { position: relative; width: 100%; padding-right: 5px; padding-left: 5px; flex: 0 0 58.333333%; max-width: 58.333333%; box-sizing: border-box; }
                        .col-8 { position: relative; width: 100%; padding-right: 5px; padding-left: 5px; flex: 0 0 66.666667%; max-width: 66.666667%; box-sizing: border-box; }
                        .col-9 { position: relative; width: 100%; padding-right: 5px; padding-left: 5px; flex: 0 0 75%; max-width: 75%; box-sizing: border-box; }
                        .col-10 { position: relative; width: 100%; padding-right: 5px; padding-left: 5px; flex: 0 0 83.333333%; max-width: 83.333333%; box-sizing: border-box; }
                        .col-11 { position: relative; width: 100%; padding-right: 5px; padding-left: 5px; flex: 0 0 91.666667%; max-width: 91.666667%; box-sizing: border-box; }
                        .col-12 { position: relative; width: 100%; padding-right: 5px; padding-left: 5px; flex: 0 0 100%; max-width: 100%; box-sizing: border-box; }
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
                        .address { font-size: 14px; }
                        .title { font-size: 18px; }
                        .transaction-id { font-size: 14px; }
                        .info-row { margin-bottom: 5px; font-size: 14px; display: flex; }
                        .info-label { width: 120px; flex-shrink: 0; }
                        .info-value { flex: 1; }
                        .header-row { margin-bottom: 5px; font-size: 14px; display: flex; }
                        .header-label { width: 120px; flex-shrink: 0; }
                        .header-value { flex: 1; }
                        .grade-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
                        .grade-table th, .grade-table td { border: 1px solid black; padding: 5px; text-align: center; font-size: 10px; }
                        .grade-table th { background-color: #f0f0f0; }
                        .grade-table .no-border-sides { border-left: none; border-right: none; }

                        /* Paged.js styles */
                        @page {
                            size: A4;
                            margin: 80mm 5mm 20mm 5mm;
                            @top-left {
                                content: element(running-header);
                            }
                        }

                        .running-header {
                            position: running(running-header);
                            width: 100%;
                            text-align: left;
                        }

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
                        <div class="row mb-1">
                            <div class="col-8">
                                <div class="company-name">'.$wholesale['name'].'</div>
                                <div class="address">'.$wholesale['address'].'</div>
                                <div class="address">'.$wholesale['address2'].'</div>
                                <div class="address">'.$wholesale['address3'].'</div>
                                <div class="address">'.$wholesale['address4'].'</div>
                            </div>
                            <div class="col-4">
                                <div class="header-row"><span class="header-label">Transaction ID</span><span class="header-value">: '.$wholesale['serial_no'].'</span></div>
                                <div class="header-row"><span class="header-label">Status</span><span class="header-value">: '.($wholesale['status'] == 'DISPATCH' ? 'Dispatch' : 'Incoming').'</span></div>
                                <div class="header-row"><span class="header-label">From Date</span><span class="header-value">: '.date('d/m/Y', strtotime($wholesale['created_datetime'])).'</span></div>
                                <div class="header-row"><span class="header-label">Purchase No</span><span class="header-value">: '.$wholesale['po_no'].'</span></div>
                            </div>
                        </div>
                        <hr>
                        
                        <div class="row mb-1">
                            <div class="col-8">
                                <div class="info-row"><span class="info-label">To '.($wholesale['status'] == 'DISPATCH' ? 'Customer' : 'Supplier').'</span><span class="info-value">: '.($wholesale['status'] == 'DISPATCH' ? searchCustomerNameById($wholesale['customer'], $wholesale['other_customer'], $db) : searchSupplierNameById($wholesale['supplier'], $wholesale['other_supplier'], $db)).'</span></div>
                                <div class="info-row"><span class="info-label">Driver Name</span><span class="info-value">: '.$wholesale['driver'].'</span></div>
                                <div class="info-row"><span class="info-label">Driver IC</span><span class="info-value">: '.$wholesale['driver_ic'].'</span></div>
                                <div class="info-row"><span class="info-label">Actual Weight</span><span class="info-value">: '.number_format(floatval($wholesale['total_weight']) + floatval($wholesale['total_reject']), 2).' kg</span></div>
                                <div class="info-row"><span class="info-label">Reject Weight (kg)</span><span class="info-value">: '.number_format($wholesale['total_reject'], 2).' kg</span></div>
                                <div class="info-row"><span class="info-label">Total Weight (kg)</span><span class="info-value">: '.number_format($wholesale['total_weight'], 2).' kg</span></div>
                                <div class="info-row"><span class="info-label">Remark</span><span class="info-value">: '.$wholesale['remark'].'</span></div>
                            </div>
                            <div class="col-4">
                                <div class="info-row"><span class="info-label">To Vehicle No</span><span class="info-value">: '.$wholesale['vehicle_no'].'</span></div>
                                <div class="info-row"><span class="info-label">Total Cages</span><span class="info-value">: '.number_format($totalCages).'</span></div>
                                <div class="info-row"><span class="info-label">Cages Weight</span><span class="info-value">: '.number_format($totalCagesWeight, 2).' kg</span></div>
                                <div class="info-row"><span class="info-label">Weight By</span><span class="info-value">: '.searchUserNameById($wholesale['weighted_by'], $db).'</span></div>
                                <div class="info-row"><span class="info-label">Check By</span><span class="info-value">: '.$wholesale['checked_by'].'</span></div>
                                <div class="info-row"><span class="info-label">Time Start</span><span class="info-value">: '.date('H:i:s', strtotime($wholesale['created_datetime'])).'</span></div>
                                <div class="info-row"><span class="info-label">Time End</span><span class="info-value">: '.date('H:i:s', strtotime($wholesale['end_time'])).'</span></div>
                            </div>
                        </div>
                        <hr>
                    </div>

                    <div class="container-fluid">
                        <div class="grade-section page-content">'.$weightDetails.'</div>
                    </div>
                </body>
                </html>';

                echo json_encode(
                    array(
                        "status" => "success",
                        "message" => $message
                    )
                );
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