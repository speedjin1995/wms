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
  $port = 'COM5';
  $baudrate = 9600;
  $databits = "8";
  $parity = "N";
  $stopbits = '1';
	
	if(($row = $result->fetch_assoc()) !== null){
    $role = $row['role_code'];
    $port = $row['port'];
    $baudrate = $row['baudrate'];
    $databits = $row['databits'];
    $parity = $row['parity'];
    $stopbits = $row['stopbits'];
  }

  $products2 = $db->query("SELECT * FROM products WHERE deleted = '0' AND customer = '$company'");
  $products = $db->query("SELECT * FROM products WHERE deleted = '0' AND customer = '$company'");
  $units = $db->query("SELECT * FROM units WHERE deleted = '0'");
  $units1 = $db->query("SELECT * FROM units WHERE deleted = '0'");
  $supplies = $db->query("SELECT * FROM supplies WHERE deleted = '0' AND customer = '$company'");
}
?>
<select class="form-control" style="width: 100%;" id="uomhidden" name="uomhidden" style="display:none;"> 
  <option selected="selected">-</option>
  <?php while($rowunits2=mysqli_fetch_assoc($units1)){ ?>
    <option value="<?=$rowunits2['id'] ?>"><?=$rowunits2['units'] ?></option>
  <?php } ?>
