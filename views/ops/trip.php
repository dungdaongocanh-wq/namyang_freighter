<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
<div class="alert alert-success mb-3 py-2" style="border-radius:10px;font-size:0.85rem">
  ✅ Tạo chuyến #<?= (int)($_GET['trip_id'] ?? 0) ?> thành công!
</div>
<?php endif; ?>

<!-- Form tạo chuyến -->
<div class="mobile-card card mb-3">
  <div class="card-body">
    <h6 class="fw-bold mb-3">🚚 Tạo chuyến giao hàng mới</h6>

    <?php if (!empty($msg) && str_starts_with($msg, 'error:')): ?>
    <div class="alert alert-danger py-2 mb-3" style="font-size:0.85rem;border-radius:8px">
      ⚠️ <?= htmlspecialchars(substr($msg, 6)) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/?page=ops.trip" id="tripForm">
      <input type="hidden" name="action" value="create_trip">

      <!-- Lái xe -->
      <div class="mb-3">
        <label class="form-label fw-semibold small">🧑‍✈️ Lái xe</label>
        <select name="driver_id" class="form-select" required>
          <option value="">-- Chọn lái xe --</option>
          <?php foreach ($drivers as $d): ?>
          <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Ngày -->
      <div class="mb-3">
        <label class="form-label fw-semibold small">📅 Ngày giao</label>
        <input type="date" name="trip_date" class="form-control"
               value="<?= date('Y-m-d') ?>" required>
      </div>

      <!-- Ghi chú -->
      <div class="mb-3">
        <label class="form-label fw-semibold small">📝 Ghi chú</label>
        <input type="text" name="note" class="form-control" placeholder="Ghi chú chuyến...">
      </div>

      <!-- Chọn lô -->
      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <label class="form-label fw-semibold small mb-0">
            📦 Chọn lô hàng
            <span class="badge bg-primary" id="selectedLoBadge">0</span>
          </label>
          <button type="button" class="btn btn-xs btn-outline-secondary"
                  style="font-size:0.75rem;padding:2px 8px"
                  onclick="toggleAllLo()">Chọn tất cả</button>
        </div>

        <?php if (empty($waitingShipments)): ?>
        <div class="text-center text-muted py-3 small" style="background:#f8fafc;border-radius:10px">
          Không có lô nào chờ lấy hàng
        </div>
        <?php else: ?>
        <div style="max-height:300px;overflow-y:auto">
          <?php foreach ($waitingShipments as $s): ?>
          <label class="d-block mb-2" style="cursor:pointer">
            <div class="d-flex align-items-center gap-2 p-2 rounded-2 lo-item"
                 style="background:#f8fafc;border:1.5px solid transparent"
                 id="lo_<?= $s['id'] ?>">
              <input type="checkbox" name="shipment_ids[]" value="<?= $s['id'] ?>"
                     class="form-check-input lo-check"
                     style="width:20px;height:20px;flex-shrink:0"
                     onchange="updateLoCard(this, <?= $s['id'] ?>)">
              <div class="flex-grow-1">
                <div class="fw-semibold small text-primary"><?= htmlspecialchars($s['hawb']) ?></div>
                <div style="font-size:0.75rem;color:#64748b">
                  <?= htmlspecialchars($s['customer_code']) ?> ·
                  <?= $s['packages'] ?> kiện · <?= number_format($s['weight'],1) ?> kg
                </div>
              </div>
            </div>
          </label>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn btn-mobile-primary btn-primary w-100"
              id="createTripBtn" <?= empty($waitingShipments)?'disabled':'' ?>>
        ✅ Tạo chuyến
      </button>
    </form>
  </div>
</div>

<!-- Chuyến gần đây -->
<?php if (!empty($recentTrips)): ?>
<div class="mobile-card card">
  <div class="card-body">
    <h6 class="fw-bold mb-3">📋 Chuyến gần đây</h6>
    <?php foreach ($recentTrips as $t): ?>
    <div class="d-flex justify-content-between align-items-center p-2 mb-2 rounded-2"
         style="background:#f8fafc">
      <div>
        <div class="fw-semibold small">Chuyến #<?= $t['id'] ?></div>
        <div style="font-size:0.75rem;color:#64748b">
          🧑‍✈️ <?= htmlspecialchars($t['driver_name'] ?? '') ?> ·
          <?= $t['item_count'] ?> lô ·
          <?= date('d/m/Y', strtotime($t['trip_date'])) ?>
        </div>
      </div>
      <span class="badge bg-<?= $t['status']==='completed'?'success':'primary' ?>">
        <?= $t['status']==='completed'?'Xong':'Đang đi' ?>
      </span>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<script>
let allLoChecked = false;
function toggleAllLo() {
  allLoChecked = !allLoChecked;
  document.querySelectorAll('.lo-check').forEach(cb => {
    cb.checked = allLoChecked;
    updateLoCard(cb, parseInt(cb.value));
  });
}
function updateLoCard(cb, id) {
  const card = document.getElementById('lo_' + id);
  card.style.borderColor  = cb.checked ? '#2d6a9f' : 'transparent';
  card.style.background   = cb.checked ? '#f0f7ff' : '#f8fafc';
  const count = document.querySelectorAll('.lo-check:checked').length;
  document.getElementById('selectedLoBadge').textContent = count;
}
</script>