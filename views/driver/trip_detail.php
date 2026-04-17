<!-- Header chuyến -->
<div class="mobile-card card mb-3"
     style="border-left:4px solid <?= $trip['status']==='completed'?'#15803d':'#1d4ed8' ?>">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <h5 class="fw-bold mb-0 text-primary">Chuyến #<?= $trip['id'] ?></h5>
        <small class="text-muted">OPS: <?= htmlspecialchars($trip['ops_name'] ?? '') ?></small>
      </div>
      <span class="badge <?= $trip['status']==='completed'?'bg-success':'bg-primary' ?> fs-6">
        <?= $trip['status']==='completed' ? '✅ Xong' : '🚚 Đang giao' ?>
      </span>
    </div>

    <div class="row g-2 mt-2">
      <div class="col-6">
        <div style="font-size:0.7rem;color:#94a3b8">NGÀY</div>
        <div class="fw-semibold small"><?= date('d/m/Y', strtotime($trip['trip_date'])) ?></div>
      </div>
      <div class="col-6">
        <div style="font-size:0.7rem;color:#94a3b8">TIẾN ĐỘ</div>
        <div class="fw-semibold small">
          <?= $deliveredItems ?>/<?= $totalItems ?> lô đã giao
        </div>
      </div>
    </div>

    <!-- Progress -->
    <div class="progress mt-2" style="height:10px;border-radius:5px">
      <div class="progress-bar <?= $trip['status']==='completed'?'bg-success':'bg-primary' ?>"
           style="width:<?= $totalItems > 0 ? ($deliveredItems/$totalItems*100) : 0 ?>%">
      </div>
    </div>

    <?php if ($trip['note']): ?>
    <div class="mt-2 p-2 rounded-2 small" style="background:#fffbeb">
      📝 <?= htmlspecialchars($trip['note']) ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Danh sách lô -->
<h6 class="fw-bold mb-2">📦 Danh sách lô (<?= $totalItems ?>)</h6>

<?php foreach ($items as $item):
  $isDelivered = $item['status'] === 'delivered';
  $hasSig      = !empty($item['signature_path']);
?>
<div class="mobile-card card mb-3"
     style="border-left:4px solid <?= $isDelivered ? '#15803d' : '#f97316' ?>">
  <div class="card-body">

    <!-- Shipment info -->
    <div class="d-flex justify-content-between align-items-start mb-2">
      <div>
        <div class="fw-bold text-primary"><?= htmlspecialchars($item['hawb']) ?></div>
        <div style="font-size:0.78rem;color:#64748b">
          <?= htmlspecialchars($item['customer_code']) ?>
          <?php if ($item['customer_phone']): ?>
          · <a href="tel:<?= $item['customer_phone'] ?>" class="text-success">
              📞 <?= $item['customer_phone'] ?>
            </a>
          <?php endif; ?>
        </div>
        <?php if ($item['customer_address']): ?>
        <div style="font-size:0.75rem;color:#94a3b8;margin-top:2px">
          📍 <?= htmlspecialchars($item['customer_address']) ?>
        </div>
        <?php endif; ?>
      </div>
      <span class="badge <?= $isDelivered ? 'bg-success' : 'bg-warning text-dark' ?>">
        <?= $isDelivered ? '✅ Đã giao' : '⏳ Chưa giao' ?>
      </span>
    </div>

    <div class="d-flex gap-2 small text-muted mb-2">
      <span>📦 <?= $item['packages'] ?> kiện</span>
      <span>⚖️ <?= number_format($item['weight'],1) ?> kg</span>
      <?php if ($item['flight_no']): ?>
      <span>✈️ <?= htmlspecialchars($item['flight_no']) ?></span>
      <?php endif; ?>
    </div>

    <?php if ($isDelivered && $hasSig): ?>
    <!-- Đã có chữ ký -->
    <div class="p-2 rounded-2 mb-2" style="background:#f0fdf4;border:1px solid #bbf7d0">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <div style="font-size:0.75rem;color:#15803d">
            ✅ Ký nhận: <strong><?= htmlspecialchars($item['signed_by_name']) ?></strong>
          </div>
          <div style="font-size:0.7rem;color:#86efac">
            <?= $item['signed_at'] ? date('d/m/Y H:i', strtotime($item['signed_at'])) : '' ?>
          </div>
        </div>
        <a href="<?= BASE_URL ?>/<?= $item['signature_path'] ?>" target="_blank">
          <img src="<?= BASE_URL ?>/<?= $item['signature_path'] ?>"
               style="height:45px;width:80px;object-fit:contain;background:#fff;border-radius:6px;border:1px solid #d1fae5">
        </a>
      </div>
    </div>

    <?php elseif (!$isDelivered): ?>
    <!-- Chưa giao → nút lấy chữ ký -->
    <a href="<?= BASE_URL ?>/?page=driver.signature&shipment_id=<?= $item['id'] ?>&trip_id=<?= $trip['id'] ?>"
       class="btn btn-mobile-primary btn-primary w-100">
      ✍️ Lấy chữ ký giao hàng
    </a>
    <?php endif; ?>

  </div>
</div>
<?php endforeach; ?>

<!-- Hoàn thành chuyến -->
<?php if ($deliveredItems === $totalItems && $totalItems > 0 && $trip['status'] !== 'completed'): ?>
<div class="text-center py-3">
  <div style="font-size:3rem">🎉</div>
  <p class="fw-semibold text-success mt-2">Tất cả lô đã được giao!</p>
  <a href="<?= BASE_URL ?>/?page=driver.dashboard" class="btn btn-success btn-lg px-5 mt-1">
    ← Về trang chủ
  </a>
</div>
<?php elseif ($trip['status'] === 'completed'): ?>
<div class="text-center py-3">
  <div style="font-size:3rem">✅</div>
  <p class="fw-semibold text-success mt-2">Chuyến đã hoàn thành!</p>
  <a href="<?= BASE_URL ?>/?page=driver.dashboard" class="btn btn-outline-success btn-lg px-5">
    ← Về trang chủ
  </a>
</div>
<?php endif; ?>