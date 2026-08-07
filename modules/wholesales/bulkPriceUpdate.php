<?php
require_once '../../php/db_connect.php';
require_once '../../php/lookup.php';

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
}
else{
  $user = $_SESSION['userID'];
  $company = $_SESSION['customer'];
  $companyProducts = $_SESSION['products'];
  $stmt = $db->prepare("SELECT * from users where id = ?");
  $stmt->bind_param('s', $user);
  $stmt->execute();
  $result = $stmt->get_result();
  $role = 'NORMAL';
  $allowEdit = 'N';

  if(($row = $result->fetch_assoc()) !== null){
    $role = $row['role_code'];
    $allowEdit = $row['allow_edit'];
  }

  if ($role != 'SADMIN'){
    $productQuery = "SELECT p.* FROM products p INNER JOIN categories c ON p.category = c.id WHERE p.deleted = '0' AND p.customer = '$company' AND c.module IN ('wholesale', 'processing') AND c.deleted = '0' ORDER BY p.product_name ASC";
    if ($db->query($productQuery)->num_rows == 0) {
      $productQuery = "SELECT * FROM products WHERE deleted = '0' AND customer = '$company' ORDER BY product_name ASC";
    }
    $products = $db->query($productQuery);
    $grades = $db->query("SELECT DISTINCT g.*, p.product_name FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY p.product_name ASC, g.units ASC");
  } else {
    $products = $db->query("SELECT p.* FROM products p INNER JOIN categories c ON p.category = c.id WHERE p.deleted = '0' AND c.module IN ('wholesale', 'processing') AND c.deleted = '0' ORDER BY p.product_name ASC");
    $grades = $db->query("SELECT DISTINCT g.*, p.product_name FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC");
  }

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>

<div class="content-header custom-title-content-box">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6">
        <h1 class="custom-title"><?=$languageArray['bulk_price_update_code'][$language] ?? 'Bulk Price Update'?></h1>
      </div>
    </div>
  </div>
</div>

<div class="content custom-table-content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body custom-search-card-body">
            <div class="row">
              <div class="form-group col-md-3 col-sm-6">
                <label><?=$languageArray['date_code'][$language] ?? 'Date'?></label>
                <div class="input-group date" id="datePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#datePicker" id="date"/>
                  <div class="input-group-append" data-target="#datePicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>

              <div class="form-group col-md-3 col-sm-6">
                <label><?=$languageArray['product_code'][$language]?> <span class="text-danger">*</span></label>
                <select class="form-control select2" id="productFilter" required>
                  <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                  <?php while($p = mysqli_fetch_assoc($products)){ ?>
                    <option value="<?=$p['id']?>"><?=$p['product_name']?></option>
                  <?php } ?>
                </select>
              </div>

              <div class="form-group col-md-3 col-sm-6">
                <label><?=$languageArray['grade_code'][$language]?> <span class="text-danger">*</span></label>
                <select class="form-control select2" id="gradeFilter" required>
                  <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                  <?php while($g = mysqli_fetch_assoc($grades)){ ?>
                    <option value="<?=$g['units']?>" data-product="<?=$g['product_name']?>"><?=$g['units']?></option>
                  <?php } ?>
                </select>
              </div>

              <div class="form-group col-md-3 col-sm-6">
                <label><?=$languageArray['transaction_status_code'][$language]?></label>
                <select class="form-control" id="transactionStatusFilter">
                  <option value="DISPATCH" selected><?=$languageArray['dispatch_code'][$language]?></option>
                  <option value="RECEIVING"><?=$languageArray['receiving_code'][$language]?></option>
                  <?php if (in_array('stocks', $companyProducts)) { ?>
                  <option value="STOCK-BAL"><?=$languageArray['stock_balance_code'][$language]?></option>
                  <?php } ?>
                </select>
              </div>
            </div>

            <div class="row">
              <div class="col-md-9 col-sm-6"></div>
              <div class="col-md-3 col-sm-6">
                <button type="button" class="btn btn-block custom-search-btn btn-sm" id="filterSearch">
                  <i class="fas fa-search"></i> <?=$languageArray['search_code'][$language]?>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row" id="resultsCard" style="display:none;">
      <div class="col-lg-12">
        <div class="card card-info">
          <div class="card-header custom-card-header">
            <div class="row custom-card-header-row">
              <div class="col-10">
                <h5 class="custom-card-header-title"><?=$languageArray['results_code'][$language] ?? 'Results'?></h5>
              </div>
              <?php if($allowEdit == 'Y'){ ?>
              <div class="col-2">
                <button type="button" class="btn btn-block custom-search-btn btn-sm" onclick="openBulkPriceModal()">
                  <i class="fas fa-tags"></i> <?=$languageArray['update_price_code'][$language] ?? 'Update Price'?>
                </button>
              </div>
              <?php } ?>
            </div>
          </div>
          <div class="card-body custom-table-card-body">
            <table id="weightTable" class="table table-bordered table-striped display" style="width:100%">
              <thead>
                <tr>
                  <th width="30"></th>
                  <th><?=$languageArray['serial_no_code'][$language]?></th>
                  <th><?=$languageArray['do_po_no_code'][$language]?></th>
                  <th><?=$languageArray['customer_supplier_code'][$language]?></th>
                  <th><?=$languageArray['start_time_code'][$language]?></th>
                  <th><?=$languageArray['transaction_status_code'][$language]?></th>
                  <th><?=$languageArray['items_code'][$language]?></th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bulk Price Update Modal -->
<div class="modal fade" id="bulkPriceModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content custom-model-content-box">
      <form role="form" id="bulkPriceForm">
        <div class="modal-header custom-model-header-box">
          <h4 class="modal-title custom-model-title-txt"><?=$languageArray['update_price_code'][$language] ?? 'Update Price'?></h4>
          <button type="button" class="close custom-btn-close-icon" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body custom-model-body-box">

          <!-- Wizard Steps Indicator -->
          <div class="wizard-container">
            <div class="wizard-step active" id="wizardStep1">
              <div class="wizard-step-circle">1</div>
              <div class="wizard-step-label"><?=$languageArray['set_price_code'][$language] ?? 'Set Price'?></div>
            </div>
            <div class="wizard-step-line" id="wizardLine1"></div>
            <div class="wizard-step" id="wizardStep2">
              <div class="wizard-step-circle">2</div>
              <div class="wizard-step-label"><?=$languageArray['preview_code'][$language]?></div>
            </div>
            <div class="wizard-step-line" id="wizardLine2"></div>
            <div class="wizard-step" id="wizardStep3">
              <div class="wizard-step-circle">3</div>
              <div class="wizard-step-label"><?=$languageArray['confirm_code'][$language] ?? 'Confirm'?></div>
            </div>
          </div>

          <!-- Step 1: inputs -->
          <div id="stepInputs">
            <div class="form-group">
              <label><?=$languageArray['pricing_type_code'][$language] ?? 'Pricing Type'?> <span class="text-danger">*</span></label>
              <select class="form-control" id="bulkPricingType" name="bulkPricingType" required>
                <option value="Float"><?=$languageArray['float_code'][$language]?></option>
                <option value="Fixed"><?=$languageArray['fixed_code'][$language]?></option>
              </select>
            </div>
            <div class="form-group">
              <label><?=$languageArray['new_price_code'][$language] ?? 'New Price'?> <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="bulkNewPrice" name="bulkNewPrice" step="0.01" min="0" required placeholder="0.00">
            </div>
          </div>

          <!-- Step 2: preview -->
          <div id="stepPreview" style="display:none;">
            <div class="alert alert-info py-2 mb-3">
              <i class="fas fa-info-circle"></i> <?=$languageArray['review_changes_message_code'][$language] ?? 'Review the changes below before confirming.'?>
            </div>
            <div class="table-responsive" style="max-height:350px; overflow-y:auto;">
              <table class="table table-bordered table-striped table-sm mb-0">
                <thead class="thead-light">
                  <tr>
                    <th><?=$languageArray['serial_no_code'][$language]?></th>
                    <th><?=$languageArray['start_time_code'][$language]?></th>
                    <th><?=$languageArray['product_code'][$language]?></th>
                    <th><?=$languageArray['grade_code'][$language]?></th>
                    <th class="text-right"><?=$languageArray['net_code'][$language]?></th>
                    <th class="text-right"><?=$languageArray['old_price_code'][$language] ?? 'Old Price'?></th>
                    <th class="text-right"><?=$languageArray['old_total_code'][$language] ?? 'Old Total'?></th>
                    <th class="text-right"><?=$languageArray['new_price_code'][$language] ?? 'New Price'?></th>
                    <th class="text-right"><?=$languageArray['new_total_code'][$language] ?? 'New Total'?></th>
                  </tr>
                </thead>
                <tbody id="previewTableBody"></tbody>
              </table>
            </div>
          </div>

          <!-- Step 3: confirm -->
          <div id="stepConfirm" style="display:none;">
            <div class="alert alert-warning mb-0">
              <h5 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> <?=$languageArray['are_you_sure_code'][$language] ?? 'Are you sure?'?></h5>
              <p class="mb-0"><?=$languageArray['bulk_update_confirm_message_code'][$language] ?? 'You are about to update the price for'?> <strong id="confirmCount"></strong> <?=$languageArray['records_code'][$language] ?? 'record'?>.</p>
              <hr>
              <p class="mb-0 small"><i class="fas fa-info-circle"></i> <?=$languageArray['action_cannot_be_undone_code'][$language] ?? 'This action cannot be undone.'?></p>
            </div>
          </div>

        </div>
        <div class="modal-footer custom-model-fotter-box">
          <button type="button" class="btn custom-close-btn" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <div>
            <button type="button" class="btn custom-close-btn" id="btnBackToInputs" style="display:none;" onclick="wizardGoTo(1)"><i class="fas fa-arrow-left"></i> <?=$languageArray['back_code'][$language] ?? 'Back'?></button>
            <button type="submit" class="btn custom-search-btn" id="btnPreview"><i class="fas fa-eye"></i> <?=$languageArray['preview_code'][$language] ?? 'Preview'?></button>
            <button type="button" class="btn custom-close-btn" id="btnBackToPreview" style="display:none;" onclick="wizardGoTo(2)"><i class="fas fa-arrow-left"></i> <?=$languageArray['back_code'][$language] ?? 'Back'?></button>
            <button type="button" class="btn custom-search-btn" id="btnGoConfirm" style="display:none;" onclick="wizardGoTo(3)"><i class="fas fa-arrow-right"></i> <?=$languageArray['next_code'][$language] ?? 'Next'?></button>
            <button type="button" class="btn custom-save-btn" id="btnConfirm" style="display:none;" onclick="confirmBulkUpdate()"><i class="fas fa-check"></i> <?=$languageArray['confirm_update_code'][$language] ?? 'Confirm Update'?></button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
/* Wizard Styles */
.wizard-container { display:flex; align-items:flex-start; justify-content:center; margin-bottom:24px; padding:16px 0; }
.wizard-step { display:flex; flex-direction:column; align-items:center; flex:0 0 auto; min-width:80px; }
.wizard-step-circle { width:40px; height:40px; border-radius:50%; background:#dee2e6; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:16px; transition:all 0.2s ease; }
.wizard-step.active .wizard-step-circle { background:#007bff; box-shadow:0 0 0 4px rgba(0,123,255,0.2); }
.wizard-step.done .wizard-step-circle { background:#28a745; }
.wizard-step-label { font-size:12px; margin-top:8px; color:#6c757d; font-weight:500; text-align:center; }
.wizard-step.active .wizard-step-label { color:#007bff; font-weight:600; }
.wizard-step.done .wizard-step-label { color:#28a745; font-weight:600; }
.wizard-step-line { flex:1; height:3px; background:#dee2e6; margin:0 8px; margin-top:20px; min-width:40px; max-width:80px; transition:background 0.2s ease; }
.wizard-step-line.done { background:#28a745; }

/* Child row table */
#weightTable td.dt-center { vertical-align:middle; }
.child-table { background:#f8f9fa; }
.child-table th { background:#e9ecef; font-weight:600; font-size:13px; color:#212529; }
.child-table td { font-size:13px; }

/* Preview table alignment */
#previewTableBody td:nth-child(n+5) { text-align:right; }
</style>

<script>
// Variables
var table;
var colProduct = '<?=$languageArray["product_code"][$language]?>';
var colGrade   = '<?=$languageArray["grade_code"][$language]?>';
var colNet     = '<?=$languageArray["net_code"][$language]?>';
var colPrice   = '<?=$languageArray["price_code"][$language]?>';
var colTotal   = '<?=$languageArray["total_code"][$language]?>';

// document.ready
$(function() {
  toastr.options = {
    "closeButton": false,
    "progressBar": false,
    "positionClass": "toast-top-right",
    "timeOut": "5000"
  };

  $('#datePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: new Date()
  });

  $('.select2').select2({ 
    allowClear: true, 
    placeholder: "Please Select" 
  });

  $('#productFilter').on('change', function() {
    var productName = $(this).find('option:selected').text();

    $('#gradeFilter').select2('destroy');

    if (!$('#gradeFilter').data('original-options')) {
      $('#gradeFilter').data('original-options', $('#gradeFilter').html());
    }

    $('#gradeFilter').html($('#gradeFilter').data('original-options'));

    $('#gradeFilter option[data-product]').each(function() {
      if ($(this).data('product') !== productName) {
        $(this).remove();
      }
    });

    $('#gradeFilter').val('');
    $('#gradeFilter').select2({ allowClear: true, placeholder: "Please Select" });
  });

  $('#filterSearch').on('click', function() {
    if (!$('#productFilter').val()) {
      toastr["error"]("Please select a product.", "Validation Error:");
      return;
    }
    if (!$('#gradeFilter').val()) {
      toastr["error"]("Please select a grade.", "Validation Error:");
      return;
    }
    if (table) { table.clear().destroy(); }
    table = buildTable();
  });

  $(document).on('init.dt draw.dt', '#weightTable', function() {
    var info = table.page.info();
    $('#resultsCard').toggle(info.recordsTotal > 0);
  });

  $('#weightTable').on('click', 'td:first-child i', function() {
    var tr = $(this).closest('tr');
    var row = table.row(tr);
    if (row.child.isShown()) {
      row.child.hide();
      $(this).removeClass('fa-minus-circle text-warning').addClass('fa-plus-circle text-info');
    } else {
      row.child(format(row.data())).show();
      $(this).removeClass('fa-plus-circle text-info').addClass('fa-minus-circle text-warning');
    }
  });

  $.validator.setDefaults({
    submitHandler: function() {
      if ($('#bulkPriceModal').hasClass('show')) {
        $('#spinnerLoading').show();
        $.post('php/modules/wholesales/bulkPriceUpdate/bulkUpdatePrice.php', {
          mode: 'preview',
          date: $('#date').val(),
          product: $('#productFilter').val(),
          grade: $('#gradeFilter').val(),
          status: $('#transactionStatusFilter').val(),
          pricingType: $('#bulkPricingType').val(),
          newPrice: $('#bulkNewPrice').val()
        }, function(data) {
          var obj = JSON.parse(data);
          if (obj.status === 'success') {
            if (obj.rows.length === 0) {
              toastr["error"]("No matching records found.", "Preview:");
            } else {
              var tbody = $('#previewTableBody').empty();
              $.each(obj.rows, function(i, r) {
                tbody.append('<tr>' +
                  '<td>' + r.serial_no + '</td>' +
                  '<td>' + r.start_time + '</td>' +
                  '<td>' + r.product_name + '</td>' +
                  '<td>' + r.grade + '</td>' +
                  '<td class="text-right">' + r.net + '</td>' +
                  '<td class="text-right">' + r.old_price + '</td>' +
                  '<td class="text-right">' + r.old_total + '</td>' +
                  '<td class="text-right text-success font-weight-bold">' + r.new_price + '</td>' +
                  '<td class="text-right text-success font-weight-bold">' + r.new_total + '</td>' +
                '</tr>');
              });
              $('#confirmCount').text(obj.rows.length);
              wizardGoTo(2);
            }
          } else {
            toastr["error"](obj.message, "Failed:");
          }
          $('#spinnerLoading').hide();
        });
      }
    }
  });
});

// Functions
function format(d) {
  var html = '<table class="table table-sm table-bordered mb-0 child-table">'
    + '<thead><tr><th>' + colProduct + '</th><th>' + colGrade + '</th><th class="text-right">' + colNet + '</th><th class="text-right">' + colPrice + '</th><th class="text-right">' + colTotal + '</th></tr></thead><tbody>';
  $.each(d.items, function(i, item) {
    html += '<tr><td>' + item.product_name + '</td><td>' + item.grade + '</td><td class="text-right">' + item.net + '</td><td class="text-right">' + item.price + '</td><td class="text-right">' + item.total + '</td></tr>';
  });
  return html + '</tbody></table>';
}

function buildTable(){
  return $("#weightTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'order': [[2, 'asc']],
    'ajax': {
      'url': 'php/modules/wholesales/bulkPriceUpdate/filterBulkPrice.php',
      'data': {
        date: $('#date').val(),
        product: $('#productFilter').val(),
        grade: $('#gradeFilter').val(),
        status: $('#transactionStatusFilter').val()
      }
    },
    'columns': [
      {
        data: null, orderable: false, className: 'dt-center', width: '30px',
        render: function() {
          return '<i class="fas fa-plus-circle text-info" style="cursor:pointer;"></i>';
        }
      },
      { data: 'serial_no' },
      { data: 'po_no' },
      { data: 'customer_supplier' },
      { data: 'start_time' },
      { data: 'status' },
      { data: 'item_count' }
    ]
  });
}

function openBulkPriceModal() {
  $('#bulkNewPrice').val('');
  $('#bulkPricingType').val('Float');
  wizardGoTo(1);
  $('#bulkPriceModal').modal('show');

  $('#bulkPriceForm').validate({
    errorElement: 'span',
    errorPlacement: function(error, element) {
      error.addClass('invalid-feedback');
      element.closest('.form-group').append(error);
    },
    highlight: function(element) { $(element).addClass('is-invalid'); },
    unhighlight: function(element) { $(element).removeClass('is-invalid'); }
  });
}

function wizardGoTo(step) {
  $('#stepInputs, #stepPreview, #stepConfirm').hide();
  $('#btnPreview, #btnBackToInputs, #btnBackToPreview, #btnGoConfirm, #btnConfirm').hide();
  $('#wizardStep1, #wizardStep2, #wizardStep3').removeClass('active done');
  $('#wizardLine1, #wizardLine2').removeClass('done');

  if (step === 1) {
    $('#stepInputs').show();
    $('#btnPreview').show();
    $('#wizardStep1').addClass('active');
  } else if (step === 2) {
    $('#stepPreview').show();
    $('#btnBackToInputs, #btnGoConfirm').show();
    $('#wizardStep1').addClass('done');
    $('#wizardStep2').addClass('active');
    $('#wizardLine1').addClass('done');
  } else {
    $('#stepConfirm').show();
    $('#btnBackToPreview, #btnConfirm').show();
    $('#wizardStep1, #wizardStep2').addClass('done');
    $('#wizardStep3').addClass('active');
    $('#wizardLine1, #wizardLine2').addClass('done');
  }
}

function confirmBulkUpdate() {
  $('#spinnerLoading').show();
  $.post('php/modules/wholesales/bulkPriceUpdate/bulkUpdatePrice.php', {
    mode: 'save',
    date: $('#date').val(),
    product: $('#productFilter').val(),
    grade: $('#gradeFilter').val(),
    status: $('#transactionStatusFilter').val(),
    pricingType: $('#bulkPricingType').val(),
    newPrice: $('#bulkNewPrice').val()
  }, function(data) {
    var obj = JSON.parse(data);
    if (obj.status === 'success') {
      $('#bulkPriceModal').modal('hide');
      toastr["success"](obj.message, "Success:");
      table.clear().destroy();
      table = buildTable();
    } else {
      toastr["error"](obj.message, "Failed:");
    }
    $('#spinnerLoading').hide();
  });
}
</script>
