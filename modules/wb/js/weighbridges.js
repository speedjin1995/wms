// Weighbridge list / entry page. Requires wbCommon.js, `wbPermissions` and `wbText`.

// 1. Variables
var weightTable;

// 2. Document ready
$(document).ready(function () {
  wbInitSelect2();
  wbInitFilters();

  $('#transactionDateTimePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY HH:mm',
    defaultDate: new Date()
  });

  $('#grossIncomingDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY HH:mm'
  });

  $('#tareOutgoingDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY HH:mm'
  });

  weightTable = $('#weightTable').DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: true,
    serverMethod: 'post',
    searching: true,
    order: [[ 1, 'asc' ]],
    language: wbTableLanguage(),
    ajax: {
      url: wbApi,
      data: function (d) {
        return $.extend(d, wbFilters(), { action: 'list' });
      }
    },
    columns: wbColumns().concat([
      {
        data: 'id',
        orderable: false,
        render: function (data) {
          return '<div class="d-flex" style="gap:4px;">' + actionButtons(data) + '</div>';
        }
      }
    ])
  });

  // Expand / collapse row details on row click
  $('#weightTable tbody').on('click', 'tr', function (e) {
    if ($(e.target).closest('td').hasClass('select-checkbox') || $(e.target).closest('button').length || $(e.target).is('input')) {
      return;
    }

    var tr = $(this);
    var row = weightTable.row(tr);
    if (!row.data()) {
      return;
    }

    if (row.child.isShown()) {
      row.child.hide();
      tr.removeClass('shown');
      return;
    }

    $.post(wbApi, { action: 'get', id: row.data().id }, function (obj) {
      if (obj.status === 'success') {
        row.child(formatDetails(obj.message)).show();
        tr.addClass('shown');
      } else {
        toastr["error"](obj.message, "Failed:");
      }
    }).fail(function () {
      toastr["error"]("Something went wrong", "Failed:");
    });
  });

  $('#filterSearch').on('click', function () {
    weightTable.ajax.reload();
  });

  $('#addEntry').on('click', function () {
    newEntry();
  });

  $('#extendForm').validate({
    errorElement: 'span',
    errorPlacement: placeError,
    highlight: function (element) { $(element).addClass('is-invalid'); },
    unhighlight: function (element) { $(element).removeClass('is-invalid'); },
    submitHandler: function () {
      saveEntry();
    }
  });

  $('#cancelForm').validate({
    errorElement: 'span',
    errorPlacement: placeError,
    highlight: function (element) { $(element).addClass('is-invalid'); },
    unhighlight: function (element) { $(element).removeClass('is-invalid'); },
    submitHandler: function () {
      cancelEntry();
    }
  });

  $('#transactionStatus').on('change', function () {
    var status = $(this).val();
    $('#customer').val('').trigger('change');
    if (status == 'Dispatch' || status == 'Sales' || status == 'Misc') {
      $('#supplierFieldDiv, #purchaseOrderDiv').hide();
      $('#customerFieldDiv, #deliveryOrderDiv').show();
    } else {
      $('#customerFieldDiv, #deliveryOrderDiv').hide();
      $('#supplierFieldDiv, #purchaseOrderDiv').show();
    }
  });

  $('#customer').on('change', function () {
    $('#customerCode').val($(this).find(':selected').data('code'));
  });

  $('#supplier').on('change', function () {
    $('#supplierCode').val($(this).find(':selected').data('code'));
  });

  $('#product').on('change', function () {
    $('#productCode').val($(this).find(':selected').data('code'));
  });

  $('#grossIncoming').on('keyup', function () {
    $('#grossIncomingDatePicker').datetimepicker('date', moment());
    calculateWeight();
  });

  $('#tareOutgoing').on('keyup', function () {
    $('#tareOutgoingDatePicker').datetimepicker('date', moment());
    calculateWeight();
  });
});

// 3. Functions
function placeError(error, element) {
  error.addClass('invalid-feedback');
  element.closest('.form-group-modern').append(error);
}

