<?php
session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
  exit;
}

if (($_SESSION['featureFlags']['include_delivery_order'] ?? 'N') != 'Y') {
  exit;
}

$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];
$doAllowPrice = (($_SESSION['featureFlags']['include_price'] ?? 'N') == 'Y' && ($_SESSION['userAllowPrice'] ?? 'N') == 'Y') ? 'Y' : 'N';
$t = function ($key, $default = '') use ($languageArray, $language) { return $languageArray[$key][$language] ?? $default; };
?>

<div class="content page-modern">
  <div class="container-fluid">

    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title"><i class="fas fa-truck-loading"></i> <?=$t('delivery_order_code', 'Delivery Order')?></h1>
    </div>

    <!-- Filter Card -->
    <div class="card filter-card">
      <div class="card-body">
        <div class="filter-row">
          <div class="filter-group">
            <label class="filter-label"><?=$t('date_from_code', 'Date From')?></label>
            <div class="input-group date" id="doDateFromPicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#doDateFromPicker" id="doDateFrom"/>
              <div class="input-group-append" data-target="#doDateFromPicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>
          <div class="filter-group">
            <label class="filter-label"><?=$t('date_to_code', 'Date To')?></label>
            <div class="input-group date" id="doDateToPicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#doDateToPicker" id="doDateTo"/>
              <div class="input-group-append" data-target="#doDateToPicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>
          <div class="filter-group filter-group-action" style="margin-left:auto;">
            <label class="filter-label">&nbsp;</label>
            <div class="d-flex" style="gap:0.5rem;">
              <button type="button" class="btn btn-filter btn-filter-secondary" id="loadDoListBtn">
                <i class="fas fa-search"></i> <?=$t('search_code', 'Search')?>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Results Card -->
    <div class="card results-card show-dt-controls">
      <div class="card-header">
        <div class="results-header-left">
          <h3 class="results-title"><i class="fas fa-truck-loading"></i> <?=$t('delivery_order_code', 'Delivery Order')?></h3>
        </div>
        <div class="results-header-right d-flex" style="gap: 0.5rem;">
          <button type="button" class="btn btn-action btn-action-primary" id="newDoBtn">
            <i class="fas fa-plus"></i> <?=$t('new_code', 'New')?>
          </button>
        </div>
      </div>
      <div class="card-body">
        <table class="table data-table" id="doListTable">
          <thead>
            <tr>
              <th><?=$t('do_no_code', 'DO No')?></th>
              <th><?=$t('date_code', 'Date')?></th>
              <th><?=$t('supplier_code', 'Supplier')?></th>
              <th><?=$t('do_weight_code', 'DO Weight')?></th>
              <th><?=$t('received_weight_code', 'Received Weight')?></th>
              <th><?=$t('weight_loss_code', 'Weight Loss')?></th>
              <th><?=$t('created_by_code', 'Created By')?></th>
              <th width="12%"><?=$t('actions_code', 'Actions')?></th>
            </tr>
          </thead>
        </table>
      </div>
    </div>

    <!-- Delivery Order Modal -->
    <div class="modal fade modal-modern" id="doModal" tabindex="-1">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-truck-loading mr-2 text-muted"></i><?=$t('delivery_order_code', 'Delivery Order')?> - <span id="doModalTitle"><?=$t('new_code', 'New')?></span></h5>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="doId">

            <div class="modal-section">
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group-modern">
                    <label class="form-label-modern"><?=$t('date_code', 'Date')?> <span class="text-danger">*</span></label>
                    <div class="input-group date" id="doDatePicker" data-target-input="nearest">
                      <input type="text" class="form-control datetimepicker-input" data-target="#doDatePicker" id="doDate"/>
                      <div class="input-group-append" data-target="#doDatePicker" data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group-modern">
                    <label class="form-label-modern"><?=$t('do_no_code', 'DO No')?></label>
                    <input type="text" class="form-control" id="doNo" placeholder="Auto">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group-modern">
                    <label class="form-label-modern"><?=$t('remark_code', 'Remark')?></label>
                    <input type="text" class="form-control" id="doRemark">
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-section">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-title mb-0"><i class="fas fa-list mr-2"></i><?=$t('supplier_code', 'Supplier')?></div>
                <div>
                  <button type="button" class="btn btn-sm btn-secondary" id="doTemplateBtn"><i class="fas fa-download mr-1"></i><?=$t('download_template_code', 'Download Template')?></button>
                  <input type="file" id="doExcelFile" accept=".xlsx,.xls" style="display:none">
                  <button type="button" class="btn btn-sm btn-info" id="doUploadBtn"><i class="fas fa-file-excel mr-1"></i><?=$t('upload_excel_code', 'Upload Excel')?></button>
                  <button type="button" class="btn btn-sm btn-success" id="doAddSupplierBtn"><i class="fas fa-plus mr-1"></i><?=$t('add_supplier_code', 'Add Supplier')?></button>
                </div>
              </div>
              <div id="doSuppliers"></div>
            </div>
          </div>
          <div class="modal-footer">
            <div class="mr-auto font-weight-bold"><?=$t('do_weight_code', 'DO Weight')?>: <span id="doGrandTotal">0.00</span> | <?=$t('received_weight_code', 'Received Weight')?>: <span id="doGrandReceived">0.00</span> | <?=$t('weight_loss_code', 'Weight Loss')?>: <span id="doGrandLoss">0.00</span></div>
            <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$t('close_code', 'Close')?></button>
            <button type="button" class="btn btn-modern btn-modern-success" id="saveDoBtn"><i class="fas fa-save mr-1"></i><?=$t('save_code', 'Save')?></button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
