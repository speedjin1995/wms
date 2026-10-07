// Loading order list / entry page. Requires `loadingPermissions`, `loadingLookups` and `loadingText`.

// 1. Variables
var loadingApi = 'php/modules/loadingOrders/api.php';
var loadingTable;
var loadedBatches = {};
var statusClasses = { pending: 'warning', partial: 'info', completed: 'success' };

// 2. Document ready
$(document).ready(function () {
  $('#fromDatePicker, #toDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: new Date()
  });

  $('#loadingDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY HH:mm'
  });

  $('.select2').each(function () {
    $(this).select2({
      allowClear: true,
      placeholder: loadingText.pleaseSelect,
      dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-content') : undefined
    });
  });

  $('#batchNo').select2({
    placeholder: loadingText.selectBatches,
    dropdownParent: $('#extendModal .modal-content'),
    width: '100%'
  });

  loadingTable = $('#loadingTable').DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: true,
    serverMethod: 'post',
    searching: true,
    order: [[1, 'asc']],
    columnDefs: [{ orderable: false, targets: [0] }],
    language: {
      emptyTable: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title">' + escapeHtml(loadingText.noRecordsFound) + '</div><div class="empty-message">' + escapeHtml(loadingText.noRecordsMessage) + '</div></div>',
      zeroRecords: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title">' + escapeHtml(loadingText.noMatchingRecords) + '</div><div class="empty-message">' + escapeHtml(loadingText.noMatchingMessage) + '</div></div>'
    },
    ajax: {
      url: loadingApi,
      data: function (d) {
        return $.extend(d, filterParams(), { action: 'list' });
      }
    },
    columns: [
      { data: 'loading_no', render: $.fn.dataTable.render.text() },
      { data: 'loading_date', render: $.fn.dataTable.render.text() },
      { data: 'status', render: function (data) { return statusBadge(data); } },
      { data: 'shipmentType', render: $.fn.dataTable.render.text() },
      {
        data: 'id',
        responsivePriority: 1,
        orderable: false,
        className: 'action-button',
        render: function (data) {
          return '<div class="d-flex flex-nowrap" style="gap:4px;">' + actionButtons(data) + '</div>';
        }
      }
    ]
  });

  // Expand / collapse row details on row click
  $('#loadingTable tbody').on('click', 'tr', function (e) {
    if ($(e.target).closest('td').hasClass('action-button') || $(e.target).closest('button, select, input').length) {
      return;
    }

    var tr = $(this);
    var row = loadingTable.row(tr);
    if (!row.data()) {
      return;
    }

    if (row.child.isShown()) {
      row.child.hide();
      tr.removeClass('shown');
      return;
    }

    $.post(loadingApi, { action: 'get', id: row.data().id }, function (obj) {
      if (obj.status === 'success') {
        row.child(formatExpandedRow(obj.message)).show();
        tr.addClass('shown');
      } else {
        toastr.error(obj.message, 'Failed:');
      }
    }).fail(function () {
      toastr.error('Something went wrong', 'Failed:');
    });
  });

  $('#filterSearch').on('click', function () {
    loadingTable.ajax.reload();
  });

  $('#addEntry').on('click', function () {
    newEntry();
  });

  // Selecting a batch adds its loadable items; deselecting removes them
  $('#batchNo').on('change', function () {
    syncBatchRows();
  });

  $('#extendForm').validate($.extend(validationOptions(saveEntry), { ignore: ':hidden, #itemDetailsTable :input, #batchNo' }));
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
    status: $('#statusFilter').val() || 'all',
    shipmentType: $('#shipmentTypeFilter').val() || 'all'
  };
}

function statusBadge(status) {
  return '<span class="badge badge-' + (statusClasses[status] || 'secondary') + '">' + escapeHtml(status) + '</span>';
}

function actionButtons(id) {
  var buttons = '';
  if (loadingPermissions.allowEdit) {
    buttons += '<button type="button" onclick="edit(' + id + ')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button>';
  }
  if (loadingPermissions.allowDelete) {
    buttons += '<button type="button" onclick="deactivate(' + id + ')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>';
  }
  return buttons;
}

function customerOptions() {
  var html = '<option value="">' + escapeHtml(loadingText.selectCustomer) + '</option>';
  $.each(loadingLookups.customers, function (i, customer) {
    html += '<option value="' + customer.id + '">' + escapeHtml(customer.customer_name) + '</option>';
  });
  return html;
}

