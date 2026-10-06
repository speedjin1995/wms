<?php
require_once '../../php/db_connect.php';
require_once '../../php/bootstrap.php';

use App\Modules\Indicator\IndicatorService;

session_start();

if(!isset($_SESSION['userID'])){
    echo '<script type="text/javascript">';
    echo 'window.location.href = "login.html";</script>';
}
else{
    $company = $_SESSION['customer'];
    $id = $_SESSION['userID'];

    $indicatorService = new IndicatorService($db, (int)$company, (int)$id, (string)($_SESSION['role'] ?? ''));
    $indicators = $indicatorService->getList();
    $current = $indicatorService->getCurrent();

    $indicatorId = $current['id'] ?? '';
    $name = $current['name'] ?? '';
    $nickname = $current['nickname'] ?? '';
    $serialNo = $current['serial_no'] ?? '';
    $macAddress = $current['mac_address'] ?? '';
    $indicator = $current['indicator'] ?? '';

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
    <form role="form" id="indicatorForm" novalidate="novalidate">
      <div class="row">
        <!-- Indicator selection -->
        <div class="col-lg-4 d-flex">
          <div class="card results-card form-card flex-fill mb-3">
            <div class="card-header">
              <div class="results-header-left">
                <h3 class="results-title"><i class="fas fa-tachometer-alt mr-2"></i><?=$languageArray['setup_code'][$language]?></h3>
              </div>
            </div>
            <div class="card-body">
              <div class="form-group mb-0">
                <label class="form-label-modern" for="indicatorSelect"><?=$languageArray['indicator_code'][$language]?></label>
                <select class="form-control select2" id="indicatorSelect" name="indicatorSelect">
                  <option value="" selected disabled><?=$languageArray['please_select_code'][$language] ?? 'Please Select'?></option>
                  <?php foreach ($indicators as $rowInd) { ?>
                    <option value="<?=$rowInd['id']?>"><?=$rowInd['name']?> (<?=$rowInd['nickname']?>)</option>
                  <?php } ?>
                </select>
              </div>
            </div>
            <div class="card-footer">
              <button type="submit" class="btn btn-action btn-action-primary" id="saveIndicator"><i class="fas fa-save"></i> <?=$languageArray['save_code'][$language]?></button>
            </div>
          </div>
        </div>

        <!-- Indicator details (read only) -->
        <div class="col-lg-8 d-flex">
          <div class="card results-card form-card flex-fill mb-3">
            <div class="card-header">
              <div class="results-header-left">
                <h3 class="results-title"><i class="fas fa-info-circle mr-2"></i><?=$languageArray['indicator_detail_code'][$language]?></h3>
              </div>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="form-label-modern" for="name"><?=$languageArray['name_code'][$language]?></label>
                    <input class="form-control" id="name" value="<?=$name?>" readonly>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="form-label-modern" for="nickname"><?=$languageArray['nickname_code'][$language]?></label>
                    <input class="form-control" id="nickname" value="<?=$nickname?>" readonly>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="form-label-modern" for="serialNo"><?=$languageArray['serial_no_code'][$language]?></label>
                    <input class="form-control" id="serialNo" value="<?=$serialNo?>" readonly>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group mb-md-0">
                    <label class="form-label-modern" for="macAddress"><?=$languageArray['mac_address_code'][$language]?></label>
                    <input class="form-control" id="macAddress" value="<?=$macAddress?>" readonly>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group mb-0">
                    <label class="form-label-modern" for="indicator"><?=$languageArray['indicator_code'][$language]?></label>
                    <input class="form-control" id="indicator" value="<?=$indicator?>" readonly>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div><!-- /.row -->
    </form>
  </div><!-- /.container-fluid -->
</section><!-- /.content -->

<script>
var currentIndicatorId = '<?=$indicatorId?>';
var indicatorText = {
  pleaseSelect: '<?=$languageArray['please_select_code'][$language] ?? 'Please Select'?>'
};
</script>
<script src="modules/indicatorSetup/js/indicatorSetup.js?v=<?=time()?>"></script>
