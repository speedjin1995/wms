<?php
require_once 'php/db_connect.php';
session_start();

if(!isset($_SESSION['userID'])){
  echo '<script>window.location.href = "login.html";</script>';
  exit;
}

$company  = $_SESSION['customer'];
$user     = $_SESSION['userID'];
$role     = $_SESSION['role'];
$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];

$stmt = $db->prepare("SELECT allow_add, allow_edit, allow_delete FROM users WHERE id=?");
$stmt->bind_param('s', $user);
$stmt->execute();
$stmt->bind_result($allowAdd, $allowEdit, $allowDelete);
$stmt->fetch();
$stmt->close();

if ($role != 'SADMIN') {
  $products  = $db->query("SELECT id, product_name FROM products WHERE deleted='0' AND customer='$company' ORDER BY product_name ASC");
  $supplies  = $db->query("SELECT id, supplier_name FROM supplies WHERE deleted='0' AND customer='$company' ORDER BY supplier_name ASC");
} else {
  $products  = $db->query("SELECT id, product_name FROM products WHERE deleted='0' ORDER BY product_name ASC");
  $supplies  = $db->query("SELECT id, supplier_name FROM supplies WHERE deleted='0' ORDER BY supplier_name ASC");
}
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?=$languageArray['purchase_code'][$language]?></h1>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">

    <!-- Filter Card -->
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <div class="row">
              <div class="form-group col-md-3">
                <label><?=$languageArray['from_date_code'][$language]?>:</label>
                <div class="input-group date" id="fromDatePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#fromDatePicker" id="fromDate">
                  <div class="input-group-append" data-target="#fromDatePicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>
              <div class="form-group col-md-3">
                <label><?=$languageArray['to_date_code'][$language]?>:</label>
                <div class="input-group date" id="toDatePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#toDatePicker" id="toDate">
                  <div class="input-group-append" data-target="#toDatePicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>
              <div class="form-group col-md-3">
                <label><?=$languageArray['supplier_code'][$language]?></label>
                <select class="form-control select2" id="supplierFilter">
                  <option value="">-</option>
                  <?php $supplies->data_seek(0); while($r = mysqli_fetch_assoc($supplies)): ?>
                    <option value="<?=htmlspecialchars($r['supplier_name'])?>"><?=htmlspecialchars($r['supplier_name'])?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="col-md-3 d-flex align-items-end">
                <button type="button" class="btn btn-block bg-gradient-warning btn-sm mb-3" id="filterSearch">
                  <i class="fas fa-search"></i> <?=$languageArray['search_code'][$language]?>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- DataTable Card -->
    <div class="row">
      <div class="col-lg-12">
        <div class="card card-info">
          <div class="card-header">
            <div class="row">
              <div class="col-10"><?=$languageArray['purchase_code'][$language]?></div>
              <?php if($allowAdd == 'Y'): ?>
              <div class="col-2">
                <button type="button" class="btn btn-block bg-gradient-success btn-sm" onclick="newEntry()">
                  <i class="fas fa-plus"></i> <?=$languageArray['add_new_code'][$language]?>
                </button>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <div class="card-body">
            <table id="purchaseTable" class="table table-bordered table-striped display">
              <thead>
                <tr>
                  <th><?=$languageArray['purchase_code'][$language]?> No</th>
                  <th><?=$languageArray['transaction_date_code'][$language]?></th>
                  <th><?=$languageArray['supplier_code'][$language]?></th>
                  <th>PO No</th>
                  <th><?=$languageArray['total_code'][$language]?> (RM)</th>
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

