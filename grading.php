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

  // Get user permissions from session
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

  if ($role != 'SADMIN'){
    $categoryFilter = !empty($categoryIds) ? " AND c.id IN (" . implode(',', array_map('intval', $categoryIds)) . ")" : "";
    $categories = $db->query("SELECT * FROM categories c WHERE c.deleted = '0' AND c.customer = '$company' AND c.module IN ('wholesale', 'processing')$categoryFilter ORDER BY c.category_name ASC");
    $categories2 = $db->query("SELECT * FROM categories c WHERE c.deleted = '0' AND c.customer = '$company' AND c.module IN ('wholesale', 'processing')$categoryFilter ORDER BY c.category_name ASC");
    $productQuery = "SELECT p.* FROM products p INNER JOIN categories c ON p.category = c.id WHERE p.deleted = '0' AND p.customer = '$company' AND c.module IN ('wholesale', 'processing') AND c.deleted = '0'$categoryFilter ORDER BY p.product_name ASC";   
    $productCheck = $db->query($productQuery);
    if ($productCheck->num_rows == 0) {
      $productQuery = "SELECT * FROM products WHERE deleted = '0' AND customer = '$company' ORDER BY product_name ASC";
    }
    $products = $db->query($productQuery);
    $products2 = $db->query($productQuery);
    $products3 = $db->query($productQuery);
    $products4 = $db->query($productQuery);
    $grades = $db->query("SELECT DISTINCT g.*, pg.product_id FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY p.product_name ASC, g.units ASC");
    $grades2 = $db->query("SELECT DISTINCT g.*, pg.product_id FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY p.product_name ASC, g.units ASC");
    $grades3 = $db->query("SELECT DISTINCT g.*, pg.product_id FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY p.product_name ASC, g.units ASC");
    $grades4 = $db->query("SELECT DISTINCT g.*, pg.product_id FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY p.product_name ASC, g.units ASC");
    $users = $db->query("SELECT * FROM users WHERE deleted = '0' AND customer = '$company' ORDER BY name ASC");
    $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' AND customer = '$company' ORDER BY locations ASC");
    $locations2 = $db->query("SELECT * FROM locations WHERE deleted = '0' AND customer = '$company' ORDER BY locations ASC");

    // Company Detail 
    $companyDetail = searchCompanyById($company, $db);
    $allowPhoto = $companyDetail['include_photo'];
  } else {
    $categories = $db->query("SELECT * FROM categories WHERE deleted = '0' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $categories2 = $db->query("SELECT * FROM categories WHERE deleted = '0' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $products = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $products2 = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $products3 = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $products4 = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $grades = $db->query("SELECT DISTINCT g.*, pg.product_id FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC");
    $grades2 = $db->query("SELECT DISTINCT g.*, pg.product_id FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC");
    $grades3 = $db->query("SELECT DISTINCT g.*, pg.product_id FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC");
    $grades4 = $db->query("SELECT DISTINCT g.*, pg.product_id FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC");
    $users = $db->query("SELECT * FROM users WHERE deleted = '0' ORDER BY name ASC");
    $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' ORDER BY locations ASC");
    $locations2 = $db->query("SELECT * FROM locations WHERE deleted = '0' ORDER BY locations ASC");

    $allowPhoto = 'Y';
  }

  $units = $db->query("SELECT * FROM units WHERE deleted = '0'");
  $units1 = $db->query("SELECT * FROM units WHERE deleted = '0'");
  
  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>

<div class="content page-modern">
  <div class="container-fluid">

    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title"><i class="fas fa-balance-scale"></i> <?=$languageArray['grading_code'][$language]?></h1>
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
            <label class="filter-label"><?=$languageArray['category_code'][$language]?></label>
            <select class="form-control select2" id="categoryFilter" name="categoryFilter">
              <option value=""><?=$languageArray['please_select_code'][$language]?></option>
              <?php while($rowCategory=mysqli_fetch_assoc($categories)){ ?>
                <option value="<?=$rowCategory['id'] ?>"><?=$rowCategory['category_name'] ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['locations_code'][$language]?></label>
            <select class="form-control select2" id="locationFilter" name="locationFilter">
              <option value=""><?=$languageArray['please_select_code'][$language]?></option>
              <?php 
              $firstLocation = null;
              while($rowLocation=mysqli_fetch_assoc($locations)){ 
                if(!$firstLocation) $firstLocation = $rowLocation;
              ?>
                <option value="<?=$rowLocation['id'] ?>" <?= $firstLocation && $rowLocation['id'] == $firstLocation['id'] ? 'selected' : '' ?>><?=$rowLocation['locations'] ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group filter-group-action">
            <label class="filter-label">&nbsp;</label>
            <button type="button" class="btn btn-filter btn-filter-primary" id="filterSearch">
              <i class="fas fa-search"></i> <?=$languageArray['search_code'][$language]?>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Results Card -->
    <div class="card results-card">
      <div class="card-header">
        <div class="results-header-left">
          <h3 class="results-title"><i class="fas fa-list"></i> <?=$languageArray['grading_code'][$language]?></h3>
        </div>
        <?php if($userAllowAdd == 'Y'){ ?>
        <div class="results-header-right d-flex" style="gap:0.5rem;">
          <button type="button" class="btn btn-action btn-action-warning" id="exportPdf">
            <i class="fas fa-file-pdf"></i> <?=$languageArray['export_pdf_code'][$language]?>
          </button>
          <button type="button" class="btn btn-action btn-action-success" id="exportExcel">
            <i class="fas fa-file-excel"></i> <?=$languageArray['export_excel_code'][$language]?>
          </button>
          <button type="button" class="btn btn-action btn-action-primary" onclick="newEntry()">
            <i class="fas fa-plus"></i> <?=$languageArray['add_new_code'][$language]?>
          </button>
        </div>
        <?php } ?>
      </div>
      <div class="card-body">
        <table id="weightTable" class="table data-table">
          <thead>
            <tr>
              <th style="width:3%;"><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
              <th><?=$languageArray['grading_no_code'][$language]?></th>
              <th><?=$languageArray['category_code'][$language]?></th>
              <th><?=$languageArray['locations_code'][$language]?></th>
              <th><?=$languageArray['start_time_code'][$language]?></th>
              <th><?=$languageArray['end_time_code'][$language]?></th>
              <th style="width:10%;"><?=$languageArray['actions_code'][$language]?></th>
            </tr>
          </thead>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- Main Entry/Edit Modal -->
<div class="modal fade modal-modern" id="extendModal">
  <div class="modal-dialog modal-xl" style="max-width:90%;">
    <div class="modal-content">
      <form role="form" id="extendForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-balance-scale mr-2 text-muted"></i><?=$languageArray['add_new_entry_code'][$language]?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <input type="hidden" class="form-control" id="id" name="id">

          <!-- Basic Info Section -->
          <div class="modal-section">
            <h6 class="section-title"><i class="fas fa-info-circle mr-2"></i><?=$languageArray['basic_info_code'][$language] ?? 'Basic Information'?></h6>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['grading_no_code'][$language]?> *</label>
                  <input type="text" class="form-control" id="gradingNo" name="gradingNo" readonly>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['start_time_code'][$language]?> *</label>
                  <div class="input-group date" id="startTimePicker" data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input" data-target="#startTimePicker" id="startTime" name="startTime" required/>
                    <div class="input-group-append" data-target="#startTimePicker" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['end_time_code'][$language]?></label>
                  <div class="input-group date" id="endTimePicker" data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input" data-target="#endTimePicker" id="endTime" name="endTime"/>
                    <div class="input-group-append" data-target="#endTimePicker" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['category_code'][$language]?></label>
                  <select class="form-control select2" id="category" name="category">
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($rowCategory=mysqli_fetch_assoc($categories2)){ ?>
                      <option value="<?=$rowCategory['id'] ?>"><?=$rowCategory['category_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['locations_code'][$language]?> *</label>
                  <select class="form-control select2" id="location" name="location">
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($rowLocation=mysqli_fetch_assoc($locations2)){ ?>
                      <option value="<?=$rowLocation['id'] ?>"><?=$rowLocation['locations'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['remark_code'][$language]?></label>
                  <textarea class="form-control" id="remarks" name="remarks" rows="1" placeholder="<?=$languageArray['enter_remark_code'][$language]?>"></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Weight Details Section -->
          <div class="modal-section">
            <div class="section-header-toggle">
              <h6 class="section-title mb-0"><i class="fas fa-weight mr-2"></i><?=$languageArray['weight_details_code'][$language]?></h6>
              <button type="button" class="btn btn-modern btn-modern-primary btn-sm" id="addWeightBtn">
                <i class="fas fa-plus"></i> <?=$languageArray['add_weight_code'][$language]?>
              </button>
            </div>
            <div class="table-responsive mt-3">
              <table class="table table-bordered table-sm">
                <thead>
                  <tr>
                    <th width="15%"><?=$languageArray['product_code'][$language]?></th>
                    <th width="15%"><?=$languageArray['grade_code'][$language]?></th>
                    <th width="12%"><?=$languageArray['gross_code'][$language]?></th>
                    <th width="12%"><?=$languageArray['tare_code'][$language]?></th>
                    <th width="12%"><?=$languageArray['net_code'][$language]?></th>
                    <th width="10%"><?=$languageArray['time_code'][$language]?></th>
                    <?php if($allowPhoto == 'Y') { ?>
                    <th width="10%"><?=$languageArray['photo_code'][$language]?></th>
                    <?php } ?>
                    <th width="8%"><?=$languageArray['actions_code'][$language]?></th>
                  </tr>
                </thead>
                <tbody id="weightDetailsTable"></tbody>
                <tfoot id="weightDetailsFooter">
                  <tr class="table-secondary">
                    <th colspan="2" class="text-right"><?=$languageArray['total_code'][$language]?></th>
                    <th id="totalWeightGross">0.00</th>
                    <th id="totalWeightTare">0.00</th>
                    <th id="totalWeightNet">0.00</th>
                    <th></th>
                    <?php if($allowPhoto == 'Y') { ?><th></th><?php } ?>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <!-- Reject Details Section -->
          <div class="modal-section">
            <div class="section-header-toggle">
              <h6 class="section-title mb-0" style="color:#dc2626;"><i class="fas fa-times-circle mr-2"></i><?=$languageArray['reject_details_code'][$language]?></h6>
              <button type="button" class="btn btn-modern btn-modern-danger btn-sm" id="addRejectWeightBtn">
                <i class="fas fa-plus"></i> <?=$languageArray['add_reject_weight_code'][$language]?>
              </button>
            </div>
            <div class="table-responsive mt-3">
              <table class="table table-bordered table-sm">
                <thead>
                  <tr>
                    <th width="15%"><?=$languageArray['product_code'][$language]?></th>
                    <th width="15%"><?=$languageArray['grade_code'][$language]?></th>
                    <th width="12%"><?=$languageArray['gross_code'][$language]?></th>
                    <th width="12%"><?=$languageArray['tare_code'][$language]?></th>
                    <th width="12%"><?=$languageArray['net_code'][$language]?></th>
                    <th width="10%"><?=$languageArray['time_code'][$language]?></th>
                    <?php if($allowPhoto == 'Y') { ?>
                    <th width="10%"><?=$languageArray['photo_code'][$language]?></th>
                    <?php } ?>
                    <th width="8%"><?=$languageArray['actions_code'][$language]?></th>
                  </tr>
                </thead>
                <tbody id="rejectDetailsTable"></tbody>
                <tfoot id="rejectDetailsFooter">
                  <tr class="table-secondary">
                    <th colspan="2" class="text-right"><?=$languageArray['total_code'][$language]?></th>
                    <th id="totalRejectGross">0.00</th>
                    <th id="totalRejectTare">0.00</th>
                    <th id="totalRejectNet">0.00</th>
                    <th></th>
                    <?php if($allowPhoto == 'Y') { ?><th></th><?php } ?>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-modern btn-modern-primary" id="saveButton"><?=$languageArray['save_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Reason Modal -->
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
          <input type="hidden" class="form-control" id="cancelId" name="id">
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$languageArray['delete_reason_code'][$language]?> *</label>
            <textarea class="form-control" id="cancelReason" name="cancelReason" rows="3" required placeholder="<?=$languageArray['enter_reason_code'][$language] ?? 'Enter reason for deletion'?>"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-modern btn-modern-danger" id="submitCancel"><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Print Options Modal -->
<div class="modal fade modal-modern" id="printOptionsModal" tabindex="-1">
  <div class="modal-dialog" style="max-width:420px;">
    <div class="modal-content">
      <form id="printOptionsForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-print mr-2 text-muted"></i><?=$languageArray['print_options_code'][$language]?></h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="printID" name="userID">
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$languageArray['print_with_photo_code'][$language]?></label>
            <select class="form-control" id="printWithPhoto" name="withPhoto">
              <option value="Y"><?=$languageArray['yes_code'][$language]?></option>
              <option value="N"><?=$languageArray['no_code'][$language]?></option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['cancel_code'][$language]?></button>
          <button type="submit" class="btn btn-modern btn-modern-primary"><?=$languageArray['print_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Variables
var weightCount = 0;
var rejectCount = 0;
var allowPhoto = '<?=$allowPhoto?>';
var productOptions = `<?php while($rowProduct=mysqli_fetch_assoc($products2)){ ?><option value="<?=$rowProduct['id'] ?>" data-category="<?=$rowProduct['category'] ?>"><?=$rowProduct['product_name'] ?></option><?php } ?>`;
var gradeOptions = `<option value="" selected disabled>Select Grade</option><?php while($rowGrade=mysqli_fetch_assoc($grades2)){ ?><option value="<?=$rowGrade['id'] ?>" data-product="<?=$rowGrade['product_id'] ?>" data-name="<?=$rowGrade['units'] ?>"><?=$rowGrade['units'] ?></option><?php } ?>`;

$(function () {
  $('#uomhidden').hide();
  var userRole = '<?=$role ?>';
  const today = new Date();
  const tomorrow = new Date(today);
  const yesterday = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);
  yesterday.setDate(yesterday.getDate() - 7);

  // Date pickers
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

  $('#startTimePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY HH:mm'
  });

  $('#endTimePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY HH:mm'
  });

  // Select2 initialization
  $('.select2').each(function() {
    $(this).select2({
      allowClear: true,
      placeholder: "<?=$languageArray['please_select_code'][$language]?>",
      dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-content') : undefined
    });
  });

  // Select all checkbox
  $('#selectAllCheckbox').on('change', function() {
    var checkboxes = $('#weightTable tbody input[type="checkbox"]');
    checkboxes.prop('checked', $(this).prop('checked')).trigger('change');
  });

  // Initialize DataTable
  var table = initDataTable();

  // Filter search
  $('#filterSearch').on('click', function() {
    $('#weightTable').DataTable().clear().destroy();
    table = initDataTable();
  });

  // Row click to expand
  $('#weightTable tbody').on('click', 'tr', function (e) {
    var tr = $(this);
    var row = table.row(tr);

    if ($(e.target).closest('td').hasClass('select-checkbox') || 
        $(e.target).closest('td').hasClass('action-button') ||
        $(e.target).is('select') || 
        $(e.target).is('input') ||
        $(e.target).is('button')) {
      return;
    }

    if (row.child.isShown()) {
      row.child.hide();
      tr.removeClass('shown');
    } else {
      $.post('php/modules/grading/getGrading.php', { userID: row.data().id}, function (data) {
        var obj = JSON.parse(data);
        if (obj.status === 'success') {
          row.child(formatExpandedRow(obj.message)).show();
          tr.addClass("shown");
          if(obj.message.weightDetails && obj.message.weightDetails.length > 0) {
            populateFilters(obj.message.id, obj.message.weightDetails);
          }
        }
      });
    }
  });

  // Export Excel
  $('#exportExcel').on('click', function() {
    var params = getFilterParams();
    var selectedIds = getSelectedIds();
    if (selectedIds.length > 0) {
      window.open("php/modules/grading/export.php?" + params + "&isMulti=Y&ids=" + selectedIds);
    } else {
      window.open("php/modules/grading/export.php?" + params + "&isMulti=N");
    }
  });

  // Export PDF
  $('#exportPdf').on('click', function() {
    var params = getFilterParams();
    var selectedIds = getSelectedIds();
    if (selectedIds.length > 0) {
      window.open("php/modules/grading/exportPdf.php?" + params + "&isMulti=Y&ids=" + selectedIds);
    } else {
      window.open("php/modules/grading/exportPdf.php?" + params + "&isMulti=N");
    }
  });

  // Form validation and submission
  $.validator.setDefaults({
    submitHandler: function () {
      if($('#extendModal').hasClass('show')){
        $('#spinnerLoading').show();
        var formData = new FormData($('#extendForm')[0]);
        $.ajax({
          url: 'php/modules/grading/grading.php',
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          success: function(data){
            var obj = JSON.parse(data); 
            if(obj.status === 'success'){
              $('#extendModal').modal('hide');
              toastr.success(obj.message, "Success:");
              $('#weightTable').DataTable().ajax.reload();
            } else if(obj.status === 'failed'){
              toastr.error(obj.message, "Failed:");
            } else {
              toastr.error("Something wrong when saving", "Failed:");
            }
            $('#spinnerLoading').hide();
          },
          error: function(){
            toastr.error("Something wrong when saving", "Failed:");
            $('#spinnerLoading').hide();
          }
        });
      } else if($('#cancelModal').hasClass('show')){
        $('#spinnerLoading').show();
        $.post('php/modules/grading/deleteGrading.php', $('#cancelForm').serialize(), function(data){
          var obj = JSON.parse(data);
          if(obj.status === 'success'){
            $('#cancelModal').modal('hide');
            toastr.success(obj.message, "Success:");
            $('#weightTable').DataTable().ajax.reload();
          } else if(obj.status === 'failed'){
            toastr.error(obj.message, "Failed:");
          } else {
            toastr.error("Something wrong when delete", "Failed:");
          }
          $('#spinnerLoading').hide();
        });
      } else if ($('#printOptionsModal').hasClass('show')){
        $('#printOptionsModal').modal('hide');
        $.post('php/modules/grading/print.php', $('#printOptionsForm').serialize(), function(data){
          var obj = JSON.parse(data);
          if(obj.status === 'success') {
            var printWindow = window.open('', '', 'height=' + screen.height + ',width=' + screen.width);
            printWindow.document.write(obj.message);
            printWindow.document.close();
            var pollCount = 0;
            var poll = setInterval(function() {
              pollCount++;
              var rendered = printWindow.document.querySelector('.pagedjs_pages');
              if (rendered || pollCount > 60) {
                clearInterval(poll);
                setTimeout(function() {
                  printWindow.print();
                  printWindow.close();
                }, 300);
              }
            }, 200);
          } else if(obj.status === 'failed'){
            alert(obj.message);
          } else {
            alert("Something wrong when printing");
          }
        });
      }
    }
  });

  // Category change - filter products
  $('#category').on('change', function() {
    var selectedCategory = $(this).val();
    $('#weightDetailsTable select[name*="[product]"], #rejectDetailsTable select[name*="[product]"]').each(function() {
      var select = $(this);
      select.select2('destroy');
      if (!select.data('original-options')) {
        select.data('original-options', select.html());
      }
      select.html(select.data('original-options'));
      if (selectedCategory) {
        select.find('option').each(function() {
          if ($(this).val() && $(this).data('category') != selectedCategory) {
            $(this).remove();
          }
        });
      }
      select.select2({
        allowClear: true,
        placeholder: "<?=$languageArray['please_select_code'][$language]?>",
        dropdownParent: $('#extendModal .modal-content'),
        width: '100%'
      });
    });
  });

  // Add weight row
  $('#addWeightBtn').on('click', function() {
    addWeightRow();
  });

  // Add reject row
  $('#addRejectWeightBtn').on('click', function() {
    addRejectRow();
  });

  // Weight details - product change
  $('#weightDetailsTable').on('change', 'select[name*="[product]"]', function() {
    filterGradesByProduct($(this));
  });

  // Weight details - gross/tare change
  $('#weightDetailsTable').on('change', 'input[id^="gross"], input[id^="tare"]', function() {
    calculateNet($(this).closest('tr'), 'weight');
  });

  $('#weightDetailsTable').on('change', 'input[id^="net"]', function() {
    updateWeightTotals();
  });

  // Reject details - gross/tare change
  $('#rejectDetailsTable').on('change', 'input[id^="gross"], input[id^="tare"]', function() {
    calculateNet($(this).closest('tr'), 'reject');
  });

  $('#rejectDetailsTable').on('change', 'input[id^="net"]', function() {
    updateRejectTotals();
  });

  // File upload status
  $('#extendForm').on('change', 'input[type="file"]', function() {
    var statusSpan = $(this).siblings('span[id$="Status"], span[id*="photoStatus"], span[id*="PhotoStatus"]');
    if (this.files && this.files[0]) {
      statusSpan.html('<i class="fas fa-check-circle text-success"></i>');
    } else {
      statusSpan.html('');
    }
  });
});

// Helper Functions
function getFilterParams() {
  return "fromDate=" + $('#fromDate').val() +
    "&toDate=" + $('#toDate').val() +
    "&category=" + ($('#categoryFilter').val() || '') +
    "&location=" + ($('#locationFilter').val() || '');
}

function getSelectedIds() {
  var ids = [];
  $("#weightTable tbody input[type='checkbox']:checked").each(function() {
    ids.push($(this).val());
  });
  return ids;
}

function initDataTable() {
  return $("#weightTable").DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: true,
    serverMethod: 'post',
    searching: true,
    order: [[1, 'asc']],
    columnDefs: [{ orderable: false, targets: [0, 6] }],
    language: {
      emptyTable: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
      zeroRecords: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters.'?></div></div>'
    },
    ajax: {
      url: 'php/modules/grading/filterGrading.php',
      data: {
        fromDate: $('#fromDate').val(),
        toDate: $('#toDate').val(),
        category: $('#categoryFilter').val() || '',
        location: $('#locationFilter').val() || ''
      }
    },
    columns: [
      {
        data: 'id',
        className: 'select-checkbox',
        orderable: false,
        render: function(data) {
          return '<input type="checkbox" class="select-checkbox" value="' + data + '"/>';
        }
      },
      { data: 'grading_no' },
      { data: 'category' },
      { data: 'locations' },
      { data: 'start_date' },
      { data: 'end_date' },
      {
        data: 'id',
        className: 'action-button',
        render: function(data) {
          var buttons = '<div class="d-flex flex-nowrap" style="gap:4px;">';
          <?php if($userAllowEdit == 'Y') { ?>
          buttons += '<button type="button" onclick="edit(' + data + ')" class="btn btn-outline-primary btn-sm" title="Edit"><i class="fas fa-pen"></i></button>';
          <?php } ?>
          buttons += '<button type="button" onclick="print(' + data + ')" class="btn btn-outline-warning btn-sm" title="Print"><i class="fas fa-print"></i></button>';
          <?php if($userAllowDelete == 'Y') { ?>
          buttons += '<button type="button" onclick="deactivate(' + data + ')" class="btn btn-outline-danger btn-sm" title="Delete"><i class="fas fa-trash"></i></button>';
          <?php } ?>
          buttons += '</div>';
          return buttons;
        }
      }
    ]
  });
}

function formatExpandedRow(row) {
  var html = `
    <div class="expanded-row-content">
      <div class="info-section">
        <div class="info-section-title"><?=$languageArray['grading_info_code'][$language] ?? 'Grading Information'?></div>
        <div class="info-grid">
          <div><span class="info-item-label"><?=$languageArray['grading_no_code'][$language]?></span><span class="info-item-value">${row.grading_no}</span></div>
          <div><span class="info-item-label"><?=$languageArray['category_code'][$language]?></span><span class="info-item-value">${row.category || '-'}</span></div>
          <div><span class="info-item-label"><?=$languageArray['start_time_code'][$language]?></span><span class="info-item-value">${row.start_date}</span></div>
          <div><span class="info-item-label"><?=$languageArray['end_time_code'][$language]?></span><span class="info-item-value">${row.end_date || '-'}</span></div>
        </div>
        ${row.remark ? '<div class="info-remark"><span class="info-item-label"><?=$languageArray['remark_code'][$language]?></span><span class="info-item-value">' + row.remark + '</span></div>' : ''}
      </div>

      <div class="details-section">
        <div class="details-header">
          <span class="details-title"><?=$languageArray['weighing_details_code'][$language]?></span>
          <div class="details-filters">
            <select class="form-control form-control-sm details-filter-select" id="productFilter_${row.id}" onchange="filterWeightTable('${row.id}')">
              <option value=""><?=$languageArray['all_products_code'][$language]?></option>
            </select>
            <select class="form-control form-control-sm details-filter-select" id="gradeFilter_${row.id}" onchange="filterWeightTable('${row.id}')">
              <option value=""><?=$languageArray['all_grades_code'][$language]?></option>
            </select>
          </div>
        </div>
        <table class="details-table" id="weightTable_${row.id}">
          <thead>
            <tr>
              <th><?=$languageArray['product_code'][$language]?></th>
              <th><?=$languageArray['grade_code'][$language]?></th>
              <th class="text-right"><?=$languageArray['gross_code'][$language]?></th>
              <th class="text-right"><?=$languageArray['tare_code'][$language]?></th>
              <th class="text-right"><?=$languageArray['net_code'][$language]?></th>
              <th><?=$languageArray['time_code'][$language]?></th>
              ${allowPhoto == 'Y' ? '<th class="text-center"><?=$languageArray['photo_code'][$language]?></th>' : ''}
            </tr>
          </thead>
          <tbody>`;

  var totalGross = 0, totalTare = 0, totalNet = 0;
  for (var i = 0; i < row.weightDetails.length; i++) {
    var d = row.weightDetails[i];
    totalGross += parseFloat(d.gross_weight) || 0;
    totalTare += parseFloat(d.tare_weight) || 0;
    totalNet += parseFloat(d.nett_weight) || 0;
    html += `<tr>
      <td>${d.product_name}</td>
      <td><span class="grade-badge">${d.to_grade_unit}</span></td>
      <td class="text-right text-mono">${parseFloat(d.gross_weight).toFixed(2)}</td>
      <td class="text-right text-mono">${parseFloat(d.tare_weight).toFixed(2)}</td>
      <td class="text-right text-mono text-primary"><strong>${parseFloat(d.nett_weight).toFixed(2)}</strong></td>
      <td>${d.weighing_time}</td>
      ${allowPhoto == 'Y' ? '<td class="text-center">' + (d.photoPath ? '<a href="php/viewPhoto.php?file=' + d.photoPath + '" target="_blank" class="btn btn-outline-info btn-sm btn-photo"><i class="fas fa-image"></i></a>' : '<span class="text-muted">-</span>') + '</td>' : ''}
    </tr>`;
  }

  html += `</tbody>
          <tfoot>
            <tr>
              <th colspan="2" class="text-right"><?=$languageArray['total_code'][$language]?></th>
              <th class="text-right">${totalGross.toFixed(2)}</th>
              <th class="text-right">${totalTare.toFixed(2)}</th>
              <th class="text-right text-primary">${totalNet.toFixed(2)}</th>
              <th></th>
              ${allowPhoto == 'Y' ? '<th></th>' : ''}
            </tr>
          </tfoot>
        </table>
      </div>`;

  // Reject details
  if (row.rejectDetails && row.rejectDetails.length > 0) {
    html += `<div class="details-section">
        <div class="details-header">
          <span class="details-title details-title-danger"><?=$languageArray['reject_details_code'][$language]?></span>
        </div>
        <table class="details-table">
          <thead>
            <tr>
              <th><?=$languageArray['product_code'][$language]?></th>
              <th><?=$languageArray['grade_code'][$language]?></th>
              <th class="text-right"><?=$languageArray['gross_code'][$language]?></th>
              <th class="text-right"><?=$languageArray['tare_code'][$language]?></th>
              <th class="text-right"><?=$languageArray['net_code'][$language]?></th>
              <th><?=$languageArray['time_code'][$language]?></th>
              ${allowPhoto == 'Y' ? '<th class="text-center"><?=$languageArray['photo_code'][$language]?></th>' : ''}
            </tr>
          </thead>
          <tbody>`;

    var rejGross = 0, rejTare = 0, rejNet = 0;
    for (var j = 0; j < row.rejectDetails.length; j++) {
      var r = row.rejectDetails[j];
      rejGross += parseFloat(r.gross_weight) || 0;
      rejTare += parseFloat(r.tare_weight) || 0;
      rejNet += parseFloat(r.nett_weight) || 0;
      html += `<tr>
        <td>${r.product_name}</td>
        <td><span class="grade-badge grade-badge-danger">${r.to_grade_unit}</span></td>
        <td class="text-right text-mono">${parseFloat(r.gross_weight).toFixed(2)}</td>
        <td class="text-right text-mono">${parseFloat(r.tare_weight).toFixed(2)}</td>
        <td class="text-right text-mono text-danger"><strong>${parseFloat(r.nett_weight).toFixed(2)}</strong></td>
        <td>${r.weighing_time}</td>
        ${allowPhoto == 'Y' ? '<td class="text-center">' + (r.photo_path ? '<a href="php/viewPhoto.php?file=' + r.photo_path + '" target="_blank" class="btn btn-outline-info btn-sm btn-photo"><i class="fas fa-image"></i></a>' : '<span class="text-muted">-</span>') + '</td>' : ''}
      </tr>`;
    }

    html += `</tbody>
          <tfoot>
            <tr>
              <th colspan="2" class="text-right"><?=$languageArray['total_code'][$language]?></th>
              <th class="text-right">${rejGross.toFixed(2)}</th>
              <th class="text-right">${rejTare.toFixed(2)}</th>
              <th class="text-right text-danger">${rejNet.toFixed(2)}</th>
              <th></th>
              ${allowPhoto == 'Y' ? '<th></th>' : ''}
            </tr>
          </tfoot>
        </table>
      </div>`;
  }

  html += '</div>';
  return html;
}

// Row Management Functions
function addWeightRow() {
  var idx = weightCount++;
  var now = new Date();
  var currentTime = now.getHours().toString().padStart(2, '0') + ':' + 
                    now.getMinutes().toString().padStart(2, '0') + ':' + 
                    now.getSeconds().toString().padStart(2, '0');
  var row = `
    <tr class="details">
      <input type="hidden" name="weightDetails[${idx}][gradingItemId]" value="">
      <td>
        <select class="form-control form-control-sm select2" id="product${idx}" name="weightDetails[${idx}][product]">
          <option value="" selected disabled><?=$languageArray['select_product_code'][$language] ?? 'Select Product'?></option>
          <?php while($rowProduct=mysqli_fetch_assoc($products)){ ?>
            <option value="<?=$rowProduct['id'] ?>" data-category="<?=$rowProduct['category'] ?>"><?=$rowProduct['product_name'] ?></option>
          <?php } ?>
        </select>
      </td>
      <td>
        <select class="form-control form-control-sm select2" id="to_grade${idx}" name="weightDetails[${idx}][to_grade]">
          <option value="" selected disabled><?=$languageArray['select_grade_code'][$language] ?? 'Select Grade'?></option>
          <?php while($rowGrade=mysqli_fetch_assoc($grades)){ ?>
            <option value="<?=$rowGrade['id'] ?>" data-product="<?=$rowGrade['product_id'] ?>" data-name="<?=$rowGrade['units'] ?>"><?=$rowGrade['units'] ?></option>
          <?php } ?>
        </select>
      </td>
      <td><input type="number" class="form-control form-control-sm" id="gross${idx}" name="weightDetails[${idx}][gross]" step="0.01" value="0.00"></td>
      <td><input type="number" class="form-control form-control-sm" id="tare${idx}" name="weightDetails[${idx}][tare]" step="0.01" value="0.00"></td>
      <td><input type="number" class="form-control form-control-sm" id="net${idx}" name="weightDetails[${idx}][net]" step="0.01" value="0.00" readonly></td>
      <td><input type="time" class="form-control form-control-sm" id="time${idx}" name="weightDetails[${idx}][time]" value="${currentTime}"/></td>
      ${allowPhoto == 'Y' ? `<td>
        <input type="hidden" id="photo${idx}" name="weightDetails[${idx}][photoPath]" value="">
        <input type="file" name="photoFiles[${idx}]" id="photoFile${idx}" accept=".png,.jpg,.jpeg" style="display:none">
        <button type="button" class="btn btn-outline-info btn-sm" onclick="$('#photoFile${idx}').click()"><i class="fas fa-camera"></i></button>
        <span id="photoStatus${idx}"></span>
      </td>` : ''}
      <td>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeWeightDetail(this)"><i class="fas fa-trash"></i></button>
      </td>
    </tr>
  `;
  $('#weightDetailsTable').append(row);
  initRowSelect2(idx, 'weight');
}

function addRejectRow() {
  var idx = rejectCount++;
  var now = new Date();
  var currentTime = now.getHours().toString().padStart(2, '0') + ':' + 
                    now.getMinutes().toString().padStart(2, '0') + ':' + 
                    now.getSeconds().toString().padStart(2, '0');
  var row = `
    <tr class="details">
      <td>
        <select class="form-control form-control-sm select2" id="rejectProduct${idx}" name="rejectDetails[${idx}][product]">
          <option value="" selected disabled><?=$languageArray['select_product_code'][$language] ?? 'Select Product'?></option>
          <?php while($rowProduct=mysqli_fetch_assoc($products3)){ ?>
            <option value="<?=$rowProduct['id'] ?>" data-category="<?=$rowProduct['category'] ?>"><?=$rowProduct['product_name'] ?></option>
          <?php } ?>
        </select>
      </td>
      <td>
        <input type="hidden" name="rejectDetails[${idx}][grade]" value="REJ">
        <span class="badge badge-danger">REJ</span>
      </td>
      <td><input type="number" class="form-control form-control-sm" id="gross${idx}" name="rejectDetails[${idx}][gross]" step="0.01" value="0.00"></td>
      <td><input type="number" class="form-control form-control-sm" id="tare${idx}" name="rejectDetails[${idx}][tare]" step="0.01" value="0.00"></td>
      <td><input type="number" class="form-control form-control-sm" id="net${idx}" name="rejectDetails[${idx}][net]" step="0.01" value="0.00" readonly></td>
      <td><input type="time" class="form-control form-control-sm" id="time${idx}" name="rejectDetails[${idx}][time]" value="${currentTime}"/></td>
      ${allowPhoto == 'Y' ? `<td>
        <input type="hidden" id="photo${idx}" name="rejectDetails[${idx}][photoPath]" value="">
        <input type="file" name="rejectPhotoFiles[${idx}]" id="rejectPhotoFile${idx}" accept=".png,.jpg,.jpeg" style="display:none">
        <button type="button" class="btn btn-outline-info btn-sm" onclick="$('#rejectPhotoFile${idx}').click()"><i class="fas fa-camera"></i></button>
        <span id="rejectPhotoStatus${idx}"></span>
      </td>` : ''}
      <td>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeRejectDetail(this)"><i class="fas fa-trash"></i></button>
      </td>
    </tr>
  `;
  $('#rejectDetailsTable').append(row);
  initRowSelect2(idx, 'reject');
}

function initRowSelect2(idx, type) {
  var tableId = type === 'weight' ? '#weightDetailsTable' : '#rejectDetailsTable';
  var selectName = type === 'weight' ? `weightDetails[${idx}][product]` : `rejectDetails[${idx}][product]`;
  var newSelect = $(tableId).find(`select[name="${selectName}"]`);
  newSelect.data('original-options', newSelect.html());
  
  var selectedCategory = $('#category').val();
  if (selectedCategory) {
    newSelect.find('option').each(function() {
      if ($(this).val() && $(this).data('category') != selectedCategory) {
        $(this).remove();
      }
    });
  }

  $(tableId + ' .select2').select2({
    allowClear: true,
    placeholder: "<?=$languageArray['please_select_code'][$language]?>",
    dropdownParent: $('#extendModal .modal-content'),
    width: '100%'
  });
}

function filterGradesByProduct($select) {
  var row = $select.closest('tr');
  var productId = $select.val();
  var gradeSelect = row.find('select[name*="[to_grade]"]');
  var currentGrade = gradeSelect.val();

  gradeSelect.select2('destroy');
  if (!gradeSelect.data('original-options')) {
    gradeSelect.data('original-options', gradeSelect.html());
  }
  gradeSelect.html(gradeSelect.data('original-options'));

  if (productId) {
    gradeSelect.find('option').each(function() {
      var gradeProduct = $(this).attr('data-product');
      if (gradeProduct && gradeProduct != productId) {
        $(this).remove();
      }
    });
  }

  gradeSelect.select2({
    allowClear: true,
    placeholder: "<?=$languageArray['please_select_code'][$language]?>",
    dropdownParent: $('#extendModal .modal-content'),
    width: '100%'
  });
  gradeSelect.val(currentGrade).trigger('change');
}

function calculateNet(row, type) {
  var gross = parseFloat(row.find('input[name*="[gross]"]').val()) || 0;
  var tare = parseFloat(row.find('input[name*="[tare]"]').val()) || 0;
  var net = Math.abs(gross - tare);
  row.find('input[name*="[net]"]').val(net.toFixed(2)).trigger('change');
}

function updateWeightTotals() {
  var totalGross = 0, totalTare = 0, totalNet = 0;
  $('#weightDetailsTable tr').each(function() {
    totalGross += parseFloat($(this).find('input[name*="[gross]"]').val()) || 0;
    totalTare += parseFloat($(this).find('input[name*="[tare]"]').val()) || 0;
    totalNet += parseFloat($(this).find('input[name*="[net]"]').val()) || 0;
  });
  $('#totalWeightGross').text(totalGross.toFixed(2));
  $('#totalWeightTare').text(totalTare.toFixed(2));
  $('#totalWeightNet').text(totalNet.toFixed(2));
}

function updateRejectTotals() {
  var totalGross = 0, totalTare = 0, totalNet = 0;
  $('#rejectDetailsTable tr').each(function() {
    totalGross += parseFloat($(this).find('input[name*="[gross]"]').val()) || 0;
    totalTare += parseFloat($(this).find('input[name*="[tare]"]').val()) || 0;
    totalNet += parseFloat($(this).find('input[name*="[net]"]').val()) || 0;
  });
  $('#totalRejectGross').text(totalGross.toFixed(2));
  $('#totalRejectTare').text(totalTare.toFixed(2));
  $('#totalRejectNet').text(totalNet.toFixed(2));
}

function removeWeightDetail(button) {
  $(button).closest('tr').remove();
  reindexDetails('weight');
  updateWeightTotals();
}

function removeRejectDetail(button) {
  $(button).closest('tr').remove();
  reindexDetails('reject');
  updateRejectTotals();
}

function reindexDetails(type) {
  var tableId = type === 'weight' ? '#weightDetailsTable' : '#rejectDetailsTable';
  var prefix = type === 'weight' ? 'weightDetails' : 'rejectDetails';
  $(tableId + ' tr').each(function(index) {
    $(this).find('input, select').each(function() {
      var name = $(this).attr('name');
      if (name) {
        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
      }
    });
  });
}

// CRUD Functions
function newEntry() {
  weightCount = 0;
  rejectCount = 0;
  $('#extendModal').find('#id').val('');
  $('#extendModal').find('#gradingNo').val('');
  $('#startTimePicker').datetimepicker('date', moment());
  $('#endTimePicker').datetimepicker('clear');
  $('#extendModal').find('#remarks').val('');
  $('#extendModal').find('#category').val('').trigger('change');
  $('#extendModal').find('#location').val('').trigger('change');
  $('#totalWeightGross, #totalWeightTare, #totalWeightNet').text('0.00');
  $('#totalRejectGross, #totalRejectTare, #totalRejectNet').text('0.00');
  $('#weightDetailsTable, #rejectDetailsTable').empty();
  $('#extendModal').modal('show');
  initFormValidation('#extendForm');
}

function edit(id) {
  $('#spinnerLoading').show();
  weightCount = 0;
  rejectCount = 0;
  
  $.post('php/modules/grading/getGrading.php', {userID: id}, function(data) {
    var obj = JSON.parse(data);
    if (obj.status === 'success') {
      var msg = obj.message;
      $('#extendModal').find('#id').val(msg.id);
      $('#extendModal').find('#gradingNo').val(msg.grading_no);
      $('#extendModal').find('#remarks').val(msg.remark);
      $('#extendModal').find('#category').val(msg.product_category).trigger('change');
      $('#extendModal').find('#location').val(msg.location).trigger('change');

      if (msg.start_date) {
        $('#startTimePicker').datetimepicker('date', moment(msg.start_date, 'YYYY-MM-DD HH:mm:ss'));
      } else {
        $('#startTimePicker').datetimepicker('clear');
      }
      if (msg.end_date) {
        $('#endTimePicker').datetimepicker('date', moment(msg.end_date, 'YYYY-MM-DD HH:mm:ss'));
      } else {
        $('#endTimePicker').datetimepicker('clear');
      }

      // Populate weight details
      $('#weightDetailsTable').empty();
      if (msg.weightDetails && msg.weightDetails.length > 0) {
        var totalGross = 0, totalTare = 0, totalNet = 0;
        for (var i = 0; i < msg.weightDetails.length; i++) {
          var d = msg.weightDetails[i];
          var idx = weightCount++;
          var row = buildEditRow(d, idx, 'weight');
          $('#weightDetailsTable').append(row);
          setRowValues(idx, d, 'weight', msg.product_category);
          totalGross += parseFloat(d.gross_weight) || 0;
          totalTare += parseFloat(d.tare_weight) || 0;
          totalNet += parseFloat(d.nett_weight) || 0;
        }
        $('#totalWeightGross').text(totalGross.toFixed(2));
        $('#totalWeightTare').text(totalTare.toFixed(2));
        $('#totalWeightNet').text(totalNet.toFixed(2));
      }

      // Populate reject details
      $('#rejectDetailsTable').empty();
      if (msg.rejectDetails && msg.rejectDetails.length > 0) {
        var rejGross = 0, rejTare = 0, rejNet = 0;
        for (var j = 0; j < msg.rejectDetails.length; j++) {
          var r = msg.rejectDetails[j];
          var ridx = rejectCount++;
          var rrow = buildEditRow(r, ridx, 'reject');
          $('#rejectDetailsTable').append(rrow);
          setRowValues(ridx, r, 'reject', msg.product_category);
          rejGross += parseFloat(r.gross_weight) || 0;
          rejTare += parseFloat(r.tare_weight) || 0;
          rejNet += parseFloat(r.nett_weight) || 0;
        }
        $('#totalRejectGross').text(rejGross.toFixed(2));
        $('#totalRejectTare').text(rejTare.toFixed(2));
        $('#totalRejectNet').text(rejNet.toFixed(2));
      }

      // Reinitialize Select2
      $('.select2').each(function() {
        $(this).select2({
          allowClear: true,
          placeholder: "<?=$languageArray['please_select_code'][$language]?>",
          dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-content') : undefined
        });
      });

      $('#extendModal').modal('show');
      initFormValidation('#extendForm');
    } else {
      toastr.error(obj.message || 'Error loading data', 'Failed:');
    }
    $('#spinnerLoading').hide();
  });
}

function buildEditRow(detail, idx, type) {
  var prefix = type === 'weight' ? 'weightDetails' : 'rejectDetails';
  var photoPrefix = type === 'weight' ? 'photoFiles' : 'rejectPhotoFiles';
  var timeVal = detail.weighing_time || '';
  
  if (type === 'reject') {
    return `
      <tr class="details">
        <input type="hidden" name="${prefix}[${idx}][gradingItemId]" value="${detail.id || ''}">
        <td>
          <select class="form-control form-control-sm select2" id="rejectProduct${idx}" name="${prefix}[${idx}][product]">
            <option value="" selected disabled><?=$languageArray['select_product_code'][$language] ?? 'Select Product'?></option>
            ${productOptions}
          </select>
        </td>
        <td>
          <input type="hidden" name="${prefix}[${idx}][grade]" value="REJ">
          <span class="badge badge-danger">REJ</span>
        </td>
        <td><input type="number" class="form-control form-control-sm" name="${prefix}[${idx}][gross]" value="${(parseFloat(detail.gross_weight)||0).toFixed(2)}" step="0.01"></td>
        <td><input type="number" class="form-control form-control-sm" name="${prefix}[${idx}][tare]" value="${(parseFloat(detail.tare_weight)||0).toFixed(2)}" step="0.01"></td>
        <td><input type="number" class="form-control form-control-sm" name="${prefix}[${idx}][net]" value="${(parseFloat(detail.nett_weight)||0).toFixed(2)}" step="0.01" readonly></td>
        <td><input type="time" class="form-control form-control-sm" name="${prefix}[${idx}][time]" value="${timeVal}"></td>
        ${allowPhoto == 'Y' ? `<td>
          <input type="hidden" name="${prefix}[${idx}][photoPath]" value="${detail.photo_path || ''}">
          <input type="file" name="${photoPrefix}[${idx}]" id="rejectPhotoFile${idx}" accept=".png,.jpg,.jpeg" style="display:none">
          ${detail.photo_path ? '<a href="php/viewPhoto.php?file=' + detail.photo_path + '" target="_blank" class="btn btn-outline-success btn-sm mr-1"><i class="fas fa-image"></i></a>' : ''}
          <button type="button" class="btn btn-outline-info btn-sm" onclick="$('#rejectPhotoFile${idx}').click()"><i class="fas fa-camera"></i></button>
          <span id="rejectPhotoStatus${idx}"></span>
        </td>` : ''}
        <td>
          <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeRejectDetail(this)"><i class="fas fa-trash"></i></button>
        </td>
      </tr>
    `;
  }
  
  return `
    <tr class="details">
      <input type="hidden" name="${prefix}[${idx}][gradingItemId]" value="${detail.id || ''}">
      <td>
        <select class="form-control form-control-sm select2" id="product${idx}" name="${prefix}[${idx}][product]">
          <option value="" selected disabled><?=$languageArray['select_product_code'][$language] ?? 'Select Product'?></option>
          ${productOptions}
        </select>
      </td>
      <td>
        <select class="form-control form-control-sm select2" id="to_grade${idx}" name="${prefix}[${idx}][to_grade]">
          ${gradeOptions}
        </select>
      </td>
      <td><input type="number" class="form-control form-control-sm" name="${prefix}[${idx}][gross]" value="${(parseFloat(detail.gross_weight)||0).toFixed(2)}" step="0.01"></td>
      <td><input type="number" class="form-control form-control-sm" name="${prefix}[${idx}][tare]" value="${(parseFloat(detail.tare_weight)||0).toFixed(2)}" step="0.01"></td>
      <td><input type="number" class="form-control form-control-sm" name="${prefix}[${idx}][net]" value="${(parseFloat(detail.nett_weight)||0).toFixed(2)}" step="0.01" readonly></td>
      <td><input type="time" class="form-control form-control-sm" name="${prefix}[${idx}][time]" value="${timeVal}"></td>
      ${allowPhoto == 'Y' ? `<td>
        <input type="hidden" name="${prefix}[${idx}][photoPath]" value="${detail.photo_path || ''}">
        <input type="file" name="${photoPrefix}[${idx}]" id="photoFile${idx}" accept=".png,.jpg,.jpeg" style="display:none">
        ${detail.photo_path ? '<a href="php/viewPhoto.php?file=' + detail.photo_path + '" target="_blank" class="btn btn-outline-success btn-sm mr-1"><i class="fas fa-image"></i></a>' : ''}
        <button type="button" class="btn btn-outline-info btn-sm" onclick="$('#photoFile${idx}').click()"><i class="fas fa-camera"></i></button>
        <span id="photoStatus${idx}"></span>
      </td>` : ''}
      <td>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeWeightDetail(this)"><i class="fas fa-trash"></i></button>
      </td>
    </tr>
  `;
}

function setRowValues(idx, detail, type, selectedCategory) {
  var tableId = type === 'weight' ? '#weightDetailsTable' : '#rejectDetailsTable';
  var prefix = type === 'weight' ? 'weightDetails' : 'rejectDetails';
  
  var productSelect = $(tableId).find(`select[name="${prefix}[${idx}][product]"]`);
  productSelect.data('original-options', productSelect.html());
  if (selectedCategory) {
    productSelect.find('option').each(function() {
      if ($(this).val() && $(this).data('category') != selectedCategory) {
        $(this).remove();
      }
    });
  }
  productSelect.val(detail.product_id);

  if (type === 'weight') {
    var gradeSelect = $(tableId).find(`select[name="${prefix}[${idx}][to_grade]"]`);
    gradeSelect.data('original-options', gradeSelect.html());
    gradeSelect.find('option').each(function() {
      var gradeProduct = $(this).attr('data-product');
      if (gradeProduct && gradeProduct != detail.product_id) {
        $(this).remove();
      }
    });
    gradeSelect.val(detail.to_grade);
  }
}

function deactivate(id) {
  Swal.fire({
    title: '<?=$languageArray['confirm_delete_code'][$language] ?? 'Confirm Delete'?>',
    text: '<?=$languageArray['delete_confirm_message_code'][$language] ?? 'Are you sure you want to delete this item?'?>',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: '<?=$languageArray['yes_delete_code'][$language] ?? 'Yes, delete it'?>',
    cancelButtonText: '<?=$languageArray['cancel_code'][$language]?>'
  }).then((result) => {
    if (result.isConfirmed) {
      $('#cancelModal').find('#cancelId').val(id);
      $('#cancelModal').find('#cancelReason').val('');
      $('#cancelModal').modal('show');
      initFormValidation('#cancelForm');
    }
  });
}

function print(id) {
  $('#printID').val(id);
  $('#printOptionsModal').modal('show');
  initFormValidation('#printOptionsForm');
}

function initFormValidation(formId) {
  $(formId).validate({
    errorElement: 'span',
    errorPlacement: function(error, element) {
      error.addClass('invalid-feedback');
      element.closest('.form-group-modern, .form-group').append(error);
    },
    highlight: function(element) {
      $(element).addClass('is-invalid');
    },
    unhighlight: function(element) {
      $(element).removeClass('is-invalid');
    }
  });
}

// Filter Functions
function filterWeightTable(rowId) {
  var productFilter = $('#productFilter_' + rowId).val();
  var gradeFilter = $('#gradeFilter_' + rowId).val();
  var totalGross = 0, totalTare = 0, totalNet = 0;

  $('#weightTable_' + rowId + ' tbody tr').each(function() {
    var product = $(this).find('td:eq(0)').text();
    var grade = $(this).find('td:eq(1)').text().trim();
    var showProduct = !productFilter || product == productFilter;
    var showGrade = !gradeFilter || grade == gradeFilter;
    var show = showProduct && showGrade;
    $(this).toggle(show);

    if (show) {
      totalGross += parseFloat($(this).find('td:eq(2)').text()) || 0;
      totalTare += parseFloat($(this).find('td:eq(3)').text()) || 0;
      totalNet += parseFloat($(this).find('td:eq(4)').text()) || 0;
    }
  });

  $('#weightTable_' + rowId + ' tfoot th:eq(1)').text(totalGross.toFixed(2));
  $('#weightTable_' + rowId + ' tfoot th:eq(2)').text(totalTare.toFixed(2));
  $('#weightTable_' + rowId + ' tfoot th:eq(3)').text(totalNet.toFixed(2));

  // Update grade filter options based on product
  updateGradeFilterOptions(rowId, productFilter);
}

function updateGradeFilterOptions(rowId, productFilter) {
  var gradeSelect = $('#gradeFilter_' + rowId);
  var currentGrade = gradeSelect.val();
  gradeSelect.find('option:not(:first)').remove();

  var grades = [];
  $('#weightTable_' + rowId + ' tbody tr').each(function() {
    var product = $(this).find('td:eq(0)').text();
    var grade = $(this).find('td:eq(1)').text().trim();
    if ((!productFilter || product === productFilter) && grades.indexOf(grade) === -1) {
      grades.push(grade);
    }
  });

  grades.sort().forEach(function(grade) {
    gradeSelect.append('<option value="' + grade + '">' + grade + '</option>');
  });
  gradeSelect.val(currentGrade);
}

function populateFilters(rowId, weightDetails) {
  var products = {}, grades = [];
  weightDetails.forEach(function(d) {
    products[d.product_name] = true;
    if (grades.indexOf(d.grade) === -1) grades.push(d.grade);
  });

  var productSelect = $('#productFilter_' + rowId);
  for (var p in products) {
    productSelect.append('<option value="' + p + '">' + p + '</option>');
  }

  grades.sort();
  var gradeSelect = $('#gradeFilter_' + rowId);
  grades.forEach(function(g) {
    gradeSelect.append('<option value="' + g + '">' + g + '</option>');
  });
}
</script>
