<?php
session_start();

if(!isset($_SESSION['userID'])){
    echo '<script type="text/javascript">';
    echo 'window.location.href = "login.html";</script>';
} else {
    //$_SESSION['module'] = isset($_POST['module']) ? $_POST['module'] : '';
    //echo 'window.location.href = "../index.php";</script>';
    $_SESSION['module'] = isset($_GET['module']) ? $_GET['module'] : '';
    echo '<script type="text/javascript">';
    echo 'window.location.href = "../index.php";</script>';
}
?>