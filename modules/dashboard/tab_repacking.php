<!-- ===== REPACKING TAB ===== -->
<div class="tab-pane fade" id="tabRepacking">

  <!-- Summary Cards -->
  <h6 class="dash-section-header"><?=$languageArray['summary_code'][$language]?></h6>
  <div class="row mb-3">
    <div class="col-6 col-md-3 mb-3">
      <div class="dash-stat-card h-100" style="background:linear-gradient(135deg,#6f42c1,#59359a);">
        <div class="stat-label"><?=$languageArray['repacking_code'][$language]?><br><?=$languageArray['total_weight_code'][$language]?></div>
        <div class="stat-value" id="rpTotalWeight">—</div>
        <div class="stat-sub"><span id="rpRecordCount">—</span> records | kg</div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
      <div class="dash-stat-card h-100" style="background:linear-gradient(135deg,#17a2b8,#138496);">
        <div class="stat-label"><?=$languageArray['repacking_code'][$language]?><br><?=$languageArray['local_code'][$language]?></div>
        <div class="stat-value" id="rpLocalWeight">—</div>
        <div class="stat-sub">kg</div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
      <div class="dash-stat-card h-100" style="background:linear-gradient(135deg,#20c997,#17a589);">
        <div class="stat-label"><?=$languageArray['repacking_code'][$language]?><br><?=$languageArray['export_code'][$language]?></div>
        <div class="stat-value" id="rpExportWeight">—</div>
        <div class="stat-sub">kg</div>
      </div>
    </div>
  </div>

  <!-- Repacking Breakdown -->
  <h6 class="dash-section-header"><?=$languageArray['repacking_code'][$language]?> <?=$languageArray['breakdown_code'][$language]?></h6>
  <div class="row">
    <div class="col-12 mb-3">
      <div class="card h-100 dash-section-card">
        <div class="card-header" onclick="toggleCard('rpBreakdownBody','rpBreakdownChevron')">
          <div class="d-flex align-items-center flex-1">
            <i class="fas fa-chevron-down dash-chevron" id="rpBreakdownChevron"></i>
            <span class="section-title mb-0"><?=$languageArray['source_product_code'][$language]?> &rarr; <?=$languageArray['target_product_code'][$language] ?? 'Target Product'?> (kg)</span>
          </div>
        </div>
        <div class="card-body" id="rpBreakdownBody">
          <div id="rpBreakdown"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
        </div>
      </div>
    </div>
  </div>

</div>
