<?php
require_once 'php/db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
} else {
    // Language
    $company = $_SESSION['customer'];
    $language = $_SESSION['language'];
    $packages = $_SESSION['packages'] ?? [];
    $userName = $_SESSION['userName'] ?? 'User';

    // Load message resource
    if (in_array('P', $packages, true)) {
        $message_resource = $db->query("SELECT * FROM message_resource WHERE company = '$company'");
    } else {
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
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        
        <title>WMS</title>
        
        <link rel="icon" href="assets/fy-fruit-trading-logo-icon.png" type="image">
        <!-- Font Awesome Icons -->
        <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
        <!-- Google Font: Source Sans Pro -->
        <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
        <!-- Home Page Styles -->
        <link rel="stylesheet" href="assets/css/home.css">
    </head>

    <body>
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="top-bar-left">
                <img src="assets/fy-fruit-trading-logo-icon.png" alt="WMS" class="top-bar-logo">
            </div>
            <div class="top-bar-user">
                <div class="user-info">
                    <div class="user-greeting"><?=$languageArray['welcome_code'][$language] ?? 'Welcome'?></div>
                    <div class="user-name"><?=htmlspecialchars($userName)?></div>
                </div>
                <a href="php/logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span><?=$languageArray['logout_code'][$language]?></span>
                </a>
            </div>
        </div>

        <div class="wrapper">
            <div class="company-name">
                <h1><?php echo $_SESSION['company_name']; ?></h1>
            </div>

            <div class="modules-grid">
                <!-- Dashboard -->
                <a href="php/setModule.php?module=dashboard" class="module-card"
                    <?php 
                        if (empty(array_intersect(['industrial', 'wholesale', 'processing'], $_SESSION['products']))) {
                            echo 'style="display:none;"';
                        }
                    ?>
                >
                    <div class="module-card-inner">
                        <img src="assets/dashboard-icon.png" alt="Dashboard" class="module-icon">
                        <div class="module-name"><?=$languageArray['dashboard_code'][$language]?></div>
                        <p class="module-desc"><?=$languageArray['dashboard_desc'][$language] ?? 'Analytics & Reports'?></p>
                    </div>
                </a>

                <!-- Pulp & Paste -->
                <a href="php/setModule.php?module=industrial" class="module-card"
                    <?php 
                        if (!in_array('industrial', $_SESSION['products'], false)) {
                            echo 'style="display:none;"';
                        }
                    ?>
                >
                    <div class="module-card-inner">
                        <img src="assets/pieces-n-puree-1.png" alt="Pulp & Paste" class="module-icon">
                        <div class="module-name"><?=$languageArray['pulp_and_paste_code'][$language]?></div>
                        <p class="module-desc"><?=$languageArray['pulp_paste_desc'][$language] ?? 'Industrial Weighing'?></p>
                    </div>
                </a>

                <!-- Weighbridge -->
                <a href="php/setModule.php?module=weighing" class="module-card"
                    <?php 
                        if (!in_array('fruits', $_SESSION['products'], false)) {
                            echo 'style="display:none;"';
                        }
                    ?>
                >
                    <div class="module-card-inner">
                        <img src="assets/weighing-bridge-icon-1.png" alt="Weighbridge" class="module-icon">
                        <div class="module-name"><?=$languageArray['weighbridge_code'][$language]?></div>
                        <p class="module-desc"><?=$languageArray['weighbridge_desc'][$language] ?? 'Vehicle Weighing'?></p>
                    </div>
                </a>

                <!-- Wholesales -->
                <a href="php/setModule.php?module=wholesale" class="module-card"
                    <?php 
                        if (!in_array('wholesale', $_SESSION['products'], false)) {
                            echo 'style="display:none;"';
                        }
                    ?>
                >
                    <div class="module-card-inner">
                        <img src="assets/wholesales-icon.png" alt="Wholesales" class="module-icon">
                        <div class="module-name"><?=$languageArray['wholesales_code'][$language]?></div>
                        <p class="module-desc"><?=$languageArray['wholesales_desc'][$language] ?? 'Dispatch & Receiving'?></p>
                    </div>
                </a>

                <!-- Packing -->
                <a href="php/setModule.php?module=packing" class="module-card"
                    <?php 
                        if (!in_array('packing', $_SESSION['products'], false)) {
                            echo 'style="display:none;"';
                        }
                    ?>
                >
                    <div class="module-card-inner">
                        <img src="assets/food-packaging-icon.png" alt="Packing" class="module-icon">
                        <div class="module-name"><?=$languageArray['packing_code'][$language]?></div>
                        <p class="module-desc"><?=$languageArray['packing_desc'][$language] ?? 'Package Management'?></p>
                    </div>
                </a>

                <!-- Pricing -->
                <a href="php/setModule.php?module=pricing" class="module-card"
                    <?php 
                        if (!in_array('pricing', $_SESSION['products'], false)) {
                            echo 'style="display:none;"';
                        }
                    ?>
                >
                    <div class="module-card-inner">
                        <img src="assets/pricing-icon.png" alt="Pricing" class="module-icon">
                        <div class="module-name"><?=$languageArray['pricing_code'][$language]?></div>
                        <p class="module-desc"><?=$languageArray['pricing_desc'][$language] ?? 'Sales & Purchases'?></p>
                    </div>
                </a>

                <!-- Processing -->
                <a href="php/setModule.php?module=processing" class="module-card"
                    <?php 
                        if (!in_array('processing', $_SESSION['products'], false)) {
                            echo 'style="display:none;"';
                        }
                    ?>
                >
                    <div class="module-card-inner">
                        <img src="assets/packaging-icon.png" alt="Processing" class="module-icon">
                        <div class="module-name"><?=$languageArray['processing_code'][$language]?></div>
                        <p class="module-desc"><?=$languageArray['processing_desc'][$language] ?? 'Production Lines'?></p>
                    </div>
                </a>

                <!-- Accounting -->
                <a href="php/setModule.php?module=accounting" class="module-card"
                    <?php 
                        if (!in_array('accounting', $_SESSION['products'], false)) {
                            echo 'style="display:none;"';
                        }
                    ?>
                >
                    <div class="module-card-inner">
                        <img src="assets/accounting-icon.png" alt="Accounting" class="module-icon">
                        <div class="module-name"><?=$languageArray['accounting_code'][$language]?></div>
                        <p class="module-desc"><?=$languageArray['accounting_desc'][$language] ?? 'Payment Vouchers'?></p>
                    </div>
                </a>

                <!-- Stock Management -->
                <a href="php/setModule.php?module=stocks" class="module-card"
                    <?php 
                        if (!in_array('stocks', $_SESSION['products'], false)) {
                            echo 'style="display:none;"';
                        }
                    ?>
                >
                    <div class="module-card-inner">
                        <img src="assets/stocks-icon.png" alt="Stocks" class="module-icon">
                        <div class="module-name"><?=$languageArray['stock_management'][$language]?></div>
                        <p class="module-desc"><?=$languageArray['stocks_desc'][$language] ?? 'Inventory & Transfers'?></p>
                    </div>
                </a>
            </div>
        </div>
    </body>
</html>
