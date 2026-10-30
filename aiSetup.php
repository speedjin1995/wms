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

$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];
$t = function ($key, $default = '') use ($languageArray, $language) { return $languageArray[$key][$language] ?? $default; };
?>

<div class="content page-modern">
  <div class="container-fluid">

    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title"><i class="fas fa-robot"></i> <?=$t('ai_setup_code', 'AI Setup')?></h1>
    </div>

    <!-- Providers Card -->
    <div class="card results-card">
      <div class="card-header">
        <div class="results-header-left">
          <h3 class="results-title"><i class="fas fa-list"></i> <?=$t('ai_providers_code', 'AI Providers')?></h3>
        </div>
      </div>
      <div class="card-body">
        <p class="text-muted px-3 pt-3 mb-2"><?=$t('ai_setup_desc_code', 'Keys are used to scan paper DOs. Each key is saved encrypted and is never shown again.')?></p>
        <table class="table data-table mb-0">
          <thead>
            <tr>
              <th><?=$t('provider_code', 'Provider')?></th>
              <th><?=$t('ai_key_status_code', 'Key')?></th>
              <th></th>
            </tr>
          </thead>
          <tbody id="aiSetupTable"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- AI API Key Modal -->
<div class="modal fade modal-modern" id="aiKeyModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-key mr-2 text-muted"></i><span id="aiKeyTitle"></span></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group-modern">
          <label class="form-label-modern"><?=$t('do_ai_key_current_code', 'Current key:')?> <span id="aiKeyHint">-</span></label>
          <input type="password" class="form-control" id="aiKeyInput" autocomplete="off" placeholder="sk-...">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$t('close_code', 'Close')?></button>
        <button type="button" class="btn btn-modern btn-modern-primary" id="aiKeySaveBtn"><i class="fas fa-save mr-1"></i><?=$t('save_code', 'Save')?></button>
      </div>
    </div>
  </div>
</div>

<script>
// AI providers the vendor can set up as connectors; auth decides how the key is sent
var aiProviders = {
  llm_openai: { label: 'OpenAI', auth: 'bearer' },
  llm_claude: { label: 'Claude', auth: 'api_key', header: 'x-api-key' },
  llm_gemini: { label: 'Gemini', auth: 'api_key', header: 'x-goog-api-key' }
};

var aiText = <?=json_encode([
  'notSet' => $t('ai_key_not_set_code', 'Not set'),
  'notAvailable' => $t('ai_not_available_code', 'Not available'),
  'setKey' => $t('ai_set_key_code', 'Set key'),
  'changeKey' => $t('ai_change_key_code', 'Change key')
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;

// One row per provider; only the hint of the saved key is ever shown
function loadAiSetup() {
  $.post('php/modules/integrations/api.php', { action: 'list' }).done(function(obj) {
    var $tbody = $('#aiSetupTable').empty();
    Object.keys(aiProviders).forEach(function(code) {
      var c = (obj.data || []).filter(function(x) { return x.code === code; })[0];
      var $action = $('<span class="text-muted small">').text(aiText.notAvailable);
      if (c) {
        $action = $('<button type="button" class="btn btn-action btn-action-primary aiKeyEditBtn">')
          .text(c.secret_hint ? aiText.changeKey : aiText.setKey)
          .data('connector', c.id).data('provider', code).data('hint', c.secret_hint || '-');
      }

      $tbody.append($('<tr>').append(
        $('<td>').text(aiProviders[code].label),
        $('<td>').text(c && c.secret_hint ? c.secret_hint : aiText.notSet),
        $('<td class="text-right">').append($action)
      ));
    });
  });
}

$(function() {
  loadAiSetup();

  $('#aiSetupTable').on('click', '.aiKeyEditBtn', function() {
    var $btn = $(this);
    $('#aiKeyTitle').text(aiProviders[$btn.data('provider')].label);
    $('#aiKeyHint').text($btn.data('hint'));
    $('#aiKeyInput').val('');
    $('#aiKeySaveBtn').data('connector', $btn.data('connector')).data('provider', $btn.data('provider'));
    $('#aiKeyModal').modal('show');
  });

  $('#aiKeySaveBtn').on('click', function() {
    var key = $.trim($('#aiKeyInput').val());
    if (!key) {
      return;
    }

    var $btn = $(this).prop('disabled', true);
    var provider = aiProviders[$btn.data('provider')];
    $.post('php/modules/integrations/api.php', {
      action: 'saveCredential', connector_id: $btn.data('connector'), auth_type: provider.auth,
      header_name: provider.header || 'x-api-key', key: key
    }).done(function(res) {
      if (res.status === 'success') {
        toastr.success(res.message, 'Success:');
        $('#aiKeyModal').modal('hide');
        loadAiSetup();
      } else {
        toastr.error(res.message, 'Failed:');
      }
    }).always(function() {
      $btn.prop('disabled', false);
      $('#aiKeyInput').val('');
    });
  });
});
</script>
