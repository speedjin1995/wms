<?php
require_once '../../php/db_connect.php';
require_once '../../php/lookup.php';

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
} else {
  $user    = $_SESSION['userID'];
  $company = $_SESSION['customer'];
  $role = $_SESSION['role'] ?? 'NORMAL';
  $userAllowPrice = $_SESSION['userAllowPrice'] ?? 'N';
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

  if ($role != 'SADMIN') {
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
    $grades = $db->query("SELECT DISTINCT g.*, p.product_name FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY p.product_name ASC, g.units ASC");
    $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' AND customer = '$company' ORDER BY locations ASC");
  } else {
    $categories = $db->query("SELECT * FROM categories WHERE deleted = '0' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $categories2 = $db->query("SELECT * FROM categories WHERE deleted = '0' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' ORDER BY locations ASC");
    $products  = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $products2  = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $grades = $db->query("SELECT DISTINCT g.*, p.product_name FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC");
  }

  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>

<div class="content page-modern">
  <div class="container-fluid">

    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title"><i class="fas fa-boxes"></i> <?=$languageArray['stock_balance_code'][$language]?></h1>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs nav-tabs-modern" id="stockTabs">
      <li class="nav-item">
        <a class="nav-link active" id="tab-report" data-toggle="tab" href="#paneReport">
          <i class="fas fa-file-alt"></i> <?=$languageArray['stock_balance_report_code'][$language]?>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="tab-adjustment" data-toggle="tab" href="#paneAdjustment">
          <i class="fas fa-sliders-h"></i> <?=$languageArray['stock_adjustment_code'][$language]?>
        </a>
      </li>
    </ul>

    <div class="tab-content">
      <!-- ── Tab 1: Stock Balance Report ── -->
      <div class="tab-pane fade show active" id="paneReport">

        <!-- Filter Card -->
        <div class="card filter-card">
          <div class="card-body">
            <div class="filter-row">
              <div class="filter-group">
                <label class="filter-label"><?=$languageArray['date_code'][$language]?></label>
                <div class="input-group date" id="datePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#datePicker" id="date"/>
                  <div class="input-group-append" data-target="#datePicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>

              <div class="filter-group">
                <label class="filter-label"><?=$languageArray['locations_code'][$language]?></label>
                <select class="form-control select2" id="locationFilter">
                  <option value="">-</option>
                  <?php while($row = mysqli_fetch_assoc($locations)) { ?>
                    <option value="<?=$row['id']?>"><?=$row['locations']?></option>
                  <?php } ?>
                </select>
              </div>

              <div class="filter-group">
                <label class="filter-label"><?=$languageArray['category_code'][$language]?></label>
                <select class="form-control select2" id="categoryFilter">
                  <option value="">-</option>
                  <?php while($row = mysqli_fetch_assoc($categories)) { ?>
                    <option value="<?=$row['id']?>"><?=$row['category_name']?></option>
                  <?php } ?>
                </select>
              </div>

              <div class="filter-group">
                <label class="filter-label"><?=$languageArray['product_code'][$language]?></label>
                <select class="form-control select2" id="productFilter">
                  <option value="">-</option>
                  <?php while($row = mysqli_fetch_assoc($products)) { ?>
                    <option value="<?=$row['id']?>" data-category="<?=$row['category']?>"><?=$row['product_name']?></option>
                  <?php } ?>
                </select>
              </div>

              <div class="filter-group filter-group-action" style="margin-left:auto;">
                <label class="filter-label">&nbsp;</label>
                <div class="d-flex" style="gap:0.5rem;">
                  <button type="button" class="btn btn-filter btn-filter-secondary" id="refreshBtn">
                    <i class="fas fa-sync-alt"></i> <?=$languageArray['refresh_code'][$language]?>
                  </button>
                  <button type="button" class="btn btn-filter btn-filter-primary" id="exportBtn">
                    <i class="fas fa-file-pdf"></i> <?=$languageArray['export_pdf_code'][$language]?>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Results Card -->
        <div class="card results-card">
          <div class="card-header">
            <div class="results-header-left">
              <h3 class="results-title"><i class="fas fa-file-alt"></i> <?=$languageArray['preview_code'][$language]?></h3>
            </div>
          </div>
          <div class="card-body p-0">
            <iframe id="previewFrame" src="" style="width:100%; height:80vh; border:none;"></iframe>
          </div>
        </div>

      </div><!-- /paneReport -->

      <!-- ── Tab 2: Stock Adjustment ── -->
      <div class="tab-pane fade" id="paneAdjustment">

        <!-- Filter Card -->
        <div class="card filter-card">
          <div class="card-body">
            <div class="filter-row">
              <div class="filter-group">
                <label class="filter-label"><?=$languageArray['date_from_code'][$language] ?? 'Date From'?></label>
                <div class="input-group date" id="adjDateFromPicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#adjDateFromPicker" id="adjDateFrom"/>
                  <div class="input-group-append" data-target="#adjDateFromPicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>
              <div class="filter-group">
                <label class="filter-label"><?=$languageArray['date_to_code'][$language] ?? 'Date To'?></label>
                <div class="input-group date" id="adjDateToPicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#adjDateToPicker" id="adjDateTo"/>
                  <div class="input-group-append" data-target="#adjDateToPicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>
              <div class="filter-group filter-group-action" style="margin-left:auto;">
                <label class="filter-label">&nbsp;</label>
                <div class="d-flex" style="gap:0.5rem;">
                  <button type="button" class="btn btn-filter btn-filter-secondary" id="loadAdjListBtn">
                    <i class="fas fa-search"></i> <?=$languageArray['search_code'][$language]?>
                  </button>
                  <button type="button" class="btn btn-filter btn-filter-primary" id="newAdjBtn">
                    <i class="fas fa-plus"></i> <?=$languageArray['new_adjustment_code'][$language] ?? 'New Adjustment'?>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Results Card -->
        <div class="card results-card show-dt-controls">
          <div class="card-header">
            <div class="results-header-left">
              <h3 class="results-title"><i class="fas fa-sliders-h"></i> <?=$languageArray['stock_adjustment_code'][$language]?></h3>
            </div>
          </div>
          <div class="card-body">
            <table class="table data-table" id="adjustListTable">
              <thead>
                <tr>
                  <th><?=$languageArray['adjustment_no_code'][$language] ?? 'Adjustment No'?></th>
                  <th><?=$languageArray['date_code'][$language]?></th>
                  <th><?=$languageArray['items_code'][$language] ?? 'Items'?></th>
                  <th><?=$languageArray['total_cost_code'][$language] ?? 'Total Cost'?></th>
                  <th><?=$languageArray['created_by_code'][$language] ?? 'Created By'?></th>
                  <th width="12%"><?=$languageArray['actions_code'][$language]?></th>
                </tr>
              </thead>
            </table>
          </div>
        </div>

      </div><!-- /paneAdjustment -->

      <!-- Stock Adjustment Modal -->
      <div class="modal fade modal-modern" id="adjModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="fas fa-sliders-h mr-2 text-muted"></i><?=$languageArray['stock_adjustment_code'][$language]?> - <span id="adjModalTitle"><?=$languageArray['new_code'][$language] ?? 'New'?></span></h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <input type="hidden" id="adjId">
              
              <!-- Header Info -->
              <div class="modal-section">
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group-modern">
                      <label class="form-label-modern"><?=$languageArray['adjustment_date_code'][$language] ?? 'Adjustment Date'?> <span class="text-danger">*</span></label>
                      <div class="input-group date" id="adjDatePicker" data-target-input="nearest">
                        <input type="text" class="form-control datetimepicker-input" data-target="#adjDatePicker" id="adjDate"/>
                        <div class="input-group-append" data-target="#adjDatePicker" data-toggle="datetimepicker">
                          <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-8">
                    <div class="form-group-modern">
                      <label class="form-label-modern"><?=$languageArray['remark_code'][$language]?></label>
                      <input type="text" class="form-control" id="adjRemark" placeholder="<?=$languageArray['enter_remark_code'][$language] ?? 'Enter remark'?>">
                    </div>
                  </div>
                </div>
              </div>

              <!-- Items Section -->
              <div class="modal-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div class="section-title mb-0"><i class="fas fa-list mr-2"></i><?=$languageArray['items_code'][$language] ?? 'Items'?></div>
                  <button type="button" class="btn btn-sm btn-success" id="addAdjItemBtn"><i class="fas fa-plus mr-1"></i><?=$languageArray['add_item_code'][$language] ?? 'Add Item'?></button>
                </div>
                <div class="table-responsive">
                  <table class="table table-bordered table-sm" id="adjItemsTable">
                    <thead style="background:#f8fafc;">
                      <tr>
                        <th style="width:25%;"><?=$languageArray['product_code'][$language]?></th>
                        <th style="width:15%;"><?=$languageArray['grade_code'][$language]?></th>
                        <th style="width:12%;" class="text-right"><?=$languageArray['current_qty_code'][$language] ?? 'Current Qty'?></th>
                        <th style="width:12%;" class="text-right"><?=$languageArray['adjust_qty_code'][$language] ?? 'Adjust Qty'?></th>
                        <th style="width:12%;" class="text-right"><?=$languageArray['new_qty_code'][$language] ?? 'New Qty'?></th>
                        <th style="width:10%;" class="text-right"><?=$languageArray['unit_cost_code'][$language] ?? 'Unit Cost'?></th>
                        <th style="width:10%;" class="text-right"><?=$languageArray['total_cost_code'][$language] ?? 'Total Cost'?></th>
                        <th style="width:15%;"><?=$languageArray['reason_code'][$language] ?? 'Reason'?></th>
                        <th style="width:5%;"></th>
                      </tr>
                    </thead>
                    <tbody id="adjItemsBody"></tbody>
                    <tfoot style="background:#f8fafc; font-weight:600;">
                      <tr>
                        <td colspan="6" class="text-right"><?=$languageArray['total_code'][$language] ?? 'Total'?>:</td>
                        <td class="text-right" id="adjTotalCost">0.00</td>
                        <td colspan="2"></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
                <div id="adjItemsEmpty" class="text-center text-muted py-4" style="display:none;">
                  <i class="fas fa-inbox fa-2x mb-2"></i>
                  <p class="mb-0"><?=$languageArray['no_items_added_code'][$language] ?? 'No items added. Click "Add Item" to begin.'?></p>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
              <button type="button" class="btn btn-modern btn-modern-success" id="saveAdjBtn"><i class="fas fa-save mr-1"></i><?=$languageArray['save_code'][$language]?></button>
            </div>
          </div>
        </div>
      </div>

      <!-- View Adjustment Modal -->
      <div class="modal fade modal-modern" id="viewAdjModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="fas fa-eye mr-2 text-muted"></i><?=$languageArray['view_adjustment_code'][$language] ?? 'View Adjustment'?> - <span id="viewAdjNo"></span></h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="info-section mb-3">
                <div class="row">
                  <div class="col-md-4"><span class="info-item-label"><?=$languageArray['date_code'][$language]?></span><span class="info-item-value" id="viewAdjDate"></span></div>
                  <div class="col-md-4"><span class="info-item-label"><?=$languageArray['created_by_code'][$language] ?? 'Created By'?></span><span class="info-item-value" id="viewAdjCreatedBy"></span></div>
                  <div class="col-md-4"><span class="info-item-label"><?=$languageArray['total_cost_code'][$language] ?? 'Total Cost'?></span><span class="info-item-value" id="viewAdjTotalCost"></span></div>
                </div>
                <div class="row mt-2">
                  <div class="col-12"><span class="info-item-label"><?=$languageArray['remark_code'][$language]?></span><span class="info-item-value" id="viewAdjRemark">-</span></div>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table table-bordered table-sm">
                  <thead style="background:#f8fafc;">
                    <tr>
                      <th><?=$languageArray['product_code'][$language]?></th>
                      <th><?=$languageArray['grade_code'][$language]?></th>
                      <th class="text-right"><?=$languageArray['qty_before_code'][$language] ?? 'Qty Before'?></th>
                      <th class="text-right"><?=$languageArray['adjustment_code'][$language] ?? 'Adjustment'?></th>
                      <th class="text-right"><?=$languageArray['qty_after_code'][$language] ?? 'Qty After'?></th>
                      <th class="text-right"><?=$languageArray['unit_cost_code'][$language] ?? 'Unit Cost'?></th>
                      <th class="text-right"><?=$languageArray['total_cost_code'][$language] ?? 'Total Cost'?></th>
                      <th><?=$languageArray['reason_code'][$language] ?? 'Reason'?></th>
                    </tr>
                  </thead>
                  <tbody id="viewAdjItemsBody"></tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /tab-content -->

  </div>
</div>

<!-- Adjustment Item Row Template -->
<script type="text/html" id="adjItemRowTemplate">
  <tr class="adj-item-row">
    <td><select class="form-control form-control-sm adj-product-select" id="adjProduct" style="width:100%;"></select></td>
    <td><select class="form-control form-control-sm adj-grade-select" id="adjGrade" style="width:100%;"><option value="">-</option></select></td>
    <td><input type="text" class="form-control form-control-sm text-right adj-current-qty" id="adjCurrentQty" readonly></td>
    <td><input type="number" step="0.01" class="form-control form-control-sm text-right adj-adjust-qty" id="adjAdjustQty" placeholder="+/-"></td>
    <td><input type="text" class="form-control form-control-sm text-right adj-new-qty" id="adjNewQty" readonly></td>
    <td><input type="number" step="0.01" class="form-control form-control-sm text-right adj-unit-cost" id="adjUnitCost" value="0"></td>
    <td class="text-right adj-total-cost">0.00</td>
    <td><input type="text" class="form-control form-control-sm adj-reason" id="adjReason"></td>
    <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-adj-item"><i class="fas fa-times"></i></button></td>
  </tr>
</script>

<script>
var adjListTable = null;
var adjItemRowCount = 0;
var productsData = [];

$(function () {
  $('.select2').select2({ 
    allowClear: true, 
    placeholder: 'Please Select' 
  });

  $('#datePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: new Date()
  });

  // ── Report Tab ──────────────────────────────────────────────────────────────
  $('#datePicker').on('change.datetimepicker', function () { 
    loadPreview(); 
  });

  $('#categoryFilter').on('change', function () {
    var selectedCategory = $(this).val();
    var productSelect = $('#productFilter');
    var currentVal = productSelect.val();
    productSelect.select2('destroy');
    if (!productSelect.data('original-options')) {
      productSelect.data('original-options', productSelect.html());
    }
    productSelect.html(productSelect.data('original-options'));
    if (selectedCategory) {
      productSelect.find('option').each(function () {
        if ($(this).val() && $(this).data('category') != selectedCategory) $(this).remove();
      });
    }
    if (currentVal && productSelect.find('option[value="' + currentVal + '"]').length) {
      productSelect.val(currentVal);
    } else {
      productSelect.val('');
    }
    productSelect.select2({ allowClear: true, placeholder: 'Please Select' });
  });

  $('#refreshBtn').on('click', function () { loadPreview(); });

  $('#exportBtn').on('click', function () {
    var date = $('#date').val();
    if (!date) { toastr["error"]("Please select a date.", "Validation Error:"); return; }
    window.open(buildUrl());
  });

  loadPreview();

  // ── Adjustment Tab ──────────────────────────────────────────────────────────
  $('#adjDateFromPicker, #adjDateToPicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: new Date()
  });

  $('#adjDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: new Date()
  });

  $('#loadAdjListBtn').on('click', function() { loadAdjustmentList(); });

  $('a[href="#paneAdjustment"]').on('shown.bs.tab', function () {
    if (!adjListTable) loadAdjustmentList();
  });

  $('#newAdjBtn').on('click', function() { openAdjustmentModal(); });
  $('#addAdjItemBtn').on('click', function() { addAdjustmentItemRow(); });
  $('#adjItemsBody').on('click', '.remove-adj-item', function() {
    $(this).closest('tr').remove();
    updateAdjustmentTotals();
    toggleAdjItemsEmpty();
  });

  $('#adjItemsBody').on('change', '.adj-product-select', function() {
    var $row = $(this).closest('tr');
    var productId = $(this).val();
    var $gradeSelect = $row.find('.adj-grade-select');
    var gradeHtml = '<option value="">-</option>';
    if (productId) {
      var product = productsData.find(function(p) { return p.id == productId; });
      if (product && product.grades) {
        product.grades.forEach(function(g) {
          gradeHtml += '<option value="' + g.grade_id + '" data-cost="' + (g.purchasing_price || 0) + '">' + g.grade_name + '</option>';
        });
      }
    }
    $gradeSelect.html(gradeHtml).trigger('change.select2');
    $row.find('.adj-current-qty, .adj-adjust-qty, .adj-new-qty').val('');
    $row.find('.adj-unit-cost').val('0');
    $row.find('.adj-total-cost').text('0.00');
  });

  $('#adjItemsBody').on('change', '.adj-grade-select', function() {
    var $row = $(this).closest('tr');
    var productId = $row.find('.adj-product-select').val();
    var gradeId = $(this).val();
    var defaultCost = $(this).find('option:selected').data('cost') || 0;
    $row.find('.adj-unit-cost').val(parseFloat(defaultCost).toFixed(2));
    if (productId && gradeId) {
      $.post('php/modules/wholesales/stockAdjustment/api.php', { action: 'balance', product_id: productId, grade: gradeId }, function(obj) {
        if (obj.status === 'success') {
          $row.find('.adj-current-qty').val(parseFloat(obj.balance).toFixed(2));
          if (obj.unit_cost > 0) $row.find('.adj-unit-cost').val(parseFloat(obj.unit_cost).toFixed(2));
        }
      });
    }
  });

  $('#adjItemsBody').on('input', '.adj-adjust-qty', function() {
    var $row = $(this).closest('tr');
    var currentQty = parseFloat($row.find('.adj-current-qty').val()) || 0;
    var adjustQty = parseFloat($(this).val()) || 0;
    $row.find('.adj-new-qty').val((currentQty + adjustQty).toFixed(2));
    updateRowTotalCost($row);
    updateAdjustmentTotals();
  });

  $('#adjItemsBody').on('input', '.adj-unit-cost', function() {
    updateRowTotalCost($(this).closest('tr'));
    updateAdjustmentTotals();
  });

  $('#saveAdjBtn').on('click', function() { saveAdjustment(); });

  // Set jQuery AJAX to expect JSON responses
  $.ajaxSetup({ dataType: 'json' });

  loadProductsData();
});

