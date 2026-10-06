// Grading list / entry page. Requires `gradingPermissions`, `gradingAllowPhoto`, `gradingLookups` and `gradingText`.

// 1. Variables
var gradingApi = 'php/modules/grading/api.php';
var gradingTable;
var weightCount = 0;
var rejectCount = 0;

// 2. Document ready
$(document).ready(function () {
  var today = new Date();

  $('#fromDatePicker, #toDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: today
  });

  $('#startTimePicker, #endTimePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY HH:mm'
  });

  $('.select2').each(function () {
    $(this).select2({
      allowClear: true,
      placeholder: gradingText.pleaseSelect,
      dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-content') : undefined
    });
  });

  $('#selectAllCheckbox').on('change', function () {
    $('#gradingTable tbody input[type="checkbox"]').prop('checked', $(this).prop('checked')).trigger('change');
  });

  gradingTable = $('#gradingTable').DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: true,
    serverMethod: 'post',
    searching: true,
    order: [[1, 'asc']],
    language: {
      emptyTable: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title">' + escapeHtml(gradingText.noRecordsFound) + '</div><div class="empty-message">' + escapeHtml(gradingText.noRecordsMessage) + '</div></div>',
      zeroRecords: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title">' + escapeHtml(gradingText.noMatchingRecords) + '</div><div class="empty-message">' + escapeHtml(gradingText.noMatchingMessage) + '</div></div>'
    },
    ajax: {
      url: gradingApi,
      data: function (d) {
        return $.extend(d, filterParams(), { action: 'list' });
      }
    },
    columns: [
      {
        data: 'id',
        className: 'select-checkbox',
        orderable: false,
        render: function (data) {
          return '<input type="checkbox" class="select-checkbox" value="' + data + '"/>';
        }
      },
      { data: 'grading_no', render: $.fn.dataTable.render.text() },
      { data: 'category', render: $.fn.dataTable.render.text() },
      { data: 'locations', render: $.fn.dataTable.render.text() },
      { data: 'start_date', render: $.fn.dataTable.render.text() },
      { data: 'end_date', render: $.fn.dataTable.render.text() },
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
  $('#gradingTable tbody').on('click', 'tr', function (e) {
    if ($(e.target).closest('td').hasClass('select-checkbox') || $(e.target).closest('td').hasClass('action-button') ||
        $(e.target).closest('button, select, input').length) {
      return;
    }

    var tr = $(this);
    var row = gradingTable.row(tr);
    if (!row.data()) {
      return;
    }

    if (row.child.isShown()) {
      row.child.hide();
      tr.removeClass('shown');
      return;
    }

    $.post(gradingApi, { action: 'get', id: row.data().id }, function (obj) {
      if (obj.status === 'success') {
        row.child(formatExpandedRow(obj.message)).show();
        tr.addClass('shown');
        populateDetailFilters(obj.message.id, obj.message.weightDetails);
      } else {
        toastr.error(obj.message, 'Failed:');
      }
    }).fail(function () {
      toastr.error('Something went wrong', 'Failed:');
    });
  });

  $('#filterSearch').on('click', function () {
    gradingTable.ajax.reload();
  });

  $('#exportExcel').on('click', function () {
    window.open(gradingApi + '?' + exportQuery('exportExcel'));
  });

  $('#exportPdf').on('click', function () {
    window.open(gradingApi + '?' + exportQuery('exportPdf'));
  });

  $('#addEntry').on('click', function () {
    newEntry();
  });

  $('#addWeightBtn').on('click', function () {
    addDetailRow('weight', null);
  });

  $('#addRejectWeightBtn').on('click', function () {
    addDetailRow('reject', null);
  });

  // Category change limits the product dropdowns
  $('#category').on('change', function () {
    $('#weightDetailsTable, #rejectDetailsTable').find('select[name*="[product]"]').each(function () {
      var select = $(this);
      var current = select.val();
      select.html(productOptions(current));
      select.val(current).trigger('change.select2');
    });
  });

  // Product change limits the grade dropdown in the same row
  $('#weightDetailsTable').on('change', 'select[name*="[product]"]', function () {
    var gradeSelect = $(this).closest('tr').find('select[name*="[to_grade]"]');
    var current = gradeSelect.val();
    gradeSelect.html(gradeOptions($(this).val(), current));
    gradeSelect.val(current).trigger('change.select2');
  });

  $('#weightDetailsTable, #rejectDetailsTable').on('input change', 'input[name*="[gross]"], input[name*="[tare]"]', function () {
    calculateNet($(this).closest('tr'));
  });

  $('#extendForm').on('change', 'input[type="file"]', function () {
    $(this).siblings('.photo-status').html(this.files && this.files[0] ? '<i class="fas fa-check-circle text-success"></i>' : '');
  });

  $('#extendForm').validate(validationOptions(saveEntry));
  $('#cancelForm').validate(validationOptions(cancelEntry));
  $('#printOptionsForm').validate(validationOptions(printEntry));
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
    category: $('#categoryFilter').val() || '',
    location: $('#locationFilter').val() || ''
  };
}

