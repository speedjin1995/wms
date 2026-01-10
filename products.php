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
            <div class="modal-body">
              <div class="card-body">
                <input type="hidden" class="form-control" id="id" name="id">
                <div class="form-group" <?php if($user != 2){ echo 'style="display:none;"'; } ?>>
                  <label for="code">Company *</label>
                  <select class="form-control select2" style="width: 100%;" id="company" name="company" required>
                    <?php while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                      <option value="<?=$rowCompany['id'] ?>" <?php if($rowCompany['id'] == $company) echo 'selected'; ?>><?=$rowCompany['name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="code">Product Code *</label>
                  <input type="text" class="form-control" name="code" id="code" placeholder="Enter Product Code" required>
                </div>
                <div class="form-group">
                  <label for="product">Product Name *</label>
                  <input type="text" class="form-control" name="product" id="product" placeholder="Enter Product Name" required>
                </div>

                <div class="form-group">
                  <label for="serial">Product Serial No</label>
                  <input type="text" class="form-control" name="serial" id="serial" placeholder="Enter Product Serial No.">
                </div>
                <div class="form-group">
                  <label for="batch">Batch No.</label>
                  <input type="text" class="form-control" name="batch" id="batch" placeholder="Enter Batch No.">
                </div>
                <div class="form-group">
                  <label for="part">Parts No.</label>
                  <input type="text" class="form-control" name="part" id="part" placeholder="Enter Part No.">
                </div>
                <div class="form-group">
                  <label for="uom">UOM</label>
                  <select class="form-control" style="width: 100%;" id="uom" name="uom"> 
                    <option selected="selected">-</option>
                    <?php while($rowunits=mysqli_fetch_assoc($units)){ ?>
                      <option value="<?=$rowunits['id'] ?>"><?=$rowunits['units'] ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="weight">Unit Weight</label>
                  <input type="number" class="form-control" name="weight" id="weight" placeholder="Enter Product Weight">
                </div>
                <div class="form-group">
                  <label for="pricingType">Pricing Type</label>
                  <select class="form-control" style="width: 100%;" id="pricingType" name="pricingType"> 
                    <option selected="selected">Fixed</option>
                    <option>Float</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="price">Price</label>
                  <input type="number" class="form-control" name="price" id="price" placeholder="Enter Product Price">
                </div>
                <div class="form-group"> 
                  <label for="remark">Remark </label>
                  <textarea class="form-control" id="remark" name="remark" placeholder="Enter your remark"></textarea>
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

<script>
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