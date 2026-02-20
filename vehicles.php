<?php
require_once 'php/db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
}
else{
  $company = $_SESSION['customer'];
  $user = $_SESSION['userID'];
  $companies = $db->query("SELECT * FROM companies WHERE deleted = 0 ORDER BY name ASC");

  if ($user != 2){
    $drivers = $db->query("SELECT * FROM drivers WHERE deleted = 0 AND customer = '".$company."' ORDER BY driver_name ASC");
  }
  else{
    $drivers = $db->query("SELECT * FROM drivers WHERE deleted = 0 ORDER BY driver_name ASC");
  }

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
			<div class="col-sm-6">
				<h1 class="m-0 text-dark"><?=$languageArray['vehicles_code'][$language]?></h1>
			</div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
	<div class="container-fluid">
        <div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-header">
              <div class="row">
                  <div class="col-4"></div>
                  <div class="col-2">
                    <button type="button" id="multiDeactivate" class="btn btn-block bg-gradient-danger btn-sm">
                      <?=$languageArray['delete_vehicle_code'][$language]?>
                    </button>
                  </div>                  <div class="col-2">
                    <a href="template/Vehicle_Template.xlsx" download>
                      <button type="button" class="btn btn-block bg-gradient-info btn-sm">
                        <?=$languageArray['download_template_code'][$language]?>
                      </button>
                    </a>
                  </div>
                  <div class="col-2">
                    <button type="button" id="uploadExcel" class="btn btn-block bg-gradient-success btn-sm">
                      <?=$languageArray['upload_excel_code'][$language]?>
                    </button>
                  </div>
                  <!-- <div class="col-2">
                      <input type="file" id="fileInput" accept=".xlsx, .xls" />
                  </div>
                  <div class="col-2">
                      <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="importExcelbtn">Import Excel</button>
                  </div>                             -->
                  <div class="col-2">
                      <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="addVehicle"><?=$languageArray['add_vehicle_code'][$language]?></button>
                  </div>
              </div>
          </div>
					<div class="card-body">
						<table id="vehicleTable" class="table table-bordered table-striped">
							<thead>
								<tr>
                  <th><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
                  <th><?=$languageArray['vehicle_number_code'][$language]?></th>
                  <th><?=$languageArray['vehicle_weight_code'][$language]?> (Kg)</th>
                  <th><?=$languageArray['driver_code'][$language]?></th>
									<!-- <th>Attendence 1</th>
									<th>Attendence 2</th> -->
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

<div class="modal fade" id="uploadModal">
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
            <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
            <button type="button" class="btn btn-success" id="uploadVehicle"><?=$languageArray['submit_code'][$language]?></button>
          </div>
      </form>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>

<div class="modal fade" id="errorModal" style="display:none">
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

<div class="modal fade" id="addModal">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <form role="form" id="vehicleForm">
            <div class="modal-header">
              <h4 class="modal-title"><?=$languageArray['add_vehicle_code'][$language]?></h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="card-body">
                <div class="form-group">
                  <input type="hidden" class="form-control" id="id" name="id">
                </div>
                <div class="form-group" <?php if($user != 2){ echo 'style="display:none;"'; } ?>>
                  <label for="code"><?=$languageArray['company_code'][$language]?> *</label>
                  <select class="form-control select2" style="width: 100%;" id="company" name="company" required>
                    <?php while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                      <option value="<?=$rowCompany['id'] ?>" <?php if($rowCompany['id'] == $company) echo 'selected'; ?>><?=$rowCompany['name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="vehicleNumber"><?=$languageArray['vehicle_number_code'][$language]?> *</label>
                  <input type="text" class="form-control" name="vehicleNumber" id="vehicleNumber" placeholder="<?=$languageArray['enter_vehicle_number_code'][$language]?>" required>
                </div>
                <div class="form-group">
                  <label for="vehicleWeight"><?=$languageArray['vehicle_weight_code'][$language]?> (Kg)</label>
                  <input type="text" class="form-control" name="vehicleWeight" id="vehicleWeight" placeholder="<?=$languageArray['enter_vehicle_weight_code'][$language]?>">
                </div>
                <div class="form-group">
                  <label for="name"><?=$languageArray['driver_code'][$language]?></label>
                  <select class="form-control select2" style="width: 100%;" id="driver" name="driver">
                    <?php while($rowDriver=mysqli_fetch_assoc($drivers)){ ?>
                      <option value="<?=$rowDriver['id'] ?>"><?=$rowDriver['driver_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
                <!-- <div class="form-group">
                  <label for="name">Attendence 1</label>
                  <input type="text" class="form-control" name="attendence1" id="attendence1" placeholder="Enter Attendence 1">
                </div>
                <div class="form-group">
                  <label for="name">Attendence 2</label>
                  <input type="text" class="form-control" name="attendence2" id="attendence2" placeholder="Enter Attendence 2">
                </div> -->
              </div>
            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-danger" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
              <button type="submit" class="btn btn-primary" name="submit" id="submitMember"><?=$languageArray['submit_code'][$language]?></button>
            </div>
        </form>
      </div>
      <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/jquery-validation/jquery.validate.min.js"></script>
<!-- Bootstrap -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE -->
<script src="dist/js/adminlte.js"></script>
<!-- OPTIONAL SCRIPTS -->
<script src="plugins/select2/js/select2.full.min.js"></script>
<script src="plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js"></script>
<script src="plugins/moment/moment.min.js"></script>
<script src="plugins/inputmask/jquery.inputmask.min.js"></script>
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="plugins/daterangepicker/daterangepicker.js"></script>
<script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<script src="plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<script src="plugins/chart.js/Chart.min.js"></script>
<script src="plugins/daterangepicker/daterangepicker.js"></script>
<script>

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
        dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-body') : undefined
    });
  });

  $("#vehicleTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'ajax': {
      'url':'php/loadVehicles.php',
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
            return '<div class="row"><div class="col-3"><button type="button" id="edit' + row.id + '" onclick="edit(' + row.id + ')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button></div><div class="col-3"><button type="button" id="delete' + row.id + '" onclick="deactivate(' + row.id + ')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></div></div>';
          } 
          else{
            return '<button type="button" id="reactivate' + row.id + '" onclick="reactivate(' + row.id + ')" class="btn btn-warning btn-sm">Reactivate</button>';
          }
        }
      }
    ]     
  });
  
  $.validator.setDefaults({
      submitHandler: function () {
          $('#spinnerLoading').show();
          $.post('php/vehicles.php', $('#vehicleForm').serialize(), function(data){
              var obj = JSON.parse(data); 
              
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
        url: 'php/uploadVehicle.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            var obj = JSON.parse(response);
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
          $.post('php/deleteVehicle.php', {userID: selectedIds, type: 'MULTI'}, function(data){
              var obj = JSON.parse(data);
              
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

  // document.getElementById('fileInput').addEventListener('change', function (e) {
  //   const file = e.target.files[0];
  //   const reader = new FileReader();

  //   reader.onload = function (e) {
  //     const data = new Uint8Array(e.target.result);
  //     const workbook = XLSX.read(data, { type: 'array' });

  //     const sheetName = workbook.SheetNames[1];
  //     const sheet = workbook.Sheets[sheetName];
  //     jsonData = XLSX.utils.sheet_to_json(sheet);
  //     console.log(jsonData);
  //   };
  //   reader.readAsArrayBuffer(file);
  // });

  // $('#importExcelbtn').on('click', function(){
  //     jsonData.forEach(function(row) {
  //         $.ajax({
  //             url: 'php/importExcelCustomer.php',
  //             type: 'POST',
  //             contentType: 'application/json',
  //             data: JSON.stringify(row),
  //             success: function(response) {
  //                 debugger;
  //                 var obj = JSON.parse(response); 
                  
  //                 if(obj.status === 'success'){
  //                     $('#addModal').modal('hide');
  //                     toastr["success"](obj.message, "Success:");
  //                     $('#customerTable').DataTable().ajax.reload();
  //                     $('#spinnerLoading').hide();
  //                 }
  //                 else if(obj.status === 'failed'){
  //                     toastr["error"](obj.message, "Failed:");
  //                     $('#spinnerLoading').hide();
  //                 }
  //                 else{
  //                     toastr["error"]("Something wrong when import", "Failed:");
  //                     $('#spinnerLoading').hide();
  //                 }
  //             },
  //             error: function(error) {
  //                 toastr["error"](obj.message, "Failed:");
  //                 $('#spinnerLoading').hide();
  //             }
  //         })
  //     })
  // });
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
  $.post('php/getVehicle.php', {userID: id}, function(data){
      var obj = JSON.parse(data);
      
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
    $.post('php/deleteVehicle.php', {userID: id}, function(data){
        var obj = JSON.parse(data);
        
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
    $.post('php/reactivateVehicle.php', {userID: id}, function(data){
        var obj = JSON.parse(data);
        
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
</script>