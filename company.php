<?php
require_once 'php/db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
    echo '<script type="text/javascript">';
    echo 'window.location.href = "login.html";</script>';
}
else{
    $id = '1';
    $stmt = $db->prepare("SELECT * from companies where id = ?");
	$stmt->bind_param('s', $id);
	$stmt->execute();
	$result = $stmt->get_result();
    $name = '';
	$address = '';
	$phone = '';
	$email = '';
	
	if(($row = $result->fetch_assoc()) !== null){
        $name = $row['name'];
        $address = $row['address'];
        $phone = $row['phone'];
        $email = $row['email'];
    }
}
?>

<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1 class="m-0 text-dark">Company Profile</h1>
			</div>
		</div>
	</div>
</section>

<section class="content" style="min-height:700px;">
	<div class="card">
		<form role="form" id="profileForm" novalidate="novalidate">
			<div class="card-body">
				<div class="form-group">
					<label for="name">Company Name *</label>
					<input type="text" class="form-control" id="name" name="name" value="<?=$name ?>" placeholder="Enter Company Name" required="">
				</div>
				
				<div class="form-group">
					<label for="address">Company Address *</label>
                    <textarea class="form-control" name="address" id="address" rows="3" placeholder="Enter Address" required=""><?=$address ?></textarea>
				</div>

                <div class="form-group">
					<label for="phone">Company Phone </label>
					<input type="text" class="form-control" id="phone" name="phone" value="<?=$phone ?>" placeholder="Enter Phone">
				</div>

                <div class="form-group">
					<label for="name">Company Email </label>
					<input type="email" class="form-control" id="email" name="email" value="<?=$email ?>" placeholder="Enter Email">
				</div>
			</div>
			
			<div class="card-footer">
				<button class="btn btn-success" id="saveProfile"><i class="fas fa-save"></i> Save</button>
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