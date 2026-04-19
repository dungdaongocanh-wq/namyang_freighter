<?php
$dateFrom   = $_GET['date_from']   ?? '';
$dateTo     = $_GET['date_to']     ?? '';
$month      = $_GET['month']       ?? '';
$customerId = (int)($_GET['customer_id'] ?? 0) ?: null;
$filtered   = $dateFrom || $dateTo || $customerId;

// Check ungrouped
$hasUngrouped = false;
$sumUngrouped = 0;
foreach ($shipments as $s) {
    $ug = (float)($costsByShipment[$s['id']]['ungrouped'] ?? 0);
    if ($ug > 0) { $hasUngrouped = true; $sumUngrouped += $ug; }
}
?>

<!-- Filter bar -->
<div class="card mb-4" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-body py-3 px-4">
    <form method="GET" action="" class="row g-2 align-items-end" id="filterForm">
      <input type="hidden" name="page" value="report.shipment">

      <div class="col-md-2">
        <label class="form-label small fw-semibold mb-1">Từ ngày</label>
        <input type="date" name="date_from" id="dateFrom" class="form-control form-control-sm"
               value="<?= htmlspecialchars($dateFrom) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold mb-1">Đến ngày</label>
        <input type="date" name="date_to" id="dateTo" class="form-control form-control-sm"
               value="<?= htmlspecialchars($dateTo) ?>">
      </div>
      <div class="col-md-auto d-flex gap-1 align-items-end">
        <button type="button" class="btn btn-outline-secondary btn-sm"
                onclick="setWeek('this')">Tuần này</button>
        <button type="button" class="btn btn-outline-secondary btn-sm"
                onclick="setWeek('last')">Tuần trước</button>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold mb-1">Tháng</label>
        <select name="month" id="monthSelect" class="form-select form-select-sm"
                onchange="fillMonthDates(this.value)">
          <option value="">-- Chọn tháng --</option>
          <?php foreach ($months as $ym): ?>
          <option value="<?= $ym ?>" <?= $month === $ym ? 'selected' : '' ?>>
            <?= date('m/Y', strtotime($ym . '-01')) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold mb-1">Khách hàng</label>
        <select name="customer_id" class="form-select form-select-sm">
          <option value="">-- Tất cả --</option>
          <?php foreach ($customers as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $customerId == $c['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['customer_code']) ?> — <?= htmlspecialchars($c['company_name'] ?? '') ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-auto d-flex gap-2 align-items-end">
        <button type="submit" class="btn btn-primary btn-sm px-3">🔍 Lọc</button>
        <a href="<?= BASE_URL ?>/?page=report.shipment" class="btn btn-outline-secondary btn-sm">↺ Reset</a>
        <?php if ($filtered): ?>
        <button type="button" class="btn btn-success btn-sm px-3"
                onclick="exportExcel()">📥 Export Excel</button>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Bảng dữ liệu -->
<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-header bg-white py-3 px-4 d-flex align-items-center gap-2"
       style="border-radius:12px 12px 0 0">
    <h6 class="mb-0 fw-bold">
      <i class="bi bi-file-earmark-bar-graph"></i> Báo cáo Lô hàng đã xác nhận
    </h6>
    <?php if ($filtered): ?>
    <span class="badge bg-primary ms-1"><?= count($shipments) ?></span>
    <?php if ($dateFrom && $dateTo): ?>
    <span class="text-muted small ms-2">
      <?= date('d/m/Y', strtotime($dateFrom)) ?> – <?= date('d/m/Y', strtotime($dateTo)) ?>
    </span>
    <?php endif; ?>
    <?php endif; ?>
  </div>
  <div class="card-body p-0">
    <?php if (!$filtered): ?>
    <div class="text-center text-muted py-5">
      <div style="font-size:2.5rem">📊</div>
      <p class="mt-2">Vui lòng chọn khoảng thời gian hoặc khách hàng để xem báo cáo</p>
    </div>
    <?php elseif (empty($shipments)): ?>
    <div class="text-center text-muted py-5">
      <div style="font-size:2.5rem">🔍</div>
      <p class="mt-2">Không có lô hàng nào trong khoảng thời gian đã chọn</p>
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
            <?php if ($hasUngrouped): ?>
            <th class="text-end" style="min-width:90px">OTHER FEE</th>
            <?php endif; ?>
            <th class="text-end" style="min-width:90px;background:#163058">TOTAL</th>
            <th style="min-width:100px">NOTE</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $no         = 1;
          $sumByGroup = [];
          $sumTotal   = 0;
          foreach ($shipments as $s):
            $sid   = $s['id'];
            $total = 0;
            foreach ($costGroups as $cg) $total += (float)($costsByShipment[$sid][$cg['id']] ?? 0);
            $ug     = (float)($costsByShipment[$sid]['ungrouped'] ?? 0);
            $total += $ug;
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
            <?php if ($hasUngrouped): ?>
            <td class="text-end">
              <?= $ug > 0 ? number_format($ug) : '<span class="text-muted">-</span>' ?>
            </td>
            <?php endif; ?>
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
            <td class="text-end text-primary"><?= number_format($sumByGroup[$cg['id']] ?? 0) ?></td>
            <?php endforeach; ?>
            <?php if ($hasUngrouped): ?>
            <td class="text-end text-primary"><?= number_format($sumUngrouped) ?></td>
            <?php endif; ?>
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
<form id="exportForm" method="GET" action="" target="_blank">
  <input type="hidden" name="page"        value="report.shipment">
  <input type="hidden" name="export"      value="1">
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

function setWeek(type) {
  const today  = new Date();
  const dow    = today.getDay() === 0 ? 7 : today.getDay();
  const monday = new Date(today);
  monday.setDate(today.getDate() - dow + 1);
  if (type === 'last') monday.setDate(monday.getDate() - 7);
  const sunday = new Date(monday);
  sunday.setDate(monday.getDate() + 6);
  const fmt = d => d.toISOString().slice(0, 10);
  document.getElementById('dateFrom').value = fmt(monday);
  document.getElementById('dateTo').value   = fmt(sunday);
  document.getElementById('monthSelect').value = '';
  document.getElementById('filterForm').submit();
}

function exportExcel() {
  const data = new FormData(document.getElementById('filterForm'));
  document.getElementById('expDateFrom').value   = data.get('date_from')   || '';
  document.getElementById('expDateTo').value     = data.get('date_to')     || '';
  document.getElementById('expMonth').value      = data.get('month')       || '';
  document.getElementById('expCustomerId').value = data.get('customer_id') || '';
  document.getElementById('exportForm').submit();
}
</script>
