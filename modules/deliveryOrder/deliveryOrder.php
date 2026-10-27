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
$userAllowAdd = $_SESSION['userAllowAdd'] ?? 'N';
$userAllowEdit = $_SESSION['userAllowEdit'] ?? 'N';
$userAllowDelete = $_SESSION['userAllowDelete'] ?? 'N';
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
          <div class="filter-group filter-group-action ml-auto">
            <label class="filter-label">&nbsp;</label>
            <div class="d-flex">
              <button type="button" class="btn btn-filter btn-filter-primary" id="loadDoListBtn">
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
          <h3 class="results-title"><i class="fas fa-list"></i> <?=$t('delivery_order_code', 'Delivery Order')?></h3>
        </div>
        <div class="results-header-right d-flex">
          <?php if($userAllowAdd == 'Y'){ ?>
          <button type="button" class="btn btn-action btn-action-primary" id="newDoBtn">
            <i class="fas fa-plus"></i> <?=$t('add_new_code', 'Add New')?>
          </button>
          <?php } ?>
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
              <th><?=$t('actions_code', 'Actions')?></th>
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
              <h6 class="section-title"><span class="badge badge-pill badge-primary mr-2">1</span><?=$t('do_order_details_code', 'Order Details')?></h6>
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
                    <label class="form-label-modern"><?=$t('do_no_code', 'DO No')?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="doNo">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group-modern">
                    <label class="form-label-modern"><?=$t('remark_code', 'Remark')?></label>
                    <textarea class="form-control" id="doRemark" rows="1" placeholder="<?=$t('enter_remark_code', 'Enter remark')?>"></textarea>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-section">
              <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <h6 class="section-title mb-0"><span class="badge badge-pill badge-primary mr-2">2</span><?=$t('do_suppliers_items_code', 'Suppliers & Items')?></h6>
                <div class="d-flex align-items-center">
                  <button type="button" class="btn btn-modern btn-modern-warning btn-sm mr-2" id="doTemplateBtn"><i class="fas fa-download mr-1"></i><?=$t('download_template_code', 'Download Template')?></button>
                  <button type="button" class="btn btn-modern btn-modern-success btn-sm" id="doUploadBtn"><i class="fas fa-file-excel mr-1"></i><?=$t('upload_excel_code', 'Upload Excel')?></button>
                </div>
                <input type="file" id="doExcelFile" accept=".xlsx,.xls" class="d-none">
              </div>
              <div id="doSuppliers" class="do-suppliers"></div>
              <button type="button" class="btn btn-modern btn-modern-primary btn-block" id="doAddSupplierBtn"><i class="fas fa-plus mr-1"></i><?=$t('add_supplier_code', 'Add Supplier')?></button>
            </div>
          </div>
          <div class="modal-footer">
            <span class="mr-auto text-warning font-weight-bold" id="doUnsavedBadge" style="display:none;"><i class="fas fa-circle fa-xs mr-1"></i><?=$t('do_unsaved_code', 'Unsaved changes')?></span>
            <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$t('close_code', 'Close')?></button>
            <button type="button" class="btn btn-modern btn-modern-primary" id="saveDoBtn"><i class="fas fa-save mr-1"></i><?=$t('save_code', 'Save')?></button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Supplier Card Template -->
<script type="text/html" id="doSupplierTemplate">
  <div class="cs-card do-supplier mb-3">
    <div class="cs-card-header">
      <span class="cs-card-number"></span>
      <select class="form-control form-control-sm do-supplier-select"></select>
      <button type="button" class="btn btn-modern btn-modern-danger btn-sm text-nowrap do-remove-supplier">
        <i class="fas fa-trash mr-1"></i><?=$t('do_remove_supplier_code', 'Remove supplier')?>
      </button>
    </div>
    <div class="p-2">
      <div class="table-responsive">
        <table class="table table-sm table-stack mb-2 do-items">
          <colgroup><col><col><col><col><col></colgroup>
          <thead class="thead-light">
            <tr>
              <th><?=$t('category_code', 'Category')?></th>
              <th><?=$t('product_code', 'Product')?></th>
              <th><?=$t('grade_code', 'Grade')?></th>
              <th class="p-0">
                <table class="do-bag-grid">
                  <colgroup><col><col><col><col></colgroup>
                  <tbody>
                    <tr>
                      <th><?=$t('do_weight_code', 'DO Weight')?></th>
                      <th><?=$t('received_weight_code', 'Received Weight')?></th>
                      <th><?=$t('weight_loss_code', 'Weight Loss')?></th>
                      <th></th>
                    </tr>
                  </tbody>
                </table>
              </th>
              <th><?=$t('actions_code', 'Actions')?></th>
            </tr>
          </thead>
          <tbody class="do-lines"></tbody>
        </table>
      </div>
      <button type="button" class="btn btn-modern btn-modern-primary btn-sm do-add-line">
        <i class="fas fa-plus mr-1"></i><?=$t('do_add_item_code', 'Add item')?>
      </button>
    </div>
  </div>
