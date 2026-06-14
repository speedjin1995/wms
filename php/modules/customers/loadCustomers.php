<?php
session_start();
require_once '../../db_connect.php';
require_once '../../lookup.php';

$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length'];
$columnIndex = $_POST['order'][0]['column'];
$columnName = $_POST['columns'][$columnIndex]['data'];
$columnSortOrder = $_POST['order'][0]['dir'];
$searchValue = mysqli_real_escape_string($db, $_POST['search']['value']);

$searchQuery = "WHERE 1=1 AND deleted = 0 ";
$company = $_SESSION['customer'];
$role = $_SESSION['role'];

if ($role != 'SADMIN') {
    $searchQuery .= " AND customer = '" . $company . "'";
}

if ($searchValue != '') {
    $customerId = searchCustomerIdByName($searchValue, $company, $db);
    $searchQuery .= " AND (customer_name like '%" . $searchValue . "%' or customer_code like '%" . $searchValue . "%' or parent = '" . $customerId . "')";
}

$sel = mysqli_query($db, "select count(*) as allcount from customers");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

$sel = mysqli_query($db, "select count(*) as allcount from customers " . $searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

$empQuery = "select * from customers " . $searchQuery . " order by deleted, (is_manual = 'Y') desc, " . $columnName . " " . $columnSortOrder . " limit " . $row . "," . $rowperpage;
$empRecords = mysqli_query($db, $empQuery);
$data = array();

while ($row = mysqli_fetch_assoc($empRecords)) {
    $address_parts = array(
        trim($row['customer_address']),
        trim($row['customer_address2']),
        trim($row['customer_address3']),
        trim($row['customer_address4'])
    );
    $filtered_address = array_filter($address_parts);
    $display_address = implode('<br>', $filtered_address);

    $data[] = array(
        "id"               => $row['id'],
        "parent"           => searchCustomerNameById($row['parent'], '', $db),
        "customer_code"    => $row['customer_code'],
        "reg_no"           => $row['reg_no'],
        "customer_name"    => $row['customer_name'],
        "customer_address" => $display_address,
        "customer_phone"   => $row['customer_phone'],
        "pic"              => $row['pic'],
        "pending_bins"     => $row['pending_bins'],
        "is_manual"        => $row['is_manual'],
        "deleted"          => $row['deleted']
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
