<?php
require_once '../../php/db_connect.php';
require_once '../../php/bootstrap.php';

use App\Services\ProfileService;

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
  exit;
}

$profileService = new ProfileService($db, (int)$_SESSION['customer'], (int)$_SESSION['userID'], (string)($_SESSION['role'] ?? ''));
$profile = $profileService->getProfile() ?? ['name' => '', 'username' => '', 'email' => '', 'languages' => 'en'];

$languageOptions = [
  'en' => 'English',
  'zh' => 'Chinese',
  'my' => 'Bahasa Malaysia',
  'ne' => 'नेपाली',
  'ja' => '日本語'
];

// Language
$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];
?>

<div class="content-header" style="padding-bottom: 0;">
  <div class="container-fluid"></div>
</div>

<!-- Main content -->
<section class="content page-modern">
  <div class="container-fluid">
    <div class="row">
      <!-- Profile -->
      <div class="col-lg-6">
        <div class="card results-card form-card">
          <div class="card-header">
            <div class="results-header-left">
              <h3 class="results-title"><i class="fas fa-id-badge mr-2"></i><?=$languageArray['my_profile_code'][$language]?></h3>
            </div>
          </div>
          <form role="form" id="profileForm">
            <div class="card-body">
              <div class="form-group">
                <label class="form-label-modern"><?=$languageArray['full_name_code'][$language]?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="userName" name="userName" value="<?=htmlspecialchars($profile['name'])?>" placeholder="<?=$languageArray['enter_full_name_code'][$language]?>" required>
              </div>
              <div class="form-group">
                <label class="form-label-modern"><?=$languageArray['username_code'][$language]?></label>
                <input type="text" class="form-control" id="userEmail" value="<?=htmlspecialchars($profile['username'])?>" readonly>
              </div>
              <div class="form-group">
                <label class="form-label-modern"><?=$languageArray['email_address_code'][$language]?></label>
                <input type="email" class="form-control" id="userEmailAddress" name="userEmailAddress" value="<?=htmlspecialchars($profile['email'])?>" placeholder="<?=$languageArray['enter_email_code'][$language]?>" autocomplete="email">
                <small class="form-text text-muted"><?=$languageArray['used_for_password_reset_code'][$language]?></small>
              </div>
              <div class="form-group mb-0">
                <label class="form-label-modern"><?=$languageArray['language_code'][$language]?> <span class="text-danger">*</span></label>
                <select class="form-control" id="language" name="language" required>
                  <?php foreach ($languageOptions as $code => $label) { ?>
                    <option value="<?=$code?>" <?= ($profile['languages'] == $code) ? 'selected' : '' ?>><?=$label?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
            <div class="card-footer">
              <button type="submit" class="btn btn-action btn-action-primary" id="saveProfile"><i class="fas fa-save"></i> <?=$languageArray['save_code'][$language]?></button>
            </div>
          </form>
        </div>
      </div>

      <!-- Change Password -->
      <div class="col-lg-6">
        <div class="card results-card form-card">
          <div class="card-header">
            <div class="results-header-left">
              <h3 class="results-title"><i class="fas fa-key mr-2"></i><?=$languageArray['change_password_code'][$language]?></h3>
            </div>
          </div>
          <form role="form" id="passwordForm">
            <div class="card-body">
              <div class="form-group">
                <label class="form-label-modern"><?=$languageArray['old_password_code'][$language]?> <span class="text-danger">*</span></label>
                <input type="password" class="form-control" name="oldPassword" id="oldPassword" placeholder="<?=$languageArray['old_password_code'][$language]?>" autocomplete="current-password" required>
              </div>
              <div class="form-group">
                <label class="form-label-modern"><?=$languageArray['new_password_code'][$language]?> <span class="text-danger">*</span></label>
                <input type="password" class="form-control" name="newPassword" id="newPassword" placeholder="<?=$languageArray['new_password_code'][$language]?>" autocomplete="new-password" required>
              </div>
              <div class="form-group mb-0">
                <label class="form-label-modern"><?=$languageArray['confirm_password_code'][$language]?> <span class="text-danger">*</span></label>
                <input type="password" class="form-control" name="confirmPassword" id="confirmPassword" placeholder="<?=$languageArray['confirm_password_code'][$language]?>" autocomplete="new-password" required>
              </div>
            </div>
            <div class="card-footer">
              <button type="submit" class="btn btn-action btn-action-primary" id="savePassword"><i class="fas fa-save"></i> <?=$languageArray['save_code'][$language]?></button>
            </div>
          </form>
        </div>
      </div>
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</section><!-- /.content -->

<script src="modules/profile/js/profile.js?v=<?=time()?>"></script>
