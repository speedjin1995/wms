<?php
require_once 'php/db_connect.php';
require_once 'php/lookup.php';

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
}
else{
  $user = $_SESSION['userID'];
  $company = $_SESSION['customer'];
  $module = $_SESSION['module'];
  $stmt = $db->prepare("SELECT * from users where id = ?");
	$stmt->bind_param('s', $user);
	$stmt->execute();
	$result = $stmt->get_result();
  $role = 'NORMAL';
	$allowAdd = 'N';
	$allowEdit = 'N';
  $allowDelete = 'N';
  $allowPhoto = 'N';

	if(($row = $result->fetch_assoc()) !== null){
    $role = $row['role_code'];
    $allowAdd = $row['allow_add'];
    $allowEdit = $row['allow_edit'];
    $allowDelete = $row['allow_delete'];
  }

  if ($role != 'SADMIN'){
    $batches = $db->query("SELECT * FROM packaging_batches WHERE deleted = '0' AND status != 'completed' AND company = '$company' ORDER BY packaging_date DESC");
    $customers = $db->query("SELECT * FROM customers WHERE deleted='0' AND customer = '$company' ORDER BY customer_name ASC");
    $shipmentTypes= $db->query("SELECT * FROM shipment_types WHERE deleted='0' AND customer = '$company' ORDER BY shipment_type ASC");
    $shipmentTypes2= $db->query("SELECT * FROM shipment_types WHERE deleted='0' AND customer = '$company' ORDER BY shipment_type ASC");

    // Company Detail 
    $companyDetail = searchCompanyById($company, $db);
    $allowPhoto = $companyDetail['include_photo'];
  } else {
    $batches = $db->query("SELECT * FROM packaging_batches WHERE deleted = '0' AND status != 'completed' ORDER BY packaging_date DESC");
    $customers = $db->query("SELECT * FROM customers WHERE deleted='0' ORDER BY customer_name ASC");
    $shipmentTypes= $db->query("SELECT * FROM shipment_types WHERE deleted='0' ORDER BY shipment_type ASC");
    $shipmentTypes2= $db->query("SELECT * FROM shipment_types WHERE deleted='0' ORDER BY shipment_type ASC");

    $allowPhoto = 'Y';
  }

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>
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
        <h1 class="m-0 text-dark"><?=$languageArray['loading_orders_code'][$language]?></h1>
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
                  <label><?=$languageArray['status_code'][$language]?></label>
                  <select class="form-control select2" id="statusFilter">
                    <option value=""><?=$languageArray['all_code'][$language]?></option>
                    <option value="pending"><?=$languageArray['pending_code'][$language]?></option>
                    <option value="completed"><?=$languageArray['complete_code'][$language]?></option>
                  </select>
                </div>
              </div>
              <div class="col-3">
                <div class="form-group">
                  <label><?=$languageArray['shipment_types_code'][$language]?></label>
                  <select class="form-control select2" id="shipmentTypeFilter" name="shipmentTypeFilter">
                    <option value=""><?=$languageArray['all_code'][$language]?></option>
                    <?php while($rowShipmentType=mysqli_fetch_assoc($shipmentTypes)){ ?>
                      <option value="<?=$rowShipmentType['id'] ?>"><?=$rowShipmentType['shipment_type'] ?></option>
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
              <div class="col-10"><?=$languageArray['loading_orders_code'][$language]?></div>
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
                  <th><?=$languageArray['loading_no_code'][$language]?></th>
                  <th><?=$languageArray['loading_date_code'][$language]?></th>
                  <th><?=$languageArray['status_code'][$language]?></th>
                  <th><?=$languageArray['shipment_types_code'][$language]?></th>
                  <th width="10%"><?=$languageArray['actions_code'][$language]?></th>
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
      <form role="form" id="extendForm" novalidate>
        <div class="modal-header bg-gray-dark color-palette">
          <h4 class="modal-title"><?=$languageArray['add_new_entry_code'][$language]?></h4>
          <button type="button" class="close bg-gray-dark color-palette" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
          <input type="hidden" class="form-control" id="id" name="id">

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label><?=$languageArray['loading_no_code'][$language]?> *</label>
                <input type="text" class="form-control" id="loadingNo" name="loadingNo" readonly>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label><?=$languageArray['loading_date_code'][$language]?> *</label>
                <div class="input-group date" id="loadingDatePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#loadingDatePicker" id="loadingDate" name="loadingDate" required/>
                  <div class="input-group-append" data-target="#loadingDatePicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                <label><?=$languageArray['shipment_types_code'][$language]?> *</label>
                <select class="form-control select2" id="shipmentType" name="shipmentType" required>
                  <option value="">Select</option>
                  <?php while($r = mysqli_fetch_assoc($shipmentTypes2)) { ?>
                    <option value="<?=$r['id']?>"><?=$r['shipment_type']?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="form-group">
                <label><?=$languageArray['remark_code'][$language]?></label>
                <textarea colspan="3" class="form-control" id="remarks" name="remarks" placeholder="<?=$languageArray['enter_remark_code'][$language]?>"></textarea>
              </div>
            </div>
          </div>
          
          <hr>
          <div class="card card-outline card-primary">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <?=$languageArray['weight_details_code'][$language]?>
                </h5>
            </div>

            <div class="card-body">
              <div class="row mb-3">
                <div class="col-md-6">
                  <div class="form-group">
                    <label><?=$languageArray['batch_no_code'][$language]?> *</label>
                    <select
                        class="form-control select2" id="batchNo" name="batchNo[]" multiple="multiple" data-placeholder="Select Batch(es)" required style="width:100%;">
                        <?php while($batchRow = mysqli_fetch_assoc($batches)) { ?>
                            <option value="<?=$batchRow['id']?>">
                                <?=$batchRow['batch_no']?>
                            </option>
                        <?php } ?>
                    </select>

                    <small class="text-muted">
                      <?=$languageArray['select_batches_hint_code'][$language]?>
                    </small>
                  </div>
                </div>

                <div class="col-md-3">
                  <div class="form-group">
                    <label><?=$languageArray['total_selected_batches_code'][$language]?></label>
                    <input type="text" class="form-control" id="selectedBatchCount" value="0" readonly>
                  </div>
                </div>

                <div class="col-md-3">
                  <div class="form-group">
                      <label><?=$languageArray['total_records_code'][$language]?></label>
                      <input type="text" class="form-control" id="totalWeightRecords" value="0" readonly>
                  </div>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover mb-0">
                  <thead>
                    <tr>
                      <th><?=$languageArray['batch_no_code'][$language]?></th>
                      <th><?=$languageArray['product_code'][$language]?></th>
                      <th><?=$languageArray['grade_code'][$language]?></th>
                      <th><?=$languageArray['packaging_size_code'][$language]?></th>
                      <th><?=$languageArray['time_code'][$language]?></th>
                      <th><?=$languageArray['customer_code'][$language]?></th>
                      <th><?=$languageArray['remark_code'][$language]?></th>
                      <!-- <?php if($allowPhoto == 'Y') { ?>
                      <th><?=$languageArray['photo_code'][$language]?></th>
                      <?php } ?> -->
                      <th width="8%"><?=$languageArray['actions_code'][$language]?></th>
                    </tr>
                  </thead>

                  <tbody id="weightDetailsTable">
                      <!-- Weight details populated here -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

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
// Values
var weightCount = 0;
var allowPhoto = '<?=$allowPhoto?>';
var customerOptions = '<?php $customerList = []; mysqli_data_seek($customers, 0); while($c = mysqli_fetch_assoc($customers)) { $customerList[] = "<option value=\\\"" . $c["id"] . "\\\">" . htmlspecialchars($c["customer_name"], ENT_QUOTES) . "</option>"; } echo implode("", $customerList); ?>';

$(function () {
  var userRole = '<?=$role ?>';
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

  $('#loadingDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY HH:mm'
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
  var statusI = $('#statusFilter').val() ? $('#statusFilter').val() : '';
  var shipmentTypeI = $('#shipmentTypeFilter').val() ? $('#shipmentTypeFilter').val() : '';

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
      'url':'php/modules/loading/filterLoadingOrders.php',
      'data': {
        fromDate: fromDateI,
        toDate: toDateI,
        status: statusI,
        shipmentType: shipmentTypeI,
      } 
    },
    'columns': [
      { data: 'loading_no' },
      { data: 'loading_date' },
      { data: 'status', 
        render: function(d) {
          var cls = { pending: 'warning', completed: 'success' };
          return '<span class="badge badge-' + (cls[d] || 'secondary') + '">' + d + '</span>';
        }
      },
      { data: 'shipmentType' },
      { 
        data: 'id',
        class: 'action-button',
        render: function ( data, type, row ) {
          var buttons = '<div class="d-flex flex-nowrap" style="gap:4px;">';
          if(<?=$allowEdit == 'Y' ? 'true' : 'false'?>) {
            buttons += '<button type="button" id="edit'+data+'" onclick="edit('+data+')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button>';
          }
          // buttons += '<button type="button" id="print'+data+'" onclick="print('+data+')" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></button>';
          if(<?=$allowDelete == 'Y' ? 'true' : 'false'?>) {
            buttons += '<button type="button" id="deactivate'+data+'" onclick="deactivate('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>';
          }
          buttons += '</div>';
          return buttons;
        }
      }
    ]
  });

  // Add event listener for opening and closing details on row click
  $('#weightTable tbody').on('click', 'tr', function (e) {
      var tr = $(this); // The row that was clicked
      var row = table.row(tr);

      // Exclude clicks on buttons, checkboxes, and form elements
      if ($(e.target).closest('td').hasClass('select-checkbox') || 
          $(e.target).closest('td').hasClass('action-button') ||
          $(e.target).is('select') || 
          $(e.target).is('input') ||
          $(e.target).is('button')) {
        return;
      }

      if (row.child.isShown()) {
          // This row is already open - close it
          row.child.hide();
          tr.removeClass('shown');
      } else {
          $.post('php/modules/loading/getLoadingOrder.php', { userID: row.data().id}, function (data) {
            var obj = JSON.parse(data);
            if (obj.status === 'success') {
              row.child(format(obj.message)).show();
              tr.addClass("shown");
            }
          });
      }
  });

  $('#filterSearch').on('click', function(){
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var statusI = $('#statusFilter').val() ? $('#statusFilter').val() : '';
    var shipmentTypeI = $('#shipmentTypeFilter').val() ? $('#shipmentTypeFilter').val() : '';

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
      'url':'php/modules/loading/filterLoadingOrders.php',
        'data': {
          fromDate: fromDateI,
          toDate: toDateI,
          status: statusI,
          shipmentType: shipmentTypeI,
        } 
      },
      'columns': [
        { data: 'loading_no' },
        { data: 'loading_date' },
        { data: 'status', 
          render: function(d) {
            var cls = { pending: 'warning', completed: 'success' };
            return '<span class="badge badge-' + (cls[d] || 'secondary') + '">' + d + '</span>';
          }
        },
        { data: 'shipmentType' },
        { 
          data: 'id',
          class: 'action-button',
          render: function ( data, type, row ) {
            var buttons = '<div class="d-flex flex-nowrap" style="gap:4px;">';
            if(<?=$allowEdit == 'Y' ? 'true' : 'false'?>) {
              buttons += '<button type="button" id="edit'+data+'" onclick="edit('+data+')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button>';
            }
            // buttons += '<button type="button" id="print'+data+'" onclick="print('+data+')" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></button>';
            if(<?=$allowDelete == 'Y' ? 'true' : 'false'?>) {
              buttons += '<button type="button" id="deactivate'+data+'" onclick="deactivate('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>';
            }
            buttons += '</div>';
            return buttons;
          }
        }
      ],
    });
  });

  $.validator.setDefaults({
    submitHandler: function () {
      if($('#extendModal').hasClass('show')){
        if ($('#weightDetailsTable tr').length === 0) {
          toastr["error"]("Please select at least one batch with items.", "Validation Error:");
          return;
        }
        var valid = true;
        var errorMsg = '';
        $('#weightDetailsTable tr').each(function(i) {
          var rowNum = i + 1;
          if (!$(this).find('select[name*="[customer_id]"]').val()) { errorMsg = 'Row ' + rowNum + ': Customer is required.'; valid = false; return false; }
          if (!$(this).find('input[name*="[loading_time]"]').val()) { errorMsg = 'Row ' + rowNum + ': Time is required.'; valid = false; return false; }
        });
        if (!valid) { toastr["error"](errorMsg, "Validation Error:"); return; }
        $('#spinnerLoading').show();
        var formData = new FormData($('#extendForm')[0]);
        $.ajax({
          url: 'php/modules/loading/loadingOrder.php',
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
              toastr["error"]("Something went wrong when saving", "Failed:");
            }
            $('#spinnerLoading').hide();
          },
          error: function(){
            toastr["error"]("Something went wrong when saving", "Failed:");
            $('#spinnerLoading').hide();
          }
        });
      } else if($('#cancelModal').hasClass('show')){
        $('#spinnerLoading').show();
        $.post('php/modules/loading/deleteLoadingOrder.php', $('#cancelForm').serialize(), function(data){
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
            toastr["error"]("Something went wrong when deleting", "Failed:");
          }
          $('#spinnerLoading').hide();
        });
      }
    }
  });

  // Show tick when file is selected
  $('#extendForm').on('change', 'input[type="file"]', function() {
    var statusSpan = $(this).siblings('span[id$="Status"], span[id*="photoStatus"], span[id*="PhotoStatus"]');
    if (this.files && this.files[0]) {
      statusSpan.html('<i class="fas fa-check-circle text-success"></i>');
    } else {
      statusSpan.html('');
    }
  });

  $('#batchNo').on('change', function() {
    if ($(this).data('suppress-change')) return;
    var selectedIds = $(this).val() || [];
    var tbody = $('#weightDetailsTable');
    tbody.empty();
    $('#selectedBatchCount').val(selectedIds.length);
    $('#totalWeightRecords').val(0);
    if (!selectedIds.length) return;

    var totalRecords = 0;
    var pending = selectedIds.length;

    selectedIds.forEach(function(batchId) {
      $.post('php/modules/loading/getPackagingBatchItems.php', { batch_id: batchId }, function(data) {
        var obj = JSON.parse(data);
        if (obj.status === 'success') {
          obj.items.forEach(function(item) {
            var idx = tbody.children().length;
            tbody.append(buildItemRow({
              packaging_batch_item_id: item.id,
              packaging_batch_id:      item.packaging_batch_id,
              product_id:              item.product_id,
              product_name:            item.product_name,
              grade:                   item.grade,
              packaging_size:          item.packaging_size,
              packaging_size_name:     item.packaging_size_name,
              units_per_box:           item.units_per_box,
              weight:                  item.weight,
              batch_no:                item.batch_no,
              loading_time:            '',
              remarks:                 ''
            }, idx));
            initRowControls(idx, null);
            totalRecords++;
          });
        }
        pending--;
        if (pending === 0) $('#totalWeightRecords').val(totalRecords);
      });
    });
  });
});

