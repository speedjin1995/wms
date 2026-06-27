<?php
require_once 'php/db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
    echo '<script type="text/javascript">';
    echo 'window.location.href = "login.html";</script>';
}
else{
    $company = $_SESSION['customer'];
    $id = $_SESSION['userID'];
    $indicators = $db->query("SELECT * FROM indicators WHERE customer = $company ORDER BY name ASC");

    $stmt = $db->prepare("SELECT i.* from companies c LEFT JOIN indicators i ON c.indicator = i.id where c.id = ?");
	$stmt->bind_param('s', $company);
	$stmt->execute();
	$result = $stmt->get_result();
    $indicatorId = '';
    $name = '';
    $nickname = '';
    $serialNo = '';
    $macAddress = '';
    $indicator = '';
	
	if(($row = $result->fetch_assoc()) !== null){
        $indicatorId = $row['id'];
        $name = $row['name'];
        $nickname = $row['nickname'];
        $serialNo = $row['serial_no'];
        $macAddress = $row['mac_address'];
        $indicator = $row['indicator'];
    }

    // Language
    $language = $_SESSION['language'];
    $languageArray = $_SESSION['languageArray'];
}
?>

<section class="content-header">
	<div class="container-fluid">
		<div>
			<div class="col-sm-6">
				<h1><?=$languageArray['setup_code'][$language]?></h1>
			</div>
		</div>
	</div>
</section>

<section class="content" style="min-height:700px;">
	<div class="card">
		<form role="form" id="profileForm" novalidate="novalidate">
			<div class="card-body">
                <div class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <label><?=$languageArray['indicator_code'][$language]?></label>
                            <select class="form-control select2" id="indicatorSelect" name="indicatorSelect">
                                <option value="" selected disabled>Please Select</option>
                                <?php while($rowInd = mysqli_fetch_assoc($indicators)){ ?>
                                    <option value="<?=$rowInd['id']?>"><?=$rowInd['name']?> (<?=$rowInd['nickname']?>)</option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
                <hr>
                <h5><?=$languageArray['indicator_detail_code'][$language]?></h5>
                <div class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <label><?=$languageArray['name_code'][$language]?></label>
                            <input class="form-control" id="name" value="<?=$name?>" readonly>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label><?=$languageArray['nickname_code'][$language]?></label>
                            <input class="form-control" id="nickname" value="<?=$nickname?>" readonly>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label><?=$languageArray['serial_no_code'][$language]?></label>
                            <input class="form-control" id="serialNo" value="<?=$serialNo?>" readonly>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label><?=$languageArray['mac_address_code'][$language]?></label>
                            <input class="form-control" id="macAddress" value="<?=$macAddress?>" readonly>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label><?=$languageArray['indicator_code'][$language]?></label>
                            <input class="form-control" id="indicator" value="<?=$indicator?>" readonly>
                        </div>
                    </div>
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
    $('.select2').select2({
      allowClear: true,
      placeholder: "Please Select"
    });

    $('#indicatorSelect').on('change', function () {
        var selectedIndicator = $(this).val();

        $('#spinnerLoading').show();
        $.post('php/getIndicator.php', {userID: selectedIndicator}, function(data){
            var obj = JSON.parse(data);
            
            if(obj.status === 'success'){
                $('#id').val(obj.message.id);
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
                toastr["error"]("Something wrong when activate", "Failed:");
            }
            $('#spinnerLoading').hide();
        });
    });

    $.validator.setDefaults({
        submitHandler: function () {
            $('#spinnerLoading').show();
            $.post('php/updateCompanyIndicator.php', $('#profileForm').serialize(), function(data){
                var obj = JSON.parse(data); 
                
                if(obj.status === 'success'){
                    toastr["success"](obj.message, "Success:");
                    
                    $.get('setup.php', function(data) {
                        $('#mainContents').html(data);
                        $('#spinnerLoading').hide();
                    });
        		}
        		else if(obj.status === 'failed'){
        		    toastr["error"](obj.message, "Failed:");
                    $('#spinnerLoading').hide();
                }
        		else{
        			toastr["error"]("Failed to update ports", "Failed:");
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

    $('#indicatorSelect').val('<?=$indicatorId?>').trigger('change');
});
</script>