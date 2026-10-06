// Packaging batch list / entry page. Requires `batchPermissions`, `batchAllowPhoto`, `batchAllowPresetLabel`, `batchLookups` and `batchText`.

// 1. Variables
var batchApi = 'php/modules/packagingBatches/api.php';
var batchTable;
var weightCount = 0;
var shipmentBatchItems = [];
var statusClasses = { pending: 'warning', partial: 'info', completed: 'success' };

// 2. Document ready
$(document).ready(function () {
  $('#fromDatePicker, #toDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: new Date()
  });

  $('#packagingDatePicker, #shipmentLoadingDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY HH:mm'
  });

  $('.select2').each(function () {
    $(this).select2({
      allowClear: true,
      placeholder: batchText.pleaseSelect,
      dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-content') : undefined
    });
  });

  batchTable = $('#batchTable').DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: true,
    serverMethod: 'post',
    searching: true,
    order: [[1, 'asc']],
    columnDefs: [{ orderable: false, targets: [0] }],
    language: {
      emptyTable: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title">' + escapeHtml(batchText.noRecordsFound) + '</div><div class="empty-message">' + escapeHtml(batchText.noRecordsMessage) + '</div></div>',
      zeroRecords: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title">' + escapeHtml(batchText.noMatchingRecords) + '</div><div class="empty-message">' + escapeHtml(batchText.noMatchingMessage) + '</div></div>'
    },
    ajax: {
      url: batchApi,
      data: function (d) {
        return $.extend(d, filterParams(), { action: 'list' });
      }
    },
    columns: [
      { data: 'batch_no', render: $.fn.dataTable.render.text() },
      { data: 'packaging_date', render: $.fn.dataTable.render.text() },
      { data: 'locations', render: $.fn.dataTable.render.text() },
      { data: 'production_line', render: $.fn.dataTable.render.text() },
      { data: 'status', render: function (data) { return statusBadge(data); } },
      {
        data: 'id',
        responsivePriority: 1,
        orderable: false,
        className: 'action-button',
        render: function (data, type, row) {
          return '<div class="d-flex flex-nowrap" style="gap:4px;">' + actionButtons(data, row.status) + '</div>';
        }
      }
    ]
  });

  // Expand / collapse row details on row click
  $('#batchTable tbody').on('click', 'tr', function (e) {
    if ($(e.target).closest('td').hasClass('action-button') || $(e.target).closest('button, select, input').length) {
      return;
    }

    var tr = $(this);
    var row = batchTable.row(tr);
    if (!row.data()) {
      return;
    }

    if (row.child.isShown()) {
      row.child.hide();
      tr.removeClass('shown');
      return;
    }

    $.post(batchApi, { action: 'get', id: row.data().id }, function (obj) {
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
    batchTable.ajax.reload();
  });

  $('#addEntry').on('click', function () {
    newEntry();
  });

  $('#addWeightBtn').on('click', function () {
    addDetailRow({ time: currentTime() });
  });

  $('#bulkAddBtn').on('click', function () {
    openBulkAdd();
  });

  // Category change limits the product dropdown in the same row
  $('#weightDetailsTable').on('change', 'select[name*="[category]"]', function () {
    var productSelect = $(this).closest('tr').find('select[name*="[product]"]');
    productSelect.html(productOptions($(this).val(), '')).val('').trigger('change');
  });

  // Product change limits the grade dropdown in the same row
  $('#weightDetailsTable').on('change', 'select[name*="[product]"]', function () {
    refreshGradeSelect($(this).closest('tr'));
  });

  // Type change limits every row's grade dropdown
  $('#gradeType').on('change', function () {
    $('#weightDetailsTable tr').each(function () {
      refreshGradeSelect($(this));
    });
  });

  // Packaging size fills an empty gross with the packaging weight
  $('#weightDetailsTable').on('change', 'select[name*="[packaging_size]"]', function () {
    var grossInput = $(this).closest('tr').find('input[name*="[gross]"]');
    var weight = $(this).find('option:selected').data('weight');
    if (weight && !parseFloat(grossInput.val())) {
      grossInput.val(parseFloat(weight).toFixed(2)).trigger('input');
    }
  });

  $('#weightDetailsTable').on('input change', 'input[name*="[gross]"], input[name*="[tare]"]', function () {
    calculateNet($(this).closest('tr'));
  });

  $('#weightDetailsTable').on('change', 'select[name*="[label]"]', function () {
    updateLabelSummary();
  });

  $('#extendForm').on('change', 'input[type="file"]', function () {
    $(this).siblings('.photo-status').html(this.files && this.files[0] ? '<i class="fas fa-check-circle text-success"></i>' : '');
  });

  $('#bulkCategory').on('change', function () {
    $('#bulkProduct').html(productOptions($(this).val(), '')).val('').trigger('change');
  });

  $('#bulkProduct').on('change', function () {
    $('#bulkGrade').html(gradeOptions($(this).val(), $('#gradeType').val(), '')).val('').trigger('change.select2');
  });

  $('#bulkPackagingSize').on('change', function () {
    var weight = $(this).find('option:selected').data('weight');
    if (weight) {
      $('#bulkWeight').val(parseFloat(weight).toFixed(2));
    }
  });

  $('#bulkAddForm').on('submit', function (e) {
    e.preventDefault();
    addBulkRows();
  });

  // Keep the page scroll locked while a nested modal closes over the entry modal
  $('#bulkAddModal, #shipmentModal').on('hidden.bs.modal', function () {
    if ($('#extendModal').hasClass('show')) {
      $('body').addClass('modal-open');
    }
  });

  $('#extendForm').validate($.extend(validationOptions(saveEntry), { ignore: ':hidden, #weightDetailsTable :input' }));
  $('#cancelForm').validate(validationOptions(cancelEntry));
  $('#shipmentForm').validate(validationOptions(submitShipment));
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
    location: $('#locationFilter').val() || '',
    productionLine: $('#productionLineFilter').val() || '',
    category: $('#categoryFilter').val() || 'all'
  };
}

