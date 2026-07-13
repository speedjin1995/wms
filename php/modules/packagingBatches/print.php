<?php
require_once '../../db_connect.php';
require_once '../../lookup.php';

session_start();

if (isset($_POST['userID'])) {
    $id            = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_NUMBER_INT);
    $language      = $_SESSION['language'];
    $languageArray = $_SESSION['languageArray'];

    $stmt = $db->prepare("
        SELECT pb.*, l.locations, pl.production_line
        FROM packaging_batches pb
        LEFT JOIN locations l ON pb.location = l.id
        LEFT JOIN production_lines pl ON pb.production_line = pl.id
        WHERE pb.id = ?
    ");
    if (!$stmt) {
        echo json_encode(["status" => "failed", "message" => "Query prepare failed"]);
        exit;
    }
    $stmt->bind_param('s', $id);
    if (!$stmt->execute()) {
        echo json_encode(["status" => "failed", "message" => "Something went wrong when execute"]);
        exit;
    }

    $batch = $stmt->get_result()->fetch_assoc();
    if (!$batch) {
        echo json_encode(["status" => "failed", "message" => "Data Not Found"]);
        exit;
    }

    // Fetch company
    $companyStmt = $db->prepare("
        SELECT name, reg_no, address, address2, address3, phone, email, company_logo
        FROM companies
        WHERE id = ?
    ");
    $companyStmt->bind_param('s', $batch['company']);
    $companyStmt->execute();
    $company = $companyStmt->get_result()->fetch_assoc();

    $companyName  = htmlspecialchars($company['name']  ?? '');
    $companyPhone = htmlspecialchars($company['phone'] ?? '');
    $companyEmail = htmlspecialchars($company['email'] ?? '');
    $addressLine1 = htmlspecialchars($company['address']  ?? '');
    $addressLine2 = htmlspecialchars($company['address2'] ?? '');
    $addressLine3 = htmlspecialchars($company['address3'] ?? '');
    $logoSrc      = !empty($company['company_logo'])
        ? 'php/viewPhoto.php?file=' . urlencode($company['company_logo']) . '&type=file_table'
        : '';

    // Fetch batch items
    $itemsStmt = $db->prepare("
        SELECT pbi.*,
               p.product_name,
               g.units AS grade_name,
               pkg.packaging_name,
               pkg.weight AS pkg_weight
        FROM packaging_batch_items pbi
        LEFT JOIN products p    ON pbi.product_id     = p.id
        LEFT JOIN grades g      ON pbi.grade           = g.id
        LEFT JOIN packaging pkg ON pbi.packaging_size  = pkg.id
        WHERE pbi.packaging_batch_id = ?
          AND pbi.deleted = 0
    ");
    $itemsStmt->bind_param('s', $id);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();

    $items = [];
    while ($item = $itemsResult->fetch_assoc()) {
        $items[] = $item;
    }

    $totalBoxes = count($items);

    // Build table rows
    $tableRows             = '';
    $packagingBatchItemIds = [];
    $totalNetWeight        = 0;

    foreach ($items as $i => $item) {
        $packagingBatchItemIds[] = $item['id'];

        $boxPacking = trim(
            ($item['packaging_name'] ?? '') . ' ' .
            ($item['pkg_weight'] !== null ? number_format(floatval($item['pkg_weight']), 0) . 'kg' : '')
        );

        $no          = $i + 1;
        $productName = htmlspecialchars($item['product_name'] ?? '');
        $gradeName   = htmlspecialchars($item['grade_name']   ?? '');
        $label       = htmlspecialchars($item['label']        ?? '');
        $unitsPerBox = intval($item['units_per_box'] ?? 0);
        $gross       = number_format(floatval($item['gross']  ?? 0), 2);
        $tare        = number_format(floatval($item['tare']   ?? 0), 2);
        $net         = number_format(floatval($item['weight'] ?? 0), 2);

        $totalNetWeight += floatval($item['weight'] ?? 0);

        $tableRows .= '
            <tr>
                <td style="text-align:center;">' . $no . '</td>
                <td>' . $productName . '</td>
                <td style="text-align:center;">' . $gradeName . '</td>
                <td style="text-align:center;">' . htmlspecialchars($boxPacking) . '</td>
                <td style="text-align:center;">' . $label . '</td>
                <td style="text-align:center;">' . $unitsPerBox . '</td>
                <td style="text-align:center;">' . $gross . '</td>
                <td style="text-align:center;">' . $tare . '</td>
                <td style="text-align:center;">' . $net . '</td>
            </tr>';
    }

    // Get customer from loading_order_items
    $customerName = '';
    if (!empty($packagingBatchItemIds)) {
        $placeholders          = implode(',', array_fill(0, count($packagingBatchItemIds), '?'));
        $loadingOrderItemsStmt = $db->prepare("
            SELECT customer_id
            FROM loading_order_items
            WHERE packaging_batch_item_id IN ($placeholders)
        ");
        if ($loadingOrderItemsStmt) {
            $loadingOrderItemsStmt->bind_param(
                str_repeat('s', count($packagingBatchItemIds)),
                ...$packagingBatchItemIds
            );
            $loadingOrderItemsStmt->execute();
            $loadingOrderItemsResult = $loadingOrderItemsStmt->get_result();

            $customerIds = [];
            while ($row = $loadingOrderItemsResult->fetch_assoc()) {
                $customerIds[] = $row['customer_id'];
            }
            $customerIds   = array_unique($customerIds);
            $customerNames = [];
            foreach ($customerIds as $customerId) {
                $customerNames[] = searchCustomerNameById($customerId, '', $db);
            }
            $customerName = implode(', ', $customerNames);
        }
    }

    $batchDate  = !empty($batch['packaging_date']) ? date('d/m/Y', strtotime($batch['packaging_date'])) : '';
    $weightedBy = searchUserNameById($batch['created_by'], $db);
    $baseUrl    = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/wms/';

    $message = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="' . $baseUrl . '">
    <title>Packing List</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #000; }

        @page {
            size: A4 portrait;
            margin: 12mm;
            @bottom-center {
                content: "Page " counter(page) " of " counter(pages);
                font-size: 12px;
            }
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
        }
        .header-left { display: flex; align-items: flex-start; gap: 10px; }
        .header-left h1 { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
        .header-left p { font-size: 12px; line-height: 1.7; }
        .header-right { text-align: right; }
        .header-right h2 { font-size: 18px; font-weight: bold; text-decoration: underline; margin-bottom: 6px; }
        .header-right p { font-size: 12px; line-height: 1.8; }
        .divider-dot { border: none; border-top: 2px dashed #000; margin: 8px 0; }
        .sub-header {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            padding: 2px 0;
            margin-bottom: 4px;
        }
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table th,
        .items-table td { border: 1px solid #000; padding: 4px 6px; font-size: 11px; }
        .items-table th { font-weight: bold; text-align: center; }
        .items-table td:nth-child(2) { text-align: left; }
        .items-table tfoot td { font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-left">
            ' . ($logoSrc ? '<img src="' . $logoSrc . '" style="width:80px;height:auto;flex-shrink:0;">' : '') . '
            <div>
                <h1>' . $companyName . '</h1>
                ' . ($addressLine1 ? '<p>' . $addressLine1 . '</p>' : '') . '
                ' . ($addressLine2 ? '<p>' . $addressLine2 . '</p>' : '') . '
                ' . ($addressLine3 ? '<p>' . $addressLine3 . '</p>' : '') . '
                <p>PHONE : ' . $companyPhone . '</p>
                <p>Email : ' . $companyEmail . '</p>
            </div>
        </div>
        <div class="header-right">
            <h2>Packing List</h2>
            <p><strong>Batch No : ' . htmlspecialchars($batch['batch_no'] ?? '') . '</strong></p>
            <p>Date : ' . $batchDate . '</p>
        </div>
    </div>
    <hr class="divider-dot">
    <div class="sub-header">
        <span><strong>To Customer :</strong> &nbsp;' . htmlspecialchars($customerName ?? '') . '</span>
        <span><strong>Location :</strong> ' . htmlspecialchars($batch['locations'] ?? '') . '.</span>
        <span><strong>Line :</strong> ' . htmlspecialchars($batch['production_line'] ?? '') . '</span>
        <span><strong>Weight By :</strong> ' . htmlspecialchars($weightedBy) . '</span>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Item Decription</th>
                <th>Grade</th>
                <th>Box / Packing</th>
                <th>Label No</th>
                <th>Pcs/Box<br>(kg)</th>
                <th>Gross Weight</th>
                <th>Tare Weight</th>
                <th>Net Weight</th>
            </tr>
        </thead>
        <tbody>' . $tableRows . '</tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right;">Total Count :</td>
                <td style="text-align:center;">' . $totalBoxes . '</td>
                <td colspan="2" style="text-align:right;">Total Weight :</td>
                <td style="text-align:center;">' . number_format($totalNetWeight, 2) . '</td>
            </tr>
        </tfoot>
    </table>

</body>
</html>';

    echo json_encode(["status" => "success", "message" => $message]);

} else {
    echo json_encode(["status" => "failed", "message" => "Please fill in all the fields"]);
}
?>
