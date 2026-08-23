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

<section class="content page-modern">
  <div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title">
        <i class="fas fa-users"></i>
        <?=$languageArray['users_code'][$language]?>
      </h1>
    </div>

    <!-- Results Card -->
    <div class="card results-card show-dt-controls">
      <div class="card-header">
        <div class="results-header-left">
          <h3 class="results-title">
            <i class="fas fa-list"></i>
            <?=$languageArray['users_code'][$language]?>
          </h3>
        </div>
        <div class="results-header-right">
          <button type="button" class="btn btn-action btn-action-warning" id="addMembers">
            <i class="fas fa-plus"></i> <?=$languageArray['add_members_code'][$language]?>
          </button>
        </div>
      </div>
      <div class="card-body">
        <table id="memberTable" class="table data-table">
          <thead>
            <tr>
              <th><?=$languageArray['full_name_code'][$language]?></th>
              <th><?=$languageArray['role_code'][$language]?></th>
              <th><?=$languageArray['allow_add_code'][$language]?></th>
              <th><?=$languageArray['allow_edit_code'][$language]?></th>
              <th><?=$languageArray['allow_delete_code'][$language]?></th>
              <th><?=$languageArray['allow_price_code'][$language]?></th>
              <th><?=$languageArray['locations_code'][$language]?></th>
              <th><?=$languageArray['created_date_code'][$language]?></th>
              <th><?=$languageArray['actions_code'][$language]?></th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</section>