var doListTable = null;
var doOptions = { suppliers: [], categories: [], products: [], defaultCurrency: null };
var doRemovedIds = [];
var DO_WEIGHT_TOLERANCE = 0.8;
var DO_FLAG_CLASSES = 'text-primary text-danger font-weight-bold border border-primary border-danger';
var doAllowPrice = '<?=$doAllowPrice?>';
var doText = <?=json_encode([
  'category' => $t('category_code', 'Category'),
  'product' => $t('product_code', 'Product'),
  'grade' => $t('grade_code', 'Grade'),
  'weight' => $t('weight_code', 'Weight'),
  'doWeight' => $t('do_weight_code', 'DO Weight'),
  'received' => $t('received_weight_code', 'Received Weight'),
  'loss' => $t('weight_loss_code', 'Weight Loss'),
  'correct' => $t('correct_do_weight_code', 'Correct DO Weight'),
  'total' => $t('total_code', 'Total'),
  'edit' => $t('edit_code', 'Edit'),
  'new' => $t('new_code', 'New'),
  'deleteConfirm' => 'Delete this delivery order? Its wholesales records will be deleted and stock reversed.'
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;

$(function() {
  $('#doDateFromPicker, #doDateToPicker, #doDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: new Date()
  });

  $('#loadDoListBtn').on('click', loadDoList);
  $('#newDoBtn').on('click', function() { openDoModal(); });
  $('#doAddSupplierBtn').on('click', function() { addLine(addSupplierCard()); });
  $('#saveDoBtn').on('click', saveDo);
  $('#doTemplateBtn').on('click', function() { window.location.href = 'php/modules/deliveryOrder/api.php?action=template'; });
  $('#doUploadBtn').on('click', function() { $('#doExcelFile').val('').click(); });
  $('#doExcelFile').on('change', onExcelSelected);

  $('#doSuppliers')
    .on('click', '.do-remove-supplier', function() {
      var $card = $(this).closest('.do-supplier');
      if ($card.data('wid')) doRemovedIds.push($card.data('wid'));
      $card.remove();
      updateDoTotals();
    })
    .on('click', '.do-add-line', function() { addLine($(this).closest('.do-supplier')); })
    .on('click', '.do-remove-line', function() {
      $(this).closest('tr').remove();
      updateDoTotals();
    })
    .on('change', '.do-category', function() {
      var $row = $(this).closest('tr');
      fillProducts($row, $(this).val());
      fillGrades($row, null);
    })
    .on('change', '.do-product', function() { fillGrades($(this).closest('tr'), $(this).val()); })
    .on('input', '.do-bag-do, .do-bag-received', updateDoTotals)
    .on('click', '.do-add-bag', function() { addBag($(this).closest('.do-line')); })
    .on('click', '.do-remove-bag', function() {
      $(this).closest('tr').remove();
      updateDoTotals();
    });

  $('#doListTable')
    .on('click', '.edit-do-btn', function() { editDo($(this).data('id')); })
    .on('click', '.delete-do-btn', function() { deleteDo($(this).data('id')); });

  loadDoList();
});

// ============================================================================
// LIST
// ============================================================================
function loadDoList() {
  if (doListTable) {
    doListTable.clear().destroy();
    doListTable = null;
  }

  doListTable = $('#doListTable').DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: false,
    order: [],
    ajax: {
      url: 'php/modules/deliveryOrder/api.php',
      type: 'POST',
      data: { action: 'list', date_from: $('#doDateFrom').val() || '', date_to: $('#doDateTo').val() || '' },
      dataSrc: function(json) { return json.status === 'success' ? json.data : []; }
    },
    columns: [
      { data: 'do_no' },
      { data: 'do_date_display' },
      { data: 'supplier_count', className: 'text-center' },
      { data: 'do_weight', className: 'text-right', render: function(d) { return (parseFloat(d) || 0).toFixed(2); } },
      { data: 'received_weight', className: 'text-right', render: function(d) { return (parseFloat(d) || 0).toFixed(2); } },
      { data: null, className: 'text-right', render: function(d) { return ((parseFloat(d.do_weight) || 0) - (parseFloat(d.received_weight) || 0)).toFixed(2); } },
      { data: 'created_by_name', defaultContent: '-' },
      { data: null, orderable: false, className: 'action-button', render: function(d) {
        return '<div class="expanded-actions">' +
          '<button type="button" class="btn btn-sm btn-outline-primary edit-do-btn" data-id="' + d.id + '"><i class="fas fa-pen"></i></button>' +
          '<button type="button" class="btn btn-sm btn-outline-danger delete-do-btn" data-id="' + d.id + '"><i class="fas fa-trash"></i></button>' +
          '</div>';
      } }
    ],
    'language': {
      'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
      'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
    }
  });
}

