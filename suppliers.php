<?php
require_once 'php/db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
}
else{
  $company = $_SESSION['customer'];
  $user = $_SESSION['userID'];
  $role = $_SESSION['role'];
  $module = $_SESSION['module'];
  $states = $db->query("SELECT * FROM states ORDER BY states ASC");
  $states2 = $db->query("SELECT * FROM states ORDER BY states ASC");
  $companies = $db->query("SELECT * FROM companies WHERE deleted = 0 ORDER BY name ASC");

  if ($role != 'SADMIN'){
    $currencies = $db->query("SELECT * FROM currency WHERE deleted = 0 AND customer = '$company' ORDER BY currency ASC");
    $suppliers = $db->query("SELECT * FROM supplies WHERE deleted = 0 AND customer = '$company' ORDER BY supplier_name ASC");
  }else{
    $currencies = $db->query("SELECT * FROM currency WHERE deleted = 0 AND customer = '$company' ORDER BY currency ASC");
    $suppliers = $db->query("SELECT * FROM supplies WHERE deleted = 0 ORDER BY supplier_name ASC");
  }

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
  
  $includeInvoice = 'N';
  $runningNoType = 0;
  if ($company_stmt = $db->prepare("SELECT * FROM companies WHERE id = ?")) {
    $company_stmt->bind_param("i", $company);
    $company_stmt->execute();
    $company_result = $company_stmt->get_result();
    $rowCompany = mysqli_fetch_assoc($company_result);
    $includeInvoice = $rowCompany['include_invoice'];
    $runningNoType = $rowCompany['running_no_type'];
  }
}
?>

<div class="content-header" style="padding-bottom: 0;">
    <div class="container-fluid">
        <!-- Breadcrumb or minimal header can go here if needed -->
    </div>
</div>

<!-- Main content -->
<section class="content page-modern">
	<div class="container-fluid">
        <div class="row">
			<div class="col-12">
				<div class="card results-card show-dt-controls">
					<div class="card-header">
            <div class="results-header-left">
              <h3 class="results-title"><i class="fas fa-truck mr-2"></i><?=$languageArray['suppliers_code'][$language]?></h3>
            </div>
            <div class="results-header-right d-flex flex-wrap" style="gap: 0.5rem;">
              <a href="template/Supplier_Template.xlsx" download class="btn btn-action btn-action-warning">
                <i class="fas fa-download"></i> <?=$languageArray['download_template_code'][$language]?>
              </a>
              <button type="button" id="uploadExcel" class="btn btn-action btn-action-success">
                <i class="fas fa-upload"></i> <?=$languageArray['upload_excel_code'][$language]?>
              </button>
              <button type="button" id="multiDeactivate" class="btn btn-action btn-action-danger">
                <i class="fas fa-trash-alt"></i> <?=$languageArray['delete_supplier_code'][$language]?>
              </button>
              <button type="button" class="btn btn-action btn-action-primary" id="addSuppliers">
                <i class="fas fa-plus"></i> <?=$languageArray['add_suppliers_code'][$language]?>
              </button>
            </div>
          </div>
					<div class="card-body">
						<table id="supplierTable" class="table data-table">
							<thead>
								<tr>
                  <th><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
                  <th><?=$languageArray['supplier_code_code'][$language]?></th>
                  <th><?=$languageArray['reg_no_code'][$language]?></th>
                  <th><?=$languageArray['parent_code'][$language]?></th>
									<th><?=$languageArray['supplier_name_code'][$language]?></th>
									<th><?=$languageArray['address_code'][$language]?></th>
									<th><?=$languageArray['phone_code'][$language]?></th>
									<th><?=$languageArray['pic_code'][$language]?></th>
								<th width="15%"><?=$languageArray['actions_code'][$language]?></th>
								</tr>
							</thead>
						</table>
					</div><!-- /.card-body -->
				</div><!-- /.card -->
			</div><!-- /.col -->
		</div><!-- /.row -->
	</div><!-- /.container-fluid -->
</section><!-- /.content -->

