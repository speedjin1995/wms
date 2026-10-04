/* ============================================================
   tab_wholesales.js — Wholesales tab logic
   ============================================================ */

var wsTrendChart      = null;
var wsHourlyRecvChart = null;
var wsHourlyDispChart = null;
var wsSupplierNormalData        = [];
var wsSupplierNormalCurrentPage = 0;
var wsSupplierPackingData       = [];
var wsSupplierPackingCurrentPage = 0;
var wsCustomerNormalData        = [];
var wsCustomerNormalCurrentPage = 0;
var wsCustomerPackingData       = [];
var wsCustomerPackingCurrentPage = 0;
var WS_PAGE_SIZE = 10;

/* ── Filter change handlers ─────────────────────────────── */
$(function () {
  $('.ws-type-btn').on('click', function () {
    $('.ws-type-btn').removeClass('active');
    $(this).addClass('active');
    $('#wsType').val($(this).data('value'));
    var val = $(this).data('value');

    if (val === 'DISPATCH') {
      $('#wsSupplierWrap').hide();
      $('#wsCustomerWrap').show();
      $('#wsSupplier').val('').trigger('change.select2');
    } else if (val === 'RECEIVING') {
      $('#wsCustomerWrap').hide();
      $('#wsSupplierWrap').show();
      $('#wsCustomer').val('').trigger('change.select2');
    } else {
      $('#wsSupplierWrap, #wsCustomerWrap').hide();
      $('#wsSupplier, #wsCustomer').val('').trigger('change.select2');
    }
    loadWholesales();
  });

  $('#wsCategory, #wsSupplier, #wsCustomer').on('change', function () {
    loadWholesales();
  });

  // Repacking breakdown: expand a source product to see its targets
  $('#wsRepackBreakdown').on('click', '.ws-repack-row', function () {
    $('#wsRepackTargets' + $(this).data('idx')).slideToggle(150);
    $(this).find('.ws-repack-chevron').toggleClass('fa-chevron-right fa-chevron-down');
  });
});

