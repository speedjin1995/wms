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
  $products = $_SESSION['products'];
  $includeInvoice = 'N';
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
  if ($company_stmt = $db->prepare("SELECT * FROM companies WHERE id = ?")) {
    $company_stmt->bind_param("i", $company);
    $company_stmt->execute();
    $company_result = $company_stmt->get_result();
    $rowCompany = mysqli_fetch_assoc($company_result);
    $includeInvoice = $rowCompany['include_invoice'];
  }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div>
			<div class="col-sm-6">
				<h1><?=$languageArray['customers_code'][$language]?></h1>
			</div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
	<div class="container-fluid">
        <div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-header categories-card-header">
              <div class="row">
                  <?php if (in_array('basket', $_SESSION['products'])) { ?>
                  <div class="col-2"></div>
                  <div class="col-2">
                    <a href="php/modules/customers/exportBinReport.php" target="_blank">
                      <button type="button" class="btn btn-block bg-gradient-primary btn-sm">
                        <?=$languageArray['export_bin_report_code'][$language]?>
                      </button>
                    </a>
                  </div>
                  <?php } else { ?>
                  <div class="col-4"></div>
                  <?php }?>
                  <div class="col-2">
                    <button type="button" id="multiDeactivate" class="btn btn-block bg-gradient-danger btn-sm">
                      <?=$languageArray['delete_customer_code'][$language]?>
                    </button>
                  </div>
                  <div class="col-3">
                    <a href="template/Customer_Template.xlsx" download>
                      <button type="button" class="btn btn-block btn-sm custom-download-btn">
                        <?=$languageArray['download_template_code'][$language]?>
                      </button>
                    </a>
                  </div>
                  <div class="col-3">
                    <button type="button" id="uploadExcel" class="btn btn-block btn-sm custom-add-btn">
                      <?=$languageArray['upload_excel_code'][$language]?>
                    </button>
                  </div>
                  <div class="col-2">
                      <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="addCustomers"><?=$languageArray['add_customers_code'][$language]?></button>
                  </div>
              </div>
          </div>
					<div class="card-body">
						<table id="customerTable" class="table table-bordered table-striped">
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

<div class="modal fade" id="uploadModal">
  <div class="modal-dialog" style="max-width: 90vw">
    <div class="modal-content">
      <form role="form" id="uploadForm" class="custom-model-extend-form">
          <div class="modal-header">
            <h4 class="modal-title"><?=$languageArray['upload_excel_code'][$language]?></h4>
            <button type="button" class="close custom-close-btn-icon" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="card-body">
              <input type="file" id="fileInput" class="custom-upload-input">
              <button type="button" id="previewButton" class="custom-preview-data-btn"><?=$languageArray['preview_data_code'][$language]?></button>
              <div id="previewTable" style="overflow: auto;"></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary custom-close-btn" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
            <button type="button" class="btn btn-success custom-save-btn" id="uploadCustomer"><?=$languageArray['submit_code'][$language]?></button>
          </div>
      </form>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>

<div class="modal fade" id="errorModal" style="display:none">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form role="form" id="uploadForm" class="custom-model-extend-form">
          <div class="modal-header">
            <h4 class="modal-title"><?=$languageArray['error_log_code'][$language]?></h4>
            <button type="button" class="close custom-close-btn-icon" data-dismiss="modal" aria-label="Close">
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

