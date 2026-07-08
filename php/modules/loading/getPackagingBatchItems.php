<?php
require_once '../../db_connect.php';
require_once '../../lookup.php';
$db->set_charset('utf8mb4');
session_start();

if (!isset($_POST['batch_id'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Missing batch_id']);
    exit;
}

$batchId = filter_input(INPUT_POST, 'batch_id', FILTER_SANITIZE_NUMBER_INT);

$stmt = $db->prepare("SELECT pbi.*, pb.batch_no FROM packaging_batch_items pbi LEFT JOIN packaging_batches pb ON pbi.packaging_batch_id = pb.id WHERE pbi.packaging_batch_id = ? AND pbi.deleted = 0 AND pbi.status = 'pending'");
$stmt->bind_param('s', $batchId);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = [
        'id'                 => $row['id'],
        'packaging_batch_id' => $row['packaging_batch_id'],
        'batch_no'           => $row['batch_no'],
        'product_id'         => $row['product_id'],
        'product_name'       => searchProductNameById($row['product_id'], $db),
        'grade'              => $row['grade'],
        'grade_name'         => searchGradeNameById($row['grade'], $db),
        'packaging_size'     => $row['packaging_size'],
        'packaging_size_name'=> searchPackagingNameById($row['packaging_size'], $db),
        'units_per_box'      => $row['units_per_box'],
        'weight'             => $row['weight'],
    ];
}

echo json_encode(['status' => 'success', 'items' => $items]);
