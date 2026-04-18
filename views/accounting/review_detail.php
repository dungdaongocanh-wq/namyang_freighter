<?php if (isset($_GET['msg']) && $_GET['msg']==='saved'): ?>
<div class="alert alert-success alert-dismissible mb-4" style="border-radius:10px">
  ✅ Đã lưu chi phí! <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
<!-- Cột trái: thông tin lô -->
<div class="col-lg-4">

  <!-- Shipment info -->
  <div class="card mb-3" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
    <div class="card-header bg-white py-3 px-3" style="border-radius:12px 12px 0 0">
      <h6 class="mb-0 fw-bold">📦 Thông tin lô</h6>
    </div>
    <div class="card-body">
      <dl class="row mb-0 small">
        <dt class="col-5 text-muted">HAWB</dt>
        <dd class="col-7 fw-semibold text-primary"><?= htmlspecialchars($shipment['hawb']) ?></dd>
        <dt class="col-5 text-muted">Khách hàng</dt>
        <dd class="col-7"><?= htmlspecialchars($shipment['customer_code']) ?></dd>
        <dt class="col-5 text-muted">Công ty</dt>
        <dd class="col-7"><?= htmlspecialchars($shipment['company_name'] ?? '') ?></dd>
        <dt class="col-5 text-muted">Flight</dt>
        <dd class="col-7"><?= htmlspecialchars($shipment['flight_no'] ?? '-') ?></dd>
        <dt class="col-5 text-muted">ETA</dt>
        <dd class="col-7"><?= $shipment['eta'] ? date('d/m/Y', strtotime($shipment['eta'])) : '-' ?></dd>
        <dt class="col-5 text-muted">Số kiện</dt>
        <dd class="col-7"><?= $shipment['packages'] ?> kiện / <?= number_format($shipment['weight'],1) ?> kg</dd>
        <dt class="col-5 text-muted">Active date</dt>
        <dd class="col-7"><?= date('d/m/Y', strtotime($shipment['active_date'])) ?></dd>
      </dl>
    </div>
  </div>

  <!-- Báo giá tham chiếu: chi phí charge KH đã lưu -->
  <div class="card mt-3" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
    <div class="card-header bg-white py-3 px-3" style="border-radius:12px 12px 0 0">
      <h6 class="mb-0 fw-bold">📋 Báo giá tham chiếu</h6>
      <div class="text-muted small mt-1">Chi phí sẽ charge khách hàng</div>
    </div>
    <div class="card-body p-0" id="quotationRefBody">
      <?php $ktCostsSaved = array_filter($costs, fn($c) => in_array($c['source'],['kt','quotation','manual','auto'])); ?>
      <?php if (empty($ktCostsSaved)): ?>
      <div class="text-center py-3 text-muted small">Chưa có chi phí nào</div>
      <?php else: ?>
      <table class="table table-sm mb-0" style="font-size:0.82rem">
        <?php foreach ($ktCostsSaved as $kc): ?>
        <tr>
          <td class="ps-3 text-muted"><?= htmlspecialchars($kc['cost_name']) ?></td>
          <td class="text-end pe-3 fw-semibold"><?= number_format($kc['amount']) ?> đ</td>
        </tr>
        <?php endforeach; ?>
        <tr style="background:#f0fdf4">
          <td class="ps-3 fw-bold">Tổng</td>
          <td class="text-end pe-3 fw-bold text-success" id="quotationRefTotal">
            <?= number_format(array_sum(array_column(array_values($ktCostsSaved),'amount'))) ?> đ
          </td>
        </tr>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Cột phải: chi phí -->
