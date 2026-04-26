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
        // array(17) { ["CustomerCode"]=> string(5) "C-002" ["RegistrationNo"]=> string(4) "dwed" ["CustomerName"]=> string(6) "dewdwe" ["Address"]=> string(6) "dwedwe" ["Address2"]=> string(6) "ewdwed" ["Address3"]=> string(6) "wedwed" ["Address4"]=> string(4) "wedw" ["State"]=> string(5) "Johor" ["BillingName"]=> string(6) "dewdwe" ["BillingAddress"]=> string(6) "dwedwe" ["BillingAddress2"]=> string(6) "ewdwed" ["BillingAddress3"]=> string(6) "wedwed" ["BillingAddress4"]=> string(4) "wedw" ["BillingState"]=> string(5) "Johor" ["Phone"]=> string(9) "015678954" ["PIC"]=> string(10) "NG JIH BIN" ["Fax"]=> string(9) "604565676" }

        $Parent = !empty($rows['Parent']) ? searchCustomerIdByName(trim($rows['Parent']), $company, $db) : null;
        $CustomerCode = !empty($rows['CustomerCode']) ? trim($rows['CustomerCode']) : '';
        $RegistrationNo = !empty($rows['RegistrationNo']) ? trim($rows['RegistrationNo']) : '';
        $CustomerName = !empty($rows['CustomerName']) ? trim($rows['CustomerName']) : '';
        $Address = !empty($rows['Address']) ? trim($rows['Address']) : '';
        $Address2 = !empty($rows['Address2']) ? trim($rows['Address2']) : '';
        $Address3 = !empty($rows['Address3']) ? trim($rows['Address3']) : '';
        $Address4 = !empty($rows['Address4']) ? trim($rows['Address4']) : '';
        $State = !empty($rows['State']) ? searchStateIdByName(trim($rows['State']), $db) : null;
        $BillingName = !empty($rows['BillingName']) ? trim($rows['BillingName']) : '';
        $BillingAddress = !empty($rows['BillingAddress']) ? trim($rows['BillingAddress']) : '';
        $BillingAddress2 = !empty($rows['BillingAddress2']) ? trim($rows['BillingAddress2']) : '';
        $BillingAddress3 = !empty($rows['BillingAddress3']) ? trim($rows['BillingAddress3']) : '';
        $BillingAddress4 = !empty($rows['BillingAddress4']) ? trim($rows['BillingAddress4']) : '';
        $BillingState = !empty($rows['BillingState']) ? searchStateIdByName(trim($rows['BillingState']), $db) : null;
        $Phone = !empty($rows['Phone']) ? trim($rows['Phone']) : '';
        $PIC = !empty($rows['PIC']) ? trim($rows['PIC']) : '';
        $Fax = !empty($rows['Fax']) ? trim($rows['Fax']) : '';

        # Check if unit exist in DB
        $deleted = "0";
        $unitQuery = "SELECT * FROM customers WHERE customer_name = '".mysqli_real_escape_string($db, $CustomerName)."' AND customer = '".mysqli_real_escape_string($db, $company)."' AND deleted = '".mysqli_real_escape_string($db, $deleted)."'";
        $unitDetail = mysqli_query($db, $unitQuery);
        $unitRow = mysqli_fetch_assoc($unitDetail);

        if(empty($unitRow)){
            if ($insert_stmt = $db->prepare("INSERT INTO customers (parent, customer_code, reg_no, customer_name, customer_address, customer_address2, customer_address3, customer_address4, states, billing_name, billing_address, billing_address2, billing_address3, billing_address4, billing_state, customer_phone, pic, fax, customer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
                $insert_stmt->bind_param('sssssssssssssssssss', $Parent, $CustomerCode, $RegistrationNo, $CustomerName, $Address, $Address2, $Address3, $Address4, $State, $BillingName, $BillingAddress, $BillingAddress2, $BillingAddress3, $BillingAddress4, $BillingState, $Phone, $PIC, $Fax, $company);
                $insert_stmt->execute();
                $unitId = $insert_stmt->insert_id; // Get the inserted unit ID
                $insert_stmt->close();            
            }
        }else{
            $errMsg = "Customer Name: ".$CustomerName." already exists.";
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