// Filters plus selected IDs (isMulti = Y exports only the ticked rows)
function exportQuery(action) {
  var params = filterParams();
  var ids = [];
  $("#gradingTable tbody input[type='checkbox']:checked").each(function () {
    ids.push($(this).val());
  });

  params.action = action;
  params.isMulti = ids.length > 0 ? 'Y' : 'N';
  if (ids.length > 0) {
    params.ids = ids.join(',');
  }

  return $.param(params);
}

function actionButtons(id) {
  var buttons = '';
  if (gradingPermissions.allowEdit) {
    buttons += '<button type="button" onclick="edit(' + id + ')" class="btn btn-outline-primary btn-sm" title="Edit"><i class="fas fa-pen"></i></button>';
  }
  buttons += '<button type="button" onclick="openPrint(' + id + ')" class="btn btn-outline-warning btn-sm" title="Print"><i class="fas fa-print"></i></button>';
  if (gradingPermissions.allowDelete) {
    buttons += '<button type="button" onclick="deactivate(' + id + ')" class="btn btn-outline-danger btn-sm" title="Delete"><i class="fas fa-trash"></i></button>';
  }
  return buttons;
}

function photoUrl(path) {
  return 'php/viewPhoto.php?file=' + encodeURIComponent(path);
}

// <option>s for products, limited to the chosen category (the selected product is always kept)
function productOptions(selected) {
  var category = $('#category').val();
  var html = '<option value="">' + escapeHtml(gradingText.selectProduct) + '</option>';

  $.each(gradingLookups.products, function (i, product) {
    if (category && product.category != category && product.id != selected) {
      return;
    }
    html += '<option value="' + product.id + '">' + escapeHtml(product.product_name) + '</option>';
  });

  return html;
}

// <option>s for grades linked to the product (the selected grade is always kept)
function gradeOptions(productId, selected) {
  var html = '<option value="">' + escapeHtml(gradingText.selectGrade) + '</option>';
  var seen = {};

  $.each(gradingLookups.grades, function (i, grade) {
    if (seen[grade.id] || (productId && grade.product_id != productId && grade.id != selected)) {
      return;
    }
    seen[grade.id] = true;
    html += '<option value="' + grade.id + '">' + escapeHtml(grade.units) + '</option>';
  });

  return html;
}

function currentTime() {
  return moment().format('HH:mm:ss');
}

