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

<div class="content-header custom-title-content-box">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6">
        <h1 class="custom-title"><?=$languageArray['bin_types_code'][$language]?></h1>
      </div>
    </div>
  </div>
</div>

<!-- Main content -->
<section class="content custom-table-content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header custom-card-header">
            <div class="row custom-card-header-row">
              <div class="col-1 custom-card-header-title-col"></div>
              <div class="col-11 custom-card-header-btn-col">
                <button type="button" id="multiDeactivate" class="btn btn-block custom-delete-btn custom-card-header-btn-size btn-sm"><?=$languageArray['delete_bin_types_code'][$language]?></button>
                <button type="button" class="btn btn-block custom-add-btn custom-card-header-btn-size btn-sm" id="addBinType"><?=$languageArray['add_bin_types_code'][$language]?></button>
              </div>
            </div>
          </div>
          <div class="card-body custom-table-card-body">
						<table id="binTypeTable" class="table table-bordered table-striped">
							<thead>
								<tr>
                  <th><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
                  <th><?=$languageArray['bin_types_code'][$language]?></th>
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
    <div class="modal-content custom-model-content-box">
      <form role="form" id="binTypeForm">
        <div class="modal-header custom-model-header-box">
          <h4 class="modal-title custom-model-title-txt" id="modalTitle"><?=$languageArray['add_bin_types_code'][$language]?></h4>
          <button type="button" class="close custom-btn-close-icon" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body custom-model-body-box">
          <div class="card-body">
            <div class="form-group">
              <input type="hidden" class="form-control" id="id" name="id">
            </div>
            <div class="form-group" <?php if($role != 'SADMIN'){ echo 'style="display:none;"'; } ?>>
              <label for="company"><?=$languageArray['company_code'][$language]?> *</label>
              <select class="form-control select2" style="width: 100%;" id="company" name="company" required>
                <?php while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                  <option value="<?=$rowCompany['id'] ?>" <?php if($rowCompany['id'] == $company) echo 'selected'; ?>><?=$rowCompany['name'] ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="form-group">
              <label for="binType"><?=$languageArray['bin_types_code'][$language]?> *</label>
              <input type="text" class="form-control" name="binType" id="binType" placeholder="<?=$languageArray['enter_bin_type_code'][$language]?>" required>
            </div>
          </div>
        </div>
        <div class="modal-footer custom-model-fotter-box">
          <button type="button" class="btn custom-close-btn" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn custom-save-btn" name="submit"><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div>

<script>

$(function () {
  $('#selectAllCheckbox').on('change', function() {
    var checkboxes = $('#binTypeTable tbody input[type="checkbox"]');
    checkboxes.prop('checked', $(this).prop('checked')).trigger('change');
  });

  $('.select2').each(function() {
    $(this).select2({
        allowClear: true,
        placeholder: "Please Select",
        dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-body') : undefined
    });
  });

  $("#binTypeTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'ajax': {
      'url':'php/modules/binType/loadBinType.php',
    },
    'columns': [
      {
        data: 'id',
        className: 'select-checkbox',
        orderable: false,
        render: function (data, type, row) {
            return '<input type="checkbox" class="select-checkbox" id="checkbox_' + data + '" value="'+data+'"/>';
        }
      },
      { data: 'bin_type' },
      { 
        data: 'deleted',
        render: function (data, type, row) {
          return '<div class="row custom-tbl-btn-icon"><button type="button" onclick="edit(' + row.id + ')" class="btn custom-edit-btn-icon btn-sm"><i class="fas fa-pen"></i></button><button type="button" onclick="deactivate(' + row.id + ')" class="btn custom-delete-btn-icon btn-sm"><i class="fas fa-trash"></i></button></div>';
        }
      }
    ],
  });
  
  $.validator.setDefaults({
      submitHandler: function () {
          $('#spinnerLoading').show();
          $.post('php/modules/binType/binType.php', $('#binTypeForm').serialize(), function(data){
              var obj = JSON.parse(data); 
              
              if(obj.status === 'success'){
                $('#addModal').modal('hide');
                toastr["success"](obj.message, "Success:");
                $('#binTypeTable').DataTable().ajax.reload();
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

  $('#addBinType').on('click', function(){
    $('#modalTitle').text('Add Bin Type');
    $('#addModal').find('#id').val("");
    $('#addModal').find('#binType').val("");
    $('#addModal').modal('show');
    
    $('#binTypeForm').validate({
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

    $("#binTypeTable tbody input[type='checkbox']").each(function () {
      if (this.checked) {
          selectedIds.push($(this).val());
      }
    });

    if (selectedIds.length > 0) {
      if (confirm('Are you sure you want to delete the selected bin types?')) {
          $.post('php/modules/binType/deleteBinType.php', {userID: selectedIds, type: 'MULTI'}, function(data){
              var obj = JSON.parse(data);
              
              if(obj.status === 'success'){
                $('#binTypeTable').DataTable().ajax.reload();
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
        alert("Please select at least one bin type to delete.");
        $('#spinnerLoading').hide();
    }     
  });
});

function edit(id){
  $('#spinnerLoading').show();
  $.post('php/modules/binType/getBinType.php', {userID: id}, function(data){
      var obj = JSON.parse(data);
      
      if(obj.status === 'success'){
          $('#modalTitle').text('Edit Bin Type');
          $('#addModal').find('#id').val(obj.message.id);
          $('#addModal').find('#binType').val(obj.message.bin_type);
          $('#addModal').find('#company').val(obj.message.customer).trigger('change');
          $('#addModal').modal('show');
          
          $('#binTypeForm').validate({
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
  if (confirm('Are you sure you want to delete this bin type?')) {
    $('#spinnerLoading').show();
    $.post('php/modules/binType/deleteBinType.php', {userID: id}, function(data){
        var obj = JSON.parse(data);
        
        if(obj.status === 'success'){
            toastr["success"](obj.message, "Success:");
            $('#binTypeTable').DataTable().ajax.reload();
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
</script>