function statusBadge(status) {
  return '<span class="badge badge-' + (statusClasses[status] || 'secondary') + '">' + escapeHtml(status) + '</span>';
}

function actionButtons(id, status) {
  var buttons = '';
  if (batchPermissions.allowEdit) {
    buttons += '<button type="button" onclick="edit(' + id + ')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button>';
  }
  buttons += '<button type="button" onclick="printBatch(' + id + ')" class="btn btn-sm btn-outline-secondary" title="Print"><i class="fas fa-print"></i></button>';
  if (status !== 'completed') {
    buttons += '<button type="button" onclick="openShipmentModal(' + id + ')" class="btn btn-sm btn-outline-info" title="Shipment"><i class="fas fa-shipping-fast"></i></button>';
  }
  if (batchPermissions.allowDelete) {
    buttons += '<button type="button" onclick="deactivate(' + id + ')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>';
  }
  return buttons;
}

function photoUrl(path) {
  return 'php/viewPhoto.php?file=' + encodeURIComponent(path);
}

function currentTime() {
  return moment().format('HH:mm:ss');
}

function optionList(placeholder, rows, valueKey, textKey) {
  var html = '<option value="">' + escapeHtml(placeholder) + '</option>';
  $.each(rows, function (i, row) {
    html += '<option value="' + escapeHtml(row[valueKey]) + '">' + escapeHtml(row[textKey]) + '</option>';
  });
  return html;
}

function supplierOptions() {
  return optionList(batchText.selectSupplier, batchLookups.suppliers, 'id', 'supplier_name');
}

function categoryOptions() {
  return optionList(batchText.selectCategory, batchLookups.categories, 'id', 'category_name');
}

// <option>s for products in the category (the selected product is always kept)
function productOptions(category, selected) {
  var html = '<option value="">' + escapeHtml(batchText.selectProduct) + '</option>';
  $.each(batchLookups.products, function (i, product) {
    if (category && product.category != category && product.id != selected) {
      return;
    }
    html += '<option value="' + product.id + '">' + escapeHtml(product.product_name) + '</option>';
  });
  return html;
}

// <option>s for grades linked to the product and matching the Local / Export type
function gradeOptions(productId, type, selected) {
  var html = '<option value="">' + escapeHtml(batchText.selectGrade) + '</option>';
  var seen = {};

  $.each(batchLookups.grades, function (i, grade) {
    var productMatch = !productId || !grade.product_id || grade.product_id == productId;
    var typeMatch = !type || !grade.grade_type || grade.grade_type == type;
    if (seen[grade.id] || (!(productMatch && typeMatch) && grade.id != selected)) {
      return;
    }
    seen[grade.id] = true;
    html += '<option value="' + grade.id + '">' + escapeHtml(grade.units) + '</option>';
  });

  return html;
}

