<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/?page=login');
    exit;
}

$role        = $_SESSION['role']      ?? '';
$fullName    = $_SESSION['full_name'] ?? 'User';
$userId      = $_SESSION['user_id']   ?? 0;
$currentPage = $_GET['page']          ?? '';

// ── Unread notifications ─────────────────────────────────────────────────────
$unreadCount = 0;
try {
    $db   = getDB();
    $nStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $nStmt->execute([$userId]);
    $unreadCount = (int)$nStmt->fetchColumn();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($viewTitle ?? 'Nam Yang Freight') ?></title>

<!-- Bootstrap 5 + Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* ═══════════════════════════════════════════════════
   CSS Variables & Reset
═══════════════════════════════════════════════════ */
:root {
  --sidebar-width:      240px;
  --sidebar-collapsed:   64px;
  --topbar-height:       58px;
  --brand-dark:        #1e3a5f;
  --brand-mid:         #2d6a9f;
  --brand-light:       #e8f0fb;
  --accent:            #3b82f6;
  --sidebar-text:      #c8d8ee;
  --sidebar-hover:     rgba(255,255,255,0.08);
  --sidebar-active:    rgba(255,255,255,0.15);
  --body-bg:           #f0f4f8;
  --card-radius:       12px;
  --transition:        0.22s ease;
}

*, *::before, *::after { box-sizing: border-box; }

body {
  margin: 0;
  font-family: 'Segoe UI', system-ui, sans-serif;
  background: var(--body-bg);
  color: #1e293b;
  overflow-x: hidden;
}

/* ═══════════════════════════════════════════════════
   Sidebar
═══════════════════════════════════════════════════ */
#sidebar {
  position: fixed;
  top: 0; left: 0; bottom: 0;
  width: var(--sidebar-width);
  background: var(--brand-dark);
  display: flex;
  flex-direction: column;
  z-index: 200;
  transition: width var(--transition);
  overflow: hidden;
}

/* Brand / Logo area */
#sidebar .sidebar-brand {
  height: var(--topbar-height);
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 18px;
  background: rgba(0,0,0,0.18);
  flex-shrink: 0;
  white-space: nowrap;
}
#sidebar .sidebar-brand .brand-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}
#sidebar .sidebar-brand .brand-name {
  font-size: 0.95rem;
  font-weight: 700;
  color: #fff;
  letter-spacing: 0.02em;
  overflow: hidden;
}

/* Nav area */
#sidebar .sidebar-nav {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 12px 0 20px;
  scrollbar-width: thin;
  scrollbar-color: rgba(255,255,255,0.15) transparent;
}
#sidebar .sidebar-nav::-webkit-scrollbar { width: 4px; }
#sidebar .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }

/* Section labels */
#sidebar .nav-label {
  font-size: 0.6rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.35);
  padding: 14px 18px 4px;
  white-space: nowrap;
  overflow: hidden;
}

/* Nav links */
#sidebar .nav-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 18px;
  color: var(--sidebar-text);
  text-decoration: none;
  font-size: 0.855rem;
  font-weight: 500;
  border-radius: 0;
  white-space: nowrap;
  transition: background var(--transition), color var(--transition);
}
#sidebar .nav-link i {
  font-size: 1.05rem;
  flex-shrink: 0;
  width: 20px;
  text-align: center;
}
#sidebar .nav-link:hover  { background: var(--sidebar-hover); color: #fff; }
#sidebar .nav-link.active {
  background: var(--sidebar-active);
  color: #fff;
  border-left: 3px solid var(--accent);
  padding-left: 15px;
}

/* Sidebar footer (user info) */
#sidebar .sidebar-footer {
  padding: 12px 16px;
  background: rgba(0,0,0,0.18);
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  white-space: nowrap;
  overflow: hidden;
}
#sidebar .sidebar-footer .avatar {
  width: 34px; height: 34px;
  border-radius: 50%;
  background: var(--brand-mid);
  color: #fff;
  font-size: 0.85rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
#sidebar .sidebar-footer .user-info { overflow: hidden; }
#sidebar .sidebar-footer .user-name  { font-size: 0.8rem; font-weight: 600; color: #fff; overflow: hidden; text-overflow: ellipsis; }
#sidebar .sidebar-footer .user-role  { font-size: 0.65rem; color: var(--sidebar-text); }

/* ═══════════════════════════════════════════════════
   Topbar
═══════════════════════════════════════════════════ */
#topbar {
  position: fixed;
  top: 0;
  left: var(--sidebar-width);
  right: 0;
  height: var(--topbar-height);
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  padding: 0 24px;
  z-index: 100;
  gap: 12px;
  transition: left var(--transition);
  box-shadow: 0 1px 8px rgba(0,0,0,0.06);
}

