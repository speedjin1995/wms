/* ============================================================
   tab_grading.js — Grading tab logic
   ============================================================ */

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

    /* group by product */
    var grouped    = {};
    var grandTotal = 0;
    items.forEach(function (item) {
      var p = item.product_name || '—';
      if (!grouped[p]) grouped[p] = { total: 0, grades: [] };
      grouped[p].total += parseFloat(item.total_weight) || 0;
      grouped[p].grades.push(item);
      grandTotal += parseFloat(item.total_weight) || 0;
    });

    var html = '';
    var idx  = 0;
    Object.keys(grouped).forEach(function (product) {
      var g   = grouped[product];
      var pct = grandTotal > 0 ? (g.total / grandTotal * 100).toFixed(1) : 0;

      html += '<div class="card mb-2 shadow-sm">' +
        '<div class="card-header py-2 px-3 gr-product-row" data-idx="' + idx + '" style="cursor:pointer;background:#f4f6f9;">' +
          '<div class="d-flex justify-content-between align-items-center">' +
            '<div><i class="fas fa-chevron-right gr-chevron mr-2" style="font-size:11px;color:#6c757d;"></i><strong>' + product + '</strong></div>' +
            '<div class="text-right">' +
              '<span class="badge badge-secondary mr-2">' + pct + '%</span>' +
              '<span class="font-weight-bold">' + formatNum(g.total) + ' kg</span>' +
            '</div>' +
          '</div>' +
          '<div class="mt-1"><div style="background:#dee2e6;border-radius:4px;height:6px;">' +
            '<div style="width:' + pct + '%;background:#6f42c1;border-radius:4px;height:6px;"></div>' +
          '</div></div>' +
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

    $('#grProductBreakdown').off('click', '.gr-product-row').on('click', '.gr-product-row', function () {
      var i      = $(this).data('idx');
      var $grades = $('#gr-grades-' + i);
      var $icon   = $(this).find('.gr-chevron');
      $grades.slideToggle(150);
      $icon.toggleClass('fa-chevron-right fa-chevron-down');
    });
  });
}
