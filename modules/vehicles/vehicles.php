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
  $companies = $db->query("SELECT * FROM companies WHERE deleted = 0 ORDER BY name ASC");

  if ($role != 'SADMIN'){
    $drivers = $db->query("SELECT * FROM drivers WHERE deleted = 0 AND customer = '".$company."' ORDER BY driver_name ASC");
  }
  else{
    $drivers = $db->query("SELECT * FROM drivers WHERE deleted = 0 ORDER BY driver_name ASC");
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
              <h3 class="results-title"><i class="fas fa-truck mr-2"></i><?=$languageArray['vehicles_code'][$language]?></h3>
            </div>
            <div class="results-header-right d-flex flex-wrap" style="gap: 0.5rem;">
              <a href="template/Vehicle_Template.xlsx" download class="btn btn-action btn-action-warning">
                <i class="fas fa-download"></i> <?=$languageArray['download_template_code'][$language]?>
              </a>
              <button type="button" id="uploadExcel" class="btn btn-action btn-action-success">
                <i class="fas fa-upload"></i> <?=$languageArray['upload_excel_code'][$language]?>
              </button>
              <button type="button" id="multiDeactivate" class="btn btn-action btn-action-danger">
                <i class="fas fa-trash-alt"></i> <?=$languageArray['delete_vehicle_code'][$language]?>
              </button>
              <button type="button" class="btn btn-action btn-action-primary" id="addVehicle">
                <i class="fas fa-plus"></i> <?=$languageArray['add_vehicle_code'][$language]?>
              </button>
            </div>
          </div>
          <div class="card-body">
            <table id="vehicleTable" class="table data-table">
              <thead>
                <tr>
                  <th><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
                  <th><?=$languageArray['vehicle_number_code'][$language]?></th>
                  <th><?=$languageArray['vehicle_weight_code'][$language]?> (Kg)</th>
                  <th><?=$languageArray['driver_code'][$language]?></th>
                  <!-- <th>Attendence 1</th>
                  <th>Attendence 2</th> -->
                  <th><?=$languageArray['actions_code'][$language]?></th>
                </tr>
              </thead>
            </table>
          </div><!-- /.card-body -->
        </div><!-- /.card -->
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</section><!-- /.content -->

<div class="modal fade modal-modern" id="uploadModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form role="form" id="uploadForm">
          <div class="modal-header">
            <h4 class="modal-title"><?=$languageArray['upload_excel_code'][$language]?></h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="card-body">
              <input type="file" id="fileInput">
              <button type="button" id="previewButton"><?=$languageArray['preview_data_code'][$language]?></button>
              <div id="previewTable" style="overflow: auto;"></div>
            </div>
          </div>
          <div class="modal-footer justify-content-between">
            <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
            <button type="button" class="btn btn-modern btn-modern-primary" id="uploadVehicle"><?=$languageArray['submit_code'][$language]?></button>
          </div>
      </form>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>

<div class="modal fade modal-modern" id="errorModal" style="display:none">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form role="form" id="uploadForm">
          <div class="modal-header">
            <h4 class="modal-title"><?=$languageArray['error_log_code'][$language]?></h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="form-group">
                <ol id="errorList" class="text-danger mt-2" style="padding-left: 20px;"></ol>
              </div>
            </div>
          </div>
      </form>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>

<div class="modal fade modal-modern" id="addModal">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <form role="form" id="vehicleForm">
            <div class="modal-header">
              <h4 class="modal-title"><?=$languageArray['add_vehicle_code'][$language]?></h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <input type="hidden" class="form-control" id="id" name="id">
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
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label-modern"><?=$languageArray['vehicle_number_code'][$language]?> <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="vehicleNumber" id="vehicleNumber" placeholder="<?=$languageArray['enter_vehicle_number_code'][$language]?>" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label-modern"><?=$languageArray['vehicle_weight_code'][$language]?> (Kg)</label>
                      <input type="text" class="form-control" name="vehicleWeight" id="vehicleWeight" placeholder="<?=$languageArray['enter_vehicle_weight_code'][$language]?>">
                    </div>
                  </div>
                </div>
                <div class="form-group mb-0">
                  <label class="form-label-modern"><?=$languageArray['driver_code'][$language]?></label>
                  <select class="form-control select2" style="width: 100%;" id="driver" name="driver">
                    <?php while($rowDriver=mysqli_fetch_assoc($drivers)){ ?>
                      <option value="<?=$rowDriver['id'] ?>"><?=$rowDriver['driver_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
                <!-- <div class="form-group">
                  <label for="name">Attendence 1</label>
                  <input type="text" class="form-control" name="attendence1" id="attendence1" placeholder="Enter Attendence 1">
                </div>
                <div class="form-group">
                  <label for="name">Attendence 2</label>
                  <input type="text" class="form-control" name="attendence2" id="attendence2" placeholder="Enter Attendence 2">
                </div> -->
              </div>
            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
              <button type="submit" class="btn btn-modern btn-modern-primary" name="submit" id="submitMember"><?=$languageArray['submit_code'][$language]?></button>
            </div>
        </form>
      </div>
      <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>

<!-- jQuery -->
<script>
var vehiclesTableLanguage = {
  'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
  'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
};
</script>
<script src="modules/vehicles/js/vehicles.js?v=<?=time()?>"></script>