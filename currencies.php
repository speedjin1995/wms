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
              <h3 class="results-title"><i class="fas fa-dollar-sign mr-2"></i><?=$languageArray['currency_code'][$language]?></h3>
            </div>
            <div class="results-header-right d-flex flex-wrap" style="gap: 0.5rem;">
              <button type="button" id="multiDeactivate" class="btn btn-action btn-action-danger">
                <i class="fas fa-trash-alt"></i> <?=$languageArray['delete_currencies_code'][$language]?>
              </button>
              <button type="button" class="btn btn-action btn-action-primary" id="addCurrency">
                <i class="fas fa-plus"></i> <?=$languageArray['add_currency_code'][$language]?>
              </button>
            </div>
          </div>
          <div class="card-body">
            <table id="currencyTable" class="table data-table">
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

<div class="modal fade modal-modern" id="addModal">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <form role="form" id="currencyForm">
            <div class="modal-header">
              <h4 class="modal-title" id="modalTitle"><?=$languageArray['add_currency_code'][$language]?></h4>
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
                  <label class="form-label-modern"><?=$languageArray['currency_code'][$language]?> <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="currency" id="currency" placeholder="<?=$languageArray['enter_currency_name_code'][$language]?>" required>
                </div>
                <div class="form-group mb-0">
                  <label class="form-label-modern"><?=$languageArray['description_code'][$language]?></label>
                  <textarea class="form-control" name="description" id="description" rows="3" placeholder="<?=$languageArray['enter_currency_description_code'][$language]?>"></textarea>
                </div>
                <div class="form-group" style="display:none">
                  <label class="form-label-modern"><?=$languageArray['rate_code'][$language]?></label>
                  <input type="text" class="form-control" name="rate" id="rate" placeholder="<?=$languageArray['enter_currency_rate_code'][$language]?>" value="1">
                </div>
              </div>
            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
              <button type="submit" class="btn btn-modern btn-modern-primary" name="submit"><?=$languageArray['submit_code'][$language]?></button>
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
  $('#selectAllCheckbox').on('change', function() {
    var checkboxes = $('#currencyTable tbody input[type="checkbox"]');
    checkboxes.prop('checked', $(this).prop('checked')).trigger('change');
  });

  $('.select2').each(function() {
    $(this).select2({
        allowClear: true,
        placeholder: "Please Select",
        dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal') : undefined
    });
  });

  $("#currencyTable").DataTable({
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
          return '<div class="d-flex" style="gap:4px;"><button type="button" onclick="edit(' + row.id + ')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button><button type="button" onclick="deactivate(' + row.id + ')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button></div>';
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
