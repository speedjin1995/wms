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

// Industrial records live in the wholesales table (records_type = industrial)
$industrialService = new WholesaleService(
  $db,
  (int)$_SESSION['customer'],
  (int)$_SESSION['userID'],
  (string)($_SESSION['role'] ?? ''),
  'industrial',
  (array)($_SESSION['userModuleAccess'] ?? [])
);
$permissions = $industrialService->getPermissions();
$flags = $industrialService->getFeatureFlags();
$lookups = $industrialService->getLookups();
$showPrice = $flags['price'] && $permissions['allowPrice'];

// Language
$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];
$t = function ($key, $default = '') use ($languageArray, $language) {
  return $languageArray[$key][$language] ?? $default;
};
$e = function ($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};
?>

<div class="content-header" style="padding-bottom: 0;">
  <div class="container-fluid"></div>
</div>

<div class="content page-modern">
  <div class="container-fluid">
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
              <option value="OUTGOING"><?=$t('outgoing_code')?></option>
              <option value="INCOMING" selected><?=$t('incoming_code')?></option>
            </select>
          </div>

          <div class="filter-group" id="customerStatusDiv" style="display:none;">
            <label class="filter-label"><?=$t('customer_code')?></label>
            <select class="form-control select2" id="customerNoFilter">
              <option value=""><?=$t('please_select_code', 'Please Select')?></option>
              <?php foreach ($lookups['customers'] as $row) { ?>
                <option value="<?=$row['id']?>"><?=$e($row['customer_name'])?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group" id="supplierStatusDiv">
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

          <div class="filter-group" id="otherVehicleFilterDiv" style="display:none;">
            <label class="filter-label"><?=$t('other_vehicle_no_code')?></label>
            <input type="text" class="form-control" id="otherVehicleNoFilter" placeholder="<?=$t('please_enter_vehicle_no_code')?>">
          </div>
        </div>

        <div class="filter-row mt-3">
          <div class="filter-group">
            <label class="filter-label"><?=$t('checked_by_code')?></label>
            <input type="text" class="form-control" id="checkedByFilter" placeholder="<?=$t('please_enter_name_code')?>">
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$t('weighed_by_code')?></label>
            <select class="form-control select2" id="weightByFilter">
              <option value=""><?=$t('please_select_code', 'Please Select')?></option>
              <?php foreach ($lookups['users'] as $row) { ?>
                <option value="<?=$row['id']?>"><?=$e($row['name'])?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group filter-group-action">
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
          <h3 class="results-title"><i class="fas fa-list"></i> <?=$t('pulp_and_paste_code')?></h3>
        </div>
        <div class="results-header-right d-flex" style="gap: 0.5rem;">
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
              <th><?=$t('serial_no_code')?></th>
              <th><?=$t('do_po_no_code')?></th>
              <th><?=$t('start_time_code')?></th>
              <th><?=$t('end_time_code')?></th>
              <th><?=$t('parent_code')?></th>
              <th><?=$t('customer_supplier_code')?></th>
              <th><?=$t('total_item_code')?></th>
              <th><?=$t('total_gross_code')?></th>
              <th><?=$t('total_tare_code')?></th>
              <th><?=$t('total_nett_code')?></th>
              <th><?=$t('total_variance_code')?></th>
              <th><?=$t('total_variance_code')?> (%)</th>
              <th><?=$t('weighed_by_code')?></th>
              <th><?=$t('indicator_code')?></th>
              <?php if ($flags['secRemark']) { ?>
              <th><?=$t('second_remarks_code')?></th>
              <?php } ?>
              <th style="width:10%"><?=$t('actions_code')?></th>
            </tr>
          </thead>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- Add / Edit Modal -->
<div class="modal fade modal-modern" id="extendModal">
  <div class="modal-dialog modal-xl" style="max-width:90%;">
    <div class="modal-content">
      <form role="form" id="extendForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-industry mr-2 text-muted"></i><?=$t('add_new_entry_code')?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <input type="hidden" id="id" name="id">
          <!-- Not shown on this screen; kept so editing does not clear saved values -->
          <input type="hidden" id="securityBillNo" name="securityBillNo">
          <input type="hidden" id="driver" name="driver">

          <!-- Basic Info -->
          <div class="modal-section">
            <h6 class="section-title"><i class="fas fa-info-circle mr-2"></i><?=$t('basic_info_code', 'Basic Info')?></h6>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('serial_no_code')?></label>
                  <input type="text" class="form-control" id="serialNo" readonly>
                </div>
              </div>
              <div class="col-md-4">
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
              <div class="col-md-4">
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
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('status_code')?> <span class="text-danger">*</span></label>
                  <select class="form-control" id="status" name="status" required>
                    <option value="OUTGOING"><?=$t('outgoing_code')?></option>
                    <option value="INCOMING" selected><?=$t('incoming_code')?></option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('do_po_no_code')?></label>
                  <input type="text" class="form-control" id="doPoNo" name="doPoNo">
                </div>
              </div>
              <div class="col-md-4" id="customerDiv" style="display:none;">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('customer_code')?></label>
                  <select class="form-control select2" id="customer" name="customer">
                    <option value=""><?=$t('please_select_code', 'Please Select')?></option>
                    <option value="OTHERS"><?=$t('others_code')?></option>
                    <?php foreach ($lookups['customers'] as $row) { ?>
                      <option value="<?=$row['id']?>"><?=$e($row['customer_name'])?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4" id="customerOtherDiv" style="display:none;">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('customer_other_code')?></label>
                  <input type="text" class="form-control" id="customerOther" name="customerOther">
                </div>
              </div>
              <div class="col-md-4" id="supplierDiv">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('supplier_code')?></label>
                  <select class="form-control select2" id="supplier" name="supplier">
                    <option value=""><?=$t('please_select_code', 'Please Select')?></option>
                    <option value="OTHERS"><?=$t('others_code')?></option>
                    <?php foreach ($lookups['suppliers'] as $row) { ?>
                      <option value="<?=$row['id']?>"><?=$e($row['supplier_name'])?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4" id="supplierOtherDiv" style="display:none;">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('supplier_other_code')?></label>
                  <input type="text" class="form-control" id="supplierOther" name="supplierOther">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('vehicle_no_code')?></label>
                  <select class="form-control select2" id="vehicle" name="vehicle">
                    <option value=""><?=$t('please_select_code', 'Please Select')?></option>
                    <option value="OTHERS"><?=$t('others_code')?></option>
                    <?php foreach ($lookups['vehicles'] as $row) { ?>
                      <option value="<?=$e($row['veh_number'])?>"><?=$e($row['veh_number'])?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4" id="vehicleNoOtherDiv" style="display:none;">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('other_vehicle_no_code')?></label>
                  <input type="text" class="form-control" id="otherVehicleNo" name="otherVehicleNo" placeholder="<?=$t('please_enter_vehicle_no_code')?>">
                </div>
              </div>
              <div class="col-md-4">
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
              <div class="col-md-12">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('remark_code')?></label>
                  <textarea class="form-control" id="remarks" name="remarks" placeholder="<?=$t('enter_remark_code')?>"></textarea>
                </div>
              </div>
              <?php if ($flags['secRemark']) { ?>
              <div class="col-md-12">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('second_remarks_code')?></label>
                  <textarea class="form-control" id="remarks2" name="remarks2" placeholder="<?=$t('enter_remark_code')?> 2"></textarea>
                </div>
              </div>
              <?php } ?>
            </div>
          </div>

          <!-- Weight Details -->
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
                    <th style="width:7%;"><?=$t('gross_code')?></th>
                    <th style="width:7%;"><?=$t('tare_code')?></th>
                    <th style="width:7%;"><?=$t('net_code')?></th>
                    <th style="width:7%;"><?=$t('variance_code')?></th>
                    <th style="width:7%;"><?=$t('variance_code')?> (%)</th>
                    <?php if ($showPrice) { ?>
                    <th style="width:7%;"><?=$t('price_code')?></th>
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
                    <td colspan="2" class="text-right"><?=$t('total_code')?></td>
                    <td id="totalWeightGross">0.00</td>
                    <td id="totalWeightTare">0.00</td>
                    <td class="text-primary font-weight-bold" id="totalWeightNet">0.00</td>
                    <td id="totalWeightVariance">0.00</td>
                    <td></td>
                    <?php if ($showPrice) { ?>
                    <td></td>
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

          <!-- Reject Details -->
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
                    <th style="width:7%;"><?=$t('gross_code')?></th>
                    <th style="width:7%;"><?=$t('tare_code')?></th>
                    <th style="width:7%;"><?=$t('net_code')?></th>
                    <?php if ($showPrice) { ?>
                    <th style="width:7%;"><?=$t('price_code')?></th>
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
                    <td colspan="2" class="text-right"><?=$t('total_code')?></td>
                    <td id="totalRejectGross">0.00</td>
                    <td id="totalRejectTare">0.00</td>
                    <td class="text-danger font-weight-bold" id="totalRejectNet">0.00</td>
                    <?php if ($showPrice) { ?>
                    <td></td>
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
          <input type="hidden" id="printId" name="id">
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$t('paper_size_code')?></label>
            <select class="form-control" id="paperSize" name="paperSize">
              <option value="A4">A4</option>
              <option value="A5">A5</option>
            </select>
          </div>
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$t('print_with_photo_code')?></label>
            <select class="form-control" id="printWithPhoto" name="withPhoto">
              <option value="Y"><?=$t('yes_code')?></option>
              <option value="N"><?=$t('no_code')?></option>
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
var industrialPermissions = <?=json_encode($permissions)?>;
var industrialFlags = <?=json_encode($flags + ['showPrice' => $showPrice])?>;
var industrialLookups = <?=json_encode([
  'products' => $lookups['products'],
  'userLocationId' => $_SESSION['userLocationId'] ?? null
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
var industrialText = <?=json_encode([
  'pleaseSelect' => $t('please_select_code', 'Please Select'),
  'selectProduct' => $t('select_product_code', 'Select Product'),
  'noRecordsFound' => $t('no_records_found_code', 'No Records Found'),
  'noRecordsMessage' => $t('no_records_message_code', 'Try adjusting your search or filter criteria'),
  'noMatchingRecords' => $t('no_matching_records_code', 'No Matching Records'),
  'noMatchingMessage' => $t('no_matching_message_code', 'No results match your current filters. Try different criteria.'),
  'edit' => $t('edit_code', 'Edit'),
  'print' => $t('print_code', 'Print'),
  'delete' => $t('delete_code', 'Delete'),
  'totalItem' => $t('total_item_code'),
  'totalGross' => $t('total_gross_code'),
  'totalNett' => $t('total_nett_code'),
  'totalVariance' => $t('total_variance_code'),
  'totalPrice' => $t('total_price_code', 'Total Price'),
  'orderInformation' => $t('order_information_code', 'Order Information'),
  'serialNo' => $t('serial_no_code'),
  'doPoNo' => $t('do_po_no_code'),
  'customerSupplier' => $t('customer_supplier_code'),
  'weighedBy' => $t('weighed_by_code'),
  'location' => $t('locations_code'),
  'indicator' => $t('indicator_code'),
  'remark' => $t('remark_code'),
  'weighingDetails' => $t('weighing_details_code', 'Weighing Details'),
  'rejectDetails' => $t('reject_details_code'),
  'allProducts' => $t('all_products_code', 'All Products'),
  'product' => $t('product_code'),
  'gross' => $t('gross_code'),
  'tare' => $t('tare_code'),
  'net' => $t('net_code'),
  'variance' => $t('variance_code'),
  'price' => $t('price_code'),
  'total' => $t('total_code'),
  'time' => $t('time_code'),
  'photo' => $t('photo_code'),
  'noRejectItems' => $t('no_reject_items_code', 'No rejected items'),
  'confirmDelete' => $t('delete_confirm_message_code', 'Are you sure you want to delete this item?'),
  'enterVehicleNo' => $t('please_enter_vehicle_no_code', 'Please enter the vehicle number.'),
  'productRowError' => $t('product_row_error_code', 'Please select a product for all weight detail rows.')
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
</script>
<script src="modules/industrial/js/industrial.js?v=<?=time()?>"></script>