// ============================================================================
// MODAL BUILDING
// ============================================================================
function loadDoOptions(callback) {
  $.post('php/modules/deliveryOrder/api.php', { action: 'options' }).done(function(obj) {
    if (obj.status === 'success') {
      doOptions = obj.data;
      callback();
    } else {
      toastr.error(obj.message, 'Failed:');
    }
  });
}

function resetDoModal(title) {
  $('#doId').val('');
  $('#doModalTitle').text(title);
  $('#doDate').val(moment().format('DD/MM/YYYY'));
  $('#doNo, #doRemark').val('');
  $('#doSuppliers').html('');
  doRemovedIds = [];
  updateDoTotals();
}

function openDoModal() {
  loadDoOptions(function() {
    resetDoModal(doText.new);
    addLine(addSupplierCard());
    $('#doModal').modal('show');
  });
}

function findProduct(id) {
  return doOptions.products.find(function(p) { return p.id == id; });
}

function findSupplier(id) {
  return doOptions.suppliers.find(function(s) { return s.id == id; });
}

function optionList(items, valueKey, textFn) {
  var $opts = [$('<option value="">-</option>')];
  items.forEach(function(item) {
    $opts.push($('<option>').val(item[valueKey]).text(textFn(item)));
  });
  return $opts;
}

function initSelect2($el) {
  $el.select2({ width: '100%', dropdownParent: $('#doModal') });
}

function addSupplierCard(supplierId, wholesaleId, serialNo) {
  var $card = $(
    '<div class="card do-supplier mb-3">' +
      '<div class="card-header d-flex align-items-center" style="gap:0.5rem;">' +
        '<div style="width:320px;"><select class="form-control form-control-sm do-supplier-select"></select></div>' +
        '<span class="ml-auto">' + doText.doWeight + ': <b class="do-supplier-do">0.00</b> | ' + doText.received + ': <b class="do-supplier-received">0.00</b> | ' + doText.loss + ': <b class="do-supplier-loss">0.00</b></span>' +
        '<button type="button" class="btn btn-sm btn-success do-add-line"><i class="fas fa-plus"></i></button>' +
        '<button type="button" class="btn btn-sm btn-danger do-remove-supplier"><i class="fas fa-times"></i></button>' +
      '</div>' +
      '<div class="card-body p-2"><table class="table table-sm table-bordered mb-0">' +
        '<thead><tr><th style="width:16%;">' + doText.category + '</th><th style="width:18%;">' + doText.product + '</th><th style="width:12%;">' + doText.grade + '</th>' +
        '<th>' + doText.weight + '</th><th style="width:4%;"></th></tr></thead>' +
        '<tbody class="do-lines"></tbody>' +
      '</table></div>' +
    '</div>'
  );

  if (wholesaleId) {
    $card.data('wid', wholesaleId);
    $card.data('serial', serialNo);
  }

  var $select = $card.find('.do-supplier-select').append(optionList(doOptions.suppliers, 'id', function(s) {
    return (s.supplier_code ? s.supplier_code + ' - ' : '') + s.supplier_name;
  }));
  $('#doSuppliers').append($card);
  initSelect2($select);
  if (supplierId) $select.val(supplierId).trigger('change.select2');

  return $card;
}

