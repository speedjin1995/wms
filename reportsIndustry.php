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

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>


<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?=$languageArray['reports_code'][$language]?></h1>
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
                  <label><?=$languageArray['transaction_status_code'][$language]?></label>
                  <select class="form-control" id="transactionStatusFilter" name="transactionStatusFilter">
                    <option value="OUTGOING" selected><?=$languageArray['outgoing_code'][$language]?></option>
                    <option value="INCOMING"><?=$languageArray['incoming_code'][$language]?></option>
                  </select>
                </div>
              </div>

              <div class="col-3" id="customerStatusDiv">
                <div class="form-group">
                  <label><?=$languageArray['customer_code'][$language]?></label>
                  <select class="form-control select2" id="customerNoFilter" name="customerNoFilter">
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($rowCustomer2=mysqli_fetch_assoc($customers)){ ?>
                      <option value="<?=$rowCustomer2['id'] ?>"><?=$rowCustomer2['customer_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="col-3" id="supplierStatusDiv" style="display: none;">
                <div class="form-group">
                  <label><?=$languageArray['supplier_code'][$language]?></label>
                  <select class="form-control select2" id="supplierNoFilter" name="supplierNoFilter">
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($rowCustomer2=mysqli_fetch_assoc($supplies)){ ?>
                      <option value="<?=$rowCustomer2['id'] ?>"><?=$rowCustomer2['supplier_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="col-3">
                <div class="form-group">
                  <label><?=$languageArray['vehicle_no_code'][$language]?></label>
                  <select class="form-control select2" id="vehicleNoFilter" name="vehicleNoFilter">
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($rowVehicle=mysqli_fetch_assoc($vehicles2)){ ?>
                      <option value="<?=$rowVehicle['veh_number'] ?>"><?=$rowVehicle['veh_number'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="col-3" id="otherVehicleFilterDiv" style="display: none;">
                <div class="form-group">
                  <label><?=$languageArray['other_vehicle_no_code'][$language]?></label>
                  <input type="text" class="form-control" id="otherVehicleNoFilter" name="otherVehicleNoFilter" placeholder="<?=$languageArray['please_enter_vehicle_no_code'][$language]?>">
                </div>
              </div>

              <div class="col-3">
                <div class="form-group">
                  <label><?=$languageArray['checked_by_code'][$language]?></label>
                  <input type="text" class="form-control" id="checkedByFilter" name="checkedByFilter" placeholder="<?=$languageArray['please_enter_name_code'][$language]?>">
                </div>
              </div>

              <div class="col-3">
                <div class="form-group">
                  <label><?=$languageArray['weighed_by_code'][$language]?></label>
                  <select class="form-control select2" id="weightByFilter" name="weightByFilter">
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($rowUser=mysqli_fetch_assoc($users)){ ?>
                      <option value="<?=$rowUser['id'] ?>"><?=$rowUser['name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="col-3">
                <div class="form-group">
                  <label><?=$languageArray['status_code'][$language]?></label>
                  <select class="form-control" id="statusFilter" name="statusFilter">
                    <option value="active" selected><?=$languageArray['active_code'][$language]?></option>
                    <option value="deleted"><?=$languageArray['deleted_code'][$language]?></option>
                  </select>
                </div>
              </div>

              <!--div class="col-3">
                <div class="form-group">
                  <label><?=$languageArray['product_code'][$language]?></label>
                  <select class="form-control select2" id="productFilter" name="productFilter" style="width: 100%;">
                    <option selected="selected">-</option>
                    <?php while($rowStatus2=mysqli_fetch_assoc($products)){ ?>
                      <option value="<?=$rowStatus2['id'] ?>"><?=$rowStatus2['product_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div-->
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
              <div class="col-6"></div>
              <div class="col-3">
                <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="exportPdf"><?=$languageArray['export_pdf_code'][$language]?></button>
              </div>
              <div class="col-3">
                <button type="button" class="btn btn-block bg-gradient-success btn-sm" id="exportExcel"><?=$languageArray['export_excel_code'][$language]?></button>
              </div>
            </div>
          </div>

          <div class="card-body">
            <table id="weightTable" class="table table-bordered table-striped display">
              <thead>
                <tr>
                  <th><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
                  <th><?=$languageArray['serial_no_code'][$language]?></th>
                  <th><?=$languageArray['do_po_no_code'][$language]?></th>
                  <th><?=$languageArray['sec_bill_no_code'][$language]?></th>
                  <th><?=$languageArray['created_datetime_code'][$language]?></th>
                  <th><?=$languageArray['parent_code'][$language]?></th>
                  <th><?=$languageArray['customer_supplier_code'][$language]?></th>
                  <th><?=$languageArray['vehicle_no_code'][$language]?></th>
                  <th><?=$languageArray['driver_code'][$language]?></th>
                  <th><?=$languageArray['total_item_code'][$language]?></th>
                  <th><?=$languageArray['total_weight_code'][$language]?></th>
                  <th><?=$languageArray['total_reject_code'][$language]?></th>
                  <th><?=$languageArray['weighed_by_code'][$language]?></th>
                  <th><?=$languageArray['checked_by_code'][$language]?></th>
                  <!-- <th width="10%">Action</th> -->
                </tr>
              </thead>
              <tfoot>
                <tr>
                    <th colspan="9"><?=$languageArray['total_code'][$language]?></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
              </tfoot>
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
  var statusI = $('#statusFilter').val();
  var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
  var customerNoI = $('#customerNoFilter').val() ? $('#customerNoFilter').val() : '';
  var supplierNoI = $('#supplierNoFilter').val() ? $('#supplierNoFilter').val() : '';
  var vehicleNoI = $('#vehicleNoFilter').val() ? $('#vehicleNoFilter').val() : '';
  var otherVehicleNoI = $('#otherVehicleNoFilter').val() ? $('#otherVehicleNoFilter').val() : '';
  var checkedByI = $('#checkedByFilter').val() ? $('#checkedByFilter').val() : '';
  var weightedByI = $('#weightByFilter').val() ? $('#weightByFilter').val() : '';

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
        transactionStatus: transactionStatusI,
        status: statusI,
        product: productI,
        customer: customerNoI,
        supplier: supplierNoI,
        vehicle: vehicleNoI,
        otherVehicle: otherVehicleNoI,
        checkedBy: checkedByI,
        weightedBy: weightedByI,
        recordType: 'industrial'
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
      { data: 'security_bills' },
      { data: 'created_datetime' },
      { data: 'parent' },
      { data: 'customer_supplier' },
      { data: 'vehicle_no' },
      { data: 'driver' },
      { data: 'total_item' },
      { data: 'total_weight' },
      { data: 'total_reject' },
      { data: 'weighted_by' },
      { data: 'checked_by' },
      // { 
      //   data: 'id',
      //   render: function ( data, type, row ) {
      //     return '<button type="button" onclick="printSlip('+data+')" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></button>';
      //   }
      // }
    ],
    "footerCallback": function(row, data, start, end, display) {
      var api = this.api();

      var totalItem = api
        .column(9, { page: 'current' })
        .data()
        .reduce(function(a, b) {
          return a + parseFloat(b || 0);
        }, 0);

      var totalWeight = api
        .column(10, { page: 'current' })
        .data()
        .reduce(function(a, b) {
          return a + parseFloat(b || 0);
        }, 0);

      var totalReject = api
        .column(11, { page: 'current' })
        .data()
        .reduce(function(a, b) {
          return a + parseFloat(b || 0);
        }, 0);

      $(api.column(9).footer()).html(totalItem);
      $(api.column(10).footer()).html(totalWeight.toFixed(2));
      $(api.column(11).footer()).html(totalReject.toFixed(2));
    }
  });

  $('#filterSearch').on('click', function(){
    //$('#spinnerLoading').show();
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var transactionStatusI = $('#transactionStatusFilter').val();
    var statusI = $('#statusFilter').val();
    var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
    var customerNoI = $('#customerNoFilter').val() ? $('#customerNoFilter').val() : '';
    var supplierNoI = $('#supplierNoFilter').val() ? $('#supplierNoFilter').val() : '';
    var vehicleNoI = $('#vehicleNoFilter').val() ? $('#vehicleNoFilter').val() : '';
    var otherVehicleNoI = $('#otherVehicleNoFilter').val() ? $('#otherVehicleNoFilter').val() : '';
    var checkedByI = $('#checkedByFilter').val() ? $('#checkedByFilter').val() : '';
    var weightedByI = $('#weightByFilter').val() ? $('#weightByFilter').val() : '';

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
          transactionStatus: transactionStatusI,
          status: statusI,
          product: productI,
          customer: customerNoI,
          supplier: supplierNoI,
          vehicle: vehicleNoI,
          otherVehicle: otherVehicleNoI,
          checkedBy: checkedByI,
          weightedBy: weightedByI,
          recordType: 'industrial'
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
        { data: 'security_bills' },
        { data: 'created_datetime' },
        { data: 'parent' },
        { data: 'customer_supplier' },
        { data: 'vehicle_no' },
        { data: 'driver' },
        { data: 'total_item' },
        { data: 'total_weight' },
        { data: 'total_reject' },
        { data: 'weighted_by' },
        { data: 'checked_by' },
        // { 
        //   data: 'id',
        //   render: function ( data, type, row ) {
        //     return '<button type="button" onclick="printSlip('+data+')" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></button>';
        //   }
        // }
      ],
      "footerCallback": function(row, data, start, end, display) {
        var api = this.api();

        var totalItem = api
          .column(9, { page: 'current' })
          .data()
          .reduce(function(a, b) {
            return a + parseFloat(b || 0);
          }, 0);

        var totalWeight = api
          .column(10, { page: 'current' })
          .data()
          .reduce(function(a, b) {
            return a + parseFloat(b || 0);
          }, 0);

        var totalReject = api
          .column(11, { page: 'current' })
          .data()
          .reduce(function(a, b) {
            return a + parseFloat(b || 0);
          }, 0);

        $(api.column(9).footer()).html(totalItem);
        $(api.column(10).footer()).html(totalWeight.toFixed(2));
        $(api.column(11).footer()).html(totalReject.toFixed(2));
      }
    });
  });

  $('#exportExcel').on('click', function(){
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var transactionStatusI = $('#transactionStatusFilter').val();
    var statusI = $('#statusFilter').val();
    var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
    var customerNoI = $('#customerNoFilter').val() ? $('#customerNoFilter').val() : '';
    var supplierNoI = $('#supplierNoFilter').val() ? $('#supplierNoFilter').val() : '';
    var vehicleNoI = $('#vehicleNoFilter').val() ? $('#vehicleNoFilter').val() : '';
    var otherVehicleNoI = $('#otherVehicleNoFilter').val() ? $('#otherVehicleNoFilter').val() : '';
    var checkedByI = $('#checkedByFilter').val() ? $('#checkedByFilter').val() : '';
    var weightedByI = $('#weightByFilter').val() ? $('#weightByFilter').val() : '';
    var selectedIds = []; // An array to store the selected 'id' values

    $("#weightTable tbody input[type='checkbox']").each(function () {
      if (this.checked) {
          selectedIds.push($(this).val());
      }
    });

    if (selectedIds.length > 0){
      window.open("php/export.php?fromDate="+fromDateI+"&toDate="+toDateI+"&transactionStatus="+transactionStatusI+"&status="+statusI+
      "&customer="+customerNoI+"&supplier="+supplierNoI+"&product="+productI+"&vehicle="+vehicleNoI+
      "&otherVehicle="+otherVehicleNoI+"&checkedBy="+checkedByI+"&weightedBy="+weightedByI+"&isMulti=Y&ids="+selectedIds);
    }else{
      window.open("php/export.php?fromDate="+fromDateI+"&toDate="+toDateI+"&transactionStatus="+transactionStatusI+"&status="+statusI+
      "&customer="+customerNoI+"&supplier="+supplierNoI+"&product="+productI+"&vehicle="+vehicleNoI+
      "&otherVehicle="+otherVehicleNoI+"&checkedBy="+checkedByI+"&weightedBy="+weightedByI+"&isMulti=N");
    }
  });

  $('#exportPdf').on('click', function(){
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var transactionStatusI = $('#transactionStatusFilter').val();
    var statusI = $('#statusFilter').val();
    var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
    var customerNoI = $('#customerNoFilter').val() ? $('#customerNoFilter').val() : '';
    var supplierNoI = $('#supplierNoFilter').val() ? $('#supplierNoFilter').val() : '';
    var vehicleNoI = $('#vehicleNoFilter').val() ? $('#vehicleNoFilter').val() : '';
    var otherVehicleNoI = $('#otherVehicleNoFilter').val() ? $('#otherVehicleNoFilter').val() : '';
    var checkedByI = $('#checkedByFilter').val() ? $('#checkedByFilter').val() : '';
    var weightedByI = $('#weightByFilter').val() ? $('#weightByFilter').val() : '';

    var selectedIds = []; // An array to store the selected 'id' values

    $("#weightTable tbody input[type='checkbox']").each(function () {
      if (this.checked) {
          selectedIds.push($(this).val());
      }
    });

    if (selectedIds.length > 0){
      window.open("php/exportPdf.php?fromDate="+fromDateI+"&toDate="+toDateI+"&transactionStatus="+transactionStatusI+"&status="+statusI+
      "&customer="+customerNoI+"&supplier="+supplierNoI+"&product="+productI+"&vehicle="+vehicleNoI+
      "&otherVehicle="+otherVehicleNoI+"&checkedBy="+checkedByI+"&weightedBy="+weightedByI+"&isMulti=Y&ids="+selectedIds);
    }else{
      window.open("php/exportPdf.php?fromDate="+fromDateI+"&toDate="+toDateI+"&transactionStatus="+transactionStatusI+"&status="+statusI+
      "&customer="+customerNoI+"&supplier="+supplierNoI+"&product="+productI+"&vehicle="+vehicleNoI+
      "&otherVehicle="+otherVehicleNoI+"&checkedBy="+checkedByI+"&weightedBy="+weightedByI+"&isMulti=N");
    }
  });

  $('#transactionStatusFilter').on('change', function () {
    var status = $(this).val();
    $('#customerNoFilter').val('').trigger('change');
    $('#supplierNoFilter').val('').trigger('change');
    if(status == "DISPATCH" || status == 'SALE-BAL'){
      $('#customerStatusDiv').show();
      $('#supplierStatusDiv').hide();
    }
    else{
      $('#customerStatusDiv').hide();
      $('#supplierStatusDiv').show();
    }
  });

  $('#vehicleNoFilter').on('change', function () {
    var vehicleNo = $(this).val();
    if(vehicleNo == "UNKOWN NO"){
      $('#otherVehicleFilterDiv').show();
    }
    else{
      $('#otherVehicleFilterDiv').hide();
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