<?php
require_once 'php/db_connect.php';
session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
}
else{
  $company = $_SESSION['customer'];
  $user = $_SESSION['userID'];
  $role = $_SESSION['role'];
  $companies = $db->query("SELECT * FROM companies WHERE deleted = 0 ORDER BY name ASC");
  $units = $db->query("SELECT * FROM units WHERE deleted = '0' ORDER BY units ASC");
  $units2 = $db->query("SELECT * FROM units WHERE deleted = '0' ORDER BY units ASC");
  $units3 = $db->query("SELECT * FROM units WHERE deleted = '0' ORDER BY units ASC");
  $units4 = $db->query("SELECT * FROM units WHERE deleted = '0' ORDER BY units ASC");

  if ($role != 'SADMIN'){
    $customers = $db->query("SELECT * FROM customers WHERE deleted = 0 AND customer = '".$company."' ORDER BY customer_name ASC");
    $grades = $db->query("SELECT * FROM grades WHERE deleted = 0 AND customer = '".$company."' ORDER BY units ASC");
    $category = $db->query("SELECT * FROM categories WHERE deleted = 0 AND customer = '".$company."' ORDER BY category_name ASC");
    $packaging = $db->query("SELECT * FROM packaging WHERE deleted = 0 AND customer = '".$company."' ORDER BY packaging_name ASC");
  }
  else{
    $customers = $db->query("SELECT * FROM customers WHERE deleted = 0 ORDER BY customer_name ASC");
    $grades = $db->query("SELECT * FROM grades WHERE deleted = 0 ORDER BY units ASC");
    $category = $db->query("SELECT * FROM categories WHERE deleted = 0 ORDER BY category_name ASC");
    $packaging = $db->query("SELECT * FROM packaging WHERE deleted = 0 ORDER BY packaging_name ASC");
  }

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>

<div class="content-header">
  <div class="container-fluid">
      <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0 text-dark"><?=$languageArray['products_code'][$language]?></h1>
    </div><!-- /.col -->
      </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</div><!-- /.content-header -->

<!-- Main content -->
<section class="content">
  <div class="container-fluid">
      <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col-4"></div>
            <div class="col-2">
              <button type="button" id="multiDeactivate" class="btn btn-block bg-gradient-danger btn-sm">
                <?=$languageArray['delete_product_code'][$language]?>
              </button>
            </div>
            <div class="col-2">
              <a href="template/Product_Template.xlsx" download>
                <button type="button" class="btn btn-block bg-gradient-info btn-sm">
                  <?=$languageArray['download_template_code'][$language]?>
                </button>
              </a>
            </div>
            <div class="col-2">
              <button type="button" id="uploadExcel" class="btn btn-block bg-gradient-success btn-sm">
                <?=$languageArray['upload_excel_code'][$language]?>
              </button>
            </div>
            <!-- <div class="col-2">
                <input type="file" id="fileInput" accept=".xlsx, .xls" />
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="importExcelbtn">Import Excel</button>
            </div>                             -->
            <div class="col-2">
              <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="addProducts"><?=$languageArray['add_products_code'][$language]?></button>
            </div>
          </div>
        </div>
        <div class="card-body">
          <table id="productTable" class="table table-bordered table-striped">
            <thead>
              <tr>
                <th><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
                <th><?=$languageArray['product_code_code'][$language]?></th>
                <th><?=$languageArray['product_name_code'][$language]?></th>
                <!--th>Price</th-->
                <th><?=$languageArray['weight_code'][$language]?></th>
                <th><?=$languageArray['remark_code'][$language]?></th>
                <th><?=$languageArray['actions_code'][$language]?></th>
              </tr>
            </thead>
          </table>
        </div><!-- /.card-body -->
      </div><!-- /.card -->
    </div><!-- /.col -->
  </div><!-- /.row -->
</div><!-- /.container-fluid -->
</section><!-- /.content -->

<div class="modal fade" id="uploadModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form role="form" id="uploadForm">
          <div class="modal-header">
            <h4 class="modal-title"><?=$languageArray['upload_excel_code'][$language]?></h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="card-body">
              <input type="file" id="fileInput">
              <button type="button" id="previewButton"><?=$languageArray['preview_data_code'][$language]?></button>
              <div id="previewTable" style="overflow: auto;"></div>
            </div>
          </div>
          <div class="modal-footer justify-content-between">
            <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
            <button type="button" class="btn btn-success" id="uploadProduct"><?=$languageArray['submit_code'][$language]?></button>
          </div>
      </form>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>

<div class="modal fade" id="errorModal" style="display:none">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form role="form" id="uploadForm">
          <div class="modal-header">
            <h4 class="modal-title"><?=$languageArray['error_log_code'][$language]?></h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="form-group">
                <ol id="errorList" class="text-danger mt-2" style="padding-left: 20px;"></ol>
              </div>
            </div>
          </div>
      </form>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>

<div class="modal fade" id="addModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form role="form" id="productForm">
        <div class="modal-header bg-gradient-dark">
          <h5 class="modal-title text-white"><i class="fas fa-box mr-2"></i><?=$languageArray['add_products_code'][$language]?></h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body" style="max-height:75vh; overflow-y:auto; background:#f4f6f9;">
          <input type="hidden" id="id" name="id">

          <!-- Company (SADMIN only) -->
          <div <?php if($role != 'SADMIN'){ echo 'style="display:none;"'; } ?>>
            <div class="card card-outline card-primary mb-3">
              <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-building mr-1"></i><?=$languageArray['company_code'][$language]?></h6></div>
              <div class="card-body py-2">
                <select class="form-control select2" style="width:100%;" id="company" name="company" required>
                  <?php while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                    <option value="<?=$rowCompany['id']?>" <?php if($rowCompany['id']==$company) echo 'selected';?>><?=$rowCompany['name']?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
          </div>

          <!-- Product Info -->
          <div class="card card-outline card-primary mb-3">
            <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-info-circle mr-1"></i>Product Information</h6></div>
            <div class="card-body py-3">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <label class="font-weight-bold"><?=$languageArray['product_code_code'][$language]?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="code" id="code" placeholder="<?=$languageArray['enter_product_code_code'][$language]?>" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <label class="font-weight-bold"><?=$languageArray['product_name_code'][$language]?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="product" id="product" placeholder="<?=$languageArray['enter_product_name_code'][$language]?>" required>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group mb-2">
                    <label class="font-weight-bold"><?=$languageArray['weight_code'][$language]?></label>
                    <input type="number" class="form-control" name="weight" id="weight" placeholder="0.000">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group mb-2">
                    <label class="font-weight-bold"><?=$languageArray['unit_code'][$language]?></label>
                    <select class="form-control select2" id="uom" name="uom">
                      <option selected>-</option>
                      <?php while($rowunits=mysqli_fetch_assoc($units)){ ?>
                        <option value="<?=$rowunits['id']?>"><?=$rowunits['units']?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group mb-2">
                    <label class="font-weight-bold"><?=$languageArray['category_code'][$language]?></label>
                    <select class="form-control select2" id="productCategory" name="productCategory">
                      <option value="" selected>-</option>
                      <?php while($rowCat=mysqli_fetch_assoc($category)){ ?>
                        <option value="<?=$rowCat['id']?>"><?=$rowCat['category_name']?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group mb-2">
                    <label class="font-weight-bold"><?=$languageArray['packaging_code'][$language]?> / <?=$languageArray['uom_code'][$language]?></label>
                    <select class="form-control select2" id="productPackaging" name="productPackaging">
                      <option value="" selected>-</option>
                      <?php while($rowPack=mysqli_fetch_assoc($packaging)){ ?>
                        <option value="<?=$rowPack['id']?>"><?=$rowPack['packaging_name']?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group mb-2">
                    <label class="font-weight-bold"><?=$languageArray['pricing_type_code'][$language]?></label>
                    <select class="form-control" id="pricingType" name="pricingType">
                      <option selected><?=$languageArray['fixed_code'][$language]?></option>
                      <option><?=$languageArray['float_code'][$language]?></option>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group mb-2">
                    <label class="font-weight-bold"><?=$languageArray['price_code'][$language]?></label>
                    <input type="number" class="form-control" name="price" id="price" placeholder="0.00">
                  </div>
                </div>
              </div>
              <div class="form-group mb-0">
                <label class="font-weight-bold"><?=$languageArray['remark_code'][$language]?></label>
                <textarea class="form-control" id="remark" name="remark" placeholder="<?=$languageArray['enter_remark_code'][$language]?>" rows="2"></textarea>
              </div>
            </div>
          </div>

          <!-- Product Image -->
          <div class="card card-outline card-secondary mb-3">
            <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-image mr-1"></i>Product Image</h6></div>
            <div class="card-body py-3">
              <div class="row align-items-center">
                <div class="col-md-6">
                  <div id="productImageDropzone" style="border:2px dashed #adb5bd; border-radius:6px; padding:24px; text-align:center; cursor:pointer; background:#fff;">
                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                    <p class="mb-1 text-muted">Click or drag &amp; drop to upload</p>
                    <small class="text-muted">PNG, JPG, JPEG — max 10MB</small>
                    <input type="file" id="productImage" name="productImage" accept="image/png,image/jpeg,image/jpg" style="display:none;">
                  </div>
                </div>
                <div class="col-md-6 text-center">
                  <div id="productImagePreview" style="display:none;">
                    <img id="productImageThumb" src="" style="max-height:160px; max-width:100%; border-radius:6px; border:1px solid #dee2e6; object-fit:contain;">
                    <div class="mt-2">
                      <button type="button" id="removeProductImage" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash mr-1"></i>Remove</button>
                    </div>
                  </div>
                  <div id="productImagePlaceholder" style="color:#adb5bd;">
                    <i class="fas fa-image fa-3x"></i>
                    <p class="mt-1 mb-0">No image selected</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Ranges Set -->
          <div class="card card-outline card-warning mb-3">
            <div class="card-header py-2 d-flex align-items-center justify-content-between">
              <h6 class="mb-0 text-danger font-weight-bold"><i class="fas fa-sliders-h mr-1"></i>Ranges Set</h6>
              <div class="ml-auto d-flex align-items-center">
                <input type="hidden" name="rangeSet" id="rangeSet" value="0">
                <div id="rangeSetToggle" style="cursor:pointer; display:inline-flex; align-items:center; background:#ccc; border-radius:30px; width:110px; height:34px; position:relative; transition:background 0.3s;">
                  <div id="rangeSetKnob" style="position:absolute; width:30px; height:30px; background:#fff; border-radius:50%; top:2px; left:2px; transition:left 0.3s; display:flex; align-items:center; justify-content:center; box-shadow:0 1px 3px rgba(0,0,0,0.3);">
                    <i id="rangeSetIcon" class="fas fa-times text-danger"></i>
                  </div>
                  <span id="rangeSetLabel" style="position:absolute; right:10px; font-size:11px; font-weight:600; color:#fff; letter-spacing:0.5px;">Disable</span>
                </div>
              </div>
            </div>
            <div id="rangeWeightFields" class="card-body py-3" style="display:none;">
              <div class="row align-items-center mb-2">
                <div class="col-md-2"><label class="mb-0 font-weight-bold">OK. Weight</label></div>
                <div class="col-md-7">
                  <input type="number" step="any" class="form-control font-weight-bold" id="okWeight" name="okWeight" placeholder="0.000" style="background:rgba(40,167,69,0.25); color:#155724; border:1px solid #28a745;">
                </div>
                <div class="col-md-3">
                  <select class="form-control" id="okWeightUnit" name="okWeightUnit">
                    <?php while($r=mysqli_fetch_assoc($units2)){ ?><option value="<?=$r['id']?>"><?=$r['units']?></option><?php } ?>
                  </select>
                </div>
              </div>
              <div class="row align-items-center mb-2">
                <div class="col-md-2"><label class="mb-0 font-weight-bold">LO. Weight</label></div>
                <div class="col-md-7">
                  <input type="number" step="any" class="form-control font-weight-bold" id="loWeight" name="loWeight" placeholder="0.000" style="background:rgba(255,193,7,0.25); color:#856404; border:1px solid #ffc107;">
                </div>
                <div class="col-md-3">
                  <select class="form-control" id="loWeightUnit" name="loWeightUnit">
                    <?php while($r=mysqli_fetch_assoc($units3)){ ?><option value="<?=$r['id']?>"><?=$r['units']?></option><?php } ?>
                  </select>
                </div>
              </div>
              <div class="row align-items-center">
                <div class="col-md-2"><label class="mb-0 font-weight-bold">HI. Weight</label></div>
                <div class="col-md-7">
                  <input type="number" step="any" class="form-control font-weight-bold" id="hiWeight" name="hiWeight" placeholder="0.000" style="background:rgba(220,53,69,0.2); color:#721c24; border:1px solid #dc3545;">
                </div>
                <div class="col-md-3">
                  <select class="form-control" id="hiWeightUnit" name="hiWeightUnit">
                    <?php while($r=mysqli_fetch_assoc($units4)){ ?><option value="<?=$r['id']?>"><?=$r['units']?></option><?php } ?>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Customers -->
          <div class="card card-outline card-success mb-3">
            <div class="card-header py-2 d-flex align-items-center justify-content-between">
              <h6 class="mb-0"><i class="fas fa-users mr-1"></i><?=$languageArray['customers_code'][$language]?></h6>
              <button type="button" class="btn btn-success btn-sm add-customer ml-auto"><i class="fas fa-plus mr-1"></i><?=$languageArray['add_customers_code'][$language]?></button>
            </div>
            <div class="card-body p-2">
              <table class="table table-sm table-bordered mb-0">
                <thead class="thead-light">
                  <tr>
                    <th width="8%"><?=$languageArray['number_short_code'][$language]?></th>
                    <th><?=$languageArray['customer_code'][$language]?></th>
                    <th><?=$languageArray['pricing_type_code'][$language]?></th>
                    <th><?=$languageArray['price_code'][$language]?> (RM)</th>
                    <th width="8%"><?=$languageArray['actions_code'][$language]?></th>
                  </tr>
                </thead>
                <tbody id="customerTable"></tbody>
              </table>
            </div>
          </div>

          <!-- Grades -->
          <div class="card card-outline card-info mb-0">
            <div class="card-header py-2 d-flex align-items-center justify-content-between">
              <h6 class="mb-0"><i class="fas fa-layer-group mr-1"></i><?=$languageArray['grades_code'][$language]?></h6>
              <button type="button" class="btn btn-info btn-sm add-grade ml-auto"><i class="fas fa-plus mr-1"></i><?=$languageArray['add_grade_code'][$language]?></button>
            </div>
            <div class="card-body p-2">
              <table class="table table-sm table-bordered mb-0">
                <thead class="thead-light">
                  <tr>
                    <th width="8%"><?=$languageArray['number_short_code'][$language]?></th>
                    <th><?=$languageArray['unit_code'][$language]?></th>
                    <th><?=$languageArray['pricing_type_code'][$language]?></th>
                    <th><?=$languageArray['price_code'][$language]?> (RM)</th>
                    <th width="8%"><?=$languageArray['actions_code'][$language]?></th>
                  </tr>
                </thead>
                <tbody id="gradeTable"></tbody>
              </table>
            </div>
          </div>

        </div>
        <div class="modal-footer justify-content-end">
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-primary" name="submit" id="submitMember"><i class="fas fa-save mr-1"></i><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/jquery-validation/jquery.validate.min.js"></script>
<!-- Bootstrap -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE -->
<script src="dist/js/adminlte.js"></script>
<!-- OPTIONAL SCRIPTS -->
<script src="plugins/select2/js/select2.full.min.js"></script>
<script src="plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js"></script>
<script src="plugins/moment/moment.min.js"></script>
<script src="plugins/inputmask/jquery.inputmask.min.js"></script>
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="plugins/daterangepicker/daterangepicker.js"></script>
<script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<script src="plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<script src="plugins/chart.js/Chart.min.js"></script>
<script src="plugins/daterangepicker/daterangepicker.js"></script>

<script type="text/html" id="customerDetail">
  <tr class="details">
    <td>
      <input type="text" class="form-control" id="no" name="no" readonly>
      <input type="text" class="form-control" id="customerProductId" name="customerProductId" hidden>
    </td>
    <td>
      <select class="form-control select2" style="width: 100%; background-color:white;" id="customers" name="customers">
        <?php while($rowCustomer=mysqli_fetch_assoc($customers)){ ?>
          <option value="<?=$rowCustomer['id'] ?>"><?=$rowCustomer['customer_name']?></option>
        <?php } ?>
      </select>
    </td>
    <td>
      <select class="form-control" style="width: 100%; background-color:white;" id="customerPricingType" name="customerPricingType">
        <option selected>Standard</option>
        <option>Fixed</option>
        <option>Float</option>
      </select>
    </td>
    <td>
      <input type="number" class="form-control" id="customerPrice" name="customerPrice" style="background-color:white;" value="0">
    </td>
    <td class="d-flex" style="text-align:center">
      <button class="btn btn-success" id="remove" style="background-color: #f06548;">
          <i class="fa fa-times"></i>
      </button>
    </td>
  </tr>
</script>

<script type="text/html" id="gradeDetail">
  <tr class="details">
    <td>
      <input type="text" class="form-control" id="gradeNo" name="gradeNo" readonly>
      <input type="text" class="form-control" id="productGradeId" name="productGradeId" hidden>
    </td>
    <td>
      <select class="form-control select2" style="width: 100%; background-color:white;" id="grades" name="grades">
        <?php while($rowGrade=mysqli_fetch_assoc($grades)){ ?>
          <option value="<?=$rowGrade['id'] ?>"><?=$rowGrade['units']?></option>
        <?php } ?>
      </select>
    </td>
    <td>
      <select class="form-control" style="width: 100%; background-color:white;" id="gradePricingType" name="gradePricingType">
        <option selected>Standard</option>
        <option>Fixed</option>
        <option>Float</option>
      </select>
    </td>
    <td>
      <input type="number" class="form-control" id="gradePrice" name="gradePrice" style="background-color:white;" value="0">
    </td>
    <td class="d-flex" style="text-align:center">
      <button class="btn btn-success" id="remove" style="background-color: #f06548;">
          <i class="fa fa-times"></i>
      </button>
    </td>
  </tr>
</script>

<script>
var customerRowCount = $("#customerTable").find(".details").length;
var gradeRowCount = $("#gradeTable").find(".details").length;

$(function () {
  $('#selectAllCheckbox').on('change', function() {
    var checkboxes = $('#productTable tbody input[type="checkbox"]');
    checkboxes.prop('checked', $(this).prop('checked')).trigger('change');
  });

  $('.select2').each(function() {
    $(this).select2({
        allowClear: true,
        placeholder: "Please Select",
        // Conditionally set dropdownParent based on the element’s location
        dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-body') : undefined
    });
  });
  
  $("#productTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'ajax': {
      'url':'php/loadProducts.php',
      'data': {
        id: <?=$company ?>
      }
    },
    'columns': [
      {
        // Add a checkbox with a unique ID for each row
        data: 'id', // Assuming 'serialNo' is a unique identifier for each row
        className: 'select-checkbox',
        orderable: false,
        render: function (data, type, row) {
            return '<input type="checkbox" class="select-checkbox" id="checkbox_' + data + '" value="'+data+'"/>';
        }
      },
      { data: 'product_code' },
      { data: 'product_name' },
      //{ data: 'price' },
      { data: 'weight' },
      { data: 'remark' },
      { 
        data: 'id',
        render: function ( data, type, row ) {
          return '<div class="row"><div class="col-3"><button type="button" id="edit'+data+'" onclick="edit('+data+')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button></div><div class="col-3"><button type="button" id="deactivate'+data+'" onclick="deactivate('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></div></div>';
        }
      }
    ],
    "rowCallback": function( row, data, index ) {
      if (data.is_manual == 'Y') {
        $(row).css('background-color', '#f8d7da');
      }
    },        
  });
    
  $('#productImageDropzone').on('click', function(e){
    if (!$(e.target).is('input')) $('#productImage').click();
  });

  $('#productImageDropzone').on('dragover', function(e){
    e.preventDefault();
    $(this).css({'border-color':'#007bff', 'background':'#e8f0fe'});
  }).on('dragleave', function(e){
    e.preventDefault();
    $(this).css({'border-color':'#adb5bd', 'background':'#fff'});
  }).on('drop', function(e){
    e.preventDefault();
    $(this).css({'border-color':'#adb5bd', 'background':'#fff'});
    var file = e.originalEvent.dataTransfer.files[0];
    if (file) setProductImagePreview(file);
  });

  $('#productImage').on('change', function(){
    if (this.files[0]) setProductImagePreview(this.files[0]);
  });

  $('#removeProductImage').on('click', function(){
    $('#productImage').val('');
    $('#productImageThumb').attr('src', '');
    $('#productImagePreview').hide();
    $('#productImagePlaceholder').show();
  });

  $.validator.setDefaults({
    submitHandler: function () {
      $('#spinnerLoading').show();
      var formData = new FormData($('#productForm')[0]);
      $.ajax({
        url: 'php/products.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(data){
          var obj = JSON.parse(data);
          if(obj.status === 'success'){
            $('#addModal').modal('hide');
            toastr["success"](obj.message, "Success:");
            $('#productTable').DataTable().ajax.reload();
            $('#spinnerLoading').hide();
          } else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
            $('#spinnerLoading').hide();
          } else {
            toastr["error"]("Something wrong when edit", "Failed:");
            $('#spinnerLoading').hide();
          }
        }
      });
    }
  });

  $('#addProducts').on('click', function(){
    $('#addModal').find('#id').val("");
    $('#addModal').find('#code').val("");
    $('#addModal').find('#product').val("");
    $('#addModal').find('#serial').val("");
    $('#addModal').find('#batch').val("");
    $('#addModal').find('#part').val("");
    $('#addModal').find('#unit').val("");
    $('#addModal').find('#remark').val("");
    $('#addModal').find('#pricingType').val("Fixed");
    $('#addModal').find('#price').val("");
    $('#addModal').find('#weight').val("");
    $('#addModal').find('#productCategory').val("").trigger('change');
    $('#addModal').find('#productPackaging').val("").trigger('change');
    $('#addModal').find('#uom').val("").trigger('change');
    setRangeSet(0);
    $('#okWeight').val(''); $('#okWeightUnit').val('kg');
    $('#loWeight').val(''); $('#loWeightUnit').val('kg');
    $('#hiWeight').val(''); $('#hiWeightUnit').val('kg');
    $('#productImage').val('');
    $('#productImagePreview').hide();
    $('#productImageThumb').attr('src', '');
    $('#productImagePlaceholder').show();

    // clear customer table
    customerRowCount = 0;
    $('#customerTable').html('');

    // clear grade table
    gradeRowCount = 0;
    $('#gradeTable').html('');

    $('#addModal').modal('show');
    
    $('#productForm').validate({
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

  $('#uploadExcel').on('click', function(){
    $('#uploadModal').modal('show');

    $('#uploadForm').validate({
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

  $('#uploadModal').find('#previewButton').on('click', function(){
    var fileInput = document.getElementById('fileInput');
    var file = fileInput.files[0];
    var reader = new FileReader();
    
    reader.onload = function(e) {
        var data = e.target.result;
        // Process data and display preview
        displayPreview(data);
    };

    reader.readAsBinaryString(file);
  });

  $('#uploadProduct').on('click', function(){
    $('#spinnerLoading').show();
    var formData = $('#uploadForm').serializeArray();
    var data = [];
    var rowIndex = -1;
    formData.forEach(function(field) {
    var match = field.name.match(/([a-zA-Z0-9]+)\[(\d+)\]/);
    if (match) {
      var fieldName = match[1];
      var index = parseInt(match[2], 10);
      if (index !== rowIndex) {
      rowIndex = index;
      data.push({});
      }
      data[index][fieldName] = field.value;
    }
    });

    // Send the JSON array to the server
    $.ajax({
        url: 'php/uploadProduct.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            var obj = JSON.parse(response);
            if (obj.status === 'success') {
              $('#spinnerLoading').hide();
              $('#uploadModal').modal('hide');
              $('#productTable').DataTable().ajax.reload();
            } 
            else if (obj.status === 'failed') {
              $('#spinnerLoading').hide();
            } 
            else if (obj.status === 'error') {
              $('#spinnerLoading').hide();
              $('#uploadModal').modal('hide');
              $('#errorModal').find('#errorList').empty();
              var errorMessage = obj.message;
              for (var i = 0; i < errorMessage.length; i++) {
                $('#errorModal').find('#errorList').append(`<li>${errorMessage[i]}</li>`);                            
              }
              $('#errorModal').modal('show');
            } 
            else {
              $('#spinnerLoading').hide();
            }
        }
    });
  });

  $('#multiDeactivate').on('click', function () {
    $('#spinnerLoading').show();
    var selectedIds = []; // An array to store the selected 'id' values

    $("#productTable tbody input[type='checkbox']").each(function () {
      if (this.checked) {
          selectedIds.push($(this).val());
      }
    });

    if (selectedIds.length > 0) {
      if (confirm('Are you sure you want to cancel these items?')) {
          $.post('php/deleteProduct.php', {userID: selectedIds, type: 'MULTI'}, function(data){
              var obj = JSON.parse(data);
              
              if(obj.status === 'success'){
                $('#productTable').DataTable().ajax.reload();
                $('#spinnerLoading').hide();
              }
              else if(obj.status === 'failed'){
                $('#spinnerLoading').hide();
              }
              else{
                $('#spinnerLoading').hide();
              }
          });
      }

      $('#spinnerLoading').hide();
    } 
    else {
        // Optionally, you can display a message or take another action if no IDs are selected
        alert("Please select at least one product to delete.");
        $('#spinnerLoading').hide();
    }     
  });

  // Find and remove selected table rows
  $("#customerTable").on('click', 'button[id^="remove"]', function () {
    $(this).parents("tr").remove();

    $("#customerTable tr").each(function (index) {
        $(this).find('input[name^="no"]').val(index + 1);
    });
  });

  $(".add-customer").click(function(){
    var $addContents = $("#customerDetail").clone();
    $("#customerTable").append($addContents.html());

    $("#customerTable").find('.details:last').attr("id", "detail" + customerRowCount);
    $("#customerTable").find('.details:last').attr("data-index", customerRowCount);
    $("#customerTable").find('#remove:last').attr("id", "remove" + customerRowCount);

    $("#customerTable").find('#no:last').attr('name', 'no['+customerRowCount+']').attr("id", "no" + customerRowCount).val(customerRowCount+1);
    $("#customerTable").find('#customers:last').attr('name', 'customers['+customerRowCount+']').attr("id", "customers" + customerRowCount).select2({
      allowClear: true,
      placeholder: "Please Select",
      dropdownParent: $('#addModal')
    });
    $("#customerTable").find('#customerPricingType:last').attr('name', 'customerPricingType['+customerRowCount+']').attr("id", "customerPricingType" + customerRowCount);
    $("#customerTable").find('#customerPrice:last').attr('name', 'customerPrice['+customerRowCount+']').attr("id", "customerPrice" + customerRowCount);

    // Apply custom styling to Select2 elements in addModal
    $('#customerTable .select2-container .select2-selection--single').css({
      'padding-top': '4px',
      'padding-bottom': '4px',
      'height': 'auto'
    });

    $('#customerTable .select2-container .select2-selection__arrow').css({
      'padding-top': '33px',
      'height': 'auto'
    });

    customerRowCount++;
  });

  // Find and remove selected table rows
  $("#gradeTable").on('click', 'button[id^="remove"]', function () {
    $(this).parents("tr").remove();

    $("#gradeTable tr").each(function (index) {
        $(this).find('input[name^="no"]').val(index + 1);
    });
  });

  $(".add-grade").click(function(){
    var $addContents = $("#gradeDetail").clone();
    $("#gradeTable").append($addContents.html());

    $("#gradeTable").find('.details:last').attr("id", "detail" + gradeRowCount);
    $("#gradeTable").find('.details:last').attr("data-index", gradeRowCount);
    $("#gradeTable").find('#remove:last').attr("id", "remove" + gradeRowCount);

    $("#gradeTable").find('#gradeNo:last').attr('name', 'gradeNo['+gradeRowCount+']').attr("id", "gradeNo" + gradeRowCount).val(gradeRowCount+1);
    $("#gradeTable").find('#grades:last').attr('name', 'grades['+gradeRowCount+']').attr("id", "grades" + gradeRowCount).select2({
      allowClear: true,
      placeholder: "Please Select",
      dropdownParent: $('#addModal')
    });
    $("#gradeTable").find('#gradePricingType:last').attr('name', 'gradePricingType['+gradeRowCount+']').attr("id", "gradePricingType" + gradeRowCount);
    $("#gradeTable").find('#gradePrice:last').attr('name', 'gradePrice['+gradeRowCount+']').attr("id", "gradePrice" + gradeRowCount);

    // Apply custom styling to Select2 elements in addModal
    $('#gradeTable .select2-container .select2-selection--single').css({
      'padding-top': '4px',
      'padding-bottom': '4px',
      'height': 'auto'
    });

    $('#gradeTable .select2-container .select2-selection__arrow').css({
      'padding-top': '33px',
      'height': 'auto'
    });

    gradeRowCount++;
  });
});

function displayPreview(data) {
  // Parse the Excel data
  var workbook = XLSX.read(data, { type: 'binary' });

  // Get the first sheet
  var sheetName = workbook.SheetNames[0];
  var sheet = workbook.Sheets[sheetName];

  // Convert the sheet to an array of objects
  var jsonData = XLSX.utils.sheet_to_json(sheet, { header: 5 });

  // Get the headers
  var headers = Object.keys(jsonData[0] || {});

  // Ensure we handle cases where there may be less than 5 columns
  while (headers.length < 5) {
      headers.push(''); // Adding empty headers to reach 5 columns
  }

  // Create HTML table headers
  var htmlTable = '<table style="width:20%;"><thead><tr>';
  headers.forEach(function(header) {
      htmlTable += '<th>' + header + '</th>';
  });
  htmlTable += '</tr></thead><tbody>';

  // Iterate over the data and create table rows
  for (var i = 0; i < jsonData.length; i++) {
      htmlTable += '<tr>';
      var rowData = jsonData[i];

      for (var j = 0; j < 5 && j < headers.length; j++) {
          var cellData = rowData[headers[j]];
          var formattedData = cellData;

          // Check if cellData is a valid Excel date serial number and format it to DD/MM/YYYY
          if (typeof cellData === 'number' && cellData > 0) {
              var excelDate = XLSX.SSF.parse_date_code(cellData);
          }

          htmlTable += '<td><input type="text" id="'+headers[j].replace(/[^a-zA-Z0-9]/g, '')+i+'" name="'+headers[j].replace(/[^a-zA-Z0-9]/g, '')+'['+i+']" value="' + (formattedData == null ? '' : formattedData) + '" /></td>';
      }
      htmlTable += '</tr>';
  }

  htmlTable += '</tbody></table>';

  var previewTable = document.getElementById('previewTable');
  previewTable.innerHTML = htmlTable;
}

function setProductImagePreview(file) {
  var reader = new FileReader();
  reader.onload = function(e) {
    $('#productImageThumb').attr('src', e.target.result);
    $('#productImagePreview').show();
    $('#productImagePlaceholder').hide();
  };
  reader.readAsDataURL(file);
}

function edit(id){
  $('#spinnerLoading').show();
  $.post('php/getProduct.php', {userID: id}, function(data){
    var obj = JSON.parse(data);
    
    if(obj.status === 'success'){
      $('#addModal').find('#id').val(obj.message.id);
      $('#addModal').find('#code').val(obj.message.product_code);
      $('#addModal').find('#product').val(obj.message.product_name);
      $('#addModal').find('#serial').val(obj.message.product_sn);
      $('#addModal').find('#batch').val(obj.message.batch_no);
      $('#addModal').find('#part').val(obj.message.parts_no);
      $('#addModal').find('#uom').val(obj.message.uom).trigger('change');
      $('#addModal').find('#remark').val(obj.message.remark);
      $('#addModal').find('#pricingType').val(obj.message.pricing_type);
      $('#addModal').find('#price').val(obj.message.price);
      $('#addModal').find('#weight').val(obj.message.weight);
      $('#addModal').find('#productCategory').val(obj.message.category).trigger('change');
      $('#addModal').find('#productPackaging').val(obj.message.packaging).trigger('change');
      $('#addModal').find('#company').val(obj.message.customer).trigger('change');
      $('#productImage').val('');
      if (obj.message.product_image) {
        $('#productImageThumb').attr('src', 'php/viewPhoto.php?file=' + obj.message.product_image + '&type=file_table');
        $('#productImagePreview').show();
        $('#productImagePlaceholder').hide();
      } else {
        $('#productImagePreview').hide();
        $('#productImageThumb').attr('src', '');
        $('#productImagePlaceholder').show();
      }
      setRangeSet(obj.message.range_set == '1' ? 1 : 0);
      $('#okWeight').val(obj.message.ok_weight); $('#okWeightUnit').val(obj.message.ok_weight_unit || 'kg');
      $('#loWeight').val(obj.message.lo_weight); $('#loWeightUnit').val(obj.message.lo_weight_unit || 'kg');
      $('#hiWeight').val(obj.message.hi_weight); $('#hiWeightUnit').val(obj.message.hi_weight_unit || 'kg');

      // customer table
      $('#customerTable').html('');
      customerRowCount = 0;
      if (obj.message.productCustomers.length > 0){
        for(var i = 0; i < obj.message.productCustomers.length; i++){
          var item = obj.message.productCustomers[i];
          var $addContents = $("#customerDetail").clone();
          $("#customerTable").append($addContents.html());

          $("#customerTable").find('.details:last').attr("id", "detail" + customerRowCount);
          $("#customerTable").find('.details:last').attr("data-index", customerRowCount);
          $("#customerTable").find('#remove:last').attr("id", "remove" + customerRowCount);

          $("#customerTable").find('#no:last').attr('name', 'no['+customerRowCount+']').attr("id", "no" + customerRowCount).val(item.no);
          $("#customerTable").find('#customers:last').attr('name', 'customers['+customerRowCount+']').attr("id", "customers" + customerRowCount).val(item.customer_id).select2({
            allowClear: true,
            placeholder: "Please Select",
            dropdownParent: $('#addModal')
          });
          $("#customerTable").find('#customerPricingType:last').attr('name', 'customerPricingType['+customerRowCount+']').attr("id", "customerPricingType" + customerRowCount).val(item.pricing_type || 'Standard');
          $("#customerTable").find('#customerPrice:last').attr('name', 'customerPrice['+customerRowCount+']').attr("id", "customerPrice" + customerRowCount).val(item.price || 0);

          // Apply custom styling to Select2 elements in addModal
          $('#customerTable .select2-container .select2-selection--single').css({
            'padding-top': '4px',
            'padding-bottom': '4px',
            'height': 'auto'
          });

          $('#customerTable .select2-container .select2-selection__arrow').css({
            'padding-top': '33px',
            'height': 'auto'
          });

          customerRowCount++;
        }
      }

      // grade table
      $('#gradeTable').html('');
      gradeRowCount = 0;
      if (obj.message.productGrades.length > 0){
        for(var i = 0; i < obj.message.productGrades.length; i++){
          var item = obj.message.productGrades[i];
          var $addContents = $("#gradeDetail").clone();
          $("#gradeTable").append($addContents.html());

          $("#gradeTable").find('.details:last').attr("id", "detail" + gradeRowCount);
          $("#gradeTable").find('.details:last').attr("data-index", gradeRowCount);
          $("#gradeTable").find('#remove:last').attr("id", "remove" + gradeRowCount);

          $("#gradeTable").find('#gradeNo:last').attr('name', 'gradeNo['+gradeRowCount+']').attr("id", "gradeNo" + gradeRowCount).val(item.no);
          $("#gradeTable").find('#grades:last').attr('name', 'grades['+gradeRowCount+']').attr("id", "grades" + gradeRowCount).val(item.grade_id).select2({
            allowClear: true,
            placeholder: "Please Select",
            dropdownParent: $('#addModal')
          });
          $("#gradeTable").find('#gradePricingType:last').attr('name', 'gradePricingType['+gradeRowCount+']').attr("id", "gradePricingType" + gradeRowCount).val(item.pricing_type || 'Standard');
          $("#gradeTable").find('#gradePrice:last').attr('name', 'gradePrice['+gradeRowCount+']').attr("id", "gradePrice" + gradeRowCount).val(item.price || 0.00);

          // Apply custom styling to Select2 elements in addModal
          $('#gradeTable .select2-container .select2-selection--single').css({
            'padding-top': '4px',
            'padding-bottom': '4px',
            'height': 'auto'
          });

          $('#gradeTable .select2-container .select2-selection__arrow').css({
            'padding-top': '33px',
            'height': 'auto'
          });

          gradeRowCount++;
        }
      }

      $('#addModal').modal('show');
      
      $('#productForm').validate({
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
      toastr["error"]("Something wrong when activate", "Failed:");
    }
    $('#spinnerLoading').hide();
  });
}

function setRangeSet(val) {
  var enabled = val == 1;
  $('#rangeSet').val(enabled ? 1 : 0);
  $('#rangeSetToggle').css('background', enabled ? '#28a745' : '#ccc');
  $('#rangeSetKnob').css('left', enabled ? '75px' : '1px');
  $('#rangeSetIcon').attr('class', enabled ? 'fas fa-check text-success' : 'fas fa-times text-danger');
  $('#rangeSetLabel').text(enabled ? 'Enable' : 'Disable').css('right', enabled ? 'auto' : '8px').css('left', enabled ? '8px' : 'auto');
  $('#rangeWeightFields').toggle(enabled);
}

$('#rangeSetToggle').on('click', function() {
  setRangeSet($('#rangeSet').val() == 1 ? 0 : 1);
});

function deactivate(id){
  if (confirm('Are you sure you want to delete this items?')) {
    //$('#spinnerLoading').show();
    $.post('php/deleteProduct.php', {userID: id}, function(data){
        var obj = JSON.parse(data);
        
        if(obj.status === 'success'){
          toastr["success"](obj.message, "Success:");
          $('#productTable').DataTable().ajax.reload();
          //$('#spinnerLoading').hide();
        }
        else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
            //$('#spinnerLoading').hide();
        }
        else{
            toastr["error"]("Something wrong when activate", "Failed:");
            //$('#spinnerLoading').hide();
        }
    });
  }
}
</script>