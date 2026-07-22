<?php
require_once 'php/db_connect.php';
session_start();

if (!isset($_SESSION['userID'])) {
  echo '<script>window.location.href = "login.html";</script>';
} else {
  $user = $_SESSION['userID'];
  $module = $_SESSION['module'] ?? '';
  $companyProducts = $_SESSION['products'] ?? [];
  $company = $_SESSION['customer'];
  $role = $_SESSION['role'];
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];

  if ($role != 'SADMIN') {
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' AND customer = '$company' ORDER BY customer_name ASC");
    $customers2 = $db->query("SELECT * FROM customers WHERE deleted = '0' AND customer = '$company' ORDER BY customer_name ASC");
    $suppliers = $db->query("SELECT * FROM supplies WHERE deleted = '0' AND customer = '$company' ORDER BY supplier_name ASC");
    $suppliers2 = $db->query("SELECT * FROM supplies WHERE deleted = '0' AND customer = '$company' ORDER BY supplier_name ASC");
    $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' AND customer = '$company' ORDER BY locations ASC");
    $productionLines = $db->query("SELECT * FROM production_lines WHERE deleted = '0' AND customers = '$company' ORDER BY production_line ASC");
  } else {
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' ORDER BY customer_name ASC");
    $customers2 = $db->query("SELECT * FROM customers WHERE deleted = '0' ORDER BY customer_name ASC");
    $suppliers = $db->query("SELECT * FROM supplies WHERE deleted = '0' ORDER BY supplier_name ASC");
    $suppliers2 = $db->query("SELECT * FROM supplies WHERE deleted = '0' ORDER BY supplier_name ASC");
    $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' ORDER BY locations ASC");
    $productionLines = $db->query("SELECT * FROM production_lines WHERE deleted = '0' ORDER BY production_line ASC");
  }
}
?>

<link rel="stylesheet" href="assets/css/dashboard.css">

<!-- ── Page Header ──────────────────────────────────────── -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?=$languageArray['dashboard_code'][$language]?></h1>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">

    <!-- ── Global Filter ──────────────────────────────────── -->
    <div class="card dash-filter-card mb-3">
      <div class="card-body">
        <div class="row">
          <div class="form-group col-6 col-md-3">
            <label><?=$languageArray['from_date_code'][$language]?></label>
            <div class="input-group date" id="dashFromDatePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#dashFromDatePicker" id="dashFromDate"/>
              <div class="input-group-append" data-target="#dashFromDatePicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>
          <div class="form-group col-6 col-md-3">
            <label><?=$languageArray['to_date_code'][$language]?></label>
            <div class="input-group date" id="dashToDatePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#dashToDatePicker" id="dashToDate"/>
              <div class="input-group-append" data-target="#dashToDatePicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>
          <div class="form-group col-12 col-md-3">
            <label><?=$languageArray['locations_code'][$language]?></label>
            <select class="form-control select2" id="dashLocation">
              <option value=""><?=$languageArray['all_code'][$language]?></option>
              <?php while ($row = mysqli_fetch_assoc($locations)) { ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['locations']) ?></option>
              <?php } ?>
            </select>
          </div>
          <div class="form-group col-12 col-md-3 d-flex align-items-end">
            <button type="button" class="btn btn-warning btn-block" id="dashSearch">
              <i class="fas fa-search"></i> <?=$languageArray['search_code'][$language]?>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Tab Navigation ─────────────────────────────────── -->
    <ul class="nav nav-tabs" id="dashTabs">
      <?php if (!empty(array_intersect($companyProducts, ['wholesale', 'processing']))) { ?>
      <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#tabWholesales">
          <i class="fas fa-cubes"></i><span class="d-none d-sm-inline ml-1"><?=$languageArray['wholesales_code'][$language]?></span>
        </a>
      </li>
      <?php } ?>
      <?php if (!empty(array_intersect($companyProducts, ['industrial']))) { ?>
      <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tabPulpPaste">
          <i class="fas fa-blender"></i><span class="d-none d-sm-inline ml-1"><?=$languageArray['pulp_and_paste_code'][$language]?></span>
        </a>
      </li>
      <?php } ?>
      <?php if (!empty(array_intersect($companyProducts, ['processing']))) { ?>
      <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tabGrading">
          <i class="fas fa-clipboard-check"></i><span class="d-none d-sm-inline ml-1"><?=$languageArray['grading_code'][$language]?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tabPackaging">
          <i class="fas fa-box-open"></i><span class="d-none d-sm-inline ml-1"><?=$languageArray['batch_packaging_code'][$language]?></span>
        </a>
      </li>
      <?php } ?>
    </ul>

    <!-- ── Tab Panes ──────────────────────────────────────── -->
    <div class="tab-content dash-tab-content">
      <?php if (!empty(array_intersect($companyProducts, ['wholesale', 'processing']))) { ?>
        <?php require_once 'modules/dashboard/tab_wholesales.php'; ?>
      <?php } ?>
      <?php if (!empty(array_intersect($companyProducts, ['industrial']))) { ?>
        <?php require_once 'modules/dashboard/tab_pulppaste.php'; ?>
      <?php } ?>
      <?php if (!empty(array_intersect($companyProducts, ['processing']))) { ?>
        <?php require_once 'modules/dashboard/tab_grading.php'; ?>
        <?php require_once 'modules/dashboard/tab_packaging.php'; ?>
      <?php } ?>
    </div>

  </div>
</div>

<!-- ── JS — load shared utils first, then per-tab logic ── -->
<script src="modules/dashboard/js/dashboard.js"></script>
<script src="modules/dashboard/js/tab_wholesales.js"></script>
<script src="modules/dashboard/js/tab_grading.js"></script>
<script src="modules/dashboard/js/tab_packaging.js"></script>
<script src="modules/dashboard/js/tab_pulppaste.js"></script>
