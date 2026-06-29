<?php
require_once 'php/db_connect.php';
require_once 'php/lookup.php';
session_start();

if (!isset($_SESSION['userID'])) {
    echo '<script>window.location.href = "login.html";</script>';
    exit;
}

$company       = $_SESSION['customer'];
$role          = $_SESSION['role'] ?? 'NORMAL';
// Language
$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];

$companyFilter = ($role != 'SADMIN') ? "AND company = '$company'" : '';
$categoryFilter = ($role != 'SADMIN') ? "AND p.customer = '$company'" : '';

$categories = $db->query("SELECT DISTINCT c.id, c.category_name FROM categories c INNER JOIN products p ON p.category = c.id WHERE c.deleted = 0 AND p.deleted = 0 $categoryFilter ORDER BY c.category_name ASC");
$products   = $db->query("SELECT id, product_name, category FROM products WHERE deleted = 0 " . (($role != 'SADMIN') ? "AND customer = '$company'" : '') . " ORDER BY product_name ASC");
$grades     = $db->query("SELECT id, units FROM grades WHERE deleted = 0 " . (($role != 'SADMIN') ? "AND customer = '$company'" : '') . " ORDER BY units ASC");

$totalRaw    = $db->query("SELECT COALESCE(SUM(balance),0) as total FROM raw_stock_balance WHERE deleted=0 $companyFilter")->fetch_assoc()['total'];
$totalGraded = $db->query("SELECT COALESCE(SUM(balance),0) as total FROM grading_stock_balance WHERE deleted=0 $companyFilter")->fetch_assoc()['total'];
$totalBoxes  = $db->query("SELECT COALESCE(SUM(box_quantity),0) as total FROM stock_balances WHERE deleted=0 AND box_quantity > 0 $companyFilter")->fetch_assoc()['total'];

$grandTotal  = floatval($totalRaw) + floatval($totalGraded);
$rawPct      = $grandTotal > 0 ? round((floatval($totalRaw)    / $grandTotal) * 100) : 0;
$gradedPct   = $grandTotal > 0 ? round((floatval($totalGraded) / $grandTotal) * 100) : 0;
$packedPct   = $totalBoxes > 0 ? min(100, round($totalBoxes / 10)) : 0;
?>

<style>
/* ── Font ─────────────────────────────────────────── */
#stockDashboardWrap, #stockDashboardWrap *:not(i):not(.fas):not(.far):not(.fab) {
  font-family: 'Source Sans Pro', Assistant, sans-serif;
}

/* ── Design tokens ─────────────────────────────────────── */
:root {
  --sd-bg: #F0F2F5;
  --sd-card: #FFFFFF;
  --sd-border: #E2E8F0;
  --sd-muted: #64748B;
  --sd-fg: #0F172A;
  --sd-secondary: #F1F5F9;
  --sd-accent: #003392;
  --sd-radius: 10px;
  --sd-blue: #3B82F6;
  --sd-amber: #F59E0B;
  --sd-emerald: #10B981;
}

/* ── Page wrapper ──────────────────────────────────────── */
#stockDashboardWrap {
  box-shadow: 0px 0px 5px 2.5px rgba(255, 255, 255, .5);
  margin-bottom: 25px;
  border: unset;
  border-radius: 5px;
  background: #fff;
}

/* ── Action bar ──────────────────────────────────────── */
.sd-action-bar {
  background: #324C75 !important;
  border: unset;
  padding: 20px 25px;
  border-top-left-radius: 5px;
  border-top-right-radius: 5px;
}

.sd-page-title {
  font-size: 25px;
  line-height: 30px;
  letter-spacing: 0.75px;
  font-weight: 700;
  color: #fff;
  margin-bottom: 0px;
}

.sd-subtitle {
  font-size: 15px;
  line-height: 23px;
  letter-spacing: 0.75px;
  font-weight: 400;
  color: #fff;
  margin-top: 0px;
  margin-bottom: 0px;
}

/* ── Tab switcher ──────────────────────────────────────── */
.sd-tabs {
  display: flex;
  gap: 5px;
  background: #fff;
  border: 1px solid #fff;
  border-radius: 5px;
  padding: 5px;
}

.sd-tab {
  padding: 5px 15px;
  border-radius: 5px;
  font-size: 15px;
  line-height: 23px;
  letter-spacing: 0.75px;
  font-weight: 400;
  border: none;
  background: transparent;
  color: #2f333e;
  cursor: pointer;
  transition: all .15s;
  white-space: nowrap;
}

.sd-tab:hover, .sd-tab.active {
  background: #3fb84e;
  color: #fff;
}

/* ── Refresh Icon Button ─────────────────────────────────────────── */
.custom-refresh-icon-btn {
  background: #FFC000 !important;
  color: #fff !important;
  padding: 13px;
  font-size: 15px;
  line-height: normal;
  letter-spacing: 0.75px;
  font-weight: 700;
  border: unset !important;
  border-radius: 5px;
  box-shadow: unset !important;
}

.custom-refresh-icon-btn:hover {
  background: #FFDE21 !important;
  color: #2f333e !important;
}

/* ── Export Button ─────────────────────────────────────────── */
.custom-export-btn {
  background: #10B981 !important;
  color: #fff !important;
  padding: 10.5px 15px;
  font-size: 15px;
  line-height: 23px;
  letter-spacing: 0.75px;
  font-weight: 700;
  border: unset !important;
  border-radius: 5px;
  box-shadow: unset !important;
  margin: 0px;
}

