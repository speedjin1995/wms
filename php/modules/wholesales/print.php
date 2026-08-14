<?php
require_once '../../db_connect.php';
require_once '../../lookup.php';

function arrangeByGrade($weighingDetails) {
    $arranged = [];
    $earliest_time = null;
    $latest_time = null;
    
    if(isset($weighingDetails) && !empty($weighingDetails)) {
        foreach($weighingDetails as $detail) {
            $product = $detail['product'] ?? 'Unknown';
            $grade = $detail['grade'] ?? 'Unknown';
            $key = $product . ' - ' . $grade;
            
            if(!isset($arranged[$key])) {
                $arranged[$key] = [];
            }
            $arranged[$key][] = $detail;
            
            if(isset($detail['time'])) {
                if($earliest_time == null || $detail['time'] < $earliest_time) {
                    $earliest_time = $detail['time'];
                }
                if($latest_time == null || $detail['time'] > $latest_time) {
                    $latest_time = $detail['time'];
                }
            }
        }
    }
    
    return ['arranged' => $arranged, 'earliest_time' => $earliest_time, 'latest_time' => $latest_time];
}

if(isset($_POST['userID'], $_POST['withPhoto'], $_POST['paperSize'])){
    $id = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_STRING);
    $withPhoto = filter_input(INPUT_POST, 'withPhoto', FILTER_SANITIZE_STRING);
    $paperSize = filter_input(INPUT_POST, 'paperSize', FILTER_SANITIZE_STRING);
    $a4Template = filter_input(INPUT_POST, 'a4Template', FILTER_SANITIZE_STRING) ?? 'A4';
    $withDetails = filter_input(INPUT_POST, 'withDetails', FILTER_SANITIZE_STRING) ?? 'N';

    if ($select_stmt = $db->prepare("SELECT * FROM wholesales LEFT JOIN companies ON wholesales.company = companies.id WHERE wholesales.id = ?")) {
        $select_stmt->bind_param('s', $id);

        if (!$select_stmt->execute()) {
            echo json_encode(['status' => 'failed', 'message' => 'Something went wrong went execute']);
        } else {
            $result = $select_stmt->get_result();

            if ($wholesale = $result->fetch_assoc()) {
                $companyDetail = searchCompanyById($wholesale['company'], $db);
                $companyLogoSrc = !empty($wholesale['company_logo']) ? 'php/viewPhoto.php?file=' . urlencode($wholesale['company_logo']) . '&type=file_table' : '';
                $weighingDetails = json_decode($wholesale['weight_details'], true);

                if ($wholesale['status'] == 'STOCK-BAL') {
                    $status = 'Stock Balance';
                } else {
                    $status = ucwords(strtolower($wholesale['status']));
                }

                if ($paperSize == 'A5') {
                    require __DIR__ . '/partial/print/printA5.php';
                } elseif ($paperSize == 'A4' && $a4Template == 'A4Classic') {
                    require __DIR__ . '/partial/print/printA4Classic.php';
                } else {
                    require __DIR__ . '/partial/print/printA4.php';
                }

                echo json_encode(['status' => 'success', 'message' => $message]);
            } else {
                echo json_encode(['status' => 'failed', 'message' => 'Data Not Found']);
            }
        }
    } else {
        echo json_encode(['status' => 'failed', 'message' => 'Something went wrong']);
    }
} else {
    echo json_encode(['status' => 'failed', 'message' => 'Please fill in all the fields']);
}
?>
