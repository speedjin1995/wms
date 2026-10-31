<?php
session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
  exit;
}

if (($_SESSION['featureFlags']['include_delivery_order'] ?? 'N') != 'Y') {
  exit;
}

$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];
$doAllowPrice = (($_SESSION['featureFlags']['include_price'] ?? 'N') == 'Y' && ($_SESSION['userAllowPrice'] ?? 'N') == 'Y') ? 'Y' : 'N';
$userAllowAdd = $_SESSION['userAllowAdd'] ?? 'N';
$userAllowEdit = $_SESSION['userAllowEdit'] ?? 'N';
$userAllowDelete = $_SESSION['userAllowDelete'] ?? 'N';
$t = function ($key, $default = '') use ($languageArray, $language) { return $languageArray[$key][$language] ?? $default; };
?>

<div class="content page-modern">
  <div class="container-fluid">

    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title"><i class="fas fa-truck-loading"></i> <?=$t('delivery_order_code', 'Delivery Order')?></h1>
    </div>

    <!-- Filter Card -->
    <div class="card filter-card">
      <div class="card-body">
        <div class="filter-row">
          <div class="filter-group">
            <label class="filter-label"><?=$t('date_from_code', 'Date From')?></label>
            <div class="input-group date" id="doDateFromPicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#doDateFromPicker" id="doDateFrom"/>
              <div class="input-group-append" data-target="#doDateFromPicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>
          <div class="filter-group">
            <label class="filter-label"><?=$t('date_to_code', 'Date To')?></label>
            <div class="input-group date" id="doDateToPicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#doDateToPicker" id="doDateTo"/>
              <div class="input-group-append" data-target="#doDateToPicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>
          <div class="filter-group filter-group-action ml-auto">
            <label class="filter-label">&nbsp;</label>
            <div class="d-flex">
              <button type="button" class="btn btn-filter btn-filter-primary" id="loadDoListBtn">
                <i class="fas fa-search"></i> <?=$t('search_code', 'Search')?>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Results Card -->
    <div class="card results-card show-dt-controls">
      <div class="card-header">
        <div class="results-header-left">
          <h3 class="results-title"><i class="fas fa-list"></i> <?=$t('delivery_order_code', 'Delivery Order')?></h3>
        </div>
        <div class="results-header-right d-flex">
          <button type="button" class="btn btn-action btn-action-warning mr-2" id="doTemplateBtn"><i class="fas fa-download mr-1"></i><?=$t('download_template_code', 'Download Template')?></button>
          <?php if($userAllowAdd == 'Y'){ ?>
          <button type="button" class="btn btn-action btn-action-success mr-2" id="doUploadBtn"><i class="fas fa-file-excel mr-1"></i><?=$t('upload_excel_code', 'Upload Excel')?></button>
          <input type="file" id="doExcelFile" accept=".xlsx,.xls" class="d-none">
          <input type="file" id="doScanFile" accept="image/jpeg,image/png,image/webp" capture="environment" class="d-none">
          <button type="button" class="btn btn-action btn-action-primary" id="newDoBtn">
            <i class="fas fa-plus"></i> <?=$t('add_new_code', 'Add New')?>
          </button>
          <?php } ?>
        </div>
      </div>
      <div class="card-body">
        <table class="table data-table" id="doListTable">
          <thead>
            <tr>
              <th><?=$t('do_no_code', 'DO No')?></th>
              <th><?=$t('date_code', 'Date')?></th>
              <th><?=$t('supplier_code', 'Supplier')?></th>
              <th><?=$t('do_weight_code', 'DO Weight')?></th>
              <th><?=$t('received_weight_code', 'Received Weight')?></th>
              <th><?=$t('weight_loss_code', 'Weight Loss')?></th>
              <th><?=$t('created_by_code', 'Created By')?></th>
              <th><?=$t('actions_code', 'Actions')?></th>
            </tr>
          </thead>
        </table>
      </div>
    </div>

    <!-- Delivery Order Modal -->
    <div class="modal fade modal-modern" id="doModal" tabindex="-1">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <div class="d-flex align-items-center">
              <span class="do-modal-icon mr-3"><i class="fas fa-truck-loading"></i></span>
              <div>
                <h5 class="modal-title"><?=$t('delivery_order_code', 'Delivery Order')?> - <span id="doModalTitle"><?=$t('new_code', 'New')?></span></h5>
                <div class="do-modal-subtitle"><?=$t('do_modal_subtitle_code', 'Good deliveries start with the right details.')?></div>
              </div>
            </div>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="doId">

            <div class="modal-section">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="section-title mb-0"><span class="do-step mr-2">01</span><?=$t('do_order_details_code', 'Order Details')?></h6>
                <div class="d-flex align-items-center">
                  <?php if($userAllowAdd == 'Y'){ ?>
                  <div class="btn-group" id="doScanGroup">
                    <button type="button" class="btn btn-modern btn-modern-primary btn-sm dropdown-toggle" id="doScanBtn" data-toggle="dropdown"><i class="fas fa-camera mr-1"></i><?=$t('do_scan_paper_code', 'Scan Paper DO')?></button>
                    <div class="dropdown-menu dropdown-menu-right" id="doScanProviders"></div>
                  </div>
                  <?php } ?>
                </div>
              </div>
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group-modern">
                    <label class="form-label-modern"><?=$t('date_code', 'Date')?> <span class="text-danger">*</span></label>
                    <div class="input-group date" id="doDatePicker" data-target-input="nearest">
                      <input type="text" class="form-control datetimepicker-input" data-target="#doDatePicker" id="doDate"/>
                      <div class="input-group-append" data-target="#doDatePicker" data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group-modern">
                    <label class="form-label-modern"><?=$t('do_no_code', 'DO No')?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="doNo">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group-modern">
                    <label class="form-label-modern"><?=$t('remark_code', 'Remark')?> <span class="do-hint ml-1"><?=$t('optional_code', 'Optional')?></span></label>
                    <textarea class="form-control" id="doRemark" rows="1" placeholder="<?=$t('enter_remark_code', 'Enter remark')?>"></textarea>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-section">
              <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <h6 class="section-title mb-0"><span class="do-step mr-2">02</span><?=$t('do_suppliers_items_code', 'Suppliers & Items')?></h6>
                <div class="d-flex align-items-center" style="width:15%;">
                  <select id="doSupplierPicker" class="form-control form-control-sm" style="width:100%;"></select>
                </div>
              </div>
              <ul class="nav do-supplier-tabs mb-3" id="doSupplierTabs"></ul>
              <div id="doSuppliers" class="tab-content do-suppliers"></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$t('close_code', 'Close')?></button>
            <button type="button" class="btn btn-modern btn-modern-primary" id="saveDoBtn"><i class="fas fa-check mr-1"></i><?=$t('save_code', 'Save')?></button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Excel Upload Preview Modal -->
