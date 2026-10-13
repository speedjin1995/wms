// Bulk price update page. Requires `bulkPriceLookups` and `bulkPriceText`.

// 1. Variables
var bulkPriceApi = 'php/modules/wholesales/bulkPriceUpdate/api.php';
var bulkTable;

// 2. Document ready
$(document).ready(function () {
  toastr.options = {
    "closeButton": false,
    "progressBar": false,
    "positionClass": "toast-top-right",
    "timeOut": "5000"
  };

  $('#datePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: new Date()
  });

  $('.select2-filter').select2({
    allowClear: true,
    placeholder: "Please Select",
    width: '100%'
  });

  $('#productFilter').on('change', function () {
    var productId = $(this).val();
    var gradesByProduct = bulkPriceLookups.gradesByProduct;
    $('#gradeFilter').select2('destroy').empty().append($('<option value="">').text(bulkPriceText.all));
    if (productId && gradesByProduct[productId]) {
      $.each(gradesByProduct[productId], function (i, g) {
        $('#gradeFilter').append($('<option>').val(g.units).text(g.units));
      });
    }
    $('#gradeFilter').select2({ allowClear: true, placeholder: "Please Select", width: '100%' });
  });

  $('#transactionStatusFilter').on('change', function () {
    var status = $(this).val();
    if (status === 'RECEIVING') {
      $('#customerFilterGroup').hide();
      $('#supplierFilterGroup').show();
      $('#customerFilter').val('').trigger('change');
    } else {
      $('#customerFilterGroup').show();
      $('#supplierFilterGroup').hide();
      $('#supplierFilter').val('').trigger('change');
    }
  });

  $('#bulkFilterSearch').on('click', function () {
    bulkTable.ajax.reload();
  });

  $('#weightTable').on('click', '.expand-icon', function () {
    var tr = $(this).closest('tr');
    var row = bulkTable.row(tr);
    if (row.child.isShown()) {
      row.child.hide();
      $(this).removeClass('expanded').find('i').removeClass('fa-chevron-down').addClass('fa-chevron-right');
    } else {
      row.child(format(row.data())).show();
      $(this).addClass('expanded').find('i').removeClass('fa-chevron-right').addClass('fa-chevron-down');
    }
  });

  $('#btnPreview').on('click', function () {
    var priceRows = collectPriceRows();
    if (priceRows.length === 0) {
      toastr["error"]("Please enter at least one price.", "Validation Error:");
      return;
    }
    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Loading...');
    $.post(bulkPriceApi, $.extend(filterParams(), { action: 'preview', priceRows: priceRows }), function (obj) {
      if (obj.status === 'success') {
        if (obj.message.length === 0) {
          toastr["error"]("No matching records found.", "Preview:");
        } else {
          var tbody = $('#previewTableBody').empty();
          $.each(obj.message, function (i, r) {
            tbody.append('<tr>' +
              '<td>' + r.serial_no + '</td>' +
              '<td>' + r.start_time + '</td>' +
              '<td>' + r.product_name + '</td>' +
              '<td>' + r.grade + '</td>' +
              '<td class="text-right">' + r.net + '</td>' +
              '<td class="text-right text-muted">' + r.old_price + '</td>' +
              '<td class="text-right text-muted">' + r.old_total + '</td>' +
              '<td class="text-right new-value font-weight-bold">' + r.new_price + '</td>' +
              '<td class="text-right new-value font-weight-bold">' + r.new_total + '</td>' +
            '</tr>');
          });
          $('#confirmCount').text(obj.message.length);
          wizardGoTo(2);
        }
      } else {
        toastr["error"](obj.message, "Failed:");
      }
    }).fail(function () {
      toastr["error"]("Request failed. Please try again.", "Error:");
    }).always(function () {
      $btn.prop('disabled', false).html(bulkPriceText.preview + '<i class="fas fa-arrow-right ml-1"></i>');
    });
  });

  bulkTable = buildTable();
});

// 3. Functions
function filterParams() {
  return {
    date: $('#date').val(),
    status: $('#transactionStatusFilter').val(),
    customer: $('#customerFilter').val(),
    supplier: $('#supplierFilter').val(),
    product: $('#productFilter').val(),
    grade: $('#gradeFilter').val()
  };
}

function format(d) {
  var html = '<div class="child-row-wrapper"><table class="child-table">'
    + '<thead><tr><th>' + bulkPriceText.product + '</th><th>' + bulkPriceText.grade + '</th><th class="text-right">' + bulkPriceText.net + '</th><th class="text-right">' + bulkPriceText.price + '</th><th class="text-right">' + bulkPriceText.total + '</th></tr></thead><tbody>';
  $.each(d.items, function (i, item) {
    html += '<tr><td>' + item.product_name + '</td><td>' + item.grade + '</td><td class="text-right">' + item.net + '</td><td class="text-right">' + item.price + '</td><td class="text-right">' + item.total + '</td></tr>';
  });
  return html + '</tbody></table></div>';
}

