<?php
$totalAmount  = array_sum(array_column($items, 'amount'));
$totalVat     = array_sum(array_map(fn($i) => $i['amount'] * $i['vat_pct'] / 100, $items));
?>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success alert-dismissible mb-3" style="border-radius:10px">
  ✅ Đã lưu báo giá! <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Header info -->
<div class="card mb-4" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-body">
    <form method="POST" action="<?= BASE_URL ?>/?page=admin.save_quotation" id="quotationForm">
      <input type="hidden" name="id" value="<?= $quotation['id'] ?>">

      <div class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Khách hàng</label>
          <select name="customer_id" class="form-select" required>
            <?php foreach ($customers as $c): ?>
            <option value="<?= $c['id'] ?>"
              <?= $c['id'] == $quotation['customer_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['customer_code']) ?> — <?= htmlspecialchars($c['company_name'] ?? '') ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Tên báo giá</label>
          <input type="text" name="name" class="form-control"
                 value="<?= htmlspecialchars($quotation['name'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">Hiệu lực từ</label>
          <input type="date" name="valid_from" class="form-control"
                 value="<?= $quotation['valid_from'] ?? '' ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">Đến ngày</label>
          <input type="date" name="valid_to" class="form-control"
                 value="<?= $quotation['valid_to'] ?? '' ?>">
        </div>
        <div class="col-md-1">
          <label class="form-label small fw-semibold">Active</label>
          <select name="is_active" class="form-select">
            <option value="1" <?= $quotation['is_active'] ? 'selected' : '' ?>>✓</option>
            <option value="0" <?= !$quotation['is_active'] ? 'selected' : '' ?>>✗</option>
          </select>
        </div>
        <div class="col-md-1">
          <button type="button" onclick="window.print()"
                  class="btn btn-outline-secondary w-100">🖨️</button>
        </div>
      </div>

      <?php if ($quotation['note'] ?? ''): ?>
      <div class="mt-2">
        <input type="text" name="note" class="form-control form-control-sm"
               value="<?= htmlspecialchars($quotation['note']) ?>"
               placeholder="Ghi chú báo giá...">
      </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Bảng chi tiết phí -->
<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center"
       style="border-radius:12px 12px 0 0">
    <h6 class="mb-0 fw-bold">CHI TIẾT BÁO GIÁ / QUOTATION DETAILS</h6>
    <button type="button" onclick="addRow()"
            class="btn btn-sm btn-outline-primary">+ Thêm dòng</button>
  </div>

  <div class="card-body p-0">
    <div class="table-responsive" id="printArea">
      <table class="table table-bordered mb-0 align-middle" id="itemsTable"
             style="font-size:0.82rem;min-width:900px">
        <thead style="background:#1e3a5f;color:#fff">
          <tr>
            <th class="ps-3" style="width:35%">Diễn giải / Description</th>
            <th style="width:7%" class="text-center">Tiền tệ</th>
            <th style="width:12%" class="text-end">Đơn giá</th>
            <th style="width:6%" class="text-center">SL</th>
            <th style="width:12%" class="text-end">Thành tiền</th>
            <th style="width:6%" class="text-center">VAT%</th>
            <th style="width:17%">Ghi chú</th>
            <th style="width:5%" class="text-center no-print">Xóa</th>
          </tr>
        </thead>
        <tbody id="itemsBody">
          <?php foreach ($items as $i => $item): ?>
          <tr class="item-row">
            <td class="ps-2">
              <input type="text" name="desc[]"
                     class="form-control form-control-sm border-0"
                     value="<?= htmlspecialchars($item['description']) ?>"
                     placeholder="Diễn giải..." style="min-width:200px">
            </td>
            <td>
              <select name="currency[]" class="form-select form-select-sm border-0 text-center">
                <option value="VND" <?= $item['currency']==='VND'?'selected':'' ?>>VND</option>
                <option value="USD" <?= $item['currency']==='USD'?'selected':'' ?>>USD</option>
              </select>
            </td>
            <td>
              <input type="number" name="unit_price[]"
                     class="form-control form-control-sm border-0 text-end unit-price"
                     value="<?= $item['unit_price'] ?>"
                     step="1000" min="0" oninput="calcRow(this)">
            </td>
            <td>
              <input type="number" name="quantity[]"
                     class="form-control form-control-sm border-0 text-center qty"
                     value="<?= $item['quantity'] ?>"
                     step="0.5" min="0" oninput="calcRow(this)">
            </td>
            <td>
              <input type="number" name="amount[]"
                     class="form-control form-control-sm border-0 text-end row-amount"
                     value="<?= $item['amount'] ?>" readonly
                     style="background:transparent;font-weight:600">
            </td>
            <td>
              <input type="number" name="vat_pct[]"
                     class="form-control form-control-sm border-0 text-center"
                     value="<?= $item['vat_pct'] ?>" min="0" max="100">
            </td>
            <td>
              <input type="text" name="item_note[]"
                     class="form-control form-control-sm border-0"
                     value="<?= htmlspecialchars($item['note'] ?? '') ?>"
                     placeholder="Ghi chú...">
            </td>
            <td class="text-center no-print">
              <button type="button" class="btn btn-sm btn-outline-danger p-1 px-2"
                      onclick="removeRow(this)">✕</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>

        <tfoot>
          <!-- Tổng chưa VAT -->
          <tr style="background:#f8fafc;font-weight:600">
            <td colspan="4" class="text-end pe-3 ps-3">Tổng cộng (chưa VAT):</td>
            <td class="text-end pe-2 text-success" id="subtotalDisplay">
              <?= number_format($totalAmount) ?>
            </td>
            <td colspan="3"></td>
          </tr>
          <!-- VAT -->
          <tr style="background:#f8fafc">
            <td colspan="4" class="text-end pe-3 ps-3 small text-muted">VAT (ước tính 8%):</td>
            <td class="text-end pe-2 small text-muted" id="vatDisplay">
              <?= number_format($totalVat) ?>
            </td>
            <td colspan="3"></td>
          </tr>
          <!-- Tổng + VAT -->
          <tr style="background:#e8f4fd;font-weight:700;font-size:0.95rem">
            <td colspan="4" class="text-end pe-3 ps-3" style="color:#1e3a5f">
              TỔNG THANH TOÁN (đã VAT):
            </td>
            <td class="text-end pe-2" style="color:#15803d;font-size:1.05rem" id="grandTotalDisplay">
              <?= number_format($totalAmount + $totalVat) ?>
            </td>
            <td colspan="3"></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <!-- Save button -->
  <div class="card-footer bg-white px-4 py-3 d-flex gap-2" style="border-radius:0 0 12px 12px">
    <button type="button" onclick="saveAll()"
            class="btn btn-primary px-5 fw-semibold">
      💾 Lưu báo giá
    </button>
    <a href="<?= BASE_URL ?>/?page=admin.quotation"
       class="btn btn-outline-secondary px-4">← Quay lại</a>
    <button type="button" onclick="window.print()"
            class="btn btn-outline-secondary px-4 ms-auto">🖨️ In PDF</button>
  </div>
</div>

<script>
// Tính thành tiền 1 dòng
function calcRow(el) {
  const row    = el.closest('.item-row');
  const price  = parseFloat(row.querySelector('.unit-price').value) || 0;
  const qty    = parseFloat(row.querySelector('.qty').value)        || 0;
  const amount = price * qty;
  row.querySelector('.row-amount').value = amount;
  calcTotals();
}

// Tính tổng footer
function calcTotals() {
  let subtotal = 0, vat = 0;
  document.querySelectorAll('.item-row').forEach(row => {
    const amount = parseFloat(row.querySelector('.row-amount').value) || 0;
    const vatPct = parseFloat(row.querySelector('input[name="vat_pct[]"]').value) || 0;
    subtotal += amount;
    vat      += amount * vatPct / 100;
  });
  const fmt = n => new Intl.NumberFormat('vi-VN').format(Math.round(n));
  document.getElementById('subtotalDisplay').textContent  = fmt(subtotal);
  document.getElementById('vatDisplay').textContent       = fmt(vat);
  document.getElementById('grandTotalDisplay').textContent = fmt(subtotal + vat);
}

// Thêm dòng mới
function addRow() {
  const tbody = document.getElementById('itemsBody');
  const tr    = document.createElement('tr');
  tr.className = 'item-row';
  tr.innerHTML = `
    <td class="ps-2">
      <input type="text" name="desc[]"
             class="form-control form-control-sm border-0"
             placeholder="Diễn giải dịch vụ..." style="min-width:200px">
    </td>
    <td>
      <select name="currency[]" class="form-select form-select-sm border-0 text-center">
        <option value="VND">VND</option>
        <option value="USD">USD</option>
      </select>
    </td>
    <td>
      <input type="number" name="unit_price[]"
             class="form-control form-control-sm border-0 text-end unit-price"
             step="1000" min="0" value="0" oninput="calcRow(this)">
    </td>
    <td>
      <input type="number" name="quantity[]"
             class="form-control form-control-sm border-0 text-center qty"
             step="0.5" min="0" value="1" oninput="calcRow(this)">
    </td>
    <td>
      <input type="number" name="amount[]"
             class="form-control form-control-sm border-0 text-end row-amount"
             value="0" readonly style="background:transparent;font-weight:600">
    </td>
    <td>
      <input type="number" name="vat_pct[]"
             class="form-control form-control-sm border-0 text-center"
             value="8" min="0" max="100">
    </td>
    <td>
      <input type="text" name="item_note[]"
             class="form-control form-control-sm border-0" placeholder="Ghi chú...">
    </td>
    <td class="text-center no-print">
      <button type="button" class="btn btn-sm btn-outline-danger p-1 px-2"
              onclick="removeRow(this)">✕</button>
    </td>`;
  tbody.appendChild(tr);
  tr.querySelector('input[name="desc[]"]').focus();
}

// Xóa dòng
function removeRow(btn) {
  btn.closest('.item-row').remove();
  calcTotals();
}

// Lưu toàn bộ
function saveAll() {
  // Submit form data qua form chính
  const mainForm  = document.getElementById('quotationForm');
  const itemTable = document.getElementById('itemsTable');

  // Copy inputs từ table vào form
  itemTable.querySelectorAll('input, select').forEach(el => {
    if (el.name) {
      const hidden = document.createElement('input');
      hidden.type  = 'hidden';
      hidden.name  = el.name;
      hidden.value = el.value;
      mainForm.appendChild(hidden);
    }
  });

  mainForm.submit();
}

// Print styles
const style = document.createElement('style');
style.textContent = `
  @media print {
    #topbar, #sidebar, .card-footer, .no-print,
    .btn, nav, .page-header { display:none !important; }
    #main-content { margin:0 !important; padding:0 !important; }
    .card { box-shadow:none !important; border:none !important; }
    table { font-size:0.75rem !important; }
    thead { background:#1e3a5f !important; -webkit-print-color-adjust:exact; }
  }
`;
document.head.appendChild(style);
</script>