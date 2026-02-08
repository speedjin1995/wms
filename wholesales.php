<?php
require_once 'php/db_connect.php';

session_start();

if(!isset($_SESSION['userID'])){
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
}
else{
  $user = $_SESSION['userID'];
  $company = $_SESSION['customer'];
  $stmt = $db->prepare("SELECT * from users where id = ?");
	$stmt->bind_param('s', $user);
	$stmt->execute();
	$result = $stmt->get_result();
  $role = 'NORMAL';
	$allowEdit = 'N';
  $allowDelete = 'N';

	if(($row = $result->fetch_assoc()) !== null){
    $role = $row['role_code'];
    $allowEdit = $row['allow_edit'];
    $allowDelete = $row['allow_delete'];
  }

  if ($user != 2){
    $products = $db->query("SELECT * FROM products WHERE deleted = '0' AND customer = '$company' ORDER BY product_name ASC");
    $products2 = $db->query("SELECT * FROM products WHERE deleted = '0' AND customer = '$company' ORDER BY product_name ASC");
    $supplies = $db->query("SELECT * FROM supplies WHERE deleted = '0' AND customer = '$company' ORDER BY supplier_name ASC");
    $supplies2 = $db->query("SELECT * FROM supplies WHERE deleted = '0' AND customer = '$company' ORDER BY supplier_name ASC");
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' AND customer = '$company' ORDER BY customer_name ASC");
    $customers2 = $db->query("SELECT * FROM customers WHERE deleted = '0' AND customer = '$company' ORDER BY customer_name ASC");
    $vehicles = $db->query("SELECT * FROM vehicles WHERE deleted = '0' AND customer = '$company' ORDER BY veh_number ASC");
    $vehicles2 = $db->query("SELECT * FROM vehicles WHERE deleted = '0' AND customer = '$company' ORDER BY veh_number ASC");
    $drivers = $db->query("SELECT * FROM drivers WHERE deleted = '0' AND customer = '$company' ORDER BY driver_name ASC");
    $grades = $db->query("SELECT DISTINCT g.*, p.product_name FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY p.product_name ASC, g.units ASC");
    $grades2 = $db->query("SELECT DISTINCT g.*, p.product_name FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY p.product_name ASC, g.units ASC");
    $grades3 = $db->query("SELECT DISTINCT g.*, p.product_name FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' AND g.customer = '$company' ORDER BY p.product_name ASC, g.units ASC");
    $users = $db->query("SELECT * FROM users WHERE deleted = '0' AND customer = '$company' ORDER BY name ASC");
  } else {
    $products = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $products2 = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $supplies = $db->query("SELECT * FROM supplies WHERE deleted = '0' ORDER BY supplier_name ASC");
    $supplies2 = $db->query("SELECT * FROM supplies WHERE deleted = '0' ORDER BY supplier_name ASC");
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' ORDER BY customer_name ASC");
    $customers2 = $db->query("SELECT * FROM customers WHERE deleted = '0' ORDER BY customer_name ASC");
    $vehicles = $db->query("SELECT * FROM vehicles WHERE deleted = '0' ORDER BY veh_number ASC");
    $vehicles2 = $db->query("SELECT * FROM vehicles WHERE deleted = '0' ORDER BY veh_number ASC");
    $drivers = $db->query("SELECT * FROM drivers WHERE deleted = '0' ORDER BY driver_name ASC");
    $grades = $db->query("SELECT DISTINCT g.*, p.product_name FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC");
    $grades2 = $db->query("SELECT DISTINCT g.*, p.product_name FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC");
    $grades3 = $db->query("SELECT DISTINCT g.*, p.product_name FROM grades g LEFT JOIN product_grades pg ON g.id = pg.grade_id LEFT JOIN products p ON pg.product_id = p.id WHERE g.deleted = '0' AND pg.deleted = '0' ORDER BY p.product_name ASC, g.units ASC");
    $users = $db->query("SELECT * FROM users WHERE deleted = '0' ORDER BY name ASC");
  }

  $units = $db->query("SELECT * FROM units WHERE deleted = '0'");
  $units1 = $db->query("SELECT * FROM units WHERE deleted = '0'");
  
  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>
<!--select class="form-control" style="width: 100%;" id="uomhidden" name="uomhidden" style="display:none;"> 
  <option selected="selected">-</option>
  <?php while($rowunits2=mysqli_fetch_assoc($units1)){ ?>
    <option value="<?=$rowunits2['id'] ?>"><?=$rowunits2['units'] ?></option>
  <?php } ?>
</select-->

<style>
  @media screen and (min-width: 676px) {
    .modal-dialog {
      max-width: 1800px; /* New width for default modal */
    }
  }
</style>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?=$languageArray['weighing_record_code'][$language]?></h1>
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->

<!-- Main content -->
<div class="content">
  <div class="container-fluid">
  <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <div class="row">
              <div class="form-group col-3">
                <label><?=$languageArray['from_date_code'][$language]?>:</label>
                <div class="input-group date" id="fromDatePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#fromDatePicker" id="fromDate"/>
                  <div class="input-group-append" data-target="#fromDatePicker" data-toggle="datetimepicker">
                  <div class="input-group-text"><i class="fa fa-calendar"></i></div></div>
                </div>
              </div>

              <div class="form-group col-3">
                <label><?=$languageArray['to_date_code'][$language]?>:</label>
                <div class="input-group date" id="toDatePicker" data-target-input="nearest">
                  <input type="text" class="form-control datetimepicker-input" data-target="#toDatePicker" id="toDate"/>
                  <div class="input-group-append" data-target="#toDatePicker" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>

              <div class="col-3">
                <div class="form-group">
                  <label><?=$languageArray['transaction_status_code'][$language]?></label>
                  <select class="form-control" id="transactionStatusFilter" name="transactionStatusFilter">
                    <option value="DISPATCH" selected><?=$languageArray['dispatch_code'][$language]?></option>
                    <option value="RECEIVING"><?=$languageArray['receiving_code'][$language]?></option>
                    <option value="SALE-BAL"><?=$languageArray['sale_balance_code'][$language]?></option>
                  </select>
                </div>
              </div>

              <div class="col-3" id="customerStatusDiv">
                <div class="form-group">
                  <label><?=$languageArray['customer_code'][$language]?></label>
                  <select class="form-control select2" id="customerNoFilter" name="customerNoFilter">
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($rowCustomer2=mysqli_fetch_assoc($customers)){ ?>
                      <option value="<?=$rowCustomer2['id'] ?>"><?=$rowCustomer2['customer_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="col-3" id="supplierStatusDiv" style="display: none;">
                <div class="form-group">
                  <label><?=$languageArray['supplier_code'][$language]?></label>
                  <select class="form-control select2" id="supplierNoFilter" name="supplierNoFilter">
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($rowCustomer2=mysqli_fetch_assoc($supplies)){ ?>
                      <option value="<?=$rowCustomer2['id'] ?>"><?=$rowCustomer2['supplier_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="col-3">
                <div class="form-group">
                  <label><?=$languageArray['vehicle_no_code'][$language]?></label>
                  <select class="form-control select2" id="vehicleNoFilter" name="vehicleNoFilter">
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($rowVehicle=mysqli_fetch_assoc($vehicles2)){ ?>
                      <option value="<?=$rowVehicle['veh_number'] ?>"><?=$rowVehicle['veh_number'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="col-3" id="otherVehicleFilterDiv" style="display: none;">
                <div class="form-group">
                  <label><?=$languageArray['other_vehicle_no_code'][$language]?></label>
                  <input type="text" class="form-control" id="otherVehicleNoFilter" name="otherVehicleNoFilter" placeholder="<?=$languageArray['please_enter_vehicle_no_code'][$language]?>">
                </div>
              </div>

              <div class="col-3">
                <div class="form-group">
                  <label><?=$languageArray['checked_by_code'][$language]?></label>
                  <input type="text" class="form-control" id="checkedByFilter" name="checkedByFilter" placeholder="<?=$languageArray['please_enter_name_code'][$language]?>">
                </div>
              </div>

              <div class="col-3">
                <div class="form-group">
                  <label><?=$languageArray['weighed_by_code'][$language]?></label>
                  <select class="form-control select2" id="weightByFilter" name="weightByFilter">
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php while($rowUser=mysqli_fetch_assoc($users)){ ?>
                      <option value="<?=$rowUser['id'] ?>"><?=$rowUser['name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <!--div class="col-3">
                <div class="form-group">
                  <label><?=$languageArray['product_code'][$language]?></label>
                  <select class="form-control select2" id="productFilter" name="productFilter" style="width: 100%;">
                    <option selected="selected">-</option>
                    <?php while($rowStatus2=mysqli_fetch_assoc($products)){ ?>
                      <option value="<?=$rowStatus2['id'] ?>"><?=$rowStatus2['product_name'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div-->

              <div class="col-3" style="display:none;">
                <div class="form-group">
                  <label><?=$languageArray['status_code'][$language]?></label>
                  <select class="form-control" id="statusFilter" name="statusFilter">
                    <option value="active" selected><?=$languageArray['active_code'][$language]?></option>
                    <option value="deleted"><?=$languageArray['deleted_code'][$language]?></option>
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-9"></div>
              <div class="col-3">
                <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="filterSearch">
                  <i class="fas fa-search"></i>
                  <?=$languageArray['search_code'][$language]?>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-12">
        <div class="card card-info">
          <div class="card-header">
            <div class="row">
              <div class="col-10"></div>
              <!-- <div class="col-2">
                <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="refreshBtn"><i class="fas fa-sync"></i> Refresh</button>
              </div> -->
              <div class="col-2" style="visibility: hidden;">
                <button type="button" class="btn btn-block bg-gradient-success btn-sm" onclick="newEntry()"><i class="fas fa-plus"></i> <?=$languageArray['add_new_code'][$language]?></button>
              </div>
            </div>
          </div>

          <div class="card-body">
            <table id="weightTable" class="table table-bordered table-striped display">
              <thead>
                <tr>
                  <th><?=$languageArray['serial_no_code'][$language]?></th>
                  <th><?=$languageArray['do_po_no_code'][$language]?></th>
                  <th><?=$languageArray['sec_bill_no_code'][$language]?></th>
                  <th><?=$languageArray['created_datetime_code'][$language]?></th>
                  <th><?=$languageArray['parent_code'][$language]?></th>
                  <th><?=$languageArray['customer_supplier_code'][$language]?></th>
                  <th><?=$languageArray['vehicle_no_code'][$language]?></th>
                  <th><?=$languageArray['driver_code'][$language]?></th>
                  <th><?=$languageArray['total_item_code'][$language]?></th>
                  <th><?=$languageArray['total_weight_code'][$language]?></th>
                  <th><?=$languageArray['total_reject_code'][$language]?></th>
                  <th><?=$languageArray['weighed_by_code'][$language]?></th>
                  <th><?=$languageArray['checked_by_code'][$language]?></th>
                  <th width="10%"><?=$languageArray['actions_code'][$language]?></th>
                </tr>
              </thead>
              <!-- <tfoot>
                <tr>
                    <th colspan="4">Total</th>
                    <th></th>
                    <th></th>
                    <th></th> 
                    <th></th>
                </tr>
              </tfoot> -->
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="extendModal">
  <div class="modal-dialog modal-xl" style="max-width: 90%;">
    <div class="modal-content">
      <form role="form" id="extendForm">
        <div class="modal-header bg-gray-dark color-palette">
          <h4 class="modal-title"><?=$languageArray['add_new_entry_code'][$language]?></h4>
          <button type="button" class="close bg-gray-dark color-palette" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body" >
          <input type="hidden" class="form-control" id="id" name="id">
          
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label><?=$languageArray['status_code'][$language]?> *</label>
                <select class="form-control" id="status" name="status" required>
                  <option value="DISPATCH"><?=$languageArray['dispatch_code'][$language]?></option>
                  <option value="RECEIVING"><?=$languageArray['receiving_code'][$language]?></option>
                  <option value="SALE-BAL"><?=$languageArray['sale_balance_code'][$language]?></option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label><?=$languageArray['do_po_no_code'][$language]?> *</label>
                <input type="text" class="form-control" id="doPoNo" name="doPoNo" required>
              </div>
            </div>
            <div class="col-md-4" id="securityBillDiv">
              <div class="form-group">
                <label><?=$languageArray['sec_bill_no_code'][$language]?></label>
                <input type="text" class="form-control" id="securityBillNo" name="securityBillNo">
              </div>
            </div>
            <div class="col-md-4" id="customerDiv">
              <div class="form-group">
                <label><?=$languageArray['customer_code'][$language]?></label>
                <select class="form-control select2" id="customer" name="customer">
                  <option value="" selected disabled hidden>Please Select</option>
                  <option value="OTHERS"><?=$languageArray['others_code'][$language]?></option>
                  <?php while($rowCustomer3=mysqli_fetch_assoc($customers2)){ ?>
                    <option value="<?=$rowCustomer3['id'] ?>"><?=$rowCustomer3['customer_name'] ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
            <div class="col-md-4" id="customerOtherDiv">
              <div class="form-group">
                <label><?=$languageArray['customer_other_code'][$language]?></label>
                <input type="text" class="form-control" id="customerOther" name="customerOther">
              </div>
            </div>
            <div class="col-md-4" id="supplierDiv">
              <div class="form-group">
                <label><?=$languageArray['supplier_code'][$language]?></label>
                <select class="form-control select2" id="supplier" name="supplier">
                  <option value="" selected disabled hidden>Please Select</option>
                  <option value="OTHERS"><?=$languageArray['others_code'][$language]?></option>
                  <?php while($rowSupplier3=mysqli_fetch_assoc($supplies2)){ ?>
                    <option value="<?=$rowSupplier3['id'] ?>"><?=$rowSupplier3['supplier_name'] ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
            <div class="col-md-4" id="supplierOtherDiv">
              <div class="form-group">
                <label><?=$languageArray['supplier_other_code'][$language]?></label>
                <input type="text" class="form-control" id="supplierOther" name="supplierOther">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label><?=$languageArray['vehicle_no_code'][$language]?></label>
                <select class="form-control select2" id="vehicle" name="vehicle">
                  <option value="" selected disabled hidden>Please Select</option>
                  <?php while($rowVehicle3=mysqli_fetch_assoc($vehicles)){ ?>
                    <option value="<?=$rowVehicle3['veh_number'] ?>"><?=$rowVehicle3['veh_number'] ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
            <div class="col-md-4" id="vehicleNoOtherDiv" style="display: none;">
              <div class="form-group">
                <label><?=$languageArray['other_vehicle_no_code'][$language]?></label>
                <input type="text" class="form-control" id="otherVehicleNo" name="otherVehicleNo" placeholder="<?=$languageArray['please_enter_vehicle_no_code'][$language]?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label><?=$languageArray['driver_code'][$language]?></label>
                <select class="form-control select2" id="driver" name="driver">
                  <option value="" selected disabled hidden>Please Select</option>
                  <?php while($rowDriver3=mysqli_fetch_assoc($drivers)){ ?>
                    <option value="<?=$rowDriver3['driver_name'] ?>"><?=$rowDriver3['driver_name'] ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
          </div>
          
          <hr>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0"><?=$languageArray['weight_details_code'][$language]?></h5>
            <button type="button" class="btn btn-success btn-sm" id="addWeightBtn">
              <i class="fas fa-plus"></i> <?=$languageArray['add_weight_code'][$language]?>
            </button>
          </div>
          <div class="row">
            <table class="table table-bordered nowrap table-striped align-middle" style="width:100%">
              <thead>
                <tr>
                  <th><?=$languageArray['number_short_code'][$language]?></th>
                  <th width="10%"><?=$languageArray['product_code'][$language]?></th>
                  <th width="10%"><?=$languageArray['grade_code'][$language]?></th>
                  <th><?=$languageArray['gross_code'][$language]?></th>
                  <th><?=$languageArray['tare_code'][$language]?></th>
                  <th><?=$languageArray['net_code'][$language]?></th>
                  <th><?=$languageArray['price_code'][$language]?></th>
                  <th><?=$languageArray['total_code'][$language]?></th>
                  <th><?=$languageArray['time_code'][$language]?></th>
                  <th width="10%"><?=$languageArray['actions_code'][$language]?></th>
                </tr>
              </thead>
              <tbody id="weightDetailsTable">
                <!-- Weight details will be populated here -->
              </tbody>
              <tfoot id="weightDetailsFooter">
                <tr>
                  <th colspan="3"><?=$languageArray['total_code'][$language]?></th>
                  <th id="totalWeightGross">0.00</th>
                  <th id="totalWeightTare">0.00</th>
                  <th id="totalWeightNet">0.00</th>
                  <th></th>
                  <th id="totalWeightPrice">0.00</th>
                  <th></th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>

          <hr>
          <h5><?=$languageArray['reject_details_code'][$language]?></h5>
          <div class="row">
            <table class="table table-bordered nowrap table-striped align-middle" style="width:100%">
              <thead>
                <tr>
                  <th><?=$languageArray['number_short_code'][$language]?></th>
                  <th width="10%"><?=$languageArray['product_code'][$language]?></th>
                  <th width="10%"><?=$languageArray['grade_code'][$language]?></th>
                  <th><?=$languageArray['gross_code'][$language]?></th>
                  <th><?=$languageArray['tare_code'][$language]?></th>
                  <th><?=$languageArray['net_code'][$language]?></th>
                  <th><?=$languageArray['price_code'][$language]?></th>
                  <th><?=$languageArray['total_code'][$language]?></th>
                  <th><?=$languageArray['time_code'][$language]?></th>
                  <th width="10%"><?=$languageArray['actions_code'][$language]?></th>
                </tr>
              </thead>
              <tbody id="rejectDetailsTable">
                <!-- Weight details will be populated here -->
              </tbody>
              <tfoot id="rejectDetailsFooter">
                <tr>
                  <th colspan="3"><?=$languageArray['total_code'][$language]?></th>
                  <th id="totalRejectGross">0.00</th>
                  <th id="totalRejectTare">0.00</th>
                  <th id="totalRejectNet">0.00</th>
                  <th></th>
                  <th id="totalRejectPrice">0.00</th>
                  <th></th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <div class="modal-footer justify-content-between bg-gray-dark color-palette">
          <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-primary" id="saveButton"><?=$languageArray['save_code'][$language]?></button>
        </div>
      </form>
    </div> <!-- /.modal-content -->
  </div> <!-- /.modal-dialog -->
</div> <!-- /.modal -->   

<div class="modal fade" id="cancelModal">
  <div class="modal-dialog modal-xl" style="max-width: 90%;">
    <div class="modal-content">
      <form role="form" id="cancelForm">
        <div class="modal-header bg-gray-dark color-palette">
          <h4 class="modal-title"><?=$languageArray['delete_reason_code'][$language]?></h4>
          <button type="button" class="close bg-gray-dark color-palette" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-12">
              <div class="form-group">
                <label><?=$languageArray['delete_reason_code'][$language]?> *</label>
                <textarea class="form-control" id="cancelReason" name="cancelReason" rows="3" required></textarea>
              </div>
            </div>
            <input type="hidden" class="form-control" id="id" name="id">
          </div>
        </div>
        <div class="modal-footer justify-content-between bg-gray-dark color-palette">
          <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-success" id="submitCancel"><?=$languageArray['submit_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Values
var controlflow = "None";
var indicatorUnit = "kg";
var weightUnit = "1";
var rate = 1;
var currency = "1";
var weightCount = 0;
var rejectCount = 0;

$(function () {
  $('#uomhidden').hide();
  var userRole = '<?=$role ?>';
  const today = new Date();
  const tomorrow = new Date(today);
  const yesterday = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);
  yesterday.setDate(yesterday.getDate() - 7);

  $('#fromDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: today
  });

  $('#toDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: today
  });

  $('.select2').each(function() {
    $(this).select2({
        allowClear: true,
        placeholder: "Please Select",
        // Conditionally set dropdownParent based on the element’s location
        dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-body') : undefined
    });
  });

  var fromDateI = $('#fromDate').val();
  var toDateI = $('#toDate').val();
  var transactionStatusI = $('#transactionStatusFilter').val();
  var statusI = $('#statusFilter').val();
  var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
  var customerNoI = $('#customerNoFilter').val() ? $('#customerNoFilter').val() : '';
  var supplierNoI = $('#supplierNoFilter').val() ? $('#supplierNoFilter').val() : '';
  var vehicleNoI = $('#vehicleNoFilter').val() ? $('#vehicleNoFilter').val() : '';
  var otherVehicleNoI = $('#otherVehicleNoFilter').val() ? $('#otherVehicleNoFilter').val() : '';
  var checkedByI = $('#checkedByFilter').val() ? $('#checkedByFilter').val() : '';
  var weightedByI = $('#weightByFilter').val() ? $('#weightByFilter').val() : '';

  var table = $("#weightTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'searching': true,
    'order': [[ 1, 'asc' ]],
    'columnDefs': [ { orderable: false, targets: [0] }],
    'ajax': {
      'url':'php/filterWholesale.php',
      'data': {
        fromDate: fromDateI,
        toDate: toDateI,
        transactionStatus: transactionStatusI,
        status: statusI,
        product: productI,
        customer: customerNoI,
        supplier: supplierNoI,
        vehicle: vehicleNoI,
        otherVehicle: otherVehicleNoI,
        checkedBy: checkedByI,
        weightedBy: weightedByI
      } 
    },
    'columns': [
      { data: 'serial_no' },
      { data: 'po_no' },
      { data: 'security_bills' },
      { data: 'created_datetime' },
      { data: 'parent' },
      { data: 'customer_supplier' },
      { data: 'vehicle_no' },
      { data: 'driver' },
      { data: 'total_item' },
      { data: 'total_weight' },
      { data: 'total_reject' },
      { data: 'weighted_by' },
      { data: 'checked_by' },
      { 
        data: 'id',
        class: 'action-button',
        render: function ( data, type, row ) {
          var buttons = '<div class="row">';
          if(<?=$allowEdit == 'Y' ? 'true' : 'false'?>) {
            buttons += '<div class="col-3 mr-2"><button type="button" id="edit'+data+'" onclick="edit('+data+')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button></div>';
          }
          buttons += '<div class="col-3 mr-2"><button type="button" id="print'+data+'" onclick="print('+data+')" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></button></div>';
          if(<?=$allowDelete == 'Y' ? 'true' : 'false'?>) {
            buttons += '<div class="col-3"><button type="button" id="deactivate'+data+'" onclick="deactivate('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></div>';
          }
          buttons += '</div>';
          return buttons;
        }
      }
    ],
    // "footerCallback": function(row, data, start, end, display) {
    //   var api = this.api();

    //   // Calculate total for 'total_cages' column
    //   var totalCages = api
    //       .column(4, { page: 'current' })
    //       .data()
    //       .reduce(function(a, b) {
    //           return a + parseFloat(b);
    //       }, 0);

    //   // Calculate total for 'total_birds' column
    //   var totalBirds = api
    //       .column(5, { page: 'current' })
    //       .data()
    //       .reduce(function(a, b) {
    //           return a + parseFloat(b);
    //       }, 0);

    //   var totalConts = api
    //     .column(6, { page: 'current' })
    //     .data()
    //     .reduce(function(a, b) {
    //         return a + parseFloat(b);
    //     }, 0);

    //   // Update footer with the total
    //   $(api.column(4).footer()).html(totalCages.toFixed(3));
    //   $(api.column(5).footer()).html(totalBirds.toFixed(3));
    //   $(api.column(6).footer()).html(totalConts);
    // }
  });

  // Add event listener for opening and closing details on row click
  $('#weightTable tbody').on('click', 'tr', function (e) {
      var tr = $(this); // The row that was clicked
      var row = table.row(tr);

      // Exclude clicks on buttons, checkboxes, and form elements
      if ($(e.target).closest('td').hasClass('select-checkbox') || 
          $(e.target).closest('td').hasClass('action-button') ||
          $(e.target).is('select') || 
          $(e.target).is('input') ||
          $(e.target).is('button')) {
        return;
      }

      if (row.child.isShown()) {
          // This row is already open - close it
          row.child.hide();
          tr.removeClass('shown');
      } else {
          $.post('php/getWholesale.php', { userID: row.data().id}, function (data) {
            var obj = JSON.parse(data);
            if (obj.status === 'success') {
              row.child(format(obj.message)).show();
              tr.addClass("shown");
              if(obj.message.weightDetails && obj.message.weightDetails.length > 0) {
                populateFilters(obj.message.id, obj.message.weightDetails);
              }
            }
          });
      }
  });

  // Add event listener for opening and closing details
  // $('#weightTable tbody').on('click', 'td.dt-control', function () {
  //   var tr = $(this).closest('tr');
  //   var row = table.row( tr );

  //   if ( row.child.isShown() ) {
  //     // This row is already open - close it
  //     row.child.hide();
  //     tr.removeClass('shown');
  //   }
  //   else {
  //     // Open this row
  //     <?php 
  //       if($role == "ADMIN"){
  //         echo 'row.child( format(row.data()) ).show();tr.addClass("shown");';
  //       }
  //       else{
  //         echo 'row.child( formatNormal(row.data()) ).show();tr.addClass("shown");';
  //       }
  //     ?>
  //   }
  // });

  $('#filterSearch').on('click', function(){
    //$('#spinnerLoading').show();
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var transactionStatusI = $('#transactionStatusFilter').val();
    var statusI = $('#statusFilter').val();
    var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
    var customerNoI = $('#customerNoFilter').val() ? $('#customerNoFilter').val() : '';
    var supplierNoI = $('#supplierNoFilter').val() ? $('#supplierNoFilter').val() : '';
    var vehicleNoI = $('#vehicleNoFilter').val() ? $('#vehicleNoFilter').val() : '';
    var otherVehicleNoI = $('#otherVehicleNoFilter').val() ? $('#otherVehicleNoFilter').val() : '';
    var checkedByI = $('#checkedByFilter').val() ? $('#checkedByFilter').val() : '';
    var weightedByI = $('#weightByFilter').val() ? $('#weightByFilter').val() : '';

    //Destroy the old Datatable
    $("#weightTable").DataTable().clear().destroy();

    //Create new Datatable
    table = $("#weightTable").DataTable({
      "responsive": true,
      "autoWidth": false,
      'processing': true,
      'serverSide': true,
      'serverMethod': 'post',
      'searching': true,
      'order': [[ 1, 'asc' ]],
      'columnDefs': [ { orderable: false, targets: [0] }],
      'ajax': {
        'url':'php/filterWholesale.php',
        'data': {
          fromDate: fromDateI,
          toDate: toDateI,
          transactionStatus: transactionStatusI,
          status: statusI,
          product: productI,
          customer: customerNoI,
          supplier: supplierNoI,
          vehicle: vehicleNoI,
          otherVehicle: otherVehicleNoI,
          checkedBy: checkedByI,
          weightedBy: weightedByI
        } 
      },
      'columns': [
        { data: 'serial_no' },
        { data: 'po_no' },
        { data: 'security_bills' },
        { data: 'created_datetime' },
        { data: 'parent' },
        { data: 'customer_supplier' },
        { data: 'vehicle_no' },
        { data: 'driver' },
        { data: 'total_item' },
        { data: 'total_weight' },
        { data: 'total_reject' },
        { data: 'weighted_by' },
        { data: 'checked_by' },
        { 
          data: 'id',
          class: 'action-button',
          render: function ( data, type, row ) {
            var buttons = '<div class="row">';
            if(<?=$allowEdit == 'Y' ? 'true' : 'false'?>) {
              buttons += '<div class="col-3 mr-2"><button type="button" id="edit'+data+'" onclick="edit('+data+')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button></div>';
            }
            buttons += '<div class="col-3 mr-2"><button type="button" id="print'+data+'" onclick="print('+data+')" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></button></div>';

            if(<?=$allowDelete == 'Y' ? 'true' : 'false'?>) {
              buttons += '<div class="col-3"><button type="button" id="deactivate'+data+'" onclick="deactivate('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></div>';
            }
            
            buttons += '</div>';
            return buttons;
          }
        }
      ],
      // "footerCallback": function(row, data, start, end, display) {
      //   var api = this.api();

      //   // Calculate total for 'total_cages' column
      //   var totalCages = api
      //       .column(4, { page: 'current' })
      //       .data()
      //       .reduce(function(a, b) {
      //           return a + parseFloat(b);
      //       }, 0);

      //   // Calculate total for 'total_birds' column
      //   var totalBirds = api
      //       .column(5, { page: 'current' })
      //       .data()
      //       .reduce(function(a, b) {
      //           return a + parseFloat(b);
      //       }, 0);

      //   var totalConts = api
      //     .column(6, { page: 'current' })
      //     .data()
      //     .reduce(function(a, b) {
      //         return a + parseFloat(b);
      //     }, 0);

      //   // Update footer with the total
      //   $(api.column(4).footer()).html(totalCages.toFixed(3));
      //   $(api.column(5).footer()).html(totalBirds.toFixed(3));
      //   $(api.column(6).footer()).html(totalConts);
      // }
    });
  });

  // $.post('http://127.0.0.1:5002/', $('#setupForm').serialize(), function(data){
  //   if(data == "true"){
  //     $('#indicatorConnected').addClass('bg-primary');
  //     $('#checkingConnection').removeClass('bg-danger');
  //     //$('#captureWeight').removeAttr('disabled');
  //   }
  //   else{
  //     $('#indicatorConnected').removeClass('bg-primary');
  //     $('#checkingConnection').addClass('bg-danger');
  //     //$('#captureWeight').attr('disabled', true);
  //   }
  // });
  
  // setInterval(function () {
  //   $.post('http://127.0.0.1:5002/handshaking', function(data){
  //     if(data != "Error"){
  //       console.log("Data Received:" + data);
  //       var text = data.split(" ");

  //       if(text.length > 2){
  //         $('#indicatorWeight').html(text[text.length - 2] + ' ' + text[text.length - 1]);
  //         var convertTog1 = convertUnits(text[text.length - 2], text[text.length - 1], 'g');

  //         if($('#uom').val() && $('#product').val()){
  //           var uomDesc = $("#uomhidden option[value='"+$('#uom').val()+"']").text();
  //           var weight = $('#product :selected').data('unit');
  //           var convertTog2 = convertUnits(weight, uomDesc, 'g');
  //           var count = parseFloat(convertTog1) / parseFloat(convertTog2);
  //           $('#countingWeight').text(count.toFixed(0));
  //         }
  //       }
  //     }
  //   });
  // }, 500);

  $.validator.setDefaults({
    submitHandler: function () {
      if($('#extendModal').hasClass('show')){
        $('#spinnerLoading').show();
        $.post('php/wholesales.php', $('#extendForm').serialize(), function(data){
          var obj = JSON.parse(data); 
          if(obj.status === 'success'){
            $('#extendModal').modal('hide');
            toastr["success"](obj.message, "Success:");
            $('#weightTable').DataTable().ajax.reload();
          }
          else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
          }
          else{
            toastr["error"]("Something wrong when edit", "Failed:");
          }

          $('#spinnerLoading').hide();
        });
      }else if($('#cancelModal').hasClass('show')){
        $('#spinnerLoading').show();
        $.post('php/deleteWholesale.php', $('#cancelForm').serialize(), function(data){
          var obj = JSON.parse(data);

          if(obj.status === 'success'){
            $('#cancelModal').modal('hide');
            toastr["success"](obj.message, "Success:");
            $('#weightTable').DataTable().ajax.reload();
            
          }
          else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
          }
          else{
            toastr["error"]("Something wrong when delete", "Failed:");
          }
          $('#spinnerLoading').hide();
        });
      }
    }
  });

  $('#refreshBtn').on('click', function(){
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var productI = $('#productFilter').val() ? $('#productFilter').val() : '';
    var supplierNoI = $('#supplierNoFilter').val() ? $('#supplierNoFilter').val() : '';

    //Destroy the old Datatable
    $("#weightTable").DataTable().clear().destroy();

    //Create new Datatable
    table = $("#weightTable").DataTable({
      "responsive": true,
      "autoWidth": false,
      'processing': true,
      'serverSide': true,
      'serverMethod': 'post',
      'searching': true,
      'order': [[ 1, 'asc' ]],
      'columnDefs': [ { orderable: false, targets: [0] }],
      'ajax': {
        'url':'php/filterWholesale.php',
        'data': {
          fromDate: fromDateI,
          toDate: toDateI,
          product: productI,
          supplier: supplierNoI
        } 
      },
      'columns': [
        { data: 'serial_no' },
        { data: 'created_datetime' },
        { data: 'supplier_name' },
        { data: 'product_name' },
        { data: 'gross' },
        { data: 'unit' },
        { data: 'count' },
        { 
          data: 'id',
          render: function ( data, type, row ) {
            return '<div class="row"><div class="col-3"><button type="button" id="edit'+data+'" onclick="edit('+data+')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button></div><div class="col-3"><button type="button" id="print'+data+'" onclick="print('+data+')" class="btn btn-warning btn-sm"><i class="fas fa-print"></i></button></div><div class="col-3"><button type="button" id="deactivate'+data+'" onclick="deactivate('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></div></div>';
          }
        }
      ],
      "footerCallback": function(row, data, start, end, display) {
        var api = this.api();

        // Calculate total for 'total_cages' column
        var totalCages = api
            .column(4, { page: 'current' })
            .data()
            .reduce(function(a, b) {
                return a + parseFloat(b);
            }, 0);

        // Calculate total for 'total_birds' column
        var totalBirds = api
            .column(5, { page: 'current' })
            .data()
            .reduce(function(a, b) {
                return a + parseFloat(b);
            }, 0);

        var totalConts = api
          .column(6, { page: 'current' })
          .data()
          .reduce(function(a, b) {
              return a + parseFloat(b);
          }, 0);

        // Update footer with the total
        $(api.column(4).footer()).html(totalCages.toFixed(3));
        $(api.column(5).footer()).html(totalBirds.toFixed(3));
        $(api.column(5).footer()).html(totalConts);
      }
    });
  });
  
  <?php 
    if($role == "ADMIN"){
      echo "$('#manual').on('click', function(){
        if($(this).is(':checked')){
          $(this).val(1);
            $('#currentWeight').removeAttr('readonly');
        }
        else{
          $(this).val(0);
            $('#currentWeight').attr('readonly', 'readonly');
        }
      })";
    }
  ?>

  $('#extendModal').find('#inCButton').on('click', function(){
    var text = $('#indicatorWeight').text();
    var weight = parseFloat(text.replace("kg","").replace("g","").replace("oz","").replace("lbs",""))
    $('#currentWeight').val(weight.toFixed(3));
    $('#currentWeight').trigger("change");
  });

  $('#extendModal').find('#currentWeight').on('change', function(){
    var weight = $('#product :selected').data('unit');
    var uom = $('#product :selected').data('uom') ? $('#product :selected').data('uom') : '';
    var uomDesc = $("#uomhidden option[value='"+uom+"']").text();
    var cweight = $('#currentWeight').val();
    $('#indicatorWeight').text(cweight.toString() + ' ' + uomDesc);

    if(weight && cweight){
      var count = parseFloat(cweight) / parseFloat(weight);
      count = parseFloat(count).toFixed(0);
      $('#actualCount').val(count);
      $('#countingWeight').text(count);
    }
  });

  $('#extendModal').find('#product').on('change', function () {
    var desc = $('#product :selected').data('description');
    var weight = $('#product :selected').data('unit');
    var batch = $('#product :selected').data('batch')? $('#product :selected').data('batch') : '';
    var uom = $('#product :selected').data('uom') ? $('#product :selected').data('uom') : '';
    var cweight = $('#currentWeight').val();

    var uomDesc = $("#uomhidden option[value='"+uom+"']").text();

    $('#unitWeight').val(weight);
    $('#unitCountWeight').text(weight.toString() + ' ' + uomDesc);

    $('#productDesc').val(desc);
    $('#uom').val(uom).trigger('change');
    $('#batchNumber').val(batch);

    if(weight && cweight){
      var count = parseFloat(cweight) / parseFloat(weight);
      count = parseFloat(count).toFixed(0);
      $('#actualCount').val(count);
      $('#countingWeight').text(count);
    }
  });

  $('#extendModal').find('#uom').on('change', function () {
    
  });

  $('#statusFilter').on('change', function () {
    var status = $(this).val();
    if(status == "DISPATCH" || status == 'SALE-BAL'){
      $('#customerStatusDiv').show();
      $('#supplierStatusDiv').hide();
    }
    else{
      $('#customerStatusDiv').hide();
      $('#supplierStatusDiv').show();
    }
  });

  $('#extendModal').find('#status').on('change', function () {
    var status = $(this).val();
    if(status == "DISPATCH" || status == 'SALE-BAL'){
      $('#extendModal').find('#customerDiv').show();
      $('#extendModal').find('#supplierDiv').hide();
      $('#extendModal').find('#securityBillDiv').hide();
    }
    else{
      $('#extendModal').find('#customerDiv').hide();
      $('#extendModal').find('#supplierDiv').show();
      $('#extendModal').find('#securityBillDiv').show();
    }
  });

  $('#extendModal').find('#customer').on('change', function () {
    var customer = $(this).val();
    if(customer == "OTHERS"){
      $('#extendModal').find('#customerOtherDiv').show();
    }
    else{
      $('#extendModal').find('#customerOtherDiv').hide();
    }
  });

  $('#extendModal').find('#supplier').on('change', function () {
    var supplier = $(this).val();
    if(supplier == "OTHERS"){
      $('#extendModal').find('#supplierOtherDiv').show();
    }
    else{
      $('#extendModal').find('#supplierOtherDiv').hide();
    }
  });

  $('#extendModal').find('#vehicle').on('change', function () {
    var vehicleNo = $(this).val();
    if(vehicleNo == "UNKOWN"){
      $('#extendModal').find('#vehicleNoOtherDiv').show();
    }
    else{
      $('#extendModal').find('#vehicleNoOtherDiv').hide();
    }
  });

  $('#vehicleNoFilter').on('change', function () {
    var vehicleNo = $(this).val();
    if(vehicleNo == "UNKOWN"){
      $('#otherVehicleFilterDiv').show();
    }
    else{
      $('#otherVehicleFilterDiv').hide();
    }
  });

  $('#addWeightBtn').on('click', function() {
    var idx = weightCount++;
    var rowNum = $('#weightDetailsTable tr').length + 1;
    var now = new Date();
    var currentTime = now.getHours().toString().padStart(2, '0') + ':' + 
                      now.getMinutes().toString().padStart(2, '0') + ':' + 
                      now.getSeconds().toString().padStart(2, '0');
    var row = `
      <tr class="details">
        <td>${rowNum}</td>
        <td style="display:none">
          <input type="hidden" id="product${idx}" name="weightDetails[${idx}][product]" value="">
          <input type="hidden" id="product_desc${idx}" name="weightDetails[${idx}][product_desc]" value="">
          <input type="hidden" id="pretare${idx}" name="weightDetails[${idx}][pretare]" value="0.00">
          <input type="hidden" id="unit${idx}" name="weightDetails[${idx}][unit]" value="Kg">
          <input type="hidden" id="package${idx}" name="weightDetails[${idx}][package]" value="">
          <input type="hidden" id="fixedfloat${idx}" name="weightDetails[${idx}][fixedfloat]" value="">
          <input type="hidden" id="isedit${idx}" name="weightDetails[${idx}][isedit]" value="N">
          <input type="hidden" id="reject${idx}" name="weightDetails[${idx}][reject]" value="0.00">
          <input type="hidden" id="isRejected${idx}" name="weightDetails[${idx}][isRejected]" value="NO">
        </td>
        <td>
          <select class="form-control select2" id="product_name${idx}" name="weightDetails[${idx}][product_name]">
            <option value="" selected disabled>Select Product</option>
            <?php while($rowProduct=mysqli_fetch_assoc($products2)){ ?>
              <option value="<?=$rowProduct['product_name'] ?>" data-id="<?=$rowProduct['id'] ?>"><?=$rowProduct['product_name'] ?></option>
            <?php } ?>
          </select>
        </td>
        <td>
          <select class="form-control select2" id="grade${idx}" name="weightDetails[${idx}][grade]">
            <?php while($rowGrade=mysqli_fetch_assoc($grades3)){ ?>
              <option value="<?=$rowGrade['units'] ?>" data-product="<?=$rowGrade['product_name'] ?>"><?=$rowGrade['units'] ?></option>
            <?php } ?>
          </select>
        </td>
        <td><input type="number" class="form-control" id="gross${idx}" name="weightDetails[${idx}][gross]" step="0.01" value="0.00"></td>
        <td><input type="number" class="form-control" id="tare${idx}" name="weightDetails[${idx}][tare]" step="0.01" value="0.00"></td>
        <td><input type="number" class="form-control" id="net${idx}" name="weightDetails[${idx}][net]" step="0.01" value="0.00" readonly></td>
        <td><input type="number" class="form-control" id="price${idx}" name="weightDetails[${idx}][price]" step="0.01" value="0.00"></td>
        <td><input type="number" class="form-control" id="total${idx}" name="weightDetails[${idx}][total]" step="0.01" value="0.00"></td>
        <td><input type="time" class="form-control" id="time${idx}" name="weightDetails[${idx}][time]" value="${currentTime}"/></td>
        <td>
          <button type="button" class="btn btn-warning btn-sm" onclick="rejectRow(this)"><i class="fas fa-times"></i></button>
          <button type="button" class="btn btn-danger btn-sm" onclick="removeWeightDetail(this)"><i class="fas fa-trash"></i></button>
        </td>
      </tr>
    `;
    $('#weightDetailsTable').append(row);
    $('.select2').select2({
      allowClear: true,
      placeholder: "Please Select",
      dropdownParent: $('#extendModal .modal-body'),
      width: '100%'
    });
  });

  $('#weightDetailsTable').on('change', 'select[name*="[product_name]"]', function() {
    var row = $(this).closest('tr');
    var productName = $(this).val();
    var productId = $(this).find('option:selected').data('id');
    row.find('input[name*="[product]"]').val(productId);
    row.find('input[name*="[product_desc]"]').val(productName);
    
    // Filter grades by selected product
    var gradeSelect = row.find('select[name*="[grade]"]');
    var currentGrade = gradeSelect.val();
    
    // Destroy Select2 before modifying options
    gradeSelect.select2('destroy');
    
    // Store all original options if not already stored
    if (!gradeSelect.data('original-options')) {
      gradeSelect.data('original-options', gradeSelect.html());
    }
    
    // Reset to original options
    gradeSelect.html(gradeSelect.data('original-options'));
    
    if(productName) {
      // Remove options that don't match the selected product
      gradeSelect.find('option').each(function() {
        var gradeProduct = $(this).data('product');
        if(gradeProduct && gradeProduct != productName) {
          $(this).remove();
        }
      });
    }
    
    // Recreate Select2
    gradeSelect.select2({
      allowClear: true,
      placeholder: "Please Select",
      dropdownParent: $('#extendModal .modal-body'),
      width: '100%'
    });
    
    gradeSelect.val(currentGrade).trigger('change');
  });

  $("#weightDetailsTable").on('change', 'input[id^="gross"]', function(){
    // Retrieve the input's attributes
    var gross = parseFloat($(this).val());
    var tare = parseFloat($(this).closest('tr').find('input[id^="tare"]').val());
    var nettWeight = Math.abs(gross - tare);

    $(this).closest('tr').find('input[id^="net"]').val(nettWeight).trigger("change");
  });

  $("#weightDetailsTable").on('change', 'input[id^="tare"]', function(){
    // Retrieve the input's attributes
    var gross = parseFloat($(this).closest('tr').find('input[id^="gross"]').val());
    var tare = parseFloat($(this).val());
    var nettWeight = Math.abs(gross - tare);

    $(this).closest('tr').find('input[id^="net"]').val(nettWeight).trigger("change");
  });

  $("#weightDetailsTable").on('change', 'input[id^="net"]', function(){
    var totalGross = 0;
    var totalTare = 0;
    var totalNet = 0;
    var totalPrice = 0;

    $('#weightDetailsTable tr').each(function() {
      totalGross += parseFloat($(this).find('input[name*="[gross]"]').val() || 0);
      totalTare += parseFloat($(this).find('input[name*="[tare]"]').val() || 0);
      totalNet += parseFloat($(this).find('input[name*="[net]"]').val() || 0);
    });

    $('#totalWeightGross').text(totalGross.toFixed(2));
    $('#totalWeightTare').text(totalTare.toFixed(2));
    $('#totalWeightNet').text(totalNet.toFixed(2));
  });

  $("#weightDetailsTable").on('change', 'input[id^="price"]', function(){
    var row = $(this).closest('tr');
    var price = parseFloat($(this).val());
    var net = parseFloat(row.find('input[name*="[net]"]').val());
    var total = price * net;

    row.find('input[name*="[total]"]').val(total.toFixed(2)).trigger("change");
  });

  $('#weightDetailsTable').on('change', 'input[name*="[total]"]', function() {
    var totalPrice = 0;
    $('#weightDetailsTable tr').each(function() {
      totalPrice += parseFloat($(this).find('input[name*="[total]"]').val() || 0);
    });
    $('#totalWeightPrice').text('RM ' + totalPrice.toFixed(2));
  });

  $('#rejectDetailsTable').on('change', 'select[name*="[product_name]"]', function() {
    var row = $(this).closest('tr');
    var productName = $(this).val();
    var productId = $(this).find('option:selected').data('id');
    row.find('input[name*="[product]"]').val(productId);
    row.find('input[name*="[product_desc]"]').val(productName);
    
    // Filter grades by selected product
    var gradeSelect = row.find('select[name*="[grade]"]');
    var currentGrade = gradeSelect.val();
    
    // Destroy Select2 before modifying options
    gradeSelect.select2('destroy');
    
    // Store all original options if not already stored
    if (!gradeSelect.data('original-options')) {
      gradeSelect.data('original-options', gradeSelect.html());
    }
    
    // Reset to original options
    gradeSelect.html(gradeSelect.data('original-options'));
    
    if(productName) {
      // Remove options that don't match the selected product
      gradeSelect.find('option').each(function() {
        var gradeProduct = $(this).data('product');
        if(gradeProduct && gradeProduct !== productName) {
          $(this).remove();
        }
      });
    }
    
    // Recreate Select2
    gradeSelect.select2({
      allowClear: true,
      placeholder: "Please Select",
      dropdownParent: $('#extendModal .modal-body'),
      width: '100%'
    });
    
    gradeSelect.val(currentGrade).trigger('change');
  });

  $("#rejectDetailsTable").on('change', 'input[id^="gross"]', function(){
    var gross = parseFloat($(this).val());
    var tare = parseFloat($(this).closest('tr').find('input[id^="tare"]').val());
    var nettWeight = Math.abs(gross - tare);
    $(this).closest('tr').find('input[id^="net"]').val(nettWeight).trigger("change");
  });

  $("#rejectDetailsTable").on('change', 'input[id^="tare"]', function(){
    var gross = parseFloat($(this).closest('tr').find('input[id^="gross"]').val());
    var tare = parseFloat($(this).val());
    var nettWeight = Math.abs(gross - tare);
    $(this).closest('tr').find('input[id^="net"]').val(nettWeight).trigger("change");
  });

  $("#rejectDetailsTable").on('change', 'input[id^="net"]', function(){
    var totalGross = 0, totalTare = 0, totalNet = 0;
    $('#rejectDetailsTable tr').each(function() {
      totalGross += parseFloat($(this).find('input[name*="[gross]"]').val() || 0);
      totalTare += parseFloat($(this).find('input[name*="[tare]"]').val() || 0);
      totalNet += parseFloat($(this).find('input[name*="[net]"]').val() || 0);
    });
    $('#totalRejectGross').text(totalGross.toFixed(2));
    $('#totalRejectTare').text(totalTare.toFixed(2));
    $('#totalRejectNet').text(totalNet.toFixed(2));
  });

  $("#rejectDetailsTable").on('change', 'input[id^="price"]', function(){
    var row = $(this).closest('tr');
    var price = parseFloat($(this).val());
    var net = parseFloat(row.find('input[name*="[net]"]').val());
    var total = price * net;
    row.find('input[name*="[total]"]').val(total.toFixed(2)).trigger("change");
  });

  $('#rejectDetailsTable').on('change', 'input[name*="[total]"]', function() {
    var totalPrice = 0;
    $('#rejectDetailsTable tr').each(function() {
      totalPrice += parseFloat($(this).find('input[name*="[total]"]').val() || 0);
    });
    $('#totalRejectPrice').text('RM ' + totalPrice.toFixed(2));
  });
});

function updatePrices(isFromCurrency, rat){
  var totalPrice;
  var unitPrice = $('#unitPrice').val();
  var totalWeight = $('#totalWeight').val();

  if(isFromCurrency == 'Y'){
    unitPrice = (unitPrice / rate) * parseFloat(rat);
    $('#extendModal').find('#unitPrice').val(unitPrice.toFixed(2));
    rate = parseFloat(rat).toFixed(2);
  }
  else{
    unitPrice = unitPrice * parseFloat(rat);
    $('#extendModal').find('#unitPrice').val(unitPrice.toFixed(2));
    rate = parseFloat(rat).toFixed(2);
  }
  

  if(unitPrice != '' &&  moq != '' && totalWeight != ''){
    totalPrice = unitPrice * totalWeight;
    $('#totalPrice').val(totalPrice.toFixed(2));
  }
  else(
    $('#totalPrice').val((0).toFixed(2))
  )
}

function updateWeights(){
  var tareWeight =  0;
  var currentWeight =  0;
  var reduceWeight = 0;
  var moq = $('#moq').val();
  var totalWeight = 0;
  var actualWeight = 0;

  if($('#currentWeight').val()){
    currentWeight =  $('#currentWeight').val();
  }

  if($('#tareWeight').val()){
    tareWeight =  $('#tareWeight').val();
  }

  if($('#reduceWeight').val()){
    reduceWeight =  $('#reduceWeight').val();
  }

  if(tareWeight == 0){
    actualWeight = currentWeight - reduceWeight;
    actualWeight = Math.abs(actualWeight);
    $('#actualWeight').val(actualWeight.toFixed(2));
  }
  else{
    actualWeight = tareWeight - currentWeight - reduceWeight;
    actualWeight = Math.abs(actualWeight);
    $('#actualWeight').val(actualWeight.toFixed(2));
  }

  if(actualWeight != '' &&  moq != ''){
    totalWeight = actualWeight * moq;
    $('#totalWeight').val(totalWeight.toFixed(2));
  }
  else{
    $('#totalWeight').val((0).toFixed(2))
  };
}

function format (row) {
  var returnString = `
  <!-- Wholesale Information -->
  <div class="row">
    <p><span><strong style="font-size:120%; text-decoration: underline;">Wholesale Order Information</strong></span>
  </div>
  <div class="row">
    <div class="col-6">
      <p><strong>Serial No:</strong> ${row.serial_no}</p>
      <p><strong>Parent:</strong> ${row.parent}</p>
      <p><strong>Customer/Supplier:</strong> ${row.customer_supplier}</p>
      <p><strong>Security Bill No:</strong> ${row.security_bills || ''}</p>
      <p><strong>PO No:</strong> ${row.po_no}</p>
      <p><strong>Vehicle:</strong> ${row.vehicle_no}</p>
      <p><strong>Driver:</strong> ${row.driver}</p>
    </div>
    <div class="col-6">
      <p><strong>Total Item:</strong> ${row.total_item}</p>
      <p><strong>Total Weight:</strong> ${row.total_weight ? parseFloat(row.total_weight).toFixed(2) : '0.00'}</p>
      <p><strong>Total Reject:</strong> ${row.total_reject ? parseFloat(row.total_reject).toFixed(2) : '0.00'}</p>
      <p><strong>Total Price:</strong> RM ${parseFloat(row.total_price).toFixed(2)}</p>
      <p><strong>Weighted By:</strong> ${row.weighted_by}</p>
      <p><strong>Checked By:</strong> ${row.checked_by || ''}</p>
    </div>
  </div>
  <div class="row">
    <div class="col-12">
      <p><strong>Remarks:</strong> ${row.remark || ''}</p>
    </div>
  </div>
  <hr>
  <h3>Weighing Details</h3>
  <div class="row mb-2">
    <div class="col-md-3">
      <select class="form-control" id="productFilter_${row.id}" onchange="filterWeightTable('${row.id}')">
        <option value="">All Products</option>
      </select>
    </div>
    <div class="col-md-3">
      <select class="form-control" id="gradeFilter_${row.id}" onchange="filterWeightTable('${row.id}')">
        <option value="">All Grades</option>
      </select>
    </div>
  </div>
  <div class="row">
    <table class="table table-bordered nowrap table-striped align-middle" id="weightTable_${row.id}" style="width:100%">
      <thead>
          <tr>
            <th>Product</th>
            <th>Grade</th>
            <th>Gross</th>
            <th>Tare</th>
            <th>Net</th>
            <th>Price</th>
            <th>Total</th>
            <th>Time</th>`;
          returnString += `
          </tr>
      </thead>
      <tbody>`;

      var totalWeightGross = 0;
      var totalWeightTare = 0;
      var totalWeightNet = 0;
      var totalWeightPrice = 0;
      for (var i = 0; i < row.weightDetails.length; i++) {
        var detail = row.weightDetails[i]; 
        
        returnString += `
            <tr>
              <td>${detail.product_name}</td>
              <td>${detail.grade}</td>
              <td>${parseFloat(detail.gross).toFixed(2)} ${detail.unit}</td>
              <td>${parseFloat(detail.tare).toFixed(2)} ${detail.unit}</td>
              <td>${parseFloat(detail.net).toFixed(2)} ${detail.unit}</td>
              <td>RM ${parseFloat(detail.price).toFixed(2)}</td>
              <td>RM ${parseFloat(detail.total).toFixed(2)}</td>
              <td>${detail.time}</td>`;
            returnString += `
            </tr>`;

        totalWeightGross += parseFloat(detail.gross);
        totalWeightTare += parseFloat(detail.tare);
        totalWeightNet += parseFloat(detail.net);
        totalWeightPrice += parseFloat(detail.total);
      }

      returnString += `
      </tbody>
      <tfoot>
        <tr>
          <th colspan="2">Total</th>
          <th>${totalWeightGross.toFixed(2)}</th>
          <th>${totalWeightTare.toFixed(2)}</th>
          <th>${totalWeightNet.toFixed(2)}</th>
          <th></th>
          <th>RM ${totalWeightPrice.toFixed(2)}</th>
          <th></th>
        </tr>
    </table>
  </div>

  <hr>
  <h3>Reject Details</h3>
  <div class="row">
    <table class="table table-bordered nowrap table-striped align-middle" style="width:100%">
      <thead>
          <tr>
            <th>Product</th>
            <th>Grade</th>
            <th>Gross</th>
            <th>Tare</th>
            <th>Net</th>
            <th>Price</th>
            <th>Total</th>
            <th>Time</th>`;
          returnString += `
          </tr>
      </thead>
      <tbody>`;

      var totalRejectGross = 0;
      var totalRejectTare = 0;
      var totalRejectNet = 0;
      var totalRejectPrice = 0;
      for (var i = 0; i < row.rejectDetails.length; i++) {
        var detail = row.rejectDetails[i]; 
        
        returnString += `
            <tr>
              <td>${detail.product_name}</td>
              <td>${detail.grade}</td>
              <td>${parseFloat(detail.gross).toFixed(2)} ${detail.unit}</td>
              <td>${parseFloat(detail.tare).toFixed(2)} ${detail.unit}</td>
              <td>${parseFloat(detail.net).toFixed(2)} ${detail.unit}</td>
              <td>RM ${parseFloat(detail.price).toFixed(2)}</td>
              <td>RM ${parseFloat(detail.total).toFixed(2)}</td>
              <td>${detail.time}</td>`;
            returnString += `
            </tr>`;

        totalRejectGross += parseFloat(detail.gross);
        totalRejectTare += parseFloat(detail.tare);
        totalRejectNet += parseFloat(detail.net);
        totalRejectPrice += parseFloat(detail.total);
      }

      returnString += `
      </tbody>
      <tfoot>
        <tr>
          <th colspan="2">Total</th>
          <th>${totalRejectGross.toFixed(2)}</th>
          <th>${totalRejectTare.toFixed(2)}</th>
          <th>${totalRejectNet.toFixed(2)}</th>
          <th></th>
          <th>RM ${totalRejectPrice.toFixed(2)}</th>
          <th></th>
        </tr>
    </table>
  </div>
  `;
  
  return returnString;
  // return '<div class="row"><div class="col-md-3"><p>Customer Name: '+row.customer_name+
  // '</p></div><div class="col-md-3"><p>Unit Weight: '+row.unit+
  // '</p></div><div class="col-md-3"><p>Weight Status: '+row.status+
  // '</p></div><div class="col-md-3"><p>MOQ: '+row.moq+
  // '</p></div></div><div class="row"><div class="col-md-3"><p>Address: '+row.customer_address+
  // '</p></div><div class="col-md-3"><p>Batch No: '+row.batchNo+
  // '</p></div><div class="col-md-3"><p>Weight By: '+row.userName+
  // '</p></div><div class="col-md-3"><p>Package: '+row.packages+
  // '</p></div></div><div class="row"><div class="col-md-3">'+
  // '</div><div class="col-md-3"><p>Lot No: '+row.lots_no+
  // '</p></div><div class="col-md-3"><p>Invoice No: '+row.invoiceNo+
  // '</p></div><div class="col-md-3 money"><p>Unit Price: '+row.unitPrice+
  // '</p></div></div><div class="row"><div class="col-md-3">'+
  // '</div><div class="col-md-3"><p>Order Weight: '+row.supplyWeight+
  // '</p></div><div class="col-md-3"><p>Delivery No: '+row.deliveryNo+
  // '</p></div><div class="col-md-3 money"><p>Total Weight: '+row.totalPrice+
  // '</p></div></div><div class="row"><div class="col-md-3"><p>Contact No: '+row.customer_phone+
  // '</p></div><div class="col-md-3"><p>Variance Weight: '+row.varianceWeight+
  // '</p></div><div class="col-md-3"><p>Purchase No: '+row.purchaseNo+
  // '</p></div><div class="col-md-3"><div class="row"><div class="col-3"><button type="button" class="btn btn-warning btn-sm" onclick="edit('+row.id+
  // ')"><i class="fas fa-pen"></i></button></div><div class="col-3"><button type="button" class="btn btn-danger btn-sm" onclick="deactivate('+row.id+
  // ')"><i class="fas fa-trash"></i></button></div><div class="col-3"><button type="button" class="btn btn-info btn-sm" onclick="print('+row.id+
  // ')"><i class="fas fa-print"></i></button></div><div class="col-3"><button type="button" class="btn btn-success btn-sm" onclick="portrait('+row.id+
  // ')"><i class="fas fa-receipt"></i></button></div></div></div></div>'+
  // '</div><div class="row"><div class="col-md-3"><p>Remark: '+row.remark+
  // '</p></div><div class="col-md-3"><p>% Variance: '+row.variancePerc+
  // '</p></div><div class="col-md-3"><p>Transporter: '+row.transporter_name+
  // '</p></div></div>';
  // ;
}

function formatNormal (row) {
  return '<div class="row"><div class="col-md-3"><p>Customer Name: '+row.customer_name+
  '</p></div><div class="col-md-3"><p>Unit Weight: '+row.unit+
  '</p></div><div class="col-md-3"><p>Weight Status: '+row.status+
  '</p></div><div class="col-md-3"><p>MOQ: '+row.moq+
  '</p></div></div><div class="row"><div class="col-md-3"><p>Address: '+row.customer_address+
  '</p></div><div class="col-md-3"><p>Batch No: '+row.batchNo+
  '</p></div><div class="col-md-3"><p>Weight By: '+row.userName+
  '</p></div><div class="col-md-3"><p>Package: '+row.packages+
  '</p></div></div><div class="row"><div class="col-md-3">'+
  '</div><div class="col-md-3"><p>Lot No: '+row.lots_no+
  '</p></div><div class="col-md-3"><p>Invoice No: '+row.invoiceNo+
  '</p></div><div class="col-md-3"><p>Unit Price: '+row.unitPrice+
  '</p></div></div><div class="row"><div class="col-md-3">'+
  '</div><div class="col-md-3"><p>Order Weight: '+row.supplyWeight+
  '</p></div><div class="col-md-3"><p>Delivery No: '+row.deliveryNo+
  '</p></div><div class="col-md-3"><p>Total Weight: '+row.totalPrice+
  '</p></div></div><div class="row"><div class="col-md-3"><p>Contact No: '+row.customer_phone+
  '</p></div><div class="col-md-3"><p>Variance Weight: '+row.varianceWeight+
  '</p></div><div class="col-md-3"><p>Purchase No: '+row.purchaseNo+
  '</p></div><div class="col-md-3"><div class="row"><div class="col-3"><button type="button" class="btn btn-warning btn-sm" onclick="edit('+row.id+
  ')"><i class="fas fa-pen"></i></button></div><div class="col-3"><button type="button" class="btn btn-info btn-sm" onclick="print('+row.id+
  ')"><i class="fas fa-print"></i></button></div><div class="col-3"><button type="button" class="btn btn-success btn-sm" onclick="portrait('+row.id+
  ')"><i class="fas fa-receipt"></i></button></div></div></div></div>'+
  '</div><div class="row"><div class="col-md-3"><p>Remark: '+row.remark+
  '</p></div><div class="col-md-3"><p>% Variance: '+row.variancePerc+
  '</p></div><div class="col-md-3"><p>Transporter: '+row.transporter_name+
  '</p></div></div>';
}

function newEntry(){
  $('#extendModal').find('#id').val("");
  $('#extendModal').find('#serialNumber').val("");
  $('#extendModal').find('#poNumber').val("");
  $('#extendModal').find('#status').val("DISPATCH");
  $('#extendModal').find('#customer').val("").trigger('change');
  $('#extendModal').find('#supplier').val("").trigger('change');
  $('#extendModal').find('#vehicleNo').val("");
  $('#extendModal').find('#driver').val("");
  $('#extendModal').find('#remark').val("");
  $('#weightDetailsTable').empty();
  $('#extendModal').modal('show');
  
  $('#extendForm').validate({
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

function numberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function edit(id) {
  $('#spinnerLoading').show();
  $.post('php/getWholesale.php', {userID: id}, function(data){
    var obj = JSON.parse(data);
    
    if(obj.status === 'success'){
      $('#extendModal').find('#id').val(obj.message.id);
      $('#extendModal').find('#status').val(obj.message.status).trigger('change');
      $('#extendModal').find('#doPoNo').val(obj.message.po_no).trigger('change');
      $('#extendModal').find('#securityBillNo').val(obj.message.security_bills).trigger('change');
      $('#extendModal').find('#customer').val(obj.message.customer).trigger('change');
      $('#extendModal').find('#supplier').val(obj.message.supplier).trigger('change');

      if (obj.message.other_vehicle){
        $('#extendModal').find('#vehicle').val('UNKOWN').trigger('change');
        $('#extendModal').find('#otherVehicleNo').val(obj.message.vehicle_no);
      } else {
        $('#extendModal').find('#vehicle').val(obj.message.vehicle_no).trigger('change');
        $('#extendModal').find('#otherVehicleNo').val('');
      }
      $('#extendModal').find('#driver').val(obj.message.driver).trigger('change');
      $('#extendModal').find('#remark').val(obj.message.remark);
      
      // Populate weight details table
      var tbody = $('#weightDetailsTable');
      tbody.empty();
      
      if(obj.message.weightDetails && obj.message.weightDetails.length > 0) {
        var totalGross = 0;
        var totalTare = 0;
        var totalNet = 0;
        var totalPrice = 0;

        for(var i = 0; i < obj.message.weightDetails.length; i++) {
          var detail = obj.message.weightDetails[i];
          var idx = weightCount++;
          var row = `
            <tr class="details">
              <td>${i + 1}</td>
              <td style="display:none">
                <input type="hidden" id="product${idx}" name="weightDetails[${idx}][product]" value="${detail.product}">
                <input type="hidden" id="product_desc${idx}" name="weightDetails[${idx}][product_desc]" value="${detail.product_desc}">
                <input type="hidden" id="pretare${idx}" name="weightDetails[${idx}][pretare]" value="${detail.pretare}">
                <input type="hidden" id="unit${idx}" name="weightDetails[${idx}][unit]" value="${detail.unit}">
                <input type="hidden" id="package${idx}" name="weightDetails[${idx}][package]" value="${detail.package}">
                <input type="hidden" id="fixedfloat${idx}" name="weightDetails[${idx}][fixedfloat]" value="${detail.fixedfloat}">
                <input type="hidden" id="isedit${idx}" name="weightDetails[${idx}][isedit]" value="${detail.isedit}">
                <input type="hidden" id="reject${idx}" name="weightDetails[${idx}][reject]" value="${detail.reject}">
                <input type="hidden" id="isRejected${idx}" name="weightDetails[${idx}][isRejected]" value="${detail.isRejected}">
              </td>
              <td><input type="hidden" id="product_name${idx}" name="weightDetails[${idx}][product_name]" value="${detail.product_name}">${detail.product_name}</td>
              <td>
                <select class="form-control select2" id="grade${idx}" name="weightDetails[${idx}][grade]">
                  <?php while($rowGrade=mysqli_fetch_assoc($grades)){ ?>
                    <option value="<?=$rowGrade['units'] ?>" data-product="<?=$rowGrade['product_name'] ?>"><?=$rowGrade['units'] ?></option>
                  <?php } ?>
                </select>
              </td>
              <td><input type="hidden" id="gross${idx}" name="weightDetails[${idx}][gross]" value="${detail.gross}">${parseFloat(detail.gross).toFixed(2)} ${detail.unit}</td>
              <td><input type="hidden" id="tare${idx}" name="weightDetails[${idx}][tare]" value="${detail.tare}">${parseFloat(detail.tare).toFixed(2)} ${detail.unit}</td>
              <td><input type="hidden" id="net${idx}" name="weightDetails[${idx}][net]" value="${detail.net}">${parseFloat(detail.net).toFixed(2)} ${detail.unit}</td>
              <td><input type="hidden" id="price${idx}" name="weightDetails[${idx}][price]" value="${detail.price}">RM ${parseFloat(detail.price).toFixed(2)}</td>
              <td><input type="hidden" id="total${idx}" name="weightDetails[${idx}][total]" value="${detail.total}">RM ${parseFloat(detail.total).toFixed(2)}</td>
              <td><input type="hidden" id="time${idx}" name="weightDetails[${idx}][time]" value="${detail.time}">${detail.time}</td>
              <td>
                <button type="button" class="btn btn-warning btn-sm" onclick="rejectRow(this)"><i class="fas fa-times"></i></button>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeWeightDetail(this)"><i class="fas fa-trash"></i></button>
              </td>
            </tr>
          `;
          tbody.append(row);
          
          // Filter grades by product
          var gradeSelect = tbody.find(`select[name="weightDetails[${idx}][grade]"]`);
          var productName = detail.product_name;
          gradeSelect.find('option').each(function() {
            if($(this).data('product') && $(this).data('product') !== productName) {
              $(this).remove();
            }
          });
          
          // Set the selected value for the grade dropdown
          gradeSelect.val(detail.grade);

          totalGross += parseFloat(detail.gross);
          totalTare += parseFloat(detail.tare);
          totalNet += parseFloat(detail.net);
          totalPrice += parseFloat(detail.total);
        }

        $('#weightDetailsFooter').find('#totalWeightGross').text(totalGross.toFixed(2));
        $('#weightDetailsFooter').find('#totalWeightTare').text(totalTare.toFixed(2));
        $('#weightDetailsFooter').find('#totalWeightNet').text(totalNet.toFixed(2));
        $('#weightDetailsFooter').find('#totalWeightPrice').text('RM' + totalPrice .toFixed(2));
      }
      
      // Populate reject details table
      var tbody = $('#rejectDetailsTable');
      tbody.empty();
      
      if(obj.message.rejectDetails && obj.message.rejectDetails.length > 0) {
        var totalRejectGross = 0;
        var totalRejectTare = 0;
        var totalRejectNet = 0;
        var totalRejectPrice = 0;

        for(var i = 0; i < obj.message.rejectDetails.length; i++) {
          var detail = obj.message.rejectDetails[i];
          var idx = rejectCount++;
          var row = `
            <tr class="details">
              <td>${i + 1}</td>
              <td style="display:none">
                <input type="hidden" id="product${idx}" name="rejectDetails[${idx}][product]" value="${detail.product}">
                <input type="hidden" id="product_desc${idx}" name="rejectDetails[${idx}][product_desc]" value="${detail.product_desc}">
                <input type="hidden" id="pretare${idx}" name="rejectDetails[${idx}][pretare]" value="${detail.pretare}">
                <input type="hidden" id="unit${idx}" name="rejectDetails[${idx}][unit]" value="${detail.unit}">
                <input type="hidden" id="package${idx}" name="rejectDetails[${idx}][package]" value="${detail.package}">
                <input type="hidden" id="fixedfloat${idx}" name="rejectDetails[${idx}][fixedfloat]" value="${detail.fixedfloat}">
                <input type="hidden" id="isedit${idx}" name="rejectDetails[${idx}][isedit]" value="${detail.isedit}">
                <input type="hidden" id="reject${idx}" name="rejectDetails[${idx}][reject]" value="${detail.reject}">
                <input type="hidden" id="isRejected${idx}" name="rejectDetails[${idx}][isRejected]" value="${detail.isRejected}">
              </td>
              <td><input type="hidden" id="product_name${idx}" name="rejectDetails[${idx}][product_name]" value="${detail.product_name}">${detail.product_name}</td>
              <td>
                <select class="form-control select2" id="grade${idx}" name="rejectDetails[${idx}][grade]">
                  <?php while($rowGrade=mysqli_fetch_assoc($grades2)){ ?>
                    <option value="<?=$rowGrade['units'] ?>"><?=$rowGrade['units'] ?></option>
                  <?php } ?>
                </select>
              </td>
              <td><input type="hidden" id="gross${idx}" name="rejectDetails[${idx}][gross]" value="${detail.gross}">${parseFloat(detail.gross).toFixed(2)} ${detail.unit}</td>
              <td><input type="hidden" id="tare${idx}" name="rejectDetails[${idx}][tare]" value="${detail.tare}">${parseFloat(detail.tare).toFixed(2)} ${detail.unit}</td>
              <td><input type="hidden" id="net${idx}" name="rejectDetails[${idx}][net]" value="${detail.net}">${parseFloat(detail.net).toFixed(2)} ${detail.unit}</td>
              <td><input type="hidden" id="price${idx}" name="rejectDetails[${idx}][price]" value="${detail.price}">RM ${parseFloat(detail.price).toFixed(2)}</td>
              <td><input type="hidden" id="total${idx}" name="rejectDetails[${idx}][total]" value="${detail.total}">RM ${parseFloat(detail.total).toFixed(2)}</td>
              <td><input type="hidden" id="time${idx}" name="rejectDetails[${idx}][time]" value="${detail.time}">${detail.time}</td>
              <td>
                <button type="button" class="btn btn-success btn-sm" onclick="acceptRow(this)"><i class="fas fa-check"></i></button>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeRejectDetail(this)"><i class="fas fa-trash"></i></button>
              </td>
            </tr>
          `;
          tbody.append(row);
          
          // Set the selected value for the grade dropdown
          tbody.find(`select[name="rejectDetails[${idx}][grade]"]`).val(detail.grade);

          totalRejectGross += parseFloat(detail.gross);
          totalRejectTare += parseFloat(detail.tare);
          totalRejectNet += parseFloat(detail.net);
          totalRejectPrice += parseFloat(detail.total);
        }

        $('#rejectDetailsFooter').find('#totalRejectGross').text(totalRejectGross.toFixed(2));
        $('#rejectDetailsFooter').find('#totalRejectTare').text(totalRejectTare.toFixed(2));
        $('#rejectDetailsFooter').find('#totalRejectNet').text(totalRejectNet.toFixed(2));
        $('#rejectDetailsFooter').find('#totalRejectPrice').text('RM' + totalRejectPrice.toFixed(2));
      }

      $('.select2').each(function() {
        $(this).select2({
          allowClear: true,
          placeholder: "Please Select",
          // Conditionally set dropdownParent based on the element’s location
          dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-body') : undefined
        });
      });
      
      $('#extendModal').modal('show');

      $('#extendForm').validate({
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
      toastr["error"]("Something wrong when pull data", "Failed:");
    }
    $('#spinnerLoading').hide();
  });
}

function rejectRow(button) {
  var row = $(button).closest('tr');
  var rejectIndex = $('#rejectDetailsTable tr').length;
  
  row.find('input[type="hidden"], input[type="number"], input[type="time"], select').each(function() {
    var name = $(this).attr('name');
    var id = $(this).attr('id');
    if(name) {
      var newName = name.replace('weightDetails', 'rejectDetails').replace(/\[\d+\]/, '[' + rejectIndex + ']');
      $(this).attr('name', newName);
    }
    if(id) {
      $(this).attr('id', id.replace(/\d+$/, rejectIndex));
    }
  });
  
  row.find('button[onclick*="rejectRow"]').replaceWith('<button type="button" class="btn btn-success btn-sm" onclick="acceptRow(this)"><i class="fas fa-check"></i></button>');
  row.find('button[onclick*="removeWeightDetail"]').attr('onclick', 'removeRejectDetail(this)');
  
  $('#rejectDetailsTable').append(row);
  reindexWeightDetails();
  reindexRejectDetails();
  updateTotals();
  $('.select2').select2({
    allowClear: true,
    placeholder: "Please Select",
    dropdownParent: $('#extendModal .modal-body'),
    width: '100%'
  });
}

function acceptRow(button) {
  var row = $(button).closest('tr');
  var weightIndex = $('#weightDetailsTable tr').length;
  
  row.find('input[type="hidden"], input[type="number"], input[type="time"], select').each(function() {
    var name = $(this).attr('name');
    var id = $(this).attr('id');
    if(name) {
      var newName = name.replace('rejectDetails', 'weightDetails').replace(/\[\d+\]/, '[' + weightIndex + ']');
      $(this).attr('name', newName);
    }
    if(id) {
      $(this).attr('id', id.replace(/\d+$/, weightIndex));
    }
  });
  
  row.find('button[onclick*="acceptRow"]').replaceWith('<button type="button" class="btn btn-warning btn-sm" onclick="rejectRow(this)"><i class="fas fa-times"></i></button>');
  row.find('button[onclick*="removeRejectDetail"]').attr('onclick', 'removeWeightDetail(this)');
  
  $('#weightDetailsTable').append(row);
  reindexWeightDetails();
  reindexRejectDetails();
  updateTotals();
  $('.select2').select2({
    allowClear: true,
    placeholder: "Please Select",
    dropdownParent: $('#extendModal .modal-body'),
    width: '100%'
  });
}

function reindexWeightDetails() {
  $('#weightDetailsTable tr').each(function(index) {
    $(this).find('td:first').text(index + 1);
    $(this).find('input[type="hidden"], select').each(function() {
      var name = $(this).attr('name');
      if(name) {
        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
      }
    });
  });
}

function reindexRejectDetails() {
  $('#rejectDetailsTable tr').each(function(index) {
    $(this).find('td:first').text(index + 1);
    $(this).find('input[type="hidden"], select').each(function() {
      var name = $(this).attr('name');
      if(name) {
        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
      }
    });
  });
}

function removeWeightDetail(button) {
  $(button).closest('tr').remove();
  reindexWeightDetails();
  updateTotals();
}

function removeRejectDetail(button) {
  $(button).closest('tr').remove();
  reindexRejectDetails();
  updateTotals();
}

function updateTotals() {
  var totalGross = 0, totalTare = 0, totalNet = 0, totalPrice = 0;
  $('#weightDetailsTable tr').each(function() {
    totalGross += parseFloat($(this).find('input[name*="[gross]"]').val() || 0);
    totalTare += parseFloat($(this).find('input[name*="[tare]"]').val() || 0);
    totalNet += parseFloat($(this).find('input[name*="[net]"]').val() || 0);
    totalPrice += parseFloat($(this).find('input[name*="[total]"]').val() || 0);
  });
  $('#totalWeightGross').text(totalGross.toFixed(2));
  $('#totalWeightTare').text(totalTare.toFixed(2));
  $('#totalWeightNet').text(totalNet.toFixed(2));
  $('#totalWeightPrice').text('RM' + totalPrice.toFixed(2));
  
  var totalRejectGross = 0, totalRejectTare = 0, totalRejectNet = 0, totalRejectPrice = 0;
  $('#rejectDetailsTable tr').each(function() {
    totalRejectGross += parseFloat($(this).find('input[name*="[gross]"]').val() || 0);
    totalRejectTare += parseFloat($(this).find('input[name*="[tare]"]').val() || 0);
    totalRejectNet += parseFloat($(this).find('input[name*="[net]"]').val() || 0);
    totalRejectPrice += parseFloat($(this).find('input[name*="[total]"]').val() || 0);
  });
  $('#totalRejectGross').text(totalRejectGross.toFixed(2));
  $('#totalRejectTare').text(totalRejectTare.toFixed(2));
  $('#totalRejectNet').text(totalRejectNet.toFixed(2));
  $('#totalRejectPrice').text('RM' + totalRejectPrice.toFixed(2));
}

function deactivate(id) {
  if (confirm('Are you sure you want to delete this item?')) {
    $('#cancelModal').find('#id').val(id);
    $('#cancelModal').modal('show');

    $('#cancelForm').validate({
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
}

function print(id) {
  $.post('php/print.php', {userID: id}, function(data){
    var obj = JSON.parse(data);
    if(obj.status === 'success') {
      var printWindow = window.open('', '', 'height=' + screen.height + ',width=' + screen.width);
      printWindow.document.write(obj.message);
      printWindow.document.close();
      setTimeout(function(){
        printWindow.print();
        printWindow.close();
      }, 500);
    }
    else if(obj.status === 'failed'){
      alert(obj.message);
    }
    else{
      alert("Something wrong when activate");
    }
  });
}

function portrait(id) {
  $.post('php/printportrait.php', {userID: id, file: 'weight'}, function(data){
    var obj = JSON.parse(data);

    if(obj.status === 'success'){
      var printWindow = window.open('', '', 'height=400,width=800');
      printWindow.document.write(obj.message);
      printWindow.document.close();
      setTimeout(function(){
        printWindow.print();
        printWindow.close();
      }, 500);
    }
    else if(obj.status === 'failed'){
      toastr["error"](obj.message, "Failed:");
    }
    else{
      toastr["error"]("Something wrong when activate", "Failed:");
    }
  });
}

function filterWeightTable(rowId) {
  var productFilter = $('#productFilter_' + rowId).val();
  var gradeFilter = $('#gradeFilter_' + rowId).val();
  
  var totalGross = 0, totalTare = 0, totalNet = 0, totalPrice = 0;
  
  $('#weightTable_' + rowId + ' tbody tr').each(function() {
    var product = $(this).find('td:eq(0)').text();
    var grade = $(this).find('td:eq(1)').text();
    var showProduct = !productFilter || product == productFilter;
    var showGrade = !gradeFilter || grade == gradeFilter;
    var show = showProduct && showGrade;
    $(this).toggle(show);
    
    if(show) {
      var grossText = $(this).find('td:eq(2)').text().split(' ')[0];
      var tareText = $(this).find('td:eq(3)').text().split(' ')[0];
      var netText = $(this).find('td:eq(4)').text().split(' ')[0];
      var totalText = $(this).find('td:eq(6)').text().replace('RM', '').trim();
      
      totalGross += parseFloat(grossText) || 0;
      totalTare += parseFloat(tareText) || 0;
      totalNet += parseFloat(netText) || 0;
      totalPrice += parseFloat(totalText) || 0;
    }
  });
  
  $('#weightTable_' + rowId + ' tfoot tr th:eq(1)').text(totalGross.toFixed(2));
  $('#weightTable_' + rowId + ' tfoot tr th:eq(2)').text(totalTare.toFixed(2));
  $('#weightTable_' + rowId + ' tfoot tr th:eq(3)').text(totalNet.toFixed(2));
  $('#weightTable_' + rowId + ' tfoot tr th:eq(5)').text('RM ' + totalPrice.toFixed(2));
  
  if(productFilter) {
    var gradeSelect = $('#gradeFilter_' + rowId);
    var currentGrade = gradeSelect.val();
    gradeSelect.find('option:not(:first)').remove();
    
    var grades = [];
    $('#weightTable_' + rowId + ' tbody tr').each(function() {
      var product = $(this).find('td:eq(0)').text();
      if(product === productFilter) {
        var grade = $(this).find('td:eq(1)').text();
        if(grades.indexOf(grade) === -1) {
          grades.push(grade);
        }
      }
    });
    
    grades.sort();
    grades.forEach(function(grade) {
      gradeSelect.append('<option value="' + grade + '">' + grade + '</option>');
    });
    gradeSelect.val(currentGrade);
  } else {
    var gradeSelect = $('#gradeFilter_' + rowId);
    var currentGrade = gradeSelect.val();
    gradeSelect.find('option:not(:first)').remove();
    
    var grades = [];
    $('#weightTable_' + rowId + ' tbody tr').each(function() {
      var grade = $(this).find('td:eq(1)').text();
      if(grades.indexOf(grade) === -1) {
        grades.push(grade);
      }
    });
    
    grades.sort();
    grades.forEach(function(grade) {
      gradeSelect.append('<option value="' + grade + '">' + grade + '</option>');
    });
    gradeSelect.val(currentGrade);
  }
}

function populateFilters(rowId, weightDetails) {
  var products = {};
  var grades = [];
  
  weightDetails.forEach(function(detail) {
    products[detail.product_name] = true;
    if(grades.indexOf(detail.grade) === -1) {
      grades.push(detail.grade);
    }
  });
  
  var productSelect = $('#productFilter_' + rowId);
  for(var product in products) {
    productSelect.append('<option value="' + product + '">' + product + '</option>');
  }
  
  grades.sort();
  var gradeSelect = $('#gradeFilter_' + rowId);
  grades.forEach(function(grade) {
    gradeSelect.append('<option value="' + grade + '">' + grade + '</option>');
  });
}

</script>