/* ── Load ───────────────────────────────────────────────── */
function loadWholesales() {
  var selectedType = $('#wsType').val();
  loadRepackingSummary(selectedType);

  // Repacking only: hide the wholesales sections
  if (selectedType === 'REPACKING') {
    $('#wsReceivingCard, #wsReceivingValueCard, #wsDispatchCard, #wsDispatchValueCard, ' +
      '#wsSupplierBreakdownHeader, #wsSupplierBreakdownRow, #wsCustomerBreakdownHeader, #wsCustomerBreakdownRow, ' +
      '#wsGradeHeader, #wsGradeRow, #wsHourlyHeader, #wsHourlyWrap, #wsTrendHeader, #wsTrendWrap').hide();
    return;
  }

  var params = $.extend(getDateParams(), {
    status:    $('#wsType').val(),
    customer:  $('#wsCustomer').val() || '',
    supplier:  $('#wsSupplier').val() || '',
    category:  $('#wsCategory').val() || ''
  });

  $.post('php/modules/wholesales/getDashboard.php', params, function (data) {
    var obj = JSON.parse(data);
    if (obj.status !== 'success') return;

    var s      = obj.summary;
    var wsType = $('#wsType').val();
    // Switched to Repacking while this was loading
    if (wsType === 'REPACKING') return;

    /* --- stat cards visibility --- */
    if (wsType === 'DISPATCH' || wsType === 'STOCK-BAL') {
      $('#wsReceivingCard, #wsReceivingValueCard').hide();
      $('#wsDispatchCard, #wsDispatchValueCard').show();
    } else if (wsType === 'RECEIVING') {
      $('#wsDispatchCard, #wsDispatchValueCard').hide();
      $('#wsReceivingCard, #wsReceivingValueCard').show();
    } else {
      $('#wsReceivingCard, #wsReceivingValueCard, #wsDispatchCard, #wsDispatchValueCard').show();
    }

    $('#wsReceivingWeight').text(formatNum(s.receiving_weight));
    $('#wsReceivingCount').text(s.receiving_count || 0);
    $('#wsReceivingValue').html(formatCurrencyMap(s.receiving_value));
    $('#wsDispatchWeight').text(formatNum(s.dispatch_weight));
    $('#wsDispatchCount').text(s.dispatch_count || 0);
    $('#wsDispatchValue').html(formatCurrencyMap(s.dispatch_value));

    /* --- volume trend chart --- */
    var trend    = obj.volumeTrend || [];
    var labels   = trend.map(function (d) { return d.date; });
    var recvData = trend.map(function (d) { return d.receiving; });
    var dispData = trend.map(function (d) { return d.dispatch; });

    if (trend.length === 0) {
      $('#wsTrendWrap, #wsTrendHeader').hide();
    } else {
      $('#wsTrendWrap, #wsTrendHeader').show();
      if (wsTrendChart) {
        wsTrendChart.data.labels              = labels;
        wsTrendChart.data.datasets[0].data    = recvData;
        wsTrendChart.data.datasets[1].data    = dispData;
        wsTrendChart.update();
      } else {
        wsTrendChart = new Chart(document.getElementById('wsTrendChart').getContext('2d'), {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [
              { label: 'Receiving (kg)', data: recvData, backgroundColor: 'rgba(23,162,184,0.7)', borderColor: '#17a2b8', borderWidth: 1 },
              { label: 'Dispatch (kg)',  data: dispData, backgroundColor: 'rgba(40,167,69,0.7)',  borderColor: '#28a745', borderWidth: 1 }
            ]
          },
          options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
              xAxes: [{ gridLines: { display: false }, ticks: { fontSize: 10 } }],
              yAxes: [{ ticks: { beginAtZero: true } }]
            },
            legend: { position: 'top' },
            tooltips: { callbacks: { label: function (item, data) {
              return data.datasets[item.datasetIndex].label + ': ' +
                parseFloat(item.yLabel).toLocaleString('en-MY', { minimumFractionDigits: 2 }) + ' kg';
            }}}
          }
        });
      }
    }

    /* --- supplier breakdown (Normal / Packing) --- */
    var hasSupplierNormal = wsType !== 'DISPATCH' && obj.supplierNormalBreakdown.length > 0;
    var hasSupplierPacking = wsType !== 'DISPATCH' && obj.supplierPackingBreakdown.length > 0;
    
    if (hasSupplierNormal) {
      $('#wsSupplierNormalWrap').show();
      wsSupplierNormalData = obj.supplierNormalBreakdown;
      wsSupplierNormalCurrentPage = 0;
      renderPagedBreakdown('wsSupplierNormalBreakdown', 'wsSupplierNormalPager', 'wsSupplierNormalPageInfo', wsSupplierNormalData, wsSupplierNormalCurrentPage, '#17a2b8');
    } else {
      $('#wsSupplierNormalWrap').hide();
      wsSupplierNormalData = [];
    }

    if (hasSupplierPacking) {
      $('#wsSupplierPackingWrap').show();
      wsSupplierPackingData = obj.supplierPackingBreakdown;
      wsSupplierPackingCurrentPage = 0;
      renderPagedBreakdown('wsSupplierPackingBreakdown', 'wsSupplierPackingPager', 'wsSupplierPackingPageInfo', wsSupplierPackingData, wsSupplierPackingCurrentPage, '#17a2b8');
    } else {
      $('#wsSupplierPackingWrap').hide();
      wsSupplierPackingData = [];
    }

    $('#wsSupplierBreakdownHeader, #wsSupplierBreakdownRow').toggle(hasSupplierNormal || hasSupplierPacking);

    /* --- customer breakdown (Normal / Packing) --- */
    var hasCustomerNormal = wsType !== 'RECEIVING' && obj.customerNormalBreakdown.length > 0;
    var hasCustomerPacking = wsType !== 'RECEIVING' && obj.customerPackingBreakdown.length > 0;
    
    if (hasCustomerNormal) {
      $('#wsCustomerNormalWrap').show();
      wsCustomerNormalData = obj.customerNormalBreakdown;
      wsCustomerNormalCurrentPage = 0;
      renderPagedBreakdown('wsCustomerNormalBreakdown', 'wsCustomerNormalPager', 'wsCustomerNormalPageInfo', wsCustomerNormalData, wsCustomerNormalCurrentPage, '#28a745');
    } else {
      $('#wsCustomerNormalWrap').hide();
      wsCustomerNormalData = [];
    }

    if (hasCustomerPacking) {
      $('#wsCustomerPackingWrap').show();
      wsCustomerPackingData = obj.customerPackingBreakdown;
      wsCustomerPackingCurrentPage = 0;
      renderPagedBreakdown('wsCustomerPackingBreakdown', 'wsCustomerPackingPager', 'wsCustomerPackingPageInfo', wsCustomerPackingData, wsCustomerPackingCurrentPage, '#28a745');
    } else {
      $('#wsCustomerPackingWrap').hide();
      wsCustomerPackingData = [];
    }

    $('#wsCustomerBreakdownHeader, #wsCustomerBreakdownRow').toggle(hasCustomerNormal || hasCustomerPacking);

    /* --- grade distribution receiving --- */
    var gradeRecv = obj.gradeDistribution || [];
    var hasGradeRecv = wsType !== 'DISPATCH' && gradeRecv.length > 0;
    if (hasGradeRecv) {
      $('#wsGradeRecvWrap').show();
      var recvTotal = gradeRecv.reduce(function (s, p) { return s + p.grades.reduce(function (a, g) { return a + g.weight; }, 0); }, 0);
      $('#wsGradeRecvTotal').text(formatNum(recvTotal) + ' kg');
      renderGradeDist('wsGradeRecvPills', 'wsGradeRecvBars', gradeRecv, 'product', '#17a2b8', 'wsGradeRecvPager', 'wsGradeRecvPageInfo');
    } else {
      $('#wsGradeRecvWrap').hide();
    }

    /* --- grade distribution dispatch --- */
    var gradeDisp = obj.gradeDistributionDispatch || [];
    var hasGradeDisp = wsType !== 'RECEIVING' && gradeDisp.length > 0;
    if (hasGradeDisp) {
      $('#wsGradeDispWrap').show();
      var dispTotal = gradeDisp.reduce(function (s, p) { return s + p.grades.reduce(function (a, g) { return a + g.weight; }, 0); }, 0);
      $('#wsGradeDispTotal').text(formatNum(dispTotal) + ' kg');
      renderGradeDist('wsGradeDispPills', 'wsGradeDispBars', gradeDisp, 'product', '#28a745', 'wsGradeDispPager', 'wsGradeDispPageInfo');
    } else {
      $('#wsGradeDispWrap').hide();
    }

    $('#wsGradeHeader, #wsGradeRow').toggle(hasGradeRecv || hasGradeDisp);

    /* --- hourly charts --- */
    var hourLabels = ['12am','1am','2am','3am','4am','5am','6am','7am','8am','9am','10am','11am',
                      '12pm','1pm','2pm','3pm','4pm','5pm','6pm','7pm','8pm','9pm','10pm','11pm'];
    var hourlyRecv    = obj.hourlyReceiving || [];
    var hourlyDisp    = obj.hourlyDispatch  || [];
    var hasRecvHourly = wsType !== 'DISPATCH'  && hourlyRecv.some(function (v) { return v > 0; });
    var hasDispHourly = wsType !== 'RECEIVING' && hourlyDisp.some(function (v) { return v > 0; });

    $('#wsHourlyWrap').toggle(hasRecvHourly || hasDispHourly);
    $('#wsHourlyHeader').toggle(hasRecvHourly || hasDispHourly);

    if (hasRecvHourly) {
      $('#wsHourlyRecvWrap').show();
      if (wsHourlyRecvChart) {
        wsHourlyRecvChart.data.datasets[0].data = hourlyRecv;
        wsHourlyRecvChart.update();
      } else {
        wsHourlyRecvChart = new Chart(document.getElementById('wsHourlyRecvChart').getContext('2d'), {
          type: 'bar',
          data: { labels: hourLabels, datasets: [{ label: 'Receiving (kg)', data: hourlyRecv, backgroundColor: 'rgba(23,162,184,0.7)', borderColor: '#17a2b8', borderWidth: 1 }] },
          options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
              xAxes: [{ gridLines: { display: false }, ticks: { fontSize: 9, maxRotation: 45 } }],
              yAxes: [{ ticks: { beginAtZero: true } }]
            },
            legend: { display: false },
            tooltips: { callbacks: { label: function (item) {
              return parseFloat(item.yLabel).toLocaleString('en-MY', { minimumFractionDigits: 2 }) + ' kg';
            }}}
          }
        });
      }
    } else {
      $('#wsHourlyRecvWrap').hide();
    }

    if (hasDispHourly) {
      $('#wsHourlyDispWrap').show();
      if (wsHourlyDispChart) {
        wsHourlyDispChart.data.datasets[0].data = hourlyDisp;
        wsHourlyDispChart.update();
      } else {
        wsHourlyDispChart = new Chart(document.getElementById('wsHourlyDispChart').getContext('2d'), {
          type: 'bar',
          data: { labels: hourLabels, datasets: [{ label: 'Dispatch (kg)', data: hourlyDisp, backgroundColor: 'rgba(40,167,69,0.7)', borderColor: '#28a745', borderWidth: 1 }] },
          options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
              xAxes: [{ gridLines: { display: false }, ticks: { fontSize: 9, maxRotation: 45 } }],
              yAxes: [{ ticks: { beginAtZero: true } }]
            },
            legend: { display: false },
            tooltips: { callbacks: { label: function (item) {
              return parseFloat(item.yLabel).toLocaleString('en-MY', { minimumFractionDigits: 2 }) + ' kg';
            }}}
          }
        });
      }
    } else {
      $('#wsHourlyDispWrap').hide();
    }
  });
}

