<!-- Summary card -->
<div class="card mb-4"
     style="border:none;border-radius:12px;background:linear-gradient(135deg,#15803d,#16a34a);color:#fff">
  <div class="card-body py-3 px-4">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <p class="mb-1 opacity-75 small">TỔNG CÔNG NỢ HIỆN TẠI</p>
        <h3 class="fw-bold mb-0"><?= number_format($totalAllDebt) ?> đ</h3>
      </div>
      <span style="font-size:3rem;opacity:0.4">💳</span>
    </div>
  </div>
</div>

<!-- Month filter -->
<div class="card mb-4" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-body py-3">
    <form method="GET" action="<?= BASE_URL ?>/" class="d-flex gap-3 align-items-center flex-wrap">
      <input type="hidden" name="page" value="customer.debt">
      <label class="fw-semibold small mb-0">📅 Tháng:</label>
      <select name="month" class="form-select form-select-sm" style="width:auto"
              onchange="this.form.submit()">
        <?php foreach ($months as $m): ?>
        <option value="<?= $m ?>" <?= $m===$month?'selected':'' ?>>
          Tháng <?= date('m/Y', strtotime($m.'-01')) ?>
        </option>
        <?php endforeach; ?>
        <?php if (empty($months)): ?>
        <option value="<?= date('Y-m') ?>">Tháng <?= date('m/Y') ?></option>
        <?php endif; ?>
      </select>
      <span class="text-muted small">
        Tháng này: <strong class="text-success"><?= number_format($totalDebt) ?> đ</strong>
      </span>
    </form>
  </div>
</div>

<!-- Debt table -->
<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-header bg-white py-3 px-4" style="border-radius:12px 12px 0 0">
    <h6 class="mb-0 fw-bold">
      💰 Công nợ tháng <?= date('m/Y', strtotime($month.'-01')) ?>
      <span class="badge bg-primary ms-1"><?= count($debtShipments) ?> lô</span>
    </h6>
  </div>
  <div class="card-body p-0">
    <?php if (empty($debtShipments)): ?>
    <div class="text-center text-muted py-5">
      <div style="font-size:3rem">🎉</div>
      <p class="mt-2">Không có công nợ trong tháng này!</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle" style="font-size:0.85rem">
        <thead style="background:#f8fafc;color:#64748b;font-size:0.78rem">
          <tr>
            <th class="ps-4">HAWB</th>
            <th>Flight</th>
            <th>Kiện / KG</th>
            <th>Active date</th>
            <th>Trạng thái</th>
            <th class="text-end pe-4">Chi phí</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($debtShipments as $s): ?>
          <tr style="cursor:pointer"
              onclick="location.href='<?= BASE_URL ?>/?page=customer.shipment_detail&id=<?= $s['id'] ?>'">
            <td class="ps-4 fw-semibold text-primary"><?= htmlspecialchars($s['hawb']) ?></td>
            <td class="small"><?= htmlspecialchars($s['flight_no'] ?? '-') ?></td>
            <td><?= $s['packages'] ?> / <?= number_format($s['weight'],1) ?> kg</td>
            <td><?= date('d/m/Y', strtotime($s['active_date'])) ?></td>
            <td>
              <?php if ($s['status'] === 'invoiced'): ?>
              <span class="badge bg-success">✅ Đã xuất HĐ</span>
              <?php else: ?>
              <span class="badge bg-warning text-dark">💰 Công nợ</span>
              <?php endif; ?>
            </td>
            <td class="text-end pe-4 fw-bold text-success">
              <?= number_format($s['total_cost']) ?> đ
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot style="background:#f0fdf4">
          <tr>
            <td class="ps-4 fw-bold" colspan="5">TỔNG THÁNG <?= date('m/Y', strtotime($month.'-01')) ?></td>
            <td class="text-end pe-4 fw-bold text-success fs-6">
              <?= number_format($totalDebt) ?> đ
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>