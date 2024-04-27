<?php
require_once 'db_connect.php';

//$lots = $db->query("SELECT * FROM lots WHERE deleted = '0'");
$products = $db->query("SELECT * FROM products WHERE deleted = '0'");
$customers = $db->query("SELECT * FROM customers WHERE deleted = '0'");
$suppliers = $db->query("SELECT * FROM supplies WHERE deleted = '0'");
$suppliers = $db->query("SELECT * FROM supplies WHERE deleted = '0'");
$grades = $db->query("SELECT * FROM grades WHERE deleted = '0'");
$transporters = $db->query("SELECT * FROM transporters WHERE deleted = '0'");

$data1 = array();
$data2 = array();
$data3 = array();
$data4 = array();
$data5 = array();

while($row1=mysqli_fetch_assoc($products)){
    $data1[] = array( 
        'id'=>$row1['id'],
        'product_name'=>$row1['product_name']
    );
}

while($row2=mysqli_fetch_assoc($customers)){
    $data2[] = array( 
        'id'=>$row2['id'],
        'customer_name'=>$row2['customer_name']
    );
}

while($row3=mysqli_fetch_assoc($suppliers)){
    $data3[] = array( 
        'id'=>$row3['id'],
        'supplier_name'=>$row3['supplier_name']
    );
}

while($row4=mysqli_fetch_assoc($grades)){
    $data4[] = array( 
        'id'=>$row4['id'],
        'grades'=>$row4['units']
    );
}

while($row5=mysqli_fetch_assoc($transporters)){
    $data5[] = array( 
        'id'=>$row5['id'],
        'transporter_name'=>$row5['transporter_name']
    );
}

$db->close();

echo json_encode(
    array(
        "status"=> "success", 
        "products"=> $data1, 
        "customers"=> $data2, 
        "suppliers"=> $data3, 
        "grades"=> $data4, 
        "transporter"=> $data5
    )
);
?>