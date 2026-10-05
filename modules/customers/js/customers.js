$(function () {
  $('#selectAllCheckbox').on('change', function() {
    var checkboxes = $('#customerTable tbody input[type="checkbox"]');
    checkboxes.prop('checked', $(this).prop('checked')).trigger('change');
  });

  // Initialize Select2 for modal elements
  $('#addModal .select2').select2({
    allowClear: true,
    placeholder: "Please Select",
    dropdownParent: $('#addModal')
  });

  $("#customerTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'language': customersTableLanguage,
    'ajax': {
      'url':'php/modules/customers/api.php',
      'data': { action: 'list' }
    },
    'columns': [
      {
        // Add a checkbox with a unique ID for each row
        data: 'id', // Assuming 'serialNo' is a unique identifier for each row
        className: 'select-checkbox',
        orderable: false,
        render: function (data, type, row) {
            return '<input type="checkbox" class="select-checkbox" id="checkbox_' + data + '" value="'+data+'"/>';
        }
      },
      { data: 'customer_code' },
      { data: 'reg_no' },
      { data: 'parent' },
      { data: 'customer_name' },
      { data: 'customer_address' },
      { data: 'customer_phone' },
      { data: 'pic' },
      {
        data: 'pending_bins',
        visible: hasBasket,
        render: function (data) {
          if (!data) return '<span class="text-muted">—</span>';

          var map = {};
          try { map = (typeof data === 'string') ? JSON.parse(data) : data; } catch(e) {}

          if (typeof map !== 'object' || Array.isArray(map)) {
            return '<span class="text-muted">—</span>';
          }

          var keys = Object.keys(map).filter(function(k) { return map[k] > 0; });
          if (keys.length === 0) return '<span class="text-muted">—</span>';

          var html = '<div style="display:flex;flex-wrap:wrap;gap:4px;">';
          keys.forEach(function(typeId) {
            var count = map[typeId];
            var label = binTypeNames[typeId] || 'Type ' + typeId;
            html += '<span style="'
              + 'display:inline-flex;align-items:center;gap:5px;'
              + 'background:linear-gradient(135deg,#f6d365,#fda085);'
              + 'color:#7a3e00;font-size:0.75rem;font-weight:700;'
              + 'padding:3px 8px 3px 10px;border-radius:20px;'
              + 'box-shadow:0 1px 3px rgba(253,160,133,0.4);white-space:nowrap;">';
            html +=   '<span>' + label + '</span>';
            html +=   '<span style="background:rgba(0,0,0,0.15);border-radius:20px;padding:1px 6px;font-size:0.85em;">' + count + '</span>';
            html += '</span>';
          });
          html += '</div>';
          return html;
        }
      },
      { 
        data: 'deleted',
        responsivePriority: 1,
        render: function (data, type, row) {
          if (data == 0) {
            return '<div class="d-flex" style="gap:4px;">'
              + '<button onclick="edit(' + row.id + ')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button>'
              + (hasBasket ? '<button onclick="openBinModal(' + row.id + ', \'' + row.customer_name + '\')" class="btn btn-sm btn-outline-warning" title="Manage Bins"><i class="fas fa-shopping-basket"></i></button>'
                          + '<button onclick="openBinHistory(' + row.id + ', \'' + row.customer_name + '\')" class="btn btn-sm btn-outline-info" title="Bin History"><i class="fas fa-history"></i></button>' : '')
              + (runningNoType === 1 ? '<button onclick="openRunningNo(' + row.id + ', \'' + row.customer_name + '\')" class="btn btn-sm btn-outline-secondary" title="Running No"><i class="fas fa-hashtag"></i></button>' : '')
              + '<button onclick="deactivate(' + row.id + ')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>'
              + '</div>';
          } else {
            return '<button onclick="reactivate(' + row.id + ')" class="btn btn-sm btn-outline-warning">Reactivate</button>';
          }
        }
      }
    ],
    "rowCallback": function( row, data, index ) {
      if (data.is_manual == 'Y') {
        $(row).css('background-color', '#f8d7da');
      }
    },        
  });
  
  $.validator.setDefaults({
    submitHandler: function () {
      if ($('#addModal').hasClass('show')) {
        // Check at least one registration field is filled
        if (requireRegistration) {
          var ctos = $('#ctosReportNo').val().trim();
          var ic = $('#icNo').val().replace(/_/g, '').replace(/-/g, '').trim();
          var ssm = $('#ssmNo').val().trim();
          if (ctos === '' && ic === '' && ssm === '') {
            toastr["error"](customersText.atLeastOneRegistration, "Failed:");
            return;
          }
        }
        $('#spinnerLoading').show();
        var formData = new FormData($('#customerForm')[0]);
        formData.append('action', 'save');
        $.ajax({
          url: 'php/modules/customers/api.php',
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          success: function(obj) {
            if (obj.status === 'success') {
              $('#addModal').modal('hide');
              toastr["success"](obj.message, "Success:");
              $('#customerTable').DataTable().ajax.reload();
              $.post('php/modules/customers/api.php', {action: 'dropdown'}, function(customers) {
                $('#parent').empty().append('<option value="">Please Select</option>');
                customers.forEach(function(customer) {
                  $('#parent').append('<option value="' + customer.id + '">' + customer.customer_name + '</option>');
                });
              });
            } else if (obj.status === 'failed') {
              toastr["error"](obj.message, "Failed:");
            } else {
              toastr["error"]("Something wrong when edit", "Failed:");
            }
            $('#spinnerLoading').hide();
          },
          error: function() {
            toastr["error"]("Something wrong when saving", "Failed:");
            $('#spinnerLoading').hide();
          }
        });
      } else if ($('#binModal').hasClass('show')) {
        $('#spinnerLoading').show();
        $.post('php/modules/customers/api.php', $('#binForm').serialize() + '&action=updateBin', function(obj) {
          if (obj.status === 'success') {
            $('#binModal').modal('hide');
            toastr['success'](obj.message, 'Success:');
            $('#binModal').find('#binCurrent').text(obj.pending_bins);
            $('#customerTable').DataTable().ajax.reload();
          } else {
            toastr['error'](obj.message, 'Failed:');
          }
          $('#spinnerLoading').hide();
        });
      }
    }
  });

  $('#addCustomers').on('click', function(){
    $('#addModal').find('#id').val("");
    $('#addModal').find('#code').val("");
    $('#addModal').find('#regNo').val("");
    $('#addModal').find('#ssmNo').val("");
    $('#addModal').find('#icNo').val("");
    $('#addModal').find('#ctosReportNo').val("");
    $('#addModal').find('#ssmFile').val("");
    $('#addModal').find('#ssmFileLabel').text(customersText.chooseFile);
    $('#addModal').find('#ssmFilePath').val("");
    $('#addModal').find('#ssmFilePreview').removeClass('d-flex').addClass('d-none');
    $('#addModal').find('#name').val("");
    $('#addModal').find('#customerType').val("Normal").trigger('change');
    $('#addModal').find('#address').val("");
    $('#addModal').find('#address2').val("");
    $('#addModal').find('#address3').val("");
    $('#addModal').find('#address4').val("");
    $('#addModal').find('#states').val("").trigger('change');
    $('#addModal').find('#phone').val("");
    $('#addModal').find('#fax').val("");
    $('#addModal').find('#email').val("");
    $('#addModal').find('#billingName').val("");
    $('#addModal').find('#billingAddress').val("");
    $('#addModal').find('#billingAddress2').val("");
    $('#addModal').find('#billingAddress3').val("");
    $('#addModal').find('#billingAddress4').val("");
    $('#addModal').find('#billingStates').val("").trigger('change');
    $('#addModal').find('#billingPhone').val("");
    $('#addModal').find('#billingFax').val("");
    $('#addModal').find('#billingPic').val("");
    $('#addModal').find('#currency').val("").trigger('change');
    $('#addModal').find('#parent').val("").trigger('change');
    $('#addModal').modal('show');
    
    $('#customerForm').validate({
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        highlight: function (element, errorClass, validClass) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).removeClass('is-invalid');
        }
    });
  });

  $('#uploadExcel').on('click', function(){
    $('#uploadModal').modal('show');

    $('#uploadForm').validate({
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        highlight: function (element, errorClass, validClass) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).removeClass('is-invalid');
        }
    });
  });

  $('#uploadModal').find('#previewButton').on('click', function(){
    var fileInput = document.getElementById('fileInput');
    var file = fileInput.files[0];
    var reader = new FileReader();
    
    reader.onload = function(e) {
        var data = e.target.result;
        // Process data and display preview
        displayPreview(data);
    };

    reader.readAsBinaryString(file);
  });

  $('#uploadCustomer').on('click', function(){
    $('#spinnerLoading').show();
    var formData = $('#uploadForm').serializeArray();
    var data = [];
    var rowIndex = -1;
    formData.forEach(function(field) {
    var match = field.name.match(/([a-zA-Z0-9]+)\[(\d+)\]/);
    if (match) {
      var fieldName = match[1];
      var index = parseInt(match[2], 10);
      if (index !== rowIndex) {
      rowIndex = index;
      data.push({});
      }
      data[index][fieldName] = field.value;
    }
    });

    // Send the JSON array to the server
    $.ajax({
        url: 'php/modules/customers/api.php?action=upload',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(obj) {
            if (obj.status === 'success') {
              $('#spinnerLoading').hide();
              $('#uploadModal').modal('hide');
              $('#customerTable').DataTable().ajax.reload();
            } 
            else if (obj.status === 'failed') {
              $('#spinnerLoading').hide();
            } 
            else if (obj.status === 'error') {
              $('#spinnerLoading').hide();
              $('#uploadModal').modal('hide');
              $('#errorModal').find('#errorList').empty();
              var errorMessage = obj.message;
              for (var i = 0; i < errorMessage.length; i++) {
                $('#errorModal').find('#errorList').append(`<li>${errorMessage[i]}</li>`);                            
              }
              $('#errorModal').modal('show');
            } 
            else {
              $('#spinnerLoading').hide();
            }
        }
    });
  });

  $('#multiDeactivate').on('click', function () {
    $('#spinnerLoading').show();
    var selectedIds = []; // An array to store the selected 'id' values

    $("#customerTable tbody input[type='checkbox']").each(function () {
      if (this.checked) {
          selectedIds.push($(this).val());
      }
    });

    if (selectedIds.length > 0) {
      if (confirm('Are you sure you want to cancel these items?')) {
          $.post('php/modules/customers/api.php', {action: 'delete', ids: selectedIds}, function(obj){

              if(obj.status === 'success'){
                $('#customerTable').DataTable().ajax.reload();
                $('#spinnerLoading').hide();
              }
              else if(obj.status === 'failed'){
                $('#spinnerLoading').hide();
              }
              else{
                $('#spinnerLoading').hide();
              }
          });
      }

      $('#spinnerLoading').hide();
    } 
    else {
        // Optionally, you can display a message or take another action if no IDs are selected
        alert("Please select at least one customer to delete.");
        $('#spinnerLoading').hide();
    }     
  });

  $('#sameAsDelivery').on('change', function() {
    var isSame = $(this).is(':checked');
    var billingFields = ['#billingAddress', '#billingAddress2', '#billingAddress3', '#billingAddress4', '#billingName', '#billingPhone', '#billingPic'];
    if (isSame) {
      $('#billingName').val($('#name').val());
      $('#billingPhone').val($('#phone').val());
      $('#billingPic').val($('#email').val());
      $('#billingAddress').val($('#address').val());
      $('#billingAddress2').val($('#address2').val());
      $('#billingAddress3').val($('#address3').val());
      $('#billingAddress4').val($('#address4').val());
      $('#billingStates').val($('#states').val()).trigger('change');
      $.each(billingFields, function(i, sel) { $(sel).prop('readonly', true); });
      $('#billingStates').next('.select2-container').css('pointer-events', 'none').css('opacity', '0.6');
    } else {
      $.each(billingFields, function(i, sel) { $(sel).prop('readonly', false); });
      $('#billingStates').next('.select2-container').css('pointer-events', '').css('opacity', '');
    }
  });

  $('#addModal').on('hidden.bs.modal', function() {
    $('#sameAsDelivery').prop('checked', false);
    ['#billingAddress','#billingAddress2','#billingAddress3','#billingAddress4','#billingName','#billingPhone','#billingPic'].forEach(function(sel) { $(sel).prop('readonly', false); });
    $('#billingStates').next('.select2-container').css('pointer-events', '').css('opacity', '');
  });

  $('#saveRunningNo').on('click', function() {
    var rows = [];
    var valid = true;
    $('#runningNoBody tr').each(function() {
      var status = $(this).find('input[name="transaction_status"]').val();
      var prefix = $(this).find('.rn-prefix').val().trim();
      var value  = parseInt($(this).find('.rn-value').val());
      if (!prefix || prefix.length > 10 || isNaN(value) || value < 1) { valid = false; return false; }
      rows.push({ transaction_status: status, prefix: prefix, value: value });
    });
    if (!valid) { toastr["error"]("Please check prefix (max 10 chars) and value (min 1).", "Failed:"); return; }
    $('#spinnerLoading').show();
    $.ajax({
      url: 'php/modules/customers/api.php',
      type: 'POST',
      data: { action: 'saveRunningNo', entity_id: $('#runningNoEntityId').val(), invoice_code: $('#runningNoInvoiceCode').val().trim(), rows: rows },
      success: function(obj) {
        if (obj.status === 'success') {
          $('#runningNoModal').modal('hide');
          toastr["success"](obj.message, "Success:");
        } else {
          toastr["error"](obj.message, "Failed:");
        }
        $('#spinnerLoading').hide();
      }
    });
  });

  // IC Number input mask
  $('#icNo').inputmask('999999-99-9999', { placeholder: '_' });

  // SSM File change handler
  $('#ssmFile').on('change', function() {
    var fileName = $(this).val().split('\\').pop();
    $('#ssmFileLabel').text(fileName || customersText.chooseFile);
  });

  // Remove SSM file
  $('#removeSsmFile').on('click', function() {
    $('#ssmFilePath').val('');
    $('#ssmFilePreview').removeClass('d-flex').addClass('d-none');
    $('#ssmFile').val('');
    $('#ssmFileLabel').text(customersText.chooseFile);
  });
});