function buildUrl() {
  var date     = $('#date').val();
  var category = $('#categoryFilter').val() || '';
  var location = $('#locationFilter').val() || '';
  var product  = $('#productFilter').val()  || '';
  return 'php/modules/wholesales/exportStockBalance.php?asAtDate=' + encodeURIComponent(date) + '&category=' + category + '&location=' + location + '&product=' + product;
}

function loadPreview() {
  var date = $('#date').val();
  if (!date) { toastr["error"]("Please select a date.", "Validation Error:"); return; }
  $('#previewFrame').attr('src', buildUrl());
}

function loadProductsData() {
  $.post('php/modules/wholesales/stockAdjustment/api.php', { action: 'products' }, function(obj) {
    if (obj.status === 'success') productsData = obj.data;
  });
}

function loadAdjustmentList() {
  if (adjListTable) { adjListTable.clear().destroy(); adjListTable = null; }
  adjListTable = $('#adjustListTable').DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: false,
    language: {
      emptyTable: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>'
    },
    ajax: {
      url: 'php/modules/wholesales/stockAdjustment/api.php',
      type: 'POST',
      data: { action: 'list', date_from: $('#adjDateFrom').val() || '', date_to: $('#adjDateTo').val() || '' },
      dataSrc: function(json) { return json.status === 'success' ? json.data : []; }
    },
    columns: [
      { data: 'adjustment_no' },
      { data: 'adjustment_date_display' },
      { data: 'total_items', className: 'text-center' },
      { data: 'total_cost', className: 'text-right', render: function(d) { return parseFloat(d).toFixed(2); } },
      { data: 'created_by_name', defaultContent: '-' },
      { data: null, orderable: false, render: function(d) {
          return '<div class="d-flex" style="gap:4px;"><button class="btn btn-sm btn-info view-adj-btn" data-id="' + d.id + '"><i class="fas fa-eye"></i></button><button class="btn btn-sm btn-success edit-adj-btn" data-id="' + d.id + '"><i class="fas fa-pen"></i></button><button class="btn btn-sm btn-danger delete-adj-btn" data-id="' + d.id + '"><i class="fas fa-trash"></i></button></div>';
        }
      }
    ]
  });

  $('#adjustListTable').off('click', '.view-adj-btn').on('click', '.view-adj-btn', function() { viewAdjustment($(this).data('id')); });
  $('#adjustListTable').off('click', '.edit-adj-btn').on('click', '.edit-adj-btn', function() { editAdjustment($(this).data('id')); });
  $('#adjustListTable').off('click', '.delete-adj-btn').on('click', '.delete-adj-btn', function() { deleteAdjustment($(this).data('id')); });
}

