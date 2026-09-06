<?php
require_once 'config.php';
require_login();

$user = $_SESSION['user'];
$role = strtolower($user['role'] ?? 'user');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $role === 'admin' ? 'Admin Console' : 'Dashboard' ?> — Campus Lost &amp; Found</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <link href="css/style.css" rel="stylesheet" />
</head>
<body>

<nav class="navbar navbar-expand-lg main-navbar sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
      <i class="bi bi-search-heart-fill" style="font-size:1.3rem;color:#4f46e5;"></i>
      Lost &amp; Found
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
            data-bs-target="#mainNav" aria-label="Toggle navigation"
            style="color:#94a3b8;">
      <i class="bi bi-list fs-4"></i>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav mx-auto gap-1">
        <?php if ($role === 'admin'): ?>
          <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Admin Console</a></li>
          <li class="nav-item"><a class="nav-link" href="browse_items.php"><i class="bi bi-grid me-1"></i>Browse Reports</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="report_item.php"><i class="bi bi-plus-circle me-1"></i>Report Item</a></li>
          <li class="nav-item"><a class="nav-link" href="browse_items.php"><i class="bi bi-grid me-1"></i>Browse Items</a></li>
        <?php endif; ?>
      </ul>
      <div class="d-flex align-items-center gap-3">
        <span class="user-badge">
          <i class="bi bi-person-fill me-1"></i>
          <?= htmlspecialchars($user['name']) ?>
          <?php if ($role === 'admin'): ?><span class="ms-1 text-warning">(Admin)</span><?php endif; ?>
        </span>
        <a href="logout.php" class="btn btn-sm btn-outline-danger border-0" style="background:rgba(239,68,68,.1);color:#f87171;border-radius:8px;">
          <i class="bi bi-box-arrow-right me-1"></i>Logout
        </a>
      </div>
    </div>
  </div>
</nav>

<?php if ($role === 'admin'): ?>
<div class="page-wrapper">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="fw-bold mb-0" style="font-size:1.35rem;">🛡️ Admin Console</h4>
      <p class="text-muted mb-0" style="font-size:.87rem;">Monitor reports, review found-item messages, and notify the reported owner.</p>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6"><div class="stat-card"><div class="stat-icon icon-lost"><i class="bi bi-file-earmark-text"></i></div><div><div class="stat-label">Total Reports</div><div class="stat-value" id="admin-total-reports">—</div></div></div></div>
    <div class="col-xl-3 col-sm-6"><div class="stat-card"><div class="stat-icon icon-found"><i class="bi bi-check2-circle"></i></div><div><div class="stat-label">Found Items</div><div class="stat-value" id="admin-found-items">—</div></div></div></div>
    <div class="col-xl-3 col-sm-6"><div class="stat-card"><div class="stat-icon icon-pending"><i class="bi bi-envelope-paper"></i></div><div><div class="stat-label">User Messages</div><div class="stat-value" id="admin-message-count">—</div></div></div></div>
    <div class="col-xl-3 col-sm-6"><div class="stat-card"><div class="stat-icon icon-claimed"><i class="bi bi-bell-fill"></i></div><div><div class="stat-label">Notifications</div><div class="stat-value" id="admin-notification-count">—</div></div></div></div>
  </div>

  <div class="panel mb-4">
    <div class="section-title"><i class="bi bi-table text-info"></i> Recent Reports</div>
    <div class="table-responsive">
      <table class="lf-table">
        <thead>
          <tr>
            <th>Item</th>
            <th>Category</th>
            <th>Status</th>
            <th>Location</th>
            <th>Contact</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="adminReportsTable">
          <tr><td colspan="6" class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-secondary me-2"></div>Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel">
    <div class="section-title"><i class="bi bi-chat-left-text text-info"></i> User Messages</div>
    <div id="adminMessagesList" class="d-grid gap-3"></div>
  </div>
