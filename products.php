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
  $companies = $db->query("SELECT * FROM companies WHERE deleted = 0");
  $units = $db->query("SELECT * FROM units WHERE deleted = '0'");

  if ($user != 2){
    $customers = $db->query("SELECT * FROM customers WHERE deleted = 0 AND customer = '".$company."'");
  }
  else{
    $customers = $db->query("SELECT * FROM customers WHERE deleted = 0");
  }
}
?>

<div class="content-header">
  <div class="container-fluid">
      <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0 text-dark">Products</h1>
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
            <div class="col-9"></div>
            <!-- <div class="col-2">
                <input type="file" id="fileInput" accept=".xlsx, .xls" />
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="importExcelbtn">Import Excel</button>
            </div>                             -->
            <div class="col-3">
              <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="addProducts">Add Products</button>
            </div>
          </div>
        </div>
        <div class="card-body">
          <table id="productTable" class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>Product Code</th>
                <th>Product Name</th>
                <!--th>Price</th-->
                <th>Weight</th>
                <th>Remark</th>
                <th>Actions</th>
              </tr>
            </thead>
          </table>
        </div><!-- /.card-body -->
      </div><!-- /.card -->
    </div><!-- /.col -->
  </div><!-- /.row -->
</div><!-- /.container-fluid -->
</section><!-- /.content -->

<div class="modal fade" id="addModal">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <form role="form" id="productForm">
            <div class="modal-header">
              <h4 class="modal-title">Add Products</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
              <div class="card-body p-3">
                <input type="hidden" class="form-control" id="id" name="id">
                <div class="form-group mb-2" <?php if($user != 2){ echo 'style="display:none;"'; } ?>>
                  <label for="code">Company *</label>
                  <select class="form-control select2" style="width: 100%;" id="company" name="company" required>
                    <?php while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                      <option value="<?=$rowCompany['id'] ?>" <?php if($rowCompany['id'] == $company) echo 'selected'; ?>><?=$rowCompany['name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="row">
                  <div class="col-6">
                    <div class="form-group mb-2">
                      <label for="code">Product Code *</label>
                      <input type="text" class="form-control" name="code" id="code" placeholder="Enter Product Code" required>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="form-group mb-2">
                      <label for="product">Product Name *</label>
                      <input type="text" class="form-control" name="product" id="product" placeholder="Enter Product Name" required>
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
                  <div class="col-6">
                    <div class="form-group mb-2">
                      <label for="uom">UOM</label>
                      <select class="form-control" id="uom" name="uom"> 
                        <option selected="selected">-</option>
                        <?php while($rowunits=mysqli_fetch_assoc($units)){ ?>
                          <option value="<?=$rowunits['id'] ?>"><?=$rowunits['units'] ?></option>
                        <?php } ?>
                      </select>
                    </div>
                  </div> -->
                </div>
                <div class="row">
                  <div class="col-6">
                    <div class="form-group mb-2">
                      <label for="weight">Weight</label>
                      <input type="number" class="form-control" name="weight" id="weight" placeholder="Weight">
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="form-group mb-2">
                      <label for="pricingType">Pricing Type</label>
                      <select class="form-control" id="pricingType" name="pricingType"> 
                        <option selected="selected">Fixed</option>
                        <option>Float</option>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-6">
                    <div class="form-group mb-2">
                      <label for="price">Price</label>
                      <input type="number" class="form-control" name="price" id="price" placeholder="Price">
                    </div>
                  </div>
                </div>
                <div class="form-group mb-3"> 
                  <label for="remark">Remark</label>
                  <textarea class="form-control" id="remark" name="remark" placeholder="Enter remark" rows="2"></textarea>
                </div>

                <div class="row col-12">
                  <div class="col-12">
                    <div class="card bg-light">
                      <div class="card-header p-2">
                        <div class="d-flex justify-content-end">
                          <div class="flex-shrink-0">
                            <button type="button" class="btn btn-success add-customer"><i class="ri-add-circle-line align-middle me-1"></i>Add Customer</button>
                          </div> 
                        </div>
                      </div>
                      <div class="card-body">
                        <div class="row">
                          <div class="col-xxl-12 col-lg-12 mb-3">
                            <table class="table table-primary">
                              <thead>
                                  <tr>
                                      <th width="10%">No</th>
                                      <th>Customer</th>
                                      <th>Pricing Type</th>
                                      <th>Price (RM)</th>
                                      <th>Action</th>
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
              </div>
            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary" name="submit" id="submitMember">Submit</button>
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

<script>
var customerRowCount = $("#customerTable").find(".details").length;

$(function () {
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
    $('#addModal').find('#uom').val("");
    $('#addModal').find('#remark').val("");
    $('#addModal').find('#pricingType').val("Fixed");
    $('#addModal').find('#price').val("");
    $('#addModal').find('#weight').val("");

    // clear customer table
    customerRowCount = 0;
    $('#customerTable').html('');

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
});

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