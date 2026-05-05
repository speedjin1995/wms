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
	
	if(($row = $result->fetch_assoc()) !== null){
    $role = $row['role_code'];
  }

  if ($role != 'SADMIN'){
    $products = $db->query("SELECT * FROM products WHERE deleted = '0' AND customer = '$company' ORDER BY product_name ASC");
    $supplies = $db->query("SELECT * FROM supplies WHERE deleted = '0' AND customer = '$company' ORDER BY supplier_name ASC");
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' AND customer = '$company' ORDER BY customer_name ASC");
    $vehicles2 = $db->query("SELECT * FROM vehicles WHERE deleted = '0' AND customer = '$company' ORDER BY veh_number ASC");
    $users = $db->query("SELECT * FROM users WHERE deleted = '0' AND customer = '$company' ORDER BY name ASC");

  } else {
    $products = $db->query("SELECT * FROM products WHERE deleted = '0' ORDER BY product_name ASC");
    $supplies = $db->query("SELECT * FROM supplies WHERE deleted = '0' ORDER BY supplier_name ASC");
    $customers = $db->query("SELECT * FROM customers WHERE deleted = '0' ORDER BY customer_name ASC");
    $vehicles2 = $db->query("SELECT * FROM vehicles WHERE deleted = '0' ORDER BY veh_number ASC");
    $users = $db->query("SELECT * FROM users WHERE deleted = '0' ORDER BY name ASC");
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
        <h1 class="m-0 text-dark"><?=$languageArray['reports_code'][$language]?></h1>
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

              <div class="form-group col-3">
                <label><?=$languageArray['receipt_no_code'][$language]?></label>
                <input type="text" id="receiptNoFilter" name="receiptNoFilter" class="form-control" placeholder="<?=$languageArray['receipt_no_code'][$language]?>">
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
              <div class="col-6"><?=$languageArray['sales_code'][$language]?></div>
              <!-- <div class="col-3">
                <button type="button" class="btn btn-block bg-gradient-warning btn-sm" id="exportPdf"><?=$languageArray['export_pdf_code'][$language]?></button>
              </div>
              <div class="col-3">
                <button type="button" class="btn btn-block bg-gradient-success btn-sm" id="exportExcel"><?=$languageArray['export_excel_code'][$language]?></button>
              </div> -->
            </div>
          </div>

          <div class="card-body">
            <table id="weightTable" class="table table-bordered table-striped display">
              <thead>
                <tr>
                  <th>#</th>
                  <th><?=$languageArray['receipt_no_code'][$language]?></th>
                  <th><?=$languageArray['sub_total_code'][$language]?> (RM)</th>
                  <th><?=$languageArray['tax_code'][$language]?> (%)</th>
                  <th><?=$languageArray['tax_amount_code'][$language]?> (RM)</th>
                  <th><?=$languageArray['discount_code'][$language]?> (RM)</th>
                  <th><?=$languageArray['total_price_code'][$language]?> (RM)</th>
                  <th><?=$languageArray['payment_method_code'][$language]?></th>
                  <th><?=$languageArray['created_by_code'][$language]?></th>
                  <th><?=$languageArray['created_datetime_code'][$language]?></th>
                  <th width="10%"><?=$languageArray['actions_code'][$language]?></th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>  

<!-- View Sales Modal -->
<div class="modal fade" id="viewSalesModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info">
        <h5 class="modal-title text-white"><i class="fas fa-receipt mr-2"></i>Sales Order - <span id="v_receipt_no"></span></h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <!-- Cart Items -->
        <h6 class="font-weight-bold mb-2">Items</h6>
        <table class="table table-bordered table-sm table-striped">
          <thead class="thead-dark">
            <tr>
              <th>#</th>
              <th>Product</th>
              <th>Weight (kg)</th>
              <th>Price (RM)</th>
              <th>Total (RM)</th>
            </tr>
          </thead>
          <tbody id="v_cart_items"></tbody>
        </table>
        <!-- Summary -->
        <div class="row justify-content-end">
          <div class="col-5">
            <table class="table table-sm">
              <tr><td>Sub Total</td><td class="text-right">RM <span id="v_subtotal"></span></td></tr>
              <tr><td>Tax (<span id="v_tax"></span>%)</td><td class="text-right">RM <span id="v_tax_amount"></span></td></tr>
              <tr><td>Discount</td><td class="text-right">RM <span id="v_discount"></span></td></tr>
              <tr class="font-weight-bold"><td>Total</td><td class="text-right">RM <span id="v_total_price"></span></td></tr>
            </table>
          </div>
        </div>
        <hr>
        <div class="row">
          <div class="col-6"><small><strong>Payment Method:</strong> <span id="v_payment_method"></span></small></div>
          <div class="col-6 text-right"><small><strong>Created By:</strong> <span id="v_created_by"></span> on <span id="v_created_datetime"></span></small></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
$(function () {
  const today = new Date();
  const tomorrow = new Date(today);
  const yesterday = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);
  yesterday.setDate(yesterday.getDate() - 7);

  $('.select2').select2({
    allowClear: true,
    placeholder: "Please Select"
  });

  //Date picker
  $('#fromDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: yesterday
  });

  $('#toDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: today
  });

  $('#selectAllCheckbox').on('change', function() {
    var checkboxes = $('#weightTable tbody input[type="checkbox"]');
    checkboxes.prop('checked', $(this).prop('checked')).trigger('change');
  });
  
  var fromDateI = $('#fromDate').val();
  var toDateI = $('#toDate').val();
  var receiptNoI = $('#receiptNoFilter').val() ? $('#receiptNoFilter').val() : '';

  var table = $("#weightTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'searching': false,
    'order': [[ 1, 'asc' ]],
    'columnDefs': [ { orderable: false, targets: [0] }],
    'ajax': {
      'url':'php/filterSales.php',
      'data': {
        fromDate: fromDateI,
        toDate: toDateI,
        receiptNo: receiptNoI,
      } 
    },
    'columns': [
      {
        data: null,
        orderable: false,
        render: function (data, type, row, meta) {
          return meta.row + meta.settings._iDisplayStart + 1;
        }
      },
      { data: 'receipt_no' },
      { data: 'subtotal' },
      { data: 'tax' },
      { data: 'tax_amount' },
      { data: 'discount' },
      { data: 'total_price' },
      { data: 'payment_method' },
      { data: 'created_by_name' },
      { data: 'created_datetime' },
      { 
        data: 'id',
        render: function ( data, type, row ) {
          return `
            <div class="row">
              <div class="col-3">
                <button type="button" id="view${data}" onclick="view(${data})" class="btn btn-info btn-sm">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
              <div class="col-3">
                <button type="button" id="deactivate${data}" onclick="deactivate(${data})" class="btn btn-danger btn-sm">
                <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>`;
        }
      }
    ]
  });

  $('#filterSearch').on('click', function(){
    //$('#spinnerLoading').show();
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var receiptNoI = $('#receiptNoFilter').val() ? $('#receiptNoFilter').val() : '';

    //Destroy the old Datatable
    $("#weightTable").DataTable().clear().destroy();

    //Create new Datatable
    table = $("#weightTable").DataTable({
      "responsive": true,
      "autoWidth": false,
      'processing': true,
      'serverSide': true,
      'serverMethod': 'post',
      'searching': false,
      'order': [[ 1, 'asc' ]],
      'columnDefs': [ { orderable: false, targets: [0] }],
      'ajax': {
        'url':'php/filterSales.php',
        'data': {
          fromDate: fromDateI,
          toDate: toDateI,
          receiptNo: receiptNoI,
        } 
      },
      'columns': [
        {
          data: null,
          orderable: false,
          render: function (data, type, row, meta) {
            return meta.row + meta.settings._iDisplayStart + 1;
          }
        },
        { data: 'receipt_no' },
        { data: 'subtotal' },
        { data: 'tax' },
        { data: 'tax_amount' },
        { data: 'discount' },
        { data: 'total_price' },
        { data: 'payment_method' },
        { data: 'created_by_name' },
        { data: 'created_datetime' },
        { 
          data: 'id',
          render: function ( data, type, row ) {
            return `
              <div class="row">
                <div class="col-3">
                  <button type="button" id="view${data}" onclick="view(${data})" class="btn btn-info btn-sm">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
                <div class="col-3">
                  <button type="button" id="deactivate${data}" onclick="deactivate(${data})" class="btn btn-danger btn-sm">
                  <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>`;
          }
        }
      ]
    });
  });
});

