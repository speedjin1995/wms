// 1. Variables
var memberTable;
var userApi = 'php/modules/users/api.php';

// 2. Document ready
$(document).ready(function() {
  $('.select2').each(function() {
    $(this).select2({
      allowClear: true,
      placeholder: userText.pleaseSelect,
      dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal-body') : undefined
    });
  });

  memberTable = $("#memberTable").DataTable({
    responsive: true,
    autoWidth: false,
    processing: true,
    serverSide: true,
    serverMethod: 'post',
    language: userTableLanguage,
    ajax: {
      url: userApi,
      data: { action: 'list' }
    },
    columns: [
      { data: 'name' },
      { data: 'role_name' },
      { data: 'allow_add', render: renderYesNo },
      { data: 'allow_edit', render: renderYesNo },
      { data: 'allow_delete', render: renderYesNo },
      { data: 'allow_price', render: renderYesNo },
      { data: 'location' },
      { data: 'created_date' },
      {
        data: 'id',
        orderable: false,
        render: function(data) {
          return '<div class="d-flex" style="gap:4px;">' +
                 '<button type="button" onclick="edit('+data+')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button>' +
                 '<button type="button" onclick="openModuleAccess('+data+')" class="btn btn-sm btn-outline-warning" title="Module Settings"><i class="fas fa-cogs"></i></button>' +
                 '<button type="button" onclick="deactivate('+data+')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>' +
                 '</div>';
        }
      }
    ]
  });

  $.validator.setDefaults({
    submitHandler: function() {
      $('#spinnerLoading').show();
      $('#submitMember').prop('disabled', true);

      $.post(userApi, $('#memberForm').serialize() + '&action=save', function(obj) {
        if (obj.status === 'success') {
          $('#addModal').modal('hide');
          toastr.success(obj.message, "Success:");
          memberTable.ajax.reload();
        } else {
          toastr.error(obj.message || "Something went wrong", "Failed:");
        }
      }).fail(function() {
        toastr.error("Something went wrong", "Failed:");
      }).always(function() {
        $('#submitMember').prop('disabled', false);
        $('#spinnerLoading').hide();
      });
    }
  });

  $('#addMembers').on('click', function() {
    $('#memberForm')[0].reset();
    $('#addModal').find('#id').val("");
    $('#addModal').find('#location').val("").trigger('change');
    $('#addModal').modal('show');
    initValidation();
  });
});

// 3. Functions
function renderYesNo(data) {
  return data === 'YES'
    ? '<span class="badge badge-success">YES</span>'
    : '<span class="badge badge-secondary">NO</span>';
}

function initValidation() {
  $('#memberForm').validate({
    errorElement: 'span',
    errorPlacement: function(error, element) {
      error.addClass('invalid-feedback');
      element.closest('.form-group').append(error);
    },
    highlight: function(element) {
      $(element).addClass('is-invalid');
    },
    unhighlight: function(element) {
      $(element).removeClass('is-invalid');
    }
  });
}

function edit(id) {
  $('#spinnerLoading').show();

  $.post(userApi, { action: 'get', id: id }, function(obj) {
    if (obj.status === 'success') {
      $('#addModal').find('#id').val(obj.message.id);
      $('#addModal').find('#username').val(obj.message.username);
      $('#addModal').find('#name').val(obj.message.name);
      $('#addModal').find('#email').val(obj.message.email);
      $('#addModal').find('#userRole').val(obj.message.role_code);
      $('#addModal').find('#allowAdd').val(obj.message.allow_add);
      $('#addModal').find('#allowEdit').val(obj.message.allow_edit);
      $('#addModal').find('#allowDelete').val(obj.message.allow_delete);
      $('#addModal').find('#allowPrice').val(obj.message.allow_price);
      $('#addModal').find('#location').val(obj.message.location).trigger('change');
      $('#addModal').modal('show');
      initValidation();
    } else {
      toastr.error(obj.message || "Something went wrong", "Failed:");
    }
  }).fail(function() {
    toastr.error("Something went wrong", "Failed:");
  }).always(function() {
    $('#spinnerLoading').hide();
  });
}

