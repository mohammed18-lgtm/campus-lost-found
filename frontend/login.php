<?php
require_once 'config.php';

// Already logged in → go to dashboard
if (!empty($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error   = '';
$success = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'logged_out') {
    $success = 'You have been logged out successfully.';
}

// Handle form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim(filter_input(INPUT_POST, 'email',    FILTER_SANITIZE_EMAIL));
    $password = trim(filter_input(INPUT_POST, 'password', FILTER_DEFAULT));

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $result = api_request('/login', 'POST', [
            'email'    => $email,
            'password' => $password,
        ]);

        if ($result['status'] === 200 && !empty($result['body']['success'])) {
            $_SESSION['user'] = $result['body']['user'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = $result['body']['message'] ?? 'Login failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login — Campus Lost &amp; Found</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <link href="css/style.css" rel="stylesheet" />
</head>
<body>
<div class="login-bg">

  <!-- Floating decorative dots -->
  <div style="position:absolute;inset:0;overflow:hidden;pointer-events:none;">
    <?php for ($i = 0; $i < 18; $i++):
      $x = rand(0,100); $y = rand(0,100); $s = rand(2,5);
      $o = round(rand(5,20) / 100, 2);
    ?>
    <div style="position:absolute;left:<?= $x ?>%;top:<?= $y ?>%;
      width:<?= $s ?>px;height:<?= $s ?>px;border-radius:50%;
      background:#fff;opacity:<?= $o ?>;"></div>
    <?php endfor; ?>
  </div>

  <div class="glass-card">

    <!-- Logo + heading -->
    <div class="text-center mb-4">
      <div class="logo-wrap">
        <i class="bi bi-search-heart-fill text-white fs-3"></i>
      </div>
      <h2 class="mb-1">Lost &amp; Found</h2>
      <p class="text-muted" style="font-size:.9rem">Campus Portal — sign in to continue</p>
    </div>

    <!-- Alerts -->
    <?php if ($error): ?>
    <div class="alert d-flex align-items-center gap-2 mb-3"
         style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);
                color:#fca5a5;border-radius:10px;font-size:.87rem;padding:.65rem 1rem;">
      <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert d-flex align-items-center gap-2 mb-3"
         style="background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.3);
                color:#6ee7b7;border-radius:10px;font-size:.87rem;padding:.65rem 1rem;">
      <i class="bi bi-check-circle-fill flex-shrink-0"></i>
      <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <!-- Login form -->
    <form method="POST" action="login.php" novalidate>

      <div class="mb-3">
        <label class="form-label" style="font-size:.85rem;color:#94a3b8;font-weight:500;">
          Email Address
        </label>
        <div class="input-icon-wrap">
          <i class="bi bi-envelope"></i>
          <input type="email" name="email" class="form-control glass-input"
                 placeholder="your@campus.edu"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 autocomplete="email" required />
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label" style="font-size:.85rem;color:#94a3b8;font-weight:500;">
          Password
        </label>
        <div class="input-icon-wrap" style="position:relative;">
          <i class="bi bi-lock"></i>
          <input type="password" name="password" id="passwordInput"
                 class="form-control glass-input"
                 placeholder="••••••••"
                 autocomplete="current-password" required
                 style="padding-right:3rem!important;" />
          <button type="button" onclick="togglePwd()"
                  style="position:absolute;right:.85rem;top:50%;transform:translateY(-50%);
                         background:none;border:none;color:#64748b;cursor:pointer;z-index:3;"
                  tabindex="-1">
            <i class="bi bi-eye" id="pwdEyeIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-gradient">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
      </button>

    </form>

    <!-- Demo credentials hint -->
    <div class="mt-4 p-3" style="background:rgba(255,255,255,0.04);border-radius:10px;
         border:1px solid rgba(255,255,255,0.08);">
      <p class="mb-1" style="font-size:.78rem;color:#64748b;text-transform:uppercase;
         letter-spacing:.05em;font-weight:600;">Demo Credentials</p>
      <div style="font-size:.82rem;color:#94a3b8;">
        <div><i class="bi bi-person-fill me-1"></i> admin@campus.edu</div>
        <div><i class="bi bi-key-fill me-1"></i> admin123</div>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<script>
function togglePwd() {
  const inp = document.getElementById('passwordInput');
  const ico = document.getElementById('pwdEyeIcon');
  if (inp.type === 'password') {
    inp.type = 'text';
    ico.classList.replace('bi-eye', 'bi-eye-slash');
  } else {
    inp.type = 'password';
    ico.classList.replace('bi-eye-slash', 'bi-eye');
  }
}
</script>
</body>
</html>
