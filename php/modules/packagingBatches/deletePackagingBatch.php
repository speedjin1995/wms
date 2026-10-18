<?php
require_once '../../db_connect.php';
require_once '../../services/stockManagementService.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

if(!isset($_SESSION['userID'])){
	echo '<script type="text/javascript">location.href = "../login.html";</script>';
	exit;
}

if(isset($_POST['id'], $_POST['cancelReason'])){
	$userID  = $_SESSION['userID'];
	$company = $_SESSION['customer'];
	$id      = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
	$deleteReason = filter_input(INPUT_POST, 'cancelReason', FILTER_SANITIZE_STRING);

	// The batch must belong to the session company
	$ownStmt = $db->prepare("SELECT id FROM packaging_batches WHERE id = ? AND company = ? AND deleted = '0'");
	$ownStmt->bind_param('ss', $id, $company);
	$ownStmt->execute();
	$owned = $ownStmt->get_result()->fetch_assoc();
	$ownStmt->close();
	if (!$owned) {
		echo json_encode(array("status" => "failed", "message" => "Record not found"));
		exit;
	}

	$db->begin_transaction();

	if (in_array('stocks', $_SESSION['products'])) {
		// Fetch existing items to reverse stock
		$prevStmt = $db->prepare("SELECT * FROM packaging_batch_items WHERE packaging_batch_id = ? AND deleted = 0");
		$prevStmt->bind_param('s', $id);
		$prevStmt->execute();
		$prevItems = $prevStmt->get_result()->fetch_all(MYSQLI_ASSOC);
		$prevStmt->close();

		$stockResult = processPackagingBatch($db, $id, $company, $userID, 'DELETE', [], $prevItems);
		if ($stockResult['status'] !== 'success') {
			$db->rollback();
			$db->close();
			echo json_encode(array("status" => "failed", "message" => $stockResult['message']));
			exit;
		}
	}

	$del = '1';
	$stmt2 = $db->prepare("UPDATE packaging_batches SET deleted=?, delete_reason=?, modified_by=? WHERE id=?");
	$stmt2->bind_param('ssss', $del, $deleteReason, $userID, $id);

	if ($stmt2->execute()) {
		$stmt2->close();
		$db->commit();
		$db->close();
		echo json_encode(
			array(
				"status"=> "success",
				"message"=> "Deleted"
			)
		);
	} else {
		$db->rollback();
		echo json_encode(
			array(
				"status"=> "failed",
				"message"=> $stmt2->error
			)
		);
	}
}
else{
    echo json_encode(
        array(
            "status"=> "failed",
            "message"=> "Please fill in all the fields"
        )
    );
}
?>
