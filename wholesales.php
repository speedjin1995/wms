<?php
require_once 'php/db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
}
else{
  $user = $_SESSION['userID'];
  $company = $_SESSION['customer'];
  $stmt = $db->prepare("SELECT * from users where id = ?");
	$stmt->bind_param('s', $user);
	$stmt->execute();
	$result = $stmt->get_result();
  $role = 'NORMAL';
	
	if(($row = $result->fetch_assoc()) !== null){
    $role = $row['role_code'];
  }

  if ($user != 2){
    $products = $db->query("SELECT * FROM products WHERE deleted = '0' AND customer = '$company' ORDER BY product_name ASC");
    $products2 = $db->query("SELECT * FROM products WHERE deleted = '0' AND customer = '$company' ORDER BY product_name ASC");
    $supplies = $db->query("SELECT * FROM supplies WHERE deleted = '0' AND customer = '$company' ORDER BY supplier_name ASC");
    $supplies2 = $db->query("SELECT * FROM supplies WHERE deleted = '0' AND customer = '$company' ORDER BY supplier_name ASC");
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' AND customer = '$company' ORDER BY customer_name ASC");
    $customers2 = $db->query("SELECT * FROM customers WHERE deleted = '0' AND customer = '$company' ORDER BY customer_name ASC");
    $vehicles = $db->query("SELECT * FROM vehicles WHERE deleted = '0' AND customer = '$company' ORDER BY veh_number ASC");
    $drivers = $db->query("SELECT * FROM drivers WHERE deleted = '0' AND customer = '$company' ORDER BY driver_name ASC");
    $grades = $db->query("SELECT * FROM grades WHERE deleted = '0' AND customer = '$company' ORDER BY units ASC");
  } else {
    $products = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $products2 = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $supplies = $db->query("SELECT * FROM supplies WHERE deleted = '0' ORDER BY supplier_name ASC");
    $supplies2 = $db->query("SELECT * FROM supplies WHERE deleted = '0' ORDER BY supplier_name ASC");
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' ORDER BY customer_name ASC");
    $customers2 = $db->query("SELECT * FROM customers WHERE deleted = '0' ORDER BY customer_name ASC");
    $vehicles = $db->query("SELECT * FROM vehicles WHERE deleted = '0' ORDER BY veh_number ASC");
    $drivers = $db->query("SELECT * FROM drivers WHERE deleted = '0' ORDER BY driver_name ASC");
    $grades = $db->query("SELECT * FROM grades WHERE deleted = '0' ORDER BY units ASC");
  }

  $units = $db->query("SELECT * FROM units WHERE deleted = '0'");
  $units1 = $db->query("SELECT * FROM units WHERE deleted = '0'");
}
?>
<!--select class="form-control" style="width: 100%;" id="uomhidden" name="uomhidden" style="display:none;"> 
  <option selected="selected">-</option>
  <?php while($rowunits2=mysqli_fetch_assoc($units1)){ ?>
    <option value="<?=$rowunits2['id'] ?>"><?=$rowunits2['units'] ?></option>
  <?php } ?>
