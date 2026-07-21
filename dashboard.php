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

<style>
  .dash-stat-card {
    border-radius: 8px;
    padding: 18px 20px;
    color: #fff;
    min-height: 90px;
  }
  .dash-stat-card .stat-label {
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
    opacity: 0.85;
    margin-bottom: 4px;
  }
  .dash-stat-card .stat-value {
    font-size: 28px;
    font-weight: 700;
    line-height: 1.1;
  }
  .dash-stat-card .stat-sub {
    font-size: 12px;
    opacity: 0.75;
    margin-top: 4px;
  }
  /* Push right elements (pager, total) to far right in collapsible card headers */
  .card-header.d-flex.justify-content-between > .d-flex.align-items-center:first-child {
    flex: 1;
  }
  /* Uniform header height across all dashboard cards */
  #tabWholesales .card-header {
    min-height: 48px;
  }
  /* Compact pager so it doesn't inflate the card header */
  #wsSupplierPager .btn,
  #wsCustomerPager .btn {
    padding: 1px 6px;
    font-size: 11px;
    line-height: 1.4;
  }
  #wsSupplierPager small,
  #wsCustomerPager small {
    font-size: 11px;
  }
  .breakdown-bar-wrap { margin-bottom: 10px; }
  .breakdown-bar-label { font-size: 13px; margin-bottom: 2px; display: flex; justify-content: space-between; }
  .breakdown-bar-track { background: #e9ecef; border-radius: 4px; height: 10px; }
  .breakdown-bar-fill { height: 10px; border-radius: 4px; background: #007bff; transition: width 0.4s; }
  .section-title {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #6c757d;
    margin-bottom: 12px;
  }
</style>

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

    <!-- Date Range Filter -->
    <div class="card">
      <div class="card-body py-3">
        <div class="row align-items-end">
          <div class="form-group col-md-3 mb-0">
            <label class="mb-1"><?=$languageArray['from_date_code'][$language]?></label>
            <div class="input-group date" id="dashFromDatePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#dashFromDatePicker" id="dashFromDate"/>
              <div class="input-group-append" data-target="#dashFromDatePicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>
          <div class="form-group col-md-3 mb-0">
            <label class="mb-1"><?=$languageArray['to_date_code'][$language]?></label>
            <div class="input-group date" id="dashToDatePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#dashToDatePicker" id="dashToDate"/>
              <div class="input-group-append" data-target="#dashToDatePicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>

          <div class="form-group col-md-3 mb-0">
            <label class="mb-1"><?=$languageArray['locations_code'][$language]?></label>
            <select class="form-control select2" id="dashLocation">
              <option value=""><?=$languageArray['all_code'][$language]?></option>
              <?php while ($row = mysqli_fetch_assoc($locations)) { ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['locations']) ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="col-md-2 mb-0">
            <button type="button" class="btn btn-warning btn-block" id="dashSearch">
              <i class="fas fa-search"></i> <?=$languageArray['search_code'][$language]?>
            </button>
          </div>

        </div>
      </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="dashTabs">
      <?php if (!empty(array_intersect($companyProducts, ['wholesale', 'processing']))) { ?>
      <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#tabWholesales">
          <i class="fas fa-cubes mr-1"></i> <?=$languageArray['wholesales_code'][$language]?>
        </a>
      </li>
      <?php } ?>
      <?php if (!empty(array_intersect($companyProducts, ['industrial']))) { ?>
      <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tabPulpPaste">
          <i class="fas fa-blender mr-1"></i> <?=$languageArray['pulp_and_paste_code'][$language]?>
        </a>
      </li>
      <?php } ?>
      <?php if (!empty(array_intersect($companyProducts, ['processing']))) { ?>
      <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tabGrading">
          <i class="fas fa-clipboard-check mr-1"></i> <?=$languageArray['grading_code'][$language]?>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tabPackaging">
          <i class="fas fa-box-open mr-1"></i> <?=$languageArray['batch_packaging_code'][$language]?>
        </a>
      </li>
      <?php } ?>
    </ul>

    <div class="tab-content" style="background:#fff; border:1px solid #dee2e6; border-top:none; border-radius:0 0 4px 4px; padding:20px;">

      <!-- ===== WHOLESALES TAB ===== -->
      <div class="tab-pane fade show active" id="tabWholesales">
        <!-- Wholesales Filters -->
        <div class="row mb-3">
          <div class="form-group col-md-3 mb-0">
            <label class="mb-1"><?=$languageArray['status_code'][$language]?></label>
            <select class="form-control select2" id="wsType">
              <option value=""><?=$languageArray['all_code'][$language]?></option>
              <option value="RECEIVING"><?=$languageArray['receiving_code'][$language]?></option>
              <option value="DISPATCH"><?=$languageArray['dispatch_code'][$language]?></option>
            </select>
          </div>
          <div class="form-group col-md-3 mb-0" id="wsSupplierWrap" style="display:none;">
            <label class="mb-1"><?=$languageArray['supplier_code'][$language]?></label>
            <select class="form-control select2" id="wsSupplier">
              <option value=""><?=$languageArray['all_code'][$language]?> <?=$languageArray['supplier_code'][$language]?></option>
              <?php while ($row = mysqli_fetch_assoc($suppliers)) { ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['supplier_name']) ?></option>
              <?php } ?>
            </select>
          </div>
          <div class="form-group col-md-3 mb-0" id="wsCustomerWrap" style="display:none;">
            <label class="mb-1"><?=$languageArray['customer_code'][$language]?></label>
            <select class="form-control select2" id="wsCustomer">
              <option value=""><?=$languageArray['all_code'][$language]?> <?=$languageArray['customer_code'][$language]?></option>
              <?php while ($row = mysqli_fetch_assoc($customers)) { ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['customer_name']) ?></option>
              <?php } ?>
            </select>
          </div>
        </div>

        <!-- Wholesales Summary Cards -->
        <div class="row mb-4 align-items-stretch" id="wsCards">
          <div class="col-md-3 col-6 mb-3" id="wsReceivingCard">
            <div class="dash-stat-card h-100" style="background:linear-gradient(135deg,#17a2b8,#138496);">
              <div class="stat-label"><?=$languageArray['receiving_code'][$language]?> — <?=$languageArray['total_weight_code'][$language]?></div>
              <div class="stat-value" id="wsReceivingWeight">—</div>
              <div class="stat-sub"><span id="wsReceivingCount">—</span> records &nbsp;|&nbsp; kg</div>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-3" id="wsReceivingValueCard">
            <div class="dash-stat-card h-100" style="background:linear-gradient(135deg,#0d6efd,#0a58ca);">
              <div class="stat-label"><?=$languageArray['receiving_code'][$language]?> — <?=$languageArray['total_value_code'][$language]?></div>
              <div class="stat-value" id="wsReceivingValue">—</div>
              <div class="stat-sub"><?=$languageArray['total_value_code'][$language]?></div>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-3" id="wsDispatchCard">
            <div class="dash-stat-card h-100" style="background:linear-gradient(135deg,#28a745,#1e7e34);">
              <div class="stat-label"><?=$languageArray['dispatch_code'][$language]?> — <?=$languageArray['total_weight_code'][$language]?></div>
              <div class="stat-value" id="wsDispatchWeight">—</div>
              <div class="stat-sub"><span id="wsDispatchCount">—</span> records &nbsp;|&nbsp; kg</div>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-3" id="wsDispatchValueCard">
            <div class="dash-stat-card h-100" style="background:linear-gradient(135deg,#fd7e14,#e55a00);">
              <div class="stat-label"><?=$languageArray['dispatch_code'][$language]?> — <?=$languageArray['total_value_code'][$language]?></div>
              <div class="stat-value" id="wsDispatchValue">—</div>
              <div class="stat-sub"><?=$languageArray['total_value_code'][$language]?></div>
            </div>
          </div>
        </div>

        <!-- Wholesales Breakdowns -->
        <div class="row mt-3">
          <div class="col-md-6" id="wsSupplierBreakdownWrap">
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between align-items-center" style="cursor:pointer;" onclick="toggleCard('wsSupplierBody','wsSupplierChevron')">
                <div class="d-flex align-items-center">
                  <i class="fas fa-chevron-down mr-2" id="wsSupplierChevron" style="font-size:11px;color:#6c757d;"></i>
                  <div class="section-title mb-0"><?=$languageArray['weight_code'][$language]?> by <?=$languageArray['supplier_code'][$language]?> (kg)</div>
                </div>
                <div id="wsSupplierPager" style="display:none;">
                  <button class="btn btn-sm btn-outline-secondary" id="wsSupplierPrev" onclick="event.stopPropagation();wsSupplierPage(-1)"><i class="fas fa-chevron-left"></i></button>
                  <small class="mx-2" id="wsSupplierPageInfo"></small>
                  <button class="btn btn-sm btn-outline-secondary" id="wsSupplierNext" onclick="event.stopPropagation();wsSupplierPage(1)"><i class="fas fa-chevron-right"></i></button>
                </div>
              </div>
              <div class="card-body" id="wsSupplierBody">
                <div id="wsSupplierBreakdown"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
              </div>
            </div>
          </div>
          <div class="col-md-6" id="wsCustomerBreakdownWrap">
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between align-items-center" style="cursor:pointer;" onclick="toggleCard('wsCustomerBody','wsCustomerChevron')">
                <div class="d-flex align-items-center">
                  <i class="fas fa-chevron-down mr-2" id="wsCustomerChevron" style="font-size:11px;color:#6c757d;"></i>
                  <div class="section-title mb-0"><?=$languageArray['weight_code'][$language]?> by <?=$languageArray['customer_code'][$language]?> (kg)</div>
                </div>
                <div id="wsCustomerPager" style="display:none;">
                  <button class="btn btn-sm btn-outline-secondary" id="wsCustomerPrev" onclick="event.stopPropagation();wsCustomerPage(-1)"><i class="fas fa-chevron-left"></i></button>
                  <small class="mx-2" id="wsCustomerPageInfo"></small>
                  <button class="btn btn-sm btn-outline-secondary" id="wsCustomerNext" onclick="event.stopPropagation();wsCustomerPage(1)"><i class="fas fa-chevron-right"></i></button>
                </div>
              </div>
              <div class="card-body" id="wsCustomerBody">
                <div id="wsCustomerBreakdown"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Grade Distribution -->
        <div class="row mt-3">
          <div class="col-md-6" id="wsGradeRecvWrap">
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between align-items-center" style="cursor:pointer;" onclick="toggleCard('wsGradeRecvBody','wsGradeRecvChevron')">
                <div class="d-flex align-items-center">
                  <i class="fas fa-chevron-down mr-2" id="wsGradeRecvChevron" style="font-size:11px;color:#6c757d;"></i>
                  <div class="section-title mb-0"><?=$languageArray['grade_distribution_code'][$language]?> &mdash; <?=$languageArray['receiving_code'][$language]?></div>
                </div>
                <span class="text-muted" style="font-size:12px;" id="wsGradeRecvTotal"></span>
              </div>
              <div class="card-body" id="wsGradeRecvBody">
                <div id="wsGradeRecvPills" class="mb-3" style="display:flex;flex-wrap:wrap;gap:6px;"></div>
                <div id="wsGradeRecvBars"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
              </div>
            </div>
          </div>
          <div class="col-md-6" id="wsGradeDispWrap">
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between align-items-center" style="cursor:pointer;" onclick="toggleCard('wsGradeDispBody','wsGradeDispChevron')">
                <div class="d-flex align-items-center">
                  <i class="fas fa-chevron-down mr-2" id="wsGradeDispChevron" style="font-size:11px;color:#6c757d;"></i>
                  <div class="section-title mb-0"><?=$languageArray['grade_distribution_code'][$language]?> &mdash; <?=$languageArray['dispatch_code'][$language]?></div>
                </div>
                <span class="text-muted" style="font-size:12px;" id="wsGradeDispTotal"></span>
              </div>
              <div class="card-body" id="wsGradeDispBody">
                <div id="wsGradeDispPills" class="mb-3" style="display:flex;flex-wrap:wrap;gap:6px;"></div>
                <div id="wsGradeDispBars"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Hourly Distribution -->
        <div class="row mt-3" id="wsHourlyWrap">
          <div class="col-md-6" id="wsHourlyRecvWrap">
            <div class="card h-100">
              <div class="card-header" style="cursor:pointer;" onclick="toggleCard('wsHourlyRecvBody','wsHourlyRecvChevron')">
                <div class="d-flex align-items-center">
                  <i class="fas fa-chevron-down mr-2" id="wsHourlyRecvChevron" style="font-size:11px;color:#6c757d;"></i>
                  <div class="section-title mb-0"><?=$languageArray['receiving_code'][$language]?> by Hour (kg)</div>
                </div>
              </div>
              <div class="card-body" id="wsHourlyRecvBody">
                <canvas id="wsHourlyRecvChart" height="120"></canvas>
              </div>
            </div>
          </div>
          <div class="col-md-6" id="wsHourlyDispWrap">
            <div class="card h-100">
              <div class="card-header" style="cursor:pointer;" onclick="toggleCard('wsHourlyDispBody','wsHourlyDispChevron')">
                <div class="d-flex align-items-center">
                  <i class="fas fa-chevron-down mr-2" id="wsHourlyDispChevron" style="font-size:11px;color:#6c757d;"></i>
                  <div class="section-title mb-0"><?=$languageArray['dispatch_code'][$language]?> by Hour (kg)</div>
                </div>
              </div>
              <div class="card-body" id="wsHourlyDispBody">
                <canvas id="wsHourlyDispChart" height="120"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Volume Trend -->
        <div class="row mt-3 mb-3" id="wsTrendWrap">
          <div class="col-md-12">
            <div class="card h-100">
              <div class="card-header" style="cursor:pointer;" onclick="toggleCard('wsTrendBody','wsTrendChevron')">
                <div class="d-flex align-items-center">
                  <i class="fas fa-chevron-down mr-2" id="wsTrendChevron" style="font-size:11px;color:#6c757d;"></i>
                  <div class="section-title mb-0"><?=$languageArray['volume_trending_code'][$language]?></div>
                </div>
              </div>
              <div class="card-body" id="wsTrendBody">
                <canvas id="wsTrendChart" height="60"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ===== GRADING TAB ===== -->
      <div class="tab-pane fade" id="tabGrading">
        <!-- Grading Summary Cards -->
        <div class="row mb-4">
          <div class="col-md-3 col-6 mb-3">
            <div class="dash-stat-card" style="background:linear-gradient(135deg,#6f42c1,#5a32a3);">
              <div class="stat-label"><?=$languageArray['total_code'][$language]?> <?=$languageArray['net_code'][$language]?> <?=$languageArray['weight_code'][$language]?></div>
              <div class="stat-value" id="grTotalNet">—</div>
              <div class="stat-sub"><span id="grSessionCount">—</span> sessions &nbsp;|&nbsp; kg</div>
            </div>
          </div>
        </div>

        <!-- Grading Breakdown by Product + Grade -->
        <div class="section-title"><?=$languageArray['net_code'][$language]?> <?=$languageArray['weight_code'][$language]?> by Product &amp; <?=$languageArray['grading_code'][$language]?></div>
        <div id="grProductBreakdown"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
      </div>

      <!-- ===== PACKAGING TAB ===== -->
      <div class="tab-pane fade" id="tabPackaging">
        <!-- Packaging Filters -->
        <div class="row mb-3">
          <div class="form-group col-md-3 mb-0">
            <label class="mb-1">Production Line</label>
            <select class="form-control select2" id="pkgProductionLine">
              <option value="">All</option>
              <?php while ($row = mysqli_fetch_assoc($productionLines)) { ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['production_line']) ?></option>
              <?php } ?>
            </select>
          </div>
        </div>
        <!-- Packaging Summary Cards -->
        <div class="row mb-4">
          <div class="col-md-3 col-6 mb-3">
            <div class="dash-stat-card" style="background:linear-gradient(135deg,#007bff,#0056b3);">
              <div class="stat-label"><?=$languageArray['total_weight_code'][$language]?></div>
              <div class="stat-value" id="pkgTotalWeight">—</div>
              <div class="stat-sub"><span id="pkgBatchCount">—</span> batches &nbsp;|&nbsp; kg</div>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-3">
            <div class="dash-stat-card" style="background:linear-gradient(135deg,#e83e8c,#c2185b);">
              <div class="stat-label"><?=$languageArray['total_code'][$language]?> <?=$languageArray['boxes_code'][$language]?></div>
              <div class="stat-value" id="pkgTotalBoxes">—</div>
              <div class="stat-sub"><?=$languageArray['boxes_code'][$language]?> packed</div>
            </div>
          </div>
        </div>

        <!-- Packaging Breakdown by Product -->
        <div class="section-title"><?=$languageArray['weight_code'][$language]?> by Product (kg)</div>
        <div id="pkgProductBreakdown"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
      </div>

      <!-- ===== PULP & PASTE TAB ===== -->
      <div class="tab-pane fade" id="tabPulpPaste">
        <!-- Pulp & Paste Filters -->
        <div class="row mb-3">
          <div class="form-group col-md-3 mb-0">
            <label class="mb-1"><?=$languageArray['status_code'][$language]?></label>
            <select class="form-control" id="ppType">
              <option value=""><?=$languageArray['all_code'][$language]?></option>
              <option value="INCOMING"><?=$languageArray['incoming_code'][$language]?></option>
              <option value="OUTGOING"><?=$languageArray['outgoing_code'][$language]?></option>
            </select>
          </div>
          <div class="form-group col-md-3 mb-0" id="ppSupplierWrap" style="display:none;">
            <label class="mb-1"><?=$languageArray['supplier_code'][$language]?></label>
            <select class="form-control select2" id="ppSupplier">
              <option value=""><?=$languageArray['all_code'][$language]?> <?=$languageArray['supplier_code'][$language]?></option>
              <?php while ($row = mysqli_fetch_assoc($suppliers2)) { ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['supplier_name']) ?></option>
              <?php } ?>
            </select>
          </div>
          <div class="form-group col-md-3 mb-0" id="ppCustomerWrap" style="display:none;">
            <label class="mb-1"><?=$languageArray['customer_code'][$language]?></label>
            <select class="form-control select2" id="ppCustomer">
              <option value=""><?=$languageArray['all_code'][$language]?> <?=$languageArray['customer_code'][$language]?></option>
              <?php while ($row = mysqli_fetch_assoc($customers2)) { ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['customer_name']) ?></option>
              <?php } ?>
            </select>
          </div>
        </div>
        <!-- Summary Cards -->
        <div class="row mb-4">
          <div class="col-md-3 col-6 mb-3" id="ppIncomingCard">
            <div class="dash-stat-card" style="background:linear-gradient(135deg,#fd7e14,#e55a00);">
              <div class="stat-label"><?=$languageArray['incoming_code'][$language]?> — <?=$languageArray['total_weight_code'][$language]?></div>
              <div class="stat-value" id="ppIncomingWeight">—</div>
              <div class="stat-sub"><span id="ppIncomingCount">—</span> records &nbsp;|&nbsp; kg</div>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-3" id="ppOutgoingCard">
            <div class="dash-stat-card" style="background:linear-gradient(135deg,#20c997,#12876f);">
              <div class="stat-label"><?=$languageArray['outgoing_code'][$language]?> — <?=$languageArray['total_weight_code'][$language]?></div>
              <div class="stat-value" id="ppOutgoingWeight">—</div>
              <div class="stat-sub"><span id="ppOutgoingCount">—</span> records &nbsp;|&nbsp; kg</div>
            </div>
          </div>
        </div>
        <!-- Breakdowns -->
        <div class="row">
          <div class="col-md-6" id="ppSupplierBreakdownWrap">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="section-title mb-0"><?=$languageArray['weight_code'][$language]?> by <?=$languageArray['supplier_code'][$language]?> (kg)</div>
              <div id="ppSupplierPager" style="display:none;">
                <button class="btn btn-sm btn-outline-secondary" onclick="ppSupplierPage(-1)"><i class="fas fa-chevron-left"></i></button>
                <small class="mx-2" id="ppSupplierPageInfo"></small>
                <button class="btn btn-sm btn-outline-secondary" onclick="ppSupplierPage(1)"><i class="fas fa-chevron-right"></i></button>
              </div>
            </div>
            <div id="ppSupplierBreakdown"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
          </div>
          <div class="col-md-6" id="ppCustomerBreakdownWrap">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="section-title mb-0"><?=$languageArray['weight_code'][$language]?> by <?=$languageArray['customer_code'][$language]?> (kg)</div>
              <div id="ppCustomerPager" style="display:none;">
                <button class="btn btn-sm btn-outline-secondary" onclick="ppCustomerPage(-1)"><i class="fas fa-chevron-left"></i></button>
                <small class="mx-2" id="ppCustomerPageInfo"></small>
                <button class="btn btn-sm btn-outline-secondary" onclick="ppCustomerPage(1)"><i class="fas fa-chevron-right"></i></button>
              </div>
            </div>
            <div id="ppCustomerBreakdown"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
          </div>
        </div>

        <!-- Volume Trend Chart -->
        <div class="card mt-3 mb-3">
          <div class="card-header">
            <div class="section-title mb-0">Volume Trending (kg)</div>
          </div>
          <div class="card-body">
            <canvas id="ppTrendChart" height="80"></canvas>
          </div>
        </div>
      </div>

    </div><!-- /.tab-content -->
  </div>
