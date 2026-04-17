<?php
// Safe defaults
$stats = $stats ?? [];
$stats['total_shipments'] = $stats['total_shipments'] ?? 0;
$stats['total_users']     = $stats['total_users']     ?? 0;
$stats['total_customers'] = $stats['total_customers'] ?? 0;
$stats['total_debt']      = $stats['total_debt']      ?? 0;
$statusBreakdown          = $statusBreakdown          ?? [];
$importLogs               = $importLogs               ?? [];
?>

<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['total_shipments','📦','Tổng lô hàng','primary'],
    ['total_users',    '👥','Người dùng',  'info'],
    ['total_customers','🏢','Khách hàng',  'success'],
    ['total_debt',     '💰','Tổng công nợ','warning'],
  ];
  foreach ($cards as [$key,$icon,$label,$clr]): ?>
  <div class="col-6 col-md-3">
    <div class="card stat-card h-100" style="border-left:4px solid var(--bs-<?= $clr ?>)">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <p class="text-muted small mb-1"><?= $label ?></p>
            <h3 class="fw-bold text-<?= $clr ?> mb-0">
              <?= $key === 'total_debt'
                    ? number_format((float)$stats[$key]) . ' đ'
                    : number_format((int)$stats[$key]) ?>
            </h3>
          </div>
          <span style="font-size:2rem"><?= $icon ?></span>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Status breakdown -->
<div class="row g-4">
<div class="col-md-6">
  <div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
    <div class="card-header bg-white py-3 px-4" style="border-radius:12px 12px 0 0">
      <h6 class="mb-0 fw-bold">📊 Phân bổ trạng thái</h6>
    </div>
    <div class="card-body p-0">
      <?php
      $statusNames = [
        'pending_customs'  => ['Chờ tờ khai',   'danger'],
        'cleared'          => ['Đã thông quan',  'warning'],
        'waiting_pickup'   => ['Chờ lấy',        'warning'],
        'in_transit'       => ['Đang giao',       'primary'],
        'delivered'        => ['Đã giao',         'success'],
        'kt_reviewing'     => ['KT duyệt',        'info'],
        'pending_approval' => ['Chờ KH duyệt',    'secondary'],
        'rejected'         => ['KH từ chối',       'dark'],
        'debt'             => ['Công nợ',           'secondary'],
        'invoiced'         => ['Đã xuất HĐ',       'success'],
      ];
      $total = !empty($statusBreakdown) ? array_sum($statusBreakdown) : 0;
      foreach ($statusNames as $st => [$label, $clr]):
        $cnt = (int)($statusBreakdown[$st] ?? 0);
        $pct = $total > 0 ? round($cnt / $total * 100) : 0;
      ?>
      <div class="d-flex align-items-center px-4 py-2 border-bottom">
        <div style="width:140px;font-size:0.8rem"><?= $label ?></div>
        <div class="flex-grow-1 me-3">
          <div class="progress" style="height:6px;border-radius:3px">
            <div class="progress-bar bg-<?= $clr ?>"
                 style="width:<?= $pct ?>%"></div>
          </div>
        </div>
        <div class="fw-semibold small" style="width:40px;text-align:right"><?= $cnt ?></div>
      </div>
      <?php endforeach; ?>
      <?php if ($total === 0): ?>
      <div class="text-center text-muted py-4 small">Chưa có dữ liệu</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Import logs -->
<div class="col-md-6">
  <div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
    <div class="card-header bg-white py-3 px-4" style="border-radius:12px 12px 0 0">
      <h6 class="mb-0 fw-bold">📥 Import gần đây</h6>
    </div>
    <div class="card-body p-0">
      <?php if (empty($importLogs)): ?>
      <div class="text-center text-muted py-4 small">Chưa có import nào</div>
      <?php else: ?>
      <?php foreach ($importLogs as $log): ?>
      <div class="px-4 py-2 border-bottom" style="font-size:0.82rem">
        <div class="d-flex justify-content-between">
          <span class="fw-semibold"><?= htmlspecialchars(basename($log['filename'] ?? '')) ?></span>
          <span class="text-muted"><?= date('d/m H:i', strtotime($log['created_at'])) ?></span>
        </div>
        <div class="text-muted small">
          By: <?= htmlspecialchars($log['full_name'] ?? '') ?> ·
          ✅ <?= (int)$log['inserted'] ?> mới ·
          🔄 <?= (int)$log['updated_rows'] ?> update ·
          ⏭ <?= (int)$log['skipped'] ?> bỏ
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
</div>