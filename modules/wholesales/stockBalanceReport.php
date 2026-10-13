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
$lookups = $wholesaleService->getStockBalanceLookups();

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

    <!-- Tabs -->
    <ul class="nav nav-tabs nav-tabs-modern" id="stockTabs">
      <li class="nav-item">
        <a class="nav-link active" id="tab-report" data-toggle="tab" href="#paneReport">
          <i class="fas fa-file-alt"></i> <?=$t('stock_balance_report_code')?>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="tab-adjustment" data-toggle="tab" href="#paneAdjustment">
          <i class="fas fa-sliders-h"></i> <?=$t('stock_adjustment_code')?>
        </a>
      </li>
    </ul>

    <div class="tab-content">
      <!-- Tab 1: Stock Balance Report -->
      <div class="tab-pane fade show active" id="paneReport">

        <!-- Filter Card -->
        <div class="card filter-card">
          <div class="card-body">
            <div class="filter-row">
              <div class="filter-group">
                <label class="filter-label"><?=$t('date_code')?></label>
                <div class="input-group date" id="datePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#datePicker" id="date"/>
                  <div class="input-group-append" data-target="#datePicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>

              <div class="filter-group">
                <label class="filter-label"><?=$t('locations_code')?></label>
                <select class="form-control select2" id="locationFilter">
                  <option value="">-</option>
                  <?php foreach ($lookups['locations'] as $row) { ?>
                    <option value="<?=$row['id']?>"><?=htmlspecialchars($row['locations'])?></option>
                  <?php } ?>
                </select>
              </div>

              <div class="filter-group">
                <label class="filter-label"><?=$t('category_code')?></label>
                <select class="form-control select2" id="categoryFilter">
                  <option value="">-</option>
                  <?php foreach ($lookups['categories'] as $row) { ?>
                    <option value="<?=$row['id']?>"><?=htmlspecialchars($row['category_name'])?></option>
                  <?php } ?>
                </select>
              </div>

              <div class="filter-group">
                <label class="filter-label"><?=$t('product_code')?></label>
                <select class="form-control select2" id="productFilter">
                  <option value="">-</option>
                  <?php foreach ($lookups['products'] as $row) { ?>
                    <option value="<?=$row['id']?>" data-category="<?=$row['category']?>"><?=htmlspecialchars($row['product_name'])?></option>
                  <?php } ?>
                </select>
              </div>

              <div class="filter-group">
                <label class="filter-label"><?=$t('type_code', 'Type')?></label>
                <select class="form-control" id="typeFilter">
                  <option value="Local" selected><?=$t('local_code', 'Local')?></option>
                  <option value="Export"><?=$t('export_code', 'Export')?></option>
                </select>
              </div>

              <div class="filter-group filter-group-action" style="margin-left:auto;">
                <label class="filter-label">&nbsp;</label>
                <button type="button" class="btn btn-filter btn-filter-primary" id="refreshBtn">
                  <i class="fas fa-search"></i> <?=$t('search_code')?>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Results Card -->
        <div class="card results-card">
          <div class="card-header">
            <div class="results-header-left">
              <h3 class="results-title"><i class="fas fa-boxes mr-2"></i><?=$t('stock_balance_code')?></h3>
            </div>
            <div class="results-header-right d-flex" style="gap: 0.5rem;">
              <button type="button" class="btn btn-action btn-action-warning" id="exportBtn">
                <i class="fas fa-file-pdf"></i> <?=$t('export_pdf_code')?>
              </button>
            </div>
          </div>
          <div class="card-body p-0">
            <iframe id="previewFrame" src="" style="width:100%; height:80vh; border:none;"></iframe>
          </div>
        </div>

      </div><!-- /paneReport -->

      <!-- Tab 2: Stock Adjustment -->
      <div class="tab-pane fade" id="paneAdjustment">

        <!-- Filter Card -->
        <div class="card filter-card">
          <div class="card-body">
            <div class="filter-row">
              <div class="filter-group">
                <label class="filter-label"><?=$t('date_from_code', 'Date From')?></label>
                <div class="input-group date" id="adjDateFromPicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#adjDateFromPicker" id="adjDateFrom"/>
                  <div class="input-group-append" data-target="#adjDateFromPicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>
              <div class="filter-group">
                <label class="filter-label"><?=$t('date_to_code', 'Date To')?></label>
                <div class="input-group date" id="adjDateToPicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#adjDateToPicker" id="adjDateTo"/>
                  <div class="input-group-append" data-target="#adjDateToPicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>
              <div class="filter-group filter-group-action" style="margin-left:auto;">
                <label class="filter-label">&nbsp;</label>
                <button type="button" class="btn btn-filter btn-filter-primary" id="loadAdjListBtn">
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
              <h3 class="results-title"><i class="fas fa-sliders-h mr-2"></i><?=$t('stock_adjustment_code')?></h3>
            </div>
            <div class="results-header-right d-flex" style="gap: 0.5rem;">
              <button type="button" class="btn btn-action btn-action-primary" id="newAdjBtn">
                <i class="fas fa-plus"></i> <?=$t('new_adjustment_code', 'New Adjustment')?>
              </button>
            </div>
          </div>
          <div class="card-body">
            <table class="table data-table" id="adjustListTable">
              <thead>
                <tr>
                  <th><?=$t('adjustment_no_code', 'Adjustment No')?></th>
                  <th><?=$t('date_code')?></th>
                  <th><?=$t('items_code', 'Items')?></th>
                  <th><?=$t('total_qty_code', 'Total Qty')?></th>
                  <th><?=$t('total_cost_code', 'Total Cost')?></th>
                  <th><?=$t('created_by_code', 'Created By')?></th>
                  <th style="width:120px;"><?=$t('actions_code')?></th>
                </tr>
              </thead>
            </table>
          </div>
        </div>

      </div><!-- /paneAdjustment -->
    </div><!-- /tab-content -->

  </div>
