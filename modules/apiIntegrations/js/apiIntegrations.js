var apiIntegrationApi = 'php/modules/integrations/api.php';

$(function() {
  loadApiIntegrations();

  $('#apiIntegrationTable').on('click', '.apiLoginEditBtn', function() {
    var c = $(this).data('login');
    $('#apiLoginTitle').text(c.name + ' - ' + apiTypeLabels[c.auth_type]);
    $('#apiLoginHint').text(c.secret_hint || '-');
    showApiLoginFields(c);
    $('#apiLoginSaveBtn').data('connector', c.id);
    $('#apiLoginModal').modal('show');
  });

  $('#apiIntegrationTable').on('click', '.apiLoginRemoveBtn', function() {
    var c = $(this).data('login');
    if (!confirm(apiText.confirmRemove)) {
      return;
    }
    $.post(apiIntegrationApi, { action: 'deleteCredential', connector_id: c.id }).done(function(res) {
      if (res.status === 'success') {
        toastr.success(apiText.removeDone, 'Success:');
        loadApiIntegrations();
      } else {
        toastr.error(res.message, 'Failed:');
      }
    });
  });

  // Secrets are cleared as soon as the modal closes
  $('#apiLoginModal').on('hidden.bs.modal', function() {
    $('#apiLoginFields').empty();
  });

  $('#apiLoginForm').on('submit', function(e) {
    e.preventDefault();
    var $btn = $('#apiLoginSaveBtn').prop('disabled', true);
    var fields = {};
    $('#apiLoginFields .apiLoginInput').each(function() {
      fields[$(this).attr('name')] = $.trim($(this).val());
    });

    $.post(apiIntegrationApi, {
      action: 'saveCredential', connector_id: $btn.data('connector'), fields: fields
    }).done(function(res) {
      if (res.status === 'success') {
        toastr.success(apiText.saveDone, 'Success:');
        $('#apiLoginModal').modal('hide');
        loadApiIntegrations();
      } else {
        toastr.error(res.message, 'Failed:');
      }
    }).always(function() {
      $btn.prop('disabled', false);
    });
  });
});

// One row per connection the vendor opened to customers
function loadApiIntegrations() {
  $.post(apiIntegrationApi, { action: 'setup' }).done(function(obj) {
    var $tbody = $('#apiIntegrationTable').empty();
    if (obj.status !== 'success') {
      toastr.error(obj.message, 'Failed:');
      return;
    }

    var rows = obj.data || [];
    if (!rows.length) {
      $tbody.append($('<tr>').append($('<td colspan="4">').html(apiEmptyState)));
      return;
    }

    rows.forEach(function(c) {
      var $action = $('<span class="text-muted small">').text(apiText.notAvailable);
      if (c.auth_type) {
        var editTitle = c.secret_hint ? apiText.changeLogin : apiText.setLogin;
        $action = $('<div class="d-flex justify-content-end" style="gap:4px;">').append(
          $('<button type="button" class="btn btn-sm btn-outline-primary apiLoginEditBtn">')
            .attr('title', editTitle)
            .append($('<i class="fas fa-key">'))
            .data('login', c)
        );
        if (c.secret_hint) {
          $action.append($('<button type="button" class="btn btn-sm btn-outline-danger apiLoginRemoveBtn">')
            .attr('title', apiText.remove)
            .append($('<i class="fas fa-trash">'))
            .data('login', c));
        }
      }

      var $status = c.secret_hint
        ? $('<span class="badge badge-success">').text(apiText.saved + ' ' + c.secret_hint)
        : $('<span class="badge badge-secondary">').text(apiText.notSet);

      $tbody.append($('<tr>').append(
        $('<td class="font-weight-bold">').text(c.name),
        $('<td>').text(c.auth_type ? apiTypeLabels[c.auth_type] : apiText.notAvailable),
        $('<td>').append($status),
        $('<td class="text-right">').append($action)
      ));
    });
  });
}

// Draws one box per field the vendor's login type needs
function showApiLoginFields(c) {
  var $box = $('#apiLoginFields').empty();
  c.fields.forEach(function(f) {
    var label = apiFieldLabels[f.name] || f.name;
    var $input = $('<input class="form-control apiLoginInput" autocomplete="off">')
      .attr('type', f.secret ? 'password' : 'text')
      .attr('name', f.name)
      .attr('placeholder', f.secret && c.secret_hint ? apiText.keepSecret : '');
    if (!f.secret) {
      $input.val(c.username);
    }
    $box.append($('<div class="form-group">').append(
      $('<label class="form-label-modern">').text(label),
      $input
    ));
  });
}
