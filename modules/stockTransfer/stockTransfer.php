<?php
require_once '../../php/db_connect.php';
require_once '../../php/bootstrap.php';

use App\Modules\StockTransfer\StockTransferService;

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
  exit;
}

$stockTransferService = new StockTransferService(
  $db,
  (int)$_SESSION['customer'],
  (int)$_SESSION['userID'],
  (string)($_SESSION['role'] ?? '')
);
$permissions = $stockTransferService->getPermissions();
$lookups = $stockTransferService->getLookups();

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
          <h3 class="results-title"><i class="fas fa-list"></i> <?=$t('stock_transfer_code')?></h3>
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
        <table id="transferTable" class="table data-table">
          <thead>
            <tr>
              <th><?=$t('transfer_no_code')?></th>
              <th><?=$t('from_batch_code')?></th>
              <th><?=$t('to_batch_code')?></th>
              <th><?=$t('created_datetime_code')?></th>
              <th><?=$t('remark_code')?></th>
              <th style="width:80px;"><?=$t('actions_code')?></th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Transfer Modal -->
<div class="modal fade modal-modern" id="transferModal">
  <div class="modal-dialog modal-xl" style="max-width:90%;">
    <div class="modal-content">
      <form role="form" id="transferForm" novalidate>
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-exchange-alt mr-2 text-muted"></i><?=$t('stock_transfer_code')?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <!-- Batches Section -->
          <div class="modal-section">
            <h6 class="section-title"><i class="fas fa-boxes mr-2"></i><?=$t('batch_code')?></h6>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('batch_code')?> A <span class="text-danger">*</span></label>
                  <select class="form-control select2" id="batchA" name="batchA" required>
                    <option value="" selected disabled hidden><?=$t('please_select_code', 'Please Select')?></option>
                    <?php foreach ($lookups['batches'] as $row) { ?>
                      <option value="<?=$row['id']?>"><?=htmlspecialchars($row['batch_no'])?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('batch_code')?> B <span class="text-danger">*</span></label>
                  <select class="form-control select2" id="batchB" name="batchB" required>
                    <option value="" selected disabled hidden><?=$t('please_select_code', 'Please Select')?></option>
                    <?php foreach ($lookups['batches'] as $row) { ?>
                      <option value="<?=$row['id']?>"><?=htmlspecialchars($row['batch_no'])?></option>
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
              <textarea class="form-control" id="transferRemarks" name="remarks" rows="2" placeholder="<?=$t('enter_remark_code')?>"></textarea>
            </div>
          </div>

          <!-- Items Section -->
          <div class="modal-section">
            <h6 class="section-title"><i class="fas fa-exchange-alt mr-2"></i><?=$t('weight_details_code')?></h6>
            <div class="row">
              <?php foreach (['A' => 'primary', 'B' => 'success'] as $side => $colour) { ?>
              <div class="col-md-6">
                <div class="d-flex align-items-center mb-2">
                  <span class="font-weight-bold"><?=$t('batch_code')?> <?=$side?>: <span id="batch<?=$side?>Label">-</span></span>
                  <span class="badge badge-<?=$colour?> ml-2" id="batch<?=$side?>Count">0</span>
                </div>
                <div class="table-responsive transfer-drop" data-side="<?=$side?>">
                  <table class="table table-bordered table-sm mb-0">
                    <thead>
                      <tr>
                        <th><?=$t('product_code')?></th>
                        <th width="15%"><?=$t('grade_code')?></th>
                        <th width="22%"><?=$t('packaging_size_code')?></th>
                        <th width="15%" class="text-right"><?=$t('weight_code')?></th>
                        <th width="8%"></th>
                      </tr>
                    </thead>
                    <tbody id="table<?=$side?>" class="transfer-zone" data-side="<?=$side?>"></tbody>
                  </table>
                </div>
              </div>
              <?php } ?>
            </div>
            <small class="text-muted"><i class="fas fa-info-circle"></i> <?=$t('drag_rows_hint_code')?></small>
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

<!-- Undo Modal -->
<div class="modal fade modal-modern" id="cancelModal">
  <div class="modal-dialog" style="max-width:500px;">
    <div class="modal-content">
      <form role="form" id="cancelForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-undo mr-2 text-danger"></i><?=$t('undo_stock_transfer_code')?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$t('reason_for_undo')?> <span class="text-danger">*</span></label>
            <textarea class="form-control" id="cancelReason" name="cancelReason" rows="3" required></textarea>
          </div>
          <input type="hidden" id="cancelId" name="id">
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$t('close_code')?></button>
          <button type="submit" class="btn btn-modern btn-modern-danger" id="submitCancel"><i class="fas fa-undo mr-1"></i><?=$t('submit_code')?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.transfer-drop { min-height: 120px; }
.transfer-drop.drag-over { outline: 2px dashed #007bff; }
.transfer-zone tr { cursor: grab; }
.transfer-zone tr.dragging { opacity: 0.4; }
.transfer-zone tr.moved { background-color: #fff8e1; }
.transfer-zone tr.zone-empty { cursor: default; }
</style>

<script>
var transferPermissions = <?=json_encode($permissions)?>;
var transferText = <?=json_encode([
  'pleaseSelect' => $t('please_select_code', 'Please Select'),
  'noRecordsFound' => $t('no_records_found_code', 'No Records Found'),
  'noRecordsMessage' => $t('no_records_message_code', 'Try adjusting your search or filter criteria'),
  'noMatchingRecords' => $t('no_matching_records_code', 'No Matching Records'),
  'noMatchingMessage' => $t('no_matching_message_code', 'No results match your current filters.'),
  'fromBatch' => $t('from_batch_code'),
  'toBatch' => $t('to_batch_code'),
  'createdDatetime' => $t('created_datetime_code'),
  'items' => $t('total_records_code'),
  'remark' => $t('remark_code'),
  'weightDetails' => $t('weight_details_code'),
  'product' => $t('product_code'),
  'grade' => $t('grade_code'),
  'packagingSize' => $t('packaging_size_code'),
  'unitPerBox' => $t('unit_per_box_code'),
  'weight' => $t('weight_code'),
  'noItems' => $t('no_records_found_code', 'No records found')
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
</script>
<script src="modules/stockTransfer/js/stockTransfer.js?v=<?=time()?>"></script>
