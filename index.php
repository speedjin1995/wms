<?php
require_once 'php/db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
}
else{
  $company = $_SESSION['customer'];
  $user = $_SESSION['userID'];
  $module = $_SESSION['module'] ?? '';
  $packages = $_SESSION['packages'] ?? [];
  $products = $_SESSION['products'] ?? [];
  $enableDailySales = $_SESSION['enableDailySales'];
  $stmt = $db->prepare("SELECT * from users where id = ?");
	$stmt->bind_param('s', $user);
	$stmt->execute();
	$result = $stmt->get_result();
  $role = 'NORMAL';
  $name = '';
  $username = '';
	
	if(($row = $result->fetch_assoc()) !== null){
    $role = $row['role_code'];
    $name = $row['name'];
    $username = $row['username'];
  }

  // Language
  $language = $_SESSION['language'];

  // Load message resource
  if (in_array('P', $packages, true)) {
    $message_resource = $db->query("SELECT * FROM message_resource WHERE company = '$company'");
  }else{
    $message_resource = $db->query("SELECT * FROM message_resource WHERE company = 0");
  }
  
  $languageArray = Array();

  while($row=mysqli_fetch_assoc($message_resource)){
    $languageArray[$row['message_key_code']] = array("en"=>$row['en'],"zh"=>$row['zh'],"my"=>$row['my'],"ne"=>$row['ne'], "ja"=>$row['ja']);
  }

  $_SESSION['languageArray'] = $languageArray;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="x-ua-compatible" content="ie=edge">

  <title>WMS</title>

  <link rel="icon" href="assets/fy-fruit-trading-logo-icon.png" type="image">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- IonIcons -->
  <link rel="stylesheet" href="http://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <!-- Google Font: Source Sans Pro -->
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
  <!-- daterange picker -->
  <link rel="stylesheet" href="plugins/daterangepicker/daterangepicker.css">
  <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <!-- iCheck for checkboxes and radio inputs -->
  
  <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Bootstrap Color Picker -->
  <link rel="stylesheet" href="plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css">
  <!-- Select2 -->
  <link rel="stylesheet" href="plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <!-- Bootstrap4 Duallistbox -->
  <link rel="stylesheet" href="plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css">
  <!-- Toastr -->
  <link rel="stylesheet" href="plugins/toastr/toastr.min.css">
  <link rel="stylesheet" href="dist/css/adminlte.min.css?v=3.2.0">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/modal-global.css">

  <style>
    body {
      background: url('assets/main-bg.jpg');
      background-repeat: repeat;
      background-size: cover;
      background-position: center;
      font-family: Assistant, sans-serif
    }
  
    .cell-1 {
      border-collapse: separate;
      border-spacing: 0 4em;
      background: #ffffff;
      border-bottom: 5px solid transparent;
      background-clip: padding-box;
      cursor: pointer
    }
  
    .table-elipse {
      cursor: pointer
    }
  
    .expand-body {
      -webkit-transition: all 0.3s ease-in-out;
      -moz-transition: all 0.3s ease-in-out;
      -o-transition: all 0.3s 0.1s ease-in-out;
      transition: all 0.3s ease-in-out
    }
  
    .row-child {
      background-color: #000;
    }

    div.loading{
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(16, 16, 16, 0.5);
      z-index: 99999;
      pointer-events: all;
    }

    @-webkit-keyframes uil-ring-anim {
      0% {
        -ms-transform: rotate(0deg);
        -moz-transform: rotate(0deg);
        -webkit-transform: rotate(0deg);
        -o-transform: rotate(0deg);
        transform: rotate(0deg);
      }
      100% {
        -ms-transform: rotate(360deg);
        -moz-transform: rotate(360deg);
        -webkit-transform: rotate(360deg);
        -o-transform: rotate(360deg);
        transform: rotate(360deg);
      }
    }

    @-webkit-keyframes uil-ring-anim {
      0% {
        -ms-transform: rotate(0deg);
        -moz-transform: rotate(0deg);
        -webkit-transform: rotate(0deg);
        -o-transform: rotate(0deg);
        transform: rotate(0deg);
      }
      100% {
        -ms-transform: rotate(360deg);
        -moz-transform: rotate(360deg);
        -webkit-transform: rotate(360deg);
        -o-transform: rotate(360deg);
        transform: rotate(360deg);
      }
    }

    @-moz-keyframes uil-ring-anim {
      0% {
        -ms-transform: rotate(0deg);
        -moz-transform: rotate(0deg);
        -webkit-transform: rotate(0deg);
        -o-transform: rotate(0deg);
        transform: rotate(0deg);
      }
      100% {
        -ms-transform: rotate(360deg);
        -moz-transform: rotate(360deg);
        -webkit-transform: rotate(360deg);
        -o-transform: rotate(360deg);
        transform: rotate(360deg);
      }
    }

    @-ms-keyframes uil-ring-anim {
      0% {
        -ms-transform: rotate(0deg);
        -moz-transform: rotate(0deg);
        -webkit-transform: rotate(0deg);
        -o-transform: rotate(0deg);
        transform: rotate(0deg);
      }
      100% {
        -ms-transform: rotate(360deg);
        -moz-transform: rotate(360deg);
        -webkit-transform: rotate(360deg);
        -o-transform: rotate(360deg);
        transform: rotate(360deg);
      }
    }

    @-moz-keyframes uil-ring-anim {
      0% {
        -ms-transform: rotate(0deg);
        -moz-transform: rotate(0deg);
        -webkit-transform: rotate(0deg);
        -o-transform: rotate(0deg);
        transform: rotate(0deg);
      }
      100% {
        -ms-transform: rotate(360deg);
        -moz-transform: rotate(360deg);
        -webkit-transform: rotate(360deg);
        -o-transform: rotate(360deg);
        transform: rotate(360deg);
      }
    }

    @-webkit-keyframes uil-ring-anim {
      0% {
        -ms-transform: rotate(0deg);
        -moz-transform: rotate(0deg);
        -webkit-transform: rotate(0deg);
        -o-transform: rotate(0deg);
        transform: rotate(0deg);
      }
      100% {
        -ms-transform: rotate(360deg);
        -moz-transform: rotate(360deg);
        -webkit-transform: rotate(360deg);
        -o-transform: rotate(360deg);
        transform: rotate(360deg);
      }
    }

    @-o-keyframes uil-ring-anim {
      0% {
        -ms-transform: rotate(0deg);
        -moz-transform: rotate(0deg);
        -webkit-transform: rotate(0deg);
        -o-transform: rotate(0deg);
        transform: rotate(0deg);
      }
      100% {
        -ms-transform: rotate(360deg);
        -moz-transform: rotate(360deg);
        -webkit-transform: rotate(360deg);
        -o-transform: rotate(360deg);
        transform: rotate(360deg);
      }
    }

    @keyframes uil-ring-anim {
      0% {
        -ms-transform: rotate(0deg);
        -moz-transform: rotate(0deg);
        -webkit-transform: rotate(0deg);
        -o-transform: rotate(0deg);
        transform: rotate(0deg);
      }
      100% {
        -ms-transform: rotate(360deg);
        -moz-transform: rotate(360deg);
        -webkit-transform: rotate(360deg);
        -o-transform: rotate(360deg);
        transform: rotate(360deg);
      }
    }

    .uil-ring-css {
      margin: auto;
      position: absolute;
      top: 0;
      left: 0;
      bottom: 0;
      right: 0;
      width: 200px;
      height: 200px;
    }

    .uil-ring-css > div {
      position: absolute;
      display: block;
      width: 160px;
      height: 160px;
      top: 20px;
      left: 20px;
      border-radius: 80px;
      box-shadow: 0 6px 0 0 #ffffff;
      -ms-animation: uil-ring-anim 1s linear infinite;
      -moz-animation: uil-ring-anim 1s linear infinite;
      -webkit-animation: uil-ring-anim 1s linear infinite;
      -o-animation: uil-ring-anim 1s linear infinite;
      animation: uil-ring-anim 1s linear infinite;
    }

    /* New CSS Style */
    .custom-navbar-style {
      background: transparent;
      padding: 10px 25px;
      border-bottom: 1px solid #E3C66A;
    }

    .custom-bar-menu {
      padding: 0px !important;
      display: flex;
      align-items: center;
    }

    .custom-bar-menu i {
      background: transparent !important;
    }

    .custom-user-menu {
      background-color: #F9F7F2;
      border-radius: 50%;
      padding: 5px;
      width: 35px;
      height: 35px !important;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .custom-user-menu i {
      font-size: 15px;
      color: #1a1a1a;
    }

    .user-drop-down-menu {
      margin: 0px;
      margin-top: 5px;
      padding: 5px 0px;
      border: unset;
      border-radius: 5px;
      background: #F9F7F2;
      box-shadow: 0px 0px 5px 0px rgba(255, 255, 255, .5);
      color: #1a1a1a;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 400;
    }

    .user-drop-down-menu a {
      padding: 5px 15px;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 400;
      color: #1a1a1a;
    }

    .user-drop-down-menu a:hover, .user-drop-down-menu a:hover {
      color: #D9A82D;
    }

    .dropdown-footer, .dropdown-header {
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 700;
      color: #D9A82D;
      padding: 5px 15px;
    }

    .custom-sidebar-style {
      box-shadow: unset !important;
      background: transparent;
      border-right: 1px solid #E3C66A;
    }

    .sidebar-mini.sidebar-collapse .main-sidebar.custom-sidebar-style:not(.sidebar-no-expand):hover {
      background: #000;
    }

    .custom-content-wrapper, .user-drop-down-menu a:hover {
      background: transparent;
    }

    .custom-logo-box {
      transition: all 0.3s ease-in-out;
      padding: 25px 15px;
      border-bottom: 1px solid #fff !important;
      font-size: 15px;
      line-height: 23px;
      height: 170px;
    }

    .sidebar-mini.sidebar-collapse .custom-logo-box {
      transition: all 0.3s ease-in-out;
      height: 90px;
    }

    .sidebar-mini.sidebar-collapse .main-sidebar.custom-sidebar-style:not(.sidebar-no-expand):hover .custom-logo-box {
      height: 170px;
    }

    .custom-logo-box .custom-logo-main {
      width: 50%;
      max-height: max-content;
      left: 15px;
      top: 25px;
    }

    .custom-logo-box .custom-logo-icon {
      left: 15px;
      top: 25px;
    }

    .custom-sidebar-acc-menu {
      padding-left: 0px;
      padding-right: 0px;
    }

    .custom-user-panel {
      display: flex;
      align-items: center;
      padding-top: 25px;
      padding-bottom: 25px;
      padding-left: 15px;
      padding-right: 15px;
      border-bottom: 1px solid #fff !important;
    }

    .user-panel-img {
      margin-right: 15px;
      padding-left: 0px !important;
    }

    .sidebar-mini.sidebar-collapse .user-panel-img {
      margin-right: 0px;
    }

    .sidebar-mini.sidebar-collapse .main-sidebar.custom-sidebar-style:not(.sidebar-no-expand):hover .user-panel-img {
      margin-right: 15px;
    }

    .user-panel-img img {
      box-shadow: unset !important;
      width: 50px;
    }

    .sidebar-mini.sidebar-collapse .user-panel-img img {
      width: 40px;
    }

    .user-panel-info {
      padding: 0px !important;
    }

    .user-panel-txt {
      margin-bottom: 0px;
      color: #fff;
      font-size: 12px;
      line-height: 20px;
      letter-spacing: 0.75px;
      font-weight: 700;
      text-transform: uppercase;
    }

    .user-panel-name {
      font-size: 18px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 400;
      color: #D9A82D !important;
    }

    .custom-sidebar-menu ul {
      padding-top:10px;
      padding-bottom:10px;
      padding-left: 15px;
      padding-right: 15px;
    }

    .custom-sidebar-menu ul li {
      padding-top: 5px;
      padding-bottom: 5px;
    }

    .custom-sidebar-menu ul li a {
      margin-bottom: 0px !important;
      padding: 10px;
      width: calc(250px - 15px * 2) !important;
      display: flex;
      align-items: center;
      background: transparent;
      color: #F9F7F2 !important;
      border-radius: 5px !important;
    }

    .sidebar-mini.sidebar-collapse .custom-sidebar-menu ul li a {
      width: calc(250px - 103px * 2) !important;
    }

    .sidebar-mini.sidebar-collapse .main-sidebar.custom-sidebar-style:not(.sidebar-no-expand):hover .custom-sidebar-menu ul li a {
      width: calc(250px - 15px * 2) !important;
    }

    .custom-sidebar-menu ul li.menu-open ul li a {
      background: transparent;
      color: #F9F7F2 !important;
    }

    .custom-sidebar-menu ul li:hover a, .custom-sidebar-menu ul li a:focus, .custom-sidebar-menu ul li.menu-open a,
    .custom-sidebar-menu ul li ul li a:hover, .custom-sidebar-menu ul li ul li a:focus {
      background: linear-gradient(135deg, rgba(246, 213, 74, 1) 0%, rgba(255, 243, 199, 1) 50%, rgba(217, 168, 45, 1) 100%);
      color: #1a1a1a !important;
    }

    .custom-sidebar-menu ul li a.active, .custom-sidebar-menu ul li ul li a.active {
      background: linear-gradient(135deg, rgba(249, 247, 242, 1) 0%, rgba(249, 247, 242, 1) 50%, rgba(249, 247, 242, 1) 100%);
      color: #1a1a1a !important;
    }

    .custom-sidebar-menu ul li a i {
      width: 25px !important;
      margin-left: 0px !important;
      margin-right: 5px !important;
      font-size: 15px !important;
    }

    .sidebar-mini.sidebar-collapse .custom-sidebar-menu ul li a i {
      margin-right: 0px !important;
    }

    .sidebar-mini.sidebar-collapse .main-sidebar.custom-sidebar-style:not(.sidebar-no-expand):hover .custom-sidebar-menu ul li a i {
      margin-right: 5px !important;
    }

    .custom-sidebar-menu ul li a p {
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 700;
      display: flex !important;
      justify-content: space-between;
      align-items: center;
      width: 100%;
    }

    .sidebar-mini.sidebar-collapse .main-sidebar.custom-sidebar-style:not(.sidebar-no-expand):hover .custom-sidebar-menu ul li a p {
      width: 100%;
    }

    .custom-sidebar-menu ul li a p i {
      position: relative !important;
      top: 0 !important;
      right: 0 !important;
      margin-right: 0px !important;
      width: auto !important;
    }

    .sidebar-mini.sidebar-collapse .main-sidebar.custom-sidebar-style:not(.sidebar-no-expand):hover .custom-sidebar-menu ul li a p i {
      margin-right: 0px !important;
    }

    .custom-sidebar-menu ul li ul {
      padding-top: 5px !important;
      padding-bottom: 5px !important;
    }

    .custom-content-detail, .custom-search-card-body, .custom-table-card-body,
    .custom-table-content .custom-profile-box .custom-profile-body-box, .custom-form-card-body {
      padding: 25px;
    }

    .custom-main-footer {
      background: transparent;
      padding: 10px 25px;
      border-top: 1px solid #E3C66A;
      color: #F9F7F2;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 700;
    }

    .custom-main-footer a:hover {
      text-decoration: underline;
    }

    .custom-title-content-box {
      padding: 0px;
    }

    .custom-title {
      font-size: 35px !important;
      line-height: 40px;
      letter-spacing: 0.75px;
      font-weight: 700;
      color: #fff;
      margin-bottom: 35px !important;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .custom-title i {
      color: #D9A82D;
    }

    .custom-table-content {
      padding-left: 0px !important;
      padding-right: 0px !important;
    }

    .custom-table-content .container-fluid .row .card, .custom-table-content .custom-profile-box, .custom-main-card {
      margin-bottom: 25px;
      border: unset;
      border-radius: 5px;
      background: #F9F7F2;
      box-shadow: 0px 0px 10px 0px rgba(227, 198, 106, 1);
    }

    .custom-table-content #logoForm .custom-profile-box:first-child {
      margin-top: 25px;
    }

    .custom-table-content .container-fluid .row:last-child .card,
    .custom-table-card-body #weightTable_wrapper .row:last-child .dataTables_paginate .pagination,
    .custom-table-card-body #translationTable_wrapper .row:last-child .dataTables_paginate .pagination,
    .custom-table-card-body #stateTable_wrapper .row:last-child .dataTables_paginate .pagination,
    .custom-table-card-body #currencyTable_wrapper .row:last-child .dataTables_paginate .pagination,
    .custom-table-card-body #supplierTable_wrapper .row:last-child .dataTables_paginate .pagination,
    .custom-table-card-body #categoryTable_wrapper .row:last-child .dataTables_paginate .pagination,
    .custom-table-card-body #packagingTable_wrapper .row:last-child .dataTables_paginate .pagination,
    .custom-table-card-body #customerTable_wrapper .row:last-child .dataTables_paginate .pagination,
    .custom-table-card-body #productTable_wrapper .row:last-child .dataTables_paginate .pagination,
    .custom-table-card-body #driverTable_wrapper .row:last-child .dataTables_paginate .pagination,
    .custom-table-card-body #vehicleTable_wrapper .row:last-child .dataTables_paginate .pagination,
    .custom-table-card-body #gradeTable_wrapper .row:last-child .dataTables_paginate .pagination,
    .custom-table-card-body #locationTable_wrapper .row:last-child .dataTables_paginate .pagination,
    .custom-table-content #profileForm .custom-profile-box:last-child,
    .custom-table-content #logoForm .custom-profile-box:last-child,
    .custom-table-card-body #memberTable_wrapper .row:last-child .dataTables_paginate .pagination, .custom-main-card,
    .custom-table-card-body #pvTable_wrapper .row:last-child .dataTables_paginate .pagination,
    .custom-table-card-body #transferTable_wrapper .row:last-child .dataTables_paginate .pagination {
      margin-bottom: 0px;
    }

    .custom-model-body-box .card-body {
      min-height: unset;
      padding: 0px;
    }

    .custom-search-card-body .form-group,
    .custom-table-card-body #weightTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-model-body-box .form-group,
    .custom-table-card-body #translationTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #stateTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #currencyTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #supplierTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #categoryTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #packagingTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #customerTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #productTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #driverTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #gradeTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #locationTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-content .custom-profile-box .custom-profile-body-box .form-group,
    .custom-table-card-body #memberTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-form-card-body .form-group,
    .custom-table-card-body #pvTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #transferTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control {
      margin-bottom: 15px;
    }

    .custom-search-card-body .form-group label, .custom-model-body-box .form-group label,
    .custom-table-content .custom-profile-box .custom-profile-body-box .form-group label,
    .custom-form-card-body .form-group label {
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 700;
      color: #1a1a1a;
      margin-bottom: 5px;
    }

    .custom-search-card-body .form-group .form-control,
    .custom-table-card-body #weightTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-model-body-box .form-group .form-control, .custom-model-body-box .custom-model-title-box-form,
    .custom-table-card-body #translationTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #stateTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #currencyTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #supplierTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #categoryTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #packagingTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #customerTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #productTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-card-box .custom-card-box-body .custom-range-set-box .form-control,
    .custom-card-box .custom-card-box-body .table-bordered tbody tr td .form-control,
    .custom-tab-box .table-bordered tbody tr td .form-control,
    .custom-table-card-body #driverTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #gradeTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #locationTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-content .custom-profile-box .custom-profile-body-box .form-group .form-control,
    .custom-table-card-body #memberTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-form-card-body .form-group .form-control,
    .custom-table-card-body #pvTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control,
    .custom-table-card-body #transferTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control {
      height: 40px;
      background: #fff;
      color: #1a1a1a;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 400;
      border: 1px solid #E3C66A;
      border-radius: 5px;
      padding: 10px;
      box-shadow: unset;
    }

    .custom-search-card-body .form-group select.form-control,
    .custom-table-card-body #weightTable_wrapper .table-bordered tbody .custom-tbl-fliter-box select.form-control,
    .custom-model-body-box .form-group select.form-control,
    .custom-table-card-body #translationTable_wrapper .table-bordered tbody .custom-tbl-fliter-box select.form-control,
    .custom-table-card-body #stateTable_wrapper .table-bordered tbody .custom-tbl-fliter-box select.form-control,
    .custom-table-card-body #currencyTable_wrapper .table-bordered tbody .custom-tbl-fliter-box select.form-control,
    .custom-table-card-body #supplierTable_wrapper .table-bordered tbody .custom-tbl-fliter-box select.form-control,
    .custom-table-card-body #categoryTable_wrapper .table-bordered tbody .custom-tbl-fliter-box select.form-control,
    .custom-table-card-body #packagingTable_wrapper .table-bordered tbody .custom-tbl-fliter-box select.form-control,
    .custom-table-card-body #customerTable_wrapper .table-bordered tbody .custom-tbl-fliter-box select.form-control,
    .custom-table-card-body #productTable_wrapper .table-bordered tbody .custom-tbl-fliter-box select.form-control,
    .custom-card-box .custom-card-box-body .custom-range-set-box select.form-control,
    .custom-card-box .custom-card-box-body .table-bordered tbody tr td select.form-control,
    .custom-tab-box .table-bordered tbody tr td select.form-control,
    .custom-table-card-body #driverTable_wrapper .table-bordered tbody .custom-tbl-fliter-box select.form-control,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tbody .custom-tbl-fliter-box select.form-control,
    .custom-table-card-body #gradeTable_wrapper .table-bordered tbody .custom-tbl-fliter-box select.form-control,
    .custom-table-card-body #locationTable_wrapper .table-bordered tbody .custom-tbl-fliter-box select.form-control,
    .custom-table-content .custom-profile-box .custom-profile-body-box .form-group select.form-control,
    .custom-table-card-body #memberTable_wrapper .table-bordered tbody .custom-tbl-fliter-box select.form-control,
    .custom-form-card-body .form-group select.form-control,
    .custom-table-card-body #pvTable_wrapper .table-bordered tbody .custom-tbl-fliter-box select.form-control,
    .custom-table-card-body #transferTable_wrapper .table-bordered tbody .custom-tbl-fliter-box select.form-control {
      -webkit-appearance: none;
      appearance: none;
      background-image: url(assets/chevron-down-solid-full.svg);
      background-repeat: no-repeat;
      background-position: right 10px center;
      background-size: 10px;
    }

    .custom-search-card-body .form-group .form-control:focus, .custom-table-card-body .custom-select:focus,
    .custom-table-card-body #weightTable_wrapper .row:first-child #weightTable_filter label .form-control:focus,
    .custom-table-card-body #weightTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control:focus,
    .custom-model-body-box .form-group .form-control:focus, .custom-model-body-box .custom-select:focus,
    .custom-table-card-body #translationTable_wrapper .row:first-child #translationTable_filter label .form-control:focus,
    .custom-table-card-body #translationTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control:focus,
    .custom-table-card-body #stateTable_wrapper .row:first-child #stateTable_filter label .form-control:focus,
    .custom-table-card-body #stateTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control:focus,
    .custom-table-card-body #currencyTable_wrapper .row:first-child #currencyTable_filter label .form-control:focus,
    .custom-table-card-body #currencyTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control:focus,
    .custom-table-card-body #supplierTable_wrapper .row:first-child #supplierTable_filter label .form-control:focus,
    .custom-table-card-body #supplierTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control:focus,
    .custom-table-card-body #categoryTable_wrapper .row:first-child #categoryTable_filter label .form-control:focus,
    .custom-table-card-body #categoryTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control:focus,
    .custom-table-card-body #packagingTable_wrapper .row:first-child #packagingTable_filter label .form-control:focus,
    .custom-table-card-body #packagingTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control:focus,
    .custom-table-card-body #customerTable_wrapper .row:first-child #customerTable_filter label .form-control:focus,
    .custom-table-card-body #customerTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control:focus,
    .custom-table-card-body #productTable_wrapper .row:first-child #productTable_filter label .form-control:focus,
    .custom-table-card-body #productTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control:focus,
    .custom-card-box .custom-card-box-body .custom-range-set-box .form-control:focus,
    .custom-card-box .custom-card-box-body .table-bordered tbody tr td .form-control:focus,
    .custom-tab-box .table-bordered tbody tr td .form-control:focus,
    .custom-table-card-body #driverTable_wrapper .row:first-child #driverTable_filter label .form-control:focus,
    .custom-table-card-body #driverTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control:focus,
    .custom-table-card-body #vehicleTable_wrapper .row:first-child #vehicleTable_filter label .form-control:focus,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control:focus,
    .custom-table-card-body #gradeTable_wrapper .row:first-child #gradeTable_filter label .form-control:focus,
    .custom-table-card-body #gradeTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control:focus,
    .custom-table-card-body #locationTable_wrapper .row:first-child #locationTable_filter label .form-control:focus,
    .custom-table-card-body #locationTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control:focus,
    .custom-table-content .custom-profile-box .custom-profile-body-box .form-group .form-control:focus,
    .custom-table-card-body #memberTable_wrapper .row:first-child #memberTable_filter label .form-control:focus,
    .custom-table-card-body #memberTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control:focus,
    .custom-form-card-body .form-group .form-control:focus,
    .custom-table-card-body #pvTable_wrapper .row:first-child #pvTable_filter label .form-control:focus,
    .custom-table-card-body #pvTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control:focus,
    .custom-table-card-body #transferTable_wrapper .row:first-child #transferTable_filter label .form-control:focus,
    .custom-table-card-body #transferTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control:focus {
      border-color: #1a1a1a;
      box-shadow: unset;
    }

    .custom-search-card-body .form-group .form-control::placeholder,
    .custom-search-card-body .form-group .select2-selection .select2-selection__rendered .select2-selection__placeholder,
    .custom-table-card-body #weightTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control::placeholder,
    .custom-model-body-box .form-group .form-control::placeholder,
    .custom-model-body-box .form-group .select2-selection .select2-selection__rendered .select2-selection__placeholder,
    .custom-table-card-body #translationTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control::placeholder,
    .custom-table-card-body #stateTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control::placeholder,
    .custom-table-card-body #currencyTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control::placeholder,
    .custom-table-card-body #supplierTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control::placeholder,
    .custom-table-card-body #categoryTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control::placeholder,
    .custom-table-card-body #packagingTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control::placeholder,
    .custom-table-card-body #customerTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control::placeholder,
    .custom-table-card-body #productTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control::placeholder,
    .custom-card-box .custom-card-box-body .custom-range-set-box .form-control::placeholder,
    .custom-card-box .custom-card-box-body .table-bordered tbody tr td .form-control::placeholder,
    .custom-tab-box .table-bordered tbody tr td .form-control::placeholder,
    .custom-table-card-body #driverTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control::placeholder,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control::placeholder,
    .custom-table-card-body #gradeTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control::placeholder,
    .custom-table-card-body #locationTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control::placeholder,
    .custom-table-content .custom-profile-box .custom-profile-body-box .form-group .form-control::placeholder,
    .custom-table-card-body #memberTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control::placeholder,
    .custom-form-card-body .form-group .form-control::placeholder,
    .custom-form-card-body .form-group .select2-selection .select2-selection__rendered .select2-selection__placeholder,
    .custom-table-card-body #pvTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control::placeholder,
    .custom-table-card-body #transferTable_wrapper .table-bordered tbody .custom-tbl-fliter-box .form-control::placeholder {
      color: rgba(26, 26, 26, .5);
    }

    .custom-table-content .custom-profile-box .custom-profile-body-box .form-group .form-control:read-only {
      background: rgba(26, 26, 26, .05);
    }

    .custom-search-card-body .form-group .input-group-append .input-group-text,
    .custom-card-box-body .form-group .input-group-append .input-group-text,
    .custom-card-box-body .form-group .input-group-unit .input-group-text,
    .custom-model-body-box .form-group .input-group-append .input-group-text {
      border: 1px solid #E3C66A;
      background: #E3C66A;
      color: #fff;
      border-top-right-radius: 5px;
      border-bottom-right-radius: 5px;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 400;
      padding: 10px;
    }

    .custom-card-box-body .form-group .input-group-unit .input-group-text {
      height: 40px;
      border-top-left-radius: 0px;
      border-bottom-left-radius: 0px;
    }

    .custom-table-content .custom-profile-box .custom-profile-body-box .form-group .input-group-prepend .input-group-text {
      border: 1px solid #E3C66A;
      background: #E3C66A;
      color: #fff;
      border-top-left-radius: 5px;
      border-bottom-left-radius: 5px;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 400;
      padding: 10px;
    }

    .custom-table-content .custom-profile-box .custom-profile-body-box .form-group .input-group .form-control {
      border-top-left-radius: 0px;
      border-bottom-left-radius: 0px;
    }

    .custom-table-content .custom-profile-box .img-thumbnail {
      padding: 15px;
      background-color: #fff;
      border: 1px solid #E3C66A;
      border-radius: 5px;
      box-shadow: unset;
      max-width: unset;
      max-height: unset;
      width: 100%;
      height: 100%;
    }

    .custom-table-content .custom-profile-box .upload-img-notice, .custom-form-card-body .form-group .email-address-notice,
    .custom-card-box-body .form-group .batch-txt-notice, .batch-txt-notice {
      font-size: 12px;
      line-height: 20px;
      letter-spacing: 0.75px;
      font-weight: 400;
      color: #1a1a1a;
    }

    .custom-search-card-body .form-group .select2-selection, .custom-model-body-box .form-group .select2-selection,
    .custom-card-box .custom-card-box-body .table-bordered tbody tr td .select2-selection,
    .custom-tab-box .table-bordered tbody tr td .select2-selection, .custom-form-card-body .form-group .select2-selection {
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 400;
      height: 40px;
      padding: 10px;
      border: 1px solid #E3C66A;
      border-radius: 5px;
      color: #1a1a1a;
      background: #fff;
    }

    .custom-card-box .custom-card-box-body .table-bordered tbody tr td .select2-selection,
    .custom-tab-box .table-bordered tbody tr td .select2-selection {
      height: 40px !important;
      padding: 10px !important;
    }

    .custom-search-card-body .form-group .select2-selection .select2-selection__rendered,
    .custom-model-body-box .form-group .select2-selection .select2-selection__rendered,
    .custom-card-box .custom-card-box-body .table-bordered tbody tr td .select2-selection .select2-selection__rendered,
    .custom-tab-box .table-bordered tbody tr td .select2-selection .select2-selection__rendered,
    .custom-form-card-body .form-group .select2-selection .select2-selection__rendered {
      margin-top: 0px;
      padding: 0px;
      line-height: 22px;
      color: #1a1a1a;
      margin-bottom: 0px;
    }

    .custom-search-card-body .form-group .select2-selection .select2-selection__rendered .select2-search--inline,
    .custom-model-body-box .form-group .select2-selection .select2-selection__rendered .select2-search--inline,
    .custom-form-card-body .form-group .select2-selection .select2-selection__rendered .select2-search--inline {
      margin-left: 0px;
    }

    .custom-search-card-body .form-group .select2-selection .select2-selection__rendered .select2-search--inline .select2-search__field,
    .custom-model-body-box .form-group .select2-selection .select2-selection__rendered .select2-search--inline .select2-search__field,
    .custom-form-card-body .form-group .select2-selection .select2-selection__rendered .select2-search--inline .select2-search__field {
      margin-top: 0px;
      padding: 0px;
      position: relative;
      top: -10px;
    }

    .custom-search-card-body .form-group .select2-selection .select2-selection__rendered .select2-selection__clear,
    .custom-model-body-box .form-group .select2-selection .select2-selection__rendered .select2-selection__clear,
    .custom-card-box .custom-card-box-body .table-bordered tbody tr td .select2-selection .select2-selection__rendered .select2-selection__clear,
    .custom-tab-box .table-bordered tbody tr td .select2-selection .select2-selection__rendered .select2-selection__clear,
    .custom-form-card-body .form-group .select2-selection .select2-selection__rendered .select2-selection__clear {
      z-index: 1;
      right: 15px;
    }

    .custom-search-card-body .form-group .select2-selection .select2-selection__arrow,
    .custom-model-body-box .form-group .select2-selection .select2-selection__arrow,
    .custom-card-box .custom-card-box-body .table-bordered tbody tr td .select2-selection .select2-selection__arrow,
    .custom-tab-box .table-bordered tbody tr td .select2-selection .select2-selection__arrow,
    .custom-form-card-body .form-group .select2-selection .select2-selection__arrow {
      height: 40px;
      right: 5px;
      top: -1px;
    }

    .custom-card-box .custom-card-box-body .table-bordered tbody tr td .select2-selection .select2-selection__arrow,
    .custom-tab-box .table-bordered tbody tr td .select2-selection .select2-selection__arrow {
      height: 40px !important;
      padding-top: 0px !important;
    }

    .custom-search-card-body .form-group .select2-selection .select2-selection__arrow b,
    .custom-model-body-box .form-group .select2-selection .select2-selection__arrow b,
    .custom-card-box .custom-card-box-body .table-bordered tbody tr td .select2-selection .select2-selection__arrow b,
    .custom-tab-box .table-bordered tbody tr td .select2-selection .select2-selection__arrow b,
    .custom-form-card-body .form-group .select2-selection .select2-selection__arrow b {
      border-color: rgba(26, 26, 26, .5) transparent transparent transparent;
    }

    .select2-container--default .select2-dropdown {
      border: 1px solid #E3C66A;
      border-bottom-left-radius: 5px;
      border-bottom-right-radius: 5px;
    }

    .select2-search--dropdown {
      padding: 5px;
    }

    .select2-container--default .select2-dropdown .select2-search__field,
    .select2-container--default .select2-search--inline .select2-search__field {
      height: 40px;
      border: 1px solid #E3C66A;
      border-radius: 5px;
      padding: 5px;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 400;
      color: #1a1a1a;
    }

    .select2-container--default .select2-results__option {
      padding: 5px 10px;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 400;
      color: #1a1a1a;
      text-transform: capitalize;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected], 
    .select2-container--default .select2-results__option--highlighted[aria-selected]:hover {
      background: #D9A82D;
    }

    .custom-search-btn {
      padding: 10px 25px;
      border: unset;
      border-radius: 5px;
      background: #EA580C;
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 5px;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 700;
      margin-top: 0px !important;
    }

    .custom-search-btn:hover, .custom-reject-btn-icon:hover, .custom-filter-btn-sm:hover {
      background: #C2410C;
      color: #fff;
    }

    .custom-card-header {
      background: linear-gradient(135deg,rgba(246, 213, 74, 1) 0%, rgba(255, 243, 199, 1) 50%, rgba(217, 168, 45, 1) 100%);
      padding: 25px;
    }

    .custom-card-header .custom-card-header-row {
      align-items: center;
    }

    .custom-card-header .custom-card-header-row .custom-card-header-btn-col {
      display: flex;
      gap: 15px;
      justify-content: flex-end;
    }

    .custom-card-header .custom-card-header-row .custom-card-header-btn-col .custom-card-header-btn-size {
      width: 20%;
    }

    .custom-card-header-title {
      font-size: 25px;
      line-height: 30px;
      letter-spacing: 0.75px;
      font-weight: 700;
      color: #1a1a1a;
      display: flex;
      align-items: center;
      margin-bottom: 0px;
    }

    .custom-add-btn {
      padding: 10px 25px;
      border: unset;
      border-radius: 5px;
      background: #16A34A;
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 5px;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 700;
      margin-top: 0px !important;
    }

    .custom-add-btn:hover, .custom-save-btn:hover, .custom-add-btn-sm:hover, .custom-check-btn-icon:hover {
      background: #15803D;
      color: #fff;
    }

    .custom-table-card-body #weightTable_wrapper .row:first-child #weightTable_length label,
    .custom-table-card-body #weightTable_wrapper .row:first-child #weightTable_filter label,
    .custom-table-card-body #translationTable_wrapper .row:first-child #translationTable_length label,
    .custom-table-card-body #translationTable_wrapper .row:first-child #translationTable_filter label,
    .custom-table-card-body #stateTable_wrapper .row:first-child #stateTable_length label,
    .custom-table-card-body #stateTable_wrapper .row:first-child #stateTable_filter label,
    .custom-table-card-body #currencyTable_wrapper .row:first-child #currencyTable_length label,
    .custom-table-card-body #currencyTable_wrapper .row:first-child #currencyTable_filter label,
    .custom-table-card-body #supplierTable_wrapper .row:first-child #supplierTable_length label,
    .custom-table-card-body #supplierTable_wrapper .row:first-child #supplierTable_filter label,
    .custom-table-card-body #categoryTable_wrapper .row:first-child #categoryTable_length label,
    .custom-table-card-body #categoryTable_wrapper .row:first-child #categoryTable_filter label,
    .custom-table-card-body #packagingTable_wrapper .row:first-child #packagingTable_length label,
    .custom-table-card-body #packagingTable_wrapper .row:first-child #packagingTable_filter label,
    .custom-table-card-body #customerTable_wrapper .row:first-child #customerTable_length label,
    .custom-table-card-body #customerTable_wrapper .row:first-child #customerTable_filter label,
    .custom-table-card-body #productTable_wrapper .row:first-child #productTable_length label,
    .custom-table-card-body #productTable_wrapper .row:first-child #productTable_filter label,
    .custom-table-card-body #driverTable_wrapper .row:first-child #driverTable_length label,
    .custom-table-card-body #driverTable_wrapper .row:first-child #driverTable_filter label,
    .custom-table-card-body #vehicleTable_wrapper .row:first-child #vehicleTable_length label,
    .custom-table-card-body #vehicleTable_wrapper .row:first-child #vehicleTable_filter label,
    .custom-table-card-body #gradeTable_wrapper .row:first-child #gradeTable_length label,
    .custom-table-card-body #gradeTable_wrapper .row:first-child #gradeTable_filter label,
    .custom-table-card-body #locationTable_wrapper .row:first-child #locationTable_length label,
    .custom-table-card-body #locationTable_wrapper .row:first-child #locationTable_filter label,
    .custom-table-card-body #memberTable_wrapper .row:first-child #memberTable_length label,
    .custom-table-card-body #memberTable_wrapper .row:first-child #memberTable_filter label,
    .custom-table-card-body #pvTable_wrapper .row:first-child #pvTable_length label,
    .custom-table-card-body #pvTable_wrapper .row:first-child #pvTable_filter label,
    .custom-table-card-body #transferTable_wrapper .row:first-child #transferTable_length label,
    .custom-table-card-body #transferTable_wrapper .row:first-child #transferTable_filter label {
      display: flex;
      align-items: center;
      gap: 15px;
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 700;
      color: #1a1a1a;
      text-transform: capitalize;
      margin-bottom: 15px;
    }

    .custom-table-card-body .custom-select, 
    .custom-table-card-body #weightTable_wrapper .row:first-child #weightTable_filter label .form-control,
    .custom-table-card-body #translationTable_wrapper .row:first-child #translationTable_filter label .form-control,
    .custom-table-card-body #stateTable_wrapper .row:first-child #stateTable_filter label .form-control,
    .custom-table-card-body #currencyTable_wrapper .row:first-child #currencyTable_filter label .form-control,
    .custom-table-card-body #supplierTable_wrapper .row:first-child #supplierTable_filter label .form-control,
    .custom-table-card-body #categoryTable_wrapper .row:first-child #categoryTable_filter label .form-control,
    .custom-table-card-body #packagingTable_wrapper .row:first-child #packagingTable_filter label .form-control,
    .custom-table-card-body #customerTable_wrapper .row:first-child #customerTable_filter label .form-control,
    .custom-table-card-body #productTable_wrapper .row:first-child #productTable_filter label .form-control,
    .custom-table-card-body #driverTable_wrapper .row:first-child #driverTable_filter label .form-control,
    .custom-table-card-body #vehicleTable_wrapper .row:first-child #vehicleTable_filter label .form-control,
    .custom-table-card-body #gradeTable_wrapper .row:first-child #gradeTable_filter label .form-control,
    .custom-table-card-body #locationTable_wrapper .row:first-child #locationTable_filter label .form-control,
    .custom-table-card-body #memberTable_wrapper .row:first-child #memberTable_filter label .form-control,
    .custom-table-card-body #pvTable_wrapper .row:first-child #pvTable_filter label .form-control,
    .custom-table-card-body #transferTable_wrapper .row:first-child #transferTable_filter label .form-control {
      height: 40px;
      padding: 10px;
      border: 1px solid #E3C66A;
      border-radius: 5px;
      box-shadow: unset;
      background: #fff;
      color: #1a1a1a;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 400;
    }

    .custom-model-body-box .form-group .custom-remarks-txtarea, .custom-model-body-box .form-group .custom-description-txtarea,
    .custom-model-body-box .form-group .custom-remark-txtarea, .custom-model-body-box .form-group .custom-reason-txtarea {
      height: 80px;
    }

    .custom-table-card-body #weightTable_wrapper .table-bordered, .custom-model-body-box .custom-add-table-detail,
    .custom-table-card-body #translationTable_wrapper .table-bordered,
    .custom-table-card-body #stateTable_wrapper .table-bordered,
    .custom-table-card-body #currencyTable_wrapper .table-bordered,
    .custom-table-card-body #supplierTable_wrapper .table-bordered,
    .custom-table-card-body #categoryTable_wrapper .table-bordered,
    .custom-table-card-body #packagingTable_wrapper .table-bordered,
    .custom-table-card-body #customerTable_wrapper .table-bordered,
    .custom-table-card-body #productTable_wrapper .table-bordered, .custom-tab-box .table-bordered,
    .custom-table-card-body #driverTable_wrapper .table-bordered,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered,
    .custom-table-card-body #gradeTable_wrapper .table-bordered,
    .custom-table-card-body #locationTable_wrapper .table-bordered,
    .custom-table-card-body #memberTable_wrapper .table-bordered,
    .custom-table-card-body #pvTable_wrapper .table-bordered,
    .custom-table-card-body #transferTable_wrapper .table-bordered {
      margin-top: 10px;
      margin-bottom: 25px;
      border: 1px solid #D9A82D;
      background: #fff;
      color: #1a1a1a;
    }

    .custom-model-body-box .custom-add-table-detail {
      margin-left: 8px;
      margin-right: 8px;
    }

    .custom-table-card-body #weightTable_wrapper .table-bordered thead th,
    .custom-model-body-box .custom-add-table-detail thead th,
    .custom-table-card-body #translationTable_wrapper .table-bordered thead th,
    .custom-table-card-body #stateTable_wrapper .table-bordered thead th,
    .custom-table-card-body #currencyTable_wrapper .table-bordered thead th,
    .custom-table-card-body #supplierTable_wrapper .table-bordered thead th,
    .custom-table-card-body #categoryTable_wrapper .table-bordered thead th,
    .custom-table-card-body #packagingTable_wrapper .table-bordered thead th,
    .custom-table-card-body #customerTable_wrapper .table-bordered thead th,
    .custom-table-card-body #productTable_wrapper .table-bordered thead th,
    .custom-card-box .custom-card-box-body .table-bordered thead th,
    .custom-model-body-box .tab-content .table-bordered thead th, .custom-tab-box .table-bordered thead th,
    .custom-table-card-body #driverTable_wrapper .table-bordered thead th,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered thead th,
    .custom-table-card-body #gradeTable_wrapper .table-bordered thead th,
    .custom-table-card-body #locationTable_wrapper .table-bordered thead th,
    .custom-table-card-body #memberTable_wrapper .table-bordered thead th,
    .custom-table-card-body #pvTable_wrapper .table-bordered thead th,
    .custom-table-card-body #transferTable_wrapper .table-bordered thead th {
      background: #D9A82D;
      color: #fff;
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 700;
      text-align: center;
      vertical-align: middle;
      border: 1px solid #fff;
      border-top: 1px solid #D9A82D;
      border-bottom: 1px solid #D9A82D;
    }

    .custom-table-card-body #weightTable_wrapper .table-bordered thead th:first-child,
    .custom-model-body-box .custom-add-table-detail thead th:first-child,
    .custom-table-card-body #translationTable_wrapper .table-bordered thead th:first-child,
    .custom-table-card-body #stateTable_wrapper .table-bordered thead th:first-child,
    .custom-table-card-body #currencyTable_wrapper .table-bordered thead th:first-child,
    .custom-table-card-body #supplierTable_wrapper .table-bordered thead th:first-child,
    .custom-table-card-body #categoryTable_wrapper .table-bordered thead th:first-child,
    .custom-table-card-body #packagingTable_wrapper .table-bordered thead th:first-child,
    .custom-table-card-body #customerTable_wrapper .table-bordered thead th:first-child,
    .custom-table-card-body #productTable_wrapper .table-bordered thead th:first-child,
    .custom-card-box .custom-card-box-body .table-bordered thead th:first-child,
    .custom-model-body-box .tab-content .table-bordered thead th:first-child,
    .custom-tab-box .table-bordered thead th:first-child,
    .custom-table-card-body #driverTable_wrapper .table-bordered thead th:first-child,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered thead th:first-child,
    .custom-table-card-body #gradeTable_wrapper .table-bordered thead th:first-child,
    .custom-table-card-body #locationTable_wrapper .table-bordered thead th:first-child,
    .custom-table-card-body #memberTable_wrapper .table-bordered thead th:first-child,
    .custom-table-card-body #pvTable_wrapper .table-bordered thead th:first-child,
    .custom-table-card-body #transferTable_wrapper .table-bordered thead th:first-child {
      border-left: 1px solid #D9A82D;
    }

    .custom-table-card-body #weightTable_wrapper .table-bordered thead th:last-child,
    .custom-model-body-box .custom-add-table-detail thead th:last-child,
    .custom-table-card-body #translationTable_wrapper .table-bordered thead th:last-child,
    .custom-table-card-body #stateTable_wrapper .table-bordered thead th:last-child,
    .custom-table-card-body #currencyTable_wrapper .table-bordered thead th:last-child,
    .custom-table-card-body #supplierTable_wrapper .table-bordered thead th:last-child,
    .custom-table-card-body #categoryTable_wrapper .table-bordered thead th:last-child,
    .custom-table-card-body #packagingTable_wrapper .table-bordered thead th:last-child,
    .custom-table-card-body #customerTable_wrapper .table-bordered thead th:last-child,
    .custom-table-card-body #productTable_wrapper .table-bordered thead th:last-child,
    .custom-card-box .custom-card-box-body .table-bordered thead th:last-child,
    .custom-model-body-box .tab-content .table-bordered thead th:last-child, .custom-tab-box .table-bordered thead th:last-child,
    .custom-table-card-body #driverTable_wrapper .table-bordered thead th:last-child,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered thead th:last-child,
    .custom-table-card-body #gradeTable_wrapper .table-bordered thead th:last-child,
    .custom-table-card-body #locationTable_wrapper .table-bordered thead th:last-child,
    .custom-table-card-body #memberTable_wrapper .table-bordered thead th:last-child,
    .custom-table-card-body #pvTable_wrapper .table-bordered thead th:last-child,
    .custom-table-card-body #transferTable_wrapper .table-bordered thead th:last-child {
      border-right: 1px solid #D9A82D;
    }

    .custom-table-card-body #weightTable_wrapper .table-bordered tbody tr:nth-of-type(odd),
    .custom-model-body-box .custom-add-table-detail tbody tr:nth-of-type(odd),
    .custom-table-card-body #translationTable_wrapper .table-bordered tbody tr:nth-of-type(odd),
    .custom-table-card-body #stateTable_wrapper .table-bordered tbody tr:nth-of-type(odd),
    .custom-table-card-body #currencyTable_wrapper .table-bordered tbody tr:nth-of-type(odd),
    .custom-table-card-body #supplierTable_wrapper .table-bordered tbody tr:nth-of-type(odd),
    .custom-table-card-body #categoryTable_wrapper .table-bordered tbody tr:nth-of-type(odd),
    .custom-table-card-body #packagingTable_wrapper .table-bordered tbody tr:nth-of-type(odd),
    .custom-table-card-body #customerTable_wrapper .table-bordered tbody tr:nth-of-type(odd),
    .custom-table-card-body #productTable_wrapper .table-bordered tbody tr:nth-of-type(odd),
    .custom-card-box .custom-card-box-body .table-bordered tbody tr:nth-of-type(odd),
    .custom-model-body-box .tab-content .table-bordered tbody tr:nth-of-type(odd),
    .custom-tab-box .table-bordered tbody tr:nth-of-type(odd),
    .custom-table-card-body #driverTable_wrapper .table-bordered tbody tr:nth-of-type(odd),
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tbody tr:nth-of-type(odd),
    .custom-table-card-body #gradeTable_wrapper .table-bordered tbody tr:nth-of-type(odd),
    .custom-table-card-body #locationTable_wrapper .table-bordered tbody tr:nth-of-type(odd),
    .custom-table-card-body #memberTable_wrapper .table-bordered tbody tr:nth-of-type(odd),
    .custom-table-card-body #pvTable_wrapper .table-bordered tbody tr:nth-of-type(odd),
    .custom-table-card-body #transferTable_wrapper .table-bordered tbody tr:nth-of-type(odd) {
      background: rgba(26, 26, 26, .15);
    }

    .custom-table-card-body #weightTable_wrapper .table-bordered tbody tr:nth-of-type(even),
    .custom-model-body-box .custom-add-table-detail tbody tr:nth-of-type(even),
    .custom-table-card-body #translationTable_wrapper .table-bordered tbody tr:nth-of-type(even),
    .custom-table-card-body #stateTable_wrapper .table-bordered tbody tr:nth-of-type(even),
    .custom-table-card-body #currencyTable_wrapper .table-bordered tbody tr:nth-of-type(even),
    .custom-table-card-body #supplierTable_wrapper .table-bordered tbody tr:nth-of-type(even),
    .custom-table-card-body #categoryTable_wrapper .table-bordered tbody tr:nth-of-type(even),
    .custom-table-card-body #packagingTable_wrapper .table-bordered tbody tr:nth-of-type(even),
    .custom-table-card-body #customerTable_wrapper .table-bordered tbody tr:nth-of-type(even),
    .custom-table-card-body #productTable_wrapper .table-bordered tbody tr:nth-of-type(even),
    .custom-card-box .custom-card-box-body .table-bordered tbody tr:nth-of-type(even),
    .custom-model-body-box .tab-content .table-bordered tbody tr:nth-of-type(even),
    .custom-tab-box .table-bordered tbody tr:nth-of-type(even),
    .custom-table-card-body #driverTable_wrapper .table-bordered tbody tr:nth-of-type(even),
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tbody tr:nth-of-type(even),
    .custom-table-card-body #gradeTable_wrapper .table-bordered tbody tr:nth-of-type(even),
    .custom-table-card-body #locationTable_wrapper .table-bordered tbody tr:nth-of-type(even),
    .custom-table-card-body #memberTable_wrapper .table-bordered tbody tr:nth-of-type(even),
    .custom-table-card-body #pvTable_wrapper .table-bordered tbody tr:nth-of-type(even),
    .custom-table-card-body #transferTable_wrapper .table-bordered tbody tr:nth-of-type(even) {
      background: #fff;
    }

    .custom-table-card-body #weightTable_wrapper .table-bordered tbody tr td,
    .custom-model-body-box .custom-add-table-detail tbody tr td,
    .custom-table-card-body #translationTable_wrapper .table-bordered tbody tr td,
    .custom-table-card-body #stateTable_wrapper .table-bordered tbody tr td,
    .custom-table-card-body #currencyTable_wrapper .table-bordered tbody tr td,
    .custom-table-card-body #supplierTable_wrapper .table-bordered tbody tr td,
    .custom-table-card-body #categoryTable_wrapper .table-bordered tbody tr td,
    .custom-table-card-body #packagingTable_wrapper .table-bordered tbody tr td,
    .custom-table-card-body #customerTable_wrapper .table-bordered tbody tr td,
    .custom-table-card-body #productTable_wrapper .table-bordered tbody tr td,
    .custom-card-box .custom-card-box-body .table-bordered tbody tr td,
    .custom-model-body-box .tab-content .table-bordered tbody tr td, .custom-tab-box .table-bordered tbody tr td,
    .custom-table-card-body #driverTable_wrapper .table-bordered tbody tr td,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tbody tr td,
    .custom-table-card-body #gradeTable_wrapper .table-bordered tbody tr td,
    .custom-table-card-body #locationTable_wrapper .table-bordered tbody tr td,
    .custom-table-card-body #memberTable_wrapper .table-bordered tbody tr td,
    .custom-table-card-body #pvTable_wrapper .table-bordered tbody tr td,
    .custom-table-card-body #transferTable_wrapper .table-bordered tbody tr td {
      padding: 10px;
      border: 1px solid #D9A82D;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 400;
    }

    .custom-table-card-body #weightTable_wrapper .table-bordered tbody .custom-tbl-title-box,
    .custom-table-card-body #translationTable_wrapper .table-bordered tbody .custom-tbl-title-box,
    .custom-table-card-body #stateTable_wrapper .table-bordered tbody .custom-tbl-title-box,
    .custom-table-card-body #currencyTable_wrapper .table-bordered tbody .custom-tbl-title-box,
    .custom-table-card-body #supplierTable_wrapper .table-bordered tbody .custom-tbl-title-box,
    .custom-table-card-body #categoryTable_wrapper .table-bordered tbody .custom-tbl-title-box,
    .custom-table-card-body #packagingTable_wrapper .table-bordered tbody .custom-tbl-title-box,
    .custom-table-card-body #customerTable_wrapper .table-bordered tbody .custom-tbl-title-box,
    .custom-table-card-body #productTable_wrapper .table-bordered tbody .custom-tbl-title-box,
    .custom-table-card-body #driverTable_wrapper .table-bordered tbody .custom-tbl-title-box,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tbody .custom-tbl-title-box,
    .custom-table-card-body #gradeTable_wrapper .table-bordered tbody .custom-tbl-title-box,
    .custom-table-card-body #locationTable_wrapper .table-bordered tbody .custom-tbl-title-box,
    .custom-table-card-body #memberTable_wrapper .table-bordered tbody .custom-tbl-title-box,
    .custom-table-card-body #pvTable_wrapper .table-bordered tbody .custom-tbl-title-box,
    .custom-table-card-body #transferTable_wrapper .table-bordered tbody .custom-tbl-title-box {
      padding-top: 15px;
      padding-left: 15px;
      padding-right: 15px;
    }

    .custom-table-card-body #weightTable_wrapper .table-bordered tbody .custom-tbl-title-box .custom-tbl-title-box-txt,
    .custom-table-card-body #translationTable_wrapper .table-bordered tbody .custom-tbl-title-box .custom-tbl-title-box-txt,
    .custom-table-card-body #stateTable_wrapper .table-bordered tbody .custom-tbl-title-box .custom-tbl-title-box-txt,
    .custom-table-card-body #currencyTable_wrapper .table-bordered tbody .custom-tbl-title-box .custom-tbl-title-box-txt,
    .custom-table-card-body #supplierTable_wrapper .table-bordered tbody .custom-tbl-title-box .custom-tbl-title-box-txt,
    .custom-table-card-body #categoryTable_wrapper .table-bordered tbody .custom-tbl-title-box .custom-tbl-title-box-txt,
    .custom-table-card-body #packagingTable_wrapper .table-bordered tbody .custom-tbl-title-box .custom-tbl-title-box-txt,
    .custom-table-card-body #customerTable_wrapper .table-bordered tbody .custom-tbl-title-box .custom-tbl-title-box-txt,
    .custom-table-card-body #productTable_wrapper .table-bordered tbody .custom-tbl-title-box .custom-tbl-title-box-txt,
    .custom-table-card-body #driverTable_wrapper .table-bordered tbody .custom-tbl-title-box .custom-tbl-title-box-txt,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tbody .custom-tbl-title-box .custom-tbl-title-box-txt,
    .custom-table-card-body #gradeTable_wrapper .table-bordered tbody .custom-tbl-title-box .custom-tbl-title-box-txt,
    .custom-table-card-body #locationTable_wrapper .table-bordered tbody .custom-tbl-title-box .custom-tbl-title-box-txt,
    .custom-table-card-body #memberTable_wrapper .table-bordered tbody .custom-tbl-title-box .custom-tbl-title-box-txt,
    .custom-table-card-body #pvTable_wrapper .table-bordered tbody .custom-tbl-title-box .custom-tbl-title-box-txt,
    .custom-table-card-body #transferTable_wrapper .table-bordered tbody .custom-tbl-title-box .custom-tbl-title-box-txt {
      margin-bottom: 25px;
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 700;
      text-decoration: underline;
      color: #1a1a1a;
    }

    .custom-table-card-body #weightTable_wrapper .table-bordered tbody .custom-tbl-content-box,
    .custom-table-card-body #translationTable_wrapper .table-bordered tbody .custom-tbl-content-box,
    .custom-table-card-body #stateTable_wrapper .table-bordered tbody .custom-tbl-content-box,
    .custom-table-card-body #currencyTable_wrapper .table-bordered tbody .custom-tbl-content-box,
    .custom-table-card-body #supplierTable_wrapper .table-bordered tbody .custom-tbl-content-box,
    .custom-table-card-body #categoryTable_wrapper .table-bordered tbody .custom-tbl-content-box,
    .custom-table-card-body #packagingTable_wrapper .table-bordered tbody .custom-tbl-content-box,
    .custom-table-card-body #customerTable_wrapper .table-bordered tbody .custom-tbl-content-box,
    .custom-table-card-body #productTable_wrapper .table-bordered tbody .custom-tbl-content-box,
    .custom-table-card-body #driverTable_wrapper .table-bordered tbody .custom-tbl-content-box,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tbody .custom-tbl-content-box,
    .custom-table-card-body #gradeTable_wrapper .table-bordered tbody .custom-tbl-content-box,
    .custom-table-card-body #locationTable_wrapper .table-bordered tbody .custom-tbl-content-box,
    .custom-table-card-body #memberTable_wrapper .table-bordered tbody .custom-tbl-content-box,
    .custom-table-card-body #pvTable_wrapper .table-bordered tbody .custom-tbl-content-box,
    .custom-table-card-body #transferTable_wrapper .table-bordered tbody .custom-tbl-content-box {
      padding-left: 15px;
      padding-right: 15px;
    }

    .custom-table-card-body #weightTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-6,
    .custom-table-card-body #weightTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-12,
    .custom-table-card-body #translationTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-6,
    .custom-table-card-body #translationTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-12,
    .custom-table-card-body #stateTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-6,
    .custom-table-card-body #stateTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-12,
    .custom-table-card-body #currencyTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-6,
    .custom-table-card-body #currencyTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-12,
    .custom-table-card-body #supplierTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-6,
    .custom-table-card-body #supplierTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-12,
    .custom-table-card-body #categoryTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-6,
    .custom-table-card-body #categoryTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-12,
    .custom-table-card-body #packagingTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-6,
    .custom-table-card-body #packagingTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-12,
    .custom-table-card-body #customerTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-6,
    .custom-table-card-body #customerTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-12,
    .custom-table-card-body #productTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-6,
    .custom-table-card-body #productTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-12,
    .custom-table-card-body #driverTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-6,
    .custom-table-card-body #driverTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-12,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-6,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-12,
    .custom-table-card-body #gradeTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-6,
    .custom-table-card-body #gradeTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-12,
    .custom-table-card-body #locationTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-6,
    .custom-table-card-body #locationTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-12,
    .custom-table-card-body #memberTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-6,
    .custom-table-card-body #memberTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-12,
    .custom-table-card-body #pvTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-6,
    .custom-table-card-body #pvTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-12,
    .custom-table-card-body #transferTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-6,
    .custom-table-card-body #transferTable_wrapper .table-bordered tbody .custom-tbl-content-box .col-12 {
      padding-left: 0px;
      padding-right: 0px;
    }

    .custom-table-card-body #weightTable_wrapper .table-bordered tbody .custom-tbl-content-box .custom-tbl-content-box-txt,
    .custom-table-card-body #translationTable_wrapper .table-bordered tbody .custom-tbl-content-box .custom-tbl-content-box-txt,
    .custom-table-card-body #stateTable_wrapper .table-bordered tbody .custom-tbl-content-box .custom-tbl-content-box-txt,
    .custom-table-card-body #currencyTable_wrapper .table-bordered tbody .custom-tbl-content-box .custom-tbl-content-box-txt,
    .custom-table-card-body #supplierTable_wrapper .table-bordered tbody .custom-tbl-content-box .custom-tbl-content-box-txt,
    .custom-table-card-body #categoryTable_wrapper .table-bordered tbody .custom-tbl-content-box .custom-tbl-content-box-txt,
    .custom-table-card-body #packagingTable_wrapper .table-bordered tbody .custom-tbl-content-box .custom-tbl-content-box-txt,
    .custom-table-card-body #customerTable_wrapper .table-bordered tbody .custom-tbl-content-box .custom-tbl-content-box-txt,
    .custom-table-card-body #productTable_wrapper .table-bordered tbody .custom-tbl-content-box .custom-tbl-content-box-txt,
    .custom-table-card-body #driverTable_wrapper .table-bordered tbody .custom-tbl-content-box .custom-tbl-content-box-txt,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tbody .custom-tbl-content-box .custom-tbl-content-box-txt,
    .custom-table-card-body #gradeTable_wrapper .table-bordered tbody .custom-tbl-content-box .custom-tbl-content-box-txt,
    .custom-table-card-body #locationTable_wrapper .table-bordered tbody .custom-tbl-content-box .custom-tbl-content-box-txt,
    .custom-table-card-body #memberTable_wrapper .table-bordered tbody .custom-tbl-content-box .custom-tbl-content-box-txt,
    .custom-table-card-body #pvTable_wrapper .table-bordered tbody .custom-tbl-content-box .custom-tbl-content-box-txt,
    .custom-table-card-body #transferTable_wrapper .table-bordered tbody .custom-tbl-content-box .custom-tbl-content-box-txt {
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 400;
      color: #1a1a1a;
      margin-bottom: 15px;
    }

    .custom-table-card-body #weightTable_wrapper .table-bordered tbody .custom-tbl-hr,
    .custom-model-body-box .custom-model-body-hr, .custom-model-body-box .custom-inner-hr,
    .custom-table-card-body #translationTable_wrapper .table-bordered tbody .custom-tbl-hr,
    .custom-table-card-body #stateTable_wrapper .table-bordered tbody .custom-tbl-hr,
    .custom-table-card-body #currencyTable_wrapper .table-bordered tbody .custom-tbl-hr,
    .custom-table-card-body #supplierTable_wrapper .table-bordered tbody .custom-tbl-hr,
    .custom-table-card-body #categoryTable_wrapper .table-bordered tbody .custom-tbl-hr,
    .custom-table-card-body #packagingTable_wrapper .table-bordered tbody .custom-tbl-hr,
    .custom-table-card-body #customerTable_wrapper .table-bordered tbody .custom-tbl-hr,
    .custom-model-body-box .custom-model-inner-line,
    .custom-table-card-body #productTable_wrapper .table-bordered tbody .custom-tbl-hr,
    .custom-table-card-body #driverTable_wrapper .table-bordered tbody .custom-tbl-hr,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tbody .custom-tbl-hr,
    .custom-table-card-body #gradeTable_wrapper .table-bordered tbody .custom-tbl-hr,
    .custom-table-card-body #locationTable_wrapper .table-bordered tbody .custom-tbl-hr,
    .custom-table-card-body #memberTable_wrapper .table-bordered tbody .custom-tbl-hr,
    .custom-form-card-body .custom-form-card-hr,
    .custom-table-card-body #pvTable_wrapper .table-bordered tbody .custom-tbl-hr,
    .custom-table-card-body #transferTable_wrapper .table-bordered tbody .custom-tbl-hr {
      margin-top: 10px;
      margin-bottom: 25px;
      border-top: 1px solid #E3C66A;
    }

    .custom-table-card-body #weightTable_wrapper .table-bordered tbody .custom-tbl-title,
    .custom-table-card-body #translationTable_wrapper .table-bordered tbody .custom-tbl-title,
    .custom-table-card-body #stateTable_wrapper .table-bordered tbody .custom-tbl-title,
    .custom-table-card-body #currencyTable_wrapper .table-bordered tbody .custom-tbl-title,
    .custom-table-card-body #supplierTable_wrapper .table-bordered tbody .custom-tbl-title,
    .custom-table-card-body #categoryTable_wrapper .table-bordered tbody .custom-tbl-title,
    .custom-table-card-body #packagingTable_wrapper .table-bordered tbody .custom-tbl-title,
    .custom-table-card-body #customerTable_wrapper .table-bordered tbody .custom-tbl-title,
    .custom-table-card-body #productTable_wrapper .table-bordered tbody .custom-tbl-title,
    .custom-table-card-body #driverTable_wrapper .table-bordered tbody .custom-tbl-title,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tbody .custom-tbl-title,
    .custom-table-card-body #gradeTable_wrapper .table-bordered tbody .custom-tbl-title,
    .custom-table-card-body #locationTable_wrapper .table-bordered tbody .custom-tbl-title,
    .custom-table-card-body #memberTable_wrapper .table-bordered tbody .custom-tbl-title,
    .custom-table-card-body #pvTable_wrapper .table-bordered tbody .custom-tbl-title,
    .custom-table-card-body #transferTable_wrapper .table-bordered tbody .custom-tbl-title {
      margin-bottom: 25px;
      padding-left: 10px;
      padding-right: 10px;
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 700;
      text-decoration: underline;
      color: #1a1a1a;
    }

    .custom-table-card-body #weightTable_wrapper .table-bordered tbody .custom-tbl-fliter-box,
    .custom-table-card-body #translationTable_wrapper .table-bordered tbody .custom-tbl-fliter-box,
    .custom-table-card-body #stateTable_wrapper .table-bordered tbody .custom-tbl-fliter-box,
    .custom-table-card-body #currencyTable_wrapper .table-bordered tbody .custom-tbl-fliter-box,
    .custom-table-card-body #supplierTable_wrapper .table-bordered tbody .custom-tbl-fliter-box,
    .custom-table-card-body #categoryTable_wrapper .table-bordered tbody .custom-tbl-fliter-box,
    .custom-table-card-body #packagingTable_wrapper .table-bordered tbody .custom-tbl-fliter-box,
    .custom-table-card-body #customerTable_wrapper .table-bordered tbody .custom-tbl-fliter-box,
    .custom-table-card-body #productTable_wrapper .table-bordered tbody .custom-tbl-fliter-box,
    .custom-table-card-body #driverTable_wrapper .table-bordered tbody .custom-tbl-fliter-box,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tbody .custom-tbl-fliter-box,
    .custom-table-card-body #gradeTable_wrapper .table-bordered tbody .custom-tbl-fliter-box,
    .custom-table-card-body #locationTable_wrapper .table-bordered tbody .custom-tbl-fliter-box,
    .custom-table-card-body #memberTable_wrapper .table-bordered tbody .custom-tbl-fliter-box,
    .custom-table-card-body #pvTable_wrapper .table-bordered tbody .custom-tbl-fliter-box,
    .custom-table-card-body #transferTable_wrapper .table-bordered tbody .custom-tbl-fliter-box {
      padding-left: 7.5px;
      padding-right: 7.5px;
    }

    .custom-table-card-body #weightTable_wrapper .table-bordered tbody .custom-inner-tbl-box .table-bordered,
    .custom-table-card-body #translationTable_wrapper .table-bordered tbody .custom-inner-tbl-box .table-bordered,
    .custom-table-card-body #stateTable_wrapper .table-bordered tbody .custom-inner-tbl-box .table-bordered,
    .custom-table-card-body #currencyTable_wrapper .table-bordered tbody .custom-inner-tbl-box .table-bordered,
    .custom-table-card-body #supplierTable_wrapper .table-bordered tbody .custom-inner-tbl-box .table-bordered,
    .custom-table-card-body #categoryTable_wrapper .table-bordered tbody .custom-inner-tbl-box .table-bordered,
    .custom-table-card-body #packagingTable_wrapper .table-bordered tbody .custom-inner-tbl-box .table-bordered,
    .custom-table-card-body #customerTable_wrapper .table-bordered tbody .custom-inner-tbl-box .table-bordered,
    .custom-table-card-body #productTable_wrapper .table-bordered tbody .custom-inner-tbl-box .table-bordered,
    .custom-table-card-body #driverTable_wrapper .table-bordered tbody .custom-inner-tbl-box .table-bordered,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tbody .custom-inner-tbl-box .table-bordered,
    .custom-table-card-body #gradeTable_wrapper .table-bordered tbody .custom-inner-tbl-box .table-bordered,
    .custom-table-card-body #locationTable_wrapper .table-bordered tbody .custom-inner-tbl-box .table-bordered,
    .custom-table-card-body #memberTable_wrapper .table-bordered tbody .custom-inner-tbl-box .table-bordered,
    .custom-table-card-body #pvTable_wrapper .table-bordered tbody .custom-inner-tbl-box .table-bordered,
    .custom-table-card-body #transferTable_wrapper .table-bordered tbody .custom-inner-tbl-box .table-bordered {
      margin-top: 0px;
      margin-bottom: 15px;
      margin-left: 15px;
      margin-right: 15px;
    }

    .custom-table-card-body #weightTable_wrapper .table-bordered tfoot tr:nth-of-type(odd),
    .custom-table-card-body #weightTable_wrapper .table-bordered tfoot tr:nth-of-type(even),
    .custom-model-body-box .custom-add-table-detail tfoot tr:nth-of-type(odd),
    .custom-model-body-box .custom-add-table-detail tfoot tr:nth-of-type(even),
    .custom-table-card-body #translationTable_wrapper .table-bordered tfoot tr:nth-of-type(odd),
    .custom-table-card-body #translationTable_wrapper .table-bordered tfoot tr:nth-of-type(even),
    .custom-table-card-body #stateTable_wrapper .table-bordered tfoot tr:nth-of-type(odd),
    .custom-table-card-body #stateTable_wrapper .table-bordered tfoot tr:nth-of-type(even),
    .custom-table-card-body #currencyTable_wrapper .table-bordered tfoot tr:nth-of-type(odd),
    .custom-table-card-body #currencyTable_wrapper .table-bordered tfoot tr:nth-of-type(even),
    .custom-table-card-body #supplierTable_wrapper .table-bordered tfoot tr:nth-of-type(odd),
    .custom-table-card-body #supplierTable_wrapper .table-bordered tfoot tr:nth-of-type(even),
    .custom-table-card-body #categoryTable_wrapper .table-bordered tfoot tr:nth-of-type(odd),
    .custom-table-card-body #categoryTable_wrapper .table-bordered tfoot tr:nth-of-type(even),
    .custom-table-card-body #packagingTable_wrapper .table-bordered tfoot tr:nth-of-type(odd),
    .custom-table-card-body #packagingTable_wrapper .table-bordered tfoot tr:nth-of-type(even),
    .custom-table-card-body #customerTable_wrapper .table-bordered tfoot tr:nth-of-type(odd),
    .custom-table-card-body #customerTable_wrapper .table-bordered tfoot tr:nth-of-type(even),
    .custom-table-card-body #productTable_wrapper .table-bordered tfoot tr:nth-of-type(odd),
    .custom-table-card-body #productTable_wrapper .table-bordered tfoot tr:nth-of-type(even),
    .custom-table-card-body #driverTable_wrapper .table-bordered tfoot tr:nth-of-type(odd),
    .custom-table-card-body #driverTable_wrapper .table-bordered tfoot tr:nth-of-type(even),
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tfoot tr:nth-of-type(odd),
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tfoot tr:nth-of-type(even),
    .custom-table-card-body #gradeTable_wrapper .table-bordered tfoot tr:nth-of-type(odd),
    .custom-table-card-body #gradeTable_wrapper .table-bordered tfoot tr:nth-of-type(even),
    .custom-table-card-body #locationTable_wrapper .table-bordered tfoot tr:nth-of-type(odd),
    .custom-table-card-body #locationTable_wrapper .table-bordered tfoot tr:nth-of-type(even),
    .custom-table-card-body #memberTable_wrapper .table-bordered tfoot tr:nth-of-type(odd),
    .custom-table-card-body #memberTable_wrapper .table-bordered tfoot tr:nth-of-type(even),
    .custom-table-card-body #pvTable_wrapper .table-bordered tfoot tr:nth-of-type(odd),
    .custom-table-card-body #pvTable_wrapper .table-bordered tfoot tr:nth-of-type(even),
    .custom-table-card-body #transferTable_wrapper .table-bordered tfoot tr:nth-of-type(odd),
    .custom-table-card-body #transferTable_wrapper .table-bordered tfoot tr:nth-of-type(even) {
      background: rgba(227, 198, 106, .25);
      border: 1px solid #D9A82D;
    }

    .custom-table-card-body #weightTable_wrapper .table-bordered tfoot tr th,
    .custom-model-body-box .custom-add-table-detail tfoot tr th,
    .custom-table-card-body #translationTable_wrapper .table-bordered tfoot tr th,
    .custom-table-card-body #stateTable_wrapper .table-bordered tfoot tr th,
    .custom-table-card-body #currencyTable_wrapper .table-bordered tfoot tr th,
    .custom-table-card-body #supplierTable_wrapper .table-bordered tfoot tr th,
    .custom-table-card-body #categoryTable_wrapper .table-bordered tfoot tr th,
    .custom-table-card-body #packagingTable_wrapper .table-bordered tfoot tr th,
    .custom-table-card-body #customerTable_wrapper .table-bordered tfoot tr th,
    .custom-table-card-body #productTable_wrapper .table-bordered tfoot tr th,
    .custom-table-card-body #driverTable_wrapper .table-bordered tfoot tr th,
    .custom-table-card-body #vehicleTable_wrapper .table-bordered tfoot tr th,
    .custom-table-card-body #gradeTable_wrapper .table-bordered tfoot tr th,
    .custom-table-card-body #locationTable_wrapper .table-bordered tfoot tr th,
    .custom-table-card-body #memberTable_wrapper .table-bordered tfoot tr th,
    .custom-table-card-body #pvTable_wrapper .table-bordered tfoot tr th,
    .custom-table-card-body #transferTable_wrapper .table-bordered tfoot tr th {
      border: 1px solid #D9A82D;
      padding: 10px;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 700;
      color: #1a1a1a;
    }

    .custom-model-content-box {
      box-shadow: 0px 0px 10px 0px rgba(227, 198, 106, 1);
      border-radius: 5px;
      border: unset;
      background: #F9F7F2;
    }

    .custom-model-header-box, .custom-model-fotter-box, .custom-form-card-footer {
      background: linear-gradient(135deg, rgba(246, 213, 74, 1) 0%, rgba(255, 243, 199, 1) 50%, rgba(217, 168, 45, 1) 100%);
      padding: 25px;
      align-items: center;
    }

    .custom-model-header-box .custom-model-title-txt {
      font-size: 25px;
      line-height: 30px;
      letter-spacing: 0.75px;
      font-weight: 700;
      color: #1a1a1a;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .custom-btn-close-icon {
      margin: 0px !important;
      padding: 0px !important;
      color: #DC2626;
      text-shadow: unset;
      opacity: 1;
      font-size: 25px;
      line-height: 30px;
      letter-spacing: 0.75px;
      font-weight: 700;
    }

    .custom-btn-close-icon:hover {
      color: #B91C1C;
    }

    .custom-model-body-box {
      padding: 25px;
      background: #F9F7F2;
    }

    .custom-model-fotter-box {
      justify-content: space-between;
    }

    .custom-close-btn {
      margin: 0px;
      padding: 10px 25px;
      border: unset;
      border-radius: 5px;
      background: #DC2626;
      color: #fff;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .custom-close-btn:hover, .custom-delete-btn-sm:hover, .custom-delete-btn-icon:hover, .custom-delete-btn:hover,
    .custom-remove-btn-sm:hover {
      background: #B91C1C;
      color: #fff;
    }

    .custom-save-btn {
      margin: 0px;
      padding: 10px 25px;
      border: unset;
      border-radius: 5px;
      background: #16A34A;
      color: #fff;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .custom-model-title-box .custom-model-title-box-txt {
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 700;
      color: #1a1a1a;
      margin-bottom: 15px;
    }

    .custom-model-title-box .custom-model-title-box-filter {
      margin-bottom: 15px;
      gap: 15px;
    }

    .custom-model-title-box .custom-model-title-box-lbl {
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 700 !important;
      margin-bottom: 0px;
    }

    .custom-add-btn-sm {
      padding: 5px 15px;
      border: unset;
      border-radius: 5px;
      background: #16A34A;
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 5px;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 700;
      height: 40px;
    }

    .custom-view-btn-sm {
      padding: 5px 15px;
      border: unset;
      border-radius: 5px;
      background: #06B6D4;
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 5px;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 700;
      height: 40px;
    }

    .custom-delete-btn-sm {
      padding: 5px 15px;
      border: unset;
      border-radius: 5px;
      background: #DC2626;
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 5px;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 700;
      height: 40px;
      margin-bottom: 15px;
    }

    .custom-filter-btn-sm {
      padding: 5px 15px;
      border: unset;
      border-radius: 5px;
      background: #EA580C;
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 5px;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 700;
      height: 40px;
    }

    .custom-table-card-body #weightTable_wrapper .custom-tbl-btn-icon,
    .custom-table-card-body #translationTable_wrapper .custom-tbl-btn-icon,
    .custom-table-card-body #stateTable_wrapper .custom-tbl-btn-icon,
    .custom-table-card-body #currencyTable_wrapper .custom-tbl-btn-icon,
    .custom-table-card-body #supplierTable_wrapper .custom-tbl-btn-icon,
    .custom-table-card-body #categoryTable_wrapper .custom-tbl-btn-icon,
    .custom-table-card-body #packagingTable_wrapper .custom-tbl-btn-icon,
    .custom-table-card-body #customerTable_wrapper .custom-tbl-btn-icon,
    .custom-table-card-body #productTable_wrapper .custom-tbl-btn-icon,
    .custom-table-card-body #driverTable_wrapper .custom-tbl-btn-icon,
    .custom-table-card-body #vehicleTable_wrapper .custom-tbl-btn-icon,
    .custom-table-card-body #gradeTable_wrapper .custom-tbl-btn-icon,
    .custom-table-card-body #locationTable_wrapper .custom-tbl-btn-icon,
    .custom-table-card-body #memberTable_wrapper .custom-tbl-btn-icon,
    .custom-table-card-body #pvTable_wrapper .custom-tbl-btn-icon,
    .custom-table-card-body #transferTable_wrapper .custom-tbl-btn-icon {
      flex-direction: row !important;
      gap: 5px;
      justify-content: flex-start;
      align-items: center;
      margin-left: 0px;
      margin-right: 0px;
    }

    .custom-edit-btn-icon {
      background: #0F766E;
      color: #fff;
      padding: 5px 10px;
      border: unset;
      border-radius: 5px;
      font-size: 12px;
      line-height: 20px;
    }

    .custom-edit-btn-icon:hover {
      background: #115E59;
      color: #fff;
    }

    .custom-print-btn-icon {
      background: #7C3AED;
      color: #fff;
      padding: 5px 10px;
      border: unset;
      border-radius: 5px;
      font-size: 12px;
      line-height: 20px;
    }

    .custom-print-btn-icon:hover, .custom-export-btn:hover {
      background: #6D28D9;
      color: #fff;
    }

    .custom-delete-btn-icon {
      background: #DC2626;
      color: #fff;
      padding: 5px 10px;
      border: unset;
      border-radius: 5px;
      font-size: 12px;
      line-height: 20px;
    }

    .custom-receipt-btn-icon, .custom-view-btn-icon {
      background: #06B6D4;
      color: #fff;
      padding: 5px 10px;
      border: unset;
      border-radius: 5px;
      font-size: 12px;
      line-height: 20px;
    }

    .custom-receipt-btn-icon:hover, .custom-view-btn-icon:hover, .custom-preview-btn:hover, .custom-view-btn-sm:hover {
      background: #0891B2;
      color: #fff;
    }

    .custom-export-btn {
      padding: 10px 25px;
      border: unset;
      border-radius: 5px;
      background: #7C3AED;
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 5px;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 700;
      margin-top: 0px !important;
    }

    .custom-reject-btn-icon {
      background: #EA580C;
      color: #fff;
      padding: 5px 10px;
      border: unset;
      border-radius: 5px;
      font-size: 12px;
      line-height: 20px;
    }

    .custom-check-btn-icon {
      background: #16A34A;
      color: #fff;
      padding: 5px 10px;
      border: unset;
      border-radius: 5px;
      font-size: 12px;
      line-height: 20px;
    }

    .custom-delete-btn {
      padding: 10px 25px;
      border: unset;
      border-radius: 5px;
      background: #DC2626;
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 5px;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 700;
      margin-top: 0px !important;
    }

    .custom-upload-btn {
      padding: 10px 25px;
      border: unset;
      border-radius: 5px;
      background: #2563EB;
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 5px;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 700;
      margin-top: 0px !important;
    }

    .custom-upload-btn:hover, .custom-upload-logo-btn:hover {
      background: #1D4ED8;
      color: #fff;
    }

    .custom-preview-btn {
      padding: 10px 25px;
      border: unset;
      border-radius: 5px;
      background: #06B6D4;
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 5px;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 700;
      margin-top: 0px !important;
    }

    .custom-preview-model-box {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 15px;
    }

    .custom-preview-field {
      color: #1a1a1a;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 400;
    }

    .custom-model-body-box .custom-model-inner-title, .custom-form-card-body .custom-form-card-title {
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 700;
      text-decoration: underline;
      color: #D9A82D;
    }

    .custom-model-body-box .custom-card-box {
      box-shadow: 0px 0px 5px 0px rgba(0, 0, 0, .5);
      border-top: 5px solid #1E3A8A;
      border-radius: 1px;
      background: #fff;
      margin-bottom: 25px;
    }

    .custom-model-body-box .custom-card-product-info-box, .custom-table-content .custom-profile-box .custom-profile-header-box {
      border-top: 5px solid #1E3A8A;
    }

    .custom-model-body-box .custom-card-product-image-box {
      border-top: 5px solid #7C3AED;
    }

    .custom-model-body-box .custom-card-range-box {
      border-top: 5px solid #0F766E;
    }

    .custom-model-body-box .custom-card-grade-box {
      border-top: 5px solid #B45309;
    }

    .custom-model-body-box .custom-card-box-header, .custom-table-content .custom-profile-box .custom-profile-header-box {
      padding: 10px 25px;
      border-bottom: 1px solid #E3C66A;
    }

    .custom-model-body-box .custom-card-range-box .custom-card-box-header,
    .custom-model-body-box .custom-card-grade-box .custom-card-box-header {
      display: flex;
    }

    .custom-model-body-box .custom-card-box-header-title, .custom-table-content .custom-profile-box .custom-profile-title {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 18px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 700;
      color: #1a1a1a;
      margin-bottom: 0px;
    }

    .custom-card-box .custom-card-box-body {
      padding: 25px;
    }

    .custom-card-box .custom-card-box-body .product-img-drop-zone {
      border: 2.5px dashed #E3C66A;
      border-radius: 5px;
      padding: 25px;
      text-align: center;
      cursor: pointer;
      background: #fff;
      color: #1a1a1a;
    }

    .custom-card-box .custom-card-box-body .product-img-drop-zone i,
    .custom-card-box .custom-card-box-body .product-img-placeholder i {
      font-size: 50px;
      margin-bottom: 15px;
    }

    .custom-card-box .custom-card-box-body .product-img-drop-zone .product-img-drop-zone-txt-1 {
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 400;
      margin-bottom: 5px;
    }

    .custom-card-box .custom-card-box-body .product-img-drop-zone .product-img-drop-zone-txt-2,
    .custom-card-box .custom-card-box-body .product-img-placeholder .product-img-placeholder-txt {
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 700;
      margin-bottom: 0px;
    }

    .custom-card-box .custom-card-box-body .product-image-preview .product-image-thumbnail {
      max-height: 200px;
      max-width: 100%;
      border-radius: 5px;
      object-fit: contain;
    }

    .custom-card-box .custom-card-box-body .product-image-btn-box {
      margin-top: 15px;
      display: flex;
      justify-content: center;
    }

    .custom-remove-btn-sm {
      padding: 5px 15px;
      border: unset;
      border-radius: 5px;
      background: #DC2626;
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 5px;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 700;
    }

    .custom-card-box .custom-card-box-body .custom-range-set-box {
      margin-bottom: 15px;
    }

    .custom-card-box .custom-card-box-body .custom-range-set-box .custom-range-set-label {
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 700;
      color: #1a1a1a;
      margin-bottom: 0px;
    }

    .custom-card-box .custom-card-box-body .custom-range-set-box .form-control.custom-range-set-weight-1 {
      background: rgba(40, 167, 69, 0.25);
      color: #155724;
      border: 1px solid #28a745;
    }

    .custom-card-box .custom-card-box-body .custom-range-set-box .form-control.custom-range-set-weight-2 {
      background: rgba(255, 193, 7, 0.25);
      color: #856404;
      border: 1px solid #ffc107;
    }

    .custom-card-box .custom-card-box-body .custom-range-set-box .form-control.custom-range-set-weight-3 {
      background: rgba(220, 53, 69, 0.2);
      color: #721c24;
      border: 1px solid #dc3545;
    }

    .custom-card-box .custom-card-box-body .table-bordered,
    .custom-model-body-box .tab-content .table-bordered {
      margin-top: 0px;
      margin-bottom: 0px;
      border: 1px solid #D9A82D;
      background: #fff;
      color: #1a1a1a;
    }

    .custom-card-box .custom-card-box-header-title .badge {
      background: #1a1a1a;
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 12px;
      line-height: 20px;
      letter-spacing: 0.75px;
      font-weight: 700;
      padding: 5px;
      border-radius: 5px;
      width: 20px;
      height: 20px;
    }

    .custom-model-body-box .custom-tab-btn-box {
      margin-bottom: 25px;
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 15px;
    }

    .custom-model-body-box .custom-tab-nav {
      margin-bottom: 25px;
      border-bottom: unset;
    }

    .custom-model-body-box .custom-tab-nav .nav-item {
      margin-right: 15px;
    }

    .custom-model-body-box .custom-tab-nav .nav-item:last-child {
      margin-right: 0px;
    }

    .custom-model-body-box .custom-tab-nav .nav-item .nav-link {
      margin-bottom: 0px;
      border: 1px solid #1a1a1a;
      border-radius: 5px;
      padding: 10px 25px;
      background: #fff;
      color: #1a1a1a;
      font-size: 18px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 700;
    }

    .custom-model-body-box .custom-tab-nav .nav-item .nav-link:hover,
    .custom-model-body-box .custom-tab-nav .nav-item .nav-link.active {
      background: #1a1a1a;
      color: #fff;
    }

    .custom-table-content .custom-profile-box .custom-profile-header-box {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .custom-table-content .custom-profile-box .custom-profile-title {
      width: 50%;
    }

    .custom-table-content .custom-profile-box .custom-profile-btn {
      display: flex;
      justify-content: flex-end;
      width: 50%;
    }

    .custom-table-content .custom-profile-box .custom-profile-footer-box {
      background: linear-gradient(135deg, rgba(246, 213, 74, 1) 0%, rgba(255, 243, 199, 1) 50%, rgba(217, 168, 45, 1) 100%);
      padding: 10px 25px;
      align-items: center;
      border-bottom-left-radius: 5px;
      border-bottom-right-radius: 5px;
    }

    .custom-table-content .custom-profile-box .custom-profile-body-box .custom-file-label {
      height: 40px;
      background: #fff;
      color: #1a1a1a;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 400;
      border: 1px solid #E3C66A;
      border-top-left-radius: 5px;
      border-bottom-left-radius: 5px;
      padding: 10px;
      margin-bottom: 0px;
    }

    .custom-table-content .custom-profile-box .custom-profile-body-box .custom-file-label:after {
      height: 40px;
      background: #E3C66A;
      color: #1a1a1a;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 400;
      padding: 10px;
      border-radius: 0px;
    }

    .custom-upload-logo-btn {
      height: 40px;
      background: #2563EB;
      color: #fff;
      font-size: 14px;
      line-height: 22px;
      letter-spacing: 0.75px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .custom-form-card-footer {
      border-bottom-left-radius: 5px;
      border-bottom-right-radius: 5px;
    }

    .custom-table-card-body #weightTable_wrapper .row:last-child, 
    .custom-table-card-body #translationTable_wrapper .row:last-child,
    .custom-table-card-body #stateTable_wrapper .row:last-child,
    .custom-table-card-body #currencyTable_wrapper .row:last-child,
    .custom-table-card-body #supplierTable_wrapper .row:last-child,
    .custom-table-card-body #categoryTable_wrapper .row:last-child,
    .custom-table-card-body #packagingTable_wrapper .row:last-child,
    .custom-table-card-body #customerTable_wrapper .row:last-child,
    .custom-table-card-body #productTable_wrapper .row:last-child,
    .custom-table-card-body #driverTable_wrapper .row:last-child,
    .custom-table-card-body #vehicleTable_wrapper .row:last-child,
    .custom-table-card-body #gradeTable_wrapper .row:last-child,
    .custom-table-card-body #locationTable_wrapper .row:last-child,
    .custom-table-card-body #memberTable_wrapper .row:last-child,
    .custom-table-card-body #pvTable_wrapper .row:last-child,
    .custom-table-card-body #transferTable_wrapper .row:last-child {
      flex-direction: column;
    }

    .custom-table-card-body #weightTable_wrapper .row:last-child .col-sm-12, 
    .custom-table-card-body #translationTable_wrapper .row:last-child .col-sm-12,
    .custom-table-card-body #stateTable_wrapper .row:last-child .col-sm-12,
    .custom-table-card-body #currencyTable_wrapper .row:last-child .col-sm-12,
    .custom-table-card-body #supplierTable_wrapper .row:last-child .col-sm-12,
    .custom-table-card-body #categoryTable_wrapper .row:last-child .col-sm-12,
    .custom-table-card-body #packagingTable_wrapper .row:last-child .col-sm-12,
    .custom-table-card-body #customerTable_wrapper .row:last-child .col-sm-12,
    .custom-table-card-body #productTable_wrapper .row:last-child .col-sm-12,
    .custom-table-card-body #driverTable_wrapper .row:last-child .col-sm-12,
    .custom-table-card-body #vehicleTable_wrapper .row:last-child .col-sm-12,
    .custom-table-card-body #gradeTable_wrapper .row:last-child .col-sm-12,
    .custom-table-card-body #locationTable_wrapper .row:last-child .col-sm-12,
    .custom-table-card-body #memberTable_wrapper .row:last-child .col-sm-12,
    .custom-table-card-body #pvTable_wrapper .row:last-child .col-sm-12,
    .custom-table-card-body #transferTable_wrapper .row:last-child .col-sm-12 {
      max-width: unset;
    }

    .custom-table-card-body #weightTable_wrapper .row:last-child .dataTables_info,
    .custom-table-card-body #translationTable_wrapper .row:last-child .dataTables_info,
    .custom-table-card-body #stateTable_wrapper .row:last-child .dataTables_info,
    .custom-table-card-body #currencyTable_wrapper .row:last-child .dataTables_info,
    .custom-table-card-body #supplierTable_wrapper .row:last-child .dataTables_info,
    .custom-table-card-body #categoryTable_wrapper .row:last-child .dataTables_info,
    .custom-table-card-body #packagingTable_wrapper .row:last-child .dataTables_info,
    .custom-table-card-body #customerTable_wrapper .row:last-child .dataTables_info,
    .custom-table-card-body #productTable_wrapper .row:last-child .dataTables_info,
    .custom-table-card-body #driverTable_wrapper .row:last-child .dataTables_info,
    .custom-table-card-body #vehicleTable_wrapper .row:last-child .dataTables_info,
    .custom-table-card-body #gradeTable_wrapper .row:last-child .dataTables_info,
    .custom-table-card-body #locationTable_wrapper .row:last-child .dataTables_info,
    .custom-table-card-body #memberTable_wrapper .row:last-child .dataTables_info,
    .custom-table-card-body #pvTable_wrapper .row:last-child .dataTables_info,
    .custom-table-card-body #transferTable_wrapper .row:last-child .dataTables_info {
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 400;
      color: #1a1a1a;
      margin-bottom: 15px;
    }

    .custom-table-card-body #weightTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled a,
    .custom-table-card-body #translationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled a,
    .custom-table-card-body #stateTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled a,
    .custom-table-card-body #currencyTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled a,
    .custom-table-card-body #supplierTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled a,
    .custom-table-card-body #categoryTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled a,
    .custom-table-card-body #packagingTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled a,
    .custom-table-card-body #customerTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled a,
    .custom-table-card-body #productTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled a,
    .custom-table-card-body #driverTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled a,
    .custom-table-card-body #vehicleTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled a,
    .custom-table-card-body #gradeTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled a,
    .custom-table-card-body #locationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled a,
    .custom-table-card-body #memberTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled a,
    .custom-table-card-body #pvTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled a,
    .custom-table-card-body #transferTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled a {
      background: #fff;
      color: rgba(26, 26, 26, .5);
      border: 1px solid rgba(26, 26, 26, .5);
    }

    .custom-table-card-body #weightTable_wrapper .row:last-child .dataTables_paginate .pagination .previous.disabled a,
    .custom-table-card-body #translationTable_wrapper .row:last-child .dataTables_paginate .pagination .previous.disabled a,
    .custom-table-card-body #stateTable_wrapper .row:last-child .dataTables_paginate .pagination .previous.disabled a,
    .custom-table-card-body #currencyTable_wrapper .row:last-child .dataTables_paginate .pagination .previous.disabled a,
    .custom-table-card-body #supplierTable_wrapper .row:last-child .dataTables_paginate .pagination .previous.disabled a,
    .custom-table-card-body #categoryTable_wrapper .row:last-child .dataTables_paginate .pagination .previous.disabled a,
    .custom-table-card-body #packagingTable_wrapper .row:last-child .dataTables_paginate .pagination .previous.disabled a,
    .custom-table-card-body #customerTable_wrapper .row:last-child .dataTables_paginate .pagination .previous.disabled a,
    .custom-table-card-body #productTable_wrapper .row:last-child .dataTables_paginate .pagination .previous.disabled a,
    .custom-table-card-body #driverTable_wrapper .row:last-child .dataTables_paginate .pagination .previous.disabled a,
    .custom-table-card-body #vehicleTable_wrapper .row:last-child .dataTables_paginate .pagination .previous.disabled a,
    .custom-table-card-body #gradeTable_wrapper .row:last-child .dataTables_paginate .pagination .previous.disabled a,
    .custom-table-card-body #locationTable_wrapper .row:last-child .dataTables_paginate .pagination .previous.disabled a,
    .custom-table-card-body #memberTable_wrapper .row:last-child .dataTables_paginate .pagination .previous.disabled a,
    .custom-table-card-body #pvTable_wrapper .row:last-child .dataTables_paginate .pagination .previous.disabled a,
    .custom-table-card-body #transferTable_wrapper .row:last-child .dataTables_paginate .pagination .previous.disabled a {
      border-top-left-radius: 5px;
      border-bottom-left-radius: 5px;
    }

    .custom-table-card-body #weightTable_wrapper .row:last-child .dataTables_paginate .pagination .next.disabled a,
    .custom-table-card-body #translationTable_wrapper .row:last-child .dataTables_paginate .pagination .next.disabled a,
    .custom-table-card-body #stateTable_wrapper .row:last-child .dataTables_paginate .pagination .next.disabled a,
    .custom-table-card-body #currencyTable_wrapper .row:last-child .dataTables_paginate .pagination .next.disabled a,
    .custom-table-card-body #supplierTable_wrapper .row:last-child .dataTables_paginate .pagination .next.disabled a,
    .custom-table-card-body #categoryTable_wrapper .row:last-child .dataTables_paginate .pagination .next.disabled a,
    .custom-table-card-body #packagingTable_wrapper .row:last-child .dataTables_paginate .pagination .next.disabled a,
    .custom-table-card-body #customerTable_wrapper .row:last-child .dataTables_paginate .pagination .next.disabled a,
    .custom-table-card-body #productTable_wrapper .row:last-child .dataTables_paginate .pagination .next.disabled a,
    .custom-table-card-body #driverTable_wrapper .row:last-child .dataTables_paginate .pagination .next.disabled a,
    .custom-table-card-body #vehicleTable_wrapper .row:last-child .dataTables_paginate .pagination .next.disabled a,
    .custom-table-card-body #gradeTable_wrapper .row:last-child .dataTables_paginate .pagination .next.disabled a,
    .custom-table-card-body #locationTable_wrapper .row:last-child .dataTables_paginate .pagination .next.disabled a,
    .custom-table-card-body #memberTable_wrapper .row:last-child .dataTables_paginate .pagination .next.disabled a,
    .custom-table-card-body #pvTable_wrapper .row:last-child .dataTables_paginate .pagination .next.disabled a,
    .custom-table-card-body #transferTable_wrapper .row:last-child .dataTables_paginate .pagination .next.disabled a {
      border-top-right-radius: 5px;
      border-bottom-right-radius: 5px;
    }

    .custom-table-card-body #weightTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a,
    .custom-table-card-body #translationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a,
    .custom-table-card-body #stateTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a,
    .custom-table-card-body #currencyTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a,
    .custom-table-card-body #supplierTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a,
    .custom-table-card-body #categoryTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a,
    .custom-table-card-body #packagingTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a,
    .custom-table-card-body #customerTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a,
    .custom-table-card-body #productTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a,
    .custom-table-card-body #driverTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a,
    .custom-table-card-body #vehicleTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a,
    .custom-table-card-body #gradeTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a,
    .custom-table-card-body #locationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a,
    .custom-table-card-body #memberTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a,
    .custom-table-card-body #pvTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a,
    .custom-table-card-body #transferTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a {
      background: #fff;
      color: #1a1a1a;
      border: 1px solid rgba(26, 26, 26, .5);
      border-radius: 1px;
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 400;
      padding: 10px 15px;
      margin-left: -1px;
    }

    .custom-table-card-body #weightTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:focus,
    .custom-table-card-body #translationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:focus,
    .custom-table-card-body #stateTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:focus,
    .custom-table-card-body #currencyTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:focus,
    .custom-table-card-body #supplierTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:focus,
    .custom-table-card-body #categoryTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:focus,
    .custom-table-card-body #packagingTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:focus,
    .custom-table-card-body #customerTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:focus,
    .custom-table-card-body #productTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:focus,
    .custom-table-card-body #driverTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:focus,
    .custom-table-card-body #vehicleTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:focus,
    .custom-table-card-body #gradeTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:focus,
    .custom-table-card-body #locationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:focus,
    .custom-table-card-body #memberTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:focus,
    .custom-table-card-body #pvTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:focus,
    .custom-table-card-body #transferTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:focus {
      box-shadow: unset;
    }

    .custom-table-card-body #weightTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:hover,
    .custom-table-card-body #weightTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active a,
    .custom-table-card-body #translationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:hover,
    .custom-table-card-body #translationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active a,
    .custom-table-card-body #stateTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:hover,
    .custom-table-card-body #stateTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active a,
    .custom-table-card-body #currencyTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:hover,
    .custom-table-card-body #currencyTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active a,
    .custom-table-card-body #supplierTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:hover,
    .custom-table-card-body #supplierTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active a,
    .custom-table-card-body #categoryTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:hover,
    .custom-table-card-body #categoryTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active a,
    .custom-table-card-body #packagingTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:hover,
    .custom-table-card-body #packagingTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active a,
    .custom-table-card-body #customerTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:hover,
    .custom-table-card-body #customerTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active a,
    .custom-table-card-body #productTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:hover,
    .custom-table-card-body #productTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active a,
    .custom-table-card-body #driverTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:hover,
    .custom-table-card-body #driverTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active a,
    .custom-table-card-body #vehicleTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:hover,
    .custom-table-card-body #vehicleTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active a,
    .custom-table-card-body #gradeTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:hover,
    .custom-table-card-body #gradeTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active a,
    .custom-table-card-body #locationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:hover,
    .custom-table-card-body #locationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active a,
    .custom-table-card-body #memberTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:hover,
    .custom-table-card-body #memberTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active a,
    .custom-table-card-body #pvTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:hover,
    .custom-table-card-body #pvTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active a,
    .custom-table-card-body #transferTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item a:hover,
    .custom-table-card-body #transferTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active a {
      background: #1a1a1a;
      color: #fff;
    }
  </style>