.custom-export-btn:hover {
  background: #059669 !important;
  color: #fff !important;
}

/* ── Container Fluid ─────────────────────────────────────────── */
.custom-container-fluid {
  padding: 25px;
}

/* ── KPI Cards ─────────────────────────────────────────── */
.sd-kpi {
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 5px;
  padding: 25px;
  display: flex;
  flex-direction: column;
  gap: 15px;
  box-shadow: 0px 0px 5px 0px rgba(0, 0, 0, .5);
  transition: box-shadow .2s;
}

.sd-kpi:hover {
  box-shadow: 0px 0px 5px 0px rgba(63, 184, 78, 1);
}

.sd-kpi-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

.sd-kpi-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 17px;
}

.sd-kpi-icon.blue {
  background: #EFF6FF;
  color: var(--sd-blue);
}

.sd-kpi-icon.amber {
  background: #FFFBEB;
  color: var(--sd-amber);
}

.sd-kpi-icon.emerald {
  background: #ECFDF5;
  color: var(--sd-emerald);
}

.sd-kpi-badge {
  font-size: 12px;
  line-height: 20px;
  letter-spacing: 0.75px;
  font-weight: 700;
  padding: 5px 15px;
  border-radius: 999px;
}

.sd-kpi-badge.blue {
  background: #EFF6FF;
  color: #1D4ED8;
}

.sd-kpi-badge.amber {
  background: #FFFBEB;
  color: #B45309;
}

.sd-kpi-badge.emerald {
  background: #ECFDF5;
  color: #065F46;
}

.sd-kpi-label {
  font-size: 12px;
  line-height: 20px;
  letter-spacing: 0.75px;
  color: #2f333e;
  text-transform: uppercase;
  margin: 0;
}

.sd-kpi-value {
  font-size: 25px;
  line-height: 30px;
  letter-spacing: 0.75px;
  font-weight: 700;
  color: #2f333e;
}

.sd-kpi-unit {
  font-size: 12px;
  line-height: 20px;
  letter-spacing: 0.75px;
  font-weight: 400;
  color: #2f333e;
  margin-left: 5px;
}

.sd-kpi-bar {
  height: 4px;
  border-radius: 999px;
  background: var(--sd-border);
  overflow: hidden;
}

.sd-kpi-bar-fill {
  height: 100%;
  border-radius: 999px;
  transition: width .6s ease;
}

.blue-fill {
  background: var(--sd-blue);
}

.amber-fill {
  background: var(--sd-amber);
}

.emerald-fill {
  background: var(--sd-emerald);
}

/* ── Summary bar ───────────────────────────────────────── */
.sd-summary-bar {
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 5px;
  margin-bottom: 25px;
  padding: 10px 25px;
  box-shadow: 0px 0px 5px 0px rgba(0, 0, 0, .5);
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 15px;
}

.sd-summary-bar-title {
  display: flex;
  align-items: center;
  gap: 5px;
  color: #2f333e;
  font-size: 12px;
  line-height: 20px;
  letter-spacing: 0.75px;
  font-weight: 400;
}

.sd-summary-items {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
}

.sd-summary-item {
  display: flex;
  align-items: center;
  gap: 5px;
}

.sd-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  flex-shrink: 0;
}

.sd-dot.blue {
  background: var(--sd-blue);
}

.sd-dot.amber {
  background: var(--sd-amber);
}

.sd-dot.emerald {
  background: var(--sd-emerald);
}

.sd-summary-item-label {
  color: #2f333e;
  font-size: 12px;
  line-height: 20px;
  letter-spacing: 0.75px;
  font-weight: 400;
}

.sd-summary-item-val {
  color: #2f333e;
  font-size: 12px;
  line-height: 20px;
  letter-spacing: 0.75px;
  font-weight: 700;
}

.sd-mini-bar {
  width: 60px;
  height: 6px;
  border-radius: 999px;
  background: var(--sd-border);
  overflow: hidden;
}

.sd-mini-bar-fill {
  height: 100%;
  border-radius: 999px;
}

.sd-summary-meta {
  margin-left: auto;
  font-size: 11px;
  color: var(--sd-muted);
}

/* ── Stock table card ──────────────────────────────────── */
.sd-table-card {
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 5px;
  box-shadow: 0px 0px 5px 0px rgba(0, 0, 0, .5);
  overflow: hidden;
}

