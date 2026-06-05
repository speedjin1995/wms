<?php
session_start();
require_once '../../db_connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$user = $_SESSION['userID'];
$company = $_SESSION['customer'];

// Read the JSON data from the request body
$data = json_decode(file_get_contents('php://input'), true);

$errorArray = array();
if (!empty($data)) {
    foreach ($data as $rows) {
        $ShipmentType = !empty($rows['ShipmentType']) ? trim($rows['ShipmentType']) : '';

        # Check if unit exist in DB
        $deleted = "0";
        $unitQuery = "SELECT * FROM shipment_types WHERE shipment_type = '".mysqli_real_escape_string($db, $ShipmentType)."' AND customers = '".mysqli_real_escape_string($db, $company)."' AND deleted = '".mysqli_real_escape_string($db, $deleted)."'";
        $unitDetail = mysqli_query($db, $unitQuery);
        $unitRow = mysqli_fetch_assoc($unitDetail);

        if(empty($unitRow)){
            if ($insert_stmt = $db->prepare("INSERT INTO shipment_types (shipment_type, customers, created_by) VALUES (?, ?, ?)")) {
                $insert_stmt->bind_param('sss', $ShipmentType, $company, $user);
                $insert_stmt->execute();
                $unitId = $insert_stmt->insert_id; // Get the inserted unit ID
                $insert_stmt->close();            
            }
        }else{
            $errMsg = "Shipment Type: ".$ShipmentType." already exists.";
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
