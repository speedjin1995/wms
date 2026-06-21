<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Synctronix | Forgot Password</title>
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
        <p class="text-primary text-uppercase font-weight-bold mb-1" style="font-size:.75rem;letter-spacing:.1em;">Account Recovery</p>
        <h2 class="font-weight-bold text-dark mb-1">Forgot Password</h2>
        <p class="text-muted mb-4">Enter your registered email address and we'll send you a reset link.</p>

        <!-- PHP alerts -->
        <?php if (isset($_GET['error'])): ?>
          <div class="alert alert-danger" role="alert" aria-live="polite">
            <?php
              $errors = [
                'not_found' => 'No account found with that email address.',
                'mail_fail' => 'Failed to send reset email. Please try again.' . (!empty($_GET['detail']) ? '<br><small class="text-muted">' . htmlspecialchars(urldecode($_GET['detail'])) . '</small>' : ''),
                'invalid'   => 'Invalid reset link. Please request a new one.',
                'expired'   => 'Your reset link has expired. Please request a new one.',
              ];
              echo $errors[$_GET['error']] ?? 'An error occurred.';
            ?>
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['sent'])): ?>
          <div class="alert alert-success" role="alert" aria-live="polite">
            Reset link sent! Please check your email.
          </div>
        <?php endif; ?>

        <form action="php/resetPassword.php" method="post">

          <div class="form-group">
            <label for="userEmail" class="font-weight-semibold text-dark small">Email Address</label>
            <input
              type="email"
              class="form-control form-control-lg"
              id="userEmail"
              name="userEmail"
              placeholder="Enter your email address"
              autocomplete="email"
              autofocus
              required
            >
          </div>

          <button type="submit" class="btn btn-primary btn-block btn-lg mt-2">
            Send Reset Link
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

</body>
</html>