.sd-table-header {
  padding: 10px 25px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.sd-table-header.blue {
  background: rgba(59,130,246,.15);
}

.sd-table-header.amber {
  background: rgba(245,158,11,.15);
}

.sd-table-header.emerald {
  background: rgba(16,185,129,.15);
}

.sd-table-title {
  font-size: 16px;
  line-height: 24px;
  letter-spacing: 0.75px;
  font-weight: 700;
  margin: 0;
}

.sd-table-title.blue {
  color: #2563EB;
}

.sd-table-title.amber {
  color: #B45309;
}

.sd-table-title.emerald {
  color: #065F46;
}

.sd-count-badge {
  margin-left: auto;
  font-size: 16px;
  line-height: 24px;
  letter-spacing: 0.75px;
  font-weight: 400;
  color: #2f333e;
  background: #fff;
  padding: 5px 15px;
  border-radius: 5px;
}

/* ── Filter bar ────────────────────────────────────────── */
.sd-filter-bar {
  padding: 15px 25px;
  background: rgba(222, 226, 230, .15);
}

.sd-search-wrap {
  position: relative;
}

.sd-search-wrap .sd-search-icon {
  position: absolute;
  left: 7.5px;
  top: 50%;
  transform: translateY(-50%);
  color: #2f333e;
  font-size: 10px;
  pointer-events: none;
}

.sd-search-wrap input {
  padding: 5px 10px 5px 25px !important;
  border: 1px solid #243958 !important;
  border-radius: 5px;
  font-size: 15px;
  line-height: 23px;
  letter-spacing: 0.75px;
  font-weight: 400;
  color: #2f333e;
  box-shadow: unset;
  width: 100%;
  transition: border-color .15s, box-shadow .15s;
}

.sd-search-wrap input:focus {
  outline: none;
  border-color: #3fb84e;
}

.sd-filter-bar .select2-container .select2-selection--single {
  height: 31px;
  border-color: var(--sd-border);
}

.sd-filter-bar .select2-container .select2-selection--single .select2-selection__rendered {
  line-height: 29px;
  font-size: 13px;
  padding-left: 10px;
}

.sd-filter-bar .select2-container .select2-selection--single .select2-selection__arrow {
  height: 29px;
  width: 24px;
}

.sd-filter-bar .select2-container .select2-selection--single .select2-selection__arrow b {
  border-width: 6px 5px 0;
}

.sd-filter-bar .select2-container .select2-selection__clear {
  font-size: 16px;
  line-height: 29px;
}

.sd-filter-bar .select2-container .select2-selection__placeholder {
  color: #aaa;
}

.text-sm .select2-container--default .select2-selection--single .select2-selection__rendered, 
select.form-control-sm~.select2-container--default .select2-selection--single .select2-selection__rendered {
  margin-top: 0px;
}

/* ── Table styles ──────────────────────────────────────── */
.sd-section {
  margin-bottom: 25px;
}

.sd-table {
  width: 100%;
  border-collapse: collapse;
}

.table-responsive {
  padding-left: 25px;
  padding-right: 25px;
}

.sd-table tbody tr:hover {
  background: rgba(222, 226, 230, .5);
}

.sd-table tbody td.text-right {
  text-align: right;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
}

.sd-table tfoot td {
  border: 1px solid #dee2e6 !important;
  padding: 10px !important;
  font-size: 15px;
  line-height: 23px;
  letter-spacing: 0.75px;
  font-weight: 700;
  color: #fff;
  background: #243958;
}

.sd-table tfoot td.text-right {
  text-align: right;
  font-variant-numeric: tabular-nums;
}

.sd-product-cell {
  display: flex;
  align-items: center;
  gap: 5px;
}

.sd-grade-badge {
  display: inline-block;
  font-size: 15px;
  line-height: 23px;
  letter-spacing: 0.75px;
  font-weight: 400;
  padding: 5px 10px;
  border-radius: 5px;
  background: #2f333e;
  color: #fff;
}

.sd-empty {
  padding: 40px 16px;
  text-align: center;
  color: var(--sd-muted);
  font-size: 13px;
}

/* ── Pagination ────────────────────────────────────────── */
.sd-pagination {
  padding: 15px 25px;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
}

.sd-pag-info {
  font-size: 16px;
  line-height: 24px;
  letter-spacing: 0.75px;
  font-weight: 400;
  color: #2f333e;
}

.sd-pag-size {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 16px;
  line-height: 24px;
  letter-spacing: 0.75px;
  font-weight: 400;
  color: #2f333e;
}

.sd-pag-size select {
  font-size: 16px;
  line-height: 24px;
  letter-spacing: 0.75px;
  font-weight: 400;
  border: 1px solid #dee2e6;
  border-radius: 5px;
  padding: 5px 15px;
  background: #fff;
  color: #2f333e;
  cursor: pointer;
}

.sd-pag-btns {
  display: flex;
  align-items: center;
  gap: 5px;
}

.sd-pag-btn {
  width: 35px;
  height: 35px;
  border: 1px solid #dee2e6;
  border-radius: 5px;
  background: transparent;
  color: #2f333e;
  cursor: pointer;
  font-size: 16px;
  line-height: 24px;
  letter-spacing: 0.75px;
  font-weight: 400;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all .1s;
}

.sd-pag-btn:hover:not(:disabled) {
  background: #243958;
  color: #fff;
}

.sd-pag-btn:disabled {
  color: rgba(47, 51, 62, .5);
  pointer-events: none;
}

.sd-pag-btn.active {
  background: #243958;
  color: #fff;
}
</style>

<!-- Content Header hidden - title handled in action bar -->
<div class="content-header" style="display:none"></div>

<div id="stockDashboardWrap">

  <!-- Action bar -->
  <div class="sd-action-bar">
    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:12px;">
      <div>
        <h2 class="sd-page-title"><?=$languageArray['dashboard_code'][$language]?></h2>
        <p class="sd-subtitle"><?=$languageArray['realtime_inventory_code'][$language]?></p>
      </div>
      <div class="d-flex align-items-center flex-wrap" style="gap:10px;">
        <div class="sd-tabs">
          <button class="sd-tab active" data-tab="overview"><?=$languageArray['overview_code'][$language]?></button>
          <button class="sd-tab" data-tab="raw"><?=$languageArray['raw_material_code'][$language]?></button>
          <button class="sd-tab" data-tab="graded"><?=$languageArray['graded_code'][$language]?></button>
          <button class="sd-tab" data-tab="packed"><?=$languageArray['packed_code'][$language]?></button>
        </div>
        <button class="btn btn-sm custom-refresh-icon-btn" id="refreshBtn" title="Refresh">
          <i class="fas fa-redo"></i>
        </button>
        <button class="btn btn-sm btn-success custom-export-btn" id="exportBtn">
          <i class="fas fa-file-excel mr-1"></i><?=$languageArray['export_excel_code'][$language]?>
        </button>
      </div>
    </div>
  </div>

  <div class="container-fluid custom-container-fluid">

    <!-- KPI Cards -->
    <div class="row" style="margin-bottom: 25px;">

      <div class="col-12 col-sm-4 mb-3 mb-sm-0">
        <div class="sd-kpi">
          <div class="sd-kpi-top">
            <div class="sd-kpi-icon blue">
              <i class="fas fa-boxes"></i>
            </div>
            <span class="sd-kpi-badge blue" id="kpiBadgeRaw">&#x2191; <?=$languageArray['active_code'][$language]?></span>
          </div>
          <div>
            <p class="sd-kpi-label"><?=$languageArray['total_raw_stock_code'][$language]?></p>
            <div>
              <span class="sd-kpi-value" id="summaryRaw"><?=number_format($totalRaw,2)?></span>
              <span class="sd-kpi-unit">kg</span>
            </div>
          </div>
          <div class="sd-kpi-bar">
            <div class="sd-kpi-bar-fill blue-fill" id="kpiBarRaw" style="width:<?=$rawPct?>%"></div>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-4 mb-3 mb-sm-0">
        <div class="sd-kpi">
          <div class="sd-kpi-top">
            <div class="sd-kpi-icon amber">
              <i class="fas fa-layer-group"></i>
            </div>
            <span class="sd-kpi-badge amber" id="kpiBadgeGraded"><?=$totalGraded > 0 ? '&#x2191; Active' : '&#x2193; Empty'?></span>
          </div>
          <div>
            <p class="sd-kpi-label"><?=$languageArray['total_graded_stock_code'][$language]?></p>
            <div>
              <span class="sd-kpi-value" id="summaryGraded"><?=number_format($totalGraded,2)?></span>
              <span class="sd-kpi-unit">kg</span>
            </div>
          </div>
          <div class="sd-kpi-bar">
            <div class="sd-kpi-bar-fill amber-fill" id="kpiBarGraded" style="width:<?=$gradedPct?>%"></div>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-4">
        <div class="sd-kpi">
          <div class="sd-kpi-top">
            <div class="sd-kpi-icon emerald">
              <i class="fas fa-box"></i>
            </div>
            <span class="sd-kpi-badge emerald" id="kpiBadgePacked"><?=$totalBoxes > 0 ? '&#x2191; Active' : '&#x2193; Empty'?></span>
          </div>
          <div>
            <p class="sd-kpi-label"><?=$languageArray['total_packed_stock_code'][$language]?></p>
            <div>
              <span class="sd-kpi-value" id="summaryPacked"><?=number_format($totalBoxes)?></span>
              <span class="sd-kpi-unit"><?=$languageArray['boxes_code'][$language]?></span>
            </div>
          </div>
          <div class="sd-kpi-bar">
            <div class="sd-kpi-bar-fill emerald-fill" id="kpiBarPacked" style="width:<?=$packedPct?>%"></div>
          </div>
        </div>
      </div>

    </div><!-- /row KPI Cards -->

    <!-- Summary bar -->
    <div class="sd-summary-bar">
      <div class="sd-summary-bar-title">
        <i class="fas fa-chart-bar"></i>
        <span><?=$languageArray['stock_summary_code'][$language]?></span>
      </div>
      <div class="sd-summary-items">
        <div class="sd-summary-item">
          <span class="sd-dot blue"></span>
          <span class="sd-summary-item-label"><?=$languageArray['raw_code'][$language]?></span>
          <span class="sd-summary-item-val" id="summaryBarRaw"><?=number_format($totalRaw,2)?> kg</span>
          <div class="sd-mini-bar d-none d-sm-block">
            <div class="sd-mini-bar-fill blue-fill" style="width:<?=$rawPct?>%"></div>
          </div>
        </div>
        <div class="sd-summary-item">
          <span class="sd-dot amber"></span>
          <span class="sd-summary-item-label"><?=$languageArray['graded_code'][$language]?></span>
          <span class="sd-summary-item-val" id="summaryBarGraded"><?=number_format($totalGraded,2)?> kg</span>
          <div class="sd-mini-bar d-none d-sm-block">
            <div class="sd-mini-bar-fill amber-fill" style="width:<?=$gradedPct?>%"></div>
          </div>
        </div>
        <div class="sd-summary-item">
          <span class="sd-dot emerald"></span>
          <span class="sd-summary-item-label"><?=$languageArray['packed_code'][$language]?></span>
          <span class="sd-summary-item-val" id="summaryBarPacked"><?=number_format($totalBoxes)?> <?=$languageArray['boxes_code'][$language]?></span>
          <div class="sd-mini-bar d-none d-sm-block">
            <div class="sd-mini-bar-fill emerald-fill" style="width:<?=$packedPct?>%"></div>
          </div>
        </div>
      </div>
      <div class="sd-summary-meta d-none d-lg-block" id="summaryMeta"></div>
    </div><!-- /summary bar -->

    <?php
    // Build reusable option strings
    ob_start();
    $categories->data_seek(0);
    while ($r = $categories->fetch_assoc()):
    ?>
      <option value="<?=$r['id']?>"><?=htmlspecialchars($r['category_name'])?></option>
    <?php
    endwhile;
    $catOptions = ob_get_clean();

    ob_start();
    $products->data_seek(0);
    while ($r = $products->fetch_assoc()):
    ?>
      <option value="<?=$r['id']?>" data-category="<?=$r['category']?>"><?=htmlspecialchars($r['product_name'])?></option>
    <?php
    endwhile;
    $prodOptions = ob_get_clean();

    ob_start();
    $grades->data_seek(0);
    while ($r = $grades->fetch_assoc()):
    ?>
      <option value="<?=$r['id']?>"><?=htmlspecialchars($r['units'])?></option>
    <?php
    endwhile;
    $gradeOptions = ob_get_clean();
    ?>

    <!-- RAW MATERIAL TABLE -->
    <div id="sectionRaw" class="sd-section">
      <div class="sd-table-card">
        <div class="sd-table-header blue">
          <span class="sd-dot blue"></span>
          <h3 class="sd-table-title blue"><?=$languageArray['raw_material_stock_code'][$language]?></h3>
          <span class="sd-count-badge" id="rawCount">0 <?=$languageArray['items_code'][$language]?></span>
        </div>
        <div class="sd-filter-bar">
          <div class="row align-items-center g-2">
            <div class="col-12 col-sm-3">
              <div class="sd-search-wrap">
                <i class="fas fa-search sd-search-icon"></i>
                <input type="text" id="rawSearch" placeholder="Search...">
              </div>
            </div>
            <div class="col-6 col-sm-3">
              <select class="form-control form-control-sm select2-filter" id="rawCategoryFilter">
                <option value=""></option>
                <?=$catOptions?>
              </select>
            </div>
            <div class="col-6 col-sm-3">
              <select class="form-control form-control-sm select2-filter" id="rawProductFilter">
                <option value=""></option>
                <?=$prodOptions?>
              </select>
            </div>
            <div class="col-6 col-sm-3">
              <select class="form-control form-control-sm select2-filter" id="rawGradeFilter">
                <option value=""></option>
                <?=$gradeOptions?>
              </select>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="sd-table">
            <thead>
              <tr>
                <th><?=$languageArray['category_code'][$language]?></th>
                <th><?=$languageArray['product_code'][$language]?></th>
                <th><?=$languageArray['grade_code'][$language]?></th>
                <th><?=$languageArray['balance_code'][$language]?> (kg)</th>
              </tr>
            </thead>
            <tbody id="rawBody">
              <tr>
                <td colspan="4" class="sd-empty">
                  <i class="fas fa-spinner fa-spin mr-1"></i><?=$languageArray['loading_code'][$language]?>...
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="3"><?=$languageArray['total_code'][$language]?></td>
                <td class="text-right" id="rawTotal">0.00</td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div class="sd-pagination" id="rawPagination"></div>
      </div>
    </div><!-- /sectionRaw -->

    <!-- GRADED STOCK TABLE -->
    <div id="sectionGraded" class="sd-section">
      <div class="sd-table-card">
        <div class="sd-table-header amber">
          <span class="sd-dot amber"></span>
          <h3 class="sd-table-title amber"><?=$languageArray['graded_stock_code'][$language]?></h3>
          <span class="sd-count-badge" id="gradedCount">0 <?=$languageArray['items_code'][$language]?></span>
        </div>
        <div class="sd-filter-bar">
          <div class="row align-items-center g-2">
            <div class="col-12 col-sm-3">
              <div class="sd-search-wrap">
                <i class="fas fa-search sd-search-icon"></i>
                <input type="text" id="gradedSearch" placeholder="Search...">
              </div>
            </div>
            <div class="col-6 col-sm-3">
              <select class="form-control form-control-sm select2-filter" id="gradedCategoryFilter">
                <option value=""></option>
                <?=$catOptions?>
              </select>
            </div>
            <div class="col-6 col-sm-3">
              <select class="form-control form-control-sm select2-filter" id="gradedProductFilter">
                <option value=""></option>
                <?=$prodOptions?>
              </select>
            </div>
            <div class="col-6 col-sm-3">
              <select class="form-control form-control-sm select2-filter" id="gradedGradeFilter">
                <option value=""></option>
                <?=$gradeOptions?>
              </select>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="sd-table">
            <thead>
              <tr>
                <th><?=$languageArray['category_code'][$language]?></th>
                <th><?=$languageArray['product_code'][$language]?></th>
                <th><?=$languageArray['grade_code'][$language]?></th>
                <th><?=$languageArray['balance_code'][$language]?> (kg)</th>
              </tr>
            </thead>
            <tbody id="gradedBody">
              <tr>
                <td colspan="4" class="sd-empty">
                  <i class="fas fa-spinner fa-spin mr-1"></i><?=$languageArray['loading_code'][$language]?>...
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="3"><?=$languageArray['total_code'][$language]?></td>
                <td class="text-right" id="gradedTotal">0.00</td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div class="sd-pagination" id="gradedPagination"></div>
      </div>
    </div><!-- /sectionGraded -->

    <!-- PACKED STOCK TABLE -->
    <div id="sectionPacked" class="sd-section">
      <div class="sd-table-card">
        <div class="sd-table-header emerald">
          <span class="sd-dot emerald"></span>
          <h3 class="sd-table-title emerald"><?=$languageArray['packed_stock_code'][$language]?></h3>
          <span class="sd-count-badge" id="packedCount">0 <?=$languageArray['items_code'][$language]?></span>
        </div>
        <div class="sd-filter-bar">
          <div class="row align-items-center g-2">
            <div class="col-12 col-sm-3">
              <div class="sd-search-wrap">
                <i class="fas fa-search sd-search-icon"></i>
                <input type="text" id="packedSearch" placeholder="Search...">
              </div>
            </div>
            <div class="col-6 col-sm-3">
              <select class="form-control form-control-sm select2-filter" id="packedCategoryFilter">
                <option value=""></option>
                <?=$catOptions?>
              </select>
            </div>
            <div class="col-6 col-sm-3">
              <select class="form-control form-control-sm select2-filter" id="packedProductFilter">
                <option value=""></option>
                <?=$prodOptions?>
              </select>
            </div>
            <div class="col-6 col-sm-3">
              <select class="form-control form-control-sm select2-filter" id="packedGradeFilter">
                <option value=""></option>
                <?=$gradeOptions?>
              </select>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="sd-table">
            <thead>
              <tr>
                <th><?=$languageArray['category_code'][$language]?></th>
                <th><?=$languageArray['product_code'][$language]?></th>
                <th><?=$languageArray['grade_code'][$language]?></th>
                <th><?=$languageArray['packaging_size_code'][$language]?></th>
                <th><?=$languageArray['balance_code'][$language]?> (<?=$languageArray['boxes_code'][$language]?>)</th>
              </tr>
            </thead>
            <tbody id="packedBody">
              <tr>
                <td colspan="5" class="sd-empty">
                  <i class="fas fa-spinner fa-spin mr-1"></i><?=$languageArray['loading_code'][$language]?>...
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="4"><?=$languageArray['total_code'][$language]?></td>
                <td class="text-right" id="packedTotal">0</td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div class="sd-pagination" id="packedPagination"></div>
      </div>
    </div><!-- /sectionPacked -->

    <div class="text-center" style="font-size: 12px; line-height: 20px; letter-spacing: 0.75px; font-weight: 400; color: #2f333e;">
      <?=$languageArray['stockos_code'][$language]?> &middot; <?=$languageArray['data_refreshed_code'][$language]?> <span id="footerUpdated"></span>
    </div>

  </div><!-- /container-fluid -->
</div><!-- /stockDashboardWrap -->

<script>
/* ── State ─────────────────────────────────────────────── */
var rawData    = [];
var gradedData = [];
var packedData = [];
var rawPage    = 1;
var gradedPage = 1;
var packedPage = 1;
var rawPageSize    = 10;
var gradedPageSize = 10;
var packedPageSize = 10;
var activeTab = 'overview';
$(function() {
  $('.sd-tab').on('click', function() {
    applyTab($(this).data('tab'));
  });

  /* ── Init Select2 ──────────────────────────────────────── */
  $('.select2-filter').each(function() {
    $(this).select2({
      allowClear: true,
      placeholder: 'All',
      width: '100%',
      dropdownParent: $(this).closest('.sd-filter-bar')
    });
  });

  /* ── Search (debounced) ────────────────────────────────── */
  var searchTimer;

  $('#rawSearch').on('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function() { rawPage = 1; renderRaw(); }, 300);
  });

  $('#gradedSearch').on('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function() { gradedPage = 1; renderGraded(); }, 300);
  });

  $('#packedSearch').on('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function() { packedPage = 1; renderPacked(); }, 300);
  });

  /* ── Filter change → reload from server ───────────────── */
  $('#rawCategoryFilter, #rawProductFilter, #rawGradeFilter').on('change', loadRaw);
  $('#gradedCategoryFilter, #gradedProductFilter, #gradedGradeFilter').on('change', loadGraded);
  $('#packedCategoryFilter, #packedProductFilter, #packedGradeFilter').on('change', loadPacked);

  /* ── Refresh ───────────────────────────────────────────── */
  $('#refreshBtn').on('click', function() {
    var $icon = $(this).find('i');
    $icon.addClass('fa-spin');
    $.when(
      $.post('php/stock/getStockDashboard.php', { type: 'raw' },    function(d) { rawData    = JSON.parse(d).data || []; }),
      $.post('php/stock/getStockDashboard.php', { type: 'graded' }, function(d) { gradedData = JSON.parse(d).data || []; }),
      $.post('php/stock/getStockDashboard.php', { type: 'packed' }, function(d) { packedData = JSON.parse(d).data || []; })
    ).then(function() {
      renderRaw();
      renderGraded();
      renderPacked();
      setTimestamp();
      $icon.removeClass('fa-spin');
    });
  });

  /* ── Export Excel ──────────────────────────────────────── */
  $('#exportBtn').on('click', function() {
    window.location.href = 'php/stock/exportStockDashboard.php';
  });

  /* ── Init ──────────────────────────────────────────────── */
  setTimestamp();
  bindCascade('rawCategoryFilter',    'rawProductFilter');
  bindCascade('gradedCategoryFilter', 'gradedProductFilter');
  bindCascade('packedCategoryFilter', 'packedProductFilter');
  applyTab('overview');
  loadRaw();
  loadGraded();
  loadPacked();
});

