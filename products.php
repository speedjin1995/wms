<?php
require_once 'php/db_connect.php';
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

<div class="content-header">
  <div class="container-fluid">
      <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0 text-dark"><?=$languageArray['products_code'][$language]?></h1>
    </div><!-- /.col -->
      </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</div><!-- /.content-header -->

<!-- Main content -->
<section class="content">
  <div class="container-fluid">
      <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col-4"></div>
            <div class="col-2">
              <button type="button" id="multiDeactivate" class="btn btn-block bg-gradient-danger btn-sm">
                <?=$languageArray['delete_product_code'][$language]?>
              </button>
            </div>
            <div class="col-2">
              <a href="template/Product_Template.xlsx" download>
                <button type="button" class="btn btn-block bg-gradient-info btn-sm">
                  <?=$languageArray['download_template_code'][$language]?>
                </button>
              </a>
            </div>
            <div class="col-2">
              <button type="button" id="uploadExcel" class="btn btn-block bg-gradient-success btn-sm">
                <?=$languageArray['upload_excel_code'][$language]?>
              </button>
            </div>
            <!-- <div class="col-2">
                <input type="file" id="fileInput" accept=".xlsx, .xls" />
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="importExcelbtn">Import Excel</button>
            </div>                             -->
            <div class="col-2">
              <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="addProducts"><?=$languageArray['add_products_code'][$language]?></button>
            </div>
          </div>
        </div>
        <div class="card-body">
          <table id="productTable" class="table table-bordered table-striped">
            <thead>
              <tr>
                <th><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
                <th><?=$languageArray['product_code_code'][$language]?></th>
                <th><?=$languageArray['product_name_code'][$language]?></th>
                <!--th>Price</th-->
                <th><?=$languageArray['weight_code'][$language]?></th>
                <th><?=$languageArray['remark_code'][$language]?></th>
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

<script src="plugins/jquery/jquery.min.js"></script>
<link rel="stylesheet" href="assets/css/modal-global.css">
<script src="plugins/jquery-validation/jquery.validate.min.js"></script>
<!-- Bootstrap -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE -->
<script src="dist/js/adminlte.js"></script>
<!-- OPTIONAL SCRIPTS -->
<script src="plugins/select2/js/select2.full.min.js"></script>
<script src="plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js"></script>
<script src="plugins/moment/moment.min.js"></script>
<script src="plugins/inputmask/jquery.inputmask.min.js"></script>
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="plugins/daterangepicker/daterangepicker.js"></script>
<script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<script src="plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<script src="plugins/chart.js/Chart.min.js"></script>
<script src="plugins/daterangepicker/daterangepicker.js"></script>

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
var customerRowCount = $("#customerCards").find(".details").length;
var gradeRowCount = $("#gradeTable").find(".details").length;
var supplierRowCount = $("#supplierCards").find(".details").length;
var defaultCurrencyId = '<?= $defaultCurrencyId ?>';

