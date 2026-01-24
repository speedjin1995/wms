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
    $supplies = $db->query("SELECT * FROM supplies WHERE deleted = '0' AND customer = '$company' ORDER BY supplier_name ASC");
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' AND customer = '$company' ORDER BY customer_name ASC");
    $vehicles2 = $db->query("SELECT * FROM vehicles WHERE deleted = '0' AND customer = '$company' ORDER BY veh_number ASC");
    $users = $db->query("SELECT * FROM users WHERE deleted = '0' AND customer = '$company' ORDER BY name ASC");

  } else {
    $products = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $supplies = $db->query("SELECT * FROM supplies WHERE deleted = '0' ORDER BY supplier_name ASC");
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' ORDER BY customer_name ASC");
    $vehicles2 = $db->query("SELECT * FROM vehicles WHERE deleted = '0' ORDER BY veh_number ASC");
    $users = $db->query("SELECT * FROM users WHERE deleted = '0' ORDER BY name ASC");
  }
}
?>


<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">Weighbridge</h1>
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
                  <label>Transaction Status</label>
                  <select class="form-control" id="transactionStatusFilter" name="transactionStatusFilter">
                    <option selected>-</option>
                    <option value="Sales">Dispatch</option>
                    <option value="Purchase">Receiving</option>
                    <!-- <option value="Local">Internal Transfer</option>
                    <option value="Misc">Miscellaneous</option> -->
                  </select>
                </div>
              </div>

              <div class="col-3" id="customerDiv" style="display: none;">
                <div class="form-group">
                  <label>Customer</label>
                  <select class="form-control select2" id="customerNoFilter" name="customerNoFilter">
                    <option value="" selected disabled hidden>Please Select</option>
                    <?php while($rowCustomer2=mysqli_fetch_assoc($customers)){ ?>
                      <option value="<?=$rowCustomer2['customer_name'] ?>"><?=$rowCustomer2['customer_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="col-3" id="supplierDiv">
                <div class="form-group">
                  <label>Supplier</label>
                  <select class="form-control select2" id="supplierNoFilter" name="supplierNoFilter">
                    <option value="" selected disabled hidden>Please Select</option>
                    <?php while($rowCustomer2=mysqli_fetch_assoc($supplies)){ ?>
                      <option value="<?=$rowCustomer2['supplier_name'] ?>"><?=$rowCustomer2['supplier_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="col-3">
                <div class="form-group">
                  <label>Vehicle No</label>
                  <select class="form-control select2" id="vehicleNoFilter" name="vehicleNoFilter">
                    <option value="" selected disabled hidden>Please Select</option>
                    <?php while($rowVehicle=mysqli_fetch_assoc($vehicles2)){ ?>
                      <option value="<?=$rowVehicle['veh_number'] ?>"><?=$rowVehicle['veh_number'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-3">
                <div class="form-group">
                  <label>Status</label>
                  <select class="form-control select2" id="statusFilter" name="statusFilter" style="width: 100%;">
                    <!-- <option value="N">Pending</option> -->
                    <option value="Y" selected>Complete</option>
                  </select>
                </div>
              </div>
              <div class="col-3">
                <div class="form-group">
                  <label>Product</label>
                  <select class="form-control select2" id="productFilter" name="productFilter" style="width: 100%;">
                    <option selected="selected">-</option>
                    <?php while($rowStatus2=mysqli_fetch_assoc($products)){ ?>
                      <option value="<?=$rowStatus2['product_name'] ?>"><?=$rowStatus2['product_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-3">
                <div class="form-group">
                  <label>Transaction ID</label>
                  <input type="text" id="transactionIDFilter" name="transactionIDFilter" class="form-control" placeholder="Transaction ID">
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
                  <th>Transaction <br>Id</th>
                  <th>Transaction <br>Date</th>
                  <th>Transaction <br>Status</th>
                  <th>DO/PO <br>No.</th>
                  <th>Vehicle <br>No.</th>
                  <th>Customer/<br>Supplier</th>
                  <th>Product</th>
                  <th>Incoming <br>Weight</th>
                  <th>Incoming <br>Date</th>
                  <th>Outgoing <br>Weight</th>
                  <th>Outgoing <br>Date</th>
                  <th>Nett <br>Weight</th>
                  <th>Reduce <br>Weight</th>
                  <th>Total Nett <br>Weight</th>
                  <!-- <th width="5%">Action</th> -->
                </tr>
              </thead>
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
  var transactionStatusI = $('#transactionStatusFilter').val();
  var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
  var customerNoI = $('#customerNoFilter').val() ? $('#customerNoFilter').val() : '';
  var supplierNoI = $('#supplierNoFilter').val() ? $('#supplierNoFilter').val() : '';
  var vehicleNoI = $('#vehicleNoFilter').val() ? $('#vehicleNoFilter').val() : '';
  var statusI = $('#statusFilter').val() ? $('#statusFilter').val() : '';
  var transactionIdI = $('#transactionIDFilter').val() ? $('#transactionIDFilter').val() : '';

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
      'url':'php/filterWeighbridge.php',
      'data': {
        fromDate: fromDateI,
        toDate: toDateI,
        status: statusI,
        product: productI,
        customer: customerNoI,
        supplier: supplierNoI,
        vehicle: vehicleNoI,
        transactionStatus: transactionStatusI,
        transactionId: transactionIdI
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
      { data: 'transaction_id' },
      { data: 'transaction_date' },
      { data: 'transaction_status' },
      { data: 'do_po' },
      { data: 'lorry_plate_no1' },
      { data: 'customer_supplier' },
      { data: 'product_name' },
      { data: 'gross_weight1' },
      { data: 'gross_weight1_date' },
      { data: 'tare_weight1' },
      { data: 'tare_weight1_date' },
      { data: 'nett_weight1' },
      { data: 'reduce_weight' },
      { data: 'final_weight' },
      // { 
      //   data: 'id',
      //   render: function ( data, type, row ) {
      //     return '<button type="button" onclick="printSlip('+data+')" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></button>';
      //   }
      // }
    ],
    // "footerCallback": function(row, data, start, end, display) {
    //   var api = this.api();

    //   var totalItem = api
    //     .column(8, { page: 'current' })
    //     .data()
    //     .reduce(function(a, b) {
    //       return a + parseFloat(b || 0);
    //     }, 0);

    //   var totalWeight = api
    //     .column(9, { page: 'current' })
    //     .data()
    //     .reduce(function(a, b) {
    //       return a + parseFloat(b || 0);
    //     }, 0);

    //   var totalReject = api
    //     .column(10, { page: 'current' })
    //     .data()
    //     .reduce(function(a, b) {
    //       return a + parseFloat(b || 0);
    //     }, 0);

    //   $(api.column(8).footer()).html(totalItem);
    //   $(api.column(9).footer()).html(totalWeight.toFixed(2));
    //   $(api.column(10).footer()).html(totalReject.toFixed(2));
    // }
  });

  $('#filterSearch').on('click', function(){
    //$('#spinnerLoading').show();
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var transactionStatusI = $('#transactionStatusFilter').val();
    var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
    var customerNoI = $('#customerNoFilter').val() ? $('#customerNoFilter').val() : '';
    var supplierNoI = $('#supplierNoFilter').val() ? $('#supplierNoFilter').val() : '';
    var vehicleNoI = $('#vehicleNoFilter').val() ? $('#vehicleNoFilter').val() : '';
    var statusI = $('#statusFilter').val() ? $('#statusFilter').val() : '';
    var transactionIdI = $('#transactionIDFilter').val() ? $('#transactionIDFilter').val() : '';

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
        'url':'php/filterWeighbridge.php',
        'data': {
          fromDate: fromDateI,
          toDate: toDateI,
          status: statusI,
          product: productI,
          customer: customerNoI,
          supplier: supplierNoI,
          vehicle: vehicleNoI,
          transactionStatus: transactionStatusI,
          transactionId: transactionIdI
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
        { data: 'transaction_id' },
        { data: 'transaction_date' },
        { data: 'transaction_status' },
        { data: 'do_po' },
        { data: 'lorry_plate_no1' },
        { data: 'customer_supplier' },
        { data: 'product_name' },
        { data: 'gross_weight1' },
        { data: 'gross_weight1_date' },
        { data: 'tare_weight1' },
        { data: 'tare_weight1_date' },
        { data: 'nett_weight1' },
        { data: 'reduce_weight' },
        { data: 'final_weight' },
        // { 
        //   data: 'id',
        //   render: function ( data, type, row ) {
        //     return '<button type="button" onclick="printSlip('+data+')" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></button>';
        //   }
        // }
      ],
      // "footerCallback": function(row, data, start, end, display) {
      //   var api = this.api();

      //   var totalItem = api
      //     .column(8, { page: 'current' })
      //     .data()
      //     .reduce(function(a, b) {
      //       return a + parseFloat(b || 0);
      //     }, 0);

      //   var totalWeight = api
      //     .column(9, { page: 'current' })
      //     .data()
      //     .reduce(function(a, b) {
      //       return a + parseFloat(b || 0);
      //     }, 0);

      //   var totalReject = api
      //     .column(10, { page: 'current' })
      //     .data()
      //     .reduce(function(a, b) {
      //       return a + parseFloat(b || 0);
      //     }, 0);

      //   $(api.column(8).footer()).html(totalItem);
      //   $(api.column(9).footer()).html(totalWeight.toFixed(2));
      //   $(api.column(10).footer()).html(totalReject.toFixed(2));
      // }
    });
  });

  $('#exportExcel').on('click', function(){
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var transactionStatusI = $('#transactionStatusFilter').val();
    var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
    var customerNoI = $('#customerNoFilter').val() ? $('#customerNoFilter').val() : '';
    var supplierNoI = $('#supplierNoFilter').val() ? $('#supplierNoFilter').val() : '';
    var vehicleNoI = $('#vehicleNoFilter').val() ? $('#vehicleNoFilter').val() : '';
    var statusI = $('#statusFilter').val() ? $('#statusFilter').val() : '';
    var transactionIdI = $('#transactionIDFilter').val() ? $('#transactionIDFilter').val() : '';

    var selectedIds = []; // An array to store the selected 'id' values
    $("#weightTable tbody input[type='checkbox']").each(function () {
      if (this.checked) {
          selectedIds.push($(this).val());
      }
    });

    if (selectedIds.length > 0){
      window.open("php/exportWb.php?fromDate="+fromDateI+"&toDate="+toDateI+"&transactionStatus="+transactionStatusI+
      "&status="+statusI+"&customer="+customerNoI+"&supplier="+supplierNoI+"&product="+productI+"&vehicle="+vehicleNoI+
      "&transactionId="+transactionIdI+"&isMulti=Y&ids="+selectedIds);
    }else{
      window.open("php/exportWb.php?fromDate="+fromDateI+"&toDate="+toDateI+"&transactionStatus="+transactionStatusI+
      "&status="+statusI+"&customer="+customerNoI+"&supplier="+supplierNoI+"&product="+productI+"&vehicle="+vehicleNoI+
      "&transactionId="+transactionIdI+"&isMulti=N");
    }
  });

  $('#exportPdf').on('click', function(){
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var transactionStatusI = $('#transactionStatusFilter').val();
    var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
    var customerNoI = $('#customerNoFilter').val() ? $('#customerNoFilter').val() : '';
    var supplierNoI = $('#supplierNoFilter').val() ? $('#supplierNoFilter').val() : '';
    var vehicleNoI = $('#vehicleNoFilter').val() ? $('#vehicleNoFilter').val() : '';
    var statusI = $('#statusFilter').val() ? $('#statusFilter').val() : '';
    var transactionIdI = $('#transactionIDFilter').val() ? $('#transactionIDFilter').val() : '';

    var selectedIds = []; // An array to store the selected 'id' values
    $("#weightTable tbody input[type='checkbox']").each(function () {
      if (this.checked) {
          selectedIds.push($(this).val());
      }
    });

    if (selectedIds.length > 0){
      window.open("php/exportPdfWb.php?fromDate="+fromDateI+"&toDate="+toDateI+"&transactionStatus="+transactionStatusI+
      "&status="+statusI+"&customer="+customerNoI+"&supplier="+supplierNoI+"&product="+productI+"&vehicle="+vehicleNoI+
      "&transactionId="+transactionIdI+"&isMulti=Y&ids="+selectedIds);
    }else{
      window.open("php/exportPdfWb.php?fromDate="+fromDateI+"&toDate="+toDateI+"&transactionStatus="+transactionStatusI+
      "&status="+statusI+"&customer="+customerNoI+"&supplier="+supplierNoI+"&product="+productI+"&vehicle="+vehicleNoI+
      "&transactionId="+transactionIdI+"&isMulti=N");
    }
  });

  $('#transactionStatusFilter').on('change', function(){
    var status = $(this).val();
    $('#customerNoFilter').val('').trigger('change');
    $('#supplierNoFilter').val('').trigger('change');
    if (status == 'Sales' || status == 'Misc') {
      $('#supplierDiv').hide();
      $('#customerDiv').show();
    } else {
      $('#customerDiv').hide();
      $('#supplierDiv').show();
    }
  });
});

function printSlip(id) {
  $.post('php/printWeighbridge.php', {userID: id, file: 'weight', isEmptyContainer: 'N'}, function(data){
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