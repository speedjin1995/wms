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
        $PackagingName = !empty($rows['PackagingName']) ? trim($rows['PackagingName']) : '';

        # Check if unit exist in DB
        $deleted = "0";
        $categoryQuery = "SELECT * FROM packaging WHERE packaging_name = '".mysqli_real_escape_string($db, $PackagingName)."' AND customer = '".mysqli_real_escape_string($db, $company)."' AND deleted = '".mysqli_real_escape_string($db, $deleted)."'";
        $categoryDetail = mysqli_query($db, $categoryQuery);
        $row = mysqli_fetch_assoc($categoryDetail);

        if(empty($row)){
            if ($insert_stmt = $db->prepare("INSERT INTO packaging (packaging_name, customer) VALUES (?, ?)")) {
                $insert_stmt->bind_param('ss', $PackagingName, $company);
                $insert_stmt->execute();
                $unitId = $insert_stmt->insert_id; // Get the inserted unit ID
                $insert_stmt->close();            
            }
        }else{
            $errMsg = "Packaging: ".$PackagingName." already exists.";
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
