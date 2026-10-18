<?php
require_once '../../php/db_connect.php';
require_once '../../php/lookup.php';

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
} else {
  $user    = $_SESSION['userID'];
  $company = $_SESSION['customer'];
  $role = $_SESSION['role'] ?? 'NORMAL';
  $userAllowPrice = $_SESSION['userAllowPrice'] ?? 'N';
  $userModuleAccess = $_SESSION['userModuleAccess'];
  $categoryIds = [];
  if (!empty($userModuleAccess['categories'])) {
    $allowedModules = ['wholesale', 'processing'];
    foreach ($userModuleAccess['categories'] as $module => $moduleCategories) {
      if (in_array($module, $allowedModules)) {
        $categoryIds = array_merge($categoryIds, $moduleCategories);
      }
    }
    $categoryIds = array_unique($categoryIds);
  }

  if ($role != 'SADMIN') {
    $categoryFilter = !empty($categoryIds) ? " AND c.id IN (" . implode(',', array_map('intval', $categoryIds)) . ")" : "";
    $categories = $db->query("SELECT * FROM categories c WHERE c.deleted = '0' AND c.customer = '$company' AND c.module IN ('wholesale', 'processing')$categoryFilter ORDER BY c.category_name ASC");
    $categories2 = $db->query("SELECT * FROM categories c WHERE c.deleted = '0' AND c.customer = '$company' AND c.module IN ('wholesale', 'processing')$categoryFilter ORDER BY c.category_name ASC");
    $productQuery = "SELECT p.* FROM products p INNER JOIN categories c ON p.category = c.id WHERE p.deleted = '0' AND p.customer = '$company' AND c.module IN ('wholesale', 'processing') AND c.deleted = '0'$categoryFilter ORDER BY p.product_name ASC";    
    $productCheck = $db->query($productQuery);
    if ($productCheck->num_rows == 0) {
      $productQuery = "SELECT * FROM products WHERE deleted = '0' AND customer = '$company' ORDER BY product_name ASC";
    }
    $products = $db->query($productQuery);
    $products2 = $db->query($productQuery);
    $grades = $db->query("SELECT DISTINCT g.*, p.product_name FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY p.product_name ASC, g.units ASC");
    $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' AND customer = '$company' ORDER BY locations ASC");
  } else {
    $categories = $db->query("SELECT * FROM categories WHERE deleted = '0' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $categories2 = $db->query("SELECT * FROM categories WHERE deleted = '0' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' ORDER BY locations ASC");
    $products  = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $products2  = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $grades = $db->query("SELECT DISTINCT g.*, p.product_name FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC");
  }

  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>

<div class="content page-modern">
  <div class="container-fluid">

    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title"><i class="fas fa-boxes"></i> <?=$languageArray['stock_balance_code'][$language]?></h1>
    </div>

    <div class="tab-content">
      <!-- ── Tab 1: Stock Balance Report ── -->
      <div class="tab-pane fade show active" id="paneReport">

        <!-- Filter Card -->
        <div class="card filter-card">
          <div class="card-body">
            <div class="filter-row">
              <div class="filter-group">
                <label class="filter-label"><?=$languageArray['date_code'][$language]?></label>
                <div class="input-group date" id="datePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#datePicker" id="date"/>
                  <div class="input-group-append" data-target="#datePicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>

              <div class="filter-group">
                <label class="filter-label"><?=$languageArray['locations_code'][$language]?></label>
                <select class="form-control select2" id="locationFilter">
                  <option value="">-</option>
                  <?php while($row = mysqli_fetch_assoc($locations)) { ?>
                    <option value="<?=$row['id']?>"><?=$row['locations']?></option>
                  <?php } ?>
                </select>
              </div>

              <div class="filter-group">
                <label class="filter-label"><?=$languageArray['category_code'][$language]?></label>
                <select class="form-control select2" id="categoryFilter">
                  <option value="">-</option>
                  <?php while($row = mysqli_fetch_assoc($categories)) { ?>
                    <option value="<?=$row['id']?>"><?=$row['category_name']?></option>
                  <?php } ?>
                </select>
              </div>

              <div class="filter-group">
                <label class="filter-label"><?=$languageArray['product_code'][$language]?></label>
                <select class="form-control select2" id="productFilter">
                  <option value="">-</option>
                  <?php while($row = mysqli_fetch_assoc($products)) { ?>
                    <option value="<?=$row['id']?>" data-category="<?=$row['category']?>"><?=$row['product_name']?></option>
                  <?php } ?>
                </select>
              </div>


              <div class="filter-group filter-group-action" style="margin-left:auto;">
                <label class="filter-label">&nbsp;</label>
                <div class="d-flex" style="gap:0.5rem;">
                  <button type="button" class="btn btn-filter btn-filter-secondary" id="refreshBtn">
                    <i class="fas fa-sync-alt"></i> <?=$languageArray['refresh_code'][$language]?>
                  </button>
                  <button type="button" class="btn btn-filter btn-filter-primary" id="exportBtn">
                    <i class="fas fa-file-pdf"></i> <?=$languageArray['export_pdf_code'][$language]?>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Results Card -->
        <div class="card results-card">
          <div class="card-header">
            <div class="results-header-left">
              <h3 class="results-title"><i class="fas fa-file-alt"></i> <?=$languageArray['preview_code'][$language]?></h3>
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
