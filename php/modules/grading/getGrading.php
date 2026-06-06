<?php
require_once '../../db_connect.php';
require_once "../../lookup.php";
$db->set_charset("utf8mb4");

session_start();

if(isset($_POST['userID'])){
	$id = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_STRING);

    if ($update_stmt = $db->prepare("SELECT * FROM grading WHERE id=?")) {
        $update_stmt->bind_param('s', $id);
        
        // Execute the prepared query.
        if (! $update_stmt->execute()) {
            echo json_encode(
                array(
                    "status" => "failed",
                    "message" => "Something went wrong"
                )); 
        }
        else{
            $result = $update_stmt->get_result();
            $message = array();
            
            while ($row = $result->fetch_assoc()) {
                $message['id'] = $row['id'];
                $message['grading_no'] = $row['grading_no'];
                $message['location_id'] = $row['location_id'];
                $message['location'] = searchLocationById($row['location_id'], $db);
                $message['indicator'] = $row['indicator'];
                $message['start_date'] = $row['start_date'];
                $message['end_date'] = $row['end_date'];
                $message['product_category'] = $row['product_category'];
                $message['category'] = searchCategoryById($row['product_category'], $db);
                $message['remark'] = $row['remark'];
                $message['customers'] = $row['customers'];

                // Query grading_items table
                $weightDetails = array();
                $rejectDetails = array();
                if ($grading_stmt = $db->prepare("SELECT * FROM grading_items WHERE grading_id = ? AND deleted = 0")){
                    $grading_stmt->bind_param('s', $row['id']);
                    $grading_stmt->execute();
                    $result2 = $grading_stmt->get_result();
                    while ($row2 = $result2->fetch_assoc()) {
                        if ($row2['to_grade'] == 'REJ'){
                            $rejectDetails[] = array(
                                'id' => $row2['id'],
                                'grading_id' => $row2['grading_id'],
                                'product_id' => $row2['product_id'],
                                'product_name' => searchProductNameById($row2['product_id'], $db),
                                'wholesales_id' => $row2['wholesales_id'],
                                'from_grade' => $row2['from_grade'],
                                'to_grade' => $row2['to_grade'],
                                'gross_weight' => $row2['gross_weight'],
                                'tare_weight' => $row2['tare_weight'],
                                'nett_weight' => $row2['nett_weight'],
                                'weighing_time' => date('H:i:s', strtotime($row2['weighing_time'])),
                                'photo_path' => $row2['photo_path'],
                            );
                        }else{
                            $weightDetails[] = array(
                                'id' => $row2['id'],
                                'grading_id' => $row2['grading_id'],
                                'product_id' => $row2['product_id'],
                                'product_name' => searchProductNameById($row2['product_id'], $db),
                                'wholesales_id' => $row2['wholesales_id'],
                                'from_grade' => $row2['from_grade'],
                                'to_grade' => $row2['to_grade'],
                                'gross_weight' => $row2['gross_weight'],
                                'tare_weight' => $row2['tare_weight'],
                                'nett_weight' => $row2['nett_weight'],
                                'weighing_time' => date('H:i:s', strtotime($row2['weighing_time'])),
                                'photo_path' => $row2['photo_path'],
                            );
                        }
                        
                    }
                }
                $message['weightDetails'] = $weightDetails;
                $message['rejectDetails'] = $rejectDetails;
            }
            
            echo json_encode(
                array(
                    "status" => "success",
                    "message" => $message
                ));   
        }
    }
}
else{
    echo json_encode(
        array(
            "status" => "failed",
            "message" => "Missing Attribute"
            )); 
}
?>