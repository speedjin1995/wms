/* ============================================================
   editDelete.js - EDIT / DELETE: open an existing DO and delete one
   ============================================================ */

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
