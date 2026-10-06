<?php
require_once '../../php/db_connect.php';
require_once '../../php/bootstrap.php';

use App\Services\PackagingBatchService;

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
  exit;
}

$packagingBatchService = new PackagingBatchService(
  $db,
  (int)$_SESSION['customer'],
  (int)$_SESSION['userID'],
  (string)($_SESSION['role'] ?? ''),
  (array)($_SESSION['userModuleAccess'] ?? [])
);
$permissions = $packagingBatchService->getPermissions();
$lookups = $packagingBatchService->getLookups();
$allowPhoto = $lookups['allowPhoto'];
$allowPresetLabel = $lookups['allowPresetLabel'];

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
            <label class="filter-label"><?=$t('locations_code')?></label>
            <select class="form-control select2" id="locationFilter">
              <option value="" disabled hidden><?=$t('please_select_code', 'Please Select')?></option>
              <?php foreach ($lookups['locations'] as $index => $row) { ?>
                <option value="<?=$row['id']?>" <?= $index === 0 ? 'selected' : '' ?>><?=htmlspecialchars($row['locations'])?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$t('production_lines_code')?></label>
            <select class="form-control select2" id="productionLineFilter">
              <option value="" selected disabled hidden><?=$t('please_select_code', 'Please Select')?></option>
              <?php foreach ($lookups['productionLines'] as $row) { ?>
                <option value="<?=$row['id']?>"><?=htmlspecialchars($row['production_line'])?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$t('category_code')?></label>
            <select class="form-control select2" id="categoryFilter">
              <option value="all"><?=$t('all_code', 'All')?></option>
              <?php foreach ($lookups['categories'] as $row) { ?>
                <option value="<?=$row['id']?>"><?=htmlspecialchars($row['category_name'])?></option>
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
          <h3 class="results-title"><i class="fas fa-list"></i> <?=$t('batch_packaging_code')?></h3>
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
        <table id="batchTable" class="table data-table">
          <thead>
            <tr>
              <th><?=$t('batch_no_code')?></th>
              <th><?=$t('packaging_date_code')?></th>
              <th><?=$t('locations_code')?></th>
              <th><?=$t('production_lines_code')?></th>
              <th><?=$t('status_code')?></th>
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
  <div class="modal-dialog modal-xl" style="max-width:90%;">
    <div class="modal-content">
      <form role="form" id="extendForm" novalidate>
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-boxes mr-2 text-muted"></i><?=$t('add_new_entry_code')?></h5>
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
                  <label class="form-label-modern"><?=$t('batch_no_code')?> <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="batchNo" readonly>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('packaging_date_code')?> <span class="text-danger">*</span></label>
                  <div class="input-group date" id="packagingDatePicker" data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input" data-target="#packagingDatePicker" id="packagingDate" name="packagingDate" required/>
                    <div class="input-group-append" data-target="#packagingDatePicker" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('locations_code')?> <span class="text-danger">*</span></label>
                  <select class="form-control select2" id="location" name="location" required>
                    <option value="" selected disabled hidden><?=$t('please_select_code', 'Please Select')?></option>
                    <?php foreach ($lookups['locations'] as $row) { ?>
                      <option value="<?=$row['id']?>"><?=htmlspecialchars($row['locations'])?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('production_lines_code')?></label>
                  <select class="form-control select2" id="productionLines" name="productionLines">
                    <option value="" selected disabled hidden><?=$t('please_select_code', 'Please Select')?></option>
                    <?php foreach ($lookups['productionLines'] as $row) { ?>
                      <option value="<?=$row['id']?>"><?=htmlspecialchars($row['production_line'])?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-3">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('type_code', 'Type')?></label>
                  <select class="form-control" id="gradeType" name="gradeType">
                    <option value="Local" selected><?=$t('local_code', 'Local')?></option>
                    <option value="Export"><?=$t('export_code', 'Export')?></option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Remarks Section -->
          <div class="modal-section">
            <h6 class="section-title"><i class="fas fa-comment-alt mr-2"></i><?=$t('remark_code')?></h6>
            <div class="row">
              <div class="col-md-12">
                <div class="form-group-modern">
                  <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="<?=$t('enter_remark_code')?>"></textarea>
                </div>
              </div>
              <?php if ($allowPresetLabel) { ?>
              <div class="col-md-12 mt-2">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('label_code')?> <?=$t('summary_code')?></label>
                  <textarea class="form-control" id="labelSummary" name="labelSummary" readonly placeholder="<?=$t('label_count_message_code', 'Label counts will appear here')?>" rows="3" style="resize:none;"></textarea>
                </div>
              </div>
              <?php } ?>
            </div>
          </div>

          <!-- Weight Details Section -->
          <div class="modal-section">
            <div class="section-header-toggle">
              <h6 class="section-title mb-0"><i class="fas fa-balance-scale mr-2"></i><?=$t('weight_details_code')?></h6>
              <div>
                <button type="button" class="btn btn-modern btn-modern-primary btn-sm" id="addWeightBtn">
                  <i class="fas fa-plus mr-1"></i><?=$t('add_weight_code')?>
                </button>
                <button type="button" class="btn btn-modern btn-modern-secondary btn-sm" id="bulkAddBtn">
                  <i class="fas fa-layer-group mr-1"></i><?=$t('bulk_add_code')?>
                </button>
              </div>
            </div>
            <div class="table-responsive mt-3">
              <table class="table table-bordered table-sm">
                <thead>
                  <tr>
                    <th width="10%"><?=$t('supplier_code')?></th>
                    <th width="10%"><?=$t('category_code')?></th>
                    <th width="10%"><?=$t('product_code')?></th>
                    <th width="8%"><?=$t('grade_code')?></th>
                    <th width="10%"><?=$t('packaging_size_code')?></th>
                    <th width="8%"><?=$t('label_code')?></th>
                    <th width="6%"><?=$t('unit_per_box_code')?></th>
                    <th width="7%"><?=$t('gross_code')?></th>
                    <th width="7%"><?=$t('tare_code')?></th>
                    <th width="7%"><?=$t('weight_code')?></th>
                    <th width="8%"><?=$t('time_code')?></th>
                    <?php if ($allowPhoto) { ?>
                    <th width="6%"><?=$t('photo_code')?></th>
                    <?php } ?>
                    <th width="5%"><?=$t('actions_code')?></th>
                  </tr>
                </thead>
                <tbody id="weightDetailsTable"></tbody>
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

<!-- Bulk Add Modal -->
<div class="modal fade modal-modern" id="bulkAddModal">
  <div class="modal-dialog modal-wide">
    <div class="modal-content">
      <form id="bulkAddForm" novalidate>
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-layer-group mr-2 text-muted"></i><?=$t('bulk_add_code')?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="modal-section">
        <div class="row">
          <div class="col-6">
            <div class="form-group-modern">
              <label class="form-label-modern"><?=$t('bulk_no_code')?> <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="bulkNo" min="1" value="1" required>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group-modern">
              <label class="form-label-modern"><?=$t('time_code')?> <span class="text-danger">*</span></label>
              <input type="time" class="form-control" id="bulkTime" required>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group-modern">
              <label class="form-label-modern"><?=$t('supplier_code')?></label>
              <select class="form-control" id="bulkSupplier"></select>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group-modern">
              <label class="form-label-modern"><?=$t('category_code')?> <span class="text-danger">*</span></label>
              <select class="form-control" id="bulkCategory"></select>
              <div class="invalid-feedback"><?=$t('please_select_category_code')?></div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group-modern">
              <label class="form-label-modern"><?=$t('product_code')?> <span class="text-danger">*</span></label>
              <select class="form-control" id="bulkProduct"></select>
              <div class="invalid-feedback"><?=$t('please_select_product_code')?></div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group-modern">
              <label class="form-label-modern"><?=$t('grade_code')?> <span class="text-danger">*</span></label>
              <select class="form-control" id="bulkGrade"></select>
              <div class="invalid-feedback"><?=$t('please_select_grade_code')?></div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group-modern">
              <label class="form-label-modern"><?=$t('packaging_size_code')?> <span class="text-danger">*</span></label>
              <select class="form-control" id="bulkPackagingSize"></select>
              <div class="invalid-feedback"><?=$t('please_select_packaging_size_code')?></div>
            </div>
          </div>
          <?php if ($allowPresetLabel) { ?>
          <div class="col-6">
            <div class="form-group-modern">
              <label class="form-label-modern"><?=$t('label_code')?></label>
              <select class="form-control" id="bulkLabel"></select>
            </div>
          </div>
          <?php } ?>
          <div class="col-6">
            <div class="form-group-modern">
              <label class="form-label-modern"><?=$t('unit_per_box_code')?> <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="bulkUnitPerBox" step="1" value="0" min="1" required>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group-modern">
              <label class="form-label-modern"><?=$t('weight_code')?> <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="bulkWeight" step="0.01" value="0.00" min="0.01" required>
            </div>
          </div>
        </div>
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$t('close_code')?></button>
        <button type="submit" class="btn btn-modern btn-modern-primary" id="bulkAddSubmit"><i class="fas fa-plus mr-1"></i><?=$t('add_code')?></button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Shipment Modal -->
<div class="modal fade modal-modern" id="shipmentModal">
  <div class="modal-dialog" style="max-width:500px;">
    <div class="modal-content">
      <form role="form" id="shipmentForm" novalidate>
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-shipping-fast mr-2 text-muted"></i><?=$t('shipment_code')?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="shipmentBatchId" name="shipmentBatchId">
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$t('loading_date_code')?> <span class="text-danger">*</span></label>
            <div class="input-group date" id="shipmentLoadingDatePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#shipmentLoadingDatePicker" id="shipmentLoadingDate" name="shipmentLoadingDate" required/>
              <div class="input-group-append" data-target="#shipmentLoadingDatePicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$t('customer_code')?> <span class="text-danger">*</span></label>
            <select class="form-control select2" id="shipmentCustomer" name="shipmentCustomer" required>
              <option value="" selected disabled hidden><?=$t('please_select_code', 'Please Select')?></option>
              <?php foreach ($lookups['customers'] as $row) { ?>
                <option value="<?=$row['id']?>"><?=htmlspecialchars($row['customer_name'])?></option>
              <?php } ?>
            </select>
          </div>
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$t('shipment_types_code')?> <span class="text-danger">*</span></label>
            <select class="form-control select2" id="shipmentType" name="shipmentType" required>
              <option value="" selected disabled hidden><?=$t('please_select_code', 'Please Select')?></option>
              <?php foreach ($lookups['shipmentTypes'] as $row) { ?>
                <option value="<?=$row['id']?>"><?=htmlspecialchars($row['shipment_type'])?></option>
              <?php } ?>
            </select>
          </div>
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$t('remark_code')?></label>
            <textarea class="form-control" id="shipmentRemark" name="shipmentRemark" rows="2" placeholder="<?=$t('enter_remark_code')?>"></textarea>
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$t('close_code')?></button>
          <button type="submit" class="btn btn-modern btn-modern-primary" id="submitShipment"><i class="fas fa-shipping-fast mr-1"></i><?=$t('submit_code')?></button>
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
var batchPermissions = <?=json_encode($permissions)?>;
var batchAllowPhoto = <?=$allowPhoto ? 'true' : 'false'?>;
var batchAllowPresetLabel = <?=$allowPresetLabel ? 'true' : 'false'?>;
var batchLookups = <?=json_encode([
  'categories' => $lookups['categories'],
  'products' => $lookups['products'],
  'grades' => $lookups['grades'],
  'packagings' => $lookups['packagings'],
  'suppliers' => $lookups['suppliers'],
  'labels' => PackagingBatchService::LABELS
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
var batchText = <?=json_encode([
  'pleaseSelect' => $t('please_select_code', 'Please Select'),
  'selectSupplier' => $t('select_supplier_code', 'Select Supplier'),
  'selectCategory' => $t('select_category_code', 'Select Category'),
  'selectProduct' => $t('select_product_code', 'Select Product'),
  'selectGrade' => $t('select_grade_code', 'Select Grade'),
  'selectPackaging' => $t('select_packaging_code', 'Select Packaging'),
  'selectLabel' => $t('select_label_code', 'Select Label'),
  'noRecordsFound' => $t('no_records_found_code', 'No Records Found'),
  'noRecordsMessage' => $t('no_records_message_code', 'Try adjusting your search or filter criteria'),
  'noMatchingRecords' => $t('no_matching_records_code', 'No Matching Records'),
  'noMatchingMessage' => $t('no_matching_message_code', 'No results match your current filters.'),
  'packagingDate' => $t('packaging_date_code'),
  'productionLines' => $t('production_lines_code'),
  'status' => $t('status_code'),
  'orderInformation' => $t('order_information_code', 'Order Information'),
  'batchNo' => $t('batch_no_code'),
  'locations' => $t('locations_code'),
  'remark' => $t('remark_code'),
  'labelSummary' => $t('label_code') . ' ' . $t('summary_code'),
  'weightDetails' => $t('weight_details_code'),
  'allProducts' => $t('all_products_code', 'All Products'),
  'allGrades' => $t('all_grades_code', 'All Grades'),
  'product' => $t('product_code'),
  'grade' => $t('grade_code'),
  'packagingSize' => $t('packaging_size_code'),
  'label' => $t('label_code'),
  'unitPerBox' => $t('unit_per_box_code'),
  'weight' => $t('weight_code'),
  'time' => $t('time_code'),
  'photo' => $t('photo_code'),
  'confirmDelete' => $t('delete_confirm_message_code', 'Are you sure you want to delete this item?')
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
</script>
<script src="modules/packagingBatches/js/packagingBatches.js?v=<?=time()?>"></script>
