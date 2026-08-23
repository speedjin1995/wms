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
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' AND customer = '$company' ORDER BY customer_name ASC");
    $suppliers = $db->query("SELECT * FROM supplies WHERE deleted = '0' AND customer = '$company' ORDER BY supplier_name ASC");
  } else {
    $products = $db->query("SELECT p.* FROM products p INNER JOIN categories c ON p.category = c.id WHERE p.deleted = '0' AND c.module IN ('wholesale', 'processing') AND c.deleted = '0' ORDER BY p.product_name ASC");
    $grades = $db->query("SELECT DISTINCT g.*, p.product_name FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC");
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' ORDER BY customer_name ASC");
    $suppliers = $db->query("SELECT * FROM supplies WHERE deleted = '0' ORDER BY supplier_name ASC");
  }

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>



<link rel="stylesheet" href="../../assets/css/page-global.css">
<link rel="stylesheet" href="../../assets/css/modal-global.css">

<style>
/* Wizard Styles */
.wizard-header { background: linear-gradient(135deg, rgba(246, 213, 74, 1) 0%, rgba(255, 243, 199, 1) 50%, rgba(217, 168, 45, 1) 100%); border-bottom: 1px solid #E3C66A; padding: 20px 0; }
.wizard-steps { display: flex; align-items: flex-start; justify-content: center; max-width: 500px; margin: 0 auto; }
.wizard-step { display: flex; flex-direction: column; align-items: center; flex: 0 0 auto; min-width: 80px; }
.wizard-step-circle { width: 44px; height: 44px; border-radius: 50%; background: #dee2e6; color: #6c757d; display: flex; align-items: center; justify-content: center; font-size: 16px; transition: all 0.3s ease; border: 3px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.wizard-step.active .wizard-step-circle { background: #D9A82D; color: #fff; box-shadow: 0 0 0 4px rgba(217,168,45,0.2), 0 2px 8px rgba(217,168,45,0.3); }
.wizard-step.done .wizard-step-circle { background: #16A34A; color: #fff; }
.wizard-step-label { font-size: 11px; margin-top: 8px; color: #6c757d; font-weight: 500; text-align: center; text-transform: uppercase; letter-spacing: 0.3px; }
.wizard-step.active .wizard-step-label { color: #D9A82D; font-weight: 600; }
.wizard-step.done .wizard-step-label { color: #16A34A; font-weight: 600; }
.wizard-step-line { flex: 1; height: 3px; background: #dee2e6; margin: 0 12px; margin-top: 22px; min-width: 60px; max-width: 100px; border-radius: 2px; transition: background 0.3s ease; }
.wizard-step-line.done { background: #16A34A; }
.step-hint { background: rgba(227, 198, 106, .25); border-radius: 5px; padding: 12px 16px; font-size: 13px; color: #1a1a1a; border-left: 3px solid #D9A82D; }
.step-hint i { color: #D9A82D; }
.preview-scroll { max-height: 350px; overflow-y: auto; }
.new-value { background: #dcfce7 !important; color: #166534 !important; }
.confirm-card { text-align: center; padding: 40px 20px; }
.confirm-icon { width: 80px; height: 80px; background: linear-gradient(135deg, rgba(246, 213, 74, 1) 0%, rgba(255, 243, 199, 1) 50%, rgba(217, 168, 45, 1) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
.confirm-icon i { font-size: 36px; color: #1a1a1a; }
.confirm-title { font-size: 22px; font-weight: 600; color: #1a1a1a; margin-bottom: 12px; }
.confirm-message { font-size: 15px; color: #1a1a1a; margin-bottom: 20px; }
.confirm-count { display: inline-block; background: #D9A82D; color: #fff; font-weight: 700; padding: 2px 12px; border-radius: 20px; font-size: 16px; }
.confirm-warning { background: rgba(227, 198, 106, .25); border-radius: 5px; padding: 12px 16px; font-size: 13px; color: #1a1a1a; display: inline-block; }
.badge-status { padding: 5px 10px; border-radius: 5px; font-size: 12px; font-weight: 700; }
.badge-dispatch { background: #dcfce7; color: #166534; }
.badge-receiving { background: #fef3c7; color: #92400e; }
.badge-count { background: #1a1a1a; color: #fff; padding: 2px 8px; border-radius: 5px; font-size: 12px; font-weight: 700; }
.expand-icon { cursor: pointer; color: #D9A82D; }
.expand-icon:hover { color: #1a1a1a; }
</style>

<div class="content custom-table-content">
  <div class="container-fluid">
    <!-- Page Header -->
    <div class="custom-title-content-box">
      <h1 class="custom-title">
        <i class="fas fa-tags"></i>
        <?=$languageArray['bulk_price_update_code'][$language] ?? 'Bulk Price Update'?>
      </h1>
    </div>

    <!-- Filter Card -->
    <div class="card custom-main-card">
      <div class="custom-search-card-body">
        <div class="row">
          <!-- Date -->
          <div class="col-md-2 form-group">
            <label><?=$languageArray['date_code'][$language] ?? 'Date'?></label>
            <div class="input-group date" id="datePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#datePicker" id="date"/>
              <div class="input-group-append" data-target="#datePicker" data-toggle="datetimepicker">
                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
              </div>
            </div>
          </div>

          <!-- Transaction Status -->
          <div class="col-md-2 form-group">
            <label><?=$languageArray['transaction_status_code'][$language]?></label>
            <select class="form-control" id="transactionStatusFilter">
              <option value="DISPATCH" selected><?=$languageArray['dispatch_code'][$language]?></option>
              <option value="RECEIVING"><?=$languageArray['receiving_code'][$language]?></option>
            </select>
          </div>

          <!-- Customer (shown for DISPATCH) -->
          <div class="filter-group" id="customerFilterGroup">
            <label class="filter-label"><?=$languageArray['customer_code'][$language] ?? 'Customer'?></label>
            <select class="form-control select2-filter" id="customerFilter">
              <option value=""><?=$languageArray['please_select_code'][$language] ?? 'Please Select'?></option>
              <?php while($c = mysqli_fetch_assoc($customers)){ ?>
                <option value="<?=$c['id']?>"><?=$c['customer_name']?></option>
              <?php } ?>
            </select>
          </div>

          <!-- Supplier (shown for RECEIVING) -->
          <div class="filter-group" id="supplierFilterGroup" style="display:none;">
            <label class="filter-label"><?=$languageArray['supplier_code'][$language] ?? 'Supplier'?></label>
            <select class="form-control select2-filter" id="supplierFilter">
              <option value=""><?=$languageArray['please_select_code'][$language] ?? 'Please Select'?></option>
              <?php while($s = mysqli_fetch_assoc($suppliers)){ ?>
                <option value="<?=$s['id']?>"><?=$s['supplier_name']?></option>
              <?php } ?>
            </select>
          </div>

          <!-- Product (Optional) -->
          <div class="col-md-2 form-group">
            <label><?=$languageArray['product_code'][$language]?> <small class="text-muted">(<?=$languageArray['optional_code'][$language] ?? 'Optional'?>)</small></label>
            <select class="form-control select2-filter" id="productFilter">
              <option value=""><?=$languageArray['all_code'][$language] ?? 'All'?></option>
              <?php while($p = mysqli_fetch_assoc($products)){ ?>
                <option value="<?=$p['id']?>"><?=$p['product_name']?></option>
              <?php } ?>
            </select>
          </div>

          <!-- Grade (Optional) -->
          <div class="col-md-2 form-group">
            <label><?=$languageArray['grade_code'][$language]?> <small class="text-muted">(<?=$languageArray['optional_code'][$language] ?? 'Optional'?>)</small></label>
            <select class="form-control select2-filter" id="gradeFilter">
              <option value=""><?=$languageArray['all_code'][$language] ?? 'All'?></option>
            </select>
          </div>

          <!-- Search Button -->
          <div class="filter-group filter-group-action">
            <button type="button" class="btn btn-filter btn-filter-primary" id="bulkFilterSearch">
              <i class="fas fa-search"></i> <?=$languageArray['search_code'][$language]?>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Results Card -->
    <div class="card custom-main-card" id="resultsCard" style="display:none;">
      <div class="custom-card-header">
        <div class="custom-card-header-row row">
          <div class="col-md-6">
            <h3 class="custom-card-header-title">
              <i class="fas fa-list-alt"></i>
              <?=$languageArray['results_code'][$language] ?? 'Results'?>
              <span class="badge" id="resultCount">0</span>
            </h3>
          </div>
          <?php if($allowEdit == 'Y'){ ?>
          <div class="col-md-6 custom-card-header-btn-col">
            <button type="button" class="btn custom-search-btn" id="btnOpenBulkPrice" onclick="openBulkPriceModal()">
              <i class="fas fa-edit"></i> <?=$languageArray['update_price_code'][$language] ?? 'Update Price'?>
            </button>
          </div>
          <?php } ?>
        </div>
      </div>
      <div class="custom-table-card-body">
        <table id="weightTable" class="table table-bordered">
          <thead>
            <tr>
              <th width="50"></th>
              <th><?=$languageArray['serial_no_code'][$language]?></th>
              <th><?=$languageArray['do_po_no_code'][$language]?></th>
              <th><?=$languageArray['customer_supplier_code'][$language]?></th>
              <th><?=$languageArray['start_time_code'][$language]?></th>
              <th class="text-center"><?=$languageArray['transaction_status_code'][$language]?></th>
              <th class="text-center"><?=$languageArray['items_code'][$language]?></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
        <!-- Empty State -->
        <div id="emptyState" class="empty-state" style="display:none;">
          <i class="fas fa-inbox"></i>
          <p><?=$languageArray['no_records_found_code'][$language] ?? 'No records found'?></p>
          <span><?=$languageArray['try_adjusting_search_code'][$language] ?? 'Try adjusting your search criteria'?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bulk Price Update Modal -->
<div class="modal fade modal-modern" id="bulkPriceModal">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content custom-model-content-box">
      <form role="form" id="bulkPriceForm">
        <div class="modal-header custom-model-header-box">
          <h5 class="modal-title custom-model-title-txt"><i class="fas fa-tags"></i><?=$languageArray['update_price_code'][$language] ?? 'Update Price'?></h5>
          <button type="button" class="close custom-btn-close-icon" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body custom-model-body-box p-0">

          <!-- Wizard Steps Indicator -->
          <div class="wizard-header">
            <div class="wizard-steps">
              <div class="wizard-step active" id="wizardStep1">
                <div class="wizard-step-circle"><i class="fas fa-dollar-sign"></i></div>
                <div class="wizard-step-label"><?=$languageArray['set_price_code'][$language] ?? 'Set Price'?></div>
              </div>
              <div class="wizard-step-line" id="wizardLine1"></div>
              <div class="wizard-step" id="wizardStep2">
                <div class="wizard-step-circle"><i class="fas fa-eye"></i></div>
                <div class="wizard-step-label"><?=$languageArray['preview_code'][$language]?></div>
              </div>
              <div class="wizard-step-line" id="wizardLine2"></div>
              <div class="wizard-step" id="wizardStep3">
                <div class="wizard-step-circle"><i class="fas fa-check"></i></div>
                <div class="wizard-step-label"><?=$languageArray['confirm_code'][$language] ?? 'Confirm'?></div>
              </div>
            </div>
          </div>

          <div class="p-4">
            <!-- Step 1: inputs -->
            <div id="stepInputs">
              <div class="step-hint mb-3">
                <i class="fas fa-lightbulb mr-2"></i>
                <span><?=$languageArray['set_new_price_hint_code'][$language] ?? 'Confirm'?></span>
              </div>
              <div class="custom-card-box" style="border-top: 5px solid #D9A82D;">
                <table class="table table-bordered mb-0" id="priceInputTable">
                  <thead>
                    <tr>
                      <th><?=$languageArray['product_code'][$language]?></th>
                      <th><?=$languageArray['grade_code'][$language]?></th>
                      <th width="150"><?=$languageArray['pricing_type_code'][$language] ?? 'Pricing Type'?></th>
                      <th width="140"><?=$languageArray['new_price_code'][$language] ?? 'New Price'?></th>
                    </tr>
                  </thead>
                  <tbody id="priceInputBody">
                    <tr><td colspan="4" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin mr-2"></i>Loading...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Step 2: preview -->
            <div id="stepPreview" style="display:none;">
              <div class="step-hint mb-3">
                <i class="fas fa-search mr-2"></i>
                <span><?=$languageArray['review_changes_message_code'][$language] ?? 'Review the changes below before confirming.'?></span>
              </div>
              <div class="custom-card-box preview-scroll" style="border-top: 5px solid #D9A82D;">
                <table class="table table-bordered mb-0">
                  <thead>
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
              <div class="confirm-card">
                <div class="confirm-icon">
                  <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h4 class="confirm-title"><?=$languageArray['are_you_sure_code'][$language] ?? 'Are you sure?'?></h4>
                <p class="confirm-message">
                  <?=$languageArray['bulk_update_confirm_message_code'][$language] ?? 'You are about to update the price for'?>
                  <span class="confirm-count" id="confirmCount">0</span>
                  <?=$languageArray['records_code'][$language] ?? 'records'?>
                </p>
                <div class="confirm-warning">
                  <i class="fas fa-info-circle mr-1"></i>
                  <?=$languageArray['action_cannot_be_undone_code'][$language] ?? 'This action cannot be undone.'?>
                </div>
              </div>
            </div>
          </div>

        </div>
        <div class="modal-footer custom-model-fotter-box">
          <button type="button" class="btn custom-close-btn" data-dismiss="modal">
            <i class="fas fa-times"></i> <?=$languageArray['close_code'][$language]?>
          </button>
          <div class="d-flex" style="gap: 10px;">
            <button type="button" class="btn custom-close-btn" id="btnBackToInputs" style="display:none;" onclick="wizardGoTo(1)">
              <i class="fas fa-arrow-left"></i> <?=$languageArray['back_code'][$language] ?? 'Back'?>
            </button>
            <button type="button" class="btn custom-search-btn" id="btnPreview">
              <?=$languageArray['preview_code'][$language] ?? 'Preview'?> <i class="fas fa-arrow-right"></i>
            </button>
            <button type="button" class="btn custom-close-btn" id="btnBackToPreview" style="display:none;" onclick="wizardGoTo(2)">
              <i class="fas fa-arrow-left"></i> <?=$languageArray['back_code'][$language] ?? 'Back'?>
            </button>
            <button type="button" class="btn custom-search-btn" id="btnGoConfirm" style="display:none;" onclick="wizardGoTo(3)">
              <?=$languageArray['next_code'][$language] ?? 'Next'?> <i class="fas fa-arrow-right"></i>
            </button>
            <button type="button" class="btn custom-save-btn" id="btnConfirm" style="display:none;" onclick="confirmBulkUpdate()">
              <i class="fas fa-check"></i> <?=$languageArray['confirm_update_code'][$language] ?? 'Confirm Update'?>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Variables
var bulkTable;
var colProduct  = '<?=$languageArray["product_code"][$language]?>';
var colGrade    = '<?=$languageArray["grade_code"][$language]?>';
var colNet      = '<?=$languageArray["net_code"][$language]?>';
var colPrice    = '<?=$languageArray["price_code"][$language]?>';
var colTotal    = '<?=$languageArray["total_code"][$language]?>';
var labelFloat  = '<?=$languageArray["float_code"][$language]?>';
var labelFixed  = '<?=$languageArray["fixed_code"][$language]?>';
var labelDispatch = '<?=$languageArray["dispatch_code"][$language]?>';
var labelReceiving = '<?=$languageArray["receiving_code"][$language]?>';
var gradesByProduct = <?php
  $gradeMap = [];
  // Re-query since result cursor is exhausted
  if ($role != 'SADMIN') {
    $gRes = $db->query("SELECT DISTINCT g.id, g.units, pg.product_id FROM grades g INNER JOIN product_grades pg ON g.id = pg.grade_id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY g.units ASC");
  } else {
    $gRes = $db->query("SELECT DISTINCT g.id, g.units, pg.product_id FROM grades g INNER JOIN product_grades pg ON g.id = pg.grade_id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY g.units ASC");
  }
  while ($gr = mysqli_fetch_assoc($gRes)) {
    $gradeMap[$gr['product_id']][] = ['id' => $gr['id'], 'units' => $gr['units']];
  }
  echo json_encode($gradeMap);
?>;

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

  $('.select2-filter').select2({ 
    allowClear: true, 
    placeholder: "Please Select",
    width: '100%'
  });

  $('#productFilter').on('change', function() {
    var productId = $(this).val();
    $('#gradeFilter').select2('destroy').html('<option value=""><?=$languageArray["all_code"][$language] ?? "All"?></option>');
    if (productId && gradesByProduct[productId]) {
      $.each(gradesByProduct[productId], function(i, g) {
        $('#gradeFilter').append('<option value="' + g.units + '">' + g.units + '</option>');
      });
    }
    $('#gradeFilter').select2({ allowClear: true, placeholder: "Please Select", width: '100%' });
  });

  $('#transactionStatusFilter').on('change', function() {
    var status = $(this).val();
    if (status === 'RECEIVING') {
      $('#customerFilterGroup').hide();
      $('#supplierFilterGroup').show();
      $('#customerFilter').val('').trigger('change');
    } else {
      $('#customerFilterGroup').show();
      $('#supplierFilterGroup').hide();
      $('#supplierFilter').val('').trigger('change');
    }
  });

  $('#bulkFilterSearch').on('click', function() {
    if (bulkTable) { bulkTable.clear().destroy(); }
    bulkTable = buildTable();
  });

  $('#weightTable').on('click', '.expand-icon', function() {
    var tr = $(this).closest('tr');
    var row = bulkTable.row(tr);
    if (row.child.isShown()) {
      row.child.hide();
      $(this).removeClass('expanded').find('i').removeClass('fa-chevron-down').addClass('fa-chevron-right');
    } else {
      row.child(format(row.data())).show();
      $(this).addClass('expanded').find('i').removeClass('fa-chevron-right').addClass('fa-chevron-down');
    }
  });

  $('#btnPreview').on('click', function() {
    var priceRows = collectPriceRows();
    if (priceRows.length === 0) {
      toastr["error"]("Please enter at least one price.", "Validation Error:");
      return;
    }
    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Loading...');
    $.post('php/modules/wholesales/bulkPriceUpdate/bulkUpdatePrice.php', {
      mode: 'preview',
      date: $('#date').val(),
      status: $('#transactionStatusFilter').val(),
      customer: $('#customerFilter').val(),
      supplier: $('#supplierFilter').val(),
      product: $('#productFilter').val(),
      grade: $('#gradeFilter').val(),
      priceRows: priceRows
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
              '<td class="text-right text-muted">' + r.old_price + '</td>' +
              '<td class="text-right text-muted">' + r.old_total + '</td>' +
              '<td class="text-right new-value font-weight-bold">' + r.new_price + '</td>' +
              '<td class="text-right new-value font-weight-bold">' + r.new_total + '</td>' +
            '</tr>');
          });
          $('#confirmCount').text(obj.rows.length);
          wizardGoTo(2);
        }
      } else {
        toastr["error"](obj.message, "Failed:");
      }
      $btn.prop('disabled', false).html('<?=$languageArray["preview_code"][$language] ?? "Preview"?><i class="fas fa-arrow-right ml-1"></i>');
    }).fail(function() {
      toastr["error"]("Request failed. Please try again.", "Error:");
      $btn.prop('disabled', false).html('<?=$languageArray["preview_code"][$language] ?? "Preview"?><i class="fas fa-arrow-right ml-1"></i>');
    });
  });
});

// Functions
function format(d) {
  var html = '<div style="padding: 15px; background: rgba(227, 198, 106, .15);"><table class="table table-bordered mb-0" style="background: #fff;">'
    + '<thead><tr><th>' + colProduct + '</th><th>' + colGrade + '</th><th class="text-right">' + colNet + '</th><th class="text-right">' + colPrice + '</th><th class="text-right">' + colTotal + '</th></tr></thead><tbody>';
  $.each(d.items, function(i, item) {
    html += '<tr><td>' + item.product_name + '</td><td>' + item.grade + '</td><td class="text-right">' + item.net + '</td><td class="text-right">' + item.price + '</td><td class="text-right">' + item.total + '</td></tr>';
  });
  return html + '</tbody></table></div>';
}

function buildTable(){
  return $("#weightTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'order': [[2, 'asc']],
    'drawCallback': function(settings) {
      var info = this.api().page.info();
      var hasRecords = info.recordsTotal > 0;
      $('#resultsCard').show();
      $('#weightTable').toggle(hasRecords);
      $('#emptyState').toggle(!hasRecords);
      $('#resultCount').text(info.recordsTotal);
      $('#btnOpenBulkPrice').prop('disabled', !hasRecords);
    },
    'ajax': {
      'url': 'php/modules/wholesales/bulkPriceUpdate/filterBulkPrice.php',
      'data': {
        date: $('#date').val(),
        product: $('#productFilter').val(),
        grade: $('#gradeFilter').val(),
        status: $('#transactionStatusFilter').val(),
        customer: $('#customerFilter').val(),
        supplier: $('#supplierFilter').val()
      }
    },
    'columns': [
      {
        data: null, orderable: false, className: 'text-center', width: '50px',
        render: function() {
          return '<span class="expand-icon"><i class="fas fa-chevron-right"></i></span>';
        }
      },
      { data: 'serial_no' },
      { data: 'po_no' },
      { data: 'customer_supplier' },
      { data: 'start_time' },
      { 
        data: 'status', 
        className: 'text-center',
        render: function(data) {
          var badgeClass = data === 'DISPATCH' ? 'badge-dispatch' : 'badge-receiving';
          var label = data === 'DISPATCH' ? labelDispatch : labelReceiving;
          return '<span class="badge-status ' + badgeClass + '">' + label + '</span>';
        }
      },
      { 
        data: 'item_count', 
        className: 'text-center',
        render: function(data) {
          return '<span class="badge-count">' + data + '</span>';
        }
      }
    ]
  });
}

function openBulkPriceModal() {
  wizardGoTo(1);
  $('#priceInputBody').html('<tr><td colspan="4" class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
  $('#bulkPriceModal').modal('show');

  $.post('php/modules/wholesales/bulkPriceUpdate/getProductGradesByFilter.php', {
    date: $('#date').val(),
    status: $('#transactionStatusFilter').val(),
    customer: $('#customerFilter').val(),
    supplier: $('#supplierFilter').val(),
    product: $('#productFilter').val(),
    grade: $('#gradeFilter').val()
  }, function(data) {
    var obj = JSON.parse(data);
    var tbody = $('#priceInputBody').empty();
    if (obj.status === 'success' && obj.combos.length > 0) {
      $.each(obj.combos, function(i, c) {
        tbody.append(
          '<tr>' +
            '<td>' + c.product_name + '<input type="hidden" class="combo-product" value="' + c.product_id + '"></td>' +
            '<td>' + c.grade + '<input type="hidden" class="combo-grade" value="' + c.grade + '"></td>' +
            '<td>' +
              '<select class="form-control form-control-sm combo-type">' +
                '<option value="Float">' + labelFloat + '</option>' +
                '<option value="Fixed">' + labelFixed + '</option>' +
              '</select>' +
            '</td>' +
            '<td><input type="number" class="form-control form-control-sm combo-price" step="0.01" min="0" placeholder="0.00"></td>' +
          '</tr>'
        );
      });
    } else {
      tbody.html('<tr><td colspan="4" class="text-center text-muted">No products found.</td></tr>');
    }
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

function collectPriceRows() {
  var rows = [];
  $('#priceInputBody tr').each(function() {
    var price = $(this).find('.combo-price').val();
    if (price === '' || price === null) return;
    rows.push({
      product:     $(this).find('.combo-product').val(),
      grade:       $(this).find('.combo-grade').val(),
      pricingType: $(this).find('.combo-type').val(),
      newPrice:    price
    });
  });
  return rows;
}

function confirmBulkUpdate() {
  var $btn = $('#btnConfirm');
  $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Updating...');
  $.post('php/modules/wholesales/bulkPriceUpdate/bulkUpdatePrice.php', {
    mode: 'save',
    date: $('#date').val(),
    status: $('#transactionStatusFilter').val(),
    customer: $('#customerFilter').val(),
    supplier: $('#supplierFilter').val(),
    product: $('#productFilter').val(),
    grade: $('#gradeFilter').val(),
    priceRows: collectPriceRows()
  }, function(data) {
    var obj = JSON.parse(data);
    if (obj.status === 'success') {
      $('#bulkPriceModal').modal('hide');
      toastr["success"](obj.message, "Success:");
      bulkTable.clear().destroy();
      bulkTable = buildTable();
    } else {
      toastr["error"](obj.message, "Failed:");
    }
    $btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i><?=$languageArray["confirm_update_code"][$language] ?? "Confirm Update"?>');
  }).fail(function() {
    toastr["error"]("Request failed. Please try again.", "Error:");
    $btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i><?=$languageArray["confirm_update_code"][$language] ?? "Confirm Update"?>');
  });
}
</script>
