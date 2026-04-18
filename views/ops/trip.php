<?php if (isset($_GET['msg']) && $_GET['msg'] === 'printed'): ?>
<div class="alert alert-success mb-3 py-2" style="border-radius:10px;font-size:0.85rem">
  ✅ Đã tạo biên bản giao hàng #<?= (int)($_GET['trip_id'] ?? 0) ?> thành công!
</div>
<?php endif; ?>

<!-- Form In Biên Bản Giao Hàng -->
<div class="mobile-card card mb-3">
  <div class="card-body">
    <h6 class="fw-bold mb-3">🖨️ Tạo Biên Bản Giao Hàng</h6>

    <?php if (!empty($msg) && str_starts_with($msg, 'error:')): ?>
    <div class="alert alert-danger py-2 mb-3" style="font-size:0.85rem;border-radius:8px">
      ⚠️ <?= htmlspecialchars(substr($msg, 6)) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/?page=ops.create_trip" id="tripForm">
      <input type="hidden" name="action" value="print_delivery">

      <!-- Công ty vận chuyển -->
      <div class="mb-3">
        <label class="form-label fw-semibold small">🚛 Công ty vận chuyển</label>
        <select name="carrier" class="form-select" required>
          <option value="">-- Chọn công ty vận chuyển --</option>
          <option value="Tâm Việt">Tâm Việt</option>
          <option value="Gia Bảo">Gia Bảo</option>
          <option value="KEN">KEN</option>
        </select>
      </div>

      <!-- Khách hàng nhận hàng -->
      <div class="mb-3">
        <label class="form-label fw-semibold small">🏢 Bên nhận hàng (Khách hàng)</label>
        <select name="customer_id" id="customerSelect" class="form-select" required
                onchange="loadShipmentsByCustomer(this.value)">
          <option value="">-- Chọn khách hàng --</option>
          <?php foreach ($customers as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['company_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Danh sách lô hàng của khách (load qua AJAX) -->
      <div class="mb-3" id="shipmentsSection" style="display:none">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <label class="form-label fw-semibold small mb-0">
            📦 Chọn lô hàng
            <span class="badge bg-primary" id="selectedLoBadge">0</span>
          </label>
          <button type="button" class="btn btn-xs btn-outline-secondary"
                  style="font-size:0.75rem;padding:2px 8px"
                  onclick="toggleAllLo()">Chọn tất cả</button>
        </div>
        <div id="shipmentsLoading" class="text-center text-muted py-2 small" style="display:none">
          <div class="spinner-border spinner-border-sm me-1"></div> Đang tải...
        </div>
        <div id="shipmentsList"></div>
      </div>

      <button type="submit" class="btn btn-primary w-100" id="submitBtn" disabled>
        🖨️ Tạo &amp; In Biên Bản Giao Hàng
      </button>
    </form>
  </div>
</div>

<!-- Biên bản gần đây -->
<?php if (!empty($recentNotes)): ?>
<div class="mobile-card card">
  <div class="card-body">
    <h6 class="fw-bold mb-3">📋 Biên bản gần đây</h6>
    <?php foreach ($recentNotes as $n): ?>
    <div class="d-flex justify-content-between align-items-center p-2 mb-2 rounded-2"
         style="background:#f8fafc">
      <div>
        <div class="fw-semibold small">DN-<?= str_pad($n['id'], 4, '0', STR_PAD_LEFT) ?></div>
        <div style="font-size:0.75rem;color:#64748b">
          🏢 <?= htmlspecialchars($n['company_name'] ?? '') ?> ·
          🚛 <?= htmlspecialchars($n['note'] ?? '') ?> ·
          📅 <?= date('d/m/Y', strtotime($n['trip_date'])) ?>
          (<?= $n['item_count'] ?> lô)
        </div>
      </div>
      <a href="<?= BASE_URL ?>/?page=ops.print_multi_delivery_note&trip_id=<?= $n['id'] ?>"
         target="_blank"
         class="btn btn-sm btn-outline-secondary">🖨️ In lại</a>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<script>
let allLoChecked = false;

function loadShipmentsByCustomer(customerId) {
  const section  = document.getElementById('shipmentsSection');
  const loading  = document.getElementById('shipmentsLoading');
  const listEl   = document.getElementById('shipmentsList');
  const submitBtn = document.getElementById('submitBtn');

  if (!customerId) {
    section.style.display = 'none';
    submitBtn.disabled = true;
    return;
  }

  section.style.display = 'block';
  loading.style.display = 'block';
  listEl.innerHTML = '';
  submitBtn.disabled = true;
  allLoChecked = false;
  document.getElementById('selectedLoBadge').textContent = '0';

  fetch('<?= BASE_URL ?>/?page=ops.shipments_by_customer&customer_id=' + encodeURIComponent(customerId))
    .then(r => r.json())
    .then(data => {
      loading.style.display = 'none';
      if (!data.length) {
        listEl.innerHTML = '<div class="text-center text-muted py-3 small" style="background:#f8fafc;border-radius:10px">Không có lô hàng nào đang chờ lấy hàng</div>';
        return;
      }
      let html = '<div style="max-height:300px;overflow-y:auto">';
      data.forEach(s => {
        html += `<label class="d-block mb-2" style="cursor:pointer">
          <div class="d-flex align-items-center gap-2 p-2 rounded-2 lo-item"
               style="background:#f8fafc;border:1.5px solid transparent"
               id="lo_${s.id}">
            <input type="checkbox" name="shipment_ids[]" value="${s.id}"
                   class="form-check-input lo-check"
                   style="width:20px;height:20px;flex-shrink:0"
                   onchange="updateLoCard(this, ${s.id})">
            <div class="flex-grow-1">
              <div class="fw-semibold small text-primary">${escHtml(s.hawb)}</div>
              <div style="font-size:0.75rem;color:#64748b">
                ${escHtml(s.customer_code)} · ${s.packages} kiện · ${parseFloat(s.weight).toFixed(1)} kg
              </div>
            </div>
          </div>
        </label>`;
      });
      html += '</div>';
      listEl.innerHTML = html;
    })
    .catch(() => {
      loading.style.display = 'none';
      listEl.innerHTML = '<div class="text-danger small">❌ Lỗi tải dữ liệu</div>';
    });
}

function escHtml(str) {
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function toggleAllLo() {
  allLoChecked = !allLoChecked;
  document.querySelectorAll('.lo-check').forEach(cb => {
    cb.checked = allLoChecked;
    updateLoCard(cb, parseInt(cb.value));
  });
}

function updateLoCard(cb, id) {
  const card = document.getElementById('lo_' + id);
  if (card) {
    card.style.borderColor = cb.checked ? '#2d6a9f' : 'transparent';
    card.style.background  = cb.checked ? '#f0f7ff' : '#f8fafc';
  }
  const count = document.querySelectorAll('.lo-check:checked').length;
  document.getElementById('selectedLoBadge').textContent = count;
  document.getElementById('submitBtn').disabled = (count === 0);
}
</script>