</select-->

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
        <h1 class="m-0 text-dark">Weighing Record</h1>
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
                <label>From Date:</label>
                <div class="input-group date" id="fromDatePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#fromDatePicker" id="fromDate"/>
                  <div class="input-group-append" data-target="#fromDatePicker" data-toggle="datetimepicker">
                  <div class="input-group-text"><i class="fa fa-calendar"></i></div></div>
                </div>
              </div>

              <div class="form-group col-3">
                <label>To Date:</label>
                <div class="input-group date" id="toDatePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#toDatePicker" id="toDate"/>
                  <div class="input-group-append" data-target="#toDatePicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>

              <div class="col-3">
                <div class="form-group">
                  <label>Status</label>
                  <select class="form-control" id="statusFilter" name="statusFilter">
                    <option value="DISPATCH" selected>Dispatch</option>
                    <option value="RECEIVING">Receiving</option>
                  </select>
                </div>
              </div>

              <div class="col-3" id="customerStatusDiv">
                <div class="form-group">
                  <label>Customer</label>
                  <select class="form-control select2" id="customerNoFilter" name="customerNoFilter">
                    <option value="" selected disabled hidden>Please Select</option>
                    <?php while($rowCustomer2=mysqli_fetch_assoc($customers)){ ?>
                      <option value="<?=$rowCustomer2['id'] ?>"><?=$rowCustomer2['customer_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="col-3" id="supplierStatusDiv" style="display: none;">
                <div class="form-group">
                  <label>Supplier</label>
                  <select class="form-control select2" id="supplierNoFilter" name="supplierNoFilter">
                    <option value="" selected disabled hidden>Please Select</option>
                    <?php while($rowCustomer2=mysqli_fetch_assoc($supplies)){ ?>
                      <option value="<?=$rowCustomer2['id'] ?>"><?=$rowCustomer2['supplier_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="col-3">
                <div class="form-group">
                  <label>Product</label>
                  <select class="form-control select2" id="productFilter" name="productFilter" style="width: 100%;">
                    <option selected="selected">-</option>
                    <?php while($rowStatus2=mysqli_fetch_assoc($products)){ ?>
                      <option value="<?=$rowStatus2['id'] ?>"><?=$rowStatus2['product_name'] ?></option>
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
                  Search
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
              <div class="col-10"></div>
              <!-- <div class="col-2">
                <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="refreshBtn"><i class="fas fa-sync"></i> Refresh</button>
              </div> -->
              <div class="col-2" style="visibility: hidden;">
                <button type="button" class="btn btn-block bg-gradient-success btn-sm" onclick="newEntry()"><i class="fas fa-plus"></i> Add New</button>
              </div>
            </div>
          </div>

          <div class="card-body">
            <table id="weightTable" class="table table-bordered table-striped display">
              <thead>
                <tr>
                  <th>Serial <br>No.</th>
                  <th>PO <br>No.</th>
                  <th>Created <br> Datetime</th>
                  <th>Customer/Supplier</th>
                  <th>Product</th>
                  <th>Vehicle <br>No.</th>
                  <th>Driver</th>
                  <th>Total <br>Item</th>
                  <th>Total <br>Weight</th>
                  <th>Total <br>Reject</th>
                  <th>Total <br>Price</th>
                  <th>Action</th>
                </tr>
              </thead>
              <!-- <tfoot>
                <tr>
                    <th colspan="4">Total</th>
                    <th></th>
                    <th></th>
                    <th></th> 
                    <th></th>
                </tr>
              </tfoot> -->
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
      <form role="form" id="extendForm">
        <div class="modal-header bg-gray-dark color-palette">
          <h4 class="modal-title">Add New Entry</h4>
          <button type="button" class="close bg-gray-dark color-palette" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body" >
          <input type="hidden" class="form-control" id="id" name="id">
          
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Status *</label>
                <select class="form-control" id="status" name="status" required>
                  <option value="DISPATCH">Dispatch</option>
                  <option value="RECEIVING">Receiving</option>
                </select>
              </div>
            </div>
            <div class="col-md-4" id="customerDiv">
              <div class="form-group">
                <label>Customer</label>
                <select class="form-control select2" id="customer" name="customer">
                  <option value="" selected disabled hidden>Please Select</option>
                  <option value="OTHERS">Others</option>
                  <?php while($rowCustomer3=mysqli_fetch_assoc($customers2)){ ?>
                    <option value="<?=$rowCustomer3['id'] ?>"><?=$rowCustomer3['customer_name'] ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
            <div class="col-md-4" id="customerOtherDiv">
              <div class="form-group">
                <label>Customer Other</label>
                <input type="text" class="form-control" id="customerOther" name="customerOther">
              </div>
            </div>
            <div class="col-md-4" id="supplierDiv">
              <div class="form-group">
                <label>Supplier</label>
                <select class="form-control select2" id="supplier" name="supplier">
                  <option value="" selected disabled hidden>Please Select</option>
                  <option value="OTHERS">Others</option>
                  <?php while($rowSupplier3=mysqli_fetch_assoc($supplies2)){ ?>
                    <option value="<?=$rowSupplier3['id'] ?>"><?=$rowSupplier3['supplier_name'] ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
            <div class="col-md-4" id="supplierOtherDiv">
              <div class="form-group">
                <label>Supplier Other</label>
                <input type="text" class="form-control" id="supplierOther" name="supplierOther">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Vehicle No.</label>
                <select class="form-control select2" id="vehicle" name="vehicle">
                  <option value="" selected disabled hidden>Please Select</option>
                  <?php while($rowVehicle3=mysqli_fetch_assoc($vehicles)){ ?>
                    <option value="<?=$rowVehicle3['veh_number'] ?>"><?=$rowVehicle3['veh_number'] ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Driver</label>
                <select class="form-control select2" id="driver" name="driver">
                  <option value="" selected disabled hidden>Please Select</option>
                  <?php while($rowDriver3=mysqli_fetch_assoc($drivers)){ ?>
                    <option value="<?=$rowDriver3['driver_name'] ?>"><?=$rowDriver3['driver_name'] ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
          </div>
          
          <hr>
          <h5>Weight Details</h5>
          <div class="row">
            <table class="table table-bordered nowrap table-striped align-middle" style="width:100%">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Grade</th>
                  <th>Gross</th>
                  <th>Tare</th>
                  <th>Net</th>
                  <th>Reject</th>
                  <th>Price</th>
                  <th>Total</th>
                  <th>Time</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="weightDetailsTable">
                <!-- Weight details will be populated here -->
              </tbody>
            </table>
          </div>
        </div>

        <div class="modal-footer justify-content-between bg-gray-dark color-palette">
          <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" id="saveButton">Save changes</button>
        </div>
      </form>
    </div> <!-- /.modal-content -->
  </div> <!-- /.modal-dialog -->
</div> <!-- /.modal -->   

<script>
// Values
var controlflow = "None";
var indicatorUnit = "kg";
var weightUnit = "1";
var rate = 1;
var currency = "1";

$(function () {
  $('#uomhidden').hide();

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
  var statusI = $('#statusFilter').val();
  var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
  var customerNoI = $('#customerNoFilter').val() ? $('#customerNoFilter').val() : '';
  var supplierNoI = $('#supplierNoFilter').val() ? $('#supplierNoFilter').val() : '';

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
      'url':'php/filterWholesale.php',
      'data': {
        fromDate: fromDateI,
        toDate: toDateI,
        status: statusI,
        product: productI,
        customer: customerNoI,
        supplier: supplierNoI
      } 
    },
    'columns': [
      { data: 'serial_no' },
      { data: 'po_no' },
      { data: 'created_datetime' },
      { data: 'customer_supplier' },
      { data: 'product' },
      { data: 'vehicle_no' },
      { data: 'driver' },
      { data: 'total_item' },
      { data: 'total_weight' },
      { data: 'total_reject' },
      { data: 'total_price' },
      { 
        data: 'id',
        render: function ( data, type, row ) {
          return '<div class="row"><div class="col-3"><button type="button" id="edit'+data+'" onclick="edit('+data+')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button></div>'+
          // '<div class="col-3"><button type="button" id="print'+data+'" onclick="print('+data+')" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></button></div>'+
          // '<div class="col-3"><button type="button" id="deactivate'+data+'" onclick="deactivate('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></div>'+
          '</div>';
        }
      }
    ],
    // "footerCallback": function(row, data, start, end, display) {
    //   var api = this.api();

    //   // Calculate total for 'total_cages' column
    //   var totalCages = api
    //       .column(4, { page: 'current' })
    //       .data()
    //       .reduce(function(a, b) {
    //           return a + parseFloat(b);
    //       }, 0);

    //   // Calculate total for 'total_birds' column
    //   var totalBirds = api
    //       .column(5, { page: 'current' })
    //       .data()
    //       .reduce(function(a, b) {
    //           return a + parseFloat(b);
    //       }, 0);

    //   var totalConts = api
    //     .column(6, { page: 'current' })
    //     .data()
    //     .reduce(function(a, b) {
    //         return a + parseFloat(b);
    //     }, 0);

    //   // Update footer with the total
    //   $(api.column(4).footer()).html(totalCages.toFixed(3));
    //   $(api.column(5).footer()).html(totalBirds.toFixed(3));
    //   $(api.column(6).footer()).html(totalConts);
    // }
  });

  // Add event listener for opening and closing details on row click
  $('#weightTable tbody').on('click', 'tr', function (e) {
      var tr = $(this); // The row that was clicked
      var row = table.row(tr);

      // Exclude specific td elements by checking the event target
      if ($(e.target).closest('td').hasClass('select-checkbox') || $(e.target).closest('td').hasClass('action-button')) {
        return;
      }

      if (row.child.isShown()) {
          // This row is already open - close it
          row.child.hide();
          tr.removeClass('shown');
      } else {
          $.post('php/getWholesale.php', { userID: row.data().id}, function (data) {
            var obj = JSON.parse(data);
            if (obj.status === 'success') {
              row.child(format(obj.message)).show();
              tr.addClass("shown");
            }
          });
      }
  });

  // Add event listener for opening and closing details
  // $('#weightTable tbody').on('click', 'td.dt-control', function () {
  //   var tr = $(this).closest('tr');
  //   var row = table.row( tr );

  //   if ( row.child.isShown() ) {
  //     // This row is already open - close it
  //     row.child.hide();
  //     tr.removeClass('shown');
  //   }
  //   else {
  //     // Open this row
  //     <?php 
  //       if($role == "ADMIN"){
  //         echo 'row.child( format(row.data()) ).show();tr.addClass("shown");';
  //       }
  //       else{
  //         echo 'row.child( formatNormal(row.data()) ).show();tr.addClass("shown");';
  //       }
  //     ?>
  //   }
  // });

  $('#filterSearch').on('click', function(){
    //$('#spinnerLoading').show();
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var statusI = $('#statusFilter').val();
    var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
    var customerNoI = $('#customerNoFilter').val() ? $('#customerNoFilter').val() : '';
    var supplierNoI = $('#supplierNoFilter').val() ? $('#supplierNoFilter').val() : '';

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
        'url':'php/filterWholesale.php',
        'data': {
          fromDate: fromDateI,
          toDate: toDateI,
          status: statusI,
          product: productI,
          customer: customerNoI,
          supplier: supplierNoI
        } 
      },
      'columns': [
        { data: 'serial_no' },
        { data: 'po_no' },
        { data: 'created_datetime' },
        { data: 'customer_supplier' },
        { data: 'product' },
        { data: 'vehicle_no' },
        { data: 'driver' },
        { data: 'total_item' },
        { data: 'total_weight' },
        { data: 'total_reject' },
        { data: 'total_price' },
        { 
          data: 'id',
          render: function ( data, type, row ) {
            return '<div class="row"><div class="col-3"><button type="button" id="edit'+data+'" onclick="edit('+data+')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button></div>'+
            // '<div class="col-3"><button type="button" id="print'+data+'" onclick="print('+data+')" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></button></div>'+
            // '<div class="col-3"><button type="button" id="deactivate'+data+'" onclick="deactivate('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></div>'+
            '</div>';
          }
        }
      ],
      // "footerCallback": function(row, data, start, end, display) {
      //   var api = this.api();

      //   // Calculate total for 'total_cages' column
      //   var totalCages = api
      //       .column(4, { page: 'current' })
      //       .data()
      //       .reduce(function(a, b) {
      //           return a + parseFloat(b);
      //       }, 0);

      //   // Calculate total for 'total_birds' column
      //   var totalBirds = api
      //       .column(5, { page: 'current' })
      //       .data()
      //       .reduce(function(a, b) {
      //           return a + parseFloat(b);
      //       }, 0);

      //   var totalConts = api
      //     .column(6, { page: 'current' })
      //     .data()
      //     .reduce(function(a, b) {
      //         return a + parseFloat(b);
      //     }, 0);

      //   // Update footer with the total
      //   $(api.column(4).footer()).html(totalCages.toFixed(3));
      //   $(api.column(5).footer()).html(totalBirds.toFixed(3));
      //   $(api.column(6).footer()).html(totalConts);
      // }
    });
  });

  // $.post('http://127.0.0.1:5002/', $('#setupForm').serialize(), function(data){
  //   if(data == "true"){
  //     $('#indicatorConnected').addClass('bg-primary');
  //     $('#checkingConnection').removeClass('bg-danger');
  //     //$('#captureWeight').removeAttr('disabled');
  //   }
  //   else{
  //     $('#indicatorConnected').removeClass('bg-primary');
  //     $('#checkingConnection').addClass('bg-danger');
  //     //$('#captureWeight').attr('disabled', true);
  //   }
  // });
  
  // setInterval(function () {
  //   $.post('http://127.0.0.1:5002/handshaking', function(data){
  //     if(data != "Error"){
  //       console.log("Data Received:" + data);
  //       var text = data.split(" ");

  //       if(text.length > 2){
  //         $('#indicatorWeight').html(text[text.length - 2] + ' ' + text[text.length - 1]);
  //         var convertTog1 = convertUnits(text[text.length - 2], text[text.length - 1], 'g');

  //         if($('#uom').val() && $('#product').val()){
  //           var uomDesc = $("#uomhidden option[value='"+$('#uom').val()+"']").text();
  //           var weight = $('#product :selected').data('unit');
  //           var convertTog2 = convertUnits(weight, uomDesc, 'g');
  //           var count = parseFloat(convertTog1) / parseFloat(convertTog2);
  //           $('#countingWeight').text(count.toFixed(0));
  //         }
  //       }
  //     }
  //   });
  // }, 500);

  $.validator.setDefaults({
    submitHandler: function () {
      if($('#extendModal').hasClass('show')){
        $('#spinnerLoading').show();
           
        $.post('php/wholesales.php', $('#extendForm').serialize(), function(data){
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
        });
      }
    }
  });

  $('#refreshBtn').on('click', function(){
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
    var supplierNoI = $('#supplierNoFilter').val() ? $('#supplierNoFilter').val() : '';

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
        'url':'php/filterWholesale.php',
        'data': {
          fromDate: fromDateI,
          toDate: toDateI,
          product: productI,
          supplier: supplierNoI
        } 
      },
      'columns': [
        { data: 'serial_no' },
        { data: 'created_datetime' },
        { data: 'supplier_name' },
        { data: 'product_name' },
        { data: 'gross' },
        { data: 'unit' },
        { data: 'count' },
        { 
          data: 'id',
          render: function ( data, type, row ) {
            return '<div class="row"><div class="col-3"><button type="button" id="edit'+data+'" onclick="edit('+data+')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button></div><div class="col-3"><button type="button" id="print'+data+'" onclick="print('+data+')" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></button></div><div class="col-3"><button type="button" id="deactivate'+data+'" onclick="deactivate('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></div></div>';
          }
        }
      ],
      "footerCallback": function(row, data, start, end, display) {
        var api = this.api();

        // Calculate total for 'total_cages' column
        var totalCages = api
            .column(4, { page: 'current' })
            .data()
            .reduce(function(a, b) {
                return a + parseFloat(b);
            }, 0);

        // Calculate total for 'total_birds' column
        var totalBirds = api
            .column(5, { page: 'current' })
            .data()
            .reduce(function(a, b) {
                return a + parseFloat(b);
            }, 0);

        var totalConts = api
          .column(6, { page: 'current' })
          .data()
          .reduce(function(a, b) {
              return a + parseFloat(b);
          }, 0);

        // Update footer with the total
        $(api.column(4).footer()).html(totalCages.toFixed(3));
        $(api.column(5).footer()).html(totalBirds.toFixed(3));
        $(api.column(5).footer()).html(totalConts);
      }
    });
  });
  
  <?php 
    if($role == "ADMIN"){
      echo "$('#manual').on('click', function(){
        if($(this).is(':checked')){
          $(this).val(1);
            $('#currentWeight').removeAttr('readonly');
        }
        else{
          $(this).val(0);
            $('#currentWeight').attr('readonly', 'readonly');
        }
      })";
    }
  ?>

  $('#extendModal').find('#inCButton').on('click', function(){
    var text = $('#indicatorWeight').text();
    var weight = parseFloat(text.replace("kg","").replace("g","").replace("oz","").replace("lbs",""))
    $('#currentWeight').val(weight.toFixed(3));
    $('#currentWeight').trigger("change");
  });

  $('#extendModal').find('#currentWeight').on('change', function(){
    var weight = $('#product :selected').data('unit');
    var uom = $('#product :selected').data('uom') ? $('#product :selected').data('uom') : '';
    var uomDesc = $("#uomhidden option[value='"+uom+"']").text();
    var cweight = $('#currentWeight').val();
    $('#indicatorWeight').text(cweight.toString() + ' ' + uomDesc);

    if(weight && cweight){
      var count = parseFloat(cweight) / parseFloat(weight);
      count = parseFloat(count).toFixed(0);
      $('#actualCount').val(count);
      $('#countingWeight').text(count);
    }
  });

  $('#extendModal').find('#product').on('change', function () {
    var desc = $('#product :selected').data('description');
    var weight = $('#product :selected').data('unit');
    var batch = $('#product :selected').data('batch')? $('#product :selected').data('batch') : '';
    var uom = $('#product :selected').data('uom') ? $('#product :selected').data('uom') : '';
    var cweight = $('#currentWeight').val();

    var uomDesc = $("#uomhidden option[value='"+uom+"']").text();

    $('#unitWeight').val(weight);
    $('#unitCountWeight').text(weight.toString() + ' ' + uomDesc);

    $('#productDesc').val(desc);
    $('#uom').val(uom).trigger('change');
    $('#batchNumber').val(batch);

    if(weight && cweight){
      var count = parseFloat(cweight) / parseFloat(weight);
      count = parseFloat(count).toFixed(0);
      $('#actualCount').val(count);
      $('#countingWeight').text(count);
    }
  });

  $('#extendModal').find('#uom').on('change', function () {
    
  });

  $('#statusFilter').on('change', function () {
    var status = $(this).val();
    if(status == "DISPATCH"){
      $('#customerStatusDiv').show();
      $('#supplierStatusDiv').hide();
    }
    else{
      $('#customerStatusDiv').hide();
      $('#supplierStatusDiv').show();
    }
  });

  $('#extendModal').find('#status').on('change', function () {
    var status = $(this).val();
    if(status == "DISPATCH"){
      $('#extendModal').find('#customerDiv').show();
      $('#extendModal').find('#supplierDiv').hide();
    }
    else{
      $('#extendModal').find('#customerDiv').hide();
      $('#extendModal').find('#supplierDiv').show();
    }
  });

  $('#extendModal').find('#customer').on('change', function () {
    var customer = $(this).val();
    if(customer == "OTHERS"){
      $('#extendModal').find('#customerOtherDiv').show();
    }
    else{
      $('#extendModal').find('#customerOtherDiv').hide();
    }
  });

  $('#extendModal').find('#supplier').on('change', function () {
    var supplier = $(this).val();
    if(supplier == "OTHERS"){
      $('#extendModal').find('#supplierOtherDiv').show();
    }
    else{
      $('#extendModal').find('#supplierOtherDiv').hide();
    }
  });
});

