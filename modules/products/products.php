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
  $units = $db->query("SELECT * FROM units WHERE deleted = '0' ORDER BY units ASC");
  $units2 = $db->query("SELECT * FROM units WHERE deleted = '0' ORDER BY units ASC");
  $units3 = $db->query("SELECT * FROM units WHERE deleted = '0' ORDER BY units ASC");
  $units4 = $db->query("SELECT * FROM units WHERE deleted = '0' ORDER BY units ASC");
  $states = $db->query("SELECT * FROM states ORDER BY states ASC");

  if ($role != 'SADMIN'){
    $customers = $db->query("SELECT c.*, s.states AS state_name FROM customers c LEFT JOIN states s ON c.states = s.id WHERE c.deleted = 0 AND c.customer = '".$company."' ORDER BY c.customer_name ASC");
    $suppliers = $db->query("SELECT sp.*, s.states AS state_name FROM supplies sp LEFT JOIN states s ON sp.states = s.id WHERE sp.deleted = 0 AND sp.customer = '".$company."' ORDER BY sp.supplier_name ASC");
    $grades = $db->query("SELECT * FROM grades WHERE deleted = 0 AND customer = '".$company."' ORDER BY units ASC");
    $grades2 = $db->query("SELECT * FROM grades WHERE deleted = 0 AND customer = '".$company."' ORDER BY units ASC");
    $gradesBulk = $db->query("SELECT * FROM grades WHERE deleted = 0 AND customer = '".$company."' ORDER BY units ASC");
    $gradesSupplier = $db->query("SELECT * FROM grades WHERE deleted = 0 AND customer = '".$company."' ORDER BY units ASC");
    $category = $db->query("SELECT * FROM categories WHERE deleted = 0 AND customer = '".$company."' ORDER BY category_name ASC");
    $packaging = $db->query("SELECT * FROM packaging WHERE deleted = 0 AND customer = '".$company."' ORDER BY packaging_name ASC");
    $currency = $db->query("SELECT * FROM currency WHERE deleted = 0 AND customer = '".$company."' ORDER BY currency ASC");
    $currency2 = $db->query("SELECT * FROM currency WHERE deleted = 0 AND customer = '".$company."' ORDER BY currency ASC");
    $currency3 = $db->query("SELECT * FROM currency WHERE deleted = 0 AND customer = '".$company."' ORDER BY currency ASC");
    $currency4 = $db->query("SELECT * FROM currency WHERE deleted = 0 AND customer = '".$company."' ORDER BY currency ASC");
    $currency5 = $db->query("SELECT * FROM currency WHERE deleted = 0 AND customer = '".$company."' ORDER BY currency ASC");
    $currency6 = $db->query("SELECT * FROM currency WHERE deleted = 0 AND customer = '".$company."' ORDER BY currency ASC");
  }
  else{
    $customers = $db->query("SELECT c.*, s.states AS state_name FROM customers c LEFT JOIN states s ON c.states = s.id WHERE c.deleted = 0 ORDER BY c.customer_name ASC");
    $suppliers = $db->query("SELECT sp.*, s.states AS state_name FROM supplies sp LEFT JOIN states s ON sp.states = s.id WHERE sp.deleted = 0 ORDER BY sp.supplier_name ASC");
    $grades = $db->query("SELECT * FROM grades WHERE deleted = 0 ORDER BY units ASC");
    $grades2 = $db->query("SELECT * FROM grades WHERE deleted = 0 ORDER BY units ASC");
    $gradesBulk = $db->query("SELECT * FROM grades WHERE deleted = 0 ORDER BY units ASC");
    $gradesSupplier = $db->query("SELECT * FROM grades WHERE deleted = 0 ORDER BY units ASC");
    $category = $db->query("SELECT * FROM categories WHERE deleted = 0 ORDER BY category_name ASC");
    $packaging = $db->query("SELECT * FROM packaging WHERE deleted = 0 ORDER BY packaging_name ASC");
    $currency = $db->query("SELECT * FROM currency WHERE deleted = 0 ORDER BY currency ASC");
    $currency2 = $db->query("SELECT * FROM currency WHERE deleted = 0 ORDER BY currency ASC");
    $currency3 = $db->query("SELECT * FROM currency WHERE deleted = 0 ORDER BY currency ASC");
    $currency4 = $db->query("SELECT * FROM currency WHERE deleted = 0 ORDER BY currency ASC");
    $currency5 = $db->query("SELECT * FROM currency WHERE deleted = 0 ORDER BY currency ASC");
    $currency6 = $db->query("SELECT * FROM currency WHERE deleted = 0 ORDER BY currency ASC");
  }

  // Default Currency
  $defaultCurrencyId = null;
  if ($curreny_stmt = $db->prepare("SELECT id FROM currency WHERE deleted = 0 AND customer = ? AND is_default = 1 LIMIT 1")) {
    $curreny_stmt->bind_param('s', $company);
    $curreny_stmt->execute();
    $curreny_result = $curreny_stmt->get_result();
    if ($curreny_row = $curreny_result->fetch_assoc()) {
      $defaultCurrencyId = $curreny_row['id'];
    }
    $curreny_stmt->close();
  }

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>