<div class="modal fade modal-modern" id="uploadModal">
  <div class="modal-dialog" style="max-width: 90vw">
    <div class="modal-content">
      <form role="form" id="uploadForm">
          <div class="modal-header">
            <h4 class="modal-title"><?=$languageArray['upload_excel_code'][$language]?></h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="card-body">
              <input type="file" id="fileInput">
              <button type="button" id="previewButton"><?=$languageArray['preview_data_code'][$language]?></button>
              <div id="previewTable" style="overflow: auto;"></div>
            </div>
          </div>
          <div class="modal-footer justify-content-between">
            <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
            <button type="button" class="btn btn-modern btn-modern-primary" id="uploadSupplier"><?=$languageArray['submit_code'][$language]?></button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade modal-modern" id="errorModal" style="display:none">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form role="form" id="errorForm">
          <div class="modal-header">
            <h4 class="modal-title"><?=$languageArray['error_log_code'][$language]?></h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="form-group">
                <ol id="errorList" class="text-danger mt-2" style="padding-left: 20px;"></ol>
              </div>
            </div>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade modal-modern" id="addModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form role="form" id="supplierForm">
          <div class="modal-header">
            <h5 class="modal-title"><?=$languageArray['add_suppliers_code'][$language]?></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="id" name="id">

            <!-- Company (SADMIN only) -->
            <div class="row" <?php if($role != 'SADMIN'){ echo 'style="display:none;"'; } ?>>
              <div class="col-md-12">
                <div class="form-group">
                  <label class="form-label-modern"><?=$languageArray['company_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control select2" style="width:100%;" id="company" name="company" required>
                    <?php while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                      <option value="<?=$rowCompany['id'] ?>" <?php if($rowCompany['id'] == $company) echo 'selected'; ?>><?=$rowCompany['name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
            </div>

            <!-- Basic Information -->
            <div class="modal-section">
              <div class="section-title"><i class="fas fa-user mr-2"></i><?=$languageArray['basic_information_code'][$language] ?? 'Basic Information'?></div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['supplier_name_code'][$language]?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" id="name" placeholder="Supplier name" required>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['supplier_code_code'][$language]?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="code" id="code" placeholder="Supplier code" required>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['supplier_type_code'][$language] ?? 'Supplier Type'?></label>
                    <select class="form-control select2" style="width:100%;" id="supplierType" name="supplierType">
                      <option value="Normal" selected><?=$languageArray['normal_code'][$language] ?? 'Normal'?></option>
                      <option value="Packing"><?=$languageArray['packing_code'][$language] ?? 'Packing'?></option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['reg_no_code'][$language]?></label>
                    <input type="text" class="form-control" name="reg_no" id="reg_no" placeholder="Registration number">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['parent_code'][$language]?></label>
                    <select class="form-control select2" style="width:100%;" id="parent" name="parent">
                      <option value="">Select Parent</option>
                      <?php while($rowSupplier=mysqli_fetch_assoc($suppliers)){ ?>
                        <option value="<?=$rowSupplier['id'] ?>"><?=$rowSupplier['supplier_name'] ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- Delivery Address -->
            <div class="modal-section">
              <div class="section-title"><i class="fas fa-map-marker-alt mr-2"></i><?=$languageArray['delivery_address_code'][$language]?></div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['address_code'][$language]?></label>
                    <input type="text" class="form-control" name="address" id="address" placeholder="Street address">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['address_code'][$language]?> 2</label>
                    <input type="text" class="form-control" name="address2" id="address2" placeholder="City">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['address_code'][$language]?> 3</label>
                    <input type="text" class="form-control" name="address3" id="address3" placeholder="Postcode">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['address_code'][$language]?> 4</label>
                    <input type="text" class="form-control" name="address4" id="address4" placeholder="Country">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['states_code'][$language]?></label>
                    <select class="form-control select2" style="width:100%;" id="states" name="states">
                      <option value="">Select State</option>
                      <?php while($rowCustomer2=mysqli_fetch_assoc($states)){ ?>
                        <option value="<?=$rowCustomer2['id'] ?>"><?=$rowCustomer2['states'] ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern">Fax</label>
                    <input type="text" class="form-control" name="fax" id="fax" placeholder="Fax number">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['phone_code'][$language]?></label>
                    <input type="text" class="form-control" name="phone" id="phone" placeholder="Phone number">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['pic_code'][$language]?></label>
                    <input type="text" class="form-control" id="email" name="email" placeholder="Person In Charge">
                  </div>
                </div>
              </div>
            </div>
            <!-- Billing Address -->
            <div class="modal-section" <?= ($includeInvoice == 'Y' ? '' : 'style="display:none;"') ?>>
              <div class="section-title d-flex align-items-center justify-content-between">
                <span><i class="fas fa-file-invoice mr-2"></i><?=$languageArray['billing_address_code'][$language]?></span>
                <div class="form-check mb-0">
                  <input type="checkbox" class="form-check-input" id="sameAsDelivery">
                  <label class="form-check-label font-weight-normal" for="sameAsDelivery"><?=$languageArray['same_as_delivery_address_code'][$language]?></label>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['billing_name_code'][$language]?></label>
                    <input type="text" class="form-control" name="billingName" id="billingName" placeholder="Billing name">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['currency_code'][$language]?></label>
                    <select class="form-control select2" style="width:100%;" id="currency" name="currency">
                      <option value="">Select Currency</option>
                      <?php while($rowCurrency=mysqli_fetch_assoc($currencies)){ ?>
                        <option value="<?=$rowCurrency['id'] ?>"><?=$rowCurrency['currency'] ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['billing_pic_code'][$language]?></label>
                    <input type="text" class="form-control" id="billingPic" name="billingPic" placeholder="Person In Charge">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['billing_address_code'][$language]?></label>
                    <input type="text" class="form-control" name="billingAddress" id="billingAddress" placeholder="Street address">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['billing_address_code'][$language]?> 2</label>
                    <input type="text" class="form-control" name="billingAddress2" id="billingAddress2" placeholder="City">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['billing_address_code'][$language]?> 3</label>
                    <input type="text" class="form-control" name="billingAddress3" id="billingAddress3" placeholder="Postcode">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['billing_address_code'][$language]?> 4</label>
                    <input type="text" class="form-control" name="billingAddress4" id="billingAddress4" placeholder="Country">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['billing_state_code'][$language]?></label>
                    <select class="form-control select2" style="width:100%;" id="billingStates" name="billingStates">
                      <option value="">Select State</option>
                      <?php while($rowCustomer2=mysqli_fetch_assoc($states2)){ ?>
                        <option value="<?=$rowCustomer2['id'] ?>"><?=$rowCustomer2['states'] ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['billing_fax_code'][$language]?></label>
                    <input type="text" class="form-control" name="billingFax" id="billingFax" placeholder="Fax number">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['billing_phone_code'][$language]?></label>
                    <input type="text" class="form-control" name="billingPhone" id="billingPhone" placeholder="Phone number">
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer justify-content-between">
            <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
            <button type="submit" class="btn btn-modern btn-modern-primary" name="submit" id="submitMember"><?=$languageArray['submit_code'][$language]?></button>
          </div>
      </form>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>

<div class="modal fade modal-modern" id="runningNoModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-hashtag mr-2"></i><?=$languageArray['running_no_code'][$language]?? 'Running No' ?> — <span id="runningNoSupplierName"></span></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="runningNoEntityId">
        <div class="modal-section">
          <div class="section-title"><i class="fas fa-tag mr-2"></i><?=$languageArray['invoice_code'][$language] ?? 'Invoice Code' ?></div>
          <div class="form-group mb-0">
            <input type="text" class="form-control" id="runningNoInvoiceCode" maxlength="50" placeholder="e.g. SUP-001">
          </div>
        </div>
        <div class="modal-section">
          <div class="section-title"><i class="fas fa-list-ol mr-2"></i><?=$languageArray['running_no_code'][$language] ?? 'Running Numbers' ?></div>
          <table class="table table-bordered table-sm mb-0">
          <thead>
            <tr>
              <th><?=$languageArray['status_code'][$language] ?? 'Status' ?></th>
              <th><?=$languageArray['prefix_code'][$language] ?? 'Prefix' ?></th>
              <th><?=$languageArray['next_value_code'][$language] ?? 'Next Value' ?></th>
            </tr>
          </thead>
          <tbody id="runningNoBody"></tbody>
          </table>
          <div class="alert alert-light border mt-2 mb-0 py-2 px-3" style="font-size: 0.8125rem;">
            <i class="fas fa-info-circle text-info mr-1"></i>
            <strong><?=$languageArray['format_code'][$language] ?? 'Format' ?>:</strong> 
            <code>[Prefix]-[Invoice Code]-[YYMM]/[Value]</code>
            <br><small class="text-muted">e.g. IV-APL-2608/25001</small>
          </div>
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
        <button type="button" class="btn btn-modern btn-modern-primary" id="saveRunningNo"><?=$languageArray['submit_code'][$language]?></button>
      </div>
    </div>
  </div>
</div>

<!-- jQuery -->
<script>

var runningNoType = <?= (int)($runningNoType ?? 0) ?>;

function openRunningNo(id, name) {
  $('#runningNoEntityId').val(id);
  $('#runningNoSupplierName').text(name);
  $('#runningNoInvoiceCode').val('');
  $('#runningNoBody').html('<tr><td colspan="3" class="text-center"><i class="fas fa-spinner fa-spin"></i></td></tr>');
  $('#runningNoModal').modal('show');
  $.get('php/modules/suppliers/runningNo.php', { entity_id: id }, function(res) {
    var obj = JSON.parse(res);
    $('#runningNoInvoiceCode').val(obj.invoice_code || '');
    var html = '';
    obj.data.forEach(function(row) {
      html += '<tr>'
        + '<td>' + row.status + '<input type="hidden" name="transaction_status" value="' + row.status + '"></td>'
        + '<td><input type="text" class="form-control form-control-sm rn-prefix" value="' + row.saved_prefix + '" maxlength="10"></td>'
        + '<td><input type="number" class="form-control form-control-sm rn-value" value="' + row.value + '" min="1"></td>'
        + '</tr>';
    });
    $('#runningNoBody').html(html);
  });
}

$('#saveRunningNo').on('click', function() {
  var rows = [];
  var valid = true;
  $('#runningNoBody tr').each(function() {
    var status = $(this).find('input[name="transaction_status"]').val();
    var prefix = $(this).find('.rn-prefix').val().trim();
    var value  = parseInt($(this).find('.rn-value').val());
    if (!prefix || prefix.length > 10 || isNaN(value) || value < 1) { valid = false; return false; }
    rows.push({ transaction_status: status, prefix: prefix, value: value });
  });
  if (!valid) { toastr["error"]("Please check prefix (max 10 chars) and value (min 1).", "Failed:"); return; }
  $('#spinnerLoading').show();
  $.ajax({
    url: 'php/modules/suppliers/runningNo.php',
    type: 'POST',
    data: { entity_id: $('#runningNoEntityId').val(), invoice_code: $('#runningNoInvoiceCode').val().trim(), rows: rows },
    success: function(res) {
      var obj = JSON.parse(res);
      if (obj.status === 'success') {
        $('#runningNoModal').modal('hide');
        toastr["success"](obj.message, "Success:");
      } else {
        toastr["error"](obj.message, "Failed:");
      }
      $('#spinnerLoading').hide();
    }
  });
});

$(function () {
  $('#selectAllCheckbox').on('change', function() {
    var checkboxes = $('#supplierTable tbody input[type="checkbox"]');
    checkboxes.prop('checked', $(this).prop('checked')).trigger('change');
  });

  $('.select2').each(function() {
    $(this).select2({
        allowClear: true,
        placeholder: "Please Select",
        // Conditionally set dropdownParent based on the element’s location
        dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal') : undefined
    });
  });

  $("#supplierTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'language': {
      'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
      'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
    },
    'ajax': {
        'url':'php/modules/suppliers/loadSupplier.php'
    },
    'columns': [
      {
        // Add a checkbox with a unique ID for each row
        data: 'id', // Assuming 'serialNo' is a unique identifier for each row
        className: 'select-checkbox',
        orderable: false,
        render: function (data, type, row) {
            return '<input type="checkbox" class="select-checkbox" id="checkbox_' + data + '" value="'+data+'"/>';
        }
      },
      { data: 'supplier_code' },
      { data: 'reg_no' },
      { data: 'parent' },
      { data: 'supplier_name' },
      { data: 'supplier_address' },
      { data: 'supplier_phone' },
      { data: 'pic' },
      { 
          data: 'id',
          render: function ( data, type, row ) {
              var html = '<div style="display:flex;gap:4px;">'
                + '<button type="button" onclick="edit('+data+')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button>';
              if (runningNoType === 1) {
                html += '<button onclick="openRunningNo(' + data + ', \'' + row.supplier_name.replace(/'/g, "\\'") + '\')" class="btn btn-secondary btn-sm"><i class="fas fa-hashtag"></i></button>';
              }
              html += '<button type="button" onclick="deactivate('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>'
                + '</div>';
              return html;
          }
      }
    ],
    "rowCallback": function( row, data, index ) {
      if (data.is_manual == 'Y') {
        $(row).css('background-color', '#f8d7da');
      }
    },
  });
    
  $.validator.setDefaults({
      submitHandler: function () {
          //$('#spinnerLoading').show();
          $.post('php/modules/suppliers/suppliers.php', $('#supplierForm').serialize(), function(data){
              var obj = JSON.parse(data); 
              
              if(obj.status === 'success'){
                $('#addModal').modal('hide');
                toastr["success"](obj.message, "Success:");
                $('#supplierTable').DataTable().ajax.reload();
                // Refresh the parent dropdown
                $.get('php/modules/suppliers/getSuppliers.php', function(data) {
                  var suppliers = JSON.parse(data);
                  $('#parent').empty().append('<option value="">Please Select</option>');
                  suppliers.forEach(function(supplier) {
                    $('#parent').append('<option value="' + supplier.id + '">' + supplier.supplier_name + '</option>');
                  });
                });
                //$('#spinnerLoading').hide();
              }
              else if(obj.status === 'failed'){
                  toastr["error"](obj.message, "Failed:");
                  //$('#spinnerLoading').hide();
              }
              else{
                  toastr["error"]("Something wrong when edit", "Failed:");
                  //$('#spinnerLoading').hide();
              }
          });
      }
  });

  $('#sameAsDelivery').on('change', function() {
    var isSame = $(this).is(':checked');
    var billingFields = ['#billingAddress', '#billingAddress2', '#billingAddress3', '#billingAddress4', '#billingName', '#billingPhone', '#billingPic'];
    if (isSame) {
      $('#billingName').val($('#name').val());
      $('#billingPhone').val($('#phone').val());
      $('#billingPic').val($('#email').val());
      $('#billingAddress').val($('#address').val());
      $('#billingAddress2').val($('#address2').val());
      $('#billingAddress3').val($('#address3').val());
      $('#billingAddress4').val($('#address4').val());
      $('#billingStates').val($('#states').val()).trigger('change');
      $.each(billingFields, function(i, sel) { $(sel).prop('readonly', true); });
      $('#billingStates').next('.select2-container').css('pointer-events', 'none').css('opacity', '0.6');
    } else {
      $.each(billingFields, function(i, sel) { $(sel).prop('readonly', false); });
      $('#billingStates').next('.select2-container').css('pointer-events', '').css('opacity', '');
    }
  });

  $('#addModal').on('hidden.bs.modal', function() {
    $('#sameAsDelivery').prop('checked', false);
    ['#billingAddress','#billingAddress2','#billingAddress3','#billingAddress4','#billingName','#billingPhone','#billingPic'].forEach(function(sel) { $(sel).prop('readonly', false); });
    $('#billingStates').next('.select2-container').css('pointer-events', '').css('opacity', '');
  });

  $('#addSuppliers').on('click', function(){
      $('#addModal').find('#id').val("");
      $('#addModal').find('#code').val("");
      $('#addModal').find('#reg_no').val("");
      $('#addModal').find('#name').val("");
      $('#addModal').find('#address').val("");
      $('#addModal').find('#address2').val("");
      $('#addModal').find('#address3').val("");
      $('#addModal').find('#address4').val("");
      $('#addModal').find('#states').val("").trigger('change');
      $('#addModal').find('#phone').val("");
      $('#addModal').find('#fax').val("");
      $('#addModal').find('#email').val("");
      $('#addModal').find('#billingName').val("");
      $('#addModal').find('#billingAddress').val("");
      $('#addModal').find('#billingAddress2').val("");
      $('#addModal').find('#billingAddress3').val("");
      $('#addModal').find('#billingAddress4').val("");
      $('#addModal').find('#billingStates').val("").trigger('change');
      $('#addModal').find('#billingPhone').val("");
      $('#addModal').find('#billingFax').val("");
      $('#addModal').find('#billingPic').val("");
      $('#addModal').find('#currency').val("").trigger('change');
      $('#addModal').find('#parent').val("").trigger('change');
      $('#addModal').find('#supplierType').val("Normal").trigger('change');
      $('#addModal').modal('show');
      
      $('#supplierForm').validate({
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
  });
});

$('#uploadExcel').on('click', function(){
  $('#uploadModal').modal('show');

  $('#uploadForm').validate({
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
});

$('#uploadModal').find('#previewButton').on('click', function(){
  var fileInput = document.getElementById('fileInput');
  var file = fileInput.files[0];
  var reader = new FileReader();
  
  reader.onload = function(e) {
      var data = e.target.result;
      // Process data and display preview
      displayPreview(data);
  };

  reader.readAsBinaryString(file);
});

$('#uploadSupplier').on('click', function(){
  $('#spinnerLoading').show();
  var formData = $('#uploadForm').serializeArray();
  var data = [];
  var rowIndex = -1;
  formData.forEach(function(field) {
  var match = field.name.match(/([a-zA-Z0-9]+)\[(\d+)\]/);
  if (match) {
    var fieldName = match[1];
    var index = parseInt(match[2], 10);
    if (index !== rowIndex) {
    rowIndex = index;
    data.push({});
    }
    data[index][fieldName] = field.value;
  }
  });

  // Send the JSON array to the server
  $.ajax({
      url: 'php/modules/suppliers/uploadSupplier.php',
      type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(data),
      success: function(response) {
          var obj = JSON.parse(response);
          if (obj.status === 'success') {
            $('#spinnerLoading').hide();
            $('#uploadModal').modal('hide');
            $('#supplierTable').DataTable().ajax.reload();
          } 
          else if (obj.status === 'failed') {
            $('#spinnerLoading').hide();
          } 
          else if (obj.status === 'error') {
            $('#spinnerLoading').hide();
            $('#uploadModal').modal('hide');
            $('#errorModal').find('#errorList').empty();
            var errorMessage = obj.message;
            for (var i = 0; i < errorMessage.length; i++) {
              $('#errorModal').find('#errorList').append(`<li>${errorMessage[i]}</li>`);                            
            }
            $('#errorModal').modal('show');
          } 
          else {
            $('#spinnerLoading').hide();
          }
      }
  });
});

$('#multiDeactivate').on('click', function () {
  $('#spinnerLoading').show();
  var selectedIds = [];

  $("#supplierTable tbody input[type='checkbox']").each(function () {
    if (this.checked) {
        selectedIds.push($(this).val());
    }
  });

  if (selectedIds.length > 0) {
    if (confirm('Are you sure you want to cancel these items?')) {
        $.post('php/modules/suppliers/deleteSupplier.php', {userID: selectedIds, type: 'MULTI'}, function(data){
            var obj = JSON.parse(data);
            
            if(obj.status === 'success'){
              $('#supplierTable').DataTable().ajax.reload();
              $('#spinnerLoading').hide();
            }
            else if(obj.status === 'failed'){
              $('#spinnerLoading').hide();
            }
            else{
              $('#spinnerLoading').hide();
            }
        });
    } else {
      $('#spinnerLoading').hide();
    }
  } 
  else {
      alert("Please select at least one supplier to delete.");
      $('#spinnerLoading').hide();
  }     
});

function displayPreview(data) {
  // Parse the Excel data
  var workbook = XLSX.read(data, { type: 'binary' });

  // Get the first sheet
  var sheetName = workbook.SheetNames[0];
  var sheet = workbook.Sheets[sheetName];

  // Convert the sheet to an array of objects
  var jsonData = XLSX.utils.sheet_to_json(sheet, { header: 20 });

  // Get the headers
  var headers = Object.keys(jsonData[0] || {});

  // Ensure we handle cases where there may be less than 20 columns
  while (headers.length < 20) {
      headers.push(''); // Adding empty headers to reach 20 columns
  }

  // Create HTML table headers
  var htmlTable = '<table style="width:20%;"><thead><tr>';
  headers.forEach(function(header) {
      htmlTable += '<th>' + header + '</th>';
  });
  htmlTable += '</tr></thead><tbody>';

  // Iterate over the data and create table rows
  for (var i = 0; i < jsonData.length; i++) {
      htmlTable += '<tr>';
      var rowData = jsonData[i];

      for (var j = 0; j < 20 && j < headers.length; j++) {
          var cellData = rowData[headers[j]];
          var formattedData = cellData;

          // Check if cellData is a valid Excel date serial number and format it to DD/MM/YYYY
          if (typeof cellData === 'number' && cellData > 0) {
              var excelDate = XLSX.SSF.parse_date_code(cellData);
          }

          htmlTable += '<td><input type="text" id="'+headers[j].replace(/[^a-zA-Z0-9]/g, '')+i+'" name="'+headers[j].replace(/[^a-zA-Z0-9]/g, '')+'['+i+']" value="' + (formattedData == null ? '' : formattedData) + '" /></td>';
      }
      htmlTable += '</tr>';
  }

  htmlTable += '</tbody></table>';

  var previewTable = document.getElementById('previewTable');
  previewTable.innerHTML = htmlTable;
}

function edit(id){
  $('#spinnerLoading').show();
  $.post('php/modules/suppliers/getSupplier.php', {userID: id}, function(data){
      var obj = JSON.parse(data);
      
      if(obj.status === 'success'){
          $('#addModal').find('#id').val(obj.message.id);
          $('#addModal').find('#code').val(obj.message.supplier_code);
          $('#addModal').find('#reg_no').val(obj.message.reg_no);
          $('#addModal').find('#name').val(obj.message.supplier_name);
          $('#addModal').find('#address').val(obj.message.supplier_address);
          $('#addModal').find('#address2').val(obj.message.supplier_address2);
          $('#addModal').find('#address3').val(obj.message.supplier_address3);
          $('#addModal').find('#address4').val(obj.message.supplier_address4);
          $('#addModal').find('#states').val(obj.message.states).trigger('change');
          $('#addModal').find('#phone').val(obj.message.supplier_phone);
          $('#addModal').find('#fax').val(obj.message.fax);
          $('#addModal').find('#email').val(obj.message.pic);
          $('#addModal').find('#billingName').val(obj.message.billing_name);
          $('#addModal').find('#billingAddress').val(obj.message.billing_address);
          $('#addModal').find('#billingAddress2').val(obj.message.billing_address2);
          $('#addModal').find('#billingAddress3').val(obj.message.billing_address3);
          $('#addModal').find('#billingAddress4').val(obj.message.billing_address4);
          $('#addModal').find('#billingStates').val(obj.message.billing_state).trigger('change');
          $('#addModal').find('#billingPhone').val(obj.message.billing_phone);
          $('#addModal').find('#billingFax').val(obj.message.billing_fax);
          $('#addModal').find('#billingPic').val(obj.message.billing_pic);
          $('#addModal').find('#currency').val(obj.message.currency).trigger('change');
          $('#addModal').find('#company').val(obj.message.customer).trigger('change');
          $('#addModal').find('#parent').val(obj.message.parent).trigger('change');
          $('#addModal').find('#supplierType').val(obj.message.supplier_type || 'Normal').trigger('change');
          $('#addModal').modal('show');
          
          $('#supplierForm').validate({
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
        alert(obj.message);
        toastr["error"](obj.message, "Failed:");
      }
      else{
        alert(obj.message);
        toastr["error"]("Something wrong when activate", "Failed:");
      }
      $('#spinnerLoading').hide();
  });
}

function deactivate(id){
    if (confirm('Are you sure you want to delete this items?')) {
        //$('#spinnerLoading').show();
        $.post('php/modules/suppliers/deleteSupplier.php', {userID: id}, function(data){
            var obj = JSON.parse(data);
            
            if(obj.status === 'success'){
                toastr["success"](obj.message, "Success:");
                $('#supplierTable').DataTable().ajax.reload();
                //$('#spinnerLoading').hide();
            }
            else if(obj.status === 'failed'){
                toastr["error"](obj.message, "Failed:");
                //$('#spinnerLoading').hide();
            }
            else{
                toastr["error"]("Something wrong when activate", "Failed:");
                //$('#spinnerLoading').hide();
            }
        });
    }
}

function reactivate(id){
  if (confirm('Are you sure you want to reactivate this items?')) {
    //$('#spinnerLoading').show();
    $.post('php/modules/suppliers/reactivateSupplier.php', {userID: id}, function(data){
        var obj = JSON.parse(data);
        
        if(obj.status === 'success'){
            toastr["success"](obj.message, "Success:");
            $('#supplierTable').DataTable().ajax.reload();
            //$('#spinnerLoading').hide();
        }
        else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
            //$('#spinnerLoading').hide();
        }
        else{
            toastr["error"]("Something wrong when activate", "Failed:");
            //$('#spinnerLoading').hide();
        }
    });
  }
}
</script>