function deactivate(id) {
  if (confirm("Are you sure you want to delete this user?")) {
    $('#spinnerLoading').show();

    $.post(userApi, { action: 'delete', id: id }, function(obj) {
      if (obj.status === 'success') {
        toastr.success(obj.message, "Success:");
        memberTable.ajax.reload();
      } else {
        toastr.error(obj.message || "Something went wrong", "Failed:");
      }
    }).fail(function() {
      toastr.error("Something went wrong", "Failed:");
    }).always(function() {
      $('#spinnerLoading').hide();
    });
  }
}

function openModuleAccess(id) {
  $('#spinnerLoading').show();
  $('#moduleAccessUserId').val(id);

  $.post(userApi, { action: 'getModuleAccess', id: id }, function(obj) {
    if (obj.status === 'success') {
      var availableModules = obj.message.availableModules;
      var categories = obj.message.categories;
      var moduleAccess = obj.message.moduleAccess;
      var selectedModules = moduleAccess.modules || [];
      var selectedCategories = moduleAccess.categories || {};

      // Build module checkboxes
      var html = '';
      availableModules.forEach(function(mod) {
        var checked = selectedModules.includes(mod) ? 'checked' : '';
        var modLabel = mod.charAt(0).toUpperCase() + mod.slice(1);
        html += '<div class="module-item mb-3">';
        html += '<div class="custom-control custom-checkbox">';
        html += '<input type="checkbox" class="custom-control-input module-checkbox" id="mod_'+mod+'" value="'+mod+'" '+checked+'>';
        html += '<label class="custom-control-label font-weight-bold" for="mod_'+mod+'">'+modLabel+'</label>';
        html += '</div>';
        html += '<div class="category-select ml-4 mt-2" id="cat_container_'+mod+'" style="display:'+(checked ? 'block' : 'none')+';">';
        html += '<select class="form-control select2-categories" id="cat_'+mod+'" multiple="multiple" data-module="'+mod+'">';
        if (categories[mod]) {
          categories[mod].forEach(function(cat) {
            var catSelected = (selectedCategories[mod] && selectedCategories[mod].includes(cat.id)) ? 'selected' : '';
            html += '<option value="'+cat.id+'" '+catSelected+'>'+cat.name+'</option>';
          });
        }
        html += '</select>';
        html += '</div>';
        html += '</div>';
      });

      $('#moduleAccessContainer').html(html);

      // Initialize Select2 for category dropdowns
      $('.select2-categories').each(function() {
        $(this).select2({
          placeholder: 'Select categories',
          allowClear: true,
          width: '100%',
          dropdownParent: $('#moduleAccessModal')
        });
      });

      // Toggle category select on module checkbox change
      $('.module-checkbox').on('change', function() {
        var mod = $(this).val();
        if ($(this).is(':checked')) {
          $('#cat_container_'+mod).slideDown();
        } else {
          $('#cat_container_'+mod).slideUp();
          $('#cat_'+mod).val(null).trigger('change');
        }
      });

      $('#moduleAccessModal').modal('show');
    } else {
      toastr.error(obj.message || 'Failed to load module settings', 'Failed:');
    }
  }).fail(function() {
    toastr.error('Failed to load module settings', 'Failed:');
  }).always(function() {
    $('#spinnerLoading').hide();
  });
}

function saveModuleAccess() {
  $('#spinnerLoading').show();

  var modules = [];
  var categories = {};

  $('.module-checkbox:checked').each(function() {
    var mod = $(this).val();
    modules.push(mod);
    var catValues = $('#cat_'+mod).val();
    if (catValues && catValues.length > 0) {
      categories[mod] = catValues.map(Number);
    }
  });

  var moduleAccess = JSON.stringify({ modules: modules, categories: categories });
  var userId = $('#moduleAccessUserId').val();

  $.post(userApi, { action: 'saveModuleAccess', id: userId, moduleAccess: moduleAccess }, function(obj) {
    if (obj.status === 'success') {
      $('#moduleAccessModal').modal('hide');
      toastr.success(obj.message, 'Success:');
    } else {
      toastr.error(obj.message || 'Failed to save', 'Failed:');
    }
  }).fail(function() {
    toastr.error('Failed to save', 'Failed:');
  }).always(function() {
    $('#spinnerLoading').hide();
  });
}
