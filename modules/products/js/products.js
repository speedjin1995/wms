var customerRowCount = $("#customerCards").find(".details").length;
var gradeRowCount = $("#gradeTable").find(".details").length;
var supplierRowCount = $("#supplierCards").find(".details").length;

$(function () {
  $('#selectAllCheckbox').on('change', function() {
    var checkboxes = $('#productTable tbody input[type="checkbox"]');
    checkboxes.prop('checked', $(this).prop('checked')).trigger('change');
  });

  $('#productColourPicker').colorpicker({
    format: 'hex',
    useAlpha: false
  });

  $('.select2').each(function() {
    $(this).select2({
        allowClear: true,
        placeholder: "Please Select",
        // Conditionally set dropdownParent based on the element’s location
        dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal') : undefined
    });
  });
  
  $("#productTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'language': productsTableLanguage,
    'ajax': {
      'url':'php/modules/products/api.php',
      'data': {
        action: 'list',
        id: productsCompany
      }
    },
    'columns': [
      {
        data: 'id',
        className: 'select-checkbox',
        orderable: false,
        render: function (data, type, row) {
            return '<input type="checkbox" class="select-checkbox" id="checkbox_' + data + '" value="'+data+'"/>';
        }
      },
      { data: 'category_name' },
      { data: 'product_code' },
      { data: 'product_name' },
      { data: 'weight' },
      { data: 'remark' },
      { 
        data: 'id',
        responsivePriority: 1,
        render: function ( data, type, row ) {
          return '<div class="d-flex" style="gap:4px;">'
            + '<button type="button" onclick="edit('+data+')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button>'
            + '<button type="button" onclick="openCustomers('+data+')" class="btn btn-sm btn-outline-info" title="Customers"><i class="fas fa-users"></i></button>'
            + '<button type="button" onclick="deactivate('+data+')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>'
            + '</div>';
        }
      }
    ],
    "rowCallback": function( row, data, index ) {
      if (data.is_manual == 'Y') {
        $(row).css('background-color', '#f8d7da');
      }
    },        
  });
    
  $('#productImageDropzone').on('click', function(e){
    if (!$(e.target).is('input')) $('#productImage').click();
  });

  $('#productImageDropzone').on('dragover', function(e){
    e.preventDefault();
    $(this).css({'border-color':'#007bff', 'background':'#e8f0fe'});
  }).on('dragleave', function(e){
    e.preventDefault();
    $(this).css({'border-color':'#adb5bd', 'background':'#fff'});
  }).on('drop', function(e){
    e.preventDefault();
    $(this).css({'border-color':'#adb5bd', 'background':'#fff'});
    var file = e.originalEvent.dataTransfer.files[0];
    if (file) setProductImagePreview(file);
  });

  $('#productImage').on('change', function(){
    if (this.files[0]) setProductImagePreview(this.files[0]);
  });

  $('#removeProductImage').on('click', function(){
    $('#productImage').val('');
    $('#productImageThumb').attr('src', '');
    $('#productImagePreview').hide();
    $('#productImagePlaceholder').show();
  });

  $.validator.setDefaults({
    submitHandler: function () {
      var gradeError = false;
      $('#gradeLocalRowsContainer .dynamic-card, #gradeExportRowsContainer .dynamic-card').each(function() {
        var index = $(this).data('index');
        var $select = $('#gradesRow'+index);
        if (!$select.val()) {
          gradeError = true;
          $select.next('.select2-container').find('.select2-selection').css({'border': '1px solid #dc3545'});
        } else {
          $select.next('.select2-container').find('.select2-selection').css({'border': ''});
        }
      });
      if (gradeError) {
        toastr["error"]("Please select a unit for all grade rows.", "Failed:");
        return false;
      }
      $('#spinnerLoading').show();
      var formData = new FormData($('#productForm')[0]);
      formData.append('action', 'save');
      $.ajax({
        url: 'php/modules/products/api.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(obj){
          if(obj.status === 'success'){
            $('#productModal').modal('hide');
            toastr["success"](obj.message, "Success:");
            $('#productTable').DataTable().ajax.reload();
            $('#spinnerLoading').hide();
          } else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
            $('#spinnerLoading').hide();
          } else {
            toastr["error"]("Something wrong when edit", "Failed:");
            $('#spinnerLoading').hide();
          }
        }
      });
    }
  });

  $('#addProducts').on('click', function(){
    $('#productModal').find('#id').val("");
    $('#productModal').find('#code').val("");
    $('#productModal').find('#product').val("");
    $('#productModal').find('#remark').val("");
    $('#productModal').find('#pricingType').val("Float");
    $('#productModal').find('#pricingCurrency').val(defaultCurrencyId).trigger('change');
    $('#productModal').find('#price').val("0.00");
    $('#productModal').find('#purchasingPricingType').val("Float");
    $('#productModal').find('#purchasingPricingCurrency').val(defaultCurrencyId).trigger('change');
    $('#productModal').find('#purchasingPrice').val("0.00");
    $('#productModal').find('#weight').val("");
    $('#productModal').find('#productCategory').val("").trigger('change');
    $('#productModal').find('#productPackaging').val("").trigger('change');
    $('#productModal').find('#productColour').val("");
    $('#productModal').find('#state').val("").trigger('change');
    $('#productModal').find('#uom').val("").trigger('change');
    setRangeSet(0);
    $('#okWeight').val(''); $('#okWeightUnit').val('kg');
    $('#loWeight').val(''); $('#loWeightUnit').val('kg');
    $('#hiWeight').val(''); $('#hiWeightUnit').val('kg');
    $('#productImage').val('');
    $('#productImagePreview').hide();
    $('#productImageThumb').attr('src', '');
    $('#productImagePlaceholder').show();

    // clear grade table and rows
    gradeRowCount = 0;
    $('#gradeTable').html('');
    $('#gradeLocalRowsContainer .dynamic-card').remove();
    $('#gradeExportRowsContainer .dynamic-card').remove();
    $('#gradeLocalEmptyState').show();
    $('#gradeExportEmptyState').show();

    $('#modalTitle').text(productsText.addTitle);
    $('#productModal').modal('show');
    
    $('#productForm').validate({
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

  $('#uploadProduct').on('click', function(){
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
        url: 'php/modules/products/api.php?action=upload',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(obj) {
            if (obj.status === 'success') {
              $('#spinnerLoading').hide();
              $('#uploadModal').modal('hide');
              $('#productTable').DataTable().ajax.reload();
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

    $("#productTable tbody input[type='checkbox']").each(function () {
      if (this.checked) {
          selectedIds.push($(this).val());
      }
    });

    if (selectedIds.length > 0) {
      if (confirm('Are you sure you want to cancel these items?')) {
          $.post('php/modules/products/api.php', {action: 'delete', ids: selectedIds}, function(obj){

              if(obj.status === 'success'){
                $('#productTable').DataTable().ajax.reload();
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
        alert("Please select at least one product to delete.");
        $('#spinnerLoading').hide();
    }     
  });

  // Find and remove selected customer cards
  $("#customerCards").on('click', 'button[id^="remove"]', function () {
    $(this).closest('.cs-card').remove();
    updateCustomerNumbers();
    toggleCustomerEmptyState();
  });

  function updateCustomerNumbers() {
    $("#customerCards .cs-card").each(function (index) {
      $(this).find('.cs-card-number').text(index + 1);
      $(this).find('input[name^="no"]').val(index + 1);
    });
  }

  function toggleCustomerEmptyState() {
    var filter = $('#customerTypeFilter').val();
    var visibleCount = filter ? $('#customerCards .cs-card[data-type="'+filter+'"]').length : $('#customerCards .cs-card').length;
    $('#customerEmptyState').toggle(visibleCount === 0);
  }

  // Filter customers by type
  $('#customerTypeFilter').on('change', function() {
    var filter = $(this).val();
    if (filter) {
      $('#customerCards .cs-card').hide();
      $('#customerCards .cs-card[data-type="'+filter+'"]').show();
    } else {
      $('#customerCards .cs-card').show();
    }
    toggleCustomerEmptyState();
  });

  // Update card data-type when type dropdown changes
  $('#customerCards').on('change', '.customer-type-select', function() {
    $(this).closest('.cs-card').attr('data-type', $(this).val());
    var filter = $('#customerTypeFilter').val();
    if (filter && $(this).val() !== filter) {
      $(this).closest('.cs-card').hide();
    }
    toggleCustomerEmptyState();
  });

  $(".add-customer").click(function(){
    $('#customerEmptyState').hide();
    var $addContents = $("#customerDetail").clone();
    $("#customerCards").append($addContents.html());

    var $card = $("#customerCards").find('.details:last');
    var defaultType = $('#customerTypeFilter').val() || 'Local';
    $card.attr("id", "detail" + customerRowCount).attr("data-index", customerRowCount).attr("data-type", defaultType);
    $card.find('.cs-card-number').text(customerRowCount + 1);
    $card.find('#remove').attr("id", "remove" + customerRowCount);
    $card.find('#customerProductId').attr('name', 'customerProductId['+customerRowCount+']').attr("id", "customerProductId" + customerRowCount);
    $card.find('#customerRowType').attr('name', 'customerRowType['+customerRowCount+']').attr("id", "customerRowType" + customerRowCount);
    $card.find('#customerType').attr('name', 'customerType['+customerRowCount+']').attr("id", "customerType" + customerRowCount).val(defaultType);
    $card.find('#no').attr('name', 'no['+customerRowCount+']').attr("id", "no" + customerRowCount).val(customerRowCount+1);
    $card.find('#customers').attr('name', 'customers['+customerRowCount+']').attr("id", "customers" + customerRowCount).select2({
      allowClear: true, placeholder: $("#customerDetail").find('#customers').data('placeholder'), dropdownParent: $('#customersModal')
    });
    $card.find('#customerGrade').attr('name', 'customerGrade['+customerRowCount+']').attr("id", "customerGrade" + customerRowCount).select2({
      allowClear: true, placeholder: "-", dropdownParent: $('#customersModal')
    });
    $card.find('#customerPricingType').attr('name', 'customerPricingType['+customerRowCount+']').attr("id", "customerPricingType" + customerRowCount);
    $card.find('#customerCurrency').attr('name', 'customerCurrency['+customerRowCount+']').attr("id", "customerCurrency" + customerRowCount).select2({
      allowClear: true, placeholder: "-", dropdownParent: $('#customersModal')
    });
    $card.find('#customerPrice').attr('name', 'customerPrice['+customerRowCount+']').attr("id", "customerPrice" + customerRowCount);

    $card.find('#customers' + customerRowCount).on('change', function() {
      var state = $(this).find('option:selected').data('state') || '';
      $(this).closest('.cs-card').find('.customer-state-display').val(state);
    }).trigger('change');

    customerRowCount++;
  });

  // Find and remove selected supplier cards
  $("#supplierCards").on('click', 'button[id^="removeSupplier"]', function () {
    $(this).closest('.cs-card').remove();
    updateSupplierNumbers();
    toggleSupplierEmptyState();
  });

  function updateSupplierNumbers() {
    $("#supplierCards .cs-card").each(function (index) {
      $(this).find('.cs-card-number').text(index + 1);
      $(this).find('input[name^="supplierNo"]').val(index + 1);
    });
  }

  function toggleSupplierEmptyState() {
    var filter = $('#supplierTypeFilter').val();
    var visibleCount = filter ? $('#supplierCards .cs-card[data-type="'+filter+'"]').length : $('#supplierCards .cs-card').length;
    $('#supplierEmptyState').toggle(visibleCount === 0);
  }

  // Filter suppliers by type
  $('#supplierTypeFilter').on('change', function() {
    var filter = $(this).val();
    if (filter) {
      $('#supplierCards .cs-card').hide();
      $('#supplierCards .cs-card[data-type="'+filter+'"]').show();
    } else {
      $('#supplierCards .cs-card').show();
    }
    toggleSupplierEmptyState();
  });

  // Update card data-type when type dropdown changes
  $('#supplierCards').on('change', '.supplier-type-select', function() {
    $(this).closest('.cs-card').attr('data-type', $(this).val());
    var filter = $('#supplierTypeFilter').val();
    if (filter && $(this).val() !== filter) {
      $(this).closest('.cs-card').hide();
    }
    toggleSupplierEmptyState();
  });

  $(".add-supplier").click(function(){
    $('#supplierEmptyState').hide();
    var $addContents = $("#supplierDetail").clone();
    $("#supplierCards").append($addContents.html());

    var $card = $("#supplierCards").find('.details:last');
    var defaultType = $('#supplierTypeFilter').val() || 'Local';
    $card.attr("id", "supplierDetail" + supplierRowCount).attr("data-index", supplierRowCount).attr("data-type", defaultType);
    $card.find('.cs-card-number').text(supplierRowCount + 1);
    $card.find('#removeSupplier').attr("id", "removeSupplier" + supplierRowCount);
    $card.find('#supplierProductId').attr('name', 'supplierProductId['+supplierRowCount+']').attr("id", "supplierProductId" + supplierRowCount);
    $card.find('#supplierRowType').attr('name', 'supplierRowType['+supplierRowCount+']').attr("id", "supplierRowType" + supplierRowCount);
    $card.find('#supplierType').attr('name', 'supplierType['+supplierRowCount+']').attr("id", "supplierType" + supplierRowCount).val(defaultType);
    $card.find('#supplierNo').attr('name', 'supplierNo['+supplierRowCount+']').attr("id", "supplierNo" + supplierRowCount).val(supplierRowCount+1);
    $card.find('#suppliers').attr('name', 'suppliers['+supplierRowCount+']').attr("id", "suppliers" + supplierRowCount).select2({
      allowClear: true, placeholder: $("#supplierDetail").find('#suppliers').data('placeholder'), dropdownParent: $('#customersModal')
    });
    $card.find('#supplierGrade').attr('name', 'supplierGrade['+supplierRowCount+']').attr("id", "supplierGrade" + supplierRowCount).select2({
      allowClear: true, placeholder: "-", dropdownParent: $('#customersModal')
    });
    $card.find('#supplierPricingType').attr('name', 'supplierPricingType['+supplierRowCount+']').attr("id", "supplierPricingType" + supplierRowCount);
    $card.find('#supplierCurrency').attr('name', 'supplierCurrency['+supplierRowCount+']').attr("id", "supplierCurrency" + supplierRowCount).select2({
      allowClear: true, placeholder: "-", dropdownParent: $('#customersModal')
    });
    $card.find('#supplierPrice').attr('name', 'supplierPrice['+supplierRowCount+']').attr("id", "supplierPrice" + supplierRowCount);

    $card.find('#suppliers' + supplierRowCount).on('change', function() {
      var state = $(this).find('option:selected').data('state') || '';
      $(this).closest('.cs-card').find('.supplier-state-display').val(state);
    }).trigger('change');

    supplierRowCount++;
  });

  $('#drawerClose, #drawerCancel, #drawerOverlay').on('click', function() {
    $('#productModal').modal('hide');
  });

  $('#rangeSetCheckbox').on('change', function() {
    setRangeSet($(this).is(':checked') ? 1 : 0);
  });

  // Remove grade row
  $('#gradeLocalRowsContainer, #gradeExportRowsContainer').on('click', '.dynamic-card-remove', function() {
    var index = $(this).data('index');
    $(this).closest('.dynamic-card').remove();
    $('#gradeTable').find('tr[data-index="'+index+'"]').remove();
    updateGradeEmptyState();
  });

  // Sync row changes to hidden table
  $('#gradeLocalRowsContainer, #gradeExportRowsContainer').on('change', 'select, input', function() {
    var index = $(this).closest('.dynamic-card').data('index');
    syncGradeRowToTable(index);
  });

  // Find and remove selected table rows
  $("#gradeTable").on('click', 'button[id^="remove"]', function () {
    var index = $(this).closest('tr').data('index');
    $(this).parents("tr").remove();
    $('#gradeRowsContainer').find('.dynamic-card[data-index="'+index+'"]').remove();
    updateGradeEmptyState();
  });

  $(".add-grade").click(function(){
    var gradeType = $(this).data('type'); // 'Local' or 'Export'
    var containerId = gradeType === 'Local' ? '#gradeLocalRowsContainer' : '#gradeExportRowsContainer';
    var emptyStateId = gradeType === 'Local' ? '#gradeLocalEmptyState' : '#gradeExportEmptyState';

    // Add visual row
    $(containerId).append(renderGradeRow(gradeRowCount, gradeType));
    $(emptyStateId).hide();

    // Init Select2 on new row
    $('#gradesRow'+gradeRowCount).select2({ allowClear: true, placeholder: "Please Select", dropdownParent: $('#productModal') });
    $('#gradePricingCurrencyRow'+gradeRowCount).val(defaultCurrencyId).select2({ allowClear: true, placeholder: "Select", dropdownParent: $('#productModal') });
    $('#gradePurchasingPricingCurrencyRow'+gradeRowCount).val(defaultCurrencyId).select2({ allowClear: true, placeholder: "Select", dropdownParent: $('#productModal') });

    // Add hidden table row for form submission
    var $addContents = $("#gradeDetail").clone();
    $("#gradeTable").append($addContents.html());

    var $tr = $("#gradeTable").find('.details:last');
    $tr.attr("id", "detail" + gradeRowCount).attr("data-index", gradeRowCount);
    $tr.find('#gradeNo').attr('name', 'gradeNo['+gradeRowCount+']').attr("id", "gradeNo" + gradeRowCount).val(gradeRowCount+1);
    $tr.find('#productGradeId').attr('name', 'productGradeId['+gradeRowCount+']').attr("id", "productGradeId" + gradeRowCount);
    $tr.find('#grades').attr('name', 'grades['+gradeRowCount+']').attr("id", "grades" + gradeRowCount);
    $tr.find('#gradeType').attr('name', 'gradeType['+gradeRowCount+']').attr("id", "gradeType" + gradeRowCount).val(gradeType);
    $tr.find('#gradePricingType').attr('name', 'gradePricingType['+gradeRowCount+']').attr("id", "gradePricingType" + gradeRowCount).val('Standard');
    $tr.find('#gradePricingCurrency').attr('name', 'gradePricingCurrency['+gradeRowCount+']').attr("id", "gradePricingCurrency" + gradeRowCount).val(defaultCurrencyId);
    $tr.find('#gradePrice').attr('name', 'gradePrice['+gradeRowCount+']').attr("id", "gradePrice" + gradeRowCount).val(0);
    $tr.find('#gradePurchasingPricingType').attr('name', 'gradePurchasingPricingType['+gradeRowCount+']').attr("id", "gradePurchasingPricingType" + gradeRowCount).val('Standard');
    $tr.find('#gradePurchasingPricingCurrency').attr('name', 'gradePurchasingPricingCurrency['+gradeRowCount+']').attr("id", "gradePurchasingPricingCurrency" + gradeRowCount).val(defaultCurrencyId);
    $tr.find('#gradePurchasingPrice').attr('name', 'gradePurchasingPrice['+gradeRowCount+']').attr("id", "gradePurchasingPrice" + gradeRowCount).val(0);

    gradeRowCount++;
  });

  $('#bulkPriceByStateModal').on('show.bs.modal', function() {
    $('#customersModal .modal-content').css('filter', 'blur(3px)');
  }).on('hide.bs.modal', function() {
    $('#customersModal .modal-content').css('filter', '');
  });

  $('#customersForm').on('submit', function(e) {
    e.preventDefault();
    $('#spinnerLoading').show();
    $.ajax({
      url: 'php/modules/products/api.php',
      type: 'POST',
      data: $(this).serialize() + '&action=saveCustomerSupplier',
      success: function(obj) {
        $('#spinnerLoading').hide();
        if (obj.status === 'success') {
          toastr["success"](obj.message, "Success:");
          $('#customersModal').modal('hide');
        } else {
          toastr["error"](obj.message, "Failed:");
        }
      }
    });
  });

  $('#bulkPriceByState').on('click', function() {
    $('#bulkTargetType').val('customer');
    $('#bulkPricingTypeGroup, #bulkSellingPriceGroup').show();
    $('#bulkPurchasingPricingTypeGroup, #bulkPurchasingPriceGroup').hide();
    $('#bulkState').val(null).trigger('change');
    $('#bulkGrade').val('').trigger('change');
    $('#bulkPricingType').val('Standard');
    $('#bulkSellingPrice').val(0);
    $('#bulkPriceByStateModal').modal('show');
  });

  $('#bulkPriceByStateSupplier').on('click', function() {
    $('#bulkTargetType').val('supplier');
    $('#bulkPricingTypeGroup, #bulkSellingPriceGroup').hide();
    $('#bulkPurchasingPricingTypeGroup, #bulkPurchasingPriceGroup').show();
    $('#bulkState').val(null).trigger('change');
    $('#bulkGrade').val('').trigger('change');
    $('#bulkPurchasingPricingType').val('Standard');
    $('#bulkPurchasingPrice').val(0);
    $('#bulkPriceByStateModal').modal('show');
  });

  $('#bulkPriceByStateSave').on('click', function() {
    var selectedStates = $('#bulkState').val();
    if (!selectedStates || selectedStates.length === 0) {
      toastr["error"]("Please select at least one state.", "Error:");
      return;
    }
    var selectedGrade = $('#bulkGrade').val();
    var targetType = $('#bulkTargetType').val();
    var updated = 0;

    if (targetType === 'customer') {
      var pricingType = $('#bulkPricingType').val();
      var sellingPrice = $('#bulkSellingPrice').val();
      $('#customerCards .cs-card.details:visible').each(function() {
        var $row = $(this);
        var customerState = $row.find('select[id^="customers"]').find('option:selected').data('state');
        var rowGrade = $row.find('select[id^="customerGrade"]').val();
        var stateMatch = selectedStates.indexOf(String(customerState)) !== -1;
        var gradeMatch = selectedGrade === '' || String(rowGrade) === String(selectedGrade);
        if (stateMatch && gradeMatch) {
          $row.find('select[id^="customerPricingType"]').val(pricingType);
          $row.find('input[id^="customerPrice"]').val(sellingPrice);
          updated++;
        }
      });
    } else {
      var purchasingPricingType = $('#bulkPurchasingPricingType').val();
      var purchasingPrice = $('#bulkPurchasingPrice').val();
      $('#supplierCards .cs-card.details:visible').each(function() {
        var $row = $(this);
        var supplierState = $row.find('select[id^="suppliers"]').find('option:selected').data('state');
        var rowGrade = $row.find('select[id^="supplierGrade"]').val();
        var stateMatch = selectedStates.indexOf(String(supplierState)) !== -1;
        var gradeMatch = selectedGrade === '' || String(rowGrade) === String(selectedGrade);
        if (stateMatch && gradeMatch) {
          $row.find('select[id^="supplierPricingType"]').val(purchasingPricingType);
          $row.find('input[id^="supplierPrice"]').val(purchasingPrice);
          updated++;
        }
      });
    }

    $('#bulkPriceByStateModal').modal('hide');
    toastr["success"](updated + " row(s) updated.", "Success:");
  });
});

function renderGradeRow(index, type) {
  var html = $('#gradeRowTemplate').html();
  return html.replace(/{index}/g, index).replace(/{type}/g, type);
}

function syncGradeRowToTable(index) {
  var $row = $('#gradeTable').find('tr[data-index="'+index+'"]');
  $row.find('input[name^="grades"]').val($('#gradesRow'+index).val());
  $row.find('input[name^="gradePricingType"]').val($('#gradePricingTypeRow'+index).val());
  $row.find('input[name^="gradePricingCurrency"]').val($('#gradePricingCurrencyRow'+index).val());
  $row.find('input[name^="gradePrice"]').val($('#gradePriceRow'+index).val());
  $row.find('input[name^="gradePurchasingPricingType"]').val($('#gradePurchasingPricingTypeRow'+index).val());
  $row.find('input[name^="gradePurchasingPricingCurrency"]').val($('#gradePurchasingPricingCurrencyRow'+index).val());
  $row.find('input[name^="gradePurchasingPrice"]').val($('#gradePurchasingPriceRow'+index).val());
}

function updateGradeEmptyState() {
  if ($('#gradeLocalRowsContainer .dynamic-card').length === 0) {
    $('#gradeLocalEmptyState').show();
  } else {
    $('#gradeLocalEmptyState').hide();
  }
  if ($('#gradeExportRowsContainer .dynamic-card').length === 0) {
    $('#gradeExportEmptyState').show();
  } else {
    $('#gradeExportEmptyState').hide();
  }
}

function displayPreview(data) {
  // Parse the Excel data
  var workbook = XLSX.read(data, { type: 'binary' });

  // Get the first sheet
  var sheetName = workbook.SheetNames[0];
  var sheet = workbook.Sheets[sheetName];

  // Convert the sheet to an array of objects
  var jsonData = XLSX.utils.sheet_to_json(sheet, { header: 5 });

  // Get the headers
  var headers = Object.keys(jsonData[0] || {});

  // Ensure we handle cases where there may be less than 5 columns
  while (headers.length < 5) {
      headers.push(''); // Adding empty headers to reach 5 columns
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

      for (var j = 0; j < 5 && j < headers.length; j++) {
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

function setProductImagePreview(file) {
  var reader = new FileReader();
  reader.onload = function(e) {
    $('#productImageThumb').attr('src', e.target.result);
    $('#productImagePreview').show();
    $('#productImagePlaceholder').hide();
  };
  reader.readAsDataURL(file);
}

function edit(id){
  $('#spinnerLoading').show();
  $.post('php/modules/products/api.php', {action: 'get', id: id}, function(obj){

    if(obj.status === 'success'){
      $('#productModal').find('#id').val(obj.message.id);
      $('#productModal').find('#code').val(obj.message.product_code);
      $('#productModal').find('#product').val(obj.message.product_name);
      $('#productModal').find('#uom').val(obj.message.uom).trigger('change');
      $('#productModal').find('#remark').val(obj.message.remark);
      $('#productModal').find('#pricingType').val(obj.message.pricing_type);
      $('#productModal').find('#pricingCurrency').val(obj.message.pricing_currency).trigger('change');
      $('#productModal').find('#price').val(obj.message.price);
      $('#productModal').find('#purchasingPricingType').val(obj.message.purchasing_pricing_type);
      $('#productModal').find('#purchasingPricingCurrency').val(obj.message.purchasing_pricing_currency).trigger('change');
      $('#productModal').find('#purchasingPrice').val(obj.message.purchasing_price);
      $('#productModal').find('#weight').val(obj.message.weight);
      $('#productModal').find('#productCategory').val(obj.message.category).trigger('change');
      $('#productModal').find('#productPackaging').val(obj.message.packaging).trigger('change');
      $('#productModal').find('#productColour').val(obj.message.colour || '');
      $('#productModal').find('#state').val(obj.message.state).trigger('change');
      $('#productModal').find('#company').val(obj.message.customer).trigger('change');
      $('#productImage').val('');
      if (obj.message.product_image) {
        $('#productImageThumb').attr('src', 'php/viewPhoto.php?file=' + obj.message.product_image + '&type=file_table');
        $('#productImagePreview').show();
        $('#productImagePlaceholder').hide();
      } else {
        $('#productImagePreview').hide();
        $('#productImageThumb').attr('src', '');
        $('#productImagePlaceholder').show();
      }
      setRangeSet(obj.message.range_set == '1' ? 1 : 0);
      $('#okWeight').val(obj.message.ok_weight); $('#okWeightUnit').val(obj.message.ok_weight_unit || 'kg');
      $('#loWeight').val(obj.message.lo_weight); $('#loWeightUnit').val(obj.message.lo_weight_unit || 'kg');
      $('#hiWeight').val(obj.message.hi_weight); $('#hiWeightUnit').val(obj.message.hi_weight_unit || 'kg');

      // grade table and rows
      $('#gradeTable').html('');
      $('#gradeLocalRowsContainer .dynamic-card').remove();
      $('#gradeExportRowsContainer .dynamic-card').remove();
      gradeRowCount = 0;
      if (obj.message.productGrades.length > 0){
        var hasLocal = false, hasExport = false;
        for(var i = 0; i < obj.message.productGrades.length; i++){
          var item = obj.message.productGrades[i];
          var gradeType = item.type || 'Local';
          var containerId = gradeType === 'Local' ? '#gradeLocalRowsContainer' : '#gradeExportRowsContainer';
          if (gradeType === 'Local') hasLocal = true;
          else hasExport = true;
          
          // Add visual row
          $(containerId).append(renderGradeRow(gradeRowCount, gradeType));
          $('#gradesRow'+gradeRowCount).val(item.grade_id).select2({ allowClear: true, placeholder: "Please Select", dropdownParent: $('#productModal') });
          $('#gradePricingTypeRow'+gradeRowCount).val(item.pricing_type || 'Standard');
          $('#gradePricingCurrencyRow'+gradeRowCount).val(item.pricing_currency).select2({ allowClear: true, placeholder: "Select", dropdownParent: $('#productModal') });
          $('#gradePriceRow'+gradeRowCount).val(item.price || 0);
          $('#gradePurchasingPricingTypeRow'+gradeRowCount).val(item.purchasing_pricing_type || 'Standard');
          $('#gradePurchasingPricingCurrencyRow'+gradeRowCount).val(item.purchasing_pricing_currency).select2({ allowClear: true, placeholder: "Select", dropdownParent: $('#productModal') });
          $('#gradePurchasingPriceRow'+gradeRowCount).val(item.purchasing_price || 0);

          // Add hidden table row
          var $addContents = $("#gradeDetail").clone();
          $("#gradeTable").append($addContents.html());

          var $tr = $("#gradeTable").find('.details:last');
          $tr.attr("id", "detail" + gradeRowCount).attr("data-index", gradeRowCount);
          $tr.find('#productGradeId').attr('name', 'productGradeId['+gradeRowCount+']').attr("id", "productGradeId" + gradeRowCount).val(item.id);
          $tr.find('#gradeNo').attr('name', 'gradeNo['+gradeRowCount+']').attr("id", "gradeNo" + gradeRowCount).val(item.no);
          $tr.find('#grades').attr('name', 'grades['+gradeRowCount+']').attr("id", "grades" + gradeRowCount).val(item.grade_id);
          $tr.find('#gradeType').attr('name', 'gradeType['+gradeRowCount+']').attr("id", "gradeType" + gradeRowCount).val(gradeType);
          $tr.find('#gradePricingType').attr('name', 'gradePricingType['+gradeRowCount+']').attr("id", "gradePricingType" + gradeRowCount).val(item.pricing_type || 'Standard');
          $tr.find('#gradePricingCurrency').attr('name', 'gradePricingCurrency['+gradeRowCount+']').attr("id", "gradePricingCurrency" + gradeRowCount).val(item.pricing_currency);
          $tr.find('#gradePrice').attr('name', 'gradePrice['+gradeRowCount+']').attr("id", "gradePrice" + gradeRowCount).val(item.price || 0);
          $tr.find('#gradePurchasingPricingType').attr('name', 'gradePurchasingPricingType['+gradeRowCount+']').attr("id", "gradePurchasingPricingType" + gradeRowCount).val(item.purchasing_pricing_type || 'Standard');
          $tr.find('#gradePurchasingPricingCurrency').attr('name', 'gradePurchasingPricingCurrency['+gradeRowCount+']').attr("id", "gradePurchasingPricingCurrency" + gradeRowCount).val(item.purchasing_pricing_currency);
          $tr.find('#gradePurchasingPrice').attr('name', 'gradePurchasingPrice['+gradeRowCount+']').attr("id", "gradePurchasingPrice" + gradeRowCount).val(item.purchasing_price || 0);

          gradeRowCount++;
        }
        $('#gradeLocalEmptyState').toggle(!hasLocal);
        $('#gradeExportEmptyState').toggle(!hasExport);
      } else {
        $('#gradeLocalEmptyState').show();
        $('#gradeExportEmptyState').show();
      }

      $('#modalTitle').text(productsText.editTitle);
      $('#productModal').modal('show');
      
      $('#productForm').validate({
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

function openCustomers(id) {
  $('#spinnerLoading').show();
  $('#customerCards').html('');
  $('#supplierCards').html('');
  customerRowCount = 0;
  supplierRowCount = 0;
  $('#customerTypeFilter').val('');
  $('#supplierTypeFilter').val('');
  $('#customersForm').find('#customerProductId').val(id);
  // Reset to customers tab
  $('#tabCustomersLink').tab('show');
  $.post('php/modules/products/api.php', {action: 'get', id: id}, function(obj) {
    if (obj.status === 'success') {
      // Load customers
      var items = obj.message.productCustomers;
      if (items.length > 0) {
        $('#customerEmptyState').hide();
      } else {
        $('#customerEmptyState').show();
      }
      for (var i = 0; i < items.length; i++) {
        var item = items[i];
        var customerType = item.type || 'Local';
        
        var $addContents = $("#customerDetail").clone();
        $("#customerCards").append($addContents.html());

        var $card = $("#customerCards").find('.details:last');
        $card.attr("id", "detail" + customerRowCount).attr("data-index", customerRowCount).attr("data-type", customerType);
        $card.find('.cs-card-number').text(customerRowCount + 1);
        $card.find('#remove').attr("id", "remove" + customerRowCount);
        $card.find('#no').attr('name', 'no['+customerRowCount+']').attr("id", "no" + customerRowCount).val(item.no);
        $card.find('#customerProductId').attr('name', 'customerProductId['+customerRowCount+']').attr("id", "customerProductId" + customerRowCount).val(item.id);
        $card.find('#customerRowType').attr('name', 'customerRowType['+customerRowCount+']').attr("id", "customerRowType" + customerRowCount);
        $card.find('#customerType').attr('name', 'customerType['+customerRowCount+']').attr("id", "customerType" + customerRowCount).val(customerType);
        $card.find('#customers').attr('name', 'customers['+customerRowCount+']').attr("id", "customers" + customerRowCount).val(item.customer_id).select2({
          allowClear: true, placeholder: $("#customerDetail").find('#customers').data('placeholder'), dropdownParent: $('#customersModal')
        }).on('change', function() {
          var state = $(this).find('option:selected').data('state') || '';
          $(this).closest('.cs-card').find('.customer-state-display').val(state);
        });
        var customerStateVal = $card.find('#customers' + customerRowCount).find('option:selected').data('state') || '';
        $card.find('.customer-state-display').val(customerStateVal);
        $card.find('#customerGrade').attr('name', 'customerGrade['+customerRowCount+']').attr("id", "customerGrade" + customerRowCount).val(item.grade_id || '').select2({
          allowClear: true, placeholder: "-", dropdownParent: $('#customersModal')
        });
        $card.find('#customerPricingType').attr('name', 'customerPricingType['+customerRowCount+']').attr("id", "customerPricingType" + customerRowCount).val(item.pricing_type || 'Standard');
        $card.find('#customerCurrency').attr('name', 'customerCurrency['+customerRowCount+']').attr("id", "customerCurrency" + customerRowCount).val(item.pricing_currency || '').select2({
          allowClear: true, placeholder: "-", dropdownParent: $('#customersModal')
        });
        $card.find('#customerPrice').attr('name', 'customerPrice['+customerRowCount+']').attr("id", "customerPrice" + customerRowCount).val(item.price || 0);

        customerRowCount++;
      }

      // Load suppliers
      var supplierItems = obj.message.productSuppliers;
      if (supplierItems.length > 0) {
        $('#supplierEmptyState').hide();
      } else {
        $('#supplierEmptyState').show();
      }
      for (var j = 0; j < supplierItems.length; j++) {
        var sItem = supplierItems[j];
        var supplierType = sItem.type || 'Local';
        
        var $sContents = $("#supplierDetail").clone();
        $("#supplierCards").append($sContents.html());

        var $sCard = $("#supplierCards").find('.details:last');
        $sCard.attr("id", "supplierDetail" + supplierRowCount).attr("data-index", supplierRowCount).attr("data-type", supplierType);
        $sCard.find('.cs-card-number').text(supplierRowCount + 1);
        $sCard.find('#removeSupplier').attr("id", "removeSupplier" + supplierRowCount);
        $sCard.find('#supplierNo').attr('name', 'supplierNo['+supplierRowCount+']').attr("id", "supplierNo" + supplierRowCount).val(sItem.no);
        $sCard.find('#supplierProductId').attr('name', 'supplierProductId['+supplierRowCount+']').attr("id", "supplierProductId" + supplierRowCount).val(sItem.id);
        $sCard.find('#supplierRowType').attr('name', 'supplierRowType['+supplierRowCount+']').attr("id", "supplierRowType" + supplierRowCount);
        $sCard.find('#supplierType').attr('name', 'supplierType['+supplierRowCount+']').attr("id", "supplierType" + supplierRowCount).val(supplierType);
        $sCard.find('#suppliers').attr('name', 'suppliers['+supplierRowCount+']').attr("id", "suppliers" + supplierRowCount).val(sItem.supplier_id).select2({
          allowClear: true, placeholder: $("#supplierDetail").find('#suppliers').data('placeholder'), dropdownParent: $('#customersModal')
        }).on('change', function() {
          var state = $(this).find('option:selected').data('state') || '';
          $(this).closest('.cs-card').find('.supplier-state-display').val(state);
        });
        var supplierStateVal = $sCard.find('#suppliers' + supplierRowCount).find('option:selected').data('state') || '';
        $sCard.find('.supplier-state-display').val(supplierStateVal);
        $sCard.find('#supplierGrade').attr('name', 'supplierGrade['+supplierRowCount+']').attr("id", "supplierGrade" + supplierRowCount).val(sItem.grade_id || '').select2({
          allowClear: true, placeholder: "-", dropdownParent: $('#customersModal')
        });
        $sCard.find('#supplierPricingType').attr('name', 'supplierPricingType['+supplierRowCount+']').attr("id", "supplierPricingType" + supplierRowCount).val(sItem.purchasing_pricing_type || 'Standard');
        $sCard.find('#supplierCurrency').attr('name', 'supplierCurrency['+supplierRowCount+']').attr("id", "supplierCurrency" + supplierRowCount).val(sItem.purchasing_pricing_currency || '').select2({
          allowClear: true, placeholder: "-", dropdownParent: $('#customersModal')
        });
        $sCard.find('#supplierPrice').attr('name', 'supplierPrice['+supplierRowCount+']').attr("id", "supplierPrice" + supplierRowCount).val(sItem.purchasing_price || 0);

        supplierRowCount++;
      }
    } else {
      toastr["error"](obj.message, "Failed:");
    }
    $('#spinnerLoading').hide();
    $('#customersModal').modal('show');
  });
}

function setRangeSet(val) {
  var enabled = val == 1;
  $('#rangeSet').val(enabled ? 1 : 0);
  $('#rangeSetCheckbox').prop('checked', enabled);
  $('#rangeWeightFields').toggle(enabled);
}

function deactivate(id){
  if (confirm('Are you sure you want to delete this items?')) {
    //$('#spinnerLoading').show();
    $.post('php/modules/products/api.php', {action: 'delete', ids: [id]}, function(obj){

        if(obj.status === 'success'){
          toastr["success"](obj.message, "Success:");
          $('#productTable').DataTable().ajax.reload();
          //$('#spinnerLoading').hide();
        }
        else if(obj.status === 'failed'){
            toastr["error"](obj.message, "Failed:");
            //$('#spinnerLoading').hide();
        }
        else{
            toastr["error"]("Something wrong when activate", "Failed:");
            //$('#spinnerLoading').hide();
        }
    });
  }
}
