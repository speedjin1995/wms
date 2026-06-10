<?php
require_once '../../db_connect.php';
require_once '../../lookup.php';
$db->set_charset('utf8mb4');
session_start();

if (!isset($_POST['userID'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Missing Attribute']);
    exit;
}

$id = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_NUMBER_INT);

$stmt = $db->prepare("SELECT lo.*, st.shipment_type as shipmentType FROM loading_orders lo LEFT JOIN shipment_types st ON lo.shipment_type = st.id WHERE lo.id = ?");
$stmt->bind_param('s', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['status' => 'failed', 'message' => 'Record not found']);
    exit;
}

$message = [
    'id'            => $row['id'],
    'loading_no'    => $row['loading_no'],
    'loading_date'  => date('d/m/Y', strtotime($row['loading_date'])),
    'shipment_type' => $row['shipment_type'],
    'shipmentType'  => $row['shipmentType'],
    'remarks'       => $row['remarks'],
    'status'        => $row['status'],
    'company'       => $row['company'],
];

$itemStmt = $db->prepare("SELECT loi.*, pbi.packaging_batch_id, pb.batch_no FROM loading_order_items loi LEFT JOIN packaging_batch_items pbi ON loi.packaging_batch_item_id = pbi.id LEFT JOIN packaging_batches pb ON pbi.packaging_batch_id = pb.id WHERE loi.loading_order_id = ? AND loi.deleted = 0");
$itemStmt->bind_param('s', $id);
$itemStmt->execute();
$itemResult = $itemStmt->get_result();

$items = [];
while ($item = $itemResult->fetch_assoc()) {
    $items[] = [
        'id'                      => $item['id'],
        'packaging_batch_item_id' => $item['packaging_batch_item_id'],
        'packaging_batch_id'      => $item['packaging_batch_id'],
        'batch_no'                => $item['batch_no'],
        'customer_id'             => $item['customer_id'],
        'customer_name'           => searchCustomerNameById($item['customer_id'], null, $db),
        'product_id'              => $item['product_id'],
        'product_name'            => searchProductNameById($item['product_id'], $db),
        'grade'                   => $item['grade'],
        'packaging_size'          => $item['packaging_size'],
        'packaging_size_name'     => searchPackagingNameById($item['packaging_size'], $db),
        'units_per_box'           => $item['units_per_box'],
        'weight'                  => $item['weight'],
        'loading_time'            => $item['loading_time'],
        'remarks'                 => $item['remarks'],
    ];
}
$itemStmt->close();

$message['items'] = $items;
echo json_encode(['status' => 'success', 'message' => $message]);
