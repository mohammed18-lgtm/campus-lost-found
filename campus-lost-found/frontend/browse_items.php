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

    <div style="min-width:180px;max-width:220px;">
      <label class="form-label" style="font-size:.78rem;text-transform:uppercase;
             letter-spacing:.05em;color:#64748b;margin-bottom:.3rem;">Location</label>
      <select id="locationFilter" class="form-select" onchange="applyFilters()">
        <option value="">All Locations</option>
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
        <div class="row g-3 mb-2">
          <div class="col-md-6">
            <label class="form-label small">Full name</label>
            <input type="text" id="claimantName" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label small">Email</label>
            <input type="email" id="claimantEmail" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label small">Phone</label>
            <input type="tel" id="claimantPhone" class="form-control" />
          </div>
          <div class="col-12">
            <label class="form-label small">Identifying details</label>
            <textarea id="claimantDetails" class="form-control" rows="3" placeholder="Describe the item, where it was found, and any identifying features." required></textarea>
          </div>
        </div>
        <p class="mb-0" style="font-size:.83rem;color:#64748b;">
          <i class="bi bi-info-circle me-1"></i>
          The admin will review the details before approving the claim.
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

<!-- ── ITEM DETAILS MODAL ────────────────────────────────── -->
<div class="modal fade" id="itemDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-info-circle me-2 text-info"></i>Item Details
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="font-size:.92rem;color:#cbd5e1;line-height:1.8;">
        <div class="row g-3">
          <div class="col-12">
            <div id="detailsImage" style="width:100%;height:250px;background:rgba(0,0,0,.3);border-radius:10px;overflow:hidden;display:flex;align-items:center;justify-content:center;">
              <img id="detailsImageContent" src="" alt="Item" style="max-width:100%;max-height:100%;object-fit:cover;" />
              <i class="bi bi-image" id="detailsImagePlaceholder" style="font-size:3rem;color:#64748b;"></i>
            </div>
          </div>
          <div class="col-md-6">
            <label class="small" style="color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">Item Name</label>
            <p id="detailsName" class="fw-600" style="color:#fff;margin:0;"></p>
          </div>
          <div class="col-md-6">
            <label class="small" style="color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">Category</label>
            <p id="detailsCategory" style="color:#cbd5e1;margin:0;"></p>
          </div>
          <div class="col-md-6">
            <label class="small" style="color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">Status</label>
            <p id="detailsStatus" style="color:#cbd5e1;margin:0;"></p>
          </div>
          <div class="col-md-6">
            <label class="small" style="color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">Location</label>
            <p id="detailsLocation" style="color:#cbd5e1;margin:0;"></p>
          </div>
          <div class="col-md-6">
            <label class="small" style="color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">Date</label>
            <p id="detailsDate" style="color:#cbd5e1;margin:0;"></p>
          </div>
          <div class="col-md-6">
            <label class="small" style="color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">Contact</label>
            <p id="detailsContact" style="color:#cbd5e1;margin:0;"></p>
          </div>
          <div class="col-12">
            <label class="small" style="color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">Description</label>
            <p id="detailsDescription" style="color:#cbd5e1;margin:0;line-height:1.6;"></p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn" style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#94a3b8;border-radius:10px;padding:.55rem 1.2rem;" data-bs-dismiss="modal">Close</button>
        <button type="button" id="detailsClaimBtn" class="btn" style="background:linear-gradient(135deg,#4f46e5,#06b6d4);color:#fff;border:none;border-radius:10px;padding:.55rem 1.4rem;font-weight:600;" onclick="openClaimModalFromDetails()">
          <i class="bi bi-hand-index me-1"></i>Claim This Item
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
  
  let detailsItemId = null;
  
  function openItemDetails(item) {
    detailsItemId = item.id;
    document.getElementById('detailsName').textContent = item.item_name || 'Unknown';
    document.getElementById('detailsCategory').textContent = item.category || 'N/A';
    document.getElementById('detailsStatus').innerHTML = `<span class="badge" style="${getStatusStyle(item.status)}">${item.status}</span>`;
    document.getElementById('detailsLocation').textContent = item.location || 'N/A';
    document.getElementById('detailsDate').textContent = item.date || 'N/A';
    document.getElementById('detailsContact').textContent = item.contact_number || 'Not provided';
    document.getElementById('detailsDescription').textContent = item.description || 'No description provided.';
    
    const imageEl = document.getElementById('detailsImageContent');
    const placeholderEl = document.getElementById('detailsImagePlaceholder');
    
    if (item.image) {
      imageEl.src = item.image;
      imageEl.style.display = 'block';
      placeholderEl.style.display = 'none';
    } else {
      imageEl.style.display = 'none';
      placeholderEl.style.display = 'block';
    }
    
    const claimBtn = document.getElementById('detailsClaimBtn');
    claimBtn.disabled = item.status === 'Claimed';
    claimBtn.style.opacity = item.status === 'Claimed' ? '0.5' : '1';
    
    const modal = new bootstrap.Modal(document.getElementById('itemDetailsModal'));
    modal.show();
  }
  
  function getStatusStyle(status) {
    if (status === 'Lost') return 'background:rgba(239,68,68,.2);color:#fca5a5;';
    if (status === 'Found') return 'background:rgba(34,197,94,.2);color:#86efac;';
    if (status === 'Claimed') return 'background:rgba(59,130,246,.2);color:#93c5fd;';
    return 'background:rgba(148,163,184,.2);color:#cbd5e1;';
  }
  
  function openClaimModalFromDetails() {
    if (detailsItemId) {
      bootstrap.Modal.getInstance(document.getElementById('itemDetailsModal')).hide();
      setTimeout(() => {
        openClaimModal(detailsItemId, document.getElementById('detailsName').textContent);
      }, 200);
    }
  }
  
  document.addEventListener('DOMContentLoaded', () => loadItems(1));
</script>
</body>
</html>
