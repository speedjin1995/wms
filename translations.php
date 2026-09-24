<?php
require_once 'php/db_connect.php';

session_start();

if (!isset($_SESSION['userID'])) {
  echo '<script type="text/javascript">';
  echo 'window.location.href = "login.html";</script>';
} else {
  $company = $_SESSION['customer'];
  $user = $_SESSION['userID'];
  $role = $_SESSION['role'];
  $companies = $db->query("SELECT * FROM companies WHERE deleted = 0 ORDER BY name ASC");
}
?>

<div class="content-header" style="padding-bottom: 0;">
  <div class="container-fluid"></div>
</div>

<section class="content page-modern">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card results-card show-dt-controls">
          <div class="card-header">
            <div class="results-header-left">
              <h3 class="results-title"><i class="fas fa-language mr-2"></i>Translations</h3>
            </div>
            <div class="results-header-right d-flex flex-wrap" style="gap: 0.5rem;">
              <button type="button" class="btn btn-action btn-action-primary" id="addTranslation">
                <i class="fas fa-plus"></i> Add Translation
              </button>
            </div>
          </div>
          <div class="card-body">
            <table id="translationTable" class="table data-table">
              <thead>
                <tr>
                  <th>No.</th>
                  <th>Message Key Code</th>
                  <th>English</th>
                  <th>中文</th>
                  <th>Bahasa Malaysia</th>
                  <th>தமிழ்</th>
                  <th>日本語</th>
                  <th width="10%">Actions</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="modal fade modal-modern" id="translationModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content custom-model-content-box">
      <form role="form" id="translationForm">
        <div class="modal-header custom-model-header-box">
          <h4 class="modal-title custom-model-title-txt">Add Translation</h4>
          <button type="button" class="close custom-btn-close-icon" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="keyId" name="keyId">

          <!-- Company (SADMIN only) -->
          <div class="modal-section" <?php if($role != 'SADMIN'){ echo 'style="display:none;"'; } ?>>
            <div class="form-group mb-0">
              <label class="form-label-modern">Company <span class="text-danger">*</span></label>
              <select class="form-control select2" style="width: 100%;" id="company" name="company" required>
                <?php while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                  <option value="<?=$rowCompany['id'] ?>" <?php if($rowCompany['id'] == $company) echo 'selected'; ?>><?=$rowCompany['name'] ?></option>
                <?php } ?>
              </select>
            </div>
          </div>

          <!-- Translation Details -->
          <div class="modal-section">
            <div class="section-title"><i class="fas fa-key mr-2"></i>Translation Key</div>
            <div class="form-group mb-0">
              <label class="form-label-modern">Message Key Code <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="keyCode" name="keyCode" placeholder="Message Key code" required>
            </div>
          </div>

          <!-- Languages -->
          <div class="modal-section">
            <div class="section-title"><i class="fas fa-globe mr-2"></i>Languages</div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label-modern">English <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="englishDecs" name="englishDecs" placeholder="English" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label-modern">中文</label>
                  <input type="text" class="form-control" id="chineseDecs" name="chineseDecs" placeholder="中文">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label-modern">Bahasa Malaysia</label>
                  <input type="text" class="form-control" id="malayDecs" name="malayDecs" placeholder="Bahasa">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label-modern">தமிழ்</label>
                  <input type="text" class="form-control" id="tamilDecs" name="tamilDecs" placeholder="தமிழ்">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group mb-0">
                  <label class="form-label-modern">日本語</label>
                  <input type="text" class="form-control" id="japaneseDecs" name="japaneseDecs" placeholder="日本語">
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-modern btn-modern-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-modern btn-modern-primary" id="submitTranslation">Submit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  var table;

$(function () {
  table = $("#translationTable").DataTable({
    "responsive": true,
    "autoWidth": false,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'order': [[ 1, 'asc' ]],
    'ajax': {
      'url':'php/modules/translations/loadTranslations.php'
    },
    'columns': [
      { data: 'counter' },
      { data: 'message_key_code' },
      { data: 'en' },
      { data: 'zh' },
      { data: 'my' },
      { data: 'ne' },
      { data: 'ja' },
      { 
        data: 'id',
        render: function ( data, type, row ) {
          return '<div class="d-flex" style="gap:4px;"><button type="button" onclick="edit('+data+')" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></button><button type="button" onclick="deactivate('+data+')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button></div>';
        }
      ]
    });
    
    $.validator.setDefaults({
      submitHandler: function () {
        $.post('php/modules/translations/translations.php', $('#translationForm').serialize(), function(data){
          var obj = JSON.parse(data); 
          
          if (obj.status === 'success') {
            $('#translationModal').modal('hide');
            toastr["success"](obj.message, "Success:");
            table.ajax.reload();
          } else if (obj.status === 'failed') {
            toastr["error"](obj.message, "Failed:");
          } else {
            toastr["error"]("Something went wrong", "Failed:");
          }
        });
      }
    });

    $('#addTranslation').on('click', function() {
      $('#translationModal').find('#keyId').val('');
      $('#translationModal').find('#keyCode').val('');
      $('#translationModal').find('#englishDecs').val('');
      $('#translationModal').find('#chineseDecs').val('');
      $('#translationModal').find('#malayDecs').val('');
      $('#translationModal').find('#japaneseDecs').val('');
      $('#translationModal').find('#tamilDecs').val('');
      $('#translationModal').modal('show');
      
      $('#translationForm').validate({
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
  });

  function edit(id) {
    $.post('php/modules/translations/getTranslation.php', {messageId: id}, function(data) {
      var obj = JSON.parse(data);
      
      if (obj.status === 'success') {
        $('#translationModal').find('#keyId').val(obj.message.id);
        $('#translationModal').find('#keyCode').val(obj.message.message_key_code);
        $('#translationModal').find('#englishDecs').val(obj.message.en);
        $('#translationModal').find('#chineseDecs').val(obj.message.zh);
        $('#translationModal').find('#malayDecs').val(obj.message.my);
        $('#translationModal').find('#japaneseDecs').val(obj.message.ja);
        $('#translationModal').find('#tamilDecs').val(obj.message.ne);
        $('#translationModal').find('#company').val(obj.message.company).trigger('change');
        $('#translationModal').modal('show');
        
        $('#translationForm').validate({
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
      } else if (obj.status === 'failed') {
        toastr["error"](obj.message, "Failed:");
      } else {
        toastr["error"]("Something went wrong", "Failed:");
      }
    });
  }

  function deactivate(id) {
    if (confirm('Are you sure you want to delete this item?')) {
      $.post('php/modules/translations/deleteMessage.php', {messageId: id}, function(data) {
        var obj = JSON.parse(data);
        
        if(obj.status === 'success'){
          toastr["success"](obj.message, "Success:");
          table.ajax.reload();
        } else if (obj.status === 'failed') {
          toastr["error"](obj.message, "Failed:");
        } else {
          toastr["error"]("Something went wrong", "Failed:");
        }
      });
    }
  }
</script>