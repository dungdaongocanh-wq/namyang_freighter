<?php if (isset($_GET['err']) && $_GET['err'] === 'no_select'): ?>
<div class="alert alert-warning mb-3 py-2" style="border-radius:10px;font-size:0.85rem">
  ⚠️ Vui lòng chọn ít nhất 1 lô để tải!
</div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>/?page=ops.download_customs" id="downloadForm">

  <!-- Header action -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-bold mb-0">📋 Lô cần tải tờ khai (<?= count($shipments) ?>)</h6>
    <button type="button" onclick="toggleAll()" class="btn btn-sm btn-outline-primary">
      Chọn tất cả
    </button>
  </div>

  <?php if (empty($shipments)): ?>
  <div class="mobile-card card">
    <div class="card-body text-center py-5">
      <div style="font-size:3rem">✅</div>
      <p class="text-muted mt-2 small">Không có lô nào cần tải!</p>
      <a href="<?= BASE_URL ?>/?page=ops.dashboard" class="btn btn-primary btn-sm mt-2">
        ← Về Dashboard
      </a>
    </div>
  </div>

  <?php else: ?>
  <!-- Danh sách lô -->
  <?php foreach ($shipments as $s): ?>
  <label class="d-block mb-2" style="cursor:pointer">
    <div class="mobile-card card" id="card_<?= $s['id'] ?>">
      <div class="card-body py-3 px-3">
        <div class="d-flex align-items-center gap-3">
          <input type="checkbox" name="shipment_ids[]" value="<?= $s['id'] ?>"
                 class="form-check-input shipment-check"
                 style="width:22px;height:22px;flex-shrink:0"
                 onchange="updateCard(this, <?= $s['id'] ?>)">
          <div class="flex-grow-1">
            <div class="fw-semibold text-primary"><?= htmlspecialchars($s['hawb']) ?></div>
            <div style="font-size:0.78rem;color:#64748b">
              <?= htmlspecialchars($s['customer_code']) ?> ·
              Active: <?= date('d/m', strtotime($s['active_date'])) ?>
            </div>
            <div class="mt-1">
              <?php if ($s['file_count'] > 0): ?>
              <span class="badge bg-success" style="font-size:0.65rem">
                ✅ <?= $s['file_count'] ?>/<?= $s['cd_count'] ?> file
              </span>
              <?php else: ?>
              <span class="badge bg-warning text-dark" style="font-size:0.65rem">
                ⏳ Chưa có file
              </span>
              <?php endif; ?>
            </div>
          </div>
          <div style="font-size:1.5rem">📄</div>
        </div>
      </div>
    </div>
  </label>
  <?php endforeach; ?>

  <!-- Download button sticky -->
  <div style="position:sticky;bottom:75px;padding:8px 0">
    <button type="submit" class="btn btn-mobile-lg btn-warning w-100 shadow"
            id="downloadBtn" disabled>
      ⬇️ Tải tờ khai đã chọn (<span id="selectedCount">0</span>)
    </button>
  </div>
  <?php endif; ?>
</form>

<script>
let allChecked = false;

function toggleAll() {
  allChecked = !allChecked;
  document.querySelectorAll('.shipment-check').forEach(cb => {
    cb.checked = allChecked;
    updateCard(cb, parseInt(cb.value));
  });
  updateCount();
}

function updateCard(cb, id) {
  const card = document.getElementById('card_' + id);
  if (cb.checked) {
    card.style.borderLeft = '4px solid #2d6a9f';
    card.style.background = '#f0f7ff';
  } else {
    card.style.borderLeft = '';
    card.style.background = '';
  }
  updateCount();
}

function updateCount() {
  const count = document.querySelectorAll('.shipment-check:checked').length;
  document.getElementById('selectedCount').textContent = count;
  document.getElementById('downloadBtn').disabled = count === 0;
}
</script>