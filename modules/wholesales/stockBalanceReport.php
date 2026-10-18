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
    </div><!-- /tab-content -->
  </div>
</div>
<script>
// ============================================================================
// DOCUMENT READY
// ============================================================================
$(function() {
  initCommon();
  initReportTab();
});

function initCommon() {
  $('.select2').select2({ allowClear: true, placeholder: 'Please Select' });
}

function initReportTab() {
  $('#datePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: new Date()
  });

  $('#datePicker').on('change.datetimepicker', loadPreview);
  $('#refreshBtn').on('click', loadPreview);
  $('#exportBtn').on('click', exportReport);
  $('#categoryFilter').on('change', filterProductsByCategory);

  loadPreview();
}

// ============================================================================
// REPORT TAB FUNCTIONS
// ============================================================================
function buildReportUrl() {
  var date = $('#date').val();
  var category = $('#categoryFilter').val() || '';
  var location = $('#locationFilter').val() || '';
  var product = $('#productFilter').val() || '';
  return 'php/modules/wholesales/exportStockBalance.php?asAtDate=' + encodeURIComponent(date) + '&category=' + category + '&location=' + location + '&product=' + product;
}

function loadPreview() {
  var date = $('#date').val();
  if (!date) {
    toastr.error('Please select a date.', 'Validation Error:');
    return;
  }
  $('#previewFrame').attr('src', buildReportUrl());
}

function exportReport() {
  var date = $('#date').val();
  if (!date) {
    toastr.error('Please select a date.', 'Validation Error:');
    return;
  }
  window.open(buildReportUrl());
}

function filterProductsByCategory() {
  var selectedCategory = $(this).val();
  var $productSelect = $('#productFilter');
  var currentVal = $productSelect.val();

  $productSelect.select2('destroy');

  if (!$productSelect.data('original-options')) {
    $productSelect.data('original-options', $productSelect.html());
  }
  $productSelect.html($productSelect.data('original-options'));

  if (selectedCategory) {
    $productSelect.find('option').each(function() {
      if ($(this).val() && $(this).data('category') != selectedCategory) {
        $(this).remove();
      }
    });
  }

  var newVal = (currentVal && $productSelect.find('option[value="' + currentVal + '"]').length) ? currentVal : '';
  $productSelect.val(newVal).select2({ allowClear: true, placeholder: 'Please Select' });
}

// ============================================================================
</script>
<script src="modules/wholesales/js/stockBalanceReport.js?v=<?=time()?>"></script>
