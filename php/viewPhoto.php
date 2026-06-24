<?php
require_once 'db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
    echo '<script type="text/javascript">';
    echo 'window.location.href = "login.php";</script>';
}
else{
    if (isset($_GET['file']) && !empty($_GET['file'])) {
        $file = filter_input(INPUT_GET, 'file', FILTER_SANITIZE_STRING);
        $type = null;

        if(isset($_GET['type']) && $_GET['type'] != null && $_GET['type'] != ''){
            $type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
        }

        if ($type == 'file_table'){
            $stmt = $db->prepare("SELECT * FROM files WHERE id = ? AND deleted = 0");
            $stmt->bind_param('s', $file);
            $stmt->execute();
            $result = $stmt->get_result();
        
            if ($row = $result->fetch_assoc()) {
                if ($row['method'] == 'local') {
                    $filepath = str_replace('../', '', $row['filepath']);
                    $filePath = str_replace('\\', '/', dirname(__DIR__, 2)) . '/' . $filepath;
            
                    if (file_exists($filePath)) {
                        header('Content-Type: ' . mime_content_type($filePath));
                        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
                        readfile($filePath);
                        exit;
                    } 
                    else {
                        echo 'File not found!!.';
                    }
                }
            } 
            else {
                echo 'File not found!!.';
            }

            $stmt->close();
            $db->close();
        }else{
            $filepath = str_replace('../', '', $file);
            $filePath = str_replace('\\', '/', dirname(__DIR__, 2)) . '/' . $filepath;

            if (file_exists($filePath)) {
                header('Content-Type: ' . mime_content_type($filePath));
                header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
                readfile($filePath);
                exit;
            } 
            else {
                echo 'File not found!!.';
            }
        }
    } else {
        echo 'Invalid file request.';
    }
}
?>
