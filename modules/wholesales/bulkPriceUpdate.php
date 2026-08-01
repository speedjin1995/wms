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

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">Bulk Price Update</h1>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <div class="row">
              <div class="form-group col-3">
                <label><?=$languageArray['date_code'][$language] ?? 'Date'?>:</label>
                <div class="input-group date" id="datePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#datePicker" id="date"/>
                  <div class="input-group-append" data-target="#datePicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>

              <div class="col-3">
                <div class="form-group">
                  <label><?=$languageArray['product_code'][$language]?></label>
                  <select class="form-control select2" id="productFilter" required>
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($p = mysqli_fetch_assoc($products)){ ?>
                      <option value="<?=$p['id']?>"><?=$p['product_name']?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="col-3">
                <div class="form-group">
                  <label><?=$languageArray['grade_code'][$language]?></label>
                  <select class="form-control select2" id="gradeFilter" required>
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($g = mysqli_fetch_assoc($grades)){ ?>
                      <option value="<?=$g['units']?>" data-product="<?=$g['product_name']?>"><?=$g['units']?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="col-3">
                <div class="form-group">
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
            </div>

            <div class="row">
              <div class="col-9"></div>
              <div class="col-3">
                <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="filterSearch">
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
          <div class="card-header">
            <div class="row align-items-center">
              <div class="col-8">Results</div>
              <?php if($allowEdit == 'Y'){ ?>
              <div class="col-4 text-right">
                <button type="button" class="btn bg-gradient-primary btn-sm" onclick="openBulkPriceModal()">
                  <i class="fas fa-tags"></i> Update Price
                </button>
              </div>
              <?php } ?>
            </div>
          </div>
          <div class="card-body">
            <table id="weightTable" class="table table-bordered table-striped display">
              <thead>
                <tr>
                  <th></th>
                  <th><?=$languageArray['serial_no_code'][$language]?></th>
                  <th><?=$languageArray['start_time_code'][$language]?></th>
                  <th><?=$languageArray['transaction_status_code'][$language]?></th>
                  <th>Items</th>
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
    <div class="modal-content">
      <form role="form" id="bulkPriceForm">
        <div class="modal-header bg-gray-dark color-palette">
          <h4 class="modal-title">Update Price</h4>
          <button type="button" class="close bg-gray-dark color-palette" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">

          <!-- Wizard Steps Indicator -->
          <div class="d-flex align-items-center mb-4">
            <div class="wizard-step active" id="wizardStep1">
              <div class="wizard-step-circle">1</div>
              <div class="wizard-step-label">Set Price</div>
            </div>
            <div class="wizard-step-line"></div>
            <div class="wizard-step" id="wizardStep2">
              <div class="wizard-step-circle">2</div>
              <div class="wizard-step-label">Preview</div>
            </div>
            <div class="wizard-step-line"></div>
            <div class="wizard-step" id="wizardStep3">
              <div class="wizard-step-circle">3</div>
              <div class="wizard-step-label">Confirm</div>
            </div>
          </div>

          <!-- Step 1: inputs -->
          <div id="stepInputs">
            <div class="form-group">
              <label>Pricing Type *</label>
              <select class="form-control" id="bulkPricingType" name="bulkPricingType" required>
                <option value="Float">Float</option>
                <option value="Fixed">Fixed</option>
              </select>
            </div>
            <div class="form-group">
              <label>New Price *</label>
              <input type="number" class="form-control" id="bulkNewPrice" name="bulkNewPrice" step="0.01" min="0" required placeholder="0.00">
            </div>
          </div>

          <!-- Step 2: preview -->
          <div id="stepPreview" style="display:none;">
            <p class="text-muted mb-2">Review the changes below before confirming.</p>
            <div style="max-height:400px; overflow-y:auto;">
              <table class="table table-bordered table-striped table-sm">
                <thead>
                  <tr>
                    <th><?=$languageArray['serial_no_code'][$language]?></th>
                    <th><?=$languageArray['start_time_code'][$language]?></th>
                    <th><?=$languageArray['product_code'][$language]?></th>
                    <th><?=$languageArray['grade_code'][$language]?></th>
                    <th><?=$languageArray['net_code'][$language]?></th>
                    <th>Old Price</th>
                    <th>Old Total</th>
                    <th>New Price</th>
                    <th>New Total</th>
                  </tr>
                </thead>
                <tbody id="previewTableBody"></tbody>
              </table>
            </div>
          </div>

          <!-- Step 3: confirm -->
          <div id="stepConfirm" style="display:none;">
            <div class="alert alert-warning">
              <i class="fas fa-exclamation-triangle"></i>
              <strong> Are you sure?</strong> You are about to update the price for all <strong id="confirmCount"></strong> record(s) shown in the preview.
              This action <strong>cannot be undone</strong>. Please review the preview carefully before proceeding.
            </div>
          </div>

        </div>
        <div class="modal-footer justify-content-between bg-gray-dark color-palette">
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <div>
            <button type="button" class="btn btn-warning" id="btnBackToInputs" style="display:none;" onclick="wizardGoTo(1)"><i class="fas fa-arrow-left"></i> Back</button>
            <button type="submit" class="btn btn-primary" id="btnPreview"><i class="fas fa-eye"></i> Preview</button>
            <button type="button" class="btn btn-warning" id="btnBackToPreview" style="display:none;" onclick="wizardGoTo(2)"><i class="fas fa-arrow-left"></i> Back</button>
            <button type="button" class="btn btn-info" id="btnGoConfirm" style="display:none;" onclick="wizardGoTo(3)"><i class="fas fa-arrow-right"></i> Next</button>
            <button type="button" class="btn btn-success" id="btnConfirm" style="display:none;" onclick="confirmBulkUpdate()"><i class="fas fa-check"></i> Confirm Update</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.wizard-step { display:flex; flex-direction:column; align-items:center; flex:0 0 auto; }
.wizard-step-circle { width:36px; height:36px; border-radius:50%; background:#ced4da; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; }
.wizard-step.active .wizard-step-circle, .wizard-step.done .wizard-step-circle { background:#007bff; }
.wizard-step.done .wizard-step-circle { background:#28a745; }
.wizard-step-label { font-size:12px; margin-top:4px; color:#6c757d; }
.wizard-step.active .wizard-step-label { color:#007bff; font-weight:600; }
.wizard-step.done .wizard-step-label { color:#28a745; font-weight:600; }
.wizard-step-line { flex:1; height:2px; background:#ced4da; margin: 0 8px; margin-bottom:20px; }
.wizard-step.done .wizard-step-line { background:#28a745; }
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

  $('.select2').select2({ allowClear: true, placeholder: "Please Select" });

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
                  '<td>' + r.net + '</td>' +
                  '<td>' + r.old_price + '</td>' +
                  '<td>' + r.old_total + '</td>' +
                  '<td><strong class="text-success">' + r.new_price + '</strong></td>' +
                  '<td><strong class="text-success">' + r.new_total + '</strong></td>' +
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
  var html = '<table class="table table-sm table-bordered mb-0" style="background:#f8f9fa;">'
    + '<thead><tr><th>' + colProduct + '</th><th>' + colGrade + '</th><th>' + colNet + '</th><th>' + colPrice + '</th><th>' + colTotal + '</th></tr></thead><tbody>';
  $.each(d.items, function(i, item) {
    html += '<tr><td>' + item.product_name + '</td><td>' + item.grade + '</td><td>' + item.net + '</td><td>' + item.price + '</td><td>' + item.total + '</td></tr>';
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

  if (step === 1) {
    $('#stepInputs').show();
    $('#btnPreview').show();
    $('#wizardStep1').addClass('active');
  } else if (step === 2) {
    $('#stepPreview').show();
    $('#btnBackToInputs, #btnGoConfirm').show();
    $('#wizardStep1').addClass('done');
    $('#wizardStep2').addClass('active');
  } else {
    $('#stepConfirm').show();
    $('#btnBackToPreview, #btnConfirm').show();
    $('#wizardStep1, #wizardStep2').addClass('done');
    $('#wizardStep3').addClass('active');
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
