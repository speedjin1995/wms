<?php
require_once 'php/db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
    echo '<script type="text/javascript">';
    echo 'window.location.href = "login.html";</script>';
}
else{
    $company = $_SESSION['customer'];
    $stmt = $db->prepare("SELECT * from companies where id = ?");
	$stmt->bind_param('s', $company);
	$stmt->execute();
	$result = $stmt->get_result();
    $name = '';
	$address = '';
	$phone = '';
	$email = '';
	
	if(($row = $result->fetch_assoc()) !== null){
        $name = $row['name'];
        $regNo = $row['reg_no'];
        $address = $row['address'];
        $address2 = $row['address2'];
        $address3 = $row['address3'];
        $address4 = $row['address4'];
        $phone = $row['phone'];
        $email = $row['email'];
        $fax = $row['fax'];
    }

    // Language
    $language = $_SESSION['language'];
    $languageArray = $_SESSION['languageArray'];
}
?>

<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1 class="m-0 text-dark"><?=$languageArray['company_profile_code'][$language]?></h1>
			</div>
		</div>
	</div>
</section>

<section class="content" style="min-height:700px;">
	<div class="card">
		<form role="form" id="profileForm" novalidate="novalidate">
			<div class="card-body">
				<div class="form-group">
					<label for="regNo"><?=$languageArray['company_reg_no_code'][$language]?> *</label>
					<input type="text" class="form-control" id="regNo" name="regNo" value="<?=$regNo ?>" placeholder="<?=$languageArray['enter_company_reg_no_code'][$language]?>" required>
				</div>

				<div class="form-group">
					<label for="name"><?=$languageArray['company_name_code'][$language]?> *</label>
					<input type="text" class="form-control" id="name" name="name" value="<?=$name ?>" placeholder="<?=$languageArray['enter_company_name_code'][$language]?>" required>
				</div>
				
				<div class="form-group">
					<label for="address"><?=$languageArray['company_address_line_1_code'][$language]?> *</label>
                    <input type="text" class="form-control" id="address1" name="address1" value="<?=$address ?>" placeholder="<?=$languageArray['enter_company_address_line_1_code'][$language]?>" required>
				</div>
				<div class="form-group">
					<label for="address2"><?=$languageArray['company_address_line_2_code'][$language]?></label>
                    <input type="text" class="form-control" id="address2" name="address2" value="<?=$address2 ?>" placeholder="<?=$languageArray['enter_company_address_line_2_code'][$language]?>">
				</div>
				<div class="form-group">
					<label for="address3"><?=$languageArray['company_address_line_3_code'][$language]?></label>
                    <input type="text" class="form-control" id="address3" name="address3" value="<?=$address3 ?>" placeholder="<?=$languageArray['enter_company_address_line_3_code'][$language]?>">
				</div>
				<div class="form-group">
					<label for="address4"><?=$languageArray['company_address_line_4_code'][$language]?></label>
                    <input type="text" class="form-control" id="address4" name="address4" value="<?=$address4 ?>" placeholder="<?=$languageArray['enter_company_address_line_4_code'][$language]?>">
				</div>

                <div class="form-group">
					<label for="phone"><?=$languageArray['company_phone_code'][$language]?></label>
					<input type="text" class="form-control" id="phone" name="phone" value="<?=$phone ?>" placeholder="<?=$languageArray['enter_phone_code'][$language]?>">
				</div>

                <div class="form-group">
					<label for="email"><?=$languageArray['company_email_code'][$language]?></label>
					<input type="email" class="form-control" id="email" name="email" value="<?=$email ?>" placeholder="<?=$languageArray['enter_email_code'][$language]?>">
				</div>

                <div class="form-group">
					<label for="fax"><?=$languageArray['company_fax_code'][$language]?></label>
					<input type="text" class="form-control" id="fax" name="fax" value="<?=$fax ?>" placeholder="<?=$languageArray['enter_fax_code'][$language]?>">
				</div>
			</div>
			
			<div class="card-footer">
				<button class="btn btn-success" id="saveProfile"><i class="fas fa-save"></i> <?=$languageArray['save_code'][$language]?></button>
			</div>
		</form>
	</div>
</section>

<script>
$(function () {
    $.validator.setDefaults({
        submitHandler: function () {
            $('#spinnerLoading').show();
            $.post('php/updateCompany.php', $('#profileForm').serialize(), function(data){
                var obj = JSON.parse(data); 
                
                if(obj.status === 'success'){
                    toastr["success"](obj.message, "Success:");
                    
                    $.get('company.php', function(data) {
                        $('#mainContents').html(data);
                        $('#spinnerLoading').hide();
                    });
        		}
        		else if(obj.status === 'failed'){
        		    toastr["error"](obj.message, "Failed:");
                    $('#spinnerLoading').hide();
                }
        		else{
        			toastr["error"]("Failed to update profile", "Failed:");
                    $('#spinnerLoading').hide();
        		}
            });
        }
    });
    
    $('#profileForm').validate({
        rules: {
            text: {
                required: true
            }
        },
        messages: {
            text: {
                required: "Please fill in this field"
            }
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