<div class="modal fade modal-modern" id="doImportModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-file-excel mr-2 text-muted"></i><?=$t('do_import_preview_code', 'Upload Preview')?></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th><?=$t('do_no_code', 'DO No')?></th>
                <th><?=$t('date_code', 'Date')?></th>
                <th class="text-right"><?=$t('suppliers_code', 'Suppliers')?></th>
                <th class="text-right"><?=$t('lines_code', 'Lines')?></th>
                <th class="text-right"><?=$t('do_weight_code', 'DO Weight')?></th>
                <th><?=$t('status_code', 'Status')?></th>
              </tr>
            </thead>
            <tbody id="doImportTable"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$t('close_code', 'Close')?></button>
        <button type="button" class="btn btn-modern btn-modern-primary" id="doImportSaveBtn"><i class="fas fa-save mr-1"></i><?=$t('do_save_valid_code', 'Save Valid DOs')?> (<span id="doImportCount">0</span>)</button>
      </div>
    </div>
  </div>
</div>

<!-- Supplier Card Template -->
<script type="text/html" id="doSupplierTemplate">
  <div class="tab-pane do-supplier">
    <div class="pt-1">
      <div class="do-supplier-meta">
        <span><i class="fas fa-circle fa-xs mr-1"></i><span class="do-supplier-code"></span> / <span class="do-item-count">0</span> <?=$t('do_items_in_delivery_code', 'items in this delivery')?></span>
        <span>
          <button type="button" class="btn btn-link btn-sm text-danger p-0 do-ask-remove-supplier"><i class="fas fa-trash mr-1"></i><?=$t('do_remove_supplier_code', 'Remove Supplier')?></button>
        </span>
      </div>
      <div class="do-remove-confirm" style="display:none;">
        <div>
          <div class="font-weight-bold"><?=$t('do_remove_supplier_code', 'Remove Supplier')?>?</div>
          <small><?=$t('do_remove_supplier_confirm_code', 'Remove this supplier and all its items?')?></small>
        </div>
        <div>
          <button type="button" class="btn btn-modern btn-modern-secondary btn-sm do-keep-supplier"><?=$t('do_keep_supplier_code', 'Keep Supplier')?></button>
          <button type="button" class="btn btn-modern btn-modern-danger btn-sm ml-2 do-remove-supplier"><?=$t('do_remove_supplier_code', 'Remove Supplier')?></button>
        </div>
      </div>
      <div class="do-supplier-unmatched">
        <span><i class="fas fa-exclamation-triangle mr-1"></i><?=$t('do_supplier_not_found_code', 'Supplier not found')?>: <strong class="do-scanned-name"></strong></span>
        <div class="do-supplier-unmatched-select"><select class="form-control form-control-sm do-supplier-select"></select></div>
      </div>
      <div class="do-lines"></div>
      <button type="button" class="btn btn-block do-add-line">
        <i class="fas fa-plus mr-1"></i><?=$t('do_add_item_code', 'Add Item')?>
      </button>
    </div>
  </div>
