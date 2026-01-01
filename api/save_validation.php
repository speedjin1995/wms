<?php
require_once 'db_connect.php';

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid JSON payload"
    ]);
    exit;
}

$company         = $data["company"];
$weighbridge_id  = $data["weighbridge_id"];
$staff           = $data["staff"];
$startedDatetime = $data["startedDatetime"];
$createdDatetime = $data["createdDatetime"];
$calibration     = $data["calibration"];   // full map

// Extract header info from calibration JSON
$wb_type            = $calibration["weighbridge_type"];
$surface            = intval($calibration["surface"]);
$unit_load_cell     = intval($calibration["unit_load_cell"]);
$default_load_weight= floatval($calibration["default_load_weight"]);
$load_cell_brand    = $calibration["load_cell_brand"];

$calibration_json = json_encode($calibration);

// Generate validation ref per company per day
$today = date("Ymd");
$stmt = $db->prepare("
    SELECT COUNT(*) as c 
    FROM validation
    WHERE company = ? AND DATE(created_at) = CURDATE()
");
$stmt->bind_param("s", $company);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$running = intval($res["c"]) + 1;
$validation_ref = "V$today-" . str_pad($running, 3, "0", STR_PAD_LEFT);

$endedDatetime = $createdDatetime;

// Insert into DB
$stmt = $db->prepare("
INSERT INTO validation
(
  company,
  validation_ref,
  weighbridge_id,
  wb_type,
  surface,
  unit_load_cell,
  default_load_weight,
  load_cell_brand,
  started_datetime,
  ended_datetime,
  staff_name,
  calibration_json,
  created_at
)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
");

$stmt->bind_param(
  "ssssiddssssss",
  $company,
  $validation_ref,
  $weighbridge_id,
  $wb_type,
  $surface,
  $unit_load_cell,
  $default_load_weight,
  $load_cell_brand,
  $startedDatetime,
  $endedDatetime,
  $staff,
  $calibration_json,
  $createdDatetime
);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Validation saved",
        "validation_ref" => $validation_ref
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => $stmt->error
    ]);
}
