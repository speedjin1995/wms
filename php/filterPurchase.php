<?php
require_once 'db_connect.php';
session_start();

$draw         = $_POST['draw'];
$row          = $_POST['start'];
$rowperpage   = $_POST['length'];
$columnIndex  = $_POST['order'][0]['column'];
$columnName   = $_POST['columns'][$columnIndex]['data'];
$columnSortOrder = $_POST['order'][0]['dir'];

$company = $_SESSION['customer'];
$role    = $_SESSION['role'];

$companyFilter = ($role != 'SADMIN') ? " AND p.company = '$company'" : '';
$searchQuery   = '';

if (!empty($_POST['fromDate'])) {
  $dt = DateTime::createFromFormat('d/m/Y', $_POST['fromDate']);
  $searchQuery .= " AND p.purchase_date >= '".$dt->format('Y-m-d 00:00:00')."'";
}
if (!empty($_POST['toDate'])) {
  $dt = DateTime::createFromFormat('d/m/Y', $_POST['toDate']);
  $searchQuery .= " AND p.purchase_date <= '".$dt->format('Y-m-d 23:59:59')."'";
}
if (!empty($_POST['supplier'])) {
  $sup = mysqli_real_escape_string($db, $_POST['supplier']);
  $searchQuery .= " AND p.supplier = '$sup'";
}

$base = "FROM purchases p WHERE p.status = 0".$companyFilter.$searchQuery;

$total = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as c $base"))['c'];

$allowedCols = ['purchase_no','purchase_date','supplier','po_no','total_price','id'];
if (!in_array($columnName, $allowedCols)) $columnName = 'purchase_date';

$records = mysqli_query($db, "SELECT p.id, p.purchase_no, DATE_FORMAT(p.purchase_date,'%d/%m/%Y %H:%i') as purchase_date, p.supplier, p.po_no, p.total_price $base ORDER BY $columnName $columnSortOrder LIMIT $row,$rowperpage");

$data = [];
while ($r = mysqli_fetch_assoc($records)) $data[] = $r;

echo json_encode([
  'draw'                => intval($draw),
  'iTotalRecords'       => $total,
  'iTotalDisplayRecords'=> $total,
  'aaData'              => $data
]);
?>
