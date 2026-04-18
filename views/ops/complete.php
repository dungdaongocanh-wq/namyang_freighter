<?php
$statusLabel = [
    'pending_customs'  => ['Chờ tờ khai',    '#fee2e2', '#b91c1c'],
    'cleared'          => ['Đã thông quan',   '#fef9c3', '#92400e'],
    'waiting_pickup'   => ['Chờ lấy hàng',   '#ffedd5', '#c2410c'],
    'in_transit'       => ['Đang giao',       '#dbeafe', '#1d4ed8'],
    'delivered'        => ['Đã giao',         '#dcfce7', '#15803d'],
    'kt_reviewing'     => ['KT đang duyệt',   '#e0f2fe', '#0369a1'],
    'pending_approval' => ['Chờ KH duyệt',    '#f3e8ff', '#7e22ce'],
    'rejected'         => ['KH từ chối',      '#f1f5f9', '#334155'],
];
[$sl, $sbg, $sclr] = $statusLabel[$shipment['status']] ?? [$shipment['status'], '#f1f5f9', '#334155'];
?>

<!-- Tóm tắt lô hàng -->
<div class="mobile-card card mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start mb-2">
      <div>
        <div class="fw-bold text-primary fs-6"><?= htmlspecialchars($shipment['hawb']) ?></div>
        <div style="font-size:0.78rem;color:#64748b">
          <?= htmlspecialchars($shipment['company_name'] ?? $shipment['customer_code'] ?? '') ?>
        </div>
      </div>
      <span class="badge px-2 py-1" style="background:<?= $sbg ?>;color:<?= $sclr ?>;font-size:0.72rem">
        <?= $sl ?>
      </span>
    </div>

    <div class="row g-2 small mt-2">
      <div class="col-6">
        <div style="font-size:0.7rem;color:#94a3b8">SỐ KIỆN</div>
        <div class="fw-semibold"><?= $shipment['packages'] ?> kiện</div>
      </div>
      <div class="col-6">
        <div style="font-size:0.7rem;color:#94a3b8">TRỌNG LƯỢNG</div>
        <div class="fw-semibold"><?= number_format($shipment['weight'], 1) ?> kg</div>
      </div>
      <div class="col-6">
        <div style="font-size:0.7rem;color:#94a3b8">ETA</div>
        <div class="fw-semibold">
          <?= $shipment['eta'] ? date('d/m/Y', strtotime($shipment['eta'])) : '—' ?>
        </div>
      </div>
      <div class="col-6">
        <div style="font-size:0.7rem;color:#94a3b8">ACTIVE DATE</div>
        <div class="fw-semibold"><?= date('d/m/Y', strtotime($shipment['active_date'])) ?></div>
      </div>
    </div>

    <?php if ($shipment['remark']): ?>
    <div class="mt-2 p-2 rounded-2 small" style="background:#fffbeb">
      📝 <?= htmlspecialchars($shipment['remark']) ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Ảnh đã chụp -->
<?php if (!empty($photos)): ?>
<div class="mobile-card card mb-3">
  <div class="card-body">
    <h6 class="fw-bold mb-3">📷 Ảnh lô hàng (<?= count($photos) ?>)</h6>
    <div class="d-flex flex-wrap gap-2">
      <?php foreach ($photos as $ph): ?>
      <a href="<?= BASE_URL ?>/<?= $ph['photo_path'] ?>" target="_blank">
        <img src="<?= BASE_URL ?>/<?= $ph['photo_path'] ?>"
             style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0">
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php else: ?>
<div class="mobile-card card mb-3">
  <div class="card-body text-center py-3 text-muted small">
    📷 Chưa có ảnh nào
  </div>
</div>
<?php endif; ?>

<!-- Gán vào chuyến -->
<?php if (!empty($availableTrips)): ?>
<div class="mobile-card card mb-3">
  <div class="card-body">
    <h6 class="fw-bold mb-3">🚚 Gán vào chuyến có sẵn</h6>
    <form method="POST" action="<?= BASE_URL ?>/?page=ops.complete">
      <input type="hidden" name="action"      value="assign_trip">
      <input type="hidden" name="shipment_id" value="<?= $shipment['id'] ?>">
      <select name="trip_id" class="form-select mb-3" required>
        <option value="">— Chọn chuyến —</option>
        <?php foreach ($availableTrips as $t): ?>
        <option value="<?= $t['id'] ?>">
          <?= htmlspecialchars($t['trip_code'] ?? 'Chuyến #' . $t['id']) ?> —
          <?= htmlspecialchars($t['driver_name'] ?? '?') ?> (<?= $t['item_count'] ?> lô)
        </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-outline-primary btn-mobile-primary w-100">
        🔗 Gán vào chuyến
      </button>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Nút xác nhận hoàn thành -->
<div class="mobile-card card mb-3">
  <div class="card-body">
    <h6 class="fw-bold mb-2 text-success">✅ Xác nhận hoàn thành lô hàng</h6>
    <p class="text-muted small mb-3">
      Xác nhận đã kiểm tra xong lô hàng và sẵn sàng giao cho lái xe.
    </p>
    <form method="POST"
          action="<?= BASE_URL ?>/?page=ops.complete"
          onsubmit="return confirm('Xác nhận hoàn thành lô <?= htmlspecialchars($shipment['hawb']) ?>?')">
      <input type="hidden" name="action"      value="confirm_complete">
      <input type="hidden" name="shipment_id" value="<?= $shipment['id'] ?>">
      <button type="submit" class="btn btn-success btn-mobile-lg w-100">
        ✅ Xác nhận hoàn thành → Giao lái xe
      </button>
    </form>
  </div>
</div>

<!-- Nút tạo chuyến mới -->
<div class="d-flex gap-2 mb-3">
  <a href="<?= BASE_URL ?>/?page=ops.create_trip" class="btn btn-outline-primary flex-grow-1">
    ➕ Tạo chuyến mới
  </a>
  <a href="<?= BASE_URL ?>/?page=ops.dashboard" class="btn btn-outline-secondary flex-grow-1">
    ← Quay lại
  </a>
</div>
