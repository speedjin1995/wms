<?php
require_once '../../php/db_connect.php';
require_once '../../php/bootstrap.php';

use App\Modules\PaymentVoucher\PaymentVoucherService;

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
  exit;
}

$paymentVoucherService = new PaymentVoucherService(
  $db,
  (int)$_SESSION['customer'],
  (int)$_SESSION['userID'],
  (string)($_SESSION['role'] ?? ''),
  (string)($_SESSION['module'] ?? 'wholesales')
);
$permissions = $paymentVoucherService->getPermissions();
$lookups = $paymentVoucherService->getLookups();

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
            <label class="filter-label"><?=$t('transaction_status_code')?></label>
            <select class="form-control" id="transactionStatusFilter">
              <option value="DISPATCH" selected><?=$t('dispatch_code')?></option>
              <option value="RECEIVING"><?=$t('receiving_code')?></option>
            </select>
          </div>

          <div class="filter-group" id="parentCustomerFilterGroup">
            <label class="filter-label"><?=$t('parent_customer_code')?></label>
            <select class="form-control select2" id="parentCustomerFilter">
              <option value=""><?=$t('please_select_code', 'Please Select')?></option>
              <?php foreach ($lookups['parentCustomers'] as $row) { ?>
                <option value="<?=$row['id']?>"><?=htmlspecialchars($row['customer_name'])?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group" id="parentSupplierFilterGroup" style="display:none;">
            <label class="filter-label"><?=$t('parent_supplier_code')?></label>
            <select class="form-control select2" id="parentSupplierFilter">
              <option value=""><?=$t('please_select_code', 'Please Select')?></option>
              <?php foreach ($lookups['parentSuppliers'] as $row) { ?>
                <option value="<?=$row['id']?>"><?=htmlspecialchars($row['supplier_name'])?></option>
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
          <h3 class="results-title"><i class="fas fa-list"></i> <?=$t('payment_voucher_code')?></h3>
        </div>
        <div class="results-header-right">
          <button type="button" class="btn btn-action btn-action-success" id="exportPvReport">
            <i class="fas fa-file-export"></i> <?=$t('export_code', 'Export')?>
          </button>
        </div>
      </div>

      <div class="card-body">
        <table id="pvTable" class="table data-table">
          <thead>
            <tr>
              <th><?=$t('voucher_date_code')?></th>
              <th><?=$t('voucher_no_code')?></th>
              <th><?=$t('name_code')?></th>
              <th><?=$t('invoice_no_code')?></th>
              <th><?=$t('total_nett_weight_code')?> (KG)</th>
              <th><?=$t('unit_price_code')?> (RM)</th>
              <th><?=$t('total_price_code')?> (RM)</th>
              <th style="width:120px;"><?=$t('actions_code')?></th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Payment Voucher Modal -->
<div class="modal fade modal-modern" id="pvModal">
  <div class="modal-dialog modal-xl" style="max-width:90%;">
    <div class="modal-content">
      <form role="form" id="pvForm" novalidate>
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-file-invoice-dollar mr-2 text-muted"></i><?=$t('payment_voucher_details_code')?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <input type="hidden" id="pvId" name="pvId">
          <input type="hidden" id="pvEntityId" name="entityId">
          <input type="hidden" id="taxRate" name="tax" value="0">

          <!-- Voucher Information Section -->
          <div class="modal-section">
            <h6 class="section-title"><i class="fas fa-info-circle mr-2"></i><?=$t('payment_voucher_details_code')?></h6>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('voucher_date_code')?> <span class="text-danger">*</span></label>
                  <div class="input-group date" id="voucherDatePicker" data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input" data-target="#voucherDatePicker" id="voucherDate" name="voucherDate" required/>
                    <div class="input-group-append" data-target="#voucherDatePicker" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('voucher_no_code')?></label>
                  <input type="text" class="form-control" id="voucherNo" readonly placeholder="<?=$t('auto_generated_code', 'Auto Generated')?>">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('invoice_no_code')?></label>
                  <input type="text" class="form-control" id="invoiceNo" name="invoiceNo" maxlength="50" placeholder="<?=$t('optional_code', 'Optional')?>">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('unit_price_code')?> (RM) <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" min="0" class="form-control" id="unitPrice" name="unitPrice" value="0" required>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('total_nett_weight_code')?> (KG)</label>
                  <input type="text" class="form-control" id="totalNettWeight" readonly>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$t('total_amount_code')?> (RM)</label>
                  <input type="text" class="form-control" id="totalAmount" readonly>
                </div>
              </div>
            </div>
          </div>

          <!-- Weighing Details Section -->
          <div class="modal-section">
            <h6 class="section-title"><i class="fas fa-balance-scale mr-2"></i><?=$t('weighing_details_code')?></h6>
            <div class="table-responsive">
              <table class="table table-bordered table-sm" id="pvItemsTable">
                <thead>
                  <tr>
                    <th><?=$t('serial_no_code')?></th>
                    <th><?=$t('date_code')?></th>
                    <th><?=$t('name_code')?></th>
                    <th><?=$t('vehicle_no_code')?></th>
                    <th><?=$t('category_code')?></th>
                    <th class="text-right"><?=$t('nett_weight_code')?> (KG)</th>
                    <th width="12%"><?=$t('unit_price_code')?> (RM)</th>
                    <th class="text-right"><?=$t('total_price_code')?> (RM)</th>
                  </tr>
                </thead>
                <tbody id="pvItemsBody"></tbody>
                <tfoot>
                  <tr class="font-weight-bold">
                    <td colspan="5" class="text-right"><?=$t('total_code')?></td>
                    <td class="text-right" id="footTotalNett">0.00</td>
                    <td></td>
                    <td class="text-right" id="footTotalPrice">0.00</td>
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

<!-- Print Options Modal -->
<div class="modal fade modal-modern" id="printModal">
  <div class="modal-dialog" style="max-width:420px;">
    <div class="modal-content">
      <form id="printForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-print mr-2 text-muted"></i><?=$t('print_code')?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="printPvId" name="pvId">
          <div class="form-group-modern mb-0">
            <label class="form-label-modern"><?=$t('select_slip_type_code')?></label>
            <select class="form-control" id="printSlipType" name="slipType">
              <option value="pv"><?=$t('payment_voucher_code')?></option>
              <option value="statement"><?=$t('statement_code')?></option>
            </select>
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$t('close_code')?></button>
          <button type="submit" class="btn btn-modern btn-modern-primary" id="confirmPrint"><i class="fas fa-print mr-1"></i><?=$t('print_code')?></button>
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

<script>
var pvPermissions = <?=json_encode($permissions)?>;
var pvText = <?=json_encode([
  'pleaseSelect' => $t('please_select_code', 'Please Select'),
  'noRecordsFound' => $t('no_records_found_code', 'No Records Found'),
  'noRecordsMessage' => $t('no_records_message_code', 'Try adjusting your search or filter criteria'),
  'noMatchingRecords' => $t('no_matching_records_code', 'No Matching Records'),
  'noMatchingMessage' => $t('no_matching_message_code', 'No results match your current filters.')
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
</script>
<script src="modules/paymentVoucher/js/paymentVoucher.js?v=<?=time()?>"></script>