function packagingOptions() {
  var html = '<option value="">' + escapeHtml(batchText.selectPackaging) + '</option>';
  $.each(batchLookups.packagings, function (i, pkg) {
    html += '<option value="' + pkg.id + '" data-weight="' + escapeHtml(pkg.weight) + '">' + escapeHtml(pkg.packaging_name) + '</option>';
  });
  return html;
}

function labelOptions() {
  var html = '<option value="">' + escapeHtml(batchText.selectLabel) + '</option>';
  $.each(batchLookups.labels, function (i, label) {
    html += '<option value="' + escapeHtml(label) + '">' + escapeHtml(label) + '</option>';
  });
  return html;
}

function initSelect2(elements, modal) {
  elements.select2({
    allowClear: true,
    placeholder: batchText.pleaseSelect,
    dropdownParent: $(modal + ' .modal-content'),
    width: '100%'
  });
}

// Weight detail row (d = existing item when editing, or values from bulk add)
function addDetailRow(d) {
  var idx = weightCount++;
  var prefix = 'weightDetails[' + idx + ']';
  var fixed = function (value) { return (parseFloat(value) || 0).toFixed(2); };

  var labelCell = batchAllowPresetLabel
    ? '<select class="form-control form-control-sm detail-select" name="' + prefix + '[label]">' + labelOptions() + '</select>'
    : '<input type="text" class="form-control form-control-sm" name="' + prefix + '[label]">';

  var photoCell = '';
  if (batchAllowPhoto) {
    photoCell = '<td class="text-nowrap">' +
      '<input type="file" name="photoFiles[' + idx + ']" accept=".png,.jpg,.jpeg" style="display:none">' +
      (d.photo_path ? '<a href="' + photoUrl(d.photo_path) + '" target="_blank" class="btn btn-outline-success btn-sm mr-1" title="View Photo"><i class="fas fa-image"></i></a>' : '') +
      '<button type="button" class="btn btn-outline-info btn-sm" onclick="$(this).siblings(\'input[type=file]\').click()"><i class="fas fa-camera"></i></button>' +
      '<span class="photo-status ml-1"></span>' +
      '</td>';
  }

  var row = $('<tr class="details">' +
    '<td><input type="hidden" name="' + prefix + '[batchItemId]" value="' + (d.id || '') + '">' +
    '<select class="form-control form-control-sm detail-select" name="' + prefix + '[supplier]">' + supplierOptions() + '</select></td>' +
    '<td><select class="form-control form-control-sm detail-select" name="' + prefix + '[category]">' + categoryOptions() + '</select></td>' +
    '<td><select class="form-control form-control-sm detail-select" name="' + prefix + '[product]">' + productOptions(d.category_id, d.product_id) + '</select></td>' +
    '<td><select class="form-control form-control-sm detail-select" name="' + prefix + '[grade]">' + gradeOptions(d.product_id, $('#gradeType').val(), d.grade) + '</select></td>' +
    '<td><select class="form-control form-control-sm detail-select" name="' + prefix + '[packaging_size]">' + packagingOptions() + '</select></td>' +
    '<td>' + labelCell + '</td>' +
    '<td><input type="number" class="form-control form-control-sm" name="' + prefix + '[unit_per_box]" step="1" min="1" value="' + (parseInt(d.units_per_box, 10) || 0) + '"></td>' +
    '<td><input type="number" class="form-control form-control-sm" name="' + prefix + '[gross]" step="0.01" min="0.01" value="' + fixed(d.gross) + '"></td>' +
    '<td><input type="number" class="form-control form-control-sm" name="' + prefix + '[tare]" step="0.01" value="' + fixed(d.tare) + '"></td>' +
    '<td><input type="number" class="form-control form-control-sm" name="' + prefix + '[weight]" step="0.01" value="' + fixed(d.weight) + '" readonly></td>' +
    '<td><input type="time" class="form-control form-control-sm" name="' + prefix + '[time]" value="' + escapeHtml(d.time || '') + '"></td>' +
    photoCell +
    '<td><button type="button" class="btn btn-outline-danger btn-sm" onclick="removeDetailRow(this)"><i class="fas fa-trash"></i></button></td>' +
    '</tr>');

  $('#weightDetailsTable').append(row);

  row.find('select[name*="[supplier]"]').val(d.supplier_id || '');
  row.find('select[name*="[category]"]').val(d.category_id || '');
  row.find('select[name*="[product]"]').val(d.product_id || '');
  row.find('select[name*="[grade]"]').val(d.grade || '');
  row.find('select[name*="[packaging_size]"]').val(d.packaging_size || '');
  row.find('[name*="[label]"]').val(d.label || '');
  initSelect2(row.find('.detail-select'), '#extendModal');
}

