<?php
require_once '../../php/db_connect.php';
require_once '../../php/lookup.php';

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
} else {
  $user    = $_SESSION['userID'];
  $company = $_SESSION['customer'];
  $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->bind_param('s', $user);
  $stmt->execute();
  $row  = $stmt->get_result()->fetch_assoc();
  $role = $row['role_code'] ?? 'NORMAL';

  if ($role != 'SADMIN') {
    $categories = $db->query("SELECT * FROM categories WHERE deleted = '0' AND customer = '$company' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' AND customer = '$company' ORDER BY locations ASC");
    $products  = $db->query("SELECT * FROM products WHERE deleted = '0' AND customer = '$company' ORDER BY product_name ASC");
  } else {
    $categories = $db->query("SELECT * FROM categories WHERE deleted = '0' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' ORDER BY locations ASC");
    $products  = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
  }

  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?=$languageArray['stock_balance_report_code'][$language]?></h1>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">

    <!-- Filter Card -->
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <div class="row">
              <div class="form-group col-3">
                <label><?=$languageArray['date_code'][$language]?></label>
                <div class="input-group date" id="datePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#datePicker" id="date"/>
                  <div class="input-group-append" data-target="#datePicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>

              <div class="col-3">
                <div class="form-group">
                  <label><?=$languageArray['locations_code'][$language]?></label>
                  <select class="form-control select2" id="locationFilter">
                    <option value="">-</option>
                    <?php while($row = mysqli_fetch_assoc($locations)) { ?>
                      <option value="<?=$row['id']?>"><?=$row['locations']?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="col-3">
                <div class="form-group">
                  <label><?=$languageArray['category_code'][$language]?></label>
                  <select class="form-control select2" id="categoryFilter">
                    <option value="">-</option>
                    <?php while($row = mysqli_fetch_assoc($categories)) { ?>
                      <option value="<?=$row['id']?>"><?=$row['category_name']?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="form-group col-3">
                <label><?=$languageArray['product_code'][$language]?></label>
                <select class="form-control select2" id="productFilter">
                  <option value="">-</option>
                  <?php while($row = mysqli_fetch_assoc($products)) { ?>
                    <option value="<?=$row['id']?>" data-category="<?=$row['category']?>"><?=$row['product_name']?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
            <div class="row">
              <div class="col-6"></div>
              <div class="col-3">
                <button type="button" class="btn btn-block btn-outline-info btn-sm" id="refreshBtn">
                  <i class="fas fa-sync-alt"></i> <?=$languageArray['refresh_code'][$language]?>
                </button>
              </div>
              <div class="col-3">
                <button type="button" class="btn btn-block bg-gradient-purple btn-sm" id="exportBtn">
                  <i class="fas fa-file-pdf"></i>
                  <?=$languageArray['export_pdf_code'][$language]?>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Preview Card -->
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-header">
            <span><i class="fas fa-file-alt mr-1"></i> <?=$languageArray['preview_code'][$language]?></span>
          </div>
          <div class="card-body p-0">
            <iframe id="previewFrame" src="" style="width:100%; height:80vh; border:none;"></iframe></iframe>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
$(function () {
  $('.select2').select2({ 
    allowClear: true, 
    placeholder: 'Please Select' 
  });

  $('#datePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: new Date()
  });

  function buildUrl() {
    var date = $('#date').val();
    var category = $('#categoryFilter').val() || '';
    var location = $('#locationFilter').val() || '';
    var product = $('#productFilter').val() || '';
    return 'php/modules/wholesales/exportStockBalance.php?asAtDate=' + encodeURIComponent(date) + '&category=' + category + '&location=' + location + '&product=' + product;
  }

  function loadPreview() {
    var date = $('#date').val();
    if (!date) {
      toastr["error"]("Please select a date.", "Validation Error:");
      return;
    }
    $('#previewFrame').attr('src', buildUrl());
  }

  $('#datePicker').on('change.datetimepicker', function () {
    loadPreview();
  });

  $('#categoryFilter').on('change', function () {
    var selectedCategory = $(this).val();
    var productSelect = $('#productFilter');
    var currentVal = productSelect.val();
    productSelect.select2('destroy');
    if (!productSelect.data('original-options')) {
      productSelect.data('original-options', productSelect.html());
    }
    productSelect.html(productSelect.data('original-options'));
    if (selectedCategory) {
      productSelect.find('option').each(function () {
        if ($(this).val() && $(this).data('category') != selectedCategory) {
          $(this).remove();
        }
      });
    }
    if (currentVal && productSelect.find('option[value="' + currentVal + '"]').length) {
      productSelect.val(currentVal);
    } else {
      productSelect.val('');
    }
    productSelect.select2({ allowClear: true, placeholder: 'Please Select' });
  });

  $('#refreshBtn').on('click', function () {
    loadPreview();
  });

  $('#exportBtn').on('click', function () {
    var date = $('#date').val();
    if (!date) {
      toastr["error"]("Please select a date.", "Validation Error:");
      return;
    }
    window.open(buildUrl());
  });

  // Load preview on page init
  loadPreview();
});
</script>
