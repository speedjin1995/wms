<?php
require_once 'php/db_connect.php';

// Validate token exists in either GET or POST
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

if (empty($token)) {
    header('Location: forgot-password.php?error=invalid');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword     = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword !== $confirmPassword) {
        $formError = 'Passwords do not match.';
    } elseif (strlen($newPassword) < 6) {
        $formError = 'Password must be at least 6 characters.';
    } else {
        // Verify token is still valid
        $stmt = $db->prepare("SELECT id FROM users WHERE reset_token = ? LIMIT 1");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            header('Location: forgot-password.php?error=invalid');
            exit;
        }

        $stmt = $db->prepare("SELECT id, salt FROM users WHERE reset_token = ? AND reset_expires > NOW() AND deleted = 0 LIMIT 1");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            header('Location: forgot-password.php?error=expired');
            exit;
        }

        $row            = $result->fetch_assoc();
        $hashedPassword = hash('sha512', $newPassword . $row['salt']);

        $update = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $update->bind_param('si', $hashedPassword, $row['id']);

        if ($update->execute()) {
            header('Location: login.html?reset=1');
        } else {
            $formError = 'Database error. Please try again.';
        }

        $stmt->close();
        $db->close();
        exit;
    }
}

// Validate token for GET (before showing the form)
$stmt = $db->prepare("SELECT id FROM users WHERE reset_token = ? LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$tokenResult = $stmt->get_result();

if ($tokenResult->num_rows === 0) {
    header('Location: forgot-password.php?error=invalid');
    exit;
}

// Check expiry separately
$stmt2 = $db->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1");
$stmt2->bind_param('s', $token);
$stmt2->execute();
if ($stmt2->get_result()->num_rows === 0) {
    header('Location: forgot-password.php?error=expired');
    exit;
}
$stmt->close();
$stmt2->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Synctronix | Reset Password</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">

  <style>
    .brand-panel {
      background: url(assets/module-bg.jpeg) center / cover no-repeat;
      position: relative;
    }
    .brand-panel-overlay {
      position: absolute;
      inset: 0;
      background: rgba(0,0,0,0.55);
    }
    .pw-wrap { position: relative; }
    .pw-wrap .form-control { padding-right: 44px; }
    .pw-toggle {
      position: absolute; right: 12px; top: 50%;
      transform: translateY(-50%);
      background: none; border: none;
      cursor: pointer; color: #adb5bd;
      font-size: 1rem; padding: 0;
    }
    .pw-toggle:focus { outline: 2px solid #007bff; border-radius: 4px; }
  </style>
</head>

<body class="hold-transition">

  <div class="d-flex" style="min-height:100vh;">

    <!-- Left: Brand Panel (desktop only) -->
    <div class="brand-panel d-none d-lg-flex col-lg-6 p-0 flex-column align-items-center justify-content-center"
         style="background-image:url('assets/module-bg.jpeg');background-size:cover;background-position:center;">
      <div class="brand-panel-overlay"></div>
      <div class="position-relative text-center text-white px-5">
        <img src="assets/wms-logo-2.png" alt="Synctronix WMS Logo" class="d-block mx-auto mb-4" style="max-height:30vh;width:auto;">
        <p class="mb-0 lead" style="opacity:.8;">Enterprise-grade weighing operations,<br>built for speed, accuracy, and compliance.</p>
      </div>
    </div>

    <!-- Right: Form Panel -->
    <div class="col-lg-6 col-12 bg-white d-flex align-items-center justify-content-center px-4 px-md-5" style="min-height:100vh;">
      <div class="w-100" style="max-width:440px;">

        <!-- Heading -->
        <p class="text-primary text-uppercase font-weight-bold mb-1" style="font-size:.75rem;letter-spacing:.1em;">Reset Password</p>
        <h2 class="font-weight-bold text-dark mb-1">Create New Password</h2>
        <p class="text-muted mb-4">Enter your new password below.</p>

        <?php if (!empty($formError)): ?>
          <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($formError); ?>
          </div>
        <?php endif; ?>

        <form action="reset-password.php" method="post">
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

          <!-- New Password -->
          <div class="form-group">
            <label for="password" class="font-weight-semibold text-dark small">New Password</label>
            <div class="pw-wrap">
              <input
                type="password"
                class="form-control form-control-lg"
                id="password"
                name="password"
                placeholder="Enter new password"
                autocomplete="new-password"
                autofocus
                required
              >
              <button type="button" class="pw-toggle" id="togglePw1" aria-label="Toggle password visibility">
                <i class="fas fa-eye" id="pwIcon1"></i>
              </button>
            </div>
          </div>

          <!-- Confirm Password -->
          <div class="form-group">
            <label for="confirm_password" class="font-weight-semibold text-dark small">Confirm Password</label>
            <div class="pw-wrap">
              <input
                type="password"
                class="form-control form-control-lg"
                id="confirm_password"
                name="confirm_password"
                placeholder="Confirm new password"
                autocomplete="new-password"
                required
              >
              <button type="button" class="pw-toggle" id="togglePw2" aria-label="Toggle confirm password visibility">
                <i class="fas fa-eye" id="pwIcon2"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-block btn-lg mt-2">
            Reset Password
          </button>

        </form>

        <div class="mt-3 text-center">
          <a href="login.html" class="small">
            <i class="fas fa-arrow-left mr-1"></i>Back to Login
          </a>
        </div>

        <p class="text-muted text-center mt-5 mb-0 small">
          &copy; Synctronix. All rights reserved.
          <span class="d-block" style="font-size:.75rem;">v1.0 &middot; Production</span>
        </p>

      </div>
    </div>

  </div>

  <!-- jQuery -->
  <script src="plugins/jquery/jquery.min.js"></script>
  <!-- Bootstrap 4 -->
  <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <!-- AdminLTE App -->
  <script src="dist/js/adminlte.min.js"></script>

  <script>
    function togglePassword(btnId, iconId, inputId) {
      document.getElementById(btnId).addEventListener('click', function () {
        var input = document.getElementById(inputId);
        var icon  = document.getElementById(iconId);
        if (input.type === 'password') {
          input.type = 'text';
          icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
          input.type = 'password';
          icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
      });
    }
    togglePassword('togglePw1', 'pwIcon1', 'password');
    togglePassword('togglePw2', 'pwIcon2', 'confirm_password');
  </script>

</body>
</html>
