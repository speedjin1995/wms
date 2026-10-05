<?php
require_once '../../php/db_connect.php';
require_once '../../php/bootstrap.php';

use App\Services\CompanyService;

session_start();

if(!isset($_SESSION['userID'])){
    echo '<script type="text/javascript">';
    echo 'window.location.href = "login.html";</script>';
}
else{
    $company = $_SESSION['customer'];
    $role = $_SESSION['role'];
    $companyService = new CompanyService($db, (int)$company, (int)$_SESSION['userID'], (string)$role);
    $name = '';
    $chineseName = '';
    $regNo = '';
    $tinNo = '';
	$address = '';
	$address2 = '';
	$address3 = '';
	$address4 = '';
	$phone = '';
	$email = '';
	$fax = '';
	$bankerName = '';
	$bankAcctNo = '';
	$bankSwiftCode = '';
	$includePrice = 'N';
	$includePhoto = 'N';
	$includeBarcode = 'N';
	$includeSecRemark = 'N';
	$includeInvoice = 'N';
	$photoUploadMode = 'local';

	$logoPath = '';
	if(($row = $companyService->getCompany()) !== null){
        $name = $row['name'];
        $chineseName = $row['chinese_name'];
        $regNo = $row['reg_no'];
        $tinNo = $row['tin_no'];
        $address = $row['address'];
        $address2 = $row['address2'];
        $address3 = $row['address3'];
        $address4 = $row['address4'];
        $phone = $row['phone'];
        $email = $row['email'];
        $fax = $row['fax'];
        $bankerName = $row['banker_name'];
        $bankAcctNo = $row['bank_acct_no'];
        $bankSwiftCode = $row['bank_swift_code'];
        $includePrice = $row['include_price'];
        $includePhoto = $row['include_photo'];
        $includeBarcode = $row['include_barcode'];
        $includeSecRemark = $row['include_sec_remark'];
        $includeInvoice = $row['include_invoice'];
        $photoUploadMode = $row['photo_upload_mode'] ?? 'local';

        if(!empty($row['company_logo'])){
            $logoPath = 'php/viewPhoto.php?file=' . $row['company_logo'].'&type=file_table';
        }
    }

    // Only SADMIN may edit company details
    $readonly = ($role != 'SADMIN') ? 'readonly' : '';
    $sadminOnly = ($role != 'SADMIN') ? 'style="display:none;"' : '';
    $invoiceOnly = ($includeInvoice == 'Y') ? '' : 'style="display:none;"';

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
    <form role="form" id="profileForm" novalidate="novalidate">

      <div class="row">
        <!-- Company Information -->
        <div class="col-lg-6 d-flex">
          <div class="card results-card form-card flex-fill mb-3">
            <div class="card-header">
              <div class="results-header-left">
                <h3 class="results-title"><i class="fas fa-building mr-2"></i><?=$languageArray['company_information_code'][$language]?></h3>
              </div>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label-modern" for="regNo"><?=$languageArray['company_reg_no_code'][$language]?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="regNo" name="regNo" value="<?=$regNo ?>" placeholder="<?=$languageArray['enter_company_reg_no_code'][$language]?>" required <?=$readonly?>>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label-modern" for="name"><?=$languageArray['company_name_code'][$language]?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="<?=$name ?>" placeholder="<?=$languageArray['enter_company_name_code'][$language]?>" required <?=$readonly?>>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-md-0">
                    <label class="form-label-modern" for="chineseName"><?=$languageArray['company_chinese_name_code'][$language]?></label>
                    <input type="text" class="form-control" id="chineseName" name="chineseName" value="<?=$chineseName ?>" <?=$readonly?>>
                  </div>
                </div>
                <div class="col-md-6" <?=$invoiceOnly?>>
                  <div class="form-group mb-0">
                    <label class="form-label-modern" for="tinNo"><?=$languageArray['tin_no_code'][$language]?></label>
                    <input type="text" class="form-control" id="tinNo" name="tinNo" value="<?=$tinNo ?>" <?=$readonly?>>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Address -->
        <div class="col-lg-6 d-flex">
          <div class="card results-card form-card flex-fill mb-3">
            <div class="card-header">
              <div class="results-header-left">
                <h3 class="results-title"><i class="fas fa-map-marker-alt mr-2"></i><?=$languageArray['address_code'][$language]?></h3>
              </div>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label-modern" for="address1"><?=$languageArray['company_address_line_1_code'][$language]?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="address1" name="address1" value="<?=$address ?>" placeholder="<?=$languageArray['enter_company_address_line_1_code'][$language]?>" required <?=$readonly?>>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label-modern" for="address2"><?=$languageArray['company_address_line_2_code'][$language]?></label>
                    <input type="text" class="form-control" id="address2" name="address2" value="<?=$address2 ?>" placeholder="<?=$languageArray['enter_company_address_line_2_code'][$language]?>" <?=$readonly?>>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-md-0">
                    <label class="form-label-modern" for="address3"><?=$languageArray['company_address_line_3_code'][$language]?></label>
                    <input type="text" class="form-control" id="address3" name="address3" value="<?=$address3 ?>" placeholder="<?=$languageArray['enter_company_address_line_3_code'][$language]?>" <?=$readonly?>>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-0">
                    <label class="form-label-modern" for="address4"><?=$languageArray['company_address_line_4_code'][$language]?></label>
                    <input type="text" class="form-control" id="address4" name="address4" value="<?=$address4 ?>" placeholder="<?=$languageArray['enter_company_address_line_4_code'][$language]?>" <?=$readonly?>>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Contact Details -->
        <div class="<?= ($includeInvoice == 'Y') ? 'col-lg-6' : 'col-12' ?> d-flex">
          <div class="card results-card form-card flex-fill mb-3">
            <div class="card-header">
              <div class="results-header-left">
                <h3 class="results-title"><i class="fas fa-phone-alt mr-2"></i><?=$languageArray['contact_details_code'][$language]?></h3>
              </div>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group mb-md-0">
                    <label class="form-label-modern" for="phone"><?=$languageArray['company_phone_code'][$language]?></label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                      </div>
                      <input type="text" class="form-control" id="phone" name="phone" value="<?=$phone ?>" placeholder="<?=$languageArray['enter_phone_code'][$language]?>" <?=$readonly?>>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group mb-md-0">
                    <label class="form-label-modern" for="email"><?=$languageArray['company_email_code'][$language]?></label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                      </div>
                      <input type="email" class="form-control" id="email" name="email" value="<?=$email ?>" placeholder="<?=$languageArray['enter_email_code'][$language]?>" <?=$readonly?>>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group mb-0">
                    <label class="form-label-modern" for="fax"><?=$languageArray['company_fax_code'][$language]?></label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-fax"></i></span>
                      </div>
                      <input type="text" class="form-control" id="fax" name="fax" value="<?=$fax ?>" placeholder="<?=$languageArray['enter_fax_code'][$language]?>" <?=$readonly?>>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Banking Details -->
        <div class="col-lg-6 d-flex" <?=$invoiceOnly?>>
          <div class="card results-card form-card flex-fill mb-3">
            <div class="card-header">
              <div class="results-header-left">
                <h3 class="results-title"><i class="fas fa-university mr-2"></i><?=$languageArray['banking_details_code'][$language]?></h3>
              </div>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group mb-md-0">
                    <label class="form-label-modern" for="bankerName"><?=$languageArray['banker_name_code'][$language]?></label>
                    <input type="text" class="form-control" name="bankerName" id="bankerName" value="<?=$bankerName ?>" placeholder="<?=$languageArray['enter_banker_name_code'][$language]?>">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group mb-md-0">
                    <label class="form-label-modern" for="bankAccountNo"><?=$languageArray['bank_account_no_code'][$language]?></label>
                    <input type="text" class="form-control" name="bankAccountNo" id="bankAccountNo" value="<?=$bankAcctNo ?>" placeholder="<?=$languageArray['enter_bank_account_no_code'][$language]?>">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group mb-0">
                    <label class="form-label-modern" for="bankSwiftCode"><?=$languageArray['bank_swift_code_code'][$language]?></label>
                    <input type="text" class="form-control" name="bankSwiftCode" id="bankSwiftCode" value="<?=$bankSwiftCode ?>" placeholder="<?=$languageArray['enter_bank_swift_code_code'][$language]?>">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Preferences -->
        <div class="col-12">
          <div class="card results-card form-card mb-3">
            <div class="card-header">
              <div class="results-header-left">
                <h3 class="results-title"><i class="fas fa-sliders-h mr-2"></i><?=$languageArray['preferences_code'][$language]?></h3>
              </div>
            </div>
            <div class="card-body">
              <div class="row">
                <!-- Toggles (SADMIN only) -->
                <div class="col-md-6 col-xl-3 mb-3" <?=$sadminOnly?>>
                  <div class="toggle-tile">
                    <span class="toggle-tile-label"><?=$languageArray['include_price_code'][$language]?></span>
                    <div class="custom-control custom-switch">
                      <input type="checkbox" class="custom-control-input" id="includePriceToggle" <?= $includePrice == 'Y' ? 'checked' : '' ?>>
                      <label class="custom-control-label" for="includePriceToggle"></label>
                      <input type="hidden" name="includePrice" id="includePriceVal" value="<?=$includePrice?>">
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-xl-3 mb-3" <?=$sadminOnly?>>
                  <div class="toggle-tile">
                    <span class="toggle-tile-label"><?=$languageArray['include_photo_code'][$language]?></span>
                    <div class="custom-control custom-switch">
                      <input type="checkbox" class="custom-control-input" id="includePhotoToggle" <?= $includePhoto == 'Y' ? 'checked' : '' ?>>
                      <label class="custom-control-label" for="includePhotoToggle"></label>
                      <input type="hidden" name="includePhoto" id="includePhotoVal" value="<?=$includePhoto?>">
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-xl-3 mb-3" <?=$sadminOnly?>>
                  <div class="toggle-tile">
                    <span class="toggle-tile-label"><?=$languageArray['include_barcode_code'][$language]?></span>
                    <div class="custom-control custom-switch">
                      <input type="checkbox" class="custom-control-input" id="includeBarcodeToggle" <?= $includeBarcode == 'Y' ? 'checked' : '' ?>>
                      <label class="custom-control-label" for="includeBarcodeToggle"></label>
                      <input type="hidden" name="includeBarcode" id="includeBarcodeVal" value="<?=$includeBarcode?>">
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-xl-3 mb-3" <?=$sadminOnly?>>
                  <div class="toggle-tile">
                    <span class="toggle-tile-label"><?=$languageArray['include_second_remark_code'][$language]?></span>
                    <div class="custom-control custom-switch">
                      <input type="checkbox" class="custom-control-input" id="includeSecRemarkToggle" <?= $includeSecRemark == 'Y' ? 'checked' : '' ?>>
                      <label class="custom-control-label" for="includeSecRemarkToggle"></label>
                      <input type="hidden" name="includeSecRemark" id="includeSecRemarkVal" value="<?=$includeSecRemark?>">
                    </div>
                  </div>
                </div>

                <div class="col-md-6 col-xl-3">
                  <div class="form-group mb-0">
                    <label class="form-label-modern" for="photoUploadMode"><?=$languageArray['photo_upload_mode_code'][$language]?></label>
                    <select class="form-control" id="photoUploadMode" name="photoUploadMode">
                      <option value="local" <?= $photoUploadMode == 'local' ? 'selected' : '' ?>><?=$languageArray['local_code'][$language]?></option>
                      <option value="google_drive" <?= $photoUploadMode == 'google_drive' ? 'selected' : '' ?>><?=$languageArray['google_drive_code'][$language]?></option>
                      <option value="one_drive" <?= $photoUploadMode == 'one_drive' ? 'selected' : '' ?>><?=$languageArray['one_drive_code'][$language]?></option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
            <div class="card-footer">
              <button type="submit" class="btn btn-action btn-action-primary" id="saveProfile"><i class="fas fa-save"></i> <?=$languageArray['save_code'][$language]?></button>
            </div>
          </div>
        </div>
      </div><!-- /.row -->
    </form>

    <!-- Company Logo -->
    <form id="logoForm" enctype="multipart/form-data">
      <div class="card results-card form-card mb-3">
        <div class="card-header">
          <div class="results-header-left">
            <h3 class="results-title"><i class="fas fa-image mr-2"></i><?=$languageArray['company_logo_code'][$language]?></h3>
          </div>
          <?php if(!empty($logoPath)): ?>
            <div class="results-header-right">
              <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#logoPreviewModal"><i class="fas fa-eye mr-1"></i> <?=$languageArray['preview_code'][$language]?></button>
            </div>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <div class="d-flex align-items-center flex-wrap" style="gap: 1.25rem;">
            <div class="logo-thumb">
              <?php if(!empty($logoPath)): ?>
                <img src="<?=$logoPath?>" alt="Logo">
              <?php else: ?>
                <i class="fas fa-image fa-2x"></i>
              <?php endif; ?>
            </div>
            <div class="flex-fill">
              <div class="input-group">
                <div class="custom-file">
                  <input type="file" class="custom-file-input" id="logoFile" name="file" accept=".png,.jpg,.jpeg">
                  <label class="custom-file-label" for="logoFile"><?=$languageArray['choose_file_code'][$language]?></label>
                </div>
              </div>
              <small class="form-text text-muted mt-1"><?=$languageArray['recommended_file_size_code'][$language]?></small>
            </div>
          </div>
        </div>
        <div class="card-footer">
          <button type="submit" class="btn btn-action btn-action-primary" id="uploadLogo"><i class="fas fa-upload"></i> <?=$languageArray['upload_code'][$language]?></button>
        </div>
      </div>
    </form>
  </div><!-- /.container-fluid -->
</section><!-- /.content -->

<?php if(!empty($logoPath)): ?>
<div class="modal fade modal-modern" id="logoPreviewModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"><?=$languageArray['company_logo_code'][$language]?></h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body text-center">
        <img src="<?=$logoPath?>" alt="<?=$languageArray['company_logo_code'][$language]?>" class="img-fluid">
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="modules/company/js/company.js?v=<?=time()?>"></script>
