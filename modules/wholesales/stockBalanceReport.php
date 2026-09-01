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
                <label class="filter-label"><?=$languageArray['date_code'][$language]?></label>
                <div class="input-group date" id="adjDatePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#adjDatePicker" id="adjDate"/>
                  <div class="input-group-append" data-target="#adjDatePicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>

              <div class="filter-group">
                <label class="filter-label"><?=$languageArray['category_code'][$language]?></label>
                <select class="form-control select2" id="adjCategoryFilter">
                  <option value="">-</option>
                  <?php while($r2 = mysqli_fetch_assoc($categories2)) { ?>
                    <option value="<?=$r2['id']?>"><?=$r2['category_name']?></option>
                  <?php } ?>
                </select>
              </div>

              <div class="filter-group">
                <label class="filter-label"><?=$languageArray['product_code'][$language]?></label>
                <select class="form-control select2" id="adjProductFilter">
                  <option value="">-</option>
                  <?php while($r2 = mysqli_fetch_assoc($products2)) { ?>
                    <option value="<?=$r2['id']?>" data-category="<?=$r2['category']?>"><?=$r2['product_name']?></option>
                  <?php } ?>
                </select>
              </div>

              <div class="filter-group">
                <label class="filter-label"><?=$languageArray['grade_code'][$language] ?? 'Grade'?></label>
                <select class="form-control select2" id="adjGradeFilter">
                  <option value="">-</option>
                  <?php while($r2 = mysqli_fetch_assoc($grades)) { ?>
                    <option value="<?=$r2['id']?>" data-product="<?=$r2['product_name']?>"><?=$r2['units']?></option>
                  <?php } ?>
                </select>
              </div>

              <div class="filter-group filter-group-action" style="margin-left:auto;">
                <label class="filter-label">&nbsp;</label>
                <button type="button" class="btn btn-filter btn-filter-primary" id="loadAdjBtn">
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
              <h3 class="results-title"><i class="fas fa-sliders-h"></i> <?=$languageArray['stock_adjustment_code'][$language]?> &mdash; <span id="adjDateLabel"><?=date('d/m/Y')?></span></h3>
            </div>
          </div>
          <div class="card-body">
            <table class="table data-table" id="adjustTable">
              <thead>
                <tr>
                  <th><?=$languageArray['category_code'][$language]?></th>
                  <th><?=$languageArray['product_code'][$language]?></th>
                  <th><?=$languageArray['grade_code'][$language]?></th>
                  <th><?=$languageArray['balance_code'][$language]?> (KG)</th>
                  <th width="8%"><?=$languageArray['actions_code'][$language]?></th>
                </tr>
              </thead>
            </table>
          </div>
        </div>

      </div><!-- /paneAdjustment -->

      <!-- Adjustment Modal -->
      <div class="modal fade modal-modern" id="adjModal" tabindex="-1">
        <div class="modal-dialog" style="max-width:450px;">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="fas fa-sliders-h mr-2 text-muted"></i><?=$languageArray['stock_adjustment_code'][$language]?></h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body custom-model-body-box">
              <input type="hidden" id="adjId">
              <input type="hidden" id="adjProductId">
              <input type="hidden" id="adjGrade">
              <div class="form-group-modern">
                <label class="form-label-modern"><?=$languageArray['product_code'][$language]?></label>
                <input type="text" class="form-control" id="adjProductDisplay" readonly>
              </div>
              <div class="form-group-modern">
                <label class="form-label-modern"><?=$languageArray['grade_code'][$language]?></label>
                <input type="text" class="form-control" id="adjGradeDisplay" readonly>
              </div>
              <div class="form-group-modern">
                <label class="form-label-modern"><?=$languageArray['current_balance_code'][$language] ?? 'Current Balance'?> (KG)</label>
                <input type="text" class="form-control" id="adjCurrentBalance" readonly>
              </div>
              <div class="form-group-modern">
                <label class="form-label-modern"><?=$languageArray['adjust_to_code'][$language] ?? 'Adjust To'?> (KG) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" class="form-control" id="adjNewBalance">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
              <button type="button" class="btn btn-modern btn-modern-success" id="saveAdjBtn"><i class="fas fa-save"></i> <?=$languageArray['save_code'][$language]?></button>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /tab-content -->

  </div>
</div>

