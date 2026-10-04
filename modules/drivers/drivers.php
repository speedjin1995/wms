<?php
require_once '../../php/db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
}
else{
  $company = $_SESSION['customer'];
  $user = $_SESSION['userID'];
  $role = $_SESSION['role'];
  $companies = $db->query("SELECT * FROM companies WHERE deleted = 0 ORDER BY name ASC");

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>

<div class="content-header" style="padding-bottom: 0;">
  <div class="container-fluid"></div>
</div>

<!-- Main content -->
<section class="content page-modern">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card results-card show-dt-controls">
          <div class="card-header">
            <div class="results-header-left">
              <h3 class="results-title"><i class="fas fa-id-card mr-2"></i><?=$languageArray['drivers_code'][$language]?></h3>
            </div>
            <div class="results-header-right d-flex flex-wrap" style="gap: 0.5rem;">
              <a href="template/Driver_Template.xlsx" download class="btn btn-action btn-action-warning">
                <i class="fas fa-download"></i> <?=$languageArray['download_template_code'][$language]?>
              </a>
              <button type="button" id="uploadExcel" class="btn btn-action btn-action-success">
                <i class="fas fa-upload"></i> <?=$languageArray['upload_excel_code'][$language]?>
              </button>
              <button type="button" id="multiDeactivate" class="btn btn-action btn-action-danger">
                <i class="fas fa-trash-alt"></i> <?=$languageArray['delete_driver_code'][$language]?>
              </button>
              <button type="button" class="btn btn-action btn-action-primary" id="addDriver">
                <i class="fas fa-plus"></i> <?=$languageArray['add_driver_code'][$language]?>
              </button>
            </div>
          </div>
          <div class="card-body">
            <table id="driverTable" class="table data-table">
              <thead>
                <tr>
                  <th><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
                  <th><?=$languageArray['driver_name_code'][$language]?></th>
                  <th><?=$languageArray['driver_ic_code'][$language]?></th>
                  <th><?=$languageArray['actions_code'][$language]?></th>
                </tr>
              </thead>
            </table>
          </div><!-- /.card-body -->
        </div><!-- /.card -->
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</section><!-- /.content -->

<div class="modal fade modal-modern" id="uploadModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form role="form" id="uploadForm">
          <div class="modal-header">
            <h4 class="modal-title"><?=$languageArray['upload_excel_code'][$language]?></h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="card-body">
              <input type="file" id="fileInput">
              <button type="button" id="previewButton"><?=$languageArray['preview_data_code'][$language]?></button>
              <div id="previewTable" style="overflow: auto;"></div>
            </div>
          </div>
          <div class="modal-footer justify-content-between">
            <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
            <button type="button" class="btn btn-modern btn-modern-primary" id="uploadDriver"><?=$languageArray['submit_code'][$language]?></button>
          </div>
      </form>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>

<div class="modal fade modal-modern" id="errorModal" style="display:none">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form role="form" id="uploadForm">
          <div class="modal-header">
            <h4 class="modal-title"><?=$languageArray['error_log_code'][$language]?></h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="form-group">
                <ol id="errorList" class="text-danger mt-2" style="padding-left: 20px;"></ol>
              </div>
            </div>
          </div>
      </form>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>

<div class="modal fade modal-modern" id="addModal">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <form role="form" id="driverForm">
            <div class="modal-header">
              <h4 class="modal-title"><?=$languageArray['add_driver_code'][$language]?></h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <input type="hidden" class="form-control" id="id" name="id">
              <div class="modal-section" <?php if($role != 'SADMIN'){ echo 'style="display:none;"'; } ?>>
                <div class="form-group mb-0">
                  <label class="form-label-modern"><?=$languageArray['company_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control select2" style="width: 100%;" id="company" name="company" required>
                    <?php while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                      <option value="<?=$rowCompany['id'] ?>" <?php if($rowCompany['id'] == $company) echo 'selected'; ?>><?=$rowCompany['name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="modal-section">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group mb-0">
                      <label class="form-label-modern"><?=$languageArray['driver_name_code'][$language]?> <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="driverName" id="driverName" placeholder="<?=$languageArray['enter_driver_name_code'][$language]?>" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-0">
                      <label class="form-label-modern"><?=$languageArray['driver_ic_code'][$language]?> <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="driverIC" id="driverIC" placeholder="<?=$languageArray['enter_driver_ic_code'][$language]?>" required>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
              <button type="submit" class="btn btn-modern btn-modern-primary" name="submit" id="submitMember"><?=$languageArray['submit_code'][$language]?></button>
            </div>
        </form>
      </div>
      <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>

<!-- jQuery -->
<script>

$(function () {
  $('#selectAllCheckbox').on('change', function() {
    var checkboxes = $('#driverTable tbody input[type="checkbox"]');
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

  $("#driverTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'language': {
      'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
      'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
    },
    'ajax': {
      'url':'php/modules/drivers/api.php',
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
      { data: 'driver_name' },
      { data: 'driver_ic' },
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
          $.post('php/modules/drivers/api.php', $('#driverForm').serialize() + '&action=save', function(obj){
              
              if(obj.status === 'success'){
                $('#addModal').modal('hide');
                toastr["success"](obj.message, "Success:");
                $('#driverTable').DataTable().ajax.reload();
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

  $('#addDriver').on('click', function(){
    $('#addModal').find('#id').val("");
    $('#addModal').find('#driverName').val("");
    $('#addModal').find('#driverIC').val("");
    $('#addModal').modal('show');
    
    $('#driverForm').validate({
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

  $('#multiDeactivate').on('click', function () {
    $('#spinnerLoading').show();
    var selectedIds = []; // An array to store the selected 'id' values

    $("#driverTable tbody input[type='checkbox']").each(function () {
      if (this.checked) {
          selectedIds.push($(this).val());
      }
    });

    if (selectedIds.length > 0) {
      if (confirm('Are you sure you want to cancel these items?')) {
          $.post('php/modules/drivers/api.php', {action: 'delete', ids: selectedIds}, function(obj){
              
              if(obj.status === 'success'){
                $('#driverTable').DataTable().ajax.reload();
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
        alert("Please select at least one driver to delete.");
        $('#spinnerLoading').hide();
    }     
  });

  $('#uploadDriver').on('click', function(){
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
        url: 'php/modules/drivers/api.php?action=upload',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(obj){
            if (obj.status === 'success') {
              $('#spinnerLoading').hide();
              $('#uploadModal').modal('hide');
              $('#driverTable').DataTable().ajax.reload();
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
});

function displayPreview(data) {
  // Parse the Excel data
  var workbook = XLSX.read(data, { type: 'binary' });

  // Get the first sheet
  var sheetName = workbook.SheetNames[0];
  var sheet = workbook.Sheets[sheetName];

  // Convert the sheet to an array of objects
  var jsonData = XLSX.utils.sheet_to_json(sheet, { header: 2 });

  // Get the headers
  var headers = Object.keys(jsonData[0] || {});

  // Ensure we handle cases where there may be less than 2 columns
  while (headers.length < 2) {
      headers.push(''); // Adding empty headers to reach 2 columns
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

      for (var j = 0; j < 2 && j < headers.length; j++) {
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
  $.post('php/modules/drivers/api.php', {action: 'get', id: id}, function(obj){
      
      if(obj.status === 'success'){
          $('#addModal').find('#id').val(obj.message.id);
          $('#addModal').find('#driverName').val(obj.message.driver_name);
          $('#addModal').find('#driverIC').val(obj.message.driver_ic);
          $('#addModal').find('#company').val(obj.message.customer).trigger('change');
          $('#addModal').modal('show');
          
          $('#driverForm').validate({
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
    $.post('php/modules/drivers/api.php', {action: 'delete', ids: [id]}, function(obj){
        
        if(obj.status === 'success'){
            toastr["success"](obj.message, "Success:");
            $('#driverTable').DataTable().ajax.reload();
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
    $.post('php/modules/drivers/api.php', {action: 'reactivate', id: id}, function(obj){
        
        if(obj.status === 'success'){
            toastr["success"](obj.message, "Success:");
            $('#driverTable').DataTable().ajax.reload();
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
</script>