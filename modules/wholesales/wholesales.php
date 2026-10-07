<?php
require_once '../../php/db_connect.php';
require_once '../../php/bootstrap.php';

use App\Modules\Wholesale\WholesaleService;

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
  exit;
}

$wholesaleService = new WholesaleService(
  $db,
  (int)$_SESSION['customer'],
  (int)$_SESSION['userID'],
  (string)($_SESSION['role'] ?? ''),
  'wholesales',
  (array)($_SESSION['userModuleAccess'] ?? [])
);
$permissions = $wholesaleService->getPermissions();
$flags = $wholesaleService->getFeatureFlags();
$lookups = $wholesaleService->getLookups();
$showPrice = $flags['price'] && $permissions['allowPrice'];
$stockEnabled = in_array('stocks', (array)($_SESSION['products'] ?? []), true);

// Language
$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];
$t = function ($key, $default = '') use ($languageArray, $language) {
  return $languageArray[$key][$language] ?? $default;
};
$e = function ($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

// List columns: key => [data field, label], ordered / hidden by the company column setup
$defaultColumns = [
  'serial_no_code'          => ['serial_no', $t('serial_no_code')],
  'do_po_no_code'           => ['po_no', $t('do_po_no_code')],
  'location_code'           => ['location', $t('locations_code')],
  'sec_bill_no_code'        => ['security_bills', $t('sec_bill_no_code')],
  'start_time_code'         => ['start_time', $t('start_time_code')],
  'end_time_code'           => ['end_time', $t('end_time_code')],
  'parent_code'             => ['parent', $t('parent_code')],
  'customer_supplier_code'  => ['customer_supplier', $t('customer_supplier_code')],
  'vehicle_no_code'         => ['vehicle_no', $t('vehicle_no_code')],
  'driver_code'             => ['driver', $t('driver_code')],
  'total_item_code'         => ['total_item', $t('total_item_code')],
  'total_weight_code'       => ['total_weight', $t('total_weight_code')],
  'total_price_reject_code' => $showPrice ? ['total_price', $t('total_price_code', 'Total Price')] : ['total_reject', $t('total_reject_code')],
  'weighed_by_code'         => ['weighted_by', $t('weighed_by_code')],
  'checked_by_code'         => ['checked_by', $t('checked_by_code')],
  'modified_by_code'        => ['modified_by', $t('modified_by_code', 'Modified By')]
];
if ($flags['secRemark']) {
  $defaultColumns['second_remarks_code'] = ['remarks2', $t('second_remarks_code')];
}
$columns = [];
if (!empty($lookups['columnSetup'])) {
  foreach ($lookups['columnSetup'] as $setup) {
    if (isset($defaultColumns[$setup['key']])) {
      $columns[] = ['data' => $defaultColumns[$setup['key']][0], 'label' => $defaultColumns[$setup['key']][1], 'visible' => ($setup['visible'] ?? true) !== false];
    }
  }
} else {
  foreach ($defaultColumns as $column) {
    $columns[] = ['data' => $column[0], 'label' => $column[1], 'visible' => true];
  }
}
?>

<!-- Main content -->
<div class="content page-modern">
  <div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title"><i class="fas fa-weight"></i> <?=$t('wholesales_code')?></h1>
    </div>

    <!-- Filter Card -->
    <div class="card filter-card">
      <div class="card-body">
        <div class="filter-row">
          <div class="filter-group">
            <label class="filter-label"><?=$t('from_date_code')?></label>
            <div class="input-group date" id="fromDatePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#fromDatePicker" id="fromDate"/>
              <div class="input-group-append" data-target="#fromDatePicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$t('to_date_code')?></label>
            <div class="input-group date" id="toDatePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#toDatePicker" id="toDate"/>
              <div class="input-group-append" data-target="#toDatePicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$t('transaction_status_code')?></label>
            <select class="form-control" id="transactionStatusFilter">
              <option value="DISPATCH" selected><?=$t('dispatch_code')?></option>
              <option value="RECEIVING"><?=$t('receiving_code')?></option>
              <?php if ($stockEnabled) { ?>
              <option value="STOCK-BAL"><?=$t('stock_balance_code')?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group" id="customerStatusDiv">
            <label class="filter-label"><?=$t('customer_code')?></label>
            <select class="form-control select2" id="customerNoFilter">
              <option value=""><?=$t('please_select_code', 'Please Select')?></option>
              <?php foreach ($lookups['customers'] as $row) { ?>
                <option value="<?=$row['id']?>"><?=$e($row['customer_name'])?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group" id="supplierStatusDiv" style="display: none;">
            <label class="filter-label"><?=$t('supplier_code')?></label>
            <select class="form-control select2" id="supplierNoFilter">
              <option value=""><?=$t('please_select_code', 'Please Select')?></option>
              <?php foreach ($lookups['suppliers'] as $row) { ?>
                <option value="<?=$row['id']?>"><?=$e($row['supplier_name'])?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$t('vehicle_no_code')?></label>
            <select class="form-control select2" id="vehicleNoFilter">
              <option value=""><?=$t('please_select_code', 'Please Select')?></option>
              <option value="OTHERS"><?=$t('others_code')?></option>
              <?php foreach ($lookups['vehicles'] as $row) { ?>
                <option value="<?=$e($row['veh_number'])?>"><?=$e($row['veh_number'])?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group" id="otherVehicleFilterDiv" style="display: none;">
            <label class="filter-label"><?=$t('other_vehicle_no_code')?></label>
            <input type="text" class="form-control" id="otherVehicleNoFilter" placeholder="<?=$t('please_enter_vehicle_no_code')?>">
          </div>
        </div>

        <div class="filter-row mt-3">
          <div class="filter-group">
            <label class="filter-label"><?=$t('category_code')?></label>
            <select class="form-control select2" id="categoryFilter">
              <option value=""><?=$t('please_select_code', 'Please Select')?></option>
              <?php foreach ($lookups['categories'] as $row) { ?>
                <option value="<?=$row['id']?>"><?=$e($row['category_name'])?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$t('locations_code')?></label>
            <select class="form-control select2" id="locationFilter">
              <option value="">-</option>
              <?php foreach ($lookups['locations'] as $row) { ?>
                <option value="<?=$row['id']?>"><?=$e($row['locations'])?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$t('type_code', 'Type')?></label>
            <select class="form-control" id="partyTypeFilter">
              <option value="" selected><?=$t('all_code', 'All')?></option>
              <option value="Normal"><?=$t('normal_code', 'Normal')?></option>
              <option value="Packing"><?=$t('packing_code', 'Packing')?></option>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$t('indicator_code')?></label>
            <select class="form-control select2" id="indicatorFilter">
              <option value=""><?=$t('please_select_code', 'Please Select')?></option>
              <option value="web"><?=$t('web_code', 'Web')?></option>
              <?php foreach ($lookups['indicators'] as $row) { ?>
                <option value="<?=$e($row['nickname'])?>"><?=$e($row['nickname'])?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$t('checked_by_code')?></label>
            <input type="text" class="form-control" id="checkedByFilter" placeholder="<?=$t('please_enter_name_code')?>">
          </div>
        </div>

        <div class="filter-row mt-3">
          <div class="filter-group">
            <label class="filter-label"><?=$t('weighed_by_code')?></label>
            <select class="form-control select2" id="weightByFilter">
              <option value=""><?=$t('please_select_code', 'Please Select')?></option>
              <?php foreach ($lookups['users'] as $row) { ?>
                <option value="<?=$row['id']?>"><?=$e($row['name'])?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group filter-group-action" style="margin-left:auto;">
            <label class="filter-label">&nbsp;</label>
            <button type="button" class="btn btn-filter btn-filter-primary" id="filterSearch">
              <i class="fas fa-search"></i> <?=$t('search_code')?>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Results Card -->
    <div class="card results-card show-dt-controls">
      <div class="card-header">
        <div class="results-header-left">
          <h3 class="results-title"><i class="fas fa-list"></i> <?=$t('wholesales_code')?></h3>
        </div>
        <div class="results-header-right d-flex" style="gap: 0.5rem;">
          <div class="dropdown">
            <button class="btn btn-action btn-action-secondary dropdown-toggle" type="button" id="columnToggleBtn" data-toggle="dropdown">
              <i class="fas fa-columns"></i> <?=$t('columns_code', 'Columns')?>
            </button>
            <div class="dropdown-menu dropdown-menu-right p-2" id="columnToggleMenu" style="min-width:200px;max-height:300px;overflow-y:auto;"></div>
          </div>
          <button type="button" class="btn btn-action btn-action-success" id="printSelected">
            <i class="fas fa-print"></i> <?=$t('print_selected_code', 'Print Selected')?>
          </button>
          <?php if ($flags['invoice'] && $permissions['allowPrice']) { ?>
          <button type="button" class="btn btn-action btn-action-warning" id="exportInvoices">
            <i class="fas fa-file-invoice"></i> <?=$t('export_invoice_code', 'Export Invoice')?>
          </button>
          <?php } ?>
          <?php if ($permissions['allowAdd']) { ?>
          <button type="button" class="btn btn-action btn-action-primary" id="addEntry">
            <i class="fas fa-plus"></i> <?=$t('add_new_code')?>
          </button>
          <?php } ?>
        </div>
      </div>

      <div class="card-body">
        <table id="weightTable" class="table data-table">
          <thead>
            <tr>
              <th style="width:40px;"><input type="checkbox" id="selectAllRows"></th>
              <?php foreach ($columns as $column) { ?>
                <th><?=$e($column['label'])?></th>
              <?php } ?>
              <th style="width:120px;"><?=$t('actions_code')?></th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add / Edit Modal -->
<div class="modal fade modal-modern" id="extendModal">
  <div class="modal-dialog modal-xl" style="max-width: 1700px;">
    <div class="modal-content">
      <form role="form" id="extendForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-weight mr-2 text-muted"></i><?=$t('add_new_entry_code')?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <input type="hidden" id="id" name="id">

          <!-- Order Information Section -->
          <div class="modal-section">
            <h6 class="section-title"><i class="fas fa-info-circle mr-2"></i><?=$t('order_information_code', 'Order Information')?></h6>
            <div class="row">
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('serial_no_code')?></label>
                  <input type="text" class="form-control" id="serialNo" readonly>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('status_code')?> <span class="text-danger">*</span></label>
                  <select class="form-control" id="status" name="status" required>
                    <option value="DISPATCH"><?=$t('dispatch_code')?></option>
                    <option value="RECEIVING"><?=$t('receiving_code')?></option>
                    <?php if ($stockEnabled) { ?>
                    <option value="STOCK-BAL"><?=$t('stock_balance_code')?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('start_time_code')?> <span class="text-danger">*</span></label>
                  <div class="input-group date" id="startTimePicker" data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input" data-target="#startTimePicker" id="startTime" name="startTime" required/>
                    <div class="input-group-append" data-target="#startTimePicker" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('end_time_code')?></label>
                  <div class="input-group date" id="endTimePicker" data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input" data-target="#endTimePicker" id="endTime" name="endTime"/>
                    <div class="input-group-append" data-target="#endTimePicker" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('do_po_no_code')?></label>
                  <input type="text" class="form-control" id="doPoNo" name="doPoNo">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('product_type_code', 'Product Type')?></label>
                  <select class="form-control" id="productType" name="productType">
                    <option value="Local"><?=$t('local_code', 'Local')?></option>
                    <option value="Export"><?=$t('export_code', 'Export')?></option>
                  </select>
                </div>
              </div>
              <div class="col-md-3" id="securityBillDiv">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('sec_bill_no_code')?></label>
                  <input type="text" class="form-control" id="securityBillNo" name="securityBillNo">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('category_code')?></label>
                  <select class="form-control select2" id="category" name="category">
                    <option value=""><?=$t('please_select_code', 'Please Select')?></option>
                    <?php foreach ($lookups['categories'] as $row) { ?>
                      <option value="<?=$row['id']?>"><?=$e($row['category_name'])?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('locations_code')?></label>
                  <select class="form-control select2" id="location" name="location">
                    <option value=""><?=$t('please_select_code', 'Please Select')?></option>
                    <?php foreach ($lookups['locations'] as $row) { ?>
                      <option value="<?=$row['id']?>"><?=$e($row['locations'])?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Customer/Supplier & Transport Section -->
          <div class="modal-section">
            <h6 class="section-title"><i class="fas fa-truck mr-2"></i><?=$t('transport_details_code', 'Transport Details')?></h6>
            <div class="row">
              <div class="col-md-3" id="customerDiv">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('customer_code')?></label>
                  <select class="form-control select2" id="customer" name="customer">
                    <option value=""><?=$t('please_select_code', 'Please Select')?></option>
                    <option value="OTHERS"><?=$t('others_code')?></option>
                    <?php foreach ($lookups['customers'] as $row) { ?>
                      <option value="<?=$row['id']?>" data-currency="<?=$e($row['currency'])?>" data-type="<?=$e($row['customer_type'])?>"><?=$e($row['customer_name'])?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-3" id="customerOtherDiv" style="display:none;">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('customer_other_code')?></label>
                  <input type="text" class="form-control" id="customerOther" name="customerOther">
                </div>
              </div>
              <div class="col-md-3" id="supplierDiv" style="display:none;">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('supplier_code')?></label>
                  <select class="form-control select2" id="supplier" name="supplier">
                    <option value=""><?=$t('please_select_code', 'Please Select')?></option>
                    <option value="OTHERS"><?=$t('others_code')?></option>
                    <?php foreach ($lookups['suppliers'] as $row) { ?>
                      <option value="<?=$row['id']?>" data-currency="<?=$e($row['currency'])?>" data-type="<?=$e($row['supplier_type'])?>"><?=$e($row['supplier_name'])?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-3" id="supplierOtherDiv" style="display:none;">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('supplier_other_code')?></label>
                  <input type="text" class="form-control" id="supplierOther" name="supplierOther">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('vehicle_no_code')?> <span class="text-danger">*</span></label>
                  <select class="form-control select2" id="vehicle" name="vehicle" required>
                    <option value=""><?=$t('please_select_code', 'Please Select')?></option>
                    <option value="OTHERS"><?=$t('others_code')?></option>
                    <?php foreach ($lookups['vehicles'] as $row) { ?>
                      <option value="<?=$e($row['veh_number'])?>"><?=$e($row['veh_number'])?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-3" id="vehicleNoOtherDiv" style="display:none;">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('other_vehicle_no_code')?> <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="otherVehicleNo" name="otherVehicleNo">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('driver_code')?> <span class="text-danger">*</span></label>
                  <select class="form-control select2" id="driver" name="driver" required>
                    <option value=""><?=$t('please_select_code', 'Please Select')?></option>
                    <option value="OTHERS"><?=$t('others_code')?></option>
                    <?php foreach ($lookups['drivers'] as $row) { ?>
                      <option value="<?=$e($row['driver_name'])?>"><?=$e($row['driver_name'])?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-3" id="driverOtherDiv" style="display:none;">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('other_driver_code')?> <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="otherDriver" name="otherDriver">
                </div>
              </div>
              <div class="col-md-3" <?=$flags['payment'] ? '' : 'style="display:none;"'?>>
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('payment_method_code')?></label>
                  <select class="form-control select2" id="paymentMethod" name="paymentMethod">
                    <option value=""><?=$t('please_select_code', 'Please Select')?></option>
                    <option value="Cash"><?=$t('cash_code')?></option>
                    <option value="Bank Transfer"><?=$t('bank_transfer_code')?></option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Remarks Section -->
          <div class="modal-section">
            <h6 class="section-title"><i class="fas fa-comment-alt mr-2"></i><?=$t('remark_code')?></h6>
            <div class="row">
              <div class="col-md-<?=$flags['secRemark'] ? '6' : '12'?>">
                <div class="form-group-modern">
                  <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="<?=$t('enter_remark_code')?>"></textarea>
                </div>
              </div>
              <?php if ($flags['secRemark']) { ?>
              <div class="col-md-6">
                <div class="form-group-modern">
                  <textarea class="form-control" id="remarks2" name="remarks2" rows="2" placeholder="<?=$t('second_remarks_code')?>"></textarea>
                </div>
              </div>
              <?php } ?>
            </div>
          </div>

          <!-- Basket Tare Calculation Section -->
          <div class="modal-section" <?=$flags['basketTare'] ? '' : 'style="display:none;"'?>>
            <h6 class="section-title"><i class="fas fa-shopping-basket mr-2"></i><?=$t('basket_tare_calculation_code', 'Basket Tare Calculation')?></h6>
            <div class="row align-items-end">
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('empty_baskets_weight_code', 'Empty Baskets Weight')?> (kg)</label>
                  <input type="number" class="form-control" id="emptyBasketWeight" name="emptyBasketWeight" step="0.01" min="0" value="0.00" placeholder="0.00">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('basket_count_code', 'Basket Count')?></label>
                  <input type="number" class="form-control" id="basketCount" name="basketCount" step="1" min="0" value="0" placeholder="0">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('average_weight_code', 'Average Weight')?> (kg)</label>
                  <input type="number" class="form-control" id="avgBasketWeight" name="avgBasketWeight" step="0.01" value="0.00" readonly style="background-color:#e9ecef;">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern">&nbsp;</label>
                  <button type="button" class="btn btn-modern btn-modern-primary btn-block" id="applyTareBtn">
                    <i class="fas fa-sync-alt mr-1"></i><?=$t('apply_to_tare_code', 'Apply to Tare')?>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Weight Details Section -->
          <div class="modal-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="section-title mb-0"><i class="fas fa-balance-scale mr-2"></i><?=$t('weight_details_code')?></h6>
              <div class="d-flex align-items-center" style="gap:0.5rem;">
                <?php if ($showPrice) { ?>
                <div class="d-flex align-items-center">
                  <label class="form-label-modern mb-0 mr-2" style="font-size:0.75rem;"><?=$t('unit_price_code')?></label>
                  <input type="number" class="form-control form-control-sm" id="bulkUnitPrice" step="0.01" placeholder="0.00" style="width:100px;">
                </div>
                <?php } ?>
                <button type="button" class="btn btn-modern btn-modern-primary btn-sm" id="addWeightBtn">
                  <i class="fas fa-plus mr-1"></i><?=$t('add_weight_code')?>
                </button>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-hover mb-0" style="font-size:0.72rem;">
                <thead class="thead-light">
                  <tr class="text-center">
                    <th style="width:3%;"><input type="checkbox" id="selectAllWeightCheckbox"></th>
                    <th style="width:11%;"><?=$t('product_code')?></th>
                    <th style="width:8%;"><?=$t('grade_code')?></th>
                    <th style="width:7%;"><?=$t('gross_code')?></th>
                    <th style="width:7%;"><?=$t('tare_code')?></th>
                    <th style="width:7%;"><?=$t('net_code')?></th>
                    <?php if ($flags['pcsBasket']) { ?>
                    <th style="width:6%;"><?=$t('pcs_basket_code', 'Pcs/Basket')?></th>
                    <?php } ?>
                    <?php if ($showPrice) { ?>
                    <th style="width:6%;"><?=$t('currency_code')?></th>
                    <th style="width:7%;"><?=$t('price_code')?></th>
                    <th style="width:7%;"><?=$t('before_disc_code', 'Before Disc')?></th>
                    <th style="width:11%;"><?=$t('discount_code', 'Discount')?></th>
                    <th style="width:7%;"><?=$t('total_code')?></th>
                    <?php } ?>
                    <th style="width:5%;"><?=$t('time_code')?></th>
                    <?php if ($flags['photo']) { ?>
                    <th style="width:3%;"><?=$t('photo_code')?></th>
                    <?php } ?>
                    <th style="width:5%;"><?=$t('actions_code')?></th>
                  </tr>
                </thead>
                <tbody id="weightDetailsTable"></tbody>
                <tfoot class="bg-light font-weight-bold">
                  <tr class="text-center">
                    <td colspan="3" class="text-right"><?=$t('total_code')?></td>
                    <td id="totalWeightGross">0.00</td>
                    <td id="totalWeightTare">0.00</td>
                    <td class="text-primary font-weight-bold" id="totalWeightNet">0.00</td>
                    <?php if ($flags['pcsBasket']) { ?>
                    <td id="totalWeightBasket">0</td>
                    <?php } ?>
                    <?php if ($showPrice) { ?>
                    <td></td>
                    <td></td>
                    <td class="font-weight-bold" id="totalWeightBeforeDiscount">0.00</td>
                    <td class="text-danger font-weight-bold" id="totalWeightDiscount">0.00</td>
                    <td class="text-success font-weight-bold" id="totalWeightPrice">0.00</td>
                    <?php } ?>
                    <td></td>
                    <?php if ($flags['photo']) { ?><td></td><?php } ?>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <!-- Reject Details Section -->
          <div class="modal-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="section-title mb-0 text-danger"><i class="fas fa-times-circle mr-2"></i><?=$t('reject_details_code')?></h6>
              <button type="button" class="btn btn-modern btn-modern-danger btn-sm" id="addRejectWeightBtn">
                <i class="fas fa-plus mr-1"></i><?=$t('add_reject_weight_code')?>
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-hover mb-0" style="font-size:0.72rem;">
                <thead class="thead-light">
                  <tr class="text-center">
                    <th style="width:3%;">#</th>
                    <th style="width:11%;"><?=$t('product_code')?></th>
                    <th style="width:8%;"><?=$t('grade_code')?></th>
                    <th style="width:7%;"><?=$t('gross_code')?></th>
                    <th style="width:7%;"><?=$t('tare_code')?></th>
                    <th style="width:7%;"><?=$t('net_code')?></th>
                    <?php if ($showPrice) { ?>
                    <th style="width:6%;"><?=$t('currency_code')?></th>
                    <th style="width:7%;"><?=$t('price_code')?></th>
                    <th style="width:7%;"><?=$t('before_disc_code', 'Before Disc')?></th>
                    <th style="width:11%;"><?=$t('discount_code', 'Discount')?></th>
                    <th style="width:7%;"><?=$t('total_code')?></th>
                    <?php } ?>
                    <th style="width:5%;"><?=$t('time_code')?></th>
                    <?php if ($flags['photo']) { ?>
                    <th style="width:3%;"><?=$t('photo_code')?></th>
                    <?php } ?>
                    <th style="width:5%;"><?=$t('actions_code')?></th>
                  </tr>
                </thead>
                <tbody id="rejectDetailsTable"></tbody>
                <tfoot class="bg-light font-weight-bold">
                  <tr class="text-center">
                    <td colspan="3" class="text-right"><?=$t('total_code')?></td>
                    <td id="totalRejectGross">0.00</td>
                    <td id="totalRejectTare">0.00</td>
                    <td class="text-danger font-weight-bold" id="totalRejectNet">0.00</td>
                    <?php if ($showPrice) { ?>
                    <td></td>
                    <td></td>
                    <td class="font-weight-bold" id="totalRejectBeforeDiscount">0.00</td>
                    <td class="text-danger font-weight-bold" id="totalRejectDiscount">0.00</td>
                    <td class="text-danger font-weight-bold" id="totalRejectPrice">0.00</td>
                    <?php } ?>
                    <td></td>
                    <?php if ($flags['photo']) { ?><td></td><?php } ?>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$t('close_code')?></button>
          <button type="submit" class="btn btn-modern btn-modern-primary" id="saveButton"><i class="fas fa-save mr-1"></i><?=$t('save_code')?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Cancel (Delete) Modal -->
<div class="modal fade modal-modern" id="cancelModal">
  <div class="modal-dialog" style="max-width:500px;">
    <div class="modal-content">
      <form role="form" id="cancelForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-trash-alt mr-2 text-danger"></i><?=$t('delete_reason_code')?></h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$t('delete_reason_code')?> <span class="text-danger">*</span></label>
            <textarea class="form-control" id="cancelReason" name="cancelReason" rows="3" required placeholder="<?=$t('enter_reason_code', 'Enter reason for deletion...')?>"></textarea>
          </div>
          <input type="hidden" id="cancelId" name="id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$t('close_code')?></button>
          <button type="submit" class="btn btn-modern btn-modern-danger" id="submitCancel"><i class="fas fa-trash mr-1"></i><?=$t('delete_code', 'Delete')?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Print Options Modal -->
<div class="modal fade modal-modern" id="printOptionsModal" tabindex="-1">
  <div class="modal-dialog" style="max-width:400px;">
    <div class="modal-content">
      <form id="printOptionsForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-print mr-2 text-muted"></i><?=$t('print_options_code')?></h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$t('paper_size_code')?></label>
            <select class="form-control" id="paperSize" name="paperSize">
              <option value="A4">A4</option>
              <option value="A5">A5</option>
            </select>
          </div>
          <div class="form-group-modern" id="a4TemplateDiv">
            <label class="form-label-modern">A4 <?=$t('template_code')?></label>
            <select class="form-control" id="a4Template" name="a4Template">
              <option value="A4"><?=$t('default_code')?></option>
              <option value="A4Classic"><?=$t('classic_code')?></option>
              <option value="A4Price"><?=$t('price_code')?></option>
              <option value="A4PriceDetail"><?=$t('price_detail_code')?></option>
            </select>
          </div>
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$t('print_with_photo_code')?></label>
            <select class="form-control" id="printWithPhoto" name="withPhoto">
              <option value="Y"><?=$t('yes_code')?></option>
              <option value="N"><?=$t('no_code')?></option>
            </select>
          </div>
          <div class="form-group-modern" id="withDetailsDiv" style="display:none;">
            <label class="form-label-modern"><?=$t('with_details_code')?></label>
            <select class="form-control" id="printWithDetails" name="withDetails">
              <option value="N"><?=$t('no_code')?></option>
              <option value="Y"><?=$t('yes_code')?></option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$t('cancel_code')?></button>
          <button type="submit" class="btn btn-modern btn-modern-primary"><i class="fas fa-print mr-1"></i><?=$t('print_code')?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var wholesalesPermissions = <?=json_encode($permissions)?>;
var wholesalesFlags = <?=json_encode($flags + ['showPrice' => $showPrice])?>;
var wholesalesLookups = <?=json_encode([
  'columns' => $columns,
  'currencies' => $lookups['currencies'],
  'defaultCurrencyId' => $lookups['defaultCurrencyId'],
  'drivers' => array_column($lookups['drivers'], 'driver_name'),
  'userLocationId' => $_SESSION['userLocationId'] ?? null
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
var wholesalesText = <?=json_encode([
  'pleaseSelect' => $t('please_select_code', 'Please Select'),
  'selectProduct' => $t('select_product_code', 'Select Product'),
  'selectGrade' => $t('select_grade_code', 'Select Grade'),
  'selectCurrency' => $t('select_currency_code', 'Select Currency'),
  'noRecordsFound' => $t('no_records_found_code', 'No Records Found'),
  'noRecordsMessage' => $t('no_records_message_code', 'Try adjusting your search or filter criteria'),
  'noMatchingRecords' => $t('no_matching_records_code', 'No Matching Records'),
  'noMatchingMessage' => $t('no_matching_message_code', 'No results match your current filters. Try different criteria.'),
  'edit' => $t('edit_code', 'Edit'),
  'print' => $t('print_code', 'Print'),
  'exportExcel' => $t('export_excel_code', 'Export Excel'),
  'invoice' => $t('invoice_code', 'Invoice'),
  'delete' => $t('delete_code', 'Delete'),
  'totalItem' => $t('total_item_code'),
  'totalWeight' => $t('total_weight_code'),
  'totalReject' => $t('total_reject_code'),
  'totalPrice' => $t('total_price_code', 'Total Price'),
  'orderInformation' => $t('wholesale_order_information_code', 'Order Information'),
  'serialNo' => $t('serial_no_code'),
  'doPoNo' => $t('do_po_no_code'),
  'vehicleNo' => $t('vehicle_no_code'),
  'driver' => $t('driver_code'),
  'weighedBy' => $t('weighed_by_code'),
  'location' => $t('locations_code'),
  'remark' => $t('remark_code'),
  'basketTare' => $t('basket_tare_calculation_code', 'Basket Tare Calculation'),
  'emptyBasketsWeight' => $t('empty_baskets_weight_code', 'Empty Baskets Weight'),
  'basketCount' => $t('basket_count_code', 'Basket Count'),
  'averageWeight' => $t('average_weight_code', 'Average Weight'),
  'weighingDetails' => $t('weighing_details_code', 'Weighing Details'),
  'rejectDetails' => $t('reject_details_code'),
  'allProducts' => $t('all_products_code', 'All Products'),
  'allGrades' => $t('all_grades_code', 'All Grades'),
  'product' => $t('product_code'),
  'grade' => $t('grade_code'),
  'gross' => $t('gross_code'),
  'tare' => $t('tare_code'),
  'net' => $t('net_code'),
  'pcsBasket' => $t('pcs_basket_code', 'Pcs/Basket'),
  'currency' => $t('currency_code'),
  'price' => $t('price_code'),
  'beforeDisc' => $t('before_disc_code', 'Before Disc'),
  'discount' => $t('discount_code', 'Discount'),
  'total' => $t('total_code'),
  'time' => $t('time_code'),
  'photo' => $t('photo_code'),
  'noRejectItems' => $t('no_reject_items_code', 'No rejected items'),
  'confirmDelete' => $t('delete_confirm_message_code', 'Are you sure you want to delete this item?'),
  'yes' => $t('yes_code', 'Yes'),
  'cancel' => $t('cancel_code', 'Cancel'),
  'enterVehicleNo' => $t('please_enter_vehicle_no_code', 'Please enter the vehicle number.'),
  'enterDriver' => $t('please_enter_driver_code', 'Please enter the driver name.'),
  'weightRowError' => $t('weight_row_error_code', 'Please fill in Product, Grade and Gross for all weight detail rows.'),
  'negativeNetError' => $t('negative_net_error_code', 'Nett weight cannot be negative. Please check your gross and tare values.'),
  'negativeNet' => $t('negative_net_code', 'Nett Weight cannot be negative value'),
  'negativeGross' => $t('negative_gross_code', 'Gross Weight cannot be negative or invalid value'),
  'calculateAverageFirst' => $t('please_calculate_average_first_code', 'Please calculate average weight first'),
  'applyTareConfirm' => $t('apply_tare_confirm_code', 'This will replace all tare values in the weight details table. Are you sure?'),
  'tareApplied' => $t('tare_applied_success_code', 'Average weight applied to all tare fields'),
  'selectRecord' => $t('please_select_record_code', 'Please select at least one record to print.'),
  'maxPrintRecords' => $t('max_print_records_code', 'Maximum 10 records can be printed at once. Please select fewer records.'),
  'noRecordsToPrint' => $t('no_records_to_print_code', 'No records to print'),
  'selectInvoice' => $t('please_select_invoice_code', 'Please select at least one invoice.')
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
</script>
<script src="modules/wholesales/js/wholesales.js?v=<?=time()?>"></script>
