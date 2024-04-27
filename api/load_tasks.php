<?php
require_once 'db_connect.php';

$post = json_decode(file_get_contents('php://input'), true);

$staffId = $post['userId'];
$now = date("Y-m-d 00:00:00");
$end = date("Y-m-d 23:59:59");
$values = array();
$checking = array();
$stmt2 = $db->prepare("SELECT * from users where id= ?");
$stmt2->bind_param('s', $staffId);
$stmt2->execute();
$stmt2 = $stmt2->get_result();

if(($row2 = $stmt2->fetch_assoc()) !== null){
    if($row2['farms'] != null){
        $values = json_decode($row2['farms'], true);
    }  
}

//$stmt = $db->prepare("SELECT * from weighing WHERE created_datetime >= ?");
$stmt = $db->prepare("SELECT * from weighing WHERE status<>'Complete' AND `deleted` = '0' ORDER BY `created_datetime`");
//$stmt->bind_param('ss', $now, $end);
$stmt->execute();
$result = $stmt->get_result();
$message = array();

while($row = $result->fetch_assoc()){
    $farmId=$row['farm_id'];
    $farmName='';
    
    if ($update_stmt = $db->prepare("SELECT * FROM farms WHERE id=?")) {
        $update_stmt->bind_param('s', $farmId);
        
        if ($update_stmt->execute()) {
            $result3 = $update_stmt->get_result();
            
            if ($row3 = $result3->fetch_assoc()) {
                $farmName=$row3['name'];
            }
        }
    }
    
    $update_stmt->close();
    
    if(!in_array($row['id'], $checking)){
        if($row['weighted_by'] != null){
            $temp = json_decode($row['weighted_by'], true);
    
            if(in_array($staffId, $temp)){
                $message[] = array( 
                    'id'=>$row['id'],
                    'serial_no'=>$row['serial_no'],
                    'po_no'=>$row['po_no'],
                    'group_no'=>$row['group_no'],
                    'customer'=>$row['customer'],
                    'supplier'=>$row['supplier'],
                    'product'=>$row['product'],
                    'driver_name'=>$row['driver_name'],
                    'lorry_no'=>$row['lorry_no'],
                    'farm_id'=>$farmId,
                    'farm_name'=>$farmName,
                    'average_cage'=>$row['average_cage'],
                    'average_bird'=>$row['average_bird'],
                    'minimum_weight'=>$row['minimum_weight'],
                    'maximum_weight'=>$row['maximum_weight'],
                    'min_crate'=>$row['min_crate'],
                    'max_crate'=>$row['max_crate'],
                    'weight_data'=>$row['weight_data'],
                    'created_datetime'=>$row['created_datetime'],
                    'start_time'=>$row['start_time'],
                    'end_time'=>$row['end_time'],
                    'grade'=>$row['grade'],
                    'gender'=>$row['gender'],
                    'house_no'=>$row['house_no'],
                    'remark'=>$row['remark']
                );
                
                array_push($checking, $row['id']);
            }
        }
    }
    
    if(!in_array($row['id'], $checking)){
        if(in_array($row['farm_id'], $values)){
            $message[] = array( 
                'id'=>$row['id'],
                'serial_no'=>$row['serial_no'],
                'po_no'=>$row['po_no'],
                'group_no'=>$row['group_no'],
                'customer'=>$row['customer'],
                'supplier'=>$row['supplier'],
                'product'=>$row['product'],
                'driver_name'=>$row['driver_name'],
                'lorry_no'=>$row['lorry_no'],
                'farm_id'=>$farmId,
                'farm_name'=>$farmName,
                'average_cage'=>$row['average_cage'],
                'average_bird'=>$row['average_bird'],
                'minimum_weight'=>$row['minimum_weight'],
                'maximum_weight'=>$row['maximum_weight'],
                'min_crate'=>$row['min_crate'],
                'max_crate'=>$row['max_crate'],
                'weight_data'=>$row['weight_data'],
                'created_datetime'=>$row['created_datetime'],
                'start_time'=>$row['start_time'],
                'end_time'=>$row['end_time'],
                'grade'=>$row['grade'],
                'gender'=>$row['gender'],
                'house_no'=>$row['house_no'],
                'remark'=>$row['remark']
            );
            
            array_push($checking, $row['id']);
        }
    }
}

$stmt->close();
$db->close();

echo json_encode(
    array(
        "status"=> "success", 
        "message"=> $message
    )
);
?>