function format (row) {
  var statusCls = { pending: 'warning', partial: 'info', completed: 'success' };
  var returnString = `
  <div class="row">
    <div class="col-6">
      <p><strong><?=$languageArray['loading_no_code'][$language]?>:</strong> ${row.loading_no}</p>
      <p><strong><?=$languageArray['loading_date_code'][$language]?>:</strong> ${row.loading_date}</p>
      <p><strong><?=$languageArray['status_code'][$language]?>:</strong> <span class="badge badge-${statusCls[row.status] || 'secondary'}">${row.status}</span></p>
    </div>
    <div class="col-6">
      <p><strong><?=$languageArray['shipment_types_code'][$language]?>:</strong> ${row.shipmentType || ''}</p>
      <p><strong><?=$languageArray['remark_code'][$language]?>:</strong> ${row.remarks || ''}</p>
    </div>
  </div>
  <hr>
  <div class="row">
    <table class="table table-bordered table-striped align-middle" style="width:100%">
      <thead>
        <tr>
          <th><?=$languageArray['batch_no_code'][$language]?></th>
          <th><?=$languageArray['product_code'][$language]?></th>
          <th><?=$languageArray['grade_code'][$language]?></th>
          <th><?=$languageArray['packaging_size_code'][$language]?></th>
          <th><?=$languageArray['unit_per_box_code'][$language]?></th>
          <th><?=$languageArray['customer_code'][$language]?></th>
          <th><?=$languageArray['time_code'][$language]?></th>
          <th><?=$languageArray['remark_code'][$language]?></th>
        </tr>
      </thead>
      <tbody>`;

  if (row.items && row.items.length > 0) {
    for (var i = 0; i < row.items.length; i++) {
      var d = row.items[i];
      returnString += `
        <tr>
          <td>${d.batch_no || ''}</td>
          <td>${d.product_name}</td>
          <td>${d.grade_name}</td>
          <td>${d.packaging_size_name}</td>
          <td>${d.units_per_box}</td>
          <td>${d.customer_name || ''}</td>
          <td>${d.loading_time ? d.loading_time.substring(11,16) : ''}</td>
          <td>${d.remarks || ''}</td>
        </tr>`;
    }
  } else {
    returnString += '<tr><td colspan="8" class="text-center">No items</td></tr>';
  }

  returnString += `
      </tbody>
    </table>
  </div>`;

  return returnString;
}

