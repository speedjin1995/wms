/* ============================================================
   deliveryOrder.js - page state, init, DO list, edit and delete
   ============================================================ */

var doListTable = null;
var doOptions = {
  suppliers: [],
  categories: [],
  products: [],
  defaultCurrency: null
};
var doRemovedIds = [];
var doSupplierSeq = 0;
var doSaved = '';
// Used until a product is picked; each product has its own do_weight_tolerance
var DO_WEIGHT_TOLERANCE = 0;
var DO_FLAG_CLASSES = 'text-primary text-danger font-weight-bold';

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

  $('#saveDoBtn').on('click', saveDo);

  $('#doImportSaveBtn').on('click', saveDoImport);

  $('#doTemplateBtn').on('click', function() {
    window.location.href = 'php/modules/deliveryOrder/api.php?action=template';
  });

  $('#doUploadBtn').on('click', function() {
    $('#doExcelFile').val('').click();
  });

  $('#doExcelFile').on('change', onExcelSelected);

  $('#doScanProviders').on('click', '.do-scan-provider', function() {
    doScanProvider = $(this).data('provider');
    $('#doScanFile').val('').click();
  });

  $('#doScanFile').on('change', onScanSelected);

  loadAiProviders();

  $('#doListTable')
    .on('click', '.edit-do-btn', function() {
      editDo($(this).data('id'));
    })
    .on('click', '.delete-do-btn', function() {
      deleteDo($(this).data('id'));
    });

  loadDoList();

  // Click a row to open or close its weight comparison (same as wholesales.php)
  $('#doListTable tbody')
    .on('click', 'tr', toggleDoDetails)
    .on('change', '.do-filter', function() {
      filterDoDetails($(this).closest('.expanded-row-content'));
    });
});

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
      // Scan replaces all suppliers, which would remove this DO's saved records
      $('#doScanGroup').hide();
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

      syncSupplierPicker();
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
// LIST
// ============================================================================
function loadDoList() {
  var weightColumn = function(d) {
    return (parseFloat(d) || 0).toFixed(2);
  };

  doListTable = $('#doListTable').DataTable({
    // The child row is the weight comparison, so Responsive only hides columns
    responsive: { details: false },
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
      { data: 'do_no', className: 'all' },
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
        className: 'action-button all',
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
    'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title">' + doText.noRecords + '</div><div class="empty-message">' + doText.noRecordsMessage + '</div></div>',
    'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title">' + doText.noMatch + '</div><div class="empty-message">' + doText.noMatchMessage + '</div></div>'
    }
  });
}

// Expand / collapse the DO vs received weight comparison under a DO row
function toggleDoDetails(e) {
  var tr = $(this);
  var row = doListTable.row(tr);

  // Skip the action buttons and clicks inside an open comparison (child rows have no row data)
  if ($(e.target).closest('td').hasClass('action-button') || $(e.target).closest('button').length || !row.data()) {
    return;
  }

  if (row.child.isShown()) {
    row.child.hide();
    tr.removeClass('shown');
    return;
  }

  var show = function() {
    $.post('php/modules/deliveryOrder/api.php', { action: 'get', id: row.data().id }).done(function(obj) {
      if (obj.status === 'failed') {
        toastr.error(obj.message, 'Failed:');
        return;
      }

      row.child(buildDoDetails(row.data(), obj.data), 'do-details-row').show();
      // Select2 needs the selects on the page; it still fires 'change' for the filter handler
      row.child().find('.do-filter').select2({ width: '180px' });
      tr.addClass('shown');
    });
  };

  // Names and tolerances come from the same options the modal uses
  if (doOptions.products.length) {
    show();
  } else {
    loadDoOptions(show);
  }
}