</script>

<!-- Item Row Template -->
<script type="text/html" id="doLineTemplate">
  <tr class="do-line">
    <td data-label="<?=$t('category_code', 'Category')?>"><select class="form-control form-control-sm do-category"></select></td>
    <td data-label="<?=$t('product_code', 'Product')?>"><select class="form-control form-control-sm do-product"></select></td>
    <td data-label="<?=$t('grade_code', 'Grade')?>"><select class="form-control form-control-sm do-grade"></select></td>
    <td class="p-0">
      <table class="do-bag-grid do-bags">
        <!-- Same columns as the weight header so they line up (widths in modal-global.css) -->
        <colgroup><col><col><col><col></colgroup>
        <tbody></tbody>
        <tfoot>
          <tr>
            <td colspan="2">
              <button type="button" class="btn btn-modern btn-modern-secondary btn-sm btn-block do-add-bag">
                <i class="fas fa-plus mr-1"></i><?=$t('do_add_weight_entry_code', 'Add weight entry')?>
              </button>
            </td>
            <td colspan="2"></td>
          </tr>
        </tfoot>
      </table>
    </td>
    <td class="text-center">
      <button type="button" class="btn btn-modern btn-modern-danger btn-sm do-remove-line">
        <i class="fas fa-trash mr-1"></i><?=$t('do_remove_item_code', 'Remove item')?>
      </button>
    </td>
  </tr>
</script>

