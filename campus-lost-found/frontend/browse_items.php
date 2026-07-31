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
  <title>Browse Items — Campus Lost &amp; Found</title>
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
        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="report_item.php"><i class="bi bi-plus-circle me-1"></i>Report Item</a></li>
        <li class="nav-item"><a class="nav-link active" href="browse_items.php"><i class="bi bi-grid me-1"></i>Browse Items</a></li>
      </ul>
      <div class="d-flex align-items-center gap-3">
        <span class="user-badge"><i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($user['name']) ?></span>
        <a href="logout.php" class="btn btn-sm border-0" style="background:rgba(239,68,68,.1);color:#f87171;border-radius:8px;">
          <i class="bi bi-box-arrow-right me-1"></i>Logout
        </a>
      </div>
    </div>
  </div>
</nav>

<!-- ── PAGE CONTENT ───────────────────────────────────────── -->
<div class="page-wrapper">

  <!-- Page header -->
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h4 class="fw-bold mb-0" style="font-size:1.35rem;">
        <i class="bi bi-grid-fill me-2" style="color:#4f46e5;"></i>Browse Items
      </h4>
      <p class="text-muted mb-0" style="font-size:.87rem;">
        Search and claim lost or found items reported on campus.
      </p>
    </div>
    <span class="text-muted" id="itemCount" style="font-size:.85rem;"></span>
  </div>

  <!-- Filter bar -->
  <div class="filter-bar">

    <div style="flex:2;min-width:200px;max-width:360px;">
      <label class="form-label" style="font-size:.78rem;text-transform:uppercase;
             letter-spacing:.05em;color:#64748b;margin-bottom:.3rem;">Search</label>
      <div class="input-group">
        <span class="input-group-text"
              style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);
                     color:#64748b;border-radius:10px 0 0 10px;">
          <i class="bi bi-search"></i>
        </span>
        <input type="text" id="searchInput" class="form-control"
               placeholder="Search by item name…"
               style="border-radius:0 10px 10px 0 !important;"
               onkeyup="if(event.key==='Enter')applyFilters()" />
      </div>
    </div>

    <div style="min-width:160px;max-width:200px;">
      <label class="form-label" style="font-size:.78rem;text-transform:uppercase;
             letter-spacing:.05em;color:#64748b;margin-bottom:.3rem;">Category</label>
      <select id="categoryFilter" class="form-select" onchange="applyFilters()">
        <option value="">All Categories</option>
        <option>Electronics</option>
        <option>Books</option>
        <option>Bags</option>
        <option>Keys</option>
        <option>Cards</option>
        <option>Clothing</option>
        <option>Accessories</option>
        <option>Sports</option>
        <option>Documents</option>
        <option>Other</option>
      </select>
    </div>

    <div style="min-width:140px;max-width:180px;">
      <label class="form-label" style="font-size:.78rem;text-transform:uppercase;
             letter-spacing:.05em;color:#64748b;margin-bottom:.3rem;">Status</label>
      <select id="statusFilter" class="form-select" onchange="applyFilters()">
        <option value="">All Statuses</option>
        <option value="Lost">Lost</option>
        <option value="Found">Found</option>
        <option value="Claimed">Claimed</option>
      </select>
    </div>

    <div class="d-flex align-items-end gap-2">
      <button onclick="applyFilters()" class="btn"
              style="background:linear-gradient(135deg,#4f46e5,#06b6d4);color:#fff;
                     border:none;border-radius:10px;padding:.6rem 1.2rem;
                     font-weight:600;white-space:nowrap;">
        <i class="bi bi-funnel me-1"></i>Filter
      </button>
      <button onclick="clearFilters()" class="btn"
              style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);
                     color:#94a3b8;border-radius:10px;padding:.6rem 1rem;white-space:nowrap;">
        <i class="bi bi-x-circle me-1"></i>Clear
      </button>
    </div>

  </div><!-- /.filter-bar -->

  <!-- Items grid -->
  <div class="row g-4 mb-4" id="itemsGrid">
    <!-- populated by JS -->
    <div class="col-12 text-center py-5">
      <div class="spinner-border text-secondary" role="status"></div>
      <p class="text-muted mt-3">Loading items…</p>
    </div>
  </div>

  <!-- Pagination -->
  <div id="pagination" class="mb-4"></div>

</div><!-- /.page-wrapper -->

<!-- ── CLAIM MODAL ─────────────────────────────────────────── -->
<div class="modal fade" id="claimModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-hand-index me-2 text-info"></i>Confirm Claim
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="font-size:.92rem;color:#cbd5e1;line-height:1.7;">
        <p>Are you sure you want to claim <strong id="claimItemName" style="color:#fff;"></strong>?</p>
        <p class="mb-0" style="font-size:.83rem;color:#64748b;">
          <i class="bi bi-info-circle me-1"></i>
          This will mark the item as <span class="status-badge badge-claimed">Claimed</span>
          and it will no longer be available for other claims.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn"
                style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);
                       color:#94a3b8;border-radius:10px;padding:.55rem 1.2rem;"
                data-bs-dismiss="modal">
          Cancel
        </button>
        <button type="button" class="btn"
                style="background:linear-gradient(135deg,#4f46e5,#06b6d4);color:#fff;
                       border:none;border-radius:10px;padding:.55rem 1.4rem;font-weight:600;"
                onclick="confirmClaim()">
          <i class="bi bi-check2 me-1"></i>Yes, Claim It
        </button>
      </div>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<script>
  function clearFilters() {
    document.getElementById('searchInput').value    = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('statusFilter').value   = '';
    currentFilters = {};
    loadItems(1);
  }
  document.addEventListener('DOMContentLoaded', () => loadItems(1));
</script>
</body>
</html>
