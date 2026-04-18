<?php
$role        = $_SESSION['role'] ?? '';
$currentPage = $_GET['page'] ?? '';

$menus = [
  'cs' => [
    ['label' => 'CS - CHỨNG TỪ', 'type' => 'header'],
    ['cs.dashboard',         '📊', 'Dashboard'],
    ['cs.upload',            '📤', 'Upload lô hàng'],
    ['cs.list',              '📋', 'Danh sách lô'],
    ['shared.delivery_board','📬', 'Theo dõi giao hàng'],
    ['cs.customs_upload',    '📂', 'Upload tờ khai'],
  ],
  'ops' => [
    ['label' => 'OPS - VẬN HÀNH', 'type' => 'header'],
    ['ops.dashboard',        '📊', 'Dashboard'],
    ['ops.shipment_list',    '📥', 'Tải tờ khai'],
    ['ops.create_trip',      '🖨️', 'In Biên Bản GH'],
    ['ops.costs',            '💰', 'Chi phí'],
    ['shared.delivery_board','📬', 'Theo dõi giao hàng'],
  ],
  'accounting' => [
    ['label' => 'KẾ TOÁN', 'type' => 'header'],
    ['accounting.dashboard', '📊', 'Dashboard'],
    ['shared.delivery_board','📬', 'Theo dõi giao hàng'],
    ['accounting.review',    '📋', 'Xét duyệt chi phí'],
    ['accounting.rejected',  '❌', 'KH từ chối'],
    ['accounting.debt',      '💼', 'Công nợ'],
    ['accounting.invoice',   '🧾', 'Hoá đơn'],
  ],
  'customer' => [
    ['label' => 'KHÁCH HÀNG', 'type' => 'header'],
    ['customer.dashboard',        '📊', 'Dashboard'],
    ['customer.shipment_list',    '📦', 'Lô hàng'],
    ['customer.pending_approval', '⚠️',  'Chờ duyệt'],
    ['customer.history',          '📋', 'Lịch sử'],
    ['customer.debt',             '💰', 'Công nợ'],
  ],
  'driver' => [
    ['label' => 'TÀI XẾ', 'type' => 'header'],
    ['driver.dashboard', '🚚', 'Chuyến của tôi'],
  ],
  'admin' => [
    ['label' => 'CS - CHỨNG TỪ', 'type' => 'header'],
    ['cs.dashboard',              '📊', 'Dashboard'],
    ['cs.upload',                 '📤', 'Upload lô hàng'],
    ['cs.list',                   '📋', 'Danh sách lô'],
    ['cs.customs_upload',         '📂', 'Upload tờ khai'],

    ['label' => 'OPS - VẬN HÀNH', 'type' => 'header'],
    ['ops.dashboard',             '📊', 'Dashboard'],
    ['ops.shipment_list',         '📥', 'Tải tờ khai'],
    ['ops.create_trip',           '🖨️', 'In Biên Bản GH'],
    ['shared.delivery_board',     '📬', 'Theo dõi giao hàng'],

    ['label' => 'KẾ TOÁN', 'type' => 'header'],
    ['accounting.dashboard',      '📊', 'Dashboard'],
    ['accounting.review',         '📋', 'Xét duyệt chi phí'],
    ['accounting.rejected',       '❌', 'KH từ chối'],
    ['accounting.debt',           '💼', 'Công nợ'],
    ['accounting.invoice',        '🧾', 'Hoá đơn'],

    ['label' => 'KHÁCH HÀNG', 'type' => 'header'],
    ['customer.dashboard',        '📊', 'Dashboard'],
    ['customer.shipment_list',    '📦', 'Danh sách lô'],
    ['customer.pending_approval', '⚠️',  'Chờ duyệt'],

    ['label' => 'TÀI XẾ', 'type' => 'header'],
    ['driver.dashboard',          '🚚', 'Chuyến giao hàng'],

    ['label' => 'QUẢN TRỊ', 'type' => 'header'],
    ['admin.dashboard',           '📊', 'Dashboard'],
    ['admin.users',               '👥', 'Quản lý Users'],
    ['admin.customers',           '🏢', 'Khách hàng'],
    ['admin.quotation',           '📋', 'Báo giá'],
    ['admin.settings',            '⚙️',  'Cài đặt'],
  ],
];

$navItems = $menus[$role] ?? [];

