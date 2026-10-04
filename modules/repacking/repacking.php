<?php
require_once '../../php/db_connect.php';
session_start();

if (!isset($_SESSION['userID'])) {
    echo '<script>window.location.href = "login.html";</script>';
    exit;
}

$company = $_SESSION['customer'];
$role = $_SESSION['role'];
$module = $_SESSION['module'];
$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];

// Categories for the source and target category filters
if ($role != 'SADMIN') {
  $categoryStmt = $db->prepare("SELECT id, category_name FROM categories WHERE deleted = 0 AND customer = ? AND module = ? ORDER BY category_name ASC");
  $categoryStmt->bind_param('is', $company, $module);
  $categoryStmt->execute();
  $categories = $categoryStmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $categoryStmt->close();

  // Same fallback as the product lists: no categories for this module, use all company categories
  if (empty($categories)) {
    $categoryStmt = $db->prepare("SELECT id, category_name FROM categories WHERE deleted = 0 AND customer = ? ORDER BY category_name ASC");
    $categoryStmt->bind_param('i', $company);
    $categoryStmt->execute();
    $categories = $categoryStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $categoryStmt->close();
  }
} else {
  $categories = $db->query("SELECT id, category_name FROM categories WHERE deleted = 0 ORDER BY category_name ASC")->fetch_all(MYSQLI_ASSOC);
}

// Products of those categories for the source and target product filters
$products = [];
$categoryIds = array_column($categories, 'id');
if (!empty($categoryIds)) {
  $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
  if ($role != 'SADMIN') {
    $productStmt = $db->prepare("SELECT id, product_name FROM products WHERE deleted = 0 AND customer = ? AND category IN ($placeholders) ORDER BY product_name ASC");
    $productStmt->bind_param('i' . str_repeat('i', count($categoryIds)), $company, ...$categoryIds);
  } else {
    $productStmt = $db->prepare("SELECT id, product_name FROM products WHERE deleted = 0 AND category IN ($placeholders) ORDER BY product_name ASC");
    $productStmt->bind_param(str_repeat('i', count($categoryIds)), ...$categoryIds);
  }
  $productStmt->execute();
  $products = $productStmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $productStmt->close();
}
?>

<div class="content-header" style="padding-bottom: 0;">
  <div class="container-fluid"></div>
</div>

