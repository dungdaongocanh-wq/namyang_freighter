<?php
$statusLabel = [
    'pending_customs'  => ['Chờ tờ khai',   'danger'],
    'cleared'          => ['Đã thông quan',  'warning'],
    'waiting_pickup'   => ['Chờ lấy hàng',  'orange'],
    'in_transit'       => ['Đang giao',      'primary'],
    'delivered'        => ['Đã giao',        'success'],
    'kt_reviewing'     => ['KT đang duyệt',  'info'],
    'pending_approval' => ['Chờ bạn duyệt',  'purple'],
    'rejected'         => ['Đã từ chối',     'dark'],
    'debt'             => ['Công nợ',         'secondary'],
    'invoiced'         => ['Đã xuất HĐ',     'success'],
];
?>

<!-- Greeting -->
<div class="mb-4">
  <h5 class="fw-bold mb-0">
    👋 Xin chào, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Khách hàng') ?>!
  </h5>
  <p class="text-muted small mb-0"><?= date('l, d/m/Y') ?></p>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card stat-card h-100" style="border-left:4px solid #2d6a9f">
      <div class="card-body">
        <p class="text-muted small mb-1">Tổng lô hàng</p>
        <h3 class="fw-bold text-primary mb-2"><?= $stats['total'] ?></h3>
        <a href="<?= BASE_URL ?>/?page=customer.shipment_list"
           class="btn btn-sm btn-outline-primary w-100">Xem tất cả</a>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card h-100" style="border-left:4px solid #1d4ed8">
      <div class="card-body">
        <p class="text-muted small mb-1">Đang vận chuyển</p>
        <h3 class="fw-bold text-primary mb-2"><?= $stats['in_transit'] ?></h3>
        <a href="<?= BASE_URL ?>/?page=customer.shipment_list&status=in_transit"
           class="btn btn-sm btn-outline-primary w-100">Theo dõi</a>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <?php $hasPending = $stats['pending_approval'] > 0; ?>
    <div class="card stat-card h-100"
         style="border-left:4px solid #7e22ce;<?= $hasPending?'animation:pulse 2s infinite':'' ?>">
      <div class="card-body">
        <p class="text-muted small mb-1">Chờ bạn duyệt</p>
        <h3 class="fw-bold mb-2" style="color:#7e22ce"><?= $stats['pending_approval'] ?></h3>
        <a href="<?= BASE_URL ?>/?page=customer.pending_approval"
           class="btn btn-sm w-100 <?= $hasPending?'btn-warning fw-semibold':'btn-outline-secondary' ?>">
          <?= $hasPending ? '⚠️ Duyệt ngay' : 'Xem' ?>
        </a>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card h-100" style="border-left:4px solid #15803d">
      <div class="card-body">
        <p class="text-muted small mb-1">Công nợ</p>
        <h3 class="fw-bold text-success mb-2"><?= $stats['debt'] ?> lô</h3>
        <a href="<?= BASE_URL ?>/?page=customer.debt"
           class="btn btn-sm btn-outline-success w-100">Chi tiết</a>
      </div>
    </div>
  </div>
</div>

<!-- Cần duyệt ngay -->
<?php if (!empty($urgentList)): ?>
<div class="card mb-4"
     style="border:none;border-radius:12px;box-shadow:0 2px 16px rgba(124,58,237,0.15);border-left:4px solid #7e22ce">
  <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center"
       style="background:#faf5ff;border-radius:12px 12px 0 0">
    <h6 class="mb-0 fw-bold" style="color:#7e22ce">
      ⚠️ Cần duyệt chi phí (<?= count($urgentList) ?>)
    </h6>
    <a href="<?= BASE_URL ?>/?page=customer.pending_approval"
       class="btn btn-sm btn-warning fw-semibold">Xem tất cả</a>
  </div>
  <div class="card-body p-0">
    <?php foreach ($urgentList as $s): ?>
    <div class="d-flex align-items-center justify-content-between px-4 py-3"
         style="border-bottom:1px solid #f3e8ff">
      <div>
        <span class="fw-semibold text-primary"><?= htmlspecialchars($s['hawb']) ?></span>
        <small class="text-muted ms-2"><?= date('d/m/Y', strtotime($s['active_date'])) ?></small>
      </div>
      <div class="d-flex align-items-center gap-3">
        <span class="fw-bold text-success"><?= number_format($s['total_cost']) ?> đ</span>
        <a href="<?= BASE_URL ?>/?page=customer.shipment_detail&id=<?= $s['id'] ?>"
           class="btn btn-sm btn-warning fw-semibold">Xem & Duyệt</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Lô gần đây -->
<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center"
       style="border-radius:12px 12px 0 0">
    <h6 class="mb-0 fw-bold">📦 Lô hàng gần đây</h6>
    <a href="<?= BASE_URL ?>/?page=customer.shipment_list" class="btn btn-sm btn-outline-primary">
      Xem tất cả
    </a>
  </div>
  <div class="card-body p-0">
    <?php if (empty($recentShipments)): ?>
    <div class="text-center text-muted py-5">
      <div style="font-size:3rem">📭</div>
      <p class="mt-2">Chưa có lô hàng nào!</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle" style="font-size:0.85rem">
        <thead style="background:#f8fafc;color:#64748b;font-size:0.78rem">
          <tr>
            <th class="ps-4">HAWB</th>
            <th>Flight / ETA</th>
            <th>Kiện / KG</th>
            <th>Active date</th>
            <th>Trạng thái</th>
            <th class="text-end pe-4">Chi phí</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentShipments as $s):
            [$lbl, $clr] = $statusLabel[$s['status']] ?? [$s['status'], 'secondary'];
          ?>
          <tr data-id="<?= $s['id'] ?>" style="cursor:pointer">
            <td class="ps-4 fw-semibold text-primary"><?= htmlspecialchars($s['hawb']) ?></td>
            <td>
              <div><?= htmlspecialchars($s['flight_no'] ?? '-') ?></div>
              <small class="text-muted">
                <?= $s['eta'] ? date('d/m/Y', strtotime($s['eta'])) : '' ?>
              </small>
            </td>
            <td><?= $s['packages'] ?> / <?= number_format($s['weight'],1) ?> kg</td>
            <td><?= date('d/m/Y', strtotime($s['active_date'])) ?></td>
            <td>
              <?php if ($clr === 'purple'): ?>
              <span class="badge" style="background:#f3e8ff;color:#7e22ce"><?= $lbl ?></span>
              <?php elseif ($clr === 'orange'): ?>
              <span class="badge" style="background:#ffedd5;color:#c2410c"><?= $lbl ?></span>
              <?php else: ?>
              <span class="badge bg-<?= $clr ?>"><?= $lbl ?></span>
              <?php endif; ?>
            </td>
            <td class="text-end pe-4">
              <?php if ($s['total_cost'] > 0): ?>
              <span class="fw-semibold text-success"><?= number_format($s['total_cost']) ?> đ</span>
              <?php else: ?>
              <span class="text-muted small">-</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<style>
@keyframes pulse {
  0%,100% { box-shadow: 0 0 0 0 rgba(124,58,237,0.3); }
  50%      { box-shadow: 0 0 0 8px rgba(124,58,237,0); }
}
</style>