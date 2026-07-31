/* ============================================================
   Campus Lost & Found Portal — Frontend JS
   ============================================================ */

const API = 'http://localhost:5001';   // Flask backend

/* ═══════════════════════════════════════════════════════════
   SPINNER
   ═══════════════════════════════════════════════════════════ */
function showSpinner() {
  if (!document.getElementById('spinnerOverlay')) {
    const el = document.createElement('div');
    el.id = 'spinnerOverlay';
    el.className = 'spinner-overlay';
    el.innerHTML = '<div class="spinner-ring"></div>';
    document.body.appendChild(el);
  }
}
function hideSpinner() {
  const el = document.getElementById('spinnerOverlay');
  if (el) el.remove();
}

/* ═══════════════════════════════════════════════════════════
   TOAST
   ═══════════════════════════════════════════════════════════ */
function toast(message, type = 'info', duration = 4000) {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }

  const icons = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill',
                  info: 'bi-info-circle-fill', warning: 'bi-exclamation-triangle-fill' };

  const el = document.createElement('div');
  el.className = `lf-toast toast-${type}`;
  el.innerHTML = `
    <i class="bi ${icons[type] || icons.info} toast-icon"></i>
    <span class="toast-msg">${message}</span>
    <button class="toast-close" onclick="dismissToast(this)"><i class="bi bi-x"></i></button>`;
  container.appendChild(el);

  setTimeout(() => dismissToast(el.querySelector('.toast-close')), duration);
}

function dismissToast(btn) {
  const el = btn.closest('.lf-toast');
  el.style.animation = 'slideOut .3s ease forwards';
  setTimeout(() => el.remove(), 280);
}

/* ═══════════════════════════════════════════════════════════
   FETCH HELPERS
   ═══════════════════════════════════════════════════════════ */
async function apiGet(endpoint) {
  const res = await fetch(API + endpoint);
  if (!res.ok) throw new Error((await res.json()).error || `HTTP ${res.status}`);
  return res.json();
}

async function apiPost(endpoint, data) {
  const res = await fetch(API + endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify(data),
  });
  return { status: res.status, data: await res.json() };
}

async function apiPostForm(endpoint, formData) {
  const res = await fetch(API + endpoint, { method: 'POST', body: formData });
  return { status: res.status, data: await res.json() };
}

async function apiPut(endpoint, data) {
  const res = await fetch(API + endpoint, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify(data),
  });
  return { status: res.status, data: await res.json() };
}

/* ═══════════════════════════════════════════════════════════
   STATUS BADGE HTML
   ═══════════════════════════════════════════════════════════ */
function statusBadge(status) {
  const cls = { Lost: 'badge-lost', Found: 'badge-found', Claimed: 'badge-claimed' };
  const ico = { Lost: '📍', Found: '✅', Claimed: '🏷️' };
  return `<span class="status-badge ${cls[status] || ''}">
            <span class="status-dot"></span>${status}
          </span>`;
}

/* ═══════════════════════════════════════════════════════════
   IMAGE URL HELPER
   ═══════════════════════════════════════════════════════════ */
function imgUrl(filename) {
  if (!filename) return null;
  return `${API}/uploads/${filename}`;
}

function itemImageHtml(image, alt) {
  if (image) {
    return `<img src="${imgUrl(image)}" alt="${alt}" onerror="this.parentElement.innerHTML='<i class=\\'bi bi-image no-img\\'></i>'" />`;
  }
  return `<i class="bi bi-image no-img"></i>`;
}

/* ═══════════════════════════════════════════════════════════
   DASHBOARD
   ═══════════════════════════════════════════════════════════ */
