<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);


require_once 'php/db_connect.php';
require_once 'php/lookup.php';

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
}
else{
  $user = $_SESSION['userID'];
  $company = $_SESSION['customer'];
  $module = $_SESSION['module'];
  $stmt = $db->prepare("SELECT * from users where id = ?");
	$stmt->bind_param('s', $user);
	$stmt->execute();
	$result = $stmt->get_result();
  $role = 'NORMAL';
	$allowAdd = 'N';
	$allowEdit = 'N';
  $allowDelete = 'N';
  $allowPhoto = 'N';

	if(($row = $result->fetch_assoc()) !== null){
    $role = $row['role_code'];
    $allowAdd = $row['allow_add'];
    $allowEdit = $row['allow_edit'];
    $allowDelete = $row['allow_delete'];
  }

  if ($role != 'SADMIN'){
    $categories = $db->query("SELECT * FROM categories WHERE deleted = '0' AND customer = '$company' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $categories2 = $db->query("SELECT * FROM categories WHERE deleted = '0' AND customer = '$company' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $categories3 = $db->query("SELECT * FROM categories WHERE deleted = '0' AND customer = '$company' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $productQuery = "SELECT p.* FROM products p INNER JOIN categories c ON p.category = c.id WHERE p.deleted = '0' AND p.customer = '$company' AND c.module IN ('wholesale', 'processing') AND c.deleted = '0' ORDER BY p.product_name ASC";   
    $productCheck = $db->query($productQuery);
    if ($productCheck->num_rows == 0) {
      $productQuery = "SELECT * FROM products WHERE deleted = '0' AND customer = '$company' ORDER BY product_name ASC";
    }
    $products = $db->query($productQuery);
    $products2 = $db->query($productQuery);
    $products3 = $db->query($productQuery);
    $products4 = $db->query($productQuery);
    $grades = $db->query("SELECT DISTINCT g.*, pg.product_id FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY p.product_name ASC, g.units ASC");
    $grades2 = $db->query("SELECT DISTINCT g.*, pg.product_id FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY p.product_name ASC, g.units ASC");
    $grades3 = $db->query("SELECT DISTINCT g.*, pg.product_id FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY p.product_name ASC, g.units ASC");
    $grades4 = $db->query("SELECT DISTINCT g.*, pg.product_id FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY p.product_name ASC, g.units ASC");
    $users = $db->query("SELECT * FROM users WHERE deleted = '0' AND customer = '$company' ORDER BY name ASC");
    $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' AND customer = '$company' ORDER BY locations ASC");
    $locations2 = $db->query("SELECT * FROM locations WHERE deleted = '0' AND customer = '$company' ORDER BY locations ASC");
    $productionLines = $db->query("SELECT * FROM production_lines WHERE deleted = '0' AND customers = '$company' ORDER BY production_line ASC");
    $productionLines2 = $db->query("SELECT * FROM production_lines WHERE deleted = '0' AND customers = '$company' ORDER BY production_line ASC");

    $packagings = $db->query("SELECT * FROM packaging WHERE deleted = '0' AND customer = '$company' AND packaging_type = 'Original' ORDER BY packaging_name ASC");
    $packagings2 = $db->query("SELECT * FROM packaging WHERE deleted = '0' AND customer = '$company' AND packaging_type = 'Original' ORDER BY packaging_name ASC");
    $packagings3 = $db->query("SELECT * FROM packaging WHERE deleted = '0' AND customer = '$company' AND packaging_type = 'Original' ORDER BY packaging_name ASC");

    // Company Detail 
    $companyDetail = searchCompanyById($company, $db);
    $allowPhoto = $companyDetail['include_photo'];
  } else {
    $categories = $db->query("SELECT * FROM categories WHERE deleted = '0' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $categories2 = $db->query("SELECT * FROM categories WHERE deleted = '0' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $categories3 = $db->query("SELECT * FROM categories WHERE deleted = '0' AND module IN ('wholesale', 'processing') ORDER BY category_name ASC");
    $products = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $products2 = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $products3 = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $products4 = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $grades = $db->query("SELECT DISTINCT g.*, pg.product_id FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC");
    $grades2 = $db->query("SELECT DISTINCT g.*, pg.product_id FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC");
    $grades3 = $db->query("SELECT DISTINCT g.*, pg.product_id FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC");
    $grades4 = $db->query("SELECT DISTINCT g.*, pg.product_id FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC");
    $users = $db->query("SELECT * FROM users WHERE deleted = '0' ORDER BY name ASC");
    $locations = $db->query("SELECT * FROM locations WHERE deleted = '0' ORDER BY locations ASC");
    $locations2 = $db->query("SELECT * FROM locations WHERE deleted = '0' ORDER BY locations ASC");
    $productionLines = $db->query("SELECT * FROM production_lines WHERE deleted = '0' ORDER BY production_line ASC");
    $productionLines2 = $db->query("SELECT * FROM production_lines WHERE deleted = '0' ORDER BY production_line ASC");

    $packagings = $db->query("SELECT * FROM packaging WHERE deleted = '0' AND packaging_type = 'Original' ORDER BY packaging_name ASC");
    $packagings2 = $db->query("SELECT * FROM packaging WHERE deleted = '0' AND packaging_type = 'Original' ORDER BY packaging_name ASC");
    $packagings3 = $db->query("SELECT * FROM packaging WHERE deleted = '0' AND packaging_type = 'Original' ORDER BY packaging_name ASC");

    $allowPhoto = 'Y';
  }

  $units = $db->query("SELECT * FROM units WHERE deleted = '0'");
  $units1 = $db->query("SELECT * FROM units WHERE deleted = '0'");
  
  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>
<style>
  @media screen and (min-width: 676px) {
    .modal-dialog {
      max-width: 1800px; /* New width for default modal */
    }
  }
</style>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?=$languageArray['batch_packaging_code'][$language]?></h1>
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->

<!-- Main content -->
<div class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <div class="row">
              <div class="form-group col-3">
                <label><?=$languageArray['from_date_code'][$language]?>:</label>
                <div class="input-group date" id="fromDatePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#fromDatePicker" id="fromDate"/>
                  <div class="input-group-append" data-target="#fromDatePicker" data-toggle="datetimepicker">
                  <div class="input-group-text"><i class="fa fa-calendar"></i></div></div>
                </div>
              </div>

              <div class="form-group col-3">
                <label><?=$languageArray['to_date_code'][$language]?>:</label>
                <div class="input-group date" id="toDatePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#toDatePicker" id="toDate"/>
                  <div class="input-group-append" data-target="#toDatePicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>
              <div class="col-3">
                <div class="form-group">
                  <label><?=$languageArray['locations_code'][$language]?></label>
                  <select class="form-control select2" id="locationFilter" name="locationFilter">
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($rowLocation=mysqli_fetch_assoc($locations)){ ?>
                      <option value="<?=$rowLocation['id'] ?>"><?=$rowLocation['locations'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-3">
                <div class="form-group">
                  <label><?=$languageArray['production_lines_code'][$language]?></label>
                  <select class="form-control select2" id="productionLineFilter" name="productionLineFilter">
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($rowProdLine=mysqli_fetch_assoc($productionLines)){ ?>
                      <option value="<?=$rowProdLine['id'] ?>"><?=$rowProdLine['production_line'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-9"></div>
              <div class="col-3">
                <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="filterSearch">
                  <i class="fas fa-search"></i>
                  <?=$languageArray['search_code'][$language]?>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-12">
        <div class="card card-info">
          <div class="card-header">
            <div class="row">
              <div class="col-10"><?=$languageArray['batch_packaging_code'][$language]?></div>
              <?php if($allowAdd == 'Y'){ ?>
              <div class="col-2">
                <button type="button" class="btn btn-block bg-gradient-success btn-sm" onclick="newEntry()"><i class="fas fa-plus"></i> <?=$languageArray['add_new_code'][$language]?></button>
              </div>
              <?php } ?>
            </div>
          </div>

          <div class="card-body">
            <table id="weightTable" class="table table-bordered table-striped display">
              <thead>
                <tr>
                  <th><?=$languageArray['batch_no_code'][$language]?></th>
                  <th><?=$languageArray['packaging_date_code'][$language]?></th>
                  <th><?=$languageArray['locations_code'][$language]?></th>
                  <th><?=$languageArray['production_lines_code'][$language]?></th>
                  <th width="10%"><?=$languageArray['actions_code'][$language]?></th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="extendModal">
  <div class="modal-dialog modal-xl" style="max-width: 90%;">
    <div class="modal-content">
      <form role="form" id="extendForm" novalidate>
        <div class="modal-header bg-gray-dark color-palette">
          <h4 class="modal-title"><?=$languageArray['add_new_entry_code'][$language]?></h4>
          <button type="button" class="close bg-gray-dark color-palette" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body" >
          <input type="hidden" class="form-control" id="id" name="id">

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label><?=$languageArray['batch_no_code'][$language]?> *</label>
                <input type="text" class="form-control" id="batchNo" name="batchNo" readonly>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label><?=$languageArray['packaging_date_code'][$language]?> *</label>
                <div class="input-group date" id="packagingDatePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#packagingDatePicker" id="packagingDate" name="packagingDate" required/>
                  <div class="input-group-append" data-target="#packagingDatePicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label><?=$languageArray['locations_code'][$language]?> *</label>
                <select class="form-control select2" id="location" name="location">
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($rowLocation=mysqli_fetch_assoc($locations2)){ ?>
                      <option value="<?=$rowLocation['id'] ?>"><?=$rowLocation['locations'] ?></option>
                    <?php } ?>
                  </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label><?=$languageArray['production_lines_code'][$language]?></label>
                <select class="form-control select2" id="productionLines" name="productionLines">
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($rowProdLine=mysqli_fetch_assoc($productionLines2)){ ?>
                      <option value="<?=$rowProdLine['id'] ?>"><?=$rowProdLine['production_line'] ?></option>
                    <?php } ?>
                  </select>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="form-group">
                <label><?=$languageArray['remark_code'][$language]?></label>
                <textarea colspan="3" class="form-control" id="remarks" name="remarks" placeholder="<?=$languageArray['enter_remark_code'][$language]?>"></textarea>
              </div>
            </div>
          </div>
          
          <hr>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0"><?=$languageArray['weight_details_code'][$language]?></h5>
            <div class="d-flex align-items-center gap-2">
              <button type="button" class="btn btn-success btn-sm" id="addWeightBtn">
                <i class="fas fa-plus"></i> <?=$languageArray['add_weight_code'][$language]?>
              </button>
              <button type="button" class="btn btn-info btn-sm ml-1" id="bulkAddBtn">
                <i class="fas fa-layer-group"></i> Bulk Add
              </button>
            </div>
          </div>
          <div class="row">
            <table class="table table-bordered nowrap table-striped align-middle" style="width:100%">
              <thead>
                <tr>
                  <th width="15%"><?=$languageArray['category_code'][$language]?></th>
                  <th width="15%"><?=$languageArray['product_code'][$language]?></th>
                  <th width="10%"><?=$languageArray['grade_code'][$language]?></th>
                  <th><?=$languageArray['packaging_size_code'][$language]?></th>
                  <th><?=$languageArray['unit_per_box_code'][$language]?></th>
                  <th><?=$languageArray['weight_code'][$language]?></th>
                  <th><?=$languageArray['time_code'][$language]?></th>
                  <?php if($allowPhoto == 'Y') { ?>
                  <th><?=$languageArray['photo_code'][$language]?></th>
                  <?php } ?>
                  <th width="8%"><?=$languageArray['actions_code'][$language]?></th>
                </tr>
              </thead>
              <tbody id="weightDetailsTable">
                <!-- Weight details will be populated here -->
              </tbody>
            </table>
          </div>

        </div>

        <div class="modal-footer justify-content-between bg-gray-dark color-palette">
          <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-primary" id="saveButton"><?=$languageArray['save_code'][$language]?></button>
        </div>
      </form>
    </div> <!-- /.modal-content -->
  </div> <!-- /.modal-dialog -->
</div> <!-- /.modal -->   

<div class="modal fade" id="bulkAddModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="bulkAddForm" novalidate>
      <div class="modal-header bg-gray-dark color-palette">
        <h4 class="modal-title"><?=$languageArray['bulk_add_code'][$language]?></h4>
        <button type="button" class="close bg-gray-dark color-palette" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label><?=$languageArray['bulk_no_code'][$language]?> *</label>
          <input type="number" class="form-control" id="bulkNo" min="1" value="1" required>
        </div>
        <div class="form-group">
          <label><?=$languageArray['category_code'][$language]?> *</label>
          <select class="form-control select2" id="bulkCategory" required>
            <option value="" selected disabled>Select Category</option>
            <?php while($rowCat=mysqli_fetch_assoc($categories3)){ ?>
              <option value="<?=$rowCat['id'] ?>"><?=$rowCat['category_name'] ?></option>
            <?php } ?>
          </select>
          <div class="invalid-feedback">Please select a category.</div>
        </div>
        <div class="form-group">
          <label><?=$languageArray['product_code'][$language]?> *</label>
          <select class="form-control select2" id="bulkProduct" required>
            <option value="" selected disabled>Select Product</option>
            <?php while($rowProduct=mysqli_fetch_assoc($products3)){ ?>
              <option value="<?=$rowProduct['id'] ?>" data-category="<?=$rowProduct['category'] ?>"><?=$rowProduct['product_name'] ?></option>
            <?php } ?>
          </select>
          <div class="invalid-feedback">Please select a product.</div>
        </div>
        <div class="form-group">
          <label><?=$languageArray['grade_code'][$language]?> *</label>
          <select class="form-control select2" id="bulkGrade" required>
            <?php while($rowGrade=mysqli_fetch_assoc($grades3)){ ?>
              <option value="<?=$rowGrade['units'] ?>" data-product="<?=$rowGrade['product_id'] ?>"><?=$rowGrade['units'] ?></option>
            <?php } ?>
          </select>
          <div class="invalid-feedback">Please select a grade.</div>
        </div>
        <div class="form-group">
          <label><?=$languageArray['packaging_size_code'][$language]?> *</label>
          <select class="form-control select2" id="bulkPackagingSize" required>
            <option value="" selected disabled>Select Packaging</option>
            <?php while($rowPkg=mysqli_fetch_assoc($packagings)){ ?>
              <option value="<?=$rowPkg['id'] ?>" data-weight="<?=$rowPkg['weight'] ?>"><?=$rowPkg['packaging_name'] ?></option>
            <?php } ?>
          </select>
          <div class="invalid-feedback">Please select a packaging size.</div>
        </div>
        <div class="form-group">
          <label><?=$languageArray['unit_per_box_code'][$language]?> *</label>
          <input type="number" class="form-control" id="bulkUnitPerBox" step="1" value="0" min="1" required>
        </div>
        <div class="form-group">
          <label><?=$languageArray['weight_code'][$language]?> *</label>
          <input type="number" class="form-control" id="bulkWeight" step="0.01" value="0.00" min="0.01" required>
        </div>
        <div class="form-group">
          <label><?=$languageArray['time_code'][$language]?> *</label>
          <input type="time" class="form-control" id="bulkTime" required>
        </div>
      </div>
      <div class="modal-footer justify-content-between bg-gray-dark color-palette">
        <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
        <button type="submit" class="btn btn-success" id="bulkAddSubmit"><?=$languageArray['add_code'][$language]?></button>
      </div>
    </div>
      </form>
  </div>
</div>

<div class="modal fade" id="cancelModal">
  <div class="modal-dialog modal-xl" style="max-width: 90%;">
    <div class="modal-content">
      <form role="form" id="cancelForm">
        <div class="modal-header bg-gray-dark color-palette">
          <h4 class="modal-title"><?=$languageArray['delete_reason_code'][$language]?></h4>
          <button type="button" class="close bg-gray-dark color-palette" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-12">
              <div class="form-group">
                <label><?=$languageArray['delete_reason_code'][$language]?> *</label>
                <textarea class="form-control" id="cancelReason" name="cancelReason" rows="3" required></textarea>
              </div>
            </div>
            <input type="hidden" class="form-control" id="id" name="id">
          </div>
        </div>
        <div class="modal-footer justify-content-between bg-gray-dark color-palette">
          <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-success" id="submitCancel"><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Values
var weightCount = 0;
var allowPhoto = '<?=$allowPhoto?>';
var categoryOptions = `<?php while($rowCat=mysqli_fetch_assoc($categories)){ ?><option value="<?=$rowCat['id'] ?>"><?=$rowCat['category_name'] ?></option><?php } ?>`;
var productOptions = `<?php while($rowProduct=mysqli_fetch_assoc($products2)){ ?><option value="<?=$rowProduct['id'] ?>" data-category="<?=$rowProduct['category'] ?>"><?=$rowProduct['product_name'] ?></option><?php } ?>`;
var packagingOptions = `<?php while($rowPkg=mysqli_fetch_assoc($packagings2)){ ?><option value="<?=$rowPkg['id'] ?>" data-weight="<?=$rowPkg['weight'] ?>"><?=$rowPkg['packaging_name'] ?></option><?php } ?>`;
var gradeOptions = `<?php while($rowGrade=mysqli_fetch_assoc($grades2)){ ?><option value="<?=$rowGrade['units'] ?>" data-product="<?=$rowGrade['product_id'] ?>" data-id="<?=$rowGrade['id'] ?>"><?=$rowGrade['units'] ?></option><?php } ?>`;

$(function () {
  $('#uomhidden').hide();
  var userRole = '<?=$role ?>';
  const today = new Date();
  const tomorrow = new Date(today);
  const yesterday = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);
  yesterday.setDate(yesterday.getDate() - 7);

  $('#fromDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: today
  });

  $('#toDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: today
  });

  $('#packagingDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY HH:mm'
  });

  $('.select2').each(function() {
    $(this).select2({
        allowClear: true,
        placeholder: "Please Select",
        // Conditionally set dropdownParent based on the element’s location
        dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-body') : undefined
    });
  });

  var fromDateI = $('#fromDate').val();
  var toDateI = $('#toDate').val();
  var locationI = $('#locationFilter').val() ? $('#locationFilter').val() : '';
  var productionLineI = $('#productionLineFilter').val() ? $('#productionLineFilter').val() : '';

  var table = $("#weightTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'searching': true,
    'order': [[ 1, 'asc' ]],
    'columnDefs': [ { orderable: false, targets: [0] }],
    'ajax': {
      'url':'php/modules/packagingBatches/filterPackagingBatches.php',
      'data': {
        fromDate: fromDateI,
        toDate: toDateI,
        location: locationI,
        productionLine: productionLineI
      } 
    },
    'columns': [
      { data: 'batch_no' },
      { data: 'packaging_date' },
      { data: 'locations' },
      { data: 'production_line' },
      { 
        data: 'id',
        class: 'action-button',
        render: function ( data, type, row ) {
          var buttons = '<div class="d-flex flex-nowrap" style="gap:4px;">';
          if(<?=$allowEdit == 'Y' ? 'true' : 'false'?>) {
            buttons += '<button type="button" id="edit'+data+'" onclick="edit('+data+')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button>';
          }
          // buttons += '<button type="button" id="print'+data+'" onclick="print('+data+')" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></button>';
          if(<?=$allowDelete == 'Y' ? 'true' : 'false'?>) {
            buttons += '<button type="button" id="deactivate'+data+'" onclick="deactivate('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>';
          }
          buttons += '</div>';
          return buttons;
        }
      }
    ]
  });

  // Add event listener for opening and closing details on row click
  $('#weightTable tbody').on('click', 'tr', function (e) {
      var tr = $(this); // The row that was clicked
      var row = table.row(tr);

      // Exclude clicks on buttons, checkboxes, and form elements
      if ($(e.target).closest('td').hasClass('select-checkbox') || 
          $(e.target).closest('td').hasClass('action-button') ||
          $(e.target).is('select') || 
          $(e.target).is('input') ||
          $(e.target).is('button')) {
        return;
      }

      if (row.child.isShown()) {
          // This row is already open - close it
          row.child.hide();
          tr.removeClass('shown');
      } else {
          $.post('php/modules/packagingBatches/getpackagingBatch.php', { userID: row.data().id}, function (data) {
            var obj = JSON.parse(data);
            if (obj.status === 'success') {
              row.child(format(obj.message)).show();
              tr.addClass("shown");
              if(obj.message.weightDetails && obj.message.weightDetails.length > 0) {
                populateFilters(obj.message.id, obj.message.weightDetails);
              }
            }
          });
      }
  });

  $('#filterSearch').on('click', function(){
    //$('#spinnerLoading').show();
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var locationI = $('#locationFilter').val() ? $('#locationFilter').val() : '';
    var productionLineI = $('#productionLineFilter').val() ? $('#productionLineFilter').val() : '';

    //Destroy the old Datatable
    $("#weightTable").DataTable().clear().destroy();

    //Create new Datatable
    table = $("#weightTable").DataTable({
      "responsive": true,
      "autoWidth": false,
      'processing': true,
      'serverSide': true,
      'serverMethod': 'post',
      'searching': true,
      'order': [[ 1, 'asc' ]],
      'columnDefs': [ { orderable: false, targets: [0] }],
      'ajax': {
      'url':'php/modules/packagingBatches/filterPackagingBatches.php',
        'data': {
          fromDate: fromDateI,
          toDate: toDateI,
          location: locationI,
          productionLine: productionLineI
        } 
      },
      'columns': [
        { data: 'batch_no' },
        { data: 'packaging_date' },
        { data: 'locations' },
        { data: 'production_line' },
        { 
          data: 'id',
          class: 'action-button',
          render: function ( data, type, row ) {
            var buttons = '<div class="d-flex flex-nowrap" style="gap:4px;">';
            if(<?=$allowEdit == 'Y' ? 'true' : 'false'?>) {
              buttons += '<button type="button" id="edit'+data+'" onclick="edit('+data+')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button>';
            }
            // buttons += '<button type="button" id="print'+data+'" onclick="print('+data+')" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></button>';
            if(<?=$allowDelete == 'Y' ? 'true' : 'false'?>) {
              buttons += '<button type="button" id="deactivate'+data+'" onclick="deactivate('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>';
            }
            buttons += '</div>';
            return buttons;
          }
        }
      ],
    });
  });

  $.validator.setDefaults({
    submitHandler: function () {
      if($('#extendModal').hasClass('show')){
        // Validate select2 dropdowns in weight details rows
        var valid = true;
        var errorMsg = '';
        $('#weightDetailsTable tr').each(function(i) {
          var rowNum = i + 1;
          if (!$(this).find('select[name*="[category]"]').val()) { errorMsg = 'Row ' + rowNum + ': Category is required.'; valid = false; return false; }
          if (!$(this).find('select[name*="[product]"]').val()) { errorMsg = 'Row ' + rowNum + ': Product is required.'; valid = false; return false; }
          if (!$(this).find('select[name*="[grade]"]').val()) { errorMsg = 'Row ' + rowNum + ': Grade is required.'; valid = false; return false; }
          if (!$(this).find('select[name*="[packaging_size]"]').val()) { errorMsg = 'Row ' + rowNum + ': Packaging size is required.'; valid = false; return false; }
        });
        if (!valid) { toastr["error"](errorMsg, "Validation Error:"); return; }
        $('#spinnerLoading').show();
        var formData = new FormData($('#extendForm')[0]);
        $.ajax({
          url: 'php/modules/packagingBatches/packagingBatch.php',
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          success: function(data){
            var obj = JSON.parse(data); 
            if(obj.status === 'success'){
              $('#extendModal').modal('hide');
              toastr["success"](obj.message, "Success:");
              $('#weightTable').DataTable().ajax.reload();
            }
            else if(obj.status === 'failed'){
              toastr["error"](obj.message, "Failed:");
            }
            else{
              toastr["error"]("Something wrong when edit", "Failed:");
            }
            $('#spinnerLoading').hide();
          },
          error: function(){
            toastr["error"]("Something wrong when saving", "Failed:");
            $('#spinnerLoading').hide();
          }
        });
      }else if($('#cancelModal').hasClass('show')){
        $('#spinnerLoading').show();
        $.post('php/modules/packagingBatches/deletePackagingBatch.php', $('#cancelForm').serialize(), function(data){
          var obj = JSON.parse(data);

          if(obj.status === 'success'){
            $('#cancelModal').modal('hide');
            toastr["success"](obj.message, "Success:");
            $('#weightTable').DataTable().ajax.reload();
            
          }
          else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
          }
          else{
            toastr["error"]("Something wrong when delete", "Failed:");
          }
          $('#spinnerLoading').hide();
        });
      }
    }
  });

  $('#addWeightBtn').on('click', function() {
    var idx = weightCount++;
    var now = new Date();
    var currentTime = now.getHours().toString().padStart(2, '0') + ':' + 
                      now.getMinutes().toString().padStart(2, '0') + ':' + 
                      now.getSeconds().toString().padStart(2, '0');
    var row = `
      <tr class="details">
        <input type="hidden" name="weightDetails[${idx}][batchItemId]" value="">
        <td>
          <select class="form-control select2" id="category${idx}" name="weightDetails[${idx}][category]" required>
            <option value="" selected disabled>Select Category</option>
            ${categoryOptions}
          </select>
        </td>
        <td>
          <select class="form-control select2" id="product${idx}" name="weightDetails[${idx}][product]" required>
            <option value="" selected disabled>Select Product</option>
            ${productOptions}
          </select>
        </td>
        <td>
          <select class="form-control select2" id="grade${idx}" name="weightDetails[${idx}][grade]" required>
            <?php while($rowGrade=mysqli_fetch_assoc($grades)){ ?>
              <option value="<?=$rowGrade['units'] ?>" data-product="<?=$rowGrade['product_id'] ?>" data-id="<?=$rowGrade['id'] ?>"><?=$rowGrade['units'] ?></option>
            <?php } ?>
          </select>
        </td>
        <td>
          <select class="form-control select2" id="packagingSize${idx}" name="weightDetails[${idx}][packaging_size]" required>
            <option value="" selected disabled>Select Packaging</option>
            <?php while($rowPkg=mysqli_fetch_assoc($packagings3)){ ?>
              <option value="<?=$rowPkg['id'] ?>" data-weight="<?=$rowPkg['weight'] ?>"><?=$rowPkg['packaging_name'] ?></option>
            <?php } ?>
          </select>
        </td>
        <td><input type="number" class="form-control" id="unitPerBox${idx}" name="weightDetails[${idx}][unit_per_box]" step="1" value="0" min="1" required></td>
        <td><input type="number" class="form-control" id="weight${idx}" name="weightDetails[${idx}][weight]" step="0.01" value="0.00" min="0.01" required></td>
        <td>
          <input type="time" class="form-control" id="time${idx}" name="weightDetails[${idx}][time]" value="${currentTime}" required/>
        </td>
        <td ${allowPhoto == 'Y' ? '' : 'style="display:none"'}>
          <input type="hidden" id="photo${idx}" name="weightDetails[${idx}][photoPath]" value="">
          <input type="file" name="photoFiles[${idx}]" id="photoFile${idx}" accept=".png,.jpg,.jpeg" style="display:none">
          <button type="button" class="btn btn-info btn-sm" onclick="$('#photoFile${idx}').click()"><i class="fas fa-camera"></i></button>
          <span id="photoStatus${idx}"></span>
        </td>
        <td>
          <button type="button" class="btn btn-danger btn-sm" onclick="removeWeightDetail(this)"><i class="fas fa-trash"></i></button>
        </td>
      </tr>
    `;
    $('#weightDetailsTable').append(row);

    $('.select2').select2({
      allowClear: true,
      placeholder: "Please Select",
      dropdownParent: $('#extendModal .modal-body'),
      width: '100%'
    });
  });

  $('#weightDetailsTable').on('change', 'select[name*="[category]"]', function() {
    $(this).removeClass('is-invalid').closest('td').find('.invalid-feedback').remove();
    var row = $(this).closest('tr');
    var selectedCategory = $(this).val();
    var productSelect = row.find('select[name*="[product]"]');

    productSelect.select2('destroy');
    if (!productSelect.data('original-options')) {
      productSelect.data('original-options', productSelect.html());
    }
    productSelect.html(productSelect.data('original-options'));

    if (selectedCategory) {
      productSelect.find('option').each(function() {
        if ($(this).val() && $(this).data('category') != selectedCategory) {
          $(this).remove();
        }
      });
    }

    productSelect.val('').select2({
      allowClear: true,
      placeholder: "Please Select",
      dropdownParent: $('#extendModal .modal-body'),
      width: '100%'
    });
  });

  $('#weightDetailsTable').on('change', 'select[name*="[product]"]', function() {
    $(this).removeClass('is-invalid').closest('td').find('.invalid-feedback').remove();
    var row = $(this).closest('tr');
    var productId = $(this).val();
    var productName = $(this).find('option:selected').text();
    
    // Filter grades by selected product
    var gradeSelect = row.find('select[name*="[grade]"]');
    var currentGrade = gradeSelect.val();
    var currentGradeId = gradeSelect.find(':selected').data('id');

    // Destroy Select2 before modifying options
    gradeSelect.select2('destroy');
    
    // Store all original options if not already stored
    if (!gradeSelect.data('original-options')) {
      gradeSelect.data('original-options', gradeSelect.html());
    }
    
    // Reset to original options
    gradeSelect.html(gradeSelect.data('original-options'));
    
    if(productId) {
      // Remove options that don't match the selected product
      gradeSelect.find('option').each(function() {
        var gradeProduct = $(this).attr('data-product');
        if(gradeProduct && gradeProduct != productId) {
          $(this).remove();
        }
      });
    }
    
    // Recreate Select2
    gradeSelect.select2({
      allowClear: true,
      placeholder: "Please Select",
      dropdownParent: $('#extendModal .modal-body'),
      width: '100%'
    });
    
    gradeSelect.val(currentGrade).trigger('change');
  });

  // Auto-fill weight from selected packaging size
  $('#weightDetailsTable').on('change', 'select[name*="[packaging_size]"]', function() {
    var weight = $(this).find('option:selected').data('weight');
    if (weight) {
      $(this).closest('tr').find('input[name*="[weight]"]').val(parseFloat(weight).toFixed(2));
    }
  });

  $('#bulkPackagingSize').on('change', function() {
    var weight = $(this).find('option:selected').data('weight');
    if (weight) $('#bulkWeight').val(parseFloat(weight).toFixed(2));
  });

  // Fix scroll when nested modal opens
  $('#bulkAddModal').on('show.bs.modal', function() {
    $('body').addClass('modal-open');
  }).on('hidden.bs.modal', function() {
    $('body').addClass('modal-open');
  });

  // Bulk Add
  var now = new Date();
  $('#bulkAddBtn').on('click', function() {
    $('#bulkAddModal').find('#bulkNo').val(1);
    $('#bulkAddModal').find('#bulkCategory').val('').trigger('change');
    $('#bulkAddModal').find('#bulkProduct').val('').trigger('change');
    $('#bulkAddModal').find('#bulkGrade').val('').trigger('change');
    $('#bulkAddModal').find('#bulkPackagingSize').val('').trigger('change');
    $('#bulkAddModal').find('#bulkUnitPerBox').val(0);
    $('#bulkAddModal').find('#bulkWeight').val(0);
    $('#bulkAddModal').find('#bulkTime').val(now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0') + ':' + now.getSeconds().toString().padStart(2,'0'));

    $('#bulkAddModal').modal('show');
  });

  $('#bulkCategory').on('change', function() {
    var selectedCategory = $(this).val();
    var productSelect = $('#bulkProduct');
    if (!productSelect.data('original-options')) {
      productSelect.data('original-options', productSelect.html());
    }
    productSelect.html(productSelect.data('original-options'));
    productSelect.find('option').each(function() {
      if ($(this).val() && $(this).data('category') != selectedCategory) {
        $(this).remove();
      }
    });
    productSelect.val('').select2({ allowClear: true, placeholder: "Please Select", dropdownParent: $('#bulkAddModal .modal-body'), width: '100%' });
    $('#bulkGrade').val('').trigger('change');
  });

  $('#bulkProduct').on('change', function() {
    var productId = $(this).val();
    var gradeSelect = $('#bulkGrade');
    if (!gradeSelect.data('original-options')) {
      gradeSelect.data('original-options', gradeSelect.html());
    }
    gradeSelect.html(gradeSelect.data('original-options'));
    gradeSelect.find('option').each(function() {
      if ($(this).attr('data-product') && $(this).attr('data-product') != productId) {
        $(this).remove();
      }
    });
    gradeSelect.val('').select2({ allowClear: true, placeholder: "Please Select", dropdownParent: $('#bulkAddModal .modal-body'), width: '100%' });
  });

  $('#bulkAddForm').on('submit', function(e) {
    e.preventDefault();
    var valid = true;
    ['#bulkCategory','#bulkProduct','#bulkGrade','#bulkPackagingSize'].forEach(function(id) {
      var el = $(id);
      if (!el.val()) {
        el.addClass('is-invalid').next('.select2-container').find('.select2-selection').addClass('is-invalid');
        el.closest('.form-group').find('.invalid-feedback').show();
        valid = false;
      } else {
        el.removeClass('is-invalid');
        el.closest('.form-group').find('.invalid-feedback').hide();
      }
    });
    if (!valid) return;
    var bulkNo = parseInt($('#bulkNo').val());
    if (!bulkNo || bulkNo < 1) { alert('Please enter a valid bulk number.'); return; }

    var categoryVal = $('#bulkCategory').val();
    var categoryText = $('#bulkCategory option:selected').text();
    var productVal = $('#bulkProduct').val();
    var productText = $('#bulkProduct option:selected').text();
    var gradeVal = $('#bulkGrade').val();
    var packagingVal = $('#bulkPackagingSize').val();
    var unitPerBox = $('#bulkUnitPerBox').val();
    var weight = $('#bulkWeight').val();
    var time = $('#bulkTime').val();

    for (var i = 0; i < bulkNo; i++) {
      var idx = weightCount++;
      var row = `
        <tr class="details">
          <input type="hidden" name="weightDetails[${idx}][batchItemId]" value="">
          <td>
            <select class="form-control select2" id="category${idx}" name="weightDetails[${idx}][category]" required>
              <option value="" selected disabled>Select Category</option>
              ${categoryOptions}
            </select>
          </td>
          <td>
            <select class="form-control select2" id="product${idx}" name="weightDetails[${idx}][product]" required>
              <option value="" selected disabled>Select Product</option>
              ${productOptions}
            </select>
          </td>
          <td>
            <select class="form-control select2" id="grade${idx}" name="weightDetails[${idx}][grade]" required>
              ${gradeOptions}
            </select>
          </td>
          <td>
            <select class="form-control select2" id="packagingSize${idx}" name="weightDetails[${idx}][packaging_size]" required>
              <option value="" selected disabled>Select Packaging</option>
              ${packagingOptions}
            </select>
          </td>
          <td><input type="number" class="form-control" id="unitPerBox${idx}" name="weightDetails[${idx}][unit_per_box]" step="1" value="${unitPerBox}" min="1" required></td>
          <td><input type="number" class="form-control" id="weight${idx}" name="weightDetails[${idx}][weight]" step="0.01" value="${parseFloat(weight).toFixed(2)}" min="0.01" required></td>
          <td><input type="time" class="form-control" id="time${idx}" name="weightDetails[${idx}][time]" value="${time}" required/></td>
          <td ${allowPhoto == 'Y' ? '' : 'style="display:none"'}>
            <input type="hidden" id="photo${idx}" name="weightDetails[${idx}][photoPath]" value="">
            <input type="file" name="photoFiles[${idx}]" id="photoFile${idx}" accept=".png,.jpg,.jpeg" style="display:none">
            <button type="button" class="btn btn-info btn-sm" onclick="$('#photoFile${idx}').click()"><i class="fas fa-camera"></i></button>
            <span id="photoStatus${idx}"></span>
          </td>
          <td>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeWeightDetail(this)"><i class="fas fa-trash"></i></button>
          </td>
        </tr>
      `;
      $('#weightDetailsTable').append(row);

      var tr = $('#weightDetailsTable tr:last');

      // Set category and filter products
      var catSelect = tr.find(`select[name="weightDetails[${idx}][category]"]`);
      catSelect.val(categoryVal);

      var prodSelect = tr.find(`select[name="weightDetails[${idx}][product]"]`);
      prodSelect.data('original-options', prodSelect.html());
      prodSelect.find('option').each(function() {
        if ($(this).val() && $(this).data('category') != categoryVal) { $(this).remove(); }
      });
      prodSelect.val(productVal);

      // Filter and set grade
      var gradeSelect = tr.find(`select[name="weightDetails[${idx}][grade]"]`);
      gradeSelect.data('original-options', gradeSelect.html());
      gradeSelect.find('option').each(function() {
        if ($(this).attr('data-product') && $(this).attr('data-product') != productVal) { $(this).remove(); }
      });
      gradeSelect.val(gradeVal);

      tr.find(`select[name="weightDetails[${idx}][packaging_size]"]`).val(packagingVal);
    }

    $('.select2').select2({
      allowClear: true,
      placeholder: "Please Select",
      dropdownParent: $('#extendModal .modal-body'),
      width: '100%'
    });

    // Set values after Select2 init
    $('#weightDetailsTable tr').slice(-bulkNo).each(function(i) {
      var idx = weightCount - bulkNo + i;
      $(this).find(`select[name="weightDetails[${idx}][category]"]`).val(categoryVal).trigger('change.select2');
      $(this).find(`select[name="weightDetails[${idx}][product]"]`).val(productVal).trigger('change.select2');
      $(this).find(`select[name="weightDetails[${idx}][grade]"]`).val(gradeVal).trigger('change.select2');
      $(this).find(`select[name="weightDetails[${idx}][packaging_size]"]`).val(packagingVal).trigger('change.select2');
    });

    $('#bulkAddModal').modal('hide');
  });

  $('#saveButton').on('click', function(e) {
    var valid = true;
    $('#weightDetailsTable tr').each(function(i) {
      var rowNum = i + 1;
      var row = $(this);
      ['[category]','[product]','[grade]','[packaging_size]'].forEach(function(field) {
        var sel = row.find('select[name*="' + field + '"]');
        if (sel.length && !sel.val()) {
          sel.addClass('is-invalid');
          sel.closest('td').find('.invalid-feedback').remove();
          sel.closest('td').append('<div class="invalid-feedback d-block">Required.</div>');
          valid = false;
        } else {
          sel.removeClass('is-invalid');
          sel.closest('td').find('.invalid-feedback').remove();
        }
      });
    });
    if (!valid) { e.preventDefault(); e.stopImmediatePropagation(); }
  });

  // Show tick when file is selected
  $('#extendForm').on('change', 'input[type="file"]', function() {
    var statusSpan = $(this).siblings('span[id$="Status"], span[id*="photoStatus"], span[id*="PhotoStatus"]');
    if (this.files && this.files[0]) {
      statusSpan.html('<i class="fas fa-check-circle text-success"></i>');
    } else {
      statusSpan.html('');
    }
  });
});

function format (row) {
  var returnString = `
  <!-- Wholesale Information -->
  <div class="row">
    <p><span><strong style="font-size:120%; text-decoration: underline;">Grading Information</strong></span>
  </div>
  <div class="row">
    <div class="col-6">
      <p><strong><?=$languageArray['grading_no_code'][$language]?>:</strong> ${row.grading_no}</p>
      <p><strong><?=$languageArray['category_code'][$language]?>:</strong> ${row.category}</p>
    </div>
    <div class="col-6">
      <p><strong><?=$languageArray['start_time_code'][$language]?>:</strong> ${row.start_date}</p>
      <p><strong><?=$languageArray['end_time_code'][$language]?>:</strong> ${row.end_date || ''}</p>
    </div>
  </div>
  <div class="row">
    <div class="col-12">
      <p><strong><?=$languageArray['remark_code'][$language]?>:</strong> ${row.remark || ''}</p>
    </div>
  </div>
    <hr>
  <h3><?=$languageArray['weighing_details_code'][$language]?></h3>
  <div class="row mb-2">
    <div class="col-md-3">
      <select class="form-control" id="productFilter_${row.id}" onchange="filterWeightTable('${row.id}')">
        <option value=""><?=$languageArray['all_products_code'][$language]?></option>
      </select>
    </div>
    <div class="col-md-3">
      <select class="form-control" id="gradeFilter_${row.id}" onchange="filterWeightTable('${row.id}')">
        <option value=""><?=$languageArray['all_grades_code'][$language]?></option>
      </select>
    </div>
  </div>
  <div class="row">
    <table class="table table-bordered nowrap table-striped align-middle" id="weightTable_${row.id}" style="width:100%">
      <thead>
          <tr>
            <th><?=$languageArray['product_code'][$language]?></th>
            <th><?=$languageArray['grade_code'][$language]?></th>
            <th><?=$languageArray['gross_code'][$language]?></th>
            <th><?=$languageArray['tare_code'][$language]?></th>
            <th><?=$languageArray['net_code'][$language]?></th>
            <th><?=$languageArray['time_code'][$language]?></th>
            ${allowPhoto == 'Y' ? '<th>Photo</th>' : ''}
          </tr>
      </thead>
      <tbody>`;

      var totalWeightGross = 0;
      var totalWeightTare = 0;
      var totalWeightNet = 0;
      for (var i = 0; i < row.weightDetails.length; i++) {
        var detail = row.weightDetails[i]; 
        
        returnString += `
            <tr>
              <td>${detail.product_name}</td>
              <td>${detail.to_grade}</td>
              <td>${parseFloat(detail.gross_weight).toFixed(2)}</td>
              <td>${parseFloat(detail.tare_weight).toFixed(2)}</td>
              <td>${parseFloat(detail.nett_weight).toFixed(2)}</td>
              <td>${detail.weighing_time}</td>
              ${allowPhoto == 'Y' ? '<td>' + (detail.photoPath ? '<a href="php/viewPhoto.php?file=' + detail.photoPath + '" target="_blank" class="btn btn-success btn-sm" title="View Photo"><i class="fas fa-image"></i></a>' : '') + '</td>' : ''}`;
            returnString += `
            </tr>`;

        totalWeightGross += parseFloat(detail.gross_weight);
        totalWeightTare += parseFloat(detail.tare_weight);
        totalWeightNet += parseFloat(detail.nett_weight);
      }

      returnString += `
      </tbody>
    </table>
  </div>

  `;

  return returnString;
}

function newEntry(){
  $('#extendModal').find('#id').val("");
  $('#extendModal').find('#batchNo').val("");
  $('#extendModal').find('#packagingDate').val("");
  $('#packagingDatePicker').datetimepicker('date', moment());
  $('#extendModal').find('#remarks').val("");
  $('#extendModal').find('#category').val("").trigger('change');
  $('#weightDetailsTable').empty();
  $('#extendModal').modal('show');
  
  $('#extendForm').validate({
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
}

function edit(id) {
  $('#spinnerLoading').show();
  $.post('php/modules/packagingBatches/getPackagingBatch.php', {userID: id}, function(data){
    var obj = JSON.parse(data);
    
    if(obj.status === 'success'){
      $('#extendModal').find('#id').val(obj.message.id);
      $('#extendModal').find('#batchNo').val(obj.message.batch_no);
      $('#extendModal').find('#remarks').val(obj.message.remarks);
      $('#extendModal').find('#location').val(obj.message.location).trigger('change');
      $('#extendModal').find('#productionLines').val(obj.message.production_line).trigger('change');
      
      if (obj.message.packaging_date) {
        $('#packagingDatePicker').datetimepicker('date', moment(obj.message.packaging_date, 'YYYY-MM-DD HH:mm:ss'));
      } else {
        $('#packagingDatePicker').datetimepicker('clear');
      }
      
      // Populate weight details table
      var tbody = $('#weightDetailsTable');
      tbody.empty();
      
      if(obj.message.weightDetails && obj.message.weightDetails.length > 0) {
        for(var i = 0; i < obj.message.weightDetails.length; i++) {
          var detail = obj.message.weightDetails[i];
          var idx = weightCount++;
          var timeVal = detail.packing_time || '';
          var row = `
            <tr class="details">
              <input type="hidden" name="weightDetails[${idx}][batchId]" value="${detail.id || ''}">
              <td>
                <select class="form-control select2" id="category${idx}" name="weightDetails[${idx}][category]" required>
                  <option value="" selected disabled>Select Category</option>
                  <?php while($rowCat=mysqli_fetch_assoc($categories2)){ ?>
                    <option value="<?=$rowCat['id'] ?>"><?=$rowCat['category_name'] ?></option>
                  <?php } ?>
                </select>
              </td>
              <td>
                <select class="form-control select2" id="product${idx}" name="weightDetails[${idx}][product]" required>
                  <option value="" selected disabled>Select Product</option>
                  ${productOptions}
                </select>
              </td>
              <td>
                <select class="form-control select2" id="grade${idx}" name="weightDetails[${idx}][grade]" required>
                  ${gradeOptions}
                </select>
              </td>
              <td>
                <select class="form-control select2" id="packagingSize${idx}" name="weightDetails[${idx}][packaging_size]" required>
                  <option value="" selected disabled>Select Packaging</option>
                  ${packagingOptions}
                </select>
              </td>
              <td><input type="number" class="form-control" id="unitPerBox${idx}" name="weightDetails[${idx}][unit_per_box]" value="${detail.units_per_box || 0}" step="1" min="1" required></td>
              <td><input type="number" class="form-control" id="weight${idx}" name="weightDetails[${idx}][weight]" value="${(parseFloat(detail.weight)||0).toFixed(2)}" step="0.01" min="0.01" required></td>
              <td><input type="time" class="form-control" id="time${idx}" name="weightDetails[${idx}][time]" value="${timeVal}" required></td>
              <td ${allowPhoto == 'Y' ? '' : 'style="display:none"'}>
                <input type="hidden" id="photo${idx}" name="weightDetails[${idx}][photoPath]" value="${detail.photo_path || ''}">
                <input type="file" name="photoFiles[${idx}]" id="photoFile${idx}" accept=".png,.jpg,.jpeg" style="display:none">
                ${detail.photo_path ? '<a href="php/viewPhoto.php?file=' + detail.photo_path + '" target="_blank" class="btn btn-success btn-sm mr-1" title="View Photo"><i class="fas fa-image"></i></a>' : ''}
                <button type="button" class="btn btn-info btn-sm" onclick="$('#photoFile${idx}').click()"><i class="fas fa-camera"></i></button>
                <span id="photoStatus${idx}"></span>
              </td>
              <td>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeWeightDetail(this)"><i class="fas fa-trash"></i></button>
              </td>
            </tr>
          `;
          tbody.append(row);

          // Set category
          tbody.find(`select[name="weightDetails[${idx}][category]"]`).val(detail.category_id);

          // Filter and set product
          var newProductSelect = tbody.find(`select[name="weightDetails[${idx}][product]"]`);
          newProductSelect.data('original-options', newProductSelect.html());
          newProductSelect.find('option').each(function() {
            if ($(this).val() && $(this).data('category') != detail.category_id) {
              $(this).remove();
            }
          });
          newProductSelect.val(detail.product_id);

          // Filter and set grade
          var gradeSelect = tbody.find(`select[name="weightDetails[${idx}][grade]"]`);
          gradeSelect.data('original-options', gradeSelect.html());
          gradeSelect.find('option').each(function() {
            if ($(this).attr('data-product') && $(this).attr('data-product') != detail.product_id) {
              $(this).remove();
            }
          });
          gradeSelect.val(detail.grade);

          // Set packaging size
          tbody.find(`select[name="weightDetails[${idx}][packaging_size]"]`).val(detail.packaging_size);
        }
      }
      
      $('.select2').each(function() {
        $(this).select2({
          allowClear: true,
          placeholder: "Please Select",
          // Conditionally set dropdownParent based on the element’s location
          dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-body') : undefined
        });
      });
      
      $('#extendModal').modal('show');

      $('#extendForm').validate({
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
    }
    else if(obj.status === 'failed'){
      toastr["error"](obj.message, "Failed:");
    }
    else{
      toastr["error"]("Something wrong when pull data", "Failed:");
    }
    $('#spinnerLoading').hide();
  });
}

function reindexWeightDetails() {
  $('#weightDetailsTable tr').each(function(index) {
    $(this).find('input, select').each(function() {
      var name = $(this).attr('name');
      if(name) {
        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
      }
    });
  });
}

function removeWeightDetail(button) {
  $(button).closest('tr').remove();
  reindexWeightDetails();
  updateTotals();
}

function updateTotals() {
  var totalGross = 0, totalTare = 0, totalNet = 0, totalPrice = 0;
  $('#weightDetailsTable tr').each(function() {
    totalGross += parseFloat($(this).find('input[name*="[gross]"]').val() || 0);
    totalTare += parseFloat($(this).find('input[name*="[tare]"]').val() || 0);
    totalNet += parseFloat($(this).find('input[name*="[net]"]').val() || 0);
  });
  $('#totalWeightGross').text(totalGross.toFixed(2));
  $('#totalWeightTare').text(totalTare.toFixed(2));
  $('#totalWeightNet').text(totalNet.toFixed(2));
  
}

function deactivate(id) {
  if (confirm('Are you sure you want to delete this item?')) {
    $('#cancelModal').find('#id').val(id);
    $('#cancelModal').modal('show');

    $('#cancelForm').validate({
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
  }
}

function print(id) {
  $.post('php/print.php', {userID: id}, function(data){
    var obj = JSON.parse(data);
    if(obj.status === 'success') {
      var printWindow = window.open('', '', 'height=' + screen.height + ',width=' + screen.width);
      printWindow.document.write(obj.message);
      printWindow.document.close();
      setTimeout(function(){
        printWindow.print();
        printWindow.close();
      }, 500);
    }
    else if(obj.status === 'failed'){
      alert(obj.message);
    }
    else{
      alert("Something wrong when activate");
    }
  });
}

function filterWeightTable(rowId) {
  var productFilter = $('#productFilter_' + rowId).val();
  var gradeFilter = $('#gradeFilter_' + rowId).val();
  
  var totalGross = 0, totalTare = 0, totalNet = 0;
  
  $('#weightTable_' + rowId + ' tbody tr').each(function() {
    var product = $(this).find('td:eq(0)').text();
    var grade = $(this).find('td:eq(1)').text();
    var showProduct = !productFilter || product == productFilter;
    var showGrade = !gradeFilter || grade == gradeFilter;
    var show = showProduct && showGrade;
    $(this).toggle(show);
    
    if(show) {
      var grossText = $(this).find('td:eq(2)').text().split(' ')[0];
      var tareText = $(this).find('td:eq(3)').text().split(' ')[0];
      var netText = $(this).find('td:eq(4)').text().split(' ')[0];
      
      totalGross += parseFloat(grossText) || 0;
      totalTare += parseFloat(tareText) || 0;
      totalNet += parseFloat(netText) || 0;
    }
  });
  
  $('#weightTable_' + rowId + ' tfoot tr th:eq(1)').text(totalGross.toFixed(2));
  $('#weightTable_' + rowId + ' tfoot tr th:eq(2)').text(totalTare.toFixed(2));
  $('#weightTable_' + rowId + ' tfoot tr th:eq(3)').text(totalNet.toFixed(2));
  
  if(productFilter) {
    var gradeSelect = $('#gradeFilter_' + rowId);
    var currentGrade = gradeSelect.val();
    gradeSelect.find('option:not(:first)').remove();
    
    var grades = [];
    $('#weightTable_' + rowId + ' tbody tr').each(function() {
      var product = $(this).find('td:eq(0)').text();
      if(product === productFilter) {
        var grade = $(this).find('td:eq(1)').text();
        if(grades.indexOf(grade) === -1) {
          grades.push(grade);
        }
      }
    });
    
    grades.sort();
    grades.forEach(function(grade) {
      gradeSelect.append('<option value="' + grade + '">' + grade + '</option>');
    });
    gradeSelect.val(currentGrade);
  } else {
    var gradeSelect = $('#gradeFilter_' + rowId);
    var currentGrade = gradeSelect.val();
    gradeSelect.find('option:not(:first)').remove();
    
    var grades = [];
    $('#weightTable_' + rowId + ' tbody tr').each(function() {
      var grade = $(this).find('td:eq(1)').text();
      if(grades.indexOf(grade) === -1) {
        grades.push(grade);
      }
    });
    
    grades.sort();
    grades.forEach(function(grade) {
      gradeSelect.append('<option value="' + grade + '">' + grade + '</option>');
    });
    gradeSelect.val(currentGrade);
  }
}

function populateFilters(rowId, weightDetails) {
  var products = {};
  var grades = [];
  
  weightDetails.forEach(function(detail) {
    products[detail.product_name] = true;
    if(grades.indexOf(detail.grade) === -1) {
      grades.push(detail.grade);
    }
  });
  
  var productSelect = $('#productFilter_' + rowId);
  for(var product in products) {
    productSelect.append('<option value="' + product + '">' + product + '</option>');
  }
  
  grades.sort();
  var gradeSelect = $('#gradeFilter_' + rowId);
  grades.forEach(function(grade) {
    gradeSelect.append('<option value="' + grade + '">' + grade + '</option>');
  });
}

</script>