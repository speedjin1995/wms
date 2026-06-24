<?php
require_once 'php/db_connect.php';
session_start();

if (!isset($_SESSION['userID'])) {
    echo '<script>window.location.href = "login.html";</script>';
    exit;
}

$language      = $_SESSION['language'];
$languageArray = $_SESSION['languageArray'];
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Inventory</h1>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Product Inventory</h3>
                    </div>
                    <div class="card-body">
                        <table id="inventoryTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th><?=$languageArray['item_code'][$language]?></th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    $("#inventoryTable").DataTable({
        "responsive": true,
        "autoWidth": false,
        "processing": true,
        "serverSide": true,
        "serverMethod": "post",
        "ajax": {
            "url": "php/loadInventory.php"
        },
        "columns": [
            { data: "product_name" },
            { data: "quantity" },
            {
                data: "status",
                render: function (data) {
                    return data == 0
                        ? '<span class="badge badge-success">Active</span>'
                        : '<span class="badge badge-danger">Inactive</span>';
                }
            }
        ]
    });
});
</script>
