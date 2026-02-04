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
        $Parent = !empty($rows['Parent']) ? searchSupplierIdByName(trim($rows['Parent']), $company, $db) : null;
        $SupplierCode = !empty($rows['SupplierCode']) ? trim($rows['SupplierCode']) : '';
        $RegistrationNo = !empty($rows['RegistrationNo']) ? trim($rows['RegistrationNo']) : '';
        $SupplierName = !empty($rows['SupplierName']) ? trim($rows['SupplierName']) : '';
        $Address = !empty($rows['Address']) ? trim($rows['Address']) : '';
        $Address2 = !empty($rows['Address2']) ? trim($rows['Address2']) : '';
        $Address3 = !empty($rows['Address3']) ? trim($rows['Address3']) : '';
        $Address4 = !empty($rows['Address4']) ? trim($rows['Address4']) : '';
        $State = !empty($rows['State']) ? searchStateIdByName(trim($rows['State']), $db) : '';
        $Phone = !empty($rows['Phone']) ? trim($rows['Phone']) : '';
        $PIC = !empty($rows['PIC']) ? trim($rows['PIC']) : '';

        # Check if unit exist in DB
        $deleted = "0";
        $unitQuery = "SELECT * FROM supplies WHERE supplier_name = '$SupplierName' AND customer = '$company' AND deleted = '$deleted'";
        $unitDetail = mysqli_query($db, $unitQuery);
        $unitRow = mysqli_fetch_assoc($unitDetail);

        if(empty($unitRow)){
            if ($insert_stmt = $db->prepare("INSERT INTO supplies (parent, supplier_code, reg_no, supplier_name, supplier_address, supplier_address2, supplier_address3, supplier_address4, states, supplier_phone, pic, customer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
                $insert_stmt->bind_param('ssssssssssss', $Parent, $SupplierCode, $RegistrationNo, $SupplierName, $Address, $Address2, $Address3, $Address4, $State, $Phone, $PIC, $company);
                $insert_stmt->execute();
                $unitId = $insert_stmt->insert_id; // Get the inserted unit ID
                $insert_stmt->close();            
            }
        }else{
            $errMsg = "Supplier Name: ".$SupplierName." already exists.";
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
