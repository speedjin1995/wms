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
	$includePrice = 'N';
	$includePhoto = 'N';
	$includeBarcode = 'N';
	$includeSecRemark = 'N';
	$photoUploadMode = 'local';

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
        $includePrice = $row['include_price'];
        $includePhoto = $row['include_photo'];
        $includeBarcode = $row['include_barcode'];
        $includeSecRemark = $row['include_sec_remark'];
        $photoUploadMode = $row['photo_upload_mode'] ?? 'local';

        if(!empty($row['company_logo'])){
            $logoPath = 'php/viewPhoto.php?file=' . $row['company_logo'].'&type=file_table';
        }
    }

    // Language
    $language = $_SESSION['language'];
    $languageArray = $_SESSION['languageArray'];
}
?>

<section class="content-header">
	<div class="container-fluid px-5">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1 class="m-0 text-dark"><i class="fas fa-building mr-2 text-primary"></i><?=$languageArray['company_profile_code'][$language]?></h1>
			</div>
		</div>
	</div>
</section>

<section class="content px-5">
	<form role="form" id="profileForm" novalidate="novalidate">

		<!-- Company Information -->
		<div class="card card-primary card-outline">
			<div class="card-header">
				<h3 class="card-title"><i class="fas fa-info-circle mr-2"></i><?=$languageArray['company_information_code'][$language]?></h3>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<label for="regNo"><small class="text-uppercase text-muted font-weight-bold"><?=$languageArray['company_reg_no_code'][$language]?> <span class="text-danger">*</span></small></label>
							<input type="text" class="form-control" id="regNo" name="regNo" value="<?=$regNo ?>" placeholder="<?=$languageArray['enter_company_reg_no_code'][$language]?>" required <?=($role != 'SADMIN') ? 'readonly' : ''?>>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label for="name"><small class="text-uppercase text-muted font-weight-bold"><?=$languageArray['company_name_code'][$language]?> <span class="text-danger">*</span></small></label>
							<input type="text" class="form-control" id="name" name="name" value="<?=$name ?>" placeholder="<?=$languageArray['enter_company_name_code'][$language]?>" required <?=($role != 'SADMIN') ? 'readonly' : ''?>>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Address -->
		<div class="card card-primary card-outline">
			<div class="card-header">
				<h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i><?=$languageArray['address_code'][$language]?></h3>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<label for="address1"><small class="text-uppercase text-muted font-weight-bold"><?=$languageArray['company_address_line_1_code'][$language]?> <span class="text-danger">*</span></small></label>
							<input type="text" class="form-control" id="address1" name="address1" value="<?=$address ?>" placeholder="<?=$languageArray['enter_company_address_line_1_code'][$language]?>" required <?=($role != 'SADMIN') ? 'readonly' : ''?>>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label for="address2"><small class="text-uppercase text-muted font-weight-bold"><?=$languageArray['company_address_line_2_code'][$language]?></small></label>
							<input type="text" class="form-control" id="address2" name="address2" value="<?=$address2 ?>" placeholder="<?=$languageArray['enter_company_address_line_2_code'][$language]?>" <?=($role != 'SADMIN') ? 'readonly' : ''?>>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label for="address3"><small class="text-uppercase text-muted font-weight-bold"><?=$languageArray['company_address_line_3_code'][$language]?></small></label>
							<input type="text" class="form-control" id="address3" name="address3" value="<?=$address3 ?>" placeholder="<?=$languageArray['enter_company_address_line_3_code'][$language]?>" <?=($role != 'SADMIN') ? 'readonly' : ''?>>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label for="address4"><small class="text-uppercase text-muted font-weight-bold"><?=$languageArray['company_address_line_4_code'][$language]?></small></label>
							<input type="text" class="form-control" id="address4" name="address4" value="<?=$address4 ?>" placeholder="<?=$languageArray['enter_company_address_line_4_code'][$language]?>" <?=($role != 'SADMIN') ? 'readonly' : ''?>>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Contact Details -->
		<div class="card card-primary card-outline">
			<div class="card-header">
				<h3 class="card-title"><i class="fas fa-phone-alt mr-2"></i><?=$languageArray['contact_details_code'][$language]?></h3>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-4">
						<div class="form-group">
							<label for="phone"><small class="text-uppercase text-muted font-weight-bold"><?=$languageArray['company_phone_code'][$language]?></small></label>
							<div class="input-group">
								<div class="input-group-prepend">
									<span class="input-group-text"><i class="fas fa-phone"></i></span>
								</div>
								<input type="text" class="form-control" id="phone" name="phone" value="<?=$phone ?>" placeholder="<?=$languageArray['enter_phone_code'][$language]?>" <?=($role != 'SADMIN') ? 'readonly' : ''?>>
							</div>
						</div>
					</div>
					<div class="col-md-4">
						<div class="form-group">
							<label for="email"><small class="text-uppercase text-muted font-weight-bold"><?=$languageArray['company_email_code'][$language]?></small></label>
							<div class="input-group">
								<div class="input-group-prepend">
									<span class="input-group-text"><i class="fas fa-envelope"></i></span>
								</div>
								<input type="email" class="form-control" id="email" name="email" value="<?=$email ?>" placeholder="<?=$languageArray['enter_email_code'][$language]?>" <?=($role != 'SADMIN') ? 'readonly' : ''?>>
							</div>
						</div>
					</div>
					<div class="col-md-4">
						<div class="form-group">
							<label for="fax"><small class="text-uppercase text-muted font-weight-bold"><?=$languageArray['company_fax_code'][$language]?></small></label>
							<div class="input-group">
								<div class="input-group-prepend">
									<span class="input-group-text"><i class="fas fa-fax"></i></span>
								</div>
								<input type="text" class="form-control" id="fax" name="fax" value="<?=$fax ?>" placeholder="<?=$languageArray['enter_fax_code'][$language]?>" <?=($role != 'SADMIN') ? 'readonly' : ''?>>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Preferences (SADMIN only) -->
		<div class="card card-primary card-outline">
			<div class="card-header">
				<h3 class="card-title"><i class="fas fa-sliders-h mr-2"></i><?=$languageArray['preferences_code'][$language]?></h3>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-4" style="<?=($role != 'SADMIN') ? 'display:none' : ''?>">
						<div class="card bg-light mb-0">
							<div class="card-body p-3 d-flex align-items-center justify-content-between">
								<span class="font-weight-bold text-sm"><?=$languageArray['include_price_code'][$language]?></span>
								<div class="custom-control custom-switch">
									<input type="checkbox" class="custom-control-input" id="includePriceToggle" <?= $includePrice == 'Y' ? 'checked' : '' ?>>
									<label class="custom-control-label" for="includePriceToggle"></label>
									<input type="hidden" name="includePrice" id="includePriceVal" value="<?=$includePrice?>">
								</div>
							</div>
						</div>
					</div>
					<div class="col-md-4" style="<?=($role != 'SADMIN') ? 'display:none' : ''?>">
						<div class="card bg-light mb-0">
							<div class="card-body p-3 d-flex align-items-center justify-content-between">
								<span class="font-weight-bold text-sm"><?=$languageArray['include_photo_code'][$language]?></span>
								<div class="custom-control custom-switch">
									<input type="checkbox" class="custom-control-input" id="includePhotoToggle" <?= $includePhoto == 'Y' ? 'checked' : '' ?>>
									<label class="custom-control-label" for="includePhotoToggle"></label>
									<input type="hidden" name="includePhoto" id="includePhotoVal" value="<?=$includePhoto?>">
								</div>
							</div>
						</div>
					</div>
					<div class="col-md-4" style="<?=($role != 'SADMIN') ? 'display:none' : ''?>">
						<div class="card bg-light mb-0">
							<div class="card-body p-3 d-flex align-items-center justify-content-between">
								<span class="font-weight-bold text-sm"><?=$languageArray['include_barcode_code'][$language]?></span>
								<div class="custom-control custom-switch">
									<input type="checkbox" class="custom-control-input" id="includeBarcodeToggle" <?= $includeBarcode == 'Y' ? 'checked' : '' ?>>
									<label class="custom-control-label" for="includeBarcodeToggle"></label>
									<input type="hidden" name="includeBarcode" id="includeBarcodeVal" value="<?=$includeBarcode?>">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row mt-3">
					<div class="col-md-4" style="<?=($role != 'SADMIN') ? 'display:none' : ''?>">
						<div class="card bg-light mb-0">
							<div class="card-body p-3 d-flex align-items-center justify-content-between">
								<span class="font-weight-bold text-sm"><?=$languageArray['include_second_remark_code'][$language]?></span>
								<div class="custom-control custom-switch">
									<input type="checkbox" class="custom-control-input" id="includeSecRemarkToggle" <?= $includeSecRemark == 'Y' ? 'checked' : '' ?>>
									<label class="custom-control-label" for="includeSecRemarkToggle"></label>
									<input type="hidden" name="includeSecRemark" id="includeSecRemarkVal" value="<?=$includeSecRemark?>">
								</div>
							</div>
						</div>
					</div>
					<div class="col-md-4">
						<div class="form-group mb-0">
							<label for="photoUploadMode"><small class="text-uppercase text-muted font-weight-bold"><?=$languageArray['photo_upload_mode_code'][$language]?></small></label>
							<select class="form-control" id="photoUploadMode" name="photoUploadMode">
								<option value="local" <?= $photoUploadMode == 'local' ? 'selected' : '' ?>><?=$languageArray['local_code'][$language]?></option>
								<option value="google_drive" <?= $photoUploadMode == 'google_drive' ? 'selected' : '' ?>><?=$languageArray['google_drive_code'][$language]?></option>
								<option value="one_drive" <?= $photoUploadMode == 'one_drive' ? 'selected' : '' ?>><?=$languageArray['one_drive_code'][$language]?></option>
							</select>
						</div>
					</div>
				</div>
			</div>

            <input type="hidden" name="id" value="<?=$company?>">
			<div class="card-footer">
				<button class="btn btn-primary" id="saveProfile"><i class="fas fa-save mr-1"></i> <?=$languageArray['save_code'][$language]?></button>
			</div>
		</div>

	</form>

	<!-- Company Logo -->
	<form id="logoForm" enctype="multipart/form-data">
		<div class="card card-primary card-outline">
			<div class="card-header">
				<h3 class="card-title"><i class="fas fa-image mr-2"></i><?=$languageArray['company_logo_code'][$language]?></h3>
				<?php if(!empty($logoPath)): ?>
					<div class="card-tools">
						<button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#logoPreviewModal"><i class="fas fa-eye mr-1"></i> <?=$languageArray['preview_code'][$language]?></button>
					</div>
				<?php endif; ?>
			</div>
			<div class="card-body">
				<div class="row align-items-center">
					<div class="col-auto">
						<?php if(!empty($logoPath)): ?>
							<img src="<?=$logoPath?>" alt="Logo" class="img-thumbnail" style="max-height:80px;">
						<?php else: ?>
							<div class="bg-light border rounded d-flex align-items-center justify-content-center" style="width:80px;height:80px;">
								<i class="fas fa-image fa-2x text-muted"></i>
							</div>
						<?php endif; ?>
					</div>
					<div class="col">
						<div class="input-group">
							<div class="custom-file">
								<input type="file" class="custom-file-input" id="logoFile" name="file" accept=".png,.jpg,.jpeg">
								<input type="hidden" id="type" name="type" value="logo">
								<input type="hidden" id="company" name="company" value="<?=$company?>">
								<label class="custom-file-label" for="logoFile"><?=$languageArray['choose_file_code'][$language]?></label>
							</div>
							<div class="input-group-append">
								<button class="btn btn-primary" type="submit" id="uploadLogo"><i class="fas fa-upload mr-1"></i> <?=$languageArray['upload_code'][$language]?></button>
							</div>
						</div>
						<small class="form-text text-muted mt-1"><?=$languageArray['recommended_file_size_code'][$language]?></small>
					</div>
				</div>
			</div>
		</div>
	</form>

	<?php if(!empty($logoPath)): ?>
	<div class="modal fade" id="logoPreviewModal" tabindex="-1" role="dialog">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"><?=$languageArray['company_logo_code'][$language]?></h5>
					<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
				</div>
				<div class="modal-body text-center">
					<img src="<?=$logoPath?>" alt="<?=$languageArray['company_logo_code'][$language]?>" class="img-fluid">
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>
</section>

<script>
$(function () {
    // Toggle switch sync
    $('#includePriceToggle').on('change', function(){ $('#includePriceVal').val(this.checked ? 'Y' : 'N'); });
    $('#includePhotoToggle').on('change', function(){ $('#includePhotoVal').val(this.checked ? 'Y' : 'N'); });
    $('#includeBarcodeToggle').on('change', function(){ $('#includeBarcodeVal').val(this.checked ? 'Y' : 'N'); });
    $('#includeSecRemarkToggle').on('change', function(){ $('#includeSecRemarkVal').val(this.checked ? 'Y' : 'N'); });

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
    
    $('#logoFile').on('change', function(){
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });

    $('#logoForm').on('submit', function(e){
        e.preventDefault();
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
        $.ajax({
            url: 'php/uploadPhoto.php',
            type: 'POST',
            data: new FormData(this),
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
        rules: { text: { required: true } },
        messages: { text: { required: "Please fill in this field" } },
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        highlight: function (element) { $(element).addClass('is-invalid'); },
        unhighlight: function (element) { $(element).removeClass('is-invalid'); }
    });
});
</script>
