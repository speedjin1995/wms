<?php
require_once 'php/db_connect.php';
require_once 'php/lookup.php';

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
}
else{
  $user    = $_SESSION['userID'];
  $company = $_SESSION['customer'];
  $module  = $_SESSION['module'];
  $stmt = $db->prepare("SELECT * from users where id = ?");
  $stmt->bind_param('s', $user);
  $stmt->execute();
  $result = $stmt->get_result();
  $role        = 'NORMAL';
  $allowAdd    = 'N';
  $allowDelete = 'N';

  if(($row = $result->fetch_assoc()) !== null){
    $role        = $row['role_code'];
    $allowAdd    = $row['allow_add'];
    $allowDelete = $row['allow_delete'];
  }

  if ($role != 'SADMIN'){
    $batches  = $db->query("SELECT * FROM packaging_batches WHERE deleted='0' AND status != 'completed' AND company='$company' ORDER BY packaging_date DESC");
    $batches2 = $db->query("SELECT * FROM packaging_batches WHERE deleted='0' AND status != 'completed' AND company='$company' ORDER BY packaging_date DESC");
  } else {
    $batches  = $db->query("SELECT * FROM packaging_batches WHERE deleted='0' AND status != 'completed' ORDER BY packaging_date DESC");
    $batches2 = $db->query("SELECT * FROM packaging_batches WHERE deleted='0' AND status != 'completed' ORDER BY packaging_date DESC");
  }

  $language      = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?=$languageArray['stock_transfer_code'][$language]?></h1>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">

    <!-- Filter -->
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
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
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
              <div class="col-3 d-flex align-items-end">
                <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="filterSearch">
                  <i class="fas fa-search"></i> <?=$languageArray['search_code'][$language]?>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Listing -->
    <div class="row">
      <div class="col-lg-12">
        <div class="card card-info">
          <div class="card-header">
            <div class="row">
              <div class="col-10"><?=$languageArray['stock_transfer_code'][$language]?></div>
              <?php if($allowAdd == 'Y'){ ?>
              <div class="col-2">
                <button type="button" class="btn btn-block bg-gradient-success btn-sm" onclick="newEntry()">
                  <i class="fas fa-plus"></i> <?=$languageArray['add_new_code'][$language]?>
                </button>
              </div>
              <?php } ?>
            </div>
          </div>
          <div class="card-body">
            <table id="transferTable" class="table table-bordered table-striped display">
              <thead>
                <tr>
                  <th><?=$languageArray['transfer_no_code'][$language]?></th>
                  <th><?=$languageArray['from_batch_code'][$language]?></th>
                  <th><?=$languageArray['to_batch_code'][$language]?></th>
                  <th><?=$languageArray['created_datetime_code'][$language]?></th>
                  <th><?=$languageArray['remark_code'][$language]?></th>
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

<!-- Transfer Modal -->
<div class="modal fade" id="transferModal">
  <div class="modal-dialog" style="max-width: 95%;">
    <div class="modal-content">
      <form role="form" id="transferForm">
        <div class="modal-header bg-gray-dark color-palette">
          <h4 class="modal-title"><?=$languageArray['stock_transfer_code'][$language]?></h4>
          <button type="button" class="close bg-gray-dark color-palette" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-5">
              <div class="form-group">
                <label><?=$languageArray['batch_code'][$language]?> A *</label>
                <select class="form-control select2" id="batchA" required style="width:100%;">
                  <option value="">Select Batch</option>
                  <?php while($b = mysqli_fetch_assoc($batches)) { ?>
                    <option value="<?=$b['id']?>"><?=$b['batch_no']?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
            <div class="col-md-5 offset-md-2">
              <div class="form-group">
                <label><?=$languageArray['batch_code'][$language]?> B *</label>
                <select class="form-control select2" id="batchB" required style="width:100%;">
                  <option value="">Select Batch</option>
                  <?php while($b = mysqli_fetch_assoc($batches2)) { ?>
                    <option value="<?=$b['id']?>"><?=$b['batch_no']?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-12">
              <div class="form-group">
                <label><?=$languageArray['remark_code'][$language]?></label>
                <textarea class="form-control" id="transferRemarks" name="remarks" rows="2"></textarea>
              </div>
            </div>
          </div>

          <!-- Side by side drag drop -->
          <div class="row">
            <!-- Batch A -->
            <div class="col-md-6">
              <div class="card card-outline card-primary">
                <div class="card-header">
                  <h6 class="mb-0">Batch A: <span id="batchALabel">-</span>
                    <span class="badge badge-secondary ml-2" id="batchACount">0</span>
                  </h6>
                </div>
                <div class="card-body p-0">
                  <table class="table table-bordered table-sm mb-0">
                    <thead class="bg-primary text-white">
                      <tr>
                        <th>Product</th>
                        <th>Grade</th>
                        <th>Pkg Size</th>
                        <th>Weight</th>
                        <th style="width:40px;"></th>
                      </tr>
                    </thead>
                    <tbody id="tableA" class="transfer-zone" data-side="A">
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Batch B -->
            <div class="col-md-6">
              <div class="card card-outline card-success">
                <div class="card-header">
                  <h6 class="mb-0">Batch B: <span id="batchBLabel">-</span>
                    <span class="badge badge-secondary ml-2" id="batchBCount">0</span>
                  </h6>
                </div>
                <div class="card-body p-0">
                  <table class="table table-bordered table-sm mb-0">
                    <thead class="bg-success text-white">
                      <tr>
                        <th>Product</th>
                        <th>Grade</th>
                        <th>Pkg Size</th>
                        <th>Weight</th>
                        <th style="width:40px;"></th>
                      </tr>
                    </thead>
                    <tbody id="tableB" class="transfer-zone" data-side="B">
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <small class="text-muted"><i class="fas fa-info-circle"></i> Drag rows between tables or use the arrow buttons to transfer items.</small>
        </div>

        <div class="modal-footer justify-content-between bg-gray-dark color-palette">
          <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-success" id="saveTransferBtn"><?=$languageArray['save_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" id="cancelForm">
        <div class="modal-header bg-gray-dark color-palette">
          <h4 class="modal-title"><?=$languageArray['delete_reason_code'][$language]?></h4>
          <button type="button" class="close bg-gray-dark color-palette" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label><?=$languageArray['delete_reason_code'][$language]?> *</label>
            <textarea class="form-control" id="cancelReason" name="cancelReason" rows="3" required></textarea>
          </div>
          <input type="hidden" id="cancelId" name="id">
        </div>
        <div class="modal-footer justify-content-between bg-gray-dark color-palette">
          <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-danger"><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.transfer-zone { min-height: 80px; }
.transfer-zone tr { cursor: grab; }
.transfer-zone tr.dragging { opacity: 0.4; }
.transfer-zone tr.drag-over { border-top: 2px solid #007bff; }
.transfer-zone tr td .btn-transfer { padding: 1px 6px; font-size: 11px; }
</style>

<script>
$(function () {
  const today = new Date();

  $('#fromDatePicker').datetimepicker({ icons: { time: 'far fa-clock' }, format: 'DD/MM/YYYY', defaultDate: today });
  $('#toDatePicker').datetimepicker({ icons: { time: 'far fa-clock' }, format: 'DD/MM/YYYY', defaultDate: today });

  $('.select2').each(function() {
    $(this).select2({ allowClear: true, placeholder: 'Please Select', dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-body') : undefined });
  });

  // DataTable
  var table = $('#transferTable').DataTable({
    responsive: true, autoWidth: false, processing: true, serverSide: true, serverMethod: 'post', searching: true,
    order: [[3, 'desc']],
    ajax: { url: 'php/modules/stockTransfer/filterStockTransfers.php', data: { fromDate: '', toDate: '' } },
    columns: [
      { data: 'transfer_no' },
      { data: 'from_batch_no' },
      { data: 'to_batch_no' },
      { data: 'created_date' },
      { data: 'remarks' },
      { data: 'id', class: 'action-button', render: function(data) {
          var btn = '<div class="d-flex flex-nowrap" style="gap:4px;">';
          <?php if($allowDelete == 'Y'){ ?>
          btn += '<button type="button" onclick="deactivate('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-undo"></i></button>';
          <?php } ?>
          btn += '</div>';
          return btn;
        }
      }
    ]
  });

  $('#filterSearch').on('click', function() {
    $('#transferTable').DataTable().clear().destroy();
    table = $('#transferTable').DataTable({
      responsive: true, autoWidth: false, processing: true, serverSide: true, serverMethod: 'post', searching: true,
      order: [[3, 'desc']],
      ajax: { url: 'php/modules/stockTransfer/filterStockTransfers.php', data: { fromDate: $('#fromDate').val(), toDate: $('#toDate').val() } },
      columns: [
        { data: 'transfer_no' },
        { data: 'from_batch_no' },
        { data: 'to_batch_no' },
        { data: 'created_date' },
        { data: 'remarks' },
        { data: 'id', class: 'action-button', render: function(data) {
            var btn = '<div class="d-flex flex-nowrap" style="gap:4px;">';
            <?php if($allowDelete == 'Y'){ ?>
            btn += '<button type="button" onclick="deactivate('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-undo"></i></button>';
            <?php } ?>
            btn += '</div>';
            return btn;
          }
        }
      ]
    });
  });

  // Load items when batch selected
  $('#batchA').on('change', function() {
    var id = $(this).val();
    var label = $(this).find('option:selected').text();
    $('#batchALabel').text(label || '-');
    if (!id) { $('#tableA').empty(); updateCounts(); return; }
    loadBatchItems(id, '#tableA', 'A');
  });

  $('#batchB').on('change', function() {
    var id = $(this).val();
    var label = $(this).find('option:selected').text();
    $('#batchBLabel').text(label || '-');
    if (!id) { $('#tableB').empty(); updateCounts(); return; }
    loadBatchItems(id, '#tableB', 'B');
  });

  // Form submit
  $('#transferForm').on('submit', function(e) {
    e.preventDefault();
    var fromBatchId = $('#batchA').val();
    var toBatchId   = $('#batchB').val();
    if (!fromBatchId || !toBatchId) { toastr['error']('Please select both batches.', 'Validation:'); return; }
    if (fromBatchId === toBatchId) { toastr['error']('Batch A and Batch B must be different.', 'Validation:'); return; }

    // Collect all items and their current side to determine from/to
    var items = [];
    $('#tableA tr').each(function() {
      var pbiId      = $(this).data('id');
      var originSide = $(this).data('origin');
      if (originSide === 'B') { // was in B, now in A — transferred B→A
        items.push({ packaging_batch_item_id: pbiId, from_batch_id: toBatchId, to_batch_id: fromBatchId });
      }
    });
    $('#tableB tr').each(function() {
      var pbiId      = $(this).data('id');
      var originSide = $(this).data('origin');
      if (originSide === 'A') { // was in A, now in B — transferred A→B
        items.push({ packaging_batch_item_id: pbiId, from_batch_id: fromBatchId, to_batch_id: toBatchId });
      }
    });

    if (items.length === 0) { toastr['warning']('No items have been transferred.', 'Info:'); return; }

    $('#spinnerLoading').show();
    $.post('php/modules/stockTransfer/saveStockTransfer.php', {
      fromBatchId: fromBatchId,
      toBatchId:   toBatchId,
      remarks:     $('#transferRemarks').val(),
      items:       items
    }, function(data) {
      var obj = JSON.parse(data);
      if (obj.status === 'success') {
        $('#transferModal').modal('hide');
        toastr['success'](obj.message, 'Success:');
        $('#transferTable').DataTable().ajax.reload();
      } else {
        toastr['error'](obj.message, 'Failed:');
      }
      $('#spinnerLoading').hide();
    });
  });

  // Cancel form
  $('#cancelForm').on('submit', function(e) {
    e.preventDefault();
    $('#spinnerLoading').show();
    $.post('php/modules/stockTransfer/deleteStockTransfer.php', { id: $('#cancelId').val(), cancelReason: $('#cancelReason').val() }, function(data) {
      var obj = JSON.parse(data);
      if (obj.status === 'success') {
        $('#cancelModal').modal('hide');
        toastr['success'](obj.message, 'Success:');
        $('#transferTable').DataTable().ajax.reload();
      } else {
        toastr['error'](obj.message, 'Failed:');
      }
      $('#spinnerLoading').hide();
    });
  });
});

function loadBatchItems(batchId, tableSelector, side) {
  $.post('php/modules/stockTransfer/getBatchItems.php', { batch_id: batchId }, function(data) {
    var obj = JSON.parse(data);
    var tbody = $(tableSelector);
    tbody.empty();
    if (obj.status === 'success') {
      obj.items.forEach(function(item) {
        tbody.append(buildTransferRow(item, side));
      });
    }
    updateCounts();
    initDragDrop();
  });
}

function buildTransferRow(item, side) {
  var arrow = side === 'A'
    ? '<button type="button" class="btn btn-sm btn-warning btn-transfer" onclick="moveRow(this, \'B\')"><i class="fas fa-arrow-right"></i></button>'
    : '<button type="button" class="btn btn-sm btn-warning btn-transfer" onclick="moveRow(this, \'A\')"><i class="fas fa-arrow-left"></i></button>';

  return $('<tr>')
    .attr('draggable', 'true')
    .data('id', item.id)
    .data('origin', side)
    .data('item', item)
    .append('<td>' + item.product_name + '</td>')
    .append('<td>' + item.grade + '</td>')
    .append('<td>' + item.packaging_size_name + '</td>')
    .append('<td>' + parseFloat(item.weight).toFixed(2) + '</td>')
    .append('<td>' + arrow + '</td>');
}

function moveRow(btn, targetSide) {
  var tr      = $(btn).closest('tr');
  var target  = targetSide === 'A' ? $('#tableA') : $('#tableB');

  // Update arrow button direction
  var newArrow = targetSide === 'A'
    ? '<button type="button" class="btn btn-sm btn-warning btn-transfer" onclick="moveRow(this, \'B\')"><i class="fas fa-arrow-right"></i></button>'
    : '<button type="button" class="btn btn-sm btn-warning btn-transfer" onclick="moveRow(this, \'A\')"><i class="fas fa-arrow-left"></i></button>';
  tr.find('td:last').html(newArrow);

  target.append(tr);
  updateCounts();
  initDragDrop();
}

function updateCounts() {
  $('#batchACount').text($('#tableA tr').length);
  $('#batchBCount').text($('#tableB tr').length);
}

function initDragDrop() {
  var dragSrc = null;

  $('.transfer-zone tr').off('dragstart dragend').on('dragstart', function(e) {
    dragSrc = this;
    $(this).addClass('dragging');
    e.originalEvent.dataTransfer.effectAllowed = 'move';
  }).on('dragend', function() {
    $(this).removeClass('dragging');
    $('.transfer-zone tr').removeClass('drag-over');
  });

  $('.transfer-zone').off('dragover drop dragleave').on('dragover', function(e) {
    e.preventDefault();
    e.originalEvent.dataTransfer.dropEffect = 'move';
    return false;
  }).on('drop', function(e) {
    e.preventDefault();
    if (dragSrc && $(dragSrc).closest('.transfer-zone')[0] !== this) {
      var targetSide = $(this).data('side');
      var newArrow = targetSide === 'A'
        ? '<button type="button" class="btn btn-sm btn-warning btn-transfer" onclick="moveRow(this, \'B\')"><i class="fas fa-arrow-right"></i></button>'
        : '<button type="button" class="btn btn-sm btn-warning btn-transfer" onclick="moveRow(this, \'A\')"><i class="fas fa-arrow-left"></i></button>';
      $(dragSrc).find('td:last').html(newArrow);
      $(this).append(dragSrc);
      updateCounts();
      initDragDrop();
    }
    return false;
  });
}

function newEntry() {
  $('#batchA').val('').trigger('change');
  $('#batchB').val('').trigger('change');
  $('#batchALabel').text('-');
  $('#batchBLabel').text('-');
  $('#transferRemarks').val('');
  $('#tableA').empty();
  $('#tableB').empty();
  updateCounts();

  // Re-init select2 inside modal
  $('#transferModal .select2').each(function() {
    if ($(this).hasClass('select2-hidden-accessible')) $(this).select2('destroy');
    $(this).select2({ allowClear: true, placeholder: 'Select Batch', dropdownParent: $('#transferModal .modal-body'), width: '100%' });
  });

  $('#transferModal').modal('show');
}

function deactivate(id) {
  $('#cancelId').val(id);
  $('#cancelReason').val('');
  $('#cancelModal').modal('show');
}
</script>
