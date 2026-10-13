// Stock balance report / stock adjustment page. Requires `stockBalanceText`.

// ============================================================================
// 1. VARIABLES
// ============================================================================
var adjListTable = null;
var adjItemRowCount = 0;
var productsData = [];
var currentAdjType = 'Local';

// ============================================================================
// 2. DOCUMENT READY
// ============================================================================
$(document).ready(function() {
  initCommon();
  initReportTab();
  initAdjustmentTab();
});

function initCommon() {
  $('.select2').select2({ allowClear: true, placeholder: 'Please Select' });
}

function initReportTab() {
  $('#datePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: new Date()
  });

  $('#datePicker').on('change.datetimepicker', loadPreview);
  $('#refreshBtn').on('click', loadPreview);
  $('#exportBtn').on('click', exportReport);
  $('#categoryFilter').on('change', filterProductsByCategory);

  loadPreview();
}

function initAdjustmentTab() {
  $('#adjDateFromPicker, #adjDateToPicker, #adjDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: new Date()
  });

  // Button events
  $('#loadAdjListBtn').on('click', loadAdjustmentList);
  $('#newAdjBtn').on('click', function() { openAdjustmentModal(); });
  $('#addAdjItemBtn').on('click', function() { addAdjustmentItemRow(); });
  $('#saveAdjBtn').on('click', saveAdjustment);

  // Tab shown event
  $('a[href="#paneAdjustment"]').on('shown.bs.tab', function() {
    if (!adjListTable) loadAdjustmentList();
  });

  // Item row events
  $('#adjItemsBody')
    .on('click', '.remove-adj-item', onRemoveItem)
    .on('change', '.adj-product-select', onProductChange)
    .on('change', '.adj-grade-select', onGradeChange)
    .on('input', '.adj-adjust-qty', onAdjustQtyChange)
    .on('input', '.adj-unit-cost', onUnitCostChange);

  // Type change event
  $('#adjType').on('change', onTypeChange);

  loadProductsData('Local');
}

// ============================================================================
// REPORT TAB FUNCTIONS
// ============================================================================
function buildReportUrl() {
  var date = $('#date').val();
  var category = $('#categoryFilter').val() || '';
  var location = $('#locationFilter').val() || '';
  var product = $('#productFilter').val() || '';
  var type = $('#typeFilter').val() || '';
  return 'php/modules/wholesales/api.php?action=exportStockBalance&asAtDate=' + encodeURIComponent(date) + '&category=' + category + '&location=' + location + '&product=' + product + '&type=' + encodeURIComponent(type);
}

function loadPreview() {
  var date = $('#date').val();
  if (!date) {
    toastr.error('Please select a date.', 'Validation Error:');
    return;
  }
  $('#previewFrame').attr('src', buildReportUrl());
}

function exportReport() {
  var date = $('#date').val();
  if (!date) {
    toastr.error('Please select a date.', 'Validation Error:');
    return;
  }
  window.open(buildReportUrl());
}

function filterProductsByCategory() {
  var selectedCategory = $(this).val();
  var $productSelect = $('#productFilter');
  var currentVal = $productSelect.val();

  $productSelect.select2('destroy');

  if (!$productSelect.data('original-options')) {
    $productSelect.data('original-options', $productSelect.html());
  }
  $productSelect.html($productSelect.data('original-options'));

  if (selectedCategory) {
    $productSelect.find('option').each(function() {
      if ($(this).val() && $(this).data('category') != selectedCategory) {
        $(this).remove();
      }
    });
  }

  var newVal = (currentVal && $productSelect.find('option[value="' + currentVal + '"]').length) ? currentVal : '';
  $productSelect.val(newVal).select2({ allowClear: true, placeholder: 'Please Select' });
}

// ============================================================================
// ADJUSTMENT TAB - DATA FUNCTIONS
// ============================================================================
function loadProductsData(type, callback) {
  $.post('php/modules/wholesales/stockAdjustment/api.php', { action: 'products', type: type || currentAdjType })
    .done(function(obj) {
      if (obj.status === 'success') {
        productsData = obj.data;
        if (callback) callback();
      }
    });
}

