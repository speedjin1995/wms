<?php
require_once '../../db_connect.php';

session_start();

if (!isset($_SESSION['userID'])) {
    echo json_encode(array("status" => "failed", "message" => "Unauthorized"));
    exit;
}

$customer_id = filter_input(INPUT_POST, 'customer_id', FILTER_SANITIZE_NUMBER_INT);
$bin_type_id = filter_input(INPUT_POST, 'bin_type_id', FILTER_SANITIZE_NUMBER_INT);

if (!$customer_id || !$bin_type_id) {
    echo json_encode(array("status" => "failed", "message" => "Missing parameters"));
    exit;
}

$draw       = $_POST['draw'];
$row        = $_POST['start'];
$rowperpage = $_POST['length'];
$columnSortOrder = $_POST['order'][0]['dir'];

$sel = mysqli_query($db, "SELECT count(*) as allcount FROM customer_bin_logs WHERE customer_id = '$customer_id' AND bin_type = '$bin_type_id'");
$totalRecords = mysqli_fetch_assoc($sel)['allcount'];

$query = "SELECT l.*, u.name as user_name 
          FROM customer_bin_logs l 
          LEFT JOIN users u ON u.id = l.created_by 
          WHERE l.customer_id = '$customer_id' AND l.bin_type = '$bin_type_id'
          ORDER BY l.created_at $columnSortOrder 
          LIMIT $row, $rowperpage";

$records = mysqli_query($db, $query);
$data = array();

while ($r = mysqli_fetch_assoc($records)) {
    $data[] = array(
        "id"         => $r['id'],
        "type"       => $r['type'],
        "qty"        => $r['qty'],
        "remark"     => $r['remark'],
        "user_name"  => $r['user_name'],
        "created_at" => $r['created_at']
    );
}

echo json_encode(array(
    "draw"                 => intval($draw),
    "iTotalRecords"        => $totalRecords,
    "iTotalDisplayRecords" => $totalRecords,
    "aaData"               => $data
));
?>
