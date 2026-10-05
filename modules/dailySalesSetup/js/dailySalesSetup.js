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
    'language': dailySalesSetupTableLanguage,
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