function onTypeChange() {
  var newType = $('#adjType').val();
  if (newType === currentAdjType) return;
  
  currentAdjType = newType;
  
  // Clear existing items and reload products
  $('#adjItemsBody').html('');
  adjItemRowCount = 0;
  toggleAdjItemsEmpty();
  updateAdjustmentTotals();
  
  loadProductsData(newType);
}

function loadAdjustmentList() {
  if (adjListTable) {
    adjListTable.clear().destroy();
    adjListTable = null;
  }

  adjListTable = $('#adjustListTable').DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: false,
    language: {
      emptyTable: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title">' + stockBalanceText.noRecordsFound + '</div><div class="empty-message">' + stockBalanceText.noRecordsMessage + '</div></div>'
    },
    ajax: {
      url: 'php/modules/wholesales/stockAdjustment/api.php',
      type: 'POST',
      data: {
        action: 'list',
        date_from: $('#adjDateFrom').val() || '',
        date_to: $('#adjDateTo').val() || ''
      },
      dataSrc: function(json) {
        return json.status === 'success' ? json.data : [];
      }
    },
    columns: [
      { data: 'adjustment_no' },
      { data: 'adjustment_date_display' },
      { data: 'total_items', className: 'text-center' },
      { data: 'total_qty', className: 'text-right', render: renderSignedNumber },
      { data: 'total_cost', className: 'text-right', render: renderSignedNumber },
      { data: 'created_by_name', defaultContent: '-' },
      { data: null, orderable: false, className: 'action-button', render: renderActionButtons }
    ]
  });

  bindTableEvents();
}

function bindTableEvents() {
  $('#adjustListTable tbody').off('click', 'tr').on('click', 'tr', onRowClick);
  $('#adjustListTable').off('click', '.print-adj-btn').on('click', '.print-adj-btn', function(e) {
    e.stopPropagation();
    printAdjustment($(this).data('id'));
  });
  $('#adjustListTable').off('click', '.edit-adj-btn').on('click', '.edit-adj-btn', function(e) {
    e.stopPropagation();
    editAdjustment($(this).data('id'));
  });
  $('#adjustListTable').off('click', '.delete-adj-btn').on('click', '.delete-adj-btn', function(e) {
    e.stopPropagation();
    deleteAdjustment($(this).data('id'));
  });
}

function onRowClick(e) {
  if ($(e.target).closest('.action-button').length || $(e.target).is('button') || $(e.target).is('i')) return;

  var tr = $(this);
  var row = adjListTable.row(tr);

  if (row.child.isShown()) {
    row.child.hide();
    tr.removeClass('shown');
  } else {
    var rowData = row.data();
    if (!rowData) return;

    $.post('php/modules/wholesales/stockAdjustment/api.php', { action: 'get', id: rowData.id })
      .done(function(obj) {
        if (obj.status === 'success') {
          row.child(format(obj.data)).show();
          tr.addClass('shown');
        }
      });
  }
}

// ============================================================================
// ADJUSTMENT TAB - RENDER FUNCTIONS
// ============================================================================
function renderSignedNumber(d) {
  var v = parseFloat(d) || 0;
  return (v >= 0 ? '+' : '') + v.toFixed(2);
}

function renderActionButtons(d) {
  return '<div class="expanded-actions">' +
    '<button type="button" class="btn btn-sm btn-outline-secondary print-adj-btn" data-id="' + d.id + '"><i class="fas fa-print"></i></button>' +
    '<button type="button" class="btn btn-sm btn-outline-primary edit-adj-btn" data-id="' + d.id + '"><i class="fas fa-pen"></i></button>' +
    '<button type="button" class="btn btn-sm btn-outline-danger delete-adj-btn" data-id="' + d.id + '"><i class="fas fa-trash"></i></button>' +
    '</div>';
}