</head>
<!--
BODY TAG OPTIONS:
=================
Apply one or more of the following classes to to the body tag
to get the desired effect
|---------------------------------------------------------|
|LAYOUT OPTIONS | sidebar-collapse                        |
|               | sidebar-mini                            |
|---------------------------------------------------------|
-->
<body class="hold-transition sidebar-mini">
<div class="loading" id="spinnerLoading">
  <div class='uil-ring-css' style='transform:scale(0.79);'>
    <div></div>
  </div>
</div>

<div class="wrapper">
  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-primary navbar-light custom-navbar-style">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link custom-bar-menu" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars bg-success"></i></a>      </li>
    </ul>
    
    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <li class="nav-item dropdown">
        <a class="nav-link custom-user-menu" data-toggle="dropdown" href="#" role="button">
          <i class="fas fa-user"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-right user-drop-down-menu">
          <h6 class="dropdown-header"><?=$languageArray['welcome_code'][$language]?> <?=$username ?>!</h6>
          <a href="#myprofile" data-file="myprofile.php" class="dropdown-item link">
            <i class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i> 
            <span class="align-middle"><?=$languageArray['profile_code'][$language]?></span>
          </a>
          <a class="dropdown-item" href="php/logout.php">
            <i class="mdi mdi-logout text-muted fs-16 align-middle me-1"></i> 
            <span class="align-middle"><?=$languageArray['logout_code'][$language]?></span>
          </a>
        </div>
      </li>
    </ul>
  </nav>

  <!-- Main Sidebar Container -->
  <!--aside class="main-sidebar sidebar-dark-primary elevation-4"  style="background-color: #ffffff;"-->
  <aside class="main-sidebar sidebar-dark-primary elevation-4 custom-sidebar-style">    <!-- Brand Logo -->
    <a href="#" class="brand-link logo-switch custom-logo-box">
      <img src="assets/fy-fruit-trading-logo-icon.png" alt="Sneakercube Logo" class="brand-image-xl logo-xs custom-logo-icon">
      <img src="assets/fy-fruit-trading-logo.png" alt="Sneakercube Logo" class="brand-image-xl logo-xl custom-logo-main">
    </a>

    <!-- Sidebar -->
    <div class="sidebar custom-sidebar-acc-menu">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel custom-user-panel">
          <div class="image user-panel-img" style="align-self: center;">
            <img src="assets/user-avatar.png" class="img-circle elevation-2" alt="User Image">
          </div>
          <div class="info user-panel-info" style="white-space: nowrap;">
            <p class="user-panel-txt"><?=$languageArray['welcome_code'][$language]?></p>
            <a href="#myprofile" data-file="myprofile.php" id="goToProfile" class="d-block user-panel-name"><?=$name ?></a>
          </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="custom-sidebar-menu">
        <ul class="nav nav-pills nav-sidebar flex-column" id="sideMenu" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Add icons to the links using the .nav-icon class
            with font-awesome or any other icon font library -->
          <!--li class="nav-item">
            <a href="#dashboard" data-file="dashboard.php" class="nav-link link">
              <i class="nav-icon fas fa-user"></i>
              <p>Dashboard</p>
            </a>
          </li-->
          <li class="nav-item">
            <a href="home.php" class="nav-link link">
              <i class="nav-icon fas fa-home"></i>
              <p><?=$languageArray['home_code'][$language]?></p>
            </a>
          </li>
          <?php if ($module == 'dashboard') { ?>
          <li class="nav-item">
            <a href="#dashboard" data-file="dashboard.php" class="nav-link link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p><?=$languageArray['dashboard_code'][$language]?></p>
            </a>
          </li>
          <?php } ?>
          <?php if ($module != 'dashboard' && $module == 'pricing') { ?>
          <li class="nav-item has-treeview menu-open">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p><?=$languageArray['pricing_code'][$language]?><i class="fas fa-angle-left right"></i></p>
            </a>
            <ul class="nav nav-treeview" style="display: block;">
              <li class="nav-item">
                <a href="#pricingSales" data-file="pricingSales.php" class="nav-link link">
                  <i class="nav-icon fas fa-cubes"></i>
                  <p><?=$languageArray['sales_code'][$language]?></p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#reportsPricingSales" data-file="reportsPricingSales.php" class="nav-link link">
                  <i class="nav-icon fas fa-chart-bar"></i>
                  <p><?=$languageArray['sales_report_code'][$language]?></p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#pricingPurchase" data-file="pricingPurchase.php" class="nav-link link">
                  <i class="nav-icon fas fa-truck"></i>
                  <p><?=$languageArray['purchase_code'][$language]?></p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#pricingInventory" data-file="pricingInventory.php" class="nav-link link">
                  <i class="nav-icon fas fa-warehouse"></i>
                  <p><?=$languageArray['inventory_code'][$language]?></p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#repacking" data-file="repacking.php" class="nav-link link">
                  <i class="nav-icon fas fa-box-open"></i>
                  <p><?=$languageArray['repacking_code'][$language]?></p>
                </a>
              </li>
            </ul>
          </li>
          <?php } ?>
          <?php if ($module != 'dashboard' && $module == 'processing') { ?>
          <li class="nav-item has-treeview menu-open">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-cog"></i>
              <p><?=$languageArray['processing_code'][$language]?><i class="fas fa-angle-left right"></i></p>
            </a>
            <ul class="nav nav-treeview" style="display: block;">
              <li class="nav-item">
                <a href="#wholesales" data-file="modules/wholesales/wholesales.php" class="nav-link link">
                  <i class="nav-icon fas fa-cubes"></i>
                  <p><?=$languageArray['wholesales_code'][$language]?></p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#bulkPriceUpdate" data-file="modules/wholesales/bulkPriceUpdate.php" class="nav-link link">
                  <i class="nav-icon fas fa-tags"></i>
                  <p><?=$languageArray['bulk_price_update_code'][$language] ?? 'Bulk Price Update'?></p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#grading" data-file="grading.php" class="nav-link link">
                  <i class="nav-icon fas fa-clipboard-check"></i>
                  <p><?=$languageArray['grading_code'][$language]?></p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#packagingBatches" data-file="packagingBatches.php" class="nav-link link">
                  <i class="nav-icon fas fa-box-open"></i>
                  <p><?=$languageArray['batch_packaging_code'][$language]?></p>
                </a>
              </li>
            </ul>
          </li>
          <?php } ?>
          <?php if ($module != 'dashboard' && $module != 'pricing' && $module != 'processing' && $module != 'accounting' && $module != 'stocks') { ?>
          <li class="nav-item has-treeview menu-open">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-weight"></i>
              <p><?=$languageArray['weighing_code'][$language]?><i class="fas fa-angle-left right"></i></p>
            </a>
            <ul class="nav nav-treeview" style="display: block;">
              <!-- <li class="nav-item">
                <a href="#weighing" data-file="weightPage.php" class="nav-link link">
                  <i class="nav-icon fas fa-balance-scale"></i>
                  <p>Weight Weighing</p>
                </a>
              </li> -->
              <?php if ($module == 'wholesale') { ?>
              <li class="nav-item">
                <a href="#wholesales" data-file="modules/wholesales/wholesales.php" class="nav-link link">
                  <i class="nav-icon fas fa-cubes"></i>
                  <p><?=$languageArray['wholesales_code'][$language]?></p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#bulkPriceUpdate" data-file="modules/wholesales/bulkPriceUpdate.php" class="nav-link link">
                  <i class="nav-icon fas fa-tags"></i>
                  <p><?=$languageArray['bulk_price_update_code'][$language] ?? 'Bulk Price Update'?></p>
                </a>
              </li>
              <?php } ?>
              <?php if ($module == 'weighing') { ?>
              <li class="nav-item">
                <a href="#weighbridges" data-file="modules/wb/weighbridges.php" class="nav-link link">
                  <i class="nav-icon fas fa-cubes"></i>
                  <p><?=$languageArray['weighbridge_code'][$language]?></p>
                </a>
              </li>
              <?php } ?>
              <?php if ($module == 'industrial') { ?>
              <li class="nav-item">
                <a href="#industrial" data-file="modules/industrial/industrial.php" class="nav-link link">
                  <i class="nav-icon fas fa-cubes"></i>
                  <p><?=$languageArray['pulp_and_paste_code'][$language]?></p>
                </a>
              </li>
              <?php } ?>
              <?php if ($module == 'packing') { ?>
              <li class="nav-item">
                <a href="#packing" data-file="packing.php" class="nav-link link">
                  <i class="nav-icon fas fa-cubes"></i>
                  <p><?=$languageArray['packing_code'][$language]?></p>
                </a>
              </li>
              <?php } ?>
              <?php if ($module == 'pricing') { ?>
              <li class="nav-item">
                <a href="#pricing" data-file="pricing.php" class="nav-link link">
                  <i class="nav-icon fas fa-cubes"></i>
                  <p><?=$languageArray['pricing_code'][$language]?></p>
                </a>
              </li>
              <?php } ?>
              <!-- <li class="nav-item">
                <a href="#counting" data-file="countPage.php" class="nav-link link">
                  <i class="nav-icon fas fa-cubes"></i>
                  <p>Weighing Records</p>
                </a>
              </li> -->
              <!-- <li class="nav-item">
                <a href="#batching" data-file="batchPage.php" class="nav-link link">
                  <i class="nav-icon fas fa-file-alt"></i>
                  <p>Batch Weighing</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#pricing" data-file="pricePage.php" class="nav-link link">
                  <i class="nav-icon fas fa-dollar-sign"></i>
                  <p>Price Weighing</p>
                </a>
              </li> -->
            </ul>
          </li>
          <?php } ?>
          <?php if ($module != 'dashboard' && $module == 'wholesale') { ?>
          <li class="nav-item has-treeview menu-open">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-th"></i>
              <p><?=$languageArray['reports_code'][$language]?><i class="fas fa-angle-left right"></i></p>
            </a>
            <ul class="nav nav-treeview" style="display: block;">
              <li class="nav-item">
                <a href="#reports" data-file="modules/wholesales/reports.php" class="nav-link link">
                  <i class="nav-icon fas fa-chart-bar"></i>
                  <p><?=$languageArray['weighing_code'][$language]?></p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#stockBalanceReport" data-file="modules/wholesales/stockBalanceReport.php" class="nav-link link">
                  <i class="nav-icon fas fa-balance-scale"></i>
                  <p><?=$languageArray['stock_balance_code'][$language]?></p>
                </a>
              </li>
            </ul>
          </li>
          <?php } ?>
          <?php if ($module != 'dashboard' && $module == 'weighing') { ?>
          <li class="nav-item">
            <a href="#reportsWb" data-file="modules/wb/reports.php" class="nav-link link">
              <i class="nav-icon fas fa-th"></i>
              <p><?=$languageArray['reports_code'][$language]?></p>
            </a>
          </li>
          <?php } ?>
          <?php if ($module != 'dashboard' && $module == 'industrial') { ?>
          <li class="nav-item">
            <a href="#reportsIndustry" data-file="modules/industrial/reports.php" class="nav-link link">
              <i class="nav-icon fas fa-th"></i>
              <p><?=$languageArray['reports_code'][$language]?></p>
            </a>
          </li>
          <?php } ?>
          <?php if ($module != 'dashboard' && $module == 'packing') { ?>
          <li class="nav-item">
            <a href="#reportsPacking" data-file="reportsPacking.php" class="nav-link link">
              <i class="nav-icon fas fa-cubes"></i>
              <p><?=$languageArray['reports_code'][$language]?></p>
            </a>
          </li>
          <?php } ?>
          <?php if ($module != 'dashboard' && $module == 'stocks') { ?>
          <li class="nav-item has-treeview menu-open">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-chart-bar"></i>
              <p><?=$languageArray['stock_management'][$language]?><i class="fas fa-angle-left right"></i></p>
            </a>
            <ul class="nav nav-treeview" style="display: none;">
              <li class="nav-item">
                <a href="#stockDashboard" data-file="stockDashboard.php" class="nav-link link">
                  <i class="nav-icon fas fa-tachometer-alt"></i>
                  <p><?=$languageArray['dashboard_code'][$language]?></p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#stockTransfer" data-file="stockTransfer.php" class="nav-link link">
                  <i class="nav-icon fas fa-exchange-alt"></i>
                  <p><?=$languageArray['stock_transfer_code'][$language]?></p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#loadingOrders" data-file="loadingOrders.php" class="nav-link link">
                  <i class="nav-icon fas fa-truck-loading"></i>
                  <p><?=$languageArray['loading_orders_code'][$language]?></p>
                </a>
              </li>
            </ul>
          </li>
          <?php } ?>
          <?php if ($module != 'dashboard' && $module == 'accounting') { ?>
          <li class="nav-item has-treeview menu-open">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-calculator"></i>
              <p><?=$languageArray['accounting_code'][$language]?><i class="fas fa-angle-left right"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#paymentVoucher" data-file="paymentVoucher.php" class="nav-link link">
                  <i class="nav-icon fas fa-file-invoice-dollar"></i>
                  <p><?=$languageArray['payment_voucher_code'][$language]?></p>
                </a>
              </li>
            </ul>
          </li>
          <?php } ?>
          <?php 
              if($module != 'dashboard' && ($role == "ADMIN" || $role == "SADMIN" || $role == "MANAGER")){
                echo '<li class="nav-item has-treeview">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-database"></i>
                  <p>'.$languageArray['master_data_code'][$language].'<i class="fas fa-angle-left right"></i></p>
                </a>
                <ul class="nav nav-treeview" style="display: none;">
                  <li class="nav-item">
                    <a href="#translations" data-file="translations.php" class="nav-link link">
                      <i class="nav-icon fas fa-language"></i>
                      <p>'.$languageArray['translations_code'][$language].'</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#states" data-file="states.php" class="nav-link link">
                      <i class="nav-icon fas fa-map-marker-alt"></i>
                      <p>'.$languageArray['states_code'][$language].'</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#currencies" data-file="currencies.php" class="nav-link link">
                      <i class="nav-icon fas fa-dollar-sign"></i>
                      <p>'.$languageArray['currency_code'][$language].'</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#units" data-file="units.php" class="nav-link link">
                      <i class="nav-icon fas fa-balance-scale"></i>
                      <p>'.$languageArray['units_code'][$language].'</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#categories" data-file="categories.php" class="nav-link link">
                      <i class="nav-icon fas fa-tags"></i>
                      <p>'.$languageArray['category_code'][$language].'</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#packaging" data-file="packaging.php" class="nav-link link">
                      <i class="nav-icon fas fa-box"></i>
                      <p>'.$languageArray['packaging_code'][$language].'</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#customer" data-file="customers.php" class="nav-link link">
                      <i class="nav-icon fas fa-users"></i>
                      <p>'.$languageArray['customer_code'][$language].'</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#supplier" data-file="suppliers.php" class="nav-link link">
                      <i class="nav-icon fas fa-file-alt"></i>
                      <p>'.$languageArray['supplier_code'][$language].'</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#products" data-file="products.php" class="nav-link link">
                      <i class="nav-icon fas fa-shopping-cart"></i>
                      <p>'.$languageArray['products_code'][$language].'</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#drivers" data-file="drivers.php" class="nav-link link">
                      <i class="nav-icon fas fa-id-card"></i>
                      <p>'.$languageArray['drivers_code'][$language].'</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#vehicles" data-file="vehicles.php" class="nav-link link">
                      <i class="nav-icon fas fa-truck"></i>
                      <p>'.$languageArray['vehicles_code'][$language].'</p>
                    </a>
                  </li>
                  <!--li class="nav-item">
                    <a href="#transporters" data-file="transporters.php" class="nav-link link">
                      <i class="nav-icon fas fa-shipping-fast"></i>
                      <p>'.$languageArray['transporters_code'][$language].'</p>
                    </a>
                  </li-->
                  <li class="nav-item">
                    <a href="#grades" data-file="grades.php" class="nav-link link">
                      <i class="nav-icon fas fa-star"></i>
                      <p>'.$languageArray['grades_code'][$language].'</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#locations" data-file="locations.php" class="nav-link link">
                      <i class="nav-icon fas fa-map-marker-alt"></i>
                      <p>'.$languageArray['locations_code'][$language].'</p>
                    </a>
                  </li>
                  ';
                if ($module == 'processing') {
                  echo '
                  <li class="nav-item">
                    <a href="#shipmentTypes" data-file="shipmentTypes.php" class="nav-link link">
                      <i class="nav-icon fas fa-shipping-fast"></i>
                      <p>'.$languageArray['shipment_types_code'][$language].'</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#productionLines" data-file="productionLines.php" class="nav-link link">
                      <i class="nav-icon fas fa-industry"></i>
                      <p>'.$languageArray['production_lines_code'][$language].'</p>
                    </a>
                  </li>';
                }

                if (in_array('basket', $_SESSION['products'])){
                  echo '
                  <li class="nav-item">
                    <a href="#binType" data-file="binType.php" class="nav-link link">
                      <i class="nav-icon fas fa-dumpster"></i>
                      <p>'.$languageArray['bin_types_code'][$language].'</p>
                    </a>
                  </li>';
                }
                echo '
                </ul>
              </li>';
              }
          ?>
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-cogs"></i>
              <p><?=$languageArray['settings_code'][$language]?><i class="fas fa-angle-left right"></i></p>
            </a>
        
            <ul class="nav nav-treeview" style="display: none;">
              <?php 
                if($role == "ADMIN" || $role == "SADMIN"){
                  echo '<li class="nav-item">
                          <a href="#company" data-file="company.php" class="nav-link link">
                            <i class="nav-icon fas fa-building"></i>
                            <p>'.$languageArray['company_profile_code'][$language].'</p>
                          </a>
                        </li>
                        <li class="nav-item">
                          <a href="#users" data-file="users.php" class="nav-link link">
                            <i class="nav-icon fas fa-user"></i>
                            <p>'.$languageArray['staffs_code'][$language].'</p>
                          </a>
                        </li>';

                  if ($enableDailySales == 'Y'){
                    echo '
                        <li class="nav-item">
                          <a href="#dailySalesSetup" data-file="dailySalesSetup.php" class="nav-link link">
                            <i class="nav-icon fas fa-calendar-check"></i>
                            <p>'.$languageArray['daily_sales_setup_code'][$language].'</p>
                          </a>
                        </li>';
                  }
                }
              ?>

              <li class="nav-item">
                <a href="#setup" data-file="setup.php" class="nav-link link">
                  <i class="nav-icon fas fa-tachometer-alt"></i>
                  <p><?=$languageArray['indicator_setup_code'][$language]?></p>
                </a>
              </li>

              <li class="nav-item">
                <a href="#myprofile" data-file="myprofile.php" class="nav-link link">
                  <i class="nav-icon fas fa-id-badge"></i>
                  <p><?=$languageArray['profile_code'][$language]?></p>
                </a>
              </li>
          
              <li class="nav-item">
                <a href="#changepassword" data-file="changePassword.php" class="nav-link link">
                  <i class="nav-icon fas fa-key"></i>
                  <p><?=$languageArray['change_password_code'][$language]?></p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="php/logout.php" class="nav-link">
              <i class="nav-icon fas fa-sign-out-alt"></i>
              <p><?=$languageArray['logout_code'][$language]?></p>
            </a>
          </li>
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper" id="mainContents">
    
  </div>
  <!-- /.content-wrapper -->

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->

  <!-- Main Footer -->
  <footer class="main-footer custom-main-footer">
    Copyright &copy; 2024 <a href="#">SyncWeight</a>. All Rights Reserved.<div class="float-right d-none d-sm-inline-block">Version
  </footer>