$(function () {
  $('#selectAllCheckbox').on('change', function() {
    var checkboxes = $('#productTable tbody input[type="checkbox"]');
    checkboxes.prop('checked', $(this).prop('checked')).trigger('change');
  });

  $('.select2').each(function() {
    $(this).select2({
        allowClear: true,
        placeholder: "Please Select",
        // Conditionally set dropdownParent based on the element’s location
        dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal') : undefined
    });
  });
  
  $("#productTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'ajax': {
      'url':'php/modules/products/loadProducts.php',
      'data': {
        id: <?=$company ?>
      }
    },
    'columns': [
      {
        // Add a checkbox with a unique ID for each row
        data: 'id', // Assuming 'serialNo' is a unique identifier for each row
        className: 'select-checkbox',
        orderable: false,
        render: function (data, type, row) {
            return '<input type="checkbox" class="select-checkbox" id="checkbox_' + data + '" value="'+data+'"/>';
        }
      },
      { data: 'product_code' },
      { data: 'product_name' },
      //{ data: 'price' },
      { data: 'weight' },
      { data: 'remark' },
      { 
        data: 'id',
        render: function ( data, type, row ) {
          return '<div class="row"><div class="col-3"><button type="button" onclick="edit('+data+')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button></div><div class="col-3"><button type="button" onclick="openCustomers('+data+')" class="btn btn-info btn-sm"><i class="fas fa-users"></i></button></div><div class="col-3"><button type="button" onclick="deactivate('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></div></div>';
        }
      }
    ],
    "rowCallback": function( row, data, index ) {
      if (data.is_manual == 'Y') {
        $(row).css('background-color', '#f8d7da');
      }
    },        
  });
    
  $('#productImageDropzone').on('click', function(e){
    if (!$(e.target).is('input')) $('#productImage').click();
  });

  $('#productImageDropzone').on('dragover', function(e){
    e.preventDefault();
    $(this).css({'border-color':'#007bff', 'background':'#e8f0fe'});
  }).on('dragleave', function(e){
    e.preventDefault();
    $(this).css({'border-color':'#adb5bd', 'background':'#fff'});
  }).on('drop', function(e){
    e.preventDefault();
    $(this).css({'border-color':'#adb5bd', 'background':'#fff'});
    var file = e.originalEvent.dataTransfer.files[0];
    if (file) setProductImagePreview(file);
  });

  $('#productImage').on('change', function(){
    if (this.files[0]) setProductImagePreview(this.files[0]);
  });

  $('#removeProductImage').on('click', function(){
    $('#productImage').val('');
    $('#productImageThumb').attr('src', '');
    $('#productImagePreview').hide();
    $('#productImagePlaceholder').show();
  });

  $.validator.setDefaults({
    submitHandler: function () {
      var gradeError = false;
      $('#gradeLocalRowsContainer .dynamic-card, #gradeExportRowsContainer .dynamic-card').each(function() {
        var index = $(this).data('index');
        var $select = $('#gradesRow'+index);
        if (!$select.val()) {
          gradeError = true;
          $select.next('.select2-container').find('.select2-selection').css({'border': '1px solid #dc3545'});
        } else {
          $select.next('.select2-container').find('.select2-selection').css({'border': ''});
        }
      });
      if (gradeError) {
        toastr["error"]("Please select a unit for all grade rows.", "Failed:");
        return false;
      }
      $('#spinnerLoading').show();
      var formData = new FormData($('#productForm')[0]);
      $.ajax({
        url: 'php/modules/products/products.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(data){
          var obj = JSON.parse(data);
          if(obj.status === 'success'){
            $('#productModal').modal('hide');
            toastr["success"](obj.message, "Success:");
            $('#productTable').DataTable().ajax.reload();
            $('#spinnerLoading').hide();
          } else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
            $('#spinnerLoading').hide();
          } else {
            toastr["error"]("Something wrong when edit", "Failed:");
            $('#spinnerLoading').hide();
          }
        }
      });
    }
  });

  $('#addProducts').on('click', function(){
    $('#productModal').find('#id').val("");
    $('#productModal').find('#code').val("");
    $('#productModal').find('#product').val("");
    $('#productModal').find('#remark').val("");
    $('#productModal').find('#pricingType').val("Float");
    $('#productModal').find('#pricingCurrency').val(defaultCurrencyId).trigger('change');
    $('#productModal').find('#price').val("0.00");
    $('#productModal').find('#purchasingPricingType').val("Float");
    $('#productModal').find('#purchasingPricingCurrency').val(defaultCurrencyId).trigger('change');
    $('#productModal').find('#purchasingPrice').val("0.00");
    $('#productModal').find('#weight').val("");
    $('#productModal').find('#productCategory').val("").trigger('change');
    $('#productModal').find('#productPackaging').val("").trigger('change');
    $('#productModal').find('#state').val("").trigger('change');
    $('#productModal').find('#uom').val("").trigger('change');
    setRangeSet(0);
    $('#okWeight').val(''); $('#okWeightUnit').val('kg');
    $('#loWeight').val(''); $('#loWeightUnit').val('kg');
    $('#hiWeight').val(''); $('#hiWeightUnit').val('kg');
    $('#productImage').val('');
    $('#productImagePreview').hide();
    $('#productImageThumb').attr('src', '');
    $('#productImagePlaceholder').show();

    // clear grade table and rows
    gradeRowCount = 0;
    $('#gradeTable').html('');
    $('#gradeLocalRowsContainer .dynamic-card').remove();
    $('#gradeExportRowsContainer .dynamic-card').remove();
    $('#gradeLocalEmptyState').show();
    $('#gradeExportEmptyState').show();

    $('#modalTitle').text('<?=$languageArray['add_products_code'][$language]?>');
    $('#productModal').modal('show');
    
    $('#productForm').validate({
      errorElement: 'span',
      errorPlacement: function (error, element) {
        error.addClass('invalid-feedback');
        element.closest('.form-group').append(error);
      },
      highlight: function (element, errorClass, validClass) {
        $(element).addClass('is-invalid');
      },
      unhighlight: function (element, errorClass, validClass) {
        $(element).removeClass('is-invalid');
      }
    });
  });

  $('#uploadExcel').on('click', function(){
    $('#uploadModal').modal('show');

    $('#uploadForm').validate({
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        highlight: function (element, errorClass, validClass) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).removeClass('is-invalid');
        }
    });
  });

  $('#uploadModal').find('#previewButton').on('click', function(){
    var fileInput = document.getElementById('fileInput');
    var file = fileInput.files[0];
    var reader = new FileReader();
    
    reader.onload = function(e) {
        var data = e.target.result;
        // Process data and display preview
        displayPreview(data);
    };

    reader.readAsBinaryString(file);
  });

  $('#uploadProduct').on('click', function(){
    $('#spinnerLoading').show();
    var formData = $('#uploadForm').serializeArray();
    var data = [];
    var rowIndex = -1;
    formData.forEach(function(field) {
    var match = field.name.match(/([a-zA-Z0-9]+)\[(\d+)\]/);
    if (match) {
      var fieldName = match[1];
      var index = parseInt(match[2], 10);
      if (index !== rowIndex) {
      rowIndex = index;
      data.push({});
      }
      data[index][fieldName] = field.value;
    }
    });

    // Send the JSON array to the server
    $.ajax({
        url: 'php/modules/products/uploadProduct.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            var obj = JSON.parse(response);
            if (obj.status === 'success') {
              $('#spinnerLoading').hide();
              $('#uploadModal').modal('hide');
              $('#productTable').DataTable().ajax.reload();
            } 
            else if (obj.status === 'failed') {
              $('#spinnerLoading').hide();
            } 
            else if (obj.status === 'error') {
              $('#spinnerLoading').hide();
              $('#uploadModal').modal('hide');
              $('#errorModal').find('#errorList').empty();
              var errorMessage = obj.message;
              for (var i = 0; i < errorMessage.length; i++) {
                $('#errorModal').find('#errorList').append(`<li>${errorMessage[i]}</li>`);                            
              }
              $('#errorModal').modal('show');
            } 
            else {
              $('#spinnerLoading').hide();
            }
        }
    });
  });

  $('#multiDeactivate').on('click', function () {
    $('#spinnerLoading').show();
    var selectedIds = []; // An array to store the selected 'id' values

    $("#productTable tbody input[type='checkbox']").each(function () {
      if (this.checked) {
          selectedIds.push($(this).val());
      }
    });

    if (selectedIds.length > 0) {
      if (confirm('Are you sure you want to cancel these items?')) {
          $.post('php/modules/products/deleteProduct.php', {userID: selectedIds, type: 'MULTI'}, function(data){
              var obj = JSON.parse(data);
              
              if(obj.status === 'success'){
                $('#productTable').DataTable().ajax.reload();
                $('#spinnerLoading').hide();
              }
              else if(obj.status === 'failed'){
                $('#spinnerLoading').hide();
              }
              else{
                $('#spinnerLoading').hide();
              }
          });
      }

      $('#spinnerLoading').hide();
    } 
    else {
        // Optionally, you can display a message or take another action if no IDs are selected
        alert("Please select at least one product to delete.");
        $('#spinnerLoading').hide();
    }     
  });

  // Find and remove selected customer cards
  $("#customerCards").on('click', 'button[id^="remove"]', function () {
    $(this).closest('.cs-card').remove();
    updateCustomerNumbers();
    toggleCustomerEmptyState();
  });

  function updateCustomerNumbers() {
    $("#customerCards .cs-card").each(function (index) {
      $(this).find('.cs-card-number').text(index + 1);
      $(this).find('input[name^="no"]').val(index + 1);
    });
  }

  function toggleCustomerEmptyState() {
    var filter = $('#customerTypeFilter').val();
    var visibleCount = filter ? $('#customerCards .cs-card[data-type="'+filter+'"]').length : $('#customerCards .cs-card').length;
    $('#customerEmptyState').toggle(visibleCount === 0);
  }

  // Filter customers by type
  $('#customerTypeFilter').on('change', function() {
    var filter = $(this).val();
    if (filter) {
      $('#customerCards .cs-card').hide();
      $('#customerCards .cs-card[data-type="'+filter+'"]').show();
    } else {
      $('#customerCards .cs-card').show();
    }
    toggleCustomerEmptyState();
  });

  // Update card data-type when type dropdown changes
  $('#customerCards').on('change', '.customer-type-select', function() {
    $(this).closest('.cs-card').attr('data-type', $(this).val());
    var filter = $('#customerTypeFilter').val();
    if (filter && $(this).val() !== filter) {
      $(this).closest('.cs-card').hide();
    }
    toggleCustomerEmptyState();
  });

  $(".add-customer").click(function(){
    $('#customerEmptyState').hide();
    var $addContents = $("#customerDetail").clone();
    $("#customerCards").append($addContents.html());

    var $card = $("#customerCards").find('.details:last');
    var defaultType = $('#customerTypeFilter').val() || 'Local';
    $card.attr("id", "detail" + customerRowCount).attr("data-index", customerRowCount).attr("data-type", defaultType);
    $card.find('.cs-card-number').text(customerRowCount + 1);
    $card.find('#remove').attr("id", "remove" + customerRowCount);
    $card.find('#customerProductId').attr('name', 'customerProductId['+customerRowCount+']').attr("id", "customerProductId" + customerRowCount);
    $card.find('#customerRowType').attr('name', 'customerRowType['+customerRowCount+']').attr("id", "customerRowType" + customerRowCount);
    $card.find('#customerType').attr('name', 'customerType['+customerRowCount+']').attr("id", "customerType" + customerRowCount).val(defaultType);
    $card.find('#no').attr('name', 'no['+customerRowCount+']').attr("id", "no" + customerRowCount).val(customerRowCount+1);
    $card.find('#customers').attr('name', 'customers['+customerRowCount+']').attr("id", "customers" + customerRowCount).select2({
      allowClear: true, placeholder: $("#customerDetail").find('#customers').data('placeholder'), dropdownParent: $('#customersModal')
    });
    $card.find('#customerGrade').attr('name', 'customerGrade['+customerRowCount+']').attr("id", "customerGrade" + customerRowCount).select2({
      allowClear: true, placeholder: "-", dropdownParent: $('#customersModal')
    });
    $card.find('#customerPricingType').attr('name', 'customerPricingType['+customerRowCount+']').attr("id", "customerPricingType" + customerRowCount);
    $card.find('#customerCurrency').attr('name', 'customerCurrency['+customerRowCount+']').attr("id", "customerCurrency" + customerRowCount).select2({
      allowClear: true, placeholder: "-", dropdownParent: $('#customersModal')
    });
    $card.find('#customerPrice').attr('name', 'customerPrice['+customerRowCount+']').attr("id", "customerPrice" + customerRowCount);

    $card.find('#customers' + customerRowCount).on('change', function() {
      var state = $(this).find('option:selected').data('state') || '';
      $(this).closest('.cs-card').find('.customer-state-display').val(state);
    }).trigger('change');

    customerRowCount++;
  });

  // Find and remove selected supplier cards
  $("#supplierCards").on('click', 'button[id^="removeSupplier"]', function () {
    $(this).closest('.cs-card').remove();
    updateSupplierNumbers();
    toggleSupplierEmptyState();
  });

  function updateSupplierNumbers() {
    $("#supplierCards .cs-card").each(function (index) {
      $(this).find('.cs-card-number').text(index + 1);
      $(this).find('input[name^="supplierNo"]').val(index + 1);
    });
  }

  function toggleSupplierEmptyState() {
    var filter = $('#supplierTypeFilter').val();
    var visibleCount = filter ? $('#supplierCards .cs-card[data-type="'+filter+'"]').length : $('#supplierCards .cs-card').length;
    $('#supplierEmptyState').toggle(visibleCount === 0);
  }

  // Filter suppliers by type
  $('#supplierTypeFilter').on('change', function() {
    var filter = $(this).val();
    if (filter) {
      $('#supplierCards .cs-card').hide();
      $('#supplierCards .cs-card[data-type="'+filter+'"]').show();
    } else {
      $('#supplierCards .cs-card').show();
    }
    toggleSupplierEmptyState();
  });

  // Update card data-type when type dropdown changes
  $('#supplierCards').on('change', '.supplier-type-select', function() {
    $(this).closest('.cs-card').attr('data-type', $(this).val());
    var filter = $('#supplierTypeFilter').val();
    if (filter && $(this).val() !== filter) {
      $(this).closest('.cs-card').hide();
    }
    toggleSupplierEmptyState();
  });

  $(".add-supplier").click(function(){
    $('#supplierEmptyState').hide();
    var $addContents = $("#supplierDetail").clone();
    $("#supplierCards").append($addContents.html());

    var $card = $("#supplierCards").find('.details:last');
    var defaultType = $('#supplierTypeFilter').val() || 'Local';
    $card.attr("id", "supplierDetail" + supplierRowCount).attr("data-index", supplierRowCount).attr("data-type", defaultType);
    $card.find('.cs-card-number').text(supplierRowCount + 1);
    $card.find('#removeSupplier').attr("id", "removeSupplier" + supplierRowCount);
    $card.find('#supplierProductId').attr('name', 'supplierProductId['+supplierRowCount+']').attr("id", "supplierProductId" + supplierRowCount);
    $card.find('#supplierRowType').attr('name', 'supplierRowType['+supplierRowCount+']').attr("id", "supplierRowType" + supplierRowCount);
    $card.find('#supplierType').attr('name', 'supplierType['+supplierRowCount+']').attr("id", "supplierType" + supplierRowCount).val(defaultType);
    $card.find('#supplierNo').attr('name', 'supplierNo['+supplierRowCount+']').attr("id", "supplierNo" + supplierRowCount).val(supplierRowCount+1);
    $card.find('#suppliers').attr('name', 'suppliers['+supplierRowCount+']').attr("id", "suppliers" + supplierRowCount).select2({
      allowClear: true, placeholder: $("#supplierDetail").find('#suppliers').data('placeholder'), dropdownParent: $('#customersModal')
    });
    $card.find('#supplierGrade').attr('name', 'supplierGrade['+supplierRowCount+']').attr("id", "supplierGrade" + supplierRowCount).select2({
      allowClear: true, placeholder: "-", dropdownParent: $('#customersModal')
    });
    $card.find('#supplierPricingType').attr('name', 'supplierPricingType['+supplierRowCount+']').attr("id", "supplierPricingType" + supplierRowCount);
    $card.find('#supplierCurrency').attr('name', 'supplierCurrency['+supplierRowCount+']').attr("id", "supplierCurrency" + supplierRowCount).select2({
      allowClear: true, placeholder: "-", dropdownParent: $('#customersModal')
    });
    $card.find('#supplierPrice').attr('name', 'supplierPrice['+supplierRowCount+']').attr("id", "supplierPrice" + supplierRowCount);

    $card.find('#suppliers' + supplierRowCount).on('change', function() {
      var state = $(this).find('option:selected').data('state') || '';
      $(this).closest('.cs-card').find('.supplier-state-display').val(state);
    }).trigger('change');

    supplierRowCount++;
  });

  $('#drawerClose, #drawerCancel, #drawerOverlay').on('click', function() {
    $('#productModal').modal('hide');
  });

  $('#rangeSetCheckbox').on('change', function() {
    setRangeSet($(this).is(':checked') ? 1 : 0);
  });

  // Remove grade row
  $('#gradeLocalRowsContainer, #gradeExportRowsContainer').on('click', '.dynamic-card-remove', function() {
    var index = $(this).data('index');
    $(this).closest('.dynamic-card').remove();
    $('#gradeTable').find('tr[data-index="'+index+'"]').remove();
    updateGradeEmptyState();
  });

  // Sync row changes to hidden table
  $('#gradeLocalRowsContainer, #gradeExportRowsContainer').on('change', 'select, input', function() {
    var index = $(this).closest('.dynamic-card').data('index');
    syncGradeRowToTable(index);
  });

  // Find and remove selected table rows
  $("#gradeTable").on('click', 'button[id^="remove"]', function () {
    var index = $(this).closest('tr').data('index');
    $(this).parents("tr").remove();
    $('#gradeRowsContainer').find('.dynamic-card[data-index="'+index+'"]').remove();
    updateGradeEmptyState();
  });

  $(".add-grade").click(function(){
    var gradeType = $(this).data('type'); // 'Local' or 'Export'
    var containerId = gradeType === 'Local' ? '#gradeLocalRowsContainer' : '#gradeExportRowsContainer';
    var emptyStateId = gradeType === 'Local' ? '#gradeLocalEmptyState' : '#gradeExportEmptyState';

    // Add visual row
    $(containerId).append(renderGradeRow(gradeRowCount, gradeType));
    $(emptyStateId).hide();

    // Init Select2 on new row
    $('#gradesRow'+gradeRowCount).select2({ allowClear: true, placeholder: "Please Select", dropdownParent: $('#productModal') });
    $('#gradePricingCurrencyRow'+gradeRowCount).val(defaultCurrencyId).select2({ allowClear: true, placeholder: "Select", dropdownParent: $('#productModal') });
    $('#gradePurchasingPricingCurrencyRow'+gradeRowCount).val(defaultCurrencyId).select2({ allowClear: true, placeholder: "Select", dropdownParent: $('#productModal') });

    // Add hidden table row for form submission
    var $addContents = $("#gradeDetail").clone();
    $("#gradeTable").append($addContents.html());

    var $tr = $("#gradeTable").find('.details:last');
    $tr.attr("id", "detail" + gradeRowCount).attr("data-index", gradeRowCount);
    $tr.find('#gradeNo').attr('name', 'gradeNo['+gradeRowCount+']').attr("id", "gradeNo" + gradeRowCount).val(gradeRowCount+1);
    $tr.find('#productGradeId').attr('name', 'productGradeId['+gradeRowCount+']').attr("id", "productGradeId" + gradeRowCount);
    $tr.find('#grades').attr('name', 'grades['+gradeRowCount+']').attr("id", "grades" + gradeRowCount);
    $tr.find('#gradeType').attr('name', 'gradeType['+gradeRowCount+']').attr("id", "gradeType" + gradeRowCount).val(gradeType);
    $tr.find('#gradePricingType').attr('name', 'gradePricingType['+gradeRowCount+']').attr("id", "gradePricingType" + gradeRowCount).val('Standard');
    $tr.find('#gradePricingCurrency').attr('name', 'gradePricingCurrency['+gradeRowCount+']').attr("id", "gradePricingCurrency" + gradeRowCount).val(defaultCurrencyId);
    $tr.find('#gradePrice').attr('name', 'gradePrice['+gradeRowCount+']').attr("id", "gradePrice" + gradeRowCount).val(0);
    $tr.find('#gradePurchasingPricingType').attr('name', 'gradePurchasingPricingType['+gradeRowCount+']').attr("id", "gradePurchasingPricingType" + gradeRowCount).val('Standard');
    $tr.find('#gradePurchasingPricingCurrency').attr('name', 'gradePurchasingPricingCurrency['+gradeRowCount+']').attr("id", "gradePurchasingPricingCurrency" + gradeRowCount).val(defaultCurrencyId);
    $tr.find('#gradePurchasingPrice').attr('name', 'gradePurchasingPrice['+gradeRowCount+']').attr("id", "gradePurchasingPrice" + gradeRowCount).val(0);

    gradeRowCount++;
  });

  $('#bulkPriceByStateModal').on('show.bs.modal', function() {
    $('#customersModal .modal-content').css('filter', 'blur(3px)');
  }).on('hide.bs.modal', function() {
    $('#customersModal .modal-content').css('filter', '');
  });

  $('#customersForm').on('submit', function(e) {
    e.preventDefault();
    $('#spinnerLoading').show();
    $.ajax({
      url: 'php/modules/products/productCustomerSupplier.php',
      type: 'POST',
      data: $(this).serialize(),
      success: function(data) {
        var obj = JSON.parse(data);
        $('#spinnerLoading').hide();
        if (obj.status === 'success') {
          toastr["success"](obj.message, "Success:");
          $('#customersModal').modal('hide');
        } else {
          toastr["error"](obj.message, "Failed:");
        }
      }
    });
  });

  $('#bulkPriceByState').on('click', function() {
    $('#bulkTargetType').val('customer');
    $('#bulkPricingTypeGroup, #bulkSellingPriceGroup').show();
    $('#bulkPurchasingPricingTypeGroup, #bulkPurchasingPriceGroup').hide();
    $('#bulkState').val(null).trigger('change');
    $('#bulkGrade').val('').trigger('change');
    $('#bulkPricingType').val('Standard');
    $('#bulkSellingPrice').val(0);
    $('#bulkPriceByStateModal').modal('show');
  });

  $('#bulkPriceByStateSupplier').on('click', function() {
    $('#bulkTargetType').val('supplier');
    $('#bulkPricingTypeGroup, #bulkSellingPriceGroup').hide();
    $('#bulkPurchasingPricingTypeGroup, #bulkPurchasingPriceGroup').show();
    $('#bulkState').val(null).trigger('change');
    $('#bulkGrade').val('').trigger('change');
    $('#bulkPurchasingPricingType').val('Standard');
    $('#bulkPurchasingPrice').val(0);
    $('#bulkPriceByStateModal').modal('show');
  });

  $('#bulkPriceByStateSave').on('click', function() {
    var selectedStates = $('#bulkState').val();
    if (!selectedStates || selectedStates.length === 0) {
      toastr["error"]("Please select at least one state.", "Error:");
      return;
    }
    var selectedGrade = $('#bulkGrade').val();
    var targetType = $('#bulkTargetType').val();
    var updated = 0;

    if (targetType === 'customer') {
      var pricingType = $('#bulkPricingType').val();
      var sellingPrice = $('#bulkSellingPrice').val();
      $('#customerCards .cs-card.details:visible').each(function() {
        var $row = $(this);
        var customerState = $row.find('select[id^="customers"]').find('option:selected').data('state');
        var rowGrade = $row.find('select[id^="customerGrade"]').val();
        var stateMatch = selectedStates.indexOf(String(customerState)) !== -1;
        var gradeMatch = selectedGrade === '' || String(rowGrade) === String(selectedGrade);
        if (stateMatch && gradeMatch) {
          $row.find('select[id^="customerPricingType"]').val(pricingType);
          $row.find('input[id^="customerPrice"]').val(sellingPrice);
          updated++;
        }
      });
    } else {
      var purchasingPricingType = $('#bulkPurchasingPricingType').val();
      var purchasingPrice = $('#bulkPurchasingPrice').val();
      $('#supplierCards .cs-card.details:visible').each(function() {
        var $row = $(this);
        var supplierState = $row.find('select[id^="suppliers"]').find('option:selected').data('state');
        var rowGrade = $row.find('select[id^="supplierGrade"]').val();
        var stateMatch = selectedStates.indexOf(String(supplierState)) !== -1;
        var gradeMatch = selectedGrade === '' || String(rowGrade) === String(selectedGrade);
        if (stateMatch && gradeMatch) {
          $row.find('select[id^="supplierPricingType"]').val(purchasingPricingType);
          $row.find('input[id^="supplierPrice"]').val(purchasingPrice);
          updated++;
        }
      });
    }

    $('#bulkPriceByStateModal').modal('hide');
    toastr["success"](updated + " row(s) updated.", "Success:");
  });
});

