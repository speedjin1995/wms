<?php
require_once '../../php/db_connect.php';
require_once '../../php/bootstrap.php';

use App\Services\WeighbridgeService;

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
  exit;
}

$company = $_SESSION['customer'];
$module = $_SESSION['module'];
$enableDailySales = $_SESSION['enableDailySales'] ?? 'N';
$dailySalesModules = $_SESSION['dailySalesModules'] ?? [];

$wbService = new WeighbridgeService($db, (int)$company, (int)$_SESSION['userID'], (string)($_SESSION['role'] ?? ''));
$permissions = $wbService->getPermissions();

// Daily sales setup states limit the product list
$filterStates = [];
if ($enableDailySales == 'Y' && in_array($module, $dailySalesModules)){
  $stateStmt = $db->prepare("SELECT state FROM daily_sales_setup WHERE module = 'weighing' AND company = ? AND deleted = 0");
  $stateStmt->bind_param('i', $company);
  $stateStmt->execute();
  $stateResult = $stateStmt->get_result();
  while ($stateRow = $stateResult->fetch_assoc()) {
    $decoded = json_decode($stateRow['state'], true);
    if (is_array($decoded)) {
      $filterStates = array_merge($filterStates, $decoded);
    }
  }
  $stateStmt->close();
}

$lookups = $wbService->getLookups((string)$module, $filterStates, true);

// Language
$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];
$t = function ($key, $default = '') use ($languageArray, $language) {
  return $languageArray[$key][$language] ?? $default;
};
?>

