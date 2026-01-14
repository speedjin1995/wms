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
    $customers = $db->query("SELECT * FROM customers WHERE deleted = 0 AND customer = '$company' ORDER BY customer_name ASC");
  }else{
    $customers = $db->query("SELECT * FROM customers WHERE deleted = 0 ORDER BY customer_name ASC");
  }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
			<div class="col-sm-6">
				<h1 class="m-0 text-dark">Customers</h1>
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
                  <div class="col-9"></div>
                  <!-- <div class="col-2">
                      <input type="file" id="fileInput" accept=".xlsx, .xls" />
                  </div>
                  <div class="col-2">
                      <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="importExcelbtn">Import Excel</button>
                  </div>                             -->
                  <div class="col-3">
                      <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="addCustomers">Add Customers</button>
                  </div>
              </div>
          </div>
					<div class="card-body">
						<table id="customerTable" class="table table-bordered table-striped">
							<thead>
								<tr>
                  <th>Code</th>
                  <th>Reg No.</th>
									<th>Name</th>
									<th>Address</th>
									<th>Phone</th>
									<th>PIC</th>
									<th width="10%">Actions</th>
								</tr>
							</thead>
						</table>
					</div><!-- /.card-body -->
				</div><!-- /.card -->
			</div><!-- /.col -->
		</div><!-- /.row -->
	</div><!-- /.container-fluid -->
</section><!-- /.content -->

