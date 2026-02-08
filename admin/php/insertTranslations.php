<?php
require_once 'db_connect.php';
require_once 'insertDefaultTranslations.php';

session_start();

if(!isset($_SESSION['adminID'])){
    echo json_encode(array('status' => 'failed', 'message' => 'Unauthorized'));
    exit();
}

if(isset($_POST['companyId'])){
    $companyId = $_POST['companyId'];
    
    try {
        insertDefaultTranslations($db, $companyId);
        echo json_encode(array('status' => 'success', 'message' => 'Default translations inserted successfully'));
    } catch (Exception $e) {
        echo json_encode(array('status' => 'failed', 'message' => 'Failed to insert translations: ' . $e->getMessage()));
    }
} else {
    echo json_encode(array('status' => 'failed', 'message' => 'Company ID is required'));
}
?>
