// Stock transfer list / entry page. Requires `transferPermissions` and `transferText`.

// 1. Variables
var transferApi = 'php/modules/stockTransfer/api.php';
var transferTable;
var dragRow = null;

// 2. Document ready
$(document).ready(function () {
  $('#fromDatePicker, #toDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: new Date()
  });

  $('.select2').each(function () {
    $(this).select2({
      allowClear: true,
      placeholder: transferText.pleaseSelect,
      dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-content') : undefined,
      width: '100%'
    });
  });

  transferTable = $('#transferTable').DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: true,
    serverMethod: 'post',
    searching: true,
    order: [[3, 'desc']],
    columnDefs: [{ orderable: false, targets: [0] }],
    language: {
      emptyTable: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title">' + escapeHtml(transferText.noRecordsFound) + '</div><div class="empty-message">' + escapeHtml(transferText.noRecordsMessage) + '</div></div>',
      zeroRecords: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title">' + escapeHtml(transferText.noMatchingRecords) + '</div><div class="empty-message">' + escapeHtml(transferText.noMatchingMessage) + '</div></div>'
    },
    ajax: {
      url: transferApi,
      data: function (d) {
        return $.extend(d, { action: 'list', fromDate: $('#fromDate').val(), toDate: $('#toDate').val() });
      }
    },
    columns: [
      { data: 'transfer_no', render: $.fn.dataTable.render.text() },
      { data: 'from_batch_no', render: $.fn.dataTable.render.text() },
      { data: 'to_batch_no', render: $.fn.dataTable.render.text() },
      { data: 'created_date', render: $.fn.dataTable.render.text() },
      { data: 'remarks', render: $.fn.dataTable.render.text() },
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
  $('#transferTable tbody').on('click', 'tr', function (e) {
    if ($(e.target).closest('td').hasClass('action-button') || $(e.target).closest('button, select, input').length) {
      return;
    }

    var tr = $(this);
    var row = transferTable.row(tr);
    if (!row.data()) {
      return;
    }

    if (row.child.isShown()) {
      row.child.hide();
      tr.removeClass('shown');
      return;
    }

    $.post(transferApi, { action: 'get', id: row.data().id }, function (obj) {
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
    transferTable.ajax.reload();
  });

  $('#addEntry').on('click', function () {
    newEntry();
  });

  // Changing either batch reloads both sides, so earlier moves are discarded
  $('#batchA, #batchB').on('change', function () {
    loadBatches();
  });

  // Drag rows between the two batch tables
  $('#transferModal').on('dragstart', '.transfer-zone tr[data-id]', function (e) {
    dragRow = $(this).addClass('dragging');
    e.originalEvent.dataTransfer.effectAllowed = 'move';
    e.originalEvent.dataTransfer.setData('text', $(this).attr('data-id'));
  });

  $('#transferModal').on('dragend', '.transfer-zone tr[data-id]', function () {
    $(this).removeClass('dragging');
    $('.transfer-drop').removeClass('drag-over');
    dragRow = null;
  });

  $('#transferModal').on('dragover', '.transfer-drop', function (e) {
    e.preventDefault();
    e.originalEvent.dataTransfer.dropEffect = 'move';
    $(this).addClass('drag-over');
  });

  $('#transferModal').on('dragleave', '.transfer-drop', function () {
    $(this).removeClass('drag-over');
  });

  $('#transferModal').on('drop', '.transfer-drop', function (e) {
    e.preventDefault();
    $(this).removeClass('drag-over');
    if (dragRow && dragRow.closest('.transfer-drop')[0] !== this) {
      moveRow(dragRow, $(this).attr('data-side'));
    }
  });

  $('#transferModal').on('click', '.btn-transfer', function () {
    var row = $(this).closest('tr');
    moveRow(row, row.closest('.transfer-drop').attr('data-side') === 'A' ? 'B' : 'A');
  });

  $('#transferForm').validate(validationOptions(saveEntry));
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

function actionButtons(id) {
  if (!transferPermissions.allowDelete) {
    return '';
  }
  return '<button type="button" onclick="deactivate(' + id + ')" class="btn btn-sm btn-outline-danger" title="Undo"><i class="fas fa-undo"></i></button>';
}

function arrowButton(side) {
  return '<button type="button" class="btn btn-sm btn-outline-warning btn-transfer"><i class="fas fa-arrow-' + (side === 'A' ? 'right' : 'left') + '"></i></button>';
}

// Row for a pending batch item; origin = the side it was loaded on
function itemRow(item, side) {
  return $('<tr draggable="true" data-id="' + escapeHtml(item.id) + '" data-origin="' + side + '">' +
    '<td>' + escapeHtml(item.product_name) + '</td>' +
    '<td>' + escapeHtml(item.grade_name) + '</td>' +
    '<td>' + escapeHtml(item.packaging_size_name) + '</td>' +
    '<td class="text-right">' + (parseFloat(item.weight) || 0).toFixed(2) + '</td>' +
    '<td class="text-center">' + arrowButton(side) + '</td>' +
    '</tr>');
}

function moveRow(row, targetSide) {
  row.removeClass('dragging').toggleClass('moved', row.attr('data-origin') !== targetSide);
  row.find('td:last').html(arrowButton(targetSide));
  $('#table' + targetSide).append(row);
  updateCounts();
}

function updateCounts() {
  $('#batchACount').text($('#tableA tr[data-id]').length);
  $('#batchBCount').text($('#tableB tr[data-id]').length);
}

function loadBatches() {
  var batchA = $('#batchA').val();
  var batchB = $('#batchB').val();

  $('#tableA, #tableB').empty();
  $('#batchALabel').text(batchA ? $('#batchA option:selected').text() : '-');
  $('#batchBLabel').text(batchB ? $('#batchB option:selected').text() : '-');
  updateCounts();

  if (batchA && batchA === batchB) {
    toastr.error('Batch A and Batch B must be different.', 'Validation Error:');
    return;
  }

  $.each({ A: batchA, B: batchB }, function (side, batchId) {
    if (!batchId) {
      return;
    }
    $.post(transferApi, { action: 'batchItems', batchId: batchId }, function (obj) {
      if ($('#batch' + side).val() !== batchId) {
        return;
      }
      if (obj.status !== 'success') {
        toastr.error(obj.message || 'Something went wrong', 'Failed:');
        return;
      }
      $.each(obj.items, function (i, item) {
        $('#table' + side).append(itemRow(item, side));
      });
      updateCounts();
    }).fail(function () {
      toastr.error('Something went wrong', 'Failed:');
    });
  });
}

function newEntry() {
  $('#transferForm').validate().resetForm();
  $('#transferRemarks').val('');
  $('#batchA, #batchB').val('').trigger('change.select2');
  loadBatches();
  $('#transferModal').modal('show');
}

function saveEntry() {
  var batchA = $('#batchA').val();
  var batchB = $('#batchB').val();
  if (batchA === batchB) {
    toastr.error('Batch A and Batch B must be different.', 'Validation Error:');
    return;
  }

  var postData = { action: 'save', batchA: batchA, batchB: batchB, remarks: $('#transferRemarks').val() };
  var count = 0;
  $.each({ A: batchA, B: batchB }, function (side, batchId) {
    $('#table' + side + ' tr[data-id]').each(function () {
      if ($(this).attr('data-origin') !== side) {
        postData['items[' + count + '][packaging_batch_item_id]'] = $(this).attr('data-id');
        postData['items[' + count + '][to_batch_id]'] = batchId;
        count++;
      }
    });
  });

  if (!count) {
    toastr.warning('No items have been transferred.', 'Info:');
    return;
  }

  $('#spinnerLoading').show();
  $('#saveButton').prop('disabled', true);

  $.post(transferApi, postData, function (obj) {
    if (obj.status === 'success') {
      $('#transferModal').modal('hide');
      toastr.success(obj.message, 'Success:');
      transferTable.ajax.reload(null, false);
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
  $('#cancelId').val(id);
  $('#cancelReason').val('');
  $('#cancelForm').validate().resetForm();
  $('#cancelModal').modal('show');
}

function cancelEntry() {
  $('#spinnerLoading').show();
  $('#submitCancel').prop('disabled', true);

  $.post(transferApi, $('#cancelForm').serialize() + '&action=cancel', function (obj) {
    if (obj.status === 'success') {
      $('#cancelModal').modal('hide');
      toastr.success(obj.message, 'Success:');
      transferTable.ajax.reload(null, false);
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
      '<td>' + e(d.from_batch_no) + ' <i class="fas fa-arrow-right text-muted mx-1"></i> ' + e(d.to_batch_no) + '</td>' +
      '<td>' + e(d.product_name) + '</td>' +
      '<td><span class="grade-badge">' + e(d.grade_name) + '</span></td>' +
      '<td>' + e(d.packaging_size_name) + '</td>' +
      '<td class="text-right text-mono">' + e(d.units_per_box) + '</td>' +
      '<td class="text-right text-mono text-primary font-weight-bold">' + (parseFloat(d.weight) || 0).toFixed(2) + '</td>' +
      '</tr>';
  });
  if (!rows) {
    rows = '<tr><td colspan="6" class="text-center text-muted">' + e(transferText.noItems) + '</td></tr>';
  }

  return '<div class="expanded-row-content">' +
    '<div class="expanded-header">' +
    '<div><div class="expanded-header-title">' + e(row.transfer_no) + '</div><div class="expanded-header-subtitle">' + e(row.created_by_name || '-') + '</div></div>' +
    '<div class="expanded-actions">' + actionButtons(row.id) + '</div>' +
    '</div>' +
    '<div class="kpi-row">' +
    kpi(transferText.fromBatch, e(row.from_batch_no || '-')) +
    kpi(transferText.toBatch, e(row.to_batch_no || '-')) +
    kpi(transferText.createdDatetime, e(row.created_date ? moment(row.created_date, 'YYYY-MM-DD HH:mm:ss').format('DD/MM/YYYY HH:mm') : '-')) +
    kpi(transferText.items, e((row.items || []).length)) +
    '</div>' +
    (row.remarks ? '<div class="info-section"><div class="info-remark"><span class="info-item-label">' + e(transferText.remark) + '</span><span class="info-item-value">' + e(row.remarks) + '</span></div></div>' : '') +
    '<div class="details-section">' +
    '<div class="details-header"><span class="details-title">' + e(transferText.weightDetails) + '</span></div>' +
    '<div class="table-responsive">' +
    '<table class="table details-table mb-0">' +
    '<thead><tr><th>' + e(transferText.fromBatch) + ' / ' + e(transferText.toBatch) + '</th><th>' + e(transferText.product) + '</th><th>' + e(transferText.grade) + '</th>' +
    '<th>' + e(transferText.packagingSize) + '</th><th class="text-right">' + e(transferText.unitPerBox) + '</th><th class="text-right">' + e(transferText.weight) + '</th></tr></thead>' +
    '<tbody>' + rows + '</tbody></table>' +
    '</div>' +
    '</div>' +
    '</div>';
}