function addLine($card, data) {
  data = data || {};
  var $row = $(
    '<tr class="do-line">' +
      '<td><select class="form-control form-control-sm do-category"></select></td>' +
      '<td><select class="form-control form-control-sm do-product"></select></td>' +
      '<td><select class="form-control form-control-sm do-grade"></select></td>' +
      '<td><table class="table table-sm table-bordered mb-0 do-bags">' +
        '<thead><tr><th>' + doText.doWeight + '</th><th>' + doText.received + '</th><th>' + doText.loss + '</th><th>' + doText.correct + '</th><th style="width:4%;"></th></tr></thead>' +
        '<tbody></tbody>' +
        '<tfoot><tr><th class="text-right">' + doText.total + '</th><th class="text-right do-line-do">0.00</th><th class="text-right do-line-received">0.00</th><th class="text-right do-line-loss">0.00</th>' +
        '<th class="text-center"><button type="button" class="btn btn-sm btn-success do-add-bag"><i class="fas fa-plus"></i></button></th></tr></tfoot>' +
      '</table></td>' +
      '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger do-remove-line"><i class="fas fa-times"></i></button></td>' +
    '</tr>'
  );
  $card.find('.do-lines').append($row);

  $row.find('.do-category').append(optionList(doOptions.categories, 'id', function(c) { return c.category_name; }));
  initSelect2($row.find('.do-category, .do-product, .do-grade'));

  if (data.category) {
    $row.find('.do-category').val(data.category).trigger('change.select2');
    fillProducts($row, data.category);
  }
  if (data.product) {
    $row.find('.do-product').val(data.product).trigger('change.select2');
    fillGrades($row, data.product);
  }
  if (data.grade_id) {
    $row.find('.do-grade').val(data.grade_id).trigger('change.select2');
  }
  (data.pairs && data.pairs.length ? data.pairs : [{}]).forEach(function(p) { addBag($row, p); });

  updateDoTotals();
  return $row;
}

function fillProducts($row, categoryId) {
  var products = categoryId ? doOptions.products.filter(function(p) { return p.category == categoryId; }) : [];
  $row.find('.do-product').empty().append(optionList(products, 'id', function(p) {
    return (p.product_code ? p.product_code + ' - ' : '') + p.product_name;
  })).trigger('change.select2');
}

function fillGrades($row, productId) {
  var product = productId ? findProduct(productId) : null;
  $row.find('.do-grade').empty().append(optionList(product ? product.grades : [], 'grade_id', function(g) {
    return g.grade_name;
  })).trigger('change.select2');
}

function addBag($row, pair) {
  pair = pair || {};
  var $bag = $(
    '<tr class="do-bag">' +
      '<td><input type="number" step="0.01" class="form-control form-control-sm do-bag-do"></td>' +
      '<td><input type="number" step="0.01" class="form-control form-control-sm do-bag-received"></td>' +
      '<td class="text-right do-bag-loss"></td>' +
      '<td class="text-right do-bag-correct"></td>' +
      '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger do-remove-bag"><i class="fas fa-times"></i></button></td>' +
    '</tr>'
  );
  if (pair.do != null) $bag.find('.do-bag-do').val(pair.do);
  if (pair.received != null) $bag.find('.do-bag-received').val(pair.received);
  $row.find('.do-bags tbody').append($bag);
  updateDoTotals();
  return $bag;
}

// Blank inputs stay blank so an unweighed bag is not read as a zero weight
function linePairs($row) {
  return $row.find('.do-bag').map(function() {
    return { do: $(this).find('.do-bag-do').val(), received: $(this).find('.do-bag-received').val() };
  }).get();
}

function hasWeight(pairs) {
  return pairs.some(function(p) { return parseFloat(p.do) > 0 || parseFloat(p.received) > 0; });
}

function hasReceived(s) {
  return s.lines.some(function(line) {
    return line.pairs.some(function(p) { return parseFloat(p.received) > 0; });
  });
}