// Item row for a packaging batch item (d = batch item, with customer / time / remarks when already loaded)
function addItemRow(d) {
  var row = $('<tr data-batch="' + escapeHtml(d.packaging_batch_id) + '" data-item="' + escapeHtml(d.id) + '">' +
    '<td>' + escapeHtml(d.batch_no) + '</td>' +
    '<td>' + escapeHtml(d.product_name) + '</td>' +
    '<td>' + escapeHtml(d.grade_name) + '</td>' +
    '<td>' + escapeHtml(d.packaging_size_name) + '</td>' +
    '<td><input type="time" class="form-control form-control-sm item-time" value="' + escapeHtml(d.loading_time || '') + '"></td>' +
    '<td><select class="form-control form-control-sm item-customer">' + customerOptions() + '</select></td>' +
    '<td><input type="text" class="form-control form-control-sm item-remarks" value="' + escapeHtml(d.remarks || '') + '"></td>' +
    '<td><button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItemRow(this)"><i class="fas fa-trash"></i></button></td>' +
    '</tr>');

  $('#itemDetailsTable').append(row);

  row.find('.item-customer').val(d.customer_id || '').select2({
    allowClear: true,
    placeholder: loadingText.selectCustomer,
    dropdownParent: $('#extendModal .modal-content'),
    width: '100%'
  });
}

function removeItemRow(button) {
  $(button).closest('tr').remove();
  updateCounts();
}

function updateCounts() {
  $('#selectedBatchCount').val(($('#batchNo').val() || []).length);
  $('#totalItemRecords').val($('#itemDetailsTable tr').length);
}

// Drop rows of deselected batches and fetch the items of newly selected ones
function syncBatchRows() {
  var selected = $('#batchNo').val() || [];

  $.each(Object.keys(loadedBatches), function (i, batchId) {
    if (selected.indexOf(batchId) === -1) {
      delete loadedBatches[batchId];
      $('#itemDetailsTable tr[data-batch="' + batchId + '"]').remove();
    }
  });

  $.each(selected, function (i, batchId) {
    if (loadedBatches[batchId]) {
      return;
    }
    loadedBatches[batchId] = true;

    $.post(loadingApi, { action: 'batchItems', batchId: batchId, orderId: $('#id').val() }, function (obj) {
      if (!loadedBatches[batchId]) {
        return;
      }
      if (obj.status !== 'success') {
        toastr.error(obj.message || 'Something went wrong', 'Failed:');
        return;
      }
      $.each(obj.items, function (j, item) {
        addItemRow(item);
      });
      updateCounts();
    }).fail(function () {
      toastr.error('Something went wrong', 'Failed:');
    });
  });

  updateCounts();
}

function resetForm() {
  loadedBatches = {};
  $('#extendForm').validate().resetForm();
  $('#extendModal').find('#id, #loadingNo, #remarks').val('');
  $('#batchNo option.extra-batch').remove();
  $('#batchNo').val(null).trigger('change.select2');
  $('#itemDetailsTable').empty();
  updateCounts();
}

function newEntry() {
  resetForm();
  $('#loadingDatePicker').datetimepicker('date', moment());
  $('#shipmentType').val('').trigger('change');
  $('#extendModal').modal('show');
}

function edit(id) {
  $('#spinnerLoading').show();

  $.post(loadingApi, { action: 'get', id: id }, function (obj) {
    if (obj.status !== 'success') {
      toastr.error(obj.message || 'Error loading data', 'Failed:');
      return;
    }

    var msg = obj.message;
    resetForm();
    $('#id').val(msg.id);
    $('#loadingNo').val(msg.loading_no);
    $('#remarks').val(msg.remarks);
    $('#shipmentType').val(msg.shipment_type).trigger('change');

    if (msg.loading_date) {
      $('#loadingDatePicker').datetimepicker('date', moment(msg.loading_date, 'YYYY-MM-DD HH:mm:ss'));
    } else {
      $('#loadingDatePicker').datetimepicker('clear');
    }

    // Batches on this order (completed batches are not in the dropdown yet)
    var batchIds = [];
    $.each(msg.items || [], function (i, item) {
      var batchId = String(item.packaging_batch_id);
      if (batchIds.indexOf(batchId) === -1) {
        batchIds.push(batchId);
        if (!$('#batchNo option[value="' + batchId + '"]').length) {
          $('#batchNo').append($('<option class="extra-batch">').val(batchId).text(item.batch_no));
        }
      }
      loadedBatches[batchId] = true;
      addItemRow($.extend({}, item, { id: item.packaging_batch_item_id }));
    });
    $('#batchNo').val(batchIds).trigger('change.select2');
    updateCounts();

    $('#extendModal').modal('show');
  }).fail(function () {
    toastr.error('Something went wrong', 'Failed:');
  }).always(function () {
    $('#spinnerLoading').hide();
  });
}

