/* ============================================================
   tab_pulppaste.js — Pulp & Paste tab logic
   ============================================================ */

var ppTrendChart          = null;
var ppSupplierData        = [];
var ppSupplierCurrentPage = 0;
var ppCustomerData        = [];
var ppCustomerCurrentPage = 0;
var PP_PAGE_SIZE = 10;

$(function () {
  $('#ppType').on('change', function () {
    var val = $(this).val();
    if (val === 'OUTGOING') {
      $('#ppSupplierWrap').hide();
      $('#ppCustomerWrap').show();
      $('#ppSupplier').val('').trigger('change.select2');
    } else if (val === 'INCOMING') {
      $('#ppCustomerWrap').hide();
      $('#ppSupplierWrap').show();
      $('#ppCustomer').val('').trigger('change.select2');
    } else {
      $('#ppSupplierWrap, #ppCustomerWrap').hide();
      $('#ppSupplier, #ppCustomer').val('').trigger('change.select2');
    }
    loadPulpPaste();
  });

  $('#ppSupplier, #ppCustomer').on('change', function () {
    loadPulpPaste();
  });
});

function loadPulpPaste() {
  var ppType = $('#ppType').val();
  var params = $.extend(getDateParams(), {
    status:   ppType,
    supplier: $('#ppSupplier').val() || '',
    customer: $('#ppCustomer').val() || ''
  });

  $.post('php/modules/industrial/getDashboard.php', params, function (data) {
    var obj = JSON.parse(data);
    if (obj.status !== 'success') return;

    var s = obj.summary;

    /* --- stat cards visibility --- */
    if (ppType === 'OUTGOING') {
      $('#ppIncomingCard').hide();
      $('#ppOutgoingCard').show();
    } else if (ppType === 'INCOMING') {
      $('#ppOutgoingCard').hide();
      $('#ppIncomingCard').show();
    } else {
      $('#ppIncomingCard, #ppOutgoingCard').show();
    }

    $('#ppIncomingWeight').text(formatNum(s.incoming_weight));
    $('#ppIncomingCount').text(s.incoming_count || 0);
    $('#ppOutgoingWeight').text(formatNum(s.outgoing_weight));
    $('#ppOutgoingCount').text(s.outgoing_count || 0);

    /* --- volume trend chart --- */
    var trend   = obj.volumeTrend || [];
    var labels  = trend.map(function (d) { return d.date; });
    var inData  = trend.map(function (d) { return d.incoming; });
    var outData = trend.map(function (d) { return d.outgoing; });

    if (ppTrendChart) {
      ppTrendChart.data.labels              = labels;
      ppTrendChart.data.datasets[0].data    = inData;
      ppTrendChart.data.datasets[1].data    = outData;
      ppTrendChart.update();
    } else {
      ppTrendChart = new Chart(document.getElementById('ppTrendChart').getContext('2d'), {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            { label: 'Incoming (kg)', data: inData,  backgroundColor: 'rgba(253,126,20,0.7)', borderColor: '#fd7e14', borderWidth: 1 },
            { label: 'Outgoing (kg)', data: outData, backgroundColor: 'rgba(32,201,151,0.7)', borderColor: '#20c997', borderWidth: 1 }
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

    /* --- supplier breakdown --- */
    if (ppType !== 'OUTGOING' && obj.supplierBreakdown && obj.supplierBreakdown.length > 0) {
      $('#ppSupplierBreakdownWrap').show();
      ppSupplierData        = obj.supplierBreakdown;
      ppSupplierCurrentPage = 0;
      renderPagedBreakdown('ppSupplierBreakdown', 'ppSupplierPager', 'ppSupplierPageInfo', ppSupplierData, ppSupplierCurrentPage, '#fd7e14');
    } else {
      $('#ppSupplierBreakdownWrap').hide();
      ppSupplierData = [];
    }

    /* --- customer breakdown --- */
    if (ppType !== 'INCOMING' && obj.customerBreakdown && obj.customerBreakdown.length > 0) {
      $('#ppCustomerBreakdownWrap').show();
      ppCustomerData        = obj.customerBreakdown;
      ppCustomerCurrentPage = 0;
      renderPagedBreakdown('ppCustomerBreakdown', 'ppCustomerPager', 'ppCustomerPageInfo', ppCustomerData, ppCustomerCurrentPage, '#20c997');
    } else {
      $('#ppCustomerBreakdownWrap').hide();
      ppCustomerData = [];
    }
  });
}

/* ── Pagers ─────────────────────────────────────────────── */
function ppSupplierPage(dir) {
  var totalPages = Math.ceil(ppSupplierData.length / PP_PAGE_SIZE);
  ppSupplierCurrentPage = Math.max(0, Math.min(ppSupplierCurrentPage + dir, totalPages - 1));
  renderPagedBreakdown('ppSupplierBreakdown', 'ppSupplierPager', 'ppSupplierPageInfo', ppSupplierData, ppSupplierCurrentPage, '#fd7e14');
}

function ppCustomerPage(dir) {
  var totalPages = Math.ceil(ppCustomerData.length / PP_PAGE_SIZE);
  ppCustomerCurrentPage = Math.max(0, Math.min(ppCustomerCurrentPage + dir, totalPages - 1));
  renderPagedBreakdown('ppCustomerBreakdown', 'ppCustomerPager', 'ppCustomerPageInfo', ppCustomerData, ppCustomerCurrentPage, '#20c997');
}
