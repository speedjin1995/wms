<?php

session_start();

?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        
        <title>WMS</title>
        
        <link rel="icon" href="assets/wms-logo-white-site-icon.png" type="image">
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
            .wrapper {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                /*background: url('assets/modules-bg.jpeg');*/
                background: url('assets/module-bg.jpeg');
                /*background: url('assets/modules-bg.jpg');*/
                background-repeat: no-repeat;
                background-size: cover;
                background-position: center;
            }

            .modules-box-list {
                display: flex;
                flex-wrap: wrap;
                flex-direction: row;
                justify-content: center;
                align-items: center;
                gap: 25px;
            }

            .modules-box-list a {
                width: 25%;
            }

            .modules-box-list .modules-box {
                background: #fff;
                padding: 25px;
                border-radius: 15px;
                display: flex;
                flex-direction: column;
                align-items: center;
                transform: translateY(0px);
                transition: all 0.25s ease-in-out;
            }

            .modules-box-list .modules-box:hover {
                cursor: pointer;
                transform: translateY(-10px);
                box-shadow: 5px -5px 0px 2.5px rgba(0, 51, 146, 1);
            }

            .modules-box-list .modules-box .modules-img {
                width: 100%;
                margin-bottom: 35px;
            }

            .modules-box-list .modules-box .modules-txt {
                font-size: 25px;
                line-height: 30px;
                font-weight: 700;
            }

            .company-name {
                position: absolute;
                top: 15%;
                width: 100%;
                text-align: center;
                font-size: 32px;
                font-weight: 700;
                letter-spacing: 1.5px;
                color: #1f2d3d;
            }
        </style>
    </head>

    <body>
        <div class="wrapper">
            <div class="company-name">
                <h1><?php echo $_SESSION['company_name']; ?></h1>
            </div>

            <div class="modules-box-list">
                <a href="php/setModule.php?module=weighing" 
                    <?php 
                        if (!in_array('fruits', $_SESSION['products'], false)) {
                            echo 'style="display:none;"';
                        }
                    ?>
                >
                    <div class="modules-box modules-box-1">
                        <img src="assets/weighing-bridge-icon-1.png" alt="Weighing Brdige" class="modules-img">
                        <div class="modules-txt">Weighing Bridge</div>
                    </div>
                </a>
                <a href="php/setModule.php?module=wholesale"
                    <?php 
                        if (!in_array('wholesale', $_SESSION['products'], false)) {
                            echo 'style="display:none;"';
                        }
                    ?>
                >
                    <div class="modules-box modules-box-2">
                        <img src="assets/wholesales-icon.png" alt="Weighing Brdige" class="modules-img">
                        <div class="modules-txt">Wholesales</div>
                    </div>
                </a>
                <a href="php/logout.php">
                    <div class="modules-box modules-box-3">
                        <img src="assets/logout-icon.png" alt="Weighing Brdige" class="modules-img">
                        <div class="modules-txt">Logout</div>
                    </div>
                </a>
            </div>
        </div>
    </body>
</html>