// Loss = DO - received; flagged when at least DO_WEIGHT_TOLERANCE off in either direction
function updateDoTotals() {
  var grandDo = 0;
  var grandReceived = 0;
  var grandLoss = 0;

  $('#doSuppliers .do-supplier').each(function() {
    var supplierDo = 0;
    var supplierReceived = 0;
    var supplierLoss = 0;

    $(this).find('.do-line').each(function() {
      var lineDo = 0;
      var lineReceived = 0;
      var lineLoss = 0;

      $(this).find('.do-bag').each(function() {
        var $bag = $(this);
        var doVal = $bag.find('.do-bag-do').val();
        var recVal = $bag.find('.do-bag-received').val();
        var doW = parseFloat(doVal) || 0;
        var recW = parseFloat(recVal) || 0;
        var $loss = $bag.find('.do-bag-loss').removeClass(DO_FLAG_CLASSES).text('');
        var $correct = $bag.find('.do-bag-correct').text('');

        lineDo += doW;
        lineReceived += recW;
        if (doVal === '' || recVal === '') return;

        var diff = Math.round((doW - recW) * 100) / 100;
        lineLoss += diff;
        $loss.text(diff.toFixed(2));
        if (-diff >= DO_WEIGHT_TOLERANCE) {
          $loss.addClass('text-primary font-weight-bold border border-primary');
          $correct.text(recW.toFixed(2));
        } else if (diff >= DO_WEIGHT_TOLERANCE) {
          $loss.addClass('text-danger font-weight-bold border border-danger');
          $correct.text(recW.toFixed(2));
        }
      });

      $(this).find('.do-line-do').text(lineDo.toFixed(2));
      $(this).find('.do-line-received').text(lineReceived.toFixed(2));
      $(this).find('.do-line-loss').text(lineLoss.toFixed(2));
      supplierDo += lineDo;
      supplierReceived += lineReceived;
      supplierLoss += lineLoss;
    });

    $(this).find('.do-supplier-do').text(supplierDo.toFixed(2));
    $(this).find('.do-supplier-received').text(supplierReceived.toFixed(2));
    $(this).find('.do-supplier-loss').text(supplierLoss.toFixed(2));
    grandDo += supplierDo;
    grandReceived += supplierReceived;
    grandLoss += supplierLoss;
  });

  $('#doGrandTotal').text(grandDo.toFixed(2));
  $('#doGrandReceived').text(grandReceived.toFixed(2));
  $('#doGrandLoss').text(grandLoss.toFixed(2));
}

// ============================================================================
// SAVE
// ============================================================================
function parseJson(d) {
  return typeof d === 'string' ? JSON.parse(d) : d;
}

// Collect the form; returns null and shows an error when incomplete
function collectDo() {
  if (!$('#doDate').val()) {
    toastr.error('Please select DO date.', 'Validation Error:');
    return null;
  }

  var suppliers = [];
  var error = null;

  $('#doSuppliers .do-supplier').each(function() {
    var $card = $(this);
    var supplierId = $card.find('.do-supplier-select').val();
    if (!supplierId) {
      error = 'Please select supplier for all cards.';
      return false;
    }

    var lines = [];
    $card.find('.do-line').each(function() {
      var $row = $(this);
      var line = {
        category: $row.find('.do-category').val(),
        product: $row.find('.do-product').val(),
        grade_id: $row.find('.do-grade').val(),
        grade: $row.find('.do-grade option:selected').text(),
        pairs: linePairs($row)
      };
      if (!line.product || !line.grade_id || !hasWeight(line.pairs)) {
        error = 'Please fill product, grade and weight for all lines.';
        return false;
      }
      lines.push(line);
    });
    if (error) return false;

    if (lines.length === 0) {
      error = 'Each supplier needs at least one line.';
      return false;
    }

    suppliers.push({ card: $card, id: $card.data('wid') || '', serial: $card.data('serial') || '', supplier: supplierId, lines: lines });
  });

  if (error) {
    toastr.error(error, 'Validation Error:');
    return null;
  }

  if (suppliers.length === 0) {
    toastr.error('Please add at least one supplier.', 'Validation Error:');
    return null;
  }

  return suppliers;
}

function supplierCurrency(supplierId) {
  var supplier = findSupplier(supplierId);
  return (supplier && supplier.currency) || doOptions.defaultCurrency || '';
}

// Purchase prices per supplier + product + grade, same lookup as the wholesales page
function loadPrices(suppliers) {
  var prices = {};
  var requests = [];

  if (doAllowPrice !== 'Y') return $.Deferred().resolve(prices).promise();

  suppliers.forEach(function(s) {
    var currency = supplierCurrency(s.supplier);
    s.lines.forEach(function(line) {
      var key = s.supplier + '_' + line.product + '_' + line.grade_id;
      if (prices[key] || !currency) return;
      prices[key] = { pricingType: '', price: 0 };
      requests.push($.post('php/modules/products/getProduct.php', {
        userID: line.product, status: 'RECEIVING', customerID: s.supplier, grade: line.grade_id, currency: currency, type: 'getPrice'
      }).done(function(data) {
        var obj = parseJson(data);
        if (obj.status === 'success') prices[key] = obj.message;
      }));
    });
  });

  return $.when.apply($, requests).then(function() { return prices; }, function() { return prices; });
}

