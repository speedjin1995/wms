/* ============================================================
   deliveryOrder.js - page state and init
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
var DO_WEIGHT_TOLERANCE = 0.8;
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

  $('#doSupplierPicker').select2({
    width: '100%',
    dropdownParent: $('#doModal'),
    placeholder: doText.addSupplier
  });

  $('#doSupplierPicker')
    .on('select2:select', function(e) {
      addLine(addSupplierCard(e.params.data.id));
    })
    .on('select2:unselecting', function(e) {
      if (!confirm(doText.removeSupplierConfirm)) {
        e.preventDefault();
        return;
      }

      $('#doSuppliers .do-supplier').filter(function() {
        return $(this).find('.do-supplier-select').val() == e.params.args.data.id;
      }).each(function() {
        removeSupplierCard($(this));
      });
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

  $('#doScanBtn').on('click', function() {
    $('#doScanFile').val('').click();
  });

  $('#doScanFile').on('change', onScanSelected);

  $('#doSuppliers')
    .on('change', '.do-supplier-select', function() {
      var supplierId = $(this).val();
      if (!supplierId) {
        return;
      }

      setSupplierTab($(this).closest('.do-supplier'), supplierId);
      $(this).prop('disabled', true).trigger('change.select2');
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
