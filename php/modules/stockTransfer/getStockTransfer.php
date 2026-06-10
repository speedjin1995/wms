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

$stmt = $db->prepare("SELECT st.*, pb1.batch_no as from_batch_no, pb2.batch_no as to_batch_no
                      FROM stock_transfers st
                      LEFT JOIN packaging_batches pb1 ON st.from_batch_id = pb1.id
                      LEFT JOIN packaging_batches pb2 ON st.to_batch_id = pb2.id
                      WHERE st.id = ?");
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
    'transfer_no'   => $row['transfer_no'],
    'from_batch_id' => $row['from_batch_id'],
    'from_batch_no' => $row['from_batch_no'],
    'to_batch_id'   => $row['to_batch_id'],
    'to_batch_no'   => $row['to_batch_no'],
    'remarks'       => $row['remarks'],
    'created_date'  => $row['created_date'],
];

$itemStmt = $db->prepare("SELECT sti.*, pbi.product_id, pbi.grade, pbi.packaging_size, pbi.units_per_box, pbi.weight, pbi.packaging_batch_id as current_batch_id
                          FROM stock_transfer_items sti
                          LEFT JOIN packaging_batch_items pbi ON sti.packaging_batch_item_id = pbi.id
                          WHERE sti.stock_transfer_id = ? AND sti.deleted = 0");
$itemStmt->bind_param('s', $id);
$itemStmt->execute();
$itemResult = $itemStmt->get_result();

$items = [];
while ($item = $itemResult->fetch_assoc()) {
    $items[] = [
        'id'                      => $item['id'],
        'packaging_batch_item_id' => $item['packaging_batch_item_id'],
        'from_batch_id'           => $item['from_batch_id'],
        'to_batch_id'             => $item['to_batch_id'],
        'current_batch_id'        => $item['current_batch_id'],
        'product_id'              => $item['product_id'],
        'product_name'            => searchProductNameById($item['product_id'], $db),
        'grade'                   => $item['grade'],
        'packaging_size'          => $item['packaging_size'],
        'packaging_size_name'     => searchPackagingNameById($item['packaging_size'], $db),
        'units_per_box'           => $item['units_per_box'],
        'weight'                  => $item['weight'],
    ];
}
$itemStmt->close();

$message['items'] = $items;
echo json_encode(['status' => 'success', 'message' => $message]);