</div>

<!-- Stock Adjustment Modal -->
<div class="modal fade modal-modern" id="adjModal" tabindex="-1">
  <div class="modal-dialog modal-xl" style="max-width:95%;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-sliders-h mr-2 text-muted"></i><?=$t('stock_adjustment_code')?> - <span id="adjModalTitle"><?=$t('new_code', 'New')?></span></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="adjId">

        <!-- Header Info -->
        <div class="modal-section">
          <div class="row">
            <div class="col-md-3">
              <div class="form-group-modern">
                <label class="form-label-modern"><?=$t('adjustment_date_code', 'Adjustment Date')?> <span class="text-danger">*</span></label>
                <div class="input-group date" id="adjDatePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#adjDatePicker" id="adjDate"/>
                  <div class="input-group-append" data-target="#adjDatePicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group-modern">
                <label class="form-label-modern"><?=$t('type_code', 'Type')?> <span class="text-danger">*</span></label>
                <select class="form-control" id="adjType">
                  <option value="Local"><?=$t('local_code', 'Local')?></option>
                  <option value="Export"><?=$t('export_code', 'Export')?></option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group-modern">
                <label class="form-label-modern"><?=$t('remark_code')?></label>
                <input type="text" class="form-control" id="adjRemark" placeholder="<?=$t('enter_remark_code', 'Enter remark')?>">
              </div>
            </div>
          </div>
        </div>

        <!-- Items Section -->
        <div class="modal-section">
          <div class="section-header-toggle">
            <h6 class="section-title mb-0"><i class="fas fa-list mr-2"></i><?=$t('items_code', 'Items')?></h6>
            <button type="button" class="btn btn-modern btn-modern-primary btn-sm" id="addAdjItemBtn">
              <i class="fas fa-plus"></i> <?=$t('add_item_code', 'Add Item')?>
            </button>
          </div>
          <div class="table-responsive mt-3">
            <table class="table table-bordered table-sm" id="adjItemsTable">
              <thead>
                <tr>
                  <th style="width:15%;"><?=$t('product_code')?></th>
                  <th style="width:10%;"><?=$t('grade_code')?></th>
                  <th style="width:8%;" class="text-right"><?=$t('current_qty_code', 'Current Qty')?></th>
                  <th style="width:8%;" class="text-right"><?=$t('adjust_qty_code', 'Adjust Qty')?></th>
                  <th style="width:8%;" class="text-right"><?=$t('new_qty_code', 'New Qty')?></th>
                  <th style="width:8%;" class="text-right"><?=$t('unit_cost_code', 'Unit Cost')?></th>
                  <th style="width:8%;" class="text-right"><?=$t('total_cost_code', 'Total Cost')?></th>
                  <th style="width:30%;"><?=$t('reason_code', 'Reason')?></th>
                  <th style="width:5%;"><?=$t('actions_code')?></th>
                </tr>
              </thead>
              <tbody id="adjItemsBody"></tbody>
              <tfoot>
                <tr class="table-secondary">
                  <th colspan="3" class="text-right"><?=$t('total_code', 'Total')?></th>
                  <th class="text-right" id="adjTotalQty">0.00</th>
                  <th colspan="2"></th>
                  <th class="text-right" id="adjTotalCost">0.00</th>
                  <th colspan="2"></th>
                </tr>
              </tfoot>
            </table>
          </div>
          <div id="adjItemsEmpty" class="text-center text-muted py-4" style="display:none;">
            <i class="fas fa-inbox fa-2x mb-2"></i>
            <p class="mb-0"><?=$t('no_items_added_code', 'No items added. Click "Add Item" to begin.')?></p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$t('close_code')?></button>
        <button type="button" class="btn btn-modern btn-modern-primary" id="saveAdjBtn"><?=$t('save_code')?></button>
      </div>
    </div>
  </div>