function updatePrices(isFromCurrency, rat){
  var totalPrice;
  var unitPrice = $('#unitPrice').val();
  var totalWeight = $('#totalWeight').val();

  if(isFromCurrency == 'Y'){
    unitPrice = (unitPrice / rate) * parseFloat(rat);
    $('#extendModal').find('#unitPrice').val(unitPrice.toFixed(2));
    rate = parseFloat(rat).toFixed(2);
  }
  else{
    unitPrice = unitPrice * parseFloat(rat);
    $('#extendModal').find('#unitPrice').val(unitPrice.toFixed(2));
    rate = parseFloat(rat).toFixed(2);
  }
  

  if(unitPrice != '' &&  moq != '' && totalWeight != ''){
    totalPrice = unitPrice * totalWeight;
    $('#totalPrice').val(totalPrice.toFixed(2));
  }
  else(
    $('#totalPrice').val((0).toFixed(2))
  )
}

function updateWeights(){
  var tareWeight =  0;
  var currentWeight =  0;
  var reduceWeight = 0;
  var moq = $('#moq').val();
  var totalWeight = 0;
  var actualWeight = 0;

  if($('#currentWeight').val()){
    currentWeight =  $('#currentWeight').val();
  }

  if($('#tareWeight').val()){
    tareWeight =  $('#tareWeight').val();
  }

  if($('#reduceWeight').val()){
    reduceWeight =  $('#reduceWeight').val();
  }

  if(tareWeight == 0){
    actualWeight = currentWeight - reduceWeight;
    actualWeight = Math.abs(actualWeight);
    $('#actualWeight').val(actualWeight.toFixed(2));
  }
  else{
    actualWeight = tareWeight - currentWeight - reduceWeight;
    actualWeight = Math.abs(actualWeight);
    $('#actualWeight').val(actualWeight.toFixed(2));
  }

  if(actualWeight != '' &&  moq != ''){
    totalWeight = actualWeight * moq;
    $('#totalWeight').val(totalWeight.toFixed(2));
  }
  else{
    $('#totalWeight').val((0).toFixed(2))
  };
}

