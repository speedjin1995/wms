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
  $productGroup = array();
  $message = array();

  if ($role != 'SADMIN'){
    $products = $db->query("SELECT p.id, p.product_name, p.price, c.category_name, p.product_image, COALESCE(i.quantity, 0) AS stock, pk.packaging_name AS packaging_name, pk.is_by_weight AS by_weight FROM products p LEFT JOIN categories c ON p.category = c.id LEFT JOIN inventory i ON i.product_id = p.id AND i.status = 0 LEFT JOIN packaging pk ON p.packaging = pk.id WHERE p.deleted = '0' AND p.customer = '$company' AND p.category IS NOT NULL ORDER BY p.product_name ASC");
  } else {
    $products = $db->query("SELECT p.id, p.product_name, p.price, c.category_name, p.product_image, COALESCE(i.quantity, 0) AS stock, pk.packaging_name AS packaging_name, pk.is_by_weight AS by_weight FROM products p LEFT JOIN categories c ON p.category = c.id LEFT JOIN inventory i ON i.product_id = p.id AND i.status = 0 LEFT JOIN packaging pk ON p.packaging = pk.id WHERE p.deleted = '0' AND p.category IS NOT NULL ORDER BY p.product_name ASC");
  }

  while($rowProducts=mysqli_fetch_assoc($products)){
    $cat = $rowProducts['category_name'] ?? 'General';
    if(!in_array($cat, $productGroup)){
      $message[] = array(
        'Category' => $cat,
        'Products' => array()
      );
      array_push($productGroup, $cat);
    }
    $key = array_search($cat, $productGroup);
    array_push($message[$key]['Products'], array(
      'id'        => $rowProducts['id'],
      'item_name' => $rowProducts['product_name'],
      'price'     => $rowProducts['price'],
      'img'       => $rowProducts['product_image'],
      'stock'     => $rowProducts['stock'],
      'packaging_name' => $rowProducts['packaging_name'],
      'by_weight' => $rowProducts['by_weight'],
    ));
  }

  // Language
  $language = $_SESSION['language'];
  $languageArray = $_SESSION['languageArray'];
}
?>

