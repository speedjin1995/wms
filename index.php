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

  <link rel="icon" href="assets/mun-meng-site-logo.png" type="image">
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
  
  <style>
    body {
      background: #eee;
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

    /* New Style Code */
    
    .brand-link {
      line-height: unset;
      border-bottom-color: #fff !important;
      padding: 15px 5px;
    }

    .brand-link .brand-image-xl {
      line-height: unset;
    }

    .brand-link .brand-image-xl.logo-xs {
      position: relative;
      top: 0px;
      left: 0px;
    }

    .brand-link .brand-image-xl.logo-xl {
      position: relative;
      top: 0px;
      left: -50px;
      max-height: unset;
      width: 100px;
      height: 100%;
    }
    
    .sidebar {
      padding-left: 0px;
      padding-right: 0px;
    }

    .sidebar .user-panel {
      border-bottom: 1px solid #fff;
      padding: 15px 5px;
      display: flex;
      align-items: center;
    }

    .sidebar .user-panel .image {
      padding-left: 10px;
      padding-right: 15px;
    }

    .sidebar .user-panel .image img {
      width: 40px;
    }

    .sidebar .user-panel .info, .modal-content .custom-model-extend-form .modal-body .card-body {
      padding: 0px;
    }

    .sidebar .user-panel .info p {
      font-size: 13px;
      line-height: 22px;
      letter-spacing: 0.75px;
      color: #fff;
      margin-bottom: 0px;
    }

    .sidebar .user-panel .info a {
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 700;
      color: #fff;
    }

    .sidebar-menu-custom {
      margin-top: 0px;
      padding: 15px 5px 0px;
    }

    .sidebar-menu-custom ul li:not(:last-child) a {
      margin-bottom: 5px !important;
    }

    .sidebar-menu-custom ul li a {
      padding: 10px 15px;
      color: #fff !important;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 400;
      display: flex;
      align-items: center;
    }

    .sidebar-menu-custom ul li:focus a {
      background: transparent !important;
    }

    .sidebar-menu-custom ul li:hover a, .sidebar-menu-custom ul li.menu-open a, 
    .sidebar-menu-custom ul li.menu-open ul li:hover a {
      background: #243958 !important;
    }

    .sidebar-menu-custom ul li a.active, .sidebar-menu-custom ul li.menu-open ul li a.active {
      background: #fff !important;
      color: #2f333e !important;
    }

    .sidebar-menu-custom ul li.menu-open ul li a, .sidebar-menu-custom ul li ul li a {
      background: transparent !important;
      color: #fff !important;
    }

    .sidebar-menu-custom ul li a .nav-icon {
      margin-top: -3px;
      margin-left: 0px !important;
      margin-right: 7px !important;
      width: 25px !important;
      font-size: 17px !important;
      line-height: 23px;
    }

    .sidebar-menu-custom ul li a p {
      display: flex !important;
      align-items: center;
      width: 100%;
      justify-content: space-between;
    }

    .sidebar-menu-custom ul li a p i {
      position: relative !important;
      top: 0px !important;
      right: 0px !important;
    }

    .main-header {
      border-bottom: unset;
      box-shadow: 0px 2.5px 5px 0px rgba(255, 255, 255, .5);
      padding: 10px 15px;
    }

    .main-header .custom-navbar-nav .nav-item .nav-link {
      color: #243958;
      padding: 0px;
      display: flex;
      align-items: center;
      height: 100%;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 400;
    }

    .content-wrapper {
      background: url('assets/dashboard-background.jpg');
      background-repeat: no-repeat;
      background-size: cover;
      background-position: center;
      padding: 25px;
    }

    .content-header {
      padding: 0px;
      border: unset;
    }

    .content-header h1 {
      font-size: 35px;
      line-height: 40px;
      letter-spacing: 0.75px;
      font-weight: 700;
      color: #fff;
      margin-bottom: 25px;
    }

    .content-header h1 i {
      color: #3fb84e;
      margin-right: 15px;
    }

    .content .card {
      box-shadow: 0px 0px 5px 2.5px rgba(255, 255, 255, .5);
      margin-bottom: 25px;
      border: unset;
      border-radius: 5px;
    }

    .content .card .card-body {
      padding: 25px;
    }

    .form-group {
      margin-bottom: 20px !important;
    }

    .form-group label {
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      color: #243958;
      margin-bottom: 10px;
    }

    .form-group label small {
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 700;
      color: #243958;
    }

    .custom-upload-file-grp small {
      font-size: 12px;
      line-height: 20px;
      letter-spacing: 0.75px;
      font-weight: 400;
      color: #243958;
    }

    .form-control {
      padding: 5px 10px !important;
      border: 1px solid #243958 !important;
      border-radius: 5px;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 400;
      color: #2f333e;
      box-shadow: unset;
    }

    .custom-upload-file-form .custom-file-label {
      padding: 5px 10px !important;
      border: 1px solid #243958 !important;
      border-radius: 5px;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 400;
      color: #2f333e;
      box-shadow: unset;
      margin-bottom: 0px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .custom-upload-file-form .custom-file-label:after {
      position: relative;
      right: -10px;
      height: calc(2.25rem + 2px);
      padding: 5px 10px;
      line-height: 23px;
      color: #2f333e;
      background: #FFC000;
      border-left: unset;
      border-radius: 1px;
      display: flex;
      align-items: center;
    }

    .form-control:disabled, .form-control[readonly] {
      background: #dee2e6;
    }

    .form-control:focus {
      color: #2f333e;
      border-color: #3fb84e !important;
      box-shadow: unset;
    }

    .input-group-append {
      margin-left: 0px;
    }

    .input-group-text {
      padding: 5px 10px;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      background: #243958;
      color: #fff;
      border: 1px solid #243958;
      border-radius: unset;
      border-top-right-radius: 5px;
      border-bottom-right-radius: 5px;
    }

    .bootstrap-datetimepicker-widget.dropdown-menu {
      width: auto;
    }

    .bootstrap-datetimepicker-widget table td.active, .bootstrap-datetimepicker-widget table td.active:hover,
    .select2-container--default .select2-results__option--highlighted[aria-selected], 
    .select2-container--default .select2-results__option--highlighted[aria-selected]:hover {
      background: #243958;
    }

    .select2-container--default .select2-selection--single {
      padding: 5px 10px;
      border: 1px solid #243958 !important;
      border-radius: 5px;
      box-shadow: unset;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 400;
      color: #2f333e;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .select2-container--default .select2-selection--single:focus,
    .select2-container--default .select2-dropdown .select2-search__field:focus, 
    .select2-container--default .select2-search--inline .select2-search__field:focus {
      border-color: #3fb84e !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
      margin-top: 0px;
      color: #2f333e;
      line-height: 24px;
      padding-right: 0px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
    }

    .select2-container--default .select2-selection--single .select2-selection__clear {
      order: 2;
      padding-right: 10px;
    }

    .select2-container--default .select2-selection--single .select2-selection__placeholder {
      color: #2f333e;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
      position: relative;
      top: -2px;
      right: 0;
      width: 10px;
      height: 100%;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow b {
      border-color: #2f333e transparent transparent transparent;
    }

    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
      border-color: transparent transparent #2f333e transparent;
    }

    .select2-search--dropdown {
      padding: 5px;
    }

    .select2-search--dropdown .select2-search__field {
      padding: 5px 10px;
      border: 1px solid #243958 !important;
      border-radius: 5px;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 400;
      color: #2f333e;
    }

    .select2-container--default .select2-results__option {
      padding: 5px 10px;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 400;
      color: #2f333e;
    }

    .select2-container--default .select2-selection--multiple {
      border: 1px solid #243958 !important;
      border-radius: 5px;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 400;
      color: #2f333e;
      box-shadow: unset;
      height: calc(2.25rem + 2px);
      display: flex;
      align-items: center;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__rendered {
      margin-bottom: 0px;
      padding: 5px 10px;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__rendered li:first-child.select2-search.select2-search--inline {
      margin-left: 0px;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__rendered .select2-search.select2-search--inline .select2-search__field {
      margin-top: 0px;
    }

    .custom-search-btn, .custom-preview-btn {
      background: #FFC000 !important;
      color: #2f333e !important;
      padding: 5px 15px;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 700;
      border: unset !important;
      border-radius: 5px;
      box-shadow: unset !important;
    }

    .custom-reject-icon-btn {
      background: #FFC000 !important;
      color: #fff !important;
      padding: 10px;
      font-size: 12px;
      line-height: normal;
      letter-spacing: 0.75px;
      font-weight: 700;
      border: unset !important;
      border-radius: 5px;
      box-shadow: unset !important;
    }

    .custom-search-btn:hover, .custom-preview-btn:hover, .custom-upload-file-form .custom-file-label:hover:after, 
    .custom-reject-icon-btn:hover {
      background: #FFDE21 !important;
      color: #2f333e !important;
    }

    .card-header, .modal-content .custom-model-extend-form .modal-header, .custom-modal-content .modal-header {
      background: #324C75 !important;
      border: unset;
      padding: 20px 25px;
      border-top-left-radius: 5px;
      border-top-right-radius: 5px;
    }

    .card-header.categories-card-header .col-3 {
      max-width: 20%;
    }

    .modal-content .custom-model-extend-form .modal-footer, #profileForm .card-outline .card-footer, 
    .custom-content-form .card-footer, .custom-modal-content .modal-footer, .custom-repacking-form .card-footer {
      background: #324C75 !important;
      border: unset;
      padding: 20px 25px;
      border-bottom-left-radius: 5px;
      border-bottom-right-radius: 5px;
      justify-content: space-between;
    }

    .custom-card-header-row {
      align-items: center;
    }

    .custom-card-header-title, .modal-content .custom-model-extend-form .modal-header .modal-title, 
    .custom-modal-content .modal-header .modal-title {
      font-size: 25px;
      line-height: 30px;
      letter-spacing: 0.75px;
      font-weight: 700;
      color: #fff;
    }

    .custom-card-title {
      font-size: 25px;
      line-height: 30px;
      letter-spacing: 0.75px;
      font-weight: 700;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .modal-content .custom-model-extend-form .modal-header .modal-title {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .custom-add-btn, .modal-content .custom-model-extend-form .modal-body #addWeightBtn,
    .modal-content .custom-model-extend-form .modal-footer .custom-save-btn {
      background: #10B981 !important;
      color: #fff !important;
      padding: 5px 15px;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 700;
      border: unset !important;
      border-radius: 5px;
      box-shadow: unset !important;
      margin: 0px;
    }

    .custom-upload-btn {
      background: #10B981 !important;
      color: #fff !important;
      padding: 5px 15px;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 700;
      border: unset !important;
      border-top-right-radius: 5px;
      border-bottom-right-radius: 5px;
      box-shadow: unset !important;
    }

    .custom-pencil-icon-btn {
      background: #10B981 !important;
      color: #fff !important;
      padding: 10px;
      font-size: 12px;
      line-height: normal;
      letter-spacing: 0.75px;
      font-weight: 700;
      border: unset !important;
      border-radius: 5px;
      box-shadow: unset !important;
    }

    .custom-add-btn:hover, .modal-content .custom-model-extend-form .modal-body #addWeightBtn:hover,
    .modal-content .custom-model-extend-form .modal-footer .custom-save-btn:hover, .custom-pencil-icon-btn:hover,
    .custom-upload-btn:hover {
      background: #059669 !important;
      color: #fff !important;
    }

    .dataTables_length {
      margin-bottom: 25px;
    }

    .dataTables_length label, .dataTables_filter label {
      margin-bottom: 0px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .dataTables_length label .form-control {
      width: 75px;
    }

    .dataTables_filter label {
      justify-content: flex-end;
    }

    .dataTables_filter label .form-control {
      width: 250px;
    }

    .table, .table-bordered {
      border: 1px solid #3fb84e;
      color: #2f333e !important;
      margin-bottom: 25px;
    }

    thead, .modal-content .custom-model-extend-form .modal-body .card-outline .card-body .table-bordered thead,
    .modal-content .custom-model-extend-form .modal-body .card-outline .card-body .table-bordered thead th,
    .table .thead-light th {
      background-color: #3fb84e;
      color: #fff;
    }

    thead th, .modal-content .custom-model-extend-form .modal-body .card-outline .card-body .table-bordered thead th,
    .table .thead-light th {
      border-top: 1px solid #dee2e6 !important;
      border-bottom: 1px solid #dee2e6 !important;
      border-left: 1px solid #fff !important;
      border-right: 1px solid #fff !important;
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 700;
      text-align: center;
      vertical-align: middle !important;
      padding: 10px !important;
    }

    tfoot th {
      border-top: 1px solid #dee2e6 !important;
      border-bottom: 1px solid #dee2e6 !important;
      border-left: 1px solid #fff !important;
      border-right: 1px solid #fff !important;
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 700;
      vertical-align: middle !important;
      padding: 10px !important;
    }

    .table-bordered th {
      border-top: 1px solid #dee2e6 !important;
      border-bottom: 1px solid #dee2e6 !important;
      border-left: 1px solid #dee2e6 !important;
      border-right: 1px solid #dee2e6 !important;
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 700;
      vertical-align: middle !important;
      padding: 10px !important;
    }

    thead th:first-child, 
    .modal-content .custom-model-extend-form .modal-body .card-outline .card-body .table-bordered thead:first-child,
    tfoot th:first-child, .table .thead-light th:first-child {
      border-left: 1px solid #dee2e6 !important;
    }

    thead th:last-child,
    .modal-content .custom-model-extend-form .modal-body .card-outline .card-body .table-bordered thead:last-child,
    tfoot th:last-child, .table .thead-light th:last-child {
      border-right: 1px solid #dee2e6 !important;
    }

    tbody tr.odd, .table #customerTable .details:nth-child(odd) {
      background: #f4f4f4;
    }

    tbody tr.even, .table #customerTable .details:nth-child(even) {
      background: #fff;
    }

    tbody td, .table-bordered tbody td, .table #customerTable td {
      border: 1px solid #dee2e6 !important;
      padding: 10px !important;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 400;
      color: #2f333e;
    }

    tbody td .row:last-child {
      flex-direction: row !important;
    }

    .table-bordered td .row {
      margin-left: 0px;
      margin-right: 0px;
    }

    .table-bordered td .row p {
      margin-bottom: 15px;
    }

    .table-bordered td hr {
      margin-top: 15px !important;
      margin-bottom: 25px !important;
    }

    .table-bordered td h3 {
      font-size: 120%;
      text-decoration: underline;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: bolder;
      color: #2f333e;
      margin-bottom: 15px;
    }

    .table-bordered td .row.mb-2 {
      justify-content: end;
      margin-bottom: 15px !important;
    }

    tfoot {
      background-color: #243958;
      color: #fff;
    }

    #weightTable_wrapper .row:last-child, #translationTable_wrapper .row:last-child, #supplierTable_wrapper .row:last-child,
    #categoryTable_wrapper .row:last-child, #packagingTable_wrapper .row:last-child, #customerTable_wrapper .row:last-child,
    #productTable_wrapper .row:last-child, #driverTable_wrapper .row:last-child, #vehicleTable_wrapper .row:last-child,
    #gradeTable_wrapper .row:last-child, #locationTable_wrapper .row:last-child, #memberTable_wrapper .row:last-child,
    #transferTable_wrapper .row:last-child, #purchaseTable_wrapper .row:last-child, #inventoryTable_wrapper .row:last-child {
      flex-direction: column;
    }

    #translationTable_wrapper .row:last-child .col-md-5, #translationTable_wrapper .row:last-child .col-md-7, 
    #supplierTable_wrapper .row:last-child .col-md-5, #supplierTable_wrapper .row:last-child .col-md-7,
    #categoryTable_wrapper .row:last-child .col-md-5, #categoryTable_wrapper .row:last-child .col-md-7,
    #packagingTable_wrapper .row:last-child .col-md-5, #packagingTable_wrapper .row:last-child .col-md-7,
    #customerTable_wrapper .row:last-child .col-md-5, #customerTable_wrapper .row:last-child .col-md-7,
    #productTable_wrapper .row:last-child .col-md-5, #productTable_wrapper .row:last-child .col-md-7,
    #driverTable_wrapper .row:last-child .col-md-5, #driverTable_wrapper .row:last-child .col-md-7,
    #vehicleTable_wrapper .row:last-child .col-md-5, #vehicleTable_wrapper .row:last-child .col-md-7,
    #gradeTable_wrapper .row:last-child .col-md-5, #gradeTable_wrapper .row:last-child .col-md-7,
    #locationTable_wrapper .row:last-child .col-md-5, #locationTable_wrapper .row:last-child .col-md-7,
    #memberTable_wrapper .row:last-child .col-md-5, #memberTable_wrapper .row:last-child .col-md-7,
    #transferTable_wrapper .row:last-child .col-md-5, #transferTable_wrapper .row:last-child .col-md-7,
    #purchaseTable_wrapper .row:last-child .col-md-5, #purchaseTable_wrapper .row:last-child .col-md-7,
    #inventoryTable_wrapper .row:last-child .col-md-5, #inventoryTable_wrapper .row:last-child .col-md-7 {
      max-width: 100%;
    }

    #weightTable_wrapper .row:last-child .dataTables_info, #translationTable_wrapper .row:last-child .dataTables_info, 
    #supplierTable_wrapper .row:last-child .dataTables_info, #categoryTable_wrapper .row:last-child .dataTables_info, 
    #packagingTable_wrapper .row:last-child .dataTables_info, #customerTable_wrapper .row:last-child .dataTables_info, 
    #productTable_wrapper .row:last-child .dataTables_info, #driverTable_wrapper .row:last-child .dataTables_info,
    #vehicleTable_wrapper .row:last-child .dataTables_info, #gradeTable_wrapper .row:last-child .dataTables_info,
    #locationTable_wrapper .row:last-child .dataTables_info, #memberTable_wrapper .row:last-child .dataTables_info,
    #transferTable_wrapper .row:last-child .dataTables_info, #purchaseTable_wrapper .row:last-child .dataTables_info,
    #inventoryTable_wrapper .row:last-child .dataTables_info {
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 400;
      margin-bottom: 25px;
      color: #2f333e;
    }

    #weightTable_wrapper .row:last-child .dataTables_paginate .pagination, 
    #translationTable_wrapper .row:last-child .dataTables_paginate .pagination,
    #supplierTable_wrapper .row:last-child .dataTables_paginate .pagination,
    #categoryTable_wrapper .row:last-child .dataTables_paginate .pagination,
    #packagingTable_wrapper .row:last-child .dataTables_paginate .pagination,
    #customerTable_wrapper .row:last-child .dataTables_paginate .pagination,
    #productTable_wrapper .row:last-child .dataTables_paginate .pagination,
    #driverTable_wrapper .row:last-child .dataTables_paginate .pagination,
    #vehicleTable_wrapper .row:last-child .dataTables_paginate .pagination,
    #gradeTable_wrapper .row:last-child .dataTables_paginate .pagination,
    #locationTable_wrapper .row:last-child .dataTables_paginate .pagination,
    #memberTable_wrapper .row:last-child .dataTables_paginate .pagination,
    #transferTable_wrapper .row:last-child .dataTables_paginate .pagination,
    #purchaseTable_wrapper .row:last-child .dataTables_paginate .pagination,
    #inventoryTable_wrapper .row:last-child .dataTables_paginate .pagination {
      margin-bottom: 0px;
      border-radius: 5px;
    }

    #weightTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link,
    #translationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link,
    #supplierTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link,
    #categoryTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link,
    #packagingTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link,
    #customerTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link,
    #productTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link,
    #driverTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link,
    #vehicleTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link,
    #gradeTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link,
    #locationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link,
    #memberTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link,
    #transferTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link,
    #purchaseTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link,
    #inventoryTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link {
      border: 1px solid #dee2e6;
      padding: 10px;
      color: #2f333e;
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 400;
    }

    #weightTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active .page-link,
    #translationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active .page-link,
    #supplierTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active .page-link,
    #categoryTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active .page-link,
    #packagingTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active .page-link,
    #customerTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active .page-link,
    #productTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active .page-link,
    #driverTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active .page-link,
    #vehicleTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active .page-link,
    #gradeTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active .page-link,
    #locationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active .page-link,
    #memberTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active .page-link,
    #transferTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active .page-link,
    #purchaseTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active .page-link,
    #inventoryTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.active .page-link,
    #weightTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link:hover,
    #translationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link:hover,
    #supplierTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link:hover,
    #categoryTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link:hover,
    #packagingTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link:hover,
    #customerTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link:hover,
    #productTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link:hover,
    #driverTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link:hover,
    #vehicleTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link:hover,
    #gradeTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link:hover,
    #locationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link:hover,
    #memberTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link:hover,
    #transferTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link:hover,
    #purchaseTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link:hover,
    #inventoryTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item .page-link:hover {
      background: #243958;
      color: #fff;
    }

    #weightTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled .page-link,
    #translationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled .page-link,
    #supplierTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled .page-link,
    #categoryTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled .page-link,
    #packagingTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled .page-link,
    #customerTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled .page-link,
    #productTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled .page-link,
    #driverTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled .page-link,
    #vehicleTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled .page-link,
    #gradeTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled .page-link,
    #locationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled .page-link,
    #memberTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled .page-link,
    #transferTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled .page-link,
    #purchaseTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled .page-link,
    #inventoryTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item.disabled .page-link {
      color: rgba(47, 51, 62, .5);
    }

    #weightTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:first-child .page-link,
    #translationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:first-child .page-link,
    #supplierTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:first-child .page-link,
    #categoryTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:first-child .page-link,
    #packagingTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:first-child .page-link,
    #customerTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:first-child .page-link,
    #productTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:first-child .page-link,
    #driverTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:first-child .page-link,
    #vehicleTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:first-child .page-link,
    #gradeTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:first-child .page-link,
    #locationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:first-child .page-link,
    #memberTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:first-child .page-link,
    #transferTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:first-child .page-link,
    #purchaseTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:first-child .page-link,
    #inventoryTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:first-child .page-link {
      border-top-left-radius: 5px;
      border-bottom-left-radius: 5px;
    }

    #weightTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:last-child .page-link,
    #translationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:last-child .page-link,
    #supplierTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:last-child .page-link,
    #categoryTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:last-child .page-link,
    #packagingTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:last-child .page-link,
    #customerTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:last-child .page-link,
    #productTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:last-child .page-link,
    #driverTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:last-child .page-link,
    #vehicleTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:last-child .page-link,
    #gradeTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:last-child .page-link,
    #locationTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:last-child .page-link,
    #memberTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:last-child .page-link,
    #transferTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:last-child .page-link,
    #purchaseTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:last-child .page-link,
    #inventoryTable_wrapper .row:last-child .dataTables_paginate .pagination .page-item:last-child .page-link {
      border-top-right-radius: 5px;
      border-bottom-right-radius: 5px;
    }

    #extendModal, #translationModal, #supplierTable_wrapper, #categoryTable_wrapper, #packagingTable_wrapper, 
    #customerTable_wrapper, #productTable_wrapper, #driverTable_wrapper, #vehicleTable_wrapper, #gradeTable_wrapper,
    #locationTable_wrapper, #memberTable_wrapper, #transferTable_wrapper, #purchaseTable_wrapper, #inventoryTable_wrapper {
      padding-right: 0px !important;
    }

    .modal-content .custom-model-extend-form .modal-header .custom-close-btn-icon, 
    .custom-modal-content .modal-header .custom-close-btn-icon {
      margin: 0px;
      padding: 0px;
      font-size: 25px;
      line-height: 30px;
      letter-spacing: 0.75px;
      color: #fff;
      text-shadow: unset;
      opacity: 1;
    }

    .modal-content .custom-model-extend-form .modal-body, .custom-modal-content .modal-body {
      padding: 20px 25px;
    }

    .modal-content .custom-model-extend-form .modal-body hr, .content .card .card-body hr {
      border-top: 1px solid #243958;
      margin-top: 25px;
      margin-bottom: 45px;
    }

    .modal-content .custom-model-extend-form .modal-body .d-flex.mb-2 {
      margin-bottom: 0px !important;
    }

    .modal-content .custom-model-extend-form .modal-body h5, .content .card .card-body h5 {
      font-size: 20px;
      line-height: 25px;
      letter-spacing: 0.75px;
      font-weight: 700;
      color: #243958;
      margin-bottom: 25px;
    }

    .modal-content .custom-model-extend-form .modal-body p {
      font-size: 20px;
      line-height: 25px;
      letter-spacing: 0.75px;
      font-weight: 700 !important;
      color: #3fb84e;
      margin-bottom: 25px !important;
    }

    .modal-content .custom-model-extend-form .modal-body .d-flex label.text-muted {
      color: #243958 !important;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      margin-right: 10px !important;
    }

    .modal-content .custom-model-extend-form .modal-body .d-flex input.form-control {
      margin-right: 10px !important;
    }

    .modal-content .custom-model-extend-form .modal-body .table-bordered {
      margin-top: 25px;
      margin-left: 7.5px;
      margin-right: 7.5px;
    }

    .modal-content .custom-model-extend-form .modal-body #addRejectWeightBtn,
    .modal-content .custom-model-extend-form .modal-footer .custom-close-btn, .custom-delete-btn {
      background: #EF4444 !important;
      color: #fff !important;
      padding: 5px 15px;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 700;
      border: unset !important;
      border-radius: 5px;
      box-shadow: unset !important;
      margin: 0px;
    }

    .custom-delete-btn, .custom-add-btn {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
    }

    .custom-remove-btn #removeProductImage {
      padding: 5px 15px;
      border: 1px solid #EF4444;
      border-radius: 5px;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 700;
      color: #EF4444;
    }

    .custom-trash-icon-btn {
      background: #EF4444 !important;
      color: #fff !important;
      padding: 10px;
      font-size: 12px;
      line-height: normal;
      letter-spacing: 0.75px;
      font-weight: 700;
      border: unset !important;
      border-radius: 5px;
      box-shadow: unset !important;
    }

    .modal-content .custom-model-extend-form .modal-body #addRejectWeightBtn:hover,
    .modal-content .custom-model-extend-form .modal-footer .custom-close-btn:hover, .custom-trash-icon-btn:hover,
    .custom-delete-btn:hover, .custom-remove-btn #removeProductImage:hover {
      background: #DC2626 !important;
      color: #fff !important;
    }

    .modal-content .custom-model-extend-form .modal-body .card-outline, .custom-repacking-form .card-outline {
      border: unset;
      margin-bottom: 25px !important;
      border-radius: 5px;
      box-shadow: 0px 0px 5px 0px rgba(0, 0, 0, .5);
    }

    .modal-content .custom-model-extend-form .modal-body .card-outline .card-header, .custom-repacking-form .card-outline .card-header {
      background: #3fb84e !important;
      color: #fff;
      padding-top: 10px !important;
      padding-bottom: 10px !important;
    }

    .modal-content .custom-model-extend-form .modal-body .card-outline .card-header h6,
    .custom-repacking-form .card-outline .card-header h6 {
      font-size: 18px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .custom-repacking-form .card-outline .card-header h6 {
      margin-bottom: 0px;
    }

    .modal-content .custom-model-extend-form .modal-body .card-outline .card-body {
      padding: 20px 25px !important;
    }

    .modal-content .custom-model-extend-form .modal-body .card-outline .card-body .table-bordered {
      margin-top: 0px;
      margin-left: 0px;
      margin-right: 0px;
    }

    .modal-content .custom-model-extend-form .modal-body .card-outline .badge-secondary {
      color: #fff;
      background-color: #2f333e;
      padding: 5px 15px;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      margin-left: 0px !important;
      border-radius: 5px;
    }

    .modal-content .custom-model-extend-form .modal-body .custom-card-outline-small {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 400;
      color: #2f333e;
    }

    .custom-model-extend-form .modal-body .nav-tabs {
      border: unset;
      margin-bottom: 25px;
    }

    .custom-model-extend-form .modal-body .nav-tabs .nav-item .nav-link {
      margin: 0px;
      border: 1px solid #2f333e;
      border-radius: 5px;
      margin-right: 15px;
      padding: 5px 15px;
      color: #2f333e;
      font-size: 16px;
      line-height: 24px;
      letter-spacing: 0.75px;
      font-weight: 700;
    }

    .custom-model-extend-form .modal-body .nav-tabs .nav-item:last-child .nav-link {
      margin-right: 0px;
    }

    .custom-model-extend-form .modal-body .nav-tabs .nav-item .nav-link:hover,
    .custom-model-extend-form .modal-body .nav-tabs .nav-item .nav-link.active {
      background: #2f333e;
      color: #fff;
    }

    .custom-model-extend-form .modal-body .tab-content .custom-tab-content {
      margin-bottom: 25px;
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 15px;
    }

    .custom-download-btn {
      background: #64748B !important;
      color: #fff !important;
      padding: 5px 15px;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 700;
      border: unset !important;
      border-radius: 5px;
      box-shadow: unset !important;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
    }

    .custom-users-icon-btn {
      background: #64748B !important;
      color: #fff !important;
      padding: 10px;
      font-size: 12px;
      line-height: normal;
      letter-spacing: 0.75px;
      font-weight: 700;
      border: unset !important;
      border-radius: 5px;
      box-shadow: unset !important;
    }

    .custom-download-btn:hover, .custom-users-icon-btn:hover {
      background: #475569 !important;
      color: #fff !important;
    }

    .custom-upload-input {
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 400;
      color: #2f333e;
    }

    .custom-preview-data-btn {
      background: #FFC000;
      color: #2f333e;
      padding: 5px 15px;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 700;
      border: unset;
      border-radius: 5px;
    }

    .custom-preview-data-btn:hover {
      background: #FFDE21;
      color: #2f333e;
    }

    .custom-activate {
      background: #10B981;
      color: #fff;
      padding: 5px 15px;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 700;
      border-radius: 5px;
    }

    .custom-inactivate {
      background: #EF4444;
      color: #fff;
      padding: 5px 15px;
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 700;
      border-radius: 5px;
    }

    .main-footer {
      border-top: unset;
      color: #2f333e;
      padding: 10px 15px;
      box-shadow: 0px -2.5px 5px 0px rgba(255, 255, 255, .5);
      font-size: 15px;
      line-height: 23px;
      letter-spacing: 0.75px;
      font-weight: 400;
    }

    .main-footer a {
      color: #3fb84e;
    }

    .main-footer a:hover {
      color: #243958;
    }

    @media (min-width: 576px) {
      .modal-content {
        box-shadow: 0px 0px 5px 0px rgba(255, 255, 255, .5);
      } 
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
  <nav class="main-header navbar navbar-expand navbar-primary navbar-light" style="background-color: white;">
    <!-- Left navbar links -->
    <ul class="navbar-nav custom-navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></i></a>
      </li>
    </ul>
    
    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#" role="button" style="background-color: #f4f4f4; border-radius: 50%; padding: 8px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
          <i class="fas fa-user" style="font-size: 16px; color: #666;"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-right">
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
  <aside class="main-sidebar sidebar-dark-primary elevation-4" style="background-color: #3fb84e;">
    <!-- Brand Logo -->
    <a href="#" class="brand-link logo-switch">
      <img src="assets/mun-meng-white-site-logo.png" alt="Sneakercube Logo" class="brand-image-xl logo-xs"">
      <img src="assets/mun-meng-white-logo.png" alt="Sneakercube Logo" class="brand-image-xl logo-xl">
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel">
          <div class="image" style="align-self: center;">
            <img src="assets/user-avatar.png" class="img-circle elevation-2" alt="User Image">
          </div>
          <div class="info" style="white-space: nowrap;">
            <p><?=$languageArray['welcome_code'][$language]?></p>
            <a href="#myprofile" data-file="myprofile.php" id="goToProfile" class="d-block"><?=$name ?></a>
          </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="sidebar-menu-custom">
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
          <?php if ($module == 'pricing') { ?>
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
          <?php if ($module == 'processing') { ?>
          <li class="nav-item has-treeview menu-open">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-cog"></i>
              <p><?=$languageArray['processing_code'][$language]?><i class="fas fa-angle-left right"></i></p>
            </a>
            <ul class="nav nav-treeview" style="display: block;">
              <li class="nav-item">
                <a href="#wholesales" data-file="wholesales.php" class="nav-link link">
                  <i class="nav-icon fas fa-cubes"></i>
                  <p><?=$languageArray['wholesales_code'][$language]?></p>
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
          <?php if ($module != 'pricing' && $module != 'processing' && $module != 'accounting' && $module != 'stocks') { ?>
          <li class="nav-item has-treeview menu-open">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
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
                <a href="#wholesales" data-file="wholesales.php" class="nav-link link">
                  <i class="nav-icon fas fa-cubes"></i>
                  <p><?=$languageArray['wholesales_code'][$language]?></p>
                </a>
              </li>
              <?php } ?>
              <?php if ($module == 'weighing') { ?>
              <li class="nav-item">
                <a href="#weighbridges" data-file="weighbridges.php" class="nav-link link">
                  <i class="nav-icon fas fa-cubes"></i>
                  <p><?=$languageArray['weighbridge_code'][$language]?></p>
                </a>
              </li>
              <?php } ?>
              <?php if ($module == 'industrial') { ?>
              <li class="nav-item">
                <a href="#industrial" data-file="industrial.php" class="nav-link link">
                  <i class="nav-icon fas fa-cubes"></i>
                  <p><?=$languageArray['industrial_code'][$language]?></p>
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
          <?php if ($module == 'wholesale') { ?>
          <li class="nav-item">
            <a href="#reports" data-file="reports.php" class="nav-link link">
              <i class="nav-icon fas fa-th"></i>
              <p><?=$languageArray['reports_code'][$language]?></p>
            </a>
          </li>
          <?php } ?>
          <?php if ($module == 'weighing') { ?>
          <li class="nav-item">
            <a href="#reportsWb" data-file="reportsWb.php" class="nav-link link">
              <i class="nav-icon fas fa-th"></i>
              <p><?=$languageArray['reports_code'][$language]?></p>
            </a>
          </li>
          <?php } ?>
          <?php if ($module == 'industrial') { ?>
          <li class="nav-item">
            <a href="#reportsIndustry" data-file="reportsIndustry.php" class="nav-link link">
              <i class="nav-icon fas fa-th"></i>
              <p><?=$languageArray['reports_code'][$language]?></p>
            </a>
          </li>
          <?php } ?>
          <?php if ($module == 'packing') { ?>
          <li class="nav-item">
            <a href="#reportsPacking" data-file="reportsPacking.php" class="nav-link link">
              <i class="nav-icon fas fa-cubes"></i>
              <p><?=$languageArray['reports_code'][$language]?></p>
            </a>
          </li>
          <?php } ?>
          <?php if ($module == 'stocks') { ?>
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
          <?php if ($module == 'accounting') { ?>
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
              if($role == "ADMIN" || $role == "SADMIN" || $role == "MANAGER"){
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
  <footer class="main-footer">
    <strong>Copyright &copy; 2024 <a href="#">SyncWeight</a> .</strong> All Rights Reserved . <div class="float-right d-none d-sm-inline-block"><b>Version</b> 1.0.0 </div>
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
