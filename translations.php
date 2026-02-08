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
  $companies = $db->query("SELECT * FROM companies WHERE deleted = 0 ORDER BY name ASC");
}
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">Translations</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <div class="row">
              <div class="col-9"><h5 class="card-title mb-0">Translation Records</h5></div>
              <div class="col-3">
                <button type="button" class="btn btn-block bg-gradient-success btn-sm" id="addTranslation">Add Translation</button>
              </div>
            </div>
          </div>
          <div class="card-body">
            <table id="translationTable" class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>No.</th>
                  <th>Message Key Code</th>
                  <th>English</th>
                  <th>中文</th>
                  <th>Bahasa Malaysia</th>
                  <th>தமிழ்</th>
                  <th>日本語</th>
                  <th>Actions</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="modal fade" id="translationModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form role="form" id="translationForm">
        <div class="modal-header">
          <h4 class="modal-title">Add Translation</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="card-body">
            <input type="hidden" id="keyId" name="keyId">
            <div class="form-group" <?php if($user != 2){ echo 'style="display:none;"'; } ?>>
              <label for="code">Company *</label>
              <select class="form-control select2" style="width: 100%;" id="company" name="company" required>
                <?php while($rowCompany=mysqli_fetch_assoc($companies)){ ?>
                  <option value="<?=$rowCompany['id'] ?>" <?php if($rowCompany['id'] == $company) echo 'selected'; ?>><?=$rowCompany['name'] ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="form-group">
              <label for="keyCode">Message Key Code *</label>
              <input type="text" class="form-control" id="keyCode" name="keyCode" placeholder="Message Key code" required>
            </div>
            <div class="form-group">
              <label for="englishDecs">English *</label>
              <input type="text" class="form-control" id="englishDecs" name="englishDecs" placeholder="English" required>
            </div>
            <div class="form-group">
              <label for="chineseDecs">中文</label>
              <input type="text" class="form-control" id="chineseDecs" name="chineseDecs" placeholder="中文">
            </div>
            <div class="form-group">
              <label for="malayDecs">Bahasa Malaysia</label>
              <input type="text" class="form-control" id="malayDecs" name="malayDecs" placeholder="Bahasa">
            </div>
            <div class="form-group">
              <label for="tamilDecs">தமிழ்</label>
              <input type="text" class="form-control" id="tamilDecs" name="tamilDecs" placeholder="தமிழ்">
            </div>
            <div class="form-group">
              <label for="japaneseDecs">日本語</label>
              <input type="text" class="form-control" id="japaneseDecs" name="japaneseDecs" placeholder="日本語">
            </div>
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" id="submitTranslation">Submit</button>
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
      'url':'php/loadTranslations.php'
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
          return '<div class="row"><div class="col-3"><button type="button" id="edit'+data+'" onclick="edit('+data+')" class="btn btn-success btn-sm"><i class="fas fa-pen"></i></button></div><div class="col-3"><button type="button" id="deactivate'+data+'" onclick="deactivate('+data+')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></div></div>';
        }
      }
    ]
  });
  
  $.validator.setDefaults({
    submitHandler: function () {
      $.post('php/translations.php', $('#translationForm').serialize(), function(data){
        var obj = JSON.parse(data); 
        
        if(obj.status === 'success'){
          $('#translationModal').modal('hide');
          toastr["success"](obj.message, "Success:");
          table.ajax.reload();
        }
        else if(obj.status === 'failed'){
          toastr["error"](obj.message, "Failed:");
        }
        else{
          toastr["error"]("Something went wrong", "Failed:");
        }
      });
    }
  });

  $('#addTranslation').on('click', function(){
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

function edit(id){
  $.post('php/getTranslation.php', {messageId: id}, function(data){
    var obj = JSON.parse(data);
    
    if(obj.status === 'success'){
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
    }
    else if(obj.status === 'failed'){
      toastr["error"](obj.message, "Failed:");
    }
    else{
      toastr["error"]("Something went wrong", "Failed:");
    }
  });
}

function deactivate(id){
  if (confirm('Are you sure you want to delete this item?')) {
    $.post('php/deleteMessage.php', {messageId: id}, function(data){
      var obj = JSON.parse(data);
      
      if(obj.status === 'success'){
        toastr["success"](obj.message, "Success:");
        table.ajax.reload();
      }
      else if(obj.status === 'failed'){
        toastr["error"](obj.message, "Failed:");
      }
      else{
        toastr["error"]("Something went wrong", "Failed:");
      }
    });
  }
}
</script>