function openAdjustmentModal(id) {
  $('#adjId').val(id || '');
  $('#adjModalTitle').text(id ? '<?=$languageArray['edit_code'][$language] ?? 'Edit'?>' : '<?=$languageArray['new_code'][$language] ?? 'New'?>');
  $('#adjDate').val(moment().format('DD/MM/YYYY'));
  $('#adjRemark').val('');
  $('#adjItemsBody').html('');
  $('#adjTotalCost').text('0.00');
  adjItemRowCount = 0;
  toggleAdjItemsEmpty();
  $('#adjModal').modal('show');
}

function addAdjustmentItemRow(data, callback) {
  var idx = adjItemRowCount++;
  
  // Clone template and append
  var $template = $('#adjItemRowTemplate').clone();
  $('#adjItemsBody').append($template.html());
  
  var $row = $('#adjItemsBody').find('.adj-item-row:last');
  $row.attr('data-idx', idx);
  
  // Build product options
  var productOptions = '<option value="">-</option>';
  productsData.forEach(function(p) {
    productOptions += '<option value="' + p.id + '">' + (p.product_code ? p.product_code + ' - ' : '') + p.product_name + '</option>';
  });
  
  // Set unique IDs and populate product dropdown
  $row.find('#adjProduct').attr('id', 'adjProduct' + idx).html(productOptions);
  $row.find('#adjGrade').attr('id', 'adjGrade' + idx);
  $row.find('#adjCurrentQty').attr('id', 'adjCurrentQty' + idx);
  $row.find('#adjAdjustQty').attr('id', 'adjAdjustQty' + idx);
  $row.find('#adjNewQty').attr('id', 'adjNewQty' + idx);
  $row.find('#adjUnitCost').attr('id', 'adjUnitCost' + idx);
  $row.find('#adjReason').attr('id', 'adjReason' + idx);
  
  // Initialize Select2
  $row.find('.adj-product-select, .adj-grade-select').select2({ width: '100%', dropdownParent: $('#adjModal') });
  
  toggleAdjItemsEmpty();
  
  // Populate data if provided (edit mode)
  if (data) {
    $row.find('.adj-product-select').val(data.product_id).trigger('change');
    setTimeout(function() {
      $row.find('.adj-grade-select').val(data.grade).trigger('change.select2');
      $row.find('.adj-current-qty').val(parseFloat(data.quantity_before).toFixed(2));
      $row.find('.adj-adjust-qty').val(parseFloat(data.adjustment_qty).toFixed(2));
      $row.find('.adj-new-qty').val(parseFloat(data.quantity_after).toFixed(2));
      $row.find('.adj-unit-cost').val(parseFloat(data.unit_cost).toFixed(2));
      $row.find('.adj-total-cost').text(formatTotalCost(parseFloat(data.adjustment_qty), parseFloat(data.unit_cost)));
      $row.find('.adj-reason').val(data.reason || '');
      if (callback) callback();
    }, 200);
  }
}