function format (row) {
  var returnString = `
  <!-- Wholesale Information -->
  <div class="row">
    <p><span><strong style="font-size:120%; text-decoration: underline;">Wholesale Order Information</strong></span>
  </div>
  <div class="row">
    <div class="col-6">
      <p><strong>Customer/Supplier:</strong> ${row.customer_supplier}</p>
      <p><strong>Serial No:</strong> ${row.serial_no}</p>
      <p><strong>PO No:</strong> ${row.po_no}</p>
      <p><strong>Vehicle:</strong> ${row.vehicle_no}</p>
      <p><strong>Driver:</strong> ${row.driver}</p>
    </div>
    <div class="col-6">
      <p><strong>Total Item:</strong> ${row.total_item}</p>
      <p><strong>Total Weight:</strong> ${row.total_weight ? parseFloat(row.total_weight).toFixed(2) : '0.00'}</p>
      <p><strong>Total Reject:</strong> ${row.total_reject ? parseFloat(row.total_reject).toFixed(2) : '0.00'}</p>
      <p><strong>Total Price:</strong> RM ${parseFloat(row.total_price).toFixed(2)}</p>
    </div>
  </div>
  <hr>
  <div class="row">
      <table class="table table-bordered nowrap table-striped align-middle" style="width:100%">
          <thead>
              <tr>
                <th>Product</th>
                <th>Grade</th>
                <th>Gross</th>
                <th>Tare</th>
                <th>Net</th>
                <th>Reject</th>
                <th>Price</th>
                <th>Total</th>
                <th>Time</th>`;
              returnString += `
              </tr>
          </thead>
          <tbody>`;

          for (var i = 0; i < row.weightDetails.length; i++) {
              var detail = row.weightDetails[i]; 
              
              returnString += `
                  <tr>
                    <td>${detail.product_name}</td>
                    <td>${detail.grade}</td>
                    <td>${parseFloat(detail.gross).toFixed(2)} ${detail.unit}</td>
                    <td>${parseFloat(detail.tare).toFixed(2)} ${detail.unit}</td>
                    <td>${parseFloat(detail.net).toFixed(2)} ${detail.unit}</td>
                    <td>${detail.reject ? parseFloat(detail.reject).toFixed(2) : '0.00'} ${detail.unit}</td>
                    <td>RM ${parseFloat(detail.price).toFixed(2)}</td>
                    <td>RM ${parseFloat(detail.total).toFixed(2)}</td>
                    <td>${detail.time}</td>`;
                  returnString += `
                  </tr>`;
          }

          returnString += `
          </tbody>
      </table>
  </div>
  `;
  
  return returnString;
  // return '<div class="row"><div class="col-md-3"><p>Customer Name: '+row.customer_name+
  // '</p></div><div class="col-md-3"><p>Unit Weight: '+row.unit+
  // '</p></div><div class="col-md-3"><p>Weight Status: '+row.status+
  // '</p></div><div class="col-md-3"><p>MOQ: '+row.moq+
  // '</p></div></div><div class="row"><div class="col-md-3"><p>Address: '+row.customer_address+
  // '</p></div><div class="col-md-3"><p>Batch No: '+row.batchNo+
  // '</p></div><div class="col-md-3"><p>Weight By: '+row.userName+
  // '</p></div><div class="col-md-3"><p>Package: '+row.packages+
  // '</p></div></div><div class="row"><div class="col-md-3">'+
  // '</div><div class="col-md-3"><p>Lot No: '+row.lots_no+
  // '</p></div><div class="col-md-3"><p>Invoice No: '+row.invoiceNo+
  // '</p></div><div class="col-md-3 money"><p>Unit Price: '+row.unitPrice+
  // '</p></div></div><div class="row"><div class="col-md-3">'+
  // '</div><div class="col-md-3"><p>Order Weight: '+row.supplyWeight+
  // '</p></div><div class="col-md-3"><p>Delivery No: '+row.deliveryNo+
  // '</p></div><div class="col-md-3 money"><p>Total Weight: '+row.totalPrice+
  // '</p></div></div><div class="row"><div class="col-md-3"><p>Contact No: '+row.customer_phone+
  // '</p></div><div class="col-md-3"><p>Variance Weight: '+row.varianceWeight+
  // '</p></div><div class="col-md-3"><p>Purchase No: '+row.purchaseNo+
  // '</p></div><div class="col-md-3"><div class="row"><div class="col-3"><button type="button" class="btn btn-warning btn-sm" onclick="edit('+row.id+
  // ')"><i class="fas fa-pen"></i></button></div><div class="col-3"><button type="button" class="btn btn-danger btn-sm" onclick="deactivate('+row.id+
  // ')"><i class="fas fa-trash"></i></button></div><div class="col-3"><button type="button" class="btn btn-info btn-sm" onclick="print('+row.id+
  // ')"><i class="fas fa-print"></i></button></div><div class="col-3"><button type="button" class="btn btn-success btn-sm" onclick="portrait('+row.id+
  // ')"><i class="fas fa-receipt"></i></button></div></div></div></div>'+
  // '</div><div class="row"><div class="col-md-3"><p>Remark: '+row.remark+
  // '</p></div><div class="col-md-3"><p>% Variance: '+row.variancePerc+
  // '</p></div><div class="col-md-3"><p>Transporter: '+row.transporter_name+
  // '</p></div></div>';
  // ;
}