/* ── Timestamp ─────────────────────────────────────────── */
function setTimestamp() {
  var now = new Date();
  var s = now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) +
          ', ' +
          now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
  $('#lastUpdated, #footerUpdated').text(s);
}

/* ── Tab logic ─────────────────────────────────────────── */
function applyTab(tab) {
  activeTab = tab;
  $('.sd-tab').removeClass('active');
  $('.sd-tab[data-tab="' + tab + '"]').addClass('active');

  if (tab === 'overview') {
    $('#sectionRaw, #sectionGraded, #sectionPacked').show();
  } else if (tab === 'raw') {
    $('#sectionRaw').show();
    $('#sectionGraded, #sectionPacked').hide();
  } else if (tab === 'graded') {
    $('#sectionGraded').show();
    $('#sectionRaw, #sectionPacked').hide();
  } else if (tab === 'packed') {
    $('#sectionPacked').show();
    $('#sectionRaw, #sectionGraded').hide();
  }
}

/* ── Category → Product cascade ───────────────────────── */
function bindCascade(catId, prodId) {
  $('#' + catId).on('change', function() {
    var cat   = $(this).val();
    var $prod = $('#' + prodId);

    $prod.select2('destroy');
    $prod.find('option').each(function() {
      if (!$(this).val()) return;
      $(this).toggle(!cat || $(this).data('category') == cat);
    });
    $prod.val('').select2({
      allowClear: true,
      placeholder: 'All',
      width: '100%',
      dropdownParent: $prod.closest('.sd-filter-bar')
    });
  });
}
/* ── Render helpers ────────────────────────────────────── */
function dotCell(color, text) {
  return '<div class="sd-product-cell"><span class="sd-dot ' + color + '"></span>' + escHtml(text) + '</div>';
}

