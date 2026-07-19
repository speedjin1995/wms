<?php
require_once 'php/db_connect.php';
session_start();

if (!isset($_SESSION['userID'])) {
    echo '<script>window.location.href = "login.html";</script>';
} else {
    $user = $_SESSION['userID'];
    $company = $_SESSION['customer'];
    $role = $_SESSION['role'];
    $language = $_SESSION['language'];
    $languageArray = $_SESSION['languageArray'];

    if ($role != 'SADMIN') {
        $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' AND customer = '$company' ORDER BY customer_name ASC");
        $suppliers = $db->query("SELECT * FROM supplies WHERE deleted = '0' AND customer = '$company' ORDER BY supplier_name ASC");
        $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' AND customer = '$company' ORDER BY locations ASC");
        $productionLines = $db->query("SELECT * FROM production_lines WHERE deleted = '0' AND customers = '$company' ORDER BY production_line ASC");
    } else {
        $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' ORDER BY customer_name ASC");
        $suppliers = $db->query("SELECT * FROM supplies WHERE deleted = '0' ORDER BY supplier_name ASC");
        $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' ORDER BY locations ASC");
        $productionLines = $db->query("SELECT * FROM production_lines WHERE deleted = '0' ORDER BY production_line ASC");
    }
}
?>

<style>
  .dash-stat-card {
    border-radius: 8px;
    padding: 18px 20px;
    color: #fff;
    min-height: 90px;
  }
  .dash-stat-card .stat-label {
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
    opacity: 0.85;
    margin-bottom: 4px;
  }
  .dash-stat-card .stat-value {
    font-size: 28px;
    font-weight: 700;
    line-height: 1.1;
  }
  .dash-stat-card .stat-sub {
    font-size: 12px;
    opacity: 0.75;
    margin-top: 4px;
  }
  .breakdown-bar-wrap { margin-bottom: 10px; }
  .breakdown-bar-label { font-size: 13px; margin-bottom: 2px; display: flex; justify-content: space-between; }
  .breakdown-bar-track { background: #e9ecef; border-radius: 4px; height: 10px; }
  .breakdown-bar-fill { height: 10px; border-radius: 4px; background: #007bff; transition: width 0.4s; }
  .section-title {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #6c757d;
    margin-bottom: 12px;
  }
</style>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?=$languageArray['processing_code'][$language]?> <?=$languageArray['dashboard_code'][$language]?></h1>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">

    <!-- Date Range Filter -->
    <div class="card">
      <div class="card-body py-3">
        <div class="row align-items-end">
          <div class="form-group col-md-3 mb-0">
            <label class="mb-1"><?=$languageArray['from_date_code'][$language]?></label>
            <div class="input-group date" id="dashFromDatePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#dashFromDatePicker" id="dashFromDate"/>
              <div class="input-group-append" data-target="#dashFromDatePicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>
          <div class="form-group col-md-3 mb-0">
            <label class="mb-1"><?=$languageArray['to_date_code'][$language]?></label>
            <div class="input-group date" id="dashToDatePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#dashToDatePicker" id="dashToDate"/>
              <div class="input-group-append" data-target="#dashToDatePicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>
          <div class="form-group col-md-3 mb-0">
            <label class="mb-1"><?=$languageArray['locations_code'][$language]?></label>
            <select class="form-control select2" id="dashLocation">
              <option value=""><?=$languageArray['all_code'][$language]?></option>
              <?php while ($row = mysqli_fetch_assoc($locations)) { ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['locations']) ?></option>
              <?php } ?>
            </select>
          </div>
          <div class="col-md-2 mb-0">
            <button type="button" class="btn btn-warning btn-block" id="dashSearch">
              <i class="fas fa-search"></i> <?=$languageArray['search_code'][$language]?>
            </button>
          </div>

        </div>
      </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="dashTabs">
      <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#tabWholesales">
          <i class="fas fa-cubes mr-1"></i> <?=$languageArray['wholesales_code'][$language]?>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tabGrading">
          <i class="fas fa-clipboard-check mr-1"></i> <?=$languageArray['grading_code'][$language]?>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tabPackaging">
          <i class="fas fa-box-open mr-1"></i> <?=$languageArray['batch_packaging_code'][$language]?>
        </a>
      </li>
    </ul>

    <div class="tab-content" style="background:#fff; border:1px solid #dee2e6; border-top:none; border-radius:0 0 4px 4px; padding:20px;">

      <!-- ===== WHOLESALES TAB ===== -->
      <div class="tab-pane fade show active" id="tabWholesales">
        <!-- Wholesales Filters -->
        <div class="row mb-3">
          <div class="form-group col-md-3 mb-0">
            <label class="mb-1"><?=$languageArray['status_code'][$language]?></label>
            <select class="form-control select2" id="wsType">
              <option value=""><?=$languageArray['all_code'][$language]?></option>
              <option value="RECEIVING"><?=$languageArray['receiving_code'][$language]?></option>
              <option value="DISPATCH"><?=$languageArray['dispatch_code'][$language]?></option>
            </select>
          </div>
          <div class="form-group col-md-3 mb-0" id="wsSupplierWrap" style="display:none;">
            <label class="mb-1"><?=$languageArray['supplier_code'][$language]?></label>
            <select class="form-control select2" id="wsSupplier">
              <option value=""><?=$languageArray['all_code'][$language]?> <?=$languageArray['supplier_code'][$language]?></option>
              <?php while ($row = mysqli_fetch_assoc($suppliers)) { ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['supplier_name']) ?></option>
              <?php } ?>
            </select>
          </div>
          <div class="form-group col-md-3 mb-0" id="wsCustomerWrap" style="display:none;">
            <label class="mb-1"><?=$languageArray['customer_code'][$language]?></label>
            <select class="form-control select2" id="wsCustomer">
              <option value=""><?=$languageArray['all_code'][$language]?> <?=$languageArray['customer_code'][$language]?></option>
              <?php while ($row = mysqli_fetch_assoc($customers)) { ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['customer_name']) ?></option>
              <?php } ?>
            </select>
          </div>
        </div>

        <!-- Wholesales Summary Cards -->
        <div class="row mb-4" id="wsCards">
          <div class="col-md-3 col-6 mb-3" id="wsReceivingCard">
            <div class="dash-stat-card" style="background:linear-gradient(135deg,#17a2b8,#138496);">
              <div class="stat-label"><?=$languageArray['receiving_code'][$language]?> — <?=$languageArray['total_weight_code'][$language]?></div>
              <div class="stat-value" id="wsReceivingWeight">—</div>
              <div class="stat-sub"><span id="wsReceivingCount">—</span> records &nbsp;|&nbsp; kg</div>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-3" id="wsDispatchCard">
            <div class="dash-stat-card" style="background:linear-gradient(135deg,#28a745,#1e7e34);">
              <div class="stat-label"><?=$languageArray['dispatch_code'][$language]?> — <?=$languageArray['total_weight_code'][$language]?></div>
              <div class="stat-value" id="wsDispatchWeight">—</div>
              <div class="stat-sub"><span id="wsDispatchCount">—</span> records &nbsp;|&nbsp; kg</div>
            </div>
          </div>
        </div>

        <!-- Wholesales Breakdowns -->
        <div class="row">
          <div class="col-md-6" id="wsSupplierBreakdownWrap">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="section-title mb-0"><?=$languageArray['weight_code'][$language]?> by <?=$languageArray['supplier_code'][$language]?> (kg)</div>
              <div id="wsSupplierPager" style="display:none;">
                <button class="btn btn-sm btn-outline-secondary" id="wsSupplierPrev" onclick="wsSupplierPage(-1)"><i class="fas fa-chevron-left"></i></button>
                <small class="mx-2" id="wsSupplierPageInfo"></small>
                <button class="btn btn-sm btn-outline-secondary" id="wsSupplierNext" onclick="wsSupplierPage(1)"><i class="fas fa-chevron-right"></i></button>
              </div>
            </div>
            <div id="wsSupplierBreakdown"><p class="text-muted">No data.</p></div>
          </div>
          <div class="col-md-6" id="wsCustomerBreakdownWrap">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="section-title mb-0"><?=$languageArray['weight_code'][$language]?> by <?=$languageArray['customer_code'][$language]?> (kg)</div>
              <div id="wsCustomerPager" style="display:none;">
                <button class="btn btn-sm btn-outline-secondary" id="wsCustomerPrev" onclick="wsCustomerPage(-1)"><i class="fas fa-chevron-left"></i></button>
                <small class="mx-2" id="wsCustomerPageInfo"></small>
                <button class="btn btn-sm btn-outline-secondary" id="wsCustomerNext" onclick="wsCustomerPage(1)"><i class="fas fa-chevron-right"></i></button>
              </div>
            </div>
            <div id="wsCustomerBreakdown"><p class="text-muted">No data.</p></div>
          </div>
        </div>

        <!-- Volume Trend Chart -->
        <div class="card mt-3 mb-3">
          <div class="card-header">
            <div class="section-title mb-0">Volume Trending (kg)</div>
          </div>
          <div class="card-body">
            <canvas id="wsTrendChart" height="80"></canvas>
          </div>
        </div>

      </div>

      <!-- ===== GRADING TAB ===== -->
      <div class="tab-pane fade" id="tabGrading">
        <!-- Grading Summary Cards -->
        <div class="row mb-4">
          <div class="col-md-3 col-6 mb-3">
            <div class="dash-stat-card" style="background:linear-gradient(135deg,#6f42c1,#5a32a3);">
              <div class="stat-label"><?=$languageArray['total_code'][$language]?> <?=$languageArray['net_code'][$language]?> <?=$languageArray['weight_code'][$language]?></div>
              <div class="stat-value" id="grTotalNet">—</div>
              <div class="stat-sub"><span id="grSessionCount">—</span> sessions &nbsp;|&nbsp; kg</div>
            </div>
          </div>
        </div>

        <!-- Grading Breakdown by Product + Grade -->
        <div class="section-title"><?=$languageArray['net_code'][$language]?> <?=$languageArray['weight_code'][$language]?> by Product &amp; <?=$languageArray['grading_code'][$language]?></div>
        <div id="grProductBreakdown"><p class="text-muted">No data.</p></div>
      </div>

      <!-- ===== PACKAGING TAB ===== -->
      <div class="tab-pane fade" id="tabPackaging">
        <!-- Packaging Filters -->
        <div class="row mb-3">
          <div class="form-group col-md-3 mb-0">
            <label class="mb-1">Production Line</label>
            <select class="form-control select2" id="pkgProductionLine">
              <option value="">All</option>
              <?php while ($row = mysqli_fetch_assoc($productionLines)) { ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['production_line']) ?></option>
              <?php } ?>
            </select>
          </div>
        </div>
        <!-- Packaging Summary Cards -->
        <div class="row mb-4">
          <div class="col-md-3 col-6 mb-3">
            <div class="dash-stat-card" style="background:linear-gradient(135deg,#007bff,#0056b3);">
              <div class="stat-label"><?=$languageArray['total_weight_code'][$language]?></div>
              <div class="stat-value" id="pkgTotalWeight">—</div>
              <div class="stat-sub"><span id="pkgBatchCount">—</span> batches &nbsp;|&nbsp; kg</div>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-3">
            <div class="dash-stat-card" style="background:linear-gradient(135deg,#e83e8c,#c2185b);">
              <div class="stat-label"><?=$languageArray['total_code'][$language]?> <?=$languageArray['boxes_code'][$language]?></div>
              <div class="stat-value" id="pkgTotalBoxes">—</div>
              <div class="stat-sub"><?=$languageArray['boxes_code'][$language]?> packed</div>
            </div>
          </div>
        </div>

        <!-- Packaging Breakdown by Product -->
        <div class="section-title"><?=$languageArray['weight_code'][$language]?> by Product (kg)</div>
        <div id="pkgProductBreakdown"><p class="text-muted">No data.</p></div>
      </div>

    </div><!-- /.tab-content -->
  </div>
