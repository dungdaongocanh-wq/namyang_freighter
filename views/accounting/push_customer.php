<?php
// Màn hình KT: danh sách lô đã duyệt nội bộ, chưa đẩy sang KH
?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'pushed'): ?>
<div class="alert alert-success alert-dismissible mb-4" style="border-radius:10px">
  ✅ Đã đẩy sang khách hàng thành công!
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
<?php $errMap = ['no_select' => 'Vui lòng chọn ít nhất 1 lô!', 'no_cost' => 'Lô chưa có chi phí nào!']; ?>
<div class="alert alert-danger alert-dismissible mb-4" style="border-radius:10px">
  ❌ <?= htmlspecialchars($errMap[$_GET['err']] ?? $_GET['err']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="fw-bold mb-1">📤 Đẩy chi phí sang KH</h5>
    <p class="text-muted small mb-0">
      Danh sách lô đã xét duyệt nội bộ, chưa gửi cho khách hàng xác nhận.
    </p>
  </div>
  <span class="badge bg-primary fs-6 px-3 py-2">
    <?= count($shipments) ?> lô
  </span>
</div>

<?php if (empty($shipments)): ?>
<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-body text-center py-5">
    <div style="font-size:3rem">🎉</div>
    <p class="fw-semibold mt-2 text-success">Không có lô nào chờ đẩy sang KH!</p>
    <a href="<?= BASE_URL ?>/?page=accounting.review" class="btn btn-outline-primary mt-1">
      ← Về xét duyệt
    </a>
  </div>
</div>
<?php else: ?>

<!-- Bảng lô hàng -->
<form method="POST" action="<?= BASE_URL ?>/?page=accounting.push_customer" id="pushForm">
  <input type="hidden" name="action" value="push_selected">

  <div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center"
         style="border-radius:12px 12px 0 0">
      <div class="form-check mb-0">
        <input class="form-check-input" type="checkbox" id="checkAll">
        <label class="form-check-label fw-semibold" for="checkAll">Chọn tất cả</label>
      </div>
      <button type="submit" class="btn btn-primary fw-semibold px-4" id="pushBtn" disabled>
        📤 Đẩy sang KH đã chọn
      </button>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:0.87rem">
          <thead class="table-light">
            <tr>
              <th class="ps-4" style="width:40px"></th>
              <th>Mã HAWB</th>
              <th>Khách hàng</th>
              <th>Active date</th>
              <th class="text-end">Tổng chi phí</th>
              <th class="text-center">Trạng thái KH</th>
              <th class="text-center">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($shipments as $s):
              $statusKh = match($s['status'] ?? '') {
                  'kt_reviewing'     => ['Chờ KT duyệt', '#e0f2fe', '#0369a1'],
                  'pending_approval' => ['Chờ KH duyệt', '#f3e8ff', '#7e22ce'],
                  'rejected'         => ['KH từ chối',   '#fee2e2', '#b91c1c'],
                  'debt'             => ['Đã duyệt',      '#dcfce7', '#15803d'],
                  default            => [$s['status'],    '#f1f5f9', '#334155'],
              };
            ?>
            <tr>
              <td class="ps-4">
                <?php if ($s['status'] === 'kt_reviewing'): ?>
                <input type="checkbox" name="shipment_ids[]" value="<?= $s['id'] ?>"
                       class="form-check-input row-check">
                <?php endif; ?>
              </td>
              <td>
                <a href="<?= BASE_URL ?>/?page=accounting.review&id=<?= $s['id'] ?>"
                   class="fw-semibold text-primary text-decoration-none">
                  <?= htmlspecialchars($s['hawb']) ?>
                </a>
              </td>
              <td><?= htmlspecialchars($s['company_name'] ?? '—') ?></td>
              <td><?= date('d/m/Y', strtotime($s['active_date'])) ?></td>
              <td class="text-end fw-semibold"><?= number_format($s['total_cost']) ?> đ</td>
              <td class="text-center">
                <span class="badge px-2 py-1"
                      style="background:<?= $statusKh[1] ?>;color:<?= $statusKh[2] ?>;font-size:0.72rem">
                  <?= $statusKh[0] ?>
                </span>
              </td>
              <td class="text-center">
                <?php if ($s['status'] === 'kt_reviewing'): ?>
                <form method="POST" action="<?= BASE_URL ?>/?page=accounting.push_customer" class="d-inline">
                  <input type="hidden" name="action"      value="push_one">
                  <input type="hidden" name="shipment_id" value="<?= $s['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-primary"
                          onclick="return confirm('Đẩy lô này sang KH?')">
                    📤 Đẩy
                  </button>
                </form>
                <?php else: ?>
                <a href="<?= BASE_URL ?>/?page=accounting.review&id=<?= $s['id'] ?>"
                   class="btn btn-sm btn-outline-secondary">
                  👁 Xem
                </a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer bg-white py-3 px-4 d-flex justify-content-between align-items-center"
         style="border-radius:0 0 12px 12px">
      <small class="text-muted">
        <span id="selectedCount">0</span> lô được chọn
      </small>
      <button type="submit" class="btn btn-primary fw-semibold px-4" id="pushBtn2" disabled>
        📤 Đẩy sang KH đã chọn
      </button>
    </div>
  </div>
</form>

<?php endif; ?>

<script>
const checkAll   = document.getElementById('checkAll');
const rowChecks  = document.querySelectorAll('.row-check');
const pushBtns   = [document.getElementById('pushBtn'), document.getElementById('pushBtn2')];
const countSpan  = document.getElementById('selectedCount');

function updateButtons() {
  const selected = document.querySelectorAll('.row-check:checked').length;
  if (countSpan) countSpan.textContent = selected;
  pushBtns.forEach(b => { if (b) b.disabled = selected === 0; });
}

if (checkAll) {
  checkAll.addEventListener('change', function() {
    rowChecks.forEach(cb => cb.checked = this.checked);
    updateButtons();
  });
}

rowChecks.forEach(cb => cb.addEventListener('change', updateButtons));

document.getElementById('pushForm')?.addEventListener('submit', function(e) {
  const selected = document.querySelectorAll('.row-check:checked').length;
  if (selected === 0) {
    e.preventDefault();
    alert('Vui lòng chọn ít nhất 1 lô!');
    return;
  }
  if (!confirm('Đẩy ' + selected + ' lô sang khách hàng?')) {
    e.preventDefault();
  }
});
</script>
