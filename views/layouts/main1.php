<?php
// Layout desktop - dùng cho CS, Kế toán, Admin, Khách hàng
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/?page=login');
    exit;
}
$role      = $_SESSION['role'];
$fullName  = $_SESSION['full_name'];
$userId    = $_SESSION['user_id'];

// Lấy số thông báo chưa đọc
$unreadCount = 0;
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $unreadCount = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

// Label hiển thị role
$roleLabels = [
    'admin'      => ['label' => 'Admin',    'color' => 'danger'],
    'cs'         => ['label' => 'CS',       'color' => 'primary'],
    'ops'        => ['label' => 'OPS',      'color' => 'warning'],
    'driver'     => ['label' => 'Lái xe',   'color' => 'info'],
    'accounting' => ['label' => 'Kế toán',  'color' => 'success'],
    'customer'   => ['label' => 'KH',       'color' => 'secondary'],
];
$roleInfo = $roleLabels[$role] ?? ['label' => $role, 'color' => 'dark'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $viewTitle ?? 'Nam Yang Freight' ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root {
    --sidebar-width: 250px;
    --sidebar-bg: #1e3a5f;
    --sidebar-text: #cbd5e1;
    --sidebar-active: #2d6a9f;
    --topbar-height: 60px;
  }
  body { background: #f0f4f8; font-family: 'Segoe UI', sans-serif; }

  /* Sidebar */
  #sidebar {
    width: var(--sidebar-width);
    min-height: 100vh;
    background: var(--sidebar-bg);
    position: fixed;
    top: 0; left: 0;
    z-index: 100;
    overflow-y: auto;
    transition: transform 0.3s ease;
  }
  #sidebar .sidebar-brand {
    padding: 20px 20px 15px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
  }
  #sidebar .sidebar-brand h5 {
    color: #fff;
    font-weight: 700;
    margin: 0;
    font-size: 1rem;
  }
  #sidebar .nav-label {
    color: rgba(255,255,255,0.4);
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 1px;
    padding: 15px 20px 5px;
    text-transform: uppercase;
  }
  #sidebar .nav-link {
    color: var(--sidebar-text);
    padding: 10px 20px;
    border-radius: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
    transition: all 0.2s;
    text-decoration: none;
  }
  #sidebar .nav-link:hover,
  #sidebar .nav-link.active {
    background: var(--sidebar-active);
    color: #fff;
    padding-left: 25px;
  }
  #sidebar .nav-link i { width: 20px; text-align: center; font-size: 1rem; }

  /* Topbar */
  #topbar {
    position: fixed;
    top: 0;
    left: var(--sidebar-width);
    right: 0;
    height: var(--topbar-height);
    background: #fff;
    box-shadow: 0 1px 4px rgba(0,0,0,0.1);
    z-index: 99;
    display: flex;
    align-items: center;
    padding: 0 24px;
    justify-content: space-between;
  }

  /* Main content */
  #main-content {
    margin-left: var(--sidebar-width);
    margin-top: var(--topbar-height);
    padding: 24px;
    min-height: calc(100vh - var(--topbar-height));
  }

  /* Notification dropdown */
  .notif-dropdown { width: 360px; max-height: 400px; overflow-y: auto; }
  .notif-item { border-left: 3px solid #2d6a9f; }
  .notif-item.unread { background: #f0f7ff; }

  /* Cards */
  .stat-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    transition: transform 0.2s;
  }
  .stat-card:hover { transform: translateY(-2px); }

  /* Responsive */
  @media (max-width: 768px) {
    #sidebar { transform: translateX(-100%); }
    #sidebar.show { transform: translateX(0); }
    #topbar { left: 0; }
    #main-content { margin-left: 0; }
  }
</style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<nav id="sidebar">
  <div class="sidebar-brand">
    <div class="d-flex align-items-center gap-2">
      <span style="font-size:1.5rem">🚢</span>
      <div>
        <h5>Nam Yang Freight</h5>
        <small style="color:rgba(255,255,255,0.5);font-size:0.7rem">Quản lý lô hàng</small>
      </div>
    </div>
  </div>

  <nav class="mt-2">
    <?php include __DIR__ . '/sidebar_menu.php'; ?>
  </nav>

  <!-- User info bottom -->
  <div style="position:absolute;bottom:0;width:100%;padding:15px 20px;border-top:1px solid rgba(255,255,255,0.1);">
    <div class="d-flex align-items-center gap-2">
      <div style="width:36px;height:36px;background:#2d6a9f;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;">
        <?= strtoupper(substr($fullName, 0, 1)) ?>
      </div>
      <div>
        <div style="color:#fff;font-size:0.85rem;font-weight:600;"><?= htmlspecialchars($fullName) ?></div>
        <span class="badge bg-<?= $roleInfo['color'] ?>" style="font-size:0.65rem;"><?= $roleInfo['label'] ?></span>
      </div>
    </div>
  </div>
</nav>

<!-- ===== TOPBAR ===== -->
<header id="topbar">
  <div class="d-flex align-items-center gap-3">
    <!-- Mobile menu toggle -->
    <button class="btn btn-sm btn-light d-md-none" onclick="toggleSidebar()">
      <i class="bi bi-list fs-5"></i>
    </button>
    <h6 class="mb-0 fw-semibold text-dark"><?= $viewTitle ?? 'Dashboard' ?></h6>
  </div>

  <div class="d-flex align-items-center gap-3">
    <!-- Notification Bell -->
    <div class="dropdown">
      <button class="btn btn-light btn-sm position-relative" data-bs-toggle="dropdown">
        <i class="bi bi-bell fs-5"></i>
        <?php if ($unreadCount > 0): ?>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;">
          <?= $unreadCount > 99 ? '99+' : $unreadCount ?>
        </span>
        <?php endif; ?>
      </button>
      <div class="dropdown-menu dropdown-menu-end notif-dropdown p-0" id="notifDropdown">
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
          <strong>🔔 Thông báo</strong>
          <?php if ($unreadCount > 0): ?>
          <a href="#" onclick="markAllRead()" class="text-primary small">Đọc tất cả</a>
          <?php endif; ?>
        </div>
        <div id="notifList">
          <div class="text-center text-muted py-4 small">Đang tải...</div>
        </div>
      </div>
    </div>

    <!-- User dropdown -->
    <div class="dropdown">
      <button class="btn btn-light btn-sm d-flex align-items-center gap-2" data-bs-toggle="dropdown">
        <div style="width:30px;height:30px;background:#1e3a5f;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.8rem;">
          <?= strtoupper(substr($fullName, 0, 1)) ?>
        </div>
        <span class="d-none d-md-inline text-dark small fw-semibold"><?= htmlspecialchars($fullName) ?></span>
        <i class="bi bi-chevron-down small"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><h6 class="dropdown-header"><?= htmlspecialchars($fullName) ?></h6></li>
        <li><span class="dropdown-item-text text-muted small">
          <span class="badge bg-<?= $roleInfo['color'] ?>"><?= $roleInfo['label'] ?></span>
        </span></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/?page=logout">
          <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
        </a></li>
      </ul>
    </div>
  </div>
</header>

<!-- ===== MAIN CONTENT ===== -->
<main id="main-content">
  <?php if (isset($viewFile) && file_exists($viewFile)): ?>
    <?php include $viewFile; ?>
  <?php else: ?>
    <div class="alert alert-warning">⚠️ View không tồn tại.</div>
  <?php endif; ?>
</main>

<!-- Mobile sidebar overlay -->
<div id="sidebarOverlay" onclick="toggleSidebar()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:99;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle sidebar mobile
function toggleSidebar() {
  const sidebar  = document.getElementById('sidebar');
  const overlay  = document.getElementById('sidebarOverlay');
  sidebar.classList.toggle('show');
  overlay.style.display = sidebar.classList.contains('show') ? 'block' : 'none';
}

// Load notifications
document.querySelector('[data-bs-toggle="dropdown"]')?.addEventListener('show.bs.dropdown', function() {
  loadNotifications();
});

function loadNotifications() {
  fetch('<?= BASE_URL ?>/?page=notifications.read&action=get')
    .then(r => r.json())
    .then(data => {
      const list = document.getElementById('notifList');
      if (!data.data || data.data.length === 0) {
        list.innerHTML = '<div class="text-center text-muted py-4 small">Không có thông báo mới</div>';
        return;
      }
      list.innerHTML = data.data.map(n => `
        <div class="p-3 border-bottom notif-item ${n.is_read == 0 ? 'unread' : ''}"
             onclick="markRead(${n.id}, this)" style="cursor:pointer">
          <div class="small">${n.message}</div>
          <div class="text-muted" style="font-size:0.7rem">${n.created_at}</div>
        </div>
      `).join('');
    }).catch(() => {});
}

function markRead(id, el) {
  fetch('<?= BASE_URL ?>/?page=notifications.read&action=mark&id=' + id, {method:'POST'})
    .then(() => { el.classList.remove('unread'); });
}

function markAllRead() {
  fetch('<?= BASE_URL ?>/?page=notifications.read&action=mark_all', {method:'POST'})
    .then(() => {
      document.querySelectorAll('.notif-item').forEach(el => el.classList.remove('unread'));
      const badge = document.querySelector('.badge.bg-danger');
      if (badge) badge.remove();
    });
}
</script>
</body>
</html>