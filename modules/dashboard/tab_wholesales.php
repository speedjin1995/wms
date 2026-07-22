<!-- ===== WHOLESALES TAB ===== -->
<div class="tab-pane fade show active" id="tabWholesales">

  <!-- Filters -->
  <div class="row dash-tab-filters">
    <div class="form-group col-12 col-md-3">
      <label><?=$languageArray['status_code'][$language]?></label>
      <select class="form-control select2" id="wsType">
        <option value=""><?=$languageArray['all_code'][$language]?></option>
        <option value="RECEIVING"><?=$languageArray['receiving_code'][$language]?></option>
        <option value="DISPATCH"><?=$languageArray['dispatch_code'][$language]?></option>
      </select>
    </div>
    <div class="form-group col-12 col-md-3" id="wsSupplierWrap" style="display:none;">
      <label><?=$languageArray['supplier_code'][$language]?></label>
      <select class="form-control select2" id="wsSupplier">
        <option value=""><?=$languageArray['all_code'][$language]?> <?=$languageArray['supplier_code'][$language]?></option>
        <?php while ($row = mysqli_fetch_assoc($suppliers)) { ?>
          <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['supplier_name']) ?></option>
        <?php } ?>
      </select>
    </div>
    <div class="form-group col-12 col-md-3" id="wsCustomerWrap" style="display:none;">
      <label><?=$languageArray['customer_code'][$language]?></label>
      <select class="form-control select2" id="wsCustomer">
        <option value=""><?=$languageArray['all_code'][$language]?> <?=$languageArray['customer_code'][$language]?></option>
        <?php while ($row = mysqli_fetch_assoc($customers)) { ?>
          <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['customer_name']) ?></option>
        <?php } ?>
      </select>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="row mb-3" id="wsCards">
    <div class="col-6 col-md-3 mb-3" id="wsReceivingCard">
      <div class="dash-stat-card h-100" style="background:linear-gradient(135deg,#17a2b8,#138496);">
        <div class="stat-label"><?=$languageArray['receiving_code'][$language]?><br><?=$languageArray['total_weight_code'][$language]?></div>
        <div class="stat-value" id="wsReceivingWeight">—</div>
        <div class="stat-sub"><span id="wsReceivingCount">—</span> records | kg</div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3" id="wsReceivingValueCard">
      <div class="dash-stat-card h-100" style="background:linear-gradient(135deg,#0d6efd,#0a58ca);">
        <div class="stat-label"><?=$languageArray['receiving_code'][$language]?><br><?=$languageArray['total_value_code'][$language]?></div>
        <div class="stat-value" id="wsReceivingValue">—</div>
        <div class="stat-sub"><?=$languageArray['total_value_code'][$language]?></div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3" id="wsDispatchCard">
      <div class="dash-stat-card h-100" style="background:linear-gradient(135deg,#28a745,#1e7e34);">
        <div class="stat-label"><?=$languageArray['dispatch_code'][$language]?><br><?=$languageArray['total_weight_code'][$language]?></div>
        <div class="stat-value" id="wsDispatchWeight">—</div>
        <div class="stat-sub"><span id="wsDispatchCount">—</span> records | kg</div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3" id="wsDispatchValueCard">
      <div class="dash-stat-card h-100" style="background:linear-gradient(135deg,#fd7e14,#e55a00);">
        <div class="stat-label"><?=$languageArray['dispatch_code'][$language]?><br><?=$languageArray['total_value_code'][$language]?></div>
        <div class="stat-value" id="wsDispatchValue">—</div>
        <div class="stat-sub"><?=$languageArray['total_value_code'][$language]?></div>
      </div>
    </div>
  </div>

  <!-- Supplier / Customer Breakdowns -->
  <div class="row">
    <div class="col-12 col-md-6 mb-3" id="wsSupplierBreakdownWrap">
      <div class="card h-100 dash-section-card">
        <div class="card-header" onclick="toggleCard('wsSupplierBody','wsSupplierChevron')">
          <div class="d-flex align-items-center flex-1">
            <i class="fas fa-chevron-down dash-chevron" id="wsSupplierChevron"></i>
            <span class="section-title mb-0"><?=$languageArray['weight_code'][$language]?> by <?=$languageArray['supplier_code'][$language]?> (kg)</span>
          </div>
          <div class="dash-pager" id="wsSupplierPager" style="display:none;">
            <button class="btn btn-sm btn-outline-secondary" id="wsSupplierPrev" onclick="event.stopPropagation();wsSupplierPage(-1)"><i class="fas fa-chevron-left"></i></button>
            <small id="wsSupplierPageInfo"></small>
            <button class="btn btn-sm btn-outline-secondary" id="wsSupplierNext" onclick="event.stopPropagation();wsSupplierPage(1)"><i class="fas fa-chevron-right"></i></button>
          </div>
        </div>
        <div class="card-body" id="wsSupplierBody">
          <div id="wsSupplierBreakdown"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3" id="wsCustomerBreakdownWrap">
      <div class="card h-100 dash-section-card">
        <div class="card-header" onclick="toggleCard('wsCustomerBody','wsCustomerChevron')">
          <div class="d-flex align-items-center flex-1">
            <i class="fas fa-chevron-down dash-chevron" id="wsCustomerChevron"></i>
            <span class="section-title mb-0"><?=$languageArray['weight_code'][$language]?> by <?=$languageArray['customer_code'][$language]?> (kg)</span>
          </div>
          <div class="dash-pager" id="wsCustomerPager" style="display:none;">
            <button class="btn btn-sm btn-outline-secondary" id="wsCustomerPrev" onclick="event.stopPropagation();wsCustomerPage(-1)"><i class="fas fa-chevron-left"></i></button>
            <small id="wsCustomerPageInfo"></small>
            <button class="btn btn-sm btn-outline-secondary" id="wsCustomerNext" onclick="event.stopPropagation();wsCustomerPage(1)"><i class="fas fa-chevron-right"></i></button>
          </div>
        </div>
        <div class="card-body" id="wsCustomerBody">
          <div id="wsCustomerBreakdown"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Grade Distribution -->
  <div class="row">
    <div class="col-12 col-md-6 mb-3" id="wsGradeRecvWrap">
      <div class="card h-100 dash-section-card">
        <div class="card-header" onclick="toggleCard('wsGradeRecvBody','wsGradeRecvChevron')">
          <div class="d-flex align-items-center flex-1">
            <i class="fas fa-chevron-down dash-chevron" id="wsGradeRecvChevron"></i>
            <span class="section-title mb-0"><?=$languageArray['grade_distribution_code'][$language]?> &mdash; <?=$languageArray['receiving_code'][$language]?></span>
          </div>
          <span class="text-muted" style="font-size:12px;flex-shrink:0;" id="wsGradeRecvTotal"></span>
        </div>
        <div class="card-body" id="wsGradeRecvBody">
          <div id="wsGradeRecvPills" class="grade-pills-wrap"></div>
          <div id="wsGradeRecvBars"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3" id="wsGradeDispWrap">
      <div class="card h-100 dash-section-card">
        <div class="card-header" onclick="toggleCard('wsGradeDispBody','wsGradeDispChevron')">
          <div class="d-flex align-items-center flex-1">
            <i class="fas fa-chevron-down dash-chevron" id="wsGradeDispChevron"></i>
            <span class="section-title mb-0"><?=$languageArray['grade_distribution_code'][$language]?> &mdash; <?=$languageArray['dispatch_code'][$language]?></span>
          </div>
          <span class="text-muted" style="font-size:12px;flex-shrink:0;" id="wsGradeDispTotal"></span>
        </div>
        <div class="card-body" id="wsGradeDispBody">
          <div id="wsGradeDispPills" class="grade-pills-wrap"></div>
          <div id="wsGradeDispBars"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Hourly Distribution -->
  <div class="row" id="wsHourlyWrap">
    <div class="col-12 col-md-6 mb-3" id="wsHourlyRecvWrap">
      <div class="card h-100 dash-section-card">
        <div class="card-header" onclick="toggleCard('wsHourlyRecvBody','wsHourlyRecvChevron')">
          <div class="d-flex align-items-center">
            <i class="fas fa-chevron-down dash-chevron" id="wsHourlyRecvChevron"></i>
            <span class="section-title mb-0"><?=$languageArray['receiving_code'][$language]?> by Hour (kg)</span>
          </div>
        </div>
        <div class="card-body" id="wsHourlyRecvBody">
          <div class="dash-chart-wrap"><canvas id="wsHourlyRecvChart"></canvas></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3" id="wsHourlyDispWrap">
      <div class="card h-100 dash-section-card">
        <div class="card-header" onclick="toggleCard('wsHourlyDispBody','wsHourlyDispChevron')">
          <div class="d-flex align-items-center">
            <i class="fas fa-chevron-down dash-chevron" id="wsHourlyDispChevron"></i>
            <span class="section-title mb-0"><?=$languageArray['dispatch_code'][$language]?> by Hour (kg)</span>
          </div>
        </div>
        <div class="card-body" id="wsHourlyDispBody">
          <div class="dash-chart-wrap"><canvas id="wsHourlyDispChart"></canvas></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Volume Trend -->
  <div class="row mb-3" id="wsTrendWrap">
    <div class="col-12">
      <div class="card dash-section-card">
        <div class="card-header" onclick="toggleCard('wsTrendBody','wsTrendChevron')">
          <div class="d-flex align-items-center">
            <i class="fas fa-chevron-down dash-chevron" id="wsTrendChevron"></i>
            <span class="section-title mb-0"><?=$languageArray['volume_trending_code'][$language]?></span>
          </div>
        </div>
        <div class="card-body" id="wsTrendBody">
          <div class="dash-chart-wrap"><canvas id="wsTrendChart"></canvas></div>
        </div>
      </div>
    </div>
  </div>

</div>
