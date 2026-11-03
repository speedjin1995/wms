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
$t = function ($key, $default = '') use ($languageArray, $language) { return $languageArray[$key][$language] ?? $default; };
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
            <label class="filter-label"><?=$t('date_code', 'Date')?></label>
            <div class="input-group date" id="doReportDatePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#doReportDatePicker" id="doReportDate"/>
              <div class="input-group-append" data-target="#doReportDatePicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>
          <div class="filter-group">
            <label class="filter-label"><?=$t('supplier_code', 'Supplier')?></label>
            <select class="form-control" id="doReportSupplier">
              <option value=""><?=$t('all_suppliers_code', 'All Suppliers')?></option>
            </select>
          </div>
          <div class="filter-group filter-group-action ml-auto">
            <label class="filter-label">&nbsp;</label>
            <div class="d-flex">
              <button type="button" class="btn btn-filter btn-filter-primary" id="doReportSearchBtn">
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
          <h3 class="results-title"><i class="fas fa-file-alt mr-2"></i><?=$t('do_receiving_report_code', 'Daily Receiving Report')?></h3>
        </div>
        <div class="results-header-right d-flex" style="gap:0.5rem;">
          <button type="button" class="btn btn-action btn-action-warning" id="doReportPdfBtn">
            <i class="fas fa-file-pdf"></i> <?=$t('export_pdf_code', 'Export PDF')?>
          </button>
          <button type="button" class="btn btn-action btn-action-success" id="doReportExcelBtn">
            <i class="fas fa-file-excel"></i> <?=$t('export_excel_code', 'Export Excel')?>
          </button>
        </div>
      </div>
      <div class="card-body">
        <table class="table data-table" id="doReportTable">
          <thead>
            <tr>
              <th><?=$t('supplier_code', 'Supplier')?></th>
              <th><?=$t('serial_no_code', 'Serial No')?></th>
              <th><?=$t('category_code', 'Category')?></th>
              <th><?=$t('product_code', 'Product')?></th>
              <th><?=$t('grade_code', 'Grade')?></th>
              <th class="text-right"><?=$t('received_weight_code', 'Received Weight')?></th>
              <th class="text-right"><?=$t('do_weight_code', 'DO Weight')?></th>
              <th class="text-right"><?=$t('weight_loss_code', 'Weight Loss')?></th>
              <th class="text-right">%</th>
            </tr>
          </thead>
          <tfoot>
            <tr style="background:#f8fafc;">
              <th><?=$t('total_code', 'Total')?></th>
              <th></th>
              <th></th>
              <th></th>
              <th></th>
              <th class="text-right"></th>
              <th class="text-right"></th>
              <th class="text-right text-primary"></th>
              <th class="text-right text-primary"></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

  </div>
</div>

<style>
  #doReportTable tr.do-group-header td { background: #eef2f7; font-weight: 600; }
  #doReportTable tr.do-group-subtotal td { font-weight: 600; border-bottom: 2px solid #e2e8f0; }
</style>

<script>
var doReportTableLanguage = {
  'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
  'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
};
var doReportText = <?=json_encode([
  'doNo' => $t('do_no_code', 'DO No'),
  'subtotal' => $t('subtotal_code', 'Subtotal')
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
</script>
<script src="modules/deliveryOrder/js/doReport.js?ver=<?=$_SESSION['ver'] ?? '1.0.0'?>"></script>
