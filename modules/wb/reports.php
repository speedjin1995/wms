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

$wbService = new WeighbridgeService($db, (int)$_SESSION['customer'], (int)$_SESSION['userID'], (string)($_SESSION['role'] ?? ''));
$lookups = $wbService->getLookups();

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

          <div class="filter-group" id="customerFilterDiv" style="display:none;">
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
              <option value="Complete" selected><?=$t('complete_code')?></option>
              <option value="Cancelled"><?=$t('cancelled_code')?></option>
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
          <h3 class="results-title"><i class="fas fa-chart-bar mr-2"></i><?=$t('reports_code')?></h3>
        </div>
        <div class="results-header-right d-flex" style="gap: 0.5rem;">
          <button type="button" class="btn btn-action btn-action-warning" id="exportPdf">
            <i class="fas fa-file-pdf"></i> <?=$t('export_pdf_code')?>
          </button>
          <button type="button" class="btn btn-action btn-action-success" id="exportExcel">
            <i class="fas fa-file-excel"></i> <?=$t('export_excel_code')?>
          </button>
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
            </tr>
          </thead>
        </table>
      </div>
    </div>

  </div>
</div>

<script>
var wbText = <?=json_encode([
  'pleaseSelect' => $t('please_select_code', 'Please Select'),
  'noRecordsFound' => $t('no_records_found_code', 'No Records Found'),
  'noRecordsMessage' => $t('no_records_message_code', 'Try adjusting your search or filter criteria'),
  'noMatchingRecords' => $t('no_matching_records_code', 'No Matching Records'),
  'noMatchingMessage' => $t('no_matching_message_code', 'No results match your current filters. Try different criteria.')
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
</script>
<script src="modules/wb/js/wbCommon.js?v=<?=time()?>"></script>
<script src="modules/wb/js/reports.js?v=<?=time()?>"></script>
