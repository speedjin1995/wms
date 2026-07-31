<?php
  session_start();

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
?>

<div class="content-header custom-title-content-box">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6">
        <h1 class="custom-title"><?=$languageArray['units_code'][$language]?></h1>
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
              <div class="col-9"></div>
              
              <div class="col-3">
                <!--button type="button" class="btn btn-block custom-add-btn btn-sm" id="addSuppliers">Add Units</button-->
              </div>
            </div>
          </div>
          
          <div class="card-body custom-table-card-body">
            <table id="supplierTable" class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th><?=$languageArray['number_short_code'][$language]?></th>
                  <th><?=$languageArray['units_code'][$language]?></th>
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
      <form role="form" id="supplierForm">
        <div class="modal-header custom-model-header-box">
          <h4 class="modal-title custom-model-title-txt"><?=$languageArray['add_units_code'][$language]?></h4>
          <button type="button" class="close custom-btn-close-icon" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        
        <div class="modal-body custom-model-body-box">
          <div class="card-body">
            <div class="form-group">
              <input type="hidden" class="form-control" id="id" name="id">
            </div>
            
            <div class="form-group">
              <label for="name"><?=$languageArray['units_code'][$language]?> *</label>
              <input type="text" class="form-control" name="code" id="code" placeholder="<?=$languageArray['enter_units_code'][$language]?>" required>
            </div>
          </div>
        </div>
        
        <div class="modal-footer custom-model-fotter-box">
          <button type="button" class="btn custom-close-btn" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn custom-save-btn" name="submit" id="submitMember"><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div>

<script>
  $(function () {
    $("#supplierTable").DataTable({
      "responsive": true,
      "autoWidth": false,
      'processing': true,
      'serverSide': true,
      'serverMethod': 'post',
      'order': [[ 1, 'asc' ]],
      'ajax': {
        'url':'php/loadUnits.php'
      },
      'columns': [
        { data: 'no' },
        { data: 'units' },
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
        //$('#spinnerLoading').show();
        $.post('php/units.php', $('#supplierForm').serialize(), function(data) {
          var obj = JSON.parse(data); 
          
          if (obj.status === 'success') {
            $('#addModal').modal('hide');
            toastr["success"](obj.message, "Success:");
            $('#supplierTable').DataTable().ajax.reload();
            //$('#spinnerLoading').hide();
          } else if (obj.status === 'failed') {
            toastr["error"](obj.message, "Failed:");
            //$('#spinnerLoading').hide();
          } else {
            toastr["error"]("Something wrong when edit", "Failed:");
            //$('#spinnerLoading').hide();
          }
        });
      }
    });
    
    //$('#spinnerLoading').hide();
    
    $('#addSuppliers').on('click', function() {
      $('#addModal').find('#id').val("");
      $('#addModal').find('#code').val("");
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

  function edit(id) {
    //$('#spinnerLoading').show();
    
    $.post('php/getUnits.php', {userID: id}, function(data) {
      var obj = JSON.parse(data);
      
      if (obj.status === 'success') {
        $('#addModal').find('#id').val(obj.message.id);
        $('#addModal').find('#code').val(obj.message.units);
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
      } else if (obj.status === 'failed') {
        toastr["error"](obj.message, "Failed:");
      } else {
        toastr["error"]("Something wrong when activate", "Failed:");
      }
      
      //$('#spinnerLoading').hide();
    });
  }

  function deactivate(id) {
    if (confirm('Are you sure you want to delete this items?')) {
      //$('#spinnerLoading').show();
      
      $.post('php/deleteUnits.php', {userID: id}, function(data) {
        var obj = JSON.parse(data);
        
        if(obj.status === 'success') {
          toastr["success"](obj.message, "Success:");
          $('#supplierTable').DataTable().ajax.reload();
          //$('#spinnerLoading').hide();
        } else if (obj.status === 'failed') {
          toastr["error"](obj.message, "Failed:");
          //$('#spinnerLoading').hide();
        } else {
          toastr["error"]("Something wrong when activate", "Failed:");
          //$('#spinnerLoading').hide();
        }
      });
    }
  }

  function reactivate(id) {
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