// ============================================================================
// ADJUSTMENT TAB - EXPANDABLE ROW FORMAT
// ============================================================================
function format(d) {
  var totalQty = parseFloat(d.total_qty) || 0;
  var totalCost = parseFloat(d.total_cost) || 0;
  var totalQtyDisplay = (totalQty >= 0 ? '+' : '') + totalQty.toFixed(2);
  var totalCostDisplay = (totalCost >= 0 ? '+' : '') + totalCost.toFixed(2);

  var html = `
  <div class="expanded-row-content">
    <!-- Header -->
    <div class="expanded-header">
      <div>
        <div class="expanded-header-title">${d.adjustment_no}</div>
        <div class="expanded-header-subtitle">${d.adjustment_date_display}</div>
      </div>
      <div class="expanded-actions">
        <button type="button" onclick="printAdjustment(${d.id})" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i></button>
        <button type="button" onclick="editAdjustment(${d.id})" class="btn btn-sm btn-outline-primary"><i class="fas fa-pen"></i></button>
        <button type="button" onclick="deleteAdjustment(${d.id})" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
      </div>
    </div>

    <!-- KPI Summary -->
    <div class="kpi-row">
      <div class="kpi-card">
        <div class="kpi-label">${stockBalanceText.totalItems}</div>
        <div class="kpi-value">${d.total_items || 0}</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">${stockBalanceText.totalQty}</div>
        <div class="kpi-value ${totalQty >= 0 ? 'kpi-value-primary' : 'kpi-value-danger'}">${totalQtyDisplay}</div>
      </div>
      <div class="kpi-card ${totalCost >= 0 ? 'kpi-card-success' : 'kpi-card-danger'}">
        <div class="kpi-label">${stockBalanceText.totalCost}</div>
        <div class="kpi-value">${totalCostDisplay}</div>
      </div>
    </div>

    <!-- Adjustment Info -->
    <div class="info-section">
      <div class="info-section-title">${stockBalanceText.adjustmentInfo}</div>
      <div class="info-grid">
        <div><span class="info-item-label">${stockBalanceText.adjustmentNo}</span><span class="info-item-value">${d.adjustment_no || '-'}</span></div>
        <div><span class="info-item-label">${stockBalanceText.date}</span><span class="info-item-value">${d.adjustment_date_display || '-'}</span></div>
        <div><span class="info-item-label">${stockBalanceText.createdBy}</span><span class="info-item-value">${d.created_by_name || '-'}</span></div>
      </div>
      ${d.remark ? '<div class="info-remark"><span class="info-item-label">' + stockBalanceText.remark + '</span><span class="info-item-value">' + d.remark + '</span></div>' : ''}
    </div>

    <!-- Adjustment Items -->
    <div class="details-section">
      <div class="details-header">
        <span class="details-title">${stockBalanceText.adjustmentItems}</span>
      </div>
      <div class="table-responsive">
        <table class="table details-table mb-0">
          <thead>
            <tr>
              <th>${stockBalanceText.product}</th>
              <th>${stockBalanceText.grade}</th>
              <th class="text-right">${stockBalanceText.qtyBefore}</th>
              <th class="text-right">${stockBalanceText.adjustment}</th>
              <th class="text-right">${stockBalanceText.qtyAfter}</th>
              <th class="text-right">${stockBalanceText.unitCost}</th>
              <th class="text-right">${stockBalanceText.totalCost}</th>
              <th>${stockBalanceText.reason}</th>
            </tr>
          </thead>
          <tbody>`;

  var footerTotalQty = 0;
  var footerTotalCost = 0;

  for (var i = 0; i < d.items.length; i++) {
    var item = d.items[i];
    var adjQty = parseFloat(item.adjustment_qty) || 0;
    var itemCost = parseFloat(item.total_cost) || 0;

    footerTotalQty += adjQty;
    footerTotalCost += itemCost;

    html += `
            <tr>
              <td>${item.product_code ? item.product_code + ' - ' : ''}${item.product_name}</td>
              <td><span class="grade-badge">${item.grade_name || '-'}</span></td>
              <td class="text-right text-mono">${parseFloat(item.quantity_before).toFixed(2)}</td>
              <td class="text-right text-mono ${adjQty >= 0 ? 'text-success' : 'text-danger'} font-weight-bold">${(adjQty >= 0 ? '+' : '') + adjQty.toFixed(2)}</td>
              <td class="text-right text-mono">${parseFloat(item.quantity_after).toFixed(2)}</td>
              <td class="text-right text-mono">${parseFloat(item.unit_cost).toFixed(2)}</td>
              <td class="text-right text-mono ${itemCost >= 0 ? 'text-success' : 'text-danger'} font-weight-bold">${(itemCost >= 0 ? '+' : '') + itemCost.toFixed(2)}</td>
              <td>${item.reason || '-'}</td>
            </tr>`;
  }

  html += `
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3">${stockBalanceText.total}</td>
              <td class="text-right text-mono ${footerTotalQty >= 0 ? 'text-success' : 'text-danger'} font-weight-bold">${(footerTotalQty >= 0 ? '+' : '') + footerTotalQty.toFixed(2)}</td>
              <td></td>
              <td></td>
              <td class="text-right text-mono ${footerTotalCost >= 0 ? 'text-success' : 'text-danger'} font-weight-bold">${(footerTotalCost >= 0 ? '+' : '') + footerTotalCost.toFixed(2)}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>`;

  return html;
}

