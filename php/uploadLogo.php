<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['userID'])) {
    echo json_encode(["status" => "failed", "message" => "Unauthorized"]);
    exit;
}

$company = $_SESSION['customer'];
$maxSize = 25 * 1024 * 1024; // 25MB
$allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];

if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["status" => "failed", "message" => "No file uploaded or upload error"]);
    exit;
}

$file = $_FILES['logo'];

if ($file['size'] > $maxSize) {
    echo json_encode(["status" => "failed", "message" => "File size exceeds 25MB limit"]);
    exit;
}

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(["status" => "failed", "message" => "Only PNG, JPG, and JPEG files are allowed"]);
    exit;
}

$uploadDir = '../uploads/logo/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$newFileName = 'logo_' . $company . '_' . time() . '.' . $ext;
$filePath = $uploadDir . $newFileName;

if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    echo json_encode(["status" => "failed", "message" => "Failed to save file"]);
    exit;
}

$dbPath = 'uploads/logo/' . $newFileName;
$origName = $file['name'];

// Insert into files table
$stmt = $db->prepare("INSERT INTO files (filename, filepath) VALUES (?, ?)");
$stmt->bind_param('ss', $origName, $dbPath);
$stmt->execute();
$fileId = $stmt->insert_id;
$stmt->close();

// Delete old logo file if exists
$stmt = $db->prepare("SELECT company_logo FROM companies WHERE id = ?");
$stmt->bind_param('s', $company);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $oldLogoId = $row['company_logo'];
    if ($oldLogoId) {
        $stmtDel = $db->prepare("UPDATE files SET deleted = 1 WHERE id = ?");
        $stmtDel->bind_param('i', $oldLogoId);
        $stmtDel->execute();
        $stmtDel->close();
    }
}
$stmt->close();

// Update company logo reference
$stmt = $db->prepare("UPDATE companies SET company_logo = ? WHERE id = ?");
$stmt->bind_param('is', $fileId, $company);
$stmt->execute();
$stmt->close();

$db->close();

echo json_encode(["status" => "success", "message" => "Logo uploaded successfully!", "filepath" => $dbPath]);
?>