function saveEntry() {
  var rows = $('#itemDetailsTable tr');
  if (!rows.length) {
    toastr.error('Please select at least one batch with items.', 'Validation Error:');
    return;
  }

  var postData = {
    action: 'save',
    id: $('#id').val(),
    loadingDate: $('#loadingDate').val(),
    shipmentType: $('#shipmentType').val(),
    remarks: $('#remarks').val()
  };

  var error = '';
  rows.each(function (i) {
    var row = $(this);
    var prefix = 'items[' + i + ']';
    var customer = row.find('.item-customer').val();
    var time = row.find('.item-time').val();

    if (!customer) {
      error = 'Row ' + (i + 1) + ': Customer is required.';
    } else if (!time) {
      error = 'Row ' + (i + 1) + ': Time is required.';
    }
    if (error) {
      return false;
    }

    postData[prefix + '[packaging_batch_item_id]'] = row.attr('data-item');
    postData[prefix + '[customer_id]'] = customer;
    postData[prefix + '[loading_time]'] = time;
    postData[prefix + '[remarks]'] = row.find('.item-remarks').val();
  });

  if (error) {
    toastr.error(error, 'Validation Error:');
    return;
  }

  $('#spinnerLoading').show();
  $('#saveButton').prop('disabled', true);

  $.post(loadingApi, postData, function (obj) {
    if (obj.status === 'success') {
      $('#extendModal').modal('hide');
      toastr.success(obj.message, 'Success:');
      loadingTable.ajax.reload(null, false);
    } else {
      toastr.error(obj.message || 'Something went wrong', 'Failed:');
    }
  }).fail(function () {
    toastr.error('Something went wrong', 'Failed:');
  }).always(function () {
    $('#saveButton').prop('disabled', false);
    $('#spinnerLoading').hide();
  });
}

function deactivate(id) {
  if (confirm(loadingText.confirmDelete)) {
    $('#cancelId').val(id);
    $('#cancelReason').val('');
    $('#cancelForm').validate().resetForm();
    $('#cancelModal').modal('show');
  }
}

function cancelEntry() {
  $('#spinnerLoading').show();
  $('#submitCancel').prop('disabled', true);

  $.post(loadingApi, $('#cancelForm').serialize() + '&action=cancel', function (obj) {
    if (obj.status === 'success') {
      $('#cancelModal').modal('hide');
      toastr.success(obj.message, 'Success:');
      loadingTable.ajax.reload(null, false);
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

function formatExpandedRow(row) {
  var e = escapeHtml;
  var kpi = function (label, valueHtml) {
    return '<div class="kpi-card"><div class="kpi-label">' + e(label) + '</div><div class="kpi-value">' + valueHtml + '</div></div>';
  };

  var rows = '';
  $.each(row.items || [], function (i, d) {
    rows += '<tr>' +
      '<td>' + e(d.batch_no) + '</td>' +
      '<td>' + e(d.product_name) + '</td>' +
      '<td><span class="grade-badge">' + e(d.grade_name) + '</span></td>' +
      '<td>' + e(d.packaging_size_name) + '</td>' +
      '<td class="text-right text-mono">' + e(d.units_per_box) + '</td>' +
      '<td>' + e(d.customer_name) + '</td>' +
      '<td class="text-center text-muted">' + e(d.loading_time) + '</td>' +
      '<td>' + e(d.remarks) + '</td>' +
      '</tr>';
  });
  if (!rows) {
    rows = '<tr><td colspan="8" class="text-center text-muted">' + e(loadingText.noItems) + '</td></tr>';
  }

  return '<div class="expanded-row-content">' +
    '<div class="expanded-header">' +
    '<div><div class="expanded-header-title">' + e(row.loading_no) + '</div><div class="expanded-header-subtitle">' + e(row.shipmentType || '-') + '</div></div>' +
    '<div class="expanded-actions">' + actionButtons(row.id) + '</div>' +
    '</div>' +
    '<div class="kpi-row">' +
    kpi(loadingText.loadingDate, e(row.loading_date ? moment(row.loading_date, 'YYYY-MM-DD HH:mm:ss').format('DD/MM/YYYY HH:mm') : '-')) +
    kpi(loadingText.shipmentTypes, e(row.shipmentType || '-')) +
    kpi(loadingText.status, statusBadge(row.status)) +
    kpi(loadingText.items, e((row.items || []).length)) +
    '</div>' +
    (row.remarks ? '<div class="info-section"><div class="info-remark"><span class="info-item-label">' + e(loadingText.remark) + '</span><span class="info-item-value">' + e(row.remarks) + '</span></div></div>' : '') +
    '<div class="details-section">' +
    '<div class="details-header"><span class="details-title">' + e(loadingText.weightDetails) + '</span></div>' +
    '<div class="table-responsive">' +
    '<table class="table details-table mb-0">' +
    '<thead><tr><th>' + e(loadingText.batchNo) + '</th><th>' + e(loadingText.product) + '</th><th>' + e(loadingText.grade) + '</th>' +
    '<th>' + e(loadingText.packagingSize) + '</th><th class="text-right">' + e(loadingText.unitPerBox) + '</th><th>' + e(loadingText.customer) + '</th>' +
    '<th class="text-center">' + e(loadingText.time) + '</th><th>' + e(loadingText.remark) + '</th></tr></thead>' +
    '<tbody>' + rows + '</tbody></table>' +
    '</div>' +
    '</div>' +
    '</div>';
}
