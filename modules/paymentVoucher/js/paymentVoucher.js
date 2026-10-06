// Payment voucher list / entry page. Requires `pvPermissions` and `pvText`.

// 1. Variables
var pvApi = 'php/modules/paymentVoucher/api.php';
var pvTable;

// 2. Document ready
$(document).ready(function () {
  $('#fromDatePicker, #toDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: new Date()
  });

  $('#voucherDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY'
  });

  $('.select2').each(function () {
    $(this).select2({
      allowClear: true,
      placeholder: pvText.pleaseSelect,
      dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-content') : undefined
    });
  });

  pvTable = $('#pvTable').DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: true,
    serverMethod: 'post',
    searching: true,
    order: [[0, 'desc']],
    language: {
      emptyTable: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title">' + escapeHtml(pvText.noRecordsFound) + '</div><div class="empty-message">' + escapeHtml(pvText.noRecordsMessage) + '</div></div>',
      zeroRecords: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title">' + escapeHtml(pvText.noMatchingRecords) + '</div><div class="empty-message">' + escapeHtml(pvText.noMatchingMessage) + '</div></div>'
    },
    ajax: {
      url: pvApi,
      data: function (d) {
        return $.extend(d, filterParams(), { action: 'list' });
      }
    },
    columns: [
      { data: 'voucher_date', render: $.fn.dataTable.render.text() },
      { data: 'voucher_no', render: $.fn.dataTable.render.text() },
      { data: 'entity_name', render: $.fn.dataTable.render.text() },
      { data: 'invoice_no', render: $.fn.dataTable.render.text() },
      { data: 'total_nett_weight', render: $.fn.dataTable.render.text() },
      { data: 'unit_price', render: $.fn.dataTable.render.text() },
      { data: 'final_amount', render: $.fn.dataTable.render.text() },
      {
        data: 'id',
        responsivePriority: 1,
        orderable: false,
        className: 'action-button',
        render: function (data, type, row) {
          return '<div class="d-flex flex-nowrap" style="gap:4px;">' + actionButtons(row) + '</div>';
        }
      }
    ]
  });

  $('#filterSearch').on('click', function () {
    pvTable.ajax.reload();
  });

  $('#transactionStatusFilter').on('change', function () {
    var isReceiving = $(this).val() === 'RECEIVING';
    $('#parentSupplierFilterGroup').toggle(isReceiving);
    $('#parentCustomerFilterGroup').toggle(!isReceiving);
  });

  $('#exportPvReport').on('click', function () {
    exportReport();
  });

  // Header unit price applies to every row
  $('#unitPrice').on('input', function () {
    $('#pvItemsBody .item-unit-price').val($(this).val());
    recalculate();
  });

  $('#pvItemsBody').on('input change', '.item-unit-price', function () {
    recalculate();
  });

  $('#pvForm').validate($.extend(validationOptions(saveEntry), { ignore: ':hidden, #pvItemsBody :input' }));
  $('#printForm').validate(validationOptions(printEntry));
  $('#cancelForm').validate(validationOptions(cancelEntry));
});

// 3. Functions
function escapeHtml(value) {
  return $('<div>').text(value === null || value === undefined ? '' : value).html();
}

function validationOptions(submit) {
  return {
    errorElement: 'span',
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback');
      element.closest('.form-group-modern').append(error);
    },
    highlight: function (element) { $(element).addClass('is-invalid'); },
    unhighlight: function (element) { $(element).removeClass('is-invalid'); },
    submitHandler: function () { submit(); }
  };
}

function filterParams() {
  return {
    fromDate: $('#fromDate').val(),
    toDate: $('#toDate').val(),
    transactionStatus: $('#transactionStatusFilter').val(),
    parentCustomerId: $('#parentCustomerFilter').val() || '',
    parentSupplierId: $('#parentSupplierFilter').val() || ''
  };
}