// Đếm notification chưa đọc
$unreadNotif = 0;
try {
    $nStmt = getDB()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $nStmt->execute([$_SESSION['user_id'] ?? 0]);
    $unreadNotif = (int)$nStmt->fetchColumn();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($viewTitle ?? APP_NAME) ?> — <?= APP_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root {
      --sidebar-w: 240px;
      --topbar-h: 56px;
      --primary:       #1e3a5f;
      --primary-light: #2d6a9f;
    }

    * { box-sizing: border-box; }
    body {
      background: #f4f6fb;
      font-family: 'Segoe UI', sans-serif;
      font-size: 0.9rem;
      margin: 0;
    }

    /* ── Topbar ── */
    #topbar {
      position: fixed; top: 0; left: 0; right: 0;
      height: var(--topbar-h);
      background: linear-gradient(90deg, var(--primary), var(--primary-light));
      color: #fff;
      display: flex; align-items: center;
      padding: 0 1rem;
      z-index: 1000;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    #topbar .brand {
      font-weight: 700;
      font-size: 1.05rem;
      letter-spacing: 0.4px;
      display: flex; align-items: center; gap: 8px;
    }
    #topbar .brand-sub {
      font-size: 0.7rem;
      opacity: 0.7;
      font-weight: 400;
    }
    #topbar .topbar-right {
      margin-left: auto;
      display: flex; align-items: center; gap: 12px;
    }
    #topbar .role-badge {
      background: rgba(255,255,255,0.2);
      border-radius: 20px;
      padding: 3px 10px;
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.5px;
    }
    .notif-wrap {
      position: relative; cursor: pointer;
    }
    .notif-wrap .notif-icon { font-size: 1.2rem; }
    .notif-badge {
      position: absolute; top: -4px; right: -6px;
      background: #ef4444; color: #fff;
      border-radius: 50%; width: 17px; height: 17px;
      font-size: 0.62rem;
      display: flex; align-items: center; justify-content: center;
      font-weight: 700;
    }
    .btn-logout {
      background: rgba(255,255,255,0.15);
      border: 1px solid rgba(255,255,255,0.3);
      color: #fff; border-radius: 8px;
      padding: 4px 14px; font-size: 0.8rem;
      cursor: pointer; transition: background 0.2s;
      text-decoration: none;
    }
    .btn-logout:hover { background: rgba(255,255,255,0.25); color: #fff; }

    /* ── Sidebar ── */
    #sidebar {
      position: fixed;
      top: var(--topbar-h); left: 0; bottom: 0;
      width: var(--sidebar-w);
      background: var(--primary);
      overflow-y: auto;
      overflow-x: hidden;
      z-index: 999;
      transition: transform 0.3s;
      scrollbar-width: thin;
      scrollbar-color: rgba(255,255,255,0.1) transparent;
    }
    #sidebar::-webkit-scrollbar { width: 4px; }
    #sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }

    .sidebar-header {
      padding: 12px 16px 4px;
      color: rgba(255,255,255,0.4);
      font-size: 0.68rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-top: 6px;
    }

    .nav-link-item {
      display: flex; align-items: center; gap: 10px;
      color: rgba(255,255,255,0.72);
      padding: 9px 16px;
      border-radius: 8px;
      margin: 1px 8px;
      font-size: 0.855rem;
      text-decoration: none;
      transition: all 0.18s;
      white-space: nowrap;
    }
    .nav-link-item:hover {
      background: rgba(255,255,255,0.12);
      color: #fff;
    }
    .nav-link-item.active {
      background: rgba(255,255,255,0.18);
      color: #fff;
      font-weight: 600;
      border-left: 3px solid #7dd3fc;
      padding-left: 13px;
    }
    .nav-icon {
      font-size: 1rem;
      width: 20px;
      text-align: center;
      flex-shrink: 0;
    }

    .sidebar-footer {
      padding: 10px 16px;
      border-top: 1px solid rgba(255,255,255,0.1);
      color: rgba(255,255,255,0.4);
      font-size: 0.7rem;
      text-align: center;
    }

    /* ── Main content ── */
    #main-content {
      margin-left: var(--sidebar-w);
      margin-top: var(--topbar-h);
      padding: 1.5rem;
      min-height: calc(100vh - var(--topbar-h));
    }

    /* ── Page header ── */
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.4rem;
      padding-bottom: 0.9rem;
      border-bottom: 1px solid #e2e8f0;
    }
    .page-header h4 {
      font-weight: 700;
      color: var(--primary);
      margin: 0;
      font-size: 1.15rem;
    }

    /* ── Stat cards ── */
    .stat-card {
      border: none !important;
      border-radius: 12px !important;
      box-shadow: 0 2px 12px rgba(0,0,0,0.07) !important;
      transition: transform 0.18s, box-shadow 0.18s;
    }
    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0,0,0,0.12) !important;
    }

    /* ── Responsive mobile ── */
    @media (max-width: 768px) {
      #sidebar { transform: translateX(-100%); }
      #sidebar.show { transform: translateX(0); box-shadow: 4px 0 20px rgba(0,0,0,0.3); }
      #main-content { margin-left: 0; padding: 1rem; }
      .sidebar-toggle { display: flex !important; }
      #topbar .brand-sub { display: none; }
    }

    /* ── Print ── */
    @media print {
      #topbar, #sidebar, .no-print { display: none !important; }
      #main-content { margin: 0 !important; padding: 0 !important; }
    }
  </style>
