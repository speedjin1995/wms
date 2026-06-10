<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../db_connect.php';
require_once '../../services/stockManagementService.php';
require_once '../../services/batchStatusService.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

function generateLoadingNo($db, $company) {
    $today     = date('Ymd');
    $dateStart = date('Y-m-d') . ' 00:00:00';

    $stmt = $db->prepare("SELECT COUNT(*) FROM loading_orders WHERE company = ? AND created_date >= ?");
    $stmt->bind_param('ss', $company, $dateStart);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_row()[0] + 1;
    $stmt->close();

    do {
        $no  = 'LO' . $today . str_pad($count, 4, '0', STR_PAD_LEFT);
        $chk = $db->prepare("SELECT COUNT(*) FROM loading_orders WHERE loading_no = ?");
        $chk->bind_param('s', $no);
        $chk->execute();
        $exists = (int)$chk->get_result()->fetch_row()[0];
        $chk->close();
        if ($exists === 0) break;
        $count++;
    } while (true);

    return $no;
}

if (isset($_POST['loadingDate'], $_POST['shipmentType'])) {
    $userID = $_SESSION['userID'];
    $company = $_SESSION['customer'];
    $loadingDate  = DateTime::createFromFormat('d/m/Y H:i', $_POST['loadingDate'])->format('Y-m-d H:i:s');
    $shipmentType = $_POST['shipmentType'];
    $remarks = null;
    $items = $_POST['items'] ?? [];

    if(isset($_POST['remarks']) && $_POST['remarks'] != null && $_POST['remarks'] != ''){
		$remarks = $_POST['remarks'];
	}

    if (isset($_POST['id']) && $_POST['id'] != null && $_POST['id'] != '') {
        $id = $_POST['id'];

        if ($update_stmt = $db->prepare("UPDATE loading_orders SET loading_date=?, shipment_type=?, remarks=?, modified_by=? WHERE id=?")) {
            $update_stmt->bind_param('sssss', $loadingDate, $shipmentType, $remarks, $userID, $id);

            if (!$update_stmt->execute()) {
                echo json_encode(['status' => 'failed', 'message' => $update_stmt->error]);
            } else {
                $update_stmt->close();

                // Fetch previous items before reverting
                $prevStmt = $db->prepare("SELECT * FROM loading_order_items WHERE loading_order_id = ? AND deleted = 0");
                $prevStmt->bind_param('s', $id);
                $prevStmt->execute();
                $prevItems = $prevStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $prevStmt->close();

                // Build lookup maps
                $prevMap = [];  // packaging_batch_item_id => row
                foreach ($prevItems as $prev) {
                    $prevMap[(string)$prev['packaging_batch_item_id']] = $prev;
                }
                $newMap = [];   // packaging_batch_item_id => new item
                foreach ($items as $item) {
                    $newMap[(string)$item['packaging_batch_item_id']] = $item;
                }

                $affectedBatches = [];

                // Step 1: revert ALL previous batch items to pending first
                foreach ($prevItems as $prev) {
                    $revertStmt = $db->prepare("UPDATE packaging_batch_items SET status = 'pending' WHERE id = ?");
                    $revertStmt->bind_param('s', $prev['packaging_batch_item_id']);
                    $revertStmt->execute();
                    $revertStmt->close();

                    $batchStmt = $db->prepare("SELECT packaging_batch_id FROM packaging_batch_items WHERE id = ?");
                    $batchStmt->bind_param('s', $prev['packaging_batch_item_id']);
                    $batchStmt->execute();
                    $batchRow = $batchStmt->get_result()->fetch_assoc();
                    $batchStmt->close();
                    if ($batchRow) $affectedBatches[] = $batchRow['packaging_batch_id'];
                }

                // Step 2: reverse all stock after all items are pending
                if (in_array('stocks', $_SESSION['products'])) {
                    foreach ($prevItems as $prev) {
                        processPackagedStock($db, $prev['product_id'], $prev['grade'], $prev['packaging_size'], 1, $company, $userID, $id, $prev['customer_id'], 'REVERSAL');
                    }
                }

                // Step 3: soft-delete removed items, update kept items, insert new items
                foreach ($prevMap as $pbiId => $prev) {
                    if (!isset($newMap[(string)$pbiId])) {
                        // removed — soft-delete
                        $delOneStmt = $db->prepare("UPDATE loading_order_items SET deleted = 1 WHERE id = ?");
                        $delOneStmt->bind_param('s', $prev['id']);
                        $delOneStmt->execute();
                        $delOneStmt->close();
                    }
                }

                // Step 4: insert new items or update existing, mark completed, dispatch stock
                foreach ($items as $item) {
                    $loadingTime = date('Y-m-d') . ' ' . $item['loading_time'] . ':00';

                    if (isset($prevMap[(string)$item['packaging_batch_item_id']])) {
                        // existing row — update in place
                        $updItem = $db->prepare("UPDATE loading_order_items SET customer_id=?, loading_time=?, remarks=? WHERE id=?");
                        $prevId  = $prevMap[(string)$item['packaging_batch_item_id']]['id'];
                        $updItem->bind_param('ssss', $item['customer_id'], $loadingTime, $item['remarks'], $prevId);
                        $updItem->execute();
                        $updItem->close();
                    } else {
                        // new item — insert
                        if ($insert_stmt2 = $db->prepare("INSERT INTO loading_order_items (loading_order_id, packaging_batch_item_id, customer_id, product_id, grade, packaging_size, units_per_box, weight, loading_time, remarks) VALUES (?,?,?,?,?,?,?,?,?,?)")) {
                            $insert_stmt2->bind_param('ssssssssss', $id, $item['packaging_batch_item_id'], $item['customer_id'], $item['product_id'], $item['grade'], $item['packaging_size'], $item['units_per_box'], $item['weight'], $loadingTime, $item['remarks']);
                            $insert_stmt2->execute();
                            $insert_stmt2->close();
                        }
                    }

                    $markStmt = $db->prepare("UPDATE packaging_batch_items SET status = 'completed' WHERE id = ?");
                    $markStmt->bind_param('s', $item['packaging_batch_item_id']);
                    $markStmt->execute();
                    $markStmt->close();

                    if (in_array('stocks', $_SESSION['products'])) {
                        processPackagedStock($db, $item['product_id'], $item['grade'], $item['packaging_size'], 1, $company, $userID, $id, $item['customer_id'], 'DISPATCH');
                    }

                    $affectedBatches[] = $item['packaging_batch_id'];
                }

                foreach (array_unique($affectedBatches) as $batchId) {
                    syncBatchStatus($db, $batchId, $userID);
                }

                $db->close();
                echo json_encode(['status' => 'success', 'message' => 'Updated Successfully!!']);
            }
        } else {
            echo json_encode(['status' => 'failed', 'message' => 'Failed to prepare statement']);
        }
    } else {
        $loadingNo = generateLoadingNo($db, $company);

        if ($insert_stmt = $db->prepare("INSERT INTO loading_orders (loading_no, loading_date, shipment_type, remarks, company, created_by, status) VALUES (?,?,?,?,?,?,'pending')")) {
            $insert_stmt->bind_param('ssssss', $loadingNo, $loadingDate, $shipmentType, $remarks, $company, $userID);

            if (!$insert_stmt->execute()) {
                echo json_encode(
                    array(
                        "status"=> "failed", 
                        "message"=> $insert_stmt->error
                    )
                );
            } else {
                $loadingOrderId  = $insert_stmt->insert_id;
                $insert_stmt->close();

                $affectedBatches = [];

                foreach ($items as $item) {
                    $loadingTime = date('Y-m-d') . ' ' . $item['loading_time'] . ':00';

                    if ($insert_stmt2 = $db->prepare("INSERT INTO loading_order_items (loading_order_id, packaging_batch_item_id, customer_id, product_id, grade, packaging_size, units_per_box, weight, loading_time, remarks) VALUES (?,?,?,?,?,?,?,?,?,?)")) {
                        $insert_stmt2->bind_param('ssssssssss', $loadingOrderId, $item['packaging_batch_item_id'], $item['customer_id'], $item['product_id'], $item['grade'], $item['packaging_size'], $item['units_per_box'], $item['weight'], $loadingTime, $item['remarks']);
                        $insert_stmt2->execute();
                        $insert_stmt2->close();
                    }

                    $markStmt = $db->prepare("UPDATE packaging_batch_items SET status = 'completed' WHERE id = ?");
                    $markStmt->bind_param('s', $item['packaging_batch_item_id']);
                    $markStmt->execute();
                    $markStmt->close();

                    if (in_array('stocks', $_SESSION['products'])) {
                        processPackagedStock($db, $item['product_id'], $item['grade'], $item['packaging_size'], 1, $company, $userID, $loadingOrderId, $item['customer_id'], 'DISPATCH');
                    }

                    $affectedBatches[] = $item['packaging_batch_id'];
                }

                foreach (array_unique($affectedBatches) as $batchId) {
                    syncBatchStatus($db, $batchId, $userID);
                }

                $db->close();
                echo json_encode(
                    array(
                        "status"=> "success", 
                        "message"=> "Added Successfully!!" 
                    )
                );
            }
        } else {
            echo json_encode(
                array(
                    "status"=> "failed", 
                    "message"=> "Failed to prepare statement"
                )
            );
        }
    }
} else {
    echo json_encode(
        array(
            "status"=> "failed", 
            "message"=> "Please fill in all the fields"
        )
    );
}

?>