</select>

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
        <h1 class="m-0 text-dark">Count Weighing</h1>
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
        <div class="card card-danger">
          <div class="card-header">
            <div class="row">
              <div class="col-8"></div>
              <div class="col-2">
                <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="refreshBtn"><i class="fas fa-sync"></i> Refresh</button>
              </div>
              <div class="col-2">
                <button type="button" class="btn btn-block bg-gradient-success btn-sm" onclick="newEntry()"><i class="fas fa-plus"></i> Add New Count</button>
              </div>
            </div>
          </div>

          <div class="card-body">
            <table id="weightTable" class="table table-bordered table-striped display">
              <thead>
                <tr>
                  <th>Serial No.</th>
                  <th>Created Datetime</th>
                  <th>Product</th>
                  <th>Gross Weight</th>
                  <th>Unit Weight</th>
                  <th>Count</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tfoot>
                <tr>
                    <th colspan="3">Total</th>
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
          <div class="row">

            <div class="col-md-4">
              <div class="small-box bg-primary">
                <a href="#" class="small-box-footer"><b>Total Weight</b></a>
                <div class="inner">
                  <h4 style="text-align: center; font-size: 50px" id="indicatorWeight">0.00kg</h4>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="small-box bg-warning">
                <a href="#" class="small-box-footer"><b>Unit Weight / PCS</b></a>
                <div class="inner">
                  <h4 style="text-align: center; font-size: 50px" id="unitCountWeight">0.00kg</h4>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="small-box bg-success">
                <a href="#" class="small-box-footer"><b>Total Count / PCS</b></a>
                <div class="inner">
                  <h4 style="text-align: center; font-size: 50px" id="countingWeight">0</h4>
                </div>
              </div>
            </div>
          </div>
              
          <div class="row">
            <div class="col-md-4">
              <input type="hidden" class="form-control" id="id" name="id">
              <div class="form-group">
                <label>Serial No. *</label>
                <input class="form-control" type="text" placeholder="Serial No" id="serialNumber" name="serialNumber" readonly>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Product *</label>
                <input type="hidden" class="form-control" id="productDesc" name="productDesc">
                <select class="form-control" style="width: 100%;" id="product" name="product" required>
                  <option selected="selected">-</option>
                  <?php while($row5=mysqli_fetch_assoc($products)){ ?>
                    <option 
                      value="<?=$row5['id'] ?>" 
                      data-description="<?=$row5['product_name'] ?>" 
                      data-batch="<?=$row5['batch_no'] ?>" 
                      data-uom="<?=$row5['uom'] ?>" 
                      data-unit="<?=$row5['weight'] ?>"><?=$row5['product_name'] ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Supplier *</label>
                <select class="form-control" style="width: 100%;" id="supplies" name="supplies" required>
                  <option selected="selected">-</option>
                  <?php while($rows=mysqli_fetch_assoc($supplies)){ ?>
                    <option value="<?=$rows['id'] ?>"><?=$rows['supplier_name'] ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Batch No. *</label>
                <input class="form-control" type="text" placeholder="Batch No" id="batchNumber" name="batchNumber" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Article No. *</label>
                <input class="form-control" type="text" placeholder="Article No" id="articleNumber" name="articleNumber" required>
              </div>
            </div>
          
            <div class="col-md-4">
              <div class="form-group">
                <label>IQC No. *</label>
                <input class="form-control" type="text" placeholder="IQC No" id="iqcNumber" name="iqcNumber" required>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>UOM * </label>
                <select class="form-control" style="width: 100%;" id="uom" name="uom"> 
                  <option selected="selected">-</option>
                  <?php while($rowunits=mysqli_fetch_assoc($units)){ ?>
                    <option value="<?=$rowunits['id'] ?>"><?=$rowunits['units'] ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Gross Weight *
                <?php 
                  if($role == "ADMIN"){         
                    echo '<span style="padding-left: 60px;"><input type="checkbox" class="form-check-input" id="manual" name="manual" value="0"/>Manual</span>';
                  }
                ?>
                </label>
                <div class="input-group">
                  <input class="form-control" type="number" placeholder="Current Weight" id="currentWeight" name="currentWeight" readonly required/>
                  <button type="button" class="btn btn-primary" id="inCButton"><i class="fas fa-sync"></i></button>
                </div>
              </div>
            </div>

            <div class="form-group col-md-4">
              <label>Unit Weight *</label>
              <div class="input-group">
                <input class="form-control" type="number" placeholder="Unit Weight" id="unitWeight" name="unitWeight" min="0" readonly/>
              </div>
            </div>

            <div class="form-group col-md-4">
              <label>Count *</label>
              <div class="input-group">
                <input class="form-control" type="number" placeholder="Actual Count" id="actualCount" name="actualCount" readonly/>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                  <label>Remark</label>
                  <textarea class="form-control" rows="1" placeholder="Enter ..." id="remark" name="remark"></textarea>
                </div>
              </div>                                            
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

<div class="modal fade" id="setupModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">

    <form role="form" id="setupForm">
      <div class="modal-header bg-gray-dark color-palette">
        <h4 class="modal-title">Setup</h4>
        <button type="button" class="close bg-gray-dark color-palette" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <div class="row">
          <div class="col-4">
            <div class="form-group">
              <label>Serial Port</label>
              <input class="form-control" type="text" id="serialPort" name="serialPort" value="<?=$port ?>">
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label>Baud Rate</label>
              <input class="form-control" type="number" id="serialPortBaudRate" name="serialPortBaudRate" value="<?=$baudrate ?>">
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label>Data Bits</label>
              <input class="form-control" type="text" id="serialPortDataBits" name="serialPortDataBits" value="<?=$databits ?>">
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-4">
            <div class="form-group">
              <label>Parity</label>
              <input class="form-control" type="text" id="serialPortParity" name="serialPortParity" value="<?=$parity ?>">
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label>Stop bits</label>
              <input class="form-control" type="text" id="serialPortStopBits" name="serialPortStopBits" value="<?=$stopbits ?>">
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer justify-content-between bg-gray-dark color-palette">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>

    </form>
  </div>
</div>      

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

  var fromDateI = $('#fromDate').val();
  var toDateI = $('#toDate').val();
  var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
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
      'url':'php/filterCount.php',
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
          .column(3, { page: 'current' })
          .data()
          .reduce(function(a, b) {
              return a + parseFloat(b);
          }, 0);

      // Calculate total for 'total_birds' column
      var totalBirds = api
          .column(4, { page: 'current' })
          .data()
          .reduce(function(a, b) {
              return a + parseFloat(b);
          }, 0);

      var totalConts = api
        .column(5, { page: 'current' })
        .data()
        .reduce(function(a, b) {
            return a + parseFloat(b);
        }, 0);

      // Update footer with the total
      $(api.column(3).footer()).html(totalCages);
      $(api.column(4).footer()).html(totalBirds);
      $(api.column(5).footer()).html(totalConts);
    }
  });

  // Add event listener for opening and closing details
  $('#weightTable tbody').on('click', 'td.dt-control', function () {
    var tr = $(this).closest('tr');
    var row = table.row( tr );

    if ( row.child.isShown() ) {
      // This row is already open - close it
      row.child.hide();
      tr.removeClass('shown');
    }
    else {
      // Open this row
      <?php 
        if($role == "ADMIN"){
          echo 'row.child( format(row.data()) ).show();tr.addClass("shown");';
        }
        else{
          echo 'row.child( formatNormal(row.data()) ).show();tr.addClass("shown");';
        }
      ?>
    }
  });

  $('#filterSearch').on('click', function(){
    //$('#spinnerLoading').show();
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
        'url':'php/filterCount.php',
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
            .column(3, { page: 'current' })
            .data()
            .reduce(function(a, b) {
                return a + parseFloat(b);
            }, 0);

        // Calculate total for 'total_birds' column
        var totalBirds = api
            .column(4, { page: 'current' })
            .data()
            .reduce(function(a, b) {
                return a + parseFloat(b);
            }, 0);

        var totalConts = api
          .column(5, { page: 'current' })
          .data()
          .reduce(function(a, b) {
              return a + parseFloat(b);
          }, 0);

        // Update footer with the total
        $(api.column(3).footer()).html(totalCages);
        $(api.column(4).footer()).html(totalBirds);
        $(api.column(5).footer()).html(totalConts);
      }
    });
  });

  $.post('http://127.0.0.1:5002/', $('#setupForm').serialize(), function(data){
    if(data == "true"){
      $('#indicatorConnected').addClass('bg-primary');
      $('#checkingConnection').removeClass('bg-danger');
      //$('#captureWeight').removeAttr('disabled');
    }
    else{
      $('#indicatorConnected').removeClass('bg-primary');
      $('#checkingConnection').addClass('bg-danger');
      //$('#captureWeight').attr('disabled', true);
    }
  });
  
  setInterval(function () {
    $.post('http://127.0.0.1:5002/handshaking', function(data){
      if(data != "Error"){
        console.log("Data Received:" + data);
        var text = data.split(" ");

        if(text.length > 2){
          $('#indicatorWeight').html(text[text.length - 2] + ' ' + text[text.length - 1]);
          var convertTog1 = convertUnits(text[text.length - 2], text[text.length - 1], 'g');

          if($('#uom').val() && $('#product').val()){
            var uomDesc = $("#uomhidden option[value='"+$('#uom').val()+"']").text();
            var weight = $('#product :selected').data('unit');
            var convertTog2 = convertUnits(weight, uomDesc, 'g');
            var count = parseFloat(convertTog1) / parseFloat(convertTog2);
            $('#countingWeight').text(count.toFixed(0));
          }
        }
      }
    });
  }, 500);

  $.validator.setDefaults({
    submitHandler: function () {
      if($('#extendModal').hasClass('show')){
          $('#spinnerLoading').show();
           
        $.post('php/insertCount.php', $('#extendForm').serialize(), function(data){
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
    var fromDateValue = '';
    var toDateValue = '';
    var statusFilter = '';
    var customerNoFilter = '';
    var vehicleFilter = '';
    var invoiceFilter = '';
    var batchFilter = '';
    var productFilter = '';

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
          'url':'php/loadCount.php'
      },
      'columns': [
        { data: 'serial_no' },
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
      "rowCallback": function( row, data, index ) {
        //$('td', row).css('background-color', '#E6E6FA');
      },
      "drawCallback": function(settings) {
        /*$('#salesInfo').text(settings.json.salesTotal);
        $('#purchaseInfo').text(settings.json.purchaseTotal);
        $('#localInfo').text(settings.json.localTotal);*/
      }
    });
  });

  $('#saleCard').on('click', function(){
    var fromDateValue = '';
    var toDateValue = '';
    var statusFilter = '1';
    var customerNoFilter = '';
    var vehicleFilter = '';
    var invoiceFilter = '';
    var batchFilter = '';
    var productFilter = '';

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
        'type': 'POST',
        'url':'php/filterWeight.php',
        'data': {
          fromDate: fromDateValue,
          toDate: toDateValue,
          status: statusFilter,
          customer: customerNoFilter,
          vehicle: vehicleFilter,
          invoice: invoiceFilter,
          batch: batchFilter,
          product: productFilter,
        } 
      },
      'columns': [
        { data: 'no' },
        { data: 'pStatus' },
        { data: 'status' },
        { data: 'serialNo' },
        { data: 'veh_number' },
        { data: 'product_name' },
        { data: 'currentWeight' },
        { data: 'inCDateTime' },
        { data: 'tare' },
        { data: 'outGDateTime' },
        { data: 'totalWeight' },
        { 
          className: 'dt-control',
          orderable: false,
          data: null,
          render: function ( data, type, row ) {
            return '<td class="table-elipse" data-toggle="collapse" data-target="#demo'+row.serialNo+'"><i class="fas fa-angle-down"></i></td>';
          }
        }
      ],
      "rowCallback": function( row, data, index ) {
        //$('td', row).css('background-color', '#E6E6FA');
      }
    });
  });

  $('#purchaseCard').on('click', function(){
    var fromDateValue = '';
    var toDateValue = '';
    var statusFilter = '2';
    var customerNoFilter = '';
    var vehicleFilter = '';
    var invoiceFilter = '';
    var batchFilter = '';
    var productFilter = '';

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
        'type': 'POST',
        'url':'php/filterWeight.php',
        'data': {
          fromDate: fromDateValue,
          toDate: toDateValue,
          status: statusFilter,
          customer: customerNoFilter,
          vehicle: vehicleFilter,
          invoice: invoiceFilter,
          batch: batchFilter,
          product: productFilter,
        } 
      },
      'columns': [
        { data: 'no' },
        { data: 'pStatus' },
        { data: 'status' },
        { data: 'serialNo' },
        { data: 'veh_number' },
        { data: 'product_name' },
        { data: 'currentWeight' },
        { data: 'inCDateTime' },
        { data: 'tare' },
        { data: 'outGDateTime' },
        { data: 'totalWeight' },
        { 
          className: 'dt-control',
          orderable: false,
          data: null,
          render: function ( data, type, row ) {
            return '<td class="table-elipse" data-toggle="collapse" data-target="#demo'+row.serialNo+'"><i class="fas fa-angle-down"></i></td>';
          }
        }
      ],
      "rowCallback": function( row, data, index ) {
        //$('td', row).css('background-color', '#E6E6FA');
      }
    });
  });

  $('#miscCard').on('click', function(){
    var fromDateValue = '';
    var toDateValue = '';
    var statusFilter = '3';
    var customerNoFilter = '';
    var vehicleFilter = '';
    var invoiceFilter = '';
    var batchFilter = '';
    var productFilter = '';

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
        'type': 'POST',
        'url':'php/filterWeight.php',
        'data': {
          fromDate: fromDateValue,
          toDate: toDateValue,
          status: statusFilter,
          customer: customerNoFilter,
          vehicle: vehicleFilter,
          invoice: invoiceFilter,
          batch: batchFilter,
          product: productFilter,
        } 
      },
      'columns': [
        { data: 'no' },
        { data: 'pStatus' },
        { data: 'status' },
        { data: 'serialNo' },
        { data: 'veh_number' },
        { data: 'product_name' },
        { data: 'currentWeight' },
        { data: 'inCDateTime' },
        { data: 'tare' },
        { data: 'outGDateTime' },
        { data: 'totalWeight' },
        { 
          className: 'dt-control',
          orderable: false,
          data: null,
          render: function ( data, type, row ) {
            return '<td class="table-elipse" data-toggle="collapse" data-target="#demo'+row.serialNo+'"><i class="fas fa-angle-down"></i></td>';
          }
        }
      ],
      "rowCallback": function( row, data, index ) {
        //$('td', row).css('background-color', '#E6E6FA');
      }
    });
  });

  $('#datePicker').on('click', function () {
    $('#datePicker').attr('data-info', '1');
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
  '</p></div><div class="col-md-3 money"><p>Unit Price: '+row.unitPrice+
  '</p></div></div><div class="row"><div class="col-md-3">'+
  '</div><div class="col-md-3"><p>Order Weight: '+row.supplyWeight+
  '</p></div><div class="col-md-3"><p>Delivery No: '+row.deliveryNo+
  '</p></div><div class="col-md-3 money"><p>Total Weight: '+row.totalPrice+
  '</p></div></div><div class="row"><div class="col-md-3"><p>Contact No: '+row.customer_phone+
  '</p></div><div class="col-md-3"><p>Variance Weight: '+row.varianceWeight+
  '</p></div><div class="col-md-3"><p>Purchase No: '+row.purchaseNo+
  '</p></div><div class="col-md-3"><div class="row"><div class="col-3"><button type="button" class="btn btn-warning btn-sm" onclick="edit('+row.id+
  ')"><i class="fas fa-pen"></i></button></div><div class="col-3"><button type="button" class="btn btn-danger btn-sm" onclick="deactivate('+row.id+
  ')"><i class="fas fa-trash"></i></button></div><div class="col-3"><button type="button" class="btn btn-info btn-sm" onclick="print('+row.id+
  ')"><i class="fas fa-print"></i></button></div><div class="col-3"><button type="button" class="btn btn-success btn-sm" onclick="portrait('+row.id+
  ')"><i class="fas fa-receipt"></i></button></div></div></div></div>'+
  '</div><div class="row"><div class="col-md-3"><p>Remark: '+row.remark+
  '</p></div><div class="col-md-3"><p>% Variance: '+row.variancePerc+
  '</p></div><div class="col-md-3"><p>Transporter: '+row.transporter_name+
  '</p></div></div>';
  ;
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
  $('#extendModal').find('#batchNumber').val("");
  $('#extendModal').find('#articleNumber').val("");
  $('#extendModal').find('#iqcNumber').val("");
  $('#extendModal').find('#productDesc').val('');
  $('#extendModal').find('#product').val('');
  $('#extendModal').find('#uom').val('');
  $('#extendModal').find('#currentWeight').attr('readonly', true).val('');
  $('#extendModal').find('#unitWeight').attr('readonly', true).val('');
  $('#extendModal').find('#actualCount').val("");
  $('#extendModal').find('#supplies').val("");
  $('#extendModal').find('#remark').val("");
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
  $.post('php/getCount.php', {userID: id}, function(data){
    var obj = JSON.parse(data);
    
    if(obj.status === 'success'){
      $('#extendModal').find('#id').val(obj.message.id);
      $('#extendModal').find('#serialNumber').val(obj.message.serial_no);
      $('#extendModal').find('#batchNumber').val(obj.message.batch_no);
      $('#extendModal').find('#articleNumber').val(obj.message.article_code);
      $('#extendModal').find('#iqcNumber').val(obj.message.iqc_no);
      $('#extendModal').find('#productDesc').val(obj.message.product_desc);
      $('#extendModal').find('#product').val(obj.message.product);
      $('#extendModal').find('#uom').val(obj.message.uom);
      $('#extendModal').find('#supplies').val(obj.message.supplier);
      $('#extendModal').find('#currentWeight').val(obj.message.gross);
      $('#extendModal').find('#unitWeight').val(obj.message.unit);
      $('#extendModal').find('#actualCount').val(obj.message.count);
      $('#extendModal').find('#remark').val(obj.message.remark);
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