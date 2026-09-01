<?php
require_once 'php/db_connect.php';
require_once 'php/lookup.php';

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
}
else{
  $user = $_SESSION['userID'];
  $company = $_SESSION['customer'];
  $module = $_SESSION['module'];
  $role = $_SESSION['role'] ?? 'NORMAL';
  $userAllowAdd = $_SESSION['userAllowAdd'] ?? 'N';
  $userAllowEdit = $_SESSION['userAllowEdit'] ?? 'N';
  $userAllowDelete = $_SESSION['userAllowDelete'] ?? 'N';
  $userAllowPrice = $_SESSION['userAllowPrice'] ?? 'N';
  $userLocationId = $_SESSION['userLocationId'] ?? null;
  $userModuleAccess = $_SESSION['userModuleAccess'];
  $categoryIds = [];
  if (!empty($userModuleAccess['categories'])) {
    $allowedModules = ['wholesale', 'processing'];
    foreach ($userModuleAccess['categories'] as $module => $moduleCategories) {
      if (in_array($module, $allowedModules)) {
        $categoryIds = array_merge($categoryIds, $moduleCategories);
      }
    }
    $categoryIds = array_unique($categoryIds);
  }

  $allowPhoto = 'N';

  if ($role != 'SADMIN'){
    $categoryFilter = !empty($categoryIds) ? " AND c.id IN (" . implode(',', array_map('intval', $categoryIds)) . ")" : "";
    $categories = $db->query("SELECT * FROM categories c WHERE c.deleted = '0' AND c.customer = '$company' AND c.module IN ('wholesale', 'processing')$categoryFilter ORDER BY c.category_name ASC");
    $categories2 = $db->query("SELECT * FROM categories c WHERE c.deleted = '0' AND c.customer = '$company' AND c.module IN ('wholesale', 'processing')$categoryFilter ORDER BY c.category_name ASC");
    $categories3 = $db->query("SELECT * FROM categories c WHERE c.deleted = '0' AND c.customer = '$company' AND c.module IN ('wholesale', 'processing')$categoryFilter ORDER BY c.category_name ASC");
    $productQuery = "SELECT p.* FROM products p INNER JOIN categories c ON p.category = c.id WHERE p.deleted = '0' AND p.customer = '$company' AND c.module IN ('wholesale', 'processing') AND c.deleted = '0'$categoryFilter ORDER BY p.product_name ASC";   
    $productCheck = $db->query($productQuery);
    if ($productCheck->num_rows == 0) {
      $productQuery = "SELECT * FROM products WHERE deleted = '0' AND customer = '$company' ORDER BY product_name ASC";
    }
    $products = $db->query($productQuery);
    $products2 = $db->query($productQuery);
    $products3 = $db->query($productQuery);
    $products4 = $db->query($productQuery);
    $grades = $db->query("SELECT DISTINCT g.*, pg.product_id, pg.type AS grade_type FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY p.product_name ASC, g.units ASC");
    $grades2 = $db->query("SELECT DISTINCT g.*, pg.product_id, pg.type AS grade_type FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY p.product_name ASC, g.units ASC");
    $grades3 = $db->query("SELECT DISTINCT g.*, pg.product_id, pg.type AS grade_type FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY p.product_name ASC, g.units ASC");
    $grades4 = $db->query("SELECT DISTINCT g.*, pg.product_id, pg.type AS grade_type FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY p.product_name ASC, g.units ASC");
    $users = $db->query("SELECT * FROM users WHERE deleted = '0' AND customer = '$company' ORDER BY name ASC");
    $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' AND customer = '$company' ORDER BY locations ASC");
    $locations2 = $db->query("SELECT * FROM locations WHERE deleted = '0' AND customer = '$company' ORDER BY locations ASC");
    $productionLines = $db->query("SELECT * FROM production_lines WHERE deleted = '0' AND customers = '$company' ORDER BY production_line ASC");
    $productionLines2 = $db->query("SELECT * FROM production_lines WHERE deleted = '0' AND customers = '$company' ORDER BY production_line ASC");

    $packagings = $db->query("SELECT * FROM packaging WHERE deleted = '0' AND customer = '$company' AND packaging_type = 'Original' ORDER BY packaging_name ASC");
    $packagings2 = $db->query("SELECT * FROM packaging WHERE deleted = '0' AND customer = '$company' AND packaging_type = 'Original' ORDER BY packaging_name ASC");
    $packagings3 = $db->query("SELECT * FROM packaging WHERE deleted = '0' AND customer = '$company' AND packaging_type = 'Original' ORDER BY packaging_name ASC");
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' AND customer = '$company' ORDER BY customer_name ASC");
    $shipmentTypes = $db->query("SELECT * FROM shipment_types WHERE deleted = '0' AND customer = '$company' ORDER BY shipment_type ASC");
    $supplies = $db->query("SELECT * FROM supplies WHERE deleted = '0' AND customer = '$company' ORDER BY supplier_name ASC");

    // Feature Flagging
    $allowPhoto = $_SESSION['featureFlags']['include_photo'] ?? 'N';
  } else {
    $categories = $db->query("SELECT * FROM categories WHERE deleted = '0' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $categories2 = $db->query("SELECT * FROM categories WHERE deleted = '0' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $categories3 = $db->query("SELECT * FROM categories WHERE deleted = '0' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $products = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $products2 = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $products3 = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $products4 = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $grades = $db->query("SELECT DISTINCT g.*, pg.product_id, pg.type AS grade_type FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC");
    $grades2 = $db->query("SELECT DISTINCT g.*, pg.product_id, pg.type AS grade_type FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC");
    $grades3 = $db->query("SELECT DISTINCT g.*, pg.product_id, pg.type AS grade_type FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC");
    $grades4 = $db->query("SELECT DISTINCT g.*, pg.product_id, pg.type AS grade_type FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC");
    $users = $db->query("SELECT * FROM users WHERE deleted = '0' ORDER BY name ASC");
    $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' ORDER BY locations ASC");
    $locations2 = $db->query("SELECT * FROM locations WHERE deleted = '0' ORDER BY locations ASC");
    $productionLines = $db->query("SELECT * FROM production_lines WHERE deleted = '0' ORDER BY production_line ASC");
    $productionLines2 = $db->query("SELECT * FROM production_lines WHERE deleted = '0' ORDER BY production_line ASC");

    $packagings = $db->query("SELECT * FROM packaging WHERE deleted = '0' AND packaging_type = 'Original' ORDER BY packaging_name ASC");
    $packagings2 = $db->query("SELECT * FROM packaging WHERE deleted = '0' AND packaging_type = 'Original' ORDER BY packaging_name ASC");
    $packagings3 = $db->query("SELECT * FROM packaging WHERE deleted = '0' AND packaging_type = 'Original' ORDER BY packaging_name ASC");
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' ORDER BY customer_name ASC");
    $shipmentTypes = $db->query("SELECT * FROM shipment_types WHERE deleted = '0' ORDER BY shipment_type ASC");
    $supplies = $db->query("SELECT * FROM supplies WHERE deleted = '0' ORDER BY supplier_name ASC");

    $allowPhoto = 'Y';
  }

  $units = $db->query("SELECT * FROM units WHERE deleted = '0'");
  $units1 = $db->query("SELECT * FROM units WHERE deleted = '0'");
  
  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>

<!-- Main content -->
<div class="content page-modern">
  <div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title"><i class="fas fa-boxes"></i> <?=$languageArray['batch_packaging_code'][$language]?></h1>
    </div>

    <!-- Filter Card -->
    <div class="card filter-card">
      <div class="card-body">
        <div class="filter-row">
          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['from_date_code'][$language]?></label>
            <div class="input-group date" id="fromDatePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#fromDatePicker" id="fromDate"/>
              <div class="input-group-append" data-target="#fromDatePicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['to_date_code'][$language]?></label>
            <div class="input-group date" id="toDatePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#toDatePicker" id="toDate"/>
              <div class="input-group-append" data-target="#toDatePicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['locations_code'][$language]?></label>
            <select class="form-control select2" id="locationFilter" name="locationFilter">
              <option value="" disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
              <?php
              $firstLocation = null;
              while($rowLocation=mysqli_fetch_assoc($locations)){ 
                if(!$firstLocation) $firstLocation = $rowLocation;
              ?>
                <option value="<?=$rowLocation['id'] ?>" <?= $firstLocation && $rowLocation['id'] == $firstLocation['id'] ? 'selected' : '' ?>><?=$rowLocation['locations'] ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['production_lines_code'][$language]?></label>
            <select class="form-control select2" id="productionLineFilter" name="productionLineFilter">
              <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
              <?php while($rowProdLine=mysqli_fetch_assoc($productionLines)){ ?>
                <option value="<?=$rowProdLine['id'] ?>"><?=$rowProdLine['production_line'] ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['category_code'][$language]?></label>
            <select class="form-control select2" id="categoryFilter" name="categoryFilter">
              <option value="all"><?=$languageArray['all_code'][$language] ?? 'All'?></option>
              <?php while($rowCatFilter=mysqli_fetch_assoc($categories3)){ ?>
                <option value="<?=$rowCatFilter['id']?>"><?=$rowCatFilter['category_name']?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group filter-group-action" style="margin-left:auto;">
            <label class="filter-label">&nbsp;</label>
            <button type="button" class="btn btn-filter btn-filter-primary" id="filterSearch">
              <i class="fas fa-search"></i> <?=$languageArray['search_code'][$language]?>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Results Card -->
    <div class="card results-card show-dt-controls">
      <div class="card-header">
        <div class="results-header-left">
          <h3 class="results-title"><i class="fas fa-list"></i> <?=$languageArray['batch_packaging_code'][$language]?></h3>
        </div>
        <div class="results-header-right">
          <?php if($userAllowAdd == 'Y'){ ?>
          <button type="button" class="btn btn-action btn-action-primary" onclick="newEntry()">
            <i class="fas fa-plus"></i> <?=$languageArray['add_new_code'][$language]?>
          </button>
          <?php } ?>
        </div>
      </div>

      <div class="card-body">
        <table id="weightTable" class="table data-table">
          <thead>
            <tr>
              <th><?=$languageArray['batch_no_code'][$language]?></th>
              <th><?=$languageArray['packaging_date_code'][$language]?></th>
              <th><?=$languageArray['locations_code'][$language]?></th>
              <th><?=$languageArray['production_lines_code'][$language]?></th>
              <th><?=$languageArray['status_code'][$language]?></th>
              <th style="width:120px;"><?=$languageArray['actions_code'][$language]?></th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade modal-modern" id="extendModal">
  <div class="modal-dialog modal-xl" style="max-width: 1700px;">
    <div class="modal-content">
      <form role="form" id="extendForm" novalidate>
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-boxes mr-2 text-muted"></i><?=$languageArray['add_new_entry_code'][$language]?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <input type="hidden" class="form-control" id="id" name="id">

          <!-- Order Information Section -->
          <div class="modal-section">
            <h6 class="section-title"><i class="fas fa-info-circle mr-2"></i><?=$languageArray['order_information_code'][$language] ?? 'Order Information'?></h6>
            <div class="row">
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['batch_no_code'][$language]?> <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="batchNo" name="batchNo" readonly>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['packaging_date_code'][$language]?> <span class="text-danger">*</span></label>
                  <div class="input-group date" id="packagingDatePicker" data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input" data-target="#packagingDatePicker" id="packagingDate" name="packagingDate" required/>
                    <div class="input-group-append" data-target="#packagingDatePicker" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['locations_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control select2" id="location" name="location">
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($rowLocation=mysqli_fetch_assoc($locations2)){ ?>
                      <option value="<?=$rowLocation['id'] ?>"><?=$rowLocation['locations'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['production_lines_code'][$language]?></label>
                  <select class="form-control select2" id="productionLines" name="productionLines">
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($rowProdLine=mysqli_fetch_assoc($productionLines2)){ ?>
                      <option value="<?=$rowProdLine['id'] ?>"><?=$rowProdLine['production_line'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['type_code'][$language] ?? 'Type'?></label>
                  <select class="form-control" id="gradeType" name="gradeType">
                    <option value="Local" selected><?=$languageArray['local_code'][$language] ?? 'Local'?></option>
                    <option value="Export"><?=$languageArray['export_code'][$language] ?? 'Export'?></option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Remarks Section -->
          <div class="modal-section">
            <h6 class="section-title"><i class="fas fa-comment-alt mr-2"></i><?=$languageArray['remark_code'][$language]?></h6>
            <div class="row">
              <div class="col-md-12">
                <div class="form-group-modern">
                  <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="<?=$languageArray['enter_remark_code'][$language]?>"></textarea>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Weight Details Section -->
          <div class="modal-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="section-title mb-0"><i class="fas fa-balance-scale mr-2"></i><?=$languageArray['weight_details_code'][$language]?></h6>
              <div class="d-flex align-items-center" style="gap:0.5rem;">
                <button type="button" class="btn btn-modern btn-modern-primary btn-sm" id="addWeightBtn">
                  <i class="fas fa-plus mr-1"></i><?=$languageArray['add_weight_code'][$language]?>
                </button>
                <button type="button" class="btn btn-modern btn-modern-secondary btn-sm" id="bulkAddBtn">
                  <i class="fas fa-layer-group mr-1"></i><?=$languageArray['bulk_add_code'][$language]?>
                </button>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-hover mb-0" style="font-size:0.72rem;">
                <thead class="thead-light">
                  <tr class="text-center">
                    <th style="width:10%;"><?=$languageArray['supplier_code'][$language]?></th>
                    <th style="width:10%;"><?=$languageArray['category_code'][$language]?></th>
                    <th style="width:10%;"><?=$languageArray['product_code'][$language]?></th>
                    <th style="width:8%;"><?=$languageArray['grade_code'][$language]?></th>
                    <th style="width:10%;"><?=$languageArray['packaging_size_code'][$language]?></th>
                    <th style="width:8%;"><?=$languageArray['label_code'][$language]?></th>
                    <th style="width:5%;"><?=$languageArray['unit_per_box_code'][$language]?></th>
                    <th style="width:5%;"><?=$languageArray['gross_code'][$language]?></th>
                    <th style="width:5%;"><?=$languageArray['tare_code'][$language]?></th>
                    <th style="width:5%;"><?=$languageArray['weight_code'][$language]?></th>
                    <th style="width:8%;"><?=$languageArray['time_code'][$language]?></th>
                    <?php if($allowPhoto == 'Y') { ?>
                    <th style="width:4%;"><?=$languageArray['photo_code'][$language]?></th>
                    <?php } ?>
                    <th style="width:6%;"><?=$languageArray['actions_code'][$language]?></th>
                  </tr>
                </thead>
                <tbody id="weightDetailsTable"></tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-modern btn-modern-primary" id="saveButton"><i class="fas fa-save mr-1"></i><?=$languageArray['save_code'][$language]?></button>
        </div>
      </form>
    </div> <!-- /.modal-content -->
  </div> <!-- /.modal-dialog -->
</div> <!-- /.modal -->   

<div class="modal fade modal-modern" id="bulkAddModal">
  <div class="modal-dialog" style="max-width:500px;">
    <div class="modal-content">
      <form id="bulkAddForm" novalidate>
      <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff;">
        <h5 class="modal-title"><i class="fas fa-layer-group mr-2"></i><?=$languageArray['bulk_add_code'][$language]?></h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="background: #f8f9ff;">
        <div class="form-group-modern">
          <label class="form-label-modern"><?=$languageArray['bulk_no_code'][$language]?> <span class="text-danger">*</span></label>
          <input type="number" class="form-control" id="bulkNo" min="1" value="1" required>
        </div>
        <div class="form-group-modern">
          <label class="form-label-modern"><?=$languageArray['category_code'][$language]?> <span class="text-danger">*</span></label>
          <select class="form-control select2" id="bulkCategory" required>
            <option value="" selected disabled>Select Category</option>
          </select>
          <div class="invalid-feedback"><?=$languageArray['please_select_category_code'][$language]?></div>
        </div>
        <div class="form-group-modern">
          <label class="form-label-modern"><?=$languageArray['product_code'][$language]?> <span class="text-danger">*</span></label>
          <select class="form-control select2" id="bulkProduct" required>
            <option value="" selected disabled>Select Product</option>
          </select>
          <div class="invalid-feedback"><?=$languageArray['please_select_product_code'][$language]?></div>
        </div>
        <div class="form-group-modern">
          <label class="form-label-modern"><?=$languageArray['grade_code'][$language]?> <span class="text-danger">*</span></label>
          <select class="form-control select2" id="bulkGrade" required>
          </select>
          <div class="invalid-feedback"><?=$languageArray['please_select_grade_code'][$language]?></div>
        </div>
        <div class="form-group-modern">
          <label class="form-label-modern"><?=$languageArray['packaging_size_code'][$language]?> <span class="text-danger">*</span></label>
          <select class="form-control select2" id="bulkPackagingSize" required>
            <option value="" selected disabled>Select Packaging</option>
          </select>
          <div class="invalid-feedback"><?=$languageArray['please_select_packaging_size_code'][$language]?></div>
        </div>
        <div class="form-group-modern">
          <label class="form-label-modern"><?=$languageArray['unit_per_box_code'][$language]?> <span class="text-danger">*</span></label>
          <input type="number" class="form-control" id="bulkUnitPerBox" step="1" value="0" min="1" required>
        </div>
        <div class="form-group-modern">
          <label class="form-label-modern"><?=$languageArray['weight_code'][$language]?> <span class="text-danger">*</span></label>
          <input type="number" class="form-control" id="bulkWeight" step="0.01" value="0.00" min="0.01" required>
        </div>
        <div class="form-group-modern">
          <label class="form-label-modern"><?=$languageArray['time_code'][$language]?> <span class="text-danger">*</span></label>
          <input type="time" class="form-control" id="bulkTime" required>
        </div>
      </div>
      <div class="modal-footer" style="background: #f8f9ff; border-top: 1px solid #e0e4f5;">
        <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
        <button type="submit" class="btn btn-modern" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none;" id="bulkAddSubmit"><i class="fas fa-plus mr-1"></i><?=$languageArray['add_code'][$language]?></button>
      </div>
    </div>
      </form>
  </div>
</div>

<div class="modal fade modal-modern" id="shipmentModal">
  <div class="modal-dialog" style="max-width: 600px;">
    <div class="modal-content">
      <form role="form" id="shipmentForm" novalidate>
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-shipping-fast mr-2 text-muted"></i><?=$languageArray['shipment_code'][$language]?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="shipmentBatchId" name="shipmentBatchId">
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$languageArray['loading_date_code'][$language]?> <span class="text-danger">*</span></label>
            <div class="input-group date" id="shipmentLoadingDatePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#shipmentLoadingDatePicker" id="shipmentLoadingDate" name="shipmentLoadingDate" required/>
              <div class="input-group-append" data-target="#shipmentLoadingDatePicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$languageArray['customer_code'][$language]?> <span class="text-danger">*</span></label>
            <select class="form-control select2" id="shipmentCustomer" name="shipmentCustomer" required>
              <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
              <?php while($rowCustomer = mysqli_fetch_assoc($customers)){ ?>
                <option value="<?=$rowCustomer['id']?>"><?=$rowCustomer['customer_name']?></option>
              <?php } ?>
            </select>
          </div>
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$languageArray['shipment_types_code'][$language]?> <span class="text-danger">*</span></label>
            <select class="form-control select2" id="shipmentType" name="shipmentType" required>
              <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
              <?php while($rowShipment = mysqli_fetch_assoc($shipmentTypes)){ ?>
                <option value="<?=$rowShipment['id']?>"><?=$rowShipment['shipment_type']?></option>
              <?php } ?>
            </select>
          </div>
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$languageArray['remark_code'][$language]?></label>
            <textarea class="form-control" id="shipmentRemark" name="shipmentRemark" rows="2" placeholder="<?=$languageArray['enter_remark_code'][$language]?>"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-modern btn-modern-primary"><i class="fas fa-shipping-fast mr-1"></i><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade modal-modern" id="cancelModal">
  <div class="modal-dialog" style="max-width:500px;">
    <div class="modal-content">
      <form role="form" id="cancelForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-trash-alt mr-2 text-danger"></i><?=$languageArray['delete_reason_code'][$language]?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$languageArray['delete_reason_code'][$language]?> <span class="text-danger">*</span></label>
            <textarea class="form-control" id="cancelReason" name="cancelReason" rows="3" required placeholder="<?=$languageArray['enter_reason_code'][$language] ?? 'Enter reason for deletion...'?>"></textarea>
          </div>
          <input type="hidden" class="form-control" id="id" name="id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-modern btn-modern-danger" id="submitCancel"><i class="fas fa-trash mr-1"></i><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Values
var weightCount = 0;
var allowPhoto = '<?=$allowPhoto?>';
var categoryOptions = `<?php while($rowCat=mysqli_fetch_assoc($categories)){ ?><option value="<?=$rowCat['id'] ?>"><?=$rowCat['category_name'] ?></option><?php } ?>`;
var supplierOptions = `<?php while($rowSupplier=mysqli_fetch_assoc($supplies)){ ?><option value="<?=$rowSupplier['id'] ?>"><?=$rowSupplier['supplier_name'] ?></option><?php } ?>`;
var productOptions = `<?php while($rowProduct=mysqli_fetch_assoc($products2)){ ?><option value="<?=$rowProduct['id'] ?>" data-category="<?=$rowProduct['category'] ?>"><?=$rowProduct['product_name'] ?></option><?php } ?>`;
var packagingOptions = `<?php while($rowPkg=mysqli_fetch_assoc($packagings2)){ ?><option value="<?=$rowPkg['id'] ?>" data-weight="<?=$rowPkg['weight'] ?>"><?=$rowPkg['packaging_name'] ?></option><?php } ?>`;
var gradeOptions = `<?php while($rowGrade=mysqli_fetch_assoc($grades2)){ ?><option value="<?=$rowGrade['id'] ?>" data-product="<?=$rowGrade['product_id'] ?>" data-type="<?=$rowGrade['grade_type'] ?? 'Local'?>" data-name="<?=$rowGrade['units'] ?>"><?=$rowGrade['units'] ?></option><?php } ?>`;

$(function () {
  $('#uomhidden').hide();
  var userRole = '<?=$role ?>';
  const today = new Date();
  const tomorrow = new Date(today);
  const yesterday = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);
  yesterday.setDate(yesterday.getDate() - 7);

  $('#fromDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: today
  });

  $('#toDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: today
  });

  $('#packagingDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY HH:mm'
  });

  $('.select2').each(function() {
    $(this).select2({
        allowClear: true,
        placeholder: "Please Select",
        // Conditionally set dropdownParent based on the element’s location
        dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-content') : undefined
    });
  });

  var fromDateI = $('#fromDate').val();
  var toDateI = $('#toDate').val();
  var locationI = $('#locationFilter').val() ? $('#locationFilter').val() : '';
  var productionLineI = $('#productionLineFilter').val() ? $('#productionLineFilter').val() : '';
  var categoryI = $('#categoryFilter').val() ? $('#categoryFilter').val() : 'all';

  var table = $("#weightTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'searching': true,
    'order': [[ 1, 'asc' ]],
    'columnDefs': [ { orderable: false, targets: [0] }],
    'language': {
      'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
      'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters.'?></div></div>'
    },
    'ajax': {
      'url':'php/modules/packagingBatches/filterPackagingBatches.php',
      'data': {
        fromDate: fromDateI,
        toDate: toDateI,
        location: locationI,
        productionLine: productionLineI,
        category: categoryI
      } 
    },
    'columns': [
      { data: 'batch_no' },
      { data: 'packaging_date' },
      { data: 'locations' },
      { data: 'production_line' },
      { data: 'status', render: function(d) { var cls = { pending: 'warning', partial: 'info', completed: 'success' }; return '<span class="badge badge-' + (cls[d] || 'secondary') + '">' + d + '</span>'; } },
      { 
        data: 'id',
        class: 'action-button',
        orderable: false,
        render: function ( data, type, row ) {
          var buttons = '<div class="d-flex" style="gap:4px;">';
          if(<?=$userAllowEdit == 'Y' ? 'true' : 'false'?>) {
            buttons += '<button type="button" onclick="edit('+data+')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button>';
          }
          buttons += '<button type="button" onclick="printBatch('+data+')" class="btn btn-sm btn-outline-secondary" title="Print"><i class="fas fa-print"></i></button>';
          if(row.status !== 'completed') {
            buttons += '<button type="button" onclick="openShipmentModal('+data+')" class="btn btn-sm btn-outline-info" title="Shipment"><i class="fas fa-shipping-fast"></i></button>';
          }
          if(<?=$userAllowDelete == 'Y' ? 'true' : 'false'?>) {
            buttons += '<button type="button" onclick="deactivate('+data+')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>';
          }
          buttons += '</div>';
          return buttons;
        }
      }
    ]
  });

  // Add event listener for opening and closing details on row click
  $('#weightTable tbody').on('click', 'tr', function (e) {
      var tr = $(this); // The row that was clicked
      var row = table.row(tr);

      // Exclude clicks on buttons, checkboxes, and form elements
      if ($(e.target).closest('td').hasClass('select-checkbox') || 
          $(e.target).closest('td').hasClass('action-button') ||
          $(e.target).is('select') || 
          $(e.target).is('input') ||
          $(e.target).is('button')) {
        return;
      }

      if (row.child.isShown()) {
          // This row is already open - close it
          row.child.hide();
          tr.removeClass('shown');
      } else {
          $.post('php/modules/packagingBatches/getpackagingBatch.php', { userID: row.data().id}, function (data) {
            var obj = JSON.parse(data);
            if (obj.status === 'success') {
              row.child(format(obj.message)).show();
              tr.addClass("shown");
              if(obj.message.weightDetails && obj.message.weightDetails.length > 0) {
                populateFilters(obj.message.id, obj.message.weightDetails);
              }
            }
          });
      }
  });

  $('#filterSearch').on('click', function(){
    //$('#spinnerLoading').show();
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var locationI = $('#locationFilter').val() ? $('#locationFilter').val() : '';
    var productionLineI = $('#productionLineFilter').val() ? $('#productionLineFilter').val() : '';
    var categoryI = $('#categoryFilter').val() ? $('#categoryFilter').val() : 'all';

    //Destroy the old Datatable
    $("#weightTable").DataTable().clear().destroy();

    //Create new Datatable
    table = $("#weightTable").DataTable({
      "responsive": true,
      "autoWidth": false,
      'processing': true,
      'serverSide': true,
      'serverMethod': 'post',
      'searching': true,
      'order': [[ 1, 'asc' ]],
      'columnDefs': [ { orderable: false, targets: [0] }],
      'language': {
        'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
        'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters.'?></div></div>'
      },
      'ajax': {
      'url':'php/modules/packagingBatches/filterPackagingBatches.php',
        'data': {
          fromDate: fromDateI,
          toDate: toDateI,
          location: locationI,
          productionLine: productionLineI,
          category: categoryI
        } 
      },
      'columns': [
        { data: 'batch_no' },
        { data: 'packaging_date' },
        { data: 'locations' },
        { data: 'production_line' },
        { data: 'status', render: function(d) { var cls = { pending: 'warning', partial: 'info', completed: 'success' }; return '<span class="badge badge-' + (cls[d] || 'secondary') + '">' + d + '</span>'; } },
        { 
          data: 'id',
          class: 'action-button',
          orderable: false,
          render: function ( data, type, row ) {
            var buttons = '<div class="d-flex" style="gap:4px;">';
            if(<?=$userAllowEdit == 'Y' ? 'true' : 'false'?>) {
              buttons += '<button type="button" onclick="edit('+data+')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button>';
            }
            buttons += '<button type="button" onclick="printBatch('+data+')" class="btn btn-sm btn-outline-secondary" title="Print"><i class="fas fa-print"></i></button>';
            if(row.status !== 'completed') {
              buttons += '<button type="button" onclick="openShipmentModal('+data+')" class="btn btn-sm btn-outline-info" title="Shipment"><i class="fas fa-shipping-fast"></i></button>';
            }
            if(<?=$userAllowDelete == 'Y' ? 'true' : 'false'?>) {
              buttons += '<button type="button" onclick="deactivate('+data+')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>';
            }
            buttons += '</div>';
            return buttons;
          }
        }
      ],
    });
  });

  $.validator.setDefaults({
    submitHandler: function () {
      if($('#extendModal').hasClass('show')){
        var valid = true;
        var errorMsg = '';
        $('#weightDetailsTable tr').each(function(i) {
          var rowNum = i + 1;

          if (!$(this).find('select[name*="[category]"]').val()) {
            errorMsg = 'Row ' + rowNum + ': Category is required.';
            valid = false;
            return false;
          }

          if (!$(this).find('select[name*="[product]"]').val()) {
            errorMsg = 'Row ' + rowNum + ': Product is required.';
            valid = false;
            return false;
          }

          if (!$(this).find('select[name*="[grade]"]').val()) {
            errorMsg = 'Row ' + rowNum + ': Grade is required.';
            valid = false;
            return false;
          }

          if (!$(this).find('select[name*="[packaging_size]"]').val()) {
            errorMsg = 'Row ' + rowNum + ': Packaging size is required.';
            valid = false;
            return false;
          }

          var gross = parseFloat($(this).find('input[name*="[gross]"]').val() || 0);
          if (gross <= 0) {
            errorMsg = 'Row ' + rowNum + ': Gross must be greater than 0.';
            valid = false;
            return false;
          }

          var net = parseFloat($(this).find('input[name*="[weight]"]').val() || 0);
          if (net < 0) {
            errorMsg = 'Row ' + rowNum + ': Net weight cannot be negative.';
            valid = false;
            return false;
          }

          if (net === 0) {
            errorMsg = 'Row ' + rowNum + ': Net weight is 0. Check gross and tare values.';
            valid = false;
            return false;
          }
        });

        if (!valid) {
          toastr["error"](errorMsg, "Validation Error:");
          return;
        }

        $('#spinnerLoading').show();
        var formData = new FormData($('#extendForm')[0]);
        $.ajax({
          url: 'php/modules/packagingBatches/packagingBatch.php',
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          success: function(data){
            var obj = JSON.parse(data); 
            if(obj.status === 'success'){
              $('#extendModal').modal('hide');
              toastr["success"](obj.message, "Success:");
              $('#weightTable').DataTable().ajax.reload();
            } else if(obj.status === 'failed'){
              toastr["error"](obj.message, "Failed:");
            } else {
              toastr["error"]("Something wrong when edit", "Failed:");
            }
            $('#spinnerLoading').hide();
          },
          error: function(){
            toastr["error"]("Something wrong when saving", "Failed:");
            $('#spinnerLoading').hide();
          }
        });
      } else if($('#shipmentModal').hasClass('show')){
        $('#spinnerLoading').show();
        var loadingDate = $('#shipmentLoadingDate').val();
        var customerId  = $('#shipmentCustomer').val();
        var postData = {
          loadingDate:  loadingDate,
          shipmentType: $('#shipmentType').val(),
          remarks:      $('#shipmentRemark').val()
        };
        $.each(shipmentBatchItems, function(i, item) {
          postData['items[' + i + '][packaging_batch_item_id]'] = item.id;
          postData['items[' + i + '][packaging_batch_id]'] = item.packaging_batch_id;
          postData['items[' + i + '][customer_id]'] = customerId;
          postData['items[' + i + '][product_id]'] = item.product_id;
          postData['items[' + i + '][grade]'] = item.grade;
          postData['items[' + i + '][packaging_size]'] = item.packaging_size;
          postData['items[' + i + '][units_per_box]'] = item.units_per_box;
          postData['items[' + i + '][weight]'] = item.weight;
          postData['items[' + i + '][loading_time]'] = moment().format('HH:mm');
          postData['items[' + i + '][remarks]'] = $('#shipmentRemark').val();
        });
        $.post('php/modules/loading/loadingOrder.php', postData, function(data){
          var obj = JSON.parse(data);
          if(obj.status === 'success'){
            $('#shipmentModal').modal('hide');
            toastr["success"](obj.message, "Success:");
            $('#weightTable').DataTable().ajax.reload();
          } else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
          } else {
            toastr["error"]("Something went wrong", "Failed:");
          }
          $('#spinnerLoading').hide();
        });
      } else if($('#cancelModal').hasClass('show')){
        $('#spinnerLoading').show();
        $.post('php/modules/packagingBatches/deletePackagingBatch.php', $('#cancelForm').serialize(), function(data){
          var obj = JSON.parse(data);
          if(obj.status === 'success'){
            $('#cancelModal').modal('hide');
            toastr["success"](obj.message, "Success:");
            $('#weightTable').DataTable().ajax.reload();
          } else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
          } else {
            toastr["error"]("Something wrong when delete", "Failed:");
          }
          $('#spinnerLoading').hide();
        });
      }
    }
  });

  $('#addWeightBtn').on('click', function() {
    var idx = weightCount++;
    var now = new Date();
    var currentTime = now.getHours().toString().padStart(2, '0') + ':' + 
                      now.getMinutes().toString().padStart(2, '0') + ':' + 
                      now.getSeconds().toString().padStart(2, '0');
    var gradeTypeFilter = $('#gradeType').val();
    
    // Filter grade options by type
    var filteredGradeOptions = gradeOptions;
    if(gradeTypeFilter) {
      var $temp = $('<select>').html(gradeOptions);
      $temp.find('option').each(function() {
        var gradeType = $(this).attr('data-type');
        if(gradeType && gradeType != gradeTypeFilter) {
          $(this).remove();
        }
      });
      filteredGradeOptions = $temp.html();
    }
    
    var row = `
      <tr class="details">
        <input type="hidden" name="weightDetails[${idx}][batchItemId]" value="">
        <td>
          <select class="form-control select2" id="supplier${idx}" name="weightDetails[${idx}][supplier]" required>
            <option value="" selected disabled>Select Supplier</option>
            ${supplierOptions}
          </select>
        </td>
        <td>
          <select class="form-control select2" id="category${idx}" name="weightDetails[${idx}][category]" required>
            <option value="" selected disabled>Select Category</option>
            ${categoryOptions}
          </select>
        </td>
        <td>
          <select class="form-control select2" id="product${idx}" name="weightDetails[${idx}][product]" required>
            <option value="" selected disabled>Select Product</option>
            ${productOptions}
          </select>
        </td>
        <td>
          <select class="form-control select2" id="grade${idx}" name="weightDetails[${idx}][grade]" required>
            ${filteredGradeOptions}
          </select>
        </td>
        <td>
          <select class="form-control select2" id="packagingSize${idx}" name="weightDetails[${idx}][packaging_size]" required>
            <option value="" selected disabled>Select Packaging</option>
            <?php while($rowPkg=mysqli_fetch_assoc($packagings3)){ ?>
              <option value="<?=$rowPkg['id'] ?>" data-weight="<?=$rowPkg['weight'] ?>"><?=$rowPkg['packaging_name'] ?></option>
            <?php } ?>
          </select>
        </td>
        <td><input type="text" class="form-control" id="label${idx}" name="weightDetails[${idx}][label]"></td>
        <td><input type="number" class="form-control" id="unitPerBox${idx}" name="weightDetails[${idx}][unit_per_box]" step="1" value="0" min="1" required></td>
        <td><input type="number" class="form-control" id="gross${idx}" name="weightDetails[${idx}][gross]" step="0.01" value="0.00" min="0.01" required></td>
        <td><input type="number" class="form-control" id="tare${idx}" name="weightDetails[${idx}][tare]" step="0.01" value="0.00"></td>
        <td><input type="number" class="form-control" id="weight${idx}" name="weightDetails[${idx}][weight]" step="0.01" value="0.00" readonly></td>
        <td>
          <input type="time" class="form-control" id="time${idx}" name="weightDetails[${idx}][time]" value="${currentTime}" required/>
        </td>
        <td ${allowPhoto == 'Y' ? '' : 'style="display:none"'}>
          <input type="hidden" id="photo${idx}" name="weightDetails[${idx}][photoPath]" value="">
          <input type="file" name="photoFiles[${idx}]" id="photoFile${idx}" accept=".png,.jpg,.jpeg" style="display:none">
          <button type="button" class="btn btn-info btn-sm" onclick="$('#photoFile${idx}').click()"><i class="fas fa-camera"></i></button>
          <span id="photoStatus${idx}"></span>
        </td>
        <td>
          <button type="button" class="btn btn-danger btn-sm" onclick="removeWeightDetail(this)"><i class="fas fa-trash"></i></button>
        </td>
      </tr>
    `;
    $('#weightDetailsTable').append(row);
    
    // Store original options for the new grade select
    var newGradeSelect = $(`#grade${idx}`);
    newGradeSelect.data('original-options', gradeOptions);

    $('.select2').select2({
      allowClear: true,
      placeholder: "Please Select",
      dropdownParent: $('#extendModal .modal-content'),
      width: '100%'
    });
  });

  $('#weightDetailsTable').on('input', 'input[id^="gross"], input[id^="tare"]', function() {
    var row = $(this).closest('tr');
    var gross = parseFloat(row.find('input[id^="gross"]').val()) || 0;
    var tare  = parseFloat(row.find('input[id^="tare"]').val()) || 0;
    var net   = gross - tare;
    row.find('input[id^="weight"]').val(net.toFixed(2));
  });

  $('#weightDetailsTable').on('change', 'select[name*="[category]"]', function() {
    $(this).removeClass('is-invalid').closest('td').find('.invalid-feedback').remove();
    var row = $(this).closest('tr');
    var selectedCategory = $(this).val();
    var productSelect = row.find('select[name*="[product]"]');

    productSelect.select2('destroy');
    if (!productSelect.data('original-options')) {
      productSelect.data('original-options', productSelect.html());
    }
    productSelect.html(productSelect.data('original-options'));

    if (selectedCategory) {
      productSelect.find('option').each(function() {
        if ($(this).val() && $(this).data('category') != selectedCategory) {
          $(this).remove();
        }
      });
    }

    productSelect.val('').select2({
      allowClear: true,
      placeholder: "Please Select",
      dropdownParent: $('#extendModal .modal-content'),
      width: '100%'
    });
  });

  $('#weightDetailsTable').on('change', 'select[name*="[product]"]', function() {
    $(this).removeClass('is-invalid').closest('td').find('.invalid-feedback').remove();
    var row = $(this).closest('tr');
    var productId = $(this).val();
    var productName = $(this).find('option:selected').text();
    var gradeTypeFilter = $('#gradeType').val();
    
    // Filter grades by selected product and type
    var gradeSelect = row.find('select[name*="[grade]"]');
    var currentGrade = gradeSelect.val();
    var currentGradeId = gradeSelect.find(':selected').data('id');

    // Destroy Select2 before modifying options
    gradeSelect.select2('destroy');
    
    // Store all original options if not already stored
    if (!gradeSelect.data('original-options')) {
      gradeSelect.data('original-options', gradeSelect.html());
    }
    
    // Reset to original options
    gradeSelect.html(gradeSelect.data('original-options'));
    
    // Remove options that don't match the selected product and type
    gradeSelect.find('option').each(function() {
      var gradeProduct = $(this).attr('data-product');
      var gradeType = $(this).attr('data-type');
      var productMatch = !productId || !gradeProduct || gradeProduct == productId;
      var typeMatch = !gradeTypeFilter || !gradeType || gradeType == gradeTypeFilter;
      if(!productMatch || !typeMatch) {
        $(this).remove();
      }
    });
    
    // Recreate Select2
    gradeSelect.select2({
      allowClear: true,
      placeholder: "Please Select",
      dropdownParent: $('#extendModal .modal-content'),
      width: '100%'
    });
    
    gradeSelect.val(currentGrade).trigger('change');
  });

  // Filter grades when type changes
  $('#gradeType').on('change', function() {
    var gradeTypeFilter = $(this).val();
    
    // Update all existing grade selects in the table
    $('#weightDetailsTable tr').each(function() {
      var row = $(this);
      var productId = row.find('select[name*="[product]"]').val();
      var gradeSelect = row.find('select[name*="[grade]"]');
      var currentGrade = gradeSelect.val();
      
      gradeSelect.select2('destroy');
      
      if (!gradeSelect.data('original-options')) {
        gradeSelect.data('original-options', gradeOptions);
      }
      
      gradeSelect.html(gradeSelect.data('original-options'));
      
      gradeSelect.find('option').each(function() {
        var gradeProduct = $(this).attr('data-product');
        var gradeType = $(this).attr('data-type');
        var productMatch = !productId || !gradeProduct || gradeProduct == productId;
        var typeMatch = !gradeTypeFilter || !gradeType || gradeType == gradeTypeFilter;
        if(!productMatch || !typeMatch) {
          $(this).remove();
        }
      });
      
      gradeSelect.select2({
        allowClear: true,
        placeholder: "Please Select",
        dropdownParent: $('#extendModal .modal-content'),
        width: '100%'
      });
      
      // Keep current value if still valid
      if(gradeSelect.find('option[value="'+currentGrade+'"]').length) {
        gradeSelect.val(currentGrade).trigger('change.select2');
      }
    });
  });

  // Auto-fill gross from selected packaging size
  $('#weightDetailsTable').on('change', 'select[name*="[packaging_size]"]', function() {
    var row = $(this).closest('tr');
    var gross = parseFloat(row.find('input[name*="[gross]"]').val()) || 0;
    var weight = $(this).find('option:selected').data('weight');
    if (weight && !gross) {
      row.find('input[name*="[gross]"]').val(parseFloat(weight).toFixed(2)).trigger('input');
    }
  });

  $('#bulkPackagingSize').on('change', function() {
    var weight = $(this).find('option:selected').data('weight');
    if (weight) $('#bulkWeight').val(parseFloat(weight).toFixed(2));
  });

  // Fix scroll when nested modal opens
  $('#bulkAddModal, #shipmentModal').on('show.bs.modal', function() {
    $('body').addClass('modal-open');
  }).on('hidden.bs.modal', function() {
    $('body').addClass('modal-open');
  });

  // Bulk Add
  var now = new Date();
  $('#bulkAddBtn').on('click', function() {
    $('#bulkCategory').html('<option value="" selected disabled>Select Category</option>' + categoryOptions);
    $('#bulkProduct').html('<option value="" selected disabled>Select Product</option>' + productOptions);
    $('#bulkGrade').html(gradeOptions);
    $('#bulkPackagingSize').html('<option value="" selected disabled>Select Packaging</option>' + packagingOptions);

    ['#bulkCategory','#bulkProduct','#bulkGrade','#bulkPackagingSize'].forEach(function(id) {
      $(id).val(null).select2({ 
        allowClear: true, 
        placeholder: 'Please Select', 
        dropdownParent: $('#bulkAddModal .modal-content'), 
        width: '100%' 
      });
    });

    $('#bulkAddModal').find('#bulkNo').val(1);
    $('#bulkAddModal').find('#bulkUnitPerBox').val(0);
    $('#bulkAddModal').find('#bulkWeight').val(0);
    $('#bulkAddModal').find('#bulkTime').val(now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0') + ':' + now.getSeconds().toString().padStart(2,'0'));

    $('#bulkAddModal').modal('show');
  });

  $('#bulkCategory').on('change', function() {
    var selectedCategory = $(this).val();
    var productSelect = $('#bulkProduct');
    productSelect.html('<option value="" selected disabled>Select Product</option>' + productOptions);
    productSelect.find('option').each(function() {
      if ($(this).val() && $(this).data('category') != selectedCategory) {
        $(this).remove();
      }
    });
    productSelect.val('').select2({ 
      allowClear: true, 
      placeholder: "Please Select", 
      dropdownParent: $('#bulkAddModal .modal-content'), 
      width: '100%' 
    });
    $('#bulkGrade').val('').trigger('change');
  });

  $('#bulkProduct').on('change', function() {
    var productId = $(this).val();
    var gradeTypeFilter = $('#gradeType').val();
    var gradeSelect = $('#bulkGrade');
    gradeSelect.html(gradeOptions);
    gradeSelect.find('option').each(function() {
      var gradeProduct = $(this).attr('data-product');
      var gradeType = $(this).attr('data-type');
      var productMatch = !productId || !gradeProduct || gradeProduct == productId;
      var typeMatch = !gradeTypeFilter || !gradeType || gradeType == gradeTypeFilter;
      if(!productMatch || !typeMatch) {
        $(this).remove();
      }
    });
    gradeSelect.val('').select2({ 
      allowClear: true, 
      placeholder: "Please Select", 
      dropdownParent: $('#bulkAddModal .modal-content'), 
      width: '100%' 
    });
  });

  $('#bulkAddForm').on('submit', function(e) {
    e.preventDefault();
    var valid = true;
    ['#bulkCategory','#bulkProduct','#bulkGrade','#bulkPackagingSize'].forEach(function(id) {
      var el = $(id);
      if (!el.val()) {
        el.addClass('is-invalid').next('.select2-container').find('.select2-selection').addClass('is-invalid');
        el.closest('.form-group').find('.invalid-feedback').show();
        valid = false;
      } else {
        el.removeClass('is-invalid');
        el.closest('.form-group').find('.invalid-feedback').hide();
      }
    });

    if (!valid) return;

    var bulkNo = parseInt($('#bulkNo').val());
    if (!bulkNo || bulkNo < 1) {
      alert('Please enter a valid bulk number.');
      return;
    }

    var categoryVal = $('#bulkCategory').val();
    var categoryText = $('#bulkCategory option:selected').text();
    var productVal = $('#bulkProduct').val();
    var productText = $('#bulkProduct option:selected').text();
    var gradeVal = $('#bulkGrade').val();
    var packagingVal = $('#bulkPackagingSize').val();
    var unitPerBox = $('#bulkUnitPerBox').val();
    var weight = $('#bulkWeight').val();
    var time = $('#bulkTime').val();

    for (var i = 0; i < bulkNo; i++) {
      var idx = weightCount++;
      var gradeTypeFilter = $('#gradeType').val();
      
      // Filter grade options by type
      var filteredGradeOptions = gradeOptions;
      if(gradeTypeFilter) {
        var $temp = $('<select>').html(gradeOptions);
        $temp.find('option').each(function() {
          var gradeType = $(this).attr('data-type');
          if(gradeType && gradeType != gradeTypeFilter) {
            $(this).remove();
          }
        });
        filteredGradeOptions = $temp.html();
      }
      
      var row = `
        <tr class="details">
          <input type="hidden" name="weightDetails[${idx}][batchItemId]" value="">
          <td>
            <select class="form-control select2" id="supplier${idx}" name="weightDetails[${idx}][supplier]" required>
              <option value="" selected disabled>Select Supplier</option>
              ${supplierOptions}
            </select>
          </td>
          <td>
            <select class="form-control select2" id="category${idx}" name="weightDetails[${idx}][category]" required>
              <option value="" selected disabled>Select Category</option>
              ${categoryOptions}
            </select>
          </td>
          <td>
            <select class="form-control select2" id="product${idx}" name="weightDetails[${idx}][product]" required>
              <option value="" selected disabled>Select Product</option>
              ${productOptions}
            </select>
          </td>
          <td>
            <select class="form-control select2" id="grade${idx}" name="weightDetails[${idx}][grade]" required>
              ${filteredGradeOptions}
            </select>
          </td>
          <td>
            <select class="form-control select2" id="packagingSize${idx}" name="weightDetails[${idx}][packaging_size]" required>
              <option value="" selected disabled>Select Packaging</option>
              ${packagingOptions}
            </select>
          </td>
          <td><input type="text" class="form-control" id="label${idx}" name="weightDetails[${idx}][label]"></td>
          <td><input type="number" class="form-control" id="unitPerBox${idx}" name="weightDetails[${idx}][unit_per_box]" step="1" value="${unitPerBox}" min="1" required></td>
          <td><input type="number" class="form-control" id="gross${idx}" name="weightDetails[${idx}][gross]" step="0.01" value="${parseFloat(weight).toFixed(2)}" min="0.01" required></td>
          <td><input type="number" class="form-control" id="tare${idx}" name="weightDetails[${idx}][tare]" step="0.01" value="0.00"></td>
          <td><input type="number" class="form-control" id="weight${idx}" name="weightDetails[${idx}][weight]" step="0.01" value="${parseFloat(weight).toFixed(2)}" readonly></td>
          <td><input type="time" class="form-control" id="time${idx}" name="weightDetails[${idx}][time]" value="${time}" required/></td>
          <td ${allowPhoto == 'Y' ? '' : 'style="display:none"'}>
            <input type="hidden" id="photo${idx}" name="weightDetails[${idx}][photoPath]" value="">
            <input type="file" name="photoFiles[${idx}]" id="photoFile${idx}" accept=".png,.jpg,.jpeg" style="display:none">
            <button type="button" class="btn btn-info btn-sm" onclick="$('#photoFile${idx}').click()"><i class="fas fa-camera"></i></button>
            <span id="photoStatus${idx}"></span>
          </td>
          <td>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeWeightDetail(this)"><i class="fas fa-trash"></i></button>
          </td>
        </tr>
      `;
      $('#weightDetailsTable').append(row);

      var tr = $('#weightDetailsTable tr:last');

      var catSelect = tr.find(`select[name="weightDetails[${idx}][category]"]`);
      catSelect.val(categoryVal);

      var prodSelect = tr.find(`select[name="weightDetails[${idx}][product]"]`);
      prodSelect.data('original-options', prodSelect.html());
      prodSelect.find('option').each(function() {
        if ($(this).val() && $(this).data('category') != categoryVal) {
          $(this).remove();
        }
      });
      prodSelect.val(productVal);

      var gradeSelect = tr.find(`select[name="weightDetails[${idx}][grade]"]`);
      gradeSelect.data('original-options', gradeSelect.html());
      gradeSelect.find('option').each(function() {
        if ($(this).attr('data-product') && $(this).attr('data-product') != productVal) {
          $(this).remove();
        }
      });
      gradeSelect.val(gradeVal);

      tr.find(`select[name="weightDetails[${idx}][packaging_size]"]`).val(packagingVal);
    }

    $('.select2').select2({
      allowClear: true,
      placeholder: "Please Select",
      dropdownParent: $('#extendModal .modal-content'),
      width: '100%'
    });

    $('#weightDetailsTable tr').slice(-bulkNo).each(function(i) {
      var idx = weightCount - bulkNo + i;
      $(this).find(`select[name="weightDetails[${idx}][category]"]`).val(categoryVal).trigger('change.select2');
      $(this).find(`select[name="weightDetails[${idx}][product]"]`).val(productVal).trigger('change.select2');
      $(this).find(`select[name="weightDetails[${idx}][grade]"]`).val(gradeVal).trigger('change.select2');
      $(this).find(`select[name="weightDetails[${idx}][packaging_size]"]`).val(packagingVal).trigger('change.select2');
    });

    $('#bulkAddModal').modal('hide');
  });

  $('#saveButton').on('click', function(e) {
    var valid = true;
    $('#weightDetailsTable tr').each(function(i) {
      var rowNum = i + 1;
      var row = $(this);
      ['[category]','[product]','[grade]','[packaging_size]'].forEach(function(field) {
        var sel = row.find('select[name*="' + field + '"]');
        if (sel.length && !sel.val()) {
          sel.addClass('is-invalid');
          sel.closest('td').find('.invalid-feedback').remove();
          sel.closest('td').append('<div class="invalid-feedback d-block">Required.</div>');
          valid = false;
        } else {
          sel.removeClass('is-invalid');
          sel.closest('td').find('.invalid-feedback').remove();
        }
      });
    });
    if (!valid) { e.preventDefault(); e.stopImmediatePropagation(); }
  });

  // Show tick when file is selected
  $('#extendForm').on('change', 'input[type="file"]', function() {
    var statusSpan = $(this).siblings('span[id$="Status"], span[id*="photoStatus"], span[id*="PhotoStatus"]');
    if (this.files && this.files[0]) {
      statusSpan.html('<i class="fas fa-check-circle text-success"></i>');
    } else {
      statusSpan.html('');
    }
  });
});

function format (row) {
  var statusCls = { pending: 'warning', partial: 'info', completed: 'success' };
  var returnString = `
  <div class="expanded-row-content">
    <!-- Header -->
    <div class="expanded-header">
      <div>
        <div class="expanded-header-title">${row.batch_no}</div>
        <div class="expanded-header-subtitle">${row.locations || '-'}</div>
      </div>
      <div class="expanded-actions">
        ${<?=$userAllowEdit == 'Y' ? 'true' : 'false'?> ? '<button type="button" onclick="edit('+row.id+')" class="btn btn-sm btn-outline-primary"><i class="fas fa-pen"></i></button>' : ''}
        <button type="button" onclick="printBatch(${row.id})" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i></button>
        ${row.status !== 'completed' ? '<button type="button" onclick="openShipmentModal('+row.id+')" class="btn btn-sm btn-outline-info"><i class="fas fa-shipping-fast"></i></button>' : ''}
        ${<?=$userAllowDelete == 'Y' ? 'true' : 'false'?> ? '<button type="button" onclick="deactivate('+row.id+')" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>' : ''}
      </div>
    </div>

    <!-- KPI Summary -->
    <div class="kpi-row">
      <div class="kpi-card">
        <div class="kpi-label"><?=$languageArray['packaging_date_code'][$language]?></div>
        <div class="kpi-value">${row.packaging_date || '-'}</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label"><?=$languageArray['production_lines_code'][$language]?></div>
        <div class="kpi-value">${row.production_lines || '-'}</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label"><?=$languageArray['status_code'][$language]?></div>
        <div class="kpi-value"><span class="badge badge-${statusCls[row.status] || 'secondary'}">${row.status}</span></div>
      </div>
    </div>

    <!-- Order Info -->
    <div class="info-section">
      <div class="info-section-title"><?=$languageArray['order_information_code'][$language] ?? 'Order Information'?></div>
      <div class="info-grid">
        <div><span class="info-item-label"><?=$languageArray['batch_no_code'][$language]?></span><span class="info-item-value">${row.batch_no || '-'}</span></div>
        <div><span class="info-item-label"><?=$languageArray['locations_code'][$language]?></span><span class="info-item-value">${row.locations || '-'}</span></div>
        <div><span class="info-item-label"><?=$languageArray['production_lines_code'][$language]?></span><span class="info-item-value">${row.production_lines || '-'}</span></div>
      </div>
      ${row.remarks ? '<div class="info-remark"><span class="info-item-label"><?=$languageArray['remark_code'][$language]?></span><span class="info-item-value">' + row.remarks + '</span></div>' : ''}
    </div>

    <!-- Weight Details -->
    <div class="details-section">
      <div class="details-header">
        <span class="details-title"><?=$languageArray['weight_details_code'][$language]?></span>
        <div class="details-filters">
          <select class="form-control form-control-sm details-filter-select" id="productFilter_${row.id}" onchange="filterWeightTable('${row.id}')">
            <option value=""><?=$languageArray['all_products_code'][$language]?></option>
          </select>
          <select class="form-control form-control-sm details-filter-select" id="gradeFilter_${row.id}" onchange="filterWeightTable('${row.id}')">
            <option value=""><?=$languageArray['all_grades_code'][$language]?></option>
          </select>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table details-table mb-0" id="weightTable_${row.id}">
          <thead>
            <tr>
              <th><?=$languageArray['product_code'][$language]?></th>
              <th><?=$languageArray['grade_code'][$language]?></th>
              <th><?=$languageArray['packaging_size_code'][$language]?></th>
              <th class="text-right"><?=$languageArray['unit_per_box_code'][$language]?></th>
              <th class="text-right"><?=$languageArray['weight_code'][$language]?></th>
              <th class="text-center"><?=$languageArray['time_code'][$language]?></th>
              <th class="text-center"><?=$languageArray['status_code'][$language]?></th>
              ${allowPhoto == 'Y' ? '<th class="text-center"><?=$languageArray['photo_code'][$language]?></th>' : ''}
            </tr>
          </thead>
          <tbody>`;

  for (var i = 0; i < row.weightDetails.length; i++) {
    var d = row.weightDetails[i];
    var itemCls = { pending: 'warning', completed: 'success' };
    returnString += `
            <tr>
              <td>${d.product_name}</td>
              <td><span class="grade-badge">${d.grade_name}</span></td>
              <td>${d.packaging_size_name}</td>
              <td class="text-right text-mono">${d.units_per_box}</td>
              <td class="text-right text-mono text-primary font-weight-bold">${parseFloat(d.weight).toFixed(2)}</td>
              <td class="text-center text-muted">${d.packing_time}</td>
              <td class="text-center"><span class="badge badge-${itemCls[d.status] || 'secondary'}">${d.status}</span></td>
              ${allowPhoto == 'Y' ? '<td class="text-center">' + (d.photo_path ? '<a href="php/viewPhoto.php?file=' + d.photo_path + '" target="_blank" class="btn btn-outline-secondary btn-sm btn-photo"><i class="fas fa-image"></i></a>' : '-') + '</td>' : ''}
            </tr>`;
  }

  returnString += `
          </tbody>
        </table>
      </div>
    </div>
  </div>
  `;

  return returnString;
}

function newEntry(){
  $('#extendModal').find('#id').val("");
  $('#extendModal').find('#batchNo').val("");
  $('#extendModal').find('#packagingDate').val("");
  $('#packagingDatePicker').datetimepicker('date', moment());
  $('#extendModal').find('#remarks').val("");
  $('#extendModal').find('#location').val("").trigger('change');
  $('#extendModal').find('#productionLines').val("").trigger('change');
  $('#extendModal').find('#gradeType').val("Local");
  $('#weightDetailsTable').empty();
  $('#extendModal').modal('show');
  
  $('#extendForm').validate({
    errorElement: 'span',
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback');
      element.closest('.form-group').append(error);
    },
    highlight: function (element, errorClass, validClass) {
      $(element).addClass('is-invalid');
    },
    unhighlight: function (element, errorClass, validClass) {
      $(element).removeClass('is-invalid');
    }
  });
}

function edit(id) {
  $('#spinnerLoading').show();
  $.post('php/modules/packagingBatches/getPackagingBatch.php', {userID: id}, function(data){
    var obj = JSON.parse(data);
    
    if(obj.status === 'success'){
      $('#extendModal').find('#id').val(obj.message.id);
      $('#extendModal').find('#batchNo').val(obj.message.batch_no);
      $('#extendModal').find('#remarks').val(obj.message.remarks);
      $('#extendModal').find('#location').val(obj.message.location).trigger('change');
      $('#extendModal').find('#productionLines').val(obj.message.production_line).trigger('change');
      $('#extendModal').find('#gradeType').val(obj.message.type || 'Local').trigger('change');
      
      if (obj.message.packaging_date) {
        $('#packagingDatePicker').datetimepicker('date', moment(obj.message.packaging_date, 'YYYY-MM-DD HH:mm:ss'));
      } else {
        $('#packagingDatePicker').datetimepicker('clear');
      }
      
      // Populate weight details table
      var tbody = $('#weightDetailsTable');
      tbody.empty();
      
      if(obj.message.weightDetails && obj.message.weightDetails.length > 0) {
        for(var i = 0; i < obj.message.weightDetails.length; i++) {
          var detail = obj.message.weightDetails[i];
          var idx = weightCount++;
          var timeVal = detail.packing_time || '';
          var row = `
            <tr class="details">
              <input type="hidden" name="weightDetails[${idx}][batchId]" value="${detail.id || ''}">
              <td>
                <select class="form-control select2" id="supplier${idx}" name="weightDetails[${idx}][supplier]" required>
                  <option value="" selected disabled>Select Supplier</option>
                  ${supplierOptions}
                </select>
              </td>
              <td>
                <select class="form-control select2" id="category${idx}" name="weightDetails[${idx}][category]" required>
                  <option value="" selected disabled>Select Category</option>
                  <?php while($rowCat=mysqli_fetch_assoc($categories2)){ ?>
                    <option value="<?=$rowCat['id'] ?>"><?=$rowCat['category_name'] ?></option>
                  <?php } ?>
                </select>
              </td>
              <td>
                <select class="form-control select2" id="product${idx}" name="weightDetails[${idx}][product]" required>
                  <option value="" selected disabled>Select Product</option>
                  ${productOptions}
                </select>
              </td>
              <td>
                <select class="form-control select2" id="grade${idx}" name="weightDetails[${idx}][grade]" required>
                  ${gradeOptions}
                </select>
              </td>
              <td>
                <select class="form-control select2" id="packagingSize${idx}" name="weightDetails[${idx}][packaging_size]" required>
                  <option value="" selected disabled>Select Packaging</option>
                  ${packagingOptions}
                </select>
              </td>
              <td><input type="text" class="form-control" id="label${idx}" name="weightDetails[${idx}][label]" value="${detail.label}"></td>
              <td><input type="number" class="form-control" id="unitPerBox${idx}" name="weightDetails[${idx}][unit_per_box]" value="${detail.units_per_box || 0}" step="1" min="1" required></td>
              <td><input type="number" class="form-control" id="gross${idx}" name="weightDetails[${idx}][gross]" value="${(parseFloat(detail.gross)||0).toFixed(2)}" step="0.01" min="0.01" required></td>
              <td><input type="number" class="form-control" id="tare${idx}" name="weightDetails[${idx}][tare]" value="${(parseFloat(detail.tare)||0).toFixed(2)}" step="0.01"></td>
              <td><input type="number" class="form-control" id="weight${idx}" name="weightDetails[${idx}][weight]" value="${(parseFloat(detail.weight)||0).toFixed(2)}" step="0.01" readonly></td>
              <td><input type="time" class="form-control" id="time${idx}" name="weightDetails[${idx}][time]" value="${timeVal}" required></td>
              <td ${allowPhoto == 'Y' ? '' : 'style="display:none"'}>
                <input type="hidden" id="photo${idx}" name="weightDetails[${idx}][photoPath]" value="${detail.photo_path || ''}">
                <input type="file" name="photoFiles[${idx}]" id="photoFile${idx}" accept=".png,.jpg,.jpeg" style="display:none">
                ${detail.photo_path ? '<a href="php/viewPhoto.php?file=' + detail.photo_path + '" target="_blank" class="btn btn-success btn-sm mr-1" title="View Photo"><i class="fas fa-image"></i></a>' : ''}
                <button type="button" class="btn btn-info btn-sm" onclick="$('#photoFile${idx}').click()"><i class="fas fa-camera"></i></button>
                <span id="photoStatus${idx}"></span>
              </td>
              <td>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeWeightDetail(this)"><i class="fas fa-trash"></i></button>
              </td>
            </tr>
          `;
          tbody.append(row);

          // Set supplier
          tbody.find(`select[name="weightDetails[${idx}][supplier]"]`).val(detail.supplier_id);

          // Set category
          tbody.find(`select[name="weightDetails[${idx}][category]"]`).val(detail.category_id);

          // Filter and set product
          var newProductSelect = tbody.find(`select[name="weightDetails[${idx}][product]"]`);
          newProductSelect.data('original-options', newProductSelect.html());
          newProductSelect.find('option').each(function() {
            if ($(this).val() && $(this).data('category') != detail.category_id) {
              $(this).remove();
            }
          });
          newProductSelect.val(detail.product_id);

          // Filter and set grade
          var gradeSelect = tbody.find(`select[name="weightDetails[${idx}][grade]"]`);
          gradeSelect.data('original-options', gradeSelect.html());
          var gradeTypeFilter = $('#gradeType').val();
          gradeSelect.find('option').each(function() {
            var optProduct = $(this).attr('data-product');
            var optType = $(this).attr('data-type');
            var productMatch = !optProduct || optProduct == detail.product_id;
            var typeMatch = !gradeTypeFilter || !optType || optType == gradeTypeFilter;
            if (!productMatch || !typeMatch) {
              $(this).remove();
            }
          });
          gradeSelect.val(detail.grade);

          // Set packaging size
          tbody.find(`select[name="weightDetails[${idx}][packaging_size]"]`).val(detail.packaging_size);
        }
      }
      
      $('.select2').each(function() {
        $(this).select2({
          allowClear: true,
          placeholder: "Please Select",
          // Conditionally set dropdownParent based on the element’s location
          dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-content') : undefined
        });
      });
      
      $('#extendModal').modal('show');

      $('#extendForm').validate({
        errorElement: 'span',
        errorPlacement: function (error, element) {
          error.addClass('invalid-feedback');
          element.closest('.form-group').append(error);
        },
        highlight: function (element, errorClass, validClass) {
          $(element).addClass('is-invalid');
        },
        unhighlight: function (element, errorClass, validClass) {
          $(element).removeClass('is-invalid');
        }
      });
    }
    else if(obj.status === 'failed'){
      toastr["error"](obj.message, "Failed:");
    }
    else{
      toastr["error"]("Something wrong when pull data", "Failed:");
    }
    $('#spinnerLoading').hide();
  });
}

function reindexWeightDetails() {
  $('#weightDetailsTable tr').each(function(index) {
    $(this).find('input, select').each(function() {
      var name = $(this).attr('name');
      if(name) {
        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
      }
    });
  });
}

function removeWeightDetail(button) {
  $(button).closest('tr').remove();
  reindexWeightDetails();
  updateTotals();
}

function updateTotals() {
  var totalGross = 0, totalTare = 0, totalNet = 0, totalPrice = 0;
  $('#weightDetailsTable tr').each(function() {
    totalGross += parseFloat($(this).find('input[name*="[gross]"]').val() || 0);
    totalTare += parseFloat($(this).find('input[name*="[tare]"]').val() || 0);
    totalNet += parseFloat($(this).find('input[name*="[net]"]').val() || 0);
  });
  $('#totalWeightGross').text(totalGross.toFixed(2));
  $('#totalWeightTare').text(totalTare.toFixed(2));
  $('#totalWeightNet').text(totalNet.toFixed(2));
  
}

function deactivate(id) {
  if (confirm('Are you sure you want to delete this item?')) {
    $('#cancelModal').find('#id').val(id);
    $('#cancelModal').modal('show');

    $('#cancelForm').validate({
      errorElement: 'span',
      errorPlacement: function (error, element) {
          error.addClass('invalid-feedback');
          element.closest('.form-group').append(error);
      },
      highlight: function (element, errorClass, validClass) {
          $(element).addClass('is-invalid');
      },
      unhighlight: function (element, errorClass, validClass) {
          $(element).removeClass('is-invalid');
      }
    });
  }
}

function printBatch(id) {
  $.post('php/modules/packagingBatches/print.php', {userID: id}, function(data){
    var obj = JSON.parse(data);
    if(obj.status === 'success') {
      var printWindow = window.open('', '', 'height=' + screen.height + ',width=' + screen.width);
      printWindow.document.write(obj.message);
      printWindow.document.close();
      setTimeout(function(){
        printWindow.print();
        printWindow.close();
      }, 500);
    }
    else if(obj.status === 'failed'){
      alert(obj.message);
    }
    else{
      alert("Something wrong when activate");
    }
  });
}

function filterWeightTable(rowId) {
  var productFilter = $('#productFilter_' + rowId).val();
  var gradeFilter = $('#gradeFilter_' + rowId).val();
  
  var totalGross = 0, totalTare = 0, totalNet = 0;
  
  $('#weightTable_' + rowId + ' tbody tr').each(function() {
    var product = $(this).find('td:eq(0)').text();
    var grade = $(this).find('td:eq(1)').text();
    var showProduct = !productFilter || product == productFilter;
    var showGrade = !gradeFilter || grade == gradeFilter;
    var show = showProduct && showGrade;
    $(this).toggle(show);
    
    if(show) {
      var grossText = $(this).find('td:eq(2)').text().split(' ')[0];
      var tareText = $(this).find('td:eq(3)').text().split(' ')[0];
      var netText = $(this).find('td:eq(4)').text().split(' ')[0];
      
      totalGross += parseFloat(grossText) || 0;
      totalTare += parseFloat(tareText) || 0;
      totalNet += parseFloat(netText) || 0;
    }
  });
  
  $('#weightTable_' + rowId + ' tfoot tr th:eq(1)').text(totalGross.toFixed(2));
  $('#weightTable_' + rowId + ' tfoot tr th:eq(2)').text(totalTare.toFixed(2));
  $('#weightTable_' + rowId + ' tfoot tr th:eq(3)').text(totalNet.toFixed(2));
  
  if(productFilter) {
    var gradeSelect = $('#gradeFilter_' + rowId);
    var currentGrade = gradeSelect.val();
    gradeSelect.find('option:not(:first)').remove();
    
    var grades = [];
    $('#weightTable_' + rowId + ' tbody tr').each(function() {
      var product = $(this).find('td:eq(0)').text();
      if(product === productFilter) {
        var grade = $(this).find('td:eq(1)').text();
        if(grades.indexOf(grade) === -1) {
          grades.push(grade);
        }
      }
    });
    
    grades.sort();
    grades.forEach(function(grade) {
      gradeSelect.append('<option value="' + grade + '">' + grade + '</option>');
    });
    gradeSelect.val(currentGrade);
  } else {
    var gradeSelect = $('#gradeFilter_' + rowId);
    var currentGrade = gradeSelect.val();
    gradeSelect.find('option:not(:first)').remove();
    
    var grades = [];
    $('#weightTable_' + rowId + ' tbody tr').each(function() {
      var grade = $(this).find('td:eq(1)').text();
      if(grades.indexOf(grade) === -1) {
        grades.push(grade);
      }
    });
    
    grades.sort();
    grades.forEach(function(grade) {
      gradeSelect.append('<option value="' + grade + '">' + grade + '</option>');
    });
    gradeSelect.val(currentGrade);
  }
}

function populateFilters(rowId, weightDetails) {
  var products = {};
  var grades = [];
  
  weightDetails.forEach(function(detail) {
    products[detail.product_name] = true;
    if(grades.indexOf(detail.grade) === -1) {
      grades.push(detail.grade);
    }
  });
  
  var productSelect = $('#productFilter_' + rowId);
  for(var product in products) {
    productSelect.append('<option value="' + product + '">' + product + '</option>');
  }
  
  grades.sort();
  var gradeSelect = $('#gradeFilter_' + rowId);
  grades.forEach(function(grade) {
    gradeSelect.append('<option value="' + grade + '">' + grade + '</option>');
  });
}
var shipmentBatchItems = [];

function openShipmentModal(id) {
  shipmentBatchItems = [];
  $('#shipmentBatchId').val(id);
  $('#shipmentCustomer').val('').trigger('change');
  $('#shipmentType').val('').trigger('change');
  $('#shipmentRemark').val('');
  $('#shipmentLoadingDate').val('');

  $('#spinnerLoading').show();
  $.post('php/modules/loading/getPackagingBatchItems.php', { batch_id: id }, function(data) {
    var obj = JSON.parse(data);
    $('#spinnerLoading').hide();
    if (obj.status === 'success') {
      if (obj.items.length === 0) {
        toastr["error"]("No pending items found in this batch.", "Failed:");
        return;
      }
      shipmentBatchItems = obj.items;
      $('#shipmentModal').modal('show');
      $('#shipmentLoadingDatePicker').datetimepicker({
        icons: { time: 'far fa-clock' },
        format: 'DD/MM/YYYY HH:mm',
        defaultDate: moment()
      });
      ['#shipmentCustomer','#shipmentType'].forEach(function(sel) {
        $(sel).select2({ 
          allowClear: true, 
          placeholder: 'Please Select', 
          dropdownParent: $('#shipmentModal .modal-content'), 
          width: '100%' 
        });
      });

      $('#shipmentForm').validate({
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        highlight: function (element, errorClass, validClass) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).removeClass('is-invalid');
        }
      });
    } else {
      toastr["error"](obj.message, "Failed:");
    }
  });
}
</script>