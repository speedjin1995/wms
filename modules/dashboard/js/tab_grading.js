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

    var recvGroups = toGradeDistFormat(obj.receivingBreakdown || []);
    var grdGroups  = toGradeDistFormat(obj.gradingBreakdown   || []);

    if (recvGroups.length > 0) {
      $('#grGradeRecvWrap').show();
      var recvTotal = recvGroups.reduce(function (s, p) { return s + p.grades.reduce(function (a, g) { return a + g.weight; }, 0); }, 0);
      $('#grGradeRecvTotal').text(formatNum(recvTotal) + ' kg');
      renderGradeDist('grGradeRecvPills', 'grGradeRecvBars', recvGroups, 'product', '#17a2b8', 'grGradeRecvPager', 'grGradeRecvPageInfo');
    } else {
      $('#grGradeRecvWrap').hide();
    }

    if (grdGroups.length > 0) {
      $('#grGradeGrdWrap').show();
      var grdTotal = grdGroups.reduce(function (s, p) { return s + p.grades.reduce(function (a, g) { return a + g.weight; }, 0); }, 0);
      $('#grGradeGrdTotal').text(formatNum(grdTotal) + ' kg');
      renderGradeDist('grGradeGrdPills', 'grGradeGrdBars', grdGroups, 'product', '#6f42c1', 'grGradeGrdPager', 'grGradeGrdPageInfo');
    } else {
      $('#grGradeGrdWrap').hide();
    }

    $('#grGradeRow').toggle(recvGroups.length > 0 || grdGroups.length > 0);
  });
}

// Transform flat [{product_name, grade_name, total_weight}] into renderGradeDist format
function toGradeDistFormat(items) {
  var map = {};
  items.forEach(function (item) {
    var p = item.product_name || 'Unknown';
    if (!map[p]) map[p] = { product: p, grades: [] };
    map[p].grades.push({ name: item.grade_name || '—', weight: parseFloat(item.total_weight) || 0 });
  });
  return Object.values(map);
}

function grGradeRecvPageFn(dir) { $('#grGradeRecvBars').data('gradePage')(dir); }
function grGradeGrdPageFn(dir)  { $('#grGradeGrdBars').data('gradePage')(dir); }
