<div class="text-center py-4">

  <!-- Success icon animation -->
  <div style="width:90px;height:90px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;font-size:3rem;animation:pop 0.4s ease">
    ✅
  </div>

  <h5 class="fw-bold text-success mt-3 mb-1">Giao hàng thành công!</h5>
  <p class="text-muted small mb-4">Đã xác nhận giao hàng và lưu chữ ký</p>
</div>

<!-- Thông tin lô -->
<div class="mobile-card card mb-3">
  <div class="card-body">
    <h6 class="fw-bold mb-3">📦 Thông tin lô hàng</h6>
    <div class="row g-2">
      <div class="col-6">
        <div style="font-size:0.7rem;color:#94a3b8">HAWB</div>
        <div class="fw-semibold small text-primary"><?= htmlspecialchars($shipment['hawb'] ?? '') ?></div>
      </div>
      <div class="col-6">
        <div style="font-size:0.7rem;color:#94a3b8">KHÁCH HÀNG</div>
        <div class="fw-semibold small"><?= htmlspecialchars($shipment['customer_code'] ?? '') ?></div>
      </div>
      <div class="col-6">
        <div style="font-size:0.7rem;color:#94a3b8">SỐ KIỆN</div>
        <div class="fw-semibold small"><?= $shipment['packages'] ?? 0 ?> kiện</div>
      </div>
      <div class="col-6">
        <div style="font-size:0.7rem;color:#94a3b8">TRỌNG LƯỢNG</div>
        <div class="fw-semibold small"><?= number_format($shipment['weight'] ?? 0, 1) ?> kg</div>
      </div>
    </div>
  </div>
</div>

<!-- Chữ ký -->
<?php if (!empty($shipment['signature_path'])): ?>
<div class="mobile-card card mb-3">
  <div class="card-body">
    <h6 class="fw-bold mb-3">✍️ Chữ ký người nhận</h6>

    <div class="p-3 rounded-2 mb-3 text-center"
         style="background:#f8fafc;border:1px solid #e2e8f0">
      <img src="<?= BASE_URL ?>/<?= $shipment['signature_path'] ?>"
           style="max-height:120px;max-width:100%;object-fit:contain">
    </div>

    <div class="d-flex justify-content-between text-muted small">
      <div>
        <span style="font-size:0.72rem">👤 Người ký:</span>
        <strong class="ms-1 text-dark"><?= htmlspecialchars($shipment['signed_by_name'] ?? '') ?></strong>
      </div>
      <div style="font-size:0.72rem">
        🕐 <?= $shipment['signed_at'] ? date('d/m/Y H:i', strtotime($shipment['signed_at'])) : '' ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Actions -->
<div class="d-grid gap-2">
  <a href="<?= BASE_URL ?>/?page=driver.trip_detail&id=<?= (int)($_GET['trip_id'] ?? 0) ?>"
     class="btn btn-mobile-primary btn-primary">
    ← Về danh sách chuyến
  </a>
  <a href="<?= BASE_URL ?>/?page=driver.dashboard"
     class="btn btn-outline-secondary">
    🏠 Về trang chủ
  </a>
</div>

<style>
@keyframes pop {
  0%   { transform: scale(0.5); opacity: 0; }
  70%  { transform: scale(1.1); }
  100% { transform: scale(1);   opacity: 1; }
}
</style>