function openRunningNo(id, name) {
  $('#runningNoEntityId').val(id);
  $('#runningNoCustomerName').text(name);
  $('#runningNoInvoiceCode').val('');
  $('#runningNoBody').html('<tr><td colspan="3" class="text-center"><i class="fas fa-spinner fa-spin"></i></td></tr>');
  $('#runningNoModal').modal('show');
  $.post('php/modules/customers/api.php', { action: 'getRunningNo', entity_id: id }, function(obj) {
    if (obj.status !== 'success') {
      toastr["error"](obj.message, "Failed:");
      $('#runningNoModal').modal('hide');
      return;
    }
    $('#runningNoInvoiceCode').val(obj.invoice_code || '');
    var html = '';
    obj.data.forEach(function(row) {
      html += '<tr>'
        + '<td>' + row.status + '<input type="hidden" name="transaction_status" value="' + row.status + '"></td>'
        + '<td><input type="text" class="form-control form-control-sm rn-prefix" value="' + row.saved_prefix + '" maxlength="10"></td>'
        + '<td><input type="number" class="form-control form-control-sm rn-value" value="' + row.value + '" min="1"></td>'
        + '</tr>';
    });
    $('#runningNoBody').html(html);
  });
}

function displayPreview(data) {
  // Parse the Excel data
  var workbook = XLSX.read(data, { type: 'binary' });

  // Get the first sheet
  var sheetName = workbook.SheetNames[0];
  var sheet = workbook.Sheets[sheetName];

  // Convert the sheet to an array of objects
  var jsonData = XLSX.utils.sheet_to_json(sheet, { header: 20 });

  // Get the headers
  var headers = Object.keys(jsonData[0] || {});

  // Ensure we handle cases where there may be less than 20 columns
  while (headers.length < 20) {
      headers.push(''); // Adding empty headers to reach 20 columns
  }

  // Create HTML table headers
  var htmlTable = '<table style="width:20%;"><thead><tr>';
  headers.forEach(function(header) {
      htmlTable += '<th>' + header + '</th>';
  });
  htmlTable += '</tr></thead><tbody>';

  // Iterate over the data and create table rows
  for (var i = 0; i < jsonData.length; i++) {
      htmlTable += '<tr>';
      var rowData = jsonData[i];

      for (var j = 0; j < 20 && j < headers.length; j++) {
          var cellData = rowData[headers[j]];
          var formattedData = cellData;

          // Check if cellData is a valid Excel date serial number and format it to DD/MM/YYYY
          if (typeof cellData === 'number' && cellData > 0) {
              var excelDate = XLSX.SSF.parse_date_code(cellData);
          }

          htmlTable += '<td><input type="text" id="'+headers[j].replace(/[^a-zA-Z0-9]/g, '')+i+'" name="'+headers[j].replace(/[^a-zA-Z0-9]/g, '')+'['+i+']" value="' + (formattedData == null ? '' : formattedData) + '" /></td>';
      }
      htmlTable += '</tr>';
  }

  htmlTable += '</tbody></table>';

  var previewTable = document.getElementById('previewTable');
  previewTable.innerHTML = htmlTable;
}

