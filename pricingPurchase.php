<?php
require_once 'php/db_connect.php';
session_start();

if(!isset($_SESSION['userID'])){
  echo '<script>window.location.href = "login.html";</script>';
  exit;
}

$company = $_SESSION['customer'];
$user = $_SESSION['userID'];
$role = $_SESSION['role'];
$module = $_SESSION['module'];
$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];

$stmt = $db->prepare("SELECT allow_add, allow_edit, allow_delete FROM users WHERE id=?");
$stmt->bind_param('s', $user);
$stmt->execute();
$stmt->bind_result($allowAdd, $allowEdit, $allowDelete);
$stmt->fetch();
$stmt->close();

if ($role != 'SADMIN') {
  $productQuery = "SELECT * FROM products p INNER JOIN categories c ON p.category = c.id WHERE p.deleted='0' AND p.customer='$company' AND c.module='$module' AND c.deleted='0' ORDER BY p.product_name ASC";
  $productCheck = $db->query($productQuery);
  if ($productCheck->num_rows == 0) {
    $productQuery = "SELECT * FROM products WHERE deleted='0' AND customer='$company' ORDER BY product_name ASC";
  }
  $products = $db->query($productQuery);
} else {
  $products  = $db->query("SELECT * FROM products WHERE deleted='0' ORDER BY product_name ASC");
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><?=$languageArray['purchase_code'][$language]?></h1>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">

        <!-- Filter Card -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label><?=$languageArray['from_date_code'][$language]?>:</label>
                                <div class="input-group date" id="fromDatePicker" data-target-input="nearest">
                                    <input type="text" class="form-control datetimepicker-input"
                                        data-target="#fromDatePicker" id="fromDate">
                                    <div class="input-group-append" data-target="#fromDatePicker"
                                        data-toggle="datetimepicker">
                                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <label><?=$languageArray['to_date_code'][$language]?>:</label>
                                <div class="input-group date" id="toDatePicker" data-target-input="nearest">
                                    <input type="text" class="form-control datetimepicker-input"
                                        data-target="#toDatePicker" id="toDate">
                                    <div class="input-group-append" data-target="#toDatePicker"
                                        data-toggle="datetimepicker">
                                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <label><?=$languageArray['purchase_no_code'][$language]?></label>
                                <input type="text" id="purchaseNoFilter" name="purchaseNoFilter" class="form-control"
                                    placeholder="<?=$languageArray['purchase_no_code'][$language]?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-block bg-gradient-warning btn-sm mb-3"
                                    id="filterSearch">
                                    <i class="fas fa-search"></i> <?=$languageArray['search_code'][$language]?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DataTable Card -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-info">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-10"><?=$languageArray['purchase_code'][$language]?></div>
                            <?php if($allowAdd == 'Y'): ?>
                            <div class="col-2">
                                <button type="button" class="btn btn-block bg-gradient-success btn-sm"
                                    onclick="newEntry()">
                                    <i class="fas fa-plus"></i> <?=$languageArray['add_new_code'][$language]?>
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="purchaseTable" class="table table-bordered table-striped display">
                            <thead>
                                <tr>
                                    <th><?=$languageArray['purchase_no_code'][$language]?></th>
                                    <th><?=$languageArray['total_price_code'][$language]?> (RM)</th>
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

<!-- Add/Edit Modal -->
<div class="modal fade" id="purchaseModal">
    <div class="modal-dialog modal-xl" style="max-width:90%;">
        <div class="modal-content">
            <form role="form" id="purchaseForm">
                <div class="modal-header bg-gray-dark color-palette">
                    <h4 class="modal-title" id="modalTitle"><?=$languageArray['add_new_code'][$language]?></h4>
                    <button type="button" class="close bg-gray-dark color-palette" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body bg-light">
                    <input type="hidden" id="id" name="id">

                    <!-- Purchase Info -->
                    <div class="card card-outline card-primary mb-3 shadow-sm">
                        <div class="card-header py-2">
                            <h6 class="card-title mb-0"><i class="fas fa-file-invoice mr-2"></i><?=$languageArray['purchase_code'][$language]?> Info</h6>
                        </div>
                        <div class="card-body pt-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="text-muted small mb-1"><?=$languageArray['purchase_code'][$language]?> No</label>
                                        <input type="text" class="form-control" id="purchaseNo" name="purchaseNo" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="card card-outline card-warning mb-3 shadow-sm">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center w-100">
                            <h6 class="card-title mb-0"><i class="fas fa-boxes mr-2"></i><?=$languageArray['item_code'][$language]?></h6>
                            <button type="button" class="btn btn-success btn-sm ml-auto" id="addItemRow">
                              <i class="fas fa-plus"></i> <?=$languageArray['add_new_code'][$language]?>
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered mb-0">
                                <thead class="thead-light">
                                  <tr>
                                    <th><?=$languageArray['item_code'][$language]?></th>
                                    <th><?=$languageArray['weight_code'][$language]?></th>
                                    <th><?=$languageArray['unit_price_code'][$language]?> (RM)</th>
                                    <th><?=$languageArray['total_price_code'][$language]?> (RM)</th>
                                    <th></th>
                                  </tr>
                                </thead>
                                <tbody id="itemTable"></tbody>
                                <tfoot>
                                  <tr>
                                    <td colspan="3" class="text-right font-weight-bold"> <?=$languageArray['total_code'][$language]?> (RM)</td>
                                    <td><input type="text" id="grandTotal" name="grandTotal" class="form-control form-control-sm text-right font-weight-bold" readonly value="0.00"></td>
                                    <td></td>
                                  </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                </div>

                <div class="modal-footer justify-content-between bg-gray-dark color-palette">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
                    <button type="submit" class="btn btn-primary" id="saveBtn"><?=$languageArray['save_code'][$language]?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Purchase Modal -->
<div class="modal fade" id="viewPurchaseModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

      <div class="modal-header bg-info color-palette">
        <h5 class="modal-title text-white">
          <i class="fas fa-file-invoice mr-2"></i><?=$languageArray['purchase_code'][$language]?> - <span id="v_purchase_no"></span>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>

      <div class="modal-body">

        <!-- Items Card -->
        <div class="card card-outline card-warning shadow-sm mb-3">
          <div class="card-header py-2">
            <h6 class="card-title mb-0"><i class="fas fa-boxes mr-2"></i><?=$languageArray['item_code'][$language]?></h6>
          </div>
          <div class="card-body p-0">
            <table class="table table-bordered table-striped mb-0">
              <thead class="thead-dark">
                <tr>
                  <th width="40">#</th>
                  <th><?=$languageArray['item_code'][$language]?></th>
                  <th width="120"><?=$languageArray['weight_code'][$language]?></th>
                  <th width="150"><?=$languageArray['unit_price_code'][$language]?> (RM)</th>
                  <th width="150"><?=$languageArray['total_price_code'][$language]?> (RM)</th>
                </tr>
              </thead>
              <tbody id="v_cart_items"></tbody>
              <tfoot>
                <tr>
                  <td colspan="4" class="text-right font-weight-bold"><?=$languageArray['total_code'][$language]?></td>
                  <td class="font-weight-bold text-right">RM <span id="v_total_price"></span></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Meta Info -->
        <div class="card card-outline card-primary shadow-sm mb-0">
          <div class="card-body py-2 px-3">
            <small><strong><?=$languageArray['created_by_code'][$language]?>:</strong> <span id="v_created_by"></span> &nbsp;|&nbsp; <span id="v_created_datetime"></span></small>
          </div>
        </div>

      </div>

      <div class="modal-footer justify-content-end bg-gray-dark color-palette">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
      </div>

    </div>
  </div>
</div>

<script type="text/html" id="itemDetail">
  <tr class="details">
    <td>
      <select class="form-control select2" id="itemProduct" name="itemProduct" style="width:100%">
        <?php while($rowPF = mysqli_fetch_assoc($products)){ ?>
        <option value="<?=$rowPF['id'] ?>" data-packaging-id="<?=$rowPF['packaging'] ?>"><?=$rowPF['product_name'] ?></option>
        <?php } ?>
      </select>
    </td>
    <td>
      <input type="number" class="form-control form-control-sm" id="itemWeight" name="itemWeight" step="0.01" min="0">
    </td>
    <td>
      <input type="number" class="form-control form-control-sm" id="itemPrice" name="itemPrice" step="0.01" min="0">
    </td>
    <td>
      <input type="text" class="form-control form-control-sm text-right" id="itemTotal" name="itemTotal" readonly value="0.00">
    </td>
    <td class="d-flex" style="text-align:center">
      <button class="btn btn-sm btn-danger" id="remove" style="background-color: #f06548;">
          <i class="fa fa-times"></i>
      </button>
    </td>
  </tr>
</script>

<script>
var itemRowCount = 0;
var allowEdit = <?=$allowEdit   == 'Y' ? 'true' : 'false'?>;
var allowDelete = <?=$allowDelete == 'Y' ? 'true' : 'false'?>;

$(function() {
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

    // Init DataTable
    var fromDateI = $('#fromDate').val();
    var toDateI = $('#toDate').val();
    var purchaseNoI = $('#purchaseNoFilter').val() ? $('#purchaseNoFilter').val() : '';

    var table = $("#purchaseTable").DataTable({
      responsive: true,
      autoWidth: false,
      processing: true,
      serverSide: true,
      serverMethod: 'post',
      order: [
        [1, 'desc']
      ],
      ajax: {
        url: 'php/filterPurchase.php',
        data: {
          fromDate: fromDateI,
          toDate: toDateI,
          purchaseNo: purchaseNoI
        }
      },
      columns: [
        {
          data: 'purchase_no'
        },
        {
          data: 'total_price'
        },
        {
          data: 'created_by_name'
        },
        {
          data: 'created_datetime'
        },
        {
          data: 'id',
          orderable: false,
          render: function(data) {
            var btns = `<div class="d-flex flex-nowrap" style="gap:4px;">
              <button type="button" id="view${data}" onclick="view(${data})" class="btn btn-info btn-sm">
                <i class="fas fa-eye"></i>
              </button>`;
            // if (allowEdit) {
            //     btns += '<button type="button" onclick="edit(' + data + ')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button>';
            // }
            if (allowDelete) {
                btns += '<button type="button" onclick="deactivate(' + data + ')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>';
            }
            btns += '</div>';
            return btns;
          }
        }
      ]
    });

    $('#filterSearch').on('click', function() {
      var fromDateI = $('#fromDate').val();
      var toDateI = $('#toDate').val();
      var purchaseNoI = $('#purchaseNoFilter').val() ? $('#purchaseNoFilter').val() : '';

      //Destroy the old Datatable
      $("#purchaseTable").DataTable().clear().destroy();

      //Create new Datatable
      table = $("#purchaseTable").DataTable({
        responsive: true,
        autoWidth: false,
        processing: true,
        serverSide: true,
        serverMethod: 'post',
        order: [
          [1, 'desc']
        ],
        ajax: {
          url: 'php/filterPurchase.php',
          data: {
            fromDate: fromDateI,
            toDate: toDateI,
            purchaseNo: purchaseNoI
          }
        },
        columns: [
          {
            data: 'purchase_no'
          },
          {
            data: 'total_price'
          },
          {
            data: 'created_by_name'
          },
          {
            data: 'created_datetime'
          },
          {
            data: 'id',
            orderable: false,
            render: function(data) {
              var btns = `<div class="d-flex flex-nowrap" style="gap:4px;">
                <button type="button" id="view${data}" onclick="view(${data})" class="btn btn-info btn-sm">
                  <i class="fas fa-eye"></i>
                </button>`;
              if (allowEdit) {
                  btns += '<button type="button" onclick="edit(' + data + ')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button>';
              }
              if (allowDelete) {
                  btns += '<button type="button" onclick="deactivate(' + data + ')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>';
              }
              btns += '</div>';
              return btns;
            }
          }
        ]
      });
    });

    $.validator.setDefaults({
      submitHandler: function() {
        if ($('#purchaseModal').hasClass('show')) {
          // Validate to make sure got at least one item
          if ($('#itemTable tr.details').length == 0) {
              toastr["error"]('Please add at least one item.', "Failed:");
              return;
          }
          $('#spinnerLoading').show();
          var formData = new FormData($('#purchaseForm')[0]);
          $.ajax({
              url: 'php/purchase.php',
              type: 'POST',
              data: formData,
              processData: false,
              contentType: false,
              success: function(data) {
                  var obj = JSON.parse(data);
                  if (obj.status === 'success') {
                      $('#purchaseModal').modal('hide');
                      toastr["success"](obj.message, "Success:");
                      $('#purchaseTable').DataTable().ajax.reload();
                  } else if (obj.status === 'failed') {
                      toastr["error"](obj.message, "Failed:");
                  } else {
                      toastr["error"]("Something wrong when edit", "Failed:");
                  }
                  $('#spinnerLoading').hide();
              },
              error: function() {
                  toastr["error"]("Something wrong when saving", "Failed:");
                  $('#spinnerLoading').hide();
              }
          });
        }
      }
    });

    // Remove item row
    $("#itemTable").on('click', 'button[id^="remove"]', function() {
        var $row = $(this).parents("tr");
        var rowId = $row.attr('id');
        $row.remove();

        $("#itemTable tr").each(function(index) {
            $(this).find('input[name^="itemNo"]').val(index + 1);
        });

        itemRowCount--;
        calculateTotals();
    });

    // Add item row
    $("#addItemRow").click(function() {
        var $addContents = $("#itemDetail").clone();
        $("#itemTable").append($addContents.html());

        $("#itemTable").find('.details:last').attr("id", "detail" + itemRowCount);
        $("#itemTable").find('.details:last').attr("data-index", itemRowCount);
        $("#itemTable").find('#remove:last').attr("id", "remove" + itemRowCount);
        
        $("#itemTable").find('#itemProduct:last').attr('name', 'itemProduct[' + itemRowCount + ']') .attr("id", "itemProduct" + itemRowCount).trigger('change');
        $("#itemTable").find('#itemWeight:last').attr('name', 'itemWeight[' + itemRowCount + ']').attr( "id", "itemWeight" + itemRowCount);
        $("#itemTable").find('#itemPrice:last').attr('name', 'itemPrice[' + itemRowCount + ']').attr( "id", "itemPrice" + itemRowCount);
        $("#itemTable").find('#itemTotal:last').attr('name', 'itemTotal[' + itemRowCount + ']').attr( "id", "itemTotal" + itemRowCount);

        itemRowCount++;

        $('.select2').select2({
          allowClear: true,
          placeholder: "Please Select",
          width: '100%'
        });
    });

    // Event delegation to calculate total price from weight
    $("#itemTable").on('input', 'input[id^="itemWeight"]', function() {
        // Retrieve the input's attributes
        var itemWeight = $(this).val() || 0;
        var unitPrice = $(this).closest('.details').find('input[id^="itemPrice"]').val() || 0;
        var totalPrice = parseFloat(itemWeight) * parseFloat(unitPrice);

        $(this).closest('.details').find('input[id^="itemTotal"]').val(totalPrice.toFixed(2));
        calculateTotals();
    });

    // Event delegation to calculate total price from unit price
    $("#itemTable").on('input', 'input[id^="itemPrice"]', function() {
        // Retrieve the input's attributes
        var unitPrice = $(this).val() || 0;
        var itemWeight = $(this).closest('.details').find('input[id^="itemWeight"]').val() || 0;
        var totalPrice = parseFloat(itemWeight) * parseFloat(unitPrice);

        $(this).closest('.details').find('input[id^="itemTotal"]').val(totalPrice.toFixed(2));
        calculateTotals();
    });
});

function calculateTotals() {
  var grandTotal = 0;
  $("#itemTable tr").each(function() {
    var itemTotal = parseFloat($(this).find('input[id^="itemTotal"]').val()) || 0;
    grandTotal += itemTotal;
  });
  $('#grandTotal').val(grandTotal.toFixed(2));
}

function view(id){
  $.post('php/getPurchase.php', {id: id}, function(data){
    var obj = JSON.parse(data);
    if(obj.status === 'success'){
      var m = obj.message;
      $('#v_purchase_no').text(m.purchase_no);
      $('#v_total_price').text(m.total_price);
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

      $('#viewPurchaseModal').modal('show');
    } else {
      toastr["error"](obj.message, "Failed:");
    }
  });
}

function newEntry() {
  $('#purchaseModal').find('#id').val("");
  $('#purchaseModal').find('#purchaseNo').val("");
  $('#itemTable').empty();
  $('#grandTotal').val('0.00');
  $('#purchaseModal').modal('show');

  $('#purchaseForm').validate({
      errorElement: 'span',
      errorPlacement: function(error, element) {
          error.addClass('invalid-feedback');
          element.closest('.form-group').append(error);
      },
      highlight: function(element, errorClass, validClass) {
          $(element).addClass('is-invalid');
      },
      unhighlight: function(element, errorClass, validClass) {
          $(element).removeClass('is-invalid');
      }
  });
}

function edit(id) {
  $('#spinnerLoading').show();
  $.post('php/getPurchase.php', {id: id}, function(data){
    var obj = JSON.parse(data);
    
    if(obj.status === 'success'){
      $('#purchaseModal').find('#id').val(obj.message.id);
      $('#purchaseModal').find('#purchaseNo').val(obj.message.purchase_no);
      $('#purchaseModal').find('#grandTotal').val(obj.message.total_price);
      
      // Populate item table
      $('#itemTable').html('');
      itemRowCount = 0;

      if (obj.message.cart_items.length > 0){
        for(var i = 0; i < obj.message.cart_items.length; i++){
          var item = obj.message.cart_items[i];
          var $addContents = $("#itemDetail").clone();
          $("#itemTable").append($addContents.html());

          $("#itemTable").find('.details:last').attr("id", "detail" + itemRowCount);
          $("#itemTable").find('.details:last').attr("data-index", itemRowCount);
          $("#itemTable").find('#remove:last').attr("id", "remove" + itemRowCount);

          $("#itemTable").find('#itemProduct:last').attr('name', 'itemProduct[' + itemRowCount + ']') .attr("id", "itemProduct" + itemRowCount).val(item.product_id);
          $("#itemTable").find('#itemWeight:last').attr('name', 'itemWeight[' + itemRowCount + ']').attr( "id", "itemWeight" + itemRowCount).val(item.weight);
          $("#itemTable").find('#itemPrice:last').attr('name', 'itemPrice[' + itemRowCount + ']').attr( "id", "itemPrice" + itemRowCount).val(item.price);
          $("#itemTable").find('#itemTotal:last').attr('name', 'itemTotal[' + itemRowCount + ']').attr( "id", "itemTotal" + itemRowCount).val(item.total_price);
          itemRowCount++;
        }
      }

      $('.select2').each(function() {
        $(this).select2({
          allowClear: true,
          placeholder: "Please Select",
          // Conditionally set dropdownParent based on the element’s location
          dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-body') : undefined
        });
      });
      
      $('#purchaseModal').modal('show');

      $('#purchaseForm').validate({
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

function deactivate(id){
  if (confirm('Are you sure you want to delete this items?')) {
    $.post('php/deletePurchase.php', {id: id}, function(data){
        var obj = JSON.parse(data);
        
        if(obj.status === 'success'){
          toastr["success"](obj.message, "Success:");
          $('#purchaseTable').DataTable().ajax.reload();
        }
        else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
        }
        else{
            toastr["error"]("Something wrong when deactivate", "Failed:");
        }
    });
  }
}
</script>