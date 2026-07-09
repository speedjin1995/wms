<?php
require_once '../../db_connect.php';
require_once '../../lookup.php';

if(isset($_POST['userID'], $_POST['withPhoto'])){
    $id = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_STRING);
    $withPhoto = filter_input(INPUT_POST, 'withPhoto', FILTER_SANITIZE_STRING);

    if ($select_stmt = $db->prepare("SELECT g.*, c.* FROM grading g LEFT JOIN companies c ON g.company = c.id WHERE g.id = ?")) {
        $select_stmt->bind_param('s', $id);

        if (!$select_stmt->execute()) {
            echo json_encode(["status" => "failed", "message" => "Something went wrong when execute"]);
        } else {
            $result = $select_stmt->get_result();

            if ($grading = $result->fetch_assoc()) {
                $companyDetail = searchCompanyById($grading['company'], $db);
                $companyLogoSrc = !empty(trim($grading['company_logo'] ?? '')) ? '../../viewPhoto.php?file=' . urlencode(trim($grading['company_logo'])) . '&type=file_table' : '';

                $categoryName = searchCategoryById($grading['product_category'], $db);
                $locationName = searchLocationById($grading['location'], $db);
                $createdBy = searchUserNameById($grading['created_by'], $db);

                // Fetch grading items
                $items_stmt = $db->prepare("SELECT gi.*, p.product_name, CASE WHEN gi.to_grade = 'REJ' THEN 'REJ' ELSE COALESCE(g.units, gi.to_grade) END as grade_name FROM grading_items gi LEFT JOIN products p ON gi.product_id = p.id LEFT JOIN grades g ON gi.to_grade = g.id WHERE gi.grading_id = ? AND gi.deleted = '0' ORDER BY p.product_name ASC, grade_name ASC");
                $items_stmt->bind_param('s', $id);
                $items_stmt->execute();
                $items_result = $items_stmt->get_result();

                // Group items by product + grade
                $grouped = [];
                $totalCages = 0;
                $totalCagesWeight = 0;
                while ($item = $items_result->fetch_assoc()) {
                    $key = $item['product_id'] . ' - ' . $item['to_grade'];
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = ['product_name' => $item['product_name'], 'grade_name' => $item['grade_name'], 'items' => []];
                    }
                    $grouped[$key]['items'][] = $item;
                    $totalCages++;
                    $totalCagesWeight += floatval($item['tare_weight']);
                }

                // Expand into chunks of 10
                $expandedGrades = [];
                foreach ($grouped as $gradeData) {
                    $chunks = array_chunk($gradeData['items'], 10);
                    foreach ($chunks as $chunk) {
                        $expandedGrades[] = ['product_name' => $gradeData['product_name'], 'grade_name' => $gradeData['grade_name'], 'items' => $chunk];
                    }
                }

                $totalExpandedGrades = count($expandedGrades);
                $rowsNeeded = ceil($totalExpandedGrades / 3);
                $totalNetWeight = 0;

                $weightDetails = '';
                for ($row = 0; $row < $rowsNeeded; $row++) {
                    $weightDetails .= ($row > 0 && $row % 2 == 0)
                        ? '<div class="row mb-3 page-break">'
                        : '<div class="row mb-3">';

                    for ($col = 0; $col < 3; $col++) {
                        $gradeIndex = $row * 3 + $col;
                        if ($gradeIndex >= $totalExpandedGrades) break;

                        $gradeData = $expandedGrades[$gradeIndex];
                        $items = $gradeData['items'];

                        $totalGross = $totalTare = $totalNet = 0;

                        $weightDetails .= '<div class="col-4"><table class="grade-table">';
                        $weightDetails .= '<tr style="font-weight:bold;background-color:#f0f0f0;"><td colspan="4">' . htmlspecialchars($gradeData['product_name']) . ' GRADE: ' . htmlspecialchars($gradeData['grade_name']) . '</td></tr>';
                        $weightDetails .= '<tr><th>No</th><th>Gross Weight</th><th>Tare Weight</th><th>Net Weight</th></tr>';

                        for ($i = 0; $i < 10; $i++) {
                            if ($i < count($items)) {
                                $gross = floatval($items[$i]['gross_weight']);
                                $tare  = floatval($items[$i]['tare_weight']);
                                $net   = floatval($items[$i]['nett_weight']);
                                $totalGross += $gross;
                                $totalTare  += $tare;
                                $totalNet   += $net;
                                $weightDetails .= '<tr><td>' . ($i+1) . '</td><td>' . number_format($gross,2) . ' kg</td><td>' . number_format($tare,2) . ' kg</td><td>' . number_format($net,2) . ' kg</td></tr>';
                            } else {
                                $weightDetails .= '<tr><td>' . ($i+1) . '</td><td></td><td></td><td></td></tr>';
                            }
                        }

                        $totalNetWeight += $totalNet;
                        $weightDetails .= '<tr style="font-weight:bold;"><td style="border-right:none;">T</td><td style="border-left:none;border-right:none;">' . number_format($totalGross,2) . ' kg</td><td style="border-left:none;border-right:none;">' . number_format($totalTare,2) . ' kg</td><td style="border-left:none;">' . number_format($totalNet,2) . ' kg</td></tr>';
                        $weightDetails .= '</table></div>';
                    }

                    $weightDetails .= '</div>';
                }

                $message = '
                <html>
                <head>
                    <script src="https://unpkg.com/pagedjs/dist/paged.polyfill.js"></script>
                    <style>
                        .container-fluid { width:100%; padding-right:10px; padding-left:10px; margin-right:auto; margin-left:auto; }
                        .row { display:flex; flex-wrap:wrap; margin-right:-5px; margin-left:-5px; }
                        .col-4 { position:relative; width:100%; padding-right:5px; padding-left:5px; flex:0 0 33.333333%; max-width:33.333333%; box-sizing:border-box; }
                        .col-6 { position:relative; width:100%; padding-right:5px; padding-left:5px; flex:0 0 50%; max-width:50%; box-sizing:border-box; }
                        .col-8 { position:relative; width:100%; padding-right:5px; padding-left:5px; flex:0 0 66.666667%; max-width:66.666667%; box-sizing:border-box; }
                        .mb-1 { margin-bottom:0.25rem !important; }
                        .mb-3 { margin-bottom:1rem !important; }
                        body { font-family:Arial, sans-serif; margin-left:10px; margin-right:30px; }
                        .company-name { font-weight:bold; font-size:16px; }
                        .address { font-size:14px; }
                        .info-row { margin-bottom:5px; font-size:14px; display:flex; }
                        .info-label { width:120px; flex-shrink:0; }
                        .info-label2 { width:100px; flex-shrink:0; }
                        .info-value { flex:1; }
                        .grade-table { width:100%; border-collapse:collapse; margin-bottom:15px; }
                        .grade-table th, .grade-table td { border:1px solid black; padding:5px; text-align:center; font-size:10px; }
                        .grade-table th { background-color:#f0f0f0; }
                        @page {
                            size: A4;
                            margin: 90mm 5mm 5mm 5mm;
                            @top-left { content: element(running-header); }
                        }
                        .running-header { position:running(running-header); width:100%; text-align:left; }
                        .page-break { page-break-before:always; break-before:page; }
                    </style>
                </head>
                <body>
                    <div class="running-header">
                        <div class="row mb-1">
                            <div class="col-8" style="display:flex;align-items:flex-start;gap:10px;">
                                ' . ($companyLogoSrc ? '<img src="'.$companyLogoSrc.'" alt="Logo" style="width:130px;height:auto;flex-shrink:0;">' : '') . '
                                <div>
                                    <div class="company-name">' . htmlspecialchars($grading['name'] ?? '') . '</div>
                                    <div class="address">' . htmlspecialchars($grading['address'] ?? '') . '</div>
                                    <div class="address">' . htmlspecialchars($grading['address2'] ?? '') . '</div>
                                    <div class="address">' . htmlspecialchars($grading['address3'] ?? '') . '</div>
                                    <div class="address">' . htmlspecialchars($grading['address4'] ?? '') . '</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="info-row"><span class="info-label2">Grading No</span><span class="info-value">: ' . $grading['grading_no'] . '</span></div>
                                <div class="info-row"><span class="info-label2">Status</span><span class="info-value">: Grading</span></div>
                                <div class="info-row"><span class="info-label2">Date</span><span class="info-value">: ' . date('d/m/Y', strtotime($grading['start_date'])) . '</span></div>
                            </div>
                        </div>
                        <hr>
                        <div class="row mb-1">
                            <div class="col-8">
                                <div class="info-row"><span class="info-label">Category</span><span class="info-value">: ' . htmlspecialchars($categoryName) . '</span></div>
                                <div class="info-row"><span class="info-label">Location</span><span class="info-value">: ' . htmlspecialchars($locationName) . '</span></div>
                                <div class="info-row"><span class="info-label">Total Net Weight</span><span class="info-value">: ' . number_format($totalNetWeight, 2) . ' kg</span></div>
                                <div class="info-row"><span class="info-label">Remark</span><span class="info-value">: ' . htmlspecialchars($grading['remark'] ?? '') . '</span></div>
                            </div>
                            <div class="col-4">
                                <div class="info-row"><span class="info-label">Total Cages</span><span class="info-value">: ' . number_format($totalCages) . '</span></div>
                                <div class="info-row"><span class="info-label">Cages Weight</span><span class="info-value">: ' . number_format($totalCagesWeight, 2) . ' kg</span></div>
                                <div class="info-row"><span class="info-label">Created By</span><span class="info-value">: ' . htmlspecialchars($createdBy) . '</span></div>
                                <div class="info-row"><span class="info-label">Time Start</span><span class="info-value">: ' . date('H:i:s', strtotime($grading['start_date'])) . '</span></div>
                                <div class="info-row"><span class="info-label">Time End</span><span class="info-value">: ' . ($grading['end_date'] ? date('H:i:s', strtotime($grading['end_date'])) : '') . '</span></div>
                            </div>
                        </div>
                        <hr>
                    </div>
                    <div class="container-fluid">
                        <div class="page-content">' . $weightDetails . '</div>
                    </div>';

                if ($withPhoto == 'Y') {
                    $photo_stmt = $db->prepare("SELECT gi.*, p.product_name, CASE WHEN gi.to_grade = 'REJ' THEN 'REJECT' ELSE COALESCE(g.units, gi.to_grade) END as grade_name FROM grading_items gi LEFT JOIN products p ON gi.product_id = p.id LEFT JOIN grades g ON gi.to_grade = g.id WHERE gi.grading_id = ? AND gi.deleted = '0' AND gi.photo_path != ''");
                    $photo_stmt->bind_param('s', $id);
                    $photo_stmt->execute();
                    $photo_result = $photo_stmt->get_result();
                    $photoItems = $photo_result->fetch_all(MYSQLI_ASSOC);

                    if (!empty($photoItems)) {
                        $message .= '<div class="page-break"><h3 style="font-size:14px;margin-bottom:10px;">Photos</h3><div class="row">';
                        foreach ($photoItems as $item) {
                            $photoSrc = '../../viewPhoto.php?file=' . urlencode($item['photo_path']) . '&type=photo';
                            $label = 'Product: ' . htmlspecialchars($item['product_name']) . ', Grade: ' . htmlspecialchars($item['grade_name']);
                            $message .= '<div class="col-4" style="margin-bottom:10px;text-align:center;"><img src="' . $photoSrc . '" style="width:100%;height:auto;border:1px solid #ccc;"><div style="font-size:12px;margin-top:4px;">' . $label . '</div></div>';
                        }
                        $message .= '</div></div>';
                    }
                }

                $message .= '</body></html>';

                echo json_encode(["status" => "success", "message" => $message]);
            } else {
                echo json_encode(["status" => "failed", "message" => "Data Not Found"]);
            }
        }
    } else {
        echo json_encode(["status" => "failed", "message" => "Something went wrong"]);
    }
} else {
    echo json_encode(["status" => "failed", "message" => "Please fill in all the fields"]);
}
?>