function actionButtons(id) {
  var buttons = '';
  if (wbPermissions.allowEdit) {
    buttons += '<button type="button" onclick="edit(' + id + ')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button>';
  }
  buttons += '<button type="button" onclick="printSlip(' + id + ')" class="btn btn-sm btn-outline-secondary" title="Print"><i class="fas fa-print"></i></button>';
  if (wbPermissions.allowDelete) {
    buttons += '<button type="button" onclick="deactivate(' + id + ')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>';
  }
  return buttons;
}

function calculateWeight() {
  var gross = $('#grossIncoming').val() || 0;
  var tare = $('#tareOutgoing').val() || 0;
  $('#nettWeight').val(Math.abs(parseFloat(gross) - parseFloat(tare)));
}

function setPickerDate(picker, value) {
  if (value) {
    $(picker).datetimepicker('date', moment(value, 'YYYY-MM-DD HH:mm:ss'));
  } else {
    $(picker).datetimepicker('clear');
  }
}

function newEntry() {
  var modal = $('#extendModal');
  modal.find('#id').val('');
  modal.find('#transactionId').val('');
  modal.find('#transactionStatus').val('Dispatch').trigger('change');
  $('#transactionDateTimePicker').datetimepicker('date', moment());
  modal.find('#poNo, #doNo, #grossIncoming, #tareOutgoing, #nettWeight').val('');
  modal.find('#customer, #supplier, #product, #vehicle').val('').trigger('change');
  $('#grossIncomingDatePicker').datetimepicker('clear');
  $('#tareOutgoingDatePicker').datetimepicker('clear');
  modal.modal('show');
}

function edit(id) {
  $('#spinnerLoading').show();

  $.post(wbApi, { action: 'get', id: id }, function (obj) {
    if (obj.status === 'success') {
      var modal = $('#extendModal');
      var row = obj.message;
      modal.find('#id').val(row.id);
      modal.find('#transactionId').val(row.transaction_id);
      modal.find('#transactionStatus').val(row.transaction_status).trigger('change');
      setPickerDate('#transactionDateTimePicker', row.transaction_date);
      modal.find('#poNo').val(row.purchase_order);
      modal.find('#doNo').val(row.delivery_no);
      modal.find('#customer').val(row.customer_name).trigger('change');
      modal.find('#supplier').val(row.supplier_name).trigger('change');
      modal.find('#product').val(row.product_name).trigger('change');
      modal.find('#vehicle').val(row.lorry_plate_no1).trigger('change');
      modal.find('#grossIncoming').val(row.gross_weight1);
      setPickerDate('#grossIncomingDatePicker', row.gross_weight1_date);
      modal.find('#tareOutgoing').val(row.tare_weight1);
      setPickerDate('#tareOutgoingDatePicker', row.tare_weight1_date);
      modal.find('#nettWeight').val(row.nett_weight1);
      modal.modal('show');
    } else {
      toastr["error"](obj.message || "Something went wrong", "Failed:");
    }
  }).fail(function () {
    toastr["error"]("Something went wrong", "Failed:");
  }).always(function () {
    $('#spinnerLoading').hide();
  });
}

function saveEntry() {
  $('#spinnerLoading').show();
  $('#saveButton').prop('disabled', true);

  $.post(wbApi, $('#extendForm').serialize() + '&action=save', function (obj) {
    if (obj.status === 'success') {
      $('#extendModal').modal('hide');
      toastr["success"](obj.message, "Success:");
      weightTable.ajax.reload(null, false);
    } else {
      toastr["error"](obj.message || "Something went wrong", "Failed:");
    }
  }).fail(function () {
    toastr["error"]("Something went wrong", "Failed:");
  }).always(function () {
    $('#saveButton').prop('disabled', false);
    $('#spinnerLoading').hide();
  });
}

function deactivate(id) {
  if (confirm(wbText.confirmDelete)) {
    $('#cancelId').val(id);
    $('#cancelReason').val('');
    $('#cancelModal').modal('show');
  }
}

function cancelEntry() {
  $('#spinnerLoading').show();
  $('#submitCancel').prop('disabled', true);

  $.post(wbApi, $('#cancelForm').serialize() + '&action=cancel', function (obj) {
    if (obj.status === 'success') {
      $('#cancelModal').modal('hide');
      toastr["success"](obj.message, "Success:");
      weightTable.ajax.reload(null, false);
    } else {
      toastr["error"](obj.message || "Something went wrong", "Failed:");
    }
  }).fail(function () {
    toastr["error"]("Something went wrong", "Failed:");
  }).always(function () {
    $('#submitCancel').prop('disabled', false);
    $('#spinnerLoading').hide();
  });
}

