<?php
require_once 'db_connect.php';

session_start();

$post = json_decode(file_get_contents('php://input'), true);

$services = 'Login';
$requests = json_encode($post);

$stmtL = $db->prepare("INSERT INTO api_requests (services, request) VALUES (?, ?)");
$stmtL->bind_param('ss', $services, $requests);
$stmtL->execute();
$invid = $stmtL->insert_id;

$username=$post['userEmail'];
$password=$post['userPassword'];
$now = date("Y-m-d H:i:s");

$stmt = $db->prepare("SELECT users.*, companies.reg_no, companies.name AS comp_name, companies.address, companies.address2, companies.address3, companies.address4, companies.phone, companies.fax, companies.email, companies.products as products, companies.sst as sst from users, companies where users.customer = companies.id AND users.username= ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if(($row = $result->fetch_assoc()) !== null){
	$password = hash('sha512', $password . $row['salt']);
	$modules = json_decode($row['products'], true);
	
	if($password == $row['password']){
	    $message = array();
	    $message['id'] = $row['id'];
        $message['username'] = $row['username'];
        $message['name'] = $row['name'];
        $message['role_code'] = $row['role_code'];
        $message['customer'] = $row['customer'];
        $message['weighing'] = in_array('weighing', $modules) ? 'Y' : 'N';
        $message['pricings'] = in_array('pricing', $modules) ? 'Y' : 'N';
        $message['wastings'] = in_array('waste', $modules) ? 'Y' : 'N';
        $message['packings'] = in_array('packing', $modules) ? 'Y' : 'N';
        $message['weighbridges'] = in_array('weighbridge', $modules) ? 'Y' : 'N';
        $message['wholesales'] = in_array('wholesale', $modules) ? 'Y' : 'N';
        $message['validations'] = in_array('validation', $modules) ? 'Y' : 'N';
        $message['fruits'] = in_array('fruits', $modules) ? 'Y' : 'N';
        $message['second_remarks'] = in_array('second_remarks', $modules) ? 'Y' : 'N';
        $message['customer_det'] = array(
            "id" => $row['customer'],
            "reg_no" => $row['reg_no'],
            "comp_name" => $row['comp_name'],
            "address" => $row['address'],
            "address2" => $row['address2'],
            "address3" => $row['address3'],
            "address4" => $row['address4'],
            "phone" => $row['phone'],
            "fax" => $row['fax'],
            "email" => $row['email'],
            "sst" => $row['sst']
        );

        $response = json_encode(
            array(
                "status"=> "success", 
                "message"=> $message
            )
        );

        $stmtU = $db->prepare("UPDATE api_requests SET response = ? WHERE id = ?");
        $stmtU->bind_param('ss', $response, $invid);
        $stmtU->execute();

        $stmt->close();
        $stmtU->close();
        $db->close();
        echo $response;
	} 
	else{
        $response = json_encode(
            array(
                "status"=> "failed", 
                "message"=> "Invalid username or password"
            )
        );

        $stmtU = $db->prepare("UPDATE api_requests SET response = ? WHERE id = ?");
        $stmtU->bind_param('ss', $response, $invid);
        $stmtU->execute();

        $stmt->close();
        $stmtU->close();
        $db->close();
        echo $response;
	}
	
} 
else{
    $response = json_encode(
        array(
            "status"=> "failed", 
            "message"=> "User not found"
        )
    );

    $stmtU = $db->prepare("UPDATE api_requests SET response = ? WHERE id = ?");
    $stmtU->bind_param('ss', $response, $invid);
    $stmtU->execute();

    $stmt->close();
    $stmtU->close();
    $db->close();
    echo $response;
}
?>