// One item per product + grade line, with its DO / received pairs, for the delivery_order_items table
function buildDoItems(suppliers) {
  var items = [];
  suppliers.forEach(function(s) {
    s.lines.forEach(function(line) {
      items.push({ supplier: s.supplier, category: line.category, product: line.product, grade_id: line.grade_id, pairs: line.pairs });
    });
  });
  return items;
}

function buildWholesalePost(s, header, prices) {
  var currency = supplierCurrency(s.supplier);
  var weightDetails = [];

  s.lines.forEach(function(line) {
    var product = findProduct(line.product);
    var price = prices[s.supplier + '_' + line.product + '_' + line.grade_id] || { pricingType: '', price: 0 };
    var unitPrice = parseFloat(price.price) || 0;

    line.pairs.forEach(function(pair) {
      var w = parseFloat(pair.received);
      if (!(w > 0)) return;
      var total = (price.pricingType == 'Float') ? unitPrice * w : unitPrice;
      weightDetails.push({
        gross: w, tare: 0, pretare: '0.00', net: w, reject: '0.00', isRejected: 'NO',
        product: line.product, product_name: product ? product.product_name : '', product_desc: product ? product.product_name : '',
        grade_id: line.grade_id, grade: line.grade, unit: 'Kg', currency: currency,
        price: unitPrice, fixedfloat: price.pricingType || '', before_discount: total.toFixed(2), discount: 0, discount_type: 'fixed', total: total.toFixed(2),
        isedit: 'N', time: ''
      });
    });
  });

  return {
    id: s.id,
    serialNo: s.serial,
    status: 'RECEIVING',
    recordType: 'wholesales',
    startTime: header.date + ' 00:00',
    endTime: header.date + ' 00:00',
    supplier: s.supplier,
    vehicle: '-',
    doPoNo: header.doNo,
    doId: header.id,
    category: s.lines[0].category,
    weightDetails: weightDetails
  };
}

// Run request factories one after another; each one always resolves
function runInOrder(factories) {
  return factories.reduce(function(chain, factory) {
    return chain.then(factory);
  }, $.Deferred().resolve().promise());
}

function postAndCollect(url, data, label, errors, onSuccess) {
  return function() {
    var done = $.Deferred();
    $.post(url, data)
      .done(function(res) {
        var obj = parseJson(res);
        if (obj.status !== 'success') {
          errors.push(label + ': ' + obj.message);
        } else if (onSuccess) {
          onSuccess(obj);
        }
      })
      .fail(function() { errors.push(label + ': request failed'); })
      .always(function() { done.resolve(); });
    return done.promise();
  };
}

function saveDo() {
  var suppliers = collectDo();
  if (!suppliers) return;

  $('#spinnerLoading').show();
  $.post('php/modules/deliveryOrder/api.php', {
    action: 'save',
    id: $('#doId').val(),
    do_no: $('#doNo').val(),
    do_date: $('#doDate').val(),
    remark: $('#doRemark').val(),
    items: JSON.stringify(buildDoItems(suppliers))
  }).done(function(obj) {
    if (obj.status !== 'success') {
      $('#spinnerLoading').hide();
      toastr.error(obj.message, 'Failed:');
      return;
    }

    var header = { id: obj.data.id, doNo: obj.data.do_no, date: $('#doDate').val() };
    $('#doId').val(header.id);
    $('#doNo').val(header.doNo);

    loadPrices(suppliers).then(function(prices) {
      var errors = [];
      suppliers.forEach(function(s) {
        if (!hasReceived(s) && s.id) doRemovedIds.push(s.id);
      });
      var tasks = suppliers.filter(hasReceived).map(function(s) {
        var supplier = findSupplier(s.supplier);
        return postAndCollect('php/modules/wholesales/wholesales.php', buildWholesalePost(s, header, prices), supplier ? supplier.supplier_name : s.supplier, errors, function(res) {
          // New rows keep their id so a retry after a part failure updates instead of duplicating
          if (res.id) {
            s.card.data('wid', res.id);
            s.card.data('serial', res.serialNo);
          }
        });
      });
      doRemovedIds.slice().forEach(function(id) {
        tasks.push(postAndCollect('php/modules/wholesales/deleteWholesale.php', { id: id, cancelReason: 'Removed from DO' }, 'Remove', errors, function() {
          doRemovedIds.splice(doRemovedIds.indexOf(id), 1);
        }));
      });

      runInOrder(tasks).then(function() {
        $('#spinnerLoading').hide();
        doListTable.ajax.reload(null, false);
        if (errors.length) {
          toastr.error(errors.join('<br>'), 'Failed:');
          return;
        }
        toastr.success('Saved ' + header.doNo, 'Success:');
        $('#doModal').modal('hide');
      });
    });
  }).fail(function() {
    $('#spinnerLoading').hide();
    toastr.error('Failed to save delivery order.', 'Error:');
  });
}