function renderGradeRow(index, type) {
  var html = $('#gradeRowTemplate').html();
  return html.replace(/{index}/g, index).replace(/{type}/g, type);
}

function syncGradeRowToTable(index) {
  var $row = $('#gradeTable').find('tr[data-index="'+index+'"]');
  $row.find('input[name^="grades"]').val($('#gradesRow'+index).val());
  $row.find('input[name^="gradePricingType"]').val($('#gradePricingTypeRow'+index).val());
  $row.find('input[name^="gradePricingCurrency"]').val($('#gradePricingCurrencyRow'+index).val());
  $row.find('input[name^="gradePrice"]').val($('#gradePriceRow'+index).val());
  $row.find('input[name^="gradePurchasingPricingType"]').val($('#gradePurchasingPricingTypeRow'+index).val());
  $row.find('input[name^="gradePurchasingPricingCurrency"]').val($('#gradePurchasingPricingCurrencyRow'+index).val());
  $row.find('input[name^="gradePurchasingPrice"]').val($('#gradePurchasingPriceRow'+index).val());
}

function updateGradeEmptyState() {
  if ($('#gradeLocalRowsContainer .dynamic-card').length === 0) {
    $('#gradeLocalEmptyState').show();
  } else {
    $('#gradeLocalEmptyState').hide();
  }
  if ($('#gradeExportRowsContainer .dynamic-card').length === 0) {
    $('#gradeExportEmptyState').show();
  } else {
    $('#gradeExportEmptyState').hide();
  }
}