function gradePill(text) {
  return '<span class="sd-grade-badge">' + escHtml(text) + '</span>';
}

function escHtml(s) {
  return $('<div>').text(s || '').html();
}

/* ── Pagination renderer ───────────────────────────────── */
function renderPagination(containerId, total, page, pageSize, onPage, onSize) {
  var totalPages = Math.max(1, Math.ceil(total / pageSize));
  var from = total === 0 ? 0 : (page - 1) * pageSize + 1;
  var to   = Math.min(page * pageSize, total);

  var html = '<div class="d-flex align-items-center" style="gap: 15px;">' +
              '<span class="sd-pag-info">Showing ' + from + '–' + to + ' of ' + total + '</span>' +
              '<div class="sd-pag-size"><span>Rows:</span>' +
              '<select onchange="(' + onSize.toString() + ')(this.value)">' +
              [5, 10, 25, 50].map(function(s) {
                return '<option value="' + s + '"' + (s === pageSize ? ' selected' : '') + '>' + s + '</option>';
              }).join('') +
              '</select></div></div>';

  var pages = getPageNums(page, totalPages);
  html += '<div class="sd-pag-btns">';
  html += '<button class="sd-pag-btn" onclick="(' + onPage.toString() + ')(1)" ' + (page === 1 ? 'disabled' : '') + '><i class="fas fa-angle-double-left"></i></button>';
  html += '<button class="sd-pag-btn" onclick="(' + onPage.toString() + ')(' + (page - 1) + ')" ' + (page === 1 ? 'disabled' : '') + '><i class="fas fa-angle-left"></i></button>';

  pages.forEach(function(p) {
    if (p === '...') {
      html += '<span class="sd-pag-btn" style="cursor:default">···</span>';
    } else {
      html += '<button class="sd-pag-btn' + (p === page ? ' active' : '') + '" onclick="(' + onPage.toString() + ')(' + p + ')">' + p + '</button>';
    }
  });

  html += '<button class="sd-pag-btn" onclick="(' + onPage.toString() + ')(' + (page + 1) + ')" ' + (page === totalPages ? 'disabled' : '') + '><i class="fas fa-angle-right"></i></button>';
  html += '<button class="sd-pag-btn" onclick="(' + onPage.toString() + ')(' + totalPages + ')" ' + (page === totalPages ? 'disabled' : '') + '><i class="fas fa-angle-double-right"></i></button>';
  html += '</div>';

  $('#' + containerId).html(html);
}