<div class="content-header" style="padding-bottom: 0;">
  <div class="container-fluid">
    <!-- Breadcrumb or minimal header can go here if needed -->
  </div>
</div>

<!-- Main content -->
<section class="content page-modern">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card results-card show-dt-controls">
          <div class="card-header">
            <div class="results-header-left">
              <h3 class="results-title"><i class="fas fa-box mr-2"></i><?=$languageArray['products_code'][$language]?></h3>
            </div>
            <div class="results-header-right d-flex flex-wrap" style="gap: 0.5rem;">
              <a href="template/Product_Template.xlsx" download class="btn btn-action btn-action-warning">
                <i class="fas fa-download"></i> <?=$languageArray['download_template_code'][$language]?>
              </a>
              <button type="button" id="uploadExcel" class="btn btn-action btn-action-success">
                <i class="fas fa-upload"></i> <?=$languageArray['upload_excel_code'][$language]?>
              </button>
              <button type="button" id="multiDeactivate" class="btn btn-action btn-action-danger">
                <i class="fas fa-trash-alt"></i> <?=$languageArray['delete_product_code'][$language]?>
              </button>
              <button type="button" class="btn btn-action btn-action-primary" id="addProducts">
                <i class="fas fa-plus"></i> <?=$languageArray['add_products_code'][$language]?>
              </button>
            </div>
          </div>
          <div class="card-body">
            <table id="productTable" class="table data-table">
              <thead>
                <tr>
                  <th><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
                  <th><?=$languageArray['category_code'][$language]?></th>
                  <th><?=$languageArray['product_code_code'][$language]?></th>
                  <th><?=$languageArray['product_name_code'][$language]?></th>
                  <th><?=$languageArray['weight_code'][$language]?></th>
                  <th><?=$languageArray['remark_code'][$language]?></th>
                  <th width="15%"><?=$languageArray['actions_code'][$language]?></th>
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
            <button type="button" class="btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
            <button type="button" class="btn-modern btn-modern-primary" id="uploadProduct"><i class="fas fa-check mr-1"></i><?=$languageArray['submit_code'][$language]?></button>
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

