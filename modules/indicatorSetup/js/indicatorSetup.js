// 1. Variables
var indicatorSetupPage = 'modules/indicatorSetup/indicatorSetup.php';
var indicatorApi = 'php/modules/indicatorSetup/api.php';

// 2. Document ready
$(document).ready(function () {
  $('#indicatorSelect').select2({
    allowClear: true,
    placeholder: indicatorText.pleaseSelect
  });

  $('#indicatorSelect').on('change', function () {
    loadIndicator($(this).val());
  });

  $('#indicatorForm').validate({
    errorElement: 'span',
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback');
      element.closest('.form-group').append(error);
    },
    highlight: function (element) { $(element).addClass('is-invalid'); },
    unhighlight: function (element) { $(element).removeClass('is-invalid'); },
    submitHandler: function () {
      saveIndicator();
    }
  });

  $('#indicatorSelect').val(currentIndicatorId).trigger('change');
});

// 3. Functions
function loadIndicator(indicatorId) {
  if (!indicatorId) {
    return;
  }

  $('#spinnerLoading').show();

  $.post(indicatorApi, {action: 'get', id: indicatorId}, function(obj){

    if(obj.status === 'success'){
      $('#name').val(obj.message.name);
      $('#nickname').val(obj.message.nickname);
      $('#serialNo').val(obj.message.serial_no);
      $('#macAddress').val(obj.message.mac_address);
      $('#indicator').val(obj.message.indicator);
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

function saveIndicator() {
  $('#spinnerLoading').show();
  $('#saveIndicator').prop('disabled', true);

  $.post(indicatorApi, $('#indicatorForm').serialize() + '&action=save', function(obj){

    if(obj.status === 'success'){
      toastr["success"](obj.message, "Success:");

      $.get(indicatorSetupPage, function(data) {
        $('#mainContents').html(data);
        $('#spinnerLoading').hide();
      });
      return;
    }
    else if(obj.status === 'failed'){
      toastr["error"](obj.message, "Failed:");
    }
    else{
      toastr["error"]("Failed to update ports", "Failed:");
    }
    $('#saveIndicator').prop('disabled', false);
    $('#spinnerLoading').hide();
  }).fail(function(){
    toastr["error"]("Failed to update ports", "Failed:");
    $('#saveIndicator').prop('disabled', false);
    $('#spinnerLoading').hide();
  });
}
