<?php
require_once 'php/db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
}
else{
    $user = $_SESSION['userID'];

    // Language
    $language = $_SESSION['language'];
    $languageArray = $_SESSION['languageArray'];
}
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><?=$languageArray['change_password_code'][$language]?></h1>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="card">
        <form role="form" id="passwordForm">
            <div class="card-body">
                <div class="form-group">
                    <label for="oldPassword"><?=$languageArray['old_password_code'][$language]?> *</label>
                    <input type="password" class="form-control" name="oldPassword" placeholder="<?=$languageArray['old_password_code'][$language]?>" required="">
                </div>
                
                <div class="form-group">
                    <label for="newPassword"><?=$languageArray['new_password_code'][$language]?></label>
                    <input type="password" class="form-control" name="newPassword" id="newPassword" placeholder="<?=$languageArray['new_password_code'][$language]?>" required="">
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword"><?=$languageArray['confirm_password_code'][$language]?> *</label>
                    <input type="password" class="form-control" name="confirmPassword" id="confirmPassword" placeholder="<?=$languageArray['confirm_password_code'][$language]?>" required="">
                </div>
            </div>
            
            <div class="card-footer">
                <button type="submit" class="btn btn-success" name="submit"><i class="fas fa-save"></i> <?=$languageArray['save_code'][$language]?></button>
            </div>
        </form>
    </div>
</section>

<script>
$(function () {
    $.validator.setDefaults({
        submitHandler: function () {
            $('#spinnerLoading').show();
            $.post('php/changepassword.php', $('#passwordForm').serialize(), function(data){
                var obj = JSON.parse(data); 
                
                if(obj.status === 'success'){
                    toastr["success"](obj.message, "Success:");
                    
                    $.get('changePassword.php', function(data) {
                        $('#mainContents').html(data);
                        $('#spinnerLoading').hide();
                    });
                }
                else if(obj.status === 'failed'){
                    toastr["error"](obj.message, "Failed:");
                    $('#spinnerLoading').hide();
                }
                else{
                    toastr["error"]("Failed to update password", "Failed:");
                    $('#spinnerLoading').hide();
                }
            });
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
</script>