function edit(id){
  $('#spinnerLoading').show();
  $.post('php/modules/customers/api.php', {action: 'get', id: id}, function(obj){

      if(obj.status === 'success'){
          $('#addModal').find('#id').val(obj.message.id);
          $('#addModal').find('#code').val(obj.message.customer_code);
          $('#addModal').find('#regNo').val(obj.message.reg_no);
          $('#addModal').find('#ssmNo').val(obj.message.ssm);
          $('#addModal').find('#icNo').val(obj.message.ic_no);
          $('#addModal').find('#ctosReportNo').val(obj.message.ctos_report_no);
          $('#addModal').find('#name').val(obj.message.customer_name);
          $('#addModal').find('#address').val(obj.message.customer_address);
          $('#addModal').find('#address2').val(obj.message.customer_address2);
          $('#addModal').find('#address3').val(obj.message.customer_address3);
          $('#addModal').find('#address4').val(obj.message.customer_address4);
          $('#addModal').find('#states').val(obj.message.states).trigger('change');
          $('#addModal').find('#phone').val(obj.message.customer_phone);
          $('#addModal').find('#fax').val(obj.message.fax);
          $('#addModal').find('#email').val(obj.message.pic);
          $('#addModal').find('#billingName').val(obj.message.billing_name);
          $('#addModal').find('#billingAddress').val(obj.message.billing_address);
          $('#addModal').find('#billingAddress2').val(obj.message.billing_address2);
          $('#addModal').find('#billingAddress3').val(obj.message.billing_address3);
          $('#addModal').find('#billingAddress4').val(obj.message.billing_address4);
          $('#addModal').find('#billingStates').val(obj.message.billing_state).trigger('change');
          $('#addModal').find('#billingPhone').val(obj.message.billing_phone);
          $('#addModal').find('#billingFax').val(obj.message.billing_fax);
          $('#addModal').find('#billingPic').val(obj.message.billing_pic);
          $('#addModal').find('#currency').val(obj.message.currency).trigger('change');
          $('#addModal').find('#company').val(obj.message.customer).trigger('change');
          $('#addModal').find('#parent').val(obj.message.parent).trigger('change');
          $('#addModal').find('#customerType').val(obj.message.customer_type || 'Normal').trigger('change');
          
          // SSM File preview
          if (obj.message.ssm_file) {
            $('#addModal').find('#ssmFilePath').val(obj.message.ssm_file);
            $('#addModal').find('#ssmFileLink').attr('href', 'php/viewPhoto.php?file=' + obj.message.ssm_file + '&type=file_table');
            $('#addModal').find('#ssmFilePreview').removeClass('d-none').addClass('d-flex');
          } else {
            $('#addModal').find('#ssmFilePath').val('');
            $('#addModal').find('#ssmFilePreview').removeClass('d-flex').addClass('d-none');
          }
          $('#addModal').find('#ssmFile').val('');
          $('#addModal').find('#ssmFileLabel').text(customersText.chooseFile);
          
          $('#addModal').modal('show');
          
          $('#customerForm').validate({
              errorElement: 'span',
              errorPlacement: function (error, element) {
                  error.addClass('invalid-feedback');
                  element.closest('.form-group').append(error);
              },
              highlight: function (element, errorClass, validClass) {
                  $(element).addClass('is-invalid');
              },
              unhighlight: function (element, errorClass, validClass) {
                  $(element).removeClass('is-invalid');
              }
          });
      }
      else if(obj.status === 'failed'){
          toastr["error"](obj.message, "Failed:");
      }
      else{
          toastr["error"]("Something wrong when activate", "Failed:");
      }
      $('#spinnerLoading').hide();
  });
}

