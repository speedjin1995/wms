$(function () {
  $('#selectAllCheckbox').on('change', function() {
    var checkboxes = $('#vehicleTable tbody input[type="checkbox"]');
    checkboxes.prop('checked', $(this).prop('checked')).trigger('change');
  });

  $('.select2').each(function() {
    $(this).select2({
        allowClear: true,
        placeholder: "Please Select",
        // Conditionally set dropdownParent based on the element’s location
        dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal') : undefined
    });
  });

  $("#vehicleTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'language': vehiclesTableLanguage,
    'ajax': {
      'url':'php/modules/vehicles/api.php',
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
      { data: 'veh_number' },
      { data: 'vehicle_weight' },
      { data: 'driver_name' },
      // { data: 'attandence_1' },
      // { data: 'attandence_2' },
      { 
        data: 'deleted',
        render: function (data, type, row) {
          if (data == 0) {
            return '<div class="d-flex" style="gap:4px;"><button type="button" id="edit' + row.id + '" onclick="edit(' + row.id + ')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button><button type="button" id="delete' + row.id + '" onclick="deactivate(' + row.id + ')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button></div>';
          } 
          else{
            return '<button type="button" id="reactivate' + row.id + '" onclick="reactivate(' + row.id + ')" class="btn btn-sm btn-outline-warning">Reactivate</button>';
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
          $('#spinnerLoading').show();
          $.post('php/modules/vehicles/api.php', $('#vehicleForm').serialize() + '&action=save', function(obj){
              
              if(obj.status === 'success'){
                $('#addModal').modal('hide');
                toastr["success"](obj.message, "Success:");
                $('#vehicleTable').DataTable().ajax.reload();
                $('#spinnerLoading').hide();
              }
              else if(obj.status === 'failed'){
                toastr["error"](obj.message, "Failed:");
                $('#spinnerLoading').hide();
              }
              else{
                toastr["error"]("Something wrong when edit", "Failed:");
                $('#spinnerLoading').hide();
              }
          });
      }
  });

  $('#addVehicle').on('click', function(){
    $('#addModal').find('#id').val("");
    $('#addModal').find('#vehicleNumber').val("");
    $('#addModal').find('#vehicleWeight').val("");
    $('#addModal').find('#driver').val("").trigger('change');
    $('#addModal').find('#attendance1').val("");
    $('#addModal').find('#attendance2').val("");
    $('#addModal').modal('show');
    
    $('#vehicleForm').validate({
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

  $('#uploadVehicle').on('click', function(){
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
        url: 'php/modules/vehicles/api.php?action=upload',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(obj){
            if (obj.status === 'success') {
              $('#spinnerLoading').hide();
              $('#uploadModal').modal('hide');
              $('#vehicleTable').DataTable().ajax.reload();
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

    $("#vehicleTable tbody input[type='checkbox']").each(function () {
      if (this.checked) {
          selectedIds.push($(this).val());
      }
    });

    if (selectedIds.length > 0) {
      if (confirm('Are you sure you want to cancel these items?')) {
          $.post('php/modules/vehicles/api.php', {action: 'delete', ids: selectedIds}, function(obj){
              
              if(obj.status === 'success'){
                $('#vehicleTable').DataTable().ajax.reload();
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
        alert("Please select at least one vehicle to delete.");
        $('#spinnerLoading').hide();
    }     
  });
});

function displayPreview(data) {
    // Parse the Excel data
    var workbook = XLSX.read(data, { type: 'binary' });

    // Get the first sheet
    var sheetName = workbook.SheetNames[0];
    var sheet = workbook.Sheets[sheetName];

    // Convert the sheet to an array of arrays
    var jsonData = XLSX.utils.sheet_to_json(sheet, { header: 1 });

    // Get the headers from first row
    var headers = jsonData[0] || [];

    // Ensure we handle cases where there may be less than 3 columns
    while (headers.length < 3) {
        headers.push(''); // Adding empty headers to reach 3 columns
    }

    // Create HTML table headers
    var htmlTable = '<table style="width:50%;"><thead><tr>';
    for (var h = 0; h < 3; h++) {
        htmlTable += '<th>' + (headers[h] || '') + '</th>';
    }
    htmlTable += '</tr></thead><tbody>';

    // Iterate over the data and create table rows (skip header row)
    for (var i = 1; i < jsonData.length; i++) {
        htmlTable += '<tr>';
        var rowData = jsonData[i] || [];

        for (var j = 0; j < 3; j++) {
            var cellData = rowData[j];
            var formattedData = cellData;

            // Check if cellData is a valid Excel date serial number and format it to DD/MM/YYYY
            if (typeof cellData === 'number' && cellData > 0) {
                var excelDate = XLSX.SSF.parse_date_code(cellData);
            }

            htmlTable += '<td><input type="text" id="'+(headers[j] || '').replace(/[^a-zA-Z0-9]/g, '')+(i-1)+'" name="'+(headers[j] || '').replace(/[^a-zA-Z0-9]/g, '')+'['+(i-1)+']" value="' + (formattedData == null || formattedData === undefined ? '' : formattedData) + '" /></td>';
        }
        htmlTable += '</tr>';
    }

    htmlTable += '</tbody></table>';

    var previewTable = document.getElementById('previewTable');
    previewTable.innerHTML = htmlTable;
}

function edit(id){
  $('#spinnerLoading').show();
  $.post('php/modules/vehicles/api.php', {action: 'get', id: id}, function(obj){
      
      if(obj.status === 'success'){
          $('#addModal').find('#id').val(obj.message.id);
          $('#addModal').find('#vehicleNumber').val(obj.message.veh_number);
          $('#addModal').find('#vehicleWeight').val(obj.message.vehicle_weight);
          $('#addModal').find('#driver').val(obj.message.driver).trigger('change');
          $('#addModal').find('#attendance1').val(obj.message.attandence_1);
          $('#addModal').find('#attendance2').val(obj.message.attandence_2);
          $('#addModal').find('#company').val(obj.message.customer).trigger('change');
          $('#addModal').modal('show');
          
          $('#vehicleForm').validate({
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
    $.post('php/modules/vehicles/api.php', {action: 'delete', ids: [id]}, function(obj){
        
        if(obj.status === 'success'){
            toastr["success"](obj.message, "Success:");
            $('#vehicleTable').DataTable().ajax.reload();
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
    $.post('php/modules/vehicles/api.php', {action: 'reactivate', id: id}, function(obj){
        
        if(obj.status === 'success'){
            toastr["success"](obj.message, "Success:");
            $('#vehicleTable').DataTable().ajax.reload();
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