// ============================================================================
// EDIT / DELETE
// ============================================================================
function editDo(id) {
  $('#spinnerLoading').show();
  loadDoOptions(function() {
    $.post('php/modules/deliveryOrder/api.php', { action: 'get', id: id }).done(function(obj) {
      $('#spinnerLoading').hide();
      if (obj.status !== 'success') {
        toastr.error(obj.message, 'Failed:');
        return;
      }

      var d = obj.data;
      resetDoModal(doText.edit);
      $('#doId').val(d.id);
      $('#doDate').val(d.do_date_display);
      $('#doNo').val(d.do_no);
      $('#doRemark').val(d.remark || '');

      var cards = {};
      d.suppliers.forEach(function(w) {
        cards[w.supplier] = addSupplierCard(w.supplier, w.id, w.serial_no);
      });
      d.items.forEach(function(item) {
        var $card = cards[item.supplier] || (cards[item.supplier] = addSupplierCard(item.supplier));
        addLine($card, { category: item.category, product: item.product, grade_id: item.grade_id, pairs: item.weight_details });
      });

      $('#doModal').modal('show');
    }).fail(function() {
      $('#spinnerLoading').hide();
      toastr.error('Failed to load delivery order.', 'Error:');
    });
  });
}

function deleteDo(id) {
  if (!confirm(doText.deleteConfirm)) return;

  $('#spinnerLoading').show();
  $.post('php/modules/deliveryOrder/api.php', { action: 'get', id: id }).done(function(obj) {
    if (obj.status !== 'success') {
      $('#spinnerLoading').hide();
      toastr.error(obj.message, 'Failed:');
      return;
    }

    var errors = [];
    var tasks = obj.data.suppliers.map(function(w) {
      return postAndCollect('php/modules/wholesales/deleteWholesale.php', { id: w.id, cancelReason: 'DO deleted' }, w.serial_no, errors);
    });

    runInOrder(tasks).then(function() {
      if (errors.length) {
        $('#spinnerLoading').hide();
        toastr.error(errors.join('<br>'), 'Failed:');
        doListTable.ajax.reload(null, false);
        return;
      }
      $.post('php/modules/deliveryOrder/api.php', { action: 'delete', id: id }).done(function(res) {
        if (res.status === 'success') {
          toastr.success(res.message, 'Success:');
        } else {
          toastr.error(res.message, 'Failed:');
        }
        doListTable.ajax.reload(null, false);
      }).always(function() { $('#spinnerLoading').hide(); });
    });
  }).fail(function() {
    $('#spinnerLoading').hide();
    toastr.error('Failed to delete delivery order.', 'Error:');
  });
}

// ============================================================================
// EXCEL UPLOAD - fills the modal, user checks then saves
// ============================================================================
function onExcelSelected() {
  var file = this.files[0];
  if (!file) return;

  var reader = new FileReader();
  reader.onload = function(e) {
    var workbook = XLSX.read(e.target.result, { type: 'binary' });
    var sheet = workbook.Sheets[workbook.SheetNames[0]];
    var rows = XLSX.utils.sheet_to_json(sheet, { header: 1, raw: true, defval: '' });
    fillFromExcel(parseDoSheet(rows));
  };
  reader.readAsBinaryString(file);
}

function cellText(v) {
  return String(v === null || v === undefined ? '' : v).trim();
}

function isNumberCell(v) {
  return cellText(v) !== '' && !isNaN(Number(v));
}

// Excel date serial or dd.mm.yyyy / dd/mm/yyyy text to DD/MM/YYYY
function excelDate(v) {
  if (typeof v === 'number') {
    var p = XLSX.SSF.parse_date_code(v);
    return p ? moment([p.y, p.m - 1, p.d]).format('DD/MM/YYYY') : '';
  }
  var m = moment(cellText(v), ['DD.MM.YYYY', 'D.M.YYYY', 'DD/MM/YYYY', 'D/M/YYYY'], true);
  return m.isValid() ? m.format('DD/MM/YYYY') : '';
}