<script>
var doListTable = null;
var doOptions = {
  suppliers: [],
  categories: [],
  products: [],
  defaultCurrency: null
};
var doRemovedIds = [];
var doSaved = '';
// Used until a product is picked; each product has its own do_weight_tolerance
var DO_WEIGHT_TOLERANCE = 0.8;
var DO_FLAG_CLASSES = 'text-primary text-danger font-weight-bold';
var doAllowPrice = '<?=$doAllowPrice?>';
var doAllowEdit = '<?=$userAllowEdit?>';
var doAllowDelete = '<?=$userAllowDelete?>';
var doText = <?=json_encode([
  'doWeight' => $t('do_weight_code', 'DO Weight'),
  'received' => $t('received_weight_code', 'Received Weight'),
  'loss' => $t('weight_loss_code', 'Weight Loss'),
  'edit' => $t('edit_code', 'Edit'),
  'new' => $t('new_code', 'New'),
  'delete' => $t('delete_code', 'Delete'),
  'deleteEntry' => $t('do_delete_entry_code', 'Delete entry'),
  'removeLineConfirm' => $t('do_remove_item_confirm_code', 'Remove this item and its weight entries?'),
  'removeSupplierConfirm' => $t('do_remove_supplier_confirm_code', 'Remove this supplier and all its items?'),
  'notWeighed' => $t('do_not_weighed_code', 'Not weighed yet'),
  'unsavedConfirm' => $t('do_unsaved_confirm_code', 'You have unsaved changes. Close without saving?'),
  'deleteConfirm' => $t('do_delete_confirm_code', 'Delete this delivery order? Its wholesales records will be deleted and stock reversed.'),
  'errDate' => $t('do_err_date_code', 'Please select DO date.'),
  'errDoNo' => $t('do_err_do_no_code', 'Please enter DO No.'),
  'errSupplier' => $t('do_err_supplier_code', 'Please select supplier for all cards.'),
  'errLine' => $t('do_err_line_code', 'Please fill product, grade and weight for all lines.'),
  'errNoLine' => $t('do_err_no_line_code', 'Each supplier needs at least one line.'),
  'errNoSupplier' => $t('do_err_no_supplier_code', 'Please add at least one supplier.')
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;

$(function() {
  $('#doDateFromPicker, #doDateToPicker, #doDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: new Date()
  });

  $('#loadDoListBtn').on('click', function() {
    doListTable.ajax.reload();
  });

  $('#newDoBtn').on('click', function() {
    openDoModal();
  });

  $('#doAddSupplierBtn').on('click', function() {
    addLine(addSupplierCard());
  });

  $('#saveDoBtn').on('click', saveDo);

  $('#doTemplateBtn').on('click', function() {
    window.location.href = 'php/modules/deliveryOrder/api.php?action=template';
  });

  $('#doUploadBtn').on('click', function() {
    $('#doExcelFile').val('').click();
  });

  $('#doExcelFile').on('change', onExcelSelected);

  $('#doSuppliers')
    .on('click', '.do-remove-supplier', function() {
      if (!confirm(doText.removeSupplierConfirm)) {
        return;
      }

      var $card = $(this).closest('.do-supplier');
      if ($card.data('wid')) {
        doRemovedIds.push($card.data('wid'));
      }

      $card.remove();
      numberDoSuppliers();
      updateDoTotals();
    })
    .on('click', '.do-add-line', function() {
      addLine($(this).closest('.do-supplier'));
    })
    .on('click', '.do-remove-line', function() {
      if (!confirm(doText.removeLineConfirm)) {
        return;
      }

      $(this).closest('.do-line').remove();
      updateDoTotals();
    })
    .on('change', '.do-category', function() {
      var $row = $(this).closest('.do-line');
      fillProducts($row, $(this).val());
      fillGrades($row, null);
      updateDoTotals();
    })
    .on('change', '.do-product', function() {
      fillGrades($(this).closest('.do-line'), $(this).val());
      updateDoTotals();
    })
    .on('input', '.do-bag-do, .do-bag-received', updateDoTotals)
    .on('click', '.do-add-bag', function() {
      addBag($(this).closest('.do-line'));
    })
    .on('click', '.do-remove-bag', function() {
      $(this).closest('.do-bag').remove();
      updateDoTotals();
    });

  $('#doModal')
    .on('input change', '.is-invalid', function() {
      $(this).removeClass('is-invalid');
    })
    .on('input change click', function() {
      $('#doUnsavedBadge').toggle(isDoDirty());
    })
    .on('hide.bs.modal', function(e) {
      if (e.target !== this) {
        return;
      }

      if (isDoDirty() && !confirm(doText.unsavedConfirm)) {
        e.preventDefault();
      }
    });

  $('#doListTable')
    .on('click', '.edit-do-btn', function() {
      editDo($(this).data('id'));
    })
    .on('click', '.delete-do-btn', function() {
      deleteDo($(this).data('id'));
    });

  loadDoList();
});

// ============================================================================
// LIST
// ============================================================================
function loadDoList() {
  var weightColumn = function(d) {
    return (parseFloat(d) || 0).toFixed(2);
  };

  doListTable = $('#doListTable').DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: false,
    order: [],
    ajax: {
      url: 'php/modules/deliveryOrder/api.php',
      type: 'POST',
      data: function(d) {
        d.action = 'list';
        d.date_from = $('#doDateFrom').val() || '';
        d.date_to = $('#doDateTo').val() || '';
      },
      dataSrc: function(json) {
        return json.status === 'success' ? json.data : [];
      }
    },
    columns: [
      { data: 'do_no' },
      { data: 'do_date_display' },
      { data: 'supplier_count', className: 'text-center' },
      { data: 'do_weight', className: 'text-right', render: weightColumn },
      { data: 'received_weight', className: 'text-right', render: weightColumn },
      {
        data: null,
        className: 'text-right',
        render: function(d) {
          var doWeight = parseFloat(d.do_weight) || 0;
          var receivedWeight = parseFloat(d.received_weight) || 0;
          return (doWeight - receivedWeight).toFixed(2);
        }
      },
      { data: 'created_by_name', defaultContent: '-' },
      {
        data: null,
        orderable: false,
        className: 'action-button',
        width: '120px',
        render: function(d) {
          var html = '<div class="expanded-actions">';

          if (doAllowEdit == 'Y') {
            html += '<button type="button" class="btn btn-sm btn-outline-primary edit-do-btn" data-id="' + d.id + '" title="' + doText.edit + '"><i class="fas fa-pen"></i></button>';
          }

          if (doAllowDelete == 'Y') {
            html += '<button type="button" class="btn btn-sm btn-outline-danger delete-do-btn" data-id="' + d.id + '" title="' + doText.delete + '"><i class="fas fa-trash"></i></button>';
          }

          return html + '</div>';
        }
      }
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
    markDoSaved();
    $('#doModal').modal('show');
  });
}

