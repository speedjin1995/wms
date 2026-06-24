<?php
require_once 'php/db_connect.php';
session_start();

if (!isset($_SESSION['userID'])) {
    echo '<script>window.location.href = "login.html";</script>';
    exit;
}

$company  = $_SESSION['customer'];
$role     = $_SESSION['role'];
$module     = $_SESSION['module'];
$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];

if ($role != 'SADMIN') {
    $sourceQuery = "SELECT products.id, products.product_name, COALESCE(inventory.quantity, 0) as stock FROM products INNER JOIN inventory ON products.id = inventory.product_id INNER JOIN packaging ON products.packaging = packaging.id INNER JOIN categories c ON products.category = c.id WHERE products.deleted='0' AND packaging.packaging_type='Original' AND products.customer='$company' AND inventory.quantity > 0 AND c.module='$module' AND c.deleted='0' ORDER BY products.product_name ASC";
    if ($db->query($sourceQuery)->num_rows == 0) {
        $sourceQuery = "SELECT products.id, products.product_name, COALESCE(inventory.quantity, 0) as stock FROM products INNER JOIN inventory ON products.id = inventory.product_id INNER JOIN packaging ON products.packaging = packaging.id WHERE products.deleted='0' AND packaging.packaging_type='Original' AND products.customer='$company' AND inventory.quantity > 0 ORDER BY products.product_name ASC";
    }
    $sources = $db->query($sourceQuery);

    $targetQuery = "SELECT products.id, products.product_name FROM products LEFT JOIN inventory ON products.id = inventory.product_id INNER JOIN packaging ON products.packaging = packaging.id INNER JOIN categories c ON products.category = c.id WHERE products.deleted='0' AND packaging.packaging_type='Repack' AND products.customer='$company' AND c.module='$module' AND c.deleted='0' ORDER BY products.product_name ASC";
    if ($db->query($targetQuery)->num_rows == 0) {
        $targetQuery = "SELECT products.id, products.product_name FROM products LEFT JOIN inventory ON products.id = inventory.product_id INNER JOIN packaging ON products.packaging = packaging.id WHERE products.deleted='0' AND packaging.packaging_type='Repack' AND products.customer='$company' ORDER BY products.product_name ASC";
    }
    $targets = $db->query($targetQuery);
} else {
    $sources = $db->query("SELECT products.id, products.product_name, COALESCE(inventory.quantity, 0) as stock FROM products INNER JOIN inventory ON products.id = inventory.product_id INNER JOIN packaging ON products.packaging = packaging.id WHERE products.deleted='0' AND packaging.packaging_type='Original' AND inventory.quantity > 0 ORDER BY products.product_name ASC");
    $targets = $db->query("SELECT products.id, products.product_name FROM products LEFT JOIN inventory ON products.id = inventory.product_id INNER JOIN packaging ON products.packaging = packaging.id WHERE products.deleted='0' AND packaging.packaging_type='Repack' ORDER BY products.product_name ASC");
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><?=$languageArray['repacking_code'][$language]?></h1>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <div class="card">
            <form role="form" id="repackingForm">
                <div class="card-body">

                    <!-- Source Product -->
                    <div class="card card-outline card-primary mb-3 shadow-sm">
                        <div class="card-header py-2">
                            <h6 class="card-title mb-0"><i class="fas fa-box-open mr-2"></i><?=$languageArray['source_product_code'][$language]?></h6>
                        </div>
                        <div class="card-body pt-3">
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label><?=$languageArray['product_bulk_code'][$language]?></label>
                                    <select class="form-control select2" style="width:100%;" id="sourceProduct" name="sourceProduct" required>
                                        <option value="" selected disabled hidden>Please Select</option>
                                        <?php while ($row = mysqli_fetch_assoc($sources)) { ?>
                                            <option value="<?= $row['id'] ?>" data-stock="<?= $row['stock'] ?>"><?= $row['product_name'] ?> (<?= $row['stock'] ?> kg)</option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label><?=$languageArray['weight_to_deduct_code'][$language]?></label>
                                    <input type="number" class="form-control" id="productWeight" name="productWeight" placeholder="Enter weight" step="0.01" min="0" required>
                                    <small id="stockInfo" class="text-muted"></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Target Products -->
                    <div class="card card-outline card-warning mb-3 shadow-sm">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center w-100">
                            <h6 class="card-title mb-0"><i class="fas fa-boxes mr-2"></i><?=$languageArray['target_products_packed_code'][$language]?></h6>
                            <button type="button" class="btn btn-success btn-sm ml-auto" id="addRowBtn">
                                <i class="fas fa-plus"></i> <?=$languageArray['add_new_code'][$language]?>
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th><?=$languageArray['product_code'][$language]?></th>
                                        <th><?=$languageArray['weight_code'][$language]?> (kg)</th>
                                        <th width="80px"></th>
                                    </tr>
                                </thead>
                                <tbody id="repackTable"></tbody>
                            </table>
                        </div>
                    </div>

                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success" id="saveBtn">
                        <i class="fas fa-save"></i> <?=$languageArray['save_code'][$language]?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Row Template -->
<script type="text/html" id="rowTemplate">
<tr class="details">
    <td>
        <select class="form-control select2" style="width:100%;" id="targetProduct" name="targetProduct">
            <option value="" selected disabled hidden>Please Select</option>
            <?php while ($row = mysqli_fetch_assoc($targets)) { ?>
                <option value="<?= $row['id'] ?>"><?= $row['product_name'] ?></option>
            <?php } ?>
        </select>
    </td>
    <td>
        <input type="number" class="form-control" id="itemWeight" name="itemWeight" placeholder="Enter weight" step="0.01" min="0" required>
    </td>
    <td>
        <button type="button" class="btn btn-danger btn-sm" id="removeBtn"><i class="fas fa-times"></i></button>
    </td>
</tr>
</script>

<script>
var rowIndex = 0;

$(function () {
    $('.select2').select2({ allowClear: true, placeholder: 'Please Select' });

    // Show available stock when source product changes
    $('#sourceProduct').on('change', function () {
        var stock = $('option:selected', this).data('stock') || 0;
        $('#stockInfo').text('Available: ' + stock + ' kg');
        $('#productWeight').attr('max', stock);
    });

    // Add row
    $('#addRowBtn').on('click', function () {
        var $tpl = $('#rowTemplate').clone();
        $('#repackTable').append($tpl.html());

        var $row = $('#repackTable .details:last');
        $row.attr('id', 'detail' + rowIndex).attr('data-index', rowIndex);
        $row.find('#targetProduct').attr('name', 'itemsRepack[' + rowIndex + ']').attr('id', 'targetProduct' + rowIndex).attr('required', true);
        $row.find('#itemWeight').attr('name', 'itemWeight[' + rowIndex + ']').attr('id', 'itemWeight' + rowIndex).attr('required', true);
        $row.find('#removeBtn').attr('id', 'removeBtn' + rowIndex);

        $row.find('.select2').select2({
            allowClear: true,
            placeholder: 'Please Select',
            dropdownParent: $row.closest('.card-body')
        });

        rowIndex++;
    });

    // Remove row
    $('#repackTable').on('click', 'button[id^="removeBtn"]', function () {
        var idx = $(this).closest('.details').attr('data-index');
        $('#repackingForm').append('<input type="hidden" name="deleted[]" value="' + idx + '">');
        $(this).closest('.details').remove();
    });

    // Submit
    $('#repackingForm').validate({
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group, td').append(error);
        },
        highlight: function (element) { $(element).addClass('is-invalid'); },
        unhighlight: function (element) { $(element).removeClass('is-invalid'); },
        submitHandler: function () {
            if ($('#repackTable .details').length === 0) {
                alert('Please add at least one target product.');
                return;
            }

            var stock = parseFloat($('option:selected', '#sourceProduct').data('stock')) || 0;
            var weight = parseFloat($('#productWeight').val()) || 0;
            if (weight > stock) {
                alert('Insufficient stock. Available: ' + stock + ' kg');
                return;
            }

            var totalTargetWeight = 0;
            $('#repackTable .details').each(function () {
                totalTargetWeight += parseFloat($(this).find('input[id^="itemWeight"]').val()) || 0;
            });

            if (totalTargetWeight != weight) {
                alert('Total target weight (' + totalTargetWeight.toFixed(2) + ' kg) must equal source weight (' + weight.toFixed(2) + ' kg).');
                return;
            }

            $('#spinnerLoading').show();
            $.post('php/repacking.php', $('#repackingForm').serialize(), function (data) {
                var obj = JSON.parse(data);
                if (obj.status === 'success') {
                    toastr['success'](obj.message, 'Success:');
                    $('#repackingForm')[0].reset();
                    $('#repackTable').empty();
                    $('#sourceProduct').val('').trigger('change');
                    rowIndex = 0;
                } else {
                    toastr['error'](obj.message, 'Failed:');
                }
                $('#spinnerLoading').hide();
            });
        }
    });
});
</script>
