<?php
require_once 'config.php';
require_login();

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard — Campus Lost &amp; Found</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <link href="css/style.css" rel="stylesheet" />
</head>
<body>

<!-- ── NAVBAR ──────────────────────────────────────────────── -->
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
        <li class="nav-item">
          <a class="nav-link active" href="dashboard.php">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="report_item.php">
            <i class="bi bi-plus-circle me-1"></i>Report Item
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="browse_items.php">
            <i class="bi bi-grid me-1"></i>Browse Items
          </a>
        </li>
      </ul>
      <div class="d-flex align-items-center gap-3">
        <span class="user-badge">
          <i class="bi bi-person-fill me-1"></i>
          <?= htmlspecialchars($user['name']) ?>
        </span>
        <a href="logout.php" class="btn btn-sm btn-outline-danger border-0"
           style="background:rgba(239,68,68,.1);color:#f87171;border-radius:8px;">
          <i class="bi bi-box-arrow-right me-1"></i>Logout
        </a>
      </div>
    </div>
  </div>
</nav>

<!-- ── PAGE CONTENT ───────────────────────────────────────── -->
<div class="page-wrapper">

  <!-- Page header -->
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h4 class="fw-bold mb-0" style="font-size:1.35rem;">
        👋 Welcome back, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>
      </h4>
      <p class="text-muted mb-0" style="font-size:.87rem;">
        Here's what's happening on campus today.
      </p>
    </div>
    <a href="report_item.php" class="btn btn-sm"
       style="background:linear-gradient(135deg,#4f46e5,#06b6d4);color:#fff;
              border:none;border-radius:10px;padding:.5rem 1.1rem;font-weight:600;">
      <i class="bi bi-plus-lg me-1"></i>Report Item
    </a>
  </div>

  <!-- Stat cards row -->
  <div class="row g-3 mb-4">

    <div class="col-xl-3 col-sm-6">
      <div class="stat-card">
        <div class="stat-icon icon-lost"><i class="bi bi-search"></i></div>
        <div>
          <div class="stat-label">Total Lost</div>
          <div class="stat-value" id="stat-lost">—</div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-sm-6">
      <div class="stat-card">
        <div class="stat-icon icon-found"><i class="bi bi-check2-circle"></i></div>
        <div>
          <div class="stat-label">Total Found</div>
          <div class="stat-value" id="stat-found">—</div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-sm-6">
      <div class="stat-card">
        <div class="stat-icon icon-pending"><i class="bi bi-clock-history"></i></div>
        <div>
          <div class="stat-label">Active Reports</div>
          <div class="stat-value" id="stat-pending">—</div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-sm-6">
      <div class="stat-card">
        <div class="stat-icon icon-claimed"><i class="bi bi-bag-check-fill"></i></div>
        <div>
          <div class="stat-label">Claimed</div>
          <div class="stat-value" id="stat-claimed">—</div>
        </div>
      </div>
    </div>

  </div>

  <!-- Recent Activity -->
  <div class="panel">
    <div class="section-title">
      <i class="bi bi-activity text-info"></i> Recent Activity
    </div>

    <div class="table-responsive">
      <table class="lf-table">
        <thead>
          <tr>
            <th>Item</th>
            <th>Category</th>
            <th>Status</th>
            <th>Location</th>
            <th>Date</th>
            <th>Reported</th>
          </tr>
        </thead>
        <tbody id="recentBody">
          <tr>
            <td colspan="6" class="text-center text-muted py-4">
              <div class="spinner-border spinner-border-sm text-secondary me-2"></div>
              Loading…
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="text-end mt-3">
      <a href="browse_items.php" style="font-size:.85rem;color:#94a3b8;" class="text-decoration-none">
        View all items <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>
  </div>

</div><!-- /.page-wrapper -->

<div id="toast-container"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<script>loadDashboard();</script>
</body>
</html>