#topbar .toggle-btn {
  background: none;
  border: none;
  padding: 6px 8px;
  border-radius: 8px;
  cursor: pointer;
  color: #64748b;
  font-size: 1.2rem;
  display: flex;
  align-items: center;
  transition: background 0.15s;
}
#topbar .toggle-btn:hover { background: #f1f5f9; color: #1e293b; }

#topbar .page-title {
  font-size: 1rem;
  font-weight: 700;
  color: #1e293b;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Notification bell */
#topbar .notif-btn {
  position: relative;
  background: none;
  border: none;
  padding: 6px 8px;
  border-radius: 8px;
  cursor: pointer;
  color: #64748b;
  font-size: 1.2rem;
  transition: background 0.15s;
}
#topbar .notif-btn:hover { background: #f1f5f9; color: #1e293b; }
#topbar .notif-badge {
  position: absolute;
  top: 2px; right: 2px;
  min-width: 16px; height: 16px;
  background: #ef4444;
  color: #fff;
  font-size: 0.55rem;
  font-weight: 700;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 3px;
  line-height: 1;
}

/* User dropdown */
#topbar .user-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: none;
  border: 1px solid #e2e8f0;
  padding: 5px 12px 5px 6px;
  border-radius: 20px;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s;
  text-decoration: none;
  color: #1e293b;
}
#topbar .user-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
#topbar .user-btn .avatar {
  width: 28px; height: 28px;
  border-radius: 50%;
  background: var(--brand-dark);
  color: #fff;
  font-size: 0.75rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}
#topbar .user-btn .uname { font-size: 0.8rem; font-weight: 600; }

/* ═══════════════════════════════════════════════════
   Main content wrapper
══════════════════════��════════════════════════════ */
#main-wrapper {
  margin-left: var(--sidebar-width);
  margin-top: var(--topbar-height);
  padding: 24px;
  min-height: calc(100vh - var(--topbar-height));
  transition: margin-left var(--transition);
}

/* ═══════════════════════════════════════════════════
   Collapsed sidebar (class on <body>)
═══════════════════════════════════════════════════ */
body.sidebar-collapsed #sidebar                { width: var(--sidebar-collapsed); }
body.sidebar-collapsed #topbar                 { left: var(--sidebar-collapsed); }
body.sidebar-collapsed #main-wrapper           { margin-left: var(--sidebar-collapsed); }
body.sidebar-collapsed #sidebar .nav-label     { opacity: 0; }
body.sidebar-collapsed #sidebar .nav-link span { display: none; }
body.sidebar-collapsed #sidebar .brand-name    { display: none; }
body.sidebar-collapsed #sidebar .user-info     { display: none; }

