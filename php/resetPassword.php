<?php
require_once 'db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['userEmail'])) {
    header('Location: ../forgot-password.php?error=invalid');
    exit;
}

$email = trim($_POST['userEmail']);

// Look up user by email
$stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND deleted = 0 LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../forgot-password.php?error=not_found');
    exit;
}

$row   = $result->fetch_assoc();
$token   = bin2hex(random_bytes(50));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Save token
$update = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
$update->bind_param('ssi', $token, $expires, $row['id']);
$update->execute();

// Build dynamic reset URL
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'];
// Strip /php/resetPassword.php to get base path e.g. /wms
$basePath  = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$resetLink = $protocol . '://' . $host . $basePath . '/reset-password.php?token=' . $token;

// Send email
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'help@syncweb.com.my';
    $mail->Password   = '@Sync5500';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('help@syncweb.com.my', 'Synctronix WMS');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Request - Synctronix WMS';
    $mail->Body    = "
        <div style='font-family:Arial,sans-serif;max-width:480px;margin:auto;'>
            <h2 style='color:#2563eb;'>Synctronix WMS</h2>
            <p>Hello,</p>
            <p>We received a request to reset your password. Click the button below to proceed:</p>
            <p style='text-align:center;margin:32px 0;'>
                <a href='{$resetLink}'
                   style='background:#2563eb;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:bold;'>
                   Reset Password
                </a>
            </p>
            <p style='color:#666;font-size:13px;'>This link will expire in <strong>1 hour</strong>.<br>
            If you did not request a password reset, please ignore this email.</p>
            <hr style='border:none;border-top:1px solid #eee;'>
            <p style='color:#aaa;font-size:12px;'>&copy; Synctronix. All rights reserved.</p>
        </div>
    ";
    $mail->AltBody = "Reset your password: {$resetLink}\n\nThis link expires in 1 hour.";

    $mail->send();
    header('Location: ../forgot-password.php?sent=1');
} catch (Exception $e) {
    header('Location: ../forgot-password.php?error=mail_fail');
}

$stmt->close();
$db->close();
exit;
?>
