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
  $products = $_SESSION['products'];
  $includeInvoice = 'N';
  $module = $_SESSION['module'];
  $states = $db->query("SELECT * FROM states ORDER BY states ASC");
  $states2 = $db->query("SELECT * FROM states ORDER BY states ASC");
  $companies = $db->query("SELECT * FROM companies WHERE deleted = 0 ORDER BY name ASC");

  if ($role != 'SADMIN'){
    $currencies = $db->query("SELECT * FROM currency WHERE deleted = 0 AND customer = '$company' ORDER BY currency ASC");
    $customers = $db->query("SELECT * FROM customers WHERE deleted = 0 AND customer = '$company' ORDER BY customer_name ASC");
  }else{
    $currencies = $db->query("SELECT * FROM currency WHERE deleted = 0 ORDER BY currency ASC");
    $customers = $db->query("SELECT * FROM customers WHERE deleted = 0 ORDER BY customer_name ASC");
  }

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];

  // Bin types for the bin modal dropdown
  $binTypesResult = $db->query("SELECT id, bin_type FROM bin_type WHERE deleted = 0 AND customer = '$company' ORDER BY bin_type ASC");
  $binTypesArr = [];
  while ($btRow = $binTypesResult->fetch_assoc()) { $binTypesArr[] = $btRow; }

  $includeInvoice = 'N';
  $allowEntityRegValidation = 'N';
  $runningNoType = 0;
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
<style>
.bin-type-btn { background:#fff; text-align:center; font-weight:600; }
input[type="radio"]:checked + .bin-type-btn { border-color:#fda085 !important; background:#fff8f5; color:#fda085; }
#binDetails { transition: none; }
</style>

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
              <h3 class="results-title"><i class="fas fa-users mr-2"></i><?=$languageArray['customers_code'][$language]?></h3>
            </div>
            <div class="results-header-right d-flex flex-wrap" style="gap: 0.5rem;">
              <?php if (in_array('basket', $_SESSION['products'])) { ?>
              <a href="php/modules/customers/exportBinReport.php" target="_blank" class="btn btn-action btn-action-secondary">
                <i class="fas fa-file-export"></i> <?=$languageArray['export_bin_report_code'][$language]?>
              </a>
              <?php } ?>
              <a href="template/Customer_Template.xlsx" download class="btn btn-action btn-action-warning">
                <i class="fas fa-download"></i> <?=$languageArray['download_template_code'][$language]?>
              </a>
              <button type="button" id="uploadExcel" class="btn btn-action btn-action-success">
                <i class="fas fa-upload"></i> <?=$languageArray['upload_excel_code'][$language]?>
              </button>
              <button type="button" id="multiDeactivate" class="btn btn-action btn-action-danger">
                <i class="fas fa-trash-alt"></i> <?=$languageArray['delete_customer_code'][$language]?>
              </button>
              <button type="button" class="btn btn-action btn-action-primary" id="addCustomers">
                <i class="fas fa-plus"></i> <?=$languageArray['add_customers_code'][$language]?>
              </button>
            </div>
          </div>
					<div class="card-body">
						<table id="customerTable" class="table data-table">
							<thead>
								<tr>
                  <th><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
                  <th><?=$languageArray['customer_code_code'][$language]?></th>
                  <th><?=$languageArray['reg_no_code'][$language]?></th>
                  <th><?=$languageArray['parent_code'][$language]?></th>
									<th><?=$languageArray['customer_name_code'][$language]?></th>
									<th><?=$languageArray['address_code'][$language]?></th>
									<th><?=$languageArray['phone_code'][$language]?></th>
									<th><?=$languageArray['pic_code'][$language]?></th>
                  <th><?=$languageArray['pending_bins_code'][$language]?></th>
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
            <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
            <button type="button" class="btn btn-success" id="uploadCustomer"><?=$languageArray['submit_code'][$language]?></button>
          </div>
      </form>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>

<div class="modal fade modal-modern" id="errorModal">
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
        <form role="form" id="customerForm">
            <div class="modal-header">
              <h4 class="modal-title"><?=$languageArray['add_customers_code'][$language]?></h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <input type="hidden" id="id" name="id">

              <!-- Company (SADMIN only) -->
              <div class="modal-section" <?php if($role != 'SADMIN'){ echo 'style="display:none;"'; } ?>>
                <div class="form-group mb-0">
                  <label class="form-label-modern"><?=$languageArray['company_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control select2" style="width:100%;" id="company" name="company" required>
                    <?php while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                      <option value="<?=$rowCompany['id'] ?>" <?php if($rowCompany['id'] == $company) echo 'selected'; ?>><?=$rowCompany['name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <!-- Basic Information Section -->
              <div class="modal-section">
                <div class="section-title"><i class="fas fa-user mr-2"></i><?=$languageArray['basic_info_code'][$language] ?? 'Basic Information'?></div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label-modern"><?=$languageArray['customer_name_code'][$language]?> <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="name" id="name" placeholder="<?=$languageArray['enter_customer_name_code'][$language]?>" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label-modern"><?=$languageArray['customer_code_code'][$language]?> <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="code" id="code" placeholder="Code" maxlength="10" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label-modern"><?=$languageArray['customer_type_code'][$language] ?? 'Customer Type'?></label>
                      <select class="form-control select2" style="width:100%;" id="customerType" name="customerType">
                        <option value="Normal" selected><?=$languageArray['normal_code'][$language] ?? 'Normal'?></option>
                        <option value="Packing"><?=$languageArray['packing_code'][$language] ?? 'Packing'?></option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-0">
                      <label class="form-label-modern"><?=$languageArray['parent_code'][$language]?></label>
                      <select class="form-control select2" style="width:100%;" id="parent" name="parent">
                        <?php while($rowCustomer=mysqli_fetch_assoc($customers)){ ?>
                          <option value="<?=$rowCustomer['id'] ?>"><?=$rowCustomer['customer_name'] ?></option>
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

              <!-- Delivery Address Section -->
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
                      <input type="text" class="form-control" name="address2" id="address2" placeholder="Apartment, suite, etc.">
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label class="form-label-modern"><?=$languageArray['address_code'][$language]?> 3</label>
                      <input type="text" class="form-control" name="address3" id="address3" placeholder="City">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label class="form-label-modern"><?=$languageArray['address_code'][$language]?> 4</label>
                      <input type="text" class="form-control" name="address4" id="address4" placeholder="Postcode">
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
                      <label class="form-label-modern"><?=$languageArray['fax_code'][$language]?></label>
                      <input type="text" class="form-control" name="fax" id="fax" placeholder="Fax number">
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group mb-0">
                      <label class="form-label-modern"><?=$languageArray['phone_code'][$language]?></label>
                      <input type="text" class="form-control" name="phone" id="phone" placeholder="01x-xxxxxxx">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group mb-0">
                      <label class="form-label-modern"><?=$languageArray['pic_code'][$language]?></label>
                      <input type="text" class="form-control" id="email" name="email" placeholder="Person In Charge">
                    </div>
                  </div>
                </div>
              </div>

              <!-- Billing Address Section -->
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
                      <label class="form-label-modern"><?=$languageArray['billing_phone_code'][$language]?></label>
                      <input type="text" class="form-control" name="billingPhone" id="billingPhone" placeholder="01x-xxxxxxx">
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
                      <input type="text" class="form-control" name="billingAddress2" id="billingAddress2" placeholder="Apartment, suite, etc.">
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group mb-0">
                      <label class="form-label-modern"><?=$languageArray['billing_address_code'][$language]?> 3</label>
                      <input type="text" class="form-control" name="billingAddress3" id="billingAddress3" placeholder="City">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group mb-0">
                      <label class="form-label-modern"><?=$languageArray['billing_address_code'][$language]?> 4</label>
                      <input type="text" class="form-control" name="billingAddress4" id="billingAddress4" placeholder="Postcode">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group mb-0">
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
                    <div class="form-group mb-0">
                      <label class="form-label-modern"><?=$languageArray['billing_pic_code'][$language]?></label>
                      <input type="text" class="form-control" id="billingPic" name="billingPic" placeholder="Person In Charge">
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

<!-- Bin Modal -->
<div class="modal fade modal-modern" id="binModal">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius:12px; overflow:hidden; border:none;">
      <form id="binForm">
        <div class="modal-header" style="background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); border:none;">
          <div>
            <h5 class="modal-title font-weight-bold mb-0" style="color:#1a1a2e;"><i class="fas fa-shopping-basket mr-2"></i><?=$languageArray['manage_bins_code'][$language]?></h5>
            <small style="color:#1a1a2e;"><span id="binCustomerName"></span></small>
          </div>
          <button type="button" class="close" style="color:#1a1a2e;" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body" style="background:#f8f9fa; color:#333;">
          <input type="hidden" id="binCustomerId" name="binCustomerId">
          <input type="hidden" id="binTypeId" name="binTypeId">

          <!-- Bin Type dropdown -->
          <div class="form-group">
            <label style="font-weight:600; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; color:#555;">Bin Type <span class="text-danger">*</span></label>
            <select class="form-control" id="binTypeSelect" style="border-radius:8px; height:48px;">
              <option value="">Select Bin Type</option>
              <?php foreach ($binTypesArr as $bt): ?>
                <option value="<?= $bt['id'] ?>"><?= htmlspecialchars($bt['bin_type']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Everything below hidden until bin type selected -->
          <div id="binDetails" style="display:none;">

            <!-- Loading skeleton -->
            <div id="binLoadingSkeleton" class="text-center mb-4" style="display:none;">
              <div style="display:inline-block; background:#fff; border-radius:12px; padding:16px 40px; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
                <div style="height:12px; width:120px; background:#e9ecef; border-radius:4px; margin:0 auto 8px;"></div>
                <div style="height:40px; width:60px; background:#e9ecef; border-radius:4px; margin:0 auto 8px;"></div>
                <div style="height:10px; width:40px; background:#e9ecef; border-radius:4px; margin:0 auto;"></div>
              </div>
            </div>

            <!-- Pending count banner -->
            <div class="text-center mb-4" id="binPendingCard">
              <div style="display:inline-block; background:#fff; border-radius:12px; padding:16px 40px; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
                <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; color:#666;"><?=$languageArray['current_pending_bins_code'][$language]?></div>
                <div id="binCurrent" style="font-size:2.5rem; font-weight:700; color:#fda085; line-height:1.1;">0</div>
                <div style="font-size:0.85rem; color:#666;"><?=$languageArray['bins_code'][$language]?></div>
              </div>
            </div>

            <!-- IN / OUT toggle -->
            <div class="form-group">
              <label style="font-weight:600; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; color:#555;"><?=$languageArray['actions_code'][$language]?></label>
              <div class="d-flex" style="gap:10px;">
                <div class="flex-fill">
                  <input type="radio" name="binAction" id="binActionOut" value="OUT" class="d-none" checked>
                  <label for="binActionOut" class="btn btn-block bin-type-btn" style="border:2px solid #dee2e6; border-radius:10px; padding:12px; cursor:pointer; transition:all 0.2s;">
                    <i class="fas fa-arrow-up text-warning mr-1"></i> <?=$languageArray['bin_out_code'][$language]?>
                    <div style="font-size:0.8rem; color:#888; font-weight:400;"><?=$languageArray['customer_takes_bins_code'][$language]?></div>
                  </label>
                </div>
                <div class="flex-fill">
                  <input type="radio" name="binAction" id="binActionIn" value="IN" class="d-none">
                  <label for="binActionIn" class="btn btn-block bin-type-btn" style="border:2px solid #dee2e6; border-radius:10px; padding:12px; cursor:pointer; transition:all 0.2s;">
                    <i class="fas fa-arrow-down text-success mr-1"></i> <?=$languageArray['bin_in_code'][$language]?>
                    <div style="font-size:0.8rem; color:#888; font-weight:400;"><?=$languageArray['customer_returns_bins_code'][$language]?></div>
                  </label>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label style="font-weight:600; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; color:#555;"><?=$languageArray['quantity_code'][$language]?> <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="binQty" name="binQty" min="1" placeholder="e.g. 5" style="border-radius:8px; font-size:0.9rem; height:48px;">
            </div>

            <div class="form-group mb-0">
              <label style="font-weight:600; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; color:#555;"><?=$languageArray['remark_code'][$language]?></label>
              <input type="text" class="form-control" id="binRemark" name="binRemark" placeholder="Optional note..." style="border-radius:8px;">
            </div>

          </div><!-- /#binDetails -->
        </div>
        <div class="modal-footer" style="background:#f8f9fa; border-top:1px solid #eee;">
          <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius:8px; min-width:90px;"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-warning" name="submit" id="submitBin" style="border-radius:8px; min-width:90px; font-weight:600;"><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Bin History Modal -->
<div class="modal fade modal-modern" id="binHistoryModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius:12px; overflow:hidden; border:none;">
      <div class="modal-header" style="background: linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%); border:none;">
        <div>
          <h5 class="modal-title font-weight-bold mb-0" style="color:#1a1a2e;"><i class="fas fa-history mr-2"></i><?=$languageArray['bin_history_code'][$language]?></h5>
          <small style="color:#1a1a2e;"><span id="binHistoryCustomerName"></span></small>
        </div>
        <button type="button" class="close" style="color:#1a1a2e;" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body" style="background:#f0f2f5; color:#333; padding:16px;">
        <div class="form-group mb-3">
          <label style="font-weight:600; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; color:#555;">Bin Type <span class="text-danger">*</span></label>
          <select class="form-control" id="binHistoryTypeSelect" style="border-radius:8px; height:48px;">
            <option value="">Select Bin Type</option>
            <?php foreach ($binTypesArr as $bt): ?>
              <option value="<?= $bt['id'] ?>"><?= htmlspecialchars($bt['bin_type']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div id="binHistoryContent" style="display:none; max-height:55vh; overflow-y:auto;">
          <div id="binHistoryList"></div>
          <div id="binHistoryPager" class="d-flex justify-content-between align-items-center mt-2"></div>
        </div>
        <div id="binHistoryPrompt" class="text-center text-muted py-5">
          <i class="fas fa-hand-point-up fa-2x mb-2"></i>
          <div>Select a bin type to view history</div>
        </div>
      </div>
      <div class="modal-footer" style="background:#f8f9fa; border-top:1px solid #eee;">
        <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius:8px; min-width:90px; font-size:0.9rem;"><?=$languageArray['close_code'][$language]?></button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade modal-modern" id="runningNoModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-hashtag mr-2"></i><?=$languageArray['running_no_code'][$language]?? 'Running No' ?> — <span id="runningNoCustomerName"></span></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="runningNoEntityId">
        <div class="modal-section">
          <div class="section-title"><i class="fas fa-tag mr-2"></i><?=$languageArray['invoice_code'][$language] ?? 'Invoice Code' ?></div>
          <div class="form-group mb-0">
            <input type="text" class="form-control" id="runningNoInvoiceCode" maxlength="50" placeholder="e.g. CUST-001">
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
var customersTableLanguage = {
  'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
  'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
};
var hasBasket = <?= in_array('basket', $_SESSION['products']) ? 'true' : 'false' ?>;
var runningNoType = <?= (int)($runningNoType ?? 0) ?>;
var binTypeNames = <?= json_encode(array_column($binTypesArr, 'bin_type', 'id')) ?>;
var requireRegistration = <?= $allowEntityRegValidation == 'Y' ? 'true' : 'false' ?>;// Feature flag: require at least one registration field
var customersText = {
  chooseFile: '<?=$languageArray['choose_file_code'][$language] ?? 'Choose file'?>',
  atLeastOneRegistration: '<?=$languageArray['at_least_one_registration_code'][$language] ?? 'At least one of CTOS Report No., IC No., or SSM No. must be filled before proceeding.'?>'
};
</script>
<script src="modules/customers/js/customers.js?v=<?=time()?>"></script>