// ============================================================================
// ADJUSTMENT TAB - MODAL FUNCTIONS
// ============================================================================
function openAdjustmentModal(id) {
  $('#adjId').val(id || '');
  $('#adjModalTitle').text(id ? stockBalanceText.edit : stockBalanceText.new);
  $('#adjType').val('Local');
  currentAdjType = 'Local';
  $('#adjDate').val(moment().format('DD/MM/YYYY'));
  $('#adjRemark').val('');
  $('#adjItemsBody').html('');
  $('#adjTotalQty, #adjTotalCost').text('0.00');
  adjItemRowCount = 0;
  toggleAdjItemsEmpty();
  loadProductsData('Local', function() {
    $('#adjModal').modal('show');
  });
}

function addAdjustmentItemRow(data, callback) {
  var idx = adjItemRowCount++;
  var $template = $('#adjItemRowTemplate').clone();
  $('#adjItemsBody').append($template.html());

  var $row = $('#adjItemsBody').find('.adj-item-row:last');
  $row.attr('data-idx', idx);

  if (data && data.id) {
    $row.attr('data-item-id', data.id);
  }

  // Build product options
  var productOptions = '<option value="">-</option>';
  productsData.forEach(function(p) {
    productOptions += '<option value="' + p.id + '">' + (p.product_code ? p.product_code + ' - ' : '') + p.product_name + '</option>';
  });

  // Set unique IDs
  $row.find('#adjProduct').attr('id', 'adjProduct' + idx).html(productOptions);
  $row.find('#adjGrade').attr('id', 'adjGrade' + idx);
  $row.find('#adjCurrentQty').attr('id', 'adjCurrentQty' + idx);
  $row.find('#adjAdjustQty').attr('id', 'adjAdjustQty' + idx);
  $row.find('#adjNewQty').attr('id', 'adjNewQty' + idx);
  $row.find('#adjUnitCost').attr('id', 'adjUnitCost' + idx);
  $row.find('#adjReason').attr('id', 'adjReason' + idx);

  // Initialize Select2
  $row.find('.adj-product-select, .adj-grade-select').select2({ width: '100%', dropdownParent: $('#adjModal') });

  toggleAdjItemsEmpty();

  // Populate data if editing
  if (data) {
    $row.find('.adj-product-select').val(data.product_id).trigger('change');
    setTimeout(function() {
      $row.find('.adj-grade-select').val(data.grade).trigger('change.select2');
      $row.find('.adj-current-qty').val(parseFloat(data.quantity_before).toFixed(2));
      $row.find('.adj-adjust-qty').val(parseFloat(data.adjustment_qty).toFixed(2));
      $row.find('.adj-new-qty').val(parseFloat(data.quantity_after).toFixed(2));
      $row.find('.adj-unit-cost').val(parseFloat(data.unit_cost).toFixed(2));
      $row.find('.adj-total-cost').text(formatTotalCost(parseFloat(data.adjustment_qty), parseFloat(data.unit_cost)));
      $row.find('.adj-reason').val(data.reason || '');
      if (callback) callback();
    }, 200);
  }
}

function toggleAdjItemsEmpty() {
  var hasRows = $('#adjItemsBody tr').length > 0;
  $('#adjItemsTable').toggle(hasRows);
  $('#adjItemsEmpty').toggle(!hasRows);
}

