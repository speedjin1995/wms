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
    $products = $db->query("SELECT p.id, p.product_name, p.price, c.category_name, p.product_image FROM products p LEFT JOIN categories c ON p.category = c.id WHERE p.deleted = '0' AND p.customer = '$company' AND p.category IS NOT NULL ORDER BY p.product_name ASC");
  } else {
    $products = $db->query("SELECT p.id, p.product_name, p.price, c.category_name, p.product_image FROM products p LEFT JOIN categories c ON p.category = c.id WHERE p.deleted = '0' AND p.category IS NOT NULL ORDER BY p.product_name ASC");
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
      'img'       => $rowProducts['product_image']
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
          $pid   = $message[$j]['Products'][$k]['id'];
          $name  = htmlspecialchars($message[$j]['Products'][$k]['item_name']);
          $price = number_format((float) $message[$j]['Products'][$k]['price'], 2);
          $img   = $message[$j]['Products'][$k]['img'];

          $imgHtml = $img
            ? '<img src="php/viewPhoto.php?file=' . $img . '&type=file_table" class="product-img" loading="lazy">'
            : '<div class="product-img-placeholder"><i class="fas fa-box"></i></div>';

          echo '
          <div class="product-item" onclick="addItems(' . $pid . ')">
            <div class="product-card">
              ' . $imgHtml . '
              <div class="card-info">
                <p class="product-name">' . $name . '</p>
                <p class="product-price">RM ' . $price . '/kg</p>
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

        <div class="form-group mb-3">
          <label><?=$languageArray['payment_method_code'][$language]?><span class="text-danger">*</span></label>
          <select class="form-control" id="paymentMethod" name="paymentMethod" required>
            <option value="" selected disabled hidden>Please Select</option>
            <option value="e-wallet">e-wallet</option>
            <option value="cash">cash</option>
          </select>
        </div>
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

<script>
var size = 0;
var PAGE_SIZE = 6;
var orderItems = {}; // keyed by product id: {name, price, weight, total}

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

  // Submit
  $('#saleForm').on('submit', function(e) {
    e.preventDefault();
    $('#spinnerLoading').show();

    if ($('#TableId .order-row').length === 0) {
      toastr.warning('Please add items first.');
      return;
    }

    if (!$('#paymentMethod').val()) {
      toastr.warning('Please select a payment method.');
      return;
    }

    $.post('php/sales.php', $('#saleForm').serialize(), function(data){
      var obj = JSON.parse(data); 
      
      if(obj.status === 'success'){
        toastr["success"](obj.message, "Success:");
        $('#spinnerLoading').hide();
        $("a[href='#reportsPricingSales']").click();
      }
      else if(obj.status === 'failed'){
        toastr["error"](obj.message, "Failed:");
        $('#spinnerLoading').hide();
      }
      else{
        toastr["error"]("Something wrong when edit", "Failed:");
        $('#spinnerLoading').hide();
      }
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

function addOrderRow(id, name, price, uomLabel, weight) {
  var total = (parseFloat(price) * parseFloat(weight)).toFixed(2);
  var priceDisplay = parseFloat(price).toFixed(2) + (uomLabel ? ' / ' + uomLabel : '');

  var html =
    '<tr class="order-row" id="row_' + id + '" data-id="' + id + '" data-price="' + price + '" data-total="' + total + '">' +
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

  if (isNaN(wt) || wt <= 0) {
    removeRow(id);
    return;
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

function addItems(id) {
  $('#spinnerLoading').show();

  $.post('php/getProduct.php', { userID: id }, function(data) {
    var obj = JSON.parse(data);

    if (obj.status === 'success') {
      var p = obj.message;

      // Build display label: packaging name if set, else uom name, else price only
      var uomLabel = p.packaging_name || p.uom_name || '';

      if ($('#row_' + id).length) {
        changeQty(id, 1);
      } else {
        addOrderRow(id, p.product_name, p.price || 0, uomLabel, 0);
        recalc();
      }
    } else {
      toastr.error(obj.message, 'Failed:');
    }

    $('#spinnerLoading').hide();
  });
}
</script>
