<?php
session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
  exit;
}

if (!in_array($_SESSION['role'] ?? '', ['ADMIN', 'SADMIN'], true)) {
  exit;
}

if (($_SESSION['featureFlags']['include_api_integration'] ?? 'N') != 'Y') {
  exit;
}

$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];
$t = function ($key, $default = '') use ($languageArray, $language) { return $languageArray[$key][$language] ?? $default; };
?>

<div class="content page-modern">
  <div class="container-fluid">

    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title"><i class="fas fa-plug"></i> <?=$t('api_integrations_code', 'API Integrations')?></h1>
    </div>

    <!-- Connections Card -->
    <div class="card results-card">
      <div class="card-header">
        <div class="results-header-left">
          <h3 class="results-title"><i class="fas fa-list"></i> <?=$t('api_connections_code', 'Connections')?></h3>
        </div>
      </div>
      <div class="card-body">
        <p class="text-muted px-3 pt-3 mb-2"><?=$t('api_integrations_desc_code', 'Logins are saved encrypted and are never shown again.')?></p>
        <table class="table data-table mb-0">
          <thead>
            <tr>
              <th><?=$t('api_connection_code', 'Connection')?></th>
              <th><?=$t('api_login_type_code', 'Login type')?></th>
              <th><?=$t('ai_key_status_code', 'Status')?></th>
              <th></th>
            </tr>
          </thead>
          <tbody id="apiIntegrationTable"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Login Modal -->
<div class="modal fade modal-modern" id="apiLoginModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-key mr-2 text-muted"></i><span id="apiLoginTitle"></span></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group-modern">
          <label class="form-label-modern"><?=$t('api_login_saved_code', 'Saved:')?> <span id="apiLoginHint">-</span></label>
        </div>
        <div id="apiLoginFields"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$t('close_code', 'Close')?></button>
        <button type="button" class="btn btn-modern btn-modern-primary" id="apiLoginSaveBtn"><i class="fas fa-save mr-1"></i><?=$t('save_code', 'Save')?></button>
      </div>
    </div>
  </div>
</div>

<script>
var apiText = <?=json_encode([
  'notSet' => $t('ai_key_not_set_code', 'Not set'),
  'notAvailable' => $t('ai_not_available_code', 'Not available'),
  'setLogin' => $t('ai_set_key_code', 'Set login'),
  'changeLogin' => $t('ai_change_key_code', 'Change login'),
  'remove' => $t('remove_code', 'Remove'),
  'confirmRemove' => $t('api_login_confirm_remove_code', 'Remove this login? Calls will use the platform default, if any.'),
  'saved' => $t('api_login_saved_code', 'Saved:'),
  'keepSecret' => $t('api_login_keep_code', 'Leave blank to keep the saved value'),
  'saveDone' => $t('api_login_saved_msg_code', 'Login saved'),
  'removeDone' => $t('api_login_removed_code', 'Login removed')
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;

// Labels for the fixed fields; header login fields show the header name itself
var apiFieldLabels = <?=json_encode([
  'key' => $t('api_key_field_code', 'API key'),
  'token' => $t('token_code', 'Token'),
  'username' => $t('username_code', 'Username'),
  'password' => $t('password_code', 'Password')
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;

var apiTypeLabels = <?=json_encode([
  'api_key' => $t('api_login_type_api_key_code', 'API key'),
  'bearer' => $t('api_login_type_bearer_code', 'Token'),
  'basic' => $t('api_login_type_basic_code', 'Username and password'),
  'header' => $t('api_login_type_header_code', 'Custom headers'),
  'jwt' => $t('api_login_type_jwt_code', 'Login (token renews itself)')
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;

var apiIntegrationApi = 'php/modules/integrations/api.php';

// One row per connection the vendor opened to customers
function loadApiIntegrations() {
  $.post(apiIntegrationApi, { action: 'setup' }).done(function(obj) {
    var $tbody = $('#apiIntegrationTable').empty();
    if (obj.status !== 'success') {
      toastr.error(obj.message, 'Failed:');
      return;
    }

    (obj.data || []).forEach(function(c) {
      var $action = $('<span class="text-muted small">').text(apiText.notAvailable);
      if (c.auth_type) {
        $action = $('<span>').append(
          $('<button type="button" class="btn btn-action btn-action-primary apiLoginEditBtn mr-1">')
            .text(c.secret_hint ? apiText.changeLogin : apiText.setLogin)
            .data('login', c)
        );
        if (c.secret_hint) {
          $action.append($('<button type="button" class="btn btn-action btn-action-danger apiLoginRemoveBtn">')
            .text(apiText.remove)
            .data('login', c));
        }
      }

      $tbody.append($('<tr>').append(
        $('<td>').text(c.name),
        $('<td>').text(c.auth_type ? apiTypeLabels[c.auth_type] : apiText.notAvailable),
        $('<td>').text(c.secret_hint ? apiText.saved + ' ' + c.secret_hint : apiText.notSet),
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
    $box.append($('<div class="form-group-modern">').append(
      $('<label class="form-label-modern">').text(label),
      $input
    ));
  });
}

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

  $('#apiLoginSaveBtn').on('click', function() {
    var $btn = $(this).prop('disabled', true);
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
</script>