function buildItemRow(item, idx) {
  return '<tr>' +
    '<input type="hidden" name="items[' + idx + '][packaging_batch_item_id]" value="' + item.packaging_batch_item_id + '">' +
    '<input type="hidden" name="items[' + idx + '][packaging_batch_id]" value="' + item.packaging_batch_id + '">' +
    '<input type="hidden" name="items[' + idx + '][product_id]" value="' + item.product_id + '">' +
    '<input type="hidden" name="items[' + idx + '][grade]" value="' + item.grade + '">' +
    '<input type="hidden" name="items[' + idx + '][packaging_size]" value="' + item.packaging_size + '">' +
    '<input type="hidden" name="items[' + idx + '][weight]" value="' + item.weight + '">' +
    '<input type="hidden" name="items[' + idx + '][units_per_box]" value="' + item.units_per_box + '">' +
    '<td>' + (item.batch_no || '') + '</td>' +
    '<td>' + (item.product_name || '') + '</td>' +
    '<td>' + (item.grade_name || '') + '</td>' +
    '<td>' + (item.packaging_size_name || '') + '</td>' +
    '<td><input type="time" class="form-control" name="items[' + idx + '][loading_time]" value="' + (item.loading_time || '') + '"></td>' +
    '<td><select class="form-control" name="items[' + idx + '][customer_id]" style="width:100%"><option value="">Select Customer</option>' + customerOptions + '</select></td>' +
    '<td><input type="text" class="form-control" name="items[' + idx + '][remarks]" value="' + (item.remarks || '') + '" placeholder="Remark"></td>' +
    '<td><button type="button" class="btn btn-danger btn-sm" onclick="removeWeightDetail(this)"><i class="fas fa-trash"></i></button></td>' +
  '</tr>';
}