<div class="modal fade" id="addModal">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <form role="form" id="customerForm">
            <div class="modal-header">
              <h4 class="modal-title">Add Customers</h4>
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
                  <label for="code">Company *</label>
                  <select class="form-control select2" style="width: 100%;" id="company" name="company" required>
                    <?php while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                      <option value="<?=$rowCompany['id'] ?>" <?php if($rowCompany['id'] == $company) echo 'selected'; ?>><?=$rowCompany['name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="form-group"> 
                  <label for="parent">Parent </label>
                  <select class="form-control select2" style="width: 100%;" id="parent" name="parent">
                    <?php while($rowCustomer=mysqli_fetch_assoc($customers)){ ?>
                      <option value="<?=$rowCustomer['id'] ?>"><?=$rowCustomer['customer_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="code">Customer Code *</label>
                  <input type="text" class="form-control" name="code" id="code" placeholder="Enter Customer Code" maxlength="10" required>
                </div>
                <div class="form-group">
                  <label for="name">Reg No. </label>
                  <input type="text" class="form-control" name="reg_no" id="reg_no" placeholder="Enter Registration No">
                </div>
                <div class="form-group">
                  <label for="name">Customer Name *</label>
                  <input type="text" class="form-control" name="name" id="name" placeholder="Enter Customer Name" required>
                </div>
                <div class="form-group"> 
                  <label for="address">Address </label>
                  <input type="text" class="form-control" name="address" id="address" placeholder="Enter  Address">
                </div>
                <div class="form-group"> 
                  <label for="address">Address 2</label>
                  <input type="text" class="form-control" name="address2" id="address2" placeholder="Enter  Address">
                </div>
                <div class="form-group"> 
                  <label for="address">Address 3</label>
                  <input type="text" class="form-control" name="address3" id="address3" placeholder="Enter  Address">
                </div>
                <div class="form-group"> 
                  <label for="address">Address 4</label>
                  <input type="text" class="form-control" name="address4" id="address4" placeholder="Enter  Address">
                </div>
                <div class="form-group">
                  <label>States</label>
                  <select class="form-control select2" style="width: 100%;" id="states" name="states">
                    <option selected="selected">-</option>
                    <?php while($rowCustomer2=mysqli_fetch_assoc($states)){ ?>
                      <option value="<?=$rowCustomer2['id'] ?>"><?=$rowCustomer2['states'] ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="phone">Phone </label>
                  <input type="text" class="form-control" name="phone" id="phone" placeholder="01x-xxxxxxx">
                </div>
                <div class="form-group"> 
                  <label for="email">PIC </label>
                  <input type="text" class="form-control" id="email" name="email" placeholder="Enter your PIC">
                </div>
              </div>
            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary" name="submit" id="submitMember">Submit</button>
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
  $('.select2').each(function() {
    $(this).select2({
        allowClear: true,
        placeholder: "Please Select",
        // Conditionally set dropdownParent based on the element’s location
        dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-body') : undefined
    });
  });

  $("#customerTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'ajax': {
      'url':'php/loadCustomers.php',
    },
    'columns': [
      { data: 'customer_code' },
      { data: 'reg_no' },
      { data: 'customer_name' },
      { data: 'customer_address' },
      { data: 'customer_phone' },
      { data: 'pic' },
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
    ],
    "rowCallback": function( row, data, index ) {

        //$('td', row).css('background-color', '#E6E6FA');
    },        
  });
  
  $.validator.setDefaults({
      submitHandler: function () {
          $('#spinnerLoading').show();
          $.post('php/customers.php', $('#customerForm').serialize(), function(data){
              var obj = JSON.parse(data); 
              
              if(obj.status === 'success'){
                $('#addModal').modal('hide');
                toastr["success"](obj.message, "Success:");
                $('#customerTable').DataTable().ajax.reload();
                
                // Refresh the parent dropdown
                $.get('php/getCustomers.php', function(customers) {
                  $('#parent').empty().append('<option value="">Please Select</option>');
                  customers.forEach(function(customer) {
                    $('#parent').append('<option value="' + customer.id + '">' + customer.customer_name + '</option>');
                  });
                });
                
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

  $('#addCustomers').on('click', function(){
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

  document.getElementById('fileInput').addEventListener('change', function (e) {
    const file = e.target.files[0];
    const reader = new FileReader();

    reader.onload = function (e) {
      const data = new Uint8Array(e.target.result);
      const workbook = XLSX.read(data, { type: 'array' });

      const sheetName = workbook.SheetNames[1];
      const sheet = workbook.Sheets[sheetName];
      jsonData = XLSX.utils.sheet_to_json(sheet);
      console.log(jsonData);
    };
    reader.readAsArrayBuffer(file);
  });

  $('#importExcelbtn').on('click', function(){
        jsonData.forEach(function(row) {
            $.ajax({
                url: 'php/importExcelCustomer.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(row),
                success: function(response) {
                    debugger;
                    var obj = JSON.parse(response); 
                    
                    if(obj.status === 'success'){
                        $('#addModal').modal('hide');
                        toastr["success"](obj.message, "Success:");
                        $('#customerTable').DataTable().ajax.reload();
                        $('#spinnerLoading').hide();
                    }
                    else if(obj.status === 'failed'){
                        toastr["error"](obj.message, "Failed:");
                        $('#spinnerLoading').hide();
                    }
                    else{
                        toastr["error"]("Something wrong when import", "Failed:");
                        $('#spinnerLoading').hide();
                    }
                },
                error: function(error) {
                    toastr["error"](obj.message, "Failed:");
                    $('#spinnerLoading').hide();
                }
            })
        })
    });
});

function edit(id){
  $('#spinnerLoading').show();
  $.post('php/getCustomer.php', {userID: id}, function(data){
      var obj = JSON.parse(data);
      
      if(obj.status === 'success'){
          $('#addModal').find('#id').val(obj.message.id);
          $('#addModal').find('#code').val(obj.message.customer_code);
          $('#addModal').find('#reg_no').val(obj.message.reg_no);
          $('#addModal').find('#name').val(obj.message.customer_name);
          $('#addModal').find('#address').val(obj.message.customer_address);
          $('#addModal').find('#address2').val(obj.message.customer_address2);
          $('#addModal').find('#address3').val(obj.message.customer_address3);
          $('#addModal').find('#address4').val(obj.message.customer_address4);
          $('#addModal').find('#states').val(obj.message.states).trigger('change');
          $('#addModal').find('#phone').val(obj.message.customer_phone);
          $('#addModal').find('#email').val(obj.message.pic);
          $('#addModal').find('#company').val(obj.message.customer).trigger('change');
          $('#addModal').find('#parent').val(obj.message.parent).trigger('change');
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
    $.post('php/deleteCustomer.php', {userID: id}, function(data){
        var obj = JSON.parse(data);
        
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
    $.post('php/reactivateCustomer.php', {userID: id}, function(data){
        var obj = JSON.parse(data);
        
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
</script>