<!-- Product Modal -->
<div class="modal fade modal-modern" id="productModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form role="form" id="productForm">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle"><?=$languageArray['add_products_code'][$language]?></h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="id" name="id">

          <!-- Company (SADMIN only) -->
          <div class="modal-section" <?php if($role != 'SADMIN') echo 'style="display:none;"'; ?>>
            <div class="section-title"><i class="fas fa-building mr-2"></i><?=$languageArray['company_code'][$language]?></div>
            <select class="form-control select2" style="width:100%;" id="company" name="company" required>
              <?php $companies->data_seek(0); while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                <option value="<?=$rowCompany['id']?>" <?php if($rowCompany['id']==$company) echo 'selected';?>><?=$rowCompany['name']?></option>
              <?php } ?>
            </select>
          </div>

          <!-- Product Info -->
          <div class="modal-section">
            <div class="section-title"><i class="fas fa-info-circle mr-2"></i><?=$languageArray['product_information_code'][$language]?></div>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['product_code_code'][$language]?> <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="code" id="code" placeholder="<?=$languageArray['enter_product_code_code'][$language]?>" required>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['product_name_code'][$language]?> <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="product" id="product" placeholder="<?=$languageArray['enter_product_name_code'][$language]?>" required>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['states_code'][$language]?></label>
                  <select class="form-control select2" id="state" name="state[]" multiple style="width:100%;">
                    <?php while($rowstates=mysqli_fetch_assoc($states)){ ?>
                      <option value="<?=$rowstates['id']?>"><?=$rowstates['states']?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['weight_code'][$language]?></label>
                  <input type="number" class="form-control" name="weight" id="weight" placeholder="0.000">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['unit_code'][$language]?></label>
                  <select class="form-control select2" id="uom" name="uom" style="width:100%;">
                    <option selected>-</option>
                    <?php while($rowunits=mysqli_fetch_assoc($units)){ ?>
                      <option value="<?=$rowunits['id']?>"><?=$rowunits['units']?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['category_code'][$language]?></label>
                  <select class="form-control select2" id="productCategory" name="productCategory" style="width:100%;">
                    <option value="" selected>-</option>
                    <?php while($rowCat=mysqli_fetch_assoc($category)){ ?>
                      <option value="<?=$rowCat['id']?>"><?=$rowCat['category_name']?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['packaging_code'][$language]?> / <?=$languageArray['uom_code'][$language]?></label>
                  <select class="form-control select2" id="productPackaging" name="productPackaging" style="width:100%;">
                    <option value="" selected>-</option>
                    <?php while($rowPack=mysqli_fetch_assoc($packaging)){ ?>
                      <option value="<?=$rowPack['id']?>"><?=$rowPack['packaging_name']?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group-modern">
                  <label class="form-label-modern"><?=$languageArray['colour_code'][$language] ?? 'Colour'?></label>
                  <div class="input-group" id="productColourPicker">
                    <input type="text" class="form-control" id="productColour" name="productColour" placeholder="#FFFFFF">
                    <div class="input-group-append">
                      <span class="input-group-text"><i class="fas fa-palette"></i></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="form-group-modern">
              <label class="form-label-modern"><?=$languageArray['remark_code'][$language]?></label>
              <textarea class="form-control" id="remark" name="remark" placeholder="<?=$languageArray['enter_remark_code'][$language]?>" rows="2"></textarea>
            </div>
          </div>

          <!-- Pricing -->
          <div class="modal-section">
            <div class="section-title"><i class="fas fa-tags mr-2"></i><?=$languageArray['pricing_code'][$language] ?? 'Pricing'?></div>
            <div class="row">
              <div class="col-md-6">
                <div class="pricing-card pricing-card-sell">
                  <div class="pricing-card-header">
                    <i class="fas fa-arrow-up"></i>
                    <span><?=$languageArray['selling_price_code'][$language]?></span>
                  </div>
                  <div class="pricing-card-body">
                    <div class="row">
                      <div class="col-4">
                        <label class="form-label-modern"><?=$languageArray['type_code'][$language] ?? 'Type'?></label>
                        <select class="form-control form-control-sm" id="pricingType" name="pricingType">
                          <option selected><?=$languageArray['fixed_code'][$language]?></option>
                          <option><?=$languageArray['float_code'][$language]?></option>
                        </select>
                      </div>
                      <div class="col-4">
                        <label class="form-label-modern"><?=$languageArray['currency_code'][$language] ?? 'Currency'?></label>
                        <select class="form-control form-control-sm select2" id="pricingCurrency" name="pricingCurrency">
                          <?php $currency->data_seek(0); while($rowcurrency=mysqli_fetch_assoc($currency)){ ?>
                            <option value="<?=$rowcurrency['id']?>"><?=$rowcurrency['currency']?></option>
                          <?php } ?>
                        </select>
                      </div>
                      <div class="col-4">
                        <label class="form-label-modern"><?=$languageArray['price_code'][$language] ?? 'Price'?></label>
                        <input type="number" class="form-control form-control-sm" name="price" id="price" placeholder="0.00" value="0.00">
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="pricing-card pricing-card-buy">
                  <div class="pricing-card-header">
                    <i class="fas fa-arrow-down"></i>
                    <span><?=$languageArray['purchasing_price_code'][$language]?></span>
                  </div>
                  <div class="pricing-card-body">
                    <div class="row">
                      <div class="col-4">
                        <label class="form-label-modern"><?=$languageArray['type_code'][$language] ?? 'Type'?></label>
                        <select class="form-control form-control-sm" id="purchasingPricingType" name="purchasingPricingType">
                          <option selected><?=$languageArray['fixed_code'][$language]?></option>
                          <option><?=$languageArray['float_code'][$language]?></option>
                        </select>
                      </div>
                      <div class="col-4">
                        <label class="form-label-modern"><?=$languageArray['currency_code'][$language] ?? 'Currency'?></label>
                        <select class="form-control form-control-sm select2" id="purchasingPricingCurrency" name="purchasingPricingCurrency">
                          <?php $currency2->data_seek(0); while($rowcurrency=mysqli_fetch_assoc($currency2)){ ?>
                            <option value="<?=$rowcurrency['id']?>"><?=$rowcurrency['currency']?></option>
                          <?php } ?>
                        </select>
                      </div>
                      <div class="col-4">
                        <label class="form-label-modern"><?=$languageArray['price_code'][$language] ?? 'Price'?></label>
                        <input type="number" class="form-control form-control-sm" name="purchasingPrice" id="purchasingPrice" placeholder="0.00" value="0.00">
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Product Image -->
          <div class="modal-section">
            <div class="section-title"><i class="fas fa-image mr-2"></i><?=$languageArray['product_image_code'][$language]?></div>
            <div class="row align-items-center">
              <div class="col-md-6">
                <div class="upload-zone" id="productImageDropzone">
                  <i class="fas fa-cloud-upload-alt"></i>
                  <p><?=$languageArray['click_or_drag_to_upload_code'][$language]?></p>
                  <span><?=$languageArray['file_format_max_size_code'][$language]?></span>
                  <input type="file" id="productImage" name="productImage" accept="image/png,image/jpeg,image/jpg" style="display:none;">
                </div>
              </div>
              <div class="col-md-6 text-center">
                <div id="productImagePreview" style="display:none;">
                  <img id="productImageThumb" src="" style="max-height:140px; max-width:100%; border-radius:8px; border:1px solid var(--border-color); object-fit:contain;">
                  <div class="mt-2">
                    <button type="button" id="removeProductImage" class="btn-drawer btn-drawer-secondary btn-sm"><i class="fas fa-trash mr-1"></i><?=$languageArray['remove_code'][$language]?></button>
                  </div>
                </div>
                <div id="productImagePlaceholder" style="color:var(--text-muted);">
                  <i class="fas fa-image fa-3x"></i>
                  <p class="mt-2 mb-0"><?=$languageArray['no_image_selected_code'][$language]?></p>
                </div>
              </div>
            </div>
          </div>

          <!-- Ranges Set -->
          <div class="modal-section collapsible-section">
            <div class="section-header-toggle" id="rangeSetHeader">
              <div class="section-title mb-0"><i class="fas fa-sliders-h mr-2"></i><?=$languageArray['ranges_set_code'][$language]?></div>
              <input type="hidden" name="rangeSet" id="rangeSet" value="0">
              <label class="toggle-switch">
                <input type="checkbox" id="rangeSetCheckbox">
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div id="rangeWeightFields" class="collapsible-content" style="display:none;">
              <div class="row mt-3">
                <div class="col-md-4">
                  <div class="form-group-modern">
                    <label class="form-label-modern" style="color:#28a745;"><?=$languageArray['ok_weight_code'][$language]?></label>
                    <div class="input-group">
                      <input type="number" step="any" class="form-control" id="okWeight" name="okWeight" placeholder="0.000" style="border-color:#28a745;">
                      <select class="form-control" id="okWeightUnit" name="okWeightUnit" style="max-width:80px;">
                        <?php $units2->data_seek(0); while($r=mysqli_fetch_assoc($units2)){ ?><option value="<?=$r['id']?>"><?=$r['units']?></option><?php } ?>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group-modern">
                    <label class="form-label-modern" style="color:#ffc107;"><?=$languageArray['lo_weight_code'][$language]?></label>
                    <div class="input-group">
                      <input type="number" step="any" class="form-control" id="loWeight" name="loWeight" placeholder="0.000" style="border-color:#ffc107;">
                      <select class="form-control" id="loWeightUnit" name="loWeightUnit" style="max-width:80px;">
                        <?php $units3->data_seek(0); while($r=mysqli_fetch_assoc($units3)){ ?><option value="<?=$r['id']?>"><?=$r['units']?></option><?php } ?>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group-modern">
                    <label class="form-label-modern" style="color:#dc3545;"><?=$languageArray['hi_weight_code'][$language]?></label>
                    <div class="input-group">
                      <input type="number" step="any" class="form-control" id="hiWeight" name="hiWeight" placeholder="0.000" style="border-color:#dc3545;">
                      <select class="form-control" id="hiWeightUnit" name="hiWeightUnit" style="max-width:80px;">
                        <?php $units4->data_seek(0); while($r=mysqli_fetch_assoc($units4)){ ?><option value="<?=$r['id']?>"><?=$r['units']?></option><?php } ?>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Grades -->
          <div class="modal-section">
            <div class="section-title mb-3"><i class="fas fa-layer-group mr-2"></i><?=$languageArray['grades_code'][$language]?></div>
            <ul class="nav nav-tabs" id="gradeTypeTabs">
              <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#gradeLocalTab" style="font-size:0.8rem; padding:0.5rem 0.75rem;"><?=$languageArray['local_code'][$language] ?? 'Local'?></a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#gradeExportTab" style="font-size:0.8rem; padding:0.5rem 0.75rem;"><?=$languageArray['export_code'][$language] ?? 'Export'?></a>
              </li>
            </ul>
            <div class="tab-content mt-2">
              <div class="tab-pane fade show active" id="gradeLocalTab">
                <div class="mb-2 text-right">
                  <button type="button" class="btn-modern btn-modern-primary btn-sm add-grade" data-type="Local"><i class="fas fa-plus mr-1"></i><?=$languageArray['add_grade_code'][$language]?></button>
                </div>
                <div id="gradeLocalRowsContainer">
                  <div id="gradeLocalEmptyState" class="empty-state">
                    <i class="fas fa-layer-group"></i>
                    <p><?=$languageArray['no_grades_added_code'][$language] ?? 'No grades added yet'?></p>
                    <span><?=$languageArray['click_add_grade_code'][$language] ?? 'Click "Add Grade" to add pricing by grade'?></span>
                  </div>
                </div>
              </div>
              <div class="tab-pane fade" id="gradeExportTab">
                <div class="mb-2 text-right">
                  <button type="button" class="btn-modern btn-modern-primary btn-sm add-grade" data-type="Export"><i class="fas fa-plus mr-1"></i><?=$languageArray['add_grade_code'][$language]?></button>
                </div>
                <div id="gradeExportRowsContainer">
                  <div id="gradeExportEmptyState" class="empty-state">
                    <i class="fas fa-layer-group"></i>
                    <p><?=$languageArray['no_grades_added_code'][$language] ?? 'No grades added yet'?></p>
                    <span><?=$languageArray['click_add_grade_code'][$language] ?? 'Click "Add Grade" to add pricing by grade'?></span>
                  </div>
                </div>
              </div>
            </div>
            <!-- Hidden table for form data submission -->
            <table style="display:none;"><tbody id="gradeTable"></tbody></table>
          </div>

        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn-modern btn-modern-primary" id="submitMember"><i class="fas fa-check mr-1"></i><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Customers Modal -->
<div class="modal fade modal-modern" id="customersModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form role="form" id="customersForm">
        <input type="hidden" id="customerProductId" name="product_id">
        <div class="modal-header bg-gradient-success">
          <h5 class="modal-title text-white"><i class="fas fa-users mr-2"></i><?=$languageArray['customers_code'][$language]?></h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body p-2">
          <ul class="nav nav-tabs mb-2" id="customerSupplierTabs">
            <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tabCustomers" id="tabCustomersLink"><?=$languageArray['customers_code'][$language]?></a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tabSuppliers" id="tabSuppliersLink"><?=$languageArray['supplier_code'][$language]?></a></li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane fade show active" id="tabCustomers">
              <div class="mb-2 d-flex justify-content-between align-items-center">
                <div>
                  <label class="mr-2 mb-0" style="font-size:0.85rem;"><?=$languageArray['filter_code'][$language] ?? 'Filter'?>:</label>
                  <select class="form-control form-control-sm d-inline-block" id="customerTypeFilter" style="width:auto;">
                    <option value=""><?=$languageArray['all_code'][$language] ?? 'All'?></option>
                    <option value="Local"><?=$languageArray['local_code'][$language] ?? 'Local'?></option>
                    <option value="Export"><?=$languageArray['export_code'][$language] ?? 'Export'?></option>
                  </select>
                </div>
                <div>
                  <button type="button" class="btn btn-warning btn-sm" id="bulkPriceByState"><i class="fas fa-tags mr-1"></i><?=$languageArray['bulk_price_by_state_code'][$language]?></button>
                  <button type="button" class="btn btn-success btn-sm add-customer"><i class="fas fa-plus mr-1"></i><?=$languageArray['add_customers_code'][$language]?></button>
                </div>
              </div>
              <div id="customerCards" class="customer-supplier-cards"></div>
              <div id="customerEmptyState" class="empty-state">
                <i class="fas fa-user-plus"></i>
                <p><?=$languageArray['no_customers_code'][$language] ?? 'No customers added'?></p>
                <span><?=$languageArray['click_add_customer_code'][$language] ?? 'Click the button above to add a customer'?></span>
              </div>
            </div>
            <div class="tab-pane fade" id="tabSuppliers">
              <div class="mb-2 d-flex justify-content-between align-items-center">
                <div>
                  <label class="mr-2 mb-0" style="font-size:0.85rem;"><?=$languageArray['filter_code'][$language] ?? 'Filter'?>:</label>
                  <select class="form-control form-control-sm d-inline-block" id="supplierTypeFilter" style="width:auto;">
                    <option value=""><?=$languageArray['all_code'][$language] ?? 'All'?></option>
                    <option value="Local"><?=$languageArray['local_code'][$language] ?? 'Local'?></option>
                    <option value="Export"><?=$languageArray['export_code'][$language] ?? 'Export'?></option>
                  </select>
                </div>
                <div>
                  <button type="button" class="btn btn-warning btn-sm" id="bulkPriceByStateSupplier"><i class="fas fa-tags mr-1"></i><?=$languageArray['bulk_price_by_state_code'][$language]?></button>
                  <button type="button" class="btn btn-success btn-sm add-supplier"><i class="fas fa-plus mr-1"></i><?=$languageArray['add_supplier_code'][$language]?></button>
                </div>
              </div>
              <div id="supplierCards" class="customer-supplier-cards"></div>
              <div id="supplierEmptyState" class="empty-state">
                <i class="fas fa-truck"></i>
                <p><?=$languageArray['no_suppliers_code'][$language] ?? 'No suppliers added'?></p>
                <span><?=$languageArray['click_add_supplier_code'][$language] ?? 'Click the button above to add a supplier'?></span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn-modern btn-modern-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn-modern btn-modern-primary" id="submitCustomers"><i class="fas fa-check mr-1"></i><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Bulk Price by State Modal -->
<div class="modal fade modal-modern" id="bulkPriceByStateModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-gradient-warning">
        <h5 class="modal-title text-white"><i class="fas fa-tags mr-2"></i><?=$languageArray['bulk_price_by_state_code'][$language]?></h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="bulkTargetType" value="customer">
        <div class="form-group">
          <label class="font-weight-bold"><?=$languageArray['states_code'][$language]?> <span class="text-danger">*</span></label>
          <select class="form-control select2" id="bulkState" multiple style="width:100%;">
            <?php
              $statesBulk = $db->query("SELECT * FROM states ORDER BY states ASC");
              while($rowStateBulk = mysqli_fetch_assoc($statesBulk)){
            ?>
              <option value="<?=$rowStateBulk['states']?>"><?=$rowStateBulk['states']?></option>
            <?php } ?>
          </select>
        </div>
        <div class="form-group">
          <label class="font-weight-bold"><?=$languageArray['grade_code'][$language]?></label>
          <select class="form-control select2" id="bulkGrade" style="width:100%;">
            <option value="">-</option>
            <?php while($rowGradeBulk = mysqli_fetch_assoc($gradesBulk)){ ?>
              <option value="<?=$rowGradeBulk['id']?>"><?=$rowGradeBulk['units']?></option>
            <?php } ?>
          </select>
        </div>
        <div class="form-group" id="bulkPricingTypeGroup">
          <label class="font-weight-bold"><?=$languageArray['pricing_type_code'][$language]?></label>
          <select class="form-control" id="bulkPricingType">
            <option value="Standard"><?=$languageArray['standard_code'][$language]?></option>
            <option value="Fixed"><?=$languageArray['fixed_code'][$language]?></option>
            <option value="Float"><?=$languageArray['float_code'][$language]?></option>
          </select>
        </div>
        <div class="form-group" id="bulkSellingPriceGroup">
          <label class="font-weight-bold"><?=$languageArray['selling_price_code'][$language]?></label>
          <input type="number" class="form-control" id="bulkSellingPrice" placeholder="0.00" value="0">
        </div>
        <div class="form-group" id="bulkPurchasingPricingTypeGroup">
          <label class="font-weight-bold"><?=$languageArray['purchasing_pricing_type_code'][$language]?></label>
          <select class="form-control" id="bulkPurchasingPricingType">
            <option value="Standard"><?=$languageArray['standard_code'][$language]?></option>
            <option value="Fixed"><?=$languageArray['fixed_code'][$language]?></option>
            <option value="Float"><?=$languageArray['float_code'][$language]?></option>
          </select>
        </div>
        <div class="form-group" id="bulkPurchasingPriceGroup">
          <label class="font-weight-bold"><?=$languageArray['purchasing_price_code'][$language]?></label>
          <input type="number" class="form-control" id="bulkPurchasingPrice" placeholder="0.00" value="0">
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn-modern btn-modern-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i><?=$languageArray['close_code'][$language]?></button>
        <button type="button" class="btn-modern btn-modern-primary" id="bulkPriceByStateSave"><i class="fas fa-check mr-1"></i><?=$languageArray['save_code'][$language]?></button>
      </div>
    </div>
  </div>
</div>

<script type="text/html" id="customerDetail">
  <div class="cs-card details">
    <input type="hidden" id="customerProductId" name="customerProductId">
    <input type="hidden" id="customerRowType" name="customerRowType" value="customer">
    <input type="hidden" id="no" name="no">
    <div class="cs-card-header">
      <span class="cs-card-number"></span>
      <select class="form-control form-control-sm select2" id="customers" name="customers" data-placeholder="<?=$languageArray['select_customer_code'][$language] ?? 'Select Customer'?>">
        <option value=""></option>
        <?php $customers->data_seek(0); while($rowCustomer=mysqli_fetch_assoc($customers)){ ?>
          <option value="<?=$rowCustomer['id']?>" data-state="<?=$rowCustomer['state_name']?>"><?=$rowCustomer['customer_name']?></option>
        <?php } ?>
      </select>
      <select class="form-control form-control-sm customer-type-select" id="customerType" name="customerType" style="width:90px; flex-shrink:0;">
        <option value="Local"><?=$languageArray['local_code'][$language] ?? 'Local'?></option>
        <option value="Export"><?=$languageArray['export_code'][$language] ?? 'Export'?></option>
      </select>
      <button type="button" class="cs-card-remove" id="remove"><i class="fas fa-times"></i></button>
    </div>
    <div class="cs-card-body">
      <div class="cs-card-field">
        <label><?=$languageArray['states_code'][$language]?></label>
        <input type="text" class="form-control form-control-sm customer-state-display" readonly>
      </div>
      <div class="cs-card-field">
        <label><?=$languageArray['grade_code'][$language]?></label>
        <select class="form-control form-control-sm select2" id="customerGrade" name="customerGrade" data-placeholder="-">
          <option value="">-</option>
          <?php $grades2->data_seek(0); while($gradeListRow=mysqli_fetch_assoc($grades2)){ ?>
            <option value="<?=$gradeListRow['id']?>"><?=$gradeListRow['units']?></option>
          <?php } ?>
        </select>
      </div>
      <div class="cs-card-field">
        <label><?=$languageArray['pricing_type_code'][$language]?></label>
        <select class="form-control form-control-sm" id="customerPricingType" name="customerPricingType">
          <option selected><?=$languageArray['standard_code'][$language]?></option>
          <option><?=$languageArray['fixed_code'][$language]?></option>
          <option><?=$languageArray['float_code'][$language]?></option>
        </select>
      </div>
      <div class="cs-card-field">
        <label><?=$languageArray['currency_code'][$language] ?? 'Currency'?></label>
        <select class="form-control form-control-sm select2" id="customerCurrency" name="customerCurrency" data-placeholder="-">
          <option value="">-</option>
          <?php $currency5->data_seek(0); while($rowCur5=mysqli_fetch_assoc($currency5)){ ?>
            <option value="<?=$rowCur5['id']?>"><?=$rowCur5['currency']?></option>
          <?php } ?>
        </select>
      </div>
      <div class="cs-card-field">
        <label><?=$languageArray['selling_price_code'][$language]?></label>
        <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="customerPrice" name="customerPrice" value="0">
      </div>
    </div>
  </div>
</script>

<script type="text/html" id="supplierDetail">
  <div class="cs-card details">
    <input type="hidden" id="supplierProductId" name="supplierProductId">
    <input type="hidden" id="supplierRowType" name="supplierRowType" value="supplier">
    <input type="hidden" id="supplierNo" name="supplierNo">
    <div class="cs-card-header">
      <span class="cs-card-number"></span>
      <select class="form-control form-control-sm select2" id="suppliers" name="suppliers" data-placeholder="<?=$languageArray['select_supplier_code'][$language] ?? 'Select Supplier'?>">
        <option value=""></option>
        <?php $suppliers->data_seek(0); while($rowSupplier=mysqli_fetch_assoc($suppliers)){ ?>
          <option value="<?=$rowSupplier['id']?>" data-state="<?=$rowSupplier['state_name']?>"><?=$rowSupplier['supplier_name']?></option>
        <?php } ?>
      </select>
      <select class="form-control form-control-sm supplier-type-select" id="supplierType" name="supplierType" style="width:90px; flex-shrink:0;">
        <option value="Local"><?=$languageArray['local_code'][$language] ?? 'Local'?></option>
        <option value="Export"><?=$languageArray['export_code'][$language] ?? 'Export'?></option>
      </select>
      <button type="button" class="cs-card-remove" id="removeSupplier"><i class="fas fa-times"></i></button>
    </div>
    <div class="cs-card-body">
      <div class="cs-card-field">
        <label><?=$languageArray['states_code'][$language]?></label>
        <input type="text" class="form-control form-control-sm supplier-state-display" readonly>
      </div>
      <div class="cs-card-field">
        <label><?=$languageArray['grade_code'][$language]?></label>
        <select class="form-control form-control-sm select2" id="supplierGrade" name="supplierGrade" data-placeholder="-">
          <option value="">-</option>
          <?php $gradesSupplier->data_seek(0); while($gradeSupRow=mysqli_fetch_assoc($gradesSupplier)){ ?>
            <option value="<?=$gradeSupRow['id']?>"><?=$gradeSupRow['units']?></option>
          <?php } ?>
        </select>
      </div>
      <div class="cs-card-field">
        <label><?=$languageArray['purchasing_pricing_type_code'][$language]?></label>
        <select class="form-control form-control-sm" id="supplierPricingType" name="supplierPricingType">
          <option selected><?=$languageArray['standard_code'][$language]?></option>
          <option><?=$languageArray['fixed_code'][$language]?></option>
          <option><?=$languageArray['float_code'][$language]?></option>
        </select>
      </div>
      <div class="cs-card-field">
        <label><?=$languageArray['currency_code'][$language] ?? 'Currency'?></label>
        <select class="form-control form-control-sm select2" id="supplierCurrency" name="supplierCurrency" data-placeholder="-">
          <option value="">-</option>
          <?php $currency6->data_seek(0); while($rowCur6=mysqli_fetch_assoc($currency6)){ ?>
            <option value="<?=$rowCur6['id']?>"><?=$rowCur6['currency']?></option>
          <?php } ?>
        </select>
      </div>
      <div class="cs-card-field">
        <label><?=$languageArray['purchasing_price_code'][$language]?></label>
        <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="supplierPrice" name="supplierPrice" value="0">
      </div>
    </div>
  </div>
</script>

<script type="text/html" id="gradeDetail">
  <tr class="details">
    <td>
      <input type="hidden" id="gradeNo" name="gradeNo">
      <input type="hidden" id="productGradeId" name="productGradeId">
      <input type="hidden" id="grades" name="grades">
      <input type="hidden" id="gradeType" name="gradeType">
      <input type="hidden" id="gradePricingType" name="gradePricingType">
      <input type="hidden" id="gradePricingCurrency" name="gradePricingCurrency">
      <input type="hidden" id="gradePrice" name="gradePrice">
      <input type="hidden" id="gradePurchasingPricingType" name="gradePurchasingPricingType">
      <input type="hidden" id="gradePurchasingPricingCurrency" name="gradePurchasingPricingCurrency">
      <input type="hidden" id="gradePurchasingPrice" name="gradePurchasingPrice">
    </td>
  </tr>
</script>

<script type="text/html" id="gradeRowTemplate">
  <div class="dynamic-card" data-index="{index}" data-type="{type}">
    <div class="dynamic-card-body">
      <div class="dynamic-card-row dynamic-card-header">
        <select class="form-control form-control-sm select2 grade-select" id="gradesRow{index}" data-index="{index}" style="width:100%;">
          <?php $grades->data_seek(0); while($rowGrade=mysqli_fetch_assoc($grades)){ ?>
            <option value="<?=$rowGrade['id']?>"><?=$rowGrade['units']?></option>
          <?php } ?>
        </select>
        <button type="button" class="dynamic-card-remove" data-index="{index}"><i class="fas fa-times"></i></button>
      </div>
      <div class="dynamic-card-row">
        <span class="dynamic-card-label dynamic-card-label-success"><i class="fas fa-arrow-up"></i> <?=$languageArray['sell_code'][$language] ?? 'Sell'?></span>
        <select class="form-control form-control-sm" id="gradePricingTypeRow{index}">
          <option selected><?=$languageArray['standard_code'][$language]?></option>
          <option><?=$languageArray['fixed_code'][$language]?></option>
          <option><?=$languageArray['float_code'][$language]?></option>
        </select>
        <select class="form-control form-control-sm select2 currency-select" id="gradePricingCurrencyRow{index}" style="width:80px; flex-shrink:0;">
          <?php $currency3->data_seek(0); while($rowCur3=mysqli_fetch_assoc($currency3)){ ?>
            <option value="<?=$rowCur3['id']?>"><?=$rowCur3['currency']?></option>
          <?php } ?>
        </select>
        <input type="number" class="form-control form-control-sm" id="gradePriceRow{index}" placeholder="0.00" value="0" style="flex:1; min-width:100px;">
        <span class="dynamic-card-label dynamic-card-label-warning"><i class="fas fa-arrow-down"></i> <?=$languageArray['buy_code'][$language] ?? 'Buy'?></span>
        <select class="form-control form-control-sm" id="gradePurchasingPricingTypeRow{index}">
          <option selected><?=$languageArray['standard_code'][$language]?></option>
          <option><?=$languageArray['fixed_code'][$language]?></option>
          <option><?=$languageArray['float_code'][$language]?></option>
        </select>
        <select class="form-control form-control-sm select2 currency-select" id="gradePurchasingPricingCurrencyRow{index}" style="width:80px; flex-shrink:0;">
          <?php $currency4->data_seek(0); while($rowCur4=mysqli_fetch_assoc($currency4)){ ?>
            <option value="<?=$rowCur4['id']?>"><?=$rowCur4['currency']?></option>
          <?php } ?>
        </select>
        <input type="number" class="form-control form-control-sm" id="gradePurchasingPriceRow{index}" placeholder="0.00" value="0" style="flex:1; min-width:100px;">
      </div>
    </div>
  </div>
</script>

<script>
var productsTableLanguage = {
  'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
  'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
};
var defaultCurrencyId = '<?= $defaultCurrencyId ?>';
var productsCompany = <?=$company ?>;
var productsText = {
  addTitle: '<?=$languageArray['add_products_code'][$language]?>',
  editTitle: '<?=$languageArray['edit_product_code'][$language] ?? 'Edit Product'?>'
};
</script>
<script src="modules/products/js/products.js?v=<?=time()?>"></script>