function formatNormal (row) {
  return '<div class="row"><div class="col-md-3"><p>Customer Name: '+row.customer_name+
  '</p></div><div class="col-md-3"><p>Unit Weight: '+row.unit+
  '</p></div><div class="col-md-3"><p>Weight Status: '+row.status+
  '</p></div><div class="col-md-3"><p>MOQ: '+row.moq+
  '</p></div></div><div class="row"><div class="col-md-3"><p>Address: '+row.customer_address+
  '</p></div><div class="col-md-3"><p>Batch No: '+row.batchNo+
  '</p></div><div class="col-md-3"><p>Weight By: '+row.userName+
  '</p></div><div class="col-md-3"><p>Package: '+row.packages+
  '</p></div></div><div class="row"><div class="col-md-3">'+
  '</div><div class="col-md-3"><p>Lot No: '+row.lots_no+
  '</p></div><div class="col-md-3"><p>Invoice No: '+row.invoiceNo+
  '</p></div><div class="col-md-3"><p>Unit Price: '+row.unitPrice+
  '</p></div></div><div class="row"><div class="col-md-3">'+
  '</div><div class="col-md-3"><p>Order Weight: '+row.supplyWeight+
  '</p></div><div class="col-md-3"><p>Delivery No: '+row.deliveryNo+
  '</p></div><div class="col-md-3"><p>Total Weight: '+row.totalPrice+
  '</p></div></div><div class="row"><div class="col-md-3"><p>Contact No: '+row.customer_phone+
  '</p></div><div class="col-md-3"><p>Variance Weight: '+row.varianceWeight+
  '</p></div><div class="col-md-3"><p>Purchase No: '+row.purchaseNo+
  '</p></div><div class="col-md-3"><div class="row"><div class="col-3"><button type="button" class="btn btn-warning btn-sm" onclick="edit('+row.id+
  ')"><i class="fas fa-pen"></i></button></div><div class="col-3"><button type="button" class="btn btn-info btn-sm" onclick="print('+row.id+
  ')"><i class="fas fa-print"></i></button></div><div class="col-3"><button type="button" class="btn btn-success btn-sm" onclick="portrait('+row.id+
  ')"><i class="fas fa-receipt"></i></button></div></div></div></div>'+
  '</div><div class="row"><div class="col-md-3"><p>Remark: '+row.remark+
  '</p></div><div class="col-md-3"><p>% Variance: '+row.variancePerc+
  '</p></div><div class="col-md-3"><p>Transporter: '+row.transporter_name+
  '</p></div></div>';
}