function refreshGradeSelect(row) {
  var gradeSelect = row.find('select[name*="[grade]"]');
  var current = gradeSelect.val();
  gradeSelect.html(gradeOptions(row.find('select[name*="[product]"]').val(), $('#gradeType').val(), ''));
  gradeSelect.val(gradeSelect.find('option[value="' + current + '"]').length ? current : '').trigger('change.select2');
}

function removeDetailRow(button) {
  $(button).closest('tr').remove();
  reindexDetails();
  updateLabelSummary();
}

// Keep posted row indexes contiguous so photos stay matched to their rows
function reindexDetails() {
  $('#weightDetailsTable tr').each(function (index) {
    $(this).find('input, select').each(function () {
      var name = $(this).attr('name');
      if (name) {
        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
      }
    });
  });
  weightCount = $('#weightDetailsTable tr').length;
}

function calculateNet(row) {
  var gross = parseFloat(row.find('input[name*="[gross]"]').val()) || 0;
  var tare = parseFloat(row.find('input[name*="[tare]"]').val()) || 0;
  row.find('input[name*="[weight]"]').val((gross - tare).toFixed(2));
}

function updateLabelSummary() {
  var counts = {};
  $('#weightDetailsTable select[name*="[label]"]').each(function () {
    var val = $(this).val();
    if (val) {
      counts[val] = (counts[val] || 0) + 1;
    }
  });

  var parts = [];
  $.each(counts, function (label, count) {
    parts.push(label + ' : ' + count);
  });
  $('#labelSummary').val(parts.join('\n'));
}

function resetForm() {
  weightCount = 0;
  $('#extendForm').validate().resetForm();
  $('#extendModal').find('#id, #batchNo, #remarks, #labelSummary').val('');
  $('#gradeType').val('Local');
  $('#weightDetailsTable').empty();
}

function newEntry() {
  resetForm();
  $('#packagingDatePicker').datetimepicker('date', moment());
  $('#location, #productionLines').val('').trigger('change');
  $('#extendModal').modal('show');
}

function edit(id) {
  $('#spinnerLoading').show();

  $.post(batchApi, { action: 'get', id: id }, function (obj) {
    if (obj.status !== 'success') {
      toastr.error(obj.message || 'Error loading data', 'Failed:');
      return;
    }

    var msg = obj.message;
    resetForm();
    $('#id').val(msg.id);
    $('#batchNo').val(msg.batch_no);
    $('#remarks').val(msg.remarks);
    $('#labelSummary').val(msg.label_remark);
    $('#location').val(msg.location).trigger('change');
    $('#productionLines').val(msg.production_line || '').trigger('change');
    $('#gradeType').val(msg.type || 'Local');

    if (msg.packaging_date) {
      $('#packagingDatePicker').datetimepicker('date', moment(msg.packaging_date, 'YYYY-MM-DD HH:mm:ss'));
    } else {
      $('#packagingDatePicker').datetimepicker('clear');
    }

    $.each(msg.weightDetails || [], function (i, detail) {
      addDetailRow($.extend({}, detail, { time: detail.packing_time }));
    });

    $('#extendModal').modal('show');
  }).fail(function () {
    toastr.error('Something went wrong', 'Failed:');
  }).always(function () {
    $('#spinnerLoading').hide();
  });
}

