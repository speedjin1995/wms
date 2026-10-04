/* ============================================================
   tab_repacking.js — Repacking tab logic
   ============================================================ */

$(function () {
  // Expand a source product to see the targets it was repacked into
  $('#rpBreakdown').on('click', '.rp-source-row', function () {
    $('#rpTargets' + $(this).data('idx')).slideToggle(150);
    $(this).find('.rp-chevron').toggleClass('fa-chevron-right fa-chevron-down');
  });
});

function loadRepacking() {
  if (!$('#tabRepacking').length) return;

  var params = $.extend(getDateParams(), { action: 'dashboard' });

  $.post('php/modules/repacking/api.php', params, function (obj) {
    if (obj.status !== 'success') return;

    var s = obj.message.summary;
    $('#rpTotalWeight').text(formatNum(s.total_weight));
    $('#rpRecordCount').text(s.record_count || 0);
    $('#rpLocalWeight').text(formatNum(s.local_weight));
    $('#rpExportWeight').text(formatNum(s.export_weight));

    renderRepackBreakdown(obj.message.breakdown || []);
  });
}

function renderRepackBreakdown(items) {
  if (items.length === 0) {
    $('#rpBreakdown').html('<p class="text-muted">No data.</p>');
    return;
  }

  var grandTotal = items.reduce(function (sum, i) { return sum + (parseFloat(i.total_weight) || 0); }, 0);
  var html = '';

  items.forEach(function (item, idx) {
    var pct = grandTotal > 0 ? (parseFloat(item.total_weight) / grandTotal * 100).toFixed(1) : 0;

    html += '<div class="card mb-2 shadow-sm">' +
      '<div class="card-header py-2 px-3 rp-source-row" data-idx="' + idx + '" style="cursor:pointer;background:#f4f6f9;">' +
        '<div class="d-flex justify-content-between align-items-center">' +
          '<div><i class="fas fa-chevron-right rp-chevron mr-2" style="font-size:11px;color:#6c757d;"></i><strong>' + escapeRepackText(item.name) + '</strong></div>' +
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
      '<div id="rpTargets' + idx + '" style="display:none;">' +
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

  $('#rpBreakdown').html(html);
}

function escapeRepackText(text) {
  return $('<div>').text(text || '').html();
}
