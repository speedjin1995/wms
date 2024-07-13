<?php
  require_once 'php/db_connect.php';
  session_start();
  $company = $_SESSION['customer'];

  $stmt2 = $db->prepare("SELECT * FROM roles WHERE deleted = '0'");
  $stmt2->execute();
  $result2 = $stmt2->get_result();
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">Users</h1>
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
              <div class="col-6"></div>
              <div class="col-6">
                <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="addMembers">Add Members</button>
              </div>
            </div>
          </div>
          <div class="card-body">
            <table id="memberTable" class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Role</th>
                  <th>Created Date</th>
                  <th>Actions</th>
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
      <form role="form" id="memberForm">
        <div class="modal-header">
          <h4 class="modal-title">Add Members</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" class="form-control" id="id" name="id">
          <input type="hidden" class="form-control" id="company" name="customer" value="<?=$company ?>">
          <div class="form-group">
            <label for="username">Username *</label>
            <input type="text" class="form-control" name="username" id="username" placeholder="Enter Username" required>
          </div>
          <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" class="form-control" name="name" id="name" placeholder="Enter Full Name" required>
          </div>
          <div class="form-group">
						<label>Role *</label>
						<select class="form-control" id="userRole" name="userRole" required>
              <option select="selected" value="">Please Select</option>
              <?php while ($row2 = $result2->fetch_assoc()) { ?>
                <?php if ($row2['role_code'] !== 'ADMIN') { ?>
                  <option value="<?= $row2['role_code'] ?>"><?= $row2['role_name'] ?></option>
                <?php } ?>
            <?php } ?>
						</select>
					</div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" name="submit" id="submitMember">Submit</button>
        </div>
      </form>
    </div> <!-- /.modal-content -->
  </div> <!-- /.modal-dialog -->
</div>
<script>
$(function () {
    $("#memberTable").DataTable({
      "responsive": true,
      "autoWidth": false,
      'processing': true,
      'serverSide': true,
      'serverMethod': 'post',
      'ajax': {
        'url':'php/loadMembers.php',
        'data': {
          id: <?=$company ?>
        }
      },
      'columns': [
        { data: 'name' },
        { data: 'role_name' },
        { data: 'created_date' },
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
          $('#spinnerLoading').show();
          $.post('php/users.php', $('#memberForm').serialize(), function(data){
              var obj = JSON.parse(data); 
              
              if(obj.status === 'success'){
                  $('#addModal').modal('hide');
                  toastr["success"](obj.message, "Success:");
                  $('#memberTable').DataTable().ajax.reload();
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

    $('#addMembers').on('click', function(){
      $('#addModal').find('#id').val("");
      $('#addModal').find('#username').val("");
      $('#addModal').find('#name').val("");
      $('#addModal').find('#userRole').val("");
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

function edit(id){
    $('#spinnerLoading').show();
    $.post('php/getUser.php', {userID: id}, function(data){
        var obj = JSON.parse(data);
        
        if(obj.status === 'success'){
            $('#addModal').find('#id').val(obj.message.id);
            $('#addModal').find('#username').val(obj.message.username);
            $('#addModal').find('#name').val(obj.message.name);
            $('#addModal').find('#userRole').val(obj.message.role_code);
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
        $.post('php/deleteUser.php', {userID: id}, function(data){
            var obj = JSON.parse(data);
            
            if(obj.status === 'success'){
                toastr["success"](obj.message, "Success:");
                $('#memberTable').DataTable().ajax.reload();
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