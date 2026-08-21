<?php
require_once '../../db_connect.php';
require_once '../../lookup.php';
session_start();
$company  = $_SESSION['customer'];
$user = $_SESSION['userID'];
$module = $_SESSION['module'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get company data
    $company_stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
    $company_stmt->bind_param('s', $company);
    $company_stmt->execute();
    $companyData = $company_stmt->get_result()->fetch_assoc();
    $company_stmt->close();
    $entityId = intval($_GET['entity_id']);
    $products = json_decode($companyData['products']);

    // Build exclusion list based on products and company
    $excludeStatuses = [];
    if (!in_array('stocks', (array)$products)) {
        $excludeStatuses[] = 'STOCK-BAL';
    }
    if ($company != 14) {
        $excludeStatuses[] = 'NITROGEN';
        $excludeStatuses[] = 'REJECT';
    }

    // Load statuses for this module + Customer entity type
    $statuses = [];
    $stmt = $db->prepare("SELECT id, status, prefix FROM statuses WHERE module = ? AND entity_type = 'Customer'");
    $stmt->bind_param('s', $module);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if (!in_array($row['status'], $excludeStatuses)) {
            $statuses[] = $row;
        }
    }
    $stmt->close();

    // Load existing running_no_entity rows for this entity
    $existing = [];
    $stmt = $db->prepare("SELECT transaction_status, prefix, value FROM running_no_entity WHERE company_id = ? AND module = ? AND entity_id = ?");
    $stmt->bind_param('sss', $company, $module, $entityId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $existing[$row['transaction_status']] = $row;
    }
    $stmt->close();

    // Load invoice_code from customers table
    $customerRow = ['invoice_code' => ''];
    $stmt = $db->prepare("SELECT invoice_code FROM customers WHERE id = ? AND customer = ?");
    $stmt->bind_param('ii', $entityId, $company);
    $stmt->execute();
    $customerRow = $stmt->get_result()->fetch_assoc() ?: $customerRow;
    $stmt->close();

    // Merge
    foreach ($statuses as &$s) {
        if (isset($existing[$s['status']])) {
            $s['saved_prefix'] = $existing[$s['status']]['prefix'];
            $s['value'] = $existing[$s['status']]['value'];
        } else {
            $s['saved_prefix'] = $s['prefix'];
            $s['value'] = 1;
        }
    }

    echo json_encode(['status' => 'success', 'data' => $statuses, 'invoice_code' => $customerRow['invoice_code'] ?? '']);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entityId = intval($_POST['entity_id']);
    $invoiceCode = trim($_POST['invoice_code'] ?? '');
    $rows = $_POST['rows'] ?? [];

    // Update invoice_code on customers table
    $stmt = $db->prepare("UPDATE customers SET invoice_code = ? WHERE id = ? AND customer = ?");
    $stmt->bind_param('sii', $invoiceCode, $entityId, $company);
    $stmt->execute();
    $stmt->close();

    foreach ($rows as $row) {
        $status = $row['transaction_status'];
        $prefix = $row['prefix'];
        $value  = intval($row['value']);

        // Check if exists
        $stmt = $db->prepare("SELECT id FROM running_no_entity WHERE company_id = ? AND module = ? AND entity_id = ? AND transaction_status = ?");
        $stmt->bind_param('ssss', $company, $module, $entityId, $status);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            $stmt = $db->prepare("UPDATE running_no_entity SET prefix = ?, value = ? WHERE company_id = ? AND module = ? AND entity_id = ? AND transaction_status = ?");
            $stmt->bind_param('ssssss', $prefix, $value, $company, $module, $entityId, $status);
        } else {
            $stmt = $db->prepare("INSERT INTO running_no_entity (company_id, module, transaction_status, entity_id, prefix, value) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssss', $company, $module, $status, $entityId, $prefix, $value);
        }
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['status' => 'success', 'message' => 'Saved Successfully!!']);
}
