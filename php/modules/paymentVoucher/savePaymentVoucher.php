<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once '../../db_connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

function generateVoucherNo($db, $company) {
    $today = date('Ymd');
    $dateStart = date('Y-m-d') . ' 00:00:00';

    $stmt = $db->prepare("SELECT COUNT(*) FROM payment_vouchers WHERE company = ? AND created_date >= ?");
    $stmt->bind_param('ss', $company, $dateStart);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_row()[0] + 1;
    $stmt->close();

    do {
        $no = 'PV' . $today . str_pad($count, 4, '0', STR_PAD_LEFT);
        $chk = $db->prepare("SELECT COUNT(*) FROM payment_vouchers WHERE voucher_no = ? AND company = ?");
        $chk->bind_param('ss', $no, $company);
        $chk->execute();
        $exists = (int)$chk->get_result()->fetch_row()[0];
        $chk->close();
        if ($exists === 0) break;
        $count++;
    } while (true);

    return $no;
}

function generateInvoiceNo($db, $company) {
    $today = date('Ymd');
    $dateStart = date('Y-m-d') . ' 00:00:00';

    $stmt = $db->prepare("SELECT COUNT(*) FROM payment_vouchers WHERE company = ? AND created_date >= ?");
    $stmt->bind_param('ss', $company, $dateStart);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_row()[0] + 1;
    $stmt->close();

    do {
        $no = 'INV' . $today . str_pad($count, 4, '0', STR_PAD_LEFT);
        $chk = $db->prepare("SELECT COUNT(*) FROM payment_vouchers WHERE invoice_no = ? AND company = ?");
        $chk->bind_param('ss', $no, $company);
        $chk->execute();
        $exists = (int)$chk->get_result()->fetch_row()[0];
        $chk->close();
        if ($exists === 0) break;
        $count++;
    } while (true);

    return $no;
}