<div class="col-lg-8">
  <form id="costsForm">
    <input type="hidden" name="shipment_id" value="<?= $shipment['id'] ?>">

    <!-- ── Section 1: Chi phí OPS (nội bộ) ── -->
    <div class="card mb-3" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
      <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center"
           style="border-radius:12px 12px 0 0">
        <div>
          <h6 class="mb-0 fw-bold">🔧 Chi phí OPS <span class="badge bg-warning text-dark ms-1">Nội bộ</span></h6>
          <div class="text-muted small mt-1">Chi phí vận hành — trả cho OPS, không charge KH</div>
        </div>
      </div>
      <div class="card-body pb-2">
        <table class="table align-middle mb-0" style="font-size:0.88rem">
          <thead style="background:#fff7ed;color:#64748b;font-size:0.78rem">
            <tr>
              <th>Tên chi phí</th>
              <th style="width:180px" class="text-end">Số tiền (đ)</th>
              <th style="width:60px"></th>
            </tr>
          </thead>
          <tbody id="opsBody">
            <?php if (empty($opsCosts)): ?>
            <tr id="opsEmptyRow">
              <td colspan="3" class="text-center text-muted small py-3">OPS chưa nhập chi phí</td>
            </tr>
            <?php else: foreach ($opsCosts as $oc): ?>
            <tr class="ops-row">
              <td>
                <input type="hidden" name="ops_costs[<?= $oc['id'] ?>][id]" value="<?= $oc['id'] ?>">
                <span><?= htmlspecialchars($oc['cost_name']) ?></span>
              </td>
              <td>
                <input type="number" name="ops_costs[<?= $oc['id'] ?>][amount]"
                       class="form-control form-control-sm text-end ops-amount-input"
                       value="<?= $oc['amount'] ?>" step="1000" min="0"
                       oninput="calcOpsTotal()">
              </td>
              <td></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
          <?php if (!empty($opsCosts)): ?>
          <tfoot>
            <tr style="background:#fff7ed">
              <td class="fw-bold ps-2">Tổng OPS:</td>
              <td class="text-end fw-bold text-warning pe-2" id="opsTotalDisplay">
                <?= number_format(array_sum(array_column($opsCosts,'amount'))) ?> đ
              </td>
              <td></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <!-- ── Section 2: Chi phí charge KH ── -->
    <div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
      <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center"
           style="border-radius:12px 12px 0 0">
        <div>
          <h6 class="mb-0 fw-bold">💰 Chi phí charge khách hàng</h6>
          <div class="text-muted small mt-1">Theo báo giá + phí phát sinh thêm tay</div>
        </div>
        <button type="button" onclick="addKtRow()"
                class="btn btn-sm btn-outline-primary">+ Thêm dòng</button>
      </div>

      <div class="card-body pb-0">
        <!-- Chọn từ quotation items -->
        <?php if (!empty($quotationItems)): ?>
        <div class="mb-3 p-3 rounded-2" style="background:#f0f7ff">
          <div class="fw-semibold small mb-2 text-primary">📋 Chọn từ báo giá khách hàng:</div>
          <?php
          // Lấy tên các KT cost đã được lưu để đánh dấu checked
          $savedKtNames = array_unique(array_map(fn($c) => trim($c['cost_name']),
              array_filter($ktCosts, fn($c) => in_array($c['source'],['quotation','kt']))));
          foreach ($quotationItems as $qi):
            $isChecked = in_array(trim($qi['description']), $savedKtNames);
          ?>
          <div class="d-flex align-items-center gap-2 mb-2 p-2 rounded"
               style="background:<?= $isChecked ? '#f0fdf4' : '#fff' ?>;border:1px solid #e2e8f0">
            <input type="checkbox" name="qt_select[]" value="<?= $qi['id'] ?>"
                   class="form-check-input qt-checkbox" style="width:18px;height:18px"
                   data-desc="<?= htmlspecialchars($qi['description']) ?>"
                   data-amount="<?= $qi['amount'] ?>"
                   <?= $isChecked ? 'checked' : '' ?>
                   onchange="toggleQuotationItem(this)">
            <div class="flex-grow-1">
              <div class="fw-semibold small"><?= htmlspecialchars($qi['description']) ?></div>
              <?php if (!empty($qi['note'])): ?>
              <div class="text-muted" style="font-size:0.75rem"><?= htmlspecialchars($qi['note']) ?></div>
              <?php endif; ?>
            </div>
            <div class="text-end fw-semibold small text-primary" style="white-space:nowrap">
              <?= number_format($qi['amount']) ?> đ
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Bảng chi phí KT -->
        <div class="table-responsive">
          <table class="table align-middle mb-0" style="font-size:0.88rem">
            <thead style="background:#f0fdf4;color:#64748b;font-size:0.78rem">
              <tr>
                <th>Tên chi phí</th>
                <th style="width:180px" class="text-end">Số tiền (đ)</th>
                <th style="width:70px" class="text-center">Nguồn</th>
                <th style="width:50px"></th>
              </tr>
            </thead>
            <tbody id="ktBody">
              <?php
              $ktIdx = 0;
              foreach ($ktCosts as $kc): ?>
              <tr class="kt-row" data-source="<?= $kc['source'] ?>" data-qt-desc="<?= htmlspecialchars($kc['cost_name']) ?>">
                <td>
                  <input type="text" name="kt_costs[<?= $ktIdx ?>][name]"
                         class="form-control form-control-sm"
                         value="<?= htmlspecialchars($kc['cost_name']) ?>">
                  <input type="hidden" name="kt_costs[<?= $ktIdx ?>][source]"
                         value="<?= htmlspecialchars($kc['source']) ?>">
                </td>
                <td>
                  <input type="number" name="kt_costs[<?= $ktIdx ?>][amount]"
                         class="form-control form-control-sm text-end kt-amount-input"
                         value="<?= $kc['amount'] ?>" step="1000" min="0"
                         oninput="calcKtTotal()">
                </td>
                <td class="text-center">
                  <span class="badge bg-<?= $kc['source']==='quotation'?'success':'info' ?>">
                    <?= $kc['source']==='quotation'?'BG':'KT' ?>
                  </span>
                </td>
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-outline-danger p-1"
                          onclick="removeKtRow(this)">✕</button>
                </td>
              </tr>
              <?php $ktIdx++; endforeach; ?>
              <?php if (empty($ktCosts)): ?>
              <tr id="ktEmptyRow">
                <td colspan="4" class="text-center text-muted small py-3">
                  Chưa có chi phí — chọn từ báo giá hoặc thêm tay bên dưới
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tổng + actions -->
      <div class="card-footer bg-white px-4 py-3" style="border-radius:0 0 12px 12px">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="fw-bold">TỔNG CHARGE KH:</span>
          <span class="fw-bold fs-5 text-success" id="ktTotalDisplay">
            <?= number_format(array_sum(array_column($ktCosts,'amount'))) ?> đ
          </span>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <button type="button" onclick="saveCosts(false)"
                  class="btn btn-outline-primary px-4">
            💾 Lưu chi phí
          </button>
          <button type="button" onclick="saveCosts(true)"
                  class="btn btn-warning px-4 fw-semibold">
            📤 Lưu & Đẩy sang KH
          </button>
          <a href="<?= BASE_URL ?>/?page=accounting.review" class="btn btn-outline-secondary">
            ← Quay lại
          </a>
        </div>
      </div>
    </div>
  </form>
