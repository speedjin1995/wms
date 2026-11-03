var doReportTable;

$(function() {
  $('#doReportDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: new Date()
  });

  $('#doReportSupplier').select2({ width: '220px' });

  $.post('php/modules/deliveryOrder/api.php', { action: 'options' }, function(res) {
    if (res.status !== 'success') {
      return;
    }

    res.data.suppliers.forEach(function(s) {
      $('#doReportSupplier').append($('<option>').val(s.id).text((s.supplier_code ? s.supplier_code + ' - ' : '') + s.supplier_name));
    });
  }, 'json');

  $('#doReportSearchBtn').on('click', function() {
    doReportTable.ajax.reload();
  });

  $('#doReportExcelBtn').on('click', function() {
    window.open('php/modules/deliveryOrder/api.php?action=report_excel&' + doReportParams());
  });

  $('#doReportPdfBtn').on('click', function() {
    window.open('php/modules/deliveryOrder/api.php?action=report_pdf&' + doReportParams());
  });

  loadDoReport();
});

function doReportParams() {
  return $.param({
    date: $('#doReportDate').val() || '',
    supplier: $('#doReportSupplier').val() || ''
  });
}

function loadDoReport() {
  var weightColumn = function(d) {
    return (parseFloat(d) || 0).toFixed(2);
  };
  var textColumn = function(d) {
    return $('<div>').text(d || '').html();
  };
  var loss = function(d) {
    return Math.round(((parseFloat(d.do_weight) || 0) - (parseFloat(d.received_weight) || 0)) * 100) / 100;
  };
  var percent = function(lossValue, doWeight) {
    return (lossValue !== 0 && doWeight > 0) ? (lossValue / doWeight * 100).toFixed(2) + '%' : '';
  };

  doReportTable = $('#doReportTable').DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: false,
    // Rows stay in DO No order so the DO groups stay together, like the printed report
    ordering: false,
    paging: false,
    ajax: {
      url: 'php/modules/deliveryOrder/api.php',
      type: 'POST',
      data: function(d) {
        d.action = 'report';
        d.date = $('#doReportDate').val() || '';
        d.supplier = $('#doReportSupplier').val() || '';
      },
      dataSrc: function(json) {
        return json.status === 'success' ? json.data : [];
      }
    },
    columns: [
      { data: 'supplier_name', className: 'all', render: textColumn },
      { data: 'serial_no', render: textColumn },
      { data: 'category_name', render: textColumn },
      { data: 'product_name', render: textColumn },
      { data: 'grade_name', render: textColumn },
      { data: 'received_weight', className: 'text-right', render: weightColumn },
      { data: 'do_weight', className: 'text-right', render: weightColumn },
      {
        data: null,
        className: 'text-right text-primary',
        render: function(d) {
          var value = loss(d);
          return value !== 0 ? value.toFixed(2) : '';
        }
      },
      {
        data: null,
        className: 'text-right text-primary',
        render: function(d) {
          return percent(loss(d), parseFloat(d.do_weight) || 0);
        }
      }
    ],
    drawCallback: function() {
      // DO No header above each group and a subtotal row below it, same as Excel/PDF
      var rows = this.api().rows({ page: 'current' });
      var nodes = rows.nodes();
      var data = rows.data();
      var groupReceived = 0;
      var groupDo = 0;

      for (var i = 0; i < data.length; i++) {
        var d = data[i];
        if (i === 0 || data[i - 1].do_no !== d.do_no) {
          groupReceived = 0;
          groupDo = 0;
          $(nodes[i]).before($('<tr class="do-group-header">').append(
            $('<td colspan="9">').text(doReportText.doNo + ': ' + (d.do_no || ''))
          ));
        }
        groupReceived += parseFloat(d.received_weight) || 0;
        groupDo += parseFloat(d.do_weight) || 0;

        if (i === data.length - 1 || data[i + 1].do_no !== d.do_no) {
          var groupLoss = Math.round((groupDo - groupReceived) * 100) / 100;
          $(nodes[i]).after($('<tr class="do-group-subtotal">').append(
            $('<td colspan="5">').text(doReportText.subtotal),
            $('<td class="text-right">').text(groupReceived.toFixed(2)),
            $('<td class="text-right">').text(groupDo.toFixed(2)),
            $('<td class="text-right text-primary">').text(groupLoss.toFixed(2)),
            $('<td class="text-right text-primary">').text(percent(groupLoss, groupDo))
          ));
        }
      }
    },
    footerCallback: function() {
      var totalReceived = 0;
      var totalDo = 0;
      this.api().rows().data().each(function(d) {
        totalReceived += parseFloat(d.received_weight) || 0;
        totalDo += parseFloat(d.do_weight) || 0;
      });
      var totalLoss = Math.round((totalDo - totalReceived) * 100) / 100;
      var $cells = $(this.api().table().footer()).find('th');

      $cells.eq(5).text(totalReceived.toFixed(2));
      $cells.eq(6).text(totalDo.toFixed(2));
      $cells.eq(7).text(totalLoss.toFixed(2));
      $cells.eq(8).text(percent(totalLoss, totalDo));
    },
    'language': doReportTableLanguage
  });
}