</head>
<body>

<!-- ═══ TOPBAR ═══ -->
<div id="topbar">
  <!-- Mobile toggle -->
  <button class="btn-logout sidebar-toggle d-none me-2"
          style="font-size:1.3rem;padding:0 8px;border:none"
          onclick="toggleSidebar()">☰</button>

  <!-- Brand -->
  <div class="brand">
    <span>🚢</span>
    <div>
      <div><?= APP_NAME ?></div>
      <div class="brand-sub">Quản lý lô hàng</div>
    </div>
  </div>

  <!-- Right side -->
  <div class="topbar-right">
    <!-- Notification -->
    <div class="notif-wrap"
         onclick="location.href='<?= BASE_URL ?>/?page=notifications'"
         title="Thông báo">
      <span class="notif-icon">🔔</span>
      <?php if ($unreadNotif > 0): ?>
      <div class="notif-badge"><?= $unreadNotif > 9 ? '9+' : $unreadNotif ?></div>
      <?php endif; ?>
    </div>

    <!-- Role badge -->
    <span class="role-badge"><?= strtoupper($role) ?></span>

    <!-- Username -->
    <span class="small d-none d-md-inline" style="opacity:0.9">
      <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? '') ?>
    </span>

    <!-- Logout -->
    <a href="<?= BASE_URL ?>/?page=logout"
       class="btn-logout"
       onclick="return confirm('Đăng xuất khỏi hệ thống?')">
      Đăng xuất
    </a>
  </div>
</div>

<!-- ═══ SIDEBAR ═══ -->
<nav id="sidebar">
  <div style="padding-top:8px;padding-bottom:60px">
    <?php foreach ($navItems as $item):
      // Render header
      if (isset($item['type']) && $item['type'] === 'header'): ?>
      <div class="sidebar-header"><?= htmlspecialchars($item['label']) ?></div>
      <?php continue; endif;

      [$page, $icon, $label] = $item;
      $prefix = explode('.', $page)[0];
      $active = ($currentPage === $page)
             || (strpos($currentPage, $prefix . '.') === 0 && $prefix !== 'admin');
    ?>
    <a href="<?= BASE_URL ?>/?page=<?= $page ?>"
       class="nav-link-item <?= $active ? 'active' : '' ?>">
      <span class="nav-icon"><?= $icon ?></span>
      <?= htmlspecialchars($label) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="sidebar-footer" style="position:sticky;bottom:0;background:var(--primary)">
    <?= APP_NAME ?> v1.0
  </div>
</nav>