<!-- Main content -->
<section class="content page-modern">
  <div class="container-fluid">
    <!-- Filter Card -->
    <div class="card filter-card">
      <div class="card-body">
        <div class="filter-row">
          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['from_date_code'][$language]?></label>
            <div class="input-group date" id="fromDatePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#fromDatePicker" id="fromDate"/>
              <div class="input-group-append" data-target="#fromDatePicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['to_date_code'][$language]?></label>
            <div class="input-group date" id="toDatePicker" data-target-input="nearest">
              <input type="text" class="form-control datetimepicker-input" data-target="#toDatePicker" id="toDate"/>
              <div class="input-group-append" data-target="#toDatePicker" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['product_type_code'][$language] ?? 'Product Type'?></label>
            <select class="form-control" id="typeFilter">
              <option value="" selected><?=$languageArray['all_code'][$language] ?? 'All'?></option>
              <option value="Local"><?=$languageArray['local_code'][$language] ?? 'Local'?></option>
              <option value="Export"><?=$languageArray['export_code'][$language] ?? 'Export'?></option>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['category_code'][$language]?></label>
            <select class="form-control select2-filter" id="categoryFilter">
              <option value=""><?=$languageArray['please_select_code'][$language]?></option>
              <?php foreach ($categories as $category) { ?>
                <option value="<?=$category['id']?>"><?=htmlspecialchars($category['category_name'])?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['source_product_code'][$language]?></label>
            <select class="form-control select2-filter" id="sourceProductFilter">
              <option value=""><?=$languageArray['please_select_code'][$language]?></option>
              <?php foreach ($products as $product) { ?>
                <option value="<?=$product['id']?>"><?=htmlspecialchars($product['product_name'])?></option>
              <?php } ?>
            </select>
          </div>
        </div>

        <div class="filter-row mt-3">
          <div class="filter-group">
            <label class="filter-label"><?=$languageArray['target_product_code'][$language]?></label>
            <select class="form-control select2-filter" id="targetProductFilter">
              <option value=""><?=$languageArray['please_select_code'][$language]?></option>
              <?php foreach ($products as $product) { ?>
                <option value="<?=$product['id']?>"><?=htmlspecialchars($product['product_name'])?></option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-group filter-group-action" style="margin-left:auto;">
            <label class="filter-label">&nbsp;</label>
            <button type="button" class="btn btn-filter btn-filter-primary" id="filterSearch">
              <i class="fas fa-search"></i> <?=$languageArray['search_code'][$language]?>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card results-card show-dt-controls">
          <div class="card-header">
            <div class="results-header-left">
              <h3 class="results-title"><i class="fas fa-box-open mr-2"></i><?=$languageArray['repacking_code'][$language]?></h3>
            </div>
            <div class="results-header-right d-flex flex-wrap" style="gap: 0.5rem;">
              <button type="button" class="btn btn-action btn-action-primary" id="addRepacking">
                <i class="fas fa-plus"></i> <?=$languageArray['add_code'][$language]?>
              </button>
            </div>
          </div>
          <div class="card-body">
            <table id="repackingTable" class="table data-table">
              <thead>
                <tr>
                  <th><?=$languageArray['repacking_no_code'][$language]?></th>
                  <th><?=$languageArray['date_code'][$language]?></th>
                  <th><?=$languageArray['product_type_code'][$language] ?? 'Product Type'?></th>
                  <th><?=$languageArray['source_product_code'][$language]?></th>
                  <th><?=$languageArray['weight_code'][$language]?> (kg)</th>
                  <th><?=$languageArray['target_products_packed_code'][$language]?></th>
                  <th><?=$languageArray['created_by_code'][$language]?></th>
                  <th width="10%"><?=$languageArray['actions_code'][$language]?></th>
                </tr>
              </thead>
            </table>
          </div><!-- /.card-body -->
        </div><!-- /.card -->
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</section><!-- /.content -->

<div class="modal fade modal-modern" id="repackingModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form role="form" id="repackingForm">
        <div class="modal-header">
          <h4 class="modal-title"><?=$languageArray['repacking_code'][$language]?></h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="id" name="id">

          <!-- Source Product -->
          <div class="modal-section">
            <div class="section-title"><i class="fas fa-box-open mr-2"></i><?=$languageArray['source_product_code'][$language]?></div>
            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <label class="form-label-modern"><?=$languageArray['date_code'][$language]?> <span class="text-danger">*</span></label>
                  <div class="input-group date" id="repackingDatePicker" data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input" data-target="#repackingDatePicker" id="repackingDate" name="repackingDate" required/>
                    <div class="input-group-append" data-target="#repackingDatePicker" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label class="form-label-modern"><?=$languageArray['repacking_no_code'][$language]?></label>
                  <input type="text" class="form-control" id="repackingNo" readonly>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label class="form-label-modern"><?=$languageArray['product_type_code'][$language] ?? 'Product Type'?></label>
                  <select class="form-control" id="productType" name="productType">
                    <option value="Local"><?=$languageArray['local_code'][$language] ?? 'Local'?></option>
                    <option value="Export"><?=$languageArray['export_code'][$language] ?? 'Export'?></option>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group mb-0">
                  <label class="form-label-modern"><?=$languageArray['category_code'][$language]?></label>
                  <select class="form-control select2" style="width:100%;" id="sourceCategory" name="sourceCategory">
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php foreach ($categories as $category) { ?>
                      <option value="<?=$category['id']?>"><?=htmlspecialchars($category['category_name'])?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-3">
                <div class="form-group mb-0">
                  <label class="form-label-modern"><?=$languageArray['product_bulk_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control select2" style="width:100%;" id="sourceProduct" name="sourceProduct" required>
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group mb-0">
                  <label class="form-label-modern"><?=$languageArray['grade_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control select2" style="width:100%;" id="sourceGrade" name="sourceGrade" required>
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group mb-0">
                  <label class="form-label-modern"><?=$languageArray['weight_to_deduct_code'][$language]?> <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" id="productWeight" name="productWeight" placeholder="<?=$languageArray['enter_weight_code'][$language]?>" step="0.01" min="0.01" required>
                </div>
              </div>
            </div>
          </div>

          <!-- Target Products -->
          <div class="modal-section">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="section-title mb-0"><i class="fas fa-boxes mr-2"></i><?=$languageArray['target_products_packed_code'][$language]?></div>
              <button type="button" class="btn btn-action btn-action-success" id="addRowBtn">
                <i class="fas fa-plus"></i> <?=$languageArray['add_new_code'][$language]?>
              </button>
            </div>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label class="form-label-modern"><?=$languageArray['category_code'][$language]?></label>
                  <select class="form-control select2" style="width:100%;" id="targetCategory" name="targetCategory">
                    <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
                    <?php foreach ($categories as $category) { ?>
                      <option value="<?=$category['id']?>"><?=htmlspecialchars($category['category_name'])?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
            </div>
            <table class="table table-bordered mb-0">
              <thead class="thead-light">
                <tr>
                  <th><?=$languageArray['product_code'][$language]?></th>
                  <th width="25%"><?=$languageArray['grade_code'][$language]?></th>
                  <th width="20%"><?=$languageArray['weight_code'][$language]?> (kg)</th>
                  <th width="60px"></th>
                </tr>
              </thead>
              <tbody id="repackTable"></tbody>
              <tfoot>
                <tr>
                  <th colspan="2" class="text-right"><?=$languageArray['total_weight_code'][$language]?></th>
                  <th id="totalTargetWeight">0</th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
          <button type="submit" class="btn btn-modern btn-modern-primary" id="saveBtn"><?=$languageArray['save_code'][$language]?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Row Template -->
<script type="text/html" id="rowTemplate">
<tr class="details">
  <td>
    <select class="form-control select2" style="width:100%;" id="targetProduct" name="targetProduct">
      <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
    </select>
  </td>
  <td>
    <select class="form-control select2" style="width:100%;" id="targetGrade" name="targetGrade">
      <option value="" selected disabled hidden><?=$languageArray['please_select_code'][$language]?></option>
    </select>
  </td>
  <td>
    <input type="number" class="form-control" id="itemWeight" name="itemWeight" placeholder="<?=$languageArray['enter_weight_code'][$language]?>" step="0.01" min="0.01" required>
  </td>
  <td class="text-center">
    <button type="button" class="btn btn-sm btn-outline-danger" id="removeBtn" title="Remove"><i class="fas fa-times"></i></button>
  </td>
</tr>
</script>

<script>
var apiUrl = 'php/modules/repacking/api.php';
var lang = <?= json_encode([
  'pleaseSelect' => $languageArray['please_select_code'][$language],
  'addTargetProduct' => $languageArray['add_target_product_code'][$language],
  'targetWeightMismatch' => $languageArray['target_weight_mismatch_code'][$language],
  'confirmDelete' => $languageArray['confirm_delete_repacking_code'][$language],
  'somethingWentWrong' => $languageArray['something_went_wrong_code'][$language]
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
var table;
var rowIndex = 0;
var sourceProducts = [];
var targetProducts = [];

$(function () {
  $('#repackingModal .select2').select2({
    allowClear: true,
    placeholder: lang.pleaseSelect,
    dropdownParent: $('#repackingModal')
  });

  $('#fromDatePicker, #toDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY',
    defaultDate: new Date()
  });

  $('.select2-filter').each(function () {
    $(this).select2({
      allowClear: true,
      placeholder: lang.pleaseSelect,
      width: '100%',
      dropdownParent: $(this).parent()
    });
  });

  $('#repackingDatePicker').datetimepicker({
    icons: { time: 'far fa-clock' },
    format: 'DD/MM/YYYY'
  });

  table = $('#repackingTable').DataTable({
    'responsive': true,
    'autoWidth': false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'order': [[1, 'desc']],
    'language': {
      'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
      'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
    },
    'ajax': {
      'url': apiUrl,
      'data': function (d) {
        d.action = 'list';
        d.fromDate = $('#fromDate').val();
        d.toDate = $('#toDate').val();
        d.typeFilter = $('#typeFilter').val();
        d.categoryFilter = $('#categoryFilter').val();
        d.sourceProductFilter = $('#sourceProductFilter').val();
        d.targetProductFilter = $('#targetProductFilter').val();
      }
    },
    'columns': [
      { data: 'repacking_no' },
      { data: 'repacking_date' },
      { data: 'type' },
      { data: 'source_product_name' },
      { data: 'source_weight' },
      { data: 'targets', orderable: false },
      { data: 'created_by_name' },
      {
        data: 'id',
        orderable: false,
        render: function (data, type, row) {
          return '<div class="d-flex" style="gap:4px;"><button type="button" onclick="edit(' + data + ')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button><button type="button" onclick="deactivate(' + data + ')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button></div>';
        }
      }
    ]
  });

  $('#filterSearch').on('click', function () {
    table.ajax.reload();
  });

  $('#addRepacking').on('click', function () {
    openModal(null);
  });

  // Filter source products by category
  $('#sourceCategory').on('change', function () {
    fillSourceProducts(null);
  });

  // Filter all target product rows by category
  $('#targetCategory').on('change', function () {
    $('#repackTable select[id^="targetProduct"]').each(function () {
      fillTargetProducts($(this));
    });
  });

  // Refresh grades when the type changes
  $('#productType').on('change', function () {
    fillGrades($('#sourceGrade'), findProduct(sourceProducts, $('#sourceProduct').val()));
    $('#repackTable .details').each(function () {
      fillGrades($(this).find('select[id^="targetGrade"]'), findProduct(targetProducts, $(this).find('select[id^="targetProduct"]').val()));
    });
  });

  // Target row: refresh grades when its product changes
  $('#repackTable').on('change', 'select[id^="targetProduct"]', function () {
    fillGrades($(this).closest('.details').find('select[id^="targetGrade"]'), findProduct(targetProducts, $(this).val()));
  });

  // Refresh grades when source product changes
  $('#sourceProduct').on('change', function () {
    fillGrades($('#sourceGrade'), findProduct(sourceProducts, $(this).val()));
  });

  $('#addRowBtn').on('click', function () {
    addTargetRow(null, null, null);
  });

  $('#repackTable').on('click', 'button[id^="removeBtn"]', function () {
    $(this).closest('.details').remove();
    updateTotalWeight();
  });

  $('#repackTable').on('input', 'input[id^="itemWeight"]', function () {
    updateTotalWeight();
  });

  $('#repackingForm').validate({
    errorElement: 'span',
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback');
      element.closest('.form-group, td').append(error);
    },
    highlight: function (element) { $(element).addClass('is-invalid'); },
    unhighlight: function (element) { $(element).removeClass('is-invalid'); },
    submitHandler: function () {
      if ($('#repackTable .details').length === 0) {
        toastr['error'](lang.addTargetProduct, 'Failed:');
        return;
      }

      var weight = parseFloat($('#productWeight').val()) || 0;

      var totalTargetWeight = getTotalTargetWeight();
      if (Math.round(totalTargetWeight * 100) !== Math.round(weight * 100)) {
        toastr['error'](lang.targetWeightMismatch + ' (' + totalTargetWeight.toFixed(2) + ' / ' + weight.toFixed(2) + ' kg)', 'Failed:');
        return;
      }

      $('#spinnerLoading').show();
      $('#saveBtn').prop('disabled', true);
      $.post(apiUrl, $('#repackingForm').serialize() + '&action=save', function (obj) {
        if (obj.status === 'success') {
          $('#repackingModal').modal('hide');
          toastr['success'](obj.message, 'Success:');
          table.ajax.reload();
        } else {
          toastr['error'](obj.message, 'Failed:');
        }
      }).fail(function () {
        toastr['error'](lang.somethingWentWrong, 'Failed:');
      }).always(function () {
        $('#spinnerLoading').hide();
        $('#saveBtn').prop('disabled', false);
      });
    }
  });
});

function openModal(record) {
  $('#spinnerLoading').show();
  $.post(apiUrl, { action: 'options' }, function (obj) {
    if (obj.status !== 'success') {
      toastr['error'](obj.message, 'Failed:');
      return;
    }

    sourceProducts = obj.message.sources;
    targetProducts = obj.message.targets;

    // Edited record's products may no longer be listed, so make sure they are selectable
    if (record) {
      if (!findProduct(sourceProducts, record.source_product)) {
        sourceProducts.push({
          id: record.source_product,
          product_name: record.source_product_name,
          category_id: record.source_category_id,
          category_name: record.source_category_name,
          grades: record.source_grades
        });
      }
      $.each(record.items, function (i, item) {
        if (!findProduct(targetProducts, item.product_id)) {
          targetProducts.push({ id: item.product_id, product_name: item.product_name, category_id: item.category_id, category_name: item.category_name, grades: item.grades });
        }
      });
    }

    resetForm();
    $('#sourceCategory').val(record && record.source_category ? String(record.source_category) : '').trigger('change.select2');
    $('#targetCategory').val(record && record.target_category ? String(record.target_category) : '').trigger('change.select2');
    $('#productType').val(record ? record.type : 'Local');
    $('#repackingNo').val(record ? record.repacking_no : '');
    $('#repackingDatePicker').datetimepicker('date', record ? moment(record.repacking_date, 'DD/MM/YYYY') : moment());
    fillSourceProducts(record ? record.source_product : null);

    if (record) {
      $('#id').val(record.id);
      $('#sourceGrade').val(String(record.source_grade)).trigger('change.select2');
      $('#productWeight').val(record.source_weight);
      $.each(record.items, function (i, item) {
        addTargetRow(item.product_id, item.grade_id, item.weight);
      });
    }

    $('#repackingModal').modal('show');
  }).fail(function () {
    toastr['error'](lang.somethingWentWrong, 'Failed:');
  }).always(function () {
    $('#spinnerLoading').hide();
  });
}

function edit(id) {
  $('#spinnerLoading').show();
  $.post(apiUrl, { action: 'get', id: id }, function (obj) {
    if (obj.status === 'success') {
      openModal(obj.message);
    } else {
      toastr['error'](obj.message, 'Failed:');
      $('#spinnerLoading').hide();
    }
  }).fail(function () {
    toastr['error'](lang.somethingWentWrong, 'Failed:');
    $('#spinnerLoading').hide();
  });
}

function deactivate(id) {
  if (!confirm(lang.confirmDelete)) {
    return;
  }

  $('#spinnerLoading').show();
  $.post(apiUrl, { action: 'delete', id: id }, function (obj) {
    if (obj.status === 'success') {
      toastr['success'](obj.message, 'Success:');
      table.ajax.reload();
    } else {
      toastr['error'](obj.message, 'Failed:');
    }
  }).fail(function () {
    toastr['error'](lang.somethingWentWrong, 'Failed:');
  }).always(function () {
    $('#spinnerLoading').hide();
  });
}

function resetForm() {
  $('#repackingForm')[0].reset();
  $('#repackingForm').validate().resetForm();
  $('#repackingForm').find('.is-invalid').removeClass('is-invalid');
  $('#id').val('');
  $('#repackTable').empty();
  rowIndex = 0;
  updateTotalWeight();
}

function addTargetRow(productId, gradeId, weight) {
  var $tpl = $('#rowTemplate').clone();
  $('#repackTable').append($tpl.html());

  var $row = $('#repackTable .details:last');
  $row.attr('id', 'detail' + rowIndex).attr('data-index', rowIndex);
  $row.find('#targetProduct').attr('name', 'itemsRepack[' + rowIndex + ']').attr('id', 'targetProduct' + rowIndex).attr('required', true);
  $row.find('#targetGrade').attr('name', 'itemGrade[' + rowIndex + ']').attr('id', 'targetGrade' + rowIndex).attr('required', true);
  $row.find('#itemWeight').attr('name', 'itemWeight[' + rowIndex + ']').attr('id', 'itemWeight' + rowIndex).attr('required', true);
  $row.find('#removeBtn').attr('id', 'removeBtn' + rowIndex);

  var $grade = $row.find('#targetGrade' + rowIndex);
  $grade.select2({
    allowClear: true,
    placeholder: lang.pleaseSelect,
    dropdownParent: $('#repackingModal')
  });

  var $select = $row.find('#targetProduct' + rowIndex);
  fillTargetProducts($select);

  $select.select2({
    allowClear: true,
    placeholder: lang.pleaseSelect,
    dropdownParent: $('#repackingModal')
  });

  if (productId) {
    // Make sure the saved product shows even if it is outside the current category filter
    if (!$select.find('option[value="' + productId + '"]').length) {
      var product = findProduct(targetProducts, productId);
      $select.append($('<option>').val(productId).text(product ? product.product_name : productId));
    }
    $select.val(String(productId)).trigger('change');
  }
  if (gradeId) {
    $grade.val(String(gradeId)).trigger('change.select2');
  }
  if (weight) {
    $row.find('#itemWeight' + rowIndex).val(weight);
  }

  rowIndex++;
  updateTotalWeight();
}

function fillSourceProducts(selectedId) {
  var category = $('#sourceCategory').val();
  var $select = $('#sourceProduct');

  $select.empty().append($('<option value="" selected disabled hidden>').text(lang.pleaseSelect));
  $.each(sourceProducts, function (i, product) {
    if (category && String(product.category_id) !== String(category) && String(product.id) !== String(selectedId)) {
      return;
    }
    $select.append($('<option>').val(product.id).text(product.product_name));
  });

  $select.val(selectedId ? String(selectedId) : '').trigger('change');
}

function fillTargetProducts($select) {
  var category = $('#targetCategory').val();
  var current = $select.val();

  $select.empty().append($('<option value="" selected disabled hidden>').text(lang.pleaseSelect));
  $.each(targetProducts, function (i, product) {
    if (category && String(product.category_id) !== String(category) && String(product.id) !== String(current)) {
      return;
    }
    $select.append($('<option>').val(product.id).text(product.product_name));
  });

  $select.val(current ? String(current) : '').trigger('change');
}

function fillGrades($select, product) {
  var type = $('#productType').val();
  var current = $select.val();

  $select.empty().append($('<option value="" selected disabled hidden>').text(lang.pleaseSelect));
  if (product && product.grades) {
    $.each(product.grades, function (i, grade) {
      if (grade.type === type) {
        $select.append($('<option>').val(grade.grade_id).text(grade.grade_name));
      }
    });
  }

  // Keep current grade if it is still valid for this product and type
  if (current && $select.find('option[value="' + current + '"]').length) {
    $select.val(current);
  } else {
    $select.val('');
  }
  $select.trigger('change.select2');
}

function getTotalTargetWeight() {
  var total = 0;
  $('#repackTable input[id^="itemWeight"]').each(function () {
    total += parseFloat($(this).val()) || 0;
  });
  return Math.round(total * 100) / 100;
}

function updateTotalWeight() {
  $('#totalTargetWeight').text(getTotalTargetWeight().toFixed(2));
}

function findProduct(products, id) {
  var found = null;
  $.each(products, function (i, product) {
    if (String(product.id) === String(id)) {
      found = product;
      return false;
    }
  });
  return found;
}
</script>
