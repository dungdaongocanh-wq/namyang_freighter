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
    'debt'             => ['Công nợ',          '#f0fdf4', '#15803d'],
    'invoiced'         => ['Đã xuất HĐ',      '#f0fdf4', '#15803d'],
    'cancelled'        => ['Đã huỷ',          '#fee2e2', '#991b1b'],
];

[$sl, $sbg, $sclr] = $statusLabel[$shipment['status']] ?? [$shipment['status'], '#f1f5f9', '#334155'];

// Trạng thái không được phép huỷ
$noCancel = ['in_transit', 'delivered', 'kt_reviewing', 'pending_approval', 'invoiced', 'cancelled'];
$canCancel = !in_array($shipment['status'], $noCancel);
?>

<!-- Thông tin lô hàng -->
<div class="card mb-4"
     style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07);border-left:5px solid <?= $sclr ?>">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <h5 class="fw-bold text-primary mb-1"><?= htmlspecialchars($shipment['hawb']) ?></h5>
        <small class="text-muted">Mã KH: <?= htmlspecialchars($shipment['customer_code'] ?? '—') ?></small>
      </div>
      <span class="badge px-3 py-2" style="background:<?= $sbg ?>;color:<?= $sclr ?>">
        <?= $sl ?>
      </span>
    </div>

    <div class="row g-2 small">
      <div class="col-sm-4">
        <div class="text-muted" style="font-size:0.72rem">KHÁCH HÀNG</div>
        <div class="fw-semibold"><?= htmlspecialchars($shipment['company_name'] ?? '—') ?></div>
      </div>
      <div class="col-sm-4">
        <div class="text-muted" style="font-size:0.72rem">NGÀY ACTIVE</div>
        <div class="fw-semibold"><?= date('d/m/Y', strtotime($shipment['active_date'])) ?></div>
      </div>
      <div class="col-sm-4">
        <div class="text-muted" style="font-size:0.72rem">ETA</div>
        <div class="fw-semibold">
          <?= $shipment['eta'] ? date('d/m/Y', strtotime($shipment['eta'])) : '—' ?>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="text-muted" style="font-size:0.72rem">SỐ KIỆN</div>
        <div class="fw-semibold"><?= $shipment['packages'] ?> kiện</div>
      </div>
      <div class="col-sm-4">
        <div class="text-muted" style="font-size:0.72rem">TRỌNG LƯỢNG</div>
        <div class="fw-semibold"><?= number_format($shipment['weight'], 1) ?> kg</div>
      </div>
      <div class="col-sm-4">
        <div class="text-muted" style="font-size:0.72rem">NGÀY TẠO</div>
        <div class="fw-semibold"><?= date('d/m/Y H:i', strtotime($shipment['created_at'])) ?></div>
      </div>
    </div>
  </div>
</div>

<?php if (!$canCancel): ?>
<!-- Không thể huỷ -->
<div class="alert alert-warning" style="border-radius:10px">
  ⚠️ <strong>Không thể huỷ lô này!</strong><br>
  <small>Lô hàng đang ở trạng thái <strong><?= $sl ?></strong> — không được phép huỷ.</small>
</div>

<a href="<?= BASE_URL ?>/?page=cs.list" class="btn btn-outline-secondary mt-2">
  ← Quay lại danh sách
</a>

<?php else: ?>
<!-- Form huỷ -->
<div class="card" style="border:none;border-radius:12px;box-shadow:0 4px 20px rgba(239,68,68,0.12);border:2px solid #fecaca">
  <div class="card-header py-3 px-4" style="background:#fff5f5;border-radius:12px 12px 0 0">
    <h6 class="mb-0 fw-bold text-danger">⚠️ Xác nhận huỷ lô hàng</h6>
  </div>
  <div class="card-body">
    <div class="alert alert-warning py-2 small mb-4" style="border-radius:8px">
      🔴 Lô <strong><?= htmlspecialchars($shipment['hawb']) ?></strong> sẽ bị huỷ và không thể khôi phục!
    </div>

    <form method="POST"
          action="<?= BASE_URL ?>/?page=cs.cancel"
          onsubmit="return confirm('Bạn chắc chắn muốn huỷ lô <?= htmlspecialchars($shipment['hawb']) ?>?')">
      <input type="hidden" name="shipment_id" value="<?= $shipment['id'] ?>">

      <div class="mb-4">
        <label class="form-label fw-semibold">
          Lý do huỷ <span class="text-danger">*</span>
        </label>
        <textarea name="reason"
                  class="form-control"
                  rows="4"
                  required
                  placeholder="Nhập lý do huỷ lô hàng (bắt buộc)..."></textarea>
        <div class="form-text">Lý do này sẽ được ghi vào lịch sử lô hàng.</div>
      </div>

      <div class="d-flex gap-3">
        <a href="<?= BASE_URL ?>/?page=cs.list" class="btn btn-outline-secondary flex-grow-1">
          ← Quay lại
        </a>
        <button type="submit" class="btn btn-danger flex-grow-1 fw-semibold">
          🗑️ Xác nhận huỷ lô
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