<div class="modal fade" id="addModal">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <form role="form" id="customerForm" class="custom-model-extend-form">
            <div class="modal-header bg-gray-dark color-palette">
              <h4 class="modal-title"><?=$languageArray['add_customers_code'][$language]?></h4>
              <button type="button" class="close custom-close-btn-icon color-palette" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <input type="hidden" id="id" name="id">

              <!-- Company (SADMIN only) -->
              <div class="row" <?php if($role != 'SADMIN'){ echo 'style="display:none;"'; } ?>>
                <div class="col-md-12">
                  <div class="form-group">
                    <label><?=$languageArray['company_code'][$language]?> <span class="text-danger">*</span></label>
                    <select class="form-control select2" style="width:100%;" id="company" name="company" required>
                      <?php while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                        <option value="<?=$rowCompany['id'] ?>" <?php if($rowCompany['id'] == $company) echo 'selected'; ?>><?=$rowCompany['name'] ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Row 1: Code, Reg No, Parent -->
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label><?=$languageArray['customer_code_code'][$language]?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="code" id="code" placeholder="<?=$languageArray['enter_customer_code_code'][$language]?>" maxlength="10" required>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label><?=$languageArray['reg_no_code'][$language]?></label>
                    <input type="text" class="form-control" name="reg_no" id="reg_no" placeholder="<?=$languageArray['enter_reg_no_code'][$language]?>">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label><?=$languageArray['parent_code'][$language]?></label>
                    <select class="form-control select2" style="width:100%;" id="parent" name="parent">
                      <?php while($rowCustomer=mysqli_fetch_assoc($customers)){ ?>
                        <option value="<?=$rowCustomer['id'] ?>"><?=$rowCustomer['customer_name'] ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Row 2: Name -->
              <div class="row">
                <div class="col-md-12">
                  <div class="form-group">
                    <label><?=$languageArray['customer_name_code'][$language]?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" id="name" placeholder="<?=$languageArray['enter_customer_name_code'][$language]?>" required>
                  </div>
                </div>
              </div>

              <hr>

              <!-- Row 3: Address 1 & 2 -->
              <p class="font-weight-bold mb-2"><?=$languageArray['delivery_address_code'][$language]?></p>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label><?=$languageArray['address_code'][$language]?></label>
                    <input type="text" class="form-control" name="address" id="address" placeholder="<?=$languageArray['enter_address_code'][$language]?>">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label><?=$languageArray['address_code'][$language]?> 2</label>
                    <input type="text" class="form-control" name="address2" id="address2" placeholder="<?=$languageArray['enter_address_code'][$language]?> 2">
                  </div>
                </div>
              </div>

              <!-- Row 5: Address 3, 4, State -->
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?=$languageArray['address_code'][$language]?> 3</label>
                    <input type="text" class="form-control" name="address3" id="address3" placeholder="<?=$languageArray['enter_address_code'][$language]?> 3">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?=$languageArray['address_code'][$language]?> 4</label>
                    <input type="text" class="form-control" name="address4" id="address4" placeholder="<?=$languageArray['enter_address_code'][$language]?> 4">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?=$languageArray['states_code'][$language]?></label>
                    <select class="form-control select2" style="width:100%;" id="states" name="states">
                      <option selected="selected">-</option>
                      <?php while($rowCustomer2=mysqli_fetch_assoc($states)){ ?>
                        <option value="<?=$rowCustomer2['id'] ?>"><?=$rowCustomer2['states'] ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Row 3: Phone, Fax, PIC -->
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?=$languageArray['phone_code'][$language]?></label>
                    <input type="text" class="form-control" name="phone" id="phone" placeholder="01x-xxxxxxx">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?=$languageArray['fax_code'][$language]?></label>
                    <input type="text" class="form-control" name="fax" id="fax" placeholder="Fax number">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?=$languageArray['pic_code'][$language]?></label>
                    <input type="text" class="form-control" id="email" name="email" placeholder="PIC">
                  </div>
                </div>
              </div>

              <hr>

              <!-- Row 6: Billing Address -->
              <div <?= ($includeInvoice == 'Y' ? '' : 'style="display:none;"') ?>>
                <p class="font-weight-bold mb-2"><?=$languageArray['billing_address_code'][$language]?></p>
                <div class="row">
                  <div class="col-md-12">
                    <div class="form-group">
                      <label><?=$languageArray['billing_name_code'][$language]?></label>
                      <input type="text" class="form-control" name="billingName" id="billingName" placeholder="<?=$languageArray['billing_name_code'][$language]?>">
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><?=$languageArray['billing_address_code'][$language]?></label>
                      <input type="text" class="form-control" name="billingAddress" id="billingAddress" placeholder="<?=$languageArray['enter_billing_address_code'][$language]?> 1">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><?=$languageArray['billing_address_code'][$language]?> 2</label>
                      <input type="text" class="form-control" name="billingAddress2" id="billingAddress2" placeholder="<?=$languageArray['enter_billing_address_code'][$language]?> 2">
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><?=$languageArray['billing_address_code'][$language]?> 3</label>
                      <input type="text" class="form-control" name="billingAddress3" id="billingAddress3" placeholder="<?=$languageArray['enter_billing_address_code'][$language]?> 3">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><?=$languageArray['billing_address_code'][$language]?> 4</label>
                      <input type="text" class="form-control" name="billingAddress4" id="billingAddress4" placeholder="<?=$languageArray['enter_billing_address_code'][$language]?> 4">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><?=$languageArray['billing_state_code'][$language]?></label>
                      <select class="form-control select2" style="width:100%;" id="billingStates" name="billingStates">
                        <option selected="selected">-</option>
                        <?php while($rowCustomer2=mysqli_fetch_assoc($states2)){ ?>
                          <option value="<?=$rowCustomer2['id'] ?>"><?=$rowCustomer2['states'] ?></option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><?=$languageArray['billing_phone_code'][$language]?></label>
                      <input type="text" class="form-control" name="billingPhone" id="billingPhone" placeholder="01x-xxxxxxx">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><?=$languageArray['billing_fax_code'][$language]?></label>
                      <input type="text" class="form-control" name="billingFax" id="billingFax" placeholder="Fax number">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><?=$languageArray['billing_pic_code'][$language]?></label>
                      <input type="text" class="form-control" id="billingPic" name="billingPic" placeholder="PIC">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><?=$languageArray['currency_code'][$language]?></label>
                      <select class="form-control select2" style="width:100%;" id="currency" name="currency">
                        <option selected="selected">-</option>
                        <?php while($rowCurrency=mysqli_fetch_assoc($currencies)){ ?>
                          <option value="<?=$rowCurrency['id'] ?>"><?=$rowCurrency['currency'] ?></option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-danger custom-close-btn" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
              <button type="submit" class="btn btn-primary custom-save-btn" name="submit" id="submitMember"><?=$languageArray['submit_code'][$language]?></button>
            </div>
        </form>
      </div>
      <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>

<!-- Bin Modal -->
<div class="modal fade" id="binModal">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius:12px; overflow:hidden; border:none;">
      <form id="binForm">
        <div class="modal-header" style="background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); border:none;">
          <div>
            <h5 class="modal-title font-weight-bold mb-0" style="color:#1a1a2e;"><i class="fas fa-shopping-basket mr-2"></i><?=$languageArray['manage_bins_code'][$language]?></h5>
            <small style="color:#1a1a2e;"><span id="binCustomerName"></span></small>
          </div>
          <button type="button" class="close custom-close-btn-icon" style="color:#1a1a2e;" data-dismiss="modal"><span>&times;</span></button>
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
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius:8px; min-width:90px;"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-warning" name="submit" id="submitBin" style="border-radius:8px; min-width:90px; font-weight:600;"><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.bin-type-btn { background:#fff; text-align:center; font-weight:600; }
input[type="radio"]:checked + .bin-type-btn { border-color:#fda085 !important; background:#fff8f5; color:#fda085; }
#binDetails { transition: none; }
</style>

<!-- Bin History Modal -->
<div class="modal fade" id="binHistoryModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius:12px; overflow:hidden; border:none;">
      <div class="modal-header" style="background: linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%); border:none;">
        <div>
          <h5 class="modal-title font-weight-bold mb-0" style="color:#1a1a2e;"><i class="fas fa-history mr-2"></i><?=$languageArray['bin_history_code'][$language]?></h5>
          <small style="color:#1a1a2e;"><span id="binHistoryCustomerName"></span></small>
        </div>
        <button type="button" class="close custom-close-btn-icon" style="color:#1a1a2e;" data-dismiss="modal"><span>&times;</span></button>
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
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius:8px; min-width:90px; font-size:0.9rem;"><?=$languageArray['close_code'][$language]?></button>
      </div>
    </div>
  </div>
</div>

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
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
<script>

var hasBasket = <?= in_array('basket', $_SESSION['products']) ? 'true' : 'false' ?>;
var binTypeNames = <?= json_encode(array_column($binTypesArr, 'bin_type', 'id')) ?>;

$(function () {
  $('#selectAllCheckbox').on('change', function() {
    var checkboxes = $('#customerTable tbody input[type="checkbox"]');
    checkboxes.prop('checked', $(this).prop('checked')).trigger('change');
  });

  $('.select2').each(function() {
    $(this).select2({
        allowClear: true,
        placeholder: "Please Select",
        // Conditionally set dropdownParent based on the element’s location
        dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-body') : undefined
    });
  });

  $("#customerTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'ajax': {
      'url':'php/modules/customers/loadCustomers.php',
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
      { data: 'customer_code' },
      { data: 'reg_no' },
      { data: 'parent' },
      { data: 'customer_name' },
      { data: 'customer_address' },
      { data: 'customer_phone' },
      { data: 'pic' },
      {
        data: 'pending_bins',
        render: function (data) {
          if (!data) return '<span class="text-muted">—</span>';

          var map = {};
          try { map = (typeof data === 'string') ? JSON.parse(data) : data; } catch(e) {}

          if (typeof map !== 'object' || Array.isArray(map)) {
            return '<span class="text-muted">—</span>';
          }

          var keys = Object.keys(map).filter(function(k) { return map[k] > 0; });
          if (keys.length === 0) return '<span class="text-muted">—</span>';

          var html = '<div style="display:flex;flex-wrap:wrap;gap:4px;">';
          keys.forEach(function(typeId) {
            var count = map[typeId];
            var label = binTypeNames[typeId] || 'Type ' + typeId;
            html += '<span style="'
              + 'display:inline-flex;align-items:center;gap:5px;'
              + 'background:linear-gradient(135deg,#f6d365,#fda085);'
              + 'color:#7a3e00;font-size:0.75rem;font-weight:700;'
              + 'padding:3px 8px 3px 10px;border-radius:20px;'
              + 'box-shadow:0 1px 3px rgba(253,160,133,0.4);white-space:nowrap;">';
            html +=   '<span>' + label + '</span>';
            html +=   '<span style="background:rgba(0,0,0,0.15);border-radius:20px;padding:1px 6px;font-size:0.85em;">' + count + '</span>';
            html += '</span>';
          });
          html += '</div>';
          return html;
        }
      },
      { 
        data: 'deleted',
        render: function (data, type, row) {
          if (data == 0) {
            return '<div style="display:flex;gap:4px;">'
              + '<button onclick="edit(' + row.id + ')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button>'
              + (hasBasket ? '<button onclick="openBinModal(' + row.id + ', \'' + row.customer_name + '\')" class="btn btn-warning btn-sm"><i class="fas fa-shopping-basket"></i></button>'
                          + '<button onclick="openBinHistory(' + row.id + ', \'' + row.customer_name + '\')" class="btn btn-info btn-sm"><i class="fas fa-history"></i></button>' : '')
              + '<button onclick="deactivate(' + row.id + ')" class="btn btn-danger btn-sm custom-trash-icon-btn"><i class="fas fa-trash"></i></button>'
              + '</div>';
          } else {
            return '<button onclick="reactivate(' + row.id + ')" class="btn btn-warning btn-sm">Reactivate</button>';
          }
        }
      }
    ],
    "rowCallback": function( row, data, index ) {
      if (data.is_manual == 'Y') {
        $(row).css('background-color', '#f8d7da');
      }
    },        
  });
  
  $.validator.setDefaults({
    submitHandler: function () {
      if ($('#addModal').hasClass('show')) {
        $('#spinnerLoading').show();
        $.post('php/modules/customers/customers.php', $('#customerForm').serialize(), function(data){
          var obj = JSON.parse(data);
          if (obj.status === 'success') {
            $('#addModal').modal('hide');
            toastr["success"](obj.message, "Success:");
            $('#customerTable').DataTable().ajax.reload();
            $.get('php/modules/customers/getCustomers.php', function(customers) {
              $('#parent').empty().append('<option value="">Please Select</option>');
              customers.forEach(function(customer) {
                $('#parent').append('<option value="' + customer.id + '">' + customer.customer_name + '</option>');
              });
            });
          } else if (obj.status === 'failed') {
            toastr["error"](obj.message, "Failed:");
          } else {
            toastr["error"]("Something wrong when edit", "Failed:");
          }
          $('#spinnerLoading').hide();
        });
      } else if ($('#binModal').hasClass('show')) {
        $('#spinnerLoading').show();
        $.post('php/modules/customers/updateBin.php', $('#binForm').serialize(), function(data) {
          var obj = JSON.parse(data);
          if (obj.status === 'success') {
            $('#binModal').modal('hide');
            toastr['success'](obj.message, 'Success:');
            $('#binModal').find('#binCurrent').text(obj.pending_bins);
            $('#customerTable').DataTable().ajax.reload();
          } else {
            toastr['error'](obj.message, 'Failed:');
          }
          $('#spinnerLoading').hide();
        });
      }
    }
  });

  $('#addCustomers').on('click', function(){
    $('#addModal').find('#id').val("");
    $('#addModal').find('#code').val("");
    $('#addModal').find('#reg_no').val("");
    $('#addModal').find('#name').val("");
    $('#addModal').find('#address').val("");
    $('#addModal').find('#address2').val("");
    $('#addModal').find('#address3').val("");
    $('#addModal').find('#address4').val("");
    $('#addModal').find('#states').val("").trigger('change');
    $('#addModal').find('#phone').val("");
    $('#addModal').find('#fax').val("");
    $('#addModal').find('#email').val("");
    $('#addModal').find('#billingName').val("");
    $('#addModal').find('#billingAddress').val("");
    $('#addModal').find('#billingAddress2').val("");
    $('#addModal').find('#billingAddress3').val("");
    $('#addModal').find('#billingAddress4').val("");
    $('#addModal').find('#billingStates').val("").trigger('change');
    $('#addModal').find('#billingPhone').val("");
    $('#addModal').find('#billingFax').val("");
    $('#addModal').find('#billingPic').val("");
    $('#addModal').find('#currency').val("").trigger('change');
    $('#addModal').find('#parent').val("").trigger('change');
    $('#addModal').modal('show');
    
    $('#customerForm').validate({
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

  $('#uploadCustomer').on('click', function(){
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
        url: 'php/modules/customers/uploadCustomer.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            var obj = JSON.parse(response);
            if (obj.status === 'success') {
              $('#spinnerLoading').hide();
              $('#uploadModal').modal('hide');
              $('#customerTable').DataTable().ajax.reload();
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

    $("#customerTable tbody input[type='checkbox']").each(function () {
      if (this.checked) {
          selectedIds.push($(this).val());
      }
    });

    if (selectedIds.length > 0) {
      if (confirm('Are you sure you want to cancel these items?')) {
          $.post('php/modules/customers/deleteCustomer.php', {userID: selectedIds, type: 'MULTI'}, function(data){
              var obj = JSON.parse(data);
              
              if(obj.status === 'success'){
                $('#customerTable').DataTable().ajax.reload();
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
        alert("Please select at least one customer to delete.");
        $('#spinnerLoading').hide();
    }     
  });
});

function displayPreview(data) {
  // Parse the Excel data
  var workbook = XLSX.read(data, { type: 'binary' });

  // Get the first sheet
  var sheetName = workbook.SheetNames[0];
  var sheet = workbook.Sheets[sheetName];

  // Convert the sheet to an array of objects
  var jsonData = XLSX.utils.sheet_to_json(sheet, { header: 20 });

  // Get the headers
  var headers = Object.keys(jsonData[0] || {});

  // Ensure we handle cases where there may be less than 20 columns
  while (headers.length < 20) {
      headers.push(''); // Adding empty headers to reach 20 columns
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

      for (var j = 0; j < 20 && j < headers.length; j++) {
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

function edit(id){
  $('#spinnerLoading').show();
  $.post('php/modules/customers/getCustomer.php', {userID: id}, function(data){
      var obj = JSON.parse(data);
      
      if(obj.status === 'success'){
          $('#addModal').find('#id').val(obj.message.id);
          $('#addModal').find('#code').val(obj.message.customer_code);
          $('#addModal').find('#reg_no').val(obj.message.reg_no);
          $('#addModal').find('#name').val(obj.message.customer_name);
          $('#addModal').find('#address').val(obj.message.customer_address);
          $('#addModal').find('#address2').val(obj.message.customer_address2);
          $('#addModal').find('#address3').val(obj.message.customer_address3);
          $('#addModal').find('#address4').val(obj.message.customer_address4);
          $('#addModal').find('#states').val(obj.message.states).trigger('change');
          $('#addModal').find('#phone').val(obj.message.customer_phone);
          $('#addModal').find('#fax').val(obj.message.fax);
          $('#addModal').find('#email').val(obj.message.pic);
          $('#addModal').find('#billingName').val(obj.message.billing_name);
          $('#addModal').find('#billingAddress').val(obj.message.billing_address);
          $('#addModal').find('#billingAddress2').val(obj.message.billing_address2);
          $('#addModal').find('#billingAddress3').val(obj.message.billing_address3);
          $('#addModal').find('#billingAddress4').val(obj.message.billing_address4);
          $('#addModal').find('#billingStates').val(obj.message.billing_state).trigger('change');
          $('#addModal').find('#billingPhone').val(obj.message.billing_phone);
          $('#addModal').find('#billingFax').val(obj.message.billing_fax);
          $('#addModal').find('#billingPic').val(obj.message.billing_pic);
          $('#addModal').find('#currency').val(obj.message.currency).trigger('change');
          $('#addModal').find('#company').val(obj.message.customer).trigger('change');
          $('#addModal').find('#parent').val(obj.message.parent).trigger('change');
          $('#addModal').modal('show');
          
          $('#customerForm').validate({
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

function deactivate(id){
  if (confirm('Are you sure you want to delete this items?')) {
    $('#spinnerLoading').show();
    $.post('php/modules/customers/deleteCustomer.php', {userID: id}, function(data){
        var obj = JSON.parse(data);
        
        if(obj.status === 'success'){
            toastr["success"](obj.message, "Success:");
            $('#customerTable').DataTable().ajax.reload();
            $('#spinnerLoading').hide();
        }
        else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
            $('#spinnerLoading').hide();
        }
        else{
            toastr["error"]("Something wrong when activate", "Failed:");
            $('#spinnerLoading').hide();
        }
    });
  }
}

function reactivate(id){
  if (confirm('Are you sure you want to reactivate this items?')) {
    $('#spinnerLoading').show();
    $.post('php/modules/customers/reactivateCustomer.php', {userID: id}, function(data){
        var obj = JSON.parse(data);
        
        if(obj.status === 'success'){
            toastr["success"](obj.message, "Success:");
            $('#customerTable').DataTable().ajax.reload();
            $('#spinnerLoading').hide();
        }
        else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
            $('#spinnerLoading').hide();
        }
        else{
            toastr["error"]("Something wrong when activate", "Failed:");
            $('#spinnerLoading').hide();
        }
    });
  }
}

function openBinModal(id, name) {
  $('#binModal').find('#binCustomerId').val(id);
  $('#binModal').find('#binCustomerName').text(name);
  $('#binModal').find('#binTypeId').val('');
  $('#binModal').find('#binTypeSelect').val('');
  $('#binModal').find('#binQty').val('');
  $('#binModal').find('#binRemark').val('');
  $('input[name="binAction"][value="OUT"]').prop('checked', true);
  $('#binDetails').hide();
  $('#binCurrent').text('0');
  $('#binModal').modal('show');

  $('#binForm').validate({
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
    },
    rules: {
      binTypeId:  { required: true },
      binQty:     { required: true, min: 1 }
    }
  });
}

$('#binTypeSelect').on('change', function() {
  var typeId = $(this).val();
  var customerId = $('#binCustomerId').val();
  $('#binTypeId').val(typeId);
  $('#binQty').val('');
  $('#binRemark').val('');
  $('input[name="binAction"][value="OUT"]').prop('checked', true);

  if (!typeId) {
    $('#binDetails').slideUp(150);
    return;
  }

  // Show skeleton, hide pending card while loading
  $('#binLoadingSkeleton').show();
  $('#binPendingCard').hide();
  if (!$('#binDetails').is(':visible')) {
    $('#binDetails').slideDown(200);
  }

  $.post('php/modules/customers/getBinPending.php', { customer_id: customerId, bin_type_id: typeId }, function(data) {
    var obj = JSON.parse(data);
    var count = (obj.status === 'success') ? obj.pending_bins : 0;
    $('#binCurrent').text(count);
    $('#binLoadingSkeleton').hide();
    $('#binPendingCard').show();
  }).fail(function() {
    $('#binCurrent').text('0');
    $('#binLoadingSkeleton').hide();
    $('#binPendingCard').show();
  });
});

function openBinHistory(id, name) {
  $('#binHistoryModal').find('#binHistoryCustomerName').text(name);
  $('#binHistoryModal').find('#binHistoryTypeSelect').val('');
  $('#binHistoryContent').hide();
  $('#binHistoryPrompt').show();
  $('#binHistoryList').html('');
  $('#binHistoryPager').html('');
  $('#binHistoryModal').data('customerId', id).modal('show');
}

$('#binHistoryTypeSelect').on('change', function() {
  var typeId = $(this).val();
  var customerId = $('#binHistoryModal').data('customerId');

  if (!typeId) {
    $('#binHistoryContent').hide();
    $('#binHistoryPrompt').show();
    return;
  }

  $('#binHistoryPrompt').hide();
  $('#binHistoryContent').show();
  $('#binHistoryList').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i></div>');
  $('#binHistoryPager').html('');

  $.post('php/modules/customers/getBinHistory.php', {
    draw: 1, start: 0, length: 1000,
    order: [{column: 0, dir: 'desc'}],
    columns: [{data: 'created_at'}],
    search: {value: ''},
    customer_id: customerId,
    bin_type_id: typeId
  }, function(data) {
    var obj = JSON.parse(data);
    var rows = obj.aaData;

    if (!rows || rows.length === 0) {
      $('#binHistoryList').html('<div class="text-center text-muted py-5"><i class="fas fa-inbox fa-2x mb-2"></i><div>No records found</div></div>');
      return;
    }

    var perPage = 5;
    var currentPage = 1;
    var totalPages = Math.ceil(rows.length / perPage);

    function renderCards(page) {
      var start = (page - 1) * perPage;
      var pageRows = rows.slice(start, start + perPage);
      var html = '';
      pageRows.forEach(function(r) {
        var isOut = r.type === 'OUT';
        var icon       = isOut ? 'fa-arrow-up' : 'fa-arrow-down';
        var iconClass  = isOut ? 'text-warning' : 'text-success';
        var badgeClass = isOut ? 'badge-warning' : 'badge-success';
        var label      = isOut ? 'OUT' : 'IN';
        html += '<div class="card mb-2 border-0 shadow-sm">';
        html +=   '<div class="card-body py-2 px-3 d-flex align-items-center">';
        html +=     '<div class="mr-3"><i class="fas ' + icon + ' fa-lg ' + iconClass + '"></i></div>';
        html +=     '<div class="flex-fill">';
        html +=       '<div class="d-flex justify-content-between align-items-center">';
        html +=         '<span class="font-weight-bold"><span class="badge ' + badgeClass + ' mr-1">' + label + '</span>' + r.qty + ' baskets</span>';
        html +=         '<small class="text-muted">' + r.created_at + '</small>';
        html +=       '</div>';
        html +=       '<small class="text-muted"><i class="fas fa-user mr-1"></i>' + (r.user_name || '-');
        if (r.remark) html += ' &middot; <i class="fas fa-comment mr-1"></i>' + r.remark;
        html +=       '</small>';
        html +=     '</div>';
        html +=   '</div>';
        html += '</div>';
      });
      $('#binHistoryList').html(html);

      var pager = '<small class="text-muted">Showing ' + (start + 1) + '-' + Math.min(start + perPage, rows.length) + ' of ' + rows.length + '</small>';
      pager += '<ul class="pagination pagination-sm mb-0">';
      pager += '<li class="page-item ' + (page === 1 ? 'disabled' : '') + '"><a class="page-link" href="#" data-page="' + (page - 1) + '">&laquo;</a></li>';
      for (var i = 1; i <= totalPages; i++) {
        pager += '<li class="page-item ' + (i === page ? 'active' : '') + '"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
      }
      pager += '<li class="page-item ' + (page === totalPages ? 'disabled' : '') + '"><a class="page-link" href="#" data-page="' + (page + 1) + '">&raquo;</a></li>';
      pager += '</ul>';
      $('#binHistoryPager').html(pager);
    }

    renderCards(currentPage);

    $('#binHistoryPager').off('click').on('click', 'a.page-link', function(e) {
      e.preventDefault();
      var page = parseInt($(this).data('page'));
      if (page < 1 || page > totalPages) return;
      currentPage = page;
      renderCards(currentPage);
    });
  });
});
</script>