</div>

<script>
  var wsTrendChart = null;

  var wsSupplierData = [];
  var wsSupplierCurrentPage = 0;
  var wsCustomerData = [];
  var wsCustomerCurrentPage = 0;
  var WS_PAGE_SIZE = 10;

  $(function () {
    var today = new Date();

    $('#dashFromDatePicker').datetimepicker({ icons: { time: 'far fa-clock' }, format: 'DD/MM/YYYY', defaultDate: today });
    $('#dashToDatePicker').datetimepicker({ icons: { time: 'far fa-clock' }, format: 'DD/MM/YYYY', defaultDate: today });

    $('.select2').each(function () {
      $(this).select2({ allowClear: true, placeholder: 'Please Select' });
    });

    // Toggle customer/supplier filter visibility based on type selection
    $('#wsType').on('change', function () {
      var val = $(this).val();
      if (val === 'DISPATCH') {
        $('#wsSupplierWrap').hide();
        $('#wsCustomerWrap').show();
        $('#wsSupplier').val('').trigger('change.select2');
      } else if (val === 'RECEIVING') {
        $('#wsCustomerWrap').hide();
        $('#wsSupplierWrap').show();
        $('#wsCustomer').val('').trigger('change.select2');
      } else {
        $('#wsSupplierWrap').hide();
        $('#wsCustomerWrap').hide();
        $('#wsSupplier').val('').trigger('change.select2');
        $('#wsCustomer').val('').trigger('change.select2');
      }
      loadWholesales();
    });

    $('#wsSupplier, #wsCustomer').on('change', function () {
      loadWholesales();
    });

    $('#pkgProductionLine').on('change', function () {
      loadPackaging();
    });

    $('#dashSearch').on('click', function () {
      loadAllDashboards();
    });

    // Also reload when switching tabs if not yet loaded
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
      var target = $(e.target).attr('href');
      if (target === '#tabGrading') loadGrading();
      if (target === '#tabPackaging') loadPackaging();
    });

    // Load all on page ready
    loadAllDashboards();
  });

  function getDateParams() {
    return {
      fromDate: $('#dashFromDate').val(),
      toDate: $('#dashToDate').val(),
      location: $('#dashLocation').val() || ''
    };
  }

  function getPkgParams() {
    return $.extend(getDateParams(), {
      productionLine: $('#pkgProductionLine').val() || ''
    });
  }

  function loadAllDashboards() {
    loadWholesales();
    loadGrading();
    loadPackaging();
  }

  function loadWholesales() {
    var params = $.extend(getDateParams(), {
      status: $('#wsType').val(),
      customer: $('#wsCustomer').val() || '',
      supplier: $('#wsSupplier').val() || ''
    });

    $.post('php/modules/wholesales/getDashboard.php', params, function (data) {
      var obj = JSON.parse(data);
      if (obj.status !== 'success') return;

      var s = obj.summary;
      var wsType = $('#wsType').val();

      // Update cards visibility
      if (wsType === 'DISPATCH') {
        $('#wsReceivingCard').hide();
        $('#wsDispatchCard').show();
      } else if (wsType === 'RECEIVING') {
        $('#wsDispatchCard').hide();
        $('#wsReceivingCard').show();
      } else {
        $('#wsReceivingCard').show();
        $('#wsDispatchCard').show();
      }

      $('#wsReceivingWeight').text(formatNum(s.receiving_weight));
      $('#wsReceivingCount').text(s.receiving_count || 0);
      $('#wsDispatchWeight').text(formatNum(s.dispatch_weight));
      $('#wsDispatchCount').text(s.dispatch_count || 0);

      // Volume trend chart
      var trend = obj.volumeTrend || [];
      var labels   = trend.map(function(d) { return d.date; });
      var recvData = trend.map(function(d) { return d.receiving; });
      var dispData = trend.map(function(d) { return d.dispatch; });

      if (wsTrendChart) {
        wsTrendChart.data.labels = labels;
        wsTrendChart.data.datasets[0].data = recvData;
        wsTrendChart.data.datasets[1].data = dispData;
        wsTrendChart.update();
      } else {
        var ctx = document.getElementById('wsTrendChart').getContext('2d');
        wsTrendChart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [
              {
                label: 'Receiving (kg)',
                data: recvData,
                backgroundColor: 'rgba(23,162,184,0.7)',
                borderColor: '#17a2b8',
                borderWidth: 1
              },
              {
                label: 'Dispatch (kg)',
                data: dispData,
                backgroundColor: 'rgba(40,167,69,0.7)',
                borderColor: '#28a745',
                borderWidth: 1
              }
            ]
          },
          options: {
            responsive: true,
            scales: {
              xAxes: [{ gridLines: { display: false } }],
              yAxes: [{ ticks: { beginAtZero: true } }]
            },
            legend: { position: 'top' },
            tooltips: {
              callbacks: {
                label: function(item, data) {
                  return data.datasets[item.datasetIndex].label + ': ' +
                    parseFloat(item.yLabel).toLocaleString('en-MY', { minimumFractionDigits: 2 }) + ' kg';
                }
              }
            }
          }
        });
      }

      // Supplier breakdown
      if (wsType !== 'DISPATCH' && obj.supplierBreakdown.length > 0) {
        $('#wsSupplierBreakdownWrap').show();
        wsSupplierData = obj.supplierBreakdown;
        wsSupplierCurrentPage = 0;
        renderPagedBreakdown('wsSupplierBreakdown', 'wsSupplierPager', 'wsSupplierPageInfo', wsSupplierData, wsSupplierCurrentPage, '#17a2b8');
      } else {
        $('#wsSupplierBreakdownWrap').hide();
        wsSupplierData = [];
      }

      // Customer breakdown
      if (wsType !== 'RECEIVING' && obj.customerBreakdown.length > 0) {
        $('#wsCustomerBreakdownWrap').show();
        wsCustomerData = obj.customerBreakdown;
        wsCustomerCurrentPage = 0;
        renderPagedBreakdown('wsCustomerBreakdown', 'wsCustomerPager', 'wsCustomerPageInfo', wsCustomerData, wsCustomerCurrentPage, '#28a745');
      } else {
        $('#wsCustomerBreakdownWrap').hide();
        wsCustomerData = [];
      }
    });
  }

  function loadGrading() {
    $.post('php/modules/grading/getDashboard.php', getDateParams(), function (data) {
      var obj = JSON.parse(data);
      if (obj.status !== 'success') return;

      var s = obj.summary;
      $('#grTotalNet').text(formatNum(s.total_net));
      $('#grSessionCount').text(s.session_count || 0);

      var items = obj.productGradeBreakdown || [];
      if (items.length === 0) {
        $('#grProductBreakdown').html('<p class="text-muted">No data.</p>');
        return;
      }

      // Group by product
      var grouped = {};
      var grandTotal = 0;
      items.forEach(function (item) {
        var p = item.product_name || '—';
        if (!grouped[p]) grouped[p] = { total: 0, grades: [] };
        grouped[p].total += parseFloat(item.total_weight) || 0;
        grouped[p].grades.push(item);
        grandTotal += parseFloat(item.total_weight) || 0;
      });

      var html = '';
      var idx = 0;
      Object.keys(grouped).forEach(function (product) {
        var g = grouped[product];
        var pct = grandTotal > 0 ? (g.total / grandTotal * 100).toFixed(1) : 0;
        html += '<div class="card mb-2 shadow-sm">' +
          '<div class="card-header py-2 px-3 gr-product-row" data-idx="' + idx + '" style="cursor:pointer;background:#f4f6f9;">' +
            '<div class="d-flex justify-content-between align-items-center">' +
              '<div>' +
                '<i class="fas fa-chevron-right gr-chevron mr-2" style="font-size:11px;color:#6c757d;"></i>' +
                '<strong>' + product + '</strong>' +
              '</div>' +
              '<div class="text-right">' +
                '<span class="badge badge-secondary mr-2">' + pct + '%</span>' +
                '<span class="font-weight-bold">' + formatNum(g.total) + ' kg</span>' +
              '</div>' +
            '</div>' +
            '<div class="mt-1">' +
              '<div style="background:#dee2e6;border-radius:4px;height:6px;">' +
                '<div style="width:' + pct + '%;background:#6f42c1;border-radius:4px;height:6px;"></div>' +
              '</div>' +
            '</div>' +
          '</div>' +
          '<div class="gr-grade-rows" id="gr-grades-' + idx + '" style="display:none;">' +
            '<div class="card-body py-2 px-3">';

        g.grades.forEach(function (grade) {
          var gPct = g.total > 0 ? (parseFloat(grade.total_weight) / g.total * 100).toFixed(1) : 0;
          html += '<div class="d-flex justify-content-between align-items-center py-1">' +
            '<span class="text-muted" style="font-size:13px;"><i class="fas fa-tag mr-1" style="font-size:10px;"></i>' + (grade.grade_name || '—') + '</span>' +
            '<span style="font-size:13px;">' + formatNum(grade.total_weight) + ' kg <span class="text-muted">(' + gPct + '%)</span></span>' +
          '</div>';
        });

        html += '</div></div></div>';
        idx++;
      });

      $('#grProductBreakdown').html(html);

      // Toggle expand
      $('#grProductBreakdown').off('click', '.gr-product-row').on('click', '.gr-product-row', function () {
        var i = $(this).data('idx');
        var $grades = $('#gr-grades-' + i);
        var $icon = $(this).find('.gr-chevron');
        $grades.slideToggle(150);
        $icon.toggleClass('fa-chevron-right fa-chevron-down');
      });
    });
  }

  function loadPackaging() {
    $.post('php/modules/packagingBatches/getDashboard.php', getPkgParams(), function (data) {
      var obj = JSON.parse(data);
      if (obj.status !== 'success') return;

      var s = obj.summary;
      $('#pkgTotalWeight').text(formatNum(s.total_weight));
      $('#pkgTotalBoxes').text(s.total_boxes || 0);
      $('#pkgBatchCount').text(s.batch_count || 0);

      var items = obj.productBreakdown || [];
      if (items.length === 0) {
        $('#pkgProductBreakdown').html('<p class="text-muted">No data.</p>');
        return;
      }

      var grandTotal = items.reduce(function(sum, i) { return sum + (parseFloat(i.total_weight) || 0); }, 0);
      var html = '';

      items.forEach(function (item, idx) {
        var pct = grandTotal > 0 ? (parseFloat(item.total_weight) / grandTotal * 100).toFixed(1) : 0;
        html += '<div class="card mb-2 shadow-sm">' +
          '<div class="card-header py-2 px-3 pkg-product-row" data-idx="' + idx + '" style="cursor:pointer;background:#f4f6f9;">' +
            '<div class="d-flex justify-content-between align-items-center">' +
              '<div>' +
                '<i class="fas fa-chevron-right pkg-chevron mr-2" style="font-size:11px;color:#6c757d;"></i>' +
                '<strong>' + item.product_name + '</strong>' +
              '</div>' +
              '<div class="text-right">' +
                '<span class="badge badge-secondary mr-2">' + pct + '%</span>' +
                '<span class="font-weight-bold">' + formatNum(item.total_weight) + ' kg</span>' +
                '<span class="text-muted ml-2" style="font-size:12px;">(' + item.total_boxes + ' boxes)</span>' +
              '</div>' +
            '</div>' +
            '<div class="mt-1">' +
              '<div style="background:#dee2e6;border-radius:4px;height:6px;">' +
                '<div style="width:' + pct + '%;background:#007bff;border-radius:4px;height:6px;"></div>' +
              '</div>' +
            '</div>' +
          '</div>' +
          '<div class="pkg-grade-rows" id="pkg-grades-' + idx + '" style="display:none;">' +
            '<div class="card-body py-2 px-3">';

        (item.grades || []).forEach(function (grade) {
          var gPct = item.total_weight > 0 ? (parseFloat(grade.total_weight) / item.total_weight * 100).toFixed(1) : 0;
          html += '<div class="d-flex justify-content-between align-items-center py-1">' +
            '<span class="text-muted" style="font-size:13px;">' +
              '<i class="fas fa-tag mr-1" style="font-size:10px;"></i>' + grade.grade_name +
              ' <span class="badge badge-light border">' + grade.packaging_name + '</span>' +
            '</span>' +
            '<span style="font-size:13px;">' + formatNum(grade.total_weight) + ' kg' +
              ' <span class="text-muted">(' + gPct + '%)</span>' +
              ' &nbsp;|&nbsp; ' + grade.total_boxes + ' boxes' +
            '</span>' +
          '</div>';
        });

        html += '</div></div></div>';
      });

      $('#pkgProductBreakdown').html(html);

      $('#pkgProductBreakdown').off('click', '.pkg-product-row').on('click', '.pkg-product-row', function () {
        var i = $(this).data('idx');
        $('#pkg-grades-' + i).slideToggle(150);
        $(this).find('.pkg-chevron').toggleClass('fa-chevron-right fa-chevron-down');
      });
    });
  }

  function wsSupplierPage(dir) {
    var totalPages = Math.ceil(wsSupplierData.length / WS_PAGE_SIZE);
    wsSupplierCurrentPage = Math.max(0, Math.min(wsSupplierCurrentPage + dir, totalPages - 1));
    renderPagedBreakdown('wsSupplierBreakdown', 'wsSupplierPager', 'wsSupplierPageInfo', wsSupplierData, wsSupplierCurrentPage, '#17a2b8');
  }

  function wsCustomerPage(dir) {
    var totalPages = Math.ceil(wsCustomerData.length / WS_PAGE_SIZE);
    wsCustomerCurrentPage = Math.max(0, Math.min(wsCustomerCurrentPage + dir, totalPages - 1));
    renderPagedBreakdown('wsCustomerBreakdown', 'wsCustomerPager', 'wsCustomerPageInfo', wsCustomerData, wsCustomerCurrentPage, '#28a745');
  }

  function renderPagedBreakdown(containerId, pagerId, pageInfoId, items, page, color) {
    var totalPages = Math.ceil(items.length / WS_PAGE_SIZE);
    var start = page * WS_PAGE_SIZE;
    var pageItems = items.slice(start, start + WS_PAGE_SIZE);

    var maxVal = Math.max.apply(null, items.map(function(i) { return parseFloat(i.total_weight) || 0; }));
    var totalVal = items.reduce(function(sum, i) { return sum + (parseFloat(i.total_weight) || 0); }, 0);

    $('#' + containerId).html(renderBreakdownItems(pageItems, maxVal, totalVal, color));

    if (totalPages > 1) {
      $('#' + pagerId).show();
      $('#' + pageInfoId).text((page + 1) + ' / ' + totalPages);
      $('#' + pagerId + ' button:first').prop('disabled', page === 0);
      $('#' + pagerId + ' button:last').prop('disabled', page >= totalPages - 1);
    } else {
      $('#' + pagerId).hide();
    }
  }

  function renderBreakdownItems(items, maxVal, totalVal, color) {
    if (!items || items.length === 0) return '<p class="text-muted">No data.</p>';
    var html = '';
    items.forEach(function(item) {
      var val = parseFloat(item.total_weight) || 0;
      var pct = maxVal > 0 ? (val / maxVal * 100).toFixed(1) : 0;
      var sharePct = totalVal > 0 ? (val / totalVal * 100).toFixed(0) : 0;
      html += '<div class="breakdown-bar-wrap">' +
        '<div class="breakdown-bar-label">' +
          '<span>' + (item.name || 'Unknown') + '</span>' +
          '<span>' + formatNum(val) + ' kg (' + sharePct + '%)</span>' +
        '</div>' +
        '<div class="breakdown-bar-track">' +
          '<div class="breakdown-bar-fill" style="width:' + pct + '%;background:' + color + ';"></div>' +
        '</div>' +
      '</div>';
    });
    return html;
  }

  function renderBreakdown(items, color) {
    if (!items || items.length === 0) return '<p class="text-muted">No data.</p>';

    var maxVal = Math.max.apply(null, items.map(function (i) { return parseFloat(i.total_weight) || 0; }));
    var totalVal = items.reduce(function (sum, i) { return sum + (parseFloat(i.total_weight) || 0); }, 0);
    var html = '';

    items.forEach(function (item) {
      var val = parseFloat(item.total_weight) || 0;
      var pct = maxVal > 0 ? (val / maxVal * 100).toFixed(1) : 0;
      var sharePct = totalVal > 0 ? (val / totalVal * 100).toFixed(0) : 0;
      html += '<div class="breakdown-bar-wrap">' +
        '<div class="breakdown-bar-label">' +
          '<span>' + (item.name || 'Unknown') + '</span>' +
          '<span>' + formatNum(val) + ' kg (' + sharePct + '%)</span>' +
        '</div>' +
        '<div class="breakdown-bar-track">' +
          '<div class="breakdown-bar-fill" style="width:' + pct + '%;background:' + color + ';"></div>' +
        '</div>' +
      '</div>';
    });

    return html;
  }

  function formatNum(val) {
    var n = parseFloat(val) || 0;
    return n.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
</script>