</script>

<!-- Item Row Template -->
<script type="text/html" id="doLineTemplate">
  <div class="do-line">
    <div class="do-line-head">
      <span class="do-line-icon"><i class="fas fa-box-open"></i></span>
      <div class="do-line-field">
        <label class="do-field-label"><?=$t('category_code', 'Category')?></label>
        <select class="form-control form-control-sm do-category"></select>
      </div>
      <div class="do-line-field">
        <label class="do-field-label"><?=$t('product_code', 'Product')?></label>
        <select class="form-control form-control-sm do-product"></select>
      </div>
      <div class="do-line-field">
        <label class="do-field-label"><?=$t('grade_code', 'Grade')?></label>
        <select class="form-control form-control-sm do-grade"></select>
      </div>
      <button type="button" class="btn btn-link text-danger do-remove-line" title="<?=$t('do_remove_item_code', 'Remove Item')?>" aria-label="<?=$t('do_remove_item_code', 'Remove Item')?>">
        <i class="fas fa-trash"></i>
      </button>
    </div>
    <div class="do-line-body">
      <div class="do-bag-row do-bag-labels">
        <span><?=$t('do_weight_code', 'DO Weight')?></span>
        <span><?=$t('received_weight_code', 'Received Weight')?></span>
        <span><?=$t('weight_loss_code', 'Weight Loss')?></span>
        <span></span>
      </div>
      <div class="do-bags"></div>
      <button type="button" class="btn btn-link btn-sm px-0 do-add-bag">
        <i class="fas fa-plus mr-1"></i><?=$t('do_add_weight_entry_code', 'Add Weight Entry')?>
      </button>
    </div>
  </div>
</script>