<style>
  .content-wrapper { padding:0 !important; }
  #sales-wrap { display:flex; height:calc(100vh - 57px); overflow:hidden; }
  #sales-left  { flex:1; overflow:hidden; display:flex; flex-direction:column; border-right:1px solid #e5e7eb; }
  #sales-right { width:480px; flex-shrink:0; overflow:hidden; display:flex; flex-direction:column; }

  #sales-wrap { font-family: 'Source Sans Pro', sans-serif; font-size:0.9rem; }
  #sales-wrap *:not(.fas):not(.far):not(.fab):not(.fal):not([class*='fa-']) { font-family: 'Source Sans Pro', sans-serif; font-size:0.9rem; }
  #sales-wrap h1 { font-size:2rem !important; font-weight:700; }

  .sales-header { flex-shrink:0; }
  .cat-tabs { display:flex; border-bottom:2px solid #e5e7eb; flex-shrink:0; }
  .cat-tab  { padding:10px 18px; font-size:0.9rem; font-weight:600; color:#6b7280; cursor:pointer; border-bottom:3px solid transparent; margin-bottom:-2px; transition:all 0.2s; white-space:nowrap; }
  .cat-tab.active { color:#2563eb; border-bottom-color:#2563eb; }

  .search-wrap { flex-shrink:0; }
  .search-wrap .input-group { border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; display:flex; align-items:center; }
  .search-wrap .input-group-text { background:#fff; color:#9ca3af; border:0; padding:8px 12px; flex-shrink:0; }
  .search-wrap input { border:0; flex:1; min-width:0; font-size:0.9rem; padding:8px 12px; outline:none; box-shadow:none; border-radius:0 8px 8px 0; }
  .search-wrap input:focus { box-shadow:none; outline:none; }

  .product-scroll { flex:1; overflow-y:auto; }
  .product-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }

  .product-card { border:1px solid #e5e7eb; border-radius:12px; cursor:pointer; transition:box-shadow 0.2s, transform 0.2s; overflow:hidden; background:#fff; }
  .product-card:hover { box-shadow:0 4px 20px rgba(0,0,0,0.10); transform:translateY(-2px); }
  .product-item.out-of-stock { pointer-events:none; }
  .product-item.out-of-stock .product-card { opacity:0.55; cursor:not-allowed; background:#f9fafb; }
  .product-item.out-of-stock .product-card:hover { box-shadow:none; transform:none; }
  .out-of-stock-badge { position:absolute; top:8px; right:8px; background:#ef4444; color:#fff; font-size:0.72rem; font-weight:700; padding:2px 8px; border-radius:20px; }
  .product-card .product-img { width:100%; height:160px; object-fit:cover; background:#f3f4f6; }
  .product-card .product-img-placeholder { width:100%; height:160px; background:#f3f4f6; display:flex; align-items:center; justify-content:center; color:#d1d5db; font-size:3rem; }
  .product-card .card-info { padding:12px 14px 14px; }
  .product-card .product-name { font-size:0.9rem; font-weight:700; color:#111827; margin:0 0 4px; }
  .product-card .product-price { font-size:0.9rem; color:#6b7280; margin:0; }

  .pagination-wrap { display:flex; justify-content:center; }
  .pagination .page-link { font-size:0.82rem; padding:4px 11px; color:#2563eb; border-color:#e5e7eb; }
  .pagination .page-item.active .page-link { background:#2563eb; border-color:#2563eb; color:#fff; }
  .pagination .page-item.disabled .page-link { color:#d1d5db; }

  .order-header { background:#2563eb; color:#fff; font-size:1rem; font-weight:700; flex-shrink:0; display:flex; align-items:center; gap:10px; }
  .order-table-wrap { flex:1; overflow-y:auto; }
  .order-table { width:100%; border-collapse:collapse; }
  .order-table thead th { font-size:0.9rem; color:#6b7280; font-weight:600; border-bottom:1px solid #f3f4f6; background:#fff; }
  .order-table tbody td { font-size:0.9rem; border-bottom:1px solid #f9fafb; vertical-align:middle; color:#111827; }
  .order-table .item-name { font-weight:600; }
  .del-btn { background:none; border:none; color:#ef4444; font-size:1rem; cursor:pointer; padding:0 4px; }

  .order-summary { border-top:1px solid #f3f4f6; flex-shrink:0; }
  .summary-row   { display:flex; justify-content:space-between; font-size:0.9rem; color:#6b7280; }
  .summary-total { display:flex; justify-content:space-between; font-size:1.05rem; font-weight:700; color:#2563eb; }
  .order-footer  { flex-shrink:0; }
  .order-footer label { font-size:0.9rem; font-weight:600; color:#374151; }
  .order-footer select { border-radius:8px; font-size:0.9rem; border-color:#e5e7eb; }
  .btn-cancel { flex:1; border:1.5px solid #ef4444; color:#ef4444; background:#fff; border-radius:8px; font-weight:600; font-size:0.88rem; transition:background 0.15s; }
  .btn-cancel:hover { background:#fef2f2; }
  .btn-submit { flex:1; background:#94a3b8; color:#fff; border:none; border-radius:8px; font-weight:600; font-size:0.88rem; display:flex; align-items:center; justify-content:center; gap:6px; transition:background 0.15s; }
  .btn-submit.ready { background:#2563eb; }
  .btn-submit.ready:hover { background:#1d4ed8; }
</style>

<div id="sales-wrap">

  <!-- Left Panel: Product Browser -->
  <div id="sales-left">

    <!-- Header & Category Tabs -->
    <div class="sales-header pt-4 pb-0 px-4">
      <h1 class="font-weight-bold mb-3"><?=$languageArray['sales_code'][$language]?></h1>
      <div class="cat-tabs" id="catTabs">
        <?php for ($i = 0; $i < count($productGroup); $i++) {
          $active = $i == 0 ? 'active' : '';
          $tabId  = str_replace(' ', '_', $productGroup[$i]);
          echo '<div class="cat-tab ' . $active . '" data-tab="' . $tabId . '">' . $productGroup[$i] . '</div>';
        } ?>
      </div>
    </div>

    <!-- Search Bar -->
    <div class="search-wrap px-4 pt-3 pb-2">
      <div class="input-group">
        <span class="input-group-text">
          <i class="fas fa-search"></i>
        </span>
        <input type="text" id="productSearch" placeholder="<?=$languageArray['search_products_code'][$language]?>">
      </div>
    </div>

    <!-- Product Grid -->
    <div class="product-scroll px-4 pt-2 pb-3">
      <?php for ($j = 0; $j < count($message); $j++) {
        $tabId = str_replace(' ', '_', $message[$j]['Category']);
        $show  = $j == 0 ? '' : 'display:none;';
        echo '<div class="tab-pane" id="tab_' . $tabId . '" data-tab="' . $tabId . '" style="' . $show . '">';
        echo '  <div class="product-grid" id="grid_' . $tabId . '">';

        for ($k = 0; $k < count($message[$j]['Products']); $k++) {
          $pid      = $message[$j]['Products'][$k]['id'];
          $name     = htmlspecialchars($message[$j]['Products'][$k]['item_name']);
          $price    = number_format((float) $message[$j]['Products'][$k]['price'], 2);
          $img      = $message[$j]['Products'][$k]['img'];
          $stock    = (float) $message[$j]['Products'][$k]['stock'];
          $packagingName = $message[$j]['Products'][$k]['packaging_name'] ?? 'kg';
          $byWeight = $message[$j]['Products'][$k]['by_weight'] ?? 'kg';
          $noStock = $stock <= 0;

          $imgHtml = $img
            ? '<img src="php/viewPhoto.php?file=' . $img . '&type=file_table" class="product-img" loading="lazy">'
            : '<div class="product-img-placeholder"><i class="fas fa-box"></i></div>';

          $badge    = $noStock ? '<span class="out-of-stock-badge">Not Available</span>' : '';
          $oosCls   = $noStock ? ' out-of-stock' : '';
          $onclick  = $noStock ? '' : ' onclick="addItems(' . $pid . ', \'' . $packagingName . '\', \'' . $byWeight . '\')"';

          echo '
          <div class="product-item' . $oosCls . '" data-stock="' . $stock . '" data-pid="' . $pid . '"' . $onclick . '>
            <div class="product-card" style="position:relative;">
              ' . $imgHtml . $badge . '
              <div class="card-info">
                <p class="product-name">' . $name . '</p>
                <p class="product-price">RM ' . $price . '/'.$packagingName.'</p>
              </div>
            </div>
          </div>';
        }

        echo '  </div>'; // .product-grid
        echo '  <div class="pagination-wrap mt-3">';
        echo '    <ul class="pagination pagination-sm" id="page_' . $tabId . '"></ul>';
        echo '  </div>';
        echo '</div>'; // .tab-pane
      } ?>
    </div>

  </div>
  <!-- /Left Panel -->

  <!-- Right Panel: Order Summary -->
  <div id="sales-right">
    <form role="form" id="saleForm">

      <!-- Order Header -->
      <div class="order-header px-4 py-3">
        <i class="fas fa-shopping-cart"></i>
        <?=$languageArray['your_orders_code'][$language]?>
      </div>

      <input type="hidden" name="company" id="companyHidden" value="<?=$company?>">

      <!-- Order Items Table -->
      <div class="order-table-wrap">
        <table class="order-table">
          <thead>
            <tr>
              <th class="px-3 py-3"><?=$languageArray['item_code'][$language]?></th>
              <th class="px-3 py-3"><?=$languageArray['price_code'][$language]?>/<?=$languageArray['uom_code'][$language]?></th>
              <th class="px-3 py-3"><?=$languageArray['weight_code'][$language]?></th>
              <th class="px-3 py-3"><?=$languageArray['total_code'][$language]?></th>
              <th class="px-3 py-3"></th>
            </tr>
          </thead>
          <tbody id="TableId"></tbody>
        </table>
      </div>

      <!-- Order Summary -->
      <div class="order-summary px-4 pt-3 pb-0">
        <div class="summary-row mb-2">
          <span><?=$languageArray['sub_total_code'][$language]?> (RM)</span>
          <span>
            <input
              type="number"
              id="subTotalDisplay"
              step="0.01"
              value="0.00"
              readonly
              style="width:70px; border:1px solid #e5e7eb; border-radius:6px; padding:2px 6px; font-size:0.9rem; text-align:right; background:#f9fafb;"
            >
          </span>
        </div>
        <div class="summary-row mb-2">
          <span><?=$languageArray['discount_code'][$language]?> (RM)</span>
          <span>
            <input
              type="number"
              name="totalDiscount"
              id="totalDiscount"
              step="0.01"
              value="0.00"
              style="width:70px; border:1px solid #e5e7eb; border-radius:6px; padding:2px 6px; font-size:0.85rem; text-align:right;"
            >
          </span>
        </div>
        <div class="summary-row mb-2">
          <span><?=$languageArray['tax_code'][$language]?> (%)</span>
          <span>
            <input
              type="number"
              name="taxRate"
              id="taxRate"
              step="0.01"
              value="0.00"
              style="width:70px; border:1px solid #e5e7eb; border-radius:6px; padding:2px 6px; font-size:0.85rem; text-align:right;"
            >
          </span>
        </div>
        <div class="summary-total mb-3">
          <span><?=$languageArray['total_code'][$language]?> (RM)</span>
          <span>
            <input
              type="number"
              id="totalDisplay"
              step="0.01"
              value="0.00"
              readonly
              style="width:70px; border:1px solid #2563eb; border-radius:6px; padding:2px 6px; font-size:0.9rem; text-align:right; background:#eff6ff; color:#2563eb; font-weight:700;"
            >
          </span>
        </div>
        <input type="hidden" name="subTotalPricing" id="subTotalPricing" value="0.00">
        <input type="hidden" name="taxAmount" id="taxAmount" value="0.00">
        <input type="hidden" name="totalPricing" id="totalPricing" value="0.00">
      </div>

      <!-- Order Footer -->
      <div class="order-footer px-4 pt-2 pb-4">

        <!-- Company (SADMIN only) -->
        <?php if ($role == 'SADMIN') { ?>
        <div class="form-group mb-3">
          <label>Company <span class="text-danger">*</span></label>
          <select class="form-control" id="companySelect" name="company">
            <?php
              $companies->data_seek(0);
              while ($rowCompany = mysqli_fetch_assoc($companies)) {
                $sel = $rowCompany['id'] == $company ? 'selected' : '';
                echo '<option value="' . $rowCompany['id'] . '" ' . $sel . '>' . htmlspecialchars($rowCompany['name']) . '</option>';
              }
            ?>
          </select>
        </div>
        <?php } ?>

        <div class="d-flex" style="gap:10px;">
          <button type="button" class="btn-cancel py-2" id="cancelSales">
            <i class="fas fa-times mr-1"></i> <?=$languageArray['cancel_code'][$language]?>
          </button>
          <button type="submit" class="btn-submit py-2" id="submitSales" name="submitsales">
            <i class="fas fa-shopping-cart"></i> <?=$languageArray['submit_code'][$language]?>
          </button>
        </div>
      </div>

    </form>
  </div>
  <!-- /Right Panel -->

</div><!-- #sales-wrap -->

<input type="text" id="barcodeScan" style="position:absolute;left:-9999px;">

<!-- Weight Input Modal -->
<div class="modal fade" id="weightModal">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?=$languageArray['enter_quantity_code'][$language]?></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="card mb-3" style="background:#2563eb;" id="displayIndicatorWeight">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h2 class="text-white mb-0"><span id="indicatorWeight">0</span> KG</h2>
              </div>
              <div>
                <span class="rounded-circle bg-white d-inline-flex align-items-center justify-content-center" style="width:45px;height:45px;">
                  <i class="fas fa-weight-hanging text-info"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label><?=$languageArray['quantity_code'][$language]?> (<span id="uom">kg</span>)</label>
          <div class="input-group">
            <input type="number" class="form-control" id="quantity" step="0.01" min="0.01" placeholder="<?=$languageArray['enter_quantity_code'][$language]?>">
            <div class="input-group-append" id="displayWeightCapture">
              <button class="btn" id="weightCapture" type="button" style="background:#2563eb;color:white;"><i class="fas fa-sync"></i></button>
            </div>
          </div>
          <input type="hidden" id="productId">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><?=$languageArray['cancel_code'][$language]?></button>
        <button type="button" class="btn btn-primary" id="weightConfirm"><?=$languageArray['save_code'][$language]?></button>
      </div>
    </div>
  </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-money-bill-wave mr-2"></i><?=$languageArray['payments_code'][$language]?></h5>
      </div>
      <div class="modal-body">
        <div class="card bg-success mb-3">
          <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <small class="text-white-50"><?=$languageArray['total_amount_code'][$language]?></small>
                <h3 class="text-white mb-0">RM <span id="paymentTotal">0.00</span></h3>
              </div>
              <div id="balanceWrap">
                <small class="text-white-50"><?=$languageArray['balance_due_code'][$language]?></small>
                <h3 class="text-white mb-0">RM <span id="paymentBalance">0.00</span></h3>
              </div>
              <div id="changeWrap" style="display:none;">
                <small class="text-white-50"><?=$languageArray['change_amount_code'][$language]?></small>
                <h3 class="text-warning mb-0">RM <span id="paymentChange">0.00</span></h3>
              </div>
            </div>
          </div>
        </div>

        <table class="table table-sm table-bordered mb-3" id="paymentEntries">
          <thead>
            <tr>
              <th><?=$languageArray['payment_method_code'][$language]?></th>
              <th><?=$languageArray['amount_code'][$language]?> (RM)</th>
              <th width="40"></th>
            </tr>
          </thead>
          <tbody id="paymentTable"></tbody>
        </table>

        <button type="button" class="btn btn-primary btn-sm" id="addPaymentBtn"><i class="fas fa-plus"></i> <?=$languageArray['add_payments_code'][$language]?></button>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" id="cancelPaymentBtn"><?=$languageArray['cancel_code'][$language]?></button>
        <input type="hidden" id="totalPaidAmount" name="totalPaidAmount" value="0.00">
        <button type="button" class="btn btn-success" id="confirmPaymentBtn" disabled><i class="fas fa-check"></i> <?=$languageArray['save_code'][$language]?></button>
      </div>
    </div>
  </div>
</div>

<script type="text/html" id="paymentDetail">
  <tr class="details">
    <td>
      <select class="form-control form-control-sm" id="payMethod" name="payMethod">
        <option value="cash">Cash</option>
        <option value="credit_card">Credit Card</option>
        <option value="e-wallet">E-Wallet</option>
        <option value="bank_transfer">Bank Transfer</option>
      </select>
    </td>
    <td>
      <input type="number" class="form-control form-control-sm" id="payAmount" name="payAmount" step="0.01" min="0.01" placeholder="Amount">
    </td>
    <td>
      <button type="button" class="btn btn-danger btn-sm" id="remove"><i class="fas fa-times"></i></button>
    </td>
  </tr>
</script>

<script>
var size = 0;
var PAGE_SIZE = 6;
var paymentIndex = 0; // keyed by product id: {name, price, weight, total}

$(function(){
  // Company filter (SADMIN only) — update hidden field when changed
  $('#companySelect').on('change', function() {
    $('#companyHidden').val($(this).val());
  });

  // Tab switching
  $(document).on('click', '.cat-tab', function() {
    var tabId = $(this).data('tab');

    $('.cat-tab').removeClass('active');
    $(this).addClass('active');
    $('.tab-pane').hide();
    $('#tab_' + tabId).show();

    initPagination(tabId);
    $('#productSearch').val('').trigger('input');
  });

  // Init first tab pagination
  $('.tab-pane:visible').each(function() {
    initPagination($(this).data('tab'));
  });

  // Search
  $('#productSearch').on('input', function() {
    var q      = $(this).val().toLowerCase();
    var tabId  = $('.cat-tab.active').data('tab');
    var $items = $('#grid_' + tabId).find('.product-item');

    if (!q) {
      initPagination(tabId);
      return;
    }

    $items.each(function() {
      $(this).toggle(
        $(this).find('.product-name').text().toLowerCase().indexOf(q) > -1
      );
    });

    $('#page_' + tabId).empty();
  });

  // Discount & tax change
  $('#totalDiscount, #taxRate').on('input', recalc);

  // Cancel
  $('#cancelSales').on('click', function() {
    $.get('pricingSales.php', function(data) {
      $('#mainContents').html(data);
    });
  });

  // Submit - open payment modal
  $('#saleForm').on('submit', function(e) {
    e.preventDefault();

    if ($('#TableId .order-row').length === 0) {
      toastr.warning('Please add items first.');
      return;
    }

    var total = parseFloat($('#totalPricing').val()) || 0;
    if (total <= 0) {
      toastr.warning('Total must be greater than 0.');
      return;
    }

    // Reset payment modal
    payments = [];
    paymentIndex = 0;
    $('#paymentTable').empty();
    $('#paymentTotal').text(total.toFixed(2));
    $('#paymentBalance').text(total.toFixed(2));
    $('#totalPaidAmount').val('0.00');
    $('#balanceWrap').show();
    $('#changeWrap').hide();
    $('#confirmPaymentBtn').prop('disabled', true);
    $('#paymentModal').modal('show');
  });

  // Remove item row
  $("#paymentTable").on('click', 'button[id^="remove"]', function() {
    var $row = $(this).parents("tr");
    var rowId = $row.attr('id');
    $row.remove();

    paymentIndex--;
    recalcPaymentBalance();
  });

  // Add payment row
  $("#addPaymentBtn").click(function() {
    var $addContents = $("#paymentDetail").clone();
    $("#paymentTable").append($addContents.html());

    $("#paymentTable").find('.details:last').attr("id", "detail" + paymentIndex);
    $("#paymentTable").find('.details:last').attr("data-index", paymentIndex);
    $("#paymentTable").find('#remove:last').attr("id", "remove" + paymentIndex);
    
    $("#paymentTable").find('#payMethod:last').attr('name', 'payMethod[' + paymentIndex + ']') .attr("id", "payMethod" + paymentIndex).trigger('change');
    $("#paymentTable").find('#payAmount:last').attr('name', 'payAmount[' + paymentIndex + ']').attr( "id", "payAmount" + paymentIndex);

    paymentIndex++;

    $('.select2').select2({
      allowClear: true,
      placeholder: "Please Select",
      width: '100%'
    });
  });

  // Event delegation to calculate total price from weight
  $("#paymentTable").on('input', 'input[id^="payAmount"]', function() {
    recalcPaymentBalance();
  });

  // Cancel payment
  $('#cancelPaymentBtn').on('click', function() {
    $('#paymentModal').modal('hide');
  });

  // Confirm payment - submit sale
  $('#confirmPaymentBtn').on('click', function() {
    $('#spinnerLoading').show();
    var formData = $('#saleForm').serialize();

    formData += '&changeAmount=' + encodeURIComponent($('#paymentChange').text() || '0.00');
    formData += '&totalPaidAmount=' + encodeURIComponent($('#totalPaidAmount').val() || '0.00');
    $('#paymentTable .details').each(function(i) {
      var method = $(this).find('select[id^="payMethod"]').val();
      var amount = $(this).find('input[id^="payAmount"]').val();
      formData += '&payments[' + i + '][method]=' + encodeURIComponent(method);
      formData += '&payments[' + i + '][amount]=' + encodeURIComponent(amount);
    });

    $.post('php/sales.php', formData, function(data){
      var obj = JSON.parse(data);

      if(obj.status === 'success'){
        toastr["success"](obj.message, "Success:");
        $('#paymentModal').modal('hide');
        $("a[href='#reportsPricingSales']").click();
      }
      else if(obj.status === 'failed'){
        toastr["error"](obj.message, "Failed:");
      }
      else{
        toastr["error"]("Something wrong when edit", "Failed:");
      }
      $('#spinnerLoading').hide();
    });
  });

  $('#weightCapture').on('click', function() {
    var indicatorWeight = $('#indicatorWeight').text();  
    $('#weightModal').find('#quantity').val(indicatorWeight);
  });

  $('#weightConfirm').on('click', function() {
    var id = $('#weightModal').find('#productId').val();
    var quantity = parseFloat($('#weightModal').find('#quantity').val()) || 0;

    if (quantity <= 0) {
      alert('Please enter a valid quantity.', 'Failed:');
      return;
    }

    if ($('#row_' + id).length) {
      $('#wt_' + id).val(quantity);
      updateWeight(id);
      $('#weightModal').modal('hide');
      $('#spinnerLoading').hide();
      return;
    }

    $('#spinnerLoading').show();

    $.post('php/getProduct.php', { userID: id }, function(data) {
      var obj = JSON.parse(data);

      if (obj.status === 'success') {
        var product = obj.message;
        var uomLabel = product.packaging_name || product.uom_name || '';
        var stock = parseFloat($('.product-item[data-pid="' + id + '"]').data('stock')) || 0;

        if (stock > 0 && quantity > stock) {
          alert('Weight exceeds available stock (' + stock + ' kg).', 'Failed:');
          $('#spinnerLoading').hide();
          return;
        }

        addOrderRow(id, product.product_name, product.price || 0, uomLabel, quantity, stock);
        recalc();
        $('#weightModal').modal('hide');
      } else {
        toastr.error(obj.message, 'Failed:');
      }

      $('#spinnerLoading').hide();
    });
  });
});

function recalc() {
  var sub = 0;

  $('#TableId .order-row').each(function() {
    sub += parseFloat($(this).data('total')) || 0;
  });

  var disc    = parseFloat($('#totalDiscount').val()) || 0;
  var taxRate = parseFloat($('#taxRate').val()) || 0;
  var afterDisc = Math.max(0, sub - disc);
  var taxAmt  = (afterDisc * taxRate / 100);
  var total   = (afterDisc + taxAmt).toFixed(2);
  sub = sub.toFixed(2);

  $('#subTotalPricing').val(sub);
  $('#subTotalDisplay').val(sub);
  $('#taxAmount').val(taxAmt.toFixed(2));
  $('#totalPricing').val(total);
  $('#totalDisplay').val(total);
  $('#submitSales').toggleClass('ready', parseFloat(total) > 0);
}

function recalcPaymentBalance() {
  var total = parseFloat($('#paymentTotal').text()) || 0;
  var paid = 0;
  $('#paymentTable .details').each(function() {
    paid += parseFloat($(this).find('input[id^="payAmount"]').val()) || 0;
  });
  var balance = total - paid;
  if (paid > total) {
    $('#balanceWrap').hide();
    $('#changeWrap').show();
    $('#paymentChange').text((paid - total).toFixed(2));
    $('#confirmPaymentBtn').prop('disabled', false);
  } else {
    $('#balanceWrap').show();
    $('#changeWrap').hide();
    $('#paymentBalance').text(balance.toFixed(2));
    $('#confirmPaymentBtn').prop('disabled', balance != 0 || paid == 0);
  }
  $('#totalPaidAmount').val(paid.toFixed(2));
}

function addOrderRow(id, name, price, uomLabel, weight, stock) {
  var total = (parseFloat(price) * parseFloat(weight)).toFixed(2);
  var priceDisplay = parseFloat(price).toFixed(2) + (uomLabel ? ' / ' + uomLabel : '');

  var html =
    '<tr class="order-row" id="row_' + id + '" data-id="' + id + '" data-price="' + price + '" data-total="' + total + '" data-stock="' + (parseFloat(stock) || 0) + '">' +
      '<td class="item-name px-3 py-2">' + name + '<input type="hidden" name="items[' + size + ']" value="' + id + '"></td>' +
      '<td class="px-3 py-2">' +
        priceDisplay +
        '<input type="hidden" name="itemPrice[' + size + ']" value="' + price + '">' +
      '</td>' +
      '<td class="px-3 py-2">' +
        '<input type="number" class="wt-input" id="wt_' + id + '" name="itemWeight[' + size + ']" value="' + weight + '" step="0.01" min="0.01"' +
        ' style="width:80px; border:1px solid #e5e7eb; border-radius:6px; padding:3px 6px; font-size:0.85rem; text-align:center;"' +
        ' onchange="updateWeight(' + id + ')">' +
      '</td>' +
      '<td id="tot_' + id + '" class="px-3 py-2">' + total + '<input type="hidden" name="totalPrice[' + size + ']" id="tp_' + id + '" value="' + total + '"></td>' +
      '<td class="px-3 py-2"><button type="button" class="del-btn" onclick="removeRow(' + id + ')"><i class="fas fa-trash-alt"></i></button></td>' +
    '</tr>';

  $('#TableId').append(html);
  size++;
}

function updateWeight(id) {
  var $row  = $('#row_' + id);
  var price = parseFloat($row.data('price'));
  var wt    = parseFloat($('#wt_' + id).val());
  var stock = parseFloat($row.data('stock'));

  if (isNaN(wt) || wt <= 0) {
    removeRow(id);
    return;
  }

  if (stock > 0 && wt > stock) {
    toastr.error('Weight exceeds available stock (' + stock + ').');
    wt = stock;
    $('#wt_' + id).val(stock);
  }

  var total = (price * wt).toFixed(2);

  $('#tot_' + id).html(
    total + '<input type="hidden" name="totalPrice[' + $row.index() + ']" id="tp_' + id + '" value="' + total + '">'
  );
  $row.data('total', total);
  recalc();
}

function removeRow(id) {
  $('#row_' + id).remove();
  recalc();
}

function initPagination(tabId) {
  var $items = $('#grid_' + tabId).find('.product-item');
  var total  = $items.length;
  var pages  = Math.ceil(total / PAGE_SIZE);
  var $pager = $('#page_' + tabId);

  $pager.empty();

  if (pages <= 1) {
    $items.show();
    return;
  }

  function showPage(p) {
    $items.hide().slice((p - 1) * PAGE_SIZE, p * PAGE_SIZE).show();
    $pager.find('.page-item').removeClass('active');
    $pager.find('[data-page="' + p + '"]').parent().addClass('active');
  }

  for (var i = 1; i <= pages; i++) {
    $pager.append(
      '<li class="page-item"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>'
    );
  }

  $pager.on('click', '.page-link', function(e) {
    e.preventDefault();
    showPage(parseInt($(this).data('page')));
  });

  showPage(1);
}

function addItems(id, packagingName, byWeight) {
  $('#weightModal').find('#productId').val(id);
  $('#weightModal').find('#uom').text(packagingName);
  $('#weightModal').find('#quantity').val('');

  if (byWeight == 'Y'){
    $('#displayIndicatorWeight').show();
    $('#displayWeightCapture').show();
  }else{
    $('#displayIndicatorWeight').hide();
    $('#displayWeightCapture').hide();
  }
  
  $('#weightModal').modal('show');
}

</script>
