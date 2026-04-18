<?php
// Dùng khi truy cập trực tiếp /?page=customer.approve&id=...
// Hiển thị chi tiết chi phí 1 lô và nút duyệt / từ chối

$statusLabel = [
    'pending_customs'  => ['Chờ tờ khai',    '#fee2e2', '#b91c1c'],
    'cleared'          => ['Đã thông quan',   '#fef9c3', '#92400e'],
    'waiting_pickup'   => ['Chờ lấy hàng',   '#ffedd5', '#c2410c'],
    'in_transit'       => ['Đang giao',       '#dbeafe', '#1d4ed8'],
    'delivered'        => ['Đã giao',         '#dcfce7', '#15803d'],
    'kt_reviewing'     => ['KT đang duyệt',   '#e0f2fe', '#0369a1'],
    'pending_approval' => ['Chờ bạn duyệt',   '#f3e8ff', '#7e22ce'],
    'rejected'         => ['Đã từ chối',      '#f1f5f9', '#334155'],
    'debt'             => ['Công nợ',          '#f0fdf4', '#15803d'],
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
        <div class="text-muted" style="font-size:0.72rem">ACTIVE DATE</div>
        <div class="fw-semibold"><?= date('d/m/Y', strtotime($shipment['active_date'])) ?></div>
      </div>
      <div class="col-sm-4">
        <div class="text-muted" style="font-size:0.72rem">SỐ KIỆN</div>
        <div class="fw-semibold"><?= $shipment['packages'] ?> kiện</div>
      </div>
      <div class="col-sm-4">
        <div class="text-muted" style="font-size:0.72rem">TRỌNG LƯỢNG</div>
        <div class="fw-semibold"><?= number_format($shipment['weight'], 1) ?> kg</div>
      </div>
    </div>
  </div>
</div>

<!-- Chi tiết chi phí -->
<div class="card mb-4"
     style="border:none;border-radius:12px;box-shadow:0 4px 20px rgba(124,58,237,0.15);border:2px solid #c4b5fd">
  <div class="card-header py-3 px-4" style="background:#faf5ff;border-radius:12px 12px 0 0">
    <h6 class="mb-0 fw-bold" style="color:#7e22ce">💰 Chi phí cần xác nhận</h6>
  </div>
  <div class="card-body p-0">
    <?php if (!empty($costs)): ?>
    <table class="table table-sm mb-0" style="font-size:0.88rem">
      <thead class="table-light">
        <tr>
          <th class="ps-4">Tên chi phí</th>
          <th class="text-end pe-4">Số tiền</th>
          <?php if (array_filter($costs, fn($c) => !empty($c['note']))): ?>
          <th>Ghi chú OPS</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($costs as $c): ?>
        <tr>
          <td class="ps-4"><?= htmlspecialchars($c['cost_name']) ?></td>
          <td class="text-end pe-4 fw-semibold"><?= number_format($c['amount']) ?> đ</td>
          <?php if (array_filter($costs, fn($cx) => !empty($cx['note']))): ?>
          <td class="text-muted small"><?= htmlspecialchars($c['note'] ?? '') ?></td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot style="background:#f0fdf4">
        <tr>
          <td class="ps-4 fw-bold">TỔNG CỘNG</td>
          <td class="text-end pe-4 fw-bold text-success fs-6">
            <?= number_format($totalCost) ?> đ
          </td>
          <?php if (array_filter($costs, fn($cx) => !empty($cx['note']))): ?>
          <td></td>
          <?php endif; ?>
        </tr>
      </tfoot>
    </table>
    <?php else: ?>
    <div class="text-center py-4 text-muted small">Chưa có chi phí nào</div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal xác nhận duyệt -->
<div class="modal fade" id="approveModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header">
        <h6 class="modal-title fw-bold text-success">✅ Xác nhận duyệt chi phí</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning py-2 small mb-3" style="border-radius:8px">
          ⚠️ Lô: <strong><?= htmlspecialchars($shipment['hawb']) ?></strong><br>
          Tổng chi phí: <strong class="text-success"><?= number_format($totalCost) ?> đ</strong>
        </div>
        <p class="text-muted small mb-0">
          Sau khi duyệt, chi phí này sẽ được ghi nhận là công nợ của bạn.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
        <form method="POST" action="<?= BASE_URL ?>/?page=customer.approve">
          <input type="hidden" name="shipment_id" value="<?= $shipment['id'] ?>">
          <button type="submit" class="btn btn-success px-4 fw-semibold">
            ✅ Đồng ý chi phí
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Hành động -->
<div class="d-flex gap-3">
  <button class="btn btn-success flex-grow-1 fw-semibold py-2"
          data-bs-toggle="modal" data-bs-target="#approveModal">
    ✅ Duyệt chi phí
  </button>
  <button class="btn btn-outline-danger flex-grow-1 fw-semibold py-2"
          data-bs-toggle="modal" data-bs-target="#rejectModal">
    ❌ Từ chối
  </button>
</div>

<div class="mt-3">
  <a href="<?= BASE_URL ?>/?page=customer.pending_approval" class="text-muted small text-decoration-none">
    ← Quay lại danh sách chờ duyệt
  </a>
</div>

<!-- Modal từ chối -->
<div class="modal fade" id="rejectModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header">
        <h6 class="modal-title fw-bold text-danger">❌ Từ chối chi phí</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= BASE_URL ?>/?page=customer.reject">
        <div class="modal-body">
          <input type="hidden" name="shipment_id" value="<?= $shipment['id'] ?>">
          <div class="alert alert-warning py-2 small mb-3" style="border-radius:8px">
            Lô: <strong><?= htmlspecialchars($shipment['hawb']) ?></strong> —
            Chi phí: <strong class="text-danger"><?= number_format($totalCost) ?> đ</strong>
          </div>
          <label class="form-label fw-semibold small">Lý do từ chối <span class="text-danger">*</span></label>
          <textarea name="reason" class="form-control" rows="3" required
                    placeholder="Nêu rõ lý do bạn không đồng ý với chi phí này..."></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-danger px-4 fw-semibold">
            ❌ Xác nhận từ chối
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
