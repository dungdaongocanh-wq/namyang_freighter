<!-- Month filter -->
<div class="card mb-4" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-body py-3">
    <form method="GET" action="<?= BASE_URL ?>/" class="d-flex gap-3 align-items-center flex-wrap">
      <input type="hidden" name="page" value="accounting.debt">
      <label class="fw-semibold small mb-0">📅 Tháng:</label>
      <select name="month" class="form-select form-select-sm" style="width:auto"
              onchange="this.form.submit()">
        <?php foreach ($months as $m): ?>
        <option value="<?= $m ?>" <?= $m===$month?'selected':'' ?>>
          <?= date('m/Y', strtotime($m.'-01')) ?>
        </option>
        <?php endforeach; ?>
        <?php if (empty($months)): ?>
        <option value="<?= date('Y-m') ?>">Tháng <?= date('m/Y') ?></option>
        <?php endif; ?>
      </select>
      <span class="text-muted small">
        Tổng: <strong class="text-success"><?= number_format($totalDebt) ?> đ</strong>
      </span>
    </form>
  </div>
</div>

<!-- Debt table -->
<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-header bg-white py-3 px-4" style="border-radius:12px 12px 0 0">
    <h6 class="mb-0 fw-bold">
      💰 Công nợ tháng <?= date('m/Y', strtotime($month.'-01')) ?>
      <span class="badge bg-success ms-1"><?= count($debtByCustomer) ?> khách hàng</span>
    </h6>
  </div>
  <div class="card-body p-0">
    <?php if (empty($debtByCustomer)): ?>
    <div class="text-center text-muted py-5">
      <div style="font-size:3rem">📭</div>
      <p class="mt-2">Không có công nợ trong tháng này!</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle" style="font-size:0.85rem">
        <thead style="background:#f8fafc;color:#64748b;font-size:0.78rem">
          <tr>
            <th class="ps-4">Khách hàng</th>
            <th>Số lô</th>
            <th>Từ ngày</th>
            <th>Đến ngày</th>
            <th class="text-end">Tổng công nợ</th>
            <th class="text-center">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($debtByCustomer as $d): ?>
          <tr>
            <td class="ps-4">
              <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($d['customer_code']) ?></span>
              <span class="small"><?= htmlspecialchars($d['company_name'] ?? '') ?></span>
            </td>
            <td><span class="badge bg-primary"><?= $d['shipment_count'] ?> lô</span></td>
            <td class="small"><?= date('d/m/Y', strtotime($d['from_date'])) ?></td>
            <td class="small"><?= date('d/m/Y', strtotime($d['to_date'])) ?></td>
            <td class="text-end fw-bold text-success fs-6">
              <?= number_format($d['total_amount']) ?> đ
            </td>
            <td class="text-center pe-3">
              <div class="d-flex gap-1 justify-content-center">
                <a href="<?= BASE_URL ?>/?page=accounting.invoice&customer_id=<?= $d['customer_id'] ?>&month=<?= $month ?>"
                   class="btn btn-sm btn-outline-primary">🧾 Hoá đơn</a>
                <button class="btn btn-sm btn-success"
                        onclick="closeMonth(<?= $d['customer_id'] ?>, '<?= $month ?>', '<?= htmlspecialchars($d['company_name'] ?? $d['customer_code']) ?>')">
                  ✅ Chốt
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot style="background:#f0fdf4">
          <tr>
            <td class="ps-4 fw-bold" colspan="4">TỔNG CỘNG</td>
            <td class="text-end fw-bold text-success fs-5"><?= number_format($totalDebt) ?> đ</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
function closeMonth(customerId, month, name) {
  if (!confirm(`Chốt công nợ tháng ${month} cho "${name}"?\n\nThao tác này sẽ chuyển tất cả lô sang trạng thái "Đã xuất HĐ".`)) return;

  fetch('<?= BASE_URL ?>/?page=accounting.close_month', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `customer_id=${customerId}&month=${month}`
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      // Toast
      const toast = document.createElement('div');
      toast.className = 'alert alert-success';
      toast.style.cssText = 'position:fixed;top:70px;right:20px;z-index:9999;min-width:280px;border-radius:10px';
      toast.textContent = `✅ Đã chốt ${d.closed} lô!`;
      document.body.appendChild(toast);
      setTimeout(() => { toast.remove(); location.reload(); }, 1500);
    }
  });
}
</script>