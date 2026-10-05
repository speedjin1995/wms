// 1. Variables
var table;
var translationApi = 'php/modules/translations/api.php';

// 2. Document ready
$(document).ready(function () {
  table = $("#translationTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'language': translationTableLanguage,
    'order': [[ 1, 'asc' ]],
    'ajax': {
      'url': translationApi,
      'data': { action: 'list' }
    },
    'columns': [
      { data: 'counter' },
      { data: 'message_key_code' },
      { data: 'en' },
      { data: 'zh' },
      { data: 'my' },
      { data: 'ne' },
      { data: 'ja' },
      {
        data: 'id',
        render: function ( data, type, row ) {
          return '<div class="d-flex" style="gap:4px;"><button type="button" onclick="edit('+data+')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button><button type="button" onclick="deactivate('+data+')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button></div>';
        }
      }
    ]
  });

  $.validator.setDefaults({
    submitHandler: function () {
      $('#submitTranslation').prop('disabled', true);

      $.post(translationApi, $('#translationForm').serialize() + '&action=save', function(obj){
        if(obj.status === 'success'){
          $('#translationModal').modal('hide');
          toastr["success"](obj.message, "Success:");
          table.ajax.reload();
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
        $('#submitTranslation').prop('disabled', false);
      });
    }
  });

  $('#addTranslation').on('click', function(){
    $('#translationModal').find('#keyId').val('');
    $('#translationModal').find('#keyCode').val('');
    $('#translationModal').find('#englishDecs').val('');
    $('#translationModal').find('#chineseDecs').val('');
    $('#translationModal').find('#malayDecs').val('');
    $('#translationModal').find('#japaneseDecs').val('');
    $('#translationModal').find('#tamilDecs').val('');
    $('#translationModal').modal('show');

    initTranslationValidation();
  });
});

// 3. Functions
function initTranslationValidation(){
  $('#translationForm').validate({
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
  $.post(translationApi, {action: 'get', id: id}, function(obj){
    if(obj.status === 'success'){
      $('#translationModal').find('#keyId').val(obj.message.id);
      $('#translationModal').find('#keyCode').val(obj.message.message_key_code);
      $('#translationModal').find('#englishDecs').val(obj.message.en);
      $('#translationModal').find('#chineseDecs').val(obj.message.zh);
      $('#translationModal').find('#malayDecs').val(obj.message.my);
      $('#translationModal').find('#japaneseDecs').val(obj.message.ja);
      $('#translationModal').find('#tamilDecs').val(obj.message.ne);
      $('#translationModal').find('#company').val(obj.message.company).trigger('change');
      $('#translationModal').modal('show');

      initTranslationValidation();
    }
    else if(obj.status === 'failed'){
      toastr["error"](obj.message, "Failed:");
    }
    else{
      toastr["error"]("Something went wrong", "Failed:");
    }
  }).fail(function(){
    toastr["error"]("Something went wrong", "Failed:");
  });
}

function deactivate(id){
  if (confirm('Are you sure you want to delete this item?')) {
    $.post(translationApi, {action: 'delete', id: id}, function(obj){
      if(obj.status === 'success'){
        toastr["success"](obj.message, "Success:");
        table.ajax.reload();
      }
      else if(obj.status === 'failed'){
        toastr["error"](obj.message, "Failed:");
      }
      else{
        toastr["error"]("Something went wrong", "Failed:");
      }
    }).fail(function(){
      toastr["error"]("Something went wrong", "Failed:");
    });
  }
}