// Weight / reject detail row (detail = existing item when editing)
function addDetailRow(type, detail) {
  var isReject = type === 'reject';
  var idx = isReject ? rejectCount++ : weightCount++;
  var prefix = isReject ? 'rejectDetails' : 'weightDetails';
  var fileName = isReject ? 'rejectPhotoFiles' : 'photoFiles';
  var d = detail || {};
  var fixed = function (value) { return (parseFloat(value) || 0).toFixed(2); };

  var gradeCell = isReject
    ? '<span class="badge badge-danger">REJ</span>'
    : '<select class="form-control form-control-sm detail-select" name="' + prefix + '[' + idx + '][to_grade]">' + gradeOptions(d.product_id, d.to_grade) + '</select>';

  var photoCell = '';
  if (gradingAllowPhoto) {
    photoCell = '<td class="text-nowrap">' +
      '<input type="file" name="' + fileName + '[' + idx + ']" accept=".png,.jpg,.jpeg" style="display:none">' +
      (d.photo_path ? '<a href="' + photoUrl(d.photo_path) + '" target="_blank" class="btn btn-outline-success btn-sm mr-1"><i class="fas fa-image"></i></a>' : '') +
      '<button type="button" class="btn btn-outline-info btn-sm" onclick="$(this).siblings(\'input[type=file]\').click()"><i class="fas fa-camera"></i></button>' +
      '<span class="photo-status ml-1"></span>' +
      '</td>';
  }

  var row = $('<tr class="details">' +
    '<td><input type="hidden" name="' + prefix + '[' + idx + '][gradingItemId]" value="' + (d.id || '') + '">' +
    '<select class="form-control form-control-sm detail-select" name="' + prefix + '[' + idx + '][product]">' + productOptions(d.product_id) + '</select></td>' +
    '<td>' + gradeCell + '</td>' +
    '<td><input type="number" class="form-control form-control-sm" name="' + prefix + '[' + idx + '][gross]" step="0.01" min="0" value="' + fixed(d.gross_weight) + '"></td>' +
    '<td><input type="number" class="form-control form-control-sm" name="' + prefix + '[' + idx + '][tare]" step="0.01" min="0" value="' + fixed(d.tare_weight) + '"></td>' +
    '<td><input type="number" class="form-control form-control-sm" name="' + prefix + '[' + idx + '][net]" step="0.01" value="' + fixed(d.nett_weight) + '" readonly></td>' +
    '<td><input type="time" class="form-control form-control-sm" name="' + prefix + '[' + idx + '][time]" value="' + (d.weighing_time || currentTime()) + '"></td>' +
    photoCell +
    '<td><button type="button" class="btn btn-outline-danger btn-sm" onclick="removeDetailRow(this, \'' + type + '\')"><i class="fas fa-trash"></i></button></td>' +
    '</tr>');

  $(isReject ? '#rejectDetailsTable' : '#weightDetailsTable').append(row);

  row.find('select[name*="[product]"]').val(d.product_id || '');
  row.find('select[name*="[to_grade]"]').val(d.to_grade || '');
  row.find('.detail-select').select2({
    allowClear: true,
    placeholder: gradingText.pleaseSelect,
    dropdownParent: $('#extendModal .modal-content'),
    width: '100%'
  });

  updateTotals(type);
}

function removeDetailRow(button, type) {
  $(button).closest('tr').remove();
  reindexDetails(type);
  updateTotals(type);
}

// Keep posted row indexes contiguous so photos stay matched to their rows
function reindexDetails(type) {
  var tableId = type === 'reject' ? '#rejectDetailsTable' : '#weightDetailsTable';
  $(tableId + ' tr').each(function (index) {
    $(this).find('input, select').each(function () {
      var name = $(this).attr('name');
      if (name) {
        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
      }
    });
  });

  if (type === 'reject') {
    rejectCount = $(tableId + ' tr').length;
  } else {
    weightCount = $(tableId + ' tr').length;
  }
}

function calculateNet(row) {
  var gross = parseFloat(row.find('input[name*="[gross]"]').val()) || 0;
  var tare = parseFloat(row.find('input[name*="[tare]"]').val()) || 0;
  row.find('input[name*="[net]"]').val(Math.abs(gross - tare).toFixed(2));
  updateTotals(row.closest('tbody').attr('id') === 'rejectDetailsTable' ? 'reject' : 'weight');
}

