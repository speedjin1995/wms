// 1. Variables
var profileApi = 'php/modules/profile/api.php';

// 2. Document ready
$(document).ready(function () {
  $('#profileForm').validate({
    errorElement: 'span',
    errorPlacement: placeError,
    highlight: highlightField,
    unhighlight: unhighlightField,
    submitHandler: function () {
      saveProfile();
    }
  });

  $('#passwordForm').validate({
    rules: {
      newPassword: {
        minlength: 6
      },
      confirmPassword: {
        equalTo: "#newPassword"
      }
    },
    messages: {
      newPassword: {
        minlength: "Your password must be at least 6 characters long"
      },
      confirmPassword: " Enter Confirm Password Same as New Password"
    },
    errorElement: 'span',
    errorPlacement: placeError,
    highlight: highlightField,
    unhighlight: unhighlightField,
    submitHandler: function () {
      changePassword();
    }
  });
});

// 3. Functions
function placeError(error, element) {
  error.addClass('invalid-feedback');
  element.closest('.form-group').append(error);
}

function highlightField(element) {
  $(element).addClass('is-invalid');
}

function unhighlightField(element) {
  $(element).removeClass('is-invalid');
}

function saveProfile() {
  $('#spinnerLoading').show();
  $('#saveProfile').prop('disabled', true);

  $.post(profileApi, $('#profileForm').serialize() + '&action=updateProfile', function (obj) {
    if (obj.status === 'success') {
      toastr["success"](obj.message, "Success:");
      // Reload the shell so the new language applies everywhere
      window.location.href = 'index.php#myprofile';
      location.reload();
      return;
    }

    toastr["error"](obj.message || "Failed to update profile", "Failed:");
    $('#saveProfile').prop('disabled', false);
    $('#spinnerLoading').hide();
  }).fail(function () {
    toastr["error"]("Failed to update profile", "Failed:");
    $('#saveProfile').prop('disabled', false);
    $('#spinnerLoading').hide();
  });
}

function changePassword() {
  $('#spinnerLoading').show();
  $('#savePassword').prop('disabled', true);

  $.post(profileApi, $('#passwordForm').serialize() + '&action=changePassword', function (obj) {
    if (obj.status === 'success') {
      toastr["success"](obj.message, "Success:");
      $('#passwordForm')[0].reset();
    }
    else {
      toastr["error"](obj.message || "Failed to update password", "Failed:");
    }
  }).fail(function () {
    toastr["error"]("Failed to update password", "Failed:");
  }).always(function () {
    $('#savePassword').prop('disabled', false);
    $('#spinnerLoading').hide();
  });
}