</div>
<!-- ./wrapper -->
<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/jquery-validation/jquery.validate.min.js"></script>
<!-- Bootstrap -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE -->
<script src="dist/js/adminlte.js"></script>
<!-- OPTIONAL SCRIPTS -->
<script src="plugins/select2/js/select2.full.min.js"></script>
<script src="plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js"></script>
<script src="plugins/moment/moment.min.js"></script>
<script src="plugins/inputmask/jquery.inputmask.min.js"></script>
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="plugins/daterangepicker/daterangepicker.js"></script>
<script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<script src="plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<script src="plugins/chart.js/Chart.min.js"></script>
<script src="plugins/daterangepicker/daterangepicker.js"></script>
<script src="plugins/sheets/xlsx.full.min.js"></script>

<script>
// Define the conversion factors
const conversionFactors = {
  kg: { kg: 1, g: 1000, oz: 35.27396, lbs: 2.20462 },
  g: { g: 1, kg: 0.001, oz: 0.03527396, lbs: 0.00220462 },
  oz: { oz: 1, kg: 0.0283495, g: 28.3495, lbs: 0.0625 },
  lbs: { lbs: 1, kg: 0.453592, g: 453.592, oz: 16 },
};

$(function () {
  toastr.options = {
    "closeButton": false,
    "debug": false,
    "newestOnTop": false,
    "progressBar": false,
    "positionClass": "toast-top-right",
    "preventDuplicates": false,
    "onclick": null,
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "5000",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
  }
  
  $('#sideMenu').on('click', '.link', function(){
      $('#spinnerLoading').show();
      var files = $(this).attr('data-file');
      $('#sideMenu').find('.active').removeClass('active');
      $(this).addClass('active');
      
      $.get(files, function(data) {
        $('#mainContents').html(data);
        $('#spinnerLoading').hide();
      });
  });

  // Handle dropdown links
  $('.dropdown-menu').on('click', '.link', function(){
      $('#spinnerLoading').show();
      var files = $(this).attr('data-file');
      $('#sideMenu').find('.active').removeClass('active');
      
      $.get(files, function(data) {
        $('#mainContents').html(data);
        $('#spinnerLoading').hide();
      });
  });

  $('#goToProfile').on('click', function(){
      $('#spinnerLoading').show();
      var files = $(this).attr('data-file');
      $('#sideMenu').find('.active').removeClass('active');
      $(this).addClass('active');
      
      $.get(files, function(data) {
          $('#mainContents').html(data);
          $('#spinnerLoading').hide();
      });
  });
  
  if(window.location.hash) {
    $("a[href='" + window.location.hash + "']").click();
  } else {
    <?php if ($module == 'wholesale') { ?>
    $("a[href='#wholesales']").click();
    <?php } else if ($module == 'weighing') { ?>
    $("a[href='#weighbridges']").click();
    <?php } else if ($module == 'industrial') { ?>
    $("a[href='#industrial']").click();
    <?php } else if ($module == 'packing') { ?>
    $("a[href='#packing']").click();
    <?php } else if ($module == 'pricing') { ?>
    $("a[href='#pricingSales']").click();
    <?php } else if ($module == 'processing') { ?>
    $("a[href='#wholesales']").click();
    <?php } else if ($module == 'accounting') { ?>
    $("a[href='#paymentVoucher']").click();
    <?php } else if ($module == 'stocks') { ?>
    $("a[href='#stockDashboard']").click();
    <?php } else if ($module == 'dashboard') { ?>
    $("a[href='#dashboard']").click();
    <?php } else { ?>
    window.location.href = 'home.php';
    <?php } ?>
  }
});

// Function to convert between units
function convertUnits(value, fromUnit, toUnit) {
  var convertedValue = value * (conversionFactors[fromUnit][toUnit] || 1);
  return convertedValue;
}

</script>
</body>
</html>