function deactivate(id){
  if (confirm('Are you sure you want to delete this items?')) {
    $('#spinnerLoading').show();
    $.post('php/modules/customers/api.php', {action: 'delete', ids: [id]}, function(obj){

        if(obj.status === 'success'){
            toastr["success"](obj.message, "Success:");
            $('#customerTable').DataTable().ajax.reload();
            $('#spinnerLoading').hide();
        }
        else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
            $('#spinnerLoading').hide();
        }
        else{
            toastr["error"]("Something wrong when activate", "Failed:");
            $('#spinnerLoading').hide();
        }
    });
  }
}

function reactivate(id){
  if (confirm('Are you sure you want to reactivate this items?')) {
    $('#spinnerLoading').show();
    $.post('php/modules/customers/api.php', {action: 'reactivate', id: id}, function(obj){

        if(obj.status === 'success'){
            toastr["success"](obj.message, "Success:");
            $('#customerTable').DataTable().ajax.reload();
            $('#spinnerLoading').hide();
        }
        else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
            $('#spinnerLoading').hide();
        }
        else{
            toastr["error"]("Something wrong when activate", "Failed:");
            $('#spinnerLoading').hide();
        }
    });
  }
}

function openBinModal(id, name) {
  $('#binModal').find('#binCustomerId').val(id);
  $('#binModal').find('#binCustomerName').text(name);
  $('#binModal').find('#binTypeId').val('');
  $('#binModal').find('#binTypeSelect').val('');
  $('#binModal').find('#binQty').val('');
  $('#binModal').find('#binRemark').val('');
  $('input[name="binAction"][value="OUT"]').prop('checked', true);
  $('#binDetails').hide();
  $('#binCurrent').text('0');
  $('#binModal').modal('show');

  $('#binForm').validate({
    errorElement: 'span',
    errorPlacement: function (error, element) {
        error.addClass('invalid-feedback');
        element.closest('.form-group').append(error);
    },
    highlight: function (element, errorClass, validClass) {
        $(element).addClass('is-invalid');
    },
    unhighlight: function (element, errorClass, validClass) {
        $(element).removeClass('is-invalid');
    },
    rules: {
      binTypeId:  { required: true },
      binQty:     { required: true, min: 1 }
    }
  });
}

