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
  $allowAdd = 'N';
	$allowEdit = 'N';
  $allowDelete = 'N';

	if(($row = $result->fetch_assoc()) !== null){
    $role = $row['role_code'];
    $allowAdd = $row['allow_add'];
    $allowEdit = $row['allow_edit'];
    $allowDelete = $row['allow_delete'];
  }

  if ($role != 'SADMIN'){
    $products = $db->query("SELECT * FROM products WHERE deleted = '0' AND customer = '$company' ORDER BY product_name ASC");
    $products2 = $db->query("SELECT * FROM products WHERE deleted = '0' AND customer = '$company' ORDER BY product_name ASC");
    $supplies = $db->query("SELECT * FROM supplies WHERE deleted = '0' AND customer = '$company' ORDER BY supplier_name ASC");
    $supplies2 = $db->query("SELECT * FROM supplies WHERE deleted = '0' AND customer = '$company' ORDER BY supplier_name ASC");
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' AND customer = '$company' ORDER BY customer_name ASC");
    $customers2 = $db->query("SELECT * FROM customers WHERE deleted = '0' AND customer = '$company' ORDER BY customer_name ASC");
    $vehicles = $db->query("SELECT * FROM vehicles WHERE deleted = '0' AND customer = '$company' ORDER BY veh_number ASC");
    $vehicles2 = $db->query("SELECT * FROM vehicles WHERE deleted = '0' AND customer = '$company' ORDER BY veh_number ASC");
    $users = $db->query("SELECT * FROM users WHERE deleted = '0' AND customer = '$company' ORDER BY name ASC");
  } else {
    $products = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $products2 = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $supplies = $db->query("SELECT * FROM supplies WHERE deleted = '0' ORDER BY supplier_name ASC");
    $supplies2 = $db->query("SELECT * FROM supplies WHERE deleted = '0' ORDER BY supplier_name ASC");
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' ORDER BY customer_name ASC");
    $customers2 = $db->query("SELECT * FROM customers WHERE deleted = '0' ORDER BY customer_name ASC");
    $vehicles = $db->query("SELECT * FROM vehicles WHERE deleted = '0' ORDER BY veh_number ASC");
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
        <h1 class="m-0 text-dark"><?=$languageArray['weighbridge_code'][$language]?></h1>
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
                    <option>-</option>
                    <option value="Dispatch"><?=$languageArray['dispatch_code'][$language]?></option>
                    <option value="Receiving"><?=$languageArray['receiving_code'][$language]?></option>
                    <!-- <option value="Local">Internal Transfer</option>
                    <option value="Misc">Miscellaneous</option> -->
                  </select>
                </div>
              </div>

              <div class="col-3" id="customerDiv" style="display: none;">
                <div class="form-group">
                  <label><?=$languageArray['customer_code'][$language]?></label>
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
                  <label><?=$languageArray['supplier_code'][$language]?></label>
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
                  <label><?=$languageArray['vehicle_no_code'][$language]?></label>
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
                  <label><?=$languageArray['status_code'][$language]?></label>
                  <select class="form-control select2" id="statusFilter" name="statusFilter" style="width: 100%;">
                    <option value="Pending" selected><?=$languageArray['pending_code'][$language]?></option>
                    <option value="Complete" ><?=$languageArray['complete_code'][$language]?></option>
                  </select>
                </div>
              </div>
              <div class="col-3">
                <div class="form-group">
                  <label><?=$languageArray['product_code'][$language]?></label>
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
                  <label><?=$languageArray['transaction_id_code'][$language]?></label>
                  <input type="text" id="transactionIDFilter" name="transactionIDFilter" class="form-control" placeholder="<?=$languageArray['transaction_id_code'][$language]?>">
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
              <div class="col-10"><?=$languageArray['weighbridge_code'][$language]?></div>
              <!-- <div class="col-3">
                <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="exportPdf">Export PDF</button>
              </div>
              <div class="col-3">
                <button type="button" class="btn btn-block bg-gradient-success btn-sm" id="exportExcel">Export Excel</button>
              </div> -->
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
                  <th><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
                  <th><?=$languageArray['transaction_id_code'][$language]?></th>
                  <th><?=$languageArray['transaction_date_code'][$language]?></th>
                  <th><?=$languageArray['transaction_status_code'][$language]?></th>
                  <th><?=$languageArray['po_no_code'][$language]?></th>
                  <th><?=$languageArray['vehicle_no_code'][$language]?></th>
                  <th><?=$languageArray['customer_supplier_code'][$language]?></th>
                  <th><?=$languageArray['incoming_weight_code'][$language]?></th>
                  <th><?=$languageArray['incoming_date_code'][$language]?></th>
                  <th><?=$languageArray['outgoing_weight_code'][$language]?></th>
                  <th><?=$languageArray['outgoing_date_code'][$language]?></th>
                  <th><?=$languageArray['total_nett_weight_code'][$language]?></th>
                  <th width="5%"><?=$languageArray['actions_code'][$language]?></th>
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
      <form role="form" id="extendForm">
        <div class="modal-header bg-gray-dark color-palette">
          <h4 class="modal-title"><?=$languageArray['add_new_entry_code'][$language]?></h4>
          <button type="button" class="close bg-gray-dark color-palette" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body bg-light">
          <input type="hidden" class="form-control" id="id" name="id">
          <input type="hidden" class="form-control" id="customerCode" name="customerCode">
          <input type="hidden" class="form-control" id="supplierCode" name="supplierCode">
          <input type="hidden" class="form-control" id="productCode" name="productCode">

          <div class="card card-outline card-primary mb-3 shadow-sm">
            <div class="card-header py-2">
              <h6 class="card-title mb-0"><i class="fas fa-file-alt mr-2"></i>Transaction Info</h6>
            </div>
            <div class="card-body pt-3">
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="text-muted small mb-1"><?=$languageArray['transaction_id_code'][$language]?> *</label>
                    <input type="text" class="form-control form-control" id="transactionId" name="transactionId" readonly>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="text-muted small mb-1"><?=$languageArray['transaction_date_code'][$language]?> *</label>
                    <div class="input-group input-group date" id="transactionDateTimePicker" data-target-input="nearest">
                      <input type="text" class="form-control datetimepicker-input" data-target="#transactionDateTimePicker" id="transactionDate" name="transactionDate"/>
                      <div class="input-group-append" data-target="#transactionDateTimePicker" data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="text-muted small mb-1"><?=$languageArray['transaction_status_code'][$language]?></label>
                    <select class="form-control form-control" id="transactionStatus" name="transactionStatus">
                      <option value="Dispatch" selected><?=$languageArray['dispatch_code'][$language]?></option>
                      <option value="Receiving"><?=$languageArray['receiving_code'][$language]?></option>
                      <!-- <option value="Local">Internal Transfer</option>
                      <option value="Misc">Miscellaneous</option> -->
                    </select>
                  </div>
                </div>
                <div class="col-md-4" id="purchaseOrderDiv">
                  <div class="form-group">
                    <label class="text-muted small mb-1"><?=$languageArray['po_no_code'][$language]?></label>
                    <input type="text" class="form-control form-control" id="poNo" name="poNo">
                  </div>
                </div>
                <div class="col-md-4" id="deliveryOrderDiv">
                  <div class="form-group">
                    <label class="text-muted small mb-1"><?=$languageArray['do_no_code'][$language]?></label>
                    <input type="text" class="form-control form-control" id="doNo" name="doNo">
                  </div>
                </div>
                <div class="col-md-4" id="customerDiv">
                  <div class="form-group">
                    <label class="text-muted small mb-1"><?=$languageArray['customer_code'][$language]?></label>
                    <select class="form-control form-control select2" id="customer" name="customer">
                      <option value="" selected disabled hidden>Please Select</option>
                      <?php while($rowCustomer3=mysqli_fetch_assoc($customers2)){ ?>
                        <option value="<?=$rowCustomer3['customer_name'] ?>" data-code="<?=$rowCustomer3['customer_code'] ?>"><?=$rowCustomer3['customer_name'] ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-4" id="supplierDiv">
                  <div class="form-group">
                    <label class="text-muted small mb-1"><?=$languageArray['supplier_code'][$language]?></label>
                    <select class="form-control form-control select2" id="supplier" name="supplier">
                      <option value="" selected disabled hidden>Please Select</option>
                      <?php while($rowSupplier3=mysqli_fetch_assoc($supplies2)){ ?>
                        <option value="<?=$rowSupplier3['supplier_name'] ?>" data-code="<?=$rowSupplier3['supplier_code'] ?>"><?=$rowSupplier3['supplier_name'] ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="text-muted small mb-1"><?=$languageArray['product_code'][$language]?></label>
                    <select class="form-control form-control select2" id="product" name="product">
                      <option value="" selected disabled hidden>Please Select</option>
                      <?php while($rowProduct3=mysqli_fetch_assoc($products2)){ ?>
                        <option value="<?=$rowProduct3['product_name'] ?>" data-code="<?=$rowProduct3['product_code'] ?>"><?=$rowProduct3['product_name'] ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="text-muted small mb-1"><?=$languageArray['vehicle_no_code'][$language]?></label>
                    <select class="form-control form-control select2" id="vehicle" name="vehicle">
                      <option value="" selected disabled hidden>Please Select</option>
                      <?php while($rowVehicle3=mysqli_fetch_assoc($vehicles)){ ?>
                        <option value="<?=$rowVehicle3['veh_number'] ?>"><?=$rowVehicle3['veh_number'] ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
              </div><!-- /.row -->
            </div>
          </div><!-- /.card Transaction Info -->

          <div class="card card-outline card-warning mb-0 shadow-sm">
            <div class="card-header py-2">
              <h6 class="card-title mb-0"><i class="fas fa-weight mr-2"></i><?=$languageArray['weighing_details_code'][$language]?></h6>
            </div>
            <div class="card-body pt-3">
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="text-muted small mb-1"><?=$languageArray['incoming_weight_code'][$language]?></label>
                    <div class="input-group input-group">
                      <input type="number" class="form-control" id="grossIncoming" name="grossIncoming" placeholder="0" required>
                      <div class="input-group-text">KG</div>
                    </div>
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="form-group">
                    <label class="text-muted small mb-1"><?=$languageArray['outgoing_weight_code'][$language]?></label>
                    <div class="input-group input-group">
                      <input type="number" class="form-control" id="tareOutgoing" name="tareOutgoing" placeholder="0">
                      <div class="input-group-text">KG</div>
                    </div>
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="form-group">
                    <label class="text-muted small mb-1"><?=$languageArray['nett_weight_code'][$language]?></label>
                    <div class="input-group input-group">
                      <input type="number" class="form-control" id="nettWeight" name="nettWeight" placeholder="0" readonly>
                      <div class="input-group-text">KG</div>
                    </div>
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="form-group">
                    <label class="text-muted small mb-1"><?=$languageArray['incoming_date_code'][$language]?></label>
                    <div class="input-group input-group date" id="grossIncomingDatePicker" data-target-input="nearest">
                      <input type="text" class="form-control datetimepicker-input" data-target="#grossIncomingDatePicker" id="grossIncomingDate" name="grossIncomingDate">
                      <div class="input-group-append" data-target="#grossIncomingDatePicker" data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="form-group">
                    <label class="text-muted small mb-1"><?=$languageArray['outgoing_date_code'][$language]?></label>
                    <div class="input-group input-group date" id="tareOutgoingDatePicker" data-target-input="nearest">
                      <input type="text" class="form-control datetimepicker-input" data-target="#tareOutgoingDatePicker" id="tareOutgoingDate" name="tareOutgoingDate">
                      <div class="input-group-append" data-target="#tareOutgoingDatePicker" data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div><!-- /.card Weighing Details -->
        </div><!-- /.modal-body -->

        <div class="modal-footer justify-content-between bg-gray-dark color-palette">
          <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-primary" id="saveButton"><?=$languageArray['save_code'][$language]?></button>
        </div>
      </form>
    </div> <!-- /.modal-content -->
  </div> <!-- /.modal-dialog -->
</div> <!-- /.modal -->  

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

  $('#transactionDateTimePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY HH:mm',
    defaultDate: today
  });

  $('#grossIncomingDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY HH:mm',
  });

  $('#tareOutgoingDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY HH:mm',
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
      { data: 'gross_weight1' },
      { data: 'gross_weight1_date' },
      { data: 'tare_weight1' },
      { data: 'tare_weight1_date' },
      { data: 'final_weight' },
      { 
        data: 'id',
        render: function ( data, type, row ) {
          var buttons = '<div class="d-flex flex-nowrap" style="gap:4px;">';
          if(<?=$allowEdit == 'Y' ? 'true' : 'false'?>) {
            buttons += '<button type="button" id="edit'+data+'" onclick="edit('+data+')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button>';
          }
          buttons += '<button type="button" id="print'+data+'" onclick="printSlip('+data+')" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></button>';
          if(<?=$allowDelete == 'Y' ? 'true' : 'false'?>) {
            buttons += '<button type="button" id="deactivate'+data+'" onclick="deactivate('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>';
          }
          buttons += '</div>';
          return buttons;
        }
      }
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
        { data: 'gross_weight1' },
        { data: 'gross_weight1_date' },
        { data: 'tare_weight1' },
        { data: 'tare_weight1_date' },
        { data: 'final_weight' },
        { 
          data: 'id',
          render: function ( data, type, row ) {
            var buttons = '<div class="d-flex flex-nowrap" style="gap:4px;">';
            if(<?=$allowEdit == 'Y' ? 'true' : 'false'?>) {
              buttons += '<button type="button" id="edit'+data+'" onclick="edit('+data+')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button>';
            }
            buttons += '<button type="button" id="print'+data+'" onclick="printSlip('+data+')" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></button>';
            if(<?=$allowDelete == 'Y' ? 'true' : 'false'?>) {
              buttons += '<button type="button" id="deactivate'+data+'" onclick="deactivate('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>';
            }
            buttons += '</div>';
            return buttons;
          }
        }
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

  $.validator.setDefaults({
    submitHandler: function () {
      if($('#extendModal').hasClass('show')){
        $('#spinnerLoading').show();
        var formData = new FormData($('#extendForm')[0]);
        $.ajax({
          url: 'php/weighbridges.php',
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
        $.post('php/deleteWeighbridge.php', $('#cancelForm').serialize(), function(data){
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

  $('#exportExcel').on('click', function(){
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var statusI = $('#statusFilter').val();
    var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
    var customerNoI = $('#customerNoFilter').val() ? $('#customerNoFilter').val() : '';
    var supplierNoI = $('#supplierNoFilter').val() ? $('#supplierNoFilter').val() : '';
    var vehicleNoI = $('#vehicleNoFilter').val() ? $('#vehicleNoFilter').val() : '';
    var checkedByI = $('#checkedByFilter').val() ? $('#checkedByFilter').val() : '';
    var weightedByI = $('#weightByFilter').val() ? $('#weightByFilter').val() : '';
    var selectedIds = []; // An array to store the selected 'id' values

    $("#weightTable tbody input[type='checkbox']").each(function () {
      if (this.checked) {
          selectedIds.push($(this).val());
      }
    });

    if (selectedIds.length > 0){
      window.open("php/export.php?fromDate="+fromDateI+"&toDate="+toDateI+"&status="+statusI+
      "&customer="+customerNoI+"&supplier="+supplierNoI+"&product="+productI+"&vehicle="+vehicleNoI+
      "&checkedBy="+checkedByI+"&weightedBy="+weightedByI+"&isMulti=Y&ids="+selectedIds);
    }else{
      window.open("php/export.php?fromDate="+fromDateI+"&toDate="+toDateI+"&status="+statusI+
      "&customer="+customerNoI+"&supplier="+supplierNoI+"&product="+productI+"&vehicle="+vehicleNoI+
      "&checkedBy="+checkedByI+"&weightedBy="+weightedByI+"&isMulti=N");
    }
  });

  $('#exportPdf').on('click', function(){
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var statusI = $('#statusFilter').val();
    var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
    var customerNoI = $('#customerNoFilter').val() ? $('#customerNoFilter').val() : '';
    var supplierNoI = $('#supplierNoFilter').val() ? $('#supplierNoFilter').val() : '';
    var vehicleNoI = $('#vehicleNoFilter').val() ? $('#vehicleNoFilter').val() : '';
    var checkedByI = $('#checkedByFilter').val() ? $('#checkedByFilter').val() : '';
    var weightedByI = $('#weightByFilter').val() ? $('#weightByFilter').val() : '';

    var selectedIds = []; // An array to store the selected 'id' values

    $("#weightTable tbody input[type='checkbox']").each(function () {
      if (this.checked) {
          selectedIds.push($(this).val());
      }
    });

    if (selectedIds.length > 0){
      window.open("php/exportPdf.php?fromDate="+fromDateI+"&toDate="+toDateI+"&status="+statusI+
      "&customer="+customerNoI+"&supplier="+supplierNoI+"&product="+productI+"&vehicle="+vehicleNoI+
      "&checkedBy="+checkedByI+"&weightedBy="+weightedByI+"&isMulti=Y&ids="+selectedIds);
    }else{
      window.open("php/exportPdf.php?fromDate="+fromDateI+"&toDate="+toDateI+"&status="+statusI+
      "&customer="+customerNoI+"&supplier="+supplierNoI+"&product="+productI+"&vehicle="+vehicleNoI+
      "&checkedBy="+checkedByI+"&weightedBy="+weightedByI+"&isMulti=N");
    }
  });

  $('#transactionStatusFilter').on('change', function(){
    var status = $(this).val();
    $('#customerNoFilter').val('').trigger('change');
    $('#supplierNoFilter').val('').trigger('change');
    if (status == 'Dispatch' || status == 'Sales' || status == 'Misc') {
      $('#supplierDiv').hide();
      $('#customerDiv').show();
    } else {
      $('#customerDiv').hide();
      $('#supplierDiv').show();
    }
  });

  $('#transactionStatus').on('change', function(){
    var status = $(this).val();
    $('#customer').val('').trigger('change');
    if (status == 'Dispatch' || status == 'Sales' || status == 'Misc') {
      $('#extendModal').find('#supplierDiv').hide();
      $('#extendModal').find('#purchaseOrderDiv').hide();
      $('#extendModal').find('#customerDiv').show();
      $('#extendModal').find('#deliveryOrderDiv').show();
    } else {
      $('#extendModal').find('#customerDiv').hide();
      $('#extendModal').find('#deliveryOrderDiv').hide();
      $('#extendModal').find('#supplierDiv').show();
      $('#extendModal').find('#purchaseOrderDiv').show();
    }
  });

  $('#customer').on('change', function(){
    $('#customerCode').val($(this).find(':selected').data('code'));
  });

  $('#supplier').on('change', function(){
    $('#supplierCode').val($(this).find(':selected').data('code'));
  });

  $('#product').on('change', function(){
    $('#productCode').val($(this).find(':selected').data('code'));
  });

  $('#grossIncoming').on('keyup', function(){
    $('#grossIncomingDatePicker').datetimepicker('date', moment());
    calculateWeight();
  });

  $('#tareOutgoing').on('keyup', function(){
    $('#tareOutgoingDatePicker').datetimepicker('date', moment());
    calculateWeight();
  });
});

function calculateWeight(){
  var gross = $('#grossIncoming').val() || 0;
  var tare = $('#tareOutgoing').val() || 0;
  var result = Math.abs(parseFloat(gross) - parseFloat(tare));
  $('#nettWeight').val(result);
}

function newEntry(){
  $('#extendModal').find('#id').val("");
  $('#extendModal').find('#transactionId').val("");
  $('#extendModal').find('#transactionStatus').val("Dispatch").trigger('change');
  $('#transactionDateTimePicker').datetimepicker('date', moment());
  $('#extendModal').find('#poNo').val("");
  $('#extendModal').find('#doNo').val("");
  $('#extendModal').find('#securityBillNo').val("");
  $('#extendModal').find('#customer').val("").trigger('change');
  $('#extendModal').find('#supplier').val("").trigger('change');
  $('#extendModal').find('#product').val("").trigger('change');
  $('#extendModal').find('#vehicle').val("").trigger('change');
  $('#extendModal').find('#grossIncoming').val("");
  $('#grossIncomingDatePicker').datetimepicker('clear');
  $('#extendModal').find('#tareOutgoing').val("");
  $('#tareOutgoingDatePicker').datetimepicker('clear');
  $('#extendModal').find('#nettWeight').val("");
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
  $.post('php/getWeighbridge.php', {userID: id}, function(data){
    var obj = JSON.parse(data);
    
    if(obj.status === 'success'){
      $('#extendModal').find('#id').val(obj.message.id);
      $('#extendModal').find('#transactionId').val(obj.message.transaction_id);
      $('#extendModal').find('#transactionStatus').val(obj.message.transaction_status).trigger('change');
      if (obj.message.transaction_date) {
        $('#transactionDateTimePicker').datetimepicker('date', moment(obj.message.transaction_date, 'YYYY-MM-DD HH:mm:ss'));
      } else {
        $('#transactionDateTimePicker').datetimepicker('clear');
      }
      $('#extendModal').find('#poNo').val(obj.message.purchase_order);
      $('#extendModal').find('#doNo').val(obj.message.delivery_no);
      $('#extendModal').find('#customer').val(obj.message.customer_name).trigger('change');
      $('#extendModal').find('#supplier').val(obj.message.supplier_name).trigger('change');
      $('#extendModal').find('#product').val(obj.message.product_name).trigger('change');
      $('#extendModal').find('#vehicle').val(obj.message.lorry_plate_no1).trigger('change');
      $('#extendModal').find('#grossIncoming').val(obj.message.gross_weight1);
      if (obj.message.gross_weight1_date) {
        $('#grossIncomingDatePicker').datetimepicker('date', moment(obj.message.gross_weight1_date, 'YYYY-MM-DD HH:mm:ss'));
      } else {
        $('#grossIncomingDatePicker').datetimepicker('clear');
      }
      $('#extendModal').find('#tareOutgoing').val(obj.message.tare_weight1);
      if (obj.message.tare_weight1_date) {
        $('#tareOutgoingDatePicker').datetimepicker('date', moment(obj.message.tare_weight1_date, 'YYYY-MM-DD HH:mm:ss'));
      } else {
        $('#tareOutgoingDatePicker').datetimepicker('clear');
      }
      $('#extendModal').find('#nettWeight').val(obj.message.nett_weight1);

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
