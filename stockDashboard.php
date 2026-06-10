<?php
require_once 'php/db_connect.php';
require_once 'php/lookup.php';
session_start();

if (!isset($_SESSION['userID'])) {
    echo '<script>window.location.href = "login.html";</script>';
    exit;
}

$company  = $_SESSION['customer'];
$role     = $_SESSION['role'] ?? 'NORMAL';
$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];

$companyFilter = ($role != 'SADMIN') ? "AND company = '$company'" : '';

// Load filter dropdowns
$categoryFilter = ($role != 'SADMIN') ? "AND p.customer = '$company'" : '';
$categories = $db->query("SELECT DISTINCT c.id, c.category_name FROM categories c INNER JOIN products p ON p.category = c.id WHERE c.deleted = 0 AND p.deleted = 0 $categoryFilter ORDER BY c.category_name ASC");
$products   = $db->query("SELECT id, product_name, category FROM products WHERE deleted = 0 " . (($role != 'SADMIN') ? "AND customer = '$company'" : '') . " ORDER BY product_name ASC");
$grades     = $db->query("SELECT id, units FROM grades WHERE deleted = 0 " . (($role != 'SADMIN') ? "AND customer = '$company'" : '') . " ORDER BY units ASC");
$packaging  = $db->query("SELECT id, packaging_name FROM packaging WHERE deleted = 0 ORDER BY packaging_name ASC");

// Summary totals
$totalRaw     = $db->query("SELECT COALESCE(SUM(balance),0) as total FROM raw_stock_balance WHERE deleted=0 $companyFilter")->fetch_assoc()['total'];
$totalGraded  = $db->query("SELECT COALESCE(SUM(balance),0) as total FROM grading_stock_balance WHERE deleted=0 $companyFilter")->fetch_assoc()['total'];
$totalBoxes   = $db->query("SELECT COALESCE(SUM(box_quantity),0) as total FROM stock_balances WHERE deleted=0 AND box_quantity > 0 $companyFilter")->fetch_assoc()['total'];
?>