function formatDetails(row) {
  var e = wbEscape;
  var grossWeight = parseFloat(row.gross_weight1) || 0;
  var tareWeight = parseFloat(row.tare_weight1) || 0;
  var nettWeight = parseFloat(row.nett_weight1) || 0;
  var isDispatch = row.transaction_status === 'Dispatch';
  var party = row.customer_name || row.supplier_name || '-';

  return `
  <div class="expanded-row-content">
    <div class="expanded-header">
      <div>
        <div class="expanded-header-title">${e(row.transaction_id || '-')}</div>
        <div class="expanded-header-subtitle">${e(party)}</div>
      </div>
      <div class="expanded-actions">${actionButtons(row.id)}</div>
    </div>

    <div class="kpi-row">
      <div class="kpi-card">
        <div class="kpi-label">${e(wbText.incomingWeight)}</div>
        <div class="kpi-value kpi-value-primary">${grossWeight.toFixed(2)} <span class="kpi-unit">KG</span></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">${e(wbText.outgoingWeight)}</div>
        <div class="kpi-value">${tareWeight.toFixed(2)} <span class="kpi-unit">KG</span></div>
      </div>
      <div class="kpi-card kpi-card-success">
        <div class="kpi-label">${e(wbText.nettWeight)}</div>
        <div class="kpi-value">${nettWeight.toFixed(2)} <span class="kpi-unit">KG</span></div>
      </div>
    </div>

    <div class="info-section">
      <div class="info-section-title">${e(wbText.transactionInfo)}</div>
      <div class="info-grid">
        <div><span class="info-item-label">${e(wbText.transactionId)}</span><span class="info-item-value">${e(row.transaction_id || '-')}</span></div>
        <div><span class="info-item-label">${e(wbText.transactionDate)}</span><span class="info-item-value">${e(row.transaction_date || '-')}</span></div>
        <div><span class="info-item-label">${e(wbText.transactionStatus)}</span><span class="info-item-value">${e(row.transaction_status || '-')}</span></div>
        <div><span class="info-item-label">${e(isDispatch ? wbText.doNo : wbText.poNo)}</span><span class="info-item-value">${e((isDispatch ? row.delivery_no : row.purchase_order) || '-')}</span></div>
        <div><span class="info-item-label">${e(wbText.vehicleNo)}</span><span class="info-item-value">${e(row.lorry_plate_no1 || '-')}</span></div>
        <div><span class="info-item-label">${e(isDispatch ? wbText.customer : wbText.supplier)}</span><span class="info-item-value">${e(party)}</span></div>
        <div><span class="info-item-label">${e(wbText.product)}</span><span class="info-item-value">${e(row.product_name || '-')}</span></div>
      </div>
    </div>

    <div class="details-section">
      <div class="details-header">
        <span class="details-title"><i class="fas fa-weight mr-1"></i>${e(wbText.weighingDetails)}</span>
      </div>
      <div class="table-responsive">
        <table class="table details-table mb-0">
          <thead>
            <tr>
              <th>${e(wbText.type)}</th>
              <th class="text-right">${e(wbText.weight)} (KG)</th>
              <th>${e(wbText.dateTime)}</th>
              <th>${e(wbText.weighedBy)}</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><span class="badge badge-info">${e(wbText.incoming)}</span></td>
              <td class="text-right text-mono font-weight-bold">${grossWeight.toFixed(2)}</td>
              <td class="text-muted">${e(row.gross_weight1_date || '-')}</td>
              <td class="text-muted">${e(row.grossWeightBy || '-')}</td>
            </tr>
            <tr>
              <td><span class="badge badge-secondary">${e(wbText.outgoing)}</span></td>
              <td class="text-right text-mono font-weight-bold">${tareWeight.toFixed(2)}</td>
              <td class="text-muted">${e(row.tare_weight1_date || '-')}</td>
              <td class="text-muted">${e(row.tareWeightBy || '-')}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td><strong>${e(wbText.nettWeight)}</strong></td>
              <td class="text-right text-mono text-success font-weight-bold">${nettWeight.toFixed(2)}</td>
              <td></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
  `;
}
