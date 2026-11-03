<?php
session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
  exit;
}

if (($_SESSION['featureFlags']['include_delivery_order'] ?? 'N') != 'Y') {
  exit;
}

$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];
$t = function ($key, $default = '') use ($languageArray, $language) { return $languageArray[$key][$language] ?? $default; };
?>

<div class="content page-modern">
  <div class="container-fluid">

    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title"><i class="fas fa-file-alt"></i> <?=$t('do_receiving_report_code', 'Daily Receiving Report')?></h1>
    </div>

    <!-- Filter Card -->
    <div class="card filter-card">
      <div class="card-body">
        <div class="filter-row">
          <div class="filter-group">
            <label class="filter-label"><?=$t('date_code', 'Date')?></label>
            <div class="input-group date" id="doReportDatePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#doReportDatePicker" id="doReportDate"/>
              <div class="input-group-append" data-target="#doReportDatePicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>
          <div class="filter-group">
            <label class="filter-label"><?=$t('supplier_code', 'Supplier')?></label>
            <select class="form-control" id="doReportSupplier">
              <option value=""><?=$t('all_suppliers_code', 'All Suppliers')?></option>
            </select>
          </div>
          <div class="filter-group filter-group-action ml-auto">
            <label class="filter-label">&nbsp;</label>
            <div class="d-flex">
              <button type="button" class="btn btn-filter btn-filter-primary" id="doReportSearchBtn">
                <i class="fas fa-search"></i> <?=$t('search_code', 'Search')?>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Results Card -->
    <div class="card results-card show-dt-controls">
      <div class="card-header">
        <div class="results-header-left">
          <h3 class="results-title"><i class="fas fa-list"></i> <?=$t('do_receiving_report_code', 'Daily Receiving Report')?></h3>
        </div>
        <div class="results-header-right d-flex" style="gap:0.5rem;">
          <button type="button" class="btn btn-action btn-action-warning" id="doReportPdfBtn">
            <i class="fas fa-file-pdf"></i> <?=$t('export_pdf_code', 'Export PDF')?>
          </button>
          <button type="button" class="btn btn-action btn-action-success" id="doReportExcelBtn">
            <i class="fas fa-file-excel"></i> <?=$t('export_excel_code', 'Export Excel')?>
          </button>
        </div>
      </div>
      <div class="card-body">
        <table class="table data-table" id="doReportTable">
          <thead>
            <tr>
              <th><?=$t('supplier_code', 'Supplier')?></th>
              <th><?=$t('serial_no_code', 'Serial No')?></th>
              <th><?=$t('category_code', 'Category')?></th>
              <th><?=$t('product_code', 'Product')?></th>
              <th><?=$t('grade_code', 'Grade')?></th>
              <th class="text-right"><?=$t('received_weight_code', 'Received Weight')?></th>
              <th class="text-right"><?=$t('do_weight_code', 'DO Weight')?></th>
              <th class="text-right"><?=$t('weight_loss_code', 'Weight Loss')?></th>
              <th class="text-right">%</th>
            </tr>
          </thead>
          <tfoot>
            <tr style="background:#f8fafc;">
              <th><?=$t('total_code', 'Total')?></th>
              <th></th>
              <th></th>
              <th></th>
              <th></th>
              <th class="text-right"></th>
              <th class="text-right"></th>
              <th class="text-right text-primary"></th>
              <th class="text-right text-primary"></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

  </div>
</div>

<style>
  #doReportTable tr.do-group-header td { background: #eef2f7; font-weight: 600; }
  #doReportTable tr.do-group-subtotal td { font-weight: 600; border-bottom: 2px solid #e2e8f0; }
</style>

<script>
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
            $('<td colspan="9">').text('<?=$t('do_no_code', 'DO No')?>: ' + (d.do_no || ''))
          ));
        }
        groupReceived += parseFloat(d.received_weight) || 0;
        groupDo += parseFloat(d.do_weight) || 0;

        if (i === data.length - 1 || data[i + 1].do_no !== d.do_no) {
          var groupLoss = Math.round((groupDo - groupReceived) * 100) / 100;
          $(nodes[i]).after($('<tr class="do-group-subtotal">').append(
            $('<td colspan="5">').text('<?=$t('subtotal_code', 'Subtotal')?>'),
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
    'language': {
      'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
      'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
    }
  });
}
</script>
