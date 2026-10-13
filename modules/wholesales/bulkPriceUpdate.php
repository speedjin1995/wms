<?php
require_once '../../php/db_connect.php';
require_once '../../php/bootstrap.php';

use App\Modules\Wholesale\BulkPriceUpdateService;

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
  exit;
}

$bulkPriceService = new BulkPriceUpdateService(
  $db,
  (int)$_SESSION['customer'],
  (int)$_SESSION['userID'],
  (string)($_SESSION['role'] ?? ''),
  (array)($_SESSION['userModuleAccess'] ?? [])
);
$permissions = $bulkPriceService->getPermissions();
$lookups = $bulkPriceService->getLookups();

// Language
$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];
$t = function ($key, $default = '') use ($languageArray, $language) {
  return $languageArray[$key][$language] ?? $default;
};
?>

<style>
/* Child Table (expanded row) */
.child-row-wrapper { background: #f8fafc; padding: 1rem 1.25rem 1rem 3.5rem; border-bottom: 1px solid var(--page-border, #e2e8f0); }
.child-table { width: 100%; background: #fff; border: 1px solid var(--page-border, #e2e8f0); border-radius: 0.5rem; overflow: hidden; font-size: 0.8125rem; }
.child-table thead th { background: #f1f5f9; padding: 0.625rem 0.875rem; font-size: 0.625rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; border-bottom: 1px solid var(--page-border, #e2e8f0); }
.child-table tbody td { padding: 0.625rem 0.875rem; color: #0f172a; border-bottom: 1px solid #f1f5f9; }
.child-table tbody tr:last-child td { border-bottom: none; }

/* Wizard Styles */
.wizard-header { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-bottom: 1px solid var(--modal-border, #e2e8f0); padding: 20px 0; }
.wizard-steps { display: flex; align-items: flex-start; justify-content: center; max-width: 500px; margin: 0 auto; }
.wizard-step { display: flex; flex-direction: column; align-items: center; flex: 0 0 auto; min-width: 80px; }
.wizard-step-circle { width: 44px; height: 44px; border-radius: 50%; background: #dee2e6; color: #6c757d; display: flex; align-items: center; justify-content: center; font-size: 16px; transition: all 0.3s ease; border: 3px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.wizard-step.active .wizard-step-circle { background: var(--modal-primary, #3b82f6); color: #fff; box-shadow: 0 0 0 4px rgba(59,130,246,0.2), 0 2px 8px rgba(59,130,246,0.3); }
.wizard-step.done .wizard-step-circle { background: var(--modal-success, #10b981); color: #fff; }
.wizard-step-label { font-size: 11px; margin-top: 8px; color: #6c757d; font-weight: 500; text-align: center; text-transform: uppercase; letter-spacing: 0.3px; }
.wizard-step.active .wizard-step-label { color: var(--modal-primary, #3b82f6); font-weight: 600; }
.wizard-step.done .wizard-step-label { color: var(--modal-success, #10b981); font-weight: 600; }
.wizard-step-line { flex: 1; height: 3px; background: #dee2e6; margin: 0 12px; margin-top: 22px; min-width: 60px; max-width: 100px; border-radius: 2px; transition: background 0.3s ease; }
.wizard-step-line.done { background: var(--modal-success, #10b981); }

/* Step Hint */
.step-hint { background: #fffbeb; border-radius: 6px; padding: 12px 16px; font-size: 13px; color: #92400e; border-left: 3px solid #f59e0b; }
.step-hint i { color: #f59e0b; }

/* Preview Scroll */
.preview-scroll { max-height: 350px; overflow-y: auto; }

/* New Value Highlight */
.new-value { background: #dcfce7 !important; color: #166534 !important; }

/* Confirm Card */
.confirm-card { text-align: center; padding: 40px 20px; }
.confirm-icon { width: 80px; height: 80px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
.confirm-icon i { font-size: 36px; color: #92400e; }
.confirm-title { font-size: 22px; font-weight: 600; color: #0f172a; margin-bottom: 12px; }
.confirm-message { font-size: 15px; color: #475569; margin-bottom: 20px; }
.confirm-count { display: inline-block; background: var(--modal-primary, #3b82f6); color: #fff; font-weight: 700; padding: 2px 12px; border-radius: 20px; font-size: 16px; }
.confirm-warning { background: #f1f5f9; border-radius: 6px; padding: 12px 16px; font-size: 13px; color: #64748b; display: inline-block; }
</style>

<div class="content-header" style="padding-bottom: 0;">
  <div class="container-fluid"></div>
</div>

<!-- Main content -->
<div class="content page-modern">
  <div class="container-fluid">
    <!-- Filter Card -->
    <div class="filter-card">
      <div class="card-body">
        <div class="filter-row">
          <!-- Date -->
          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['date_code'][$language] ?? 'Date'?></label>
            <div class="input-group date" id="datePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#datePicker" id="date"/>
              <div class="input-group-append" data-target="#datePicker" data-toggle="datetimepicker">
                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
              </div>
            </div>
          </div>

          <!-- Transaction Status -->
          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['transaction_status_code'][$language]?></label>
            <select class="form-control" id="transactionStatusFilter">
              <option value="DISPATCH" selected><?=$languageArray['dispatch_code'][$language]?></option>
              <option value="RECEIVING"><?=$languageArray['receiving_code'][$language]?></option>
            </select>
          </div>

          <!-- Customer (shown for DISPATCH) -->
          <div class="filter-group" id="customerFilterGroup">
            <label class="filter-label"><?=$languageArray['customer_code'][$language] ?? 'Customer'?></label>
            <select class="form-control select2-filter" id="customerFilter">
              <option value=""><?=$languageArray['please_select_code'][$language] ?? 'Please Select'?></option>
              <?php foreach($lookups['customers'] as $c){ ?>
                <option value="<?=$c['id']?>"><?=htmlspecialchars($c['customer_name'])?></option>
              <?php } ?>
            </select>
          </div>

          <!-- Supplier (shown for RECEIVING) -->
          <div class="filter-group" id="supplierFilterGroup" style="display:none;">
            <label class="filter-label"><?=$languageArray['supplier_code'][$language] ?? 'Supplier'?></label>
            <select class="form-control select2-filter" id="supplierFilter">
              <option value=""><?=$languageArray['please_select_code'][$language] ?? 'Please Select'?></option>
              <?php foreach($lookups['suppliers'] as $s){ ?>
                <option value="<?=$s['id']?>"><?=htmlspecialchars($s['supplier_name'])?></option>
              <?php } ?>
            </select>
          </div>

          <!-- Product (Optional) -->
          <div class="filter-group">
            <label class="filter-label text-muted"><?=$languageArray['product_code'][$language]?> <small>(<?=$languageArray['optional_code'][$language] ?? 'Optional'?>)</small></label>
            <select class="form-control select2-filter" id="productFilter">
              <option value=""><?=$languageArray['all_code'][$language] ?? 'All'?></option>
              <?php foreach($lookups['products'] as $p){ ?>
                <option value="<?=$p['id']?>"><?=htmlspecialchars($p['product_name'])?></option>
              <?php } ?>
            </select>
          </div>

          <!-- Grade (Optional) -->
          <div class="filter-group">
            <label class="filter-label text-muted"><?=$languageArray['grade_code'][$language]?> <small>(<?=$languageArray['optional_code'][$language] ?? 'Optional'?>)</small></label>
            <select class="form-control select2-filter" id="gradeFilter">
              <option value=""><?=$languageArray['all_code'][$language] ?? 'All'?></option>
            </select>
          </div>

          <!-- Search Button -->
          <div class="filter-group filter-group-action">
            <button type="button" class="btn btn-filter btn-filter-primary" id="bulkFilterSearch">
              <i class="fas fa-search"></i> <?=$languageArray['search_code'][$language]?>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Results Card -->
    <div class="results-card" id="resultsCard" style="display:none;">
      <div class="card-header">
        <div class="results-header-left">
          <h3 class="results-title">
            <i class="fas fa-list-alt"></i>
            <?=$languageArray['bulk_price_update_code'][$language] ?? 'Bulk Price Update'?>
          </h3>
          <span class="results-count" id="resultCount">0</span>
        </div>
        <?php if($permissions['allowEdit']){ ?>
        <div class="results-header-right">
          <button type="button" class="btn btn-action btn-action-warning" id="btnOpenBulkPrice" onclick="openBulkPriceModal()">
            <i class="fas fa-edit"></i> <?=$languageArray['update_price_code'][$language] ?? 'Update Price'?>
          </button>
        </div>
        <?php } ?>
      </div>
      <div class="card-body">
        <table id="weightTable" class="table data-table">
          <thead>
            <tr>
              <th width="50"></th>
              <th><?=$languageArray['serial_no_code'][$language]?></th>
              <th><?=$languageArray['do_po_no_code'][$language]?></th>
              <th><?=$languageArray['customer_supplier_code'][$language]?></th>
              <th><?=$languageArray['start_time_code'][$language]?></th>
              <th class="text-center"><?=$languageArray['transaction_status_code'][$language]?></th>
              <th class="text-center"><?=$languageArray['items_code'][$language]?></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
        <!-- Empty State -->
        <div id="emptyState" class="empty-state" style="display:none;">
          <div class="empty-state-icon">
            <i class="fas fa-inbox"></i>
          </div>
          <p class="empty-state-text"><?=$languageArray['no_records_found_code'][$language] ?? 'No records found'?></p>
          <span class="empty-state-hint"><?=$languageArray['try_adjusting_search_code'][$language] ?? 'Try adjusting your search criteria'?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bulk Price Update Modal -->
<div class="modal fade modal-modern" id="bulkPriceModal">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <form role="form" id="bulkPriceForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-tags text-muted mr-2"></i><?=$languageArray['update_price_code'][$language] ?? 'Update Price'?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-0">

          <!-- Wizard Steps Indicator -->
          <div class="wizard-header">
            <div class="wizard-steps">
              <div class="wizard-step active" id="wizardStep1">
                <div class="wizard-step-circle"><i class="fas fa-dollar-sign"></i></div>
                <div class="wizard-step-label"><?=$languageArray['set_price_code'][$language] ?? 'Set Price'?></div>
              </div>
              <div class="wizard-step-line" id="wizardLine1"></div>
              <div class="wizard-step" id="wizardStep2">
                <div class="wizard-step-circle"><i class="fas fa-eye"></i></div>
                <div class="wizard-step-label"><?=$languageArray['preview_code'][$language]?></div>
              </div>
              <div class="wizard-step-line" id="wizardLine2"></div>
              <div class="wizard-step" id="wizardStep3">
                <div class="wizard-step-circle"><i class="fas fa-check"></i></div>
                <div class="wizard-step-label"><?=$languageArray['confirm_code'][$language] ?? 'Confirm'?></div>
              </div>
            </div>
          </div>

          <div class="p-4">
            <!-- Step 1: inputs -->
            <div id="stepInputs">
              <div class="step-hint mb-3">
                <i class="fas fa-lightbulb mr-2"></i>
                <span><?=$languageArray['set_new_price_hint_code'][$language] ?? 'Confirm'?></span>
              </div>
              <div class="modal-section p-0">
                <table class="table table-sm mb-0" id="priceInputTable">
                  <thead>
                    <tr>
                      <th><?=$languageArray['product_code'][$language]?></th>
                      <th><?=$languageArray['grade_code'][$language]?></th>
                      <th width="150"><?=$languageArray['pricing_type_code'][$language] ?? 'Pricing Type'?></th>
                      <th width="140"><?=$languageArray['new_price_code'][$language] ?? 'New Price'?></th>
                    </tr>
                  </thead>
                  <tbody id="priceInputBody">
                    <tr><td colspan="4" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin mr-2"></i>Loading...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Step 2: preview -->
            <div id="stepPreview" style="display:none;">
              <div class="step-hint mb-3">
                <i class="fas fa-search mr-2"></i>
                <span><?=$languageArray['review_changes_message_code'][$language] ?? 'Review the changes below before confirming.'?></span>
              </div>
              <div class="modal-section p-0 preview-scroll">
                <table class="table table-sm mb-0">
                  <thead>
                    <tr>
                      <th><?=$languageArray['serial_no_code'][$language]?></th>
                      <th><?=$languageArray['start_time_code'][$language]?></th>
                      <th><?=$languageArray['product_code'][$language]?></th>
                      <th><?=$languageArray['grade_code'][$language]?></th>
                      <th class="text-right"><?=$languageArray['net_code'][$language]?></th>
                      <th class="text-right"><?=$languageArray['old_price_code'][$language] ?? 'Old Price'?></th>
                      <th class="text-right"><?=$languageArray['old_total_code'][$language] ?? 'Old Total'?></th>
                      <th class="text-right"><?=$languageArray['new_price_code'][$language] ?? 'New Price'?></th>
                      <th class="text-right"><?=$languageArray['new_total_code'][$language] ?? 'New Total'?></th>
                    </tr>
                  </thead>
                  <tbody id="previewTableBody"></tbody>
                </table>
              </div>
            </div>

            <!-- Step 3: confirm -->
            <div id="stepConfirm" style="display:none;">
              <div class="confirm-card">
                <div class="confirm-icon">
                  <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h4 class="confirm-title"><?=$languageArray['are_you_sure_code'][$language] ?? 'Are you sure?'?></h4>
                <p class="confirm-message">
                  <?=$languageArray['bulk_update_confirm_message_code'][$language] ?? 'You are about to update the price for'?>
                  <span class="confirm-count" id="confirmCount">0</span>
                  <?=$languageArray['records_code'][$language] ?? 'records'?>
                </p>
                <div class="confirm-warning">
                  <i class="fas fa-info-circle mr-1"></i>
                  <?=$languageArray['action_cannot_be_undone_code'][$language] ?? 'This action cannot be undone.'?>
                </div>
              </div>
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal">
            <?=$languageArray['close_code'][$language]?>
          </button>
          <div class="ml-auto">
            <button type="button" class="btn btn-modern btn-modern-secondary" id="btnBackToInputs" style="display:none;" onclick="wizardGoTo(1)">
              <i class="fas fa-arrow-left mr-1"></i><?=$languageArray['back_code'][$language] ?? 'Back'?>
            </button>
            <button type="button" class="btn btn-modern btn-modern-primary" id="btnPreview">
              <?=$languageArray['preview_code'][$language] ?? 'Preview'?><i class="fas fa-arrow-right ml-1"></i>
            </button>
            <button type="button" class="btn btn-modern btn-modern-secondary" id="btnBackToPreview" style="display:none;" onclick="wizardGoTo(2)">
              <i class="fas fa-arrow-left mr-1"></i><?=$languageArray['back_code'][$language] ?? 'Back'?>
            </button>
            <button type="button" class="btn btn-modern btn-modern-primary" id="btnGoConfirm" style="display:none;" onclick="wizardGoTo(3)">
              <?=$languageArray['next_code'][$language] ?? 'Next'?><i class="fas fa-arrow-right ml-1"></i>
            </button>
            <button type="button" class="btn btn-modern btn-modern-success" id="btnConfirm" style="display:none;" onclick="confirmBulkUpdate()">
              <i class="fas fa-check mr-1"></i><?=$languageArray['confirm_update_code'][$language] ?? 'Confirm Update'?>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var bulkPriceLookups = <?=json_encode([
  'gradesByProduct' => (object)$lookups['gradesByProduct']
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
var bulkPriceText = <?=json_encode([
  'product' => $t('product_code'),
  'grade' => $t('grade_code'),
  'net' => $t('net_code'),
  'price' => $t('price_code'),
  'total' => $t('total_code'),
  'float' => $t('float_code'),
  'fixed' => $t('fixed_code'),
  'dispatch' => $t('dispatch_code'),
  'receiving' => $t('receiving_code'),
  'all' => $t('all_code', 'All'),
  'preview' => $t('preview_code', 'Preview'),
  'confirmUpdate' => $t('confirm_update_code', 'Confirm Update')
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
</script>
<script src="modules/wholesales/js/bulkPriceUpdate.js?v=<?=time()?>"></script>
