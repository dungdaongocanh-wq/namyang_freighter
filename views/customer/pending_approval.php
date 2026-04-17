<?php
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';
?>
<?php if ($msg === 'approved'): ?>
<div class="alert alert-success alert-dismissible mb-4" style="border-radius:10px">
  ✅ Đã đồng ý chi phí! <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php elseif ($msg === 'rejected'): ?>
<div class="alert alert-info alert-dismissible mb-4" style="border-radius:10px">
  ✅ Đã từ chối và gửi lý do cho kế toán!
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($err === 'no_reason'): ?>
<div class="alert alert-danger alert-dismissible mb-4" style="border-radius:10px">
  ❌ Vui lòng nhập lý do từ chối!
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="fw-bold mb-1">⚠️ Chi phí chờ xác nhận</h5>
    <p class="text-muted small mb-0">
      Vui lòng xem xét và xác nhận hoặc từ chối từng lô hàng dưới đây.
    </p>
  </div>
  <span class="badge bg-warning text-dark fs-6 px-3 py-2">
    <?= count($pendingList) ?> lô
  </span>
</div>

<?php if (empty($pendingList)): ?>
<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-body text-center py-5">
    <div style="font-size:3rem">🎉</div>
    <p class="fw-semibold mt-2 text-success">Không có chi phí nào chờ xác nhận!</p>
    <a href="<?= BASE_URL ?>/?page=customer.dashboard" class="btn btn-outline-primary mt-1">
      ← Về Dashboard
    </a>
  </div>
</div>
<?php else: ?>
<?php foreach ($pendingList as $s): ?>
<div class="card mb-3"
     style="border:none;border-radius:12px;box-shadow:0 2px 16px rgba(124,58,237,0.12);border:1.5px solid #e9d5ff">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <a href="<?= BASE_URL ?>/?page=customer.shipment_detail&id=<?= $s['id'] ?>"
           class="fw-bold text-primary text-decoration-none fs-6">
          <?= htmlspecialchars($s['hawb']) ?>
        </a>
        <div class="small text-muted mt-1">
          Active date: <?= date('d/m/Y', strtotime($s['active_date'])) ?> ·
          <?= $s['packages'] ?> kiện · <?= number_format($s['weight'],1) ?> kg
        </div>
      </div>
      <div class="text-end">
        <div class="fw-bold text-success fs-5"><?= number_format($s['total_cost']) ?> đ</div>
        <small class="text-muted"><?= $s['cost_lines'] ?> dòng chi phí</small>
      </div>
    </div>

    <div class="d-flex gap-2">
      <!-- Approve -->
      <form method="POST" action="<?= BASE_URL ?>/?page=customer.approve" class="flex-grow-1">
        <input type="hidden" name="shipment_id" value="<?= $s['id'] ?>">
        <button type="submit" class="btn btn-success w-100 fw-semibold"
                onclick="return confirm('Xác nhận đồng ý chi phí <?= number_format($s['total_cost']) ?> đ?')">
          ✅ Đồng ý
        </button>
      </form>

      <!-- View detail -->
      <a href="<?= BASE_URL ?>/?page=customer.shipment_detail&id=<?= $s['id'] ?>"
         class="btn btn-outline-primary px-3">
        👁 Xem
      </a>

      <!-- Reject -->
      <button class="btn btn-outline-danger px-3 fw-semibold"
              data-bs-toggle="modal"
              data-bs-target="#rejectModal<?= $s['id'] ?>">
        ❌
      </button>
    </div>
  </div>
</div>

<!-- Reject Modal riêng cho từng lô -->
<div class="modal fade" id="rejectModal<?= $s['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header">
        <h6 class="modal-title fw-bold text-danger">❌ Từ chối — <?= htmlspecialchars($s['hawb']) ?></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= BASE_URL ?>/?page=customer.reject">
        <div class="modal-body">
          <input type="hidden" name="shipment_id" value="<?= $s['id'] ?>">
          <p class="text-muted small mb-3">
            Chi phí cần từ chối: <strong class="text-danger"><?= number_format($s['total_cost']) ?> đ</strong>
          </p>
          <label class="form-label fw-semibold small">Lý do từ chối *</label>
          <textarea name="reason" class="form-control" rows="3" required
                    placeholder="Vui lòng nêu rõ lý do..."></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary"
                  data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-danger px-4">
            ❌ Xác nhận từ chối
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>