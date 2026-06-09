<?php
require_once '../../db_connect.php';
require_once "../../lookup.php";
$db->set_charset("utf8mb4");

session_start();

if(isset($_POST['userID'])){
	$id = filter_input(INPUT_POST, 'userID', FILTER_SANITIZE_STRING);

    if ($update_stmt = $db->prepare("SELECT * FROM packaging_batches WHERE id=?")) {
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
                $message['batch_no'] = $row['batch_no'];
                $message['packaging_date'] = $row['packaging_date'];
                $message['location'] = $row['location'];
                $message['locations'] = searchLocationById($row['location'], $db);
                $message['production_line'] = $row['production_line'];
                $message['production_lines'] = searchLocationById($row['production_line'], $db);
                $message['remarks'] = $row['remarks'];
                $message['status'] = $row['status'];
                $message['company'] = $row['company'];

                // Query packaging_batch_items table
                $weightDetails = array();
                if ($grading_stmt = $db->prepare("SELECT * FROM packaging_batch_items WHERE packaging_batch_id = ? AND deleted = 0")){
                    $grading_stmt->bind_param('s', $row['id']);
                    $grading_stmt->execute();
                    $result2 = $grading_stmt->get_result();
                    while ($row2 = $result2->fetch_assoc()) {
                        $weightDetails[] = array(
                            'id' => $row2['id'],
                            'packaging_batch_id' => $row2['packaging_batch_id'],
                            'category_id' => $row2['category_id'],
                            'product_id' => $row2['product_id'],
                            'product_name' => searchProductNameById($row2['product_id'], $db),
                            'grade' => $row2['grade'],
                            'grade_name' => searchGradeNameById($row2['grade'], $db),
                            'packaging_size' => $row2['packaging_size'],
                            'packaging_size_name' => searchPackagingNameById($row2['packaging_size'], $db),
                            'units_per_box' => $row2['units_per_box'],
                            'weight' => $row2['weight'],
                            'packing_time' => date('H:i:s', strtotime($row2['packing_time'])),
                            'photo_path' => $row2['photo_path'],
                            'status' => $row2['status'],
                        );
                    }
                }
                $message['weightDetails'] = $weightDetails;
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