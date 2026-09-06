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
    $form_type = $_POST['form_type'] ?? 'login';

    if ($form_type === 'register') {
        $name     = trim((string)($_POST['name'] ?? ''));
        $email    = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $password = trim(filter_input(INPUT_POST, 'password', FILTER_DEFAULT));

        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Please enter your name, email, and password.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            $result = api_request('/register', 'POST', [
                'name'     => $name,
                'email'    => $email,
                'password' => $password,
            ]);

            if ($result['status'] === 201 && !empty($result['body']['success'])) {
                $_SESSION['user'] = $result['body']['user'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = $result['body']['message'] ?? 'Account creation failed. Please try again.';
            }
        }
    } else {
        $email    = trim(filter_input(INPUT_POST, 'email',    FILTER_SANITIZE_EMAIL));
        $password = trim(filter_input(INPUT_POST, 'password', FILTER_DEFAULT));
        $role     = strtolower(trim((string)($_POST['role'] ?? 'user')));
        if (!in_array($role, ['user', 'admin'], true)) {
            $role = 'user';
        }

        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $result = api_request('/login', 'POST', [
                'email'    => $email,
                'password' => $password,
                'role'     => $role,
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
  <div class="login-particles" aria-hidden="true">
    <?php for ($i = 0; $i < 26; $i++):
      $x = rand(0,100); $y = rand(0,100); $s = rand(2,7);
      $o = round(rand(5,28) / 100, 2);
    ?>
    <span class="floating-dot"
      style="left:<?= $x ?>%; top:<?= $y ?>%; width:<?= $s ?>px; height:<?= $s ?>px; opacity:<?= $o ?>; animation-delay: <?= $i * 0.55 ?>s;"></span>
    <?php endfor; ?>
  </div>

  <div class="login-scene">
    <div class="agent-panel" aria-hidden="true">
      <div class="agent-badge">
        <i class="bi bi-search-heart-fill"></i>
        Smart Campus Finder
      </div>

      <div class="agent-visual">
        <div class="pulse-ring ring-one"></div>
        <div class="pulse-ring ring-two"></div>
        <div class="agent-orb">
          <i class="bi bi-search"></i>
        </div>
      </div>

      <div class="agent-lines">
        <span></span>
        <span></span>
        <span></span>
      </div>

      <div class="agent-status">
        <span class="status-dot"></span>
        Searching for lost items...
      </div>

      <div class="agent-tags">
        <span>Keys</span>
        <span>Wallet</span>
        <span>ID Card</span>
      </div>

      <div class="security-overview" aria-label="Portal security features">
        <div class="security-overview-heading">
          <i class="bi bi-shield-check"></i>
          <span>Security essentials</span>
        </div>
        <div class="security-types">
          <div class="security-type">
            <i class="bi bi-person-lock"></i>
            <div><strong>Access control</strong><small>Role-based consoles</small></div>
          </div>
          <div class="security-type">
            <i class="bi bi-database-lock"></i>
            <div><strong>Data protection</strong><small>Private user details</small></div>
          </div>
          <div class="security-type">
            <i class="bi bi-key"></i>
            <div><strong>Account security</strong><small>Protected sign-in</small></div>
          </div>
          <div class="security-type">
            <i class="bi bi-flag"></i>
            <div><strong>Safe reporting</strong><small>Responsible sharing</small></div>
          </div>
        </div>
      </div>
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
      <input type="hidden" name="form_type" value="login" />

      <div class="mb-3">
        <label class="form-label" style="font-size:.85rem;color:#94a3b8;font-weight:500;">
          Console Access
        </label>
        <div class="input-icon-wrap">
          <i class="bi bi-shield-lock"></i>
          <select name="role" class="form-select glass-input" aria-label="Console access">
            <option value="user" selected>User Console</option>
            <option value="admin">Admin Console</option>
          </select>
        </div>
      </div>

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

      <div class="d-flex justify-content-between align-items-center mb-3">
        <div></div>
        <a href="reset_password.php" class="text-decoration-none small" style="color:#7dd3fc;">Forgot password?</a>
      </div>

      <button type="submit" class="btn-gradient">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
      </button>

    </form>

    <div class="mt-4 pt-3 border-top border-secondary-subtle">
      <h6 class="text-white mb-3" style="font-size:.85rem;letter-spacing:.06em;text-transform:uppercase;color:#cbd5e1;">Create a user account</h6>
      <form method="POST" action="login.php" novalidate>
        <input type="hidden" name="form_type" value="register" />

        <div class="mb-3">
          <input type="text" name="name" class="form-control glass-input"
                 placeholder="Full name" autocomplete="name" required />
        </div>

        <div class="mb-3">
          <input type="email" name="email" class="form-control glass-input"
                 placeholder="Email address" autocomplete="email" required />
        </div>

        <div class="mb-3" style="position:relative;">
          <input type="password" name="password" id="registerPasswordInput" class="form-control glass-input"
                 placeholder="Create password (min 6 chars)" autocomplete="new-password" required
                 style="padding-right:3rem!important;" />
          <button type="button" onclick="togglePasswordVisibility('registerPasswordInput', 'registerEyeIcon')"
                  style="position:absolute;right:.85rem;top:50%;transform:translateY(-50%);background:none;border:none;color:#64748b;cursor:pointer;z-index:3;"
                  tabindex="-1">
            <i class="bi bi-eye" id="registerEyeIcon"></i>
          </button>
        </div>

        <button type="submit" class="btn btn-sm w-100" style="background:rgba(16,185,129,0.18);color:#bbf7d0;border:1px solid rgba(52,211,153,0.35);border-radius:10px;">
          <i class="bi bi-person-plus-fill me-2"></i>Create Account
        </button>
      </form>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<script>
// Invisible voice welcome feature using Web Speech Synthesis API
function playWelcomeVoice() {
  // Prevent multiple invocations using sessionStorage (plays only once per session)
  if (sessionStorage.getItem('welcomeVoicePlayed')) {
    return;
  }
  
  // Check if browser supports Speech Synthesis API
  const synth = window.speechSynthesis;
  if (!synth) {
    return;
  }
  
  try {
    // Mark as played in sessionStorage to prevent duplicate messages
    sessionStorage.setItem('welcomeVoicePlayed', 'true');
    
    // Create the welcome message
    const utterance = new SpeechSynthesisUtterance('Welcome to Campus Lost and Found Portal. Please login to continue.');
    
    // Set voice properties for natural and professional sound
    utterance.rate = 0.95;      // Slightly slower than normal for clarity
    utterance.pitch = 1.0;      // Normal pitch
    utterance.volume = 0.8;     // Slightly lower volume to be less intrusive
    
    // Attempt to use a female voice if available (typically sounds more professional)
    const voices = synth.getVoices();
    if (voices.length > 0) {
      // Try to find a female voice, fallback to first voice if not available
      const femaleVoice = voices.find(voice => voice.name.toLowerCase().includes('female')) || 
                         voices.find(voice => voice.name.toLowerCase().includes('woman')) ||
                         voices[0];
      utterance.voice = femaleVoice;
    }
    
    // Speak the welcome message
    synth.speak(utterance);
  } catch (error) {
    // Silently fail if there's any error - don't break the login page
    console.debug('Welcome voice feature unavailable');
  }
}

// Trigger welcome voice when page loads (only once per browser session)
document.addEventListener('DOMContentLoaded', playWelcomeVoice);

function togglePasswordVisibility(inputId, iconId) {
  const inp = document.getElementById(inputId);
  const ico = document.getElementById(iconId);
  if (!inp || !ico) return;
  if (inp.type === 'password') {
    inp.type = 'text';
    ico.classList.replace('bi-eye', 'bi-eye-slash');
  } else {
    inp.type = 'password';
    ico.classList.replace('bi-eye-slash', 'bi-eye');
  }
}

function togglePwd() {
  togglePasswordVisibility('passwordInput', 'pwdEyeIcon');
}
</script>
</body>
</html>
