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
  $states = $db->query("SELECT * FROM states");
  $companies = $db->query("SELECT * FROM companies WHERE deleted = 0");

  if ($user != 2){
    $suppliers = $db->query("SELECT * FROM supplies WHERE deleted = 0 AND customer = '$company'");
  }else{
    $suppliers = $db->query("SELECT * FROM supplies WHERE deleted = 0");
  }
}
?>

<div class="content-header">
  <div class="container-fluid">
      <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0 text-dark">Suppliers</h1>
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
                  <div class="col-9"></div>
                  <div class="col-3">
                      <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="addSuppliers">Add Suppliers</button>
                  </div>
              </div>
          </div>
					<div class="card-body">
						<table id="supplierTable" class="table table-bordered table-striped">
							<thead>
								<tr>
                  <th>Code </th>
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
        <form role="form" id="supplierForm">
            <div class="modal-header">
              <h4 class="modal-title">Add Suppliers</h4>
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
                    <?php while($rowSupplier=mysqli_fetch_assoc($suppliers)){ ?>
                      <option value="<?=$rowSupplier['id'] ?>"><?=$rowSupplier['supplier_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="name">Supplier Code *</label>
                  <input type="text" class="form-control" name="code" id="code" placeholder="Enter Supplier Code" required>
                </div>
                <div class="form-group">
                  <label for="name">Reg No. </label>
                  <input type="text" class="form-control" name="reg_no" id="reg_no" placeholder="Enter Registration No">
                </div>
                <div class="form-group">
                  <label for="name">Supplier Name *</label>
                  <input type="text" class="form-control" name="name" id="name" placeholder="Enter Supplier Name" required>
                </div>
                <div class="form-group"> 
                  <label for="address">Address </label>
                  <input type="text" class="form-control" name="address" id="address" placeholder="Enter  Address" >
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
                  <input type="text" class="form-control" name="phone" id="phone" placeholder="Enter Phone" >
                </div>
                <div class="form-group"> 
                  <label for="email">PIC </label>
                  <input type="text" class="form-control" id="email" name="email" placeholder="Enter your pic" >
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
        { data: 'supplier_code' },
        { data: 'reg_no' },
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