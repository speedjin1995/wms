$(function () {
  $('#selectAllCheckbox').on('change', function() {
    var checkboxes = $('#stateTable tbody input[type="checkbox"]');
    checkboxes.prop('checked', $(this).prop('checked')).trigger('change');
  });

  $('.select2').each(function() {
    $(this).select2({
        allowClear: true,
        placeholder: "Please Select",
        dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal') : undefined
    });
  });

  $("#stateTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'language': statesTableLanguage,
    'ajax': {
      'url':'php/modules/states/api.php',
      'data': { action: 'list' }
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
          return '<div class="d-flex" style="gap:4px;"><button type="button" onclick="edit(' + row.id + ')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button><button type="button" onclick="deactivate(' + row.id + ')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button></div>';
        }
      }
    ],
  });
  
  $.validator.setDefaults({
      submitHandler: function () {
          $('#spinnerLoading').show();
          $.post('php/modules/states/api.php', $('#stateForm').serialize() + '&action=save', function(obj){
              
              if(obj.status === 'success'){
                $('#addModal').modal('hide');
                toastr["success"](obj.message, "Success:");
                $('#stateTable').DataTable().ajax.reload();
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

  $('#addState').on('click', function(){
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
          $.post('php/modules/states/api.php', {action: 'delete', ids: selectedIds}, function(obj){
              
              if(obj.status === 'success'){
                $('#stateTable').DataTable().ajax.reload();
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
        alert("Please select at least one state to delete.");
        $('#spinnerLoading').hide();
    }     
  });
});

function edit(id){
  $('#spinnerLoading').show();
  $.post('php/modules/states/api.php', {action: 'get', id: id}, function(obj){
      
      if(obj.status === 'success'){
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
  if (confirm('Are you sure you want to delete this state?')) {
    $('#spinnerLoading').show();
    $.post('php/modules/states/api.php', {action: 'delete', ids: [id]}, function(obj){
        
        if(obj.status === 'success'){
            toastr["success"](obj.message, "Success:");
            $('#stateTable').DataTable().ajax.reload();
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
