// Wholesales list / entry page. Requires `wholesalesPermissions`, `wholesalesFlags`, `wholesalesLookups` and `wholesalesText`.

// 1. Variables
var wholesaleApi = 'php/modules/wholesales/api.php';
var productsApi = 'php/modules/products/api.php';
var wholesaleTable;
var weightCount = 0;
var rejectCount = 0;
var netFromCalc = false; // true while net is written from gross - tare (not typed by the user)
var currentProductType = 'Local';
var productsDataByType = [];
var printIds = [];
// Reject rows added directly use this product (kept as before)
var rejectProductId = '35';
var rejectProductName = 'REJECT (拒收)';

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
      placeholder: wholesalesText.pleaseSelect,
      dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-content') : $(this).parent()
    });
  });

  buildColumnToggleMenu();

  wholesaleTable = $('#weightTable').DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: true,
    serverMethod: 'post',
    searching: true,
    order: [[0, 'asc']],
    language: {
      emptyTable: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title">' + escapeHtml(wholesalesText.noRecordsFound) + '</div><div class="empty-message">' + escapeHtml(wholesalesText.noRecordsMessage) + '</div></div>',
      zeroRecords: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title">' + escapeHtml(wholesalesText.noMatchingRecords) + '</div><div class="empty-message">' + escapeHtml(wholesalesText.noMatchingMessage) + '</div></div>'
    },
    ajax: {
      url: wholesaleApi,
      data: function (d) {
        return $.extend(d, filterParams(), { action: 'list' });
      }
    },
    columns: tableColumns()
  });

  $('#filterSearch').on('click', function () {
    wholesaleTable.ajax.reload();
  });

  $('#selectAllRows').on('change', function () {
    $('#weightTable tbody .rowCheckbox').prop('checked', $(this).prop('checked'));
  });

  // Expand / collapse row details on row click
  $('#weightTable tbody').on('click', 'tr', function (e) {
    if ($(e.target).closest('td').hasClass('select-checkbox') || $(e.target).closest('td').hasClass('action-button') ||
        $(e.target).closest('button, select, input, a').length) {
      return;
    }

    var tr = $(this);
    var row = wholesaleTable.row(tr);
    if (!row.data()) {
      return;
    }

    if (row.child.isShown()) {
      row.child.hide();
      tr.removeClass('shown');
      return;
    }

    $.post(wholesaleApi, { action: 'get', id: row.data().id }, function (obj) {
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
    var isSupplier = isSupplierStatus($(this).val());
    $('#customerStatusDiv').toggle(!isSupplier);
    $('#supplierStatusDiv').toggle(isSupplier);
  });

  $('#vehicleNoFilter').on('change', function () {
    $('#otherVehicleFilterDiv').toggle(isOtherOption($(this).val()));
  });

  $('#addEntry').on('click', function () {
    newEntry();
  });

  $('#printSelected').on('click', function () {
    printSelected();
  });

  $('#exportInvoices').on('click', function () {
    exportInvoices();
  });

  $('#paperSize').on('change', function () {
    $('#withDetailsDiv').toggle($(this).val() === 'A5');
    $('#a4TemplateDiv').toggle($(this).val() === 'A4');
  });

  // Entry form header
  $('#status').on('change', function () {
    var isSupplier = isSupplierStatus($(this).val());
    $('#customerDiv').toggle(!isSupplier);
    $('#supplierDiv').toggle(isSupplier);
    $('#securityBillDiv').toggle(isSupplier);
    $('#customerOtherDiv').toggle(!isSupplier && $('#customer').val() === 'OTHERS');
    $('#supplierOtherDiv').toggle(isSupplier && $('#supplier').val() === 'OTHERS');
    priceWeightRows();
  });

  $('#productType').on('change', function () {
    var newType = $(this).val();
    if (newType === currentProductType) {
      return;
    }

    currentProductType = newType;
    clearDetailRows();
    loadProductsByType(newType);
  });

  $('#category').on('change', function () {
    $('#weightDetailsTable tr').each(function () {
      var select = field($(this), 'product');
      var current = select.val();
      select.html(productOptions(current, field($(this), 'product_name').val()));
      setSelectValue(select, current);
    });
  });

  $('#customer, #supplier').on('change', function () {
    var isCustomer = this.id === 'customer';
    $(isCustomer ? '#customerOtherDiv' : '#supplierOtherDiv').toggle($(this).val() === 'OTHERS' && isSupplierStatus($('#status').val()) !== isCustomer);
    applyPartyCurrency();
  });

  $('#vehicle').on('change', function () {
    $('#vehicleNoOtherDiv').toggle(isOtherOption($(this).val()));
  });

  $('#driver').on('change', function () {
    $('#driverOtherDiv').toggle(isOtherOption($(this).val()));
  });

  $('#addWeightBtn').on('click', function () {
    addDetailRow('weight', null, null);
  });

  $('#addRejectWeightBtn').on('click', function () {
    addDetailRow('reject', null, null);
  });

  // Weight rows
  $('#weightDetailsTable').on('change', 'select[name$="[product]"]', function () {
    var row = $(this).closest('tr');
    var product = findProduct($(this).val());
    var productName = product ? product.product_name : '';
    field(row, 'product_name').val(productName);
    field(row, 'product_desc').val(productName);

    var gradeSelect = field(row, 'grade_id');
    gradeSelect.html(gradeOptions($(this).val(), '', ''));
    setSelectValue(gradeSelect, '');
    field(row, 'grade').val('');
  });

  $('#weightDetailsTable').on('change', 'select[name$="[grade_id]"]', function () {
    var row = $(this).closest('tr');
    field(row, 'grade').val($(this).val() ? $(this).find('option:selected').text() : '');
    if (wholesalesFlags.showPrice) {
      calculatePrice(row, 'replace');
    }
  });

  $('#weightDetailsTable').on('change', 'select[name$="[currency]"]', function () {
    if (wholesalesFlags.showPrice) {
      calculatePrice($(this).closest('tr'), 'replace');
    }
  });

  $('#weightDetailsTable, #rejectDetailsTable').on('change', 'input[name$="[gross]"], input[name$="[tare]"]', function () {
    var row = $(this).closest('tr');
    var net = (parseFloat(field(row, 'gross').val()) || 0) - (parseFloat(field(row, 'tare').val()) || 0);
    if (net < 0) {
      toastr.warning(wholesalesText.negativeNet, 'Warning:');
    }

    netFromCalc = true;
    field(row, 'net').val(net.toFixed(2)).trigger('change');
    netFromCalc = false;
  });

  $('#weightDetailsTable, #rejectDetailsTable').on('change', 'input[name$="[net]"]', function () {
    var row = $(this).closest('tr');

    // Net typed by the user: gross follows net + tare
    if (!netFromCalc) {
      var gross = (parseFloat($(this).val()) || 0) + (parseFloat(field(row, 'tare').val()) || 0);
      if (isNaN(gross) || gross < 0) {
        toastr.warning(wholesalesText.negativeGross, 'Warning:');
        gross = 0;
      }
      field(row, 'gross').val(gross.toFixed(2));
    }

    recalculateRow(row);
  });

  $('#weightDetailsTable').on('change', 'input[name$="[price]"]', function () {
    recalculateRow($(this).closest('tr'));
  });

  $('#weightDetailsTable, #rejectDetailsTable').on('change', 'input[name$="[discount]"], select[name$="[discount_type]"]', function () {
    applyRowPrice($(this).closest('tr'));
  });

  $('#weightDetailsTable').on('change input', 'input[name$="[no_basket]"]', function () {
    updateTotals();
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
      recalculateRow(row);
    });
  });

  $('#selectAllWeightCheckbox').on('change', function () {
    $('#weightDetailsTable .weight-check').prop('checked', $(this).prop('checked'));
  });

  // Basket tare: average empty basket weight applied to every tare
  $('#emptyBasketWeight, #basketCount').on('input change', function () {
    var emptyWeight = parseFloat($('#emptyBasketWeight').val()) || 0;
    var count = parseInt($('#basketCount').val()) || 0;
    $('#avgBasketWeight').val((count > 0 ? emptyWeight / count : 0).toFixed(2));
  });

  $('#applyTareBtn').on('click', function () {
    var avgWeight = parseFloat($('#avgBasketWeight').val()) || 0;
    if (avgWeight <= 0) {
      toastr.warning(wholesalesText.calculateAverageFirst, 'Warning:');
      return;
    }
    if (!confirm(wholesalesText.applyTareConfirm)) {
      return;
    }
    $('#weightDetailsTable tr').each(function () {
      field($(this), 'tare').val(avgWeight.toFixed(2)).trigger('change');
    });
    toastr.success(wholesalesText.tareApplied, 'Success:');
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

function isSupplierStatus(status) {
  return status === 'RECEIVING' || status === 'INCOMING';
}

function isOtherOption(value) {
  return value === 'OTHERS' || value === 'UNKNOWN' || value === 'UNKOWN NO';
}

// Named input / select of a detail row, e.g. field(row, 'net') => [name$="[net]"]
function field(row, key) {
  return row.find('[name$="[' + key + ']"]');
}

function setSelectValue(select, value) {
  select.val(value);
  if (select.is('select')) {
    select.trigger('change.select2');
  }
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

// Entry form also validates the (hidden) Select2 vehicle / driver selects
function entryValidationOptions() {
  return {
    errorElement: 'span',
    ignore: [],
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback').css('display', 'block');
      if (element.next('.select2-container').length) {
        error.insertAfter(element.next('.select2-container'));
      } else {
        element.closest('.form-group-modern, td').append(error);
      }
    },
    highlight: function (element) {
      $(element).addClass('is-invalid');
      $(element).next('.select2-container').find('.select2-selection').css('border-color', '#dc3545');
    },
    unhighlight: function (element) {
      $(element).removeClass('is-invalid');
      $(element).next('.select2-container').find('.select2-selection').css('border-color', '');
    },
    submitHandler: function () { saveEntry(); }
  };
}

function filterParams() {
  return {
    fromDate: $('#fromDate').val(),
    toDate: $('#toDate').val(),
    transactionStatus: $('#transactionStatusFilter').val(),
    status: 'active',
    category: $('#categoryFilter').val() || '',
    customer: $('#customerNoFilter').val() || '',
    supplier: $('#supplierNoFilter').val() || '',
    vehicle: $('#vehicleNoFilter').val() || '',
    otherVehicle: $('#otherVehicleNoFilter').val() || '',
    checkedBy: $('#checkedByFilter').val() || '',
    weightedBy: $('#weightByFilter').val() || '',
    location: $('#locationFilter').val() || '',
    partyType: $('#partyTypeFilter').val() || '',
    indicator: $('#indicatorFilter').val() || ''
  };
}

// ============================================================================
// LIST
// ============================================================================

function tableColumns() {
  var columns = [{
    data: 'id',
    orderable: false,
    className: 'select-checkbox',
    render: function (data) {
      return '<input type="checkbox" class="rowCheckbox" value="' + data + '">';
    }
  }];

  $.each(wholesalesLookups.columns, function (i, column) {
    var definition = { data: column.data, visible: column.visible };
    // total_price holds per-currency lines joined with <br>
    if (column.data !== 'total_price') {
      definition.render = $.fn.dataTable.render.text();
    }
    columns.push(definition);
  });

  columns.push({
    data: 'id',
    responsivePriority: 1,
    orderable: false,
    className: 'action-button',
    render: function (data, type, row) {
      return '<div class="d-flex" style="gap:4px;">' + actionButtons(row, false) + '</div>';
    }
  });

  return columns;
}

function actionButtons(row, compact) {
  var id = parseInt(row.id);
  var buttons = '';
  if (wholesalesPermissions.allowEdit) {
    buttons += '<button type="button" onclick="edit(' + id + ')" class="btn btn-sm btn-outline-primary" title="' + escapeHtml(wholesalesText.edit) + '"><i class="fas fa-pen"></i></button>';
  }
  buttons += '<button type="button" onclick="openPrint(' + id + ')" class="btn btn-sm btn-outline-secondary" title="' + escapeHtml(wholesalesText.print) + '"><i class="fas fa-print"></i></button>';
  if (!compact) {
    buttons += '<button type="button" onclick="exportExcel(' + id + ')" class="btn btn-sm btn-outline-success" title="' + escapeHtml(wholesalesText.exportExcel) + '"><i class="fas fa-file-excel"></i></button>';
  }
  if (wholesalesFlags.invoice && wholesalesPermissions.allowPrice && (row.status === 'DISPATCH' || row.status === 'RECEIVING')) {
    buttons += '<button type="button" onclick="printInvoice(' + id + ')" class="btn btn-sm btn-outline-info" title="' + escapeHtml(wholesalesText.invoice) + '"><i class="fas fa-file-invoice"></i></button>';
  }
  if (wholesalesPermissions.allowDelete) {
    buttons += '<button type="button" onclick="deactivate(' + id + ')" class="btn btn-sm btn-outline-danger" title="' + escapeHtml(wholesalesText.delete) + '"><i class="fas fa-trash"></i></button>';
  }
  return buttons;
}

function buildColumnToggleMenu() {
  var menu = $('#columnToggleMenu');
  menu.empty();

  $.each(wholesalesLookups.columns, function (i, column) {
    var item = $('<div class="form-check"><input class="form-check-input column-toggle" type="checkbox"><label class="form-check-label"></label></div>');
    item.find('input').attr('data-index', i + 1).prop('checked', column.visible);
    item.find('label').text(column.label);
    menu.append(item);
  });

  menu.on('click', function (e) { e.stopPropagation(); });
  menu.on('change', '.column-toggle', function () {
    wholesaleTable.column(parseInt($(this).attr('data-index'))).visible($(this).is(':checked'));
  });
}

function formatExpandedRow(row) {
  var showPrice = wholesalesFlags.showPrice;
  var t = wholesalesText;
  var info = function (label, value) {
    return '<div><span class="info-item-label">' + escapeHtml(label) + '</span><span class="info-item-value">' + escapeHtml(value || '-') + '</span></div>';
  };
  var kg = function (value) {
    return value ? fixed(value) + ' kg' : '-';
  };
  var priceHeaders = '<th>' + escapeHtml(t.currency) + '</th><th class="text-right">' + escapeHtml(t.price) + '</th><th class="text-right">' + escapeHtml(t.beforeDisc) + '</th><th class="text-right">' + escapeHtml(t.discount) + '</th><th class="text-right">' + escapeHtml(t.total) + '</th>';
  var photoCell = function (detail) {
    return wholesalesFlags.photo
      ? '<td class="text-center">' + (detail.photoPath ? '<a href="' + photoUrl(detail.photoPath) + '" target="_blank" class="btn btn-outline-secondary btn-sm btn-photo"><i class="fas fa-image"></i></a>' : '-') + '</td>'
      : '';
  };
  var discountText = function (detail) {
    var value = parseFloat(detail.discount) || 0;
    return detail.discount_type === 'percent' ? value.toFixed(2) + '%' : value.toFixed(2);
  };
  var discountAmount = function (detail) {
    var value = parseFloat(detail.discount) || 0;
    return detail.discount_type === 'percent' ? (parseFloat(detail.before_discount) || 0) * value / 100 : value;
  };

  var html = '<div class="expanded-row-content">' +
    '<div class="expanded-header"><div>' +
      '<div class="expanded-header-title">' + escapeHtml(row.serial_no) + '</div>' +
      '<div class="expanded-header-subtitle">' + escapeHtml(row.customer_supplier || '-') + '</div>' +
    '</div><div class="expanded-actions">' + actionButtons(row, true) + '</div></div>' +
    '<div class="kpi-row">' +
      '<div class="kpi-card"><div class="kpi-label">' + escapeHtml(t.totalItem) + '</div><div class="kpi-value">' + (row.totalItems || 0) + '</div></div>' +
      '<div class="kpi-card"><div class="kpi-label">' + escapeHtml(t.totalWeight) + '</div><div class="kpi-value kpi-value-primary">' + fixed(row.totalWeight) + ' <span class="kpi-unit">Kg</span></div></div>' +
      '<div class="kpi-card"><div class="kpi-label">' + escapeHtml(t.totalReject) + '</div><div class="kpi-value kpi-value-danger">' + fixed(row.totalReject) + ' <span class="kpi-unit">Kg</span></div></div>' +
      (showPrice ? '<div class="kpi-card kpi-card-success"><div class="kpi-label">' + escapeHtml(t.totalPrice) + '</div><div class="kpi-value">' + fixed(row.totalPrice) + '</div></div>' : '') +
    '</div>' +
    '<div class="info-section"><div class="info-section-title">' + escapeHtml(t.orderInformation) + '</div><div class="info-grid">' +
      info(t.serialNo, row.serial_no) + info(t.doPoNo, row.po_no) + info(t.vehicleNo, row.vehicle_no) +
      info(t.driver, row.driver) + info(t.weighedBy, row.weighted_by) + info(t.location, row.location_name) +
    '</div>' +
    (row.remark ? '<div class="info-remark"><span class="info-item-label">' + escapeHtml(t.remark) + '</span><span class="info-item-value">' + escapeHtml(row.remark) + '</span></div>' : '') +
    '</div>';

  if (wholesalesFlags.basketTare) {
    html += '<div class="info-section"><div class="info-section-title"><i class="fas fa-shopping-basket mr-1"></i>' + escapeHtml(t.basketTare) + '</div><div class="info-grid">' +
      info(t.emptyBasketsWeight, kg(row.empty_baskets_weight)) + info(t.basketCount, row.basket_count) + info(t.averageWeight, kg(row.avg_basket_weight)) +
      '</div></div>';
  }

  // Weighing details (rows carry their values for the product / grade filters)
  var totals = { gross: 0, tare: 0, net: 0, basket: 0, before: 0, discount: 0, total: 0 };
  html += '<div class="details-section"><div class="details-header">' +
    '<span class="details-title">' + escapeHtml(t.weighingDetails) + '</span>' +
    '<div class="details-filters">' +
      '<button type="button" class="btn btn-sm btn-outline-success" onclick="exportExcel(' + parseInt(row.id) + ')" title="' + escapeHtml(t.exportExcel) + '"><i class="fas fa-file-excel"></i></button>' +
      '<select class="form-control form-control-sm details-filter-select" id="productFilter_' + row.id + '" onchange="filterDetailTable(' + parseInt(row.id) + ')"><option value="">' + escapeHtml(t.allProducts) + '</option></select>' +
      '<select class="form-control form-control-sm details-filter-select" id="gradeFilter_' + row.id + '" onchange="filterDetailTable(' + parseInt(row.id) + ')"><option value="">' + escapeHtml(t.allGrades) + '</option></select>' +
    '</div></div>' +
    '<div class="table-responsive"><table class="table details-table mb-0" id="weightTable_' + row.id + '"><thead><tr>' +
      '<th>' + escapeHtml(t.product) + '</th><th>' + escapeHtml(t.grade) + '</th>' +
      '<th class="text-right">' + escapeHtml(t.gross) + '</th><th class="text-right">' + escapeHtml(t.tare) + '</th><th class="text-right">' + escapeHtml(t.net) + '</th>' +
      (wholesalesFlags.pcsBasket ? '<th class="text-right">' + escapeHtml(t.pcsBasket) + '</th>' : '') +
      (showPrice ? priceHeaders : '') +
      '<th class="text-center">' + escapeHtml(t.time) + '</th>' +
      (wholesalesFlags.photo ? '<th class="text-center">' + escapeHtml(t.photo) + '</th>' : '') +
    '</tr></thead><tbody>';

  $.each(row.weightDetails, function (i, detail) {
    var discount = discountAmount(detail);
    var tr = $('<tr>').attr({
      'data-product': detail.product_name, 'data-grade': detail.grade,
      'data-gross': detail.gross, 'data-tare': detail.tare, 'data-net': detail.net,
      'data-before': detail.before_discount || 0, 'data-discount': discount, 'data-total': detail.total || 0
    });
    tr.html(
      '<td>' + escapeHtml(detail.product_name) + '</td>' +
      '<td><span class="grade-badge">' + escapeHtml(detail.grade) + '</span></td>' +
      '<td class="text-right text-mono">' + fixed(detail.gross) + '</td>' +
      '<td class="text-right text-mono">' + fixed(detail.tare) + '</td>' +
      '<td class="text-right text-mono text-primary font-weight-bold">' + fixed(detail.net) + '</td>' +
      (wholesalesFlags.pcsBasket ? '<td class="text-right text-mono">' + (parseInt(detail.no_per_basket) || 0) + '</td>' : '') +
      (showPrice ? '<td>' + escapeHtml(detail.currency_name) + '</td><td class="text-right text-mono">' + fixed(detail.price) + '</td><td class="text-right text-mono">' + fixed(detail.before_discount) + '</td><td class="text-right text-mono">' + discountText(detail) + '</td><td class="text-right text-mono text-success font-weight-bold">' + fixed(detail.total) + '</td>' : '') +
      '<td class="text-center text-muted">' + escapeHtml(detail.time) + '</td>' +
      photoCell(detail)
    );
    html += tr.prop('outerHTML');

    totals.gross += parseFloat(detail.gross) || 0;
    totals.tare += parseFloat(detail.tare) || 0;
    totals.net += parseFloat(detail.net) || 0;
    totals.basket += parseInt(detail.no_per_basket) || 0;
    totals.before += parseFloat(detail.before_discount) || 0;
    totals.discount += discount;
    totals.total += parseFloat(detail.total) || 0;
  });

  html += '</tbody><tfoot><tr><td colspan="2">' + escapeHtml(t.total) + '</td>' +
    '<td class="text-right text-mono" id="footGross_' + row.id + '">' + totals.gross.toFixed(2) + '</td>' +
    '<td class="text-right text-mono" id="footTare_' + row.id + '">' + totals.tare.toFixed(2) + '</td>' +
    '<td class="text-right text-mono text-primary" id="footNet_' + row.id + '">' + totals.net.toFixed(2) + '</td>' +
    (wholesalesFlags.pcsBasket ? '<td class="text-right text-mono">' + totals.basket + '</td>' : '') +
    (showPrice ? '<td></td><td></td><td class="text-right text-mono" id="footBeforeDiscount_' + row.id + '">' + totals.before.toFixed(2) + '</td><td class="text-right text-mono text-danger" id="footDiscount_' + row.id + '">' + totals.discount.toFixed(2) + '</td><td class="text-right text-mono text-success" id="footPrice_' + row.id + '">' + totals.total.toFixed(2) + '</td>' : '') +
    '<td></td>' + (wholesalesFlags.photo ? '<td></td>' : '') +
    '</tr></tfoot></table></div></div>';

  // Reject details
  var rejectColspan = 6 + (showPrice ? 5 : 0) + (wholesalesFlags.photo ? 1 : 0);
  var rejectTotals = { gross: 0, tare: 0, net: 0, before: 0, total: 0 };
  html += '<div class="details-section"><div class="details-header">' +
    '<span class="details-title details-title-danger"><i class="fas fa-times-circle mr-1"></i>' + escapeHtml(t.rejectDetails) + '</span></div>' +
    '<div class="table-responsive"><table class="table details-table mb-0"><thead><tr>' +
      '<th>' + escapeHtml(t.product) + '</th><th>' + escapeHtml(t.grade) + '</th>' +
      '<th class="text-right">' + escapeHtml(t.gross) + '</th><th class="text-right">' + escapeHtml(t.tare) + '</th><th class="text-right">' + escapeHtml(t.net) + '</th>' +
      (showPrice ? priceHeaders : '') +
      '<th class="text-center">' + escapeHtml(t.time) + '</th>' +
      (wholesalesFlags.photo ? '<th class="text-center">' + escapeHtml(t.photo) + '</th>' : '') +
    '</tr></thead><tbody>';

  if (!row.rejectDetails.length) {
    html += '<tr><td colspan="' + rejectColspan + '" class="details-empty"><i class="fas fa-check-circle"></i> ' + escapeHtml(t.noRejectItems) + '</td></tr>';
  }

  $.each(row.rejectDetails, function (i, detail) {
    html += '<tr>' +
      '<td>' + escapeHtml(detail.product_name) + '</td>' +
      '<td><span class="grade-badge grade-badge-danger">' + escapeHtml(detail.grade) + '</span></td>' +
      '<td class="text-right text-mono">' + fixed(detail.gross) + '</td>' +
      '<td class="text-right text-mono">' + fixed(detail.tare) + '</td>' +
      '<td class="text-right text-mono text-danger font-weight-bold">' + fixed(detail.net) + '</td>' +
      (showPrice ? '<td>' + escapeHtml(detail.currency_name) + '</td><td class="text-right text-mono">' + fixed(detail.price) + '</td><td class="text-right text-mono">' + fixed(detail.before_discount) + '</td><td class="text-right text-mono">' + discountText(detail) + '</td><td class="text-right text-mono text-danger font-weight-bold">' + fixed(detail.total) + '</td>' : '') +
      '<td class="text-center text-muted">' + escapeHtml(detail.time) + '</td>' +
      photoCell(detail) +
      '</tr>';

    rejectTotals.gross += parseFloat(detail.gross) || 0;
    rejectTotals.tare += parseFloat(detail.tare) || 0;
    rejectTotals.net += parseFloat(detail.net) || 0;
    rejectTotals.before += parseFloat(detail.before_discount) || 0;
    rejectTotals.total += parseFloat(detail.total) || 0;
  });

  html += '</tbody>';
  if (row.rejectDetails.length) {
    html += '<tfoot><tr><td colspan="2">' + escapeHtml(t.total) + '</td>' +
      '<td class="text-right text-mono">' + rejectTotals.gross.toFixed(2) + '</td>' +
      '<td class="text-right text-mono">' + rejectTotals.tare.toFixed(2) + '</td>' +
      '<td class="text-right text-mono text-danger">' + rejectTotals.net.toFixed(2) + '</td>' +
      (showPrice ? '<td></td><td></td><td class="text-right text-mono">' + rejectTotals.before.toFixed(2) + '</td><td></td><td class="text-right text-mono text-danger">' + rejectTotals.total.toFixed(2) + '</td>' : '') +
      '<td></td>' + (wholesalesFlags.photo ? '<td></td>' : '') +
      '</tr></tfoot>';
  }
  html += '</table></div></div></div>';

  return html;
}

function populateDetailFilters(rowId, weightDetails) {
  var products = [];
  var grades = [];
  $.each(weightDetails, function (i, detail) {
    if (products.indexOf(detail.product_name) === -1) {
      products.push(detail.product_name);
    }
    if (grades.indexOf(detail.grade) === -1) {
      grades.push(detail.grade);
    }
  });

  var productSelect = $('#productFilter_' + rowId);
  $.each(products, function (i, product) {
    productSelect.append($('<option>').val(product).text(product));
  });

  var gradeSelect = $('#gradeFilter_' + rowId);
  $.each(grades.sort(), function (i, grade) {
    gradeSelect.append($('<option>').val(grade).text(grade));
  });
}

// Product / grade filter inside an expanded row, with recalculated footer totals
function filterDetailTable(rowId) {
  var productFilter = $('#productFilter_' + rowId).val();
  var gradeSelect = $('#gradeFilter_' + rowId);
  var gradeFilter = gradeSelect.val();
  var totals = { gross: 0, tare: 0, net: 0, before: 0, discount: 0, total: 0 };
  var grades = [];

  $('#weightTable_' + rowId + ' tbody tr').each(function () {
    var tr = $(this);
    var product = String(tr.attr('data-product'));
    var grade = String(tr.attr('data-grade'));
    var show = (!productFilter || product === productFilter) && (!gradeFilter || grade === gradeFilter);
    tr.toggle(show);

    if ((!productFilter || product === productFilter) && grades.indexOf(grade) === -1) {
      grades.push(grade);
    }
    if (show) {
      $.each({ gross: 'data-gross', tare: 'data-tare', net: 'data-net', before: 'data-before', discount: 'data-discount', total: 'data-total' }, function (key, attr) {
        totals[key] += parseFloat(tr.attr(attr)) || 0;
      });
    }
  });

  $('#footGross_' + rowId).text(totals.gross.toFixed(2));
  $('#footTare_' + rowId).text(totals.tare.toFixed(2));
  $('#footNet_' + rowId).text(totals.net.toFixed(2));
  $('#footBeforeDiscount_' + rowId).text(totals.before.toFixed(2));
  $('#footDiscount_' + rowId).text(totals.discount.toFixed(2));
  $('#footPrice_' + rowId).text(totals.total.toFixed(2));

  gradeSelect.find('option:not(:first)').remove();
  $.each(grades.sort(), function (i, grade) {
    gradeSelect.append($('<option>').val(grade).text(grade));
  });
  gradeSelect.val(gradeFilter);
}

// ============================================================================
// ENTRY FORM
// ============================================================================

function resetForm() {
  // Rows first, so the header change handlers below do not re-price old rows
  clearDetailRows();
  $('#extendForm').validate().resetForm();
  $('#extendForm .is-invalid').removeClass('is-invalid');
  $('#id, #serialNo, #doPoNo, #securityBillNo, #customerOther, #supplierOther, #otherVehicleNo, #otherDriver, #remarks, #remarks2, #bulkUnitPrice').val('');
  $('#category, #paymentMethod, #customer, #supplier, #vehicle, #driver').val('').trigger('change');
  $('#productType').val('Local');
  currentProductType = 'Local';
  $('#endTimePicker').datetimepicker('clear');
  $('#emptyBasketWeight, #avgBasketWeight').val('0.00');
  $('#basketCount').val('0');
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
  $('#status').val('DISPATCH').trigger('change');
  $('#location').val(wholesalesLookups.userLocationId || '').trigger('change');
  $('#startTimePicker').datetimepicker('date', moment());

  loadProductsByType('Local', function () {
    $('#extendModal').modal('show');
  });
}

function edit(id) {
  $('#spinnerLoading').show();

  $.post(wholesaleApi, { action: 'get', id: id }, function (obj) {
    if (obj.status !== 'success') {
      toastr.error(obj.message, 'Failed:');
      return;
    }

    var record = obj.message;
    resetForm();
    $('#id').val(record.id);
    $('#serialNo').val(record.serial_no);
    $('#category').val(record.category || '').trigger('change');
    $('#paymentMethod').val(record.payment_method || '').trigger('change');
    $('#status').val(record.status).trigger('change');
    $('#productType').val(record.type || 'Local');
    currentProductType = record.type || 'Local';
    $('#doPoNo').val(record.po_no || '');
    $('#securityBillNo').val(record.security_bills || '');
    $('#customer').val(record.customer || '').trigger('change');
    $('#customerOther').val(record.other_customer || '');
    $('#supplier').val(record.supplier || '').trigger('change');
    $('#supplierOther').val(record.other_supplier || '');
    $('#location').val(record.location || '').trigger('change');
    $('#remarks').val(record.remark || '');
    $('#remarks2').val(record.remarks2 || '');
    $('#emptyBasketWeight').val(record.empty_baskets_weight || '0.00');
    $('#basketCount').val(record.basket_count || '0');
    $('#avgBasketWeight').val(record.avg_basket_weight || '0.00');

    if (record.other_vehicle && record.vehicle_no) {
      $('#vehicle').val('OTHERS').trigger('change');
      $('#otherVehicleNo').val(record.vehicle_no);
    } else {
      $('#vehicle').val(record.vehicle_no || '').trigger('change');
    }

    if (record.driver && wholesalesLookups.drivers.indexOf(record.driver) === -1) {
      $('#driver').val('OTHERS').trigger('change');
      $('#otherDriver').val(record.driver);
    } else {
      $('#driver').val(record.driver || '').trigger('change');
    }

    if (record.start_time) {
      $('#startTimePicker').datetimepicker('date', moment(record.start_time, 'YYYY-MM-DD HH:mm:ss'));
    } else {
      $('#startTimePicker').datetimepicker('clear');
    }
    if (record.end_time) {
      $('#endTimePicker').datetimepicker('date', moment(record.end_time, 'YYYY-MM-DD HH:mm:ss'));
    }

    loadProductsByType(currentProductType, function () {
      $.each(record.weightDetails, function (i, detail) {
        addDetailRow('weight', $.extend({}, detail, { no_basket: detail.no_per_basket }), null);
      });
      $.each(record.rejectDetails, function (i, detail) {
        addDetailRow('reject', detail, null);
      });
      updateTotals();
      $('#extendModal').modal('show');
    });
  }, 'json').fail(function () {
    toastr.error('Something wrong when pull data', 'Failed:');
  }).always(function () {
    $('#spinnerLoading').hide();
  });
}

function saveEntry() {
  if ($('#vehicle').val() === 'OTHERS' && !$.trim($('#otherVehicleNo').val())) {
    toastr.error(wholesalesText.enterVehicleNo, 'Validation Error:');
    return;
  }
  if ($('#driver').val() === 'OTHERS' && !$.trim($('#otherDriver').val())) {
    toastr.error(wholesalesText.enterDriver, 'Validation Error:');
    return;
  }

  var weightRowError = false;
  var netError = false;
  $('#weightDetailsTable tr').each(function () {
    var row = $(this);
    if (!field(row, 'product').val() || !field(row, 'grade_id').val() || !(parseFloat(field(row, 'gross').val()) > 0)) {
      weightRowError = true;
      return false;
    }
    if (parseFloat(field(row, 'net').val()) < 0) {
      netError = true;
      return false;
    }
  });
  $('#rejectDetailsTable tr').each(function () {
    if (parseFloat(field($(this), 'net').val()) < 0) {
      netError = true;
      return false;
    }
  });

  if (weightRowError) {
    toastr.error(wholesalesText.weightRowError, 'Validation Error:');
    return;
  }
  if (netError) {
    toastr.error(wholesalesText.negativeNetError, 'Validation Error:');
    return;
  }

  var formData = new FormData($('#extendForm')[0]);
  formData.append('action', 'save');

  $('#saveButton').prop('disabled', true);
  $('#spinnerLoading').show();

  $.ajax({
    url: wholesaleApi,
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'json'
  }).done(function (obj) {
    if (obj.status === 'success') {
      $('#extendModal').modal('hide');
      toastr.success(obj.message, 'Success:');
      wholesaleTable.ajax.reload(null, false);
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
  if (!confirm(wholesalesText.confirmDelete)) {
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

  $.post(wholesaleApi, { action: 'cancel', id: $('#cancelId').val(), cancelReason: $('#cancelReason').val() }, function (obj) {
    if (obj.status === 'success') {
      $('#cancelModal').modal('hide');
      toastr.success(obj.message, 'Success:');
      wholesaleTable.ajax.reload(null, false);
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
// PRODUCTS / DETAIL ROWS
// ============================================================================

function loadProductsByType(type, callback) {
  $.post(productsApi, { action: 'getProductsByType', type: type || currentProductType }, function (obj) {
    if (obj.status === 'success') {
      productsDataByType = obj.data;
      if (callback) {
        callback();
      }
    } else {
      toastr.error(obj.message || 'Failed to load products', 'Error:');
    }
  }, 'json').fail(function () {
    toastr.error('Failed to load products', 'Error:');
  });
}

function findProduct(productId) {
  var found = null;
  $.each(productsDataByType, function (i, product) {
    if (product.id == productId) {
      found = product;
      return false;
    }
  });
  return found;
}

// <option>s for products of the chosen category (a saved product outside the list is kept)
function productOptions(selected, selectedName) {
  var category = $('#category').val();
  var html = '<option value="">' + escapeHtml(wholesalesText.selectProduct) + '</option>';
  var found = false;

  $.each(productsDataByType, function (i, product) {
    if (category && product.category != category && product.id != selected) {
      return;
    }
    found = found || product.id == selected;
    html += '<option value="' + product.id + '">' + escapeHtml(product.product_name) + '</option>';
  });

  if (selected && !found) {
    html += '<option value="' + escapeHtml(selected) + '">' + escapeHtml(selectedName || selected) + '</option>';
  }

  return html;
}

// <option>s for the product's grades (a saved grade outside the list is kept)
function gradeOptions(productId, selected, selectedName) {
  var html = '<option value="">' + escapeHtml(wholesalesText.selectGrade) + '</option>';
  var product = findProduct(productId);
  var found = false;

  if (product && product.grades) {
    $.each(product.grades, function (i, grade) {
      found = found || grade.grade_id == selected;
      html += '<option value="' + grade.grade_id + '">' + escapeHtml(grade.grade_name) + '</option>';
    });
  }

  if (selected && !found) {
    html += '<option value="' + escapeHtml(selected) + '">' + escapeHtml(selectedName || selected) + '</option>';
  }

  return html;
}

function currencyOptions() {
  var html = '<option value="">' + escapeHtml(wholesalesText.selectCurrency) + '</option>';
  $.each(wholesalesLookups.currencies, function (i, currency) {
    html += '<option value="' + currency.id + '">' + escapeHtml(currency.currency) + '</option>';
  });
  return html;
}

function rowDefaults(isReject) {
  return {
    product: isReject ? rejectProductId : '',
    product_name: isReject ? rejectProductName : '',
    product_desc: isReject ? '1REJ' : '',
    grade: isReject ? 'REJ' : '',
    grade_id: '',
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
    no_basket: 0,
    currency: '',
    price: 0,
    before_discount: 0,
    discount_type: 'fixed',
    discount: 0,
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
  var hiddenKeys = ['product_name', 'product_desc', 'pretare', 'unit', 'package', 'fixedfloat', 'isedit', 'reject', 'isRejected', 'grade', 'photoPath'];
  if (isReject) {
    hiddenKeys.push('product', 'grade_id');
  }
  if (isReject || !wholesalesFlags.pcsBasket) {
    hiddenKeys.push('no_basket');
  }
  if (!wholesalesFlags.showPrice) {
    hiddenKeys.push('currency', 'price', 'before_discount', 'discount_type', 'discount', 'total');
  }

  var cells = isReject
    ? '<td class="text-center row-no"></td>'
    : '<td class="text-center"><input type="checkbox" class="weight-check"></td>';
  cells += '<td style="display:none">' + $.map(hiddenKeys, hidden).join('') + '</td>';

  if (isReject) {
    cells += '<td>' + escapeHtml(d.product_name) + '</td><td>' + escapeHtml(d.grade) + '</td>';
  } else {
    cells += '<td><select class="form-control detail-select" name="' + name('product') + '">' + productOptions(d.product, d.product_name) + '</select></td>' +
      '<td><select class="form-control detail-select" name="' + name('grade_id') + '">' + gradeOptions(d.product, d.grade_id, d.grade) + '</select></td>';
  }

  cells += '<td>' + number('gross') + '</td><td>' + number('tare') + '</td><td>' + number('net', isReject ? ' readonly' : '') + '</td>';

  if (!isReject && wholesalesFlags.pcsBasket) {
    cells += '<td><input type="number" class="form-control" name="' + name('no_basket') + '" step="1" min="0" value="' + (parseInt(d.no_basket) || 0) + '"></td>';
  }

  if (wholesalesFlags.showPrice) {
    cells += '<td><select class="form-control detail-select" name="' + name('currency') + '">' + currencyOptions() + '</select></td>' +
      '<td>' + number('price', isReject ? ' readonly' : '') + '</td>' +
      '<td>' + number('before_discount', ' readonly') + '</td>' +
      '<td><div class="input-group input-group-sm">' +
        '<select class="form-control" name="' + name('discount_type') + '" style="max-width:60px;"><option value="fixed">Fix</option><option value="percent">%</option></select>' +
        number('discount') +
      '</div></td>' +
      '<td>' + number('total', ' readonly') + '</td>';
  }

  cells += '<td><input type="time" class="form-control" name="' + name('time') + '" value="' + escapeHtml(d.time) + '"></td>';

  if (wholesalesFlags.photo) {
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

  if (!isReject) {
    field(row, 'product').val(d.product || '');
    field(row, 'grade_id').val(d.grade_id || '');
  }
  if (wholesalesFlags.showPrice) {
    field(row, 'discount_type').val(d.discount_type === 'percent' ? 'percent' : 'fixed');
  }
  field(row, 'currency').val(d.currency || partyCurrency() || '');

  if (fileInput && fileInput.length) {
    row.find('input[type="file"]').replaceWith(fileInput.attr('name', fileName));
    var files = fileInput.prop('files');
    if (files && files.length) {
      row.find('.photo-status').html('<i class="fas fa-check-circle text-success"></i>');
    }
  }

  row.find('.detail-select').select2({
    allowClear: true,
    placeholder: wholesalesText.pleaseSelect,
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

  if (toType === 'reject') {
    data.isRejected = 'YES';
    data.product_name = rejectProductName;
    data.product_desc = 'REJ';
  } else {
    data.isRejected = 'NO';
    var product = data.product === rejectProductId ? null : findProduct(data.product);
    if (product) {
      data.product_name = product.product_name;
      data.product_desc = product.product_name;
    } else if (data.product === rejectProductId) {
      data.product = '';
      data.product_name = '';
      data.product_desc = '';
      data.grade = '';
      data.grade_id = '';
    }
  }

  removeDetailRow(row, toType === 'reject' ? 'weight' : 'reject');
  addDetailRow(toType, data, fileInput);
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
  sumDetailRows('#weightDetailsTable', 'Weight');
  sumDetailRows('#rejectDetailsTable', 'Reject');
}

function sumDetailRows(tableId, suffix) {
  var totals = { gross: 0, tare: 0, net: 0, basket: 0, before: 0, discount: 0, total: 0 };

  $(tableId).children('tr').each(function () {
    var row = $(this);
    var value = function (key) {
      return parseFloat(field(row, key).val()) || 0;
    };
    var before = value('before_discount');

    totals.gross += value('gross');
    totals.tare += value('tare');
    totals.net += value('net');
    totals.basket += parseInt(field(row, 'no_basket').val()) || 0;
    totals.before += before;
    totals.discount += field(row, 'discount_type').val() === 'percent' ? before * value('discount') / 100 : value('discount');
    totals.total += value('total');
  });

  $('#total' + suffix + 'Gross').text(totals.gross.toFixed(2));
  $('#total' + suffix + 'Tare').text(totals.tare.toFixed(2));
  $('#total' + suffix + 'Net').text(totals.net.toFixed(2));
  $('#total' + suffix + 'Basket').text(totals.basket);
  $('#total' + suffix + 'BeforeDiscount').text(totals.before.toFixed(2));
  $('#total' + suffix + 'Discount').text(totals.discount.toFixed(2));
  $('#total' + suffix + 'Price').text(totals.total.toFixed(2));
}

// ============================================================================
// PRICING
// ============================================================================

// Selected customer (dispatch / stock balance) or supplier (receiving)
function selectedParty() {
  var select = isSupplierStatus($('#status').val()) ? $('#supplier') : $('#customer');
  var option = select.find('option:selected');

  return { id: select.val() || '', currency: option.data('currency') || '', type: option.data('type') || '' };
}

function partyCurrency() {
  return selectedParty().currency || wholesalesLookups.defaultCurrencyId || '';
}

// Party changed: rows take its currency and weight rows are re-priced
function applyPartyCurrency() {
  var currencyId = partyCurrency();
  if (currencyId) {
    $('#weightDetailsTable, #rejectDetailsTable').children('tr').each(function () {
      setSelectValue(field($(this), 'currency'), currencyId);
    });
  }
  if (selectedParty().id) {
    priceWeightRows();
  }
}

function priceWeightRows() {
  if (!wholesalesFlags.showPrice) {
    return;
  }
  $('#weightDetailsTable').children('tr').each(function () {
    calculatePrice($(this), 'replace');
  });
}

// Weight changed: re-price with the known pricing type, or ask the server for it
function recalculateRow(row) {
  if (row.closest('#weightDetailsTable').length && field(row, 'fixedfloat').val() === '' && field(row, 'product').val()) {
    calculatePrice(row, 'override');
    return;
  }
  applyRowPrice(row);
}

// mode: 'replace' = use the price list price, 'override' = keep the typed price, otherwise keep a price above 0
function calculatePrice(row, mode) {
  var productId = field(row, 'product').val();
  var currencyId = field(row, 'currency').val();
  var status = $('#status').val();
  var party = selectedParty();

  // Packing parties are not charged
  if (mode === 'replace' && party.type === 'Packing') {
    field(row, 'price').val('0.00');
    field(row, 'before_discount').val('0.00');
    field(row, 'total').val('0.00');
    updateTotals();
    return;
  }

  if (!productId || !currencyId || !status) {
    return;
  }

  $('#spinnerLoading').show();
  $.post(productsApi, { action: 'getPrice', id: productId, status: status, customerID: party.id, grade: field(row, 'grade_id').val() || '', currency: currencyId }, function (obj) {
    if (obj.status === 'success') {
      var existing = parseFloat(field(row, 'price').val()) || 0;
      var listPrice = parseFloat(obj.message.price) || 0;
      var price = mode === 'replace' ? listPrice : (mode === 'override' || existing > 0 ? existing : listPrice);

      field(row, 'fixedfloat').val(obj.message.pricingType);
      field(row, 'price').val(price);
      applyRowPrice(row);
    } else {
      toastr.error(obj.message, 'Failed:');
    }
  }, 'json').fail(function () {
    toastr.error('Something went wrong', 'Failed:');
  }).always(function () {
    $('#spinnerLoading').hide();
  });
}

// Before discount = price x net (Float) or price (Fixed); total = before discount less fixed / % discount
function applyRowPrice(row) {
  var price = parseFloat(field(row, 'price').val()) || 0;
  var net = parseFloat(field(row, 'net').val()) || 0;
  var beforeDiscount = field(row, 'fixedfloat').val() === 'Float' ? price * net : price;
  var discount = parseFloat(field(row, 'discount').val()) || 0;
  var total = field(row, 'discount_type').val() === 'percent' ? beforeDiscount - (beforeDiscount * discount / 100) : beforeDiscount - discount;

  field(row, 'before_discount').val(beforeDiscount.toFixed(2));
  field(row, 'total').val(Math.max(total, 0).toFixed(2));
  updateTotals();
}

// ============================================================================
// PRINT / EXPORT
// ============================================================================

function openPrint(id) {
  printIds = [id];
  $('#printOptionsModal').modal('show');
}

function printSelected() {
  var ids = $('#weightTable tbody .rowCheckbox:checked').map(function () {
    return $(this).val();
  }).get();

  if (!ids.length) {
    toastr.warning(wholesalesText.selectRecord, 'Warning:');
    return;
  }
  if (ids.length > 10) {
    toastr.error(wholesalesText.maxPrintRecords, 'Error:');
    return;
  }

  printIds = ids;
  $('#printOptionsModal').modal('show');
}

function printEntry() {
  var formData = $('#printOptionsForm').serialize();
  var paperSize = $('#paperSize').val();

  if (!printIds.length) {
    toastr.error('No record selected for printing', 'Error:');
    return;
  }

  $('#printOptionsModal').modal('hide');

  if (printIds.length > 1) {
    openMultiPrintPreview(printIds, formData, paperSize);
    return;
  }

  $.post(wholesaleApi, formData + '&action=printSlip&id=' + encodeURIComponent(printIds[0]), function (obj) {
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
    // A5: wait for the QR images to load
    var images = $(printWindow.document).find('.qr-block img');
    var loaded = 0;
    var done = false;
    var finish = function () {
      if (!done) {
        done = true;
        setTimeout(printAndClose, 300);
      }
    };

    if (!images.length) {
      finish();
      return;
    }
    images.each(function () {
      if (this.complete) {
        loaded++;
      } else {
        $(this).on('load error', function () {
          if (++loaded >= images.length) {
            finish();
          }
        });
      }
    });
    if (loaded >= images.length) {
      finish();
    }
    setTimeout(finish, 5000);
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

// Print several records one after another into a single print window
function openMultiPrintPreview(ids, formData, paperSize) {
  var pages = [];
  var index = 0;

  $('#spinnerLoading').show();

  var next = function () {
    if (index >= ids.length) {
      $('#spinnerLoading').hide();
      combineAndPrint(pages, paperSize);
      return;
    }

    $.post(wholesaleApi, formData + '&action=printSlip&id=' + encodeURIComponent(ids[index]) + '&mode=content', function (obj) {
      if (obj.status === 'success') {
        pages.push(obj.message);
      }
    }, 'json').always(function () {
      index++;
      next();
    });
  };

  next();
}

function combineAndPrint(pages, paperSize) {
  if (!pages.length) {
    toastr.error(wholesalesText.noRecordsToPrint, 'Error:');
    return;
  }

  var styles = paperSize === 'A5' ? [
    '@page { size: A4 portrait; margin: 0; }',
    '* { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }',
    'body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 0 1mm; background: #fff; }',
    '.record-section { page-break-after: always; width: 210mm; height: 148mm; overflow: hidden; padding: 5mm 0; }',
    '.record-section:last-child { page-break-after: avoid; }',
    '.a5-wrapper { width: 100%; height: 138mm; }',
    '.slip-border { border: 2px solid #000; padding: 8px; box-sizing: border-box; width: 100%; height: 100%; position: relative; }',
    '.header-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; }',
    '.company-name { font-size: 16px; font-weight: bold; }',
    '.slip-title { font-size: 18px; font-weight: bold; text-decoration: underline; text-align: right; margin-right: 50px; }',
    '.info-block { display: flex; justify-content: space-between; margin-bottom: 8px; border-bottom: 1px solid #000; padding-bottom: 6px; }',
    '.info-left { flex: 1; }',
    '.info-right { text-align: left; }',
    '.info-row { display: flex; margin-bottom: 3px; }',
    '.info-label { font-weight: bold; width: 90px; flex-shrink: 0; }',
    '.info-value { flex: 1; }',
    'table.items { width: 100%; border-collapse: collapse; margin-bottom: 10px; }',
    'table.items th { border-bottom: 2px solid #000; padding: 3px 4px; text-decoration: underline; font-weight: bold; text-align: center; font-size: 14px; }',
    'table.items td { padding: 2px 4px; text-align: center; font-size: 14px; }',
    '.a5-footer { display: flex; justify-content: space-between; align-items: flex-end; position: absolute; bottom: 8px; left: 8px; right: 8px; }',
    'table.summary { border-collapse: collapse; }',
    'table.summary th, table.summary td { border: 1px solid #000; padding: 6px 8px; text-align: center; }',
    'table.summary th { font-weight: bold; }',
    'table.summary td { font-weight: bold; }',
    '@media print { @page { size: A4 portrait; margin: 0; } body { padding: 0 1mm; } }'
  ] : [
    '* { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }',
    'body { font-family: Arial, sans-serif; font-size: 13px; margin: 0; padding: 10mm; }',
    '.record-section { page-break-after: always; margin-bottom: 10mm; }',
    '.record-section:last-child { page-break-after: avoid; }',
    '.record-header { margin-bottom: 10px; border-bottom: 1px solid #000; padding-bottom: 5px; }',
    '.header-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 5px; }',
    '.company-name { font-size: 18px; font-weight: bold; }',
    '.slip-title { font-size: 20px; font-weight: bold; text-decoration: underline; }',
    '.info-block { display: flex; justify-content: space-between; }',
    '.info-row { margin-bottom: 1px; font-size: 12px; display: flex; white-space: nowrap; }',
    '.info-label { font-weight: bold; width: 95px; display: inline-block; text-align: left; flex-shrink: 0; }',
    'table.items { width: 100%; border-collapse: collapse; }',
    'table.items th { border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 6px 4px; font-weight: bold; text-align: center; font-size: 13px; }',
    'table.items td { border: none; padding: 4px; text-align: center; font-size: 13px; }',
    'table.items td:nth-child(2) { text-align: left; }',
    '.summary-section { margin-top: 20px; display: flex; justify-content: flex-end; }',
    'table.summary { border-collapse: collapse; }',
    'table.summary th, table.summary td { border: 1px solid #000; padding: 8px 12px; text-align: center; }',
    'table.summary th { background: #f0f0f0; font-weight: bold; }',
    'table.summary td { font-weight: bold; font-size: 13px; }',
    '.row { display: flex; flex-wrap: wrap; margin-right: -5px; margin-left: -5px; }',
    '.col-4 { flex: 0 0 33.333333%; max-width: 33.333333%; padding: 0 5px; box-sizing: border-box; }',
    '.col-8 { flex: 0 0 66.666667%; max-width: 66.666667%; padding: 0 5px; box-sizing: border-box; }',
    '.mb-1 { margin-bottom: 0.25rem; }',
    '.mb-3 { margin-bottom: 1rem; }',
    '.address { font-size: 14px; }',
    '.header-row { margin-bottom: 5px; font-size: 14px; display: flex; }',
    '.header-label { width: 120px; flex-shrink: 0; }',
    '.header-value { flex: 1; }',
    'table.grade-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }',
    'table.grade-table th, table.grade-table td { border: 1px solid black; padding: 5px; text-align: center; font-size: 10px; }',
    'table.grade-table th { background-color: #f0f0f0; }',
    '.info-section { display: flex; width: 100%; margin-bottom: 3px; border-bottom: 1px solid #000; padding-bottom: 3px; }',
    '.info-col { flex: 1; padding-right: 6px; }',
    '.irow { display: flex; margin-bottom: 1px; font-size: 11px; }',
    '.ilabel { width: 90px; flex-shrink: 0; font-weight: bold; }',
    '.ivalue { flex: 1; }',
    '.hrow { display: flex; font-size: 11px; margin-bottom: 2px; }',
    '.hlabel { width: 90px; flex-shrink: 0; }',
    '.hvalue { flex: 1; }',
    '.status-title { font-size: 22px; font-weight: bold; text-align: center; margin-bottom: 4px; }',
    '@page { size: A4 portrait; margin: 10mm; }',
    '@media print { body { margin: 0; padding: 0; } }'
  ];

  var printWindow = window.open('', '_blank', 'height=' + screen.height + ',width=' + screen.width);
  printWindow.document.write('<html><head><style>' + styles.join('') + '</style></head><body>' + pages.join('') + '</body></html>');
  printWindow.document.close();

  setTimeout(function () {
    printWindow.focus();
    printWindow.print();
    printWindow.close();
  }, 500);
}

function exportExcel(id) {
  $('<form method="POST" target="_blank" style="display:none"></form>')
    .attr('action', wholesaleApi)
    .append($('<input type="hidden" name="action" value="exportExcel">'))
    .append($('<input type="hidden" name="id">').val(id))
    .appendTo('body')
    .submit()
    .remove();
}

// Inner HTML of a tag (head / body) in a full HTML document string
function htmlPart(html, tag) {
  var match = new RegExp('<' + tag + '[^>]*>([\\s\\S]*)</' + tag + '>', 'i').exec(html);
  return match ? match[1] : html;
}

function exportInvoices() {
  var ids = $('#weightTable tbody .rowCheckbox:checked').map(function () {
    return $(this).val();
  }).get();

  if (!ids.length) {
    toastr.warning(wholesalesText.selectInvoice, 'Warning:');
    return;
  }
  if (ids.length === 1) {
    printInvoice(ids[0]);
    return;
  }

  $('#spinnerLoading').show();
  var requests = $.map(ids, function (id) {
    return $.post(wholesaleApi, { action: 'printInvoice', id: id }, null, 'json');
  });

  $.when.apply($, requests).done(function () {
    var head = '';
    var body = '';
    $.each(arguments, function (i, args) {
      var obj = args[0];
      if (obj.status === 'success') {
        head = head || htmlPart(obj.message, 'head');
        body += '<div style="page-break-after: always;">' + htmlPart(obj.message, 'body') + '</div>';
      }
    });

    if (body) {
      var printWindow = window.open('', '_blank');
      printWindow.document.write('<html><head>' + head + '</head><body>' + body + '</body></html>');
      printWindow.document.close();
    }
  }).fail(function () {
    toastr.error('Failed to load invoices.', 'Error:');
  }).always(function () {
    $('#spinnerLoading').hide();
  });
}

function printInvoice(id) {
  $.post(wholesaleApi, { action: 'printInvoice', id: id }, function (obj) {
    if (obj.status === 'success') {
      var printWindow = window.open('', '_blank');
      printWindow.document.write(obj.message);
      printWindow.document.close();
    } else {
      toastr.error(obj.message, 'Failed:');
    }
  }, 'json').fail(function () {
    toastr.error('Something wrong when printing invoice', 'Failed:');
  });
}
