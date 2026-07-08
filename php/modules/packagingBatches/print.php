<?php
require_once '../../db_connect.php';
require_once '../../lookup.php';

session_start();

if(isset($_POST['userID'])) {
    $id = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_NUMBER_INT);
    // Language
    $language = $_SESSION['language'];
    $languageArray = $_SESSION['languageArray'];


    $stmt = $db->prepare("SELECT pb.*, l.locations, pl.production_line FROM packaging_batches pb LEFT JOIN locations l ON pb.location = l.id LEFT JOIN production_lines pl ON pb.production_line = pl.id WHERE pb.id = ?");
    if(!$stmt) {
        echo json_encode(["status" => "failed", "message" => "Query prepare failed"]);
        exit;
    }
    $stmt->bind_param('s', $id);
    if(!$stmt->execute()) {
        echo json_encode(["status" => "failed", "message" => "Something went wrong when execute"]);
        exit;
    }

    $result = $stmt->get_result();
    $batch = $result->fetch_assoc();

    if(!$batch) {
        echo json_encode(["status" => "failed", "message" => "Data Not Found"]);
        exit;
    }

    // Fetch company
    $companyStmt = $db->prepare("SELECT name, reg_no, address, address2, address3, phone, email, company_logo FROM companies WHERE id = ?");
    $companyStmt->bind_param('s', $batch['company']);
    $companyStmt->execute();
    $company = $companyStmt->get_result()->fetch_assoc();

    $logoSrc = !empty($company['company_logo']) ? 'php/viewPhoto.php?file=' . urlencode($company['company_logo']) . '&type=file_table' : '';
    $companyAddress = implode(' ', array_filter([$company['address'], $company['address2'], $company['address3']]));
    $companyName = htmlspecialchars($company['name'] ?? '');
    $companyReg  = htmlspecialchars($company['reg_no'] ?? '');
    $companyPhone = htmlspecialchars($company['phone'] ?? '');
    $companyEmail = htmlspecialchars($company['email'] ?? '');
    $companyAddress = htmlspecialchars($companyAddress);

    // Get customer
    //$customerName = searchCustomerNameById($batch['customer'], '', $db);

    // Fetch batch items
    $itemsStmt = $db->prepare("SELECT pbi.*, p.product_name, g.units as grade_name, pkg.packaging_name, pkg.weight as pkg_weight FROM packaging_batch_items pbi LEFT JOIN products p ON pbi.product_id = p.id LEFT JOIN grades g ON pbi.grade = g.id LEFT JOIN packaging pkg ON pbi.packaging_size = pkg.id WHERE pbi.packaging_batch_id = ? AND pbi.deleted = 0");
    $itemsStmt->bind_param('s', $id);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    $totalWeight = 0;

    $items = [];
    while($item = $itemsResult->fetch_assoc()) {
        $items[] = $item;
        $totalWeight += floatval($item['weight'] ?? 0);
    }

    $totalBoxes = count($items);


    // Build table rows
    $tableRows = '';
    foreach($items as $i => $item) {
        $tableRows .= '<tr>';
        $tableRows .= '<td style="text-align:center;">' . ($i + 1) . '</td>';
        $tableRows .= '<td>' . htmlspecialchars($item['product_name'] ?? '') . ' - ' . htmlspecialchars($item['grade_name'] ?? '') . '</td>';
        $tableRows .= '<td style="text-align:center;">' . number_format(floatval($item['pkg_weight'] ?? 0), 0) . ' kg</td>';
        $tableRows .= '<td style="text-align:center;">' . intval($item['units_per_box'] ?? 0) . '</td>';
        $tableRows .= '<td style="text-align:center;">' . number_format(floatval($item['weight'] ?? 0), 2) . '</td>';
        $tableRows .= '</tr>';
    }

    $batchDate = !empty($batch['packaging_date']) ? date('d/m/Y', strtotime($batch['packaging_date'])) : '';

    $message = '
        <!DOCTYPE html>
            <html lang="en">
            <head>
            <meta charset="UTF-8">
            <base href="' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/wms/">
            <title>Packing List</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: Arial, sans-serif; font-size: 12px; color: #000; background: #fff; }
                .page { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 15mm; }
                .header { display: flex; align-items: flex-start; margin-bottom: 10px; }
                .header img.logo { width: 80px; height: 80px; margin-right: 20px; flex-shrink: 0; }
                .header-company { flex: 1; }
                .header-top { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px; }
                .header-company h1 { font-size: 18px; font-weight: bold; }
                .header-company p { font-size: 11px; line-height: 1.7; }
                .header-company a { color: #1a0dab; text-decoration: underline; }
                .header-reg { font-size: 11px; text-align: right; white-space: nowrap; }
                .divider { border: none; border-top: 1px solid #ccc; margin: 16px 0; }
                .doc-info { margin: 18px 0; }
                .doc-info h2 { font-size: 13px; font-weight: bold; margin-bottom: 10px; }
                .doc-info p { font-size: 12px; margin-bottom: 6px; }
                .doc-info p span { font-weight: bold; }
                .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .items-table th, .items-table td { border: 1px solid #000; padding: 6px 8px; font-size: 12px; }
                .items-table th { font-weight: bold; text-align: center; }
                .items-table td:nth-child(2) { text-align: left; }
                .summary { margin-top: 20px; text-align: right; font-size: 12px; }
                .summary span { font-weight: bold; margin-right: 20px; }
                .signature-section { margin-top: 40px; text-align: right; }
                .signature-section p { font-weight: bold; margin-bottom: 50px; }
                .signature-line { display: inline-block; width: 180px; border-top: 1px solid #000; text-align: center; padding-top: 4px; margin-top: 25px; font-weight: bold; font-size: 12px; }
                @media print { body { background: #fff; } .page { margin: 0; padding: 15mm; } @page { size: A4; margin: 0; } }
            </style>
            </head>
            <body>
            <div class="page">
            <div class="header">
                ' . ($logoSrc ? '<img class="logo" src="' . $logoSrc . '" alt="Logo">' : '') . '
                <div class="header-company">
                <div class="header-top">
                    <h1>' . $companyName . '</h1>
                    <div class="header-reg">' . $companyReg . '</div>
                </div>
                <p>' . $companyAddress . '</p>
                <p>PHONE : ' . $companyPhone . '</p>
                <p>Email : <a href="mailto:' . $companyEmail . '">' . $companyEmail . '</a></p>
                </div>
            </div>

            <hr class="divider">

            <div class="doc-info">
                <h2>'.$languageArray['packing_list_code'][$language].'</h2>
                <p>'.$languageArray['batch_no_code'][$language].': <span>' . htmlspecialchars($batch['batch_no'] ?? '') . '</span></p>
                <p>'.$languageArray['date_code'][$language].': <span>' . $batchDate . '</span></p>
                <p>'.$languageArray['locations_code'][$language].': <span>' . htmlspecialchars($batch['locations'] ?? '') . '</span></p>
            </div>

            <hr class="divider">

            <table class="items-table">
                <thead>
                <tr>
                    <th>'.$languageArray['number_short_code'][$language].'</th>
                    <th>'.$languageArray['description_code'][$language].'</th>
                    <th>'.$languageArray['pack_weight_code'][$language].' (kg)</th>
                    <th>'.$languageArray['qty_per_box_code'][$language].'</th>
                    <th>'.$languageArray['total_weight_code'][$language].'</th>
                </tr>
                </thead>
                <tbody>' . $tableRows . '</tbody>
            </table>

            <hr class="divider">

            <div class="summary">
                <span>'.$languageArray['total_boxes_code'][$language].':</span> ' . $totalBoxes . '
                <span>'.$languageArray['total_weight_code'][$language].':</span> ' . $totalWeight . '
            </div>

            <hr class="divider" style="margin-top:12px;">

            <div class="signature-section">
                <p>' . $companyName . '</p>
                <div class="signature-line">'.$languageArray['authorised_signature_code'][$language].'</div>
            </div>
            </div>
            </body>
        </html>
    ';

    echo json_encode(["status" => "success", "message" => $message]);

} else {
    echo json_encode(["status" => "failed", "message" => "Please fill in all the fields"]);
}
?>