$('#binTypeSelect').on('change', function() {
  var typeId = $(this).val();
  var customerId = $('#binCustomerId').val();
  $('#binTypeId').val(typeId);
  $('#binQty').val('');
  $('#binRemark').val('');
  $('input[name="binAction"][value="OUT"]').prop('checked', true);

  if (!typeId) {
    $('#binDetails').slideUp(150);
    return;
  }

  // Show skeleton, hide pending card while loading
  $('#binLoadingSkeleton').show();
  $('#binPendingCard').hide();
  if (!$('#binDetails').is(':visible')) {
    $('#binDetails').slideDown(200);
  }

  $.post('php/modules/customers/api.php', { action: 'binPending', customer_id: customerId, bin_type_id: typeId }, function(obj) {
    var count = (obj.status === 'success') ? obj.pending_bins : 0;
    $('#binCurrent').text(count);
    $('#binLoadingSkeleton').hide();
    $('#binPendingCard').show();
  }).fail(function() {
    $('#binCurrent').text('0');
    $('#binLoadingSkeleton').hide();
    $('#binPendingCard').show();
  });
});

function openBinHistory(id, name) {
  $('#binHistoryModal').find('#binHistoryCustomerName').text(name);
  $('#binHistoryModal').find('#binHistoryTypeSelect').val('');
  $('#binHistoryContent').hide();
  $('#binHistoryPrompt').show();
  $('#binHistoryList').html('');
  $('#binHistoryPager').html('');
  $('#binHistoryModal').data('customerId', id).modal('show');
}

