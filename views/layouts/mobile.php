<?php
// Layout mobile - dùng cho OPS và Driver
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/?page=login');
    exit;
}
$role     = $_SESSION['role'];
$fullName = $_SESSION['full_name'];
$userId   = $_SESSION['user_id'];
$currentPage = $_GET['page'] ?? '';

// Unread notifications
$unreadCount = 0;
try {
    $db   = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $unreadCount = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

// Bottom nav config theo role
$bottomNavOps = [
    ['page' => 'ops.dashboard',        'icon' => 'house-fill',      'label' => 'Home'],
    ['page' => 'ops.download_customs', 'icon' => 'download',        'label' => 'Tờ khai'],
    ['page' => 'ops.trip',             'icon' => 'truck',           'label' => 'Chuyến'],
    ['page' => 'ops.costs',            'icon' => 'calculator-fill', 'label' => 'Chi phí'],
];
$bottomNavDriver = [
    ['page' => 'driver.dashboard', 'icon' => 'house-fill',  'label' => 'Home'],
    ['page' => 'driver.dashboard', 'icon' => 'truck',       'label' => 'Chuyến'],
];
$bottomNav = ($role === 'driver') ? $bottomNavDriver : $bottomNavOps;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title><?= $viewTitle ?? 'Nam Yang Freight' ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root { --bottom-nav-height: 65px; }
  body {
    background: #f0f4f8;
    font-family: 'Segoe UI', sans-serif;
    padding-bottom: var(--bottom-nav-height);
    -webkit-tap-highlight-color: transparent;
  }

  /* Top bar */
  #mobile-topbar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: #1e3a5f;
    color: #fff;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
  }
  #mobile-topbar .page-title {
    font-size: 1rem;
    font-weight: 600;
  }

  /* Bottom nav */
  #bottom-nav {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    height: var(--bottom-nav-height);
    background: #fff;
    border-top: 1px solid #e2e8f0;
    display: flex;
    z-index: 100;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
  }
  #bottom-nav a {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    text-decoration: none;
    font-size: 0.65rem;
    gap: 3px;
    min-height: 48px;
    transition: color 0.2s;
  }
  #bottom-nav a.active { color: #1e3a5f; }
  #bottom-nav a i { font-size: 1.4rem; }

  /* Content */
  #mobile-content { padding: 16px; }

  /* Buttons mobile */
  .btn-mobile-primary {
    min-height: 52px;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 14px;
  }
  .btn-mobile-lg {
    min-height: 60px;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 16px;
  }

  /* Cards mobile */
  .mobile-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    margin-bottom: 12px;
  }
  .mobile-card .card-body { padding: 16px; }

  /* Status badges */
  .status-pending_customs  { background:#fee2e2;color:#b91c1c; }
  .status-cleared          { background:#fef9c3;color:#92400e; }
  .status-waiting_pickup   { background:#ffedd5;color:#c2410c; }
  .status-in_transit       { background:#dbeafe;color:#1d4ed8; }
  .status-delivered        { background:#dcfce7;color:#15803d; }
  .status-pending_approval { background:#f3e8ff;color:#7e22ce; }
</style>
</head>
<body>

<!-- Top bar -->
<header id="mobile-topbar">
  <div class="d-flex align-items-center gap-2">
    <span style="font-size:1.2rem">🚢</span>
    <span class="page-title"><?= $viewTitle ?? 'Nam Yang Freight' ?></span>
  </div>
  <div class="d-flex align-items-center gap-3">
    <!-- Notification -->
    <a href="#" class="position-relative text-white" data-bs-toggle="offcanvas" data-bs-target="#notifOffcanvas">
      <i class="bi bi-bell fs-5"></i>
      <?php if ($unreadCount > 0): ?>
      <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.55rem">
        <?= $unreadCount ?>
      </span>
      <?php endif; ?>
    </a>
    <!-- Logout -->
    <a href="<?= BASE_URL ?>/?page=logout" class="text-white">
      <i class="bi bi-box-arrow-right fs-5"></i>
    </a>
  </div>
</header>

<!-- Main content -->
<main id="mobile-content">
  <?php if (isset($viewFile) && file_exists($viewFile)): ?>
    <?php include $viewFile; ?>
  <?php else: ?>
    <div class="alert alert-warning">⚠️ View không tồn tại.</div>
  <?php endif; ?>
</main>

<!-- Bottom navigation -->
<nav id="bottom-nav">
  <?php foreach ($bottomNav as $nav): ?>
  <a href="<?= BASE_URL ?>/?page=<?= $nav['page'] ?>"
     class="<?= $currentPage === $nav['page'] ? 'active' : '' ?>">
    <i class="bi bi-<?= $nav['icon'] ?>"></i>
    <span><?= $nav['label'] ?></span>
  </a>
  <?php endforeach; ?>
  <!-- Profile luôn cuối -->
  <a href="#" data-bs-toggle="offcanvas" data-bs-target="#profileOffcanvas"
     class="<?= str_starts_with($currentPage, 'profile') ? 'active' : '' ?>">
    <i class="bi bi-person-circle"></i>
    <span>Tôi</span>
  </a>
</nav>

<!-- Notification offcanvas -->
<div class="offcanvas offcanvas-end" id="notifOffcanvas" style="max-width:340px">
  <div class="offcanvas-header">
    <h6 class="offcanvas-title">🔔 Thông báo</h6>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0" id="notifListMobile">
    <div class="text-center text-muted py-4">Đang tải...</div>
  </div>
</div>

<!-- Profile offcanvas -->
<div class="offcanvas offcanvas-bottom rounded-top" id="profileOffcanvas" style="height:auto">
  <div class="offcanvas-body text-center py-4">
    <div style="width:60px;height:60px;background:#1e3a5f;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;font-weight:700;margin:0 auto 12px;">
      <?= strtoupper(substr($fullName, 0, 1)) ?>
    </div>
    <h5 class="mb-1"><?= htmlspecialchars($fullName) ?></h5>
    <span class="badge bg-primary mb-3"><?= ucfirst($role) ?></span>
    <div>
      <a href="<?= BASE_URL ?>/?page=logout" class="btn btn-outline-danger w-100">
        <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
      </a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('notifOffcanvas')?.addEventListener('show.bs.offcanvas', function() {
  fetch('<?= BASE_URL ?>/?page=notifications.read&action=get')
    .then(r => r.json())
    .then(data => {
      const list = document.getElementById('notifListMobile');
      if (!data.data || data.data.length === 0) {
        list.innerHTML = '<div class="text-center text-muted py-4">Không có thông báo</div>';
        return;
      }
      list.innerHTML = data.data.map(n => `
        <div class="p-3 border-bottom ${n.is_read==0?'bg-light':''}">
          <div class="small">${n.message}</div>
          <div class="text-muted" style="font-size:0.7rem">${n.created_at}</div>
        </div>
      `).join('');
    }).catch(() => {});
});
</script>
</body>
</html>