function displayPreview(data) {
  // Parse the Excel data
  var workbook = XLSX.read(data, { type: 'binary' });

  // Get the first sheet
  var sheetName = workbook.SheetNames[0];
  var sheet = workbook.Sheets[sheetName];

  // Convert the sheet to an array of objects
  var jsonData = XLSX.utils.sheet_to_json(sheet, { header: 5 });

  // Get the headers
  var headers = Object.keys(jsonData[0] || {});

  // Ensure we handle cases where there may be less than 5 columns
  while (headers.length < 5) {
      headers.push(''); // Adding empty headers to reach 5 columns
  }

  // Create HTML table headers
  var htmlTable = '<table style="width:20%;"><thead><tr>';
  headers.forEach(function(header) {
      htmlTable += '<th>' + header + '</th>';
  });
  htmlTable += '</tr></thead><tbody>';

  // Iterate over the data and create table rows
  for (var i = 0; i < jsonData.length; i++) {
      htmlTable += '<tr>';
      var rowData = jsonData[i];

      for (var j = 0; j < 5 && j < headers.length; j++) {
          var cellData = rowData[headers[j]];
          var formattedData = cellData;

          // Check if cellData is a valid Excel date serial number and format it to DD/MM/YYYY
          if (typeof cellData === 'number' && cellData > 0) {
              var excelDate = XLSX.SSF.parse_date_code(cellData);
          }

          htmlTable += '<td><input type="text" id="'+headers[j].replace(/[^a-zA-Z0-9]/g, '')+i+'" name="'+headers[j].replace(/[^a-zA-Z0-9]/g, '')+'['+i+']" value="' + (formattedData == null ? '' : formattedData) + '" /></td>';
      }
      htmlTable += '</tr>';
  }

  htmlTable += '</tbody></table>';

  var previewTable = document.getElementById('previewTable');
  previewTable.innerHTML = htmlTable;
}

