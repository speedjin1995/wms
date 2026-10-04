<?php
require_once '../../php/db_connect.php';

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
  if ($role != 'SADMIN'){
    $states = $db->query("SELECT * FROM states WHERE deleted = 0 AND customer = '$company' ORDER BY states ASC");
  }
  else{
    $states = $db->query("SELECT * FROM states WHERE deleted = 0 ORDER BY states ASC");
  }

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
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
              <h3 class="results-title"><i class="fas fa-calendar-check mr-2"></i><?=$languageArray['daily_sales_setup_code'][$language]?></h3>
            </div>
            <div class="results-header-right d-flex flex-wrap" style="gap: 0.5rem;">
              <button type="button" class="btn btn-action btn-action-primary" id="addDailySales">
                <i class="fas fa-plus"></i> <?=$languageArray['add_code'][$language]?>
              </button>
            </div>
          </div>
          <div class="card-body">
            <table id="dailySalesSetupTable" class="table data-table">
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

<div class="modal fade modal-modern" id="addModal">
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
              <input type="hidden" class="form-control" id="id" name="id">
              <div class="modal-section" <?php if($role != 'SADMIN'){ echo 'style="display:none;"'; } ?>>
                <div class="form-group mb-0">
                  <label class="form-label-modern"><?=$languageArray['company_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control select2" style="width: 100%;" id="company" name="company" required>
                    <?php while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                      <option value="<?=$rowCompany['id'] ?>" <?php if($rowCompany['id'] == $company) echo 'selected'; ?>><?=$rowCompany['name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="modal-section">
                <div class="form-group">
                  <label class="form-label-modern"><?=$languageArray['modules_code'][$language]?> <span class="text-danger">*</span></label>
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
                <div class="form-group mb-0">
                  <label class="form-label-modern"><?=$languageArray['states_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control select2" style="width: 100%;" id="state" name="state[]" multiple="multiple" required>
                    <?php while($rowstates=mysqli_fetch_assoc($states)){ ?>
                      <option value="<?=$rowstates['id']?>"><?=$rowstates['states']?></option>
                    <?php } ?>
                  </select>
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

<!-- jQuery -->
<script>

$(function () {
  $('.select2').each(function() {
    $(this).select2({
        allowClear: true,
        placeholder: "Please Select",
        // Conditionally set dropdownParent based on the element’s location
        dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal') : undefined
    });
  });

  $("#dailySalesSetupTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'language': {
      'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
      'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
    },
    'ajax': {
      'url':'php/modules/dailySalesSetup/api.php',
      'data': { action: 'list' }
    },
    'columns': [
      { data: 'module' },
      { data: 'state' },
      { 
        data: 'id',
        render: function (data, type, row) {
          return '<div class="d-flex" style="gap:4px;"><button type="button" id="edit' + row.id + '" onclick="edit(' + row.id + ')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button><button type="button" id="delete' + row.id + '" onclick="deactivate(' + row.id + ')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button></div>';
        }
      }
    ]
  });
  
  $.validator.setDefaults({
      submitHandler: function () {
          $('#spinnerLoading').show();
          $('#addModal').find('#module').prop('disabled', false);
          $.post('php/modules/dailySalesSetup/api.php', $('#dailySalesSetupForm').serialize() + '&action=save', function(obj){
              
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
  $.post('php/modules/dailySalesSetup/api.php', {action: 'get', id: id}, function(obj){
      
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
    $.post('php/modules/dailySalesSetup/api.php', {action: 'delete', id: id}, function(obj){
        
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