async function loadDashboard() {
  showSpinner();
  try {
    const [stats, recent] = await Promise.all([
      apiGet('/stats'),
      apiGet('/items/recent?limit=8'),
    ]);

    // stat cards
    setText('stat-lost',    stats.lost    ?? 0);
    setText('stat-found',   stats.found   ?? 0);
    setText('stat-pending', (stats.lost + stats.found) ?? 0);
    setText('stat-claimed', stats.claimed ?? 0);

    // recent table
    const tbody = document.getElementById('recentBody');
    if (!tbody) return;

    if (!recent.length) {
      tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="bi bi-inbox"></i><p>No items yet.</p></div></td></tr>`;
      return;
    }

    tbody.innerHTML = recent.map(item => `
      <tr>
        <td><span class="fw-semibold">${esc(item.item_name)}</span></td>
        <td><span class="cat-chip">${esc(item.category)}</span></td>
        <td>${statusBadge(item.status)}</td>
        <td><i class="bi bi-geo-alt me-1 text-muted" style="font-size:.8rem"></i>${esc(item.location)}</td>
        <td>${item.date || ''}</td>
        <td class="text-muted" style="font-size:.78rem">${timeAgo(item.created_at)}</td>
      </tr>`).join('');
  } catch (err) {
    toast('Failed to load dashboard: ' + err.message, 'error');
  } finally {
    hideSpinner();
  }
}

/* ═══════════════════════════════════════════════════════════
   BROWSE ITEMS
   ═══════════════════════════════════════════════════════════ */
let currentPage = 1;
let currentFilters = {};

async function loadItems(page = 1) {
  currentPage = page;
  showSpinner();

  const params = new URLSearchParams({ page, limit: 9, ...currentFilters });

  try {
    const data = await apiGet('/items?' + params.toString());
    renderItemCards(data.items);
    renderPagination(data.page, data.total_pages, data.total);
  } catch (err) {
    toast('Failed to load items: ' + err.message, 'error');
  } finally {
    hideSpinner();
  }
}

function renderItemCards(items) {
  const grid = document.getElementById('itemsGrid');
  if (!grid) return;

  if (!items.length) {
    grid.innerHTML = `<div class="col-12"><div class="empty-state panel"><i class="bi bi-search"></i><p>No items match your search.</p></div></div>`;
    return;
  }

  grid.innerHTML = items.map(item => {
    const isClaimed = item.status === 'Claimed';
    const btnHtml   = isClaimed
      ? `<span class="btn-claimed"><i class="bi bi-check2"></i> Claimed</span>`
      : `<button class="btn-claim" onclick="openClaimModal(${item.id},'${esc(item.item_name)}')">
           <i class="bi bi-hand-index me-1"></i>Claim
         </button>`;

    return `
    <div class="col-lg-4 col-md-6 col-sm-12">
      <div class="item-card">
        <div class="item-card-img">
          ${itemImageHtml(item.image, item.item_name)}
        </div>
        <div class="item-card-body">
          <div class="d-flex align-items-start justify-content-between gap-1 mb-1">
            <h6 class="item-card-title" title="${esc(item.item_name)}">${esc(item.item_name)}</h6>
            ${statusBadge(item.status)}
          </div>
          <div class="item-card-meta">
            <span><i class="bi bi-tag"></i>${esc(item.category)}</span>
            <span><i class="bi bi-geo-alt"></i>${esc(item.location)}</span>
            <span><i class="bi bi-calendar3"></i>${item.date || ''}</span>
          </div>
          <p class="item-card-desc">${esc(item.description || 'No description provided.')}</p>
        </div>
        <div class="item-card-footer">
          <span class="contact-chip"><i class="bi bi-telephone"></i>${esc(item.contact_number)}</span>
          ${btnHtml}
        </div>
      </div>
    </div>`;
  }).join('');
}

function renderPagination(page, totalPages, total) {
  const wrap = document.getElementById('pagination');
  if (!wrap) return;

  const countEl = document.getElementById('itemCount');
  if (countEl) countEl.textContent = `${total} item${total !== 1 ? 's' : ''} found`;

  if (totalPages <= 1) { wrap.innerHTML = ''; return; }

  let html = `<nav><ul class="pagination justify-content-center mb-0">`;
  html += `<li class="page-item ${page===1?'disabled':''}">
    <button class="page-link" onclick="loadItems(${page-1})"><i class="bi bi-chevron-left"></i></button></li>`;

  for (let p = Math.max(1, page-2); p <= Math.min(totalPages, page+2); p++) {
    html += `<li class="page-item ${p===page?'active':''}">
      <button class="page-link" onclick="loadItems(${p})">${p}</button></li>`;
  }

  html += `<li class="page-item ${page===totalPages?'disabled':''}">
    <button class="page-link" onclick="loadItems(${page+1})"><i class="bi bi-chevron-right"></i></button></li>`;
  html += `</ul></nav>`;
  wrap.innerHTML = html;
}

function applyFilters() {
  currentFilters = {};
  const search   = document.getElementById('searchInput')?.value.trim();
  const category = document.getElementById('categoryFilter')?.value;
  const status   = document.getElementById('statusFilter')?.value;
  if (search)   currentFilters.search   = search;
  if (category) currentFilters.category = category;
  if (status)   currentFilters.status   = status;
  loadItems(1);
}

/* ── Claim modal ────────────────────────────────────────────── */
let claimItemId = null;

function openClaimModal(id, name) {
  claimItemId = id;
  const el = document.getElementById('claimItemName');
  if (el) el.textContent = name;
  const modal = new bootstrap.Modal(document.getElementById('claimModal'));
  modal.show();
}

async function confirmClaim() {
  if (!claimItemId) return;
  showSpinner();
  try {
    const { status, data } = await apiPost(`/claim/${claimItemId}`, {});
    if (status === 200) {
      toast('Item successfully claimed!', 'success');
      bootstrap.Modal.getInstance(document.getElementById('claimModal')).hide();
      await loadItems(currentPage);
    } else {
      toast(data.error || 'Failed to claim item.', 'error');
    }
  } catch (err) {
    toast('Error: ' + err.message, 'error');
  } finally {
    hideSpinner();
    claimItemId = null;
  }
}

/* ═══════════════════════════════════════════════════════════
   REPORT ITEM FORM
   ═══════════════════════════════════════════════════════════ */
function initReportForm() {
  // Image preview
  const fileInput = document.getElementById('imageInput');
  if (fileInput) {
    fileInput.addEventListener('change', function () {
      const file = this.files[0];
      if (!file) return;

      if (!['image/jpeg','image/png','image/gif','image/webp'].includes(file.type)) {
        toast('Invalid file type. Allowed: JPG, PNG, GIF, WEBP', 'error');
        this.value = '';
        return;
      }
      if (file.size > 5 * 1024 * 1024) {
        toast('Image too large. Max 5 MB.', 'error');
        this.value = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = e => {
        const preview = document.getElementById('imagePreview');
        preview.style.display = 'block';
        preview.querySelector('img').src = e.target.result;
        document.getElementById('uploadPlaceholder').style.display = 'none';
      };
      reader.readAsDataURL(file);
    });
  }

  // Set today as default date
  const dateInput = document.getElementById('dateInput');
  if (dateInput && !dateInput.value) {
    dateInput.value = new Date().toISOString().split('T')[0];
  }

  // Form submit
  const form = document.getElementById('reportForm');
  if (form) {
    form.addEventListener('submit', submitReportForm);
  }
}

async function submitReportForm(e) {
  e.preventDefault();
  const form = e.target;

  // Client-side validation
  const required = ['item_name','category','status','location','date','contact_number'];
  let valid = true;
  required.forEach(name => {
    const el = form.elements[name];
    if (!el || !el.value.trim()) {
      el?.classList.add('is-invalid');
      valid = false;
    } else {
      el?.classList.remove('is-invalid');
    }
  });
  if (!valid) { toast('Please fill in all required fields.', 'warning'); return; }

  showSpinner();

  try {
    const fd = new FormData(form);
    const response = await fetch(`${API}/item`, { method: 'POST', body: fd });
    const data = await response.json();

    if (response.ok && data.success) {
      toast('Item reported successfully!', 'success');
      form.reset();
      document.getElementById('imagePreview').style.display = 'none';
      document.getElementById('uploadPlaceholder').style.display = '';
    } else {
      toast(data.error || 'Failed to report item.', 'error');
    }
  } catch (err) {
    toast('Network error: ' + err.message, 'error');
  } finally {
    hideSpinner();
  }
}

/* ═══════════════════════════════════════════════════════════
   UTILITIES
   ═══════════════════════════════════════════════════════════ */
function setText(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = val;
}

function esc(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function timeAgo(dateStr) {
  if (!dateStr) return '';
  const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
  if (diff < 60)   return 'Just now';
  if (diff < 3600) return Math.floor(diff/60) + 'm ago';
  if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
  return Math.floor(diff/86400) + 'd ago';
}

/* Drag-over highlight for upload zone */
document.addEventListener('DOMContentLoaded', () => {
  const zone = document.querySelector('.upload-zone');
  if (zone) {
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', () => zone.classList.remove('dragover'));
  }
});
