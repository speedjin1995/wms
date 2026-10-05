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
  $module = $_SESSION['module'];
  $states = $db->query("SELECT * FROM states ORDER BY states ASC");
  $states2 = $db->query("SELECT * FROM states ORDER BY states ASC");
  $companies = $db->query("SELECT * FROM companies WHERE deleted = 0 ORDER BY name ASC");

  if ($role != 'SADMIN'){
    $currencies = $db->query("SELECT * FROM currency WHERE deleted = 0 AND customer = '$company' ORDER BY currency ASC");
    $suppliers = $db->query("SELECT * FROM supplies WHERE deleted = 0 AND customer = '$company' ORDER BY supplier_name ASC");
  }else{
    $currencies = $db->query("SELECT * FROM currency WHERE deleted = 0 AND customer = '$company' ORDER BY currency ASC");
    $suppliers = $db->query("SELECT * FROM supplies WHERE deleted = 0 ORDER BY supplier_name ASC");
  }

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
  
  $includeInvoice = 'N';
  $runningNoType = 0;
  $allowEntityRegValidation = 'N';
  if ($company_stmt = $db->prepare("SELECT * FROM companies WHERE id = ?")) {
    $company_stmt->bind_param("i", $company);
    $company_stmt->execute();
    $company_result = $company_stmt->get_result();
    $rowCompany = mysqli_fetch_assoc($company_result);
    $includeInvoice = $rowCompany['include_invoice'];
    $runningNoType = $rowCompany['running_no_type'];
  }

  $allowEntityRegValidation = $_SESSION['featureFlags']['allow_entity_registration_validation'] ?? 'N';
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
              <h3 class="results-title"><i class="fas fa-truck mr-2"></i><?=$languageArray['suppliers_code'][$language]?></h3>
            </div>
            <div class="results-header-right d-flex flex-wrap" style="gap: 0.5rem;">
              <a href="template/Supplier_Template.xlsx" download class="btn btn-action btn-action-warning">
                <i class="fas fa-download"></i> <?=$languageArray['download_template_code'][$language]?>
              </a>
              <button type="button" id="uploadExcel" class="btn btn-action btn-action-success">
                <i class="fas fa-upload"></i> <?=$languageArray['upload_excel_code'][$language]?>
              </button>
              <button type="button" id="multiDeactivate" class="btn btn-action btn-action-danger">
                <i class="fas fa-trash-alt"></i> <?=$languageArray['delete_supplier_code'][$language]?>
              </button>
              <button type="button" class="btn btn-action btn-action-primary" id="addSuppliers">
                <i class="fas fa-plus"></i> <?=$languageArray['add_suppliers_code'][$language]?>
              </button>
            </div>
          </div>
					<div class="card-body">
						<table id="supplierTable" class="table data-table">
							<thead>
								<tr>
                  <th><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
                  <th><?=$languageArray['supplier_code_code'][$language]?></th>
                  <th><?=$languageArray['reg_no_code'][$language]?></th>
                  <th><?=$languageArray['parent_code'][$language]?></th>
									<th><?=$languageArray['supplier_name_code'][$language]?></th>
									<th><?=$languageArray['address_code'][$language]?></th>
									<th><?=$languageArray['phone_code'][$language]?></th>
									<th><?=$languageArray['pic_code'][$language]?></th>
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
  <div class="modal-dialog" style="max-width: 90vw">
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
            <button type="button" class="btn btn-modern btn-modern-primary" id="uploadSupplier"><?=$languageArray['submit_code'][$language]?></button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade modal-modern" id="errorModal" style="display:none">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form role="form" id="errorForm">
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
  </div>
</div>

