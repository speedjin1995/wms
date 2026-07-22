/* ============================================================
   dashboard.js — Shared utilities + page init
   ============================================================ */

$(function () {
  var today = new Date();

  $('#dashFromDatePicker').datetimepicker({ icons: { time: 'far fa-clock' }, format: 'DD/MM/YYYY', defaultDate: today });
  $('#dashToDatePicker').datetimepicker({ icons: { time: 'far fa-clock' }, format: 'DD/MM/YYYY', defaultDate: today });

  $('.select2').each(function () {
    $(this).select2({ allowClear: true, placeholder: 'Please Select' });
  });

  $('#dashSearch').on('click', function () {
    loadAllDashboards();
  });

  $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
    var target = $(e.target).attr('href');
    if (target === '#tabGrading')   loadGrading();
    if (target === '#tabPackaging') loadPackaging();
    if (target === '#tabPulpPaste') loadPulpPaste();
  });

  loadAllDashboards();
});

/* ── Params ─────────────────────────────────────────────── */
function getDateParams() {
  return {
    fromDate: $('#dashFromDate').val(),
    toDate:   $('#dashToDate').val(),
    location: $('#dashLocation').val() || ''
  };
}

function getPkgParams() {
  return $.extend(getDateParams(), {
    productionLine: $('#pkgProductionLine').val() || ''
  });
}

/* ── Load all ───────────────────────────────────────────── */
function loadAllDashboards() {
  loadWholesales();
  loadGrading();
  loadPackaging();
  loadPulpPaste();
}

/* ── Shared renderers ───────────────────────────────────── */
function renderBreakdownItems(items, maxVal, totalVal, color) {
  if (!items || items.length === 0) return '<p class="text-muted">No data.</p>';
  var html = '';
  items.forEach(function (item) {
    var val      = parseFloat(item.total_weight) || 0;
    var pct      = maxVal   > 0 ? (val / maxVal   * 100).toFixed(1) : 0;
    var sharePct = totalVal > 0 ? (val / totalVal * 100).toFixed(0)  : 0;
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

function renderPagedBreakdown(containerId, pagerId, pageInfoId, items, page, color) {
  var WS_PAGE_SIZE = 10;
  var totalPages   = Math.ceil(items.length / WS_PAGE_SIZE);
  var start        = page * WS_PAGE_SIZE;
  var pageItems    = items.slice(start, start + WS_PAGE_SIZE);
  var maxVal       = Math.max.apply(null, items.map(function (i) { return parseFloat(i.total_weight) || 0; }));
  var totalVal     = items.reduce(function (s, i) { return s + (parseFloat(i.total_weight) || 0); }, 0);

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

function renderGradeDist(pillsId, barsId, groups, groupKey, color, pagerId, pageInfoId) {
  var GRADE_PAGE_SIZE = 10;
  var $pills = $('#' + pillsId);
  var $bars  = $('#' + barsId);
  $pills.empty();

  var allGrades = {};
  groups.forEach(function (g) {
    g.grades.forEach(function (gr) {
      allGrades[gr.name] = (allGrades[gr.name] || 0) + gr.weight;
    });
  });

  // sorted grade entries: [[name, weight], ...]
  var currentGrades = null;
  var currentPage   = 0;

  function renderBars(grades, page) {
    currentGrades = grades;
    currentPage   = page || 0;
    var sorted     = Object.keys(grades).sort(function (a, b) { return grades[b] - grades[a]; });
    var totalPages = Math.ceil(sorted.length / GRADE_PAGE_SIZE);
    var pageItems  = sorted.slice(currentPage * GRADE_PAGE_SIZE, (currentPage + 1) * GRADE_PAGE_SIZE);
    var total      = Object.values(grades).reduce(function (s, v) { return s + v; }, 0);
    var max        = Math.max.apply(null, Object.values(grades));
    var html       = '';
    pageItems.forEach(function (name) {
      var w     = grades[name];
      var pct   = max   > 0 ? (w / max   * 100).toFixed(1) : 0;
      var share = total > 0 ? (w / total * 100).toFixed(0)  : 0;
      html += '<div class="breakdown-bar-wrap">' +
        '<div class="breakdown-bar-label"><span>' + name + '</span><span>' + formatNum(w) + ' kg (' + share + '%)</span></div>' +
        '<div class="breakdown-bar-track"><div class="breakdown-bar-fill" style="width:' + pct + '%;background:' + color + ';"></div></div>' +
      '</div>';
    });
    $bars.html(html || '<p class="text-muted">No data.</p>');

    if (pagerId) {
      if (totalPages > 1) {
        $('#' + pagerId).show();
        $('#' + pageInfoId).text((currentPage + 1) + ' / ' + totalPages);
        $('#' + pagerId + ' button:first').prop('disabled', currentPage === 0);
        $('#' + pagerId + ' button:last').prop('disabled', currentPage >= totalPages - 1);
      } else {
        $('#' + pagerId).hide();
      }
    }
  }

  // expose page function on the element for external pager buttons
  $bars.data('gradePage', function (dir) {
    var sorted     = Object.keys(currentGrades).sort(function (a, b) { return currentGrades[b] - currentGrades[a]; });
    var totalPages = Math.ceil(sorted.length / GRADE_PAGE_SIZE);
    currentPage    = Math.max(0, Math.min(currentPage + dir, totalPages - 1));
    renderBars(currentGrades, currentPage);
  });

  var $all = $('<button class="btn btn-sm btn-secondary active mr-1 mb-1">All</button>');
  $all.on('click', function () {
    $pills.find('button').removeClass('active btn-secondary').addClass('btn-outline-secondary');
    $(this).removeClass('btn-outline-secondary').addClass('btn-secondary active');
    renderBars(allGrades, 0);
  });
  $pills.append($all);

  groups.forEach(function (g) {
    var label = g[groupKey] || 'Unknown';
    var $btn  = $('<button class="btn btn-sm btn-outline-secondary mr-1 mb-1"></button>').text(label);
    $btn.on('click', function () {
      $pills.find('button').removeClass('active btn-secondary').addClass('btn-outline-secondary');
      $(this).removeClass('btn-outline-secondary').addClass('btn-secondary active');
      var gradeObj = {};
      g.grades.forEach(function (gr) { gradeObj[gr.name] = gr.weight; });
      renderBars(gradeObj, 0);
    });
    $pills.append($btn);
  });

  renderBars(allGrades, 0);
}

/* ── UI helpers ─────────────────────────────────────────── */
function toggleCard(bodyId, chevronId) {
  var $body    = $('#' + bodyId);
  var $chevron = $('#' + chevronId);
  $body.slideToggle(150, function () {
    if ($body.is(':visible')) {
      $chevron.removeClass('fa-chevron-right').addClass('fa-chevron-down');
    } else {
      $chevron.removeClass('fa-chevron-down').addClass('fa-chevron-right');
    }
  });
}

/* ── Formatters ─────────────────────────────────────────── */
function formatNum(val) {
  var n = parseFloat(val) || 0;
  return n.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatCurrencyMap(map) {
  if (!map || typeof map !== 'object') return '—';
  var keys = Object.keys(map);
  if (keys.length === 0) return '—';
  return keys.map(function (cur) {
    var n = parseFloat(map[cur]) || 0;
    return (cur || '?') + ' ' + n.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }).join('<br>');
}