<style>
.stock-filter { background:#f8f9fa; border-radius:6px; padding:10px 15px; margin-bottom:10px; }
.stock-table thead th { background:#343a40; color:#fff; white-space:nowrap; }
.stock-table tfoot td { background:#e9ecef; font-weight:600; }
.section-card { border:none; box-shadow:0 1px 4px rgba(0,0,0,.12); }
.section-card .card-header { font-weight:600; font-size:1rem; }
.summary-val { font-size:1.6rem; font-weight:700; }
</style>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-8">
        <h1 class="m-0 text-dark">Stock Dashboard</h1>
      </div>
      <div class="col-sm-4 text-right">
        <button class="btn btn-success btn-sm" id="exportBtn"><i class="fas fa-file-excel mr-1"></i>Export Excel</button>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">

    <!-- Summary Cards -->
    <div class="row mb-3">
      <div class="col-md-4">
        <div class="small-box bg-info">
          <div class="inner">
            <p class="summary-val" id="summaryRaw"><?=number_format($totalRaw,2)?> kg</p>
            <p>Total Raw Stock</p>
          </div>
          <div class="icon"><i class="fas fa-boxes"></i></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="small-box bg-warning">
          <div class="inner">
            <p class="summary-val" id="summaryGraded"><?=number_format($totalGraded,2)?> kg</p>
            <p>Total Graded Stock</p>
          </div>
          <div class="icon"><i class="fas fa-layer-group"></i></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="small-box bg-success">
          <div class="inner">
            <p class="summary-val" id="summaryPacked"><?=number_format($totalBoxes)?> boxes</p>
            <p>Total Packed Stock</p>
          </div>
          <div class="icon"><i class="fas fa-box"></i></div>
        </div>
      </div>
    </div>

    <!-- Three Sections -->
    <div class="row">

      <!-- RAW MATERIAL -->
      <div class="col-md-4">
        <div class="card section-card">
          <div class="card-header bg-info color-palette">
            <i class="fas fa-boxes mr-1"></i> Raw Material Stock
          </div>
          <div class="card-body p-2">
            <div class="stock-filter">
              <div class="form-row">
                <div class="col">
                  <select class="form-control form-control-sm select2-filter" id="rawCategoryFilter">
                    <option value="">All Categories</option>
                    <?php $categories->data_seek(0); while($r=$categories->fetch_assoc()): ?>
                      <option value="<?=$r['id']?>"><?=htmlspecialchars($r['category_name'])?></option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <div class="col">
                  <select class="form-control form-control-sm select2-filter" id="rawProductFilter">
                    <option value="">All Products</option>
                    <?php $products->data_seek(0); while($r=$products->fetch_assoc()): ?>
                      <option value="<?=$r['id']?>" data-category="<?=$r['category']?>"><?=htmlspecialchars($r['product_name'])?></option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <div class="col">
                  <select class="form-control form-control-sm select2-filter" id="rawGradeFilter">
                    <option value="">All Grades</option>
                    <?php $grades->data_seek(0); while($r=$grades->fetch_assoc()): ?>
                      <option value="<?=$r['id']?>"><?=htmlspecialchars($r['units'])?></option>
                    <?php endwhile; ?>
                  </select>
                </div>
              </div>
            </div>
            <table class="table table-sm table-bordered stock-table" id="rawTable">
              <thead>
                <tr>
                  <th>Category</th>
                  <th>Product</th>
                  <th>Grade</th>
                  <th class="text-right">Balance (kg)</th>
                </tr>
              </thead>
              <tbody id="rawBody"></tbody>
              <tfoot>
                <tr>
                  <td colspan="3">Total</td>
                  <td class="text-right" id="rawTotal">0.00</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <!-- GRADED STOCK -->
      <div class="col-md-4">
        <div class="card section-card">
          <div class="card-header bg-warning color-palette">
            <i class="fas fa-layer-group mr-1"></i> Graded Stock
          </div>
          <div class="card-body p-2">
            <div class="stock-filter">
              <div class="form-row">
                <div class="col">
                  <select class="form-control form-control-sm select2-filter" id="gradedCategoryFilter">
                    <option value="">All Categories</option>
                    <?php $categories->data_seek(0); while($r=$categories->fetch_assoc()): ?>
                      <option value="<?=$r['id']?>"><?=htmlspecialchars($r['category_name'])?></option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <div class="col">
                  <select class="form-control form-control-sm select2-filter" id="gradedProductFilter">
                    <option value="">All Products</option>
                    <?php $products->data_seek(0); while($r=$products->fetch_assoc()): ?>
                      <option value="<?=$r['id']?>" data-category="<?=$r['category']?>"><?=htmlspecialchars($r['product_name'])?></option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <div class="col">
                  <select class="form-control form-control-sm select2-filter" id="gradedGradeFilter">
                    <option value="">All Grades</option>
                    <?php $grades->data_seek(0); while($r=$grades->fetch_assoc()): ?>
                      <option value="<?=$r['id']?>"><?=htmlspecialchars($r['units'])?></option>
                    <?php endwhile; ?>
                  </select>
                </div>
              </div>
            </div>
            <table class="table table-sm table-bordered stock-table" id="gradedTable">
              <thead>
                <tr>
                  <th>Category</th>
                  <th>Product</th>
                  <th>Grade</th>
                  <th class="text-right">Balance (kg)</th>
                </tr>
              </thead>
              <tbody id="gradedBody"></tbody>
              <tfoot>
                <tr>
                  <td colspan="3">Total</td>
                  <td class="text-right" id="gradedTotal">0.00</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <!-- PACKED STOCK -->
      <div class="col-md-4">
        <div class="card section-card">
          <div class="card-header bg-success color-palette">
            <i class="fas fa-box mr-1"></i> Packed Stock
          </div>
          <div class="card-body p-2">
            <div class="stock-filter">
              <div class="form-row">
                <div class="col">
                  <select class="form-control form-control-sm select2-filter" id="packedCategoryFilter">
                    <option value="">All Categories</option>
                    <?php $categories->data_seek(0); while($r=$categories->fetch_assoc()): ?>
                      <option value="<?=$r['id']?>"><?=htmlspecialchars($r['category_name'])?></option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <div class="col">
                  <select class="form-control form-control-sm select2-filter" id="packedProductFilter">
                    <option value="">All Products</option>
                    <?php $products->data_seek(0); while($r=$products->fetch_assoc()): ?>
                      <option value="<?=$r['id']?>" data-category="<?=$r['category']?>"><?=htmlspecialchars($r['product_name'])?></option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <div class="col">
                  <select class="form-control form-control-sm select2-filter" id="packedGradeFilter">
                    <option value="">All Grades</option>
                    <?php $grades->data_seek(0); while($r=$grades->fetch_assoc()): ?>
                      <option value="<?=$r['id']?>"><?=htmlspecialchars($r['units'])?></option>
                    <?php endwhile; ?>
                  </select>
                </div>
              </div>
            </div>
            <table class="table table-sm table-bordered stock-table" id="packedTable">
              <thead>
                <tr>
                  <th>Category</th>
                  <th>Product</th>
                  <th>Grade</th>
                  <th>Packaging</th>
                  <th class="text-right">Boxes</th>
                </tr>
              </thead>
              <tbody id="packedBody"></tbody>
              <tfoot>
                <tr>
                  <td colspan="4">Total</td>
                  <td class="text-right" id="packedTotal">0</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

    </div><!-- end row -->
  </div>
</div>

<script>
$(function() {
  $('.select2-filter').select2({ allowClear: true, placeholder: 'All', width: '100%' });

  // Category → filter product dropdown
  function bindCategoryProductFilter(categoryId, productId) {
    $('#' + categoryId).on('change', function() {
      var cat = $(this).val();
      $('#' + productId + ' option').each(function() {
        if (!$(this).val()) return;
        $(this).toggle(!cat || $(this).data('category') == cat);
      });
      $('#' + productId).val('').trigger('change.select2');
    });
  }
  bindCategoryProductFilter('rawCategoryFilter',    'rawProductFilter');
  bindCategoryProductFilter('gradedCategoryFilter', 'gradedProductFilter');
  bindCategoryProductFilter('packedCategoryFilter', 'packedProductFilter');

  // Load all on init
  loadRaw();
  loadGraded();
  loadPacked();

  // Filter change handlers
  $('#rawCategoryFilter, #rawProductFilter, #rawGradeFilter').on('change', loadRaw);
  $('#gradedCategoryFilter, #gradedProductFilter, #gradedGradeFilter').on('change', loadGraded);
  $('#packedCategoryFilter, #packedProductFilter, #packedGradeFilter').on('change', loadPacked);

  function loadRaw() {
    $.post('php/stock/getStockDashboard.php', {
      type: 'raw',
      category: $('#rawCategoryFilter').val(),
      product:  $('#rawProductFilter').val(),
      grade:    $('#rawGradeFilter').val()
    }, function(data) {
      var obj = JSON.parse(data);
      var tbody = $('#rawBody').empty();
      var total = 0;
      if (obj.status === 'success' && obj.data.length) {
        obj.data.forEach(function(r) {
          tbody.append('<tr><td>' + r.category_name + '</td><td>' + r.product_name + '</td><td>' + r.grade_name + '</td><td class="text-right">' + parseFloat(r.balance).toFixed(2) + '</td></tr>');
          total += parseFloat(r.balance);
        });
      } else {
        tbody.append('<tr><td colspan="4" class="text-center text-muted">No data</td></tr>');
      }
      $('#rawTotal').text(total.toFixed(2));
      $('#summaryRaw').text(total.toFixed(2) + ' kg');
    });
  }

  function loadGraded() {
    $.post('php/stock/getStockDashboard.php', {
      type: 'graded',
      category: $('#gradedCategoryFilter').val(),
      product:  $('#gradedProductFilter').val(),
      grade:    $('#gradedGradeFilter').val()
    }, function(data) {
      var obj = JSON.parse(data);
      var tbody = $('#gradedBody').empty();
      var total = 0;
      if (obj.status === 'success' && obj.data.length) {
        obj.data.forEach(function(r) {
          tbody.append('<tr><td>' + r.category_name + '</td><td>' + r.product_name + '</td><td>' + r.grade_name + '</td><td class="text-right">' + parseFloat(r.balance).toFixed(2) + '</td></tr>');
          total += parseFloat(r.balance);
        });
      } else {
        tbody.append('<tr><td colspan="4" class="text-center text-muted">No data</td></tr>');
      }
      $('#gradedTotal').text(total.toFixed(2));
      $('#summaryGraded').text(total.toFixed(2) + ' kg');
    });
  }

  function loadPacked() {
    $.post('php/stock/getStockDashboard.php', {
      type: 'packed',
      category: $('#packedCategoryFilter').val(),
      product:  $('#packedProductFilter').val(),
      grade:    $('#packedGradeFilter').val()
    }, function(data) {
      var obj = JSON.parse(data);
      var tbody = $('#packedBody').empty();
      var total = 0;
      if (obj.status === 'success' && obj.data.length) {
        obj.data.forEach(function(r) {
          tbody.append('<tr><td>' + r.category_name + '</td><td>' + r.product_name + '</td><td>' + r.grade_name + '</td><td>' + r.packaging_name + '</td><td class="text-right">' + r.box_quantity + '</td></tr>');
          total += parseInt(r.box_quantity);
        });
      } else {
        tbody.append('<tr><td colspan="5" class="text-center text-muted">No data</td></tr>');
      }
      $('#packedTotal').text(total);
      $('#summaryPacked').text(total + ' boxes');
    });
  }

  // Export to Excel - 3 sheets
  $('#exportBtn').on('click', function() {
    var wb = XLSX.utils.book_new();

    // Sheet 1 - Raw
    var rawData = [['Category','Product','Grade','Balance (kg)']];
    $('#rawBody tr').each(function() {
      var cells = $(this).find('td');
      if (cells.length === 4) rawData.push([cells.eq(0).text(), cells.eq(1).text(), cells.eq(2).text(), cells.eq(3).text()]);
    });
    rawData.push(['','','Total', $('#rawTotal').text()]);
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(rawData), 'Raw Material');

    // Sheet 2 - Graded
    var gradedData = [['Category','Product','Grade','Balance (kg)']];
    $('#gradedBody tr').each(function() {
      var cells = $(this).find('td');
      if (cells.length === 4) gradedData.push([cells.eq(0).text(), cells.eq(1).text(), cells.eq(2).text(), cells.eq(3).text()]);
    });
    gradedData.push(['','','Total', $('#gradedTotal').text()]);
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(gradedData), 'Graded Stock');

    // Sheet 3 - Packed
    var packedData = [['Category','Product','Grade','Packaging Size','Boxes']];
    $('#packedBody tr').each(function() {
      var cells = $(this).find('td');
      if (cells.length === 5) packedData.push([cells.eq(0).text(), cells.eq(1).text(), cells.eq(2).text(), cells.eq(3).text(), cells.eq(4).text()]);
    });
    packedData.push(['','','','Total', $('#packedTotal').text()]);
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(packedData), 'Packed Stock');

    XLSX.writeFile(wb, 'Stock_Dashboard_' + new Date().toISOString().slice(0,10) + '.xlsx');
  });
});
</script>
