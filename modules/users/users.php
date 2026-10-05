<?php
require_once '../../php/db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
  exit;
}

$company = $_SESSION['customer'];
$role = $_SESSION['role'];

$stmt2 = $db->prepare("SELECT * FROM roles WHERE deleted = '0'");
$stmt2->execute();
$result2 = $stmt2->get_result();

$locationStmt = $db->prepare("SELECT * FROM locations WHERE deleted = '0' AND customer = ? ORDER BY locations ASC");
$locationStmt->bind_param('i', $company);
$locationStmt->execute();
$locations = $locationStmt->get_result();
$locationStmt->close();

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
      <div class="col-12">
        <div class="card results-card show-dt-controls">
          <div class="card-header">
            <div class="results-header-left">
              <h3 class="results-title"><i class="fas fa-users mr-2"></i><?=$languageArray['users_code'][$language]?></h3>
            </div>
            <div class="results-header-right d-flex flex-wrap" style="gap: 0.5rem;">
              <button type="button" class="btn btn-action btn-action-primary" id="addMembers">
                <i class="fas fa-plus"></i> <?=$languageArray['add_members_code'][$language]?>
              </button>
            </div>
          </div>
          <div class="card-body">
            <table id="memberTable" class="table data-table">
              <thead>
                <tr>
                  <th><?=$languageArray['full_name_code'][$language]?></th>
                  <th><?=$languageArray['role_code'][$language]?></th>
                  <th><?=$languageArray['allow_add_code'][$language]?></th>
                  <th><?=$languageArray['allow_edit_code'][$language]?></th>
                  <th><?=$languageArray['allow_delete_code'][$language]?></th>
                  <th><?=$languageArray['allow_price_code'][$language]?></th>
                  <th><?=$languageArray['locations_code'][$language]?></th>
                  <th><?=$languageArray['created_date_code'][$language]?></th>
                  <th width="10%"><?=$languageArray['actions_code'][$language]?></th>
                </tr>
              </thead>
            </table>
          </div><!-- /.card-body -->
        </div><!-- /.card -->
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</section><!-- /.content -->

<!-- Modal -->
<div class="modal fade modal-modern" id="addModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form role="form" id="memberForm">
        <div class="modal-header">
          <h4 class="modal-title"><?=$languageArray['add_members_code'][$language]?></h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" class="form-control" id="id" name="id">
          
          <!-- Account Info Section -->
          <div class="modal-section">
            <div class="section-title"><i class="fas fa-user-circle mr-2"></i> <?=$languageArray['account_information_code'][$language]?></div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label-modern"><?=$languageArray['username_code'][$language]?> <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="username" id="username" placeholder="<?=$languageArray['enter_username_code'][$language]?>" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label-modern"><?=$languageArray['full_name_code'][$language]?> <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="name" id="name" placeholder="<?=$languageArray['enter_full_name_code'][$language]?>" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label-modern"><?=$languageArray['email_address_code'][$language]?></label>
                  <input type="email" class="form-control" name="email" id="email" placeholder="<?=$languageArray['enter_email_code'][$language]?>">
                  <small class="text-muted"><?=$languageArray['used_for_password_reset_code'][$language]?></small>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label-modern"><?=$languageArray['role_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control" id="userRole" name="userRole" required>
                    <option select="selected" value=""><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while ($row2 = $result2->fetch_assoc()) { ?>
                      <?php if ($row2['role_code'] !== 'ADMIN') { ?>
                        <option value="<?= $row2['role_code'] ?>"><?= $row2['role_name'] ?></option>
                      <?php } ?>
                    <?php } ?>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Permissions Section -->
          <div class="modal-section">
            <div class="section-title"><i class="fas fa-shield-alt mr-2"></i> <?=$languageArray['permissions_code'][$language]?></div>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label class="form-label-modern"><?=$languageArray['allow_add_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control" id="allowAdd" name="allowAdd" required>
                    <option value="Y"><?=$languageArray['yes_code'][$language]?></option>
                    <option value="N"><?=$languageArray['no_code'][$language]?></option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label class="form-label-modern"><?=$languageArray['allow_edit_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control" id="allowEdit" name="allowEdit" required>
                    <option value="Y"><?=$languageArray['yes_code'][$language]?></option>
                    <option value="N"><?=$languageArray['no_code'][$language]?></option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label class="form-label-modern"><?=$languageArray['allow_delete_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control" id="allowDelete" name="allowDelete" required>
                    <option value="Y"><?=$languageArray['yes_code'][$language]?></option>
                    <option value="N"><?=$languageArray['no_code'][$language]?></option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group mb-0">
                  <label class="form-label-modern"><?=$languageArray['allow_price_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control" id="allowPrice" name="allowPrice" required>
                    <option value="Y"><?=$languageArray['yes_code'][$language]?></option>
                    <option value="N"><?=$languageArray['no_code'][$language]?></option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Location Section -->
          <div class="modal-section">
            <div class="section-title"><i class="fas fa-map-marker-alt mr-2"></i> <?=$languageArray['location_assignment_code'][$language]?></div>
            <div class="form-group mb-0">
              <label class="form-label-modern"><?=$languageArray['locations_code'][$language]?></label>
              <select class="form-control select2" id="location" name="location">
                <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                <?php while($rowLocation=mysqli_fetch_assoc($locations)){ ?>
                  <option value="<?=$rowLocation['id'] ?>"><?=$rowLocation['locations'] ?></option>
                <?php } ?>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-modern btn-modern-primary" name="submit" id="submitMember"><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Module Access Modal -->
<div class="modal fade modal-modern" id="moduleAccessModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"><i class="fas fa-cogs mr-2"></i><?=$languageArray['module_settings_code'][$language] ?? 'Module Settings'?></h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="moduleAccessUserId">
        <p class="text-muted mb-3"><?=$languageArray['select_modules_categories_code'][$language] ?? 'Select modules and categories this user can access'?></p>
        <div id="moduleAccessContainer"></div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
        <button type="button" class="btn btn-modern btn-modern-primary" onclick="saveModuleAccess()"><?=$languageArray['save_code'][$language] ?? 'Save'?></button>
      </div>
    </div>
  </div>
</div>

<script>
var userTableLanguage = {
  emptyTable: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
  zeroRecords: '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
};
var userText = {
  pleaseSelect: '<?=$languageArray['please_select_code'][$language] ?? 'Please Select'?>'
};
</script>
<script src="modules/users/js/users.js?v=<?=time()?>"></script>
