<!-- ===== PACKAGING TAB ===== -->
<div class="tab-pane fade" id="tabPackaging">

  <!-- Filters -->
  <div class="row dash-tab-filters">
    <div class="form-group col-12 col-md-3">
      <label><?=$languageArray['production_line_code'][$language] ?? 'Production Line'?></label>
      <select class="form-control select2" id="pkgProductionLine">
        <option value=""><?=$languageArray['all_code'][$language]?></option>
        <?php while ($row = mysqli_fetch_assoc($productionLines)) { ?>
          <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['production_line']) ?></option>
        <?php } ?>
      </select>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="row mb-3">
    <div class="col-6 col-md-3 mb-3">
      <div class="dash-stat-card" style="background:linear-gradient(135deg,#007bff,#0056b3);">
        <div class="stat-label"><?=$languageArray['total_weight_code'][$language]?></div>
        <div class="stat-value" id="pkgTotalWeight">—</div>
        <div class="stat-sub"><span id="pkgBatchCount">—</span> batches | kg</div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
      <div class="dash-stat-card" style="background:linear-gradient(135deg,#e83e8c,#c2185b);">
        <div class="stat-label"><?=$languageArray['total_code'][$language]?> <?=$languageArray['boxes_code'][$language]?></div>
        <div class="stat-value" id="pkgTotalBoxes">—</div>
        <div class="stat-sub"><?=$languageArray['boxes_code'][$language]?> packed</div>
      </div>
    </div>
  </div>

  <!-- Product Breakdown -->
  <div class="section-title"><?=$languageArray['weight_code'][$language]?> by Product (kg)</div>
  <div id="pkgProductBreakdown"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>

</div>