<!-- ═══ MAIN CONTENT ═══ -->
<main id="main-content">

  <!-- Page header -->
  <div class="page-header">
    <h4><?= htmlspecialchars($viewTitle ?? '') ?></h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item">
          <a href="<?= BASE_URL ?>/?page=<?= $role ?>.dashboard"
             class="text-decoration-none text-muted">🏠 Home</a>
        </li>
        <li class="breadcrumb-item active text-muted">
          <?= htmlspecialchars($viewTitle ?? '') ?>
        </li>
      </ol>
    </nav>
  </div>

  <!-- Alert messages (global) -->
  <?php if (isset($_GET['msg'])): ?>
  <?php
  $msgMap = [
    'saved'       => ['success', '✅ Đã lưu thành công!'],
    'deleted'     => ['warning', '🗑️ Đã xoá!'],
    'pushed'      => ['success', '✅ Đã đẩy sang khách hàng!'],
    'approved'    => ['success', '✅ Đã duyệt thành công!'],
    'rejected'    => ['info',    'ℹ️ Đã ghi nhận từ chối!'],
    'resubmitted' => ['success', '✅ Đã gửi lại cho khách hàng!'],
    'imported'    => ['success', '✅ Import thành công!'],
  ];
  [$alertType, $alertMsg] = $msgMap[$_GET['msg']] ?? ['info', htmlspecialchars($_GET['msg'])];
  ?>
  <div class="alert alert-<?= $alertType ?> alert-dismissible mb-4"
       style="border-radius:10px">
    <?= $alertMsg ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <?php if (isset($_GET['err'])): ?>
  <?php
  $errMap = [
    'not_found'  => 'Không tìm thấy dữ liệu!',
    'no_cost'    => 'Chưa có chi phí nào!',
    'no_reason'  => 'Vui lòng nhập lý do!',
    'invalid'    => 'Yêu cầu không hợp lệ!',
    'no_pwd'     => 'Vui lòng nhập mật khẩu!',
    'unauthorized'=> 'Bạn không có quyền thực hiện!',
  ];
  $errMsg = $errMap[$_GET['err']] ?? htmlspecialchars($_GET['err']);
  ?>
  <div class="alert alert-danger alert-dismissible mb-4" style="border-radius:10px">
    ❌ <?= $errMsg ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <!-- View content -->
  <?php include $viewFile; ?>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- ═══ SHIPMENT OFFCANVAS ═══ -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="shipmentOffcanvas"
     style="width:min(520px,100vw)">
  <div class="offcanvas-header border-bottom py-3"
       style="background:linear-gradient(90deg,#1e3a5f,#2d6a9f);color:#fff">
    <h6 class="offcanvas-title fw-bold mb-0">📦 Chi tiết lô hàng</h6>
    <button type="button" class="btn-close btn-close-white"
            data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0" id="shipmentOffcanvasBody">
    <div class="text-center py-5 text-muted" id="ocLoader">
      <div class="spinner-border spinner-border-sm me-2"></div> Đang tải...
    </div>
  </div>
</div>

<script>
// Toggle sidebar mobile
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('show');
}

// Đóng sidebar khi click ngoài (mobile)
document.addEventListener('click', function(e) {
  if (window.innerWidth > 768) return;
  const sidebar = document.getElementById('sidebar');
  const toggle  = document.querySelector('.sidebar-toggle');
  if (!sidebar || !toggle) return;
  if (sidebar.classList.contains('show')
      && !sidebar.contains(e.target)
      && !toggle.contains(e.target)) {
    sidebar.classList.remove('show');
  }
});

// Active link highlight (chính xác hơn)
document.querySelectorAll('.nav-link-item').forEach(link => {
  const href  = link.getAttribute('href') || '';
  const page  = new URLSearchParams(href.split('?')[1] || '').get('page') || '';
  const cur   = '<?= $currentPage ?>';
  if (page && cur === page) {
    link.classList.add('active');
  }
});

// ── Shipment Offcanvas ───────────────────────────────────────
const _shipmentOffcanvas = new bootstrap.Offcanvas(document.getElementById('shipmentOffcanvas'));

function openShipmentDetail(id) {
  const body = document.getElementById('shipmentOffcanvasBody');
  body.innerHTML = '<div class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Đang tải...</div>';
  _shipmentOffcanvas.show();
  fetch('<?= BASE_URL ?>/?page=shipment.modal&id=' + id)
    .then(r => r.text())
    .then(html => { body.innerHTML = html; })
    .catch(() => { body.innerHTML = '<div class="p-4 text-danger">❌ Lỗi tải dữ liệu.</div>'; });
}

// Delegate click — row/card có data-id sẽ mở offcanvas
// Trừ khi click vào link/button bên trong
document.addEventListener('click', function(e) {
  const row = e.target.closest('[data-id]');
  if (!row) return;
  if (e.target.closest('a, button, input, select, textarea')) return;
  e.preventDefault();
  openShipmentDetail(row.dataset.id);
});
</script>
</body>
</html>