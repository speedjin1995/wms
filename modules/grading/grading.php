<?php
require_once '../../php/db_connect.php';
require_once '../../php/bootstrap.php';

use App\Services\GradingService;

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
  exit;
}

$gradingService = new GradingService(
  $db,
  (int)$_SESSION['customer'],
  (int)$_SESSION['userID'],
  (string)($_SESSION['role'] ?? ''),
  (array)($_SESSION['userModuleAccess'] ?? [])
);
$permissions = $gradingService->getPermissions();
$lookups = $gradingService->getLookups();
$allowPhoto = $lookups['allowPhoto'] == 'Y';

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
            <label class="filter-label"><?=$t('category_code')?></label>
            <select class="form-control select2" id="categoryFilter">
              <option value=""><?=$t('please_select_code', 'Please Select')?></option>
              <?php foreach ($lookups['categories'] as $row) { ?>
                <option value="<?=$row['id']?>"><?=$row['category_name']?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$t('locations_code')?></label>
            <select class="form-control select2" id="locationFilter">
              <option value=""><?=$t('please_select_code', 'Please Select')?></option>
              <?php foreach ($lookups['locations'] as $index => $row) { ?>
                <option value="<?=$row['id']?>" <?= $index === 0 ? 'selected' : '' ?>><?=$row['locations']?></option>
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
          <h3 class="results-title"><i class="fas fa-balance-scale mr-2"></i><?=$t('grading_code')?></h3>
        </div>
        <div class="results-header-right d-flex" style="gap: 0.5rem;">
          <button type="button" class="btn btn-action btn-action-warning" id="exportPdf">
            <i class="fas fa-file-pdf"></i> <?=$t('export_pdf_code')?>
          </button>
          <button type="button" class="btn btn-action btn-action-success" id="exportExcel">
            <i class="fas fa-file-excel"></i> <?=$t('export_excel_code')?>
          </button>
          <?php if ($permissions['allowAdd']) { ?>
          <button type="button" class="btn btn-action btn-action-primary" id="addEntry">
            <i class="fas fa-plus"></i> <?=$t('add_new_code')?>
          </button>
          <?php } ?>
        </div>
      </div>
      <div class="card-body">
        <table id="gradingTable" class="table data-table">
          <thead>
            <tr>
              <th style="width:40px;"><input type="checkbox" id="selectAllCheckbox"></th>
              <th><?=$t('grading_no_code')?></th>
              <th><?=$t('category_code')?></th>
              <th><?=$t('locations_code')?></th>
              <th><?=$t('start_time_code')?></th>
              <th><?=$t('end_time_code')?></th>
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
      <form role="form" id="extendForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-balance-scale mr-2 text-muted"></i><?=$t('add_new_entry_code')?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <input type="hidden" id="id" name="id">

          <!-- Basic Info Section -->
          <div class="modal-section">
            <h6 class="section-title"><i class="fas fa-info-circle mr-2"></i><?=$t('basic_info_code', 'Basic Information')?></h6>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('grading_no_code')?></label>
                  <input type="text" class="form-control" id="gradingNo" readonly>
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
            </div>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('category_code')?></label>
                  <select class="form-control select2" id="category" name="category">
                    <option value=""><?=$t('please_select_code', 'Please Select')?></option>
                    <?php foreach ($lookups['categories'] as $row) { ?>
                      <option value="<?=$row['id']?>"><?=$row['category_name']?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('locations_code')?> <span class="text-danger">*</span></label>
                  <select class="form-control select2" id="location" name="location" required>
                    <option value=""><?=$t('please_select_code', 'Please Select')?></option>
                    <?php foreach ($lookups['locations'] as $row) { ?>
                      <option value="<?=$row['id']?>"><?=$row['locations']?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('remark_code')?></label>
                  <textarea class="form-control" id="remarks" name="remarks" rows="1" placeholder="<?=$t('enter_remark_code')?>"></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Weight Details Section -->
          <div class="modal-section">
            <div class="section-header-toggle">
              <h6 class="section-title mb-0"><i class="fas fa-weight mr-2"></i><?=$t('weight_details_code')?></h6>
              <button type="button" class="btn btn-modern btn-modern-primary btn-sm" id="addWeightBtn">
                <i class="fas fa-plus"></i> <?=$t('add_weight_code')?>
              </button>
            </div>
            <div class="table-responsive mt-3">
              <table class="table table-bordered table-sm">
                <thead>
                  <tr>
                    <th width="15%"><?=$t('product_code')?></th>
                    <th width="15%"><?=$t('grade_code')?></th>
                    <th width="12%"><?=$t('gross_code')?></th>
                    <th width="12%"><?=$t('tare_code')?></th>
                    <th width="12%"><?=$t('net_code')?></th>
                    <th width="10%"><?=$t('time_code')?></th>
                    <?php if ($allowPhoto) { ?><th width="10%"><?=$t('photo_code')?></th><?php } ?>
                    <th width="8%"><?=$t('actions_code')?></th>
                  </tr>
                </thead>
                <tbody id="weightDetailsTable"></tbody>
                <tfoot>
                  <tr class="table-secondary">
                    <th colspan="2" class="text-right"><?=$t('total_code')?></th>
                    <th id="totalWeightGross">0.00</th>
                    <th id="totalWeightTare">0.00</th>
                    <th id="totalWeightNet">0.00</th>
                    <th></th>
                    <?php if ($allowPhoto) { ?><th></th><?php } ?>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <!-- Reject Details Section -->
          <div class="modal-section">
            <div class="section-header-toggle">
              <h6 class="section-title mb-0" style="color:#dc2626;"><i class="fas fa-times-circle mr-2"></i><?=$t('reject_details_code')?></h6>
              <button type="button" class="btn btn-modern btn-modern-danger btn-sm" id="addRejectWeightBtn">
                <i class="fas fa-plus"></i> <?=$t('add_reject_weight_code')?>
              </button>
            </div>
            <div class="table-responsive mt-3">
              <table class="table table-bordered table-sm">
                <thead>
                  <tr>
                    <th width="15%"><?=$t('product_code')?></th>
                    <th width="15%"><?=$t('grade_code')?></th>
                    <th width="12%"><?=$t('gross_code')?></th>
                    <th width="12%"><?=$t('tare_code')?></th>
                    <th width="12%"><?=$t('net_code')?></th>
                    <th width="10%"><?=$t('time_code')?></th>
                    <?php if ($allowPhoto) { ?><th width="10%"><?=$t('photo_code')?></th><?php } ?>
                    <th width="8%"><?=$t('actions_code')?></th>
                  </tr>
                </thead>
                <tbody id="rejectDetailsTable"></tbody>
                <tfoot>
                  <tr class="table-secondary">
                    <th colspan="2" class="text-right"><?=$t('total_code')?></th>
                    <th id="totalRejectGross">0.00</th>
                    <th id="totalRejectTare">0.00</th>
                    <th id="totalRejectNet">0.00</th>
                    <th></th>
                    <?php if ($allowPhoto) { ?><th></th><?php } ?>
                    <th></th>
                  </tr>
                </tfoot>
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
            <textarea class="form-control" id="cancelReason" name="cancelReason" rows="3" required placeholder="<?=$t('enter_reason_code', 'Enter reason for deletion')?>"></textarea>
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

<!-- Print Options Modal -->
<div class="modal fade modal-modern" id="printOptionsModal" tabindex="-1">
  <div class="modal-dialog" style="max-width:420px;">
    <div class="modal-content">
      <form id="printOptionsForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-print mr-2 text-muted"></i><?=$t('print_options_code')?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="printId" name="id">
          <div class="form-group-modern mb-0">
            <label class="form-label-modern"><?=$t('print_with_photo_code')?></label>
            <select class="form-control" id="printWithPhoto" name="withPhoto">
              <option value="Y"><?=$t('yes_code')?></option>
              <option value="N"><?=$t('no_code')?></option>
            </select>
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$t('cancel_code')?></button>
          <button type="submit" class="btn btn-modern btn-modern-primary" id="submitPrint"><i class="fas fa-print mr-1"></i><?=$t('print_code')?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var gradingPermissions = <?=json_encode($permissions)?>;
var gradingAllowPhoto = <?=$allowPhoto ? 'true' : 'false'?>;
var gradingLookups = <?=json_encode([
  'products' => $lookups['products'],
  'grades' => $lookups['grades']
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
var gradingText = <?=json_encode([
  'pleaseSelect' => $t('please_select_code', 'Please Select'),
  'selectProduct' => $t('select_product_code', 'Select Product'),
  'selectGrade' => $t('select_grade_code', 'Select Grade'),
  'noRecordsFound' => $t('no_records_found_code', 'No Records Found'),
  'noRecordsMessage' => $t('no_records_message_code', 'Try adjusting your search or filter criteria'),
  'noMatchingRecords' => $t('no_matching_records_code', 'No Matching Records'),
  'noMatchingMessage' => $t('no_matching_message_code', 'No results match your current filters.'),
  'gradingInfo' => $t('grading_info_code', 'Grading Information'),
  'gradingNo' => $t('grading_no_code'),
  'category' => $t('category_code'),
  'startTime' => $t('start_time_code'),
  'endTime' => $t('end_time_code'),
  'remark' => $t('remark_code'),
  'weighingDetails' => $t('weighing_details_code'),
  'rejectDetails' => $t('reject_details_code'),
  'allProducts' => $t('all_products_code', 'All Products'),
  'allGrades' => $t('all_grades_code', 'All Grades'),
  'product' => $t('product_code'),
  'grade' => $t('grade_code'),
  'gross' => $t('gross_code'),
  'tare' => $t('tare_code'),
  'net' => $t('net_code'),
  'time' => $t('time_code'),
  'photo' => $t('photo_code'),
  'total' => $t('total_code'),
  'confirmDelete' => $t('delete_confirm_message_code', 'Are you sure you want to delete this item?')
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
</script>
<script src="modules/grading/js/grading.js?v=<?=time()?>"></script>