// Same look as the wholesales expanded row: one section per supplier (its wholesale record) listing category, product and grade
// per weight entry; entries flagged with the product's tolerance like updateDoTotals()
function buildDoDetails(d, data) {
  var statuses = {
    matched: ['badge-success', 'fa-check', doText.matched],
    short: ['badge-danger', 'fa-arrow-down', doText.short],
    over: ['badge-info', 'fa-arrow-up', doText.over],
    notWeighed: ['badge-secondary', 'fa-minus', doText.notWeighed]
  };
  var kpi = function(label, valueClass) {
    return $('<div class="kpi-card">').append(
      $('<div class="kpi-label">').text(label),
      $('<div class="kpi-value">').addClass(valueClass).append($('<span>'), ' ', $('<span class="kpi-unit">').text('Kg'))
    );
  };
  // value => label for each filter, in the order first seen
  var choices = { supplier: {}, category: {}, product: {}, grade: {} };
  var sections = {};
  var sectionOrder = [];

  (data.items || []).forEach(function(item) {
    var supplier = findSupplier(item.supplier);
    var category = doOptions.categories.find(function(c) { return c.id == item.category; });
    var product = findProduct(item.product);
    var grade = product ? product.grades.find(function(g) { return g.grade_id == item.grade_id; }) : null;
    var names = {
      supplier: supplier ? supplier.supplier_code + ' - ' + supplier.supplier_name : '-',
      category: category ? category.category_name : '-',
      product: product ? product.product_name : '-',
      grade: grade ? grade.grade_name : '-'
    };

    if (!hasWeight(item.weight_details || [])) {
      return;
    }

    if (!sections[item.supplier]) {
      var wholesale = (data.suppliers || []).find(function(w) { return w.supplier == item.supplier; });
      sections[item.supplier] = $('<tbody>');
      sectionOrder.push({ id: item.supplier, name: names.supplier, serialNo: wholesale ? wholesale.serial_no : '' });
    }

    choices.supplier[item.supplier] = names.supplier;
    choices.category[item.category || ''] = names.category;
    choices.product[item.product] = names.product;
    choices.grade[item.grade_id] = names.grade;

    item.weight_details.forEach(function(p) {
      var doW = parseFloat(p.do) || 0;
      var recW = parseFloat(p.received) || 0;
      var loss = Math.round((doW - recW) * 100) / 100;
      var overTolerance = doTolerance(product, doW, false);
      var shortTolerance = doTolerance(product, doW, true);
      var status = 'matched';
      if (recW <= 0) {
        status = 'notWeighed';
      } else if (loss > 0 && loss >= shortTolerance) {
        status = 'short';
      } else if (loss < 0 && -loss >= overTolerance) {
        status = 'over';
      }
      var s = statuses[status];

      sections[item.supplier].append($('<tr>')
        .attr({ 'data-supplier': item.supplier, 'data-category': item.category || '', 'data-product': item.product, 'data-grade': item.grade_id, 'data-do': doW, 'data-rec': recW })
        .append(
          $('<td>').text(names.category),
          $('<td>').text(names.product),
          $('<td>').append($('<span class="grade-badge">').text(names.grade)),
          $('<td class="text-right text-mono">').text(doW.toFixed(2)),
          $('<td class="text-right text-mono text-primary font-weight-bold">').text(recW > 0 ? recW.toFixed(2) : '-'),
          $('<td class="text-right text-mono">').addClass(status === 'short' ? 'text-danger font-weight-bold' : (status === 'over' ? 'text-info font-weight-bold' : '')).text(recW > 0 ? loss.toFixed(2) : '-'),
          $('<td>').append($('<span class="badge">').addClass(s[0]).append($('<i class="fas mr-1">').addClass(s[1]), $('<span>').text(s[2])))
        ));
    });
  });

  var $filters = $('<div class="details-filters flex-wrap">');
  [
    ['supplier', doText.allSuppliers],
    ['category', doText.allCategories],
    ['product', doText.allProducts],
    ['grade', doText.allGrades]
  ].forEach(function(f) {
    var $select = $('<select class="form-control form-control-sm details-filter-select do-filter">').attr('data-key', f[0])
      .append($('<option value="">').text(f[1]));
    $.each(choices[f[0]], function(value, label) {
      $select.append($('<option>').val(value).text(label));
    });
    $filters.append($select);
  });

  var $wrap = $('<div class="expanded-row-content">').append(
    $('<div class="expanded-header">').append(
      $('<div>').append(
        $('<div class="expanded-header-title">').text(d.do_no),
        $('<div class="expanded-header-subtitle">').text(d.do_date_display + (d.remark ? ' - ' + d.remark : ''))
      ),
      sectionOrder.length ? $filters : ''
    ),
    $('<div class="kpi-row do-totals">').append(
      kpi(doText.doWeight, ''),
      kpi(doText.received, 'kpi-value-primary'),
      kpi(doText.loss, 'kpi-value-danger')
    )
  );

  sectionOrder.forEach(function(sup) {
    $wrap.append($('<div class="details-section do-supplier-section">').append(
      $('<div class="details-header">').append($('<span class="details-title">').text(sup.name + (sup.serialNo ? ' (' + sup.serialNo + ')' : ''))),
      $('<div class="table-responsive">').append($('<table class="table details-table mb-0">').append(
        $('<thead>').append($('<tr>').append(
          $('<th>').text(doText.category),
          $('<th>').text(doText.product),
          $('<th>').text(doText.grade),
          $('<th class="text-right">').text(doText.doWeight),
          $('<th class="text-right">').text(doText.received),
          $('<th class="text-right">').text(doText.loss),
          $('<th>').text(doText.status)
        )),
        sections[sup.id],
        $('<tfoot class="do-totals">').append($('<tr>').append(
          $('<td colspan="3">').text(doText.total),
          $('<td class="text-right text-mono">'),
          $('<td class="text-right text-mono text-primary">'),
          $('<td class="text-right text-mono">'),
          $('<td>')
        ))
      ))
    ));
  });

  $wrap.append($('<div class="details-empty do-no-match">').append(
    $('<i class="fas fa-inbox">'), ' ', $('<span>').text(sectionOrder.length ? doText.noMatch : doText.noEntries)
  ));

  filterDoDetails($wrap);
  return $wrap;
}