/* ── Repacking ──────────────────────────────────────────── */
function loadRepackingSummary(wsType) {
  // Repacking cards show on All and Repacking; the breakdown only on Repacking
  if (wsType !== '' && wsType !== 'REPACKING') {
    $('#wsRepackCard, #wsRepackTypeCard, #wsRepackHeader, #wsRepackRow').hide();
    return;
  }

  var params = $.extend(getDateParams(), {
    action:   'dashboard',
    category: $('#wsCategory').val() || ''
  });

  $.post('php/modules/repacking/api.php', params, function (obj) {
    if (obj.status !== 'success' || $('#wsType').val() !== wsType) return;

    var s = obj.message.summary;
    $('#wsRepackWeight').text(formatNum(s.total_weight));
    $('#wsRepackCount').text(s.record_count || 0);
    $('#wsRepackLocal').text(formatNum(s.local_weight));
    $('#wsRepackExport').text(formatNum(s.export_weight));
    $('#wsRepackCard, #wsRepackTypeCard').show();

    if (wsType === 'REPACKING') {
      renderRepackBreakdown(obj.message.breakdown || []);
      $('#wsRepackHeader, #wsRepackRow').show();
    } else {
      $('#wsRepackHeader, #wsRepackRow').hide();
    }
  });
}

function renderRepackBreakdown(items) {
  if (items.length === 0) {
    $('#wsRepackBreakdown').html('<p class="text-muted">No data.</p>');
    return;
  }

  var grandTotal = items.reduce(function (sum, i) { return sum + (parseFloat(i.total_weight) || 0); }, 0);
  var html = '';

  items.forEach(function (item, idx) {
    var pct = grandTotal > 0 ? (parseFloat(item.total_weight) / grandTotal * 100).toFixed(1) : 0;

    html += '<div class="card mb-2 shadow-sm">' +
      '<div class="card-header py-2 px-3 ws-repack-row" data-idx="' + idx + '" style="cursor:pointer;background:#f4f6f9;">' +
        '<div class="d-flex justify-content-between align-items-center">' +
          '<div><i class="fas fa-chevron-right ws-repack-chevron mr-2" style="font-size:11px;color:#6c757d;"></i><strong>' + escapeRepackText(item.name) + '</strong></div>' +
          '<div class="text-right">' +
            '<span class="badge badge-secondary mr-2">' + pct + '%</span>' +
            '<span class="font-weight-bold">' + formatNum(item.total_weight) + ' kg</span>' +
            '<span class="text-muted ml-2" style="font-size:12px;">(' + item.record_count + ' records)</span>' +
          '</div>' +
        '</div>' +
        '<div class="mt-1"><div style="background:#dee2e6;border-radius:4px;height:6px;">' +
          '<div style="width:' + pct + '%;background:#6f42c1;border-radius:4px;height:6px;"></div>' +
        '</div></div>' +
      '</div>' +
      '<div id="wsRepackTargets' + idx + '" style="display:none;">' +
        '<div class="card-body py-2 px-3">';

    (item.targets || []).forEach(function (target) {
      var tPct = item.total_weight > 0 ? (parseFloat(target.total_weight) / item.total_weight * 100).toFixed(1) : 0;
      html += '<div class="d-flex justify-content-between align-items-center py-1 border-bottom">' +
        '<span class="text-muted" style="font-size:13px;"><i class="fas fa-long-arrow-alt-right mr-1"></i>' + escapeRepackText(target.name) + '</span>' +
        '<span style="font-size:13px;">' + formatNum(target.total_weight) + ' kg <span class="text-muted">(' + tPct + '%)</span></span>' +
      '</div>';
    });

    html += '</div></div></div>';
  });

  $('#wsRepackBreakdown').html(html);
}

