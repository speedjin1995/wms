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

<div class="content-header custom-title-content-box">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1 class="custom-title">Inventory</h1>
            </div>
        </div>
    </div>
</div>

<div class="content custom-table-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header custom-card-header">
                        <h3 class="custom-card-header-title">Product Inventory</h3>
                    </div>
                    <div class="card-body custom-table-card-body">
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
                        ? '<span class="badge custom-badge-activate">Active</span>'
                        : '<span class="badge custom-badge-deactivate">Inactive</span>';
                }
            }
        ]
    });
});
</script>
