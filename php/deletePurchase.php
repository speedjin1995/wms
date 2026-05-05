<?php
require_once 'db_connect.php';
session_start();

if (!isset($_POST['id'])) {
  echo json_encode(['status'=>'failed','message'=>'Missing ID']); exit;
}

$id = mysqli_real_escape_string($db, $_POST['id']);

$stmt = $db->prepare("UPDATE purchases SET status=1 WHERE id=?");
$stmt->bind_param('s', $id);

if ($stmt->execute()) {
  echo json_encode(['status'=>'success','message'=>'Deleted Successfully!']);
} else {
  echo json_encode(['status'=>'failed','message'=>$stmt->error]);
}
$stmt->close();
?>
