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
  $states = $db->query("SELECT * FROM states ORDER BY states ASC");
  $companies = $db->query("SELECT * FROM companies WHERE deleted = 0 ORDER BY name ASC");

  if ($user != 2){
    $suppliers = $db->query("SELECT * FROM supplies WHERE deleted = 0 AND customer = '$company' ORDER BY supplier_name ASC");
  }else{
    $suppliers = $db->query("SELECT * FROM supplies WHERE deleted = 0 ORDER BY supplier_name ASC");
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
      <h1 class="m-0 text-dark"><?=$languageArray['suppliers_code'][$language]?></h1>
    </div><!-- /.col -->
      </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</div><!-- /.content-header -->

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
                    <?=$languageArray['delete_supplier_code'][$language]?>
                  </button>
                </div>
                <div class="col-2">
                  <a href="template/Supplier_Template.xlsx" download>
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
                <div class="col-2">
                    <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="addSuppliers"><?=$languageArray['add_suppliers_code'][$language]?></button>
                </div>
              </div>
          </div>
					<div class="card-body">
						<table id="supplierTable" class="table table-bordered table-striped">
							<thead>
								<tr>
                  <th><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
                  <th><?=$languageArray['supplier_code_code'][$language]?></th>
                  <th><?=$languageArray['reg_no_code'][$language]?></th>
                  <th><?=$languageArray['parent_code'][$language]?></th>
									<th><?=$languageArray['supplier_name_code'][$language]?></th>
									<th><?=$languageArray['address_code'][$language]?></th>
									<th><?=$languageArray['phone_code'][$language]?></th>
									<th><?=$languageArray['pic_code'][$language]?></th>
									<th width="10%"><?=$languageArray['actions_code'][$language]?></th>
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
            <button type="button" class="btn btn-success" id="uploadSupplier"><?=$languageArray['submit_code'][$language]?></button>
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
        <form role="form" id="supplierForm">
            <div class="modal-header">
              <h4 class="modal-title"><?=$languageArray['add_suppliers_code'][$language]?></h4>
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
                  <label for="parent"><?=$languageArray['parent_code'][$language]?> </label>
                  <select class="form-control select2" style="width: 100%;" id="parent" name="parent">
                    <?php while($rowSupplier=mysqli_fetch_assoc($suppliers)){ ?>
                      <option value="<?=$rowSupplier['id'] ?>"><?=$rowSupplier['supplier_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="name"><?=$languageArray['supplier_code_code'][$language]?> *</label>
                  <input type="text" class="form-control" name="code" id="code" placeholder="<?=$languageArray['enter_supplier_code_code'][$language]?>" required>
                </div>
                <div class="form-group">
                  <label for="name"><?=$languageArray['reg_no_code'][$language]?> </label>
                  <input type="text" class="form-control" name="reg_no" id="reg_no" placeholder="<?=$languageArray['enter_reg_no_code'][$language]?>">
                </div>
                <div class="form-group">
                  <label for="name"><?=$languageArray['supplier_name_code'][$language]?> *</label>
                  <input type="text" class="form-control" name="name" id="name" placeholder="<?=$languageArray['enter_supplier_name_code'][$language]?>" required>
                </div>
                <div class="form-group"> 
                  <label for="address"><?=$languageArray['address_code'][$language]?> </label>
                  <input type="text" class="form-control" name="address" id="address" placeholder="<?=$languageArray['enter_address_code'][$language]?>" >
                </div>
                <div class="form-group"> 
                  <label for="address"><?=$languageArray['address_code'][$language]?> 2</label>
                  <input type="text" class="form-control" name="address2" id="address2" placeholder="<?=$languageArray['enter_address_code'][$language]?> 2">
                </div>
                <div class="form-group"> 
                  <label for="address"><?=$languageArray['address_code'][$language]?> 3</label>
                  <input type="text" class="form-control" name="address3" id="address3" placeholder="<?=$languageArray['enter_address_code'][$language]?> 3">
                </div>
                <div class="form-group"> 
                  <label for="address"><?=$languageArray['address_code'][$language]?> 4</label>
                  <input type="text" class="form-control" name="address4" id="address4" placeholder="<?=$languageArray['enter_address_code'][$language]?> 4">
                </div>
                <div class="form-group">
                  <label><?=$languageArray['states_code'][$language]?></label>
                  <select class="form-control select2" style="width: 100%;" id="states" name="states">
                    <option selected="selected">-</option>
                    <?php while($rowCustomer2=mysqli_fetch_assoc($states)){ ?>
                      <option value="<?=$rowCustomer2['id'] ?>"><?=$rowCustomer2['states'] ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="phone"><?=$languageArray['phone_code'][$language]?> </label>
                  <input type="text" class="form-control" name="phone" id="phone" placeholder="<?=$languageArray['enter_phone_code'][$language]?>" >
                </div>
                <div class="form-group"> 
                  <label for="email"><?=$languageArray['pic_code'][$language]?> </label>
                  <input type="text" class="form-control" id="email" name="email" placeholder="<?=$languageArray['enter_pic_code'][$language]?>" >
                </div>
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
    var checkboxes = $('#supplierTable tbody input[type="checkbox"]');
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

  $("#supplierTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'ajax': {
        'url':'php/loadSupplier.php'
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
      { data: 'supplier_code' },
      { data: 'reg_no' },
      { data: 'parent' },
      { data: 'supplier_name' },
      { data: 'supplier_address' },
      { data: 'supplier_phone' },
      { data: 'pic' },
      { 
          data: 'id',
          render: function ( data, type, row ) {
              return '<div class="row"><div class="col-3"><button type="button" id="edit'+data+'" onclick="edit('+data+')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button></div><div class="col-3"><button type="button" id="deactivate'+data+'" onclick="deactivate('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></div></div>';
          }
      }
    ],
    "rowCallback": function( row, data, index ) {
      //$('td', row).css('background-color', '#E6E6FA');
    },
  });
    
  $.validator.setDefaults({
      submitHandler: function () {
          //$('#spinnerLoading').show();
          $.post('php/suppliers.php', $('#supplierForm').serialize(), function(data){
              var obj = JSON.parse(data); 
              
              if(obj.status === 'success'){
                $('#addModal').modal('hide');
                toastr["success"](obj.message, "Success:");
                $('#supplierTable').DataTable().ajax.reload();
                // Refresh the parent dropdown
                $.get('php/getSuppliers.php', function(data) {
                  var suppliers = JSON.parse(data);
                  $('#parent').empty().append('<option value="">Please Select</option>');
                  suppliers.forEach(function(supplier) {
                    $('#parent').append('<option value="' + supplier.id + '">' + supplier.supplier_name + '</option>');
                  });
                });
                //$('#spinnerLoading').hide();
              }
              else if(obj.status === 'failed'){
                  toastr["error"](obj.message, "Failed:");
                  //$('#spinnerLoading').hide();
              }
              else{
                  toastr["error"]("Something wrong when edit", "Failed:");
                  //$('#spinnerLoading').hide();
              }
          });
      }
  });

  $('#addSuppliers').on('click', function(){
      $('#addModal').find('#id').val("");
      $('#addModal').find('#code').val("");
      $('#addModal').find('#reg_no').val("");
      $('#addModal').find('#name').val("");
      $('#addModal').find('#address').val("");
      $('#addModal').find('#address2').val("");
      $('#addModal').find('#address3').val("");
      $('#addModal').find('#address4').val("");
      $('#addModal').find('#states').val("");
      $('#addModal').find('#phone').val("");
      $('#addModal').find('#email').val("");
      $('#addModal').find('#parent').val("").trigger('change');
      $('#addModal').modal('show');
      
      $('#supplierForm').validate({
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

$('#uploadSupplier').on('click', function(){
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
      url: 'php/uploadSupplier.php',
      type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(data),
      success: function(response) {
          var obj = JSON.parse(response);
          if (obj.status === 'success') {
            $('#spinnerLoading').hide();
            $('#uploadModal').modal('hide');
            $('#supplierTable').DataTable().ajax.reload();
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
  var selectedIds = [];

  $("#supplierTable tbody input[type='checkbox']").each(function () {
    if (this.checked) {
        selectedIds.push($(this).val());
    }
  });

  if (selectedIds.length > 0) {
    if (confirm('Are you sure you want to cancel these items?')) {
        $.post('php/deleteSupplier.php', {userID: selectedIds, type: 'MULTI'}, function(data){
            var obj = JSON.parse(data);
            
            if(obj.status === 'success'){
              $('#supplierTable').DataTable().ajax.reload();
              $('#spinnerLoading').hide();
            }
            else if(obj.status === 'failed'){
              $('#spinnerLoading').hide();
            }
            else{
              $('#spinnerLoading').hide();
            }
        });
    } else {
      $('#spinnerLoading').hide();
    }
  } 
  else {
      alert("Please select at least one supplier to delete.");
      $('#spinnerLoading').hide();
  }     
});

function displayPreview(data) {
  // Parse the Excel data
  var workbook = XLSX.read(data, { type: 'binary' });

  // Get the first sheet
  var sheetName = workbook.SheetNames[0];
  var sheet = workbook.Sheets[sheetName];

  // Convert the sheet to an array of objects
  var jsonData = XLSX.utils.sheet_to_json(sheet, { header: 11 });

  // Get the headers
  var headers = Object.keys(jsonData[0] || {});

  // Ensure we handle cases where there may be less than 11 columns
  while (headers.length < 11) {
      headers.push(''); // Adding empty headers to reach 11 columns
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

      for (var j = 0; j < 11 && j < headers.length; j++) {
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
  $.post('php/getSupplier.php', {userID: id}, function(data){
      var obj = JSON.parse(data);
      
      if(obj.status === 'success'){
          $('#addModal').find('#id').val(obj.message.id);
          $('#addModal').find('#code').val(obj.message.supplier_code);
          $('#addModal').find('#reg_no').val(obj.message.reg_no);
          $('#addModal').find('#name').val(obj.message.supplier_name);
          $('#addModal').find('#address').val(obj.message.supplier_address);
          $('#addModal').find('#address2').val(obj.message.supplier_address2);
          $('#addModal').find('#address3').val(obj.message.supplier_address3);
          $('#addModal').find('#address4').val(obj.message.supplier_address4);
          $('#addModal').find('#states').val(obj.message.states).trigger('change');
          $('#addModal').find('#phone').val(obj.message.supplier_phone);
          $('#addModal').find('#email').val(obj.message.pic);
          $('#addModal').find('#company').val(obj.message.customer).trigger('change');
          $('#addModal').find('#parent').val(obj.message.parent).trigger('change');
          $('#addModal').modal('show');
          
          $('#supplierForm').validate({
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
        alert(obj.message);
        toastr["error"](obj.message, "Failed:");
      }
      else{
        alert(obj.message);
        toastr["error"]("Something wrong when activate", "Failed:");
      }
      $('#spinnerLoading').hide();
  });
}

function deactivate(id){
    if (confirm('Are you sure you want to delete this items?')) {
        //$('#spinnerLoading').show();
        $.post('php/deleteSupplier.php', {userID: id}, function(data){
            var obj = JSON.parse(data);
            
            if(obj.status === 'success'){
                toastr["success"](obj.message, "Success:");
                $('#supplierTable').DataTable().ajax.reload();
                //$('#spinnerLoading').hide();
            }
            else if(obj.status === 'failed'){
                toastr["error"](obj.message, "Failed:");
                //$('#spinnerLoading').hide();
            }
            else{
                toastr["error"]("Something wrong when activate", "Failed:");
                //$('#spinnerLoading').hide();
            }
        });
    }
}

function reactivate(id){
  if (confirm('Are you sure you want to reactivate this items?')) {
    //$('#spinnerLoading').show();
    $.post('php/reactivateSupplier.php', {userID: id}, function(data){
        var obj = JSON.parse(data);
        
        if(obj.status === 'success'){
            toastr["success"](obj.message, "Success:");
            $('#supplierTable').DataTable().ajax.reload();
            //$('#spinnerLoading').hide();
        }
        else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
            //$('#spinnerLoading').hide();
        }
        else{
            toastr["error"]("Something wrong when activate", "Failed:");
            //$('#spinnerLoading').hide();
        }
    });
  }
}
</script>