<script>
var doAllowPrice = '<?=$doAllowPrice?>';
var doAllowEdit = '<?=$userAllowEdit?>';
var doAllowDelete = '<?=$userAllowDelete?>';
var doText = <?=json_encode([
  'doWeight' => $t('do_weight_code', 'DO Weight'),
  'aiNotSetUp' => $t('do_ai_not_set_up_code', 'AI scan is not set up. Please contact support.'),
  'received' => $t('received_weight_code', 'Received Weight'),
  'loss' => $t('weight_loss_code', 'Weight Loss'),
  'edit' => $t('edit_code', 'Edit'),
  'new' => $t('new_code', 'New'),
  'delete' => $t('delete_code', 'Delete'),
  'deleteEntry' => $t('do_delete_entry_code', 'Delete Entry'),
  'removeLineConfirm' => $t('do_remove_item_confirm_code', 'Remove this item and its weight entries?'),
  'removeSupplierConfirm' => $t('do_remove_supplier_confirm_code', 'Remove this supplier and all its items?'),
  'notWeighed' => $t('do_not_weighed_code', 'Not weighed yet'),
  'matched' => $t('do_matched_code', 'Matched'),
  'short' => $t('do_short_code', 'Short'),
  'over' => $t('do_over_code', 'Over'),
  'noEntries' => $t('do_no_weight_entries_code', 'No weight entries yet'),
  'status' => $t('status_code', 'Status'),
  'supplier' => $t('supplier_code', 'Supplier'),
  'product' => $t('product_code', 'Product'),
  'grade' => $t('grade_code', 'Grade'),
  'total' => $t('total_code', 'Total'),
  'category' => $t('category_code', 'Category'),
  'addSupplier' => $t('add_supplier_code', 'Add Supplier'),
  'selectSupplier' => $t('select_supplier_code', 'Select Supplier'),
  'supplierExists' => $t('do_supplier_exists_code', 'Supplier already added. Showing its tab.'),
  'allSuppliers' => $t('all_suppliers_code', 'All Suppliers'),
  'allCategories' => $t('all_categories_code', 'All Categories'),
  'allProducts' => $t('all_products_code', 'All Products'),
  'allGrades' => $t('all_grades_code', 'All Grades'),
  'noMatch' => $t('no_matching_records_code', 'No Matching Records'),
  'noRecords' => $t('no_records_found_code', 'No Records Found'),
  'noRecordsMessage' => $t('no_records_message_code', 'Try adjusting your search or filter criteria'),
  'noMatchMessage' => $t('no_matching_message_code', 'No results match your current filters. Try different criteria.'),
  'unsavedConfirm' => $t('do_unsaved_confirm_code', 'You have unsaved changes. Close without saving?'),
  'deleteConfirm' => $t('do_delete_confirm_code', 'Delete this delivery order? Its wholesales records will be deleted and stock reversed.'),
  'errDate' => $t('do_err_date_code', 'Please select DO date.'),
  'errDoNo' => $t('do_err_do_no_code', 'Please enter DO No.'),
  'errSupplier' => $t('do_err_supplier_code', 'Please select supplier for all cards.'),
  'errLine' => $t('do_err_line_code', 'Please fill product, grade and weight for all lines.'),
  'errNoLine' => $t('do_err_no_line_code', 'Each supplier needs at least one line.'),
  'errNoSupplier' => $t('do_err_no_supplier_code', 'Please add at least one supplier.'),
  'ready' => $t('do_ready_code', 'Ready'),
  'skipped' => $t('do_skipped_code', 'Skipped')
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
</script>
<script src="modules/deliveryOrder/js/modal.js?ver=<?=filemtime(__DIR__ . "/js/modal.js")?>"></script>
<script src="modules/deliveryOrder/js/save.js?ver=<?=filemtime(__DIR__ . "/js/save.js")?>"></script>
<script src="modules/deliveryOrder/js/import.js?ver=<?=filemtime(__DIR__ . "/js/import.js")?>"></script>
<script src="modules/deliveryOrder/js/deliveryOrder.js?ver=<?=filemtime(__DIR__ . "/js/deliveryOrder.js")?>"></script>
