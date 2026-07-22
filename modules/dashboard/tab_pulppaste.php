<!-- ===== PULP & PASTE TAB ===== -->
<div class="tab-pane fade" id="tabPulpPaste">

  <!-- Filters -->
  <div class="row dash-tab-filters">
    <div class="form-group col-12 col-md-3">
      <label><?=$languageArray['status_code'][$language]?></label>
      <select class="form-control select2" id="ppType">
        <option value=""><?=$languageArray['all_code'][$language]?></option>
        <option value="INCOMING"><?=$languageArray['incoming_code'][$language]?></option>
        <option value="OUTGOING"><?=$languageArray['outgoing_code'][$language]?></option>
      </select>
    </div>
    <div class="form-group col-12 col-md-3" id="ppSupplierWrap" style="display:none;">
      <label><?=$languageArray['supplier_code'][$language]?></label>
      <select class="form-control select2" id="ppSupplier">
        <option value=""><?=$languageArray['all_code'][$language]?> <?=$languageArray['supplier_code'][$language]?></option>
        <?php while ($row = mysqli_fetch_assoc($suppliers2)) { ?>
          <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['supplier_name']) ?></option>
        <?php } ?>
      </select>
    </div>
    <div class="form-group col-12 col-md-3" id="ppCustomerWrap" style="display:none;">
      <label><?=$languageArray['customer_code'][$language]?></label>
      <select class="form-control select2" id="ppCustomer">
        <option value=""><?=$languageArray['all_code'][$language]?> <?=$languageArray['customer_code'][$language]?></option>
        <?php while ($row = mysqli_fetch_assoc($customers2)) { ?>
          <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['customer_name']) ?></option>
        <?php } ?>
      </select>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="row mb-3">
    <div class="col-6 col-md-3 mb-3" id="ppIncomingCard">
      <div class="dash-stat-card" style="background:linear-gradient(135deg,#fd7e14,#e55a00);">
        <div class="stat-label"><?=$languageArray['incoming_code'][$language]?><br><?=$languageArray['total_weight_code'][$language]?></div>
        <div class="stat-value" id="ppIncomingWeight">—</div>
        <div class="stat-sub"><span id="ppIncomingCount">—</span> records | kg</div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3" id="ppOutgoingCard">
      <div class="dash-stat-card" style="background:linear-gradient(135deg,#20c997,#12876f);">
        <div class="stat-label"><?=$languageArray['outgoing_code'][$language]?><br><?=$languageArray['total_weight_code'][$language]?></div>
        <div class="stat-value" id="ppOutgoingWeight">—</div>
        <div class="stat-sub"><span id="ppOutgoingCount">—</span> records | kg</div>
      </div>
    </div>
  </div>

  <!-- Supplier / Customer Breakdowns -->
  <div class="row">
    <div class="col-12 col-md-6 mb-3" id="ppSupplierBreakdownWrap">
      <div class="card dash-section-card">
        <div class="card-header" onclick="toggleCard('ppSupplierBody','ppSupplierChevron')">
          <div class="d-flex align-items-center flex-1">
            <i class="fas fa-chevron-down dash-chevron" id="ppSupplierChevron"></i>
            <span class="section-title mb-0"><?=$languageArray['weight_code'][$language]?> by <?=$languageArray['supplier_code'][$language]?> (kg)</span>
          </div>
          <div class="dash-pager" id="ppSupplierPager" style="display:none;">
            <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();ppSupplierPage(-1)"><i class="fas fa-chevron-left"></i></button>
            <small id="ppSupplierPageInfo"></small>
            <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();ppSupplierPage(1)"><i class="fas fa-chevron-right"></i></button>
          </div>
        </div>
        <div class="card-body" id="ppSupplierBody">
          <div id="ppSupplierBreakdown"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3" id="ppCustomerBreakdownWrap">
      <div class="card dash-section-card">
        <div class="card-header" onclick="toggleCard('ppCustomerBody','ppCustomerChevron')">
          <div class="d-flex align-items-center flex-1">
            <i class="fas fa-chevron-down dash-chevron" id="ppCustomerChevron"></i>
            <span class="section-title mb-0"><?=$languageArray['weight_code'][$language]?> by <?=$languageArray['customer_code'][$language]?> (kg)</span>
          </div>
          <div class="dash-pager" id="ppCustomerPager" style="display:none;">
            <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();ppCustomerPage(-1)"><i class="fas fa-chevron-left"></i></button>
            <small id="ppCustomerPageInfo"></small>
            <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();ppCustomerPage(1)"><i class="fas fa-chevron-right"></i></button>
          </div>
        </div>
        <div class="card-body" id="ppCustomerBody">
          <div id="ppCustomerBreakdown"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Volume Trend -->
  <div class="row mb-3">
    <div class="col-12">
      <div class="card dash-section-card">
        <div class="card-header" onclick="toggleCard('ppTrendBody','ppTrendChevron')">
          <div class="d-flex align-items-center">
            <i class="fas fa-chevron-down dash-chevron" id="ppTrendChevron"></i>
            <span class="section-title mb-0"><?=$languageArray['volume_trending_code'][$language] ?? 'Volume Trending'?> (kg)</span>
          </div>
        </div>
        <div class="card-body" id="ppTrendBody">
          <div class="dash-chart-wrap"><canvas id="ppTrendChart"></canvas></div>
        </div>
      </div>
    </div>
  </div>

</div>