// ============================================================================
// ADJUSTMENT TAB - ITEM ROW EVENT HANDLERS
// ============================================================================
function onRemoveItem() {
  $(this).closest('tr').remove();
  updateAdjustmentTotals();
  toggleAdjItemsEmpty();
}

function onProductChange() {
  var $row = $(this).closest('tr');
  var productId = $(this).val();
  var $gradeSelect = $row.find('.adj-grade-select');
  var gradeHtml = '<option value="">-</option>';

  if (productId) {
    var product = productsData.find(function(p) { return p.id == productId; });
    if (product && product.grades) {
      product.grades.forEach(function(g) {
        // Grades are already filtered by type from backend
        gradeHtml += '<option value="' + g.grade_id + '" data-cost="' + (g.purchasing_price || 0) + '">' + g.grade_name + '</option>';
      });
    }
  }

  $gradeSelect.html(gradeHtml).trigger('change.select2');
  $row.find('.adj-current-qty, .adj-adjust-qty, .adj-new-qty').val('');
  $row.find('.adj-unit-cost').val('0');
  $row.find('.adj-total-cost').text('0.00');
}

function onGradeChange() {
  var $row = $(this).closest('tr');
  var productId = $row.find('.adj-product-select').val();
  var gradeId = $(this).val();
  var defaultCost = $(this).find('option:selected').data('cost') || 0;

  $row.find('.adj-unit-cost').val(parseFloat(defaultCost).toFixed(2));

  if (productId && gradeId) {
    $.post('php/modules/wholesales/stockAdjustment/api.php', { action: 'balance', product_id: productId, grade: gradeId, type: currentAdjType })
      .done(function(obj) {
        if (obj.status === 'success') {
          $row.find('.adj-current-qty').val(parseFloat(obj.balance).toFixed(2));
          if (obj.unit_cost > 0) {
            $row.find('.adj-unit-cost').val(parseFloat(obj.unit_cost).toFixed(2));
          }
        }
      });
  }
}

function onAdjustQtyChange() {
  var $row = $(this).closest('tr');
  var currentQty = parseFloat($row.find('.adj-current-qty').val()) || 0;
  var adjustQty = parseFloat($(this).val()) || 0;
  $row.find('.adj-new-qty').val((currentQty + adjustQty).toFixed(2));
  updateRowTotalCost($row);
  updateAdjustmentTotals();
}

function onUnitCostChange() {
  updateRowTotalCost($(this).closest('tr'));
  updateAdjustmentTotals();
}

// ============================================================================
// ADJUSTMENT TAB - CALCULATION FUNCTIONS
// ============================================================================
function updateRowTotalCost($row) {
  var adjustQty = parseFloat($row.find('.adj-adjust-qty').val()) || 0;
  var unitCost = parseFloat($row.find('.adj-unit-cost').val()) || 0;
  $row.find('.adj-total-cost').text(formatTotalCost(adjustQty, unitCost));
}

function formatTotalCost(adjustQty, unitCost) {
  var total = adjustQty * unitCost;
  return (total >= 0 ? '+' : '') + total.toFixed(2);
}

function updateAdjustmentTotals() {
  var totalQty = 0;
  var totalCost = 0;

  $('#adjItemsBody tr').each(function() {
    totalQty += parseFloat($(this).find('.adj-adjust-qty').val()) || 0;
    var costText = $(this).find('.adj-total-cost').text().replace('+', '');
    totalCost += parseFloat(costText) || 0;
  });

  $('#adjTotalQty').text((totalQty >= 0 ? '+' : '') + totalQty.toFixed(2));
  $('#adjTotalCost').text((totalCost >= 0 ? '+' : '') + totalCost.toFixed(2));
}

// ============================================================================
// ADJUSTMENT TAB - CRUD FUNCTIONS
// ============================================================================
function printAdjustment(id) {
  window.open('php/modules/wholesales/stockAdjustment/print.php?id=' + id, '_blank');
}