function escapeRepackText(text) {
  return $('<div>').text(text || '').html();
}

/* ── Pager ──────────────────────────────────────────────── */
function wsSupplierNormalPage(dir) {
  var totalPages = Math.ceil(wsSupplierNormalData.length / WS_PAGE_SIZE);
  wsSupplierNormalCurrentPage = Math.max(0, Math.min(wsSupplierNormalCurrentPage + dir, totalPages - 1));
  renderPagedBreakdown('wsSupplierNormalBreakdown', 'wsSupplierNormalPager', 'wsSupplierNormalPageInfo', wsSupplierNormalData, wsSupplierNormalCurrentPage, '#17a2b8');
}

function wsSupplierPackingPage(dir) {
  var totalPages = Math.ceil(wsSupplierPackingData.length / WS_PAGE_SIZE);
  wsSupplierPackingCurrentPage = Math.max(0, Math.min(wsSupplierPackingCurrentPage + dir, totalPages - 1));
  renderPagedBreakdown('wsSupplierPackingBreakdown', 'wsSupplierPackingPager', 'wsSupplierPackingPageInfo', wsSupplierPackingData, wsSupplierPackingCurrentPage, '#17a2b8');
}

function wsCustomerNormalPage(dir) {
  var totalPages = Math.ceil(wsCustomerNormalData.length / WS_PAGE_SIZE);
  wsCustomerNormalCurrentPage = Math.max(0, Math.min(wsCustomerNormalCurrentPage + dir, totalPages - 1));
  renderPagedBreakdown('wsCustomerNormalBreakdown', 'wsCustomerNormalPager', 'wsCustomerNormalPageInfo', wsCustomerNormalData, wsCustomerNormalCurrentPage, '#28a745');
}

