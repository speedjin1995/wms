<!-- ===== WHOLESALES TAB ===== -->
<div class="tab-pane fade show active" id="tabWholesales">
  <!-- Filters -->
  <div class="row dash-tab-filters">
    <div class="form-group col-12 col-md-3">
      <label><?=$languageArray['status_code'][$language]?></label>
      <div class="btn-group btn-group-sm ws-type-group d-flex" role="group">
        <button type="button" class="btn btn-outline-secondary ws-type-btn active" data-value=""><?=$languageArray['all_code'][$language]?></button>
        <button type="button" class="btn btn-outline-info ws-type-btn" data-value="RECEIVING"><?=$languageArray['receiving_code'][$language]?></button>
        <button type="button" class="btn btn-outline-success ws-type-btn" data-value="DISPATCH"><?=$languageArray['dispatch_code'][$language]?></button>
      </div>
      <input type="hidden" id="wsType" value="">
    </div>
    <div class="form-group col-12 col-md-2" id="wsPartyTypeWrap" style="display:none;">
      <label><?=$languageArray['type_code'][$language]?></label>
      <select class="form-control" id="wsPartyType">
        <option value=""><?=$languageArray['all_code'][$language]?></option>
        <option value="Normal">Normal</option>
        <option value="Packing">Packing</option>
      </select>
    </div>
    <div class="form-group col-12 col-md-3" id="wsSupplierWrap" style="display:none;">
      <label><?=$languageArray['supplier_code'][$language]?></label>
      <select class="form-control select2" id="wsSupplier" data-all-label="<?=$languageArray['all_code'][$language]?> <?=$languageArray['supplier_code'][$language]?>">
        <option value=""><?=$languageArray['all_code'][$language]?> <?=$languageArray['supplier_code'][$language]?></option>
        <?php while ($row = mysqli_fetch_assoc($suppliers)) { ?>
          <option value="<?= $row['id'] ?>" data-type="<?= htmlspecialchars($row['supplier_type'] ?? 'Normal') ?>"><?= htmlspecialchars($row['supplier_name']) ?></option>
        <?php } ?>
      </select>
    </div>
    <div class="form-group col-12 col-md-3" id="wsCustomerWrap" style="display:none;">
      <label><?=$languageArray['customer_code'][$language]?></label>
      <select class="form-control select2" id="wsCustomer" data-all-label="<?=$languageArray['all_code'][$language]?> <?=$languageArray['customer_code'][$language]?>">
        <option value=""><?=$languageArray['all_code'][$language]?> <?=$languageArray['customer_code'][$language]?></option>
        <?php while ($row = mysqli_fetch_assoc($customers)) { ?>
          <option value="<?= $row['id'] ?>" data-type="<?= htmlspecialchars($row['customer_type'] ?? 'Normal') ?>"><?= htmlspecialchars($row['customer_name']) ?></option>
        <?php } ?>
      </select>
    </div>
  </div>

  <!-- Summary Cards -->
  <h6 class="dash-section-header"><?=$languageArray['summary_code'][$language]?></h6>
  <div class="row" id="wsCards">
    <div class="col-6 col-md-3" id="wsReceivingCard">
      <div class="dash-stat-card h-100" style="background:linear-gradient(135deg,#17a2b8,#138496);">
        <div class="stat-label"><?=$languageArray['receiving_code'][$language]?><br><?=$languageArray['total_weight_code'][$language]?></div>
        <div class="stat-value" id="wsReceivingWeight">—</div>
        <div class="stat-sub"><span id="wsReceivingCount">—</span> records | kg</div>
      </div>
    </div>
    <div class="col-6 col-md-3" id="wsReceivingValueCard">
      <div class="dash-stat-card h-100" style="background:linear-gradient(135deg,#0d6efd,#0a58ca);">
        <div class="stat-label"><?=$languageArray['receiving_code'][$language]?><br><?=$languageArray['total_value_code'][$language]?></div>
        <div class="stat-value" id="wsReceivingValue">—</div>
        <div class="stat-sub"><?=$languageArray['total_value_code'][$language]?></div>
      </div>
    </div>
    <div class="col-6 col-md-3" id="wsDispatchCard">
      <div class="dash-stat-card h-100" style="background:linear-gradient(135deg,#28a745,#1e7e34);">
        <div class="stat-label"><?=$languageArray['dispatch_code'][$language]?><br><?=$languageArray['total_weight_code'][$language]?></div>
        <div class="stat-value" id="wsDispatchWeight">—</div>
        <div class="stat-sub"><span id="wsDispatchCount">—</span> records | kg</div>
      </div>
    </div>
    <div class="col-6 col-md-3" id="wsDispatchValueCard">
      <div class="dash-stat-card h-100" style="background:linear-gradient(135deg,#fd7e14,#e55a00);">
        <div class="stat-label"><?=$languageArray['dispatch_code'][$language]?><br><?=$languageArray['total_value_code'][$language]?></div>
        <div class="stat-value" id="wsDispatchValue">—</div>
        <div class="stat-sub"><?=$languageArray['total_value_code'][$language]?></div>
      </div>
    </div>
  </div>

  <!-- Supplier Breakdowns (Normal / Packing) -->
  <h6 class="dash-section-header" id="wsSupplierBreakdownHeader"><?=$languageArray['supplier_code'][$language]?> <?=$languageArray['breakdown_code'][$language]?></h6>
  <div class="row" id="wsSupplierBreakdownRow">
    <div class="col-12 col-md-6 mb-3" id="wsSupplierNormalWrap">
      <div class="card h-100 dash-section-card">
        <div class="card-header" onclick="toggleCard('wsSupplierNormalBody','wsSupplierNormalChevron')">
          <div class="d-flex align-items-center flex-1">
            <i class="fas fa-chevron-down dash-chevron" id="wsSupplierNormalChevron"></i>
            <span class="section-title mb-0">Normal <?=$languageArray['supplier_code'][$language]?> (kg)</span>
          </div>
          <div class="d-flex align-items-center" style="gap:8px;">
            <button class="btn btn-sm btn-outline-info" onclick="event.stopPropagation();openExportModal('supplier_normal')" title="Export Excel"><i class="fas fa-file-excel"></i></button>
            <div class="dash-pager" id="wsSupplierNormalPager" style="display:none;">
              <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();wsSupplierNormalPage(-1)"><i class="fas fa-chevron-left"></i></button>
              <small id="wsSupplierNormalPageInfo"></small>
              <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();wsSupplierNormalPage(1)"><i class="fas fa-chevron-right"></i></button>
            </div>
          </div>
        </div>
        <div class="card-body" id="wsSupplierNormalBody">
          <div id="wsSupplierNormalBreakdown"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3" id="wsSupplierPackingWrap">
      <div class="card h-100 dash-section-card">
        <div class="card-header" onclick="toggleCard('wsSupplierPackingBody','wsSupplierPackingChevron')">
          <div class="d-flex align-items-center flex-1">
            <i class="fas fa-chevron-down dash-chevron" id="wsSupplierPackingChevron"></i>
            <span class="section-title mb-0">Packing <?=$languageArray['supplier_code'][$language]?> (kg)</span>
          </div>
          <div class="d-flex align-items-center" style="gap:8px;">
            <button class="btn btn-sm btn-outline-info" onclick="event.stopPropagation();openExportModal('supplier_packing')" title="Export Excel"><i class="fas fa-file-excel"></i></button>
            <div class="dash-pager" id="wsSupplierPackingPager" style="display:none;">
              <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();wsSupplierPackingPage(-1)"><i class="fas fa-chevron-left"></i></button>
              <small id="wsSupplierPackingPageInfo"></small>
              <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();wsSupplierPackingPage(1)"><i class="fas fa-chevron-right"></i></button>
            </div>
          </div>
        </div>
        <div class="card-body" id="wsSupplierPackingBody">
          <div id="wsSupplierPackingBreakdown"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Customer Breakdowns (Normal / Packing) -->
  <h6 class="dash-section-header" id="wsCustomerBreakdownHeader"><?=$languageArray['customer_code'][$language]?> <?=$languageArray['breakdown_code'][$language]?></h6>
  <div class="row" id="wsCustomerBreakdownRow">
    <div class="col-12 col-md-6 mb-3" id="wsCustomerNormalWrap">
      <div class="card h-100 dash-section-card">
        <div class="card-header" onclick="toggleCard('wsCustomerNormalBody','wsCustomerNormalChevron')">
          <div class="d-flex align-items-center flex-1">
            <i class="fas fa-chevron-down dash-chevron" id="wsCustomerNormalChevron"></i>
            <span class="section-title mb-0">Normal <?=$languageArray['customer_code'][$language]?> (kg)</span>
          </div>
          <div class="d-flex align-items-center" style="gap:8px;">
            <button class="btn btn-sm btn-outline-success" onclick="event.stopPropagation();openExportModal('customer_normal')" title="Export Excel"><i class="fas fa-file-excel"></i></button>
            <div class="dash-pager" id="wsCustomerNormalPager" style="display:none;">
              <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();wsCustomerNormalPage(-1)"><i class="fas fa-chevron-left"></i></button>
              <small id="wsCustomerNormalPageInfo"></small>
              <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();wsCustomerNormalPage(1)"><i class="fas fa-chevron-right"></i></button>
            </div>
          </div>
        </div>
        <div class="card-body" id="wsCustomerNormalBody">
          <div id="wsCustomerNormalBreakdown"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3" id="wsCustomerPackingWrap">
      <div class="card h-100 dash-section-card">
        <div class="card-header" onclick="toggleCard('wsCustomerPackingBody','wsCustomerPackingChevron')">
          <div class="d-flex align-items-center flex-1">
            <i class="fas fa-chevron-down dash-chevron" id="wsCustomerPackingChevron"></i>
            <span class="section-title mb-0">Packing <?=$languageArray['customer_code'][$language]?> (kg)</span>
          </div>
          <div class="d-flex align-items-center" style="gap:8px;">
            <button class="btn btn-sm btn-outline-success" onclick="event.stopPropagation();openExportModal('customer_packing')" title="Export Excel"><i class="fas fa-file-excel"></i></button>
            <div class="dash-pager" id="wsCustomerPackingPager" style="display:none;">
              <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();wsCustomerPackingPage(-1)"><i class="fas fa-chevron-left"></i></button>
              <small id="wsCustomerPackingPageInfo"></small>
              <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();wsCustomerPackingPage(1)"><i class="fas fa-chevron-right"></i></button>
            </div>
          </div>
        </div>
        <div class="card-body" id="wsCustomerPackingBody">
          <div id="wsCustomerPackingBreakdown"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Grade Distribution -->
  <h6 class="dash-section-header" id="wsGradeHeader"><?=$languageArray['grade_distribution_code'][$language]?></h6>
  <div class="row custom-breakdown-box" id="wsGradeRow">
    <div class="col-12 col-md-6 custom-breakdown-card" id="wsGradeRecvWrap">
      <div class="card h-100 dash-section-card">
        <div class="card-header" onclick="toggleCard('wsGradeRecvBody','wsGradeRecvChevron')">
          <div class="d-flex align-items-center flex-1">
            <i class="fas fa-chevron-down dash-chevron" id="wsGradeRecvChevron"></i>
            <span class="section-title"><?=$languageArray['grade_distribution_code'][$language]?> &mdash; <?=$languageArray['receiving_code'][$language]?></span>
          </div>
          <div class="d-flex align-items-center" style="gap: 10px; flex-shrink: 0;">
            <button class="btn btn-sm btn-outline-info" onclick="event.stopPropagation();exportGradeDistribution('RECEIVING')" title="Export Excel"><i class="fas fa-file-excel"></i></button>
            <span class="text-muted dash-meta-text" id="wsGradeRecvTotal"></span>
            <div class="dash-pager" id="wsGradeRecvPager" style="display:none;">
              <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();wsGradeRecvPageFn(-1)"><i class="fas fa-chevron-left"></i></button>
              <small id="wsGradeRecvPageInfo"></small>
              <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();wsGradeRecvPageFn(1)"><i class="fas fa-chevron-right"></i></button>
            </div>
          </div>
        </div>
        <div class="card-body" id="wsGradeRecvBody">
          <div id="wsGradeRecvPills" class="grade-pills-wrap"></div>
          <div id="wsGradeRecvBars"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 custom-breakdown-card" id="wsGradeDispWrap">
      <div class="card h-100 dash-section-card">
        <div class="card-header" onclick="toggleCard('wsGradeDispBody','wsGradeDispChevron')">
          <div class="d-flex align-items-center flex-1">
            <i class="fas fa-chevron-down dash-chevron" id="wsGradeDispChevron"></i>
            <span class="section-title mb-0"><?=$languageArray['grade_distribution_code'][$language]?> &mdash; <?=$languageArray['dispatch_code'][$language]?></span>
          </div>
          <div class="d-flex align-items-center" style="gap: 10px; flex-shrink: 0;">
            <button class="btn btn-sm btn-outline-success" onclick="event.stopPropagation();exportGradeDistribution('DISPATCH')" title="Export Excel"><i class="fas fa-file-excel"></i></button>
            <span class="text-muted dash-meta-text" id="wsGradeDispTotal"></span>
            <div class="dash-pager" id="wsGradeDispPager" style="display:none;">
              <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();wsGradeDispPageFn(-1)"><i class="fas fa-chevron-left"></i></button>
              <small id="wsGradeDispPageInfo"></small>
              <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();wsGradeDispPageFn(1)"><i class="fas fa-chevron-right"></i></button>
            </div>
          </div>
        </div>
        <div class="card-body" id="wsGradeDispBody">
          <div id="wsGradeDispPills" class="grade-pills-wrap"></div>
          <div id="wsGradeDispBars"><p class="text-muted"><?=$languageArray['no_data_code'][$language]?></p></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Hourly Distribution -->
  <h6 class="dash-section-header" id="wsHourlyHeader"><?=$languageArray['hourly_distribution_code'][$language]?></h6>
  <div class="row custom-breakdown-box" id="wsHourlyWrap">
    <div class="col-12 col-md-6 custom-breakdown-card" id="wsHourlyRecvWrap">
      <div class="card h-100 dash-section-card">
        <div class="card-header" onclick="toggleCard('wsHourlyRecvBody','wsHourlyRecvChevron')">
          <div class="d-flex align-items-center">
            <i class="fas fa-chevron-down dash-chevron" id="wsHourlyRecvChevron"></i>
            <span class="section-title"><?=$languageArray['receiving_code'][$language]?> by Hour (kg)</span>
          </div>
        </div>
        <div class="card-body" id="wsHourlyRecvBody">
          <div class="dash-chart-wrap"><canvas id="wsHourlyRecvChart"></canvas></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 custom-breakdown-card" id="wsHourlyDispWrap">
      <div class="card h-100 dash-section-card">
        <div class="card-header" onclick="toggleCard('wsHourlyDispBody','wsHourlyDispChevron')">
          <div class="d-flex align-items-center">
            <i class="fas fa-chevron-down dash-chevron" id="wsHourlyDispChevron"></i>
            <span class="section-title"><?=$languageArray['dispatch_code'][$language]?> by Hour (kg)</span>
          </div>
        </div>
        <div class="card-body" id="wsHourlyDispBody">
          <div class="dash-chart-wrap"><canvas id="wsHourlyDispChart"></canvas></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Volume Trend -->
  <h6 class="dash-section-header" id="wsTrendHeader"><?=$languageArray['volume_trending_code'][$language]?></h6>
  <div id="wsTrendWrap" class="volume-trading-box">
    <div class="dash-chart-wrap"><canvas id="wsTrendChart"></canvas></div>
  </div>

  <!-- Export Type Modal -->
  <div class="modal fade" id="wsExportTypeModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:380px;">
      <div class="modal-content custom-model-content-box">
        <div class="modal-header custom-model-header-box">
          <h5 class="modal-title custom-model-title-txt"><i class="fas fa-file-export"></i> Export Options</h5>
          <button type="button" class="close custom-btn-close-icon" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body custom-model-body-box">
          <input type="hidden" id="wsExportParty">
          <div class="form-group mb-0">
            <label>Export Type</label>
            <select class="form-control" id="wsExportType">
              <option value="summary">Summary</option>
              <option value="individual">Individual</option>
            </select>
          </div>
        </div>
        <div class="modal-footer custom-model-fotter-box">
          <button type="button" class="custom-close-btn" data-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
          <button type="button" class="custom-save-btn" onclick="doExportBreakdown()"><i class="fas fa-file-export"></i> Export</button>
        </div>
      </div>
    </div>
  </div>
</div>
