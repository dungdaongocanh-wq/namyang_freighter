<?php
$role = $_SESSION['role'] ?? '';
?>

<!-- Filter bar -->
<div class="card mb-4" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-body py-3 px-4">
    <form method="GET" action="" class="row g-2 align-items-end" id="filterForm">
      <input type="hidden" name="page" value="statement.index">

      <?php if (in_array($role, ['cs', 'admin'])): ?>
      <div class="col-md-3">
        <label class="form-label small fw-semibold mb-1">Khách hàng</label>
        <select name="customer_id" class="form-select form-select-sm">
          <option value="">-- Tất cả khách hàng --</option>
          <?php foreach ($customers as $c): ?>
          <option value="<?= $c['id'] ?>"
            <?= (isset($_GET['customer_id']) && $_GET['customer_id'] == $c['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['customer_code']) ?> — <?= htmlspecialchars($c['company_name'] ?? '') ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <div class="col-md-2">
        <label class="form-label small fw-semibold mb-1">Từ ngày</label>
        <input type="date" name="date_from" class="form-control form-control-sm"
               value="<?= htmlspecialchars($dateFrom ?? '') ?>" id="dateFrom">
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold mb-1">Đến ngày</label>
        <input type="date" name="date_to" class="form-control form-control-sm"
               value="<?= htmlspecialchars($dateTo ?? '') ?>" id="dateTo">
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold mb-1">Tháng</label>
        <select name="month" class="form-select form-select-sm" id="monthSelect"
                onchange="fillMonthDates(this.value)">
          <option value="">-- Chọn tháng --</option>
          <?php foreach ($months as $ym): ?>
          <option value="<?= $ym ?>" <?= ($month ?? '') === $ym ? 'selected' : '' ?>>
            <?= $ym ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-auto d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm px-3">🔍 Lọc</button>
        <a href="<?= BASE_URL ?>/?page=statement.index" class="btn btn-outline-secondary btn-sm">↺ Reset</a>
      </div>
      <div class="col-md-auto">
        <button type="button" class="btn btn-success btn-sm px-3"
                onclick="exportExcel()">📥 Export Excel</button>
      </div>
    </form>
  </div>
</div>

<!-- Bảng kê -->
<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-header bg-white py-3 px-4 d-flex align-items-center gap-2"
       style="border-radius:12px 12px 0 0">
    <h6 class="mb-0 fw-bold">📊 Bảng kê chi tiết chi phí</h6>
    <span class="badge bg-primary ms-1"><?= count($shipments) ?></span>
    <?php if ($dateFrom && $dateTo): ?>
    <span class="text-muted small ms-2">
      <?= date('d/m/Y', strtotime($dateFrom)) ?> – <?= date('d/m/Y', strtotime($dateTo)) ?>
    </span>
    <?php endif; ?>
  </div>
  <div class="card-body p-0">
    <?php if (empty($shipments)): ?>
    <div class="text-center text-muted py-5">
      <div style="font-size:2.5rem">📋</div>
      <p class="mt-2">Không có dữ liệu — vui lòng chọn khoảng thời gian</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle mb-0" style="font-size:0.82rem">
        <thead style="background:#1e3a5f;color:#fff;font-size:0.78rem">
          <tr>
            <th class="text-center" style="width:40px">NO</th>
            <th style="width:85px">DATE</th>
            <th>CONSIGNEE</th>
            <th>HAWB</th>
            <th>CD NO.</th>
            <th class="text-center" style="width:55px">PKG</th>
            <th class="text-end" style="width:75px">GW (KG)</th>
            <?php foreach ($costGroups as $cg): ?>
            <th class="text-end" style="min-width:90px"><?= htmlspecialchars(strtoupper($cg['name'])) ?></th>
            <?php endforeach; ?>
            <th class="text-end" style="min-width:90px;background:#163058">TOTAL</th>
            <th style="min-width:100px">NOTE</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $no = 1;
          $sumByGroup  = [];
          $sumTotal    = 0;

          foreach ($shipments as $s):
            $sid   = $s['id'];
            $total = 0;

            // Tính total cho dòng này
            foreach ($costGroups as $cg) {
                $total += (float)($costsByShipment[$sid][$cg['id']] ?? 0);
            }
            $total += (float)($costsByShipment[$sid]['ungrouped'] ?? 0);

            // Cộng vào sum
            foreach ($costGroups as $cg) {
                if (!isset($sumByGroup[$cg['id']])) $sumByGroup[$cg['id']] = 0;
                $sumByGroup[$cg['id']] += (float)($costsByShipment[$sid][$cg['id']] ?? 0);
            }
            $sumTotal += $total;
          ?>
          <tr>
            <td class="text-center"><?= $no++ ?></td>
            <td><?= $s['active_date'] ? date('d/m/Y', strtotime($s['active_date'])) : '-' ?></td>
            <td class="fw-semibold"><?= htmlspecialchars($s['company_name'] ?? '') ?></td>
            <td class="fw-semibold text-primary"><?= htmlspecialchars($s['hawb']) ?></td>
            <td class="text-muted small"><?= htmlspecialchars($s['cd_numbers'] ?? '-') ?></td>
            <td class="text-center"><?= (int)$s['packages'] ?></td>
            <td class="text-end"><?= number_format((float)$s['weight'], 1) ?></td>
            <?php foreach ($costGroups as $cg): ?>
            <td class="text-end">
              <?php $amt = (float)($costsByShipment[$sid][$cg['id']] ?? 0); ?>
              <?= $amt > 0 ? number_format($amt) : '<span class="text-muted">-</span>' ?>
            </td>
            <?php endforeach; ?>
            <td class="text-end fw-semibold text-success">
              <?= $total > 0 ? number_format($total) : '<span class="text-muted">-</span>' ?>
            </td>
            <td class="text-muted small"><?= htmlspecialchars($s['remark'] ?? '') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot style="background:#f0f7ff;font-weight:700;font-size:0.82rem">
          <tr>
            <td colspan="7" class="text-end pe-3">TỔNG CỘNG:</td>
            <?php foreach ($costGroups as $cg): ?>
            <td class="text-end text-primary">
              <?= number_format($sumByGroup[$cg['id']] ?? 0) ?>
            </td>
            <?php endforeach; ?>
            <td class="text-end text-success"><?= number_format($sumTotal) ?></td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Hidden export form -->
<form id="exportForm" method="GET" action="<?= BASE_URL ?>/?page=statement.export" target="_blank">
  <input type="hidden" name="page" value="statement.export">
  <input type="hidden" name="date_from"   id="expDateFrom">
  <input type="hidden" name="date_to"     id="expDateTo">
  <input type="hidden" name="month"       id="expMonth">
  <input type="hidden" name="customer_id" id="expCustomerId">
</form>

<script>
function fillMonthDates(ym) {
  if (!ym) return;
  const [y, m] = ym.split('-');
  const lastDay = new Date(y, m, 0).getDate();
  document.getElementById('dateFrom').value = `${ym}-01`;
  document.getElementById('dateTo').value   = `${ym}-${String(lastDay).padStart(2,'0')}`;
}

function exportExcel() {
  const filterForm = document.getElementById('filterForm');
  const data       = new FormData(filterForm);

  document.getElementById('expDateFrom').value   = data.get('date_from')   || '';
  document.getElementById('expDateTo').value     = data.get('date_to')     || '';
  document.getElementById('expMonth').value      = data.get('month')       || '';
  document.getElementById('expCustomerId').value = data.get('customer_id') || '';

  document.getElementById('exportForm').submit();
}
</script>
