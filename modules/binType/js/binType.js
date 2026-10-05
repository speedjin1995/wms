// 1. Variables
var binTypeTable;
var binTypeApi = 'php/modules/binType/api.php';

// 2. Document ready
$(document).ready(function () {
  $('#selectAllCheckbox').on('change', function() {
    var checkboxes = $('#binTypeTable tbody input[type="checkbox"]');
    checkboxes.prop('checked', $(this).prop('checked')).trigger('change');
  });

  $('.select2').each(function() {
    $(this).select2({
      allowClear: true,
      placeholder: binTypeText.pleaseSelect,
      dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal') : undefined
    });
  });

  binTypeTable = $("#binTypeTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'language': binTypeTableLanguage,
    'order': [[ 1, 'asc' ]],
    'ajax': {
      'url': binTypeApi,
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
      { data: 'bin_type' },
      {
        data: 'id',
        responsivePriority: 1,
        orderable: false,
        render: function (data, type, row) {
          return '<div class="d-flex" style="gap:4px;"><button type="button" onclick="edit(' + data + ')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button><button type="button" onclick="deactivate(' + data + ')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button></div>';
        }
      }
    ]
  });

  $.validator.setDefaults({
    submitHandler: function () {
      $('#spinnerLoading').show();
      $('#submitBinType').prop('disabled', true);

      $.post(binTypeApi, $('#binTypeForm').serialize() + '&action=save', function(obj){
        if(obj.status === 'success'){
          $('#addModal').modal('hide');
          toastr["success"](obj.message, "Success:");
          binTypeTable.ajax.reload();
        }
        else if(obj.status === 'failed'){
          toastr["error"](obj.message, "Failed:");
        }
        else{
          toastr["error"]("Something went wrong", "Failed:");
        }
      }).fail(function(){
        toastr["error"]("Something went wrong", "Failed:");
      }).always(function(){
        $('#submitBinType').prop('disabled', false);
        $('#spinnerLoading').hide();
      });
    }
  });

  $('#addBinType').on('click', function(){
    $('#modalTitle').text(binTypeText.addTitle);
    $('#addModal').find('#id').val("");
    $('#addModal').find('#binType').val("");
    $('#addModal').modal('show');

    initBinTypeValidation();
  });

  $('#multiDeactivate').on('click', function () {
    var selectedIds = [];

    $("#binTypeTable tbody input[type='checkbox']").each(function () {
      if (this.checked) {
        selectedIds.push($(this).val());
      }
    });

    if (selectedIds.length > 0) {
      if (confirm('Are you sure you want to delete the selected bin types?')) {
        deleteBinTypes(selectedIds);
      }
    }
    else {
      alert("Please select at least one bin type to delete.");
    }
  });
});

// 3. Functions
function initBinTypeValidation(){
  $('#binTypeForm').validate({
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

function edit(id){
  $('#spinnerLoading').show();

  $.post(binTypeApi, {action: 'get', id: id}, function(obj){
    if(obj.status === 'success'){
      $('#modalTitle').text(binTypeText.editTitle);
      $('#addModal').find('#id').val(obj.message.id);
      $('#addModal').find('#binType').val(obj.message.bin_type);
      $('#addModal').find('#company').val(obj.message.customer).trigger('change');
      $('#addModal').modal('show');

      initBinTypeValidation();
    }
    else if(obj.status === 'failed'){
      toastr["error"](obj.message, "Failed:");
    }
    else{
      toastr["error"]("Something went wrong", "Failed:");
    }
  }).fail(function(){
    toastr["error"]("Something went wrong", "Failed:");
  }).always(function(){
    $('#spinnerLoading').hide();
  });
}

function deactivate(id){
  if (confirm('Are you sure you want to delete this bin type?')) {
    deleteBinTypes([id]);
  }
}

function deleteBinTypes(ids){
  $('#spinnerLoading').show();

  $.post(binTypeApi, {action: 'delete', ids: ids}, function(obj){
    if(obj.status === 'success'){
      toastr["success"](obj.message, "Success:");
      $('#selectAllCheckbox').prop('checked', false);
      binTypeTable.ajax.reload();
    }
    else if(obj.status === 'failed'){
      toastr["error"](obj.message, "Failed:");
    }
    else{
      toastr["error"]("Something went wrong", "Failed:");
    }
  }).fail(function(){
    toastr["error"]("Something went wrong", "Failed:");
  }).always(function(){
    $('#spinnerLoading').hide();
  });
}