if (isset($_POST['entityId']) && $_POST['entityId'] != null && $_POST['entityId'] != '' &&
    isset($_POST['voucherDate']) && $_POST['voucherDate'] != null && $_POST['voucherDate'] != '') {

    $userID = $_SESSION['userID'];
    $company = $_SESSION['customer'];
    $module = $_SESSION['module'] ?? 'wholesales';
    $transactionStatus = isset($_POST['transactionStatus']) && $_POST['transactionStatus'] != '' ? $_POST['transactionStatus'] : (($module == 'industrial') ? 'INCOMING' : 'RECEIVING');
    $isIncoming = in_array($transactionStatus, ['RECEIVING', 'INCOMING']);

    $entityId = filter_input(INPUT_POST, 'entityId', FILTER_SANITIZE_NUMBER_INT);
    $voucherDate = DateTime::createFromFormat('d/m/Y', $_POST['voucherDate'])->format('Y-m-d');

    $invoiceNo = null;
    if (isset($_POST['invoiceNo']) && $_POST['invoiceNo'] != null && $_POST['invoiceNo'] != '') {
        $invoiceNo = filter_input(INPUT_POST, 'invoiceNo', FILTER_SANITIZE_STRING);
    }

    $unitPrice = '0';
    if (isset($_POST['unitPrice']) && $_POST['unitPrice'] != null && $_POST['unitPrice'] != '') {
        $unitPrice = filter_input(INPUT_POST, 'unitPrice', FILTER_SANITIZE_STRING);
    }

    $tax = '0';
    if (isset($_POST['tax']) && $_POST['tax'] != null && $_POST['tax'] != '') {
        $tax = filter_input(INPUT_POST, 'tax', FILTER_SANITIZE_STRING);
    }

    $totalNett = '0';
    if (isset($_POST['totalNettWeight']) && $_POST['totalNettWeight'] != null && $_POST['totalNettWeight'] != '') {
        $totalNett = filter_input(INPUT_POST, 'totalNettWeight', FILTER_SANITIZE_STRING);
    }

    $totalAmount = '0';
    if (isset($_POST['totalAmount']) && $_POST['totalAmount'] != null && $_POST['totalAmount'] != '') {
        $totalAmount = filter_input(INPUT_POST, 'totalAmount', FILTER_SANITIZE_STRING);
    }

    $deductionAmount = '0';
    if (isset($_POST['deductionAmount']) && $_POST['deductionAmount'] != null && $_POST['deductionAmount'] != '') {
        $deductionAmount = filter_input(INPUT_POST, 'deductionAmount', FILTER_SANITIZE_STRING);
    }

    $additionAmount = '0';
    if (isset($_POST['additionAmount']) && $_POST['additionAmount'] != null && $_POST['additionAmount'] != '') {
        $additionAmount = filter_input(INPUT_POST, 'additionAmount', FILTER_SANITIZE_STRING);
    }

    $finalAmount = $totalAmount;
    if (isset($_POST['finalAmount']) && $_POST['finalAmount'] != null && $_POST['finalAmount'] != '') {
        $finalAmount = filter_input(INPUT_POST, 'finalAmount', FILTER_SANITIZE_STRING);
    }

    $deductionDetails = null;
    if (isset($_POST['deductionDetails']) && $_POST['deductionDetails'] != null && $_POST['deductionDetails'] != '') {
        $deductionDetails = filter_input(INPUT_POST, 'deductionDetails', FILTER_SANITIZE_STRING);
    }

    $additionDetails = null;
    if (isset($_POST['additionDetails']) && $_POST['additionDetails'] != null && $_POST['additionDetails'] != '') {
        $additionDetails = filter_input(INPUT_POST, 'additionDetails', FILTER_SANITIZE_STRING);
    }

    $wholesaleIds = $_POST['wholesaleIds'] ?? [];

    if (empty($wholesaleIds)) {
        echo json_encode(['status' => 'failed', 'message' => 'No records selected']);
        exit;
    }

    if (isset($_POST['pvId']) && $_POST['pvId'] != null && $_POST['pvId'] != '') {
        $pvId = filter_input(INPUT_POST, 'pvId', FILTER_SANITIZE_NUMBER_INT);

        // Update PV header
        if ($update_stmt = $db->prepare("UPDATE payment_vouchers SET voucher_date=?, invoice_no=?, unit_price=?, tax=?, total_nett_weight=?, total_amount=?, deduction_amount=?, addition_amount=?, final_amount=?, deduction_details=?, addition_details=?, modified_by=? WHERE id=?")) {
            $update_stmt->bind_param('sssssssssssss', $voucherDate, $invoiceNo, $unitPrice, $tax, $totalNett, $totalAmount, $deductionAmount, $additionAmount, $finalAmount, $deductionDetails, $additionDetails, $userID, $pvId);

            if (!$update_stmt->execute()) {
                echo json_encode(['status' => 'failed', 'message' => $update_stmt->error]);
                exit;
            }
            $update_stmt->close();
        }
    } else {
        $voucherNo = generateVoucherNo($db, $company);
        $invoiceNo = generateInvoiceNo($db, $company);

        if ($insert_stmt = $db->prepare("INSERT INTO payment_vouchers (entity_id, status, voucher_no, voucher_date, invoice_no, unit_price, tax, total_nett_weight, total_amount, deduction_amount, addition_amount, final_amount, deduction_details, addition_details, company, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")) {
            $insert_stmt->bind_param('ssssssssssssssss', $entityId, $transactionStatus, $voucherNo, $voucherDate, $invoiceNo, $unitPrice, $tax, $totalNett, $totalAmount, $deductionAmount, $additionAmount, $finalAmount, $deductionDetails, $additionDetails, $company, $userID);

            if (!$insert_stmt->execute()) {
                echo json_encode(['status' => 'failed', 'message' => $insert_stmt->error]);
                exit;
            }
            $pvId = $insert_stmt->insert_id;
            $insert_stmt->close();
        } else {
            echo json_encode(['status' => 'failed', 'message' => 'Failed to prepare statement']);
            exit;
        }
    }

    // Link selected wholesales records and update unit_price
    foreach ($wholesaleIds as $wId) {
        if ($linkStmt = $db->prepare("UPDATE wholesales SET pv_id = ?, modified_by=? WHERE id = ?")) {
            $linkStmt->bind_param('sss', $pvId, $userID, $wId);
            $linkStmt->execute();
            $linkStmt->close();
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => isset($_POST['pvId']) && $_POST['pvId'] != '' ? 'Updated Successfully!!' : 'Saved Successfully!!'
    ]);
} else {
    echo json_encode(['status' => 'failed', 'message' => 'Please fill in all required fields']);
}
