<?php
require_once '../../php/db_connect.php';
session_start();

if (!isset($_SESSION['userID'])) {
    echo '<script>window.location.href = "login.html";</script>';
    exit;
}

if (($_SESSION['featureFlags']['include_production'] ?? 'N') != 'Y') {
    exit;
}

$company = $_SESSION['customer'];
$role = $_SESSION['role'];
$language = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];
$companies = $role == 'SADMIN' ? $db->query("SELECT id, name FROM companies WHERE deleted = 0 ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC) : [];
?>

<div class="content-header" style="padding-bottom: 0;">
  <div class="container-fluid"></div>
</div>

<!-- Main content -->
<div class="content page-modern">
	<div class="container-fluid">
        <div class="row">
			<div class="col-12">
				<div class="card results-card show-dt-controls">
					<div class="card-header">
              <div class="results-header-left">
                <h3 class="results-title"><i class="fas fa-cogs mr-2"></i><?=$languageArray['production_types_code'][$language]?></h3>
              </div>
              <div class="results-header-right d-flex flex-wrap" style="gap: 0.5rem;">
                <button type="button" id="multiDeactivate" class="btn btn-action btn-action-danger">
                  <i class="fas fa-trash-alt"></i> <?=$languageArray['delete_production_type_code'][$language]?>
                </button>
                <button type="button" class="btn btn-action btn-action-primary" id="addProductionType">
                  <i class="fas fa-plus"></i> <?=$languageArray['add_production_type_code'][$language]?>
                </button>
              </div>
          </div>
					<div class="card-body">
						<table id="productionTypeTable" class="table data-table">
							<thead>
								<tr>
                  <th><input type="checkbox" id="selectAllCheckbox" class="selectAllCheckbox"></th>
                  <th><?=$languageArray['production_types_code'][$language]?></th>
                  <th><?=$languageArray['description_code'][$language]?></th>
									<th><?=$languageArray['actions_code'][$language]?></th>
								</tr>
							</thead>
						</table>
					</div><!-- /.card-body -->
				</div><!-- /.card -->
			</div><!-- /.col -->
		</div><!-- /.row -->
	</div><!-- /.container-fluid -->
</div><!-- /.content -->

<div class="modal fade modal-modern" id="addModal">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <form role="form" id="productionTypeForm">
            <div class="modal-header">
              <h4 class="modal-title"><?=$languageArray['add_production_type_code'][$language]?></h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <input type="hidden" id="id" name="id">
              <?php if ($role == 'SADMIN') { ?>
              <div class="modal-section">
                <div class="form-group mb-0">
                  <label class="form-label-modern" for="company"><?=$languageArray['company_code'][$language]?> <span class="text-danger">*</span></label>
                  <select class="form-control select2" style="width: 100%;" id="company" name="company" required>
                    <?php foreach ($companies as $rowCompany) { ?>
                      <option value="<?=$rowCompany['id'] ?>" <?php if($rowCompany['id'] == $company) echo 'selected'; ?>><?=htmlspecialchars($rowCompany['name']) ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <?php } ?>
              <div class="modal-section">
                <div class="form-group">
                  <label class="form-label-modern" for="name"><?=$languageArray['production_types_code'][$language]?> <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="name" id="name" placeholder="<?=$languageArray['enter_production_type_code'][$language]?>" required>
                </div>
                <div class="form-group mb-0">
                  <label class="form-label-modern" for="description"><?=$languageArray['description_code'][$language]?></label>
                  <textarea class="form-control" name="description" id="description" rows="3" placeholder="<?=$languageArray['enter_description_code'][$language]?>"></textarea>
                </div>
              </div>
            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal"><?=$languageArray['close_code'][$language]?></button>
              <button type="submit" class="btn btn-modern btn-modern-primary" name="submit"><?=$languageArray['submit_code'][$language]?></button>
            </div>
        </form>
      </div>
      <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>

<script>
var apiUrl = 'php/modules/productionTypes/api.php';
var table;

$(function () {
  $('#selectAllCheckbox').on('change', function() {
    $('#productionTypeTable tbody input[type="checkbox"]').prop('checked', $(this).prop('checked'));
  });

  $('#addModal .select2').select2({
    allowClear: true,
    placeholder: "Please Select",
    dropdownParent: $('#addModal .modal-body')
  });

  table = $("#productionTypeTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'order': [[1, 'asc']],
    'language': {
      'emptyTable': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div><div class="empty-title"><?=$languageArray['no_records_found_code'][$language] ?? 'No Records Found'?></div><div class="empty-message"><?=$languageArray['no_records_message_code'][$language] ?? 'Try adjusting your search or filter criteria'?></div></div>',
      'zeroRecords': '<div class="datatable-empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><div class="empty-title"><?=$languageArray['no_matching_records_code'][$language] ?? 'No Matching Records'?></div><div class="empty-message"><?=$languageArray['no_matching_message_code'][$language] ?? 'No results match your current filters. Try different criteria.'?></div></div>'
    },
    'ajax': {
      'url': apiUrl,
      'data': function (d) { d.action = 'list'; }
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
      { data: 'name', render: $.fn.dataTable.render.text() },
      { data: 'description', render: $.fn.dataTable.render.text() },
      {
        data: 'deleted',
        orderable: false,
        render: function (data, type, row) {
          if (data == 0) {
            return '<div class="row"><div class="col-3"><button type="button" id="edit' + row.id + '" onclick="edit(' + row.id + ')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button></div><div class="col-3"><button type="button" id="delete' + row.id + '" onclick="deactivate(' + row.id + ')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></div></div>';
          }
          else{
            return '<button type="button" id="reactivate' + row.id + '" onclick="reactivate(' + row.id + ')" class="btn btn-warning btn-sm">Reactivate</button>';
          }
        }
      }
    ]
  });

  $('#productionTypeForm').validate({
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
      },
      submitHandler: function () {
          $('#spinnerLoading').show();
          $.post(apiUrl, $('#productionTypeForm').serialize() + '&action=save', function(obj){
              if(obj.status === 'success'){
                $('#addModal').modal('hide');
                toastr["success"](obj.message, "Success:");
                table.ajax.reload();
              }
              else{
                toastr["error"](obj.message, "Failed:");
              }
              $('#spinnerLoading').hide();
          });
      }
  });

  $('#addProductionType').on('click', function(){
    $('#addModal').find('#id').val("");
    $('#addModal').find('#name').val("");
    $('#addModal').find('#description').val("");
    $('#addModal').modal('show');
  });

  $('#multiDeactivate').on('click', function () {
    var selectedIds = [];

    $("#productionTypeTable tbody input[type='checkbox']:checked").each(function () {
      selectedIds.push($(this).val());
    });

    if (selectedIds.length == 0) {
      alert("Please select at least one production type to delete.");
      return;
    }

    if (confirm('Are you sure you want to delete these items?')) {
      deleteRecords(selectedIds);
    }
  });
});

