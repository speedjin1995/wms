<?php
require_once 'db_connect.php';
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
    $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
    $method = 'local';

    if ($type == 'logo') {
        $maxSize = 25 * 1024 * 1024;
    } else {
        $maxSize = 10 * 1024 * 1024;
    }

    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(
            array(
                "status" => "failed",
                "message" => "No file uploaded or upload error"
            )
        );
        exit;
    }

    $file = $_FILES['file'];
    if ($file['size'] > $maxSize) {
        echo json_encode(
            array(
                "status" => "failed",
                "message" => "File size exceeds " . ($maxSize / 1024 / 1024) . "MB limit"
            )
        );
        exit;
    }

    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(
            array(
                "status" => "failed",
                "message" => "Only PNG, JPG, and JPEG files are allowed"
            )
        );
        exit;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileDir = 'uploads/';
    $uploadPath = str_replace('\\', '/', dirname(__DIR__, 2)) . '/' . $fileDir;

    // create uploads directory if it doesn't exist
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }

    $uploadDir = $uploadPath . $type . '/';

    // create type-specific directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = time() . '_' . $company . '_' . basename($file['name']);
    $filePath = $uploadDir . $filename;
    $dbPath = $fileDir . $type . '/' . $filename;

    // Move the uploaded file to the target directory
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        // Update certificate data in the database
        if ($stmt = $db->prepare("INSERT INTO files (filename, filepath, method, company) VALUES (?, ?, ?, ?)")) {
            $stmt->bind_param('ssss', $filename, $dbPath, $method, $company);
            $stmt->execute();
            $fid = $stmt->insert_id;
            $stmt->close();
        } 
    } else{
        echo json_encode(
            array (
                "status" => "failed", 
                "message" => "Failed to save file"
            )   
        ); 
        exit;
    }

    if ($type == 'logo') {
        // Soft-delete old logo file if exists
        $stmt = $db->prepare("SELECT company_logo FROM companies WHERE id = ?");
        $stmt->bind_param('s', $company);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $oldLogoId = $row['company_logo'];
            if ($oldLogoId) {
                $stmtDel = $db->prepare("UPDATE files SET deleted = 1 WHERE id = ?");
                $stmtDel->bind_param('s', $oldLogoId);
                $stmtDel->execute();
                $stmtDel->close();
            }
        }
        $stmt->close();

        // Update company logo reference
        $stmt = $db->prepare("UPDATE companies SET company_logo = ? WHERE id = ?");
        $stmt->bind_param('is', $fid, $company);
        $stmt->execute();
        $stmt->close();
    } else {

    }

    $db->close();

    echo json_encode(
        array(
            "status" => "success",
            "message" => "File uploaded successfully!",
        )
    );
}else{
    echo json_encode(
        array(
            "status"=> "failed", 
            "message"=> "Please fill in all the fields"
        )
    );
}
?>