function wsCustomerPackingPage(dir) {
  var totalPages = Math.ceil(wsCustomerPackingData.length / WS_PAGE_SIZE);
  wsCustomerPackingCurrentPage = Math.max(0, Math.min(wsCustomerPackingCurrentPage + dir, totalPages - 1));
  renderPagedBreakdown('wsCustomerPackingBreakdown', 'wsCustomerPackingPager', 'wsCustomerPackingPageInfo', wsCustomerPackingData, wsCustomerPackingCurrentPage, '#28a745');
}

function wsGradeRecvPageFn(dir) { $('#wsGradeRecvBars').data('gradePage')(dir); }
function wsGradeDispPageFn(dir) { $('#wsGradeDispBars').data('gradePage')(dir); }

/* ── Export functions ───────────────────────────────────── */
function openExportModal(party) {
  $('#wsExportParty').val(party);
  $('#wsExportType').val('summary');
  $('#wsExportTypeModal').modal('show');
}

function doExportBreakdown() {
  var party = $('#wsExportParty').val(); // e.g. supplier_normal, supplier_packing, customer_normal, customer_packing
  var exportType = $('#wsExportType').val();
  var params = getDateParams();
  var url;
  
  // Parse party type (e.g. "supplier_normal" -> base="supplier", partyType="Normal")
  var parts = party.split('_');
  var base = parts[0]; // supplier or customer
  var partyType = parts[1] ? parts[1].charAt(0).toUpperCase() + parts[1].slice(1) : ''; // Normal or Packing
  
  if (exportType === 'individual') {
    url = 'php/modules/wholesales/exportDashboard.php?type=' + base + '_individual';
  } else {
    url = 'php/modules/wholesales/exportDashboard.php?type=' + base;
  }
  
  url += '&fromDate=' + encodeURIComponent(params.fromDate);
  url += '&toDate=' + encodeURIComponent(params.toDate);
  url += '&partyType=' + encodeURIComponent(partyType);
  
  if (base === 'customer') {
    url += '&customer=' + encodeURIComponent($('#wsCustomer').val() || '');
  } else {
    url += '&supplier=' + encodeURIComponent($('#wsSupplier').val() || '');
  }
  
  $('#wsExportTypeModal').modal('hide');
  window.open(url, '_blank');
}

function exportGradeDistribution(status) {
  var params = getDateParams();
  var url = 'php/modules/wholesales/exportDashboard.php?type=grade';
  url += '&fromDate=' + encodeURIComponent(params.fromDate);
  url += '&toDate=' + encodeURIComponent(params.toDate);
  url += '&status=' + encodeURIComponent(status);
  window.open(url, '_blank');
}