function edit(id){
  $('#spinnerLoading').show();
  $.post(apiUrl, {action: 'get', id: id}, function(obj){
      if(obj.status === 'success'){
          $('#addModal').find('#id').val(obj.message.id);
          $('#addModal').find('#name').val(obj.message.name);
          $('#addModal').find('#description').val(obj.message.description);
          $('#addModal').find('#company').val(obj.message.customer).trigger('change');
          $('#addModal').modal('show');
      }
      else{
          toastr["error"](obj.message, "Failed:");
      }
      $('#spinnerLoading').hide();
  });
}

function deactivate(id){
  if (confirm('Are you sure you want to delete this items?')) {
    deleteRecords([id]);
  }
}

function deleteRecords(ids){
  $('#spinnerLoading').show();
  $.post(apiUrl, {action: 'delete', ids: ids}, function(obj){
      if(obj.status === 'success'){
          toastr["success"](obj.message, "Success:");
          table.ajax.reload();
      }
      else{
          toastr["error"](obj.message, "Failed:");
      }
      $('#spinnerLoading').hide();
  });
}

function reactivate(id){
  if (confirm('Are you sure you want to reactivate this items?')) {
    $('#spinnerLoading').show();
    $.post(apiUrl, {action: 'reactivate', id: id}, function(obj){
        if(obj.status === 'success'){
            toastr["success"](obj.message, "Success:");
            table.ajax.reload();
        }
        else{
            toastr["error"](obj.message, "Failed:");
        }
        $('#spinnerLoading').hide();
    });
  }
}
</script>
