<?php
require_once 'db_connect.php';
session_start();

$userID  = $_SESSION['userID'];
$company = $_SESSION['customer'];
$now     = date('Y-m-d H:i:s');
$today   = date('Y-m-d 00:00:00');

$id          = isset($_POST['id']) && $_POST['id'] != '' ? $_POST['id'] : null;
$purchaseDate = isset($_POST['purchaseDate']) ? $_POST['purchaseDate'] : $now;
$supplier    = isset($_POST['supplier']) ? mysqli_real_escape_string($db, $_POST['supplier']) : '';
$poNo        = isset($_POST['poNo'])     ? mysqli_real_escape_string($db, $_POST['poNo'])     : '';
$items       = isset($_POST['items'])    ? $_POST['items'] : [];

// Parse date
$dt = DateTime::createFromFormat('d/m/Y H:i', $purchaseDate);
$purchaseDateFmt = $dt ? $dt->format('Y-m-d H:i:s') : $now;

// Calculate total
$totalPrice = 0;
foreach ($items as $item) {
  $totalPrice += floatval($item['total'] ?? 0);
}

if ($id) {
  // Update
  $stmt = $db->prepare("UPDATE purchases SET purchase_date=?, supplier=?, po_no=?, total_price=? WHERE id=?");
  $stmt->bind_param('sssss', $purchaseDateFmt, $supplier, $poNo, $totalPrice, $id);
  if (!$stmt->execute()) {
    echo json_encode(['status'=>'failed','message'=>$stmt->error]); exit;
  }
  $stmt->close();

  // Delete old items
  $db->query("UPDATE purchases_items SET status=1 WHERE purchase_id='$id'");
} else {
  // Generate purchase number
  $count = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as c FROM purchases WHERE created_datetime >= '$today'"))['c'] + 1;
  $purchaseNo = 'P'.date('Ymd').str_pad($count, 4, '0', STR_PAD_LEFT);

  $stmt = $db->prepare("INSERT INTO purchases (purchase_no, purchase_date, supplier, po_no, total_price, company, created_by, created_datetime) VALUES (?,?,?,?,?,?,?,?)");
  $stmt->bind_param('ssssssss', $purchaseNo, $purchaseDateFmt, $supplier, $poNo, $totalPrice, $company, $userID, $now);
  if (!$stmt->execute()) {
    echo json_encode(['status'=>'failed','message'=>$stmt->error]); exit;
  }
  $id = $stmt->insert_id;
  $stmt->close();
}

// Insert items
foreach ($items as $item) {
  $productId = $item['product_id'] ?? '';
  $weight    = $item['weight']     ?? 0;
  $price     = $item['price']      ?? 0;
  $total     = $item['total']      ?? 0;
  if (!$productId) continue;
  $stmt = $db->prepare("INSERT INTO purchases_items (purchase_id, product_id, weight, price, total_price) VALUES (?,?,?,?,?)");
  $stmt->bind_param('sssss', $id, $productId, $weight, $price, $total);
  $stmt->execute();
  $stmt->close();
}

echo json_encode(['status'=>'success','message'=>'Saved Successfully!']);
?>
