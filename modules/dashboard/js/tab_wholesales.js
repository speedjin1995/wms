/* ============================================================
   tab_wholesales.js — Wholesales tab logic
   ============================================================ */

var wsTrendChart      = null;
var wsHourlyRecvChart = null;
var wsHourlyDispChart = null;
var wsSupplierData         = [];
var wsSupplierCurrentPage  = 0;
var wsCustomerData         = [];
var wsCustomerCurrentPage  = 0;
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

  $('#wsSupplier, #wsCustomer').on('change', function () {
    loadWholesales();
  });
});

/* ── Load ───────────────────────────────────────────────── */
function loadWholesales() {
  var params = $.extend(getDateParams(), {
    status:   $('#wsType').val(),
    customer: $('#wsCustomer').val() || '',
    supplier: $('#wsSupplier').val() || ''
  });

  $.post('php/modules/wholesales/getDashboard.php', params, function (data) {
    var obj = JSON.parse(data);
    if (obj.status !== 'success') return;

    var s      = obj.summary;
    var wsType = $('#wsType').val();

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

    /* --- supplier breakdown --- */
    var hasSupplier = wsType !== 'DISPATCH' && obj.supplierBreakdown.length > 0;
    if (hasSupplier) {
      $('#wsSupplierBreakdownWrap').show();
      wsSupplierData        = obj.supplierBreakdown;
      wsSupplierCurrentPage = 0;
      renderPagedBreakdown('wsSupplierBreakdown', 'wsSupplierPager', 'wsSupplierPageInfo', wsSupplierData, wsSupplierCurrentPage, '#17a2b8');
    } else {
      $('#wsSupplierBreakdownWrap').hide();
      wsSupplierData = [];
    }

    /* --- customer breakdown --- */
    var hasCustomer = wsType !== 'RECEIVING' && obj.customerBreakdown.length > 0;
    if (hasCustomer) {
      $('#wsCustomerBreakdownWrap').show();
      wsCustomerData        = obj.customerBreakdown;
      wsCustomerCurrentPage = 0;
      renderPagedBreakdown('wsCustomerBreakdown', 'wsCustomerPager', 'wsCustomerPageInfo', wsCustomerData, wsCustomerCurrentPage, '#28a745');
    } else {
      $('#wsCustomerBreakdownWrap').hide();
      wsCustomerData = [];
    }

    $('#wsBreakdownHeader, #wsBreakdownRow').toggle(hasSupplier || hasCustomer);

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

/* ── Pager ──────────────────────────────────────────────── */
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

function wsGradeRecvPageFn(dir) { $('#wsGradeRecvBars').data('gradePage')(dir); }
function wsGradeDispPageFn(dir) { $('#wsGradeDispBars').data('gradePage')(dir); }
