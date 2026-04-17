<?php if (isset($_GET['msg']) && $_GET['msg']==='resubmitted'): ?>
<div class="alert alert-success alert-dismissible mb-4" style="border-radius:10px">
  ✅ Đã gửi lại cho khách hàng! <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-header bg-white py-3 px-4" style="border-radius:12px 12px 0 0">
    <h6 class="mb-0 fw-bold">
      ❌ Lô KH từ chối
      <span class="badge bg-danger ms-1"><?= count($rejectedList) ?></span>
    </h6>
  </div>
  <div class="card-body p-0">
    <?php if (empty($rejectedList)): ?>
    <div class="text-center text-muted py-5">
      <div style="font-size:3rem">🎉</div>
      <p class="mt-2">Không có lô nào bị từ chối!</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle" style="font-size:0.85rem">
        <thead style="background:#f8fafc;color:#64748b;font-size:0.78rem">
          <tr>
            <th class="ps-4">HAWB</th>
            <th>Khách hàng</th>
            <th>Active date</th>
            <th class="text-end">Chi phí</th>
            <th>Lý do từ chối</th>
            <th>Ngày từ chối</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rejectedList as $s): ?>
          <tr>
            <td class="ps-4 fw-semibold text-danger"><?= htmlspecialchars($s['hawb']) ?></td>
            <td>
              <span class="badge bg-light text-dark border"><?= htmlspecialchars($s['customer_code']) ?></span>
              <div class="small text-muted"><?= htmlspecialchars($s['company_name'] ?? '') ?></div>
            </td>
            <td><?= date('d/m/Y', strtotime($s['active_date'])) ?></td>
            <td class="text-end fw-semibold"><?= number_format($s['total_cost']) ?> đ</td>
            <td>
              <?php if ($s['reject_reason']): ?>
              <span class="text-danger small">
                "<?= htmlspecialchars(mb_substr($s['reject_reason'], 0, 60)) ?><?= mb_strlen($s['reject_reason'])>60?'…':'' ?>"
              </span>
              <?php else: ?>
              <span class="text-muted small">-</span>
              <?php endif; ?>
            </td>
            <td class="small text-muted">
              <?= $s['rejected_at'] ? date('d/m/Y H:i', strtotime($s['rejected_at'])) : '-' ?>
            </td>
            <td class="pe-3">
              <button class="btn btn-sm btn-outline-primary"
                      data-bs-toggle="modal"
                      data-bs-target="#resubmitModal"
                      data-id="<?= $s['id'] ?>"
                      data-hawb="<?= htmlspecialchars($s['hawb']) ?>">
                📤 Gửi lại
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Resubmit Modal -->
<div class="modal fade" id="resubmitModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header">
        <h6 class="modal-title fw-bold">📤 Gửi lại cho khách hàng</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= BASE_URL ?>/?page=accounting.resubmit">
        <div class="modal-body">
          <p class="text-muted small mb-3">
            Lô: <strong id="resubmitHawb"></strong>
          </p>
          <input type="hidden" name="shipment_id" id="resubmitId">
          <label class="form-label small fw-semibold">Ghi chú điều chỉnh</label>
          <textarea name="note" class="form-control" rows="3"
                    placeholder="Mô tả nội dung điều chỉnh chi phí..."></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary"
                  data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary px-4">
            📤 Xác nhận gửi lại
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('resubmitModal').addEventListener('show.bs.modal', function(e) {
  const btn = e.relatedTarget;
  document.getElementById('resubmitId').value   = btn.dataset.id;
  document.getElementById('resubmitHawb').textContent = btn.dataset.hawb;
});
</script>