// Row-level checks the form validator does not cover (first problem only)
function detailRowsError() {
  if (!$('#location').val()) {
    return 'Location is required.';
  }

  var error = '';
  $('#weightDetailsTable tr').each(function (i) {
    var row = $(this);
    var prefix = 'Row ' + (i + 1) + ': ';
    var required = { '[category]': 'Category', '[product]': 'Product', '[grade]': 'Grade', '[packaging_size]': 'Packaging size' };

    $.each(required, function (field, label) {
      if (!row.find('select[name*="' + field + '"]').val()) {
        error = prefix + label + ' is required.';
        return false;
      }
    });
    if (error) {
      return false;
    }

    if ((parseInt(row.find('input[name*="[unit_per_box]"]').val(), 10) || 0) < 1) {
      error = prefix + 'Unit per box must be at least 1.';
    } else if ((parseFloat(row.find('input[name*="[gross]"]').val()) || 0) <= 0) {
      error = prefix + 'Gross must be greater than 0.';
    } else if ((parseFloat(row.find('input[name*="[weight]"]').val()) || 0) < 0) {
      error = prefix + 'Net weight cannot be negative.';
    } else if ((parseFloat(row.find('input[name*="[weight]"]').val()) || 0) === 0) {
      error = prefix + 'Net weight is 0. Check gross and tare values.';
    }
    return !error;
  });

  return error;
}

