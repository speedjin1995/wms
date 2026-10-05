<?php
require_once '../../php/db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
}
else{
  $company = $_SESSION['customer'];
  $user = $_SESSION['userID'];
  $role = $_SESSION['role'];

  if ($role == 'SADMIN') {
    $companies = $db->query("SELECT * FROM companies WHERE deleted = 0 ORDER BY name ASC");
  } else {
    $companyStmt = $db->prepare("SELECT * FROM companies WHERE id = ? AND deleted = 0");
    $companyStmt->bind_param('i', $company);
    $companyStmt->execute();
    $companies = $companyStmt->get_result();
    $companyStmt->close();
  }

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
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
              <h3 class="results-title"><i class="fas fa-box mr-2"></i><?=$languageArray['bin_types_code'][$language]?></h3>
            </div>
            <div class="results-header-right d-flex flex-wrap" style="gap: 0.5rem;">
              <button type="button" id="multiDeactivate" class="btn btn-action btn-action-danger">
                <i class="fas fa-trash-alt"></i> <?=$languageArray['delete_bin_types_code'][$language]?>
              </button>
              <button type="button" class="btn btn-action btn-action-primary" id="addBinType">
                <i class="fas fa-plus"></i> <?=$languageArray['add_bin_types_code'][$language]?>
              </button>
            </div>
          </div>
          <div class="card-body">
            <table id="binTypeTable" class="table data-table">
              <thead>
                <tr>
                  <th><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
                  <th><?=$languageArray['bin_types_code'][$language]?></th>
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

<div class="modal fade modal-modern" id="addModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form role="form" id="binTypeForm">
        <div class="modal-header">
          <h4 class="modal-title" id="modalTitle"><?=$languageArray['add_bin_types_code'][$language]?></h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" class="form-control" id="id" name="id">

          <!-- Company (SADMIN only) -->
          <div class="modal-section" <?php if($role != 'SADMIN'){ echo 'style="display:none;"'; } ?>>
            <div class="form-group mb-0">
              <label class="form-label-modern"><?=$languageArray['company_code'][$language]?> <span class="text-danger">*</span></label>
              <select class="form-control select2" style="width: 100%;" id="company" name="company" required>
                <?php while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                  <option value="<?=$rowCompany['id'] ?>" <?php if($rowCompany['id'] == $company) echo 'selected'; ?>><?=$rowCompany['name'] ?></option>
                <?php } ?>
              </select>
            </div>
          </div>

          <div class="modal-section">
            <div class="form-group mb-0">
              <label class="form-label-modern"><?=$languageArray['bin_types_code'][$language]?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="binType" id="binType" placeholder="<?=$languageArray['enter_bin_type_code'][$language]?>" required>
            </div>
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-modern btn-modern-primary" name="submit" id="submitBinType"><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>

<script>
var binTypeText = {
  'addTitle': '<?=$languageArray['add_bin_types_code'][$language]?>',
  'editTitle': '<?=$languageArray['edit_bin_types_code'][$language] ?? 'Edit Bin Type'?>',
  'pleaseSelect': '<?=$languageArray['please_select_code'][$language] ?? 'Please Select'?>'
};
var binTypeTableLanguage = {
  'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
  'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
};
</script>
<script src="modules/binType/js/binType.js?v=<?=time()?>"></script>