// Every field value in the modal; adding or removing a row changes it too
function doSnapshot() {
  var values = $('#doModal')
    .find('input, select, textarea')
    .not('.select2-search__field')
    .map(function() {
      return $(this).val();
    })
    .get();

  return JSON.stringify(values);
}

function isDoDirty() {
  return doSnapshot() !== doSaved;
}

function markDoSaved() {
  doSaved = doSnapshot();
  $('#doUnsavedBadge').hide();
}

function findProduct(id) {
  return doOptions.products.find(function(p) {
    return p.id == id;
  });
}

function findSupplier(id) {
  return doOptions.suppliers.find(function(s) {
    return s.id == id;
  });
}

function optionList(items, valueKey, textFn) {
  var $opts = [$('<option value="">-</option>')];

  items.forEach(function(item) {
    $opts.push($('<option>').val(item[valueKey]).text(textFn(item)));
  });

  return $opts;
}

function initSelect2($el) {
  $el.select2({
    width: '100%',
    dropdownParent: $('#doModal')
  });
}

function addSupplierCard(supplierId, wholesaleId, serialNo) {
  // trim: jQuery only treats the string as HTML when it starts with "<"
  var $card = $($.trim($('#doSupplierTemplate').html()));

  if (wholesaleId) {
    $card.data('wid', wholesaleId);
    $card.data('serial', serialNo);
  }

  var supplierOptions = optionList(doOptions.suppliers, 'id', function(s) {
    var code = s.supplier_code ? s.supplier_code + ' - ' : '';
    return code + s.supplier_name;
  });
  var $select = $card.find('.do-supplier-select').append(supplierOptions);

  $('#doSuppliers').append($card);
  numberDoSuppliers();
  initSelect2($select);

  if (supplierId) {
    $select.val(supplierId).trigger('change.select2');
  }

  return $card;
}

// Card numbers follow the on-screen order, same as the customer/supplier cards on the products page
function numberDoSuppliers() {
  $('#doSuppliers .cs-card-number').each(function(i) {
    $(this).text(i + 1);
  });
}

function addLine($card, data) {
  data = data || {};

  var $row = $($.trim($('#doLineTemplate').html()));
  $card.find('.do-lines').append($row);

  var categoryOptions = optionList(doOptions.categories, 'id', function(c) {
    return c.category_name;
  });
  $row.find('.do-category').append(categoryOptions);
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

  var pairs = (data.pairs && data.pairs.length) ? data.pairs : [{}];
  pairs.forEach(function(p) {
    addBag($row, p);
  });

  updateDoTotals();
  return $row;
}

function fillProducts($row, categoryId) {
  var products = [];
  if (categoryId) {
    products = doOptions.products.filter(function(p) {
      return p.category == categoryId;
    });
  }

  var productOptions = optionList(products, 'id', function(p) {
    var code = p.product_code ? p.product_code + ' - ' : '';
    return code + p.product_name;
  });

  $row.find('.do-product').empty().append(productOptions).trigger('change.select2');
}

function fillGrades($row, productId) {
  var product = productId ? findProduct(productId) : null;
  var grades = product ? product.grades : [];

  var gradeOptions = optionList(grades, 'grade_id', function(g) {
    return g.grade_name;
  });

  $row.find('.do-grade').empty().append(gradeOptions).trigger('change.select2');
}

function addBag($row, pair) {
  pair = pair || {};

  var $bag = $(
    '<tr class="do-bag">' +
      '<td data-label="' + doText.doWeight + '"><input type="number" step="0.01" class="form-control form-control-sm do-bag-do"></td>' +
      '<td data-label="' + doText.received + '"><input type="number" step="0.01" class="form-control form-control-sm do-bag-received" placeholder="' + doText.notWeighed + '"></td>' +
      '<td class="text-right do-bag-loss" data-label="' + doText.loss + '"></td>' +
      '<td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm do-remove-bag" title="' + doText.deleteEntry + '" aria-label="' + doText.deleteEntry + '"><i class="fas fa-trash"></i></button></td>' +
    '</tr>'
  );

  if (pair.do != null) {
    $bag.find('.do-bag-do').val(pair.do);
  }

  if (pair.received != null) {
    $bag.find('.do-bag-received').val(pair.received);
  }

  $row.find('.do-bags > tbody').append($bag);
  updateDoTotals();
  return $bag;
}

