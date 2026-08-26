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

  if ($role != 'SADMIN'){
    $categoryFilter = !empty($categoryIds) ? " AND c.id IN (" . implode(',', array_map('intval', $categoryIds)) . ")" : "";
    $categories = $db->query("SELECT * FROM categories c WHERE c.deleted = '0' AND c.customer = '$company' AND c.module IN ('wholesale', 'processing')$categoryFilter ORDER BY c.category_name ASC");
    $products = $db->query("SELECT * FROM products WHERE deleted = '0' AND customer = '$company' ORDER BY product_name ASC");
    $supplies = $db->query("SELECT * FROM supplies WHERE deleted = '0' AND customer = '$company' ORDER BY supplier_name ASC");
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' AND customer = '$company' ORDER BY customer_name ASC");
    $vehicles2 = $db->query("SELECT * FROM vehicles WHERE deleted = '0' AND customer = '$company' ORDER BY veh_number ASC");
    $users = $db->query("SELECT * FROM users WHERE deleted = '0' AND customer = '$company' ORDER BY name ASC");
    $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' AND customer = '$company' ORDER BY locations ASC");

    // Company Detail 
    $companyDetail = searchCompanyById($company, $db);
    $allowPrice = $companyDetail['include_price'];
    $allowIntegration = $companyDetail['include_integration'];
  } else {
    $categories = $db->query("SELECT * FROM categories WHERE deleted = '0' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $products = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $supplies = $db->query("SELECT * FROM supplies WHERE deleted = '0' ORDER BY supplier_name ASC");
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' ORDER BY customer_name ASC");
    $vehicles2 = $db->query("SELECT * FROM vehicles WHERE deleted = '0' ORDER BY veh_number ASC");
    $users = $db->query("SELECT * FROM users WHERE deleted = '0' ORDER BY name ASC");
    $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' ORDER BY locations ASC");

    $allowPrice = 'Y';
    $allowIntegration = 'Y';
  }

  // Integration Configs
  $integrationConfigs = [];
  if ($allowIntegration == 'Y') {
    $intStmt = $db->prepare("SELECT id, integration_type, status, name, config_json FROM integration_configs WHERE company_id = ? AND module = 'wholesale' AND deleted = 0");
    $intStmt->bind_param('i', $company);
    $intStmt->execute();
    $intResult = $intStmt->get_result();
    while ($intRow = $intResult->fetch_assoc()) {
      $integrationConfigs[] = $intRow;
    }
  }

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>

<div class="content page-modern">
  <div class="container-fluid">

    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title"><i class="fas fa-chart-bar"></i> <?=$languageArray['reports_code'][$language]?></h1>
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
            <label class="filter-label"><?=$languageArray['transaction_status_code'][$language]?></label>
            <select class="form-control" id="transactionStatusFilter">
              <option value="DISPATCH" selected><?=$languageArray['dispatch_code'][$language]?></option>
              <option value="RECEIVING"><?=$languageArray['receiving_code'][$language]?></option>
              <?php if (in_array('stocks', $companyProducts)) { ?>
              <option value="STOCK-BAL"><?=$languageArray['stock_balance_code'][$language]?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group" id="customerStatusDiv">
            <label class="filter-label"><?=$languageArray['customer_code'][$language]?></label>
            <select class="form-control select2" id="customerNoFilter">
              <option value=""><?=$languageArray['please_select_code'][$language]?></option>
              <?php while($rowCustomer2=mysqli_fetch_assoc($customers)){ ?>
                <option value="<?=$rowCustomer2['id'] ?>"><?=$rowCustomer2['customer_name'] ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group" id="supplierStatusDiv" style="display:none;">
            <label class="filter-label"><?=$languageArray['supplier_code'][$language]?></label>
            <select class="form-control select2" id="supplierNoFilter">
              <option value=""><?=$languageArray['please_select_code'][$language]?></option>
              <?php while($rowCustomer2=mysqli_fetch_assoc($supplies)){ ?>
                <option value="<?=$rowCustomer2['id'] ?>"><?=$rowCustomer2['supplier_name'] ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['vehicle_no_code'][$language]?></label>
            <select class="form-control select2" id="vehicleNoFilter">
              <option value=""><?=$languageArray['please_select_code'][$language]?></option>
              <option value="OTHERS"><?=$languageArray['others_code'][$language]?></option>
              <?php while($rowVehicle=mysqli_fetch_assoc($vehicles2)){ ?>
                <option value="<?=$rowVehicle['veh_number'] ?>"><?=$rowVehicle['veh_number'] ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group" id="otherVehicleFilterDiv" style="display:none;">
            <label class="filter-label"><?=$languageArray['other_vehicle_no_code'][$language]?></label>
            <input type="text" class="form-control" id="otherVehicleNoFilter" placeholder="<?=$languageArray['please_enter_vehicle_no_code'][$language]?>">
          </div>
        </div>

        <div class="filter-row mt-3">
          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['category_code'][$language]?></label>
            <select class="form-control select2" id="categoryFilter">
              <option value=""><?=$languageArray['please_select_code'][$language]?></option>
              <?php while($rowCategory=mysqli_fetch_assoc($categories)){ ?>
                <option value="<?=$rowCategory['id'] ?>"><?=$rowCategory['category_name'] ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['locations_code'][$language]?></label>
            <select class="form-control select2" id="locationFilter">
              <option value="">-</option>
              <?php while($rowLocation=mysqli_fetch_assoc($locations)){ ?>
                <option value="<?=$rowLocation['id'] ?>"><?=$rowLocation['locations'] ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['checked_by_code'][$language]?></label>
            <input type="text" class="form-control" id="checkedByFilter" placeholder="<?=$languageArray['please_enter_name_code'][$language]?>">
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['weighed_by_code'][$language]?></label>
            <select class="form-control select2" id="weightByFilter">
              <option value=""><?=$languageArray['please_select_code'][$language]?></option>
              <?php while($rowUser=mysqli_fetch_assoc($users)){ ?>
                <option value="<?=$rowUser['id'] ?>"><?=$rowUser['name'] ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['type_code'][$language] ?? 'Type'?></label>
            <select class="form-control" id="partyTypeFilter" name="partyTypeFilter">
              <option value="" selected><?=$languageArray['all_code'][$language] ?? 'All'?></option>
              <option value="Normal"><?=$languageArray['normal_code'][$language] ?? 'Normal'?></option>
              <option value="Packing"><?=$languageArray['packing_code'][$language] ?? 'Packing'?></option>
            </select>
          </div>
        </div>

        <div class="filter-row mt-3">
          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['status_code'][$language]?></label>
            <select class="form-control" id="statusFilter">
              <option value="active" selected><?=$languageArray['active_code'][$language]?></option>
              <option value="deleted"><?=$languageArray['deleted_code'][$language]?></option>
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
          <h3 class="results-title"><i class="fas fa-list"></i> <?=$languageArray['reports_code'][$language]?></h3>
        </div>
        <div class="results-header-right d-flex" style="gap:0.5rem;">
          <?php if($allowIntegration == 'Y' && !empty($integrationConfigs)) { ?>
          <button type="button" class="btn btn-action btn-action-danger" id="exportIntegration">
            <i class="fas fa-plug"></i> <?=$languageArray['export_integration_code'][$language]?>
          </button>
          <?php } ?>
          <button type="button" class="btn btn-action btn-action-warning" id="exportPdf">
            <i class="fas fa-file-pdf"></i> <?=$languageArray['export_pdf_code'][$language]?>
          </button>
          <button type="button" class="btn btn-action btn-action-success" id="exportExcel">
            <i class="fas fa-file-excel"></i> <?=$languageArray['export_excel_code'][$language]?>
          </button>
        </div>
      </div>
      <div class="card-body">
        <table id="weightTable" class="table data-table">
          <thead>
            <tr>
              <th style="width:3%; text-align:center;"><input type="checkbox" id="selectAllCheckbox"></th>
              <th><?=$languageArray['serial_no_code'][$language]?></th>
              <th><?=$languageArray['do_po_no_code'][$language]?></th>
              <th><?=$languageArray['sec_bill_no_code'][$language]?></th>
              <th><?=$languageArray['start_time_code'][$language]?></th>
              <th><?=$languageArray['end_time_code'][$language]?></th>
              <th><?=$languageArray['parent_code'][$language]?></th>
              <th><?=$languageArray['customer_supplier_code'][$language]?></th>
              <th><?=$languageArray['vehicle_no_code'][$language]?></th>
              <th><?=$languageArray['driver_code'][$language]?></th>
              <th><?=$languageArray['total_item_code'][$language]?></th>
              <th><?=$languageArray['total_weight_code'][$language]?></th>
              <?php if($allowPrice == 'Y' && $userAllowPrice == 'Y') { ?>
              <th><?=$languageArray['total_price_code'][$language]?></th>
              <?php } else { ?>
              <th><?=$languageArray['total_reject_code'][$language]?></th>
              <?php } ?>
              <th><?=$languageArray['weighed_by_code'][$language]?></th>
              <th><?=$languageArray['checked_by_code'][$language]?></th>
            </tr>
          </thead>
          <tfoot>
            <tr>
              <th colspan="10"><?=$languageArray['total_code'][$language]?></th>
              <th></th>
              <th></th>
              <th></th>
              <th></th>
              <th></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- PDF Export Modal -->
<div class="modal fade modal-modern" id="pdfModal" tabindex="-1">
  <div class="modal-dialog" style="max-width:420px;">
    <div class="modal-content">
      <form id="pdfForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-file-pdf mr-2 text-muted"></i><?=$languageArray['export_pdf_code'][$language]?></h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$languageArray['report_type_code'][$language]?></label>
            <select class="form-control" id="pdfReportType">
              <option value="summary"><?=$languageArray['summary_report_code'][$language]?></option>
              <?php if($allowPrice == 'Y' && $userAllowPrice == 'Y') { ?>
              <option value="invoice"><?=$languageArray['invoice_listing_report_code'][$language]?></option>
              <?php } ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['cancel_code'][$language]?></button>
          <button type="submit" class="btn btn-modern btn-modern-primary"><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Integration Export Modal -->
<div class="modal fade modal-modern" id="integrationModal" tabindex="-1">
  <div class="modal-dialog" style="max-width:420px;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-plug mr-2 text-muted"></i><?=$languageArray['export_integration_code'][$language]?></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group-modern">
          <label class="form-label-modern"><?=$languageArray['integration_type_code'][$language]?></label>
          <select class="form-control" id="integrationType">
            <option value="">-- Select --</option>
          </select>
        </div>
        <div class="form-group-modern">
          <label class="form-label-modern"><?=$languageArray['document_type_code'][$language]?></label>
          <select class="form-control" id="integrationDocType" disabled>
            <option value="">-- Select --</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['cancel_code'][$language]?></button>
        <button type="button" class="btn btn-modern btn-modern-primary" id="integrationExportBtn"><?=$languageArray['export_code'][$language]?></button>
      </div>
    </div>
  </div>
</div>

<script>
var allowPrice = '<?=$allowPrice?>';
var userAllowPrice = '<?=$userAllowPrice?>';
var allowIntegration = '<?=$allowIntegration?>';
var integrationConfigs = <?=json_encode($integrationConfigs)?>;

$(function () {
  const today = new Date();
  const yesterday = new Date(today);
  yesterday.setDate(yesterday.getDate() - 7);

  $('.select2').select2({
    allowClear: true,
    placeholder: "Please Select"
  });

  //Date picker
  $('#fromDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: yesterday
  });

  $('#toDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: today
  });

  $('#selectAllCheckbox').on('change', function() {
    var checkboxes = $('#weightTable tbody input[type="checkbox"]');
    checkboxes.prop('checked', $(this).prop('checked')).trigger('change');
  });

  var table = initTable();

  $('#filterSearch').on('click', function() {
    $('#weightTable').DataTable().clear().destroy();
    table = initTable();
  });

  $.validator.setDefaults({
    submitHandler: function () {
      if ($('#pdfModal').hasClass('show')){
        $('#pdfModal').modal('hide');
        var reportType = $('#pdfReportType').val();
        var fromDateI = $('#fromDate').val();
        var toDateI = $('#toDate').val();
        var transactionStatusI = $('#transactionStatusFilter').val();
        var statusI = $('#statusFilter').val();
        var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
        var categoryI = $('#categoryFilter').val() ? $('#categoryFilter').val() : '';
        var customerNoI = $('#customerNoFilter').val() ? $('#customerNoFilter').val() : '';
        var supplierNoI = $('#supplierNoFilter').val() ? $('#supplierNoFilter').val() : '';
        var vehicleNoI = $('#vehicleNoFilter').val() ? $('#vehicleNoFilter').val() : '';
        var otherVehicleNoI = $('#otherVehicleNoFilter').val() ? $('#otherVehicleNoFilter').val() : '';
        var checkedByI = $('#checkedByFilter').val() ? $('#checkedByFilter').val() : '';
        var weightedByI = $('#weightByFilter').val() ? $('#weightByFilter').val() : '';
        var locationI = $('#locationFilter').val() ? $('#locationFilter').val() : '';
        var partyTypeI = $('#partyTypeFilter').val() ? $('#partyTypeFilter').val() : '';
        var selectedIds = [];

        $("#weightTable tbody input[type='checkbox']").each(function () {
          if (this.checked) selectedIds.push($(this).val());
        });

        var base = "php/modules/wholesales/exportPdf.php?reportType="+reportType+"&fromDate="+fromDateI+"&toDate="+toDateI+
          "&transactionStatus="+transactionStatusI+"&status="+statusI+
          "&customer="+customerNoI+"&supplier="+supplierNoI+"&product="+productI+"&category="+categoryI+
          "&vehicle="+vehicleNoI+"&otherVehicle="+otherVehicleNoI+"&checkedBy="+checkedByI+
          "&weightedBy="+weightedByI+"&location="+locationI+"&partyType="+partyTypeI;

        if (selectedIds.length > 0) {
          window.open(base + "&isMulti=Y&ids=" + selectedIds);
        } else {
          window.open(base + "&isMulti=N");
        }
      }
    }
  });

  $('#exportPdf').on('click', function() {
    $('#pdfReportType').val('summary');
    $('#pdfModal').modal('show');
    $('#pdfForm').validate({
      errorElement: 'span',
      errorPlacement: function(error, element) { 
        error.addClass('invalid-feedback'); 
        element.closest('.form-group-modern').append(error); 
      },
      highlight: function(element) { 
        $(element).addClass('is-invalid'); 
      },
      unhighlight: function(element) { 
        $(element).removeClass('is-invalid'); 
      }
    });
  });

  $('#exportExcel').on('click', function() {
    var params = buildParams();
    var selectedIds = getSelectedIds();
    window.open("php/modules/wholesales/export.php?" + params.substring(1) + (selectedIds.length > 0 ? "&isMulti=Y&ids=" + selectedIds : "&isMulti=N"));
  });

  if (allowIntegration === 'Y') {
    $('#exportIntegration').on('click', function() {
      var statusFilter = ($('#transactionStatusFilter').val() === 'STOCK-BAL') ? 'DISPATCH' : $('#transactionStatusFilter').val();
      $('#integrationType').empty().append('<option value="">-- Select --</option>');
      $('#integrationDocType').empty().append('<option value="">-- Select --</option>').prop('disabled', true);
      var types = [];
      integrationConfigs.forEach(function(config) {
        if (config.status === statusFilter && types.indexOf(config.integration_type) === -1) {
          types.push(config.integration_type);
          $('#integrationType').append('<option value="' + config.integration_type + '">' + config.integration_type + '</option>');
        }
      });
      $('#integrationModal').modal('show');
    });

    $('#integrationType').on('change', function() {
      var selectedType = $(this).val();
      var statusFilter = ($('#transactionStatusFilter').val() === 'STOCK-BAL') ? 'DISPATCH' : $('#transactionStatusFilter').val();
      var $docType = $('#integrationDocType').empty().append('<option value="">-- Select --</option>');
      if (selectedType) {
        $docType.prop('disabled', false);
        integrationConfigs.forEach(function(config) {
          if (config.integration_type === selectedType && config.status === statusFilter) {
            $docType.append('<option value="' + config.id + '">' + config.name + '</option>');
          }
        });
      } else {
        $docType.prop('disabled', true);
      }
    });

    $('#integrationExportBtn').on('click', function() {
      if (!$('#integrationType').val()) { 
        toastr.error("Please select an integration type.", "Validation Error:"); 
        return; 
      }

      var configId = $('#integrationDocType').val();
      if (!configId) { 
        toastr.error("Please select a document type.", "Validation Error:"); 
        return; 
      }
      var params = buildParams();
      var selectedIds = getSelectedIds();
      window.open("php/modules/wholesales/exportIntegration.php?configId=" + configId + params + (selectedIds.length > 0 ? "&isMulti=Y&ids=" + selectedIds : "&isMulti=N"));
      $('#integrationModal').modal('hide');
    });
  }

  $('#transactionStatusFilter').on('change', function() {
    var status = $(this).val();
    $('#customerNoFilter').val('').trigger('change');
    $('#supplierNoFilter').val('').trigger('change');
    if (status == "DISPATCH" || status == 'STOCK-BAL') {
      $('#customerStatusDiv').show();
      $('#supplierStatusDiv').hide();
    } else {
      $('#customerStatusDiv').hide();
      $('#supplierStatusDiv').show();
    }
  });

  $('#vehicleNoFilter').on('change', function() {
    var v = $(this).val();
    $('#otherVehicleFilterDiv').toggle(v == "UNKOWN NO" || v == "OTHERS" || v == "UNKNOWN");
  });
});

function buildParams() {
  return "&fromDate=" + $('#fromDate').val() +
    "&toDate=" + $('#toDate').val() +
    "&transactionStatus=" + $('#transactionStatusFilter').val() +
    "&status=" + $('#statusFilter').val() +
    "&customer=" + ($('#customerNoFilter').val() || '') +
    "&supplier=" + ($('#supplierNoFilter').val() || '') +
    "&product=" + ($('#productFilter').val() || '') +
    "&category=" + ($('#categoryFilter').val() || '') +
    "&vehicle=" + ($('#vehicleNoFilter').val() || '') +
    "&otherVehicle=" + ($('#otherVehicleNoFilter').val() || '') +
    "&checkedBy=" + ($('#checkedByFilter').val() || '') +
    "&weightedBy=" + ($('#weightByFilter').val() || '') +
    "&location=" + ($('#locationFilter').val() || '') +
    "&partyType=" + ($('#partyTypeFilter').val() || '');
}

function getSelectedIds() {
  var ids = [];
  $("#weightTable tbody input[type='checkbox']:checked").each(function() { 
    ids.push($(this).val()); 
  });
  return ids;
}

function initTable() {
  return $("#weightTable").DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: true,
    serverMethod: 'post',
    searching: true,
    order: [[ 1, 'asc' ]],
    columnDefs: [{ orderable: false, targets: [0] }],
    language: {
      emptyTable: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
      zeroRecords: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
    },
    ajax: {
      url: 'php/modules/wholesales/filterWholesale.php',
      data: {
        fromDate: $('#fromDate').val(),
        toDate: $('#toDate').val(),
        transactionStatus: $('#transactionStatusFilter').val(),
        status: $('#statusFilter').val(),
        product: $('#productFilter').val() || '',
        category: $('#categoryFilter').val() || '',
        customer: $('#customerNoFilter').val() || '',
        supplier: $('#supplierNoFilter').val() || '',
        vehicle: $('#vehicleNoFilter').val() || '',
        otherVehicle: $('#otherVehicleNoFilter').val() || '',
        checkedBy: $('#checkedByFilter').val() || '',
        weightedBy: $('#weightByFilter').val() || '',
        location: $('#locationFilter').val() || '', 
        partyType: $('#partyTypeFilter').val() || '', 
      }
    },
    columns: [
      {
        data: 'id', orderable: false, className: 'select-checkbox',
        render: function(data) { 
          return '<input type="checkbox" class="select-checkbox" value="' + data + '">'; 
        }
      },
      { data: 'serial_no' },
      { data: 'po_no' },
      { data: 'security_bills' },
      { data: 'start_time' },
      { data: 'end_time' },
      { data: 'parent' },
      { data: 'customer_supplier' },
      { data: 'vehicle_no' },
      { data: 'driver' },
      { data: 'total_item' },
      { data: 'total_weight' },
      { data: allowPrice == 'Y' && userAllowPrice == 'Y' ? 'total_price' : 'total_reject', orderable: allowPrice == 'Y' && userAllowPrice == 'Y' },
      { data: 'weighted_by' },
      { data: 'checked_by' }
    ],
    footerCallback: function(row, data, start, end, display) {
      var api = this.api();
      var totalItem = api.column(10, { page: 'current' }).data().reduce(function(a, b) { 
        return a + parseFloat(String(b || 0).replace(/,/g, '')); 
      }, 0);
      var totalWeight = api.column(11, { page: 'current' }).data().reduce(function(a, b) { 
        return a + parseFloat(String(b || 0).replace(/,/g, '')); 
      }, 0);

      if (allowPrice == 'Y' && userAllowPrice == 'Y') {
        var totalPrice = api.column(12, { page: 'current' }).data().reduce(function(a, b) {
          String(b || '').split(/<br\s*\/?>/i).forEach(function(part) {
            part = part.trim();
            if (!part) return;
            var tokens = part.split(' ');
            var cur = tokens[0];
            var amt = parseFloat((tokens[1] || '0').replace(/,/g, '')) || 0;
            a[cur] = (a[cur] || 0) + amt;
          });
          return a;
        }, {});
        $(api.column(12).footer()).html(Object.entries(totalPrice).map(function(e) { 
          return e[0] + ' ' + e[1].toFixed(2); 
        }).join('<br>') || '0.00');
      } else {
        var totalReject = api.column(12, { page: 'current' }).data().reduce(function(a, b) { 
          return a + parseFloat(String(b || 0).replace(/,/g, '')); 
        }, 0);
        $(api.column(12).footer()).html(totalReject.toFixed(2));
      }

      $(api.column(10).footer()).html(totalItem);
      $(api.column(11).footer()).html(totalWeight.toFixed(2));
    }
  });
}
</script>
