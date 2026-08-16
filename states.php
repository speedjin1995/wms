<?php
require_once 'php/db_connect.php';

session_start();

if (!isset($_SESSION['userID'])) {
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
} else {
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
        <h1 class="custom-title"><?=$languageArray['states_code'][$language]?></h1>
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</div><!-- /.content-header -->

<!-- Main content -->
<section class="content custom-table-content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header custom-card-header">
            <div class="row custom-card-header-row">
              <div class="col-8"></div>
              
              <div class="col-2">
                <button type="button" id="multiDeactivate" class="btn btn-block custom-delete-btn btn-sm"><?=$languageArray['delete_states_code'][$language]?></button>
              </div>
              
              <div class="col-2">
                <button type="button" class="btn btn-block custom-add-btn btn-sm" id="addState"><?=$languageArray['add_states_code'][$language]?></button>
              </div>
            </div>
          </div>
          
          <div class="card-body custom-table-card-body">
						<table id="stateTable" class="table table-bordered table-striped">
							<thead>
								<tr>
                  <th><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
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
    <div class="modal-content custom-model-content-box">
      <form role="form" id="stateForm">
        <div class="modal-header custom-model-header-box">
          <h4 class="modal-title custom-model-title-txt" id="modalTitle"><?=$languageArray['add_states_code'][$language]?></h4>
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
              <label for="state"><?=$languageArray['states_code'][$language]?> *</label>
              <input type="text" class="form-control" name="state" id="state" placeholder="Enter state name" required>
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

<!-- jQuery -->
<script>
  $(function () {
    $('#selectAllCheckbox').on('change', function() {
      var checkboxes = $('#stateTable tbody input[type="checkbox"]');
      checkboxes.prop('checked', $(this).prop('checked')).trigger('change');
    });

    $('.select2').each(function() {
      $(this).select2({
          allowClear: true,
          placeholder: "Please Select",
          dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-body') : undefined
      });
    });

    $("#stateTable").DataTable({
      "responsive": true,
      "autoWidth": false,
      'processing': true,
      'serverSide': true,
      'serverMethod': 'post',
      'ajax': {
        'url':'php/modules/states/loadStates.php',
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
        { data: 'states' },
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
      
        $.post('php/modules/states/states.php', $('#stateForm').serialize(), function(data) {
          var obj = JSON.parse(data); 

          if (obj.status === 'success') {
            $('#addModal').modal('hide');
            toastr["success"](obj.message, "Success:");
            $('#stateTable').DataTable().ajax.reload();
            $('#spinnerLoading').hide();
          } else if (obj.status === 'failed') {
            toastr["error"](obj.message, "Failed:");
            $('#spinnerLoading').hide();
          } else {
            toastr["error"]("Something went wrong", "Failed:");
            $('#spinnerLoading').hide();
          }
        });
      }
    });

    $('#addState').on('click', function() {
      $('#modalTitle').text('Add State');
      $('#addModal').find('#id').val("");
      $('#addModal').find('#state').val("");
      $('#addModal').modal('show');
    
      $('#stateForm').validate({
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

      $("#stateTable tbody input[type='checkbox']").each(function () {
        if (this.checked) {
            selectedIds.push($(this).val());
        }
      });

      if (selectedIds.length > 0) {
        if (confirm('Are you sure you want to delete the selected states?')) {
          $.post('php/modules/states/deleteState.php', {userID: selectedIds, type: 'MULTI'}, function(data) {
            var obj = JSON.parse(data);

            if (obj.status === 'success') {
              $('#stateTable').DataTable().ajax.reload();
              $('#spinnerLoading').hide();
            } else if (obj.status === 'failed') {
              toastr["error"](obj.message, "Failed:");
              $('#spinnerLoading').hide();
            } else {
              $('#spinnerLoading').hide();
            }
          });
        } else {
          $('#spinnerLoading').hide();
        }
      } else {
        alert("Please select at least one state to delete.");
        $('#spinnerLoading').hide();
      }     
    });
  });

  function edit(id) {
    $('#spinnerLoading').show();
  
    $.post('php/modules/states/getState.php', {userID: id}, function(data) {
      var obj = JSON.parse(data);
    
      if (obj.status === 'success') {
        $('#modalTitle').text('Edit State');
        $('#addModal').find('#id').val(obj.message.id);
        $('#addModal').find('#state').val(obj.message.states);
        $('#addModal').find('#company').val(obj.message.customer).trigger('change');
        $('#addModal').modal('show');
        
        $('#stateForm').validate({
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
      } else if (obj.status === 'failed') {
        toastr["error"](obj.message, "Failed:");
      } else {
        toastr["error"]("Something went wrong", "Failed:");
      }
    
      $('#spinnerLoading').hide();
    });
  }

  function deactivate(id) {
    if (confirm('Are you sure you want to delete this state?')) {
      $('#spinnerLoading').show();
    
      $.post('php/modules/states/deleteState.php', {userID: id}, function(data) {
        var obj = JSON.parse(data);
      
        if (obj.status === 'success') {
          toastr["success"](obj.message, "Success:");
          $('#stateTable').DataTable().ajax.reload();
          $('#spinnerLoading').hide();
        } else if (obj.status === 'failed') {
          toastr["error"](obj.message, "Failed:");
          $('#spinnerLoading').hide();
        } else {
          toastr["error"]("Something went wrong", "Failed:");
          $('#spinnerLoading').hide();
        }
      });
    }
  }
</script>