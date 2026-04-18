<?php
// Status badge helper
function statusBadge($status) {
    $map = [
        'pending_customs'  => ['Chờ tờ khai',  'danger'],
        'cleared'          => ['Đã thông quan', 'warning'],
        'waiting_pickup'   => ['Chờ lấy hàng', 'orange'],
        'in_transit'       => ['Đang giao',     'primary'],
        'delivered'        => ['Đã giao',       'success'],
        'kt_reviewing'     => ['KT duyệt',      'info'],
        'pending_approval' => ['Chờ KH duyệt',  'purple'],
        'rejected'         => ['KH từ chối',    'dark'],
        'debt'             => ['Công nợ',        'secondary'],
        'invoiced'         => ['Đã xuất HĐ',    'success'],
    ];
    $s   = $map[$status] ?? [$status, 'secondary'];
    $clr = $s[1];
    // Map custom colors
    $styleMap = [
        'orange' => 'background:#ffedd5;color:#c2410c',
        'purple' => 'background:#f3e8ff;color:#7e22ce',
    ];
    if (isset($styleMap[$clr])) {
        return "<span class='badge' style='{$styleMap[$clr]}'>{$s[0]}</span>";
    }
    return "<span class='badge bg-{$clr}'>{$s[0]}</span>";
}
?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card stat-card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <p class="text-muted small mb-1">Nhập hôm nay</p>
            <h3 class="fw-bold mb-0 text-primary"><?= $stats['today'] ?></h3>
          </div>
          <span style="font-size:2rem">📦</span>
        </div>
        <a href="<?= BASE_URL ?>/?page=cs.upload" class="btn btn-primary btn-sm mt-3 w-100">
          + Upload lô mới
        </a>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card h-100">
      <div class="card-body">
        <p class="text-muted small mb-1">Chờ tờ khai</p>
        <h3 class="fw-bold mb-0 text-danger"><?= $stats['pending_customs'] ?></h3>
        <a href="<?= BASE_URL ?>/?page=cs.customs_upload" class="btn btn-outline-danger btn-sm mt-3 w-100">
          Upload tờ khai
        </a>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card h-100">
      <div class="card-body">
        <p class="text-muted small mb-1">Đã thông quan</p>
        <h3 class="fw-bold mb-0 text-warning"><?= $stats['cleared'] ?></h3>
        <a href="<?= BASE_URL ?>/?page=cs.list&status=cleared" class="btn btn-outline-warning btn-sm mt-3 w-100">
          Xem danh sách
        </a>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card h-100">
      <div class="card-body">
        <p class="text-muted small mb-1">Chờ lấy hàng</p>
        <h3 class="fw-bold mb-0" style="color:#c2410c"><?= $stats['waiting_pickup'] ?></h3>
        <a href="<?= BASE_URL ?>/?page=cs.list&status=waiting_pickup" class="btn btn-sm mt-3 w-100"
           style="background:#ffedd5;color:#c2410c">
          Xem danh sách
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Lô hàng hôm nay -->
<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-header bg-white d-flex justify-content-between align-items-center py-3"
       style="border-radius:12px 12px 0 0;border-bottom:1px solid #f1f5f9">
    <h6 class="mb-0 fw-bold">📋 Lô hàng nhập hôm nay</h6>
    <a href="<?= BASE_URL ?>/?page=cs.list" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
  </div>
  <div class="card-body p-0">
    <?php if (empty($todayShipments)): ?>
    <div class="text-center text-muted py-5">
      <div style="font-size:3rem">📭</div>
      <p class="mt-2">Chưa có lô hàng nào hôm nay</p>
      <a href="<?= BASE_URL ?>/?page=cs.upload" class="btn btn-primary">+ Upload lô hàng</a>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead style="background:#f8fafc;font-size:0.8rem;color:#64748b">
          <tr>
            <th class="ps-4">HAWB</th>
            <th>Khách hàng</th>
            <th>Flight</th>
            <th>Kiện/KG</th>
            <th>Trạng thái</th>
            <th>Active date</th>
          </tr>
        </thead>
        <tbody style="font-size:0.875rem">
          <?php foreach ($todayShipments as $s): ?>
          <tr data-id="<?= $s['id'] ?>" style="cursor:pointer">
            <td class="ps-4">
              <span class="fw-semibold text-primary"><?= htmlspecialchars($s['hawb']) ?></span>
            </td>
            <td>
              <span class="badge bg-light text-dark"><?= htmlspecialchars($s['customer_code']) ?></span>
              <small class="text-muted d-block"><?= htmlspecialchars($s['company_name'] ?? '') ?></small>
            </td>
            <td><?= htmlspecialchars($s['flight_no'] ?? '-') ?></td>
            <td><?= $s['packages'] ?> / <?= number_format($s['weight'], 1) ?> kg</td>
            <td><?= statusBadge($s['status']) ?></td>
            <td><?= date('d/m/Y', strtotime($s['active_date'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>