<?php
  require_once 'php/db_connect.php';
  session_start();
  $company = $_SESSION['customer'];
  $role = $_SESSION['role'];

  $stmt2 = $db->prepare("SELECT * FROM roles WHERE deleted = '0'");
  $stmt2->execute();
  $result2 = $stmt2->get_result();

  if ($role != 'SADMIN') {
    $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' AND customer = '$company' ORDER BY locations ASC");
  } else {
    $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' ORDER BY locations ASC");
  }

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
?>

<div class="content-header custom-title-content-box">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6">
        <h1 class="custom-title"><?=$languageArray['users_code'][$language]?></h1>
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
            <div class="row">
              <div class="col-9"></div>
              
              <div class="col-3">
                <button type="button" class="btn btn-block custom-add-btn btn-sm" id="addMembers"><?=$languageArray['add_members_code'][$language]?></button>
              </div>
            </div>
          </div>
          
          <div class="card-body custom-table-card-body">
            <table id="memberTable" class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th><?=$languageArray['full_name_code'][$language]?></th>
                  <th><?=$languageArray['role_code'][$language]?></th>
                  <th><?=$languageArray['allow_add_code'][$language]?></th>
                  <th><?=$languageArray['allow_edit_code'][$language]?></th>
                  <th><?=$languageArray['allow_delete_code'][$language]?></th>
                  <th><?=$languageArray['locations_code'][$language]?></th>
                  <th><?=$languageArray['created_date_code'][$language]?></th>
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
      <form role="form" id="memberForm">
        <div class="modal-header custom-model-header-box">
          <h4 class="modal-title custom-model-title-txt"><?=$languageArray['add_members_code'][$language]?></h4>
          <button type="button" class="close custom-btn-close-icon" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        
        <div class="modal-body custom-model-body-box">
          <input type="hidden" class="form-control" id="id" name="id">
          <input type="hidden" class="form-control" id="company" name="customer" value="<?=$company ?>">
          
          <div class="form-group">
            <label for="username"><?=$languageArray['username_code'][$language]?> *</label>
            <input type="text" class="form-control" name="username" id="username" placeholder="<?=$languageArray['enter_username_code'][$language]?>" required>
          </div>
          
          <div class="form-group">
            <label for="name"><?=$languageArray['full_name_code'][$language]?> *</label>
            <input type="text" class="form-control" name="name" id="name" placeholder="<?=$languageArray['enter_full_name_code'][$language]?>" required>
          </div>
          
          <div class="form-group">
            <label for="email"><?=$languageArray['email_address_code'][$language]?></label>
            <input type="email" class="form-control" name="email" id="email" placeholder="<?=$languageArray['enter_email_code'][$language]?>">
            <small class="form-text text-muted"><?=$languageArray['used_for_password_reset_code'][$language]?></small>
          </div>
          
          <div class="form-group">
            <label><?=$languageArray['role_code'][$language]?> *</label>
            <select class="form-control" id="userRole" name="userRole" required>
              <option select="selected" value=""><?=$languageArray['please_select_code'][$language]?></option>
              <?php while ($row2 = $result2->fetch_assoc()) { ?>
                <?php if ($row2['role_code'] !== 'ADMIN') { ?>
                  <option value="<?= $row2['role_code'] ?>"><?= $row2['role_name'] ?></option>
                <?php } ?>
              <?php } ?>
            </select>
          </div>
          
          <div class="form-group">
            <label><?=$languageArray['allow_add_code'][$language]?> *</label>
            <select class="form-control" id="allowAdd" name="allowAdd" required>
              <option value="Y"><?=$languageArray['yes_code'][$language]?></option>
              <option value="N"><?=$languageArray['no_code'][$language]?></option>
            </select>
          </div>
          
          <div class="form-group">
            <label><?=$languageArray['allow_edit_code'][$language]?> *</label>
            <select class="form-control" id="allowEdit" name="allowEdit" required>
              <option value="Y"><?=$languageArray['yes_code'][$language]?></option>
              <option value="N"><?=$languageArray['no_code'][$language]?></option>
            </select>
          </div>
          
          <div class="form-group">
            <label><?=$languageArray['allow_delete_code'][$language]?> *</label>
            <select class="form-control" id="allowDelete" name="allowDelete" required>
              <option value="Y"><?=$languageArray['yes_code'][$language]?></option>
              <option value="N"><?=$languageArray['no_code'][$language]?></option>
            </select>
          </div>
          
          <div class="form-group">
            <label><?=$languageArray['locations_code'][$language]?></label>
            <select class="form-control select2" id="location" name="location">
              <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
              <?php while($rowLocation=mysqli_fetch_assoc($locations)){ ?>
                <option value="<?=$rowLocation['id'] ?>"><?=$rowLocation['locations'] ?></option>
              <?php } ?>
            </select>
          </div>
        </div>
        
        <div class="modal-footer custom-model-fotter-box">
          <button type="button" class="btn custom-close-btn" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn custom-save-btn" name="submit" id="submitMember"><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div> <!-- /.modal-content -->
  </div> <!-- /.modal-dialog -->