// Blank inputs stay blank so an unweighed bag is not read as a zero weight
function linePairs($row) {
  return $row.find('.do-bag').map(function() {
    return {
      do: $(this).find('.do-bag-do').val(),
      received: $(this).find('.do-bag-received').val()
    };
  }).get();
}

function hasWeight(pairs) {
  return pairs.some(function(p) {
    return parseFloat(p.do) > 0 || parseFloat(p.received) > 0;
  });
}

function hasReceived(s) {
  return s.lines.some(function(line) {
    return line.pairs.some(function(p) {
      return parseFloat(p.received) > 0;
    });
  });
}

// Allowed difference in kg for one entry: the product's tolerance in kg, or a % of the entry's DO weight
function doTolerance(product, doWeight) {
  if (!product) {
    return DO_WEIGHT_TOLERANCE;
  }

  var tolerance = parseFloat(product.do_weight_tolerance) || 0;
  if (product.do_weight_tolerance_type === 'PERCENT') {
    return doWeight * tolerance / 100;
  }

  return tolerance;
}

// Loss = DO - received, only when both are filled; flagged when at least the product's tolerance off in either direction
function updateDoTotals() {
  $('#doSuppliers .do-line').each(function() {
    var $line = $(this);
    var product = findProduct($line.find('.do-product').val());

    $line.find('.do-bag').each(function() {
      var $bag = $(this);
      var doVal = $bag.find('.do-bag-do').val();
      var recVal = $bag.find('.do-bag-received').val();
      var doW = parseFloat(doVal) || 0;
      var recW = parseFloat(recVal) || 0;
      var complete = doVal !== '' && recVal !== '';
      var loss = complete ? Math.round((doW - recW) * 100) / 100 : 0;
      var tolerance = doTolerance(product, doW);
      var over = complete && -loss >= tolerance;
      var short = complete && loss >= tolerance;

      var flagClass = '';
      if (over) {
        flagClass = 'text-primary font-weight-bold';
      } else if (short) {
        flagClass = 'text-danger font-weight-bold';
      }

      $bag.find('.do-bag-loss').toggleClass('table-info', over).toggleClass('table-danger', short);
      $bag.find('.do-bag-loss')
        .text(complete ? loss.toFixed(2) : '')
        .removeClass(DO_FLAG_CLASSES)
        .addClass(flagClass);
    });
  });
}

// ============================================================================
// SAVE
// ============================================================================
function parseJson(d) {
  return typeof d === 'string' ? JSON.parse(d) : d;
}

// Marks the field, scrolls to it and shows the message; returns false so it can end an .each loop
function doInvalid($el, message) {
  var $target = $el.is('select') ? $el.next('.select2') : $el;
  var $body = $('#doModal .modal-body');

  $el.addClass('is-invalid');

  if ($target.length) {
    var scrollTop = $body.scrollTop() + $target.offset().top - $body.offset().top - 100;
    $body.animate({ scrollTop: scrollTop }, 200);
  }

  toastr.error(message, 'Validation Error:');
  return false;
}

// Collect the form as suppliers (for the wholesales) and items (for delivery_order_items); null when incomplete
function collectDo() {
  if (!$('#doDate').val()) {
    doInvalid($('#doDate'), doText.errDate);
    return null;
  }

  if (!$.trim($('#doNo').val())) {
    doInvalid($('#doNo'), doText.errDoNo);
    return null;
  }

  var suppliers = [];
  var items = [];
  var valid = true;

  $('#doSuppliers .do-supplier').each(function() {
    var $card = $(this);
    var $select = $card.find('.do-supplier-select');
    var supplierId = $select.val();

    if (!supplierId) {
      valid = doInvalid($select, doText.errSupplier);
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

      var $missing = null;
      if (!line.product) {
        $missing = $row.find('.do-product');
      } else if (!line.grade_id) {
        $missing = $row.find('.do-grade');
      } else if (!hasWeight(line.pairs)) {
        $missing = $row.find('.do-bag-do, .do-add-bag').first();
      }

      if ($missing) {
        valid = doInvalid($missing, doText.errLine);
        return false;
      }

      lines.push(line);
      items.push({
        supplier: supplierId,
        category: line.category,
        product: line.product,
        grade_id: line.grade_id,
        pairs: line.pairs
      });
    });

    if (!valid) {
      return false;
    }

    if (lines.length === 0) {
      valid = doInvalid($card.find('.do-add-line'), doText.errNoLine);
      return false;
    }

    suppliers.push({
      card: $card,
      id: $card.data('wid') || '',
      serial: $card.data('serial') || '',
      supplier: supplierId,
      lines: lines
    });
  });

  if (!valid) {
    return null;
  }

  if (suppliers.length === 0) {
    doInvalid($('#doAddSupplierBtn'), doText.errNoSupplier);
    return null;
  }

  return {
    suppliers: suppliers,
    items: items
  };
}