</div>
</div>

<!-- Hidden push form -->
<form id="pushForm" method="POST" action="<?= BASE_URL ?>/?page=accounting.push_customer">
  <input type="hidden" name="shipment_id" value="<?= $shipment['id'] ?>">
</form>

<script>
let ktIdx = <?= $ktIdx ?? 0 ?>;

// ── Thêm dòng KT tay ──
function addKtRow() {
  const emptyRow = document.getElementById('ktEmptyRow');
  if (emptyRow) emptyRow.remove();

  const tbody = document.getElementById('ktBody');
  const tr = document.createElement('tr');
  tr.className = 'kt-row';
  tr.dataset.source = 'kt';
  tr.innerHTML = `
    <td>
      <input type="text" name="kt_costs[${ktIdx}][name]"
             class="form-control form-control-sm" placeholder="Tên chi phí phát sinh">
      <input type="hidden" name="kt_costs[${ktIdx}][source]" value="kt">
    </td>
    <td>
      <input type="number" name="kt_costs[${ktIdx}][amount]"
             class="form-control form-control-sm text-end kt-amount-input"
             step="1000" min="0" oninput="calcKtTotal()" placeholder="0">
    </td>
    <td class="text-center"><span class="badge bg-info">KT</span></td>
    <td class="text-center">
      <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeKtRow(this)">✕</button>
    </td>`;
  tbody.appendChild(tr);
  ktIdx++;
  tr.querySelector('input[type=text]').focus();
  calcKtTotal();
}

// ── Toggle chọn từ quotation ──
function toggleQuotationItem(cb) {
  const desc   = cb.dataset.desc;
  const amount = parseFloat(cb.dataset.amount) || 0;
  const tbody  = document.getElementById('ktBody');

  if (cb.checked) {
    const emptyRow = document.getElementById('ktEmptyRow');
    if (emptyRow) emptyRow.remove();

    const tr = document.createElement('tr');
    tr.className = 'kt-row';
    tr.dataset.source = 'quotation';
    tr.dataset.qtDesc = desc;
    tr.innerHTML = `
      <td>
        <input type="text" name="kt_costs[${ktIdx}][name]"
               class="form-control form-control-sm"
               value="${escHtml(desc)}" readonly style="background:#f0fdf4">
        <input type="hidden" name="kt_costs[${ktIdx}][source]" value="quotation">
      </td>
      <td>
        <input type="number" name="kt_costs[${ktIdx}][amount]"
               class="form-control form-control-sm text-end kt-amount-input"
               value="${amount}" step="1000" min="0" oninput="calcKtTotal()">
      </td>
      <td class="text-center"><span class="badge bg-success">BG</span></td>
      <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger p-1"
                onclick="removeKtRow(this)">✕</button>
      </td>`;
    tbody.appendChild(tr);
    ktIdx++;
  } else {
    tbody.querySelectorAll('.kt-row').forEach(row => {
      if (row.dataset.qtDesc === desc) row.remove();
    });
    if (tbody.querySelectorAll('.kt-row').length === 0) {
      const empty = document.createElement('tr');
      empty.id = 'ktEmptyRow';
      empty.innerHTML = '<td colspan="4" class="text-center text-muted small py-3">Chưa có chi phí — chọn từ báo giá hoặc thêm tay bên dưới</td>';
      tbody.appendChild(empty);
    }
  }
  calcKtTotal();
}

