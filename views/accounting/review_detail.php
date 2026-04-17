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

  <!-- Báo giá tham chiếu -->
  <?php if ($quotation): ?>
  <div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
    <div class="card-header bg-white py-3 px-3" style="border-radius:12px 12px 0 0">
      <h6 class="mb-0 fw-bold">📋 Báo giá tham chiếu</h6>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0" style="font-size:0.8rem">
        <tr><td class="text-muted ps-3">Phí vận chuyển</td>
            <td class="text-end pe-3 fw-semibold"><?= number_format($quotation['freight_rate'] ?? 0) ?> đ/kg</td></tr>
        <tr><td class="text-muted ps-3">Phí bốc xếp</td>
            <td class="text-end pe-3 fw-semibold"><?= number_format($quotation['handling_fee'] ?? 0) ?> đ</td></tr>
        <tr><td class="text-muted ps-3">Phí khác</td>
            <td class="text-end pe-3 fw-semibold"><?= number_format($quotation['other_fee'] ?? 0) ?> đ</td></tr>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Cột phải: chi phí -->
<div class="col-lg-8">
  <div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center"
         style="border-radius:12px 12px 0 0">
      <h6 class="mb-0 fw-bold">💰 Bảng chi phí</h6>
      <button type="button" onclick="addCostRow()"
              class="btn btn-sm btn-outline-primary">+ Thêm dòng</button>
    </div>

    <form id="costsForm">
      <input type="hidden" name="shipment_id" value="<?= $shipment['id'] ?>">

      <div class="card-body pb-0">
        <div class="table-responsive">
          <table class="table align-middle mb-0" id="costsTable" style="font-size:0.9rem">
            <thead style="background:#f8fafc;color:#64748b;font-size:0.78rem">
              <tr>
                <th>Tên chi phí</th>
                <th style="width:160px" class="text-end">Số tiền (đ)</th>
                <th style="width:80px" class="text-center">Nguồn</th>
                <th style="width:50px"></th>
              </tr>
            </thead>
            <tbody id="costsBody">
              <?php foreach ($costs as $i => $c): ?>
              <tr class="cost-row">
                <td>
                  <input type="text" name="costs[<?= $i ?>][name]"
                         class="form-control form-control-sm"
                         value="<?= htmlspecialchars($c['cost_name']) ?>">
                </td>
                <td>
                  <input type="number" name="costs[<?= $i ?>][amount]"
                         class="form-control form-control-sm text-end amount-input"
                         value="<?= $c['amount'] ?>" step="1000" min="0"
                         oninput="calcTotal()">
                </td>
                <td class="text-center">
                  <span class="badge bg-<?= $c['source']==='ops'?'warning text-dark':'info' ?>">
                    <?= strtoupper($c['source']) ?>
                  </span>
                </td>
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-outline-danger p-1"
                          onclick="removeRow(this)">✕</button>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($costs)): ?>
              <?php
              $defaultNames = ['Phí vận chuyển','Phí bốc xếp','Phí lưu kho','Phí hải quan'];
              foreach ($defaultNames as $i => $dn): ?>
              <tr class="cost-row">
                <td>
                  <input type="text" name="costs[<?= $i ?>][name]"
                         class="form-control form-control-sm" value="<?= $dn ?>">
                </td>
                <td>
                  <input type="number" name="costs[<?= $i ?>][amount]"
                         class="form-control form-control-sm text-end amount-input"
                         value="" step="1000" min="0" oninput="calcTotal()">
                </td>
                <td class="text-center">
                  <span class="badge bg-info">KT</span>
                </td>
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-outline-danger p-1"
                          onclick="removeRow(this)">✕</button>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tổng + actions -->
      <div class="card-footer bg-white px-4 py-3" style="border-radius:0 0 12px 12px">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="fw-bold">TỔNG CHI PHÍ:</span>
          <span class="fw-bold fs-5 text-success" id="totalDisplay">
            <?= number_format(array_sum(array_column($costs, 'amount'))) ?> đ
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
    </form>
  </div>
</div>
</div>

<!-- Hidden push form -->
<form id="pushForm" method="POST" action="<?= BASE_URL ?>/?page=accounting.push_customer">
  <input type="hidden" name="shipment_id" value="<?= $shipment['id'] ?>">
</form>

<script>
let rowIdx = <?= max(count($costs), 4) ?>;

function addCostRow() {
  const tbody = document.getElementById('costsBody');
  const tr = document.createElement('tr');
  tr.className = 'cost-row';
  tr.innerHTML = `
    <td><input type="text" name="costs[${rowIdx}][name]"
               class="form-control form-control-sm" placeholder="Tên chi phí"></td>
    <td><input type="number" name="costs[${rowIdx}][amount]"
               class="form-control form-control-sm text-end amount-input"
               step="1000" min="0" oninput="calcTotal()" placeholder="0"></td>
    <td class="text-center"><span class="badge bg-info">KT</span></td>
    <td class="text-center">
      <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeRow(this)">✕</button>
    </td>`;
  tbody.appendChild(tr);
  rowIdx++;
  tr.querySelector('input[type=text]').focus();
}

function removeRow(btn) {
  btn.closest('.cost-row').remove();
  calcTotal();
}

function calcTotal() {
  let total = 0;
  document.querySelectorAll('.amount-input').forEach(i => {
    total += parseFloat(i.value) || 0;
  });
  document.getElementById('totalDisplay').textContent =
    new Intl.NumberFormat('vi-VN').format(total) + ' đ';
}

function saveCosts(pushToCustomer) {
  const form     = document.getElementById('costsForm');
  const formData = new FormData(form);

  fetch('<?= BASE_URL ?>/?page=accounting.save_costs', {
    method: 'POST', body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      if (pushToCustomer) {
        document.getElementById('pushForm').submit();
      } else {
        // Flash message
        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible';
        alert.style.cssText = 'border-radius:10px;position:fixed;top:70px;right:20px;z-index:9999;min-width:280px';
        alert.innerHTML = '✅ Đã lưu chi phí! <button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
      }
    }
  });
}

calcTotal();
</script>