<!-- Modal -->
<div class="modal fade modal-modern" id="addModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form role="form" id="memberForm">
        <div class="modal-header">
          <h4 class="modal-title"><?=$languageArray['add_members_code'][$language]?></h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" class="form-control" id="id" name="id">
          <input type="hidden" class="form-control" id="company" name="customer" value="<?=$company ?>">
          
          <!-- Account Info Section -->
          <div class="modal-section">
            <div class="section-title"><i class="fas fa-user-circle mr-2"></i> <?=$languageArray['account_information_code'][$language]?></div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['username_code'][$language]?> <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="username" id="username" placeholder="<?=$languageArray['enter_username_code'][$language]?>" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['full_name_code'][$language]?> <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="name" id="name" placeholder="<?=$languageArray['enter_full_name_code'][$language]?>" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['email_address_code'][$language]?></label>
                  <input type="email" class="form-control" name="email" id="email" placeholder="<?=$languageArray['enter_email_code'][$language]?>">
                  <small class="text-muted"><?=$languageArray['used_for_password_reset_code'][$language]?></small>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['role_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control" id="userRole" name="userRole" required>
                    <option select="selected" value=""><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while ($row2 = $result2->fetch_assoc()) { ?>
                      <?php if ($row2['role_code'] !== 'ADMIN') { ?>
                        <option value="<?= $row2['role_code'] ?>"><?= $row2['role_name'] ?></option>
                      <?php } ?>
                    <?php } ?>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Permissions Section -->
          <div class="modal-section">
            <div class="section-title"><i class="fas fa-shield-alt mr-2"></i> <?=$languageArray['permissions_code'][$language]?></div>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['allow_add_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control" id="allowAdd" name="allowAdd" required>
                    <option value="Y"><?=$languageArray['yes_code'][$language]?></option>
                    <option value="N"><?=$languageArray['no_code'][$language]?></option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['allow_edit_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control" id="allowEdit" name="allowEdit" required>
                    <option value="Y"><?=$languageArray['yes_code'][$language]?></option>
                    <option value="N"><?=$languageArray['no_code'][$language]?></option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['allow_delete_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control" id="allowDelete" name="allowDelete" required>
                    <option value="Y"><?=$languageArray['yes_code'][$language]?></option>
                    <option value="N"><?=$languageArray['no_code'][$language]?></option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['allow_price_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control" id="allowPrice" name="allowPrice" required>
                    <option value="Y"><?=$languageArray['yes_code'][$language]?></option>
                    <option value="N"><?=$languageArray['no_code'][$language]?></option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Location Section -->
          <div class="modal-section">
            <div class="section-title"><i class="fas fa-map-marker-alt mr-2"></i> <?=$languageArray['location_assignment_code'][$language]?></div>
            <div class="form-group-modern">
              <label class="form-label-modern"><?=$languageArray['locations_code'][$language]?></label>
              <select class="form-control select2" id="location" name="location">
                <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                <?php while($rowLocation=mysqli_fetch_assoc($locations)){ ?>
                  <option value="<?=$rowLocation['id'] ?>"><?=$rowLocation['locations'] ?></option>
                <?php } ?>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-modern btn-modern-primary" name="submit" id="submitMember"><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var memberTable;

$(document).ready(function() {
  $('.select2').each(function() {
    $(this).select2({
      allowClear: true,
      placeholder: "Please Select",
      dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-body') : undefined
    });
  });

  memberTable = $("#memberTable").DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: true,
    serverMethod: 'post',
    ajax: {
      url: 'php/modules/users/loadMembers.php',
      data: { id: <?=$company ?> }
    },
    columns: [
      { data: 'name' },
      { data: 'role_name' },
      { 
        data: 'allow_add',
        render: function(data) {
          return data === 'YES' 
            ? '<span class="badge badge-success">YES</span>' 
            : '<span class="badge badge-secondary">NO</span>';
        }
      },
      { 
        data: 'allow_edit',
        render: function(data) {
          return data === 'YES' 
            ? '<span class="badge badge-success">YES</span>' 
            : '<span class="badge badge-secondary">NO</span>';
        }
      },
      { 
        data: 'allow_delete',
        render: function(data) {
          return data === 'YES' 
            ? '<span class="badge badge-success">YES</span>' 
            : '<span class="badge badge-secondary">NO</span>';
        }
      },
      { 
        data: 'allow_price',
        render: function(data) {
          return data === 'YES' 
            ? '<span class="badge badge-success">YES</span>' 
            : '<span class="badge badge-secondary">NO</span>';
        }
      },
      { data: 'location' },
      { data: 'created_date' },
      { 
        data: 'id',
        orderable: false,
        render: function(data) {
          return '<button type="button" onclick="edit('+data+')" class="btn btn-action btn-action-primary btn-sm mr-1"><i class="fas fa-pen"></i></button>' +
                 '<button type="button" onclick="deactivate('+data+')" class="btn btn-action btn-action-danger btn-sm"><i class="fas fa-trash"></i></button>';
        }
      }
    ]
  });

  $.validator.setDefaults({
    submitHandler: function() {
      $('#spinnerLoading').show();
      $.post('php/modules/users/users.php', $('#memberForm').serialize(), function(data) {
        var obj = JSON.parse(data);
        if (obj.status === 'success') {
          $('#addModal').modal('hide');
          toastr.success(obj.message, "Success:");
          memberTable.ajax.reload();
        } else {
          toastr.error(obj.message || "Something went wrong", "Failed:");
        }
        $('#spinnerLoading').hide();
      });
    }
  });

  $('#addMembers').on('click', function() {
    $('#memberForm')[0].reset();
    $('#addModal').find('#id').val("");
    $('#addModal').find('#location').val("").trigger('change');
    $('#addModal').modal('show');
    initValidation();
  });
});

function initValidation() {
  $('#memberForm').validate({
    errorElement: 'span',
    errorPlacement: function(error, element) {
      error.addClass('invalid-feedback');
      element.closest('.form-group-modern').append(error);
    },
    highlight: function(element) {
      $(element).addClass('is-invalid');
    },
    unhighlight: function(element) {
      $(element).removeClass('is-invalid');
    }
  });
}

function edit(id) {
  $('#spinnerLoading').show();
  $.post('php/modules/users/getUser.php', { userID: id }, function(data) {
    var obj = JSON.parse(data);
    if (obj.status === 'success') {
      $('#addModal').find('#id').val(obj.message.id);
      $('#addModal').find('#username').val(obj.message.username);
      $('#addModal').find('#name').val(obj.message.name);
      $('#addModal').find('#email').val(obj.message.email);
      $('#addModal').find('#userRole').val(obj.message.role_code);
      $('#addModal').find('#allowAdd').val(obj.message.allow_add);
      $('#addModal').find('#allowEdit').val(obj.message.allow_edit);
      $('#addModal').find('#allowDelete').val(obj.message.allow_delete);
      $('#addModal').find('#allowPrice').val(obj.message.allow_price);
      $('#addModal').find('#location').val(obj.message.location).trigger('change');
      $('#addModal').modal('show');
      initValidation();
    } else {
      toastr.error(obj.message || "Something went wrong", "Failed:");
    }
    $('#spinnerLoading').hide();
  });
}

function deactivate(id) {
  Swal.fire({
    title: 'Are you sure?',
    text: "You want to delete this user?",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, delete it!'
  }).then((result) => {
    if (result.isConfirmed) {
      $('#spinnerLoading').show();
      $.post('php/modules/users/deleteUser.php', { userID: id }, function(data) {
        var obj = JSON.parse(data);
        if (obj.status === 'success') {
          toastr.success(obj.message, "Success:");
          memberTable.ajax.reload();
        } else {
          toastr.error(obj.message || "Something went wrong", "Failed:");
        }
        $('#spinnerLoading').hide();
      });
    }
  });
}
</script>