</div>

<script>
  var wsTrendChart     = null;
  var wsHourlyRecvChart = null;
  var wsHourlyDispChart = null;

  var wsSupplierData = [];
  var wsSupplierCurrentPage = 0;
  var wsCustomerData = [];
  var wsCustomerCurrentPage = 0;
  var ppTrendChart = null;
  var ppSupplierData = [];
  var ppSupplierCurrentPage = 0;
  var ppCustomerData = [];
  var ppCustomerCurrentPage = 0;
  var WS_PAGE_SIZE = 10;

  $(function () {
    var today = new Date();

    $('#dashFromDatePicker').datetimepicker({ icons: { time: 'far fa-clock' }, format: 'DD/MM/YYYY', defaultDate: today });
    $('#dashToDatePicker').datetimepicker({ icons: { time: 'far fa-clock' }, format: 'DD/MM/YYYY', defaultDate: today });

    $('.select2').each(function () {
      $(this).select2({ allowClear: true, placeholder: 'Please Select' });
    });

    // Toggle customer/supplier filter visibility based on type selection
    $('#wsType').on('change', function () {
      var val = $(this).val();
      if (val == 'DISPATCH') {
        $('#wsSupplierWrap').hide();
        $('#wsCustomerWrap').show();
        $('#wsSupplier').val('').trigger('change.select2');
      } else if (val == 'RECEIVING') {
        $('#wsCustomerWrap').hide();
        $('#wsSupplierWrap').show();
        $('#wsCustomer').val('').trigger('change.select2');
      } else {
        $('#wsSupplierWrap').hide();
        $('#wsCustomerWrap').hide();
        $('#wsSupplier').val('').trigger('change.select2');
        $('#wsCustomer').val('').trigger('change.select2');
      }
      loadWholesales();
    });

    $('#wsSupplier, #wsCustomer').on('change', function () {
      loadWholesales();
    });

    $('#pkgProductionLine').on('change', function () {
      loadPackaging();
    });

    $('#ppType').on('change', function () {
      var val = $(this).val();
      if (val == 'OUTGOING') {
        $('#ppSupplierWrap').hide();
        $('#ppCustomerWrap').show();
        $('#ppSupplier').val('').trigger('change.select2');
      } else if (val == 'INCOMING') {
        $('#ppCustomerWrap').hide();
        $('#ppSupplierWrap').show();
        $('#ppCustomer').val('').trigger('change.select2');
      } else {
        $('#ppSupplierWrap').hide();
        $('#ppCustomerWrap').hide();
        $('#ppSupplier').val('').trigger('change.select2');
        $('#ppCustomer').val('').trigger('change.select2');
      }
      loadPulpPaste();
    });

    $('#ppSupplier, #ppCustomer').on('change', function () {
      loadPulpPaste();
    });

    $('#dashSearch').on('click', function () {
      loadAllDashboards();
    });

    // Also reload when switching tabs if not yet loaded
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
      var target = $(e.target).attr('href');
      if (target == '#tabGrading') {
        loadGrading();
      }
      if (target == '#tabPackaging'){
        loadPackaging();
      } 
      if (target == '#tabPulpPaste') {
        loadPulpPaste();
      }
    });

    // Load all on page ready
    loadAllDashboards();
  });

  function getDateParams() {
    return {
      fromDate: $('#dashFromDate').val(),
      toDate: $('#dashToDate').val(),
      location: $('#dashLocation').val() || ''
    };
  }

  function getPkgParams() {
    return $.extend(getDateParams(), {
      productionLine: $('#pkgProductionLine').val() || ''
    });
  }

  function loadAllDashboards() {
    loadWholesales();
    loadGrading();
    loadPackaging();
    loadPulpPaste();
  }

  function loadWholesales() {
    var params = $.extend(getDateParams(), {
      status: $('#wsType').val(),
      customer: $('#wsCustomer').val() || '',
      supplier: $('#wsSupplier').val() || ''
    });

    $.post('php/modules/wholesales/getDashboard.php', params, function (data) {
      var obj = JSON.parse(data);
      if (obj.status !== 'success') return;

      var s = obj.summary;
      var wsType = $('#wsType').val();

      // Update cards visibility
      if (wsType == 'DISPATCH' || wsType == 'STOCK-BAL') {
        $('#wsReceivingCard, #wsReceivingValueCard').hide();
        $('#wsDispatchCard, #wsDispatchValueCard').show();
      } else if (wsType == 'RECEIVING') {
        $('#wsDispatchCard, #wsDispatchValueCard').hide();
        $('#wsReceivingCard, #wsReceivingValueCard').show();
      } else {
        $('#wsReceivingCard, #wsReceivingValueCard').show();
        $('#wsDispatchCard, #wsDispatchValueCard').show();
      }

      $('#wsReceivingWeight').text(formatNum(s.receiving_weight));
      $('#wsReceivingCount').text(s.receiving_count || 0);
      $('#wsReceivingValue').html(formatCurrencyMap(s.receiving_value));
      $('#wsDispatchWeight').text(formatNum(s.dispatch_weight));
      $('#wsDispatchCount').text(s.dispatch_count || 0);
      $('#wsDispatchValue').html(formatCurrencyMap(s.dispatch_value));

      // Volume trend chart — hide the card if there is no data
      var trend = obj.volumeTrend || [];

      if (trend.length == 0) {
        $('#wsTrendWrap').hide();
      } else {
        $('#wsTrendWrap').show();
      }

      var labels   = trend.map(function(d) { return d.date; });
      var recvData = trend.map(function(d) { return d.receiving; });
      var dispData = trend.map(function(d) { return d.dispatch; });

      if (wsTrendChart) {
        wsTrendChart.data.labels = labels;
        wsTrendChart.data.datasets[0].data = recvData;
        wsTrendChart.data.datasets[1].data = dispData;
        wsTrendChart.update();
      } else {
        var ctx = document.getElementById('wsTrendChart').getContext('2d');
        wsTrendChart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [
              {
                label: 'Receiving (kg)',
                data: recvData,
                backgroundColor: 'rgba(23,162,184,0.7)',
                borderColor: '#17a2b8',
                borderWidth: 1
              },
              {
                label: 'Dispatch (kg)',
                data: dispData,
                backgroundColor: 'rgba(40,167,69,0.7)',
                borderColor: '#28a745',
                borderWidth: 1
              }
            ]
          },
          options: {
            responsive: true,
            scales: {
              xAxes: [{ gridLines: { display: false } }],
              yAxes: [{ ticks: { beginAtZero: true } }]
            },
            legend: { position: 'top' },
            tooltips: {
              callbacks: {
                label: function(item, data) {
                  return data.datasets[item.datasetIndex].label + ': ' +
                    parseFloat(item.yLabel).toLocaleString('en-MY', { minimumFractionDigits: 2 }) + ' kg';
                }
              }
            }
          }
        });
      }

      // Supplier breakdown
      if (wsType != 'DISPATCH' && obj.supplierBreakdown.length > 0) {
        $('#wsSupplierBreakdownWrap').show();
        wsSupplierData = obj.supplierBreakdown;
        wsSupplierCurrentPage = 0;
        renderPagedBreakdown('wsSupplierBreakdown', 'wsSupplierPager', 'wsSupplierPageInfo', wsSupplierData, wsSupplierCurrentPage, '#17a2b8');
      } else {
        $('#wsSupplierBreakdownWrap').hide();
        wsSupplierData = [];
      }

      // Customer breakdown
      if (wsType != 'RECEIVING' && obj.customerBreakdown.length > 0) {
        $('#wsCustomerBreakdownWrap').show();
        wsCustomerData = obj.customerBreakdown;
        wsCustomerCurrentPage = 0;
        renderPagedBreakdown('wsCustomerBreakdown', 'wsCustomerPager', 'wsCustomerPageInfo', wsCustomerData, wsCustomerCurrentPage, '#28a745');
      } else {
        $('#wsCustomerBreakdownWrap').hide();
        wsCustomerData = [];
      }

      // Grade distribution — receiving (grouped by product)
      var gradeRecv = obj.gradeDistribution || [];
      if (wsType != 'DISPATCH' && gradeRecv.length > 0) {
        $('#wsGradeRecvWrap').show();
        var recvTotal = gradeRecv.reduce(function(s, p) { return s + p.grades.reduce(function(a, g) { return a + g.weight; }, 0); }, 0);
        $('#wsGradeRecvTotal').text(formatNum(recvTotal) + ' kg');
        renderGradeDist('wsGradeRecvPills', 'wsGradeRecvBars', gradeRecv, 'product', '#17a2b8');
      } else {
        $('#wsGradeRecvWrap').hide();
      }

      // Grade distribution — dispatch (grouped by product)
      var gradeDisp = obj.gradeDistributionDispatch || [];
      if (wsType != 'RECEIVING' && gradeDisp.length > 0) {
        $('#wsGradeDispWrap').show();
        var dispTotal = gradeDisp.reduce(function(s, p) { return s + p.grades.reduce(function(a, g) { return a + g.weight; }, 0); }, 0);
        $('#wsGradeDispTotal').text(formatNum(dispTotal) + ' kg');
        renderGradeDist('wsGradeDispPills', 'wsGradeDispBars', gradeDisp, 'product', '#28a745');
      } else {
        $('#wsGradeDispWrap').hide();
      }

      // Hourly distribution charts
      // Labels for all 24 hours in 12-hour am/pm format
      var hourLabels = ['12am','1am','2am','3am','4am','5am','6am','7am','8am','9am','10am','11am',
                        '12pm','1pm','2pm','3pm','4pm','5pm','6pm','7pm','8pm','9pm','10pm','11pm'];

      var hourlyRecv = obj.hourlyReceiving || [];
      var hourlyDisp = obj.hourlyDispatch  || [];

      // Check if any hour has data — hide the whole row if both are empty
      var hasRecvHourly = wsType != 'DISPATCH' && hourlyRecv.some(function(v) { return v > 0; });
      var hasDispHourly = wsType != 'RECEIVING' && hourlyDisp.some(function(v) { return v > 0; });

      if (!hasRecvHourly && !hasDispHourly) {
        $('#wsHourlyWrap').hide();
      } else {
        $('#wsHourlyWrap').show();
      }

      if (hasRecvHourly) {
        $('#wsHourlyRecvWrap').show();
        if (wsHourlyRecvChart) {
          wsHourlyRecvChart.data.datasets[0].data = hourlyRecv;
          wsHourlyRecvChart.update();
        } else {
          var ctx = document.getElementById('wsHourlyRecvChart').getContext('2d');
          wsHourlyRecvChart = new Chart(ctx, {
            type: 'bar',
            data: {
              labels: hourLabels,
              datasets: [{ label: 'Receiving (kg)', data: hourlyRecv, backgroundColor: 'rgba(23,162,184,0.7)', borderColor: '#17a2b8', borderWidth: 1 }]
            },
            options: {
              responsive: true,
              scales: {
                xAxes: [{ gridLines: { display: false } }],
                yAxes: [{ ticks: { beginAtZero: true } }]
              },
              legend: { display: false },
              tooltips: {
                callbacks: {
                  label: function(item) {
                    return parseFloat(item.yLabel).toLocaleString('en-MY', { minimumFractionDigits: 2 }) + ' kg';
                  }
                }
              }
            }
          });
        }
      } else {
        $('#wsHourlyRecvWrap').hide();
      }

      if (hasDispHourly) {
        $('#wsHourlyDispWrap').show();
        if (wsHourlyDispChart) {
          wsHourlyDispChart.data.datasets[0].data = hourlyDisp;
          wsHourlyDispChart.update();
        } else {
          var ctx = document.getElementById('wsHourlyDispChart').getContext('2d');
          wsHourlyDispChart = new Chart(ctx, {
            type: 'bar',
            data: {
              labels: hourLabels,
              datasets: [{ label: 'Dispatch (kg)', data: hourlyDisp, backgroundColor: 'rgba(40,167,69,0.7)', borderColor: '#28a745', borderWidth: 1 }]
            },
            options: {
              responsive: true,
              scales: {
                xAxes: [{ gridLines: { display: false } }],
                yAxes: [{ ticks: { beginAtZero: true } }]
              },
              legend: { display: false },
              tooltips: {
                callbacks: {
                  label: function(item) {
                    return parseFloat(item.yLabel).toLocaleString('en-MY', { minimumFractionDigits: 2 }) + ' kg';
                  }
                }
              }
            }
          });
        }
      } else {
        $('#wsHourlyDispWrap').hide();
      }
    });
  }

  function loadGrading() {
    $.post('php/modules/grading/getDashboard.php', getDateParams(), function (data) {
      var obj = JSON.parse(data);
      if (obj.status !== 'success') return;

      var s = obj.summary;
      $('#grTotalNet').text(formatNum(s.total_net));
      $('#grSessionCount').text(s.session_count || 0);

      var items = obj.productGradeBreakdown || [];
      if (items.length == 0) {
        $('#grProductBreakdown').html('<p class="text-muted">No data.</p>');
        return;
      }

      // Group by product
      var grouped = {};
      var grandTotal = 0;
      items.forEach(function (item) {
        var p = item.product_name || '—';
        if (!grouped[p]) grouped[p] = { total: 0, grades: [] };
        grouped[p].total += parseFloat(item.total_weight) || 0;
        grouped[p].grades.push(item);
        grandTotal += parseFloat(item.total_weight) || 0;
      });

      var html = '';
      var idx = 0;
      Object.keys(grouped).forEach(function (product) {
        var g = grouped[product];
        var pct = grandTotal > 0 ? (g.total / grandTotal * 100).toFixed(1) : 0;
        html += '<div class="card mb-2 shadow-sm">' +
          '<div class="card-header py-2 px-3 gr-product-row" data-idx="' + idx + '" style="cursor:pointer;background:#f4f6f9;">' +
            '<div class="d-flex justify-content-between align-items-center">' +
              '<div>' +
                '<i class="fas fa-chevron-right gr-chevron mr-2" style="font-size:11px;color:#6c757d;"></i>' +
                '<strong>' + product + '</strong>' +
              '</div>' +
              '<div class="text-right">' +
                '<span class="badge badge-secondary mr-2">' + pct + '%</span>' +
                '<span class="font-weight-bold">' + formatNum(g.total) + ' kg</span>' +
              '</div>' +
            '</div>' +
            '<div class="mt-1">' +
              '<div style="background:#dee2e6;border-radius:4px;height:6px;">' +
                '<div style="width:' + pct + '%;background:#6f42c1;border-radius:4px;height:6px;"></div>' +
              '</div>' +
            '</div>' +
          '</div>' +
          '<div class="gr-grade-rows" id="gr-grades-' + idx + '" style="display:none;">' +
            '<div class="card-body py-2 px-3">';

        g.grades.forEach(function (grade) {
          var gPct = g.total > 0 ? (parseFloat(grade.total_weight) / g.total * 100).toFixed(1) : 0;
          html += '<div class="d-flex justify-content-between align-items-center py-1">' +
            '<span class="text-muted" style="font-size:13px;"><i class="fas fa-tag mr-1" style="font-size:10px;"></i>' + (grade.grade_name || '—') + '</span>' +
            '<span style="font-size:13px;">' + formatNum(grade.total_weight) + ' kg <span class="text-muted">(' + gPct + '%)</span></span>' +
          '</div>';
        });

        html += '</div></div></div>';
        idx++;
      });

      $('#grProductBreakdown').html(html);

      // Toggle expand
      $('#grProductBreakdown').off('click', '.gr-product-row').on('click', '.gr-product-row', function () {
        var i = $(this).data('idx');
        var $grades = $('#gr-grades-' + i);
        var $icon = $(this).find('.gr-chevron');
        $grades.slideToggle(150);
        $icon.toggleClass('fa-chevron-right fa-chevron-down');
      });
    });
  }

  function loadPackaging() {
    $.post('php/modules/packagingBatches/getDashboard.php', getPkgParams(), function (data) {
      var obj = JSON.parse(data);
      if (obj.status !== 'success') return;

      var s = obj.summary;
      $('#pkgTotalWeight').text(formatNum(s.total_weight));
      $('#pkgTotalBoxes').text(s.total_boxes || 0);
      $('#pkgBatchCount').text(s.batch_count || 0);

      var items = obj.productBreakdown || [];
      if (items.length == 0) {
        $('#pkgProductBreakdown').html('<p class="text-muted">No data.</p>');
        return;
      }

      var grandTotal = items.reduce(function(sum, i) { return sum + (parseFloat(i.total_weight) || 0); }, 0);
      var html = '';

      items.forEach(function (item, idx) {
        var pct = grandTotal > 0 ? (parseFloat(item.total_weight) / grandTotal * 100).toFixed(1) : 0;
        html += '<div class="card mb-2 shadow-sm">' +
          '<div class="card-header py-2 px-3 pkg-product-row" data-idx="' + idx + '" style="cursor:pointer;background:#f4f6f9;">' +
            '<div class="d-flex justify-content-between align-items-center">' +
              '<div>' +
                '<i class="fas fa-chevron-right pkg-chevron mr-2" style="font-size:11px;color:#6c757d;"></i>' +
                '<strong>' + item.product_name + '</strong>' +
              '</div>' +
              '<div class="text-right">' +
                '<span class="badge badge-secondary mr-2">' + pct + '%</span>' +
                '<span class="font-weight-bold">' + formatNum(item.total_weight) + ' kg</span>' +
                '<span class="text-muted ml-2" style="font-size:12px;">(' + item.total_boxes + ' boxes)</span>' +
              '</div>' +
            '</div>' +
            '<div class="mt-1">' +
              '<div style="background:#dee2e6;border-radius:4px;height:6px;">' +
                '<div style="width:' + pct + '%;background:#007bff;border-radius:4px;height:6px;"></div>' +
              '</div>' +
            '</div>' +
          '</div>' +
          '<div class="pkg-grade-rows" id="pkg-grades-' + idx + '" style="display:none;">' +
            '<div class="card-body py-2 px-3">';

        (item.grades || []).forEach(function (grade) {
          var gPct = item.total_weight > 0 ? (parseFloat(grade.total_weight) / item.total_weight * 100).toFixed(1) : 0;
          html += '<div class="d-flex justify-content-between align-items-center py-1">' +
            '<span class="text-muted" style="font-size:13px;">' +
              '<i class="fas fa-tag mr-1" style="font-size:10px;"></i>' + grade.grade_name +
              ' <span class="badge badge-light border">' + grade.packaging_name + '</span>' +
            '</span>' +
            '<span style="font-size:13px;">' + formatNum(grade.total_weight) + ' kg' +
              ' <span class="text-muted">(' + gPct + '%)</span>' +
              ' &nbsp;|&nbsp; ' + grade.total_boxes + ' boxes' +
            '</span>' +
          '</div>';
        });

        html += '</div></div></div>';
      });

      $('#pkgProductBreakdown').html(html);

      $('#pkgProductBreakdown').off('click', '.pkg-product-row').on('click', '.pkg-product-row', function () {
        var i = $(this).data('idx');
        $('#pkg-grades-' + i).slideToggle(150);
        $(this).find('.pkg-chevron').toggleClass('fa-chevron-right fa-chevron-down');
      });
    });
  }

  function loadPulpPaste() {
    var ppType = $('#ppType').val();
    var params = $.extend(getDateParams(), {
      status: ppType,
      supplier: $('#ppSupplier').val() || '',
      customer: $('#ppCustomer').val() || ''
    });
    $.post('php/modules/industrial/getDashboard.php', params, function (data) {
      var obj = JSON.parse(data);
      if (obj.status !== 'success') return;

      var s = obj.summary;

      if (ppType == 'OUTGOING') {
        $('#ppIncomingCard').hide();
        $('#ppOutgoingCard').show();
      } else if (ppType == 'INCOMING') {
        $('#ppOutgoingCard').hide();
        $('#ppIncomingCard').show();
      } else {
        $('#ppIncomingCard').show();
        $('#ppOutgoingCard').show();
      }

      $('#ppIncomingWeight').text(formatNum(s.incoming_weight));
      $('#ppIncomingCount').text(s.incoming_count || 0);
      $('#ppOutgoingWeight').text(formatNum(s.outgoing_weight));
      $('#ppOutgoingCount').text(s.outgoing_count || 0);

      // Volume trend chart
      var trend = obj.volumeTrend || [];
      var labels   = trend.map(function(d) { return d.date; });
      var inData   = trend.map(function(d) { return d.incoming; });
      var outData  = trend.map(function(d) { return d.outgoing; });

      if (ppTrendChart) {
        ppTrendChart.data.labels = labels;
        ppTrendChart.data.datasets[0].data = inData;
        ppTrendChart.data.datasets[1].data = outData;
        ppTrendChart.update();
      } else {
        var ctx = document.getElementById('ppTrendChart').getContext('2d');
        ppTrendChart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [
              { label: 'Incoming (kg)', data: inData, backgroundColor: 'rgba(253,126,20,0.7)', borderColor: '#fd7e14', borderWidth: 1 },
              { label: 'Outgoing (kg)', data: outData, backgroundColor: 'rgba(32,201,151,0.7)', borderColor: '#20c997', borderWidth: 1 }
            ]
          },
          options: {
            responsive: true,
            scales: {
              xAxes: [{ gridLines: { display: false } }],
              yAxes: [{ ticks: { beginAtZero: true } }]
            },
            legend: { position: 'top' },
            tooltips: {
              callbacks: {
                label: function(item, data) {
                  return data.datasets[item.datasetIndex].label + ': ' +
                    parseFloat(item.yLabel).toLocaleString('en-MY', { minimumFractionDigits: 2 }) + ' kg';
                }
              }
            }
          }
        });
      }

      // Supplier breakdown
      if (ppType != 'OUTGOING' && obj.supplierBreakdown && obj.supplierBreakdown.length > 0) {
        $('#ppSupplierBreakdownWrap').show();
        ppSupplierData = obj.supplierBreakdown;
        ppSupplierCurrentPage = 0;
        renderPagedBreakdown('ppSupplierBreakdown', 'ppSupplierPager', 'ppSupplierPageInfo', ppSupplierData, ppSupplierCurrentPage, '#fd7e14');
      } else {
        $('#ppSupplierBreakdownWrap').hide();
        ppSupplierData = [];
      }

      // Customer breakdown
      if (ppType != 'INCOMING' && obj.customerBreakdown && obj.customerBreakdown.length > 0) {
        $('#ppCustomerBreakdownWrap').show();
        ppCustomerData = obj.customerBreakdown;
        ppCustomerCurrentPage = 0;
        renderPagedBreakdown('ppCustomerBreakdown', 'ppCustomerPager', 'ppCustomerPageInfo', ppCustomerData, ppCustomerCurrentPage, '#20c997');
      } else {
        $('#ppCustomerBreakdownWrap').hide();
        ppCustomerData = [];
      }
    });
  }

  function ppSupplierPage(dir) {
    var totalPages = Math.ceil(ppSupplierData.length / WS_PAGE_SIZE);
    ppSupplierCurrentPage = Math.max(0, Math.min(ppSupplierCurrentPage + dir, totalPages - 1));
    renderPagedBreakdown('ppSupplierBreakdown', 'ppSupplierPager', 'ppSupplierPageInfo', ppSupplierData, ppSupplierCurrentPage, '#fd7e14');
  }

  function ppCustomerPage(dir) {
    var totalPages = Math.ceil(ppCustomerData.length / WS_PAGE_SIZE);
    ppCustomerCurrentPage = Math.max(0, Math.min(ppCustomerCurrentPage + dir, totalPages - 1));
    renderPagedBreakdown('ppCustomerBreakdown', 'ppCustomerPager', 'ppCustomerPageInfo', ppCustomerData, ppCustomerCurrentPage, '#20c997');
  }

  function wsSupplierPage(dir) {
    var totalPages = Math.ceil(wsSupplierData.length / WS_PAGE_SIZE);
    wsSupplierCurrentPage = Math.max(0, Math.min(wsSupplierCurrentPage + dir, totalPages - 1));
    renderPagedBreakdown('wsSupplierBreakdown', 'wsSupplierPager', 'wsSupplierPageInfo', wsSupplierData, wsSupplierCurrentPage, '#17a2b8');
  }

  function wsCustomerPage(dir) {
    var totalPages = Math.ceil(wsCustomerData.length / WS_PAGE_SIZE);
    wsCustomerCurrentPage = Math.max(0, Math.min(wsCustomerCurrentPage + dir, totalPages - 1));
    renderPagedBreakdown('wsCustomerBreakdown', 'wsCustomerPager', 'wsCustomerPageInfo', wsCustomerData, wsCustomerCurrentPage, '#28a745');
  }

  function renderPagedBreakdown(containerId, pagerId, pageInfoId, items, page, color) {
    var totalPages = Math.ceil(items.length / WS_PAGE_SIZE);
    var start = page * WS_PAGE_SIZE;
    var pageItems = items.slice(start, start + WS_PAGE_SIZE);

    var maxVal = Math.max.apply(null, items.map(function(i) { return parseFloat(i.total_weight) || 0; }));
    var totalVal = items.reduce(function(sum, i) { return sum + (parseFloat(i.total_weight) || 0); }, 0);

    $('#' + containerId).html(renderBreakdownItems(pageItems, maxVal, totalVal, color));

    if (totalPages > 1) {
      $('#' + pagerId).show();
      $('#' + pageInfoId).text((page + 1) + ' / ' + totalPages);
      $('#' + pagerId + ' button:first').prop('disabled', page === 0);
      $('#' + pagerId + ' button:last').prop('disabled', page >= totalPages - 1);
    } else {
      $('#' + pagerId).hide();
    }
  }

  function renderBreakdownItems(items, maxVal, totalVal, color) {
    if (!items || items.length == 0) return '<p class="text-muted">No data.</p>';
    var html = '';
    items.forEach(function(item) {
      var val = parseFloat(item.total_weight) || 0;
      var pct = maxVal > 0 ? (val / maxVal * 100).toFixed(1) : 0;
      var sharePct = totalVal > 0 ? (val / totalVal * 100).toFixed(0) : 0;
      html += '<div class="breakdown-bar-wrap">' +
        '<div class="breakdown-bar-label">' +
          '<span>' + (item.name || 'Unknown') + '</span>' +
          '<span>' + formatNum(val) + ' kg (' + sharePct + '%)</span>' +
        '</div>' +
        '<div class="breakdown-bar-track">' +
          '<div class="breakdown-bar-fill" style="width:' + pct + '%;background:' + color + ';"></div>' +
        '</div>' +
      '</div>';
    });
    return html;
  }

  function renderBreakdown(items, color) {
    if (!items || items.length == 0) return '<p class="text-muted">No data.</p>';

    var maxVal = Math.max.apply(null, items.map(function (i) { return parseFloat(i.total_weight) || 0; }));
    var totalVal = items.reduce(function (sum, i) { return sum + (parseFloat(i.total_weight) || 0); }, 0);
    var html = '';

    items.forEach(function (item) {
      var val = parseFloat(item.total_weight) || 0;
      var pct = maxVal > 0 ? (val / maxVal * 100).toFixed(1) : 0;
      var sharePct = totalVal > 0 ? (val / totalVal * 100).toFixed(0) : 0;
      html += '<div class="breakdown-bar-wrap">' +
        '<div class="breakdown-bar-label">' +
          '<span>' + (item.name || 'Unknown') + '</span>' +
          '<span>' + formatNum(val) + ' kg (' + sharePct + '%)</span>' +
        '</div>' +
        '<div class="breakdown-bar-track">' +
          '<div class="breakdown-bar-fill" style="width:' + pct + '%;background:' + color + ';"></div>' +
        '</div>' +
      '</div>';
    });

    return html;
  }

  function renderGradeDist(pillsId, barsId, groups, groupKey, color) {
    // Build pill buttons for each group; clicking filters bars
    var $pills = $('#' + pillsId);
    var $bars  = $('#' + barsId);
    $pills.empty();

    // Aggregate all grades across groups for the "All" view
    var allGrades = {};
    groups.forEach(function(g) {
      g.grades.forEach(function(gr) {
        allGrades[gr.name] = (allGrades[gr.name] || 0) + gr.weight;
      });
    });

    function renderBars(grades) {
      var total = Object.values(grades).reduce(function(s, v) { return s + v; }, 0);
      var max   = Math.max.apply(null, Object.values(grades));
      var html  = '';
      Object.keys(grades).sort(function(a, b) { return grades[b] - grades[a]; }).forEach(function(name) {
        var w    = grades[name];
        var pct  = max > 0 ? (w / max * 100).toFixed(1) : 0;
        var share = total > 0 ? (w / total * 100).toFixed(0) : 0;
        html += '<div class="breakdown-bar-wrap">' +
          '<div class="breakdown-bar-label"><span>' + name + '</span><span>' + formatNum(w) + ' kg (' + share + '%)</span></div>' +
          '<div class="breakdown-bar-track"><div class="breakdown-bar-fill" style="width:' + pct + '%;background:' + color + ';"></div></div>' +
        '</div>';
      });
      $bars.html(html || '<p class="text-muted">No data.</p>');
    }

    // "All" pill
    var $all = $('<button class="btn btn-sm btn-secondary active mr-1 mb-1">All</button>');
    $all.on('click', function() {
      $pills.find('button').removeClass('active btn-secondary').addClass('btn-outline-secondary');
      $(this).removeClass('btn-outline-secondary').addClass('btn-secondary active');
      renderBars(allGrades);
    });
    $pills.append($all);

    groups.forEach(function(g) {
      var label = g[groupKey] || 'Unknown';
      var $btn = $('<button class="btn btn-sm btn-outline-secondary mr-1 mb-1"></button>').text(label);
      $btn.on('click', function() {
        $pills.find('button').removeClass('active btn-secondary').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-secondary active');
        var gradeObj = {};
        g.grades.forEach(function(gr) { gradeObj[gr.name] = gr.weight; });
        renderBars(gradeObj);
      });
      $pills.append($btn);
    });

    renderBars(allGrades);
  }

  function toggleCard(bodyId, chevronId) {
    var $body    = $('#' + bodyId);
    var $chevron = $('#' + chevronId);
    $body.slideToggle(150, function() {
      if ($body.is(':visible')) {
        $chevron.removeClass('fa-chevron-right').addClass('fa-chevron-down');
      } else {
        $chevron.removeClass('fa-chevron-down').addClass('fa-chevron-right');
      }
    });
  }

  function formatCurrencyMap(map) {
    if (!map || typeof map !== 'object') return '—';
    var keys = Object.keys(map);
    if (keys.length === 0) return '—';
    return keys.map(function(cur) {
      var n = parseFloat(map[cur]) || 0;
      return (cur || '?') + ' ' + n.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }).join('<br>');
  }

  function formatNum(val) {
    var n = parseFloat(val) || 0;
    return n.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
</script>
