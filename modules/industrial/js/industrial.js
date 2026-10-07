// Industrial (pulp & paste) list / entry page. Requires `industrialPermissions`, `industrialFlags`, `industrialLookups` and `industrialText`.

// 1. Variables
var industrialApi = 'php/modules/industrial/api.php';
var productsApi = 'php/modules/products/api.php';
var industrialTable;
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
      placeholder: industrialText.pleaseSelect,
      dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-content') : $(this).parent()
    });
  });

  industrialTable = $('#weightTable').DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: true,
    serverMethod: 'post',
    searching: true,
    order: [[1, 'asc']],
    language: {
      emptyTable: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title">' + escapeHtml(industrialText.noRecordsFound) + '</div><div class="empty-message">' + escapeHtml(industrialText.noRecordsMessage) + '</div></div>',
      zeroRecords: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title">' + escapeHtml(industrialText.noMatchingRecords) + '</div><div class="empty-message">' + escapeHtml(industrialText.noMatchingMessage) + '</div></div>'
    },
    ajax: {
      url: industrialApi,
      data: function (d) {
        return $.extend(d, filterParams(), { action: 'list' });
      }
    },
    columns: tableColumns()
  });

  $('#filterSearch').on('click', function () {
    industrialTable.ajax.reload();
  });

  // Expand / collapse row details on row click
  $('#weightTable tbody').on('click', 'tr', function (e) {
    if ($(e.target).closest('td').hasClass('action-button') || $(e.target).closest('button, select, input, a').length) {
      return;
    }

    var tr = $(this);
    var row = industrialTable.row(tr);
    if (!row.data()) {
      return;
    }

    if (row.child.isShown()) {
      row.child.hide();
      tr.removeClass('shown');
      return;
    }

    $.post(industrialApi, { action: 'get', id: row.data().id }, function (obj) {
      if (obj.status === 'success') {
        row.child(formatExpandedRow(obj.message)).show();
        tr.addClass('shown');
        populateDetailFilters(obj.message.id, obj.message.weightDetails);
      } else {
        toastr.error(obj.message, 'Failed:');
      }
    }, 'json').fail(function () {
      toastr.error('Something went wrong', 'Failed:');
    });
  });

  $('#transactionStatusFilter').on('change', function () {
    var isIncoming = $(this).val() === 'INCOMING';
    $('#customerStatusDiv').toggle(!isIncoming);
    $('#supplierStatusDiv').toggle(isIncoming);
  });

  $('#vehicleNoFilter').on('change', function () {
    $('#otherVehicleFilterDiv').toggle(isOtherOption($(this).val()));
  });

  $('#addEntry').on('click', function () {
    newEntry();
  });

  // Entry form header
  $('#status').on('change', function () {
    var isIncoming = $(this).val() === 'INCOMING';
    $('#customerDiv').toggle(!isIncoming);
    $('#supplierDiv').toggle(isIncoming);
    $('#customerOtherDiv').toggle(!isIncoming && $('#customer').val() === 'OTHERS');
    $('#supplierOtherDiv').toggle(isIncoming && $('#supplier').val() === 'OTHERS');

    $('#weightDetailsTable').children('tr').each(function () {
      refreshProductRow($(this));
    });
  });

  $('#customer').on('change', function () {
    $('#customerOtherDiv').toggle($(this).val() === 'OTHERS' && $('#status').val() !== 'INCOMING');
  });

  $('#supplier').on('change', function () {
    $('#supplierOtherDiv').toggle($(this).val() === 'OTHERS' && $('#status').val() === 'INCOMING');
  });

  $('#vehicle').on('change', function () {
    $('#vehicleNoOtherDiv').toggle(isOtherOption($(this).val()));
  });

  $('#addWeightBtn').on('click', function () {
    addDetailRow('weight', null, null);
  });

  $('#addRejectWeightBtn').on('click', function () {
    addDetailRow('reject', null, null);
  });

  // Product chosen: names, variance and (weight rows) price list price
  $('#weightDetailsTable, #rejectDetailsTable').on('change', 'select[name$="[product]"]', function () {
    var row = $(this).closest('tr');
    var product = findProduct($(this).val());
    field(row, 'product_name').val(product ? product.product_name : '');
    field(row, 'product_desc').val(product ? product.product_name : '');

    if (row.closest('#weightDetailsTable').length) {
      refreshProductRow(row);
    }
  });

  // Net = |gross - tare|
  $('#weightDetailsTable, #rejectDetailsTable').on('change', 'input[name$="[gross]"], input[name$="[tare]"]', function () {
    var row = $(this).closest('tr');
    var net = Math.abs((parseFloat(field(row, 'gross').val()) || 0) - (parseFloat(field(row, 'tare').val()) || 0));
    field(row, 'net').val(net.toFixed(2));

    if (row.closest('#weightDetailsTable').length) {
      calculateVariance(row);
    }
    applyRowPrice(row);
  });

  $('#weightDetailsTable').on('change', 'input[name$="[price]"]', function () {
    applyRowPrice($(this).closest('tr'));
  });

  $('#weightDetailsTable').on('click', '.reject-row', function () {
    moveDetailRow($(this).closest('tr'), 'reject');
  });

  $('#rejectDetailsTable').on('click', '.accept-row', function () {
    moveDetailRow($(this).closest('tr'), 'weight');
  });

  $('#weightDetailsTable').on('click', '.remove-row', function () {
    removeDetailRow($(this).closest('tr'), 'weight');
  });

  $('#rejectDetailsTable').on('click', '.remove-row', function () {
    removeDetailRow($(this).closest('tr'), 'reject');
  });

  $('#extendForm').on('click', '.photo-btn', function () {
    $(this).siblings('input[type="file"]').trigger('click');
  });

  $('#extendForm').on('change', 'input[type="file"]', function () {
    var files = $(this).prop('files');
    $(this).siblings('.photo-status').html(files && files.length ? '<i class="fas fa-check-circle text-success"></i>' : '');
  });

  $('#bulkUnitPrice').on('input', function () {
    var price = parseFloat($(this).val());
    if (isNaN(price)) {
      return;
    }
    $('#weightDetailsTable .weight-check:checked').each(function () {
      var row = $(this).closest('tr');
      field(row, 'price').val(price.toFixed(2));
      applyRowPrice(row);
    });
  });

  $('#selectAllWeightCheckbox').on('change', function () {
    $('#weightDetailsTable .weight-check').prop('checked', $(this).prop('checked'));
  });

  $('#extendForm').validate(entryValidationOptions());
  $('#cancelForm').validate(validationOptions(cancelEntry));
  $('#printOptionsForm').validate(validationOptions(printEntry));
});