function view(id){
  $.post('php/getSales.php', {id: id}, function(data){
    var obj = JSON.parse(data);
    if(obj.status === 'success'){
      var m = obj.message;
      $('#v_receipt_no').text(m.receipt_no);
      $('#v_subtotal').text(m.subtotal);
      $('#v_tax').text(m.tax);
      $('#v_tax_amount').text(m.tax_amount);
      $('#v_discount').text(m.discount);
      $('#v_total_price').text(m.total_price);
      $('#v_payment_method').text(m.payment_method);
      $('#v_created_by').text(m.created_by_name);
      $('#v_created_datetime').text(m.created_datetime);

      var rows = '';
      $.each(m.cart_items, function(i, item){
        rows += `<tr>
          <td>${i + 1}</td>
          <td>${item.product_name}</td>
          <td>${item.weight}</td>
          <td>${item.price}</td>
          <td>${item.total_price}</td>
        </tr>`;
      });
      $('#v_cart_items').html(rows || '<tr><td colspan="5" class="text-center">No items</td></tr>');

      $('#viewSalesModal').modal('show');
    } else {
      toastr["error"](obj.message, "Failed:");
    }
  });
}

function deactivate(id){
  if (confirm('Are you sure you want to delete this items?')) {
    $.post('php/deleteSales.php', {id: id}, function(data){
        var obj = JSON.parse(data);
        
        if(obj.status === 'success'){
          toastr["success"](obj.message, "Success:");
          $('#weightTable').DataTable().ajax.reload();
        }
        else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
        }
        else{
            toastr["error"]("Something wrong when activate", "Failed:");
        }
    });
  }
}
</script>