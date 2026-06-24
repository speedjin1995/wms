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
        $DriverName = !empty($rows['DriverName']) ? trim($rows['DriverName']) : '';
        $DriverIC = !empty($rows['DriverIC']) ? trim($rows['DriverIC']) : '';

        # Check if unit exist in DB
        $deleted = "0";
        $unitQuery = "SELECT * FROM drivers WHERE driver_name = '".mysqli_real_escape_string($db, $DriverName)."' AND customer = '".mysqli_real_escape_string($db, $company)."' AND deleted = '".mysqli_real_escape_string($db, $deleted)."'";
        $unitDetail = mysqli_query($db, $unitQuery);
        $unitRow = mysqli_fetch_assoc($unitDetail);

        if(empty($unitRow)){
            if ($insert_stmt = $db->prepare("INSERT INTO drivers (driver_name, driver_ic, customer) VALUES (?, ?, ?)")) {
                $insert_stmt->bind_param('sss', $DriverName, $DriverIC, $company);
                $insert_stmt->execute();
                $unitId = $insert_stmt->insert_id; // Get the inserted unit ID
                $insert_stmt->close();            
            }
        }else{
            $errMsg = "Driver Name: ".$DriverName." already exists.";
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