function getPageNums(cur, total) {
  if (total <= 7) {
    var a = [];
    for (var i = 1; i <= total; i++) {
      a.push(i);
    }
    return a;
  }
  var pages = [1];
  if (cur > 3) pages.push('...');
  for (var p = Math.max(2, cur - 1); p <= Math.min(total - 1, cur + 1); p++) {
    pages.push(p);
  }
  if (cur < total - 2) pages.push('...');
  pages.push(total);
  return pages;
}

/* ── Filter helper ─────────────────────────────────────── */
function filterRows(rows, search, cat, prod, grade) {
  return rows.filter(function(r) {
    if (cat   && r.category_id != cat)   return false;
    if (prod  && r.product_id  != prod)  return false;
    if (grade && r.grade       != grade) return false;
    if (search) {
      var s = search.toLowerCase();
      if ((r.product_name  || '').toLowerCase().indexOf(s) === -1 &&
          (r.category_name || '').toLowerCase().indexOf(s) === -1) {
        return false;
      }
    }
    return true;
  });
}

/* ── RAW ───────────────────────────────────────────────── */
function renderRaw() {
  var filtered = filterRows(
    rawData,
    $('#rawSearch').val(),
    $('#rawCategoryFilter').val(),
    $('#rawProductFilter').val(),
    $('#rawGradeFilter').val()
  );

  var total = 0;
  filtered.forEach(function(r) {
    total += parseFloat(r.balance) || 0;
  });

  var paged = filtered.slice((rawPage - 1) * rawPageSize, rawPage * rawPageSize);
  var tbody = $('#rawBody').empty();

  if (!paged.length) {
    tbody.append('<tr><td colspan="4" class="sd-empty">No data available</td></tr>');
  } else {
    paged.forEach(function(r) {
      tbody.append(
        '<tr>' +
        '<td>' + escHtml(r.category_name) + '</td>' +
        '<td>' + dotCell('blue', r.product_name) + '</td>' +
        '<td>' + gradePill(r.grade_name) + '</td>' +
        '<td class="text-right">' + parseFloat(r.balance).toFixed(2) + '</td>' +
        '</tr>'
      );
    });
  }

  $('#rawTotal').text(total.toFixed(2));
  $('#rawCount').text(filtered.length + ' items');
  $('#summaryRaw').text(parseFloat(total).toFixed(2));
  $('#summaryBarRaw').text(parseFloat(total).toFixed(2) + ' kg');

  renderPagination(
    'rawPagination', filtered.length, rawPage, rawPageSize,
    function(p) { rawPage = p; renderRaw(); },
    function(s) { rawPageSize = parseInt(s); rawPage = 1; renderRaw(); }
  );
}

