<?php
// Màn hình KH từ chối chi phí — hiển thị thông tin lô + form nhập lý do

$statusLabel = [
    'pending_approval' => ['Chờ bạn duyệt', '#f3e8ff', '#7e22ce'],
    'rejected'         => ['Đã từ chối',     '#f1f5f9', '#334155'],
];
[$sl, $sbg, $sclr] = $statusLabel[$shipment['status']] ?? [$shipment['status'], '#f1f5f9', '#334155'];
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
        <div class="text-muted" style="font-size:0.72rem">CHUYẾN BAY</div>
        <div class="fw-semibold"><?= htmlspecialchars($shipment['flight_no'] ?? '—') ?></div>
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
    </div>
  </div>
</div>

<!-- Chi tiết chi phí (chỉ xem) -->
<?php if (!empty($costs)): ?>
<div class="card mb-4" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-header bg-white py-3 px-4" style="border-radius:12px 12px 0 0">
    <h6 class="mb-0 fw-bold">💰 Chi phí cần từ chối</h6>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm mb-0" style="font-size:0.88rem">
      <tbody>
        <?php foreach ($costs as $c): ?>
        <tr>
          <td class="ps-4"><?= htmlspecialchars($c['cost_name']) ?></td>
          <td class="text-end pe-4 fw-semibold"><?= number_format($c['amount']) ?> đ</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot style="background:#fff1f2">
        <tr>
          <td class="ps-4 fw-bold text-danger">TỔNG CỘNG</td>
          <td class="text-end pe-4 fw-bold text-danger fs-6">
            <?= number_format($totalCost) ?> đ
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Form từ chối -->
<div class="card"
     style="border:none;border-radius:12px;box-shadow:0 4px 20px rgba(239,68,68,0.12);border:2px solid #fecaca">
  <div class="card-header py-3 px-4" style="background:#fff5f5;border-radius:12px 12px 0 0">
    <h6 class="mb-0 fw-bold text-danger">❌ Nhập lý do từ chối</h6>
  </div>
  <div class="card-body">
    <p class="text-muted small mb-4">
      Vui lòng nêu rõ lý do không đồng ý với chi phí trên. Kế toán sẽ nhận được thông báo và điều chỉnh lại.
    </p>

    <form method="POST" action="<?= BASE_URL ?>/?page=customer.reject"
          onsubmit="return validateReject()">
      <input type="hidden" name="shipment_id" value="<?= $shipment['id'] ?>">

      <div class="mb-4">
        <label class="form-label fw-semibold">
          Lý do từ chối <span class="text-danger">*</span>
        </label>
        <textarea id="rejectReason"
                  name="reason"
                  class="form-control"
                  rows="5"
                  required
                  minlength="10"
                  placeholder="Ví dụ: Chi phí lưu kho quá cao so với báo giá ban đầu. Đề nghị xem lại..."></textarea>
        <div class="form-text">Tối thiểu 10 ký tự.</div>
      </div>

      <div class="d-flex gap-3">
        <a href="<?= BASE_URL ?>/?page=customer.pending_approval"
           class="btn btn-outline-secondary flex-grow-1">
          ← Quay lại
        </a>
        <button type="submit" class="btn btn-danger flex-grow-1 fw-semibold py-2">
          ❌ Xác nhận từ chối
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function validateReject() {
  const reason = document.getElementById('rejectReason').value.trim();
  if (reason.length < 10) {
    alert('Vui lòng nhập lý do chi tiết (ít nhất 10 ký tự)!');
    return false;
  }
  return confirm(
    'Xác nhận TỪ CHỐI chi phí lô <?= htmlspecialchars($shipment['hawb']) ?>?\n' +
    'Tổng: <?= number_format($totalCost) ?> đ\n\n' +
    'Kế toán sẽ nhận được thông báo.'
  );
}
</script>
