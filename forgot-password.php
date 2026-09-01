<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Synctronix | Forgot Password</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <!-- Login Page Styles -->
  <link rel="stylesheet" href="assets/css/login.css">
</head>

<body class="hold-transition">

  <div class="login-wrapper">

    <!-- Left: Brand Panel (desktop only) -->
    <div class="login-brand-panel">
      <div class="login-brand-overlay"></div>
      <div class="login-brand-content">
        <img src="assets/wms-logo-2.png" alt="Synctronix WMS" class="login-brand-logo">
        <p class="login-brand-tagline">
          <strong>Enterprise-grade weighing operations</strong><br>
          built for speed, accuracy, and compliance.
        </p>
      </div>
    </div>

    <!-- Right: Form Panel -->
    <div class="login-form-panel">
      <div class="login-form-container">

        <!-- Mobile Logo -->
        <img src="assets/wms-logo-2.png" alt="Synctronix WMS" class="login-mobile-logo">

        <!-- Header -->
        <div class="login-header">
          <div class="login-brand-badge">
            <i class="fas fa-key"></i>
            Account Recovery
          </div>
          <h1 class="login-title">Forgot Password</h1>
          <p class="login-subtitle">Enter your registered email address and we'll send you a reset link.</p>
        </div>

        <!-- PHP Error Alert -->
        <?php if (isset($_GET['error'])): ?>
          <div class="login-alert login-alert-error" role="alert" aria-live="polite">
            <i class="fas fa-exclamation-circle"></i>
            <span>
              <?php
                $errors = [
                  'not_found' => 'No account found with that email address.',
                  'mail_fail' => 'Failed to send reset email. Please try again.' . (!empty($_GET['detail']) ? '<br><small>' . htmlspecialchars(urldecode($_GET['detail'])) . '</small>' : ''),
                  'invalid'   => 'Invalid reset link. Please request a new one.',
                  'expired'   => 'Your reset link has expired. Please request a new one.',
                ];
                echo $errors[$_GET['error']] ?? 'An error occurred.';
              ?>
            </span>
          </div>
        <?php endif; ?>

        <!-- PHP Success Alert -->
        <?php if (isset($_GET['sent'])): ?>
          <div class="login-alert login-alert-success" role="alert" aria-live="polite">
            <i class="fas fa-check-circle"></i>
            <span>Reset link sent! Please check your email.</span>
          </div>
        <?php endif; ?>

        <!-- Forgot Password Form -->
        <form action="php/resetPassword.php" method="post" id="forgotForm">

          <!-- Email -->
          <div class="login-form-group">
            <label for="userEmail" class="login-label">Email Address</label>
            <input
              type="email"
              class="login-input"
              id="userEmail"
              name="userEmail"
              placeholder="Enter your email address"
              autocomplete="email"
              autofocus
              required
            >
          </div>

          <!-- Submit Button -->
          <button type="submit" class="login-submit" id="submitBtn">
            <span class="spinner"></span>
            <span class="btn-text">Send Reset Link</span>
          </button>

        </form>

        <!-- Back to Login -->
        <div class="text-center">
          <a href="login.html" class="login-back">
            <i class="fas fa-arrow-left"></i>
            Back to Login
          </a>
        </div>

        <!-- Footer -->
        <div class="login-footer">
          <p class="login-footer-text">&copy; Synctronix. All rights reserved.</p>
          <p class="login-footer-version">v1.0 · Production</p>
        </div>

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
    // Form submit loading state
    document.getElementById('forgotForm').addEventListener('submit', function () {
      document.getElementById('submitBtn').classList.add('is-loading');
      document.getElementById('submitBtn').disabled = true;
    });
  </script>

</body>
</html>