function setProductImagePreview(file) {
  var reader = new FileReader();
  reader.onload = function(e) {
    $('#productImageThumb').attr('src', e.target.result);
    $('#productImagePreview').show();
    $('#productImagePlaceholder').hide();
  };
  reader.readAsDataURL(file);
}

function edit(id){
  $('#spinnerLoading').show();
  $.post('php/modules/products/getProduct.php', {userID: id}, function(data){
    var obj = JSON.parse(data);
    
    if(obj.status === 'success'){
      $('#productModal').find('#id').val(obj.message.id);
      $('#productModal').find('#code').val(obj.message.product_code);
      $('#productModal').find('#product').val(obj.message.product_name);
      $('#productModal').find('#uom').val(obj.message.uom).trigger('change');
      $('#productModal').find('#remark').val(obj.message.remark);
      $('#productModal').find('#pricingType').val(obj.message.pricing_type);
      $('#productModal').find('#pricingCurrency').val(obj.message.pricing_currency).trigger('change');
      $('#productModal').find('#price').val(obj.message.price);
      $('#productModal').find('#purchasingPricingType').val(obj.message.purchasing_pricing_type);
      $('#productModal').find('#purchasingPricingCurrency').val(obj.message.purchasing_pricing_currency).trigger('change');
      $('#productModal').find('#purchasingPrice').val(obj.message.purchasing_price);
      $('#productModal').find('#weight').val(obj.message.weight);
      $('#productModal').find('#productCategory').val(obj.message.category).trigger('change');
      $('#productModal').find('#productPackaging').val(obj.message.packaging).trigger('change');
      $('#productModal').find('#state').val(obj.message.state).trigger('change');
      $('#productModal').find('#company').val(obj.message.customer).trigger('change');
      $('#productImage').val('');
      if (obj.message.product_image) {
        $('#productImageThumb').attr('src', 'php/viewPhoto.php?file=' + obj.message.product_image + '&type=file_table');
        $('#productImagePreview').show();
        $('#productImagePlaceholder').hide();
      } else {
        $('#productImagePreview').hide();
        $('#productImageThumb').attr('src', '');
        $('#productImagePlaceholder').show();
      }
      setRangeSet(obj.message.range_set == '1' ? 1 : 0);
      $('#okWeight').val(obj.message.ok_weight); $('#okWeightUnit').val(obj.message.ok_weight_unit || 'kg');
      $('#loWeight').val(obj.message.lo_weight); $('#loWeightUnit').val(obj.message.lo_weight_unit || 'kg');
      $('#hiWeight').val(obj.message.hi_weight); $('#hiWeightUnit').val(obj.message.hi_weight_unit || 'kg');

      // grade table and rows
      $('#gradeTable').html('');
      $('#gradeLocalRowsContainer .dynamic-card').remove();
      $('#gradeExportRowsContainer .dynamic-card').remove();
      gradeRowCount = 0;
      if (obj.message.productGrades.length > 0){
        var hasLocal = false, hasExport = false;
        for(var i = 0; i < obj.message.productGrades.length; i++){
          var item = obj.message.productGrades[i];
          var gradeType = item.type || 'Local';
          var containerId = gradeType === 'Local' ? '#gradeLocalRowsContainer' : '#gradeExportRowsContainer';
          if (gradeType === 'Local') hasLocal = true;
          else hasExport = true;
          
          // Add visual row
          $(containerId).append(renderGradeRow(gradeRowCount, gradeType));
          $('#gradesRow'+gradeRowCount).val(item.grade_id).select2({ allowClear: true, placeholder: "Please Select", dropdownParent: $('#productModal') });
          $('#gradePricingTypeRow'+gradeRowCount).val(item.pricing_type || 'Standard');
          $('#gradePricingCurrencyRow'+gradeRowCount).val(item.pricing_currency).select2({ allowClear: true, placeholder: "Select", dropdownParent: $('#productModal') });
          $('#gradePriceRow'+gradeRowCount).val(item.price || 0);
          $('#gradePurchasingPricingTypeRow'+gradeRowCount).val(item.purchasing_pricing_type || 'Standard');
          $('#gradePurchasingPricingCurrencyRow'+gradeRowCount).val(item.purchasing_pricing_currency).select2({ allowClear: true, placeholder: "Select", dropdownParent: $('#productModal') });
          $('#gradePurchasingPriceRow'+gradeRowCount).val(item.purchasing_price || 0);

          // Add hidden table row
          var $addContents = $("#gradeDetail").clone();
          $("#gradeTable").append($addContents.html());

          var $tr = $("#gradeTable").find('.details:last');
          $tr.attr("id", "detail" + gradeRowCount).attr("data-index", gradeRowCount);
          $tr.find('#productGradeId').attr('name', 'productGradeId['+gradeRowCount+']').attr("id", "productGradeId" + gradeRowCount).val(item.id);
          $tr.find('#gradeNo').attr('name', 'gradeNo['+gradeRowCount+']').attr("id", "gradeNo" + gradeRowCount).val(item.no);
          $tr.find('#grades').attr('name', 'grades['+gradeRowCount+']').attr("id", "grades" + gradeRowCount).val(item.grade_id);
          $tr.find('#gradeType').attr('name', 'gradeType['+gradeRowCount+']').attr("id", "gradeType" + gradeRowCount).val(gradeType);
          $tr.find('#gradePricingType').attr('name', 'gradePricingType['+gradeRowCount+']').attr("id", "gradePricingType" + gradeRowCount).val(item.pricing_type || 'Standard');
          $tr.find('#gradePricingCurrency').attr('name', 'gradePricingCurrency['+gradeRowCount+']').attr("id", "gradePricingCurrency" + gradeRowCount).val(item.pricing_currency);
          $tr.find('#gradePrice').attr('name', 'gradePrice['+gradeRowCount+']').attr("id", "gradePrice" + gradeRowCount).val(item.price || 0);
          $tr.find('#gradePurchasingPricingType').attr('name', 'gradePurchasingPricingType['+gradeRowCount+']').attr("id", "gradePurchasingPricingType" + gradeRowCount).val(item.purchasing_pricing_type || 'Standard');
          $tr.find('#gradePurchasingPricingCurrency').attr('name', 'gradePurchasingPricingCurrency['+gradeRowCount+']').attr("id", "gradePurchasingPricingCurrency" + gradeRowCount).val(item.purchasing_pricing_currency);
          $tr.find('#gradePurchasingPrice').attr('name', 'gradePurchasingPrice['+gradeRowCount+']').attr("id", "gradePurchasingPrice" + gradeRowCount).val(item.purchasing_price || 0);

          gradeRowCount++;
        }
        $('#gradeLocalEmptyState').toggle(!hasLocal);
        $('#gradeExportEmptyState').toggle(!hasExport);
      } else {
        $('#gradeLocalEmptyState').show();
        $('#gradeExportEmptyState').show();
      }

      $('#modalTitle').text('<?=$languageArray['edit_product_code'][$language] ?? 'Edit Product'?>');
      $('#productModal').modal('show');
      
      $('#productForm').validate({
        errorElement: 'span',
        errorPlacement: function (error, element) {
          error.addClass('invalid-feedback');
          element.closest('.form-group').append(error);
        },
        highlight: function (element, errorClass, validClass) {
          $(element).addClass('is-invalid');
        },
        unhighlight: function (element, errorClass, validClass) {
          $(element).removeClass('is-invalid');
        }
      });
    }
    else if(obj.status === 'failed'){
      toastr["error"](obj.message, "Failed:");
    }
    else{
      toastr["error"]("Something wrong when activate", "Failed:");
    }
    $('#spinnerLoading').hide();
  });
}

