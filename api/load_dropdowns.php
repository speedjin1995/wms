<?php
require_once 'db_connect.php';

$post = json_decode(file_get_contents('php://input'), true);

$staffId = $post['userId'];
$userId = $post['uid'];

$units = $db->query("SELECT * FROM units WHERE deleted = '0'");
$locations = $db->query("SELECT * FROM locations WHERE deleted = '0' AND customer='".$staffId."'");
$products = $db->query("SELECT * FROM products WHERE deleted = '0' AND customer='".$staffId."'");

$data1 = array();
$data2 = array();
$data3 = array();

while($row1=mysqli_fetch_assoc($units)){
    $data1[] = array( 
        'id'=>$row1['id'],
        'units'=>$row1['units']
    );
}

while($row2=mysqli_fetch_assoc($locations)){
    $data2[] = array( 
        'id'=>$row2['id'],
        'locations'=>$row2['locations']
    );
}

while($row3=mysqli_fetch_assoc($products)){
    $data3[] = array( 
        'id'=>$row3['id'],
        'product_name'=>$row3['product_name'],
        'remark'=>$row3['remark']
    );
}

$db->close();

echo json_encode(
    array(
        "status"=> "success", 
        "units"=> $data1, 
        "locations"=> $data2, 
        "products"=> $data3
    )
);
?>