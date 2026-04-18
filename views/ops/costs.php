<?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
<div class="alert alert-success mb-3 py-2" style="border-radius:10px;font-size:0.85rem">
  ✅ Đã lưu chi phí thành công!
</div>
<?php endif; ?>

<!-- OPS Chi phí tổng quan -->
<div class="mobile-card card mb-3" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-body pb-2">
    <h6 class="fw-bold mb-3" style="color:#1e3a5f">💸 Chi phí OPS đã bỏ ra</h6>
    <div class="row g-2 mb-3">
      <!-- Hôm nay -->
      <div class="col-4">
        <div class="text-center p-2 rounded-2" style="background:#fff7ed">
          <div style="font-size:0.7rem;color:#9a3412;font-weight:600">Hôm nay</div>
          <div class="fw-bold" style="font-size:0.9rem;color:#c2410c">
            <?= number_format($opsCostToday) ?><span style="font-size:0.65rem">đ</span>
          </div>
        </div>
      </div>
      <!-- Tuần này -->
      <div class="col-4">
        <div class="text-center p-2 rounded-2" style="background:#eff6ff">
          <div style="font-size:0.7rem;color:#1d4ed8;font-weight:600">Tuần này</div>
          <div class="fw-bold" style="font-size:0.9rem;color:#1d4ed8">
            <?= number_format($opsCostWeek) ?><span style="font-size:0.65rem">đ</span>
          </div>
        </div>
      </div>
      <!-- Tháng này -->
      <div class="col-4">
        <div class="text-center p-2 rounded-2" style="background:#f0fdf4">
          <div style="font-size:0.7rem;color:#15803d;font-weight:600">Tháng này</div>
          <div class="fw-bold" style="font-size:0.9rem;color:#15803d">
            <?= number_format($opsCostMonth) ?><span style="font-size:0.65rem">đ</span>
          </div>
        </div>
      </div>
    </div>
    <?php if (!empty($opsCostBreakdown)): ?>
    <div style="border-top:1px solid #f1f5f9;padding-top:8px">
      <div style="font-size:0.72rem;color:#64748b;font-weight:600;margin-bottom:6px">Top chi phí tháng này:</div>
      <?php foreach ($opsCostBreakdown as $b): ?>
      <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.78rem">
        <span class="text-muted"><?= htmlspecialchars($b['cost_name'] ?: '(không tên)') ?></span>
        <span class="fw-semibold text-danger"><?= number_format($b['total']) ?>đ</span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Lô chưa nhập chi phí -->
<?php if (!$shipment && !empty($pendingCosts)): ?>
<div class="mobile-card card mb-3">
  <div class="card-body">
    <h6 class="fw-bold mb-3">📋 Lô cần nhập chi phí (<?= count($pendingCosts) ?>)</h6>
    <?php foreach ($pendingCosts as $s): ?>
    <a href="<?= BASE_URL ?>/?page=ops.costs&id=<?= $s['id'] ?>" class="text-decoration-none">
      <div class="d-flex justify-content-between align-items-center p-2 mb-2 rounded-2"
           style="background:#fff7ed;border-left:3px solid #f97316">
        <div>
          <div class="fw-semibold small text-primary"><?= htmlspecialchars($s['hawb']) ?></div>
          <div style="font-size:0.75rem;color:#64748b">
            <?= htmlspecialchars($s['customer_code']) ?> ·
            <?= date('d/m', strtotime($s['active_date'])) ?>
          </div>
        </div>
        <span style="font-size:0.75rem;color:#f97316">Nhập chi phí ›</span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Form nhập chi phí -->
<?php if ($shipment): ?>
<div class="mobile-card card">
  <div class="card-body">
    <!-- Shipment info -->
    <div class="p-2 mb-3 rounded-2" style="background:#f0f7ff">
      <div class="fw-bold text-primary"><?= htmlspecialchars($shipment['hawb']) ?></div>
      <div style="font-size:0.78rem;color:#64748b">
        <?= htmlspecialchars($shipment['customer_code']) ?> ·
        <?= $shipment['packages'] ?> kiện · <?= number_format($shipment['weight'],1) ?> kg
      </div>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/?page=ops.costs" id="costsForm">
      <input type="hidden" name="shipment_id" value="<?= $shipment['id'] ?>">

      <div id="costRows">
        <?php
        $defaultCosts = [
            ['name' => 'Phí vận chuyển',    'amount' => ''],
            ['name' => 'Phí bốc xếp',       'amount' => ''],
            ['name' => 'Phí lưu kho',        'amount' => ''],
            ['name' => 'Chi phí khác',       'amount' => ''],
        ];
        $rows = !empty($existingCosts) ? $existingCosts : $defaultCosts;
        foreach ($rows as $i => $cost): ?>
        <div class="cost-row d-flex gap-2 mb-2 align-items-center">
          <input type="text" name="costs[<?= $i ?>][name]"
                 class="form-control form-control-sm"
                 placeholder="Tên chi phí"
                 value="<?= htmlspecialchars($cost['cost_name'] ?? $cost['name'] ?? '') ?>">
          <input type="number" name="costs[<?= $i ?>][amount]"
                 class="form-control form-control-sm"
                 placeholder="Số tiền"
                 style="width:130px"
                 step="1000" min="0"
                 value="<?= $cost['amount'] ?? '' ?>"
                 oninput="calcTotal()">
          <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0"
                  onclick="removeRow(this)">✕</button>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Tổng -->
      <div class="d-flex justify-content-between align-items-center my-3 p-2 rounded-2"
           style="background:#f0fdf4">
        <span class="fw-semibold small">💰 Tổng chi phí:</span>
        <span class="fw-bold text-success" id="totalCost">0 đ</span>
      </div>

      <!-- Add row -->
      <button type="button" class="btn btn-outline-secondary btn-sm w-100 mb-3"
              onclick="addRow()">+ Thêm chi phí</button>

      <button type="submit" class="btn btn-mobile-primary btn-success w-100">
        💾 Lưu chi phí
      </button>
    </form>
  </div>
</div>
<?php elseif (empty($pendingCosts)): ?>
<div class="mobile-card card">
  <div class="card-body text-center py-5">
    <div style="font-size:3rem">✅</div>
    <p class="text-muted mt-2 small">Không có lô nào cần nhập chi phí!</p>
  </div>
</div>
<?php endif; ?>

<script>
let rowIndex = <?= count($rows ?? []) ?>;

function addRow() {
  const container = document.getElementById('costRows');
  const div = document.createElement('div');
  div.className = 'cost-row d-flex gap-2 mb-2 align-items-center';
  div.innerHTML = `
    <input type="text" name="costs[${rowIndex}][name]"
           class="form-control form-control-sm" placeholder="Tên chi phí">
    <input type="number" name="costs[${rowIndex}][amount]"
           class="form-control form-control-sm" placeholder="Số tiền"
           style="width:130px" step="1000" min="0" oninput="calcTotal()">
    <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0"
            onclick="removeRow(this)">✕</button>
  `;
  container.appendChild(div);
  rowIndex++;
}

function removeRow(btn) {
  btn.closest('.cost-row').remove();
  calcTotal();
}

function calcTotal() {
  let total = 0;
  document.querySelectorAll('input[name*="[amount]"]').forEach(i => {
    total += parseFloat(i.value) || 0;
  });
  document.getElementById('totalCost').textContent =
    new Intl.NumberFormat('vi-VN').format(total) + ' đ';
}

// Init
calcTotal();
</script>