function loadRaw() {
  rawPage = 1;
  $.post('php/stock/getStockDashboard.php', {
    type:     'raw',
    category: $('#rawCategoryFilter').val(),
    product:  $('#rawProductFilter').val(),
    grade:    $('#rawGradeFilter').val()
  }, function(data) {
    var obj = JSON.parse(data);
    rawData = (obj.status === 'success') ? obj.data : [];
    renderRaw();
  });
}

/* ── GRADED ────────────────────────────────────────────── */
function renderGraded() {
  var filtered = filterRows(
    gradedData,
    $('#gradedSearch').val(),
    $('#gradedCategoryFilter').val(),
    $('#gradedProductFilter').val(),
    $('#gradedGradeFilter').val()
  );

  var total = 0;
  filtered.forEach(function(r) {
    total += parseFloat(r.balance) || 0;
  });

  var paged = filtered.slice((gradedPage - 1) * gradedPageSize, gradedPage * gradedPageSize);
  var tbody = $('#gradedBody').empty();

  if (!paged.length) {
    tbody.append('<tr><td colspan="4" class="sd-empty">No data available</td></tr>');
  } else {
    paged.forEach(function(r) {
      tbody.append(
        '<tr>' +
        '<td>' + escHtml(r.category_name) + '</td>' +
        '<td>' + dotCell('amber', r.product_name) + '</td>' +
        '<td>' + gradePill(r.grade_name) + '</td>' +
        '<td class="text-right">' + parseFloat(r.balance).toFixed(2) + '</td>' +
        '</tr>'
      );
    });
  }

  $('#gradedTotal').text(total.toFixed(2));
  $('#gradedCount').text(filtered.length + ' items');
  $('#summaryGraded').text(parseFloat(total).toFixed(2));
  $('#summaryBarGraded').text(parseFloat(total).toFixed(2) + ' kg');

  renderPagination(
    'gradedPagination', filtered.length, gradedPage, gradedPageSize,
    function(p) { gradedPage = p; renderGraded(); },
    function(s) { gradedPageSize = parseInt(s); gradedPage = 1; renderGraded(); }
  );
}

