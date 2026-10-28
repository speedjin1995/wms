<?php
session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
  exit;
}

if (!in_array($_SESSION['role'] ?? '', ['ADMIN', 'SADMIN'], true) || ($_SESSION['featureFlags']['include_do_ai'] ?? 'N') != 'Y') {
  exit;
}

require_once __DIR__ . '/../../php/bootstrap.php';

$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];
$t = function ($key, $default = '') use ($languageArray, $language) { return $languageArray[$key][$language] ?? $default; };
$providers = \App\Integrations\LlmExtractor::PROVIDERS;
?>

<div class="content page-modern">
  <div class="container-fluid">

    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title"><i class="fas fa-plug"></i> <?=$t('integrations_code', 'Integrations')?></h1>
    </div>

    <div class="row">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-magic mr-2"></i><?=$t('scan_do_ai_code', 'Scan DO (AI)')?></h5>
            <span class="badge badge-secondary" id="intSourceBadge"></span>
          </div>
          <div class="card-body">
            <p class="text-muted" id="intSourceText"></p>

            <form id="intForm" autocomplete="off">
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group-modern">
                    <label class="form-label-modern"><?=$t('ai_provider_code', 'AI Provider')?> *</label>
                    <select class="form-control" id="intProvider" name="provider">
                      <option value="anthropic">Anthropic (Claude)</option>
                      <option value="openai">OpenAI</option>
                      <option value="gemini">Google Gemini</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-8">
                  <div class="form-group-modern">
                    <label class="form-label-modern"><?=$t('ai_model_code', 'Model')?></label>
                    <input type="text" class="form-control" id="intModel" name="model">
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group-modern">
                    <label class="form-label-modern"><?=$t('api_key_code', 'API Key')?></label>
                    <input type="password" class="form-control" id="intApiKey" name="api_key" autocomplete="new-password">
                    <small class="text-muted"><?=$t('api_key_help_code', 'Stored encrypted and never shown again. Leave blank to keep the saved key.')?></small>
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="card-footer d-flex">
            <button type="button" class="btn btn-modern btn-modern-danger btn-sm mr-auto" id="intRemoveBtn" style="display:none;"><i class="fas fa-undo mr-1"></i><?=$t('use_platform_default_code', 'Use Platform Default')?></button>
            <button type="button" class="btn btn-modern btn-modern-secondary btn-sm mr-2" id="intTestBtn"><i class="fas fa-vial mr-1"></i><?=$t('test_connection_code', 'Test Connection')?></button>
            <button type="button" class="btn btn-modern btn-modern-primary btn-sm" id="intSaveBtn"><i class="fas fa-save mr-1"></i><?=$t('save_code', 'Save')?></button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
var intProviders = <?=json_encode($providers)?>;
var intText = <?=json_encode([
  'own' => $t('using_own_key_code', 'Own key'),
  'platform' => $t('using_platform_key_code', 'Platform default'),
  'none' => $t('not_configured_code', 'Not configured'),
  'ownText' => $t('using_own_key_text_code', 'AI scan uses your company\'s own API key.'),
  'platformText' => $t('using_platform_key_text_code', 'AI scan uses the platform key. Save your own key below to use your own AI account instead.'),
  'noneText' => $t('not_configured_text_code', 'No AI connection yet. Save an API key below to enable AI scan.'),
  'removeConfirm' => $t('use_platform_default_confirm_code', 'Remove your own key and use the platform default?')
])?>;

$(document).ready(function() {
  $('#intProvider').on('change', function() {
    $('#intModel').attr('placeholder', intProviders[$(this).val()].model);
  }).trigger('change');

  $('#intSaveBtn').on('click', function() {
    postIntegration('save', $(this));
  });

  $('#intTestBtn').on('click', function() {
    postIntegration('test', $(this));
  });

  $('#intRemoveBtn').on('click', function() {
    if (!confirm(intText.removeConfirm)) {
      return;
    }
    postIntegration('remove', $(this));
  });

  loadIntegration();
});

function loadIntegration() {
  $.post('php/modules/integrations/api.php', { action: 'get' }).done(function(res) {
    if (res.status !== 'success') {
      toastr.error(res.message, 'Failed:');
      return;
    }

    var d = res.data;
    $('#intSourceBadge').text(intText[d.source])
      .toggleClass('badge-success', d.source === 'own')
      .toggleClass('badge-info', d.source === 'platform')
      .toggleClass('badge-secondary', d.source === 'none');
    $('#intSourceText').text(intText[d.source + 'Text']);
    $('#intRemoveBtn').toggle(d.source === 'own');
    $('#intApiKey').val('').attr('placeholder', d.secret_hint || '');

    if (d.source === 'own') {
      $('#intProvider').val(d.provider).trigger('change');
      $('#intModel').val(d.model);
    }
  });
}

function postIntegration(action, $btn) {
  var data = $('#intForm').serializeArray();
  data.push({ name: 'action', value: action });

  $btn.prop('disabled', true);
  $('#spinnerLoading').show();

  $.post('php/modules/integrations/api.php', $.param(data)).done(function(res) {
    if (res.status === 'success') {
      toastr.success(res.message, 'Success:');
      if (action !== 'test') {
        loadIntegration();
      }
    } else {
      toastr.error(res.message, 'Failed:');
    }
  }).fail(function() {
    toastr.error('Something went wrong', 'Error:');
  }).always(function() {
    $btn.prop('disabled', false);
    $('#spinnerLoading').hide();
  });
}
</script>