// Split on the first " - " (or "-"): "D197 - CC KP" -> ["D197", "CC KP"]
function splitCode(text) {
  var idx = text.indexOf(' - ');
  var len = 3;
  if (idx < 0) {
    idx = text.indexOf('-');
    len = 1;
  }
  return idx < 0 ? [text.trim(), ''] : [text.substring(0, idx).trim(), text.substring(idx + len).trim()];
}

function parseDoSheet(rows) {
  var result = { date: '', doNo: '', blocks: [] };
  var block = null;
  var columns = null;
  var state = 'supplier';

  rows.forEach(function(row) {
    var cells = row.map(cellText);
    var filled = cells.filter(function(c) { return c !== ''; });

    // DATE: / DO NO: labels, value in the same cell after the colon or in the next filled cell
    var isLabelRow = false;
    cells.forEach(function(c, i) {
      var key = c.toUpperCase().replace(/\s+/g, '');
      var label = key.indexOf('DATE:') === 0 ? 'date' : (key.indexOf('DONO:') === 0 ? 'doNo' : null);
      if (!label) return;
      isLabelRow = true;
      var rawValue = c.substring(c.indexOf(':') + 1).trim();
      if (rawValue === '') {
        for (var j = i + 1; j < row.length; j++) {
          if (cellText(row[j]) !== '') { rawValue = row[j]; break; }
        }
      }
      result[label] = label === 'date' ? excelDate(rawValue) : cellText(rawValue);
    });
    if (isLabelRow) return;

    if (filled.length === 0) {
      state = 'supplier';
      return;
    }

    var hasText = filled.some(function(c) { return isNaN(Number(c)); });

    // A single text cell outside the header step starts a supplier block
    if (state !== 'header' && filled.length === 1 && hasText) {
      block = { supplier: splitCode(filled[0])[0], supplierText: filled[0], lines: [] };
      result.blocks.push(block);
      columns = {};
      state = 'header';
      return;
    }

    if (!block) return;

    if (state === 'header') {
      cells.forEach(function(c, i) {
        if (c === '') return;
        var parts = splitCode(c);
        columns[i] = { productCode: parts[0], gradeName: parts[1], text: c, weights: [] };
        block.lines.push(columns[i]);
      });
      state = 'weights';
      return;
    }

    // Rows with a text label (e.g. TOTAL) end the block
    if (hasText) {
      state = 'supplier';
      return;
    }

    row.forEach(function(v, i) {
      if (columns[i] && isNumberCell(v)) columns[i].weights.push(parseFloat(v));
    });
  });

  return result;
}

function fillFromExcel(parsed) {
  if (parsed.blocks.length === 0) {
    toastr.error('No supplier found in the Excel file.', 'Failed:');
    return;
  }

  var warnings = [];
  var upper = function(v) { return cellText(v).toUpperCase(); };

  $('#doSuppliers .do-supplier').each(function() {
    if ($(this).data('wid')) doRemovedIds.push($(this).data('wid'));
  });
  $('#doSuppliers').html('');
  if (parsed.date) $('#doDate').val(parsed.date);
  if (parsed.doNo) $('#doNo').val(parsed.doNo);

  parsed.blocks.forEach(function(block) {
    var supplier = doOptions.suppliers.find(function(s) {
      return upper(s.supplier_code) === upper(block.supplier) || upper(s.supplier_name) === upper(block.supplierText);
    });
    if (!supplier) warnings.push('Supplier ' + block.supplierText);

    var $card = addSupplierCard(supplier ? supplier.id : null);
    block.lines.forEach(function(line) {
      var product = doOptions.products.find(function(p) {
        return upper(p.product_code) === upper(line.productCode) || upper(p.product_name) === upper(line.productCode);
      });
      var grade = product ? product.grades.find(function(g) { return upper(g.grade_name) === upper(line.gradeName); }) : null;
      if (!product || !grade) warnings.push(line.text + ' (' + block.supplierText + ')');

      addLine($card, {
        category: product ? product.category : null,
        product: product ? product.id : null,
        grade_id: grade ? grade.grade_id : null,
        pairs: line.weights.map(function(w) { return { do: w, received: '' }; })
      });
    });
  });

  updateDoTotals();
  if (warnings.length) {
    toastr.warning('Not matched, please select: <br>' + warnings.join('<br>'), 'Check:');
  }
}
</script>
