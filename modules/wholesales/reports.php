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
    $allowPcsBasket = $companyDetail['include_pcs_basket'];
    $secRemarksExists = ($companyDetail['include_sec_remark'] == 'Y');
    $columnSetup = [];
    if (!empty($companyDetail['column_setup'])) {
      $columnSetupAll = json_decode($companyDetail['column_setup'], true);
      $columnSetup = $columnSetupAll['wholesale']['columns'] ?? [];
    }
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
    $allowPcsBasket = 'Y';
    $secRemarksExists = true;
    $columnSetup = [];
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
          <div class="dropdown">
            <button class="btn btn-action btn-action-secondary dropdown-toggle" type="button" id="columnToggleBtn" data-toggle="dropdown">
              <i class="fas fa-columns"></i> <?=$languageArray['columns_code'][$language] ?? 'Columns'?>
            </button>
            <div class="dropdown-menu dropdown-menu-right p-2" id="columnToggleMenu" style="min-width:200px;max-height:300px;overflow-y:auto;"></div>
          </div>
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
              <th style="width:40px;"><input type="checkbox" id="selectAllCheckbox"></th>
              <?php
              $defaultColHeaders = [
                'serial_no_code'         => $languageArray['serial_no_code'][$language],
                'do_po_no_code'          => $languageArray['do_po_no_code'][$language],
                'location_code'          => $languageArray['locations_code'][$language],
                'sec_bill_no_code'       => $languageArray['sec_bill_no_code'][$language],
                'start_time_code'        => $languageArray['start_time_code'][$language],
                'end_time_code'          => $languageArray['end_time_code'][$language],
                'parent_code'            => $languageArray['parent_code'][$language],
                'customer_supplier_code' => $languageArray['customer_supplier_code'][$language],
                'vehicle_no_code'        => $languageArray['vehicle_no_code'][$language],
                'driver_code'            => $languageArray['driver_code'][$language],
                'total_item_code'        => $languageArray['total_item_code'][$language],
                'total_weight_code'      => $languageArray['total_weight_code'][$language],
                'total_price_reject_code'=> ($allowPrice == 'Y' && $userAllowPrice == 'Y') ? ($languageArray['total_price_code'][$language] ?? 'Total Price') : $languageArray['total_reject_code'][$language],
                'weighed_by_code'        => $languageArray['weighed_by_code'][$language],
                'checked_by_code'        => $languageArray['checked_by_code'][$language],
                'modified_by_code'       => $languageArray['modified_by_code'][$language] ?? 'Modified By',
              ];
              if ($secRemarksExists) {
                $defaultColHeaders['second_remarks_code'] = $languageArray['second_remarks_code'][$language];
              }
              $orderedHeaders = !empty($columnSetup)
                ? array_filter(array_map(fn($col) => isset($defaultColHeaders[$col['key']]) ? [$col['key'], $defaultColHeaders[$col['key']]] : null, $columnSetup))
                : array_map(fn($k, $v) => [$k, $v], array_keys($defaultColHeaders), $defaultColHeaders);
              foreach ($orderedHeaders as $col) {
                echo '<th>' . htmlspecialchars($col[1]) . '</th>';
              }
              ?>
            </tr>
          </thead>
          <tfoot>
            <tr>
              <th><?=$languageArray['total_code'][$language]?></th>
              <?php foreach (array_values($orderedHeaders) as $col) {
                $key = $col[0];
                if ($key === 'total_item_code' || $key === 'total_weight_code' || $key === 'total_price_reject_code') {
                  echo '<th data-footer="' . $key . '"></th>';
                } else {
                  echo '<th></th>';
                }
              } ?>
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
var allowPcsBasket = '<?=$allowPcsBasket?>';
var integrationConfigs = <?=json_encode($integrationConfigs)?>;
var columnSetup = <?=json_encode($columnSetup)?>;

var defaultColumns = [
  ['serial_no_code',          'serial_no',        '<?=$languageArray['serial_no_code'][$language]?>'],
  ['do_po_no_code',           'po_no',            '<?=$languageArray['do_po_no_code'][$language]?>'],
  ['location_code',           'location',         '<?=$languageArray['locations_code'][$language]?>'],
  ['sec_bill_no_code',        'security_bills',   '<?=$languageArray['sec_bill_no_code'][$language]?>'],
  ['start_time_code',         'start_time',       '<?=$languageArray['start_time_code'][$language]?>'],
  ['end_time_code',           'end_time',         '<?=$languageArray['end_time_code'][$language]?>'],
  ['parent_code',             'parent',           '<?=$languageArray['parent_code'][$language]?>'],
  ['customer_supplier_code',  'customer_supplier','<?=$languageArray['customer_supplier_code'][$language]?>'],
  ['vehicle_no_code',         'vehicle_no',       '<?=$languageArray['vehicle_no_code'][$language]?>'],
  ['driver_code',             'driver',           '<?=$languageArray['driver_code'][$language]?>'],
  ['total_item_code',         'total_item',       '<?=$languageArray['total_item_code'][$language]?>'],
  ['total_weight_code',       'total_weight',     '<?=$languageArray['total_weight_code'][$language]?>'],
  ['total_price_reject_code', allowPrice == 'Y' ? 'total_price' : 'total_reject', allowPrice == 'Y' ? '<?=$languageArray['total_price_code'][$language] ?? 'Total Price'?>' : '<?=$languageArray['total_reject_code'][$language]?>'],
  ['weighed_by_code',         'weighted_by',      '<?=$languageArray['weighed_by_code'][$language]?>'],
  ['checked_by_code',         'checked_by',       '<?=$languageArray['checked_by_code'][$language]?>'],
  ['modified_by_code',        'modified_by',      '<?=$languageArray['modified_by_code'][$language] ?? 'Modified By'?>']
  <?php if ($secRemarksExists) { ?>,['second_remarks_code', 'remarks2', '<?=$languageArray['second_remarks_code'][$language]?>']<?php } ?>
];