function actionButtons(row) {
  var parentId = parseInt(row.parent_id, 10) || 0;
  var pvId = parseInt(row.pv_id, 10) || 0;
  var buttons = '';

  if (pvPermissions.allowEdit) {
    buttons += '<button type="button" onclick="openPv(' + parentId + ', ' + pvId + ')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button>';
  }
  if (pvId) {
    buttons += '<button type="button" onclick="openPrint(' + pvId + ')" class="btn btn-sm btn-outline-secondary" title="Print"><i class="fas fa-print"></i></button>';
    if (pvPermissions.allowDelete) {
      buttons += '<button type="button" onclick="deactivate(' + pvId + ')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>';
    }
  }
  return buttons;
}

function openPrintWindow(html) {
  var printWindow = window.open('', '', 'height=' + screen.height + ',width=' + screen.width);
  printWindow.document.write(html);
  printWindow.document.close();
  return printWindow;
}

function exportReport() {
  $('#spinnerLoading').show();

  $.post(pvApi, $.extend(filterParams(), { action: 'exportReport' }), function (obj) {
    if (obj.status === 'success') {
      openPrintWindow(obj.message);
    } else {
      toastr.error(obj.message || 'Something went wrong', 'Failed:');
    }
  }).fail(function () {
    toastr.error('Something went wrong', 'Failed:');
  }).always(function () {
    $('#spinnerLoading').hide();
  });
}

function resetForm() {
  $('#pvForm').validate().resetForm();
  $('#pvId, #pvEntityId, #voucherNo, #invoiceNo, #totalNettWeight, #totalAmount').val('');
  $('#unitPrice').val(0);
  $('#taxRate').val(0);
  $('#pvItemsBody').empty();
  $('#footTotalNett, #footTotalPrice').text('0.00');
  $('#voucherDatePicker').datetimepicker('date', moment());
}

function openPv(parentId, pvId) {
  resetForm();
  $('#pvId').val(pvId || '');
  $('#pvEntityId').val(parentId || '');
  $('#spinnerLoading').show();

  $.post(pvApi, {
    action: 'items',
    parentId: parentId,
    pvId: pvId || '',
    transactionStatus: $('#transactionStatusFilter').val(),
    fromDate: $('#fromDate').val(),
    toDate: $('#toDate').val()
  }, function (obj) {
    if (obj.status !== 'success') {
      toastr.error(obj.message || 'Error loading data', 'Failed:');
      return;
    }

    $('#totalNettWeight').val(obj.total_nett_weight);

    var pv = obj.paymentVoucher || {};
    if (pv.id) {
      $('#voucherNo').val(pv.voucher_no);
      $('#invoiceNo').val($('<textarea>').html(pv.invoice_no || '').text());
      $('#unitPrice').val(pv.unit_price || 0);
      $('#taxRate').val(pv.tax || 0);
      if (pv.voucher_date) {
        $('#voucherDatePicker').datetimepicker('date', moment(pv.voucher_date, 'YYYY-MM-DD'));
      }
    }

    $.each(obj.items, function (i, item) {
      $('#pvItemsBody').append(
        '<tr data-id="' + item.id + '" data-nett="' + item.nett_raw + '">' +
          '<td>' + escapeHtml(item.serial_no) + '</td>' +
          '<td>' + escapeHtml(item.start_time) + '</td>' +
          '<td>' + escapeHtml(item.entity_name) + '</td>' +
          '<td>' + escapeHtml(item.vehicle_no) + '</td>' +
          '<td>' + escapeHtml(item.categories) + '</td>' +
          '<td class="text-right">' + escapeHtml(item.nett) + '</td>' +
          '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm item-unit-price" value="' + escapeHtml(item.unit_price) + '"></td>' +
          '<td class="text-right item-total-price">0.00</td>' +
        '</tr>'
      );
    });
    recalculate();
    $('#pvModal').modal('show');
  }).fail(function () {
    toastr.error('Something went wrong', 'Failed:');
  }).always(function () {
    $('#spinnerLoading').hide();
  });
}

