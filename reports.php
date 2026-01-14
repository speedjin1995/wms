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
    $products = $db->query("SELECT * FROM products WHERE deleted = '0' AND customer = '$company'");
    $supplies = $db->query("SELECT * FROM supplies WHERE deleted = '0' AND customer = '$company'");
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' AND customer = '$company'");
  } else {
    $products = $db->query("SELECT * FROM products WHERE deleted = '0'");
    $supplies = $db->query("SELECT * FROM supplies WHERE deleted = '0'");
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0'");
  }
}
?>


<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">Reports</h1>
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

              <div class="col-3" id="customerDiv">
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

              <div class="col-3" id="supplierDiv" style="display: none;">
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
              <div class="col-6"></div>
              <div class="col-3">
                <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="exportPdf">Export PDF</button>
              </div>
              <div class="col-3">
                <button type="button" class="btn btn-block bg-gradient-success btn-sm" id="exportExcel">Export Excel</button>
              </div>
            </div>
          </div>

          <div class="card-body">
            <table id="weightTable" class="table table-bordered table-striped display">
              <thead>
                <tr>
                  <th><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
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
                  <th>Actions</th>
                </tr>
              </thead>
              <!-- <tfoot>
                <tr>
                    <th colspan="8">Total</th>
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

<script>
$(function () {
  const today = new Date();
  const tomorrow = new Date(today);
  const yesterday = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);
  yesterday.setDate(yesterday.getDate() - 7);

  $('.select2').select2({
    allowClear: true,
    placeholder: "Please Select"
  });

  //Date picker
  $('#fromDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: yesterday
  });

  $('#toDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: today
  });

  $('#selectAllCheckbox').on('change', function() {
    var checkboxes = $('#weightTable tbody input[type="checkbox"]');
    checkboxes.prop('checked', $(this).prop('checked')).trigger('change');
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
      {
        // Add a checkbox with a unique ID for each row
        data: 'id', // Assuming 'serialNo' is a unique identifier for each row
        className: 'select-checkbox',
        orderable: false,
        render: function (data, type, row) {
            return '<input type="checkbox" class="select-checkbox" id="checkbox_' + data + '" value="'+data+'"/>';
        }
      },
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
          return '<button type="button" onclick="printSlip('+data+')" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></button>';
        }
      }
    ],
    // "footerCallback": function(row, data, start, end, display) {
    //   var api = this.api();

    //   // Calculate total for 'total_cages' column
    //   var totalCages = api
    //       .column(7, { page: 'current' })
    //       .data()
    //       .reduce(function(a, b) {
    //           return a + parseFloat(b);
    //       }, 0);

    //   // Calculate total for 'total_birds' column
    //   var totalBirds = api
    //       .column(8, { page: 'current' })
    //       .data()
    //       .reduce(function(a, b) {
    //           return a + parseInt(b);
    //       }, 0);

    //   var totalConts = api
    //     .column(9, { page: 'current' })
    //     .data()
    //     .reduce(function(a, b) {
    //         return a + parseFloat(b);
    //     }, 0);


    //   // Update footer with the total
    //   $(api.column(7).footer()).html(totalCages.toFixed(3));
    //   $(api.column(8).footer()).html(totalBirds.toFixed(3));
    //   $(api.column(9).footer()).html(totalConts);
    // }
  });

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
      'searching': false,
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
        {
          // Add a checkbox with a unique ID for each row
          data: 'id', // Assuming 'serialNo' is a unique identifier for each row
          className: 'select-checkbox',
          orderable: false,
          render: function (data, type, row) {
              return '<input type="checkbox" class="select-checkbox" id="checkbox_' + data + '" value="'+data+'"/>';
          }
        },
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
            return '<button type="button" onclick="printSlip('+data+')" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></button>';
          }
        }
      ],
      // "footerCallback": function(row, data, start, end, display) {
      //   var api = this.api();

      //   // Calculate total for 'total_cages' column
      //   var totalCages = api
      //       .column(7, { page: 'current' })
      //       .data()
      //       .reduce(function(a, b) {
      //           return a + parseFloat(b);
      //       }, 0);

      //   // Calculate total for 'total_birds' column
      //   var totalBirds = api
      //       .column(8, { page: 'current' })
      //       .data()
      //       .reduce(function(a, b) {
      //           return a + parseFloat(b);
      //       }, 0);

      //   var totalConts = api
      //     .column(9, { page: 'current' })
      //     .data()
      //     .reduce(function(a, b) {
      //         return a + parseFloat(b);
      //     }, 0);


      //   // Update footer with the total
      //   $(api.column(7).footer()).html(totalCages.toFixed(3));
      //   $(api.column(8).footer()).html(totalBirds.toFixed(3));
      //   $(api.column(9).footer()).html(totalConts);
      // }
    });
  });

  $('#exportExcel').on('click', function(){
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var statusI = $('#statusFilter').val();
    var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
    var customerNoI = $('#customerNoFilter').val() ? $('#customerNoFilter').val() : '';
    var supplierNoI = $('#supplierNoFilter').val() ? $('#supplierNoFilter').val() : '';

    var selectedIds = []; // An array to store the selected 'id' values

    $("#weightTable tbody input[type='checkbox']").each(function () {
      if (this.checked) {
          selectedIds.push($(this).val());
      }
    });

    if (selectedIds.length > 0){
      window.open("php/export.php?fromDate="+fromDateI+"&toDate="+toDateI+"&status="+statusI+
      "&customer="+customerNoI+"&supplier="+supplierNoI+"&product="+productI+"&isMulti=Y&ids="+selectedIds);
    }else{
      window.open("php/export.php?fromDate="+fromDateI+"&toDate="+toDateI+"&status="+statusI+
      "&customer="+customerNoI+"&supplier="+supplierNoI+"&product="+productI+"&isMulti=N");
    }
  });

  $('#exportPdf').on('click', function(){
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var statusI = $('#statusFilter').val();
    var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
    var customerNoI = $('#customerNoFilter').val() ? $('#customerNoFilter').val() : '';
    var supplierNoI = $('#supplierNoFilter').val() ? $('#supplierNoFilter').val() : '';

    var selectedIds = []; // An array to store the selected 'id' values

    $("#weightTable tbody input[type='checkbox']").each(function () {
      if (this.checked) {
          selectedIds.push($(this).val());
      }
    });

    if (selectedIds.length > 0){
      window.open("php/exportPdf.php?fromDate="+fromDateI+"&toDate="+toDateI+"&status="+statusI+
      "&customer="+customerNoI+"&supplier="+supplierNoI+"&product="+productI+"&isMulti=Y&ids="+selectedIds);
    }else{
      window.open("php/exportPdf.php?fromDate="+fromDateI+"&toDate="+toDateI+"&status="+statusI+
      "&customer="+customerNoI+"&supplier="+supplierNoI+"&product="+productI+"&isMulti=N");
    }
  });

  $('#statusFilter').on('change', function(){
    var status = $(this).val();
    $('#customerNoFilter').val('').trigger('change');
    $('#supplierNoFilter').val('').trigger('change');
    if (status == 'DISPATCH'){
      $('#supplierDiv').hide();
      $('#customerDiv').show();
    } else {
      $('#customerDiv').hide();
      $('#supplierDiv').show();
    }
  });
});

function printSlip(id) {
  $.post('php/print.php', {userID: id}, function(data){
    var response = JSON.parse(data);
    if(response.status === 'success') {
      var printWindow = window.open('', '', 'height=' + screen.height + ',width=' + screen.width);
      printWindow.document.write(response.message);
      printWindow.document.close();
      setTimeout(function(){
        printWindow.print();
        printWindow.close();
      }, 500);
    } else {
      alert('Error: ' + response.message);
    }
  });
}
</script>