// 3. Functions
function escapeHtml(value) {
  return $('<div>').text(value === null || value === undefined ? '' : value).html();
}

function fixed(value) {
  return (parseFloat(value) || 0).toFixed(2);
}

function isOtherOption(value) {
  return value === 'OTHERS' || value === 'UNKNOWN' || value === 'UNKOWN NO';
}

// Named input / select of a detail row, e.g. field(row, 'net') => [name$="[net]"]
function field(row, key) {
  return row.find('[name$="[' + key + ']"]');
}

function photoUrl(path) {
  return 'php/viewPhoto.php?file=' + encodeURIComponent(path);
}

function currentTime() {
  return moment().format('HH:mm:ss');
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

function entryValidationOptions() {
  var options = validationOptions(saveEntry);
  options.ignore = [];
  options.errorPlacement = function (error, element) {
    error.addClass('invalid-feedback').css('display', 'block');
    if (element.next('.select2-container').length) {
      error.insertAfter(element.next('.select2-container'));
    } else {
      element.closest('.form-group-modern, td').append(error);
    }
  };
  return options;
}

function filterParams() {
  return {
    fromDate: $('#fromDate').val(),
    toDate: $('#toDate').val(),
    transactionStatus: $('#transactionStatusFilter').val(),
    status: 'active',
    customer: $('#customerNoFilter').val() || '',
    supplier: $('#supplierNoFilter').val() || '',
    vehicle: $('#vehicleNoFilter').val() || '',
    otherVehicle: $('#otherVehicleNoFilter').val() || '',
    checkedBy: $('#checkedByFilter').val() || '',
    weightedBy: $('#weightByFilter').val() || ''
  };
}

// ============================================================================
// LIST
// ============================================================================

function tableColumns() {
  var text = $.fn.dataTable.render.text();
  var columns = $.map(
    ['serial_no', 'po_no', 'start_time', 'end_time', 'parent', 'customer_supplier', 'total_item', 'total_gross', 'total_tare', 'total_nett', 'total_variance', 'total_variance_perc', 'weighted_by', 'indicator'],
    function (data) { return { data: data, render: text }; }
  );

  if (industrialFlags.secRemark) {
    columns.push({ data: 'remarks2', render: text });
  }

  columns.push({
    data: 'id',
    responsivePriority: 1,
    orderable: false,
    className: 'action-button',
    render: function (data) {
      return '<div class="d-flex" style="gap:4px;">' + actionButtons(data) + '</div>';
    }
  });

  return columns;
}

function actionButtons(id) {
  id = parseInt(id);
  var buttons = '';
  if (industrialPermissions.allowEdit) {
    buttons += '<button type="button" onclick="edit(' + id + ')" class="btn btn-sm btn-outline-primary" title="' + escapeHtml(industrialText.edit) + '"><i class="fas fa-pen"></i></button>';
  }
  buttons += '<button type="button" onclick="openPrint(' + id + ')" class="btn btn-sm btn-outline-secondary" title="' + escapeHtml(industrialText.print) + '"><i class="fas fa-print"></i></button>';
  if (industrialPermissions.allowDelete) {
    buttons += '<button type="button" onclick="deactivate(' + id + ')" class="btn btn-sm btn-outline-danger" title="' + escapeHtml(industrialText.delete) + '"><i class="fas fa-trash"></i></button>';
  }
  return buttons;
}

function formatExpandedRow(row) {
  var showPrice = industrialFlags.showPrice;
  var t = industrialText;
  var info = function (label, value) {
    return '<div><span class="info-item-label">' + escapeHtml(label) + '</span><span class="info-item-value">' + escapeHtml(value || '-') + '</span></div>';
  };
  var photoCell = function (detail) {
    return industrialFlags.photo
      ? '<td class="text-center">' + (detail.photoPath ? '<a href="' + photoUrl(detail.photoPath) + '" target="_blank" class="btn btn-outline-secondary btn-sm btn-photo"><i class="fas fa-image"></i></a>' : '-') + '</td>'
      : '';
  };
  var priceHeaders = showPrice ? '<th class="text-right">' + escapeHtml(t.price) + '</th><th class="text-right">' + escapeHtml(t.total) + '</th>' : '';

  var html = '<div class="expanded-row-content">' +
    '<div class="expanded-header"><div>' +
      '<div class="expanded-header-title">' + escapeHtml(row.serial_no) + '</div>' +
      '<div class="expanded-header-subtitle">' + escapeHtml(row.customer_supplier || '-') + '</div>' +
    '</div><div class="expanded-actions">' + actionButtons(row.id) + '</div></div>' +
    '<div class="kpi-row">' +
      '<div class="kpi-card"><div class="kpi-label">' + escapeHtml(t.totalItem) + '</div><div class="kpi-value">' + (row.totalItems || 0) + '</div></div>' +
      '<div class="kpi-card"><div class="kpi-label">' + escapeHtml(t.totalGross) + '</div><div class="kpi-value">' + fixed(row.totalGross) + ' <span class="kpi-unit">Kg</span></div></div>' +
      '<div class="kpi-card"><div class="kpi-label">' + escapeHtml(t.totalNett) + '</div><div class="kpi-value kpi-value-primary">' + fixed(row.totalNett) + ' <span class="kpi-unit">Kg</span></div></div>' +
      '<div class="kpi-card"><div class="kpi-label">' + escapeHtml(t.totalVariance) + '</div><div class="kpi-value kpi-value-danger">' + fixed(row.totalVariance) + ' <span class="kpi-unit">Kg</span></div></div>' +
      (showPrice ? '<div class="kpi-card kpi-card-success"><div class="kpi-label">' + escapeHtml(t.totalPrice) + '</div><div class="kpi-value">' + fixed(row.totalPrice) + '</div></div>' : '') +
    '</div>' +
    '<div class="info-section"><div class="info-section-title">' + escapeHtml(t.orderInformation) + '</div><div class="info-grid">' +
      info(t.serialNo, row.serial_no) + info(t.doPoNo, row.po_no) + info(t.customerSupplier, row.customer_supplier) +
      info(t.weighedBy, row.weighted_by) + info(t.location, row.location_name) + info(t.indicator, row.indicator) +
    '</div>' +
    (row.remark ? '<div class="info-remark"><span class="info-item-label">' + escapeHtml(t.remark) + '</span><span class="info-item-value">' + escapeHtml(row.remark) + '</span></div>' : '') +
    '</div>';

  // Weighing details (rows carry their values for the product filter)
  var totals = { gross: 0, tare: 0, net: 0, variance: 0, total: 0 };
  html += '<div class="details-section"><div class="details-header">' +
    '<span class="details-title">' + escapeHtml(t.weighingDetails) + '</span>' +
    '<div class="details-filters"><select class="form-control form-control-sm details-filter-select" id="productFilter_' + row.id + '" onchange="filterDetailTable(' + parseInt(row.id) + ')"><option value="">' + escapeHtml(t.allProducts) + '</option></select></div>' +
    '</div><div class="table-responsive"><table class="table details-table mb-0" id="weightTable_' + row.id + '"><thead><tr>' +
      '<th>' + escapeHtml(t.product) + '</th><th class="text-right">' + escapeHtml(t.gross) + '</th><th class="text-right">' + escapeHtml(t.tare) + '</th><th class="text-right">' + escapeHtml(t.net) + '</th>' +
      '<th class="text-right">' + escapeHtml(t.variance) + '</th><th class="text-right">' + escapeHtml(t.variance) + ' (%)</th>' + priceHeaders +
      '<th class="text-center">' + escapeHtml(t.time) + '</th>' + (industrialFlags.photo ? '<th class="text-center">' + escapeHtml(t.photo) + '</th>' : '') +
    '</tr></thead><tbody>';

  $.each(row.weightDetails, function (i, detail) {
    var tr = $('<tr>').attr({
      'data-product': detail.product_name, 'data-gross': detail.gross, 'data-tare': detail.tare,
      'data-net': detail.net, 'data-variance': detail.variance || 0, 'data-total': detail.total || 0
    });
    tr.html(
      '<td>' + escapeHtml(detail.product_name) + '</td>' +
      '<td class="text-right text-mono">' + fixed(detail.gross) + '</td>' +
      '<td class="text-right text-mono">' + fixed(detail.tare) + '</td>' +
      '<td class="text-right text-mono text-primary font-weight-bold">' + fixed(detail.net) + '</td>' +
      '<td class="text-right text-mono">' + fixed(detail.variance) + '</td>' +
      '<td class="text-right text-mono">' + fixed(detail.varPerc) + '</td>' +
      (showPrice ? '<td class="text-right text-mono">' + fixed(detail.price) + '</td><td class="text-right text-mono text-success font-weight-bold">' + fixed(detail.total) + '</td>' : '') +
      '<td class="text-center text-muted">' + escapeHtml(detail.time) + '</td>' +
      photoCell(detail)
    );
    html += tr.prop('outerHTML');

    totals.gross += parseFloat(detail.gross) || 0;
    totals.tare += parseFloat(detail.tare) || 0;
    totals.net += parseFloat(detail.net) || 0;
    totals.variance += parseFloat(detail.variance) || 0;
    totals.total += parseFloat(detail.total) || 0;
  });

  html += '</tbody><tfoot><tr><td>' + escapeHtml(t.total) + '</td>' +
    '<td class="text-right text-mono" id="footGross_' + row.id + '">' + totals.gross.toFixed(2) + '</td>' +
    '<td class="text-right text-mono" id="footTare_' + row.id + '">' + totals.tare.toFixed(2) + '</td>' +
    '<td class="text-right text-mono text-primary" id="footNet_' + row.id + '">' + totals.net.toFixed(2) + '</td>' +
    '<td class="text-right text-mono" id="footVariance_' + row.id + '">' + totals.variance.toFixed(2) + '</td><td></td>' +
    (showPrice ? '<td></td><td class="text-right text-mono text-success" id="footPrice_' + row.id + '">' + totals.total.toFixed(2) + '</td>' : '') +
    '<td></td>' + (industrialFlags.photo ? '<td></td>' : '') +
    '</tr></tfoot></table></div></div>';

  // Reject details
  var rejectColspan = 5 + (showPrice ? 2 : 0) + (industrialFlags.photo ? 1 : 0);
  var rejectTotals = { gross: 0, tare: 0, net: 0, total: 0 };
  html += '<div class="details-section"><div class="details-header">' +
    '<span class="details-title details-title-danger"><i class="fas fa-times-circle mr-1"></i>' + escapeHtml(t.rejectDetails) + '</span></div>' +
    '<div class="table-responsive"><table class="table details-table mb-0"><thead><tr>' +
      '<th>' + escapeHtml(t.product) + '</th><th class="text-right">' + escapeHtml(t.gross) + '</th><th class="text-right">' + escapeHtml(t.tare) + '</th><th class="text-right">' + escapeHtml(t.net) + '</th>' + priceHeaders +
      '<th class="text-center">' + escapeHtml(t.time) + '</th>' + (industrialFlags.photo ? '<th class="text-center">' + escapeHtml(t.photo) + '</th>' : '') +
    '</tr></thead><tbody>';

  if (!row.rejectDetails.length) {
    html += '<tr><td colspan="' + rejectColspan + '" class="details-empty"><i class="fas fa-check-circle"></i> ' + escapeHtml(t.noRejectItems) + '</td></tr>';
  }

  $.each(row.rejectDetails, function (i, detail) {
    html += '<tr>' +
      '<td>' + escapeHtml(detail.product_name) + '</td>' +
      '<td class="text-right text-mono">' + fixed(detail.gross) + '</td>' +
      '<td class="text-right text-mono">' + fixed(detail.tare) + '</td>' +
      '<td class="text-right text-mono text-danger font-weight-bold">' + fixed(detail.net) + '</td>' +
      (showPrice ? '<td class="text-right text-mono">' + fixed(detail.price) + '</td><td class="text-right text-mono text-danger font-weight-bold">' + fixed(detail.total) + '</td>' : '') +
      '<td class="text-center text-muted">' + escapeHtml(detail.time) + '</td>' +
      photoCell(detail) +
      '</tr>';

    rejectTotals.gross += parseFloat(detail.gross) || 0;
    rejectTotals.tare += parseFloat(detail.tare) || 0;
    rejectTotals.net += parseFloat(detail.net) || 0;
    rejectTotals.total += parseFloat(detail.total) || 0;
  });

  html += '</tbody>';
  if (row.rejectDetails.length) {
    html += '<tfoot><tr><td>' + escapeHtml(t.total) + '</td>' +
      '<td class="text-right text-mono">' + rejectTotals.gross.toFixed(2) + '</td>' +
      '<td class="text-right text-mono">' + rejectTotals.tare.toFixed(2) + '</td>' +
      '<td class="text-right text-mono text-danger">' + rejectTotals.net.toFixed(2) + '</td>' +
      (showPrice ? '<td></td><td class="text-right text-mono text-danger">' + rejectTotals.total.toFixed(2) + '</td>' : '') +
      '<td></td>' + (industrialFlags.photo ? '<td></td>' : '') +
      '</tr></tfoot>';
  }
  html += '</table></div></div></div>';

  return html;
}

function populateDetailFilters(rowId, weightDetails) {
  var products = [];
  $.each(weightDetails, function (i, detail) {
    if (products.indexOf(detail.product_name) === -1) {
      products.push(detail.product_name);
    }
  });

  var productSelect = $('#productFilter_' + rowId);
  $.each(products, function (i, product) {
    productSelect.append($('<option>').val(product).text(product));
  });
}

// Product filter inside an expanded row, with recalculated footer totals
function filterDetailTable(rowId) {
  var productFilter = $('#productFilter_' + rowId).val();
  var totals = { gross: 0, tare: 0, net: 0, variance: 0, total: 0 };

  $('#weightTable_' + rowId + ' tbody tr').each(function () {
    var tr = $(this);
    var show = !productFilter || String(tr.attr('data-product')) === productFilter;
    tr.toggle(show);

    if (show) {
      $.each(totals, function (key) {
        totals[key] += parseFloat(tr.attr('data-' + key)) || 0;
      });
    }
  });

  $('#footGross_' + rowId).text(totals.gross.toFixed(2));
  $('#footTare_' + rowId).text(totals.tare.toFixed(2));
  $('#footNet_' + rowId).text(totals.net.toFixed(2));
  $('#footVariance_' + rowId).text(totals.variance.toFixed(2));
  $('#footPrice_' + rowId).text(totals.total.toFixed(2));
}

// ============================================================================
// ENTRY FORM
// ============================================================================

function resetForm() {
  // Rows first, so the header change handlers below do not touch old rows
  clearDetailRows();
  $('#extendForm').validate().resetForm();
  $('#extendForm .is-invalid').removeClass('is-invalid');
  $('#id, #serialNo, #doPoNo, #securityBillNo, #driver, #customerOther, #supplierOther, #otherVehicleNo, #remarks, #remarks2, #bulkUnitPrice').val('');
  $('#customer, #supplier, #vehicle').val('').trigger('change');
  $('#endTimePicker').datetimepicker('clear');
  $('#selectAllWeightCheckbox').prop('checked', false);
}

function clearDetailRows() {
  $('#weightDetailsTable, #rejectDetailsTable').find('.detail-select').select2('destroy');
  $('#weightDetailsTable, #rejectDetailsTable').empty();
  weightCount = 0;
  rejectCount = 0;
  updateTotals();
}

function newEntry() {
  resetForm();
  $('#status').val('INCOMING').trigger('change');
  $('#location').val(industrialLookups.userLocationId || '').trigger('change');
  $('#startTimePicker').datetimepicker('date', moment());
  $('#extendModal').modal('show');
}

function edit(id) {
  $('#spinnerLoading').show();

  $.post(industrialApi, { action: 'get', id: id }, function (obj) {
    if (obj.status !== 'success') {
      toastr.error(obj.message, 'Failed:');
      return;
    }

    var record = obj.message;
    resetForm();
    $('#id').val(record.id);
    $('#serialNo').val(record.serial_no);
    $('#status').val(record.status).trigger('change');
    $('#doPoNo').val(record.po_no || '');
    $('#securityBillNo').val(record.security_bills || '');
    $('#driver').val(record.driver || '');
    $('#customer').val(record.customer || '').trigger('change');
    $('#customerOther').val(record.other_customer || '');
    $('#supplier').val(record.supplier || '').trigger('change');
    $('#supplierOther').val(record.other_supplier || '');
    $('#location').val(record.location || '').trigger('change');
    $('#remarks').val(record.remark || '');
    $('#remarks2').val(record.remarks2 || '');

    // '-' is stored when no vehicle was chosen
    if (record.other_vehicle && record.vehicle_no && record.vehicle_no !== '-') {
      $('#vehicle').val('OTHERS').trigger('change');
      $('#otherVehicleNo').val(record.vehicle_no);
    } else {
      $('#vehicle').val(record.vehicle_no === '-' ? '' : (record.vehicle_no || '')).trigger('change');
    }

    if (record.start_time) {
      $('#startTimePicker').datetimepicker('date', moment(record.start_time, 'YYYY-MM-DD HH:mm:ss'));
    } else {
      $('#startTimePicker').datetimepicker('clear');
    }
    if (record.end_time) {
      $('#endTimePicker').datetimepicker('date', moment(record.end_time, 'YYYY-MM-DD HH:mm:ss'));
    }

    $.each(record.weightDetails, function (i, detail) {
      addDetailRow('weight', $.extend({}, detail, { variancePerc: detail.varPerc }), null);
    });
    $.each(record.rejectDetails, function (i, detail) {
      addDetailRow('reject', detail, null);
    });
    updateTotals();
    $('#extendModal').modal('show');
  }, 'json').fail(function () {
    toastr.error('Something wrong when pull data', 'Failed:');
  }).always(function () {
    $('#spinnerLoading').hide();
  });
}

function saveEntry() {
  if (isOtherOption($('#vehicle').val()) && !$.trim($('#otherVehicleNo').val())) {
    toastr.error(industrialText.enterVehicleNo, 'Validation Error:');
    return;
  }

  var productMissing = false;
  $('#weightDetailsTable tr').each(function () {
    if (!field($(this), 'product').val()) {
      productMissing = true;
      return false;
    }
  });
  if (productMissing) {
    toastr.error(industrialText.productRowError, 'Validation Error:');
    return;
  }

  var formData = new FormData($('#extendForm')[0]);
  formData.append('action', 'save');

  $('#saveButton').prop('disabled', true);
  $('#spinnerLoading').show();

  $.ajax({
    url: industrialApi,
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'json'
  }).done(function (obj) {
    if (obj.status === 'success') {
      $('#extendModal').modal('hide');
      toastr.success(obj.message, 'Success:');
      industrialTable.ajax.reload(null, false);
    } else {
      toastr.error(obj.message, 'Failed:');
    }
  }).fail(function () {
    toastr.error('Something wrong when saving', 'Failed:');
  }).always(function () {
    $('#saveButton').prop('disabled', false);
    $('#spinnerLoading').hide();
  });
}

function deactivate(id) {
  if (!confirm(industrialText.confirmDelete)) {
    return;
  }

  $('#cancelForm').validate().resetForm();
  $('#cancelId').val(id);
  $('#cancelReason').val('');
  $('#cancelModal').modal('show');
}

function cancelEntry() {
  $('#submitCancel').prop('disabled', true);
  $('#spinnerLoading').show();

  $.post(industrialApi, { action: 'cancel', id: $('#cancelId').val(), cancelReason: $('#cancelReason').val() }, function (obj) {
    if (obj.status === 'success') {
      $('#cancelModal').modal('hide');
      toastr.success(obj.message, 'Success:');
      industrialTable.ajax.reload(null, false);
    } else {
      toastr.error(obj.message, 'Failed:');
    }
  }, 'json').fail(function () {
    toastr.error('Something wrong when delete', 'Failed:');
  }).always(function () {
    $('#submitCancel').prop('disabled', false);
    $('#spinnerLoading').hide();
  });
}

// ============================================================================
// DETAIL ROWS
// ============================================================================

function findProduct(productId) {
  var found = null;
  $.each(industrialLookups.products, function (i, product) {
    if (product.id == productId) {
      found = product;
      return false;
    }
  });
  return found;
}

// <option>s for products (a saved product outside the list is kept)
function productOptions(selected, selectedName) {
  var html = '<option value="">' + escapeHtml(industrialText.selectProduct) + '</option>';
  var found = false;

  $.each(industrialLookups.products, function (i, product) {
    found = found || product.id == selected;
    html += '<option value="' + product.id + '">' + escapeHtml(product.product_name) + '</option>';
  });

  if (selected && !found) {
    html += '<option value="' + escapeHtml(selected) + '">' + escapeHtml(selectedName || selected) + '</option>';
  }

  return html;
}

function rowDefaults(isReject) {
  return {
    product: '',
    product_name: '',
    product_desc: '',
    pretare: '0.00',
    unit: 'Kg',
    package: '',
    fixedfloat: '',
    isedit: 'N',
    reject: '0.00',
    isRejected: isReject ? 'YES' : 'NO',
    gross: 0,
    tare: 0,
    net: 0,
    variance: 0,
    variancePerc: 0,
    price: 0,
    total: 0,
    time: currentTime(),
    photoPath: ''
  };
}

// Weight / reject row. detail = saved or moved row values; fileInput = photo input carried over from a moved row.
function addDetailRow(type, detail, fileInput) {
  var isReject = type === 'reject';
  var idx = isReject ? rejectCount++ : weightCount++;
  var prefix = isReject ? 'rejectDetails' : 'weightDetails';
  var fileName = (isReject ? 'rejectPhotoFiles' : 'photoFiles') + '[' + idx + ']';
  var d = $.extend(rowDefaults(isReject), detail || {});
  var name = function (key) {
    return prefix + '[' + idx + '][' + key + ']';
  };
  var hidden = function (key) {
    return '<input type="hidden" name="' + name(key) + '" value="' + escapeHtml(d[key]) + '">';
  };
  var number = function (key, attributes) {
    return '<input type="number" class="form-control" name="' + name(key) + '" step="0.01" value="' + fixed(d[key]) + '"' + (attributes || '') + '>';
  };

  // Values without a visible cell are posted as hidden inputs
  var hiddenKeys = ['product_name', 'product_desc', 'pretare', 'unit', 'package', 'fixedfloat', 'isedit', 'reject', 'isRejected', 'photoPath'];
  if (isReject) {
    hiddenKeys.push('variance', 'variancePerc');
  }
  if (!industrialFlags.showPrice) {
    hiddenKeys.push('price', 'total');
  }

  var cells = isReject
    ? '<td class="text-center row-no"></td>'
    : '<td class="text-center"><input type="checkbox" class="weight-check"></td>';
  cells += '<td style="display:none">' + $.map(hiddenKeys, hidden).join('') + '</td>';
  cells += '<td><select class="form-control detail-select" name="' + name('product') + '">' + productOptions(d.product, d.product_name) + '</select></td>';
  cells += '<td>' + number('gross') + '</td><td>' + number('tare') + '</td><td>' + number('net', ' readonly') + '</td>';

  if (!isReject) {
    cells += '<td>' + number('variance', ' readonly') + '</td><td>' + number('variancePerc', ' readonly') + '</td>';
  }
  if (industrialFlags.showPrice) {
    cells += '<td>' + number('price', isReject ? ' readonly' : '') + '</td><td>' + number('total', ' readonly') + '</td>';
  }

  cells += '<td><input type="time" class="form-control" name="' + name('time') + '" value="' + escapeHtml(d.time) + '"></td>';

  if (industrialFlags.photo) {
    cells += '<td class="text-nowrap">' +
      '<input type="file" name="' + fileName + '" accept=".png,.jpg,.jpeg" style="display:none">' +
      (d.photoPath ? '<a href="' + photoUrl(d.photoPath) + '" target="_blank" class="btn btn-success btn-sm mr-1"><i class="fas fa-image"></i></a>' : '') +
      '<button type="button" class="btn btn-info btn-sm photo-btn"><i class="fas fa-camera"></i></button>' +
      '<span class="photo-status"></span>' +
      '</td>';
  }

  cells += '<td class="text-nowrap">' +
    (isReject
      ? '<button type="button" class="btn btn-success btn-sm accept-row"><i class="fas fa-check"></i></button> '
      : '<button type="button" class="btn btn-warning btn-sm reject-row"><i class="fas fa-times"></i></button> ') +
    '<button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button>' +
    '</td>';

  var row = $('<tr class="details">' + cells + '</tr>');
  $(isReject ? '#rejectDetailsTable' : '#weightDetailsTable').append(row);
  field(row, 'product').val(d.product || '');

  if (fileInput && fileInput.length) {
    row.find('input[type="file"]').replaceWith(fileInput.attr('name', fileName));
    var files = fileInput.prop('files');
    if (files && files.length) {
      row.find('.photo-status').html('<i class="fas fa-check-circle text-success"></i>');
    }
  }

  row.find('.detail-select').select2({
    allowClear: true,
    placeholder: industrialText.pleaseSelect,
    dropdownParent: $('#extendModal .modal-content'),
    width: '100%'
  });

  renumberRejectRows();
  updateTotals();
  return row;
}

// Field values of a row keyed by field name
function rowData(row) {
  var data = {};
  row.find('input[name], select[name]').not('[type="file"]').each(function () {
    var match = /\[(\w+)\]$/.exec($(this).attr('name'));
    if (match) {
      data[match[1]] = $(this).val();
    }
  });
  return data;
}

// Move a row between the weight and reject tables, keeping its values and chosen photo
function moveDetailRow(row, toType) {
  var data = rowData(row);
  var fileInput = row.find('input[type="file"]').detach();
  data.isRejected = toType === 'reject' ? 'YES' : 'NO';

  removeDetailRow(row, toType === 'reject' ? 'weight' : 'reject');
  var newRow = addDetailRow(toType, data, fileInput);
  if (toType === 'weight') {
    calculateVariance(newRow);
  }
}

function removeDetailRow(row, type) {
  row.find('.detail-select').select2('destroy');
  row.remove();
  reindexDetailRows(type);
  updateTotals();
}

// Keep posted row indexes contiguous so photos stay matched to their rows
function reindexDetailRows(type) {
  var isReject = type === 'reject';
  var rows = $(isReject ? '#rejectDetailsTable' : '#weightDetailsTable').children('tr');

  rows.each(function (index) {
    $(this).find('input[name], select[name]').each(function () {
      $(this).attr('name', $(this).attr('name').replace(/\[\d+\]/, '[' + index + ']'));
    });
  });

  if (isReject) {
    rejectCount = rows.length;
    renumberRejectRows();
  } else {
    weightCount = rows.length;
  }
}

function renumberRejectRows() {
  $('#rejectDetailsTable').children('tr').each(function (index) {
    $(this).find('.row-no').text(index + 1);
  });
}

function updateTotals() {
  var sum = function (tableId, key) {
    var total = 0;
    $(tableId).children('tr').each(function () {
      total += parseFloat(field($(this), key).val()) || 0;
    });
    return total;
  };

  $('#totalWeightGross').text(sum('#weightDetailsTable', 'gross').toFixed(2));
  $('#totalWeightTare').text(sum('#weightDetailsTable', 'tare').toFixed(2));
  $('#totalWeightNet').text(sum('#weightDetailsTable', 'net').toFixed(2));
  $('#totalWeightVariance').text(sum('#weightDetailsTable', 'variance').toFixed(2));
  $('#totalWeightPrice').text('RM ' + sum('#weightDetailsTable', 'total').toFixed(2));
  $('#totalRejectGross').text(sum('#rejectDetailsTable', 'gross').toFixed(2));
  $('#totalRejectTare').text(sum('#rejectDetailsTable', 'tare').toFixed(2));
  $('#totalRejectNet').text(sum('#rejectDetailsTable', 'net').toFixed(2));
  $('#totalRejectPrice').text('RM ' + sum('#rejectDetailsTable', 'total').toFixed(2));
}

// ============================================================================
// VARIANCE / PRICING
// ============================================================================

// Product or status changed: variance, then the price list price (weight rows)
function refreshProductRow(row) {
  calculateVariance(row);
  if (industrialFlags.showPrice) {
    calculatePrice(row);
  }
}

// Variance against the product's OK weight (only once there is a net weight)
function calculateVariance(row) {
  var product = findProduct(field(row, 'product').val());
  var net = parseFloat(field(row, 'net').val()) || 0;

  if (product && net > 0) {
    var okWeight = parseFloat(product.ok_weight) || 0;
    var variance = Math.abs(net - okWeight);
    field(row, 'variance').val(variance.toFixed(2));
    field(row, 'variancePerc').val((okWeight !== 0 ? variance / okWeight * 100 : 0).toFixed(2));
  }
  updateTotals();
}

// Price list price for the row's product (looked up with the selected customer, as before)
function calculatePrice(row) {
  var productId = field(row, 'product').val();
  var status = $('#status').val();
  if (!productId || !status) {
    return;
  }

  $.post(productsApi, { action: 'getPrice', id: productId, status: status, customerID: $('#customer').val() || '', grade: '' }, function (obj) {
    if (obj.status === 'success') {
      field(row, 'fixedfloat').val(obj.message.pricingType);
      field(row, 'price').val(obj.message.price);
      applyRowPrice(row);
    } else {
      toastr.error(obj.message, 'Failed:');
    }
  }, 'json').fail(function () {
    toastr.error('Something went wrong', 'Failed:');
  });
}

// Total = price x net (Float) or price (Fixed)
function applyRowPrice(row) {
  var price = parseFloat(field(row, 'price').val()) || 0;
  var net = parseFloat(field(row, 'net').val()) || 0;
  field(row, 'total').val((field(row, 'fixedfloat').val() === 'Float' ? price * net : price).toFixed(2));
  updateTotals();
}

// ============================================================================
// PRINT
// ============================================================================

function openPrint(id) {
  $('#printId').val(id);
  $('#printOptionsModal').modal('show');
}

function printEntry() {
  var paperSize = $('#paperSize').val();
  $('#printOptionsModal').modal('hide');

  $.post(industrialApi, $('#printOptionsForm').serialize() + '&action=printSlip', function (obj) {
    if (obj.status === 'success') {
      openPrintPreview(obj.message, paperSize);
    } else {
      toastr.error(obj.message, 'Failed:');
    }
  }, 'json').fail(function () {
    toastr.error('Failed to generate print document', 'Failed:');
  });
}

function openPrintPreview(printHtml, paperSize) {
  var printWindow = window.open('', '', 'height=' + screen.height + ',width=' + screen.width);
  printWindow.document.write(printHtml);
  printWindow.document.close();

  var printAndClose = function () {
    printWindow.print();
    printWindow.close();
  };

  if (paperSize === 'A5') {
    // A5: wait for the QR image to load
    var image = $(printWindow.document).find('.qr-block img');
    if (image.length && !image[0].complete) {
      image.on('load error', printAndClose);
    } else {
      setTimeout(printAndClose, 300);
    }
    return;
  }

  // A4: wait for Paged.js to render
  var pollCount = 0;
  var poll = setInterval(function () {
    pollCount++;
    if ($(printWindow.document).find('.pagedjs_pages').length || pollCount > 60) {
      clearInterval(poll);
      setTimeout(printAndClose, 300);
    }
  }, 200);
}
