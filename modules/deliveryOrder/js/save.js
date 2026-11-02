/* ============================================================
   save.js - SAVE: collecting and saving the DO and its wholesales
   ============================================================ */

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

  var $pane = $el.closest('.tab-pane');
  if ($pane.length) {
    $('#doSupplierTabs a[data-target="#' + $pane.attr('id') + '"]').tab('show');
  }

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
    doInvalid($('#doSupplierPicker'), doText.errNoSupplier);
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
