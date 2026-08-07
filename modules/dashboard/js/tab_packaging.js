/* ============================================================
   tab_packaging.js — Packaging tab logic
   ============================================================ */

$(function () {
  $('#pkgProductionLine').on('change', function () {
    loadPackaging();
  });
});

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
      $('#pkgProductBreakdown').html('<p class="no-data-txt">No data.</p>');
      return;
    }

    var grandTotal = items.reduce(function (sum, i) { return sum + (parseFloat(i.total_weight) || 0); }, 0);
    var html = '';

    items.forEach(function (item, idx) {
      var pct = grandTotal > 0 ? (parseFloat(item.total_weight) / grandTotal * 100).toFixed(1) : 0;

      html += '<div class="card mb-2 shadow-sm">' +
        '<div class="card-header py-2 px-3 pkg-product-row" data-idx="' + idx + '" style="cursor:pointer;background:#f4f6f9;">' +
          '<div class="d-flex justify-content-between align-items-center">' +
            '<div><i class="fas fa-chevron-right pkg-chevron mr-2" style="font-size:11px;color:#6c757d;"></i><strong>' + item.product_name + '</strong></div>' +
            '<div class="text-right">' +
              '<span class="badge badge-secondary mr-2">' + pct + '%</span>' +
              '<span class="font-weight-bold">' + formatNum(item.total_weight) + ' kg</span>' +
              '<span class="text-muted ml-2" style="font-size:12px;">(' + item.total_boxes + ' boxes)</span>' +
            '</div>' +
          '</div>' +
          '<div class="mt-1"><div style="background:#dee2e6;border-radius:4px;height:6px;">' +
            '<div style="width:' + pct + '%;background:#007bff;border-radius:4px;height:6px;"></div>' +
          '</div></div>' +
        '</div>' +
        '<div class="pkg-grade-rows" id="pkg-grades-' + idx + '" style="display:none;">' +
          '<div class="card-body py-2 px-3">';

      (item.grades || []).forEach(function (grade, gIdx) {
        var gPct = item.total_weight > 0 ? (parseFloat(grade.total_weight) / item.total_weight * 100).toFixed(1) : 0;
        var gradeId = 'pkg-items-' + idx + '-' + gIdx;
        html += '<div class="pkg-grade-item border-bottom">' +
          '<div class="d-flex justify-content-between align-items-center py-1 pkg-grade-row" data-grade="' + gradeId + '" style="cursor:pointer;">' +
            '<span class="text-muted" style="font-size:13px;">' +
              '<i class="fas fa-chevron-right pkg-grade-chevron mr-1" style="font-size:10px;"></i>' +
              '<i class="fas fa-tag mr-1" style="font-size:10px;"></i>' + grade.grade_name +
              ' <span class="badge badge-light border">' + grade.packaging_name + '</span>' +
            '</span>' +
            '<span style="font-size:13px;">' + formatNum(grade.total_weight) + ' kg' +
              ' <span class="text-muted">(' + gPct + '%)</span>' +
              ' &nbsp;|&nbsp; ' + grade.total_boxes + ' boxes' +
            '</span>' +
          '</div>' +
          '<div id="' + gradeId + '" style="display:none;padding:4px 16px 8px;">' +
            '<table class="table table-sm table-bordered mb-0" style="font-size:12px;">' +
              '<thead class="thead-light"><tr>' +
                '<th>Batch No</th><th>Date</th><th>Pcs/Box</th><th>Weight (kg)</th><th>Customer</th>' +
              '</tr></thead><tbody>';
        (grade.items || []).forEach(function (it) {
          html += '<tr>' +
            '<td>' + it.batch_no + '</td>' +
            '<td>' + it.date + '</td>' +
            '<td>' + it.units_per_box + '</td>' +
            '<td>' + formatNum(it.weight) + '</td>' +
            '<td>' + it.customer_name + '</td>' +
          '</tr>';
        });
        html += '</tbody></table></div></div>';
      });

      html += '</div></div></div>';
    });

    $('#pkgProductBreakdown').html(html);

    $('#pkgProductBreakdown').off('click', '.pkg-product-row').on('click', '.pkg-product-row', function () {
      var i = $(this).data('idx');
      $('#pkg-grades-' + i).slideToggle(150);
      $(this).find('.pkg-chevron').toggleClass('fa-chevron-right fa-chevron-down');
    });

    $('#pkgProductBreakdown').off('click', '.pkg-grade-row').on('click', '.pkg-grade-row', function (e) {
      e.stopPropagation();
      var gradeId = $(this).attr('data-grade');
      var $target = document.getElementById(gradeId);
      if ($target) { $($target).slideToggle(150); }
      $(this).find('.pkg-grade-chevron').toggleClass('fa-chevron-right fa-chevron-down');
    });
  });
}
