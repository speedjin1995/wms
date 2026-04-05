<?php
require_once 'php/db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
    echo '<script type="text/javascript">';
    echo 'window.location.href = "login.html";</script>';
}
else{
    $company = $_SESSION['customer'];
    $role = $_SESSION['role'];
    $stmt = $db->prepare("SELECT * from companies where id = ?");
	$stmt->bind_param('s', $company);
	$stmt->execute();
	$result = $stmt->get_result();
    $name = '';
	$address = '';
	$phone = '';
	$email = '';
	
	$logoPath = '';
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

        if(!empty($row['company_logo'])){
            $stmtLogo = $db->prepare("SELECT filepath FROM files WHERE id = ? AND deleted = 0");
            $stmtLogo->bind_param('i', $row['company_logo']);
            $stmtLogo->execute();
            $logoResult = $stmtLogo->get_result();
            if($logoRow = $logoResult->fetch_assoc()){
                $logoPath = $logoRow['filepath'];
            }
            $stmtLogo->close();
        }
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

<section class="content">
	<div class="card">
		<form role="form" id="profileForm" novalidate="novalidate">
			<div class="card-body">
				<div class="row">
					<!-- Left Column -->
					<div class="col-md-6">
						<div class="form-group">
							<label for="regNo"><?=$languageArray['company_reg_no_code'][$language]?> *</label>
							<input type="text" class="form-control" id="regNo" name="regNo" value="<?=$regNo ?>" placeholder="<?=$languageArray['enter_company_reg_no_code'][$language]?>" required <?=($role != 'SADMIN') ? 'readonly' : ''?>>
						</div>
						<div class="form-group">
							<label for="name"><?=$languageArray['company_name_code'][$language]?> *</label>
							<input type="text" class="form-control" id="name" name="name" value="<?=$name ?>" placeholder="<?=$languageArray['enter_company_name_code'][$language]?>" required <?=($role != 'SADMIN') ? 'readonly' : ''?>>
						</div>
						<div class="form-group">
							<label for="address"><?=$languageArray['company_address_line_1_code'][$language]?> *</label>
							<input type="text" class="form-control" id="address1" name="address1" value="<?=$address ?>" placeholder="<?=$languageArray['enter_company_address_line_1_code'][$language]?>" required <?=($role != 'SADMIN') ? 'readonly' : ''?>>
						</div>
						<div class="form-group">
							<label for="address2"><?=$languageArray['company_address_line_2_code'][$language]?></label>
							<input type="text" class="form-control" id="address2" name="address2" value="<?=$address2 ?>" placeholder="<?=$languageArray['enter_company_address_line_2_code'][$language]?>" <?=($role != 'SADMIN') ? 'readonly' : ''?>>
						</div>
						<div class="form-group">
							<label for="address3"><?=$languageArray['company_address_line_3_code'][$language]?></label>
							<input type="text" class="form-control" id="address3" name="address3" value="<?=$address3 ?>" placeholder="<?=$languageArray['enter_company_address_line_3_code'][$language]?>" <?=($role != 'SADMIN') ? 'readonly' : ''?>>
						</div>
						<div class="form-group">
							<label for="address4"><?=$languageArray['company_address_line_4_code'][$language]?></label>
							<input type="text" class="form-control" id="address4" name="address4" value="<?=$address4 ?>" placeholder="<?=$languageArray['enter_company_address_line_4_code'][$language]?>" <?=($role != 'SADMIN') ? 'readonly' : ''?>>
						</div>
					</div>
					<!-- Right Column -->
					<div class="col-md-6">
						<div class="form-group">
							<label for="phone"><?=$languageArray['company_phone_code'][$language]?></label>
							<input type="text" class="form-control" id="phone" name="phone" value="<?=$phone ?>" placeholder="<?=$languageArray['enter_phone_code'][$language]?>" <?=($role != 'SADMIN') ? 'readonly' : ''?>>
						</div>
						<div class="form-group">
							<label for="email"><?=$languageArray['company_email_code'][$language]?></label>
							<input type="email" class="form-control" id="email" name="email" value="<?=$email ?>" placeholder="<?=$languageArray['enter_email_code'][$language]?>" <?=($role != 'SADMIN') ? 'readonly' : ''?>>
						</div>
						<div class="form-group">
							<label for="fax"><?=$languageArray['company_fax_code'][$language]?></label>
							<input type="text" class="form-control" id="fax" name="fax" value="<?=$fax ?>" placeholder="<?=$languageArray['enter_fax_code'][$language]?>" <?=($role != 'SADMIN') ? 'readonly' : ''?>>
						</div>
						<!-- Logo Upload -->
						<div>
							<hr>
							<label>Company Logo</label>
							<?php if(!empty($logoPath)): ?>
								<button type="button" class="btn btn-outline-primary btn-sm ml-2" data-toggle="modal" data-target="#logoPreviewModal"><i class="fas fa-eye"></i> Preview</button>
							<?php endif; ?>
							<div class="input-group mt-2">
								<div class="custom-file">
									<input type="file" class="custom-file-input" id="logoFile" accept=".png,.jpg,.jpeg">
									<label class="custom-file-label" for="logoFile">Choose file</label>
								</div>
								<div class="input-group-append">
									<button class="btn btn-success" type="button" id="uploadLogo"><i class="fas fa-upload"></i> Upload</button>
								</div>
							</div>
							<small class="form-text text-muted">Recommended: 300 x 100 px. Max: 25MB. Format: PNG, JPG, JPEG.</small>
						</div>
					</div>
				</div>
			</div>
			<div class="card-footer" style="<?=($role != 'SADMIN') ? 'display:none' : 'display:block'?>">
				<button class="btn btn-success" id="saveProfile"><i class="fas fa-save"></i> <?=$languageArray['save_code'][$language]?></button>
			</div>
		</form>
	</div>
	<?php if(!empty($logoPath)): ?>
	<!-- Logo Preview Modal -->
	<div class="modal fade" id="logoPreviewModal" tabindex="-1" role="dialog">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Company Logo</h5>
					<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
				</div>
				<div class="modal-body text-center">
					<img src="<?=$logoPath?>" alt="Company Logo" style="max-width:100%;">
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>
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
    
    // Logo upload
    $('#logoFile').on('change', function(){
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });

    $('#uploadLogo').on('click', function(e){
        e.preventDefault();
        var fileInput = $('#logoFile')[0];
        if(!fileInput.files.length){
            toastr["error"]("Please select a file", "Failed:");
            return;
        }
        var file = fileInput.files[0];
        var maxSize = 25 * 1024 * 1024;
        var allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];

        if(file.size > maxSize){
            toastr["error"]("File size exceeds 25MB limit", "Failed:");
            return;
        }
        if(allowedTypes.indexOf(file.type) === -1){
            toastr["error"]("Only PNG, JPG, and JPEG files are allowed", "Failed:");
            return;
        }

        var formData = new FormData();
        formData.append('logo', file);

        $('#spinnerLoading').show();
        $.ajax({
            url: 'php/uploadLogo.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(data){
                var obj = JSON.parse(data);
                if(obj.status === 'success'){
                    toastr["success"](obj.message, "Success:");
                    $.get('company.php', function(data){
                        $('#mainContents').html(data);
                        $('#spinnerLoading').hide();
                    });
                } else {
                    toastr["error"](obj.message, "Failed:");
                    $('#spinnerLoading').hide();
                }
            },
            error: function(){
                toastr["error"]("Failed to upload logo", "Failed:");
                $('#spinnerLoading').hide();
            }
        });
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