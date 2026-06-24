<?php
require_once 'db_connect.php';
require_once 'uploadFileHelper.php';
session_start();

if (!isset($_SESSION['userID'])) {
    echo json_encode(
        array(
            "status" => "failed", 
            "message" => "Unauthorized"
        )
    );
    exit;
}

if(isset($_POST['type'], $_POST['company'])){
    $company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_STRING);
    $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(
            array(
                "status" => "failed", 
                "message" => "No file uploaded or upload error"
            )
        );
        exit;
    }

    $result = uploadFile($_FILES['file'], $type, $company, $db);

    if ($result['status'] === 'failed') {
        echo json_encode(
            array(
                "status" => "failed", 
                "message" => $result['message']
            )
        );
        exit;
    }

    $fid = $result['fid'];

    if ($type == 'logo') {
        // Delete old logo file from disk and soft-delete from DB
        $stmt = $db->prepare("SELECT company_logo FROM companies WHERE id = ?");
        $stmt->bind_param('s', $company);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $oldLogoId = $row['company_logo'];
            if ($oldLogoId) {
                deleteOldFile($oldLogoId, $db);
            }
        }
        $stmt->close();

        // Update company logo reference
        $stmt = $db->prepare("UPDATE companies SET company_logo = ? WHERE id = ?");
        $stmt->bind_param('is', $fid, $company);
        $stmt->execute();
        $stmt->close();
    }

    $db->close();

    echo json_encode(
        array(
            "status" => "success", 
            "message" => "File uploaded successfully!"
        )
    );
}else{
    echo json_encode(
        array(
            "status" => "failed", 
            "message" => "Please fill in all the fields"
        )
    );
}
?>