<!-- Add/Edit Modal -->
<div class="modal fade" id="purchaseModal">
  <div class="modal-dialog modal-xl" style="max-width:90%;">
    <div class="modal-content">
      <form role="form" id="purchaseForm">
        <div class="modal-header bg-gray-dark color-palette">
          <h4 class="modal-title" id="modalTitle"><?=$languageArray['add_new_code'][$language]?></h4>
          <button type="button" class="close bg-gray-dark color-palette" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>

        <div class="modal-body bg-light">
          <input type="hidden" id="purchaseId" name="id">

          <!-- Purchase Info -->
          <div class="card card-outline card-primary mb-3 shadow-sm">
            <div class="card-header py-2">
              <h6 class="card-title mb-0"><i class="fas fa-file-invoice mr-2"></i><?=$languageArray['purchase_code'][$language]?> Info</h6>
            </div>
            <div class="card-body pt-3">
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="text-muted small mb-1"><?=$languageArray['purchase_code'][$language]?> No</label>
                    <input type="text" class="form-control" id="purchaseNo" name="purchaseNo" readonly>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="text-muted small mb-1"><?=$languageArray['transaction_date_code'][$language]?> *</label>
                    <div class="input-group date" id="purchaseDatePicker" data-target-input="nearest">
                      <input type="text" class="form-control datetimepicker-input" data-target="#purchaseDatePicker" id="purchaseDate" name="purchaseDate">
                      <div class="input-group-append" data-target="#purchaseDatePicker" data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="text-muted small mb-1"><?=$languageArray['supplier_code'][$language]?></label>
                    <select class="form-control select2modal" id="supplier" name="supplier">
                      <option value="" disabled hidden>Please Select</option>
                      <?php $supplies->data_seek(0); while($r = mysqli_fetch_assoc($supplies)): ?>
                        <option value="<?=htmlspecialchars($r['supplier_name'])?>"><?=htmlspecialchars($r['supplier_name'])?></option>
                      <?php endwhile; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="text-muted small mb-1">PO No</label>
                    <input type="text" class="form-control" id="poNo" name="poNo">
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Items -->
          <div class="card card-outline card-warning mb-3 shadow-sm">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
              <h6 class="card-title mb-0"><i class="fas fa-boxes mr-2"></i><?=$languageArray['item_code'][$language]?></h6>
              <button type="button" class="btn btn-success btn-sm" onclick="addItemRow()">
                <i class="fas fa-plus"></i> <?=$languageArray['add_new_code'][$language]?>
              </button>
            </div>
            <div class="card-body p-0">
              <table class="table table-bordered mb-0" id="itemsTable">
                <thead class="thead-light">
                  <tr>
                    <th><?=$languageArray['item_code'][$language]?></th>
                    <th style="width:160px"><?=$languageArray['weight_code'][$language]?> (kg)</th>
                    <th style="width:160px"><?=$languageArray['price_code'][$language]?>/kg (RM)</th>
                    <th style="width:130px"><?=$languageArray['total_code'][$language]?> (RM)</th>
                    <th style="width:50px"></th>
                  </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
                <tfoot>
                  <tr>
                    <td colspan="3" class="text-right font-weight-bold"><?=$languageArray['total_code'][$language]?> (RM)</td>
                    <td><input type="text" id="grandTotal" class="form-control form-control-sm text-right font-weight-bold" readonly value="0.00"></td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

        </div>

        <div class="modal-footer justify-content-between bg-gray-dark color-palette">
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-primary" id="saveBtn"><?=$languageArray['save_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="deleteForm">
        <div class="modal-header bg-gray-dark color-palette">
          <h4 class="modal-title"><?=$languageArray['delete_reason_code'][$language]?></h4>
          <button type="button" class="close bg-gray-dark color-palette" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="deleteId" name="id">
          <div class="form-group">
            <label><?=$languageArray['delete_reason_code'][$language]?> *</label>
            <textarea class="form-control" id="deleteReason" name="reason" rows="3" required></textarea>
          </div>
        </div>
        <div class="modal-footer justify-content-between bg-gray-dark color-palette">
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-danger"><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var productOptions = <?php
  $opts = [];
  $products->data_seek(0);
  while($r = mysqli_fetch_assoc($products)) {
    $opts[] = ['id' => $r['id'], 'name' => htmlspecialchars($r['product_name'])];
  }
  echo json_encode($opts);