function buildTable() {
  return $("#weightTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'order': [[4, 'asc']],
    'drawCallback': function (settings) {
      var info = this.api().page.info();
      var hasRecords = info.recordsTotal > 0;
      $('#resultsCard').show();
      $('#weightTable').toggle(hasRecords);
      $('#emptyState').toggle(!hasRecords);
      $('#resultCount').text(info.recordsTotal);
      $('#btnOpenBulkPrice').prop('disabled', !hasRecords);
    },
    'ajax': {
      'url': bulkPriceApi,
      'data': function (d) {
        $.extend(d, filterParams(), { action: 'list' });
      }
    },
    'columns': [
      {
        data: null, orderable: false, className: 'text-center', width: '50px',
        render: function () {
          return '<span class="expand-icon"><i class="fas fa-chevron-right"></i></span>';
        }
      },
      { data: 'serial_no' },
      { data: 'po_no' },
      { data: 'customer_supplier' },
      { data: 'start_time' },
      {
        data: 'status',
        className: 'text-center',
        render: function (data) {
          var badgeClass = data === 'DISPATCH' ? 'badge-dispatch' : 'badge-receiving';
          var label = data === 'DISPATCH' ? bulkPriceText.dispatch : bulkPriceText.receiving;
          return '<span class="badge-status ' + badgeClass + '">' + label + '</span>';
        }
      },
      {
        data: 'item_count',
        className: 'text-center',
        render: function (data) {
          return '<span class="badge-count">' + data + '</span>';
        }
      }
    ]
  });
}

function openBulkPriceModal() {
  wizardGoTo(1);
  $('#priceInputBody').html('<tr><td colspan="4" class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
  $('#bulkPriceModal').modal('show');

  $.post(bulkPriceApi, $.extend(filterParams(), { action: 'productGrades' }), function (obj) {
    var tbody = $('#priceInputBody').empty();
    if (obj.status === 'success' && obj.message.length > 0) {
      $.each(obj.message, function (i, c) {
        tbody.append(
          '<tr>' +
            '<td>' + c.product_name + '<input type="hidden" class="combo-product" value="' + c.product_id + '"></td>' +
            '<td>' + c.grade + '<input type="hidden" class="combo-grade" value="' + c.grade + '"></td>' +
            '<td>' +
              '<select class="form-control form-control-sm combo-type">' +
                '<option value="Float">' + bulkPriceText.float + '</option>' +
                '<option value="Fixed">' + bulkPriceText.fixed + '</option>' +
              '</select>' +
            '</td>' +
            '<td><input type="number" class="form-control form-control-sm combo-price" step="0.01" min="0" placeholder="0.00"></td>' +
          '</tr>'
        );
      });
    } else {
      tbody.html('<tr><td colspan="4" class="text-center text-muted">No products found.</td></tr>');
    }
  }).fail(function () {
    toastr["error"]("Request failed. Please try again.", "Error:");
  });
}

function wizardGoTo(step) {
  $('#stepInputs, #stepPreview, #stepConfirm').hide();
  $('#btnPreview, #btnBackToInputs, #btnBackToPreview, #btnGoConfirm, #btnConfirm').hide();
  $('#wizardStep1, #wizardStep2, #wizardStep3').removeClass('active done');
  $('#wizardLine1, #wizardLine2').removeClass('done');

  if (step === 1) {
    $('#stepInputs').show();
    $('#btnPreview').show();
    $('#wizardStep1').addClass('active');
  } else if (step === 2) {
    $('#stepPreview').show();
    $('#btnBackToInputs, #btnGoConfirm').show();
    $('#wizardStep1').addClass('done');
    $('#wizardStep2').addClass('active');
    $('#wizardLine1').addClass('done');
  } else {
    $('#stepConfirm').show();
    $('#btnBackToPreview, #btnConfirm').show();
    $('#wizardStep1, #wizardStep2').addClass('done');
    $('#wizardStep3').addClass('active');
    $('#wizardLine1, #wizardLine2').addClass('done');
  }
}

function collectPriceRows() {
  var rows = [];
  $('#priceInputBody tr').each(function () {
    var price = $(this).find('.combo-price').val();
    if (price === '' || price === null || price === undefined) return;
    rows.push({
      product: $(this).find('.combo-product').val(),
      grade: $(this).find('.combo-grade').val(),
      pricingType: $(this).find('.combo-type').val(),
      newPrice: price
    });
  });
  return rows;
}

function confirmBulkUpdate() {
  var $btn = $('#btnConfirm');
  $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Updating...');
  $.post(bulkPriceApi, $.extend(filterParams(), { action: 'update', priceRows: collectPriceRows() }), function (obj) {
    if (obj.status === 'success') {
      $('#bulkPriceModal').modal('hide');
      toastr["success"](obj.message, "Success:");
      bulkTable.ajax.reload();
    } else {
      toastr["error"](obj.message, "Failed:");
    }
  }).fail(function () {
    toastr["error"]("Request failed. Please try again.", "Error:");
  }).always(function () {
    $btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i>' + bulkPriceText.confirmUpdate);
  });
}