</div>

<!-- Adjustment Item Row Template -->
<script type="text/html" id="adjItemRowTemplate">
  <tr class="adj-item-row">
    <td><select class="form-control form-control-sm adj-product-select" id="adjProduct" style="width:100%;"></select></td>
    <td><select class="form-control form-control-sm adj-grade-select" id="adjGrade" style="width:100%;"><option value="">-</option></select></td>
    <td><input type="text" class="form-control form-control-sm text-right adj-current-qty" id="adjCurrentQty" readonly></td>
    <td><input type="number" step="0.01" class="form-control form-control-sm text-right adj-adjust-qty" id="adjAdjustQty" placeholder="+/-"></td>
    <td><input type="text" class="form-control form-control-sm text-right adj-new-qty" id="adjNewQty" readonly></td>
    <td><input type="number" step="0.01" class="form-control form-control-sm text-right adj-unit-cost" id="adjUnitCost" value="0"></td>
    <td class="text-right adj-total-cost">0.00</td>
    <td><textarea class="form-control form-control-sm adj-reason" id="adjReason" rows="1"></textarea></td>
    <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm remove-adj-item"><i class="fas fa-trash"></i></button></td>
  </tr>
</script>

<script>
var stockBalanceText = <?=json_encode([
  'noRecordsFound' => $t('no_records_found_code', 'No Records Found'),
  'noRecordsMessage' => $t('no_records_message_code', 'Try adjusting your search or filter criteria'),
  'totalItems' => $t('total_items_code', 'Total Items'),
  'totalQty' => $t('total_qty_code', 'Total Qty'),
  'totalCost' => $t('total_cost_code', 'Total Cost'),
  'adjustmentInfo' => $t('adjustment_information_code', 'Adjustment Information'),
  'adjustmentNo' => $t('adjustment_no_code', 'Adjustment No'),
  'date' => $t('date_code'),
  'createdBy' => $t('created_by_code', 'Created By'),
  'remark' => $t('remark_code'),
  'adjustmentItems' => $t('adjustment_items_code', 'Adjustment Items'),
  'product' => $t('product_code'),
  'grade' => $t('grade_code'),
  'qtyBefore' => $t('qty_before_code', 'Qty Before'),
  'adjustment' => $t('adjustment_code', 'Adjustment'),
  'qtyAfter' => $t('qty_after_code', 'Qty After'),
  'unitCost' => $t('unit_cost_code', 'Unit Cost'),
  'reason' => $t('reason_code', 'Reason'),
  'total' => $t('total_code', 'Total'),
  'edit' => $t('edit_code', 'Edit'),
  'new' => $t('new_code', 'New'),
  'confirmDelete' => $t('confirm_delete_code', 'Confirm Delete'),
  'deleteConfirmMessage' => $t('delete_adjustment_confirm_code', 'Are you sure you want to delete this adjustment? Stock will be reversed.'),
  'delete' => $t('delete_code', 'Delete')
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
</script>
<script src="modules/wholesales/js/stockBalanceReport.js?v=<?=time()?>"></script>