?>;

var itemRowCount = 0;
var allowEdit   = <?=$allowEdit   == 'Y' ? 'true' : 'false'?>;
var allowDelete = <?=$allowDelete == 'Y' ? 'true' : 'false'?>;

$(function() {
  var today     = new Date();
  var yesterday = new Date(); yesterday.setDate(yesterday.getDate() - 7);

  $('.select2').select2({ allowClear: true, placeholder: 'Please Select' });

  $('#fromDatePicker').datetimepicker({ format: 'DD/MM/YYYY', defaultDate: yesterday });
  $('#toDatePicker').datetimepicker({ format: 'DD/MM/YYYY', defaultDate: today });
  $('#purchaseDatePicker').datetimepicker({ format: 'DD/MM/YYYY HH:mm', defaultDate: today });

  // Init DataTable
  var table = initTable($('#fromDate').val(), $('#toDate').val(), '');

  $('#filterSearch').on('click', function() {
    $('#purchaseTable').DataTable().clear().destroy();
    initTable($('#fromDate').val(), $('#toDate').val(), $('#supplierFilter').val());
  });

  // Submit purchase form
  $('#purchaseForm').on('submit', function(e) {
    e.preventDefault();
    var items = collectItems();
    if (items.length === 0) { toastr.warning('Please add at least one item.'); return; }

    $('#spinnerLoading').show();
    var data = $(this).serialize() + '&' + $.param({ items: items });

    $.post('php/purchase.php', data, function(res) {
      var obj = JSON.parse(res);
      $('#spinnerLoading').hide();
      if (obj.status === 'success') {
        toastr.success(obj.message, 'Success:');
        $('#purchaseModal').modal('hide');
        $('#purchaseTable').DataTable().ajax.reload();
      } else {
        toastr.error(obj.message, 'Failed:');
      }
    });
  });

  // Submit delete form
  $('#deleteForm').on('submit', function(e) {
    e.preventDefault();
    $('#spinnerLoading').show();
    $.post('php/deletePurchase.php', $(this).serialize(), function(res) {
      var obj = JSON.parse(res);
      $('#spinnerLoading').hide();
      if (obj.status === 'success') {
        toastr.success(obj.message, 'Success:');
        $('#deleteModal').modal('hide');
        $('#purchaseTable').DataTable().ajax.reload();
      } else {
        toastr.error(obj.message, 'Failed:');
      }
    });
  });
});

function initTable(from, to, supplier) {
  return $('#purchaseTable').DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: true,
    serverMethod: 'post',
    order: [[1, 'desc']],
    ajax: {
      url: 'php/filterPurchase.php',
      data: { fromDate: from, toDate: to, supplier: supplier }
    },
    columns: [
      { data: 'purchase_no' },
      { data: 'purchase_date' },
      { data: 'supplier' },
      { data: 'po_no' },
      { data: 'total_price' },
      {
        data: 'id',
        orderable: false,
        render: function(data) {
          var btns = '<div class="d-flex flex-nowrap" style="gap:4px;">';
          if (allowEdit)   btns += '<button type="button" onclick="editEntry('+data+')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button>';
          if (allowDelete) btns += '<button type="button" onclick="deleteEntry('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>';
          btns += '</div>';
          return btns;
        }
      }
    ]
  });
}

function buildProductSelect(selectedId) {
  var html = '<select class="form-control form-control-sm item-product">';
  html += '<option value="" disabled selected>Please Select</option>';
  productOptions.forEach(function(p) {
    html += '<option value="'+p.id+'"'+(p.id == selectedId ? ' selected' : '')+'>'+p.name+'</option>';
  });
  html += '</select>';
  return html;
}

