<?php
require_once 'db_connect.php';

$raw = file_get_contents("php://input");
$post = json_decode($raw, true);

$company = $post['userId'];
$uid = $post['uid'];

// Optional: validate user belongs to company here

$stmt = $db->prepare("
    SELECT 
        id,
        validation_ref,
        weighbridge_id,
        wb_type,
        staff_name,
        started_datetime,
        ended_datetime,
        created_at,
        calibration_json
    FROM validation
    WHERE company = ?
    AND deleted = '0'
    ORDER BY created_at DESC
    LIMIT 200
");

$stmt->bind_param("s", $company);
$stmt->execute();
$result = $stmt->get_result();

$list = [];

while ($row = $result->fetch_assoc()) {
    $cal = json_decode($row["calibration_json"], true);

    $list[] = [
        "id" => $row["id"],
        "validation_ref" => $row["validation_ref"],
        "weighbridge_id" => $row["weighbridge_id"],
        "wb_type" => $row["wb_type"],
        "staff" => $row["staff_name"],
        "started_datetime" => $row["started_datetime"],
        "ended_datetime" => $row["ended_datetime"],
        "created_at" => $row["created_at"],
        "calibration" => $cal   // JSON decoded
    ];
}

echo json_encode([
    "status" => "success",
    "message" => $list
]);