function openCustomers(id) {
  $('#spinnerLoading').show();
  $('#customerCards').html('');
  $('#supplierCards').html('');
  customerRowCount = 0;
  supplierRowCount = 0;
  $('#customerTypeFilter').val('');
  $('#supplierTypeFilter').val('');
  $('#customersForm').find('#customerProductId').val(id);
  // Reset to customers tab
  $('#tabCustomersLink').tab('show');
  $.post('php/modules/products/getProduct.php', {userID: id}, function(data) {
    var obj = JSON.parse(data);
    if (obj.status === 'success') {
      // Load customers
      var items = obj.message.productCustomers;
      if (items.length > 0) {
        $('#customerEmptyState').hide();
      } else {
        $('#customerEmptyState').show();
      }
      for (var i = 0; i < items.length; i++) {
        var item = items[i];
        var customerType = item.type || 'Local';
        
        var $addContents = $("#customerDetail").clone();
        $("#customerCards").append($addContents.html());

        var $card = $("#customerCards").find('.details:last');
        $card.attr("id", "detail" + customerRowCount).attr("data-index", customerRowCount).attr("data-type", customerType);
        $card.find('.cs-card-number').text(customerRowCount + 1);
        $card.find('#remove').attr("id", "remove" + customerRowCount);
        $card.find('#no').attr('name', 'no['+customerRowCount+']').attr("id", "no" + customerRowCount).val(item.no);
        $card.find('#customerProductId').attr('name', 'customerProductId['+customerRowCount+']').attr("id", "customerProductId" + customerRowCount).val(item.id);
        $card.find('#customerRowType').attr('name', 'customerRowType['+customerRowCount+']').attr("id", "customerRowType" + customerRowCount);
        $card.find('#customerType').attr('name', 'customerType['+customerRowCount+']').attr("id", "customerType" + customerRowCount).val(customerType);
        $card.find('#customers').attr('name', 'customers['+customerRowCount+']').attr("id", "customers" + customerRowCount).val(item.customer_id).select2({
          allowClear: true, placeholder: $("#customerDetail").find('#customers').data('placeholder'), dropdownParent: $('#customersModal')
        }).on('change', function() {
          var state = $(this).find('option:selected').data('state') || '';
          $(this).closest('.cs-card').find('.customer-state-display').val(state);
        });
        var customerStateVal = $card.find('#customers' + customerRowCount).find('option:selected').data('state') || '';
        $card.find('.customer-state-display').val(customerStateVal);
        $card.find('#customerGrade').attr('name', 'customerGrade['+customerRowCount+']').attr("id", "customerGrade" + customerRowCount).val(item.grade_id || '').select2({
          allowClear: true, placeholder: "-", dropdownParent: $('#customersModal')
        });
        $card.find('#customerPricingType').attr('name', 'customerPricingType['+customerRowCount+']').attr("id", "customerPricingType" + customerRowCount).val(item.pricing_type || 'Standard');
        $card.find('#customerCurrency').attr('name', 'customerCurrency['+customerRowCount+']').attr("id", "customerCurrency" + customerRowCount).val(item.pricing_currency || '').select2({
          allowClear: true, placeholder: "-", dropdownParent: $('#customersModal')
        });
        $card.find('#customerPrice').attr('name', 'customerPrice['+customerRowCount+']').attr("id", "customerPrice" + customerRowCount).val(item.price || 0);

        customerRowCount++;
      }

      // Load suppliers
      var supplierItems = obj.message.productSuppliers;
      if (supplierItems.length > 0) {
        $('#supplierEmptyState').hide();
      } else {
        $('#supplierEmptyState').show();
      }
      for (var j = 0; j < supplierItems.length; j++) {
        var sItem = supplierItems[j];
        var supplierType = sItem.type || 'Local';
        
        var $sContents = $("#supplierDetail").clone();
        $("#supplierCards").append($sContents.html());

        var $sCard = $("#supplierCards").find('.details:last');
        $sCard.attr("id", "supplierDetail" + supplierRowCount).attr("data-index", supplierRowCount).attr("data-type", supplierType);
        $sCard.find('.cs-card-number').text(supplierRowCount + 1);
        $sCard.find('#removeSupplier').attr("id", "removeSupplier" + supplierRowCount);
        $sCard.find('#supplierNo').attr('name', 'supplierNo['+supplierRowCount+']').attr("id", "supplierNo" + supplierRowCount).val(sItem.no);
        $sCard.find('#supplierProductId').attr('name', 'supplierProductId['+supplierRowCount+']').attr("id", "supplierProductId" + supplierRowCount).val(sItem.id);
        $sCard.find('#supplierRowType').attr('name', 'supplierRowType['+supplierRowCount+']').attr("id", "supplierRowType" + supplierRowCount);
        $sCard.find('#supplierType').attr('name', 'supplierType['+supplierRowCount+']').attr("id", "supplierType" + supplierRowCount).val(supplierType);
        $sCard.find('#suppliers').attr('name', 'suppliers['+supplierRowCount+']').attr("id", "suppliers" + supplierRowCount).val(sItem.supplier_id).select2({
          allowClear: true, placeholder: $("#supplierDetail").find('#suppliers').data('placeholder'), dropdownParent: $('#customersModal')
        }).on('change', function() {
          var state = $(this).find('option:selected').data('state') || '';
          $(this).closest('.cs-card').find('.supplier-state-display').val(state);
        });
        var supplierStateVal = $sCard.find('#suppliers' + supplierRowCount).find('option:selected').data('state') || '';
        $sCard.find('.supplier-state-display').val(supplierStateVal);
        $sCard.find('#supplierGrade').attr('name', 'supplierGrade['+supplierRowCount+']').attr("id", "supplierGrade" + supplierRowCount).val(sItem.grade_id || '').select2({
          allowClear: true, placeholder: "-", dropdownParent: $('#customersModal')
        });
        $sCard.find('#supplierPricingType').attr('name', 'supplierPricingType['+supplierRowCount+']').attr("id", "supplierPricingType" + supplierRowCount).val(sItem.purchasing_pricing_type || 'Standard');
        $sCard.find('#supplierCurrency').attr('name', 'supplierCurrency['+supplierRowCount+']').attr("id", "supplierCurrency" + supplierRowCount).val(sItem.purchasing_pricing_currency || '').select2({
          allowClear: true, placeholder: "-", dropdownParent: $('#customersModal')
        });
        $sCard.find('#supplierPrice').attr('name', 'supplierPrice['+supplierRowCount+']').attr("id", "supplierPrice" + supplierRowCount).val(sItem.purchasing_price || 0);

        supplierRowCount++;
      }
    } else {
      toastr["error"](obj.message, "Failed:");
    }
    $('#spinnerLoading').hide();
    $('#customersModal').modal('show');
  });
}

function setRangeSet(val) {
  var enabled = val == 1;
  $('#rangeSet').val(enabled ? 1 : 0);
  $('#rangeSetCheckbox').prop('checked', enabled);
  $('#rangeWeightFields').toggle(enabled);
}

function deactivate(id){
  if (confirm('Are you sure you want to delete this items?')) {
    //$('#spinnerLoading').show();
    $.post('php/modules/products/deleteProduct.php', {userID: id}, function(data){
        var obj = JSON.parse(data);
        
        if(obj.status === 'success'){
          toastr["success"](obj.message, "Success:");
          $('#productTable').DataTable().ajax.reload();
          //$('#spinnerLoading').hide();
        }
        else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
            //$('#spinnerLoading').hide();
        }
        else{
            toastr["error"]("Something wrong when activate", "Failed:");
            //$('#spinnerLoading').hide();
        }
    });
  }
}
</script>