function toggleAdjItemsEmpty() {
  var hasRows = $('#adjItemsBody tr').length > 0;
  $('#adjItemsTable').toggle(hasRows);
  $('#adjItemsEmpty').toggle(!hasRows);
}

function updateRowTotalCost($row) {
  var adjustQty = parseFloat($row.find('.adj-adjust-qty').val()) || 0;
  var unitCost = parseFloat($row.find('.adj-unit-cost').val()) || 0;
  $row.find('.adj-total-cost').text(formatTotalCost(adjustQty, unitCost));
}

function formatTotalCost(adjustQty, unitCost) {
  var total = adjustQty * unitCost;
  var sign = total >= 0 ? '+' : '';
  return sign + total.toFixed(2);
}

function updateAdjustmentTotals() {
  var total = 0;
  $('#adjItemsBody tr').each(function() {
    var text = $(this).find('.adj-total-cost').text().replace('+', '');
    total += parseFloat(text) || 0;
  });
  var sign = total >= 0 ? '+' : '';
  $('#adjTotalCost').text(sign + total.toFixed(2));
}

function saveAdjustment() {
  var adjDate = $('#adjDate').val();
  if (!adjDate) { toastr["error"]("Please select adjustment date.", "Validation Error:"); return; }
  var items = [];
  var hasError = false;
  $('#adjItemsBody tr').each(function() {
    var productId = $(this).find('.adj-product-select').val();
    var grade = $(this).find('.adj-grade-select').val();
    var adjustQty = $(this).find('.adj-adjust-qty').val();
    if (!productId || !grade) { hasError = true; return false; }
    items.push({
      product_id: productId,
      grade: grade,
      quantity_before: $(this).find('.adj-current-qty').val() || 0,
      adjustment_qty: adjustQty || 0,
      quantity_after: $(this).find('.adj-new-qty').val() || 0,
      unit_cost: $(this).find('.adj-unit-cost').val() || 0,
      reason: $(this).find('.adj-reason').val() || ''
    });
  });
  if (hasError) { toastr["error"]("Please select product and grade for all items.", "Validation Error:"); return; }
  if (items.length === 0) { toastr["error"]("Please add at least one item.", "Validation Error:"); return; }
  $('#spinnerLoading').show();
  $.post('php/modules/wholesales/stockAdjustment/api.php', {
    action: 'save',
    id: $('#adjId').val(),
    adjustment_date: adjDate,
    remark: $('#adjRemark').val(),
    items: JSON.stringify(items)
  }, function(obj) {
    $('#spinnerLoading').hide();
    if (obj.status === 'success') {
      toastr["success"](obj.message, "Success:");
      $('#adjModal').modal('hide');
      adjListTable.ajax.reload(null, false);
    } else {
      toastr["error"](obj.message, "Failed:");
    }
  });
}

