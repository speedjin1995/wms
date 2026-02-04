<?php
$licenseCompanyId = null;
$licenseIsProfessional = false;

$licensePath = __DIR__ . '/../../license.php';

if (file_exists($licensePath)) {
    $licenseContent = file_get_contents($licensePath);
    $licenseData = json_decode($licenseContent, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($licenseData['company'])) {
        $licenseCompanyId = $licenseData['company'];
        $licenseIsProfessional = true; // license file = PRO
    }
}

require_once 'db_connect.php';

session_start();

$username=$_POST['userEmail'];
$password=$_POST['userPassword'];
$now = date("Y-m-d H:i:s");

$stmt = $db->prepare("SELECT u.*, c.packages AS packages, c.products AS products FROM users u LEFT JOIN companies c ON u.customer = c.id WHERE username=? AND u.deleted=0");
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if(($row = $result->fetch_assoc()) !== null){
	// Checking to see if user company has medium or professional package
	$packages = json_decode($row['packages'], true);
	$products = json_decode($row['products'], true);

	if ($row['id'] == 2 || in_array('M', $packages, true) || in_array('P', $packages, true)) {
		$password = hash('sha512', $password . $row['salt']);
		if($password == $row['password']){
			$_SESSION['userID']=$row['id'];
			$_SESSION['customer']= ($licenseIsProfessional ? $licenseCompanyId : $row['customer']);
			$_SESSION['products']=$products;
			$stmt->close();
			$db->close();
			
			echo '<script type="text/javascript">';
			echo 'window.location.href = "../home.php";</script>';
		} 
		else{
			echo '<script type="text/javascript">alert("Login unsuccessful, password or username is not matched");';
			echo 'window.location.href = "../login.html";</script>';
		}
	}else{
		echo '<script type="text/javascript">alert("Login unsuccessful, user not authorized to access web");';
		echo 'window.location.href = "../login.html";</script>';
	}
} 
else{
	 echo '<script type="text/javascript">alert("Login unsuccessful, password or username is not matched");';
	 echo 'window.location.href = "../login.html";</script>';
}
?>
