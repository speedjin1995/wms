// 1. Variables
var companyPage = 'modules/company/company.php';
var companyApi = 'php/modules/company/api.php';

// 2. Document ready
$(document).ready(function () {
  // Toggle switch sync
  $('#includePriceToggle').on('change', function(){ $('#includePriceVal').val(this.checked ? 'Y' : 'N'); });
  $('#includePhotoToggle').on('change', function(){ $('#includePhotoVal').val(this.checked ? 'Y' : 'N'); });
  $('#includeBarcodeToggle').on('change', function(){ $('#includeBarcodeVal').val(this.checked ? 'Y' : 'N'); });
  $('#includeSecRemarkToggle').on('change', function(){ $('#includeSecRemarkVal').val(this.checked ? 'Y' : 'N'); });

  $('#profileForm').validate({
    errorElement: 'span',
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback');
      element.closest('.form-group').append(error);
    },
    highlight: function (element) { $(element).addClass('is-invalid'); },
    unhighlight: function (element) { $(element).removeClass('is-invalid'); },
    submitHandler: function () {
      saveCompany();
    }
  });

  $('#logoFile').on('change', function(){
    var fileName = $(this).val().split('\\').pop();
    $(this).next('.custom-file-label').html(fileName);
  });

  $('#logoForm').on('submit', function(e){
    e.preventDefault();
    uploadLogo(this);
  });
});

// 3. Functions
function reloadCompanyPage() {
  $.get(companyPage, function(data) {
    $('#mainContents').html(data);
    $('#spinnerLoading').hide();
  });
}

function saveCompany() {
  $('#spinnerLoading').show();
  $('#saveProfile').prop('disabled', true);

  $.post(companyApi, $('#profileForm').serialize() + '&action=update', function(obj){

    if(obj.status === 'success'){
      toastr["success"](obj.message, "Success:");
      reloadCompanyPage();
      return;
    }
    else if(obj.status === 'failed'){
      toastr["error"](obj.message, "Failed:");
    }
    else{
      toastr["error"]("Failed to update profile", "Failed:");
    }
    $('#saveProfile').prop('disabled', false);
    $('#spinnerLoading').hide();
  }).fail(function(){
    toastr["error"]("Failed to update profile", "Failed:");
    $('#saveProfile').prop('disabled', false);
    $('#spinnerLoading').hide();
  });
}

function uploadLogo(form) {
  var fileInput = $('#logoFile')[0];

  if(!fileInput.files.length){
    toastr["error"]("Please select a file", "Failed:");
    return;
  }

  var file = fileInput.files[0];
  if(file.size > 25 * 1024 * 1024){
    toastr["error"]("File size exceeds 25MB limit", "Failed:");
    return;
  }
  if(['image/png', 'image/jpeg', 'image/jpg'].indexOf(file.type) === -1){
    toastr["error"]("Only PNG, JPG, and JPEG files are allowed", "Failed:");
    return;
  }

  $('#spinnerLoading').show();
  $('#uploadLogo').prop('disabled', true);

  $.ajax({
    url: companyApi + '?action=uploadLogo',
    type: 'POST',
    data: new FormData(form),
    processData: false,
    contentType: false,
    success: function(obj){
      if(obj.status === 'success'){
        toastr["success"](obj.message, "Success:");
        reloadCompanyPage();
      } else {
        toastr["error"](obj.message, "Failed:");
        $('#uploadLogo').prop('disabled', false);
        $('#spinnerLoading').hide();
      }
    },
    error: function(){
      toastr["error"]("Failed to upload logo", "Failed:");
      $('#uploadLogo').prop('disabled', false);
      $('#spinnerLoading').hide();
    }
  });
}
