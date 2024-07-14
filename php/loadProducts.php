<?php
## Database configuration
require_once 'db_connect.php';

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
$searchValue = mysqli_real_escape_string($db,$_POST['search']['value']); // Search value

## Search 
$searchQuery = " ";

if(isset($_POST['id']) && $_POST['id'] != null && $_POST['id'] != ''){
  $searchQuery = " AND customer = '".$_POST['id']."'";
}

if($searchValue != ''){
   $searchQuery = " AND (product_name like '%".$searchValue."%' OR remark like '%".$searchValue."%')";
}

## Total number of records without filtering
$sel = mysqli_query($db,"select count(*) as allcount from products WHERE deleted = '0'");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of record with filtering
$sel = mysqli_query($db,"select count(*) as allcount from products WHERE deleted = '0'".$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
$empQuery = "select * from products WHERE deleted = '0'".$searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
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
    "price"=>$row['price'],
    "weight"=>$row['weight'].' '.$uom,
    "uom"=>$row['uom'],
    "unit"=>$uom,
    "remark"=>$row['remark'],
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