<?php
require_once 'db_connect.php';

session_start();

$post = json_decode(file_get_contents('php://input'), true);

$username=$post['userEmail'];
$password=$post['userPassword'];
$now = date("Y-m-d H:i:s");

$stmt = $db->prepare("SELECT * from users where username= ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if(($row = $result->fetch_assoc()) !== null){
	$password = hash('sha512', $password . $row['salt']);
	
	if($password == $row['password']){
	    $message = array();
	    $message['id'] = $row['id'];
        $message['username'] = $row['username'];
        $message['name'] = $row['name'];
        $message['role_code'] = $row['role_code'];
        $message['customer'] = $row['customer'];
        
		$stmt->close();
		$db->close();
		
		echo json_encode(
            array(
                "status"=> "success", 
                "message"=> $message
            )
        );
	} 
	else{
		echo json_encode(
            array(
                "status"=> "failed", 
                "message"=> $update_stmt->error
            )
        );
	}
	
} 
else{
	 echo json_encode(
        array(
            "status"=> "failed", 
            "message"=> $update_stmt->error
        )
    );
}
?>
