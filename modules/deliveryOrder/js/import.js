/* ============================================================
   import.js - EXCEL UPLOAD: preview and save DOs from a file
   ============================================================ */

// ============================================================================
// EXCEL UPLOAD - previews the DOs in the file, then saves the ones with no warnings
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

    showDoImport(obj.data.dos);
  }).fail(function() {
    toastr.error('Failed to read the Excel file.', 'Error:');
  }).always(function() {
    $('#spinnerLoading').hide();
  });
}

// ============================================================================
// PAPER SCAN - the tenant's AI connector reads the photo; the first DO fills the open modal
// ============================================================================
var doScanProvider = '';

function onScanSelected() {
  var file = this.files[0];
  if (!file) {
    return;
  }

  var formData = new FormData();
  formData.append('action', 'scan');
  formData.append('provider', doScanProvider);
  formData.append('file', file);

  $('#spinnerLoading').show();
  $('#doScanBtn').prop('disabled', true);

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

    fillFromExcel(obj.data.dos[0]);
  }).fail(function() {
    toastr.error('Failed to scan the image.', 'Error:');
  }).always(function() {
    $('#spinnerLoading').hide();
    $('#doScanBtn').prop('disabled', false);
  });
}

// AI providers the vendor can set up as connectors; auth decides how the key is sent
var aiProviders = {
  llm_openai: { label: 'OpenAI', auth: 'bearer' },
  llm_claude: { label: 'Claude', auth: 'api_key', header: 'x-api-key' },
  llm_gemini: { label: 'Gemini', auth: 'api_key', header: 'x-goog-api-key' }
};

// Fills the scan dropdown with the AI providers that have a key set (keys are set in Settings > AI Setup)
function loadAiProviders() {
  $.post('php/modules/integrations/api.php', { action: 'list' }).done(function(obj) {
    var $menu = $('#doScanProviders').empty();
    var keyed = (obj.data || []).filter(function(c) { return aiProviders[c.code] && c.secret_hint; });
    if (!keyed.length) {
      $menu.append($('<span class="dropdown-item-text text-muted">').text(doText.aiNotSetUp));
      return;
    }

    keyed.forEach(function(c) {
      $menu.append($('<button type="button" class="dropdown-item do-scan-provider">').text(aiProviders[c.code].label).data('provider', c.code));
    });
  });
}

// Preview of every DO in the upload; a DO with warnings is listed as skipped and not saved
var doImportRows = [];

function showDoImport(dos) {
  doImportRows = dos;
  var $tbody = $('#doImportTable').empty();

  dos.forEach(function(d) {
    var lines = 0;
    var weight = 0;
    d.suppliers.forEach(function(s) {
      s.lines.forEach(function(line) {
        lines++;
        line.pairs.forEach(function(p) {
          weight += parseFloat(p.do) || 0;
        });
      });
    });

    var ok = d.warnings.length === 0;
    $tbody.append($('<tr>').toggleClass('text-muted', !ok).append(
      $('<td>').text(d.doNo),
      $('<td>').text(d.date),
      $('<td class="text-right">').text(d.suppliers.length),
      $('<td class="text-right">').text(lines),
      $('<td class="text-right text-mono">').text(weight.toFixed(2)),
      $('<td>').append(
        ok ? $('<span class="badge badge-success">').text(doText.ready) : $('<span class="badge badge-warning">').text(doText.skipped),
        ok ? '' : $('<div class="small text-danger mt-1">').text(d.warnings.join(', '))
      )
    ));
  });

  $('#doImportCount').text(dos.filter(function(d) { return d.warnings.length === 0; }).length);
  $('#doImportModal').modal('show');
}

// Saves each DO with no warnings through the same save action as the modal, one after another
function saveDoImport() {
  var valid = doImportRows.filter(function(d) { return d.warnings.length === 0; });
  if (!valid.length) {
    return;
  }

  var errors = [];
  var requests = valid.map(function(d) {
    var items = [];
    d.suppliers.forEach(function(s) {
      s.lines.forEach(function(line) {
        items.push({ supplier: s.supplier, product: line.product, grade_id: line.grade_id, pairs: line.pairs });
      });
    });

    return {
      url: 'php/modules/deliveryOrder/api.php',
      data: { action: 'save', id: '', do_no: d.doNo, do_date: d.date, remark: '', items: JSON.stringify(items) },
      label: d.doNo
    };
  });

  $('#spinnerLoading').show();
  $('#doImportSaveBtn').prop('disabled', true);

  postInOrder(requests, errors).then(function() {
    $('#spinnerLoading').hide();
    $('#doImportSaveBtn').prop('disabled', false);
    doListTable.ajax.reload(null, false);

    if (errors.length) {
      toastr.error(errors.join('<br>'), 'Failed:');
    }
    if (errors.length < valid.length) {
      toastr.success('Saved ' + (valid.length - errors.length) + ' of ' + valid.length + ' DOs', 'Success:');
    }
    $('#doImportModal').modal('hide');
  });
}

function fillFromExcel(d) {
  $('#doSuppliers .do-supplier').each(function() {
    if ($(this).data('wid')) {
      doRemovedIds.push($(this).data('wid'));
    }
  });

  $('#doSuppliers, #doSupplierTabs').html('');

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

  syncSupplierPicker();
  updateDoTotals();

  if (d.warnings.length) {
    toastr.warning('Not matched, please select: <br>' + d.warnings.join('<br>'), 'Check:');
  }
}
