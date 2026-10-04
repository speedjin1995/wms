<?php
session_start();
## Database configuration
require_once '../../db_connect.php';

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir'] == 'desc' ? 'desc' : 'asc'; // asc or desc
$searchValue = mysqli_real_escape_string($db,$_POST['search']['value']); // Search value

// Sortable columns (products joined with categories)
$sortColumns = array(
  'product_code' => 'p.product_code',
  'product_name' => 'p.product_name',
  'category_name' => 'c.category_name',
  'weight' => 'p.weight',
  'remark' => 'p.remark'
);
$columnName = $sortColumns[$columnName] ?? 'p.product_name';

## Search
$searchQuery = "WHERE p.deleted = 0";
$company = $_SESSION['customer'];
$user = $_SESSION['userID'];
$role = $_SESSION['role'];

if ($role != 'SADMIN'){
  $searchQuery .= " AND p.customer = '".$company."'";
}

if($searchValue != ''){
  $searchQuery .= " AND (p.product_name like '%".$searchValue."%' or p.remark like '%".$searchValue."%' or c.category_name like '%".$searchValue."%')";
}

## Total number of records without filtering
$sel = mysqli_query($db,"select count(*) as allcount from products WHERE deleted = '0'");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of record with filtering
$sel = mysqli_query($db,"select count(*) as allcount from products p left join categories c on p.category = c.id ".$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
$empQuery = "select p.*, c.category_name from products p left join categories c on p.category = c.id ".$searchQuery." order by p.deleted, ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
$empRecords = mysqli_query($db, $empQuery);
$data = array();

while($row = mysqli_fetch_assoc($empRecords)) {
  $uom = 'g';
  
  if($row['uom']!=null && $row['uom']!=''){
    $id = $row['uom'];

    if ($update_stmt = $db->prepare("SELECT * FROM units WHERE id=?")) {
      $update_stmt->bind_param('s', $id);
      
      // Execute the prepared query.
      if ($update_stmt->execute()) {
        $result1 = $update_stmt->get_result();
        
        if ($row1 = $result1->fetch_assoc()) {
          $uom = $row1['units'];
        }
      }
    }
  }

  $data[] = array( 
    "id"=>$row['id'],
    "product_code"=>$row['product_code'],
    "product_name"=>$row['product_name'],
    "category_name"=>$row['category_name'] ?? '',
    "pricing_type"=>$row['pricing_type'],
    "price"=>$row['price'],
    "weight"=>$row['weight'].' '.$uom,
    "uom"=>$row['uom'],
    "unit"=>$uom,
    "remark"=>$row['remark'],
    'is_manual'=>$row['is_manual'],
    "deleted"=>$row['deleted']
  );
}

## Response
$response = array(
  "draw" => intval($draw),
  "iTotalRecords" => $totalRecords,
  "iTotalDisplayRecords" => $totalRecordwithFilter,
  "aaData" => $data
);

echo json_encode($response);

?>