function updateTotals(type) {
  var isReject = type === 'reject';
  var totals = { gross: 0, tare: 0, net: 0 };

  $(isReject ? '#rejectDetailsTable tr' : '#weightDetailsTable tr').each(function () {
    totals.gross += parseFloat($(this).find('input[name*="[gross]"]').val()) || 0;
    totals.tare += parseFloat($(this).find('input[name*="[tare]"]').val()) || 0;
    totals.net += parseFloat($(this).find('input[name*="[net]"]').val()) || 0;
  });

  var prefix = isReject ? '#totalReject' : '#totalWeight';
  $(prefix + 'Gross').text(totals.gross.toFixed(2));
  $(prefix + 'Tare').text(totals.tare.toFixed(2));
  $(prefix + 'Net').text(totals.net.toFixed(2));
}

function resetForm() {
  weightCount = 0;
  rejectCount = 0;
  $('#extendForm').validate().resetForm();
  $('#extendModal').find('#id, #gradingNo, #remarks').val('');
  $('#weightDetailsTable, #rejectDetailsTable').empty();
  updateTotals('weight');
  updateTotals('reject');
}

function newEntry() {
  resetForm();
  $('#startTimePicker').datetimepicker('date', moment());
  $('#endTimePicker').datetimepicker('clear');
  $('#category').val('').trigger('change');
  $('#location').val($('#locationFilter').val() || '').trigger('change');
  $('#extendModal').modal('show');
}

function edit(id) {
  $('#spinnerLoading').show();

  $.post(gradingApi, { action: 'get', id: id }, function (obj) {
    if (obj.status !== 'success') {
      toastr.error(obj.message || 'Error loading data', 'Failed:');
      return;
    }

    var msg = obj.message;
    resetForm();
    $('#id').val(msg.id);
    $('#gradingNo').val(msg.grading_no);
    $('#remarks').val(msg.remark);
    $('#category').val(msg.product_category || '').trigger('change');
    $('#location').val(msg.location).trigger('change');
    setPickerDate('#startTimePicker', msg.start_date);
    setPickerDate('#endTimePicker', msg.end_date);

    $.each(msg.weightDetails || [], function (i, detail) {
      addDetailRow('weight', detail);
    });
    $.each(msg.rejectDetails || [], function (i, detail) {
      addDetailRow('reject', detail);
    });

    $('#extendModal').modal('show');
  }).fail(function () {
    toastr.error('Something went wrong', 'Failed:');
  }).always(function () {
    $('#spinnerLoading').hide();
  });
}

function setPickerDate(picker, value) {
  if (value) {
    $(picker).datetimepicker('date', moment(value, 'YYYY-MM-DD HH:mm:ss'));
  } else {
    $(picker).datetimepicker('clear');
  }
}

function saveEntry() {
  var formData = new FormData($('#extendForm')[0]);
  formData.append('action', 'save');

  $('#spinnerLoading').show();
  $('#saveButton').prop('disabled', true);

  $.ajax({
    url: gradingApi,
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function (obj) {
      if (obj.status === 'success') {
        $('#extendModal').modal('hide');
        toastr.success(obj.message, 'Success:');
        gradingTable.ajax.reload(null, false);
      } else {
        toastr.error(obj.message || 'Something went wrong', 'Failed:');
      }
    },
    error: function () {
      toastr.error('Something went wrong', 'Failed:');
    },
    complete: function () {
      $('#saveButton').prop('disabled', false);
      $('#spinnerLoading').hide();
    }
  });
}

function deactivate(id) {
  if (confirm(gradingText.confirmDelete)) {
    $('#cancelId').val(id);
    $('#cancelReason').val('');
    $('#cancelModal').modal('show');
  }
}