$('#binHistoryTypeSelect').on('change', function() {
  var typeId = $(this).val();
  var customerId = $('#binHistoryModal').data('customerId');

  if (!typeId) {
    $('#binHistoryContent').hide();
    $('#binHistoryPrompt').show();
    return;
  }

  $('#binHistoryPrompt').hide();
  $('#binHistoryContent').show();
  $('#binHistoryList').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i></div>');
  $('#binHistoryPager').html('');

  $.post('php/modules/customers/api.php', {
    action: 'binHistory',
    draw: 1, start: 0, length: 1000,
    order: [{column: 0, dir: 'desc'}],
    columns: [{data: 'created_at'}],
    search: {value: ''},
    customer_id: customerId,
    bin_type_id: typeId
  }, function(obj) {
    var rows = obj.aaData;

    if (!rows || rows.length === 0) {
      $('#binHistoryList').html('<div class="text-center text-muted py-5"><i class="fas fa-inbox fa-2x mb-2"></i><div>No records found</div></div>');
      return;
    }

    var perPage = 5;
    var currentPage = 1;
    var totalPages = Math.ceil(rows.length / perPage);

    function renderCards(page) {
      var start = (page - 1) * perPage;
      var pageRows = rows.slice(start, start + perPage);
      var html = '';
      pageRows.forEach(function(r) {
        var isOut = r.type === 'OUT';
        var icon       = isOut ? 'fa-arrow-up' : 'fa-arrow-down';
        var iconClass  = isOut ? 'text-warning' : 'text-success';
        var badgeClass = isOut ? 'badge-warning' : 'badge-success';
        var label      = isOut ? 'OUT' : 'IN';
        html += '<div class="card mb-2 border-0 shadow-sm">';
        html +=   '<div class="card-body py-2 px-3 d-flex align-items-center">';
        html +=     '<div class="mr-3"><i class="fas ' + icon + ' fa-lg ' + iconClass + '"></i></div>';
        html +=     '<div class="flex-fill">';
        html +=       '<div class="d-flex justify-content-between align-items-center">';
        html +=         '<span class="font-weight-bold"><span class="badge ' + badgeClass + ' mr-1">' + label + '</span>' + r.qty + ' baskets</span>';
        html +=         '<small class="text-muted">' + r.created_at + '</small>';
        html +=       '</div>';
        html +=       '<small class="text-muted"><i class="fas fa-user mr-1"></i>' + (r.user_name || '-');
        if (r.remark) html += ' &middot; <i class="fas fa-comment mr-1"></i>' + r.remark;
        html +=       '</small>';
        html +=     '</div>';
        html +=   '</div>';
        html += '</div>';
      });
      $('#binHistoryList').html(html);

      var pager = '<small class="text-muted">Showing ' + (start + 1) + '-' + Math.min(start + perPage, rows.length) + ' of ' + rows.length + '</small>';
      pager += '<ul class="pagination pagination-sm mb-0">';
      pager += '<li class="page-item ' + (page === 1 ? 'disabled' : '') + '"><a class="page-link" href="#" data-page="' + (page - 1) + '">&laquo;</a></li>';
      for (var i = 1; i <= totalPages; i++) {
        pager += '<li class="page-item ' + (i === page ? 'active' : '') + '"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
      }
      pager += '<li class="page-item ' + (page === totalPages ? 'disabled' : '') + '"><a class="page-link" href="#" data-page="' + (page + 1) + '">&raquo;</a></li>';
      pager += '</ul>';
      $('#binHistoryPager').html(pager);
    }

    renderCards(currentPage);

    $('#binHistoryPager').off('click').on('click', 'a.page-link', function(e) {
      e.preventDefault();
      var page = parseInt($(this).data('page'));
      if (page < 1 || page > totalPages) return;
      currentPage = page;
      renderCards(currentPage);
    });
  });
});
