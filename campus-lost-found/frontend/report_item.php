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
  <title>Report Item — Campus Lost &amp; Found</title>
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
        <li class="nav-item"><a class="nav-link active" href="report_item.php"><i class="bi bi-plus-circle me-1"></i>Report Item</a></li>
        <li class="nav-item"><a class="nav-link" href="browse_items.php"><i class="bi bi-grid me-1"></i>Browse Items</a></li>
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

  <div class="mb-4">
    <h4 class="fw-bold mb-0" style="font-size:1.35rem;">
      <i class="bi bi-pencil-square me-2" style="color:#4f46e5;"></i>Report a Lost or Found Item
    </h4>
    <p class="text-muted mb-0" style="font-size:.87rem;">
      Fill in the details below. All fields marked <span style="color:#ef4444;">*</span> are required.
    </p>
  </div>

  <div class="row g-4">

    <!-- FORM -->
    <div class="col-lg-8">
      <div class="form-panel">

        <form id="reportForm" enctype="multipart/form-data" novalidate>

          <div class="row g-3">

            <div class="col-12">
              <div class="alert mb-0" style="background:rgba(79,70,229,0.12);border:1px solid rgba(79,70,229,0.25);color:#c7d2fe;border-radius:10px;padding:.85rem 1rem;">
                <div class="d-flex align-items-center gap-2 fw-semibold">
                  <i class="bi bi-person-badge-fill"></i>
                  Reporter Details
                </div>
                <div class="row g-2 mt-2">
                  <div class="col-md-6">
                    <label class="form-label mb-1" style="font-size:.8rem;color:#cbd5e1;">Name</label>
                    <input type="text" name="reporter_name" value="<?= htmlspecialchars($user['name']) ?>" class="form-control" readonly />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label mb-1" style="font-size:.8rem;color:#cbd5e1;">Email</label>
                    <input type="email" name="reporter_email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control" readonly />
                  </div>
                </div>
              </div>
            </div>

            <!-- Item Name -->
            <div class="col-md-8">
              <label class="form-label">Item Name <span style="color:#ef4444;">*</span></label>
              <input type="text" name="item_name" class="form-control"
                     placeholder="e.g. Blue Backpack, iPhone 14, Library Card" maxlength="150" required />
              <div class="invalid-feedback">Item name is required.</div>
            </div>

            <!-- Category -->
            <div class="col-md-4">
              <label class="form-label">Category <span style="color:#ef4444;">*</span></label>
              <select name="category" class="form-select" required>
                <option value="">Select category…</option>
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
              <div class="invalid-feedback">Please select a category.</div>
            </div>

            <!-- Status -->
            <div class="col-md-4">
              <label class="form-label">Type <span style="color:#ef4444;">*</span></label>
              <select name="status" class="form-select" required>
                <option value="">Lost or Found?</option>
                <option value="Lost">🔴  I Lost this item</option>
                <option value="Found">🟢  I Found this item</option>
              </select>
              <div class="invalid-feedback">Please select Lost or Found.</div>
            </div>

            <!-- Location -->
            <div class="col-md-5">
              <label class="form-label">Location <span style="color:#ef4444;">*</span></label>
              <input type="text" name="location" class="form-control"
                     placeholder="e.g. Main Library 2nd Floor" maxlength="200" required />
              <div class="invalid-feedback">Location is required.</div>
            </div>

            <!-- Date -->
            <div class="col-md-3">
              <label class="form-label">Date <span style="color:#ef4444;">*</span></label>
              <input type="date" name="date" id="dateInput" class="form-control" required />
              <div class="invalid-feedback">Date is required.</div>
            </div>

            <!-- Contact Number -->
            <div class="col-md-6">
              <label class="form-label">Contact Number <span style="color:#ef4444;">*</span></label>
              <div class="input-group">
                <span class="input-group-text"
                      style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);
                             color:#94a3b8;border-radius:10px 0 0 10px;">
                  <i class="bi bi-telephone"></i>
                </span>
                <input type="tel" name="contact_number" class="form-control"
                       placeholder="555-0100" maxlength="20"
                       style="border-radius:0 10px 10px 0 !important;" required />
              </div>
              <div class="invalid-feedback">Contact number is required.</div>
            </div>

            <!-- Description -->
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="4"
                        placeholder="Describe the item in detail — colour, brand, identifying marks, contents, etc."
                        maxlength="1000"></textarea>
              <div class="form-text" style="color:#475569;font-size:.78rem;">
                Be as specific as possible to help identify the item.
              </div>
            </div>

          </div><!-- /.row -->

          <!-- Submit -->
          <hr class="divider" />
          <div class="d-flex gap-3 align-items-center">
            <button type="submit" class="btn"
                    style="background:linear-gradient(135deg,#4f46e5,#06b6d4);color:#fff;
                           border:none;border-radius:10px;padding:.7rem 2rem;font-weight:600;
                           font-size:.97rem;">
              <i class="bi bi-send-fill me-2"></i>Submit Report
            </button>
            <a href="dashboard.php" class="btn"
               style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);
                      color:#94a3b8;border-radius:10px;padding:.7rem 1.5rem;">
              Cancel
            </a>
          </div>

        </form>
      </div>
    </div><!-- /.col-lg-8 -->

    <!-- SIDEBAR: Image Upload + Tips -->
    <div class="col-lg-4">

      <!-- Image upload -->
      <div class="form-panel mb-4">
        <div class="section-title mb-3">
          <i class="bi bi-image text-info"></i> Item Photo
        </div>
        <div class="upload-zone" id="uploadZone">
          <input type="file" name="image" id="imageInput" accept="image/jpeg,image/png,image/gif,image/webp" form="reportForm" />
          <div id="uploadPlaceholder">
            <div class="upload-icon"><i class="bi bi-cloud-arrow-up"></i></div>
            <p class="mb-1" style="font-size:.9rem;font-weight:600;">Click or drag to upload</p>
            <p class="text-muted" style="font-size:.78rem;">JPG, PNG, GIF, WEBP — max 5 MB</p>
          </div>
          <div id="imagePreview">
            <img src="" alt="Preview" />
            <p class="text-muted mt-2" style="font-size:.78rem;">Click to change image</p>
          </div>
        </div>
      </div>

      <!-- Tips panel -->
      <div class="form-panel">
        <div class="section-title mb-3">
          <i class="bi bi-lightbulb text-warning"></i> Reporting Tips
        </div>
        <ul class="list-unstyled mb-0" style="font-size:.84rem;color:#94a3b8;line-height:1.9;">
          <li><i class="bi bi-check2 text-success me-2"></i>Include as much detail as possible</li>
          <li><i class="bi bi-check2 text-success me-2"></i>Note any unique identifying marks</li>
          <li><i class="bi bi-check2 text-success me-2"></i>Use the exact building/room name</li>
          <li><i class="bi bi-check2 text-success me-2"></i>Upload a clear photo if available</li>
          <li><i class="bi bi-check2 text-success me-2"></i>Provide a reliable contact number</li>
        </ul>

        <hr class="divider" />

        <div style="font-size:.8rem;color:#64748b;">
          <i class="bi bi-shield-check text-info me-1"></i>
          Your contact details are only visible to logged-in campus members.
        </div>
      </div>

    </div><!-- /.col-lg-4 -->

  </div><!-- /.row -->

</div><!-- /.page-wrapper -->

<div id="toast-container"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<script>document.addEventListener('DOMContentLoaded', initReportForm);</script>
</body>
</html>