function viewAdjustment(id) {
  $.post('php/modules/wholesales/stockAdjustment/api.php', { action: 'get', id: id }, function(obj) {
    if (obj.status === 'success') {
      var d = obj.data;
      $('#viewAdjNo').text(d.adjustment_no);
      $('#viewAdjDate').text(d.adjustment_date_display);
      $('#viewAdjCreatedBy').text(d.created_by_name || '-');
      $('#viewAdjTotalCost').text(parseFloat(d.total_cost).toFixed(2));
      $('#viewAdjRemark').text(d.remark || '-');
      var html = '';
      d.items.forEach(function(item) {
        var adjQty = parseFloat(item.adjustment_qty);
        var adjClass = adjQty >= 0 ? 'text-success' : 'text-danger';
        var adjSign = adjQty >= 0 ? '+' : '';
        html += '<tr><td>' + (item.product_code ? item.product_code + ' - ' : '') + item.product_name + '</td><td>' + (item.grade_name || '-') + '</td><td class="text-right">' + parseFloat(item.quantity_before).toFixed(2) + '</td><td class="text-right ' + adjClass + '">' + adjSign + adjQty.toFixed(2) + '</td><td class="text-right">' + parseFloat(item.quantity_after).toFixed(2) + '</td><td class="text-right">' + parseFloat(item.unit_cost).toFixed(2) + '</td><td class="text-right">' + parseFloat(item.total_cost).toFixed(2) + '</td><td>' + (item.reason || '-') + '</td></tr>';
      });
      $('#viewAdjItemsBody').html(html);
      $('#viewAdjModal').modal('show');
    } else {
      toastr["error"](obj.message, "Failed:");
    }
  });
}

