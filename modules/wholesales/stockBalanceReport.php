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
  $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->bind_param('s', $user);
  $stmt->execute();
  $row  = $stmt->get_result()->fetch_assoc();
  $role = $row['role_code'] ?? 'NORMAL';

  if ($role != 'SADMIN') {
    $categories = $db->query("SELECT * FROM categories WHERE deleted = '0' AND customer = '$company' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $categories2 = $db->query("SELECT * FROM categories WHERE deleted = '0' AND customer = '$company' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' AND customer = '$company' ORDER BY locations ASC");
    $products  = $db->query("SELECT * FROM products WHERE deleted = '0' AND customer = '$company' ORDER BY product_name ASC");
    $products2  = $db->query("SELECT * FROM products WHERE deleted = '0' AND customer = '$company' ORDER BY product_name ASC");
    $grades = $db->query("SELECT DISTINCT g.*, p.product_name FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY p.product_name ASC, g.units ASC");
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

<div class="content-header custom-title-content-box">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6">
        <h1 class="custom-title"><?=$languageArray['stock_balance_code'][$language]?></h1>
      </div>
    </div>
  </div>
</div>

<div class="content custom-table-content">
  <div class="container-fluid">

    <!-- Tabs -->
    <ul class="nav nav-tabs custom-nav-tabs mb-3" id="stockTabs">
      <li class="nav-item">
        <a class="nav-link active" id="tab-report" data-toggle="tab" href="#paneReport">
          <i class="fas fa-file-alt mr-1"></i> <?=$languageArray['stock_balance_report_code'][$language]?>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="tab-adjustment" data-toggle="tab" href="#paneAdjustment">
          <i class="fas fa-sliders-h mr-1"></i> <?=$languageArray['stock_adjustment_code'][$language]?>
        </a>
      </li>
    </ul>

    <div class="tab-content">
      <!-- ── Tab 1: Stock Balance Report ── -->
      <div class="tab-pane fade show active" id="paneReport">

        <div class="row">
          <div class="col-lg-12">
            <div class="card">
              <div class="card-body custom-search-card-body">
                <div class="row">
                  <div class="form-group col-3">
                    <label><?=$languageArray['date_code'][$language]?></label>
                    <div class="input-group date" id="datePicker" data-target-input="nearest">
                      <input type="text" class="form-control datetimepicker-input" data-target="#datePicker" id="date"/>
                      <div class="input-group-append" data-target="#datePicker" data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                      </div>
                    </div>
                  </div>

                  <div class="col-3">
                    <div class="form-group">
                      <label><?=$languageArray['locations_code'][$language]?></label>
                      <select class="form-control select2" id="locationFilter">
                        <option value="">-</option>
                        <?php while($row = mysqli_fetch_assoc($locations)) { ?>
                          <option value="<?=$row['id']?>"><?=$row['locations']?></option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>

                  <div class="col-3">
                    <div class="form-group">
                      <label><?=$languageArray['category_code'][$language]?></label>
                      <select class="form-control select2" id="categoryFilter">
                        <option value="">-</option>
                        <?php while($row = mysqli_fetch_assoc($categories)) { ?>
                          <option value="<?=$row['id']?>"><?=$row['category_name']?></option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>

                  <div class="form-group col-3">
                    <label><?=$languageArray['product_code'][$language]?></label>
                    <select class="form-control select2" id="productFilter">
                      <option value="">-</option>
                      <?php while($row = mysqli_fetch_assoc($products)) { ?>
                        <option value="<?=$row['id']?>" data-category="<?=$row['category']?>"><?=$row['product_name']?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="row">
                  <div class="col-6"></div>
                  <div class="col-3">
                    <button type="button" class="btn btn-block custom-view-btn-sm btn-sm" id="refreshBtn">
                      <i class="fas fa-sync-alt"></i> <?=$languageArray['refresh_code'][$language]?>
                    </button>
                  </div>
                  <div class="col-3">
                    <button type="button" class="btn btn-block custom-export-btn btn-sm" id="exportBtn">
                      <i class="fas fa-file-pdf"></i> <?=$languageArray['export_pdf_code'][$language]?>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-lg-12">
            <div class="card">
              <div class="card-header custom-card-header">
                <h5 class="custom-card-header-title"><i class="fas fa-file-alt mr-1"></i> <?=$languageArray['preview_code'][$language]?></h5>
              </div>
              <div class="card-body p-0">
                <iframe id="previewFrame" src="" style="width:100%; height:80vh; border:none;"></iframe>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /paneReport -->

      <!-- ── Tab 2: Stock Adjustment ── -->
      <div class="tab-pane fade" id="paneAdjustment">
        <!-- Filters -->
        <div class="row">
          <div class="col-lg-12">
            <div class="card">
              <div class="card-body custom-search-card-body">
                <div class="row">
                  <div class="col-3">
                    <div class="form-group">
                      <label><?=$languageArray['date_code'][$language]?></label>
                      <div class="input-group date" id="adjDatePicker" data-target-input="nearest">
                        <input type="text" class="form-control datetimepicker-input" data-target="#adjDatePicker" id="adjDate"/>
                        <div class="input-group-append" data-target="#adjDatePicker" data-toggle="datetimepicker">
                          <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="form-group">
                      <label><?=$languageArray['category_code'][$language]?></label>
                      <select class="form-control select2" id="adjCategoryFilter">
                        <option value="">-</option>
                        <?php
                        while($r2 = mysqli_fetch_assoc($categories2)) { ?>
                          <option value="<?=$r2['id']?>"><?=$r2['category_name']?></option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="form-group">
                      <label><?=$languageArray['product_code'][$language]?></label>
                      <select class="form-control select2" id="adjProductFilter">
                        <option value="">-</option>
                        <?php
                        while($r2 = mysqli_fetch_assoc($products2)) { ?>
                          <option value="<?=$r2['id']?>" data-category="<?=$r2['category']?>"><?=$r2['product_name']?></option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="form-group">
                      <label>Grade</label>
                      <select class="form-control select2" id="adjGradeFilter">
                        <option value="">-</option>
                        <?php
                        while($r2 = mysqli_fetch_assoc($grades)) { ?>
                          <option value="<?=$r2['id']?>" data-product="<?=$r2['product_name']?>"><?=$r2['units']?></option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-12 mt-1">
                    <div class="row">
                      <div class="col-9"></div>
                      <div class="col-3">
                        <button type="button" class="btn btn-block custom-search-btn btn-sm" id="loadAdjBtn">
                          <i class="fas fa-search"></i> <?=$languageArray['search_code'][$language]?>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Table -->
        <div class="row">
          <div class="col-lg-12">
            <div class="card">
              <div class="card-header custom-card-header">
                <h5 class="custom-card-header-title"><i class="fas fa-sliders-h mr-1"></i> <?=$languageArray['stock_adjustment_code'][$language]?> &mdash; <span id="adjDateLabel"><?=date('d/m/Y')?></span></h5>
              </div>
              <div class="card-body custom-table-card-body">
                <table class="table table-bordered table-striped" id="adjustTable">
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
          </div>
        </div>

      </div><!-- /paneAdjustment -->

      <!-- Adjustment Modal -->
      <div class="modal fade" id="adjModal">
        <div class="modal-dialog" style="max-width:450px;">
          <div class="modal-content custom-model-content-box">
            <div class="modal-header custom-model-header-box">
              <h4 class="modal-title custom-model-title-txt">Stock Adjustment</h4>
              <button type="button" class="close custom-btn-close-icon" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body custom-model-body-box">
              <input type="hidden" id="adjId">
              <input type="hidden" id="adjProductId">
              <input type="hidden" id="adjGrade">
              <div class="form-group">
                <label>Product</label>
                <input type="text" class="form-control" id="adjProductDisplay" readonly>
              </div>
              <div class="form-group">
                <label>Grade</label>
                <input type="text" class="form-control" id="adjGradeDisplay" readonly>
              </div>
              <div class="form-group">
                <label>Current Balance (KG)</label>
                <input type="text" class="form-control" id="adjCurrentBalance" readonly>
              </div>
              <div class="form-group">
                <label>Adjust To (KG) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" class="form-control" id="adjNewBalance">
              </div>
            </div>
            <div class="modal-footer custom-model-fotter-box">
              <button type="button" class="btn custom-close-btn" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
              <button type="button" class="btn custom-save-btn" id="saveAdjBtn"><i class="fas fa-save"></i> <?=$languageArray['save_code'][$language]?></button>
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
          return '<button class="btn btn-sm btn-success open-adj-btn" ' +
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