<!-- Main content -->
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
              <option value="">-</option>
              <option value="Dispatch"><?=$t('dispatch_code')?></option>
              <option value="Receiving"><?=$t('receiving_code')?></option>
            </select>
          </div>

          <div class="filter-group" id="customerFilterDiv" style="display: none;">
            <label class="filter-label"><?=$t('customer_code')?></label>
            <select class="form-control select2" id="customerNoFilter">
              <option value=""><?=$t('please_select_code', 'Please Select')?></option>
              <?php foreach ($lookups['customers'] as $row) { ?>
                <option value="<?=$row['customer_name']?>"><?=$row['customer_name']?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group" id="supplierFilterDiv">
            <label class="filter-label"><?=$t('supplier_code')?></label>
            <select class="form-control select2" id="supplierNoFilter">
              <option value=""><?=$t('please_select_code', 'Please Select')?></option>
              <?php foreach ($lookups['suppliers'] as $row) { ?>
                <option value="<?=$row['supplier_name']?>"><?=$row['supplier_name']?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$t('vehicle_no_code')?></label>
            <select class="form-control select2" id="vehicleNoFilter">
              <option value=""><?=$t('please_select_code', 'Please Select')?></option>
              <?php foreach ($lookups['vehicles'] as $row) { ?>
                <option value="<?=$row['veh_number']?>"><?=$row['veh_number']?></option>
              <?php } ?>
            </select>
          </div>
        </div>

        <div class="filter-row mt-3">
          <div class="filter-group">
            <label class="filter-label"><?=$t('status_code')?></label>
            <select class="form-control select2" id="statusFilter">
              <option value="Pending" selected><?=$t('pending_code')?></option>
              <option value="Complete"><?=$t('complete_code')?></option>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$t('product_code')?></label>
            <select class="form-control select2" id="productFilter">
              <option value="">-</option>
              <?php foreach ($lookups['products'] as $row) { ?>
                <option value="<?=$row['product_name']?>"><?=$row['product_name']?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$t('transaction_id_code')?></label>
            <input type="text" id="transactionIDFilter" class="form-control" placeholder="<?=$t('transaction_id_code')?>">
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
          <h3 class="results-title"><i class="fas fa-truck-loading mr-2"></i><?=$t('weighbridge_code')?></h3>
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
              <th style="width:40px;"><input type="checkbox" id="selectAllCheckbox"></th>
              <th><?=$t('transaction_id_code')?></th>
              <th><?=$t('transaction_date_code')?></th>
              <th><?=$t('transaction_status_code')?></th>
              <th><?=$t('po_no_code')?></th>
              <th><?=$t('vehicle_no_code')?></th>
              <th><?=$t('customer_supplier_code')?></th>
              <th><?=$t('product_code')?></th>
              <th class="text-right"><?=$t('incoming_weight_code')?></th>
              <th><?=$t('incoming_date_code')?></th>
              <th class="text-right"><?=$t('outgoing_weight_code')?></th>
              <th><?=$t('outgoing_date_code')?></th>
              <th class="text-right"><?=$t('total_nett_weight_code')?></th>
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
  <div class="modal-dialog modal-xl" style="max-width: 1200px;">
    <div class="modal-content">
      <form role="form" id="extendForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-truck-loading mr-2 text-muted"></i><?=$t('add_new_entry_code')?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <input type="hidden" id="id" name="id">
          <input type="hidden" id="customerCode" name="customerCode">
          <input type="hidden" id="supplierCode" name="supplierCode">
          <input type="hidden" id="productCode" name="productCode">

          <!-- Transaction Info Section -->
          <div class="modal-section">
            <h6 class="section-title"><i class="fas fa-file-alt mr-2"></i><?=$t('transaction_info_code', 'Transaction Info')?></h6>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('transaction_id_code')?></label>
                  <input type="text" class="form-control" id="transactionId" readonly>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('transaction_date_code')?> <span class="text-danger">*</span></label>
                  <div class="input-group date" id="transactionDateTimePicker" data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input" data-target="#transactionDateTimePicker" id="transactionDate" name="transactionDate" required/>
                    <div class="input-group-append" data-target="#transactionDateTimePicker" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('transaction_status_code')?></label>
                  <select class="form-control" id="transactionStatus" name="transactionStatus">
                    <option value="Dispatch" selected><?=$t('dispatch_code')?></option>
                    <option value="Receiving"><?=$t('receiving_code')?></option>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-4" id="purchaseOrderDiv">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('po_no_code')?></label>
                  <input type="text" class="form-control" id="poNo" name="poNo">
                </div>
              </div>
              <div class="col-md-4" id="deliveryOrderDiv">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('do_no_code')?></label>
                  <input type="text" class="form-control" id="doNo" name="doNo">
                </div>
              </div>
              <div class="col-md-4" id="customerFieldDiv">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('customer_code')?></label>
                  <select class="form-control select2" id="customer" name="customer">
                    <option value=""><?=$t('please_select_code', 'Please Select')?></option>
                    <?php foreach ($lookups['customers'] as $row) { ?>
                      <option value="<?=$row['customer_name']?>" data-code="<?=$row['customer_code']?>"><?=$row['customer_name']?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4" id="supplierFieldDiv">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('supplier_code')?></label>
                  <select class="form-control select2" id="supplier" name="supplier">
                    <option value=""><?=$t('please_select_code', 'Please Select')?></option>
                    <?php foreach ($lookups['suppliers'] as $row) { ?>
                      <option value="<?=$row['supplier_name']?>" data-code="<?=$row['supplier_code']?>"><?=$row['supplier_name']?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('product_code')?></label>
                  <select class="form-control select2" id="product" name="product">
                    <option value=""><?=$t('please_select_code', 'Please Select')?></option>
                    <?php foreach ($lookups['products'] as $row) { ?>
                      <option value="<?=$row['product_name']?>" data-code="<?=$row['product_code']?>"><?=$row['product_name']?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('vehicle_no_code')?></label>
                  <select class="form-control select2" id="vehicle" name="vehicle">
                    <option value=""><?=$t('please_select_code', 'Please Select')?></option>
                    <?php foreach ($lookups['vehicles'] as $row) { ?>
                      <option value="<?=$row['veh_number']?>"><?=$row['veh_number']?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Weighing Details Section -->
          <div class="modal-section">
            <h6 class="section-title"><i class="fas fa-weight mr-2"></i><?=$t('weighing_details_code')?></h6>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('incoming_weight_code')?> <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <input type="number" class="form-control" id="grossIncoming" name="grossIncoming" placeholder="0" min="0" step="any" required>
                    <div class="input-group-append">
                      <span class="input-group-text">KG</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('outgoing_weight_code')?></label>
                  <div class="input-group">
                    <input type="number" class="form-control" id="tareOutgoing" name="tareOutgoing" placeholder="0" min="0" step="any">
                    <div class="input-group-append">
                      <span class="input-group-text">KG</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('nett_weight_code')?></label>
                  <div class="input-group">
                    <input type="number" class="form-control" id="nettWeight" placeholder="0" readonly>
                    <div class="input-group-append">
                      <span class="input-group-text">KG</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('incoming_date_code')?></label>
                  <div class="input-group date" id="grossIncomingDatePicker" data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input" data-target="#grossIncomingDatePicker" id="grossIncomingDate" name="grossIncomingDate">
                    <div class="input-group-append" data-target="#grossIncomingDatePicker" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('outgoing_date_code')?></label>
                  <div class="input-group date" id="tareOutgoingDatePicker" data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input" data-target="#tareOutgoingDatePicker" id="tareOutgoingDate" name="tareOutgoingDate">
                    <div class="input-group-append" data-target="#tareOutgoingDatePicker" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer justify-content-between">
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
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="cancelId" name="id">
          <div class="form-group-modern mb-0">
            <label class="form-label-modern"><?=$t('delete_reason_code')?> <span class="text-danger">*</span></label>
            <textarea class="form-control" id="cancelReason" name="cancelReason" rows="3" required placeholder="<?=$t('enter_reason_code', 'Enter reason for deletion...')?>"></textarea>
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$t('close_code')?></button>
          <button type="submit" class="btn btn-modern btn-modern-danger" id="submitCancel"><i class="fas fa-trash mr-1"></i><?=$t('submit_code')?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var wbPermissions = <?=json_encode($permissions)?>;
var wbText = <?=json_encode([
  'pleaseSelect' => $t('please_select_code', 'Please Select'),
  'noRecordsFound' => $t('no_records_found_code', 'No Records Found'),
  'noRecordsMessage' => $t('no_records_message_code', 'Try adjusting your search or filter criteria'),
  'noMatchingRecords' => $t('no_matching_records_code', 'No Matching Records'),
  'noMatchingMessage' => $t('no_matching_message_code', 'No results match your current filters. Try different criteria.'),
  'incomingWeight' => $t('incoming_weight_code'),
  'outgoingWeight' => $t('outgoing_weight_code'),
  'nettWeight' => $t('nett_weight_code', 'Nett Weight'),
  'transactionInfo' => $t('transaction_info_code', 'Transaction Information'),
  'transactionId' => $t('transaction_id_code'),
  'transactionDate' => $t('transaction_date_code'),
  'transactionStatus' => $t('transaction_status_code'),
  'doNo' => $t('do_no_code'),
  'poNo' => $t('po_no_code'),
  'vehicleNo' => $t('vehicle_no_code'),
  'customer' => $t('customer_code'),
  'supplier' => $t('supplier_code'),
  'product' => $t('product_code'),
  'weighingDetails' => $t('weighing_details_code'),
  'type' => $t('type_code', 'Type'),
  'weight' => $t('weight_code', 'Weight'),
  'dateTime' => $t('date_time_code', 'Date/Time'),
  'weighedBy' => $t('weighed_by_code', 'Weighed By'),
  'incoming' => $t('incoming_code', 'Incoming'),
  'outgoing' => $t('outgoing_code', 'Outgoing'),
  'confirmDelete' => 'Are you sure you want to delete this item?'
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
</script>
<script src="modules/wb/js/wbCommon.js?v=<?=time()?>"></script>
<script src="modules/wb/js/weighbridges.js?v=<?=time()?>"></script>