// Show only the entries matching every filter, then recount each supplier's total and the KPI cards
function filterDoDetails($wrap) {
  var filters = {};
  $wrap.find('.do-filter').each(function() {
    filters[$(this).attr('data-key')] = $(this).val();
  });

  // KPI cards hold the number in their first span, the footer row in its number cells
  var setTotals = function($el, doTotal, recTotal) {
    var values = [doTotal, recTotal, doTotal - recTotal];
    $el.find('.kpi-value > span:first-child, td.text-mono').each(function(i) {
      $(this).text(values[i].toFixed(2));
    });
  };

  var allDo = 0;
  var allRec = 0;
  var anyShown = false;
  $wrap.find('.do-supplier-section').each(function() {
    var $section = $(this);
    var sectionDo = 0;
    var sectionRec = 0;
    var shown = false;

    $section.find('tbody tr').each(function() {
      var $tr = $(this);
      var show = Object.keys(filters).every(function(key) {
        return !filters[key] || $tr.attr('data-' + key) === filters[key];
      });

      $tr.toggle(show);
      if (show) {
        shown = true;
        sectionDo += parseFloat($tr.attr('data-do'));
        sectionRec += parseFloat($tr.attr('data-rec'));
      }
    });

    $section.toggle(shown);
    setTotals($section.find('tfoot.do-totals'), sectionDo, sectionRec);
    allDo += sectionDo;
    allRec += sectionRec;
    anyShown = anyShown || shown;
  });

  setTotals($wrap.find('.kpi-row.do-totals'), allDo, allRec);
  $wrap.find('.do-no-match').toggle(!anyShown);
}
