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

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];

  if ($role == 'SADMIN') {
    $companies = $db->query("SELECT * FROM companies WHERE deleted = 0 ORDER BY name ASC");
  } else {
    $companyStmt = $db->prepare("SELECT * FROM companies WHERE id = ? AND deleted = 0");
    $companyStmt->bind_param('i', $company);
    $companyStmt->execute();
    $companies = $companyStmt->get_result();
    $companyStmt->close();
  }
}
?>

<div class="content-header" style="padding-bottom: 0;">
  <div class="container-fluid"></div>
</div>

<section class="content page-modern">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card results-card show-dt-controls">
          <div class="card-header">
            <div class="results-header-left">
              <h3 class="results-title"><i class="fas fa-language mr-2"></i>Translations</h3>
            </div>
            <div class="results-header-right d-flex flex-wrap" style="gap: 0.5rem;">
              <button type="button" class="btn btn-action btn-action-primary" id="addTranslation">
                <i class="fas fa-plus"></i> Add Translation
              </button>
            </div>
          </div>
          <div class="card-body">
            <table id="translationTable" class="table data-table">
              <thead>
                <tr>
                  <th>No.</th>
                  <th>Message Key Code</th>
                  <th>English</th>
                  <th>中文</th>
                  <th>Bahasa Malaysia</th>
                  <th>தமிழ்</th>
                  <th>日本語</th>
                  <th width="10%">Actions</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="modal fade modal-modern" id="translationModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form role="form" id="translationForm">
        <div class="modal-header">
          <h4 class="modal-title">Add Translation</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="keyId" name="keyId">

          <!-- Company (SADMIN only) -->
          <div class="modal-section" <?php if($role != 'SADMIN'){ echo 'style="display:none;"'; } ?>>
            <div class="form-group mb-0">
              <label class="form-label-modern">Company <span class="text-danger">*</span></label>
              <select class="form-control select2" style="width: 100%;" id="company" name="company" required>
                <?php while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                  <option value="<?=$rowCompany['id'] ?>" <?php if($rowCompany['id'] == $company) echo 'selected'; ?>><?=$rowCompany['name'] ?></option>
                <?php } ?>
              </select>
            </div>
          </div>

          <!-- Translation Details -->
          <div class="modal-section">
            <div class="section-title"><i class="fas fa-key mr-2"></i>Translation Key</div>
            <div class="form-group mb-0">
              <label class="form-label-modern">Message Key Code <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="keyCode" name="keyCode" placeholder="Message Key code" required>
            </div>
          </div>

          <!-- Languages -->
          <div class="modal-section">
            <div class="section-title"><i class="fas fa-globe mr-2"></i>Languages</div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label-modern">English <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="englishDecs" name="englishDecs" placeholder="English" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label-modern">中文</label>
                  <input type="text" class="form-control" id="chineseDecs" name="chineseDecs" placeholder="中文">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label-modern">Bahasa Malaysia</label>
                  <input type="text" class="form-control" id="malayDecs" name="malayDecs" placeholder="Bahasa">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label-modern">தமிழ்</label>
                  <input type="text" class="form-control" id="tamilDecs" name="tamilDecs" placeholder="தமிழ்">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group mb-0">
                  <label class="form-label-modern">日本語</label>
                  <input type="text" class="form-control" id="japaneseDecs" name="japaneseDecs" placeholder="日本語">
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-modern btn-modern-primary" id="submitTranslation">Submit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var translationTableLanguage = {
  'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
  'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
};
</script>
<script src="modules/translations/js/translations.js?v=<?=time()?>"></script>