</div>
<?php else: ?>
<div class="page-wrapper">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="fw-bold mb-0" style="font-size:1.35rem;">
        👋 Welcome back, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>
      </h4>
      <p class="text-muted mb-0" style="font-size:.87rem;">Here's what's happening on campus today.</p>
    </div>
    <a href="report_item.php" class="btn btn-sm" style="background:linear-gradient(135deg,#4f46e5,#06b6d4);color:#fff;border:none;border-radius:10px;padding:.5rem 1.1rem;font-weight:600;">
      <i class="bi bi-plus-lg me-1"></i>Report Item
    </a>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6"><div class="stat-card"><div class="stat-icon icon-lost"><i class="bi bi-search"></i></div><div><div class="stat-label">Total Lost</div><div class="stat-value" id="stat-lost">—</div></div></div></div>
    <div class="col-xl-3 col-sm-6"><div class="stat-card"><div class="stat-icon icon-found"><i class="bi bi-check2-circle"></i></div><div><div class="stat-label">Total Found</div><div class="stat-value" id="stat-found">—</div></div></div></div>
    <div class="col-xl-3 col-sm-6"><div class="stat-card"><div class="stat-icon icon-pending"><i class="bi bi-clock-history"></i></div><div><div class="stat-label">Active Reports</div><div class="stat-value" id="stat-pending">—</div></div></div></div>
    <div class="col-xl-3 col-sm-6"><div class="stat-card"><div class="stat-icon icon-claimed"><i class="bi bi-bag-check-fill"></i></div><div><div class="stat-label">Claimed</div><div class="stat-value" id="stat-claimed">—</div></div></div></div>
  </div>

  <div class="panel mb-4">
    <div class="section-title"><i class="bi bi-activity text-info"></i> Recent Activity</div>
    <div class="table-responsive">
      <table class="lf-table">
        <thead><tr><th>Item</th><th>Category</th><th>Status</th><th>Location</th><th>Date</th><th>Reported</th></tr></thead>
        <tbody id="recentBody"><tr><td colspan="6" class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-secondary me-2"></div>Loading…</td></tr></tbody>
      </table>
    </div>
    <div class="text-end mt-3"><a href="browse_items.php" style="font-size:.85rem;color:#94a3b8;" class="text-decoration-none">View all items <i class="bi bi-arrow-right ms-1"></i></a></div>
  </div>

  <div class="panel">
    <div class="section-title"><i class="bi bi-search-heart text-info"></i> Found Something? Tell the Admin</div>
    <form id="foundItemMessageForm">
      <div class="row g-3">
        <div class="col-md-5">
          <label class="form-label">Item Name</label>
          <input type="text" class="form-control" name="item_name" placeholder="Example: Black wallet" required>
        </div>
        <div class="col-md-7">
          <label class="form-label">Subject</label>
          <input type="text" class="form-control" name="subject" value="Found item report" required>
        </div>
        <div class="col-12">
          <label class="form-label">Message</label>
          <textarea class="form-control" name="message" rows="4" placeholder="I found this item near the library. Please let me know if it belongs to you." required></textarea>
        </div>
      </div>
      <div class="d-flex justify-content-end mt-3">
        <button type="submit" class="btn" style="background:linear-gradient(135deg,#4f46e5,#06b6d4);color:#fff;border:none;border-radius:10px;padding:.7rem 1.5rem;font-weight:600;">Send to Admin</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div id="toast-container"></div>

<!-- Response Modal -->
<?php if ($role === 'admin'): ?>
<div class="modal fade" id="responseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: var(--card-bg); border: 1px solid var(--border);">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Reply to User Message</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label small text-muted">From</label>
          <div class="alert alert-sm" style="background:rgba(255,255,255,0.05);border:1px solid var(--border);color:#dbeafe;padding:.5rem .8rem;margin:0;">
            <span id="responseFromName">Admin</span>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label small text-muted">To</label>
          <div class="alert alert-sm" style="background:rgba(255,255,255,0.05);border:1px solid var(--border);color:#dbeafe;padding:.5rem .8rem;margin:0;">
            <span id="responseToEmail">user@campus.edu</span>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label small text-muted">User's Message</label>
          <div style="background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:8px;padding:1rem;color:#cbd5e1;font-size:.9rem;max-height:120px;overflow-y:auto;">
            <span id="responseUserMessage">Loading message...</span>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="responseText">Your Response</label>
          <textarea class="form-control" id="responseText" rows="5" placeholder="Write your response to the user..." style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#e2e8f0;"></textarea>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background:rgba(255,255,255,0.1);border:none;color:#94a3b8;">Cancel</button>
        <button type="button" class="btn btn-primary" id="sendResponseBtn" style="background:linear-gradient(135deg,#4f46e5,#06b6d4);border:none;color:#fff;">Send Response & Email</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<script>
// Store user data in localStorage for access in JavaScript
const userData = <?php echo json_encode($user); ?>;
localStorage.setItem('admin_user', JSON.stringify(userData));

<?php if ($role === 'admin'): ?>
  document.addEventListener('DOMContentLoaded', loadAdminDashboard);
<?php else: ?>
  document.addEventListener('DOMContentLoaded', loadDashboard);
<?php endif; ?>
</script>
</body>
</html>
