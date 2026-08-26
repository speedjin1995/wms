<?php
require_once '../../db_connect.php';
session_start();

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

if (isset($_POST['userID'], $_POST['moduleAccess'])) {
    $userId = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_NUMBER_INT);
    $moduleAccess = $_POST['moduleAccess'];
    
    // Validate JSON
    $decoded = json_decode($moduleAccess, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['status' => 'failed', 'message' => 'Invalid data format']);
        exit;
    }
    
    $stmt = $db->prepare("UPDATE users SET module_access = ? WHERE id = ?");
    $stmt->bind_param('si', $moduleAccess, $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Module access updated successfully']);
    } else {
        echo json_encode(['status' => 'failed', 'message' => $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'failed', 'message' => 'Missing required fields']);
}
?>
