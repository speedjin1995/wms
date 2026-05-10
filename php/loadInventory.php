<?php
session_start();
require_once 'db_connect.php';

$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length'];
$columnIndex = $_POST['order'][0]['column'];
$columnName = $_POST['columns'][$columnIndex]['data'];
$columnSortOrder = $_POST['order'][0]['dir'];
$searchValue = mysqli_real_escape_string($db, $_POST['search']['value']);

$company = $_SESSION['customer'];
$role = $_SESSION['role'];

$searchQuery = "WHERE 1=1";

if ($role != 'SADMIN') {
    $searchQuery .= " AND products.customer = '" . $company . "'";
}

if ($searchValue != '') {
    $searchQuery .= " AND products.product_name LIKE '%" . $searchValue . "%'";
}

$sel = mysqli_query($db, "SELECT count(*) as allcount FROM inventory LEFT JOIN products ON inventory.product_id = products.id " . $searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

$totalRecordwithFilter = $totalRecords;

$empQuery = "SELECT inventory.*, products.product_name FROM inventory LEFT JOIN products ON inventory.product_id = products.id " . $searchQuery . " ORDER BY " . $columnName . " " . $columnSortOrder . " LIMIT " . $row . "," . $rowperpage;
$empRecords = mysqli_query($db, $empQuery);
$data = array();

while ($row = mysqli_fetch_assoc($empRecords)) {
    $data[] = array(
        "id"           => $row['id'],
        "product_name" => $row['product_name'],
        "quantity"     => $row['quantity'],
        "status"       => $row['status'],
    );
}

$response = array(
    "draw"                => intval($draw),
    "iTotalRecords"       => $totalRecords,
    "iTotalDisplayRecords"=> $totalRecordwithFilter,
    "aaData"              => $data
);

echo json_encode($response);
?>
