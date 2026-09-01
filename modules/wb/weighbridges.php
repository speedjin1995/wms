<?php
require_once '../../php/db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
}
else{
  $user = $_SESSION['userID'];
  $company = $_SESSION['customer'];
  $module = $_SESSION['module'];
  $enableDailySales = $_SESSION['enableDailySales'];
  $dailySalesModules = $_SESSION['dailySalesModules'];
  $stmt = $db->prepare("SELECT * from users where id = ?");
	$stmt->bind_param('s', $user);
	$stmt->execute();
	$result = $stmt->get_result();
  $role = 'NORMAL';
  $allowAdd = 'N';
	$allowEdit = 'N';
  $allowDelete = 'N';
  $filterStates = [];
  if ($enableDailySales == 'Y' && in_array($module, $dailySalesModules)){
    // Query to get daily setup states
    $stateQuery = "SELECT * FROM daily_sales_setup WHERE module = 'weighing' AND company = ? AND deleted = 0";
    if ($state_stmt = $db->prepare($stateQuery)) {
        $state_stmt->bind_param('s', $company);
        $state_stmt->execute();
        $state_result = $state_stmt->get_result();
        while ($state_row = $state_result->fetch_assoc()) {
            $decoded = json_decode($state_row['state'], true);
            if (is_array($decoded)) {
                $filterStates = array_merge($filterStates, $decoded);
            }
        }
    }
  }

	if(($row = $result->fetch_assoc()) !== null){
    $role = $row['role_code'];
    $allowAdd = $row['allow_add'];
    $allowEdit = $row['allow_edit'];
    $allowDelete = $row['allow_delete'];
  }

  if ($role != 'SADMIN'){
    $stateFilter = '';
    if (!empty($filterStates)) {
      $stateJson = json_encode(array_values($filterStates));
      $stateFilter = " AND JSON_OVERLAPS(p.state, '$stateJson')";
    }
    $productQuery = "SELECT p.* FROM products p INNER JOIN categories c ON p.category = c.id WHERE p.deleted = '0' AND p.customer = '$company' AND c.module = '$module' AND c.deleted = '0'$stateFilter ORDER BY p.product_name ASC";
    $productCheck = $db->query($productQuery);
    if ($productCheck->num_rows == 0) {
      $productQuery = "SELECT * FROM products WHERE deleted = '0' AND customer = '$company' ORDER BY product_name ASC";
    }
    $products = $db->query($productQuery);
    $products2 = $db->query($productQuery);
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

<!-- Main content -->
<div class="content page-modern">
  <div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title"><i class="fas fa-truck-loading"></i> <?=$languageArray['weighbridge_code'][$language]?></h1>
    </div>

    <!-- Filter Card -->
    <div class="card filter-card">
      <div class="card-body">
        <div class="filter-row">
          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['from_date_code'][$language]?></label>
            <div class="input-group date" id="fromDatePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#fromDatePicker" id="fromDate"/>
              <div class="input-group-append" data-target="#fromDatePicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['to_date_code'][$language]?></label>
            <div class="input-group date" id="toDatePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#toDatePicker" id="toDate"/>
              <div class="input-group-append" data-target="#toDatePicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['transaction_status_code'][$language]?></label>
            <select class="form-control" id="transactionStatusFilter" name="transactionStatusFilter">
              <option>-</option>
              <option value="Dispatch"><?=$languageArray['dispatch_code'][$language]?></option>
              <option value="Receiving"><?=$languageArray['receiving_code'][$language]?></option>
            </select>
          </div>

          <div class="filter-group" id="customerDiv" style="display: none;">
            <label class="filter-label"><?=$languageArray['customer_code'][$language]?></label>
            <select class="form-control select2" id="customerNoFilter" name="customerNoFilter">
              <option value=""><?=$languageArray['please_select_code'][$language] ?? 'Please Select'?></option>
              <?php while($rowCustomer2=mysqli_fetch_assoc($customers)){ ?>
                <option value="<?=$rowCustomer2['customer_name'] ?>"><?=$rowCustomer2['customer_name'] ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group" id="supplierDiv">
            <label class="filter-label"><?=$languageArray['supplier_code'][$language]?></label>
            <select class="form-control select2" id="supplierNoFilter" name="supplierNoFilter">
              <option value=""><?=$languageArray['please_select_code'][$language] ?? 'Please Select'?></option>
              <?php while($rowCustomer2=mysqli_fetch_assoc($supplies)){ ?>
                <option value="<?=$rowCustomer2['supplier_name'] ?>"><?=$rowCustomer2['supplier_name'] ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['vehicle_no_code'][$language]?></label>
            <select class="form-control select2" id="vehicleNoFilter" name="vehicleNoFilter">
              <option value=""><?=$languageArray['please_select_code'][$language] ?? 'Please Select'?></option>
              <?php while($rowVehicle=mysqli_fetch_assoc($vehicles2)){ ?>
                <option value="<?=$rowVehicle['veh_number'] ?>"><?=$rowVehicle['veh_number'] ?></option>
              <?php } ?>
            </select>
          </div>
        </div>

        <div class="filter-row mt-3">
          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['status_code'][$language]?></label>
            <select class="form-control select2" id="statusFilter" name="statusFilter">
              <option value="Pending" selected><?=$languageArray['pending_code'][$language]?></option>
              <option value="Complete"><?=$languageArray['complete_code'][$language]?></option>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['product_code'][$language]?></label>
            <select class="form-control select2" id="productFilter" name="productFilter">
              <option value="">-</option>
              <?php while($rowStatus2=mysqli_fetch_assoc($products)){ ?>
                <option value="<?=$rowStatus2['product_name'] ?>"><?=$rowStatus2['product_name'] ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['transaction_id_code'][$language]?></label>
            <input type="text" id="transactionIDFilter" name="transactionIDFilter" class="form-control" placeholder="<?=$languageArray['transaction_id_code'][$language]?>">
          </div>

          <div class="filter-group filter-group-action" style="margin-left:auto;">
            <label class="filter-label">&nbsp;</label>
            <button type="button" class="btn btn-filter btn-filter-primary" id="filterSearch">
              <i class="fas fa-search"></i> <?=$languageArray['search_code'][$language]?>
            </button>
          </div>
        </div>
      </div>
    </div>
    <!-- Results Card -->
    <div class="card results-card show-dt-controls">
      <div class="card-header">
        <div class="results-header-left">
          <h3 class="results-title"><i class="fas fa-list"></i> <?=$languageArray['weighbridge_code'][$language]?></h3>
        </div>
        <div class="results-header-right">
          <?php if($allowAdd == 'Y'){ ?>
          <button type="button" class="btn btn-action btn-action-primary" onclick="newEntry()">
            <i class="fas fa-plus"></i> <?=$languageArray['add_new_code'][$language]?>
          </button>
          <?php } ?>
        </div>
      </div>

      <div class="card-body">
        <table id="weightTable" class="table data-table">
          <thead>
            <tr>
              <th style="width:40px;"><input type="checkbox" id="selectAllCheckbox"></th>
              <th><?=$languageArray['transaction_id_code'][$language]?></th>
              <th><?=$languageArray['transaction_date_code'][$language]?></th>
              <th><?=$languageArray['transaction_status_code'][$language]?></th>
              <th><?=$languageArray['po_no_code'][$language]?></th>
              <th><?=$languageArray['vehicle_no_code'][$language]?></th>
              <th><?=$languageArray['customer_supplier_code'][$language]?></th>
              <th><?=$languageArray['product_code'][$language]?></th>
              <th class="text-right"><?=$languageArray['incoming_weight_code'][$language]?></th>
              <th><?=$languageArray['incoming_date_code'][$language]?></th>
              <th class="text-right"><?=$languageArray['outgoing_weight_code'][$language]?></th>
              <th><?=$languageArray['outgoing_date_code'][$language]?></th>
              <th class="text-right"><?=$languageArray['total_nett_weight_code'][$language]?></th>
              <th style="width:120px;"><?=$languageArray['actions_code'][$language]?></th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>   

<div class="modal fade modal-modern" id="extendModal">
  <div class="modal-dialog modal-xl" style="max-width: 1200px;">
    <div class="modal-content">
      <form role="form" id="extendForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-truck-loading mr-2 text-muted"></i><?=$languageArray['add_new_entry_code'][$language]?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <input type="hidden" class="form-control" id="id" name="id">
          <input type="hidden" class="form-control" id="customerCode" name="customerCode">
          <input type="hidden" class="form-control" id="supplierCode" name="supplierCode">
          <input type="hidden" class="form-control" id="productCode" name="productCode">
          <input type="hidden" class="form-control" id="grossWeightBy" name="grossWeightBy">
          <input type="hidden" class="form-control" id="tareWeightBy" name="tareWeightBy">

          <!-- Transaction Info Section -->
          <div class="modal-section">
            <h6 class="section-title"><i class="fas fa-file-alt mr-2"></i><?=$languageArray['transaction_info_code'][$language] ?? 'Transaction Info'?></h6>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['transaction_id_code'][$language]?> <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="transactionId" name="transactionId" readonly>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['transaction_date_code'][$language]?> <span class="text-danger">*</span></label>
                  <div class="input-group date" id="transactionDateTimePicker" data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input" data-target="#transactionDateTimePicker" id="transactionDate" name="transactionDate"/>
                    <div class="input-group-append" data-target="#transactionDateTimePicker" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['transaction_status_code'][$language]?></label>
                  <select class="form-control" id="transactionStatus" name="transactionStatus">
                    <option value="Dispatch" selected><?=$languageArray['dispatch_code'][$language]?></option>
                    <option value="Receiving"><?=$languageArray['receiving_code'][$language]?></option>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-4" id="purchaseOrderDiv">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['po_no_code'][$language]?></label>
                  <input type="text" class="form-control" id="poNo" name="poNo">
                </div>
              </div>
              <div class="col-md-4" id="deliveryOrderDiv">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['do_no_code'][$language]?></label>
                  <input type="text" class="form-control" id="doNo" name="doNo">
                </div>
              </div>
              <div class="col-md-4" id="customerDiv">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['customer_code'][$language]?></label>
                  <select class="form-control select2" id="customer" name="customer">
                    <option value=""><?=$languageArray['please_select_code'][$language] ?? 'Please Select'?></option>
                    <?php while($rowCustomer3=mysqli_fetch_assoc($customers2)){ ?>
                      <option value="<?=$rowCustomer3['customer_name'] ?>" data-code="<?=$rowCustomer3['customer_code'] ?>"><?=$rowCustomer3['customer_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4" id="supplierDiv">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['supplier_code'][$language]?></label>
                  <select class="form-control select2" id="supplier" name="supplier">
                    <option value=""><?=$languageArray['please_select_code'][$language] ?? 'Please Select'?></option>
                    <?php while($rowSupplier3=mysqli_fetch_assoc($supplies2)){ ?>
                      <option value="<?=$rowSupplier3['supplier_name'] ?>" data-code="<?=$rowSupplier3['supplier_code'] ?>"><?=$rowSupplier3['supplier_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['product_code'][$language]?></label>
                  <select class="form-control select2" id="product" name="product">
                    <option value=""><?=$languageArray['please_select_code'][$language] ?? 'Please Select'?></option>
                    <?php while($rowProduct3=mysqli_fetch_assoc($products2)){ ?>
                      <option value="<?=$rowProduct3['product_name'] ?>" data-code="<?=$rowProduct3['product_code'] ?>"><?=$rowProduct3['product_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['vehicle_no_code'][$language]?></label>
                  <select class="form-control select2" id="vehicle" name="vehicle">
                    <option value=""><?=$languageArray['please_select_code'][$language] ?? 'Please Select'?></option>
                    <?php while($rowVehicle3=mysqli_fetch_assoc($vehicles)){ ?>
                      <option value="<?=$rowVehicle3['veh_number'] ?>"><?=$rowVehicle3['veh_number'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Weighing Details Section -->
          <div class="modal-section">
            <h6 class="section-title"><i class="fas fa-weight mr-2"></i><?=$languageArray['weighing_details_code'][$language]?></h6>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['incoming_weight_code'][$language]?></label>
                  <div class="input-group">
                    <input type="number" class="form-control" id="grossIncoming" name="grossIncoming" placeholder="0" required>
                    <div class="input-group-append">
                      <span class="input-group-text">KG</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['outgoing_weight_code'][$language]?></label>
                  <div class="input-group">
                    <input type="number" class="form-control" id="tareOutgoing" name="tareOutgoing" placeholder="0">
                    <div class="input-group-append">
                      <span class="input-group-text">KG</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['nett_weight_code'][$language]?></label>
                  <div class="input-group">
                    <input type="number" class="form-control" id="nettWeight" name="nettWeight" placeholder="0" readonly>
                    <div class="input-group-append">
                      <span class="input-group-text">KG</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['incoming_date_code'][$language]?></label>
                  <div class="input-group date" id="grossIncomingDatePicker" data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input" data-target="#grossIncomingDatePicker" id="grossIncomingDate" name="grossIncomingDate">
                    <div class="input-group-append" data-target="#grossIncomingDatePicker" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['outgoing_date_code'][$language]?></label>
                  <div class="input-group date" id="tareOutgoingDatePicker" data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input" data-target="#tareOutgoingDatePicker" id="tareOutgoingDate" name="tareOutgoingDate">
                    <div class="input-group-append" data-target="#tareOutgoingDatePicker" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-modern btn-modern-primary" id="saveButton"><i class="fas fa-save mr-1"></i><?=$languageArray['save_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>  

<div class="modal fade modal-modern" id="cancelModal">
  <div class="modal-dialog" style="max-width:500px;">
    <div class="modal-content">
      <form role="form" id="cancelForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-trash-alt mr-2 text-danger"></i><?=$languageArray['delete_reason_code'][$language]?></h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group-modern">
            <label class="form-label-modern"><?=$languageArray['delete_reason_code'][$language]?> <span class="text-danger">*</span></label>
            <textarea class="form-control" id="cancelReason" name="cancelReason" rows="3" required placeholder="<?=$languageArray['enter_reason_code'][$language] ?? 'Enter reason for deletion...'?>"></textarea>
          </div>
          <input type="hidden" id="id" name="id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-modern btn-modern-danger" id="submitCancel"><i class="fas fa-trash mr-1"></i><?=$languageArray['submit_code'][$language]?></button>
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
    'language': {
      'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
      'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
    },
    'ajax': {
      'url':'php/modules/wb/filterWeighbridge.php',
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
      { data: 'final_weight' },
      { 
        data: 'id',
        render: function ( data, type, row ) {
          var buttons = '<div class="d-flex" style="gap:4px;">';
          if(<?=$allowEdit == 'Y' ? 'true' : 'false'?>) {
            buttons += '<button type="button" onclick="edit('+data+')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button>';
          }
          buttons += '<button type="button" onclick="printSlip('+data+')" class="btn btn-sm btn-outline-secondary" title="Print"><i class="fas fa-print"></i></button>';
          if(<?=$allowDelete == 'Y' ? 'true' : 'false'?>) {
            buttons += '<button type="button" onclick="deactivate('+data+')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>';
          }
          buttons += '</div>';
          return buttons;
        }
      }
    ]
  });

  // Add event listener for opening and closing details on row click
  $('#weightTable tbody').on('click', 'tr', function (e) {
    var tr = $(this);
    var row = table.row(tr);

    // Exclude clicks on buttons, checkboxes, and form elements
    if ($(e.target).closest('td').hasClass('select-checkbox') || 
        $(e.target).is('button') ||
        $(e.target).closest('button').length ||
        $(e.target).is('input')) {
      return;
    }

    if (row.child.isShown()) {
      // This row is already open - close it
      row.child.hide();
      tr.removeClass('shown');
    } else {
      $.post('php/modules/wb/getWeighbridge.php', { userID: row.data().id }, function (data) {
        var obj = JSON.parse(data);
        if (obj.status === 'success') {
          row.child(format(obj.message)).show();
          tr.addClass('shown');
        }
      });
    }
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
      'searching': true,
      'order': [[ 1, 'asc' ]],
      'columnDefs': [ { orderable: false, targets: [0] }],
      'language': {
        'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
        'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
      },
      'ajax': {
        'url':'php/modules/wb/filterWeighbridge.php',
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
        { data: 'final_weight' },
        { 
          data: 'id',
          render: function ( data, type, row ) {
            var buttons = '<div class="d-flex" style="gap:4px;">';
            if(<?=$allowEdit == 'Y' ? 'true' : 'false'?>) {
              buttons += '<button type="button" onclick="edit('+data+')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button>';
            }
            buttons += '<button type="button" onclick="printSlip('+data+')" class="btn btn-sm btn-outline-secondary" title="Print"><i class="fas fa-print"></i></button>';
            if(<?=$allowDelete == 'Y' ? 'true' : 'false'?>) {
              buttons += '<button type="button" onclick="deactivate('+data+')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>';
            }
            buttons += '</div>';
            return buttons;
          }
        }
      ]
    });
  });

  $.validator.setDefaults({
    submitHandler: function () {
      if($('#extendModal').hasClass('show')){
        $('#spinnerLoading').show();
        var formData = new FormData($('#extendForm')[0]);
        $.ajax({
          url: 'php/modules/wb/weighbridges.php',
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
        $.post('php/modules/wb/deleteWeighbridge.php', $('#cancelForm').serialize(), function(data){
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
    $('#grossWeightBy').val('<?= $user ?>');
    $('#grossIncomingDatePicker').datetimepicker('date', moment());
    calculateWeight();
  });

  $('#tareOutgoing').on('keyup', function(){
    $('#tareWeightBy').val('<?= $user ?>');
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
  $('#extendModal').find('#grossWeightBy').val("");
  $('#extendModal').find('#grossIncoming').val("");
  $('#grossIncomingDatePicker').datetimepicker('clear');
  $('#extendModal').find('#tareWeightBy').val("");
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
  $.post('php/modules/wb/getWeighbridge.php', {userID: id}, function(data){
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
      $('#extendModal').find('#grossWeightBy').val(obj.message.gross_weight_by1);
      $('#extendModal').find('#grossIncoming').val(obj.message.gross_weight1);
      if (obj.message.gross_weight1_date) {
        $('#grossIncomingDatePicker').datetimepicker('date', moment(obj.message.gross_weight1_date, 'YYYY-MM-DD HH:mm:ss'));
      } else {
        $('#grossIncomingDatePicker').datetimepicker('clear');
      }
      $('#extendModal').find('#tareWeightBy').val(obj.message.tare_weight_by1);
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
  $.post('php/modules/wb/printWeighbridge.php', {userID: id, file: 'weight', isEmptyContainer: 'N'}, function(data){
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

function format(row) {
  var grossWeight = parseFloat(row.gross_weight1) || 0;
  var tareWeight = parseFloat(row.tare_weight1) || 0;
  var nettWeight = parseFloat(row.nett_weight1) || 0;
  
  var returnString = `
  <div class="expanded-row-content">
    <!-- Header -->
    <div class="expanded-header">
      <div>
        <div class="expanded-header-title">${row.transaction_id || '-'}</div>
        <div class="expanded-header-subtitle">${row.customer_name || row.supplier_name || '-'}</div>
      </div>
      <div class="expanded-actions">
        ${<?=$allowEdit == 'Y' ? 'true' : 'false'?> ? '<button type="button" onclick="edit('+row.id+')" class="btn btn-sm btn-outline-primary"><i class="fas fa-pen"></i></button>' : ''}
        <button type="button" onclick="printSlip(${row.id})" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i></button>
        ${<?=$allowDelete == 'Y' ? 'true' : 'false'?> ? '<button type="button" onclick="deactivate('+row.id+')" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>' : ''}
      </div>
    </div>

    <!-- KPI Summary -->
    <div class="kpi-row">
      <div class="kpi-card">
        <div class="kpi-label"><?=$languageArray['incoming_weight_code'][$language]?></div>
        <div class="kpi-value kpi-value-primary">${grossWeight.toFixed(2)} <span class="kpi-unit">KG</span></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label"><?=$languageArray['outgoing_weight_code'][$language]?></div>
        <div class="kpi-value">${tareWeight.toFixed(2)} <span class="kpi-unit">KG</span></div>
      </div>
      <div class="kpi-card kpi-card-success">
        <div class="kpi-label"><?=$languageArray['nett_weight_code'][$language] ?? 'Nett Weight'?></div>
        <div class="kpi-value">${nettWeight.toFixed(2)} <span class="kpi-unit">KG</span></div>
      </div>
    </div>

    <!-- Transaction Info -->
    <div class="info-section">
      <div class="info-section-title"><?=$languageArray['transaction_info_code'][$language] ?? 'Transaction Information'?></div>
      <div class="info-grid">
        <div><span class="info-item-label"><?=$languageArray['transaction_id_code'][$language]?></span><span class="info-item-value">${row.transaction_id || '-'}</span></div>
        <div><span class="info-item-label"><?=$languageArray['transaction_date_code'][$language]?></span><span class="info-item-value">${row.transaction_date || '-'}</span></div>
        <div><span class="info-item-label"><?=$languageArray['transaction_status_code'][$language]?></span><span class="info-item-value">${row.transaction_status || '-'}</span></div>
        <div><span class="info-item-label">${row.transaction_status === 'Dispatch' ? '<?=$languageArray['do_no_code'][$language]?>' : '<?=$languageArray['po_no_code'][$language]?>'}</span><span class="info-item-value">${row.transaction_status === 'Dispatch' ? (row.delivery_no || '-') : (row.purchase_order || '-')}</span></div>
        <div><span class="info-item-label"><?=$languageArray['vehicle_no_code'][$language]?></span><span class="info-item-value">${row.lorry_plate_no1 || '-'}</span></div>
        <div><span class="info-item-label">${row.transaction_status === 'Dispatch' ? '<?=$languageArray['customer_code'][$language]?>' : '<?=$languageArray['supplier_code'][$language]?>'}</span><span class="info-item-value">${row.customer_name || row.supplier_name || '-'}</span></div>
        <div><span class="info-item-label"><?=$languageArray['product_code'][$language]?></span><span class="info-item-value">${row.product_name || '-'}</span></div>
      </div>
    </div>

    <!-- Weighing Details -->
    <div class="details-section">
      <div class="details-header">
        <span class="details-title"><i class="fas fa-weight mr-1"></i><?=$languageArray['weighing_details_code'][$language]?></span>
      </div>
      <div class="table-responsive">
        <table class="table details-table mb-0">
          <thead>
            <tr>
              <th><?=$languageArray['type_code'][$language] ?? 'Type'?></th>
              <th class="text-right"><?=$languageArray['weight_code'][$language] ?? 'Weight'?> (KG)</th>
              <th><?=$languageArray['date_time_code'][$language] ?? 'Date/Time'?></th>
              <th><?=$languageArray['weighed_by_code'][$language] ?? 'Weighed By'?></th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><span class="badge badge-info"><?=$languageArray['incoming_code'][$language] ?? 'Incoming'?></span></td>
              <td class="text-right text-mono font-weight-bold">${grossWeight.toFixed(2)}</td>
              <td class="text-muted">${row.gross_weight1_date || '-'}</td>
              <td class="text-muted">${row.grossWeightBy || '-'}</td>
            </tr>
            <tr>
              <td><span class="badge badge-secondary"><?=$languageArray['outgoing_code'][$language] ?? 'Outgoing'?></span></td>
              <td class="text-right text-mono font-weight-bold">${tareWeight.toFixed(2)}</td>
              <td class="text-muted">${row.tare_weight1_date || '-'}</td>
              <td class="text-muted">${row.tareWeightBy || '-'}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td><strong><?=$languageArray['nett_weight_code'][$language] ?? 'Nett Weight'?></strong></td>
              <td class="text-right text-mono text-success font-weight-bold">${nettWeight.toFixed(2)}</td>
              <td></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
  `;
  
  return returnString;
}
</script>
