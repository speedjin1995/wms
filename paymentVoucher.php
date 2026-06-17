<?php
require_once 'php/db_connect.php';
require_once 'php/lookup.php';

session_start();

if (!isset($_SESSION['userID'])) {
    echo '<script>window.location.href="login.html";</script>';
    exit;
}

$user = $_SESSION['userID'];
$company = $_SESSION['customer'];
$module = $_SESSION['module'] ?? 'wholesales';

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('s', $user);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$role = $row['role_code'] ?? 'NORMAL';
$allowEdit = $row['allow_edit'] ?? 'N';
$allowDelete = $row['allow_delete'] ?? 'N';

if ($role != 'SADMIN') {
  $supplies = $db->query("SELECT s.* FROM supplies s WHERE s.deleted=0 AND s.customer='$company' AND s.parent IS NOT NULL ORDER BY s.supplier_name ASC");
  $parentSupplies = $db->query("SELECT DISTINCT sp.* FROM supplies sp INNER JOIN supplies sc ON sc.parent = sp.id WHERE sp.deleted=0 AND sp.customer='$company' ORDER BY sp.supplier_name ASC");
  $customers = $db->query("SELECT * FROM customers WHERE deleted=0 AND customer='$company' AND parent IS NOT NULL ORDER BY customer_name ASC");
  $parentCustomers = $db->query("SELECT DISTINCT cp.* FROM customers cp INNER JOIN customers cc ON cc.parent = cp.id WHERE cp.deleted=0 AND cp.customer='$company' ORDER BY cp.customer_name ASC");
} else {
  $supplies = $db->query("SELECT s.* FROM supplies s WHERE s.deleted=0 AND s.parent IS NOT NULL ORDER BY s.supplier_name ASC");
  $parentSupplies = $db->query("SELECT DISTINCT sp.* FROM supplies sp INNER JOIN supplies sc ON sc.parent = sp.id WHERE sp.deleted=0 ORDER BY sp.supplier_name ASC");
  $customers = $db->query("SELECT * FROM customers WHERE deleted=0 AND parent IS NOT NULL ORDER BY customer_name ASC");
  $parentCustomers = $db->query("SELECT DISTINCT cp.* FROM customers cp INNER JOIN customers cc ON cc.parent = cp.id WHERE cp.deleted=0 ORDER BY cp.customer_name ASC");
}