function supplierCurrency(supplierId) {
  var supplier = findSupplier(supplierId);
  return (supplier && supplier.currency) || doOptions.defaultCurrency || '';
}

// Purchase prices per supplier + product + grade, same lookup as the wholesales page
function loadPrices(suppliers) {
  var prices = {};
  var requests = [];

  if (doAllowPrice !== 'Y') {
    return $.Deferred().resolve(prices).promise();
  }

  suppliers.forEach(function(s) {
    var currency = supplierCurrency(s.supplier);

    s.lines.forEach(function(line) {
      var key = s.supplier + '_' + line.product + '_' + line.grade_id;
      if (prices[key] || !currency) {
        return;
      }

      prices[key] = { pricingType: '', price: 0 };

      var request = $.post('php/modules/products/getProduct.php', {
        userID: line.product,
        status: 'RECEIVING',
        customerID: s.supplier,
        grade: line.grade_id,
        currency: currency,
        type: 'getPrice'
      }).done(function(data) {
        var obj = parseJson(data);
        if (obj.status === 'success') {
          prices[key] = obj.message;
        }
      });

      requests.push(request);
    });
  });

  var returnPrices = function() {
    return prices;
  };

  return $.when.apply($, requests).then(returnPrices, returnPrices);
}

function buildWholesalePost(s, header, prices) {
  var currency = supplierCurrency(s.supplier);
  var weightDetails = [];

  s.lines.forEach(function(line) {
    var product = findProduct(line.product);
    var productName = product ? product.product_name : '';
    var price = prices[s.supplier + '_' + line.product + '_' + line.grade_id] || { pricingType: '', price: 0 };
    var unitPrice = parseFloat(price.price) || 0;

    line.pairs.forEach(function(pair) {
      var w = parseFloat(pair.received);
      if (!(w > 0)) {
        return;
      }

      var total = (price.pricingType == 'Float') ? unitPrice * w : unitPrice;

      weightDetails.push({
        gross: w,
        tare: 0,
        pretare: '0.00',
        net: w,
        reject: '0.00',
        isRejected: 'NO',
        product: line.product,
        product_name: productName,
        product_desc: productName,
        grade_id: line.grade_id,
        grade: line.grade,
        unit: 'Kg',
        currency: currency,
        price: unitPrice,
        fixedfloat: price.pricingType || '',
        before_discount: total.toFixed(2),
        discount: 0,
        discount_type: 'fixed',
        total: total.toFixed(2),
        isedit: 'N',
        time: ''
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

// Posts the requests one after another; failures are collected as "label: message" and the chain always resolves
function postInOrder(requests, errors) {
  return requests.reduce(function(chain, req) {
    return chain.then(function() {
      var done = $.Deferred();

      $.post(req.url, req.data)
        .done(function(res) {
          var obj = parseJson(res);
          if (obj.status !== 'success') {
            errors.push(req.label + ': ' + obj.message);
          } else if (req.onSuccess) {
            req.onSuccess(obj);
          }
        })
        .fail(function() {
          errors.push(req.label + ': request failed');
        })
        .always(function() {
          done.resolve();
        });

      return done.promise();
    });
  }, $.Deferred().resolve().promise());
}

// One wholesale per supplier with received weight, then remove the dropped ones; resolves with the error list
function saveWholesales(suppliers, header, prices) {
  var errors = [];

  suppliers.forEach(function(s) {
    if (!hasReceived(s) && s.id) {
      doRemovedIds.push(s.id);
    }
  });

  var requests = suppliers.filter(hasReceived).map(function(s) {
    var supplier = findSupplier(s.supplier);

    return {
      url: 'php/modules/wholesales/wholesales.php',
      data: buildWholesalePost(s, header, prices),
      label: supplier ? supplier.supplier_name : s.supplier,
      onSuccess: function(res) {
        // New rows keep their id so a retry after a part failure updates instead of duplicating
        if (res.id) {
          s.card.data('wid', res.id);
          s.card.data('serial', res.serialNo);
        }
      }
    };
  });

  doRemovedIds.slice().forEach(function(id) {
    requests.push({
      url: 'php/modules/wholesales/deleteWholesale.php',
      data: { id: id, cancelReason: 'Removed from DO' },
      label: 'Remove',
      onSuccess: function() {
        doRemovedIds.splice(doRemovedIds.indexOf(id), 1);
      }
    });
  });

  return postInOrder(requests, errors).then(function() {
    return errors;
  });
}

function saveDo() {
  var form = collectDo();
  if (!form) {
    return;
  }

  $('#spinnerLoading').show();

  $.post('php/modules/deliveryOrder/api.php', {
    action: 'save',
    id: $('#doId').val(),
    do_no: $('#doNo').val(),
    do_date: $('#doDate').val(),
    remark: $('#doRemark').val(),
    items: JSON.stringify(form.items)
  }).done(function(obj) {
    if (obj.status !== 'success') {
      $('#spinnerLoading').hide();
      toastr.error(obj.message, 'Failed:');
      return;
    }

    var header = {
      id: obj.data.id,
      doNo: obj.data.do_no,
      date: $('#doDate').val()
    };
    $('#doId').val(header.id);
    $('#doNo').val(header.doNo);

    loadPrices(form.suppliers).then(function(prices) {
      return saveWholesales(form.suppliers, header, prices);
    }).then(function(errors) {
      $('#spinnerLoading').hide();
      doListTable.ajax.reload(null, false);

      if (errors.length) {
        toastr.error(errors.join('<br>'), 'Failed:');
        return;
      }

      markDoSaved();
      toastr.success('Saved ' + header.doNo, 'Success:');
      $('#doModal').modal('hide');
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
        if (!cards[item.supplier]) {
          cards[item.supplier] = addSupplierCard(item.supplier);
        }

        addLine(cards[item.supplier], {
          category: item.category,
          product: item.product,
          grade_id: item.grade_id,
          pairs: item.weight_details
        });
      });

      markDoSaved();
      $('#doModal').modal('show');
    }).fail(function() {
      $('#spinnerLoading').hide();
      toastr.error('Failed to load delivery order.', 'Error:');
    });
  });
}

function deleteDo(id) {
  if (!confirm(doText.deleteConfirm)) {
    return;
  }

  $('#spinnerLoading').show();

  $.post('php/modules/deliveryOrder/api.php', { action: 'get', id: id }).done(function(obj) {
    if (obj.status !== 'success') {
      $('#spinnerLoading').hide();
      toastr.error(obj.message, 'Failed:');
      return;
    }

    var errors = [];
    var requests = obj.data.suppliers.map(function(w) {
      return {
        url: 'php/modules/wholesales/deleteWholesale.php',
        data: { id: w.id, cancelReason: 'DO deleted' },
        label: w.serial_no
      };
    });

    postInOrder(requests, errors).then(function() {
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
      }).always(function() {
        $('#spinnerLoading').hide();
      });
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
  if (!file) {
    return;
  }

  var formData = new FormData();
  formData.append('action', 'import');
  formData.append('file', file);

  $('#spinnerLoading').show();

  $.ajax({
    url: 'php/modules/deliveryOrder/api.php',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false
  }).done(function(obj) {
    if (obj.status !== 'success') {
      toastr.error(obj.message, 'Failed:');
      return;
    }

    fillFromExcel(obj.data);
  }).fail(function() {
    toastr.error('Failed to read the Excel file.', 'Error:');
  }).always(function() {
    $('#spinnerLoading').hide();
  });
}

function fillFromExcel(d) {
  $('#doSuppliers .do-supplier').each(function() {
    if ($(this).data('wid')) {
      doRemovedIds.push($(this).data('wid'));
    }
  });

  $('#doSuppliers').html('');

  if (d.date) {
    $('#doDate').val(d.date);
  }

  if (d.doNo) {
    $('#doNo').val(d.doNo);
  }

  d.suppliers.forEach(function(s) {
    var $card = addSupplierCard(s.supplier);

    s.lines.forEach(function(line) {
      addLine($card, line);
    });
  });

  updateDoTotals();

  if (d.warnings.length) {
    toastr.warning('Not matched, please select: <br>' + d.warnings.join('<br>'), 'Check:');
  }
}
</script>