function removeKtRow(btn) {
  const tr = btn.closest('.kt-row');
  if (tr.dataset.source === 'quotation' && tr.dataset.qtDesc) {
    document.querySelectorAll('.qt-checkbox').forEach(cb => {
      if (cb.dataset.desc === tr.dataset.qtDesc) cb.checked = false;
    });
  }
  tr.remove();
  const tbody = document.getElementById('ktBody');
  if (tbody.querySelectorAll('.kt-row').length === 0) {
    const empty = document.createElement('tr');
    empty.id = 'ktEmptyRow';
    empty.innerHTML = '<td colspan="4" class="text-center text-muted small py-3">Chưa có chi phí — chọn từ báo giá hoặc thêm tay bên dưới</td>';
    tbody.appendChild(empty);
  }
  calcKtTotal();
}

function calcOpsTotal() {
  let total = 0;
  document.querySelectorAll('.ops-amount-input').forEach(i => total += parseFloat(i.value) || 0);
  const el = document.getElementById('opsTotalDisplay');
  if (el) el.textContent = new Intl.NumberFormat('vi-VN').format(total) + ' đ';
}

function calcKtTotal() {
  let total = 0;
  document.querySelectorAll('.kt-amount-input').forEach(i => total += parseFloat(i.value) || 0);
  document.getElementById('ktTotalDisplay').textContent =
    new Intl.NumberFormat('vi-VN').format(total) + ' đ';
}

function escHtml(str) {
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function saveCosts(pushToCustomer) {
  const form    = document.getElementById('costsForm');
  const formData = new FormData(form);
  const saveBtn = document.querySelector('[onclick="saveCosts(false)"]');
  const pushBtn = document.querySelector('[onclick="saveCosts(true)"]');
  if (saveBtn) { saveBtn.disabled = true; saveBtn.innerHTML = '⏳ Đang lưu...'; }
  if (pushBtn) { pushBtn.disabled = true; }

  fetch('<?= BASE_URL ?>/?page=accounting.save_costs', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = '💾 Lưu chi phí'; }
    if (pushBtn) { pushBtn.disabled = false; }

    if (data.success) {
      if (pushToCustomer) {
        document.getElementById('pushForm').submit();
      } else {
        updateQuotationRef(data.kt_costs, data.kt_total);

        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible';
        alert.style.cssText = 'border-radius:10px;position:fixed;top:70px;right:20px;z-index:9999;min-width:280px';
        alert.innerHTML = '✅ Đã lưu chi phí! <button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
      }
    } else {
      alert(data.message || 'Lỗi lưu chi phí!');
    }
  })
  .catch(() => {
    if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = '💾 Lưu chi phí'; }
    if (pushBtn) { pushBtn.disabled = false; }
    alert('Lỗi kết nối!');
  });
}

function updateQuotationRef(ktCosts, ktTotal) {
  const body = document.getElementById('quotationRefBody');
  if (!body) return;
  if (!ktCosts || ktCosts.length === 0) {
    body.innerHTML = '<div class="text-center py-3 text-muted small">Chưa có chi phí nào</div>';
    return;
  }
  let html = '<table class="table table-sm mb-0" style="font-size:0.82rem">';
  ktCosts.forEach(c => {
    html += `<tr>
      <td class="ps-3 text-muted">${escHtml(c.cost_name)}</td>
      <td class="text-end pe-3 fw-semibold">${new Intl.NumberFormat('vi-VN').format(c.amount)} đ</td>
    </tr>`;
  });
  html += `<tr style="background:#f0fdf4">
    <td class="ps-3 fw-bold">Tổng</td>
    <td class="text-end pe-3 fw-bold text-success">${new Intl.NumberFormat('vi-VN').format(ktTotal)} đ</td>
  </tr></table>`;
  body.innerHTML = html;
}

// Init
calcOpsTotal();
calcKtTotal();
</script>