function editAdjustment(id) {
  $.post('php/modules/wholesales/stockAdjustment/api.php', { action: 'get', id: id }, function(obj) {
    if (obj.status === 'success') {
      var d = obj.data;
      $('#adjId').val(d.id);
      $('#adjModalTitle').text('<?=$languageArray['edit_code'][$language] ?? 'Edit'?>');
      $('#adjDate').val(d.adjustment_date_display);
      $('#adjRemark').val(d.remark || '');
      $('#adjItemsBody').html('');
      adjItemRowCount = 0;
      var itemsToAdd = d.items.length;
      var itemsAdded = 0;
      d.items.forEach(function(item) {
        addAdjustmentItemRow(item, function() {
          itemsAdded++;
          if (itemsAdded === itemsToAdd) updateAdjustmentTotals();
        });
      });
      $('#adjModal').modal('show');
    } else {
      toastr["error"](obj.message, "Failed:");
    }
  });
}

function deleteAdjustment(id) {
  Swal.fire({
    title: '<?=$languageArray['confirm_delete_code'][$language] ?? 'Confirm Delete'?>',
    text: '<?=$languageArray['delete_adjustment_confirm_code'][$language] ?? 'Are you sure you want to delete this adjustment? Stock will be reversed.'?>',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: '<?=$languageArray['delete_code'][$language] ?? 'Delete'?>'
  }).then(function(result) {
    if (result.isConfirmed) {
      $('#spinnerLoading').show();
      $.post('php/modules/wholesales/stockAdjustment/api.php', { action: 'delete', id: id }, function(obj) {
        $('#spinnerLoading').hide();
        if (obj.status === 'success') {
          toastr["success"](obj.message, "Success:");
          adjListTable.ajax.reload(null, false);
        } else {
          toastr["error"](obj.message, "Failed:");
        }
      });
    }
  });
}
</script>