/* ═══════════════════════════════════════════════════
   Cards (global style)
═══════════════════════════════════════════════════ */
.card {
  border: none !important;
  border-radius: var(--card-radius) !important;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07) !important;
}
.card-header { background: #fff !important; border-bottom: 1px solid #f1f5f9 !important; }

/* ═══════════════════════════════════════════════════
   Status badges
═══════════════════════════════════════════════════ */
.status-pending_customs  { background:#fee2e2;color:#b91c1c;padding:3px 8px;border-radius:6px;font-size:0.75rem;font-weight:600; }
.status-cleared          { background:#fef9c3;color:#92400e;padding:3px 8px;border-radius:6px;font-size:0.75rem;font-weight:600; }
.status-waiting_pickup   { background:#ffedd5;color:#c2410c;padding:3px 8px;border-radius:6px;font-size:0.75rem;font-weight:600; }
.status-in_transit       { background:#dbeafe;color:#1d4ed8;padding:3px 8px;border-radius:6px;font-size:0.75rem;font-weight:600; }
.status-delivered        { background:#dcfce7;color:#15803d;padding:3px 8px;border-radius:6px;font-size:0.75rem;font-weight:600; }
.status-pending_approval { background:#f3e8ff;color:#7e22ce;padding:3px 8px;border-radius:6px;font-size:0.75rem;font-weight:600; }
.status-cancelled        { background:#f1f5f9;color:#64748b;padding:3px 8px;border-radius:6px;font-size:0.75rem;font-weight:600; }

/* ═══════════════════════════════════════════════════
   Responsive – mobile breakpoint
═══════════════════════════════════════════════════ */
@media (max-width: 768px) {
  #sidebar {
    transform: translateX(-100%);
    transition: transform var(--transition), width var(--transition);
  }
  body.sidebar-open #sidebar { transform: translateX(0); width: var(--sidebar-width) !important; }
  body.sidebar-open #sidebar .nav-label     { opacity: 1; }
  body.sidebar-open #sidebar .nav-link span { display: inline; }
  body.sidebar-open #sidebar .brand-name    { display: inline; }
  body.sidebar-open #sidebar .user-info     { display: block; }
  #topbar       { left: 0 !important; }
  #main-wrapper { margin-left: 0 !important; padding: 16px; }
  #overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 199; }
  body.sidebar-open #overlay { display: block; }
}
</style>
</head>
<body>

<!-- ══ OVERLAY (mobile only) ══════════════════════════════════════════════ -->
<div id="overlay" onclick="closeSidebar()"></div>

<!-- ══ SIDEBAR ════════════════════════════════════════════════════════════ -->
<aside id="sidebar">

  <!-- Brand -->
  <div class="sidebar-brand">
    <span class="brand-icon">🚢</span>
    <span class="brand-name">Nam Yang Freight</span>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav">
    <?php include __DIR__ . '/sidebar_menu.php'; ?>
  </nav>

  <!-- Footer -->
  <div class="sidebar-footer">
    <div class="avatar"><?= strtoupper(mb_substr($fullName, 0, 1)) ?></div>
    <div class="user-info">
      <div class="user-name"><?= htmlspecialchars($fullName) ?></div>
      <div class="user-role"><?= ucfirst($role) ?></div>
    </div>
  </div>
</aside>

<!-- ══ TOPBAR ══════════════════════════════════════════════════════════════ -->
<header id="topbar">
  <!-- Toggle sidebar button -->
  <button class="toggle-btn" onclick="toggleSidebar()" title="Thu/Mở menu">
    <i class="bi bi-list"></i>
  </button>

  <!-- Page title -->
  <span class="page-title"><?= htmlspecialchars($viewTitle ?? 'Nam Yang Freight') ?></span>

  <!-- Notification bell -->
  <button class="notif-btn" data-bs-toggle="offcanvas" data-bs-target="#notifOffcanvas" title="Thông báo">
    <i class="bi bi-bell"></i>
    <?php if ($unreadCount > 0): ?>
    <span class="notif-badge"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
    <?php endif; ?>
  </button>

  <!-- User dropdown -->
  <div class="dropdown">
    <button class="user-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
      <span class="avatar"><?= strtoupper(mb_substr($fullName, 0, 1)) ?></span>
      <span class="uname d-none d-sm-inline"><?= htmlspecialchars($fullName) ?></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:180px;border-radius:10px;border:1px solid #e2e8f0;">
      <li>
        <span class="dropdown-item-text text-muted small">
          <i class="bi bi-shield-check me-1"></i><?= ucfirst($role) ?>
        </span>
      </li>
      <li><hr class="dropdown-divider my-1"></li>
      <li>
        <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/?page=logout">
          <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
        </a>
      </li>
    </ul>
  </div>
</header>

<!-- ══ MAIN CONTENT ════════════════════════════════════════════════════════ -->
<main id="main-wrapper">
  <?php if (isset($viewFile) && file_exists($viewFile)): ?>
    <?php include $viewFile; ?>
  <?php else: ?>
    <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <span>⚠️ View không tồn tại: <code><?= htmlspecialchars($viewFile ?? 'undefined') ?></code></span>
    </div>
  <?php endif; ?>
</main>

<!-- ══ NOTIFICATION OFFCANVAS ══════════════════════════════════════════════ -->
<div class="offcanvas offcanvas-end" id="notifOffcanvas" style="max-width:360px">
  <div class="offcanvas-header border-bottom"
       style="background:linear-gradient(90deg,var(--brand-dark),var(--brand-mid));color:#fff">
    <h6 class="offcanvas-title fw-bold mb-0">
      <i class="bi bi-bell-fill me-2"></i>Thông báo
      <?php if ($unreadCount > 0): ?>
      <span class="badge bg-danger ms-2" style="font-size:0.7rem"><?= $unreadCount ?></span>
      <?php endif; ?>
    </h6>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-header border-bottom py-2 px-3">
    <button class="btn btn-link btn-sm text-muted p-0" id="markAllReadBtn">
      <i class="bi bi-check2-all me-1"></i>Đánh dấu tất cả đã đọc
    </button>
  </div>
  <div class="offcanvas-body p-0" id="notifList">
    <div class="text-center text-muted py-5">
      <div class="spinner-border spinner-border-sm mb-2"></div><br>Đang tải...
    </div>
  </div>
</div>

<!-- ══ SHIPMENT DETAIL OFFCANVAS ═══════════════════════════════════════════ -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="shipmentOffcanvas" style="width:520px;max-width:100vw">
  <div class="offcanvas-header border-bottom py-3"
       style="background:linear-gradient(90deg,var(--brand-dark),var(--brand-mid));color:#fff">
    <h6 class="offcanvas-title fw-bold mb-0">
      <i class="bi bi-box-seam me-2"></i>Chi tiết lô hàng
    </h6>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0" id="shipmentOffcanvasBody">
    <div class="text-center py-5 text-muted">
      <div class="spinner-border spinner-border-sm me-2"></div> Đang tải...
    </div>
  </div>
</div>

<!-- ══ SCRIPTS ════════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ── Sidebar toggle ──────────────────────────────────────────────────────────
const SIDEBAR_KEY = 'ny_sidebar_collapsed';

function toggleSidebar() {
  if (window.innerWidth <= 768) {
    document.body.classList.toggle('sidebar-open');
  } else {
    const collapsed = document.body.classList.toggle('sidebar-collapsed');
    localStorage.setItem(SIDEBAR_KEY, collapsed ? '1' : '0');
  }
}

function closeSidebar() {
  document.body.classList.remove('sidebar-open');
}

// Restore desktop state
(function () {
  if (window.innerWidth > 768 && localStorage.getItem(SIDEBAR_KEY) === '1') {
    document.body.classList.add('sidebar-collapsed');
  }
})();

// ── Notifications ───────────────────────────────────────────────────────────
document.getElementById('notifOffcanvas')?.addEventListener('show.bs.offcanvas', function () {
  loadNotifications();
});

function loadNotifications() {
  fetch('<?= BASE_URL ?>/?page=notifications.read&action=get')
    .then(r => r.json())
    .then(data => {
      const list = document.getElementById('notifList');
      if (!data.data || data.data.length === 0) {
        list.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-bell-slash fs-2 d-block mb-2"></i>Không có thông báo</div>';
        return;
      }
      list.innerHTML = data.data.map(n => `
        <div class="p-3 border-bottom d-flex gap-3 ${n.is_read == 0 ? 'bg-light' : ''}">
          <div class="flex-shrink-0 mt-1">
            <span style="width:8px;height:8px;border-radius:50%;background:${n.is_read == 0 ? '#3b82f6' : 'transparent'};display:inline-block;border:2px solid ${n.is_read == 0 ? '#3b82f6' : '#cbd5e1'}"></span>
          </div>
          <div class="flex-grow-1">
            <div class="small">${n.message}</div>
            <div class="text-muted mt-1" style="font-size:0.7rem"><i class="bi bi-clock me-1"></i>${n.created_at}</div>
          </div>
        </div>
      `).join('');
    })
    .catch(() => {
      document.getElementById('notifList').innerHTML = '<div class="text-center text-danger py-4">❌ Lỗi tải thông báo.</div>';
    });
}

document.getElementById('markAllReadBtn')?.addEventListener('click', function () {
  fetch('<?= BASE_URL ?>/?page=notifications.mark_all_read', { method: 'POST' })
    .then(() => {
      loadNotifications();
      document.querySelectorAll('.notif-badge').forEach(el => el.remove());
    }).catch(() => {});
});

// ── Shipment detail offcanvas ──────────────────────────────────────���────────
const _shipmentOffcanvas = new bootstrap.Offcanvas(document.getElementById('shipmentOffcanvas'));

function openShipmentDetail(id) {
  const body = document.getElementById('shipmentOffcanvasBody');
  body.innerHTML = '<div class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Đang tải...</div>';
  _shipmentOffcanvas.show();
  fetch('<?= BASE_URL ?>/?page=shipment.modal&id=' + id)
    .then(r => r.text())
    .then(html => {
      body.innerHTML = html;
      body.querySelectorAll('script').forEach(oldScript => {
        const s = document.createElement('script');
        Array.from(oldScript.attributes).forEach(a => s.setAttribute(a.name, a.value));
        s.textContent = oldScript.textContent;
        document.body.appendChild(s);
        oldScript.remove();
      });
    })
    .catch(() => { body.innerHTML = '<div class="p-4 text-danger">❌ Lỗi tải dữ liệu.</div>'; });
}

// Delegate row-click to open shipment detail
document.addEventListener('click', function (e) {
  const row = e.target.closest('[data-id]');
  if (!row) return;
  if (e.target.closest('a, button, input, select, textarea')) return;
  e.preventDefault();
  openShipmentDetail(row.dataset.id);
});
</script>
</body>
</html>