function recalculate() {
  var unitPrice = parseFloat($('#unitPrice').val()) || 0;
  var tax = parseFloat($('#taxRate').val()) || 0;
  var totalNett = 0;
  var totalPrice = 0;

  $('#pvItemsBody tr').each(function () {
    var nett = parseFloat($(this).data('nett')) || 0;
    var rowPrice = parseFloat($(this).find('.item-unit-price').val());
    var nettAmt = (isNaN(rowPrice) ? unitPrice : rowPrice) * nett;
    var total = nettAmt + nettAmt * (tax / 100);
    $(this).find('.item-total-price').text(total.toFixed(2));

    totalNett += nett;
    totalPrice += total;
  });

  $('#footTotalNett').text(totalNett.toFixed(2));
  $('#footTotalPrice').text(totalPrice.toFixed(2));
  $('#totalAmount').val(totalPrice.toFixed(2));
}

function saveEntry() {
  var rows = $('#pvItemsBody tr');
  if (!rows.length) {
    toastr.error('No records loaded.', 'Error:');
    return;
  }

  var formData = new FormData($('#pvForm')[0]);
  formData.append('action', 'save');
  formData.append('transactionStatus', $('#transactionStatusFilter').val());
  rows.each(function (i) {
    formData.append('wholesales[' + i + '][id]', $(this).data('id'));
    formData.append('wholesales[' + i + '][pv_unit_price]', $(this).find('.item-unit-price').val());
  });

  $('#spinnerLoading').show();
  $('#saveButton').prop('disabled', true);

  $.ajax({
    url: pvApi,
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function (obj) {
      if (obj.status === 'success') {
        $('#pvModal').modal('hide');
        toastr.success(obj.message, 'Success:');
        pvTable.ajax.reload(null, false);
      } else {
        toastr.error(obj.message || 'Something went wrong', 'Failed:');
      }
    },
    error: function () {
      toastr.error('Something went wrong when saving', 'Failed:');
    },
    complete: function () {
      $('#saveButton').prop('disabled', false);
      $('#spinnerLoading').hide();
    }
  });
}

function openPrint(pvId) {
  $('#printPvId').val(pvId);
  $('#printSlipType').val('pv');
  $('#printModal').modal('show');
}

function printEntry() {
  var isStatement = $('#printSlipType').val() === 'statement';
  $('#printModal').modal('hide');
  $('#spinnerLoading').show();

  $.post(pvApi, $('#printForm').serialize() + '&action=printSlip', function (obj) {
    if (obj.status !== 'success') {
      toastr.error(obj.message || 'Something went wrong when printing', 'Failed:');
      return;
    }

    var printWindow = openPrintWindow(obj.message);

    // Wait for paged.js (statement) to finish laying out pages before printing
    var pollCount = 0;
    var poll = setInterval(function () {
      pollCount++;
      if (!isStatement || $(printWindow.document).find('.pagedjs_pages').length || pollCount > 60) {
        clearInterval(poll);
        setTimeout(function () {
          printWindow.print();
          printWindow.close();
        }, 300);
      }
    }, 200);
  }).fail(function () {
    toastr.error('Something went wrong when printing', 'Failed:');
  }).always(function () {
    $('#spinnerLoading').hide();
  });
}

function deactivate(pvId) {
  $('#cancelId').val(pvId);
  $('#cancelReason').val('');
  $('#cancelForm').validate().resetForm();
  $('#cancelModal').modal('show');
}

function cancelEntry() {
  $('#spinnerLoading').show();
  $('#submitCancel').prop('disabled', true);

  $.post(pvApi, $('#cancelForm').serialize() + '&action=cancel', function (obj) {
    if (obj.status === 'success') {
      $('#cancelModal').modal('hide');
      toastr.success(obj.message, 'Success:');
      pvTable.ajax.reload(null, false);
    } else {
      toastr.error(obj.message || 'Something went wrong', 'Failed:');
    }
  }).fail(function () {
    toastr.error('Something went wrong', 'Failed:');
  }).always(function () {
    $('#submitCancel').prop('disabled', false);
    $('#spinnerLoading').hide();
  });
}
