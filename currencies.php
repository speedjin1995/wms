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
  $companies = $db->query("SELECT * FROM companies WHERE deleted = 0 ORDER BY name ASC");

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>

<style>
.star-default { font-size: 1.4rem; cursor: pointer; color: #ccc; display: block; text-align: center; }
.star-default.active { color: #f5a623; }
.star-default:hover { color: #f5a623; }
</style>

<div class="content-header">
    <div class="container-fluid">
        <div>
          <div class="col-sm-6">
            <h1><?=$languageArray['currency_code'][$language]?></h1>
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
                <div class="col-8"></div>
                <div class="col-2">
                  <button type="button" id="multiDeactivate" class="btn btn-block btn-sm custom-delete-btn">
                    <?=$languageArray['delete_currencies_code'][$language]?>
                  </button>
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-block btn-sm custom-add-btn" id="addCurrency"><?=$languageArray['add_currency_code'][$language]?></button>
                </div>
            </div>
          </div>
					<div class="card-body">
						<table id="currencyTable" class="table table-bordered table-striped">
							<thead>
								<tr>
                  <th style="width:40px"><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
                  <th style="width:40px"><?=$languageArray['default_code'][$language]?></th>
                  <th><?=$languageArray['currency_code'][$language]?></th>
                  <th><?=$languageArray['description_code'][$language]?></th>
                  <!-- <th><?=$languageArray['rate_code'][$language]?></th> -->
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
        <form role="form" id="currencyForm" class="custom-model-extend-form">
            <div class="modal-header">
              <h4 class="modal-title" id="modalTitle"><?=$languageArray['add_currency_code'][$language]?></h4>
              <button type="button" class="close custom-close-btn-icon" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="card-body">
                <div class="form-group">
                  <input type="hidden" class="form-control" id="id" name="id">
                </div>
                <div class="form-group" <?php if($role != 'SADMIN'){ echo 'style="display:none;"'; } ?>>
                  <label for="company">Company *</label>
                  <select class="form-control select2" style="width: 100%;" id="company" name="company" required>
                    <?php while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                      <option value="<?=$rowCompany['id'] ?>" <?php if($rowCompany['id'] == $company) echo 'selected'; ?>><?=$rowCompany['name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="currency"><?=$languageArray['currency_code'][$language]?> *</label>
                  <input type="text" class="form-control" name="currency" id="currency" placeholder="<?=$languageArray['enter_currency_name_code'][$language]?>" required>
                </div>
                <div class="form-group">
                  <label for="description"><?=$languageArray['description_code'][$language]?></label>
                  <textarea class="form-control" name="description" id="description" rows="3" placeholder="<?=$languageArray['enter_currency_description_code'][$language]?>"></textarea>
                </div>
                <div class="form-group" style="display:none">
                  <label for="rate"><?=$languageArray['rate_code'][$language]?></label>
                  <input type="text" class="form-control" name="rate" id="rate" placeholder="<?=$languageArray['enter_currency_rate_code'][$language]?>" value="1">
                </div>
              </div>
            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-danger custom-delete-btn" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
              <button type="submit" class="btn btn-primary custom-add-btn" name="submit"><?=$languageArray['submit_code'][$language]?></button>
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
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script>

$(function () {
  $('#selectAllCheckbox').on('change', function() {
    var checkboxes = $('#currencyTable tbody input[type="checkbox"]');
    checkboxes.prop('checked', $(this).prop('checked')).trigger('change');
  });

  $('.select2').each(function() {
    $(this).select2({
        allowClear: true,
        placeholder: "Please Select",
        dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-body') : undefined
    });
  });

  $("#currencyTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'ajax': {
      'url':'php/modules/currencies/loadCurrencies.php',
    },
    'columns': [
      {
        data: 'id',
        className: 'select-checkbox',
        orderable: false,
        render: function (data, type, row) {
            return '<input type="checkbox" class="select-checkbox" id="checkbox_' + data + '" value="'+data+'">';
        }
      },
      {
        data: 'is_default',
        orderable: false,
        render: function (data, type, row) {
            var starClass = data == '1' ? 'star-default active' : 'star-default';
            var starTitle = data == '1' ? 'Default Currency' : 'Set as Default';
            return '<span class="'+starClass+'" onclick="toggleDefault('+row.id+')" title="'+starTitle+'">★</span>';
        }
      },
      { data: 'currency' },
      { data: 'description' },
      // { data: 'rate' },
      { 
        data: 'deleted',
        render: function (data, type, row) {
          return '<div class="row"><div class="col-3"><button type="button" onclick="edit(' + row.id + ')" class="btn btn-success btn-sm custom-pencil-icon-btn"><i class="fas fa-pen"></i></button></div><div class="col-3"><button type="button" onclick="deactivate(' + row.id + ')" class="btn btn-danger btn-sm custom-trash-icon-btn"><i class="fas fa-trash"></i></button></div></div>';
        }
      }
    ],
  });
  
  $.validator.setDefaults({
      submitHandler: function () {
          $('#spinnerLoading').show();
          $.post('php/modules/currencies/currencies.php', $('#currencyForm').serialize(), function(data){
              var obj = JSON.parse(data); 
              
              if(obj.status === 'success'){
                $('#addModal').modal('hide');
                toastr["success"](obj.message, "Success:");
                $('#currencyTable').DataTable().ajax.reload();
                $('#spinnerLoading').hide();
              }
              else if(obj.status === 'failed'){
                toastr["error"](obj.message, "Failed:");
                $('#spinnerLoading').hide();
              }
              else{
                toastr["error"]("Something went wrong", "Failed:");
                $('#spinnerLoading').hide();
              }
          });
      }
  });

  $('#addCurrency').on('click', function(){
    $('#modalTitle').text('Add Currency');
    $('#addModal').find('#id').val("");
    $('#addModal').find('#currency').val("");
    $('#addModal').find('#description').val("");
    $('#addModal').find('#rate').val("1");
    $('#addModal').modal('show');
    
    $('#currencyForm').validate({
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

  $('#multiDeactivate').on('click', function () {
    $('#spinnerLoading').show();
    var selectedIds = [];

    $("#currencyTable tbody input[type='checkbox']").each(function () {
      if (this.checked) {
          selectedIds.push($(this).val());
      }
    });

    if (selectedIds.length > 0) {
      if (confirm('Are you sure you want to delete the selected currencies?')) {
          $.post('php/modules/currencies/deleteCurrency.php', {userID: selectedIds, type: 'MULTI'}, function(data){
              var obj = JSON.parse(data);
              
              if(obj.status === 'success'){
                $('#currencyTable').DataTable().ajax.reload();
                $('#spinnerLoading').hide();
              }
              else if(obj.status === 'failed'){
                toastr["error"](obj.message, "Failed:");
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
        alert("Please select at least one currency to delete.");
        $('#spinnerLoading').hide();
    }     
  });
});

function edit(id){
  $('#spinnerLoading').show();
  $.post('php/modules/currencies/getCurrency.php', {userID: id}, function(data){
      var obj = JSON.parse(data);
      
      if(obj.status === 'success'){
          $('#modalTitle').text('Edit Currency');
          $('#addModal').find('#id').val(obj.message.id);
          $('#addModal').find('#currency').val(obj.message.currency);
          $('#addModal').find('#description').val(obj.message.description);
          $('#addModal').find('#rate').val(obj.message.rate);
          $('#addModal').find('#company').val(obj.message.customer).trigger('change');
          $('#addModal').modal('show');
          
          $('#currencyForm').validate({
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
          toastr["error"]("Something went wrong", "Failed:");
      }
      $('#spinnerLoading').hide();
  });
}

function deactivate(id){
  if (confirm('Are you sure you want to delete this currency?')) {
    $('#spinnerLoading').show();
    $.post('php/modules/currencies/deleteCurrency.php', {userID: id}, function(data){
        var obj = JSON.parse(data);
        
        if(obj.status === 'success'){
            toastr["success"](obj.message, "Success:");
            $('#currencyTable').DataTable().ajax.reload();
            $('#spinnerLoading').hide();
        }
        else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
            $('#spinnerLoading').hide();
        }
        else{
            toastr["error"]("Something went wrong", "Failed:");
            $('#spinnerLoading').hide();
        }
    });
  }
}

function toggleDefault(id) {
  $.post('php/modules/currencies/setDefaultCurrency.php', { id: id }, function(data) {
    var obj = JSON.parse(data);
    if (obj.status === 'success') {
      toastr["success"](obj.message, "Success:");
      $('#currencyTable').DataTable().ajax.reload();
    } else {
      toastr["error"](obj.message, "Failed:");
    }
  });
}
</script>