function initRowControls(idx, customerId) {
  setTimeout(function() {
    $('select[name="items[' + idx + '][customer_id]"]').select2({
      allowClear: true,
      placeholder: 'Select Customer',
      dropdownParent: $('#extendModal'),
      width: '100%'
    }).val(customerId || '').trigger('change');
  }, 0);
}

function openModal() {
  $('#extendForm').validate({
    errorElement: 'span',
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback');
      element.closest('.form-group').append(error);
    },
    highlight: function (element, errorClass, validClass) { $(element).addClass('is-invalid'); },
    unhighlight: function (element, errorClass, validClass) { $(element).removeClass('is-invalid'); }
  });
  $('#extendModal').modal('show');
}

function newEntry(){
  $('#extendModal').find('#id').val('');
  $('#extendModal').find('#loadingNo').val('').trigger('change');
  $('#loadingDatePicker').datetimepicker('date', moment());
  $('#extendModal').find('#remarks').val('');
  $('#extendModal').find('#shipmentType').val('').trigger('change');
  $('#extendModal').find('#batchNo').val('').trigger('change');
  $('#weightDetailsTable').empty();
  $('#selectedBatchCount').val(0);
  $('#totalWeightRecords').val(0);
  openModal();
}

function edit(id) {
  $('#spinnerLoading').show();
  $.post('php/modules/loading/getLoadingOrder.php', {userID: id}, function(data){
    var obj = JSON.parse(data);
    if (obj.status === 'success') {
      $('#extendModal').find('#id').val(obj.message.id);
      $('#extendModal').find('loadingNo').val(obj.message.loading_no);
      $('#extendModal').find('#remarks').val(obj.message.remarks);
      $('#extendModal').find('#shipmentType').val(obj.message.shipment_type).trigger('change');
      if (obj.message.loading_date) {
        $('#loadingDatePicker').datetimepicker('date', moment(obj.message.loading_date, 'DD/MM/YYYY'));
      } else {
        $('#loadingDatePicker').datetimepicker('clear');
      }
      var tbody = $('#weightDetailsTable');
      tbody.empty();
      // derive unique batch IDs and set batchNo without triggering change
      var uniqueBatchIds = [];
      if (obj.message.items && obj.message.items.length > 0) {
        obj.message.items.forEach(function(item) {
          if (uniqueBatchIds.indexOf(String(item.packaging_batch_id)) === -1) {
            uniqueBatchIds.push(String(item.packaging_batch_id));
          }
        });
      }
      // add any missing batch options (e.g. completed batches not in dropdown)
      uniqueBatchIds.forEach(function(batchId, i) {
        var batchNo = obj.message.items.find(function(it) { return String(it.packaging_batch_id) === batchId; }).batch_no;
        if ($('#batchNo option[value="' + batchId + '"]').length === 0) {
          $('#batchNo').append(new Option(batchNo, batchId, true, true));
        }
      });
      $('#batchNo').data('suppress-change', true).val(uniqueBatchIds).trigger('change').data('suppress-change', false);
      $('#selectedBatchCount').val(uniqueBatchIds.length);
      $('#totalWeightRecords').val(0);
      if (obj.message.items && obj.message.items.length > 0) {
        obj.message.items.forEach(function(item, idx) {
          tbody.append(buildItemRow({
            packaging_batch_item_id: item.packaging_batch_item_id,
            packaging_batch_id:      item.packaging_batch_id,
            product_id:              item.product_id,
            product_name:            item.product_name,
            grade:                   item.grade,
            grade_name:                   item.grade_name,
            packaging_size:          item.packaging_size,
            packaging_size_name:     item.packaging_size_name,
            units_per_box:           item.units_per_box,
            weight:                  item.weight,
            batch_no:                item.batch_no || '',
            loading_time:            item.loading_time ? item.loading_time.substring(11, 16) : '',
            remarks:                 item.remarks || ''
          }, idx));
          initRowControls(idx, item.customer_id);
        });
        $('#totalWeightRecords').val(obj.message.items.length);
      }
      openModal();
    } else if (obj.status === 'failed') {
      toastr["error"](obj.message, "Failed:");
    } else {
      toastr["error"]("Something wrong when pull data", "Failed:");
    }
    $('#spinnerLoading').hide();
  });
}