function newEntry(){
  $('#extendModal').find('#id').val("");
  $('#extendModal').find('#serialNumber').val("");
  $('#extendModal').find('#poNumber').val("");
  $('#extendModal').find('#status').val("DISPATCH");
  $('#extendModal').find('#customer').val("").trigger('change');
  $('#extendModal').find('#supplier').val("").trigger('change');
  $('#extendModal').find('#vehicleNo').val("");
  $('#extendModal').find('#driver').val("");
  $('#extendModal').find('#remark').val("");
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

function numberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function edit(id) {
  $('#spinnerLoading').show();
  $.post('php/getWholesale.php', {userID: id}, function(data){
    var obj = JSON.parse(data);
    
    if(obj.status === 'success'){
      $('#extendModal').find('#id').val(obj.message.id);
      $('#extendModal').find('#status').val(obj.message.status).trigger('change');
      $('#extendModal').find('#customer').val(obj.message.customer).trigger('change');
      $('#extendModal').find('#supplier').val(obj.message.supplier).trigger('change');
      $('#extendModal').find('#vehicle').val(obj.message.vehicle_no).trigger('change');
      $('#extendModal').find('#driver').val(obj.message.driver).trigger('change');
      $('#extendModal').find('#remark').val(obj.message.remark);
      
      // Populate weight details table
      var tbody = $('#weightDetailsTable');
      tbody.empty();
      
      if(obj.message.weightDetails && obj.message.weightDetails.length > 0) {
        for(var i = 0; i < obj.message.weightDetails.length; i++) {
          var detail = obj.message.weightDetails[i];
          var row = `
            <tr class="details">
              <td style="display:none">
                <input type="hidden" id="product${i}" name="weightDetails[${i}][product]" value="${detail.product}">
                <input type="hidden" id="product_desc${i}" name="weightDetails[${i}][product_desc]" value="${detail.product_desc}">
                <input type="hidden" id="pretare${i}" name="weightDetails[${i}][pretare]" value="${detail.pretare}">
                <input type="hidden" id="unit${i}" name="weightDetails[${i}][unit]" value="${detail.unit}">
                <input type="hidden" id="package${i}" name="weightDetails[${i}][package]" value="${detail.package}">
                <input type="hidden" id="fixedfloat${i}" name="weightDetails[${i}][fixedfloat]" value="${detail.fixedfloat}">
                <input type="hidden" id="isedit${i}" name="weightDetails[${i}][isedit]" value="${detail.isedit}">
              </td>
              <td><input type="hidden" id="product_name${i}" name="weightDetails[${i}][product_name]" value="${detail.product_name}">${detail.product_name}</td>
              <td>
                <select class="form-control select2" id="grade${i}" name="weightDetails[${i}][grade]">
                  <?php while($rowGrade=mysqli_fetch_assoc($grades)){ ?>
                    <option value="<?=$rowGrade['units'] ?>"><?=$rowGrade['units'] ?></option>
                  <?php } ?>
                </select>
              </td>
              <td><input type="hidden" id="gross${i}" name="weightDetails[${i}][gross]" value="${detail.gross}">${parseFloat(detail.gross).toFixed(2)} ${detail.unit}</td>
              <td><input type="hidden" id="tare${i}" name="weightDetails[${i}][tare]" value="${detail.tare}">${parseFloat(detail.tare).toFixed(2)} ${detail.unit}</td>
              <td><input type="hidden" id="net${i}" name="weightDetails[${i}][net]" value="${detail.net}">${parseFloat(detail.net).toFixed(2)} ${detail.unit}</td>
              <td><input type="number" class="form-control" id="reject${i}" name="weightDetails[${i}][reject]" value="${parseFloat(detail.reject).toFixed(2) || '0.00'}"></td>
              <td><input type="hidden" id="price${i}" name="weightDetails[${i}][price]" value="${detail.price}">RM ${parseFloat(detail.price).toFixed(2)}</td>
              <td><input type="hidden" id="total${i}" name="weightDetails[${i}][total]" value="${detail.total}">RM ${parseFloat(detail.total).toFixed(2)}</td>
              <td><input type="hidden" id="time${i}" name="weightDetails[${i}][time]" value="${detail.time}">${detail.time}</td>
              <td><button type="button" class="btn btn-danger btn-sm" onclick="removeWeightDetail(this)"><i class="fas fa-trash"></i></button></td>
            </tr>
          `;
          tbody.append(row);
          
          // Set the selected value for the grade dropdown
          tbody.find(`select[name="weightDetails[${i}][grade]"]`).val(detail.grade);
        }

        $('.select2').each(function() {
          $(this).select2({
              allowClear: true,
              placeholder: "Please Select",
              // Conditionally set dropdownParent based on the element’s location
              dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-body') : undefined
          });
        });
      }
      
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

function removeWeightDetail(button) {
  $(button).closest('tr').remove();
}

function deactivate(id) {
  if (confirm('Are you sure you want to delete this items?')) {
    $('#spinnerLoading').show();
    $.post('php/deleteCount.php', {userID: id}, function(data){
      var obj = JSON.parse(data);

      if(obj.status === 'success'){
        toastr["success"](obj.message, "Success:");
        $('#weightTable').DataTable().ajax.reload();
        /*$.get('weightPage.php', function(data) {
          $('#mainContents').html(data);
        });*/
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
}

function print(id) {
  $.post('php/print.php', {userID: id}, function(data){
    var obj = JSON.parse(data);

    if(obj.status === 'success'){
      var printWindow = window.open('', '', 'height=400,width=800');
      printWindow.document.write(obj.message);
      printWindow.document.close();
      setTimeout(function(){
        printWindow.print();
        printWindow.close();
      }, 500);
    }
    else if(obj.status === 'failed'){
      toastr["error"](obj.message, "Failed:");
    }
    else{
      toastr["error"]("Something wrong when activate", "Failed:");
    }
  });
}

function portrait(id) {
  $.post('php/printportrait.php', {userID: id, file: 'weight'}, function(data){
    var obj = JSON.parse(data);

    if(obj.status === 'success'){
      var printWindow = window.open('', '', 'height=400,width=800');
      printWindow.document.write(obj.message);
      printWindow.document.close();
      setTimeout(function(){
        printWindow.print();
        printWindow.close();
      }, 500);
    }
    else if(obj.status === 'failed'){
      toastr["error"](obj.message, "Failed:");
    }
    else{
      toastr["error"]("Something wrong when activate", "Failed:");
    }
  });
}
</script>