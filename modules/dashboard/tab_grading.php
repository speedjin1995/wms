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

  <!-- Grade Distribution -->
  <h6 class="dash-section-header"><?=$languageArray['grade_distribution_code'][$language]?></h6>
  <div class="row" id="grGradeRow">
    <div class="col-12 col-md-6 mb-3" id="grGradeRecvWrap">
      <div class="card h-100 dash-section-card">
        <div class="card-header" onclick="toggleCard('grGradeRecvBody','grGradeRecvChevron')">
          <div class="d-flex align-items-center flex-1">
            <i class="fas fa-chevron-down dash-chevron" id="grGradeRecvChevron"></i>
            <span class="section-title mb-0"><?=$languageArray['grade_distribution_code'][$language]?> &mdash; <?=$languageArray['receiving_code'][$language]?></span>
          </div>
          <div class="d-flex align-items-center" style="gap:8px;flex-shrink:0;">
            <span class="text-muted dash-meta-text" id="grGradeRecvTotal"></span>
            <div class="dash-pager" id="grGradeRecvPager" style="display:none;">
              <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();grGradeRecvPageFn(-1)"><i class="fas fa-chevron-left"></i></button>
              <small id="grGradeRecvPageInfo"></small>
              <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();grGradeRecvPageFn(1)"><i class="fas fa-chevron-right"></i></button>
            </div>
          </div>
        </div>
        <div class="card-body" id="grGradeRecvBody">
          <div id="grGradeRecvPills" class="grade-pills-wrap"></div>
          <div id="grGradeRecvBars"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3" id="grGradeGrdWrap">
      <div class="card h-100 dash-section-card">
        <div class="card-header" onclick="toggleCard('grGradeGrdBody','grGradeGrdChevron')">
          <div class="d-flex align-items-center flex-1">
            <i class="fas fa-chevron-down dash-chevron" id="grGradeGrdChevron"></i>
            <span class="section-title mb-0"><?=$languageArray['grade_distribution_code'][$language]?> &mdash; <?=$languageArray['grading_code'][$language]?></span>
          </div>
          <div class="d-flex align-items-center" style="gap:8px;flex-shrink:0;">
            <span class="text-muted dash-meta-text" id="grGradeGrdTotal"></span>
            <div class="dash-pager" id="grGradeGrdPager" style="display:none;">
              <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();grGradeGrdPageFn(-1)"><i class="fas fa-chevron-left"></i></button>
              <small id="grGradeGrdPageInfo"></small>
              <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();grGradeGrdPageFn(1)"><i class="fas fa-chevron-right"></i></button>
            </div>
          </div>
        </div>
        <div class="card-body" id="grGradeGrdBody">
          <div id="grGradeGrdPills" class="grade-pills-wrap"></div>
          <div id="grGradeGrdBars"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
        </div>
      </div>
    </div>
  </div>

</div>