</div>

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
    
    $("#memberTable").DataTable({
      "responsive": true,
      "autoWidth": false,
      'processing': true,
      'serverSide': true,
      'serverMethod': 'post',
      'ajax': {
        'url':'php/modules/users/loadMembers.php',
        'data': {
          id: <?=$company ?>
        }
      },
      'columns': [
        { data: 'name' },
        { data: 'role_name' },
        { data: 'allow_add' },
        { data: 'allow_edit' },
        { data: 'allow_delete' },
        { data: 'location' },
        { data: 'created_date' },
        { 
          data: 'id',
          render: function ( data, type, row ) {
            return '<div class="row custom-tbl-btn-icon"><button type="button" id="edit'+data+'" onclick="edit('+data+')" class="btn custom-edit-btn-icon btn-sm"><i class="fas fa-pen"></i></button><button type="button" id="deactivate'+data+'" onclick="deactivate('+data+')" class="btn custom-delete-btn-icon btn-sm"><i class="fas fa-trash"></i></button></div>';
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
        $.post('php/modules/users/users.php', $('#memberForm').serialize(), function(data) {
          var obj = JSON.parse(data);
          
          if (obj.status === 'success') {
            $('#addModal').modal('hide');
            toastr["success"](obj.message, "Success:");
            $('#memberTable').DataTable().ajax.reload();
            $('#spinnerLoading').hide();
          } else if (obj.status === 'failed') {
            toastr["error"](obj.message, "Failed:");
            $('#spinnerLoading').hide();
          } else {
            toastr["error"]("Something wrong when edit", "Failed:");
            $('#spinnerLoading').hide();
          }
        });
      }
    });
    
    $('#addMembers').on('click', function() {
      $('#addModal').find('#id').val("");
      $('#addModal').find('#username').val("");
      $('#addModal').find('#name').val("");
      $('#addModal').find('#email').val("");
      $('#addModal').find('#userRole').val("");
      $('#addModal').find('#allowAdd').val("Y");
      $('#addModal').find('#allowEdit').val("Y");
      $('#addModal').find('#allowDelete').val("Y");
      $('#addModal').find('#location').val("").trigger('change');
      $('#addModal').modal('show');
      
      $('#memberForm').validate({
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
  
  function edit(id) {
    $('#spinnerLoading').show();
    $.post('php/modules/users/getUser.php', {userID: id}, function(data) {
      var obj = JSON.parse(data);
      
      if(obj.status === 'success') {
        $('#addModal').find('#id').val(obj.message.id);
        $('#addModal').find('#username').val(obj.message.username);
        $('#addModal').find('#name').val(obj.message.name);
        $('#addModal').find('#email').val(obj.message.email);
        $('#addModal').find('#userRole').val(obj.message.role_code);
        $('#addModal').find('#allowAdd').val(obj.message.allow_add);
        $('#addModal').find('#allowEdit').val(obj.message.allow_edit);
        $('#addModal').find('#allowDelete').val(obj.message.allow_delete);
        $('#addModal').find('#allowDelete').val(obj.message.allow_delete);
        $('#addModal').find('#location').val(obj.message.location).trigger('change');
        $('#addModal').modal('show');
        
        $('#memberForm').validate({
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
        toastr["error"]("Something wrong when activate", "Failed:");
      }
      
      $('#spinnerLoading').hide();
    });
  }
  
  function deactivate(id) {
    if (confirm('Are you sure you want to delete this items?')) {
      $('#spinnerLoading').show();
      $.post('php/modules/users/deleteUser.php', {userID: id}, function(data) {
        var obj = JSON.parse(data);
        
        if (obj.status === 'success') {
          toastr["success"](obj.message, "Success:");
          $('#memberTable').DataTable().ajax.reload();
          $('#spinnerLoading').hide();
        } else if (obj.status === 'failed') {
          toastr["error"](obj.message, "Failed:");
          $('#spinnerLoading').hide();
        } else {
          toastr["error"]("Something wrong when activate", "Failed:");
          $('#spinnerLoading').hide();
        }
      });
    }
  }
</script>