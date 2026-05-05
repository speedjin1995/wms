<?php
require_once 'db_connect.php';
session_start();

if (!isset($_POST['id'])) {
  echo json_encode(['status'=>'failed','message'=>'Missing ID']); exit;
}

$id = mysqli_real_escape_string($db, $_POST['id']);

$row = mysqli_fetch_assoc(mysqli_query($db, "SELECT * FROM purchases WHERE id='$id' AND status=0"));
if (!$row) {
  echo json_encode(['status'=>'failed','message'=>'Record not found']); exit;
}

$items = [];
$res = mysqli_query($db, "SELECT pi.*, p.product_name FROM purchases_items pi LEFT JOIN products p ON pi.product_id = p.id WHERE pi.purchase_id='$id' AND pi.status=0");
while ($r = mysqli_fetch_assoc($res)) $items[] = $r;

$row['items'] = $items;
echo json_encode(['status'=>'success','message'=>$row]);
?>
