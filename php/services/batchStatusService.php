<?php

/**
 * Recalculates and updates a packaging_batch status based on its items.
 * all completed → completed | some completed → partial | none completed → pending
 */
function syncBatchStatus($db, $batchId, $userId) {
    $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(status = 'completed') as done FROM packaging_batch_items WHERE packaging_batch_id = ? AND deleted = 0");
    $stmt->bind_param('s', $batchId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $total  = (int)$row['total'];
    $done   = (int)$row['done'];
    $status = ($done === 0) ? 'pending' : (($done === $total) ? 'completed' : 'partial');

    $upd = $db->prepare("UPDATE packaging_batches SET status = ?, modified_by = ? WHERE id = ?");
    $upd->bind_param('sss', $status, $userId, $batchId);
    $upd->execute();
    $upd->close();
}