function saveEntry() {
  var error = detailRowsError();
  if (error) {
    toastr.error(error, 'Validation Error:');
    return;
  }

  var formData = new FormData($('#extendForm')[0]);
  formData.append('action', 'save');

  $('#spinnerLoading').show();
  $('#saveButton').prop('disabled', true);

  $.ajax({
    url: batchApi,
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function (obj) {
      if (obj.status === 'success') {
        $('#extendModal').modal('hide');
        toastr.success(obj.message, 'Success:');
        batchTable.ajax.reload(null, false);
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

function openBulkAdd() {
  $('#bulkSupplier').html(supplierOptions());
  $('#bulkCategory').html(categoryOptions());
  $('#bulkProduct').html(productOptions('', ''));
  $('#bulkGrade').html(gradeOptions('', $('#gradeType').val(), ''));
  $('#bulkPackagingSize').html(packagingOptions());
  $('#bulkLabel').html(labelOptions());

  var selects = $('#bulkSupplier, #bulkCategory, #bulkProduct, #bulkGrade, #bulkPackagingSize, #bulkLabel');
  selects.val('').removeClass('is-invalid');
  initSelect2(selects, '#bulkAddModal');
  $('#bulkAddModal .invalid-feedback').hide();

  $('#bulkNo').val(1);
  $('#bulkUnitPerBox').val(0);
  $('#bulkWeight').val(0);
  $('#bulkTime').val(currentTime());
  $('#bulkAddModal').modal('show');
}

function addBulkRows() {
  var valid = true;
  $.each(['#bulkCategory', '#bulkProduct', '#bulkGrade', '#bulkPackagingSize'], function (i, id) {
    var el = $(id);
    var feedback = el.closest('.form-group-modern').find('.invalid-feedback');
    el.next('.select2-container').find('.select2-selection').toggleClass('is-invalid', !el.val());
    feedback.toggle(!el.val());
    if (!el.val()) {
      valid = false;
    }
  });
  if (!valid) {
    return;
  }

  var bulkNo = parseInt($('#bulkNo').val(), 10);
  if (!bulkNo || bulkNo < 1) {
    toastr.error('Please enter a valid bulk number.', 'Validation Error:');
    return;
  }

  var weight = $('#bulkWeight').val();
  var detail = {
    supplier_id: $('#bulkSupplier').val(),
    category_id: $('#bulkCategory').val(),
    product_id: $('#bulkProduct').val(),
    grade: $('#bulkGrade').val(),
    packaging_size: $('#bulkPackagingSize').val(),
    label: batchAllowPresetLabel ? $('#bulkLabel').val() : '',
    units_per_box: $('#bulkUnitPerBox').val(),
    gross: weight,
    tare: 0,
    weight: weight,
    time: $('#bulkTime').val()
  };

  for (var i = 0; i < bulkNo; i++) {
    addDetailRow(detail);
  }

  updateLabelSummary();
  $('#bulkAddModal').modal('hide');
}

function deactivate(id) {
  if (confirm(batchText.confirmDelete)) {
    $('#cancelId').val(id);
    $('#cancelReason').val('');
    $('#cancelForm').validate().resetForm();
    $('#cancelModal').modal('show');
  }
}

function cancelEntry() {
  $('#spinnerLoading').show();
  $('#submitCancel').prop('disabled', true);

  $.post(batchApi, $('#cancelForm').serialize() + '&action=cancel', function (obj) {
    if (obj.status === 'success') {
      $('#cancelModal').modal('hide');
      toastr.success(obj.message, 'Success:');
      batchTable.ajax.reload(null, false);
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

function printBatch(id) {
  $.post(batchApi, { action: 'printSlip', id: id }, function (obj) {
    if (obj.status !== 'success') {
      toastr.error(obj.message || 'Something went wrong when printing', 'Failed:');
      return;
    }

    var printWindow = window.open('', '', 'height=' + screen.height + ',width=' + screen.width);
    printWindow.document.write(obj.message);
    printWindow.document.close();
    setTimeout(function () {
      printWindow.print();
      printWindow.close();
    }, 500);
  }).fail(function () {
    toastr.error('Something went wrong when printing', 'Failed:');
  });
}

function openShipmentModal(id) {
  shipmentBatchItems = [];
  $('#shipmentBatchId').val(id);
  $('#shipmentCustomer, #shipmentType').val('').trigger('change');
  $('#shipmentRemark').val('');
  $('#shipmentForm').validate().resetForm();

  $('#spinnerLoading').show();
  $.post('php/modules/loading/getPackagingBatchItems.php', { batch_id: id }, function (obj) {
    if (obj.status !== 'success') {
      toastr.error(obj.message || 'Something went wrong', 'Failed:');
      return;
    }
    if (obj.items.length === 0) {
      toastr.error('No pending items found in this batch.', 'Failed:');
      return;
    }

    shipmentBatchItems = obj.items;
    $('#shipmentLoadingDatePicker').datetimepicker('date', moment());
    $('#shipmentModal').modal('show');
  }, 'json').fail(function () {
    toastr.error('Something went wrong', 'Failed:');
  }).always(function () {
    $('#spinnerLoading').hide();
  });
}

function submitShipment() {
  var customerId = $('#shipmentCustomer').val();
  if (!customerId || !$('#shipmentType').val()) {
    toastr.error('Please fill in all the fields', 'Validation Error:');
    return;
  }

  var remarks = $('#shipmentRemark').val();
  var postData = {
    loadingDate: $('#shipmentLoadingDate').val(),
    shipmentType: $('#shipmentType').val(),
    remarks: remarks
  };
  $.each(shipmentBatchItems, function (i, item) {
    var prefix = 'items[' + i + ']';
    postData[prefix + '[packaging_batch_item_id]'] = item.id;
    postData[prefix + '[packaging_batch_id]'] = item.packaging_batch_id;
    postData[prefix + '[customer_id]'] = customerId;
    postData[prefix + '[product_id]'] = item.product_id;
    postData[prefix + '[grade]'] = item.grade;
    postData[prefix + '[packaging_size]'] = item.packaging_size;
    postData[prefix + '[units_per_box]'] = item.units_per_box;
    postData[prefix + '[weight]'] = item.weight;
    postData[prefix + '[loading_time]'] = moment().format('HH:mm');
    postData[prefix + '[remarks]'] = remarks;
  });

  $('#spinnerLoading').show();
  $('#submitShipment').prop('disabled', true);

  $.post('php/modules/loading/loadingOrder.php', postData, function (obj) {
    if (obj.status === 'success') {
      $('#shipmentModal').modal('hide');
      toastr.success(obj.message, 'Success:');
      batchTable.ajax.reload(null, false);
    } else {
      toastr.error(obj.message || 'Something went wrong', 'Failed:');
    }
  }, 'json').fail(function () {
    toastr.error('Something went wrong', 'Failed:');
  }).always(function () {
    $('#submitShipment').prop('disabled', false);
    $('#spinnerLoading').hide();
  });
}

function formatExpandedRow(row) {
  var e = escapeHtml;
  var infoItem = function (label, value) {
    return '<div><span class="info-item-label">' + e(label) + '</span><span class="info-item-value">' + e(value || '-') + '</span></div>';
  };
  var kpi = function (label, valueHtml) {
    return '<div class="kpi-card"><div class="kpi-label">' + e(label) + '</div><div class="kpi-value">' + valueHtml + '</div></div>';
  };

  var rows = '';
  $.each(row.weightDetails || [], function (i, d) {
    rows += '<tr data-product="' + e(d.product_name) + '" data-grade="' + e(d.grade_name) + '">' +
      '<td>' + e(d.product_name) + '</td>' +
      '<td><span class="grade-badge">' + e(d.grade_name) + '</span></td>' +
      '<td>' + e(d.packaging_size_name) + '</td>' +
      '<td>' + e(d.label) + '</td>' +
      '<td class="text-right text-mono">' + e(d.units_per_box) + '</td>' +
      '<td class="text-right text-mono text-primary font-weight-bold">' + (parseFloat(d.weight) || 0).toFixed(2) + '</td>' +
      '<td class="text-center text-muted">' + e(d.packing_time) + '</td>' +
      '<td class="text-center">' + statusBadge(d.status) + '</td>' +
      (batchAllowPhoto ? '<td class="text-center">' + (d.photo_path ? '<a href="' + photoUrl(d.photo_path) + '" target="_blank" class="btn btn-outline-secondary btn-sm btn-photo"><i class="fas fa-image"></i></a>' : '-') + '</td>' : '') +
      '</tr>';
  });

  return '<div class="expanded-row-content">' +
    '<div class="expanded-header">' +
    '<div><div class="expanded-header-title">' + e(row.batch_no) + '</div><div class="expanded-header-subtitle">' + e(row.locations || '-') + '</div></div>' +
    '<div class="expanded-actions">' + actionButtons(row.id, row.status) + '</div>' +
    '</div>' +
    '<div class="kpi-row">' +
    kpi(batchText.packagingDate, e(row.packaging_date || '-')) +
    kpi(batchText.productionLines, e(row.production_lines || '-')) +
    kpi(batchText.status, statusBadge(row.status)) +
    '</div>' +
    '<div class="info-section">' +
    '<div class="info-section-title">' + e(batchText.orderInformation) + '</div>' +
    '<div class="info-grid">' +
    infoItem(batchText.batchNo, row.batch_no) +
    infoItem(batchText.locations, row.locations) +
    infoItem(batchText.productionLines, row.production_lines) +
    '</div>' +
    (row.remarks ? '<div class="info-remark"><span class="info-item-label">' + e(batchText.remark) + '</span><span class="info-item-value">' + e(row.remarks) + '</span></div>' : '') +
    (batchAllowPresetLabel && row.label_remark ? '<div class="info-remark"><span class="info-item-label">' + e(batchText.labelSummary) + '</span><span class="info-item-value" style="white-space:pre-line;">' + e(row.label_remark) + '</span></div>' : '') +
    '</div>' +
    '<div class="details-section">' +
    '<div class="details-header">' +
    '<span class="details-title">' + e(batchText.weightDetails) + '</span>' +
    '<div class="details-filters">' +
    '<select class="form-control form-control-sm details-filter-select" id="productFilter_' + row.id + '"><option value="">' + e(batchText.allProducts) + '</option></select>' +
    '<select class="form-control form-control-sm details-filter-select" id="gradeFilter_' + row.id + '"><option value="">' + e(batchText.allGrades) + '</option></select>' +
    '</div>' +
    '</div>' +
    '<div class="table-responsive">' +
    '<table class="table details-table mb-0" id="detailTable_' + row.id + '">' +
    '<thead><tr><th>' + e(batchText.product) + '</th><th>' + e(batchText.grade) + '</th><th>' + e(batchText.packagingSize) + '</th>' +
    '<th>' + e(batchText.label) + '</th><th class="text-right">' + e(batchText.unitPerBox) + '</th><th class="text-right">' + e(batchText.weight) + '</th>' +
    '<th class="text-center">' + e(batchText.time) + '</th><th class="text-center">' + e(batchText.status) + '</th>' +
    (batchAllowPhoto ? '<th class="text-center">' + e(batchText.photo) + '</th>' : '') + '</tr></thead>' +
    '<tbody>' + rows + '</tbody></table>' +
    '</div>' +
    '</div>' +
    '</div>';
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

// Show rows matching the product / grade filters
function filterDetailTable(rowId) {
  var product = $('#productFilter_' + rowId).val();
  updateGradeFilterOptions(rowId, product);
  var grade = $('#gradeFilter_' + rowId).val();

  $('#detailTable_' + rowId + ' tbody tr').each(function () {
    $(this).toggle((!product || $(this).attr('data-product') == product) && (!grade || $(this).attr('data-grade') == grade));
  });
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