<div class="modal fade modal-modern" id="addModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form role="form" id="supplierForm">
          <div class="modal-header">
            <h5 class="modal-title"><?=$languageArray['add_suppliers_code'][$language]?></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="id" name="id">

            <!-- Company (SADMIN only) -->
            <div class="row" <?php if($role != 'SADMIN'){ echo 'style="display:none;"'; } ?>>
              <div class="col-md-12">
                <div class="form-group">
                  <label class="form-label-modern"><?=$languageArray['company_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control select2" style="width:100%;" id="company" name="company" required>
                    <?php while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                      <option value="<?=$rowCompany['id'] ?>" <?php if($rowCompany['id'] == $company) echo 'selected'; ?>><?=$rowCompany['name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
            </div>

            <!-- Basic Information -->
            <div class="modal-section">
              <div class="section-title"><i class="fas fa-user mr-2"></i><?=$languageArray['basic_information_code'][$language] ?? 'Basic Information'?></div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['supplier_name_code'][$language]?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" id="name" placeholder="Supplier name" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['supplier_code_code'][$language]?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="code" id="code" placeholder="Supplier code" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['supplier_type_code'][$language] ?? 'Supplier Type'?></label>
                    <select class="form-control select2" style="width:100%;" id="supplierType" name="supplierType">
                      <option value="Normal" selected><?=$languageArray['normal_code'][$language] ?? 'Normal'?></option>
                      <option value="Packing"><?=$languageArray['packing_code'][$language] ?? 'Packing'?></option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-0">
                    <label class="form-label-modern"><?=$languageArray['parent_code'][$language]?></label>
                    <select class="form-control select2" style="width:100%;" id="parent" name="parent">
                      <option value="">Select Parent</option>
                      <?php while($rowSupplier=mysqli_fetch_assoc($suppliers)){ ?>
                        <option value="<?=$rowSupplier['id'] ?>"><?=$rowSupplier['supplier_name'] ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- Company Registration Section -->
            <div class="modal-section">
              <div class="section-title"><i class="fas fa-building mr-2"></i><?=$languageArray['company_registration_code'][$language] ?? 'Company Registration'?></div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group mb-3">
                    <label class="form-label-modern"><?=$languageArray['reg_no_code'][$language]?></label>
                    <input type="text" class="form-control" name="regNo" id="regNo" placeholder="<?=$languageArray['reg_no_code'][$language]?>">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group mb-3">
                    <label class="form-label-modern"><?=$languageArray['ctos_report_no'][$language] ?? 'CTOS Report No.'?></label>
                    <input type="text" class="form-control" name="ctosReportNo" id="ctosReportNo" placeholder="<?=$languageArray['ctos_report_no'][$language] ?? 'CTOS Report No.'?>">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group mb-3">
                    <label class="form-label-modern"><?=$languageArray['ic_no_code'][$language] ?? 'IC No.'?></label>
                    <input type="text" class="form-control" name="icNo" id="icNo" placeholder="000000-00-0000" data-inputmask="'mask': '999999-99-9999'">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group mb-0">
                    <label class="form-label-modern"><?=$languageArray['ssm_no_code'][$language] ?? 'SSM No.'?></label>
                    <input type="text" class="form-control" name="ssmNo" id="ssmNo" placeholder="<?=$languageArray['ssm_no_code'][$language] ?? 'SSM No.'?>">
                  </div>
                </div>
                <div class="col-md-8">
                  <div class="form-group mb-0">
                    <label class="form-label-modern"><?=$languageArray['ssm_cert_code'][$language] ?? 'SSM Certificate'?></label>
                    <div class="d-flex align-items-center" style="gap: 0.5rem;">
                      <div class="custom-file" style="max-width: 280px;">
                        <input type="file" class="custom-file-input" id="ssmFile" name="ssmFile" accept=".pdf,.png,.jpg,.jpeg">
                        <label class="custom-file-label" for="ssmFile" id="ssmFileLabel"><?=$languageArray['choose_file_code'][$language] ?? 'Choose file'?></label>
                      </div>
                      <div id="ssmFilePreview" class="d-none align-items-center" style="gap: 0.375rem;">
                        <a href="#" id="ssmFileLink" target="_blank" class="btn btn-outline-info btn-sm" title="<?=$languageArray['view_file_code'][$language] ?? 'View File'?>"><i class="fas fa-eye mr-1"></i><?=$languageArray['view_file_code'][$language] ?? 'View'?></a>
                        <button type="button" class="btn btn-outline-danger btn-sm" id="removeSsmFile" title="<?=$languageArray['remove_file_code'][$language] ?? 'Remove File'?>"><i class="fas fa-times"></i></button>
                        <input type="hidden" name="ssmFilePath" id="ssmFilePath" value="">
                      </div>
                    </div>
                    <small class="form-text text-muted mt-1"><?=$languageArray['allowed_formats_code'][$language] ?? 'Allowed formats'?>: PDF, PNG, JPG (Max 10MB)</small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Delivery Address -->
            <div class="modal-section">
              <div class="section-title"><i class="fas fa-map-marker-alt mr-2"></i><?=$languageArray['delivery_address_code'][$language]?></div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['address_code'][$language]?></label>
                    <input type="text" class="form-control" name="address" id="address" placeholder="Street address">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['address_code'][$language]?> 2</label>
                    <input type="text" class="form-control" name="address2" id="address2" placeholder="City">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['address_code'][$language]?> 3</label>
                    <input type="text" class="form-control" name="address3" id="address3" placeholder="Postcode">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['address_code'][$language]?> 4</label>
                    <input type="text" class="form-control" name="address4" id="address4" placeholder="Country">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['states_code'][$language]?></label>
                    <select class="form-control select2" style="width:100%;" id="states" name="states">
                      <option value="">Select State</option>
                      <?php while($rowCustomer2=mysqli_fetch_assoc($states)){ ?>
                        <option value="<?=$rowCustomer2['id'] ?>"><?=$rowCustomer2['states'] ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern">Fax</label>
                    <input type="text" class="form-control" name="fax" id="fax" placeholder="Fax number">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['phone_code'][$language]?></label>
                    <input type="text" class="form-control" name="phone" id="phone" placeholder="Phone number">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['pic_code'][$language]?></label>
                    <input type="text" class="form-control" id="email" name="email" placeholder="Person In Charge">
                  </div>
                </div>
              </div>
            </div>
            <!-- Billing Address -->
            <div class="modal-section" <?= ($includeInvoice == 'Y' ? '' : 'style="display:none;"') ?>>
              <div class="section-title d-flex align-items-center justify-content-between">
                <span><i class="fas fa-file-invoice mr-2"></i><?=$languageArray['billing_address_code'][$language]?></span>
                <div class="form-check mb-0">
                  <input type="checkbox" class="form-check-input" id="sameAsDelivery">
                  <label class="form-check-label font-weight-normal" for="sameAsDelivery"><?=$languageArray['same_as_delivery_address_code'][$language]?></label>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['billing_name_code'][$language]?></label>
                    <input type="text" class="form-control" name="billingName" id="billingName" placeholder="Billing name">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['currency_code'][$language]?></label>
                    <select class="form-control select2" style="width:100%;" id="currency" name="currency">
                      <option value="">Select Currency</option>
                      <?php while($rowCurrency=mysqli_fetch_assoc($currencies)){ ?>
                        <option value="<?=$rowCurrency['id'] ?>"><?=$rowCurrency['currency'] ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['billing_pic_code'][$language]?></label>
                    <input type="text" class="form-control" id="billingPic" name="billingPic" placeholder="Person In Charge">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['billing_address_code'][$language]?></label>
                    <input type="text" class="form-control" name="billingAddress" id="billingAddress" placeholder="Street address">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['billing_address_code'][$language]?> 2</label>
                    <input type="text" class="form-control" name="billingAddress2" id="billingAddress2" placeholder="City">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['billing_address_code'][$language]?> 3</label>
                    <input type="text" class="form-control" name="billingAddress3" id="billingAddress3" placeholder="Postcode">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['billing_address_code'][$language]?> 4</label>
                    <input type="text" class="form-control" name="billingAddress4" id="billingAddress4" placeholder="Country">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['billing_state_code'][$language]?></label>
                    <select class="form-control select2" style="width:100%;" id="billingStates" name="billingStates">
                      <option value="">Select State</option>
                      <?php while($rowCustomer2=mysqli_fetch_assoc($states2)){ ?>
                        <option value="<?=$rowCustomer2['id'] ?>"><?=$rowCustomer2['states'] ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['billing_fax_code'][$language]?></label>
                    <input type="text" class="form-control" name="billingFax" id="billingFax" placeholder="Fax number">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="form-label-modern"><?=$languageArray['billing_phone_code'][$language]?></label>
                    <input type="text" class="form-control" name="billingPhone" id="billingPhone" placeholder="Phone number">
                  </div>
                </div>
              </div>
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

<div class="modal fade modal-modern" id="runningNoModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-hashtag mr-2"></i><?=$languageArray['running_no_code'][$language]?? 'Running No' ?> — <span id="runningNoSupplierName"></span></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="runningNoEntityId">
        <div class="modal-section">
          <div class="section-title"><i class="fas fa-tag mr-2"></i><?=$languageArray['invoice_code'][$language] ?? 'Invoice Code' ?></div>
          <div class="form-group mb-0">
            <input type="text" class="form-control" id="runningNoInvoiceCode" maxlength="50" placeholder="e.g. SUP-001">
          </div>
        </div>
        <div class="modal-section">
          <div class="section-title"><i class="fas fa-list-ol mr-2"></i><?=$languageArray['running_no_code'][$language] ?? 'Running Numbers' ?></div>
          <table class="table table-bordered table-sm mb-0">
          <thead>
            <tr>
              <th><?=$languageArray['status_code'][$language] ?? 'Status' ?></th>
              <th><?=$languageArray['prefix_code'][$language] ?? 'Prefix' ?></th>
              <th><?=$languageArray['next_value_code'][$language] ?? 'Next Value' ?></th>
            </tr>
          </thead>
          <tbody id="runningNoBody"></tbody>
          </table>
          <div class="alert alert-light border mt-2 mb-0 py-2 px-3" style="font-size: 0.8125rem;">
            <i class="fas fa-info-circle text-info mr-1"></i>
            <strong><?=$languageArray['format_code'][$language] ?? 'Format' ?>:</strong> 
            <code>[Prefix]-[Invoice Code]-[YYMM]/[Value]</code>
            <br><small class="text-muted">e.g. IV-APL-2608/25001</small>
          </div>
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
        <button type="button" class="btn btn-modern btn-modern-primary" id="saveRunningNo"><?=$languageArray['submit_code'][$language]?></button>
      </div>
    </div>
  </div>
</div>

<!-- jQuery -->
<script>
var suppliersTableLanguage = {
  'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
  'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
};
var runningNoType = <?= (int)($runningNoType ?? 0) ?>;
var requireRegistration = <?= $allowEntityRegValidation == 'Y' ? 'true' : 'false' ?>;// Feature flag: require at least one registration field
var suppliersText = {
  chooseFile: '<?=$languageArray['choose_file_code'][$language] ?? 'Choose file'?>',
  atLeastOneRegistration: '<?=$languageArray['at_least_one_registration_code'][$language] ?? 'At least one of CTOS Report No., IC No., or SSM No. must be filled before proceeding.'?>'
};
</script>
<script src="modules/suppliers/js/suppliers.js?v=<?=time()?>"></script>