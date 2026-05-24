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
  $role = $_SESSION['role'];
  $products = $_SESSION['products'];
  $companies = $db->query("SELECT * FROM companies WHERE deleted = 0 ORDER BY name ASC");
  $states = $db->query("SELECT * FROM states ORDER BY states ASC");

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
			<div class="col-sm-6">
				<h1 class="m-0 text-dark"><?=$languageArray['daily_sales_setup_code'][$language]?></h1>
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
                  <div class="col-10"></div>
                  <div class="col-2">
                      <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="addDailySales"><?=$languageArray['add_code'][$language]?></button>
                  </div>
              </div>
          </div>
					<div class="card-body">
						<table id="dailySalesSetupTable" class="table table-bordered table-striped">
							<thead>
								<tr>
                  <th><?=$languageArray['modules_code'][$language]?></th>
                  <th><?=$languageArray['states_code'][$language]?></th>
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

<div class="modal fade" id="addModal">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <form role="form" id="dailySalesSetupForm">
            <div class="modal-header">
              <h4 class="modal-title"><?=$languageArray['add_code'][$language]?></h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="card-body">
                <div class="form-group">
                  <input type="hidden" class="form-control" id="id" name="id">
                </div>
                <div class="form-group" <?php if($role != 'SADMIN'){ echo 'style="display:none;"'; } ?>>
                  <label for="code"><?=$languageArray['company_code'][$language]?> *</label>
                  <select class="form-control select2" style="width: 100%;" id="company" name="company" required>
                    <?php while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                      <option value="<?=$rowCompany['id'] ?>" <?php if($rowCompany['id'] == $company) echo 'selected'; ?>><?=$rowCompany['name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="module"><?=$languageArray['modules_code'][$language]?> *</label>
                  <select class="form-control select2" style="width: 100%;" id="module" name="module" required>
                    <?php if (in_array('industrial', $products, true)) { ?>
                      <option value="industrial"><?=$languageArray['pulp_and_paste_code'][$language]?></option>
                    <?php } ?>
                    <?php if (in_array('fruits', $products, true)) { ?>
                      <option value="weighing"><?=$languageArray['weighbridge_code'][$language]?></option>
                    <?php } ?>
                    <?php if (in_array('wholesale', $products, true)) { ?>
                      <option value="wholesales"><?=$languageArray['wholesales_code'][$language]?></option>
                    <?php } ?>
                    <?php if (in_array('packing', $products, true)) { ?>
                      <option value="packing"><?=$languageArray['packing_code'][$language]?></option>
                    <?php } ?>
                    <?php if (in_array('pricing', $products, true)) { ?>
                      <option value="pricing"><?=$languageArray['pricing_code'][$language]?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="form-group mb-2">
                  <label class="font-weight-bold"><?=$languageArray['states_code'][$language]?> *</label>
                  <select class="form-control select2" id="state" name="state[]" multiple="multiple" required>
                    <?php while($rowstates=mysqli_fetch_assoc($states)){ ?>
                      <option value="<?=$rowstates['id']?>"><?=$rowstates['states']?></option>
                    <?php } ?>
                  </select>
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
  $('.select2').each(function() {
    $(this).select2({
        allowClear: true,
        placeholder: "Please Select",
        // Conditionally set dropdownParent based on the element’s location
        dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-body') : undefined
    });
  });

  $("#dailySalesSetupTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'ajax': {
      'url':'php/filterDailySalesSetup.php',
    },
    'columns': [
      { data: 'module' },
      { data: 'state' },
      { 
        data: 'id',
        render: function (data, type, row) {
          return '<div class="row"><div class="col-3"><button type="button" id="edit' + row.id + '" onclick="edit(' + row.id + ')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button></div><div class="col-3"><button type="button" id="delete' + row.id + '" onclick="deactivate(' + row.id + ')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></div></div>';
        }
      }
    ]
  });
  
  $.validator.setDefaults({
      submitHandler: function () {
          $('#spinnerLoading').show();
          $('#addModal').find('#module').prop('disabled', false);
          $.post('php/dailySalesSetup.php', $('#dailySalesSetupForm').serialize(), function(data){
              var obj = JSON.parse(data); 
              
              if(obj.status === 'success'){
                $('#addModal').modal('hide');
                toastr["success"](obj.message, "Success:");
                $('#dailySalesSetupTable').DataTable().ajax.reload();
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

  $('#addModal').on('hidden.bs.modal', function(){
    $('#addModal').find('#module').prop('disabled', false);
  });

  $('#addDailySales').on('click', function(){
    $('#addModal').find('#id').val("");
    $('#addModal').find('#module').val("").trigger('change').prop('disabled', false);
    $('#addModal').find('#state').val("").trigger('change');
    $('#addModal').modal('show');
    
    $('#dailySalesSetupForm').validate({
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
  $.post('php/getDailySalesSetup.php', {userID: id}, function(data){
      var obj = JSON.parse(data);
      
      if(obj.status === 'success'){
        $('#addModal').find('#id').val(obj.message.id);
        $('#addModal').find('#module').val(obj.message.module).trigger('change').prop('disabled', true);
        $('#addModal').find('#state').val(obj.message.state).trigger('change');
        $('#addModal').find('#company').val(obj.message.company).trigger('change');
        $('#addModal').modal('show');
        
        $('#dailySalesSetupForm').validate({
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
    $.post('php/deleteCategory.php', {userID: id}, function(data){
        var obj = JSON.parse(data);
        
        if(obj.status === 'success'){
            toastr["success"](obj.message, "Success:");
            $('#dailySalesSetupTable').DataTable().ajax.reload();
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