function buildColumnDefs() {
  var colMap = {};
  defaultColumns.forEach(function(col) { colMap[col[0]] = col; });
  return (columnSetup && columnSetup.length > 0)
    ? columnSetup.filter(function(s) { return colMap[s.key]; }).map(function(s) {
        return { col: colMap[s.key], visible: s.visible !== false };
      })
    : defaultColumns.map(function(col) { return { col: col, visible: true }; });
}

function getTableColumns() {
  var ordered = buildColumnDefs();
  var cols = [{
    data: 'id', className: 'select-checkbox', orderable: false,
    render: function(data, type, row) {
      return '<input type="checkbox" class="select-checkbox" id="checkbox_' + data + '" value="' + data + '"/>';
    }
  }];
  ordered.forEach(function(item) {
    cols.push({ data: item.col[1], visible: item.visible });
  });
  return cols;
}

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

  buildColumnToggleMenu();

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

function buildColumnToggleMenu() {
  var menu = $('#columnToggleMenu');
  menu.empty();
  var ordered = buildColumnDefs();
  ordered.forEach(function(item) {
    var label = item.col[2];
    var dataField = item.col[1];
    menu.append(
      '<div class="form-check">' +
        '<input class="form-check-input column-toggle" type="checkbox" data-field="' + dataField + '"' + (item.visible ? ' checked' : '') + '>' +
        '<label class="form-check-label">' + label + '</label>' +
      '</div>'
    );
  });
  menu.on('click', function(e) { e.stopPropagation(); });
  menu.on('change', '.column-toggle', function() {
    var field = $(this).data('field');
    var visible = $(this).is(':checked');
    var dt = $('#weightTable').DataTable();
    dt.columns().every(function() {
      if (this.dataSrc() === field) { this.visible(visible); }
    });
  });
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
    columns: getTableColumns(),
    footerCallback: function(row, data, start, end, display) {
      var api = this.api();
      var cols = getTableColumns();
      var totalItemIdx = -1, totalWeightIdx = -1, totalPriceIdx = -1;
      cols.forEach(function(c, i) {
        if (c.data === 'total_item') totalItemIdx = i;
        if (c.data === 'total_weight') totalWeightIdx = i;
        if (c.data === 'total_price' || c.data === 'total_reject') totalPriceIdx = i;
      });
      if (totalItemIdx > -1) {
        $(api.column(totalItemIdx).footer()).html(
          api.column(totalItemIdx, { page: 'current' }).data().reduce(function(a, b) { return a + parseFloat(String(b || 0).replace(/,/g, '')); }, 0)
        );
      }
      if (totalWeightIdx > -1) {
        $(api.column(totalWeightIdx).footer()).html(
          api.column(totalWeightIdx, { page: 'current' }).data().reduce(function(a, b) { return a + parseFloat(String(b || 0).replace(/,/g, '')); }, 0).toFixed(2)
        );
      }
      if (totalPriceIdx > -1) {
        if (allowPrice == 'Y' && userAllowPrice == 'Y') {
          var totals = api.column(totalPriceIdx, { page: 'current' }).data().reduce(function(a, b) {
            String(b || '').split(/<br\s*\/?>/i).forEach(function(part) {
              part = part.trim(); if (!part) return;
              var tokens = part.split(' ');
              var amt = parseFloat((tokens[1] || '0').replace(/,/g, '')) || 0;
              a[tokens[0]] = (a[tokens[0]] || 0) + amt;
            });
            return a;
          }, {});
          $(api.column(totalPriceIdx).footer()).html(Object.entries(totals).map(function(e) { return e[0] + ' ' + e[1].toFixed(2); }).join('<br>') || '0.00');
        } else {
          $(api.column(totalPriceIdx).footer()).html(
            api.column(totalPriceIdx, { page: 'current' }).data().reduce(function(a, b) { return a + parseFloat(String(b || 0).replace(/,/g, '')); }, 0).toFixed(2)
          );
        }
      }
    }
  });
}
</script>