function addItemRow(id, productId, weight, price, total) {
  var idx = itemRowCount++;
  var row = '<tr id="irow_'+idx+'" data-idx="'+idx+'">' +
    '<td>'+buildProductSelect(productId || '')+'</td>' +
    '<td><input type="number" class="form-control form-control-sm item-weight" step="0.01" min="0" value="'+(weight||'')+'"></td>' +
    '<td><input type="number" class="form-control form-control-sm item-price" step="0.01" min="0" value="'+(price||'')+'"></td>' +
    '<td><input type="text" class="form-control form-control-sm item-total text-right" readonly value="'+(total ? parseFloat(total).toFixed(2) : '0.00')+'"></td>' +
    '<td><button type="button" class="btn btn-danger btn-sm" onclick="removeItemRow('+idx+')"><i class="fas fa-trash"></i></button></td>' +
    '</tr>';
  $('#itemsBody').append(row);
  recalcRow(idx);

  // Bind recalc on input
  $('#irow_'+idx+' .item-weight, #irow_'+idx+' .item-price').on('input', function() { recalcRow(idx); });
}

function recalcRow(idx) {
  var $row  = $('#irow_'+idx);
  var w     = parseFloat($row.find('.item-weight').val()) || 0;
  var p     = parseFloat($row.find('.item-price').val()) || 0;
  var total = (w * p).toFixed(2);
  $row.find('.item-total').val(total);
  recalcGrand();
}

function recalcGrand() {
  var grand = 0;
  $('#itemsBody .item-total').each(function() { grand += parseFloat($(this).val()) || 0; });
  $('#grandTotal').val(grand.toFixed(2));
}

function removeItemRow(idx) {
  $('#irow_'+idx).remove();
  recalcGrand();
}

function collectItems() {
  var items = [];
  $('#itemsBody tr').each(function() {
    var productId = $(this).find('.item-product').val();
    var weight    = $(this).find('.item-weight').val();
    var price     = $(this).find('.item-price').val();
    var total     = $(this).find('.item-total').val();
    if (productId) items.push({ product_id: productId, weight: weight, price: price, total: total });
  });
  return items;
}

function newEntry() {
  $('#purchaseForm')[0].reset();
  $('#purchaseId').val('');
  $('#purchaseNo').val('');
  $('#itemsBody').empty();
  itemRowCount = 0;
  $('#grandTotal').val('0.00');
  $('#modalTitle').text('Add Purchase 新增采购');
  $('#purchaseDatePicker').datetimepicker('date', moment());
  initModalSelect2();
  addItemRow();
  $('#purchaseModal').modal('show');
}

function editEntry(id) {
  $('#spinnerLoading').show();
  $.post('php/getPurchase.php', { id: id }, function(res) {
    var obj = JSON.parse(res);
    $('#spinnerLoading').hide();
    if (obj.status !== 'success') { toastr.error(obj.message, 'Failed:'); return; }
    var d = obj.message;

    $('#purchaseId').val(d.id);
    $('#purchaseNo').val(d.purchase_no);
    $('#poNo').val(d.po_no);
    if (d.purchase_date) {
      $('#purchaseDatePicker').datetimepicker('date', moment(d.purchase_date, 'YYYY-MM-DD HH:mm:ss'));
    }
    $('#itemsBody').empty();
    itemRowCount = 0;
    $('#grandTotal').val('0.00');
    $('#modalTitle').text('Edit Purchase 编辑采购');
    initModalSelect2();
    $('#supplier').val(d.supplier).trigger('change');

    (d.items || []).forEach(function(item) {
      addItemRow(null, item.product_id, item.weight, item.price, item.total_price);
    });

    $('#purchaseModal').modal('show');
  });
}

function deleteEntry(id) {
  $('#deleteId').val(id);
  $('#deleteReason').val('');
  $('#deleteModal').modal('show');
}

function initModalSelect2() {
  $('#purchaseModal .select2modal').select2({
    allowClear: true,
    placeholder: 'Please Select',
    dropdownParent: $('#purchaseModal .modal-body')
  });
}
</script>