function loadGraded() {
  gradedPage = 1;
  $.post('php/stock/getStockDashboard.php', {
    type:     'graded',
    category: $('#gradedCategoryFilter').val(),
    product:  $('#gradedProductFilter').val(),
    grade:    $('#gradedGradeFilter').val()
  }, function(data) {
    var obj = JSON.parse(data);
    gradedData = (obj.status === 'success') ? obj.data : [];
    renderGraded();
  });
}

/* ── PACKED ────────────────────────────────────────────── */
function renderPacked() {
  var filtered = filterRows(
    packedData,
    $('#packedSearch').val(),
    $('#packedCategoryFilter').val(),
    $('#packedProductFilter').val(),
    $('#packedGradeFilter').val()
  );

  var total = 0;
  filtered.forEach(function(r) {
    total += parseInt(r.box_quantity) || 0;
  });

  var paged = filtered.slice((packedPage - 1) * packedPageSize, packedPage * packedPageSize);
  var tbody = $('#packedBody').empty();

  if (!paged.length) {
    tbody.append('<tr><td colspan="5" class="sd-empty">No data available</td></tr>');
  } else {
    paged.forEach(function(r) {
      tbody.append(
        '<tr>' +
        '<td>' + escHtml(r.category_name) + '</td>' +
        '<td>' + dotCell('emerald', r.product_name) + '</td>' +
        '<td>' + gradePill(r.grade_name) + '</td>' +
        '<td>' + escHtml(r.packaging_name) + '</td>' +
        '<td class="text-right">' + r.box_quantity + '</td>' +
        '</tr>'
      );
    });
  }

  $('#packedTotal').text(total);
  $('#packedCount').text(filtered.length + ' items');
  $('#summaryPacked').text(total);
  $('#summaryBarPacked').text(total + ' boxes');

  renderPagination(
    'packedPagination', filtered.length, packedPage, packedPageSize,
    function(p) { packedPage = p; renderPacked(); },
    function(s) { packedPageSize = parseInt(s); packedPage = 1; renderPacked(); }
  );
}

function loadPacked() {
  packedPage = 1;
  $.post('php/stock/getStockDashboard.php', {
    type:     'packed',
    category: $('#packedCategoryFilter').val(),
    product:  $('#packedProductFilter').val(),
    grade:    $('#packedGradeFilter').val()
  }, function(data) {
    var obj = JSON.parse(data);
    packedData = (obj.status === 'success') ? obj.data : [];
    renderPacked();
  });
}
</script>