$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?=$languageArray['payment_voucher_code'][$language]?></h1>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">

    <!-- Filters -->
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <div class="row">
              <div class="form-group col-3">
                <label><?=$languageArray['from_date_code'][$language]?>:</label>
                <div class="input-group date" id="fromDatePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#fromDatePicker" id="fromDate"/>
                  <div class="input-group-append" data-target="#fromDatePicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>
              <div class="form-group col-3">
                <label><?=$languageArray['to_date_code'][$language]?>:</label>
                <div class="input-group date" id="toDatePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#toDatePicker" id="toDate"/>
                  <div class="input-group-append" data-target="#toDatePicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>
              <div class="col-3">
                <div class="form-group">
                  <label><?=$languageArray['transaction_status_code'][$language]?></label>
                  <select class="form-control" id="transactionStatusFilter" name="transactionStatusFilter">
                    <option value="DISPATCH" selected><?=$languageArray['dispatch_code'][$language]?></option>
                    <option value="RECEIVING"><?=$languageArray['receiving_code'][$language]?></option>
                  </select>
                </div>
              </div>
              <div class="col-3" id="viewCustomerFilter">
                <div class="form-group">
                  <label><?=$languageArray['customer_code'][$language]?></label>
                  <select class="form-control select2" id="customerFilter">
                    <option value=""><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($c = mysqli_fetch_assoc($customers)) { ?>
                      <option value="<?=$c['id']?>"><?=$c['customer_name']?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-3" id="viewCustomerParentFilter">
                <div class="form-group">
                  <label><?=$languageArray['parent_customer_code'][$language]?></label>
                  <select class="form-control select2" id="parentCustomerFilter">
                    <option value=""><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($pc = mysqli_fetch_assoc($parentCustomers)) { ?>
                      <option value="<?=$pc['id']?>"><?=$pc['customer_name']?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-3" id="viewSupplierFilter" style="display:none">
                <div class="form-group">
                  <label><?=$languageArray['supplier_code'][$language]?></label>
                  <select class="form-control select2" id="supplierFilter">
                    <option value=""><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($s = mysqli_fetch_assoc($supplies)) { ?>
                      <option value="<?=$s['id']?>"><?=$s['supplier_name']?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-3" id="viewSupplierParentFilter" style="display:none">
                <div class="form-group">
                  <label><?=$languageArray['parent_supplier_code'][$language]?></label>
                  <select class="form-control select2" id="parentSupplierFilter">
                    <option value=""><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($ps = mysqli_fetch_assoc($parentSupplies)) { ?>
                      <option value="<?=$ps['id']?>"><?=$ps['supplier_name']?></option>
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

    <!-- Listing -->
    <div class="row">
      <div class="col-lg-12">
        <div class="card card-info">
          <div class="card-header">
            <div class="row">
              <div class="col-10"><?=$languageArray['payment_voucher_code'][$language]?></div>
            </div>
          </div>
          <div class="card-body">
            <table id="pvTable" class="table table-bordered table-striped display">
              <thead>
                <tr>
                  <th><?=$languageArray['voucher_date_code'][$language]?></th>
                  <th><?=$languageArray['voucher_no_code'][$language]?></th>
                  <th><?=$languageArray['name_code'][$language]?></th>
                  <th><?=$languageArray['invoice_no_code'][$language]?></th>
                  <th><?=$languageArray['total_nett_weight_code'][$language]?> (KG)</th>
                  <th><?=$languageArray['unit_price_code'][$language]?> (RM)</th>
                  <!-- <th><?=$languageArray['nett_amount_code'][$language]?> (RM)</th> -->
                  <!-- <th><?=$languageArray['tax_amount_code'][$language]?> (RM)</th> -->
                  <th><?=$languageArray['total_price_code'][$language]?> (RM)</th>
                  <th width="10%"><?=$languageArray['actions_code'][$language]?></th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Payment Voucher Modal -->
<div class="modal fade" id="pvModal">
  <div class="modal-dialog" style="max-width:95%;">
    <div class="modal-content">
      <form id="pvForm">
        <div class="modal-header bg-gray-dark color-palette">
          <h4 class="modal-title"><?=$languageArray['payment_voucher_details_code'][$language]?></h4>
          <button type="button" class="close bg-gray-dark color-palette" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="pvId" name="pvId">
          <input type="hidden" id="pvEntityId" name="entityId">
          <input type="hidden" id="deductionAmount" name="deductionAmount" value="0">
          <input type="hidden" id="additionAmount" name="additionAmount" value="0">
          <input type="hidden" id="finalAmount" name="finalAmount" value="0">
          <input type="hidden" id="totalNettAmount" name="totalNettAmount" value="0">
          <input type="hidden" id="totalTaxAmount" name="totalTaxAmount" value="0">

          <div class="row mb-3">
            <div class="col-md-4">
              <div class="form-group">
                <label><?=$languageArray['voucher_date_code'][$language]?> *</label>
                <div class="input-group date" id="voucherDatePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#voucherDatePicker" id="voucherDate" name="voucherDate" required/>
                  <div class="input-group-append" data-target="#voucherDatePicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label><?=$languageArray['voucher_no_code'][$language]?></label>
                <input type="text" class="form-control" id="voucherNo" name="voucherNo" readonly placeholder="Auto Generated">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label><?=$languageArray['invoice_no_code'][$language]?></label>
                <input type="text" class="form-control" id="invoiceNo" name="invoiceNo" placeholder="Optional">
              </div>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <div class="form-group">
                <label><?=$languageArray['unit_price_code'][$language]?> (RM) *</label>
                <input type="number" step="0.01" class="form-control" id="unitPrice" name="unitPrice" value="0" required>
              </div>
            </div>
            <div class="col-md-4" style="display:none">
              <div class="form-group">
                <label><?=$languageArray['tax_code'][$language]?> (%)</label>
                <input type="number" step="0.01" class="form-control" id="taxRate" name="tax" value="0">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label><?=$languageArray['total_nett_weight_code'][$language]?> (KG)</label>
                <input type="text" class="form-control" id="totalNettWeight" name="totalNettWeight" readonly>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label><?=$languageArray['total_amount_code'][$language]?> (RM)</label>
                <input type="text" class="form-control" id="totalAmount" name="totalAmount" readonly>
              </div>
            </div>
          </div>

          <h6><?=$languageArray['weighing_details_code'][$language]?></h6>
          <div class="table-responsive">
            <table class="table table-bordered table-sm" id="pvItemsTable">
              <thead class="bg-primary text-white">
                <tr>
                  <th><?=$languageArray['serial_no_code'][$language]?></th>
                  <th><?=$languageArray['name_code'][$language]?></th>
                  <th><?=$languageArray['vehicle_no_code'][$language]?></th>
                  <th><?=$languageArray['nett_weight_code'][$language]?> (KG)</th>
                  <th><?=$languageArray['unit_price_code'][$language]?> (RM)</th>
                  <th style="display:none"><?=$languageArray['nett_amount_code'][$language]?> (RM)</th>
                  <th style="display:none"><?=$languageArray['tax_amount_code'][$language]?> (RM)</th>
                  <th><?=$languageArray['total_price_code'][$language]?> (RM)</th>
                </tr>
              </thead>
              <tbody id="pvItemsBody"></tbody>
              <tfoot>
                <tr class="font-weight-bold">
                  <td colspan="3" class="text-right"><?=$languageArray['total_code'][$language]?></td>
                  <td id="footTotalNett">0.00</td>
                  <td></td>
                  <!-- <td id="footTotalNettAmt">0.00</td>
                  <td id="footTotalTaxAmt">0.00</td> -->
                  <td id="footTotalPrice">0.00</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
        <div class="modal-footer justify-content-between bg-gray-dark color-palette">
          <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-success"><?=$languageArray['save_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Print Modal -->
<div class="modal fade" id="printModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-gray-dark color-palette">
        <h4 class="modal-title"><?=$languageArray['print_code'][$language]?></h4>
        <button type="button" class="close bg-gray-dark color-palette" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="printPvId">
        <div class="form-group">
          <label><?=$languageArray['select_slip_type_code'][$language]?></label>
          <select class="form-control" id="printSlipType">
            <option value="pv"><?=$languageArray['payment_voucher_code'][$language]?></option>
            <option value="statement"><?=$languageArray['statement_code'][$language]?></option>
          </select>
        </div>
      </div>
      <div class="modal-footer justify-content-between bg-gray-dark color-palette">
        <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
        <button type="button" class="btn btn-success" id="confirmPrint"><?=$languageArray['print_code'][$language]?></button>
      </div>
    </div>
  </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="cancelForm">
        <div class="modal-header bg-gray-dark color-palette">
          <h4 class="modal-title"><?=$languageArray['delete_reason_code'][$language]?></h4>
          <button type="button" class="close bg-gray-dark color-palette" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label><?=$languageArray['delete_reason_code'][$language]?> *</label>
            <textarea class="form-control" id="cancelReason" name="cancelReason" rows="3" required></textarea>
          </div>
          <input type="hidden" id="cancelId" name="id">
        </div>
        <div class="modal-footer justify-content-between bg-gray-dark color-palette">
          <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-danger"><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(function() {
  const today = new Date();

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

  $('#voucherDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY'
  });

  $('.select2').each(function() {
    $(this).select2({
      allowClear: true,
      placeholder: 'Please Select',
      dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-body') : undefined
    });
  });

  var fromDateI = $('#fromDate').val();
  var toDateI = $('#toDate').val();
  var supplierI = $('#supplierFilter').val() ? $('#supplierFilter').val() : '';
  var parentSupplierI = $('#parentSupplierFilter').val() ? $('#parentSupplierFilter').val() : '';
  var customerI = $('#customerFilter').val() ? $('#customerFilter').val() : '';
  var parentCustomerI = $('#parentCustomerFilter').val() ? $('#parentCustomerFilter').val() : '';
  var transactionStatusI = $('#transactionStatusFilter').val();

  var table = $('#pvTable').DataTable({
    'responsive': true,
    'autoWidth': false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'searching': true,
    'order': [[ 0, 'desc' ]],
    'ajax': {
      'url': 'php/modules/paymentVoucher/filterPaymentVoucher.php',
      'data': {
        fromDate: fromDateI,
        toDate: toDateI,
        supplierId: supplierI,
        parentSupplierId: parentSupplierI,
        customerId: customerI,
        parentCustomerId: parentCustomerI,
        transactionStatus: transactionStatusI
      }
    },
    'columns': [
      { data: 'voucher_date' },
      { data: 'voucher_no' },
      { data: 'entity_name' },
      { data: 'invoice_no' },
      { data: 'total_nett_weight' },
      { data: 'unit_price' },
      // { data: 'nett_amount' },
      // { data: 'tax_amount' },
      { data: 'final_amount' },
      {
        data: 'id',
        class: 'action-button',
        render: function ( data, type, row ) {
          var buttons = '<div class="d-flex flex-nowrap" style="gap:4px;">';
          if(<?=$allowEdit == 'Y' ? 'true' : 'false'?>) {
            buttons += '<button type="button" onclick="openPv(\'' + row.parent_id + '\',\'' + row.pv_id + '\')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button>';
          }
          if (row.pv_id) {
            buttons += '<button type="button" onclick="print(\'' + row.pv_id + '\')" class="btn btn-info btn-sm"><i class="fas fa-print"></i></button>';
          }
          if(<?=$allowDelete == 'Y' ? 'true' : 'false'?>) {
            if (row.pv_id) {
              buttons += '<button type="button" onclick="deactivate(\'' + row.pv_id + '\')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>';
            }
          }
          buttons += '</div>';
          return buttons;
        }
      }
    ]
  });

  $('#filterSearch').on('click', function() {
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var supplierI = $('#supplierFilter').val() ? $('#supplierFilter').val() : '';
    var parentSupplierI = $('#parentSupplierFilter').val() ? $('#parentSupplierFilter').val() : '';
    var customerI = $('#customerFilter').val() ? $('#customerFilter').val() : '';
    var parentCustomerI = $('#parentCustomerFilter').val() ? $('#parentCustomerFilter').val() : '';
    var transactionStatusI = $('#transactionStatusFilter').val();

    $('#pvTable').DataTable().clear().destroy();

    table = $('#pvTable').DataTable({
      'responsive': true,
      'autoWidth': false,
      'processing': true,
      'serverSide': true,
      'serverMethod': 'post',
      'searching': true,
      'order': [[ 0, 'desc' ]],
      'ajax': {
        'url': 'php/modules/paymentVoucher/filterPaymentVoucher.php',
        'data': {
          fromDate: fromDateI,
          toDate: toDateI,
          supplierId: supplierI,
          parentSupplierId: parentSupplierI,
          customerId: customerI,
          parentCustomerId: parentCustomerI,
          transactionStatus: transactionStatusI
        }
      },
      'columns': [
        { data: 'voucher_date' },
        { data: 'voucher_no' },
        { data: 'entity_name' },
        { data: 'invoice_no' },
        { data: 'total_nett_weight' },
        { data: 'unit_price' },
        // { data: 'nett_amount' },
        // { data: 'tax_amount' },
        { data: 'final_amount' },
        {
          data: 'id',
          class: 'action-button',
          render: function ( data, type, row ) {
            var buttons = '<div class="d-flex flex-nowrap" style="gap:4px;">';
            if(<?=$allowEdit == 'Y' ? 'true' : 'false'?>) {
              buttons += '<button type="button" onclick="openPv(\'' + row.parent_id + '\',\'' + row.pv_id + '\')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button>';
            }
            if (row.pv_id) {
              buttons += '<button type="button" onclick="print(\'' + row.pv_id + '\')" class="btn btn-info btn-sm"><i class="fas fa-print"></i></button>';
            }
            if(<?=$allowDelete == 'Y' ? 'true' : 'false'?>) {
              if (row.pv_id) {
                buttons += '<button type="button" onclick="deactivate(\'' + row.pv_id + '\')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>';
              }
            }
            buttons += '</div>';
            return buttons;
          }
        }
      ]
    });
  });

  $('#transactionStatusFilter').on('change', function() {
    var status = $(this).val();

    if (status == 'RECEIVING'){
      $('#viewCustomerFilter').hide();
      $('#viewCustomerParentFilter').hide();
      $('#viewSupplierFilter').show();
      $('#viewSupplierParentFilter').show();
    }else{
      $('#viewCustomerFilter').show();
      $('#viewCustomerParentFilter').show();
      $('#viewSupplierFilter').hide();
      $('#viewSupplierParentFilter').hide();
    }
  });

  $('#pvModal').on('input', '#unitPrice, #taxRate', function() {
    recalculate();
  });

  // Form submit
  $('#pvForm').on('submit', function(e) {
    e.preventDefault();
    var wholesaleIds = [];
    $('#pvItemsBody tr').each(function() { wholesaleIds.push($(this).data('id')); });
    if (!wholesaleIds.length) { toastr['error']('No records loaded.', 'Error:'); return; }

    $('#spinnerLoading').show();
    var formData = new FormData($('#pvForm')[0]);
    wholesaleIds.forEach(function(id, i) {
      formData.append('wholesaleIds[' + i + ']', id);
    });
    formData.append('transactionStatus', $('#transactionStatusFilter').val());
    $.ajax({
      url: 'php/modules/paymentVoucher/savePaymentVoucher.php',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(data) {
        var obj = JSON.parse(data);
        if (obj.status === 'success') {
          $('#pvModal').modal('hide');
          toastr['success'](obj.message, 'Success:');
          $('#pvTable').DataTable().ajax.reload();
        } else {
          toastr['error'](obj.message, 'Failed:');
        }
        $('#spinnerLoading').hide();
      },
      error: function() {
        toastr['error']('Something went wrong when saving', 'Failed:');
        $('#spinnerLoading').hide();
      }
    });
  });

  $('#confirmPrint').on('click', function() {
    var pvId = $('#printPvId').val();
    var slipType = $('#printSlipType').val();
    $('#printModal').modal('hide');
    $('#spinnerLoading').show();
    $.post('php/modules/paymentVoucher/printPvSlip.php', {pvId: pvId, slipType: slipType}, function(data) {
      var obj = JSON.parse(data);
      if (obj.status === 'success') {
        var printWindow = window.open('', '', 'height=' + screen.height + ',width=' + screen.width);
        printWindow.document.write(obj.message);
        printWindow.document.close();
        setTimeout(function() { printWindow.print(); printWindow.close(); }, 500);
      } else {
        toastr['error'](obj.message, 'Failed:');
      }
      $('#spinnerLoading').hide();
    });
  });

  // Cancel form
  $('#cancelForm').on('submit', function(e) {
    e.preventDefault();
    $('#spinnerLoading').show();
    $.post('php/modules/paymentVoucher/deletePaymentVoucher.php', {
      id: $('#cancelId').val(), cancelReason: $('#cancelReason').val()
    }, function(data) {
      var obj = JSON.parse(data);
      if (obj.status === 'success') {
        $('#cancelModal').modal('hide');
        toastr['success'](obj.message, 'Success:');
        $('#pvTable').DataTable().ajax.reload();
      } else {
        toastr['error'](obj.message, 'Failed:');
      }
      $('#spinnerLoading').hide();
    });
  });
});

function openPv(entityId, pvId) {
  $('#pvId').val(pvId || '');
  $('#pvEntityId').val(entityId);
  $('#voucherNo').val('');
  $('#invoiceNo').val('');
  $('#unitPrice').val(0);
  $('#taxRate').val(0);
  $('#totalNettWeight').val('');
  $('#nettAmount').val('');
  $('#taxAmount').val('');
  $('#totalAmount').val('');
  $('#finalAmount').val(0);
  $('#deductionAmount').val(0);
  $('#additionAmount').val(0);
  $('#pvItemsBody').empty();
  $('#voucherDatePicker').datetimepicker('date', moment());

  $('#spinnerLoading').show();
  $.post('php/modules/paymentVoucher/getPaymentVoucherItems.php', {
    parent_id: entityId,
    pv_id: pvId || '',
    transactionStatus: $('#transactionStatusFilter').val()
  }, function(data) {
    var obj = JSON.parse(data);
    if (obj.status === 'success') {
      $('#totalNettWeight').val(obj.total_nett_weight);

      // Populate existing PV header data if editing
      if (obj.paymentVoucher && obj.paymentVoucher.id) {
        var pv = obj.paymentVoucher;
        $('#voucherNo').val(pv.voucher_no);
        $('#invoiceNo').val(pv.invoice_no || '');
        $('#unitPrice').val(pv.unit_price || 0);
        $('#taxRate').val(pv.tax || 0);
        $('#totalAmount').val(pv.total_amount || 0);
        $('#nettAmount').val(pv.nett_amount || 0);
        $('#taxAmount').val(pv.tax_amount || 0);
        $('#finalAmount').val(pv.final_amount || 0);
        $('#deductionAmount').val(pv.deduction_amount || 0);
        $('#additionAmount').val(pv.addition_amount || 0);
        if (pv.voucher_date) {
          $('#voucherDatePicker').datetimepicker('date', moment(pv.voucher_date, 'YYYY-MM-DD'));
        }
      }

      obj.items.forEach(function(item) {
        $('#pvItemsBody').append(
          '<tr data-id="' + item.id + '" data-nett="' + item.nett_raw + '">' +
            '<td>' + item.serial_no + '</td>' +
            '<td>' + item.supplier_name + '</td>' +
            '<td>' + item.vehicle_no + '</td>' +
            '<td class="item-nett">' + item.nett + '</td>' +
            '<td class="item-unit-price">' + item.unit_price + '</td>' +
            '<td class="item-nett-amt" style="display:none">' + item.nett_amount + '</td>' +
            '<td class="item-tax-amt" style="display:none">0.00</td>' +
            '<td class="item-total-price">0.00</td>' +
          '</tr>'
        );
      });
      recalculate();
    }
    $('#spinnerLoading').hide();
  });

  $('#pvModal').modal('show');
}

function recalculate() {
  var unitPrice = parseFloat($('#unitPrice').val()) || 0;
  var tax = parseFloat($('#taxRate').val()) || 0;
  var totalNett = 0, totalNettAmt = 0, totalTaxAmt = 0, totalPrice = 0;

  $('#pvItemsBody tr').each(function() {
    var nett = parseFloat($(this).data('nett')) || 0;
    var nettAmt = unitPrice * nett;
    var taxAmt = nettAmt * (tax / 100);
    var total = nettAmt + taxAmt;

    $(this).find('.item-unit-price').text(unitPrice.toFixed(2));
    $(this).find('.item-nett-amt').text(nettAmt.toFixed(2));
    $(this).find('.item-tax-amt').text(taxAmt.toFixed(2));
    $(this).find('.item-total-price').text(total.toFixed(2));

    totalNett    += nett;
    totalNettAmt += nettAmt;
    totalTaxAmt  += taxAmt;
    totalPrice   += total;
  });

  $('#footTotalNett').text(totalNett.toFixed(2));
  $('#footTotalNettAmt').text(totalNettAmt.toFixed(2));
  $('#totalNettAmount').val(totalNettAmt.toFixed(2));
  $('#footTotalTaxAmt').text(totalTaxAmt.toFixed(2));
  $('#totalTaxAmount').val(totalTaxAmt.toFixed(2));
  $('#footTotalPrice').text(totalPrice.toFixed(2));
  $('#totalAmount').val(totalPrice.toFixed(2));
  $('#finalAmount').val(totalPrice.toFixed(2));
}

function deactivate(pvId) {
  $('#cancelId').val(pvId);
  $('#cancelReason').val('');
  $('#cancelModal').modal('show');
}

function print(pvId){
  $('#printPvId').val(pvId);
  $('#printSlipType').val('pv');
  $('#printModal').modal('show');
}
</script>