function saveAdjustment() {
  var adjDate = $('#adjDate').val();
  if (!adjDate) {
    toastr.error('Please select adjustment date.', 'Validation Error:');
    return;
  }

  var items = [];
  var hasError = false;

  $('#adjItemsBody tr').each(function() {
    var productId = $(this).find('.adj-product-select').val();
    var grade = $(this).find('.adj-grade-select').val();

    if (!productId || !grade) {
      hasError = true;
      return false;
    }

    items.push({
      id: $(this).data('item-id') || null,
      product_id: productId,
      grade: grade,
      quantity_before: $(this).find('.adj-current-qty').val() || 0,
      adjustment_qty: $(this).find('.adj-adjust-qty').val() || 0,
      quantity_after: $(this).find('.adj-new-qty').val() || 0,
      unit_cost: $(this).find('.adj-unit-cost').val() || 0,
      reason: $(this).find('.adj-reason').val() || ''
    });
  });

  if (hasError) {
    toastr.error('Please select product and grade for all items.', 'Validation Error:');
    return;
  }

  if (items.length === 0) {
    toastr.error('Please add at least one item.', 'Validation Error:');
    return;
  }

  var adjId = $('#adjId').val();
  var action = adjId ? 'update' : 'save';

  $('#spinnerLoading').show();
  $.post('php/modules/wholesales/stockAdjustment/api.php', {
    action: action,
    id: adjId,
    type: $('#adjType').val(),
    adjustment_date: adjDate,
    remark: $('#adjRemark').val(),
    items: JSON.stringify(items)
  })
    .done(function(obj) {
      if (obj.status === 'success') {
        toastr.success(obj.message, 'Success:');
        $('#adjModal').modal('hide');
        adjListTable.ajax.reload(null, false);
      } else {
        toastr.error(obj.message, 'Failed:');
      }
    })
    .fail(function() {
      toastr.error('Failed to save adjustment.', 'Error:');
    })
    .always(function() {
      $('#spinnerLoading').hide();
    });
}

function editAdjustment(id) {
  $('#spinnerLoading').show();
  $.post('php/modules/wholesales/stockAdjustment/api.php', { action: 'get', id: id })
    .done(function(obj) {
      if (obj.status === 'success') {
        var d = obj.data;
        $('#adjId').val(d.id);
        $('#adjModalTitle').text(stockBalanceText.edit);
        $('#adjType').val(d.type || 'Local');
        currentAdjType = d.type || 'Local';
        $('#adjDate').val(d.adjustment_date_display);
        $('#adjRemark').val(d.remark || '');
        $('#adjItemsBody').html('');
        adjItemRowCount = 0;

        // Load products for the type first, then add items
        loadProductsData(currentAdjType, function() {
          var itemsToAdd = d.items.length;
          if (itemsToAdd === 0) {
            toggleAdjItemsEmpty();
            $('#spinnerLoading').hide();
            $('#adjModal').modal('show');
            return;
          }

          var itemsAdded = 0;
          d.items.forEach(function(item) {
            addAdjustmentItemRow(item, function() {
              itemsAdded++;
              if (itemsAdded === itemsToAdd) {
                updateAdjustmentTotals();
                $('#spinnerLoading').hide();
                $('#adjModal').modal('show');
              }
            });
          });
        });
      } else {
        $('#spinnerLoading').hide();
        toastr.error(obj.message, 'Failed:');
      }
    })
    .fail(function() {
      $('#spinnerLoading').hide();
      toastr.error('Failed to load adjustment data.', 'Error:');
    });
}

function deleteAdjustment(id) {
  Swal.fire({
    title: stockBalanceText.confirmDelete,
    text: stockBalanceText.deleteConfirmMessage,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: stockBalanceText.delete
  }).then(function(result) {
    if (result.isConfirmed) {
      $('#spinnerLoading').show();
      $.post('php/modules/wholesales/stockAdjustment/api.php', { action: 'delete', id: id })
        .done(function(obj) {
          if (obj.status === 'success') {
            toastr.success(obj.message, 'Success:');
            adjListTable.ajax.reload(null, false);
          } else {
            toastr.error(obj.message, 'Failed:');
          }
        })
        .fail(function() {
          toastr.error('Failed to delete adjustment.', 'Error:');
        })
        .always(function() {
          $('#spinnerLoading').hide();
        });
    }
  });
}
