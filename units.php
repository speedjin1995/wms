<?php
  session_start();

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
?>

<div class="content-header" style="padding-bottom: 0;">
  <div class="container-fluid"></div>
</div>

<!-- Main content -->
<section class="content page-modern">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card results-card show-dt-controls">
          <div class="card-header">
            <div class="results-header-left">
              <h3 class="results-title"><i class="fas fa-balance-scale mr-2"></i><?=$languageArray['units_code'][$language]?></h3>
            </div>
            <div class="results-header-right d-flex flex-wrap" style="gap: 0.5rem;">
              <!--button type="button" class="btn btn-action btn-action-primary" id="addSuppliers"><i class="fas fa-plus"></i> Add Units</button-->
            </div>
          </div>
          <div class="card-body">
            <table id="supplierTable" class="table data-table">
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

<div class="modal fade modal-modern" id="addModal">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <form role="form" id="supplierForm">
            <div class="modal-header">
              <h4 class="modal-title"><?=$languageArray['add_units_code'][$language]?></h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <input type="hidden" class="form-control" id="id" name="id">
              <div class="modal-section">
                <div class="form-group mb-0">
                  <label class="form-label-modern"><?=$languageArray['units_code'][$language]?> <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="code" id="code" placeholder="<?=$languageArray['enter_units_code'][$language]?>" required>
                </div>
              </div>
            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
              <button type="submit" class="btn btn-modern btn-modern-primary" name="submit" id="submitMember"><?=$languageArray['submit_code'][$language]?></button>
            </div>
        </form>
      </div>
      <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>

<script>
$(function () {
  $("#supplierTable").DataTable({
        "responsive": true,
        "autoWidth": false,
        'processing': true,
        'serverSide': true,
        'serverMethod': 'post',
    'language': {
      'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
      'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
    },
        'order': [[ 1, 'asc' ]],
        'ajax': {
            'url':'php/modules/units/api.php',
            'data': { action: 'list' }
        },
        'columns': [
            { data: 'no' },
            { data: 'units' },
            { 
                data: 'id',
                render: function ( data, type, row ) {
                    return '<div class="d-flex" style="gap:4px;"><button type="button" id="edit'+data+'" onclick="edit('+data+')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button><button type="button" id="deactivate'+data+'" onclick="deactivate('+data+')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button></div>';
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
            $.post('php/modules/units/api.php', $('#supplierForm').serialize() + '&action=save', function(obj){
                
                if(obj.status === 'success'){
                  $('#addModal').modal('hide');
                  toastr["success"](obj.message, "Success:");
                  $('#supplierTable').DataTable().ajax.reload();
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

    //$('#spinnerLoading').hide();

    $('#addSuppliers').on('click', function(){
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

function edit(id){
    //$('#spinnerLoading').show();
    $.post('php/modules/units/api.php', {action: 'get', id: id}, function(obj){
        
        if(obj.status === 'success'){
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
        }
        else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
        }
        else{
            toastr["error"]("Something wrong when activate", "Failed:");
        }
        //$('#spinnerLoading').hide();
    });
}

function deactivate(id){
    if (confirm('Are you sure you want to delete this items?')) {
        //$('#spinnerLoading').show();
        $.post('php/modules/units/api.php', {action: 'delete', ids: [id]}, function(obj){
            
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
    $.post('php/modules/units/api.php', {action: 'reactivate', id: id}, function(obj){
        
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