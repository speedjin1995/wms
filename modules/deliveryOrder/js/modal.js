/* ============================================================
   modal.js - MODAL: building the DO modal, suppliers, lines, bags and totals
   ============================================================ */

$(function() {
  $('#doSupplierPicker').select2({
    width: '100%',
    dropdownAutoWidth: true,
    dropdownParent: $('#doModal'),
    placeholder: doText.addSupplier
  });

  $('#doSupplierPicker').on('select2:select', function(e) {
    var id = e.params.data.id;
    var $existing = $('#doSuppliers .do-supplier').filter(function() {
      return $(this).find('.do-supplier-select').val() == id;
    }).first();

    if ($existing.length) {
      toastr.warning(doText.supplierExists);
      $('#doSupplierTabs a[data-target="#' + $existing.attr('id') + '"]').tab('show');
    } else {
      addLine(addSupplierCard(id));
    }

    $(this).val(null).trigger('change.select2');
  });

  $('#doSuppliers')
    .on('click', '.do-ask-remove-supplier', function() {
      $(this).closest('.do-supplier').find('.do-remove-confirm').show();
    })
    .on('click', '.do-keep-supplier', function() {
      $(this).closest('.do-remove-confirm').hide();
    })
    .on('click', '.do-remove-supplier', function() {
      removeSupplierCard($(this).closest('.do-supplier'));
    });

  $('#doSuppliers')
    .on('change', '.do-supplier-select', function() {
      var supplierId = $(this).val();
      if (!supplierId) {
        return;
      }

      setSupplierTab($(this).closest('.do-supplier'), supplierId);
      $(this).prop('disabled', true).trigger('change.select2').next('.select2').hide();
      syncSupplierPicker();
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
});

// ============================================================================
// MODAL BUILDING
// ============================================================================
function loadDoOptions(callback) {
  $.post('php/modules/deliveryOrder/api.php', { action: 'options' }).done(function(obj) {
    if (obj.status === 'success') {
      doOptions = obj.data;
      fillSupplierPicker();
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
  $('#doSuppliers, #doSupplierTabs').html('');
  syncSupplierPicker();
  doRemovedIds = [];
  updateDoTotals();
}

function openDoModal() {
  loadDoOptions(function() {
    resetDoModal(doText.new);
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

function supplierLabel(s) {
  return (s.supplier_code ? s.supplier_code + ' - ' : '') + s.supplier_name;
}

function fillSupplierPicker() {
  var $picker = $('#doSupplierPicker').empty().append('<option></option>');

  doOptions.suppliers.forEach(function(s) {
    $picker.append($('<option>').val(s.id).text(supplierLabel(s)));
  });

  $picker.trigger('change.select2');
}

// Picker stays empty; picking a supplier already on a tab jumps to that tab instead
function syncSupplierPicker() {
  $('#doSupplierPicker').val(null).trigger('change.select2');
}

// Tab shows the supplier name; '?' until an unmatched scan/Excel supplier is picked
function setSupplierTab($card, supplierId) {
  var supplier = findSupplier(supplierId);
  $('#doSupplierTabs a[data-target="#' + $card.attr('id') + '"] .do-tab-label').text(supplier ? supplier.supplier_name : '?');
  $card.find('.do-supplier-code').text(supplier ? (supplier.supplier_code || supplier.supplier_name) : '?');
}

function removeSupplierCard($card) {
  var $tab = $('#doSupplierTabs a[data-target="#' + $card.attr('id') + '"]');
  var wasActive = $tab.hasClass('active');

  if ($card.data('wid')) {
    doRemovedIds.push($card.data('wid'));
  }

  $tab.parent().remove();
  $card.remove();

  if (wasActive) {
    $('#doSupplierTabs a:first').tab('show');
  }

  updateDoTotals();
}

function addSupplierCard(supplierId, wholesaleId, serialNo) {
  // trim: jQuery only treats the string as HTML when it starts with "<"
  var $card = $($.trim($('#doSupplierTemplate').html()));
  var paneId = 'doSupplier' + (++doSupplierSeq);
  $card.attr('id', paneId);

  if (wholesaleId) {
    $card.data('wid', wholesaleId);
    $card.data('serial', serialNo);
  }

  var supplierOptions = optionList(doOptions.suppliers, 'id', supplierLabel);
  var $select = $card.find('.do-supplier-select').append(supplierOptions);

  $('#doSuppliers').append($card);
  $('#doSupplierTabs').append($('<li class="nav-item">').append(
    $('<a class="nav-link" data-toggle="tab" href="#">').attr('data-target', '#' + paneId).append(
      $('<span class="do-tab-label">'),
      $('<span class="do-tab-count">')
    )
  ));
  initSelect2($select);

  if (supplierId) {
    $select.val(supplierId).prop('disabled', true).trigger('change.select2');
    $select.next('.select2').hide();
  }

  setSupplierTab($card, supplierId);
  $('#doSupplierTabs a[data-target="#' + paneId + '"]').tab('show');

  return $card;
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
    '<div class="do-bag do-bag-row">' +
      '<div class="input-group input-group-sm" data-label="' + doText.doWeight + '"><input type="number" step="0.01" class="form-control do-bag-do"><div class="input-group-append"><span class="input-group-text">kg</span></div></div>' +
      '<div class="input-group input-group-sm" data-label="' + doText.received + '"><input type="number" step="0.01" class="form-control do-bag-received" placeholder="' + doText.notWeighed + '"><div class="input-group-append"><span class="input-group-text">kg</span></div></div>' +
      '<div class="do-bag-loss" data-label="' + doText.loss + '"></div>' +
      '<div class="text-center"><button type="button" class="btn btn-link text-muted btn-sm do-remove-bag" title="' + doText.deleteEntry + '" aria-label="' + doText.deleteEntry + '"><i class="fas fa-times"></i></button></div>' +
    '</div>'
  );

  if (pair.do != null) {
    $bag.find('.do-bag-do').val(pair.do);
  }

  if (pair.received != null) {
    $bag.find('.do-bag-received').val(pair.received);
  }

  $row.find('.do-bags').append($bag);
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

// Allowed difference in kg for one entry: the product's over (+) or short (-) tolerance, or a % of the entry's DO weight
function doTolerance(product, doWeight, short) {
  if (!product) {
    return DO_WEIGHT_TOLERANCE;
  }

  var tolerance = parseFloat(short ? product.do_weight_tolerance_minus : product.do_weight_tolerance) || 0;
  if (product.do_weight_tolerance_type === 'PERCENT') {
    return doWeight * tolerance / 100;
  }

  return tolerance;
}

// Loss = DO - received, only when both are filled; flagged when at least the product's tolerance off in either direction
function updateDoTotals() {
  $('#doSuppliers .do-supplier').each(function() {
    var count = $(this).find('.do-line').length;
    $('#doSupplierTabs a[data-target="#' + this.id + '"] .do-tab-count').text(count);
    $(this).find('.do-item-count').text(count);
  });

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
      var overTolerance = doTolerance(product, doW, false);
      var shortTolerance = doTolerance(product, doW, true);
      var over = complete && loss < 0 && -loss >= overTolerance;
      var short = complete && loss > 0 && loss >= shortTolerance;

      var flagClass = '';
      if (over) {
        flagClass = 'text-primary font-weight-bold';
      } else if (short) {
        flagClass = 'text-danger font-weight-bold';
      }

      var pct = complete && doW > 0 ? '<small class="d-block text-muted">' + (loss / doW * 100).toFixed(2) + '%</small>' : '';
      $bag.find('.do-bag-loss')
        .html(complete ? loss.toFixed(2) + ' kg' + pct : '—')
        .removeClass(DO_FLAG_CLASSES)
        .addClass(flagClass);
    });
  });
}