<script>
var adjTable = null;

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
  $('#datePicker').on('change.datetimepicker', function () { loadPreview(); });

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
  $('#adjCategoryFilter').select2({ allowClear: true, placeholder: 'Please Select' });
  $('#adjProductFilter').select2({ allowClear: true, placeholder: 'Please Select' });
  $('#adjGradeFilter').select2({ allowClear: true, placeholder: 'Please Select' });

  $('#adjDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: new Date()
  });

  $('#adjDatePicker').on('change.datetimepicker', function (e) {
    $('#adjDateLabel').text(e.date ? e.date.format('DD/MM/YYYY') : '');
  });

  // Filter product by category
  $('#adjCategoryFilter').on('change', function () {
    var selectedCategory = $(this).val();
    var ps = $('#adjProductFilter');
    ps.select2('destroy');
    if (!ps.data('adj-original-options')) ps.data('adj-original-options', ps.html());
    ps.html(ps.data('adj-original-options'));
    if (selectedCategory) {
      ps.find('option').each(function () {
        if ($(this).val() && $(this).data('category') != selectedCategory) $(this).remove();
      });
    }
    ps.val('').select2({ allowClear: true, placeholder: 'Please Select' });
    resetAdjGrades();
  });

  $('#adjProductFilter').on('change', function () {
    var productName = $(this).find('option:selected').text();
    var gs = $('#adjGradeFilter');
    gs.select2('destroy');
    if (!gs.data('original-options')) gs.data('original-options', gs.html());
    gs.html(gs.data('original-options'));
    if ($(this).val()) {
      gs.find('option').each(function () {
        var gradeProduct = $(this).data('product');
        if (gradeProduct && gradeProduct !== productName) $(this).remove();
      });
    }
    gs.val('').select2({ allowClear: true, placeholder: 'Please Select' });
  });

  $('#loadAdjBtn').on('click', function () { loadAdjustment(); });

  $('a[href="#paneAdjustment"]').on('shown.bs.tab', function () {
    if (!adjTable) loadAdjustment();
  });

  $('#saveAdjBtn').on('click', function () {
    var balance = $('#adjNewBalance').val();
    if (balance === '') { toastr["error"]("Please enter a balance value.", "Validation Error:"); return; }
    $('#spinnerLoading').show();
    $.post('php/modules/wholesales/stockAdjustment/saveStockAdjustment.php', {
      id:            $('#adjId').val(),
      product_id:    $('#adjProductId').val(),
      grade:         $('#adjGrade').val(),
      balance:       balance,
      today_balance: $('#adjCurrentBalance').val()
    }, function (data) {
      var obj = JSON.parse(data);
      $('#spinnerLoading').hide();
      if (obj.status === 'success') {
        toastr["success"](obj.message, "Success:");
        $('#adjModal').modal('hide');
        adjTable.ajax.reload(null, false);
      } else {
        toastr["error"](obj.message, "Failed:");
      }
    });
  });

  $(document).on('click', '.open-adj-btn', function () {
    var btn = $(this);
    $('#adjId').val(btn.data('id'));
    $('#adjProductId').val(btn.data('product'));
    $('#adjGrade').val(btn.data('grade'));
    $('#adjCurrentBalance').val(parseFloat(btn.data('balance')).toFixed(2));
    $('#adjProductDisplay').val(btn.data('product-label'));
    $('#adjGradeDisplay').val(btn.data('grade-label'));
    $('#adjNewBalance').val(parseFloat(btn.data('balance')).toFixed(2));
    $('#adjModal').modal('show');
  });
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
  if (!date) { 
    toastr["error"]("Please select a date.", "Validation Error:"); 
    return; 
  }
  $('#previewFrame').attr('src', buildUrl());
}

function resetAdjGrades() {
  var gs = $('#adjGradeFilter');
  gs.select2('destroy');
  if (!gs.data('original-options')) gs.data('original-options', gs.html());
  gs.html(gs.data('original-options'));
  gs.val('').select2({ allowClear: true, placeholder: 'Please Select' });
}

function loadAdjustment() {
  if (adjTable) { adjTable.clear().destroy(); adjTable = null; }
  adjTable = $('#adjustTable').DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: false,
    searching: false,
    language: {
      emptyTable: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
      zeroRecords: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
    },
    ajax: {
      url: 'php/modules/wholesales/stockAdjustment/getStockAdjustment.php',
      type: 'POST',
      data: {
        date:     $('#adjDate').val() || '',
        category: $('#adjCategoryFilter').val() || '',
        product:  $('#adjProductFilter').val()  || '',
        grade:    $('#adjGradeFilter').val()    || ''
      },
      dataSrc: function (json) {
        if (json.status !== 'success') { toastr["error"](json.message, "Failed:"); return []; }
        return json.data;
      }
    },
    columns: [
      { data: 'category_name', defaultContent: '-' },
      { data: null, render: function (d) { return d.product_code ? d.product_code + ' - ' + d.product_name : d.product_name; } },
      { data: 'grade_name', defaultContent: '-' },
      { data: 'balance', render: function (d) {
          var v = parseFloat(d).toFixed(2);
          return v < 0 ? '<span class="text-danger font-weight-bold">' + v + '</span>' : v;
        }
      },
      { data: null, orderable: false, render: function (d) {
          return '<button class="btn btn-sm custom-add-btn open-adj-btn" ' +
            'data-id="' + (d.id || '') + '" ' +
            'data-product="' + d.product_id + '" ' +
            'data-grade="' + (d.grade || '') + '" ' +
            'data-balance="' + d.balance + '" ' +
            'data-product-label="' + (d.product_code ? d.product_code + ' - ' + d.product_name : d.product_name) + '" ' +
            'data-grade-label="' + (d.grade_name || '-') + '">' +
            '<i class="fas fa-sliders-h"></i> Adjust</button>';
        }
      }
    ]
  });
}
</script>
