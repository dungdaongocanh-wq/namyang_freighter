<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-header bg-white py-3 px-4" style="border-radius:12px 12px 0 0">
    <h6 class="mb-0 fw-bold">📋 Lịch sử duyệt chi phí</h6>
  </div>
  <div class="card-body p-0">
    <?php if (empty($history)): ?>
    <div class="text-center text-muted py-5">
      <div style="font-size:3rem">📭</div>
      <p class="mt-2">Chưa có lịch sử duyệt nào!</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle" style="font-size:0.85rem">
        <thead style="background:#f8fafc;color:#64748b;font-size:0.78rem">
          <tr>
            <th class="ps-4">HAWB</th>
            <th>Active date</th>
            <th>Hành động</th>
            <th class="text-end">Chi phí</th>
            <th>Lý do / Ghi chú</th>
            <th>Thời gian</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($history as $h): ?>
          <tr>
            <td class="ps-4">
              <a href="<?= BASE_URL ?>/?page=customer.shipment_detail&id=<?= $h['shipment_id'] ?>"
                 class="fw-semibold text-primary text-decoration-none">
                <?= htmlspecialchars($h['hawb']) ?>
              </a>
            </td>
            <td><?= date('d/m/Y', strtotime($h['active_date'])) ?></td>
            <td>
              <?php if ($h['action'] === 'approved'): ?>
              <span class="badge bg-success">✅ Đã đồng ý</span>
              <?php elseif ($h['action'] === 'rejected'): ?>
              <span class="badge bg-danger">❌ Từ chối</span>
              <?php else: ?>
              <span class="badge bg-secondary"><?= htmlspecialchars($h['action']) ?></span>
              <?php endif; ?>
            </td>
            <td class="text-end fw-semibold text-success">
              <?= number_format($h['total_cost']) ?> đ
            </td>
            <td class="small text-muted" style="max-width:200px">
              <?= htmlspecialchars(mb_substr($h['reason'] ?? '', 0, 80)) ?>
              <?= mb_strlen($h['reason'] ?? '') > 80 ? '…' : '' ?>
            </td>
            <td class="small text-muted">
              <?= date('d/m/Y H:i', strtotime($h['created_at'])) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>