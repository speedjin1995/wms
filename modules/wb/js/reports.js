// Weighbridge reports page. Requires wbCommon.js and `wbText`.

// 1. Variables
var reportTable;

// 2. Document ready
$(document).ready(function () {
  wbInitSelect2();
  wbInitFilters();

  reportTable = $('#weightTable').DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: true,
    serverMethod: 'post',
    searching: true,
    order: [[ 1, 'asc' ]],
    language: wbTableLanguage(),
    ajax: {
      url: wbApi,
      data: function (d) {
        return $.extend(d, wbFilters(), { action: 'list' });
      }
    },
    columns: wbColumns()
  });

  $('#filterSearch').on('click', function () {
    reportTable.ajax.reload();
  });

  $('#exportExcel').on('click', function () {
    window.open(wbApi + '?' + exportQuery('exportExcel'));
  });

  $('#exportPdf').on('click', function () {
    window.open(wbApi + '?' + exportQuery('exportPdf'));
  });
});

// 3. Functions
// Filters plus selected IDs (isMulti = Y exports only the ticked rows)
function exportQuery(action) {
  var params = wbFilters();
  params.action = action;
  var ids = wbSelectedIds();

  params.isMulti = ids.length > 0 ? 'Y' : 'N';
  if (ids.length > 0) {
    params.ids = ids.join(',');
  }

  return $.param(params);
}
