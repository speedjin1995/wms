<!-- ===== GRADING TAB ===== -->
<div class="tab-pane fade" id="tabGrading">

  <!-- Summary Cards -->
  <div class="row mb-3">
    <div class="col-6 col-md-3 mb-3">
      <div class="dash-stat-card" style="background:linear-gradient(135deg,#6f42c1,#5a32a3);">
        <div class="stat-label"><?=$languageArray['total_code'][$language]?> <?=$languageArray['net_code'][$language]?><br><?=$languageArray['weight_code'][$language]?></div>
        <div class="stat-value" id="grTotalNet">—</div>
        <div class="stat-sub"><span id="grSessionCount">—</span> sessions | kg</div>
      </div>
    </div>
  </div>

  <!-- Product + Grade Breakdown -->
  <div class="section-title"><?=$languageArray['net_code'][$language]?> <?=$languageArray['weight_code'][$language]?> by Product &amp; <?=$languageArray['grading_code'][$language]?></div>
  <div id="grProductBreakdown"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>

</div>
