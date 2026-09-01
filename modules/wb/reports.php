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
  $stmt = $db->prepare("SELECT * from users where id = ?");
	$stmt->bind_param('s', $user);
	$stmt->execute();
	$result = $stmt->get_result();
  $role = 'NORMAL';
	
	if(($row = $result->fetch_assoc()) !== null){
    $role = $row['role_code'];
  }

  if ($role != 'SADMIN'){
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


<div class="content page-modern">
  <div class="container-fluid">

    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title"><i class="fas fa-chart-bar"></i> <?=$languageArray['reports_code'][$language]?></h1>
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
            <select class="form-control" id="transactionStatusFilter">
              <option selected>-</option>
              <option value="Dispatch"><?=$languageArray['dispatch_code'][$language]?></option>
              <option value="Receiving"><?=$languageArray['receiving_code'][$language]?></option>
              <!-- <option value="Local">Internal Transfer</option>
              <option value="Misc">Miscellaneous</option> -->
            </select>
          </div>

          <div class="filter-group" id="customerDiv" style="display:none;">
            <label class="filter-label"><?=$languageArray['customer_code'][$language]?></label>
            <select class="form-control select2" id="customerNoFilter">
              <option value="">Please Select</option>
              <?php while($rowCustomer2=mysqli_fetch_assoc($customers)){ ?>
                <option value="<?=$rowCustomer2['customer_name'] ?>"><?=$rowCustomer2['customer_name'] ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group" id="supplierDiv">
            <label class="filter-label"><?=$languageArray['supplier_code'][$language]?></label>
            <select class="form-control select2" id="supplierNoFilter">
              <option value="">Please Select</option>
              <?php while($rowCustomer2=mysqli_fetch_assoc($supplies)){ ?>
                <option value="<?=$rowCustomer2['supplier_name'] ?>"><?=$rowCustomer2['supplier_name'] ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['vehicle_no_code'][$language]?></label>
            <select class="form-control select2" id="vehicleNoFilter">
              <option value="">Please Select</option>
              <?php while($rowVehicle=mysqli_fetch_assoc($vehicles2)){ ?>
                <option value="<?=$rowVehicle['veh_number'] ?>"><?=$rowVehicle['veh_number'] ?></option>
              <?php } ?>
            </select>
          </div>
        </div>

        <div class="filter-row mt-3">
          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['status_code'][$language]?></label>
            <select class="form-control select2" id="statusFilter">
              <option value="Complete" selected><?=$languageArray['complete_code'][$language]?></option>
              <option value="Cancelled"><?=$languageArray['cancelled_code'][$language]?></option>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['product_code'][$language]?></label>
            <select class="form-control select2" id="productFilter">
              <option selected>-</option>
              <?php while($rowStatus2=mysqli_fetch_assoc($products)){ ?>
                <option value="<?=$rowStatus2['product_name'] ?>"><?=$rowStatus2['product_name'] ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['transaction_id_code'][$language]?></label>
            <input type="text" id="transactionIDFilter" class="form-control" placeholder="<?=$languageArray['transaction_id_code'][$language]?>">
          </div>

          <div class="filter-group">
            <label class="filter-label">&nbsp;</label>
            <button type="button" class="btn btn-filter btn-filter-primary w-100" id="filterSearch">
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
          <h3 class="results-title"><i class="fas fa-list"></i> <?=$languageArray['reports_code'][$language]?></h3>
        </div>
        <div class="results-header-right d-flex" style="gap:0.5rem;">
          <button type="button" class="btn btn-action btn-action-warning" id="exportPdf">
            <i class="fas fa-file-pdf"></i> <?=$languageArray['export_pdf_code'][$language]?>
          </button>
          <button type="button" class="btn btn-action btn-action-success" id="exportExcel">
            <i class="fas fa-file-excel"></i> <?=$languageArray['export_excel_code'][$language]?>
          </button>
        </div>
      </div>
      <div class="card-body">
        <table id="weightTable" class="table data-table">
          <thead>
            <tr>
              <th style="width:40px;"><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
              <th><?=$languageArray['transaction_id_code'][$language]?></th>
              <th><?=$languageArray['transaction_date_code'][$language]?></th>
              <th><?=$languageArray['transaction_status_code'][$language]?></th>
              <th><?=$languageArray['po_no_code'][$language]?></th>
              <th><?=$languageArray['vehicle_no_code'][$language]?></th>
              <th><?=$languageArray['customer_supplier_code'][$language]?></th>
              <th><?=$languageArray['product_code'][$language]?></th>
              <th><?=$languageArray['incoming_weight_code'][$language]?></th>
              <th><?=$languageArray['incoming_date_code'][$language]?></th>
              <th><?=$languageArray['outgoing_weight_code'][$language]?></th>
              <th><?=$languageArray['outgoing_date_code'][$language]?></th>
              <th><?=$languageArray['total_nett_weight_code'][$language]?></th>
              <!-- <th width="5%">Action</th> -->
            </tr>
          </thead>
        </table>
      </div>
    </div>

  </div>
</div>  

<script>
$(function () {
  const today = new Date();
  const yesterday = new Date(today);
  yesterday.setDate(yesterday.getDate() - 7);

  $('.select2').select2({
    allowClear: true,
    placeholder: "Please Select"
  });

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

  var table = initTable();

  $('#filterSearch').on('click', function() {
    $('#weightTable').DataTable().clear().destroy();
    table = initTable();
  });

  $('#exportExcel').on('click', function() {
    var params = buildParams();
    var selectedIds = getSelectedIds();
    window.open('php/modules/wb/export.php?' + params.substring(1) + (selectedIds.length > 0 ? '&isMulti=Y&ids=' + selectedIds : '&isMulti=N'));
  });

  $('#exportPdf').on('click', function() {
    var params = buildParams();
    var selectedIds = getSelectedIds();
    window.open('php/modules/wb/exportPdf.php?' + params.substring(1) + (selectedIds.length > 0 ? '&isMulti=Y&ids=' + selectedIds : '&isMulti=N'));
  });

  $('#transactionStatusFilter').on('change', function() {
    var status = $(this).val();
    $('#customerNoFilter').val('').trigger('change');
    $('#supplierNoFilter').val('').trigger('change');
    if (status == 'Sales' || status == 'Dispatch' || status == 'Misc') {
      $('#supplierDiv').hide();
      $('#customerDiv').show();
    } else {
      $('#customerDiv').hide();
      $('#supplierDiv').show();
    }
  });
});

function buildParams() {
  return '&fromDate=' + $('#fromDate').val() +
    '&toDate=' + $('#toDate').val() +
    '&transactionStatus=' + $('#transactionStatusFilter').val() +
    '&status=' + ($('#statusFilter').val() || '') +
    '&customer=' + ($('#customerNoFilter').val() || '') +
    '&supplier=' + ($('#supplierNoFilter').val() || '') +
    '&product=' + ($('#productFilter').val() || '') +
    '&vehicle=' + ($('#vehicleNoFilter').val() || '') +
    '&transactionId=' + ($('#transactionIDFilter').val() || '');
}

function getSelectedIds() {
  var ids = [];
  $("#weightTable tbody input[type='checkbox']:checked").each(function() {
    ids.push($(this).val());
  });
  return ids;
}

function initTable() {
  return $('#weightTable').DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: true,
    serverMethod: 'post',
    searching: true,
    order: [[ 1, 'asc' ]],
    columnDefs: [{ orderable: false, targets: [0] }],
    ajax: {
      url: 'php/modules/wb/filterWeighbridge.php',
      data: {
        fromDate: $('#fromDate').val(),
        toDate: $('#toDate').val(),
        status: $('#statusFilter').val() || '',
        product: $('#productFilter').val() || '',
        customer: $('#customerNoFilter').val() || '',
        supplier: $('#supplierNoFilter').val() || '',
        vehicle: $('#vehicleNoFilter').val() || '',
        transactionStatus: $('#transactionStatusFilter').val(),
        transactionId: $('#transactionIDFilter').val() || ''
      }
    },
    language: {
      emptyTable: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
      zeroRecords: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
    },
    columns: [
      {
        data: 'id',
        className: 'select-checkbox',
        orderable: false,
        render: function(data, type, row) {
          return '<input type="checkbox" class="select-checkbox" id="checkbox_' + data + '" value="' + data + '"/>';
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
      // { 
      //   data: 'id',
      //   render: function ( data, type, row ) {
      //     return '<button type="button" onclick="printSlip('+data+')" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></button>';
      //   }
      // }
    ]
  });
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
</script>