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
  $companies = $db->query("SELECT * FROM companies WHERE deleted = 0 ORDER BY name ASC");
  $units = $db->query("SELECT * FROM units WHERE deleted = '0' ORDER BY units ASC");

  if ($user != 2){
    $customers = $db->query("SELECT * FROM customers WHERE deleted = 0 AND customer = '".$company."' ORDER BY customer_name ASC");
    $grades = $db->query("SELECT * FROM grades WHERE deleted = 0 AND customer = '".$company."' ORDER BY units ASC");
  }
  else{
    $customers = $db->query("SELECT * FROM customers WHERE deleted = 0 ORDER BY customer_name ASC");
    $grades = $db->query("SELECT * FROM grades WHERE deleted = 0 ORDER BY units ASC");
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
            <div class="modal-header">
              <h4 class="modal-title"><?=$languageArray['add_products_code'][$language]?></h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
              <div class="card-body p-3">
                <input type="hidden" class="form-control" id="id" name="id">
                <div class="form-group mb-2" <?php if($user != 2){ echo 'style="display:none;"'; } ?>>
                  <label for="code"><?=$languageArray['company_code'][$language]?> *</label>
                  <select class="form-control select2" style="width: 100%;" id="company" name="company" required>
                    <?php while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                      <option value="<?=$rowCompany['id'] ?>" <?php if($rowCompany['id'] == $company) echo 'selected'; ?>><?=$rowCompany['name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="row">
                  <div class="col-6">
                    <div class="form-group mb-2">
                      <label for="code"><?=$languageArray['product_code_code'][$language]?> *</label>
                      <input type="text" class="form-control" name="code" id="code" placeholder="<?=$languageArray['enter_product_code_code'][$language]?>" required>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="form-group mb-2">
                      <label for="product"><?=$languageArray['product_name_code'][$language]?> *</label>
                      <input type="text" class="form-control" name="product" id="product" placeholder="<?=$languageArray['enter_product_name_code'][$language]?>" required>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <!-- <div class="col-6">
                    <div class="form-group mb-2">
                      <label for="serial">Serial No</label>
                      <input type="text" class="form-control" name="serial" id="serial" placeholder="Serial No.">
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="form-group mb-2">
                      <label for="batch">Batch No</label>
                      <input type="text" class="form-control" name="batch" id="batch" placeholder="Batch No.">
                    </div>
                  </div> -->
                </div>
                <div class="row">
                  <!-- <div class="col-6">
                    <div class="form-group mb-2">
                      <label for="part">Parts No</label>
                      <input type="text" class="form-control" name="part" id="part" placeholder="Part No.">
                    </div>
                  </div>
                  -->
                </div>
                <div class="row">
                  <div class="col-6">
                    <div class="form-group mb-2">
                      <label for="weight"><?=$languageArray['weight_code'][$language]?></label>
                      <input type="number" class="form-control" name="weight" id="weight" placeholder="<?=$languageArray['weight_code'][$language]?>">
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="form-group mb-2">
                      <label for="pricingType"><?=$languageArray['pricing_type_code'][$language]?></label>
                      <select class="form-control" id="pricingType" name="pricingType"> 
                        <option selected="selected"><?=$languageArray['fixed_code'][$language]?></option>
                        <option><?=$languageArray['float_code'][$language]?></option>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-6">
                    <div class="form-group mb-2">
                      <label for="price"><?=$languageArray['price_code'][$language]?></label>
                      <input type="number" class="form-control" name="price" id="price" placeholder="<?=$languageArray['price_code'][$language]?>">
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="form-group mb-2">
                      <label for="uom"><?=$languageArray['unit_code'][$language]?></label>
                      <select class="form-control" id="uom" name="uom"> 
                        <option selected="selected">-</option>
                        <?php while($rowunits=mysqli_fetch_assoc($units)){ ?>
                          <option value="<?=$rowunits['id'] ?>"><?=$rowunits['units'] ?></option>
                        <?php } ?>
                      </select>
                    </div>
                  </div> 
                </div>
                <div class="form-group mb-3"> 
                  <label for="remark"><?=$languageArray['remark_code'][$language]?></label>
                  <textarea class="form-control" id="remark" name="remark" placeholder="<?=$languageArray['enter_remark_code'][$language]?>" rows="2"></textarea>
                </div>

                <div class="row">
                  <div class="col-12">
                    <div class="card bg-light">
                      <div class="card-header">
                        <div class="row">
                          <div class="col-10">
                            <h3><?=$languageArray['customers_code'][$language]?></h3>
                          </div>
                          <div class="col-2">
                            <button type="button" class="btn btn-success add-customer">
                              <i class="ri-add-circle-line align-middle me-1"></i><?=$languageArray['add_customers_code'][$language]?>
                            </button>
                          </div>
                        </div>
                      </div>
                      <div class="card-body">
                        <div class="row">
                          <div class="col-xxl-12 col-lg-12 mb-3">
                            <table class="table table-primary">
                              <thead>
                                <tr>
                                  <th width="10%"><?=$languageArray['number_short_code'][$language]?></th>
                                  <th><?=$languageArray['customer_code'][$language]?></th>
                                  <th><?=$languageArray['pricing_type_code'][$language]?></th>
                                  <th><?=$languageArray['price_code'][$language]?> (RM)</th>
                                  <th><?=$languageArray['actions_code'][$language]?></th>
                                </tr>
                              </thead>
                              <tbody id="customerTable"></tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-12">
                    <div class="card bg-light">
                      <div class="card-header">
                        <div class="row">
                          <div class="col-10">
                            <h3><?=$languageArray['grades_code'][$language]?></h3>
                          </div>
                          <div class="col-2">
                            <button type="button" class="btn btn-success add-grade">
                              <i class="ri-add-circle-line align-middle me-1"></i><?=$languageArray['add_grade_code'][$language]?>
                            </button>
                          </div>
                        </div>
                      </div>
                      <div class="card-body">
                        <div class="row">
                          <div class="col-xxl-12 col-lg-12 mb-3">
                            <table class="table table-primary">
                              <thead>
                                <tr>
                                  <th width="10%"><?=$languageArray['number_short_code'][$language]?></th>
                                  <th><?=$languageArray['unit_code'][$language]?></th>
                                  <th><?=$languageArray['actions_code'][$language]?></th>
                                </tr>
                              </thead>
                              <tbody id="gradeTable"></tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-danger" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
              <button type="submit" class="btn btn-primary" name="submit" id="submitMember"><?=$languageArray['submit_code'][$language]?></button>
            </div>
        </form>
      </div>
      <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
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
        <option selected>Fixed</option>
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
      //$('td', row).css('background-color', '#E6E6FA');
    },        
  });
    
  $.validator.setDefaults({
    submitHandler: function () {
      $('#spinnerLoading').show();
      $.post('php/products.php', $('#productForm').serialize(), function(data){
        var obj = JSON.parse(data); 
        
        if(obj.status === 'success'){
          $('#addModal').modal('hide');
          toastr["success"](obj.message, "Success:");
          $('#productTable').DataTable().ajax.reload();
          $('#spinnerLoading').hide();
        }
        else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
            $('#spinnerLoading').hide();
        }
        else{
            toastr["error"]("Something wrong when edit", "Failed:");
            $('#spinnerLoading').hide();
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
      $('#addModal').find('#uom').val(obj.message.uom);
      $('#addModal').find('#remark').val(obj.message.remark);
      $('#addModal').find('#pricingType').val(obj.message.pricing_type);
      $('#addModal').find('#price').val(obj.message.price);
      $('#addModal').find('#weight').val(obj.message.weight);
      $('#addModal').find('#company').val(obj.message.customer).trigger('change');

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
          $("#customerTable").find('#customerPricingType:last').attr('name', 'customerPricingType['+customerRowCount+']').attr("id", "customerPricingType" + customerRowCount).val(item.pricing_type);
          $("#customerTable").find('#customerPrice:last').attr('name', 'customerPrice['+customerRowCount+']').attr("id", "customerPrice" + customerRowCount).val(item.price);

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