function reindexWeightDetails() {
  $('#weightDetailsTable tr').each(function(index) {
    $(this).find('input, select').each(function() {
      var name = $(this).attr('name');
      if(name) {
        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
      }
    });
  });
}

function removeWeightDetail(button) {
  $(button).closest('tr').remove();
  reindexWeightDetails();
  $('#totalWeightRecords').val($('#weightDetailsTable tr').length);
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

function print(id) {
  $.post('php/print.php', {userID: id}, function(data){
    var obj = JSON.parse(data);
    if(obj.status === 'success') {
      var printWindow = window.open('', '', 'height=' + screen.height + ',width=' + screen.width);
      printWindow.document.write(obj.message);
      printWindow.document.close();
      setTimeout(function(){
        printWindow.print();
        printWindow.close();
      }, 500);
    }
    else if(obj.status === 'failed'){
      alert(obj.message);
    }
    else{
      alert("Something wrong when activate");
    }
  });
}

function filterWeightTable(rowId) {
  var productFilter = $('#productFilter_' + rowId).val();
  var gradeFilter = $('#gradeFilter_' + rowId).val();
  
  var totalGross = 0, totalTare = 0, totalNet = 0;
  
  $('#weightTable_' + rowId + ' tbody tr').each(function() {
    var product = $(this).find('td:eq(0)').text();
    var grade = $(this).find('td:eq(1)').text();
    var showProduct = !productFilter || product == productFilter;
    var showGrade = !gradeFilter || grade == gradeFilter;
    var show = showProduct && showGrade;
    $(this).toggle(show);
    
    if(show) {
      var grossText = $(this).find('td:eq(2)').text().split(' ')[0];
      var tareText = $(this).find('td:eq(3)').text().split(' ')[0];
      var netText = $(this).find('td:eq(4)').text().split(' ')[0];
      
      totalGross += parseFloat(grossText) || 0;
      totalTare += parseFloat(tareText) || 0;
      totalNet += parseFloat(netText) || 0;
    }
  });
  
  $('#weightTable_' + rowId + ' tfoot tr th:eq(1)').text(totalGross.toFixed(2));
  $('#weightTable_' + rowId + ' tfoot tr th:eq(2)').text(totalTare.toFixed(2));
  $('#weightTable_' + rowId + ' tfoot tr th:eq(3)').text(totalNet.toFixed(2));
  
  if(productFilter) {
    var gradeSelect = $('#gradeFilter_' + rowId);
    var currentGrade = gradeSelect.val();
    gradeSelect.find('option:not(:first)').remove();
    
    var grades = [];
    $('#weightTable_' + rowId + ' tbody tr').each(function() {
      var product = $(this).find('td:eq(0)').text();
      if(product === productFilter) {
        var grade = $(this).find('td:eq(1)').text();
        if(grades.indexOf(grade) === -1) {
          grades.push(grade);
        }
      }
    });
    
    grades.sort();
    grades.forEach(function(grade) {
      gradeSelect.append('<option value="' + grade + '">' + grade + '</option>');
    });
    gradeSelect.val(currentGrade);
  } else {
    var gradeSelect = $('#gradeFilter_' + rowId);
    var currentGrade = gradeSelect.val();
    gradeSelect.find('option:not(:first)').remove();
    
    var grades = [];
    $('#weightTable_' + rowId + ' tbody tr').each(function() {
      var grade = $(this).find('td:eq(1)').text();
      if(grades.indexOf(grade) === -1) {
        grades.push(grade);
      }
    });
    
    grades.sort();
    grades.forEach(function(grade) {
      gradeSelect.append('<option value="' + grade + '">' + grade + '</option>');
    });
    gradeSelect.val(currentGrade);
  }
}

function populateFilters(rowId, weightDetails) {
  var products = {};
  var grades = [];
  
  weightDetails.forEach(function(detail) {
    products[detail.product_name] = true;
    if(grades.indexOf(detail.grade) === -1) {
      grades.push(detail.grade);
    }
  });
  
  var productSelect = $('#productFilter_' + rowId);
  for(var product in products) {
    productSelect.append('<option value="' + product + '">' + product + '</option>');
  }
  
  grades.sort();
  var gradeSelect = $('#gradeFilter_' + rowId);
  grades.forEach(function(grade) {
    gradeSelect.append('<option value="' + grade + '">' + grade + '</option>');
  });
}
</script>