function cancelEntry() {
  $('#spinnerLoading').show();
  $('#submitCancel').prop('disabled', true);

  $.post(gradingApi, $('#cancelForm').serialize() + '&action=cancel', function (obj) {
    if (obj.status === 'success') {
      $('#cancelModal').modal('hide');
      toastr.success(obj.message, 'Success:');
      gradingTable.ajax.reload(null, false);
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

function openPrint(id) {
  $('#printId').val(id);
  $('#printOptionsModal').modal('show');
}

function printEntry() {
  $('#printOptionsModal').modal('hide');

  $.post(gradingApi, $('#printOptionsForm').serialize() + '&action=printSlip', function (obj) {
    if (obj.status !== 'success') {
      toastr.error(obj.message || 'Something went wrong when printing', 'Failed:');
      return;
    }

    var printWindow = window.open('', '', 'height=' + screen.height + ',width=' + screen.width);
    printWindow.document.write(obj.message);
    printWindow.document.close();

    // Wait for paged.js to finish laying out pages before printing
    var pollCount = 0;
    var poll = setInterval(function () {
      pollCount++;
      if ($(printWindow.document).find('.pagedjs_pages').length || pollCount > 60) {
        clearInterval(poll);
        setTimeout(function () {
          printWindow.print();
          printWindow.close();
        }, 300);
      }
    }, 200);
  }).fail(function () {
    toastr.error('Something went wrong when printing', 'Failed:');
  });
}

function detailTable(details, isReject, tableId) {
  var e = escapeHtml;
  var totals = { gross: 0, tare: 0, net: 0 };
  var rows = '';

  $.each(details, function (i, d) {
    totals.gross += parseFloat(d.gross_weight) || 0;
    totals.tare += parseFloat(d.tare_weight) || 0;
    totals.net += parseFloat(d.nett_weight) || 0;
    rows += '<tr data-product="' + e(d.product_name) + '" data-grade="' + e(d.to_grade_unit) + '">' +
      '<td>' + e(d.product_name) + '</td>' +
      '<td><span class="grade-badge' + (isReject ? ' grade-badge-danger' : '') + '">' + e(d.to_grade_unit) + '</span></td>' +
      '<td class="text-right text-mono">' + (parseFloat(d.gross_weight) || 0).toFixed(2) + '</td>' +
      '<td class="text-right text-mono">' + (parseFloat(d.tare_weight) || 0).toFixed(2) + '</td>' +
      '<td class="text-right text-mono ' + (isReject ? 'text-danger' : 'text-primary') + '"><strong>' + (parseFloat(d.nett_weight) || 0).toFixed(2) + '</strong></td>' +
      '<td>' + e(d.weighing_time) + '</td>' +
      (gradingAllowPhoto ? '<td class="text-center">' + (d.photo_path ? '<a href="' + photoUrl(d.photo_path) + '" target="_blank" class="btn btn-outline-info btn-sm btn-photo"><i class="fas fa-image"></i></a>' : '<span class="text-muted">-</span>') + '</td>' : '') +
      '</tr>';
  });

  return '<table class="details-table"' + (tableId ? ' id="' + tableId + '"' : '') + '>' +
    '<thead><tr><th>' + e(gradingText.product) + '</th><th>' + e(gradingText.grade) + '</th>' +
    '<th class="text-right">' + e(gradingText.gross) + '</th><th class="text-right">' + e(gradingText.tare) + '</th>' +
    '<th class="text-right">' + e(gradingText.net) + '</th><th>' + e(gradingText.time) + '</th>' +
    (gradingAllowPhoto ? '<th class="text-center">' + e(gradingText.photo) + '</th>' : '') + '</tr></thead>' +
    '<tbody>' + rows + '</tbody>' +
    '<tfoot><tr><th colspan="2" class="text-right">' + e(gradingText.total) + '</th>' +
    '<th class="text-right total-gross">' + totals.gross.toFixed(2) + '</th>' +
    '<th class="text-right total-tare">' + totals.tare.toFixed(2) + '</th>' +
    '<th class="text-right total-net ' + (isReject ? 'text-danger' : 'text-primary') + '">' + totals.net.toFixed(2) + '</th>' +
    '<th></th>' + (gradingAllowPhoto ? '<th></th>' : '') + '</tr></tfoot></table>';
}

function formatExpandedRow(row) {
  var e = escapeHtml;
  var html = '<div class="expanded-row-content">' +
    '<div class="info-section">' +
    '<div class="info-section-title">' + e(gradingText.gradingInfo) + '</div>' +
    '<div class="info-grid">' +
    '<div><span class="info-item-label">' + e(gradingText.gradingNo) + '</span><span class="info-item-value">' + e(row.grading_no) + '</span></div>' +
    '<div><span class="info-item-label">' + e(gradingText.category) + '</span><span class="info-item-value">' + e(row.category || '-') + '</span></div>' +
    '<div><span class="info-item-label">' + e(gradingText.startTime) + '</span><span class="info-item-value">' + e(row.start_date) + '</span></div>' +
    '<div><span class="info-item-label">' + e(gradingText.endTime) + '</span><span class="info-item-value">' + e(row.end_date || '-') + '</span></div>' +
    '</div>' +
    (row.remark ? '<div class="info-remark"><span class="info-item-label">' + e(gradingText.remark) + '</span><span class="info-item-value">' + e(row.remark) + '</span></div>' : '') +
    '</div>' +
    '<div class="details-section">' +
    '<div class="details-header">' +
    '<span class="details-title">' + e(gradingText.weighingDetails) + '</span>' +
    '<div class="details-filters">' +
    '<select class="form-control form-control-sm details-filter-select" id="productFilter_' + row.id + '"><option value="">' + e(gradingText.allProducts) + '</option></select>' +
    '<select class="form-control form-control-sm details-filter-select" id="gradeFilter_' + row.id + '"><option value="">' + e(gradingText.allGrades) + '</option></select>' +
    '</div>' +
    '</div>' +
    detailTable(row.weightDetails || [], false, 'detailTable_' + row.id) +
    '</div>';

  if (row.rejectDetails && row.rejectDetails.length > 0) {
    html += '<div class="details-section">' +
      '<div class="details-header"><span class="details-title details-title-danger">' + e(gradingText.rejectDetails) + '</span></div>' +
      detailTable(row.rejectDetails, true, null) +
      '</div>';
  }

  return html + '</div>';
}

function populateDetailFilters(rowId, weightDetails) {
  var products = [];
  $.each(weightDetails || [], function (i, d) {
    if (products.indexOf(d.product_name) === -1) {
      products.push(d.product_name);
    }
  });

  var productSelect = $('#productFilter_' + rowId);
  $.each(products.sort(), function (i, product) {
    productSelect.append($('<option>').val(product).text(product));
  });

  updateGradeFilterOptions(rowId, '');

  $('#productFilter_' + rowId + ', #gradeFilter_' + rowId).select2({ width: '160px' }).on('change', function () {
    filterDetailTable(rowId);
  });
}

// Show rows matching the product / grade filters and recalculate the footer totals
function filterDetailTable(rowId) {
  var product = $('#productFilter_' + rowId).val();
  var table = $('#detailTable_' + rowId);
  updateGradeFilterOptions(rowId, product);
  var grade = $('#gradeFilter_' + rowId).val();
  var totals = { gross: 0, tare: 0, net: 0 };

  table.find('tbody tr').each(function () {
    var show = (!product || $(this).attr('data-product') == product) && (!grade || $(this).attr('data-grade') == grade);
    $(this).toggle(show);
    if (show) {
      totals.gross += parseFloat($(this).find('td:eq(2)').text()) || 0;
      totals.tare += parseFloat($(this).find('td:eq(3)').text()) || 0;
      totals.net += parseFloat($(this).find('td:eq(4)').text()) || 0;
    }
  });

  table.find('.total-gross').text(totals.gross.toFixed(2));
  table.find('.total-tare').text(totals.tare.toFixed(2));
  table.find('.total-net').text(totals.net.toFixed(2));
}

// Grade filter only offers grades present for the chosen product
function updateGradeFilterOptions(rowId, product) {
  var gradeSelect = $('#gradeFilter_' + rowId);
  var current = gradeSelect.val();
  var grades = [];

  $('#detailTable_' + rowId + ' tbody tr').each(function () {
    var grade = $(this).attr('data-grade');
    if ((!product || $(this).attr('data-product') == product) && grades.indexOf(grade) === -1) {
      grades.push(grade);
    }
  });

  gradeSelect.find('option:not(:first)').remove();
  $.each(grades.sort(), function (i, grade) {
    gradeSelect.append($('<option>').val(grade).text(grade));
  });
  gradeSelect.val(grades.indexOf(current) !== -1 ? current : '').trigger('change.select2');
}
