<?php
require_once 'db_connect.php';

$post = json_decode(file_get_contents('php://input'), true);

$staffId = $post['userId'];
$userId = $post['uid'];
$customer = "";

if(isset($post['customer']) && $post['customer']!=null && $post['customer']!=''){
    $customer = $post['customer'];
}

$units = $db->query("SELECT * FROM units WHERE deleted = '0'");
$locations = $db->query("SELECT * FROM locations WHERE deleted = '0' AND customer='".$staffId."'");
$products = $db->query("SELECT * FROM products WHERE deleted = '0' AND customer='".$staffId."'");
$vehicles = $db->query("SELECT * FROM vehicles WHERE deleted = '0' AND customer='".$staffId."'");
$transporters = $db->query("SELECT * FROM transporters WHERE deleted = '0' AND customer='".$staffId."'");
$supplies = $db->query("SELECT * FROM supplies WHERE deleted = '0' AND customer='".$staffId."'");
$customers = $db->query("SELECT * FROM customers WHERE deleted = '0' AND customer='".$staffId."'");
$sizes = $db->query("SELECT * FROM size WHERE deleted = '0' AND customer='".$staffId."'");
$specs = $db->query("SELECT * FROM spec WHERE deleted = '0' AND customer='".$staffId."'");
$drivers = $db->query("SELECT * FROM drivers WHERE deleted = '0' AND customer='".$staffId."'");
$destinations = $db->query("SELECT * FROM destinations WHERE deleted = '0' AND customer='".$staffId."'");
$grades = $db->query("SELECT * FROM grades WHERE deleted = '0' AND customer='".$staffId."'");

$data1 = array();
$data2 = array();
$data3 = array();
$data4 = array();
$data5 = array();
$data6 = array();
$data7 = array();
$data8 = array();
$data9 = array();
$data10 = array();
$data11 = array();
$data12 = array();

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
    $pricing_type = $row3['pricing_type'];
    $price=$row3['price'];
    
    if($customer != ''){
        $product_cus = $db->query("SELECT * FROM product_customers WHERE product_id = '".$row3['id']."' AND customer_id='".$customer."'");
        
        if($rowPC=mysqli_fetch_assoc($product_cus)){
            $pricing_type = $rowPC['pricing_type'];
            $price=$rowPC['price'];
        }
        else{
            $pricing_type = $row3['pricing_type'];
            $price=$row3['price'];
        }
    }
    
    // ===== Fetch grades for this product =====
    $pgrades = [];

    $gradeSql = "
        SELECT g.id, g.units
        FROM product_grades pg
        INNER JOIN grades g ON g.id = pg.grade_id
        WHERE pg.product_id = '{$row3['id']}'
          AND pg.deleted = 0
          AND g.deleted = 0
    ";

    $gradeResult = $db->query($gradeSql);

    while ($g = mysqli_fetch_assoc($gradeResult)) {
        $pgrades[] = [
            'id'    => $g['id'],
            'units' => $g['units'],
        ];
    }
    
    $data3[] = array( 
        'id'=>$row3['id'],
        'product_name'=>$row3['product_name'],
        'remark'=>$row3['remark'],
        'price'=>$price,
        'pricing_type'=>$pricing_type,
        'weight'=>$row3['weight'],
        'grades'=> $pgrades
    );
}

while($row4=mysqli_fetch_assoc($vehicles)){
    $data4[] = array( 
        'id'=>$row4['id'],
        'veh_number'=>$row4['veh_number']
    );
}

while($row5=mysqli_fetch_assoc($transporters)){
    $data5[] = array( 
        'id'=>$row5['id'],
        'transporter_name'=>$row5['transporter_name'],
        'transporter_ic'=>$row5['transporter_ic']
    );
}

while($row6=mysqli_fetch_assoc($supplies)){
    $data6[] = array( 
        'id'=>$row6['id'],
        'supplier_name'=>$row6['supplier_name'],
        'reg_no'=>$row6['reg_no'],
        'supplier_address'=>$row6['supplier_address'],
        'supplier_address2'=>$row6['supplier_address2'],
        'supplier_address3'=>$row6['supplier_address3'],
        'supplier_address4'=>$row6['supplier_address4'],
        'supplier_phone'=>$row6['supplier_phone'],
        'supplier_email'=>$row6['pic']
    );
}

while($row7=mysqli_fetch_assoc($customers)){
    $data7[] = array( 
        'id'=>$row7['id'],
        'customer_name'=>$row7['customer_name'],
        'reg_no'=>$row7['reg_no'],
        'customer_address'=>$row7['customer_address'],
        'customer_address2'=>$row7['customer_address2'],
        'customer_address3'=>$row7['customer_address3'],
        'customer_address4'=>$row7['customer_address4'],
        'customer_phone'=>$row7['customer_phone'],
        'customer_email'=>$row7['pic']
    );
}

while($row8=mysqli_fetch_assoc($sizes)){
    $data8[] = array( 
        'id'=>$row8['id'],
        'size'=>$row8['size']
    );
}

while($row9=mysqli_fetch_assoc($specs)){
    $data9[] = array( 
        'id'=>$row9['id'],
        'spec'=>$row9['spec']
    );
}

while($row10=mysqli_fetch_assoc($drivers)){
    $data10[] = array( 
        'id'=>$row10['id'],
        'driver_name'=>$row10['driver_name'],
        'driver_ic'=>$row10['driver_ic']
    );
}

while($row11=mysqli_fetch_assoc($destinations)){
    $data11[] = array( 
        'id'=>$row11['id'],
        'destination_name'=>$row11['destination_name']
    );
}

while($row12=mysqli_fetch_assoc($grades)){
    $data12[] = array( 
        'id'=>$row12['id'],
        'units'=>$row12['units']
    );
}

$db->close();

echo json_encode(
    array(
        "status"=> "success", 
        "units"=> $data1, 
        "locations"=> $data2, 
        "products"=> $data3, 
        "vehicles"=> $data4, 
        "drivers"=> $data5, 
        "suppliers"=> $data6, 
        "customers"=> $data7, 
        "sizes"=> $data8, 
        "specs"=> $data9, 
        "driverslist"=> $data10, 
        "destinations"=> $data11,
        "grades"=> $data12
    )
);
?>