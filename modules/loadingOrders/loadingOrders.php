<?php
require_once '../../php/db_connect.php';
require_once '../../php/bootstrap.php';

use App\Modules\LoadingOrder\LoadingOrderService;

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
  exit;
}

$loadingOrderService = new LoadingOrderService(
  $db,
  (int)$_SESSION['customer'],
  (int)$_SESSION['userID'],
  (string)($_SESSION['role'] ?? '')
);
$permissions = $loadingOrderService->getPermissions();
$lookups = $loadingOrderService->getLookups();

// Language
$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];
$t = function ($key, $default = '') use ($languageArray, $language) {
  return $languageArray[$key][$language] ?? $default;
};
?>

<div class="content-header" style="padding-bottom: 0;">
  <div class="container-fluid"></div>
</div>

<!-- Main content -->
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
            <label class="filter-label"><?=$t('status_code')?></label>
            <select class="form-control select2" id="statusFilter">
              <option value="all"><?=$t('all_code', 'All')?></option>
              <option value="pending"><?=$t('pending_code')?></option>
              <option value="completed"><?=$t('complete_code')?></option>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$t('shipment_types_code')?></label>
            <select class="form-control select2" id="shipmentTypeFilter">
              <option value="all"><?=$t('all_code', 'All')?></option>
              <?php foreach ($lookups['shipmentTypes'] as $row) { ?>
                <option value="<?=$row['id']?>"><?=htmlspecialchars($row['shipment_type'])?></option>
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
          <h3 class="results-title"><i class="fas fa-list"></i> <?=$t('loading_orders_code')?></h3>
        </div>
        <div class="results-header-right">
          <?php if ($permissions['allowAdd']) { ?>
          <button type="button" class="btn btn-action btn-action-primary" id="addEntry">
            <i class="fas fa-plus"></i> <?=$t('add_new_code')?>
          </button>
          <?php } ?>
        </div>
      </div>

      <div class="card-body">
        <table id="loadingTable" class="table data-table">
          <thead>
            <tr>
              <th><?=$t('loading_no_code')?></th>
              <th><?=$t('loading_date_code')?></th>
              <th><?=$t('status_code')?></th>
              <th><?=$t('shipment_types_code')?></th>
              <th style="width:100px;"><?=$t('actions_code')?></th>
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
      <form role="form" id="extendForm" novalidate>
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-truck-loading mr-2 text-muted"></i><?=$t('add_new_entry_code')?></h5>
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
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('loading_no_code')?></label>
                  <input type="text" class="form-control" id="loadingNo" readonly>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('loading_date_code')?> <span class="text-danger">*</span></label>
                  <div class="input-group date" id="loadingDatePicker" data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input" data-target="#loadingDatePicker" id="loadingDate" name="loadingDate" required/>
                    <div class="input-group-append" data-target="#loadingDatePicker" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('shipment_types_code')?> <span class="text-danger">*</span></label>
                  <select class="form-control select2" id="shipmentType" name="shipmentType" required>
                    <option value="" selected disabled hidden><?=$t('please_select_code', 'Please Select')?></option>
                    <?php foreach ($lookups['shipmentTypes'] as $row) { ?>
                      <option value="<?=$row['id']?>"><?=htmlspecialchars($row['shipment_type'])?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Remarks Section -->
          <div class="modal-section">
            <h6 class="section-title"><i class="fas fa-comment-alt mr-2"></i><?=$t('remark_code')?></h6>
            <div class="form-group-modern">
              <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="<?=$t('enter_remark_code')?>"></textarea>
            </div>
          </div>

          <!-- Weight Details Section -->
          <div class="modal-section">
            <h6 class="section-title"><i class="fas fa-boxes mr-2"></i><?=$t('weight_details_code')?></h6>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('batch_no_code')?> <span class="text-danger">*</span></label>
                  <select class="form-control" id="batchNo" multiple="multiple" style="width:100%;">
                    <?php foreach ($lookups['batches'] as $row) { ?>
                      <option value="<?=$row['id']?>"><?=htmlspecialchars($row['batch_no'])?></option>
                    <?php } ?>
                  </select>
                  <small class="text-muted"><?=$t('select_batches_hint_code')?></small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('total_selected_batches_code')?></label>
                  <input type="text" class="form-control" id="selectedBatchCount" value="0" readonly>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('total_records_code')?></label>
                  <input type="text" class="form-control" id="totalItemRecords" value="0" readonly>
                </div>
              </div>
            </div>
            <div class="table-responsive mt-3">
              <table class="table table-bordered table-sm">
                <thead>
                  <tr>
                    <th width="12%"><?=$t('batch_no_code')?></th>
                    <th width="14%"><?=$t('product_code')?></th>
                    <th width="8%"><?=$t('grade_code')?></th>
                    <th width="12%"><?=$t('packaging_size_code')?></th>
                    <th width="10%"><?=$t('time_code')?></th>
                    <th width="20%"><?=$t('customer_code')?></th>
                    <th><?=$t('remark_code')?></th>
                    <th width="5%"><?=$t('actions_code')?></th>
                  </tr>
                </thead>
                <tbody id="itemDetailsTable"></tbody>
              </table>
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

<!-- Delete Reason Modal -->
<div class="modal fade modal-modern" id="cancelModal">
  <div class="modal-dialog" style="max-width:500px;">
    <div class="modal-content">
      <form role="form" id="cancelForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-trash-alt mr-2 text-danger"></i><?=$t('delete_reason_code')?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$t('delete_reason_code')?> <span class="text-danger">*</span></label>
            <textarea class="form-control" id="cancelReason" name="cancelReason" rows="3" required placeholder="<?=$t('enter_reason_code', 'Enter reason for deletion...')?>"></textarea>
          </div>
          <input type="hidden" id="cancelId" name="id">
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
var loadingPermissions = <?=json_encode($permissions)?>;
var loadingLookups = <?=json_encode([
  'customers' => $lookups['customers']
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
var loadingText = <?=json_encode([
  'pleaseSelect' => $t('please_select_code', 'Please Select'),
  'selectBatches' => $t('select_batches_code', 'Select Batch(es)'),
  'selectCustomer' => $t('select_customer_code', 'Select Customer'),
  'noRecordsFound' => $t('no_records_found_code', 'No Records Found'),
  'noRecordsMessage' => $t('no_records_message_code', 'Try adjusting your search or filter criteria'),
  'noMatchingRecords' => $t('no_matching_records_code', 'No Matching Records'),
  'noMatchingMessage' => $t('no_matching_message_code', 'No results match your current filters.'),
  'loadingDate' => $t('loading_date_code'),
  'shipmentTypes' => $t('shipment_types_code'),
  'status' => $t('status_code'),
  'items' => $t('total_records_code'),
  'remark' => $t('remark_code'),
  'weightDetails' => $t('weight_details_code'),
  'batchNo' => $t('batch_no_code'),
  'product' => $t('product_code'),
  'grade' => $t('grade_code'),
  'packagingSize' => $t('packaging_size_code'),
  'unitPerBox' => $t('unit_per_box_code'),
  'customer' => $t('customer_code'),
  'time' => $t('time_code'),
  'noItems' => $t('no_records_found_code', 'No records found'),
  'confirmDelete' => $t('delete_confirm_message_code', 'Are you sure you want to delete this item?')
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
</script>
<script src="modules/loadingOrders/js/loadingOrders.js?v=<?=time()?>"></script>
