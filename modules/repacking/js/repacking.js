var apiUrl = 'php/modules/repacking/api.php';
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
    'language': repackingTableLanguage,
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
        responsivePriority: 1,
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
