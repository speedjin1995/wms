<?php
session_start();
require_once 'db_connect.php';
require_once 'lookup.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$user = $_SESSION['userID'];
$company = $_SESSION['customer'];

// Read the JSON data from the request body
$data = json_decode(file_get_contents('php://input'), true);

$errorArray = array();
if (!empty($data)) {
    foreach ($data as $rows) {
        $ProductCode = !empty($rows['ProductCode']) ? trim($rows['ProductCode']) : '';
        $ProductName = !empty($rows['ProductName']) ? trim($rows['ProductName']) : '';
        $Weight = !empty($rows['Weight']) ? trim($rows['Weight']) : '';
        $PricingType = !empty($rows['PricingType']) ? trim($rows['PricingType']) : '';
        $Price = !empty($rows['Price']) ? trim($rows['Price']) : '';

        # Check if unit exist in DB
        $deleted = "0";
        $unitQuery = "SELECT * FROM products WHERE product_name = '$ProductName' AND customer = '$company' AND deleted = '$deleted'";
        $unitDetail = mysqli_query($db, $unitQuery);
        $unitRow = mysqli_fetch_assoc($unitDetail);

        if(empty($unitRow)){
            if ($insert_stmt = $db->prepare("INSERT INTO products (product_code, product_name, weight, pricing_type, price, customer) VALUES (?, ?, ?, ?, ?, ?)")) {
                $insert_stmt->bind_param('ssssss', $ProductCode, $ProductName, $Weight, $PricingType, $Price, $company);
                $insert_stmt->execute();
                $unitId = $insert_stmt->insert_id; // Get the inserted unit ID
                $insert_stmt->close();            
            }
        }else{
            $errMsg = "Product Name: ".$ProductName." already exists.";
            $errorArray[] = $errMsg;
            continue;
        }
        
    }

    $db->close();

    if (!empty($errorArray)){
        echo json_encode(
            array(
                "status"=> "error", 
                "message"=> $errorArray 
            )
        );
    }else{
        echo json_encode(
            array(
                "status"=> "success", 
                "message"=> "Added Successfully!!" 
            )
        );
    }
} else {
    echo json_encode(
        array(
            "status"=> "failed", 
            "message"=> "Please fill in all the fields"
        )
    );     
}
?>
