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

<div class="content-header" style="padding-bottom: 0;">
  <div class="container-fluid"></div>
</div>

<section class="content page-modern">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card results-card">
          <div class="card-header">
            <div class="results-header-left">
              <h3 class="results-title"><i class="fas fa-plug mr-2"></i><?=$t('api_connections_code', 'Connections')?></h3>
              <small class="text-muted"><i class="fas fa-lock mr-1"></i><?=$t('api_integrations_desc_code', 'Logins are saved encrypted and are never shown again.')?></small>
            </div>
          </div>
          <div class="card-body">
            <table class="table data-table mb-0">
              <thead>
                <tr>
                  <th><?=$t('api_connection_code', 'Connection')?></th>
                  <th><?=$t('api_login_type_code', 'Login type')?></th>
                  <th><?=$t('ai_key_status_code', 'Status')?></th>
                  <th width="10%" class="text-right"><?=$t('actions_code', 'Actions')?></th>
                </tr>
              </thead>
              <tbody id="apiIntegrationTable"></tbody>
            </table>
          </div><!-- /.card-body -->
        </div><!-- /.card -->
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</section><!-- /.content -->

<!-- Login Modal -->
<div class="modal fade modal-modern" id="apiLoginModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" id="apiLoginForm" autocomplete="off">
        <div class="modal-header">
          <h4 class="modal-title"><i class="fas fa-key mr-2"></i><span id="apiLoginTitle"></span></h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="modal-section">
            <div class="section-title"><i class="fas fa-user-lock mr-2"></i><?=$t('api_login_saved_code', 'Saved:')?> <span id="apiLoginHint">-</span></div>
            <div id="apiLoginFields"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$t('close_code', 'Close')?></button>
          <button type="submit" class="btn btn-modern btn-modern-primary" id="apiLoginSaveBtn"><?=$t('save_code', 'Save')?></button>
        </div>
      </form>
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

var apiEmptyState = '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$t('no_records_found_code', 'No Records Found')?></div></div>';
</script>
<script src="modules/apiIntegrations/js/apiIntegrations.js?ver=<?=$_SESSION['ver'] ?? '1.0.0'?>"></script>
