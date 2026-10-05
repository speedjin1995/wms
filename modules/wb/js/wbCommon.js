// Shared helpers for the weighbridge list and reports pages.
// Requires a page-level `wbText` object (translated labels).

// 1. Variables
var wbApi = 'php/modules/wb/api.php';

// 3. Functions
function wbEscape(value) {
  return $('<div>').text(value === null || value === undefined ? '' : value).html();
}

function wbInitSelect2() {
  $('.select2').each(function() {
    $(this).select2({
      allowClear: true,
      placeholder: wbText.pleaseSelect,
      dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-body') : undefined
    });
  });
}

function wbInitFilters() {
  var today = new Date();
  var lastWeek = new Date(today);
  lastWeek.setDate(lastWeek.getDate() - 7);

  $('#fromDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: lastWeek
  });

  $('#toDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: today
  });

  $('#transactionStatusFilter').on('change', function() {
    var status = $(this).val();
    $('#customerNoFilter').val('').trigger('change');
    $('#supplierNoFilter').val('').trigger('change');
    if (status == 'Dispatch' || status == 'Sales' || status == 'Misc') {
      $('#supplierFilterDiv').hide();
      $('#customerFilterDiv').show();
    } else {
      $('#customerFilterDiv').hide();
      $('#supplierFilterDiv').show();
    }
  });

  $('#selectAllCheckbox').on('change', function() {
    $('#weightTable tbody input[type="checkbox"]').prop('checked', $(this).prop('checked')).trigger('change');
  });
}

// Current filter values (read on every request so ajax.reload() picks up changes)
function wbFilters() {
  return {
    fromDate: $('#fromDate').val(),
    toDate: $('#toDate').val(),
    transactionStatus: $('#transactionStatusFilter').val() || '',
    status: $('#statusFilter').val() || '',
    product: $('#productFilter').val() || '',
    customer: $('#customerNoFilter').val() || '',
    supplier: $('#supplierNoFilter').val() || '',
    vehicle: $('#vehicleNoFilter').val() || '',
    transactionId: $('#transactionIDFilter').val() || ''
  };
}

function wbTableLanguage() {
  return {
    emptyTable: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title">' + wbEscape(wbText.noRecordsFound) + '</div><div class="empty-message">' + wbEscape(wbText.noRecordsMessage) + '</div></div>',
    zeroRecords: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title">' + wbEscape(wbText.noMatchingRecords) + '</div><div class="empty-message">' + wbEscape(wbText.noMatchingMessage) + '</div></div>'
  };
}

// Checkbox + data columns shared by both tables
function wbColumns() {
  var text = $.fn.dataTable.render.text();

  return [
    {
      data: 'id',
      className: 'select-checkbox',
      orderable: false,
      render: function (data) {
        return '<input type="checkbox" class="select-checkbox" id="checkbox_' + data + '" value="' + data + '"/>';
      }
    },
    { data: 'transaction_id', render: text },
    { data: 'transaction_date', render: text },
    { data: 'transaction_status', render: text },
    { data: 'do_po', render: text },
    { data: 'lorry_plate_no1', render: text },
    { data: 'customer_supplier', render: text },
    { data: 'product_name', render: text },
    { data: 'gross_weight1', className: 'text-right', render: text },
    { data: 'gross_weight1_date', render: text },
    { data: 'tare_weight1', className: 'text-right', render: text },
    { data: 'tare_weight1_date', render: text },
    { data: 'final_weight', className: 'text-right', render: text }
  ];
}

function wbSelectedIds() {
  var ids = [];
  $("#weightTable tbody input[type='checkbox']:checked").each(function() {
    ids.push($(this).val());
  });
  return ids;
}

function printSlip(id) {
  $.post(wbApi, { action: 'printSlip', id: id }, function(response) {
    if (response.status === 'success') {
      var printWindow = window.open('', '', 'height=' + screen.height + ',width=' + screen.width);
      printWindow.document.write(response.message);
      printWindow.document.close();
      setTimeout(function() {
        printWindow.print();
        printWindow.close();
      }, 500);
    } else {
      toastr["error"](response.message, "Failed:");
    }
  }).fail(function() {
    toastr["error"]("Something went wrong", "Failed:");
  });
}
