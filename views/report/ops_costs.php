<?php
$dateFrom  = $_GET['date_from']    ?? '';
$dateTo    = $_GET['date_to']      ?? '';
$month     = $_GET['month']        ?? '';
$opsUserId = (int)($_GET['ops_user_id'] ?? 0) ?: null;
$filtered  = $dateFrom || $dateTo || $opsUserId;

// Grand total helpers
$grandTotalByGroup = [];
$grandTotalAll     = 0;
foreach ($costGroups as $cg) $grandTotalByGroup[$cg['id']] = 0;
$grandUngrouped    = 0;

// Check if any ungrouped cost exists across all data
$hasUngrouped = false;
foreach ($costsByOpsShipment as $opsId => $shipCosts) {
    foreach ($shipCosts as $sid => $costs) {
        if (!empty($costs['ungrouped'])) { $hasUngrouped = true; break 2; }
    }
}
?>

<!-- Filter bar -->
<div class="card mb-4" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-body py-3 px-4">
    <form method="GET" action="" class="row g-2 align-items-end" id="filterForm">
      <input type="hidden" name="page" value="report.ops_costs">

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
        <label class="form-label small fw-semibold mb-1">Nhân viên OPS</label>
        <select name="ops_user_id" class="form-select form-select-sm">
          <option value="">-- Tất cả --</option>
          <?php foreach ($opsUsers as $u): ?>
          <option value="<?= $u['id'] ?>" <?= $opsUserId == $u['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($u['full_name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-auto d-flex gap-2 align-items-end">
        <button type="submit" class="btn btn-primary btn-sm px-3">🔍 Lọc</button>
        <a href="<?= BASE_URL ?>/?page=report.ops_costs" class="btn btn-outline-secondary btn-sm">↺ Reset</a>
        <?php if ($filtered): ?>
        <button type="button" class="btn btn-success btn-sm px-3"
                onclick="exportExcel()">📥 Export Excel</button>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<?php if (!$filtered): ?>
<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-body text-center text-muted py-5">
    <div style="font-size:2.5rem">👷</div>
    <p class="mt-2">Vui lòng chọn khoảng thời gian hoặc nhân viên OPS để xem báo cáo</p>
  </div>
</div>
<?php elseif (empty($shipmentsByOps)): ?>
<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-body text-center text-muted py-5">
    <div style="font-size:2.5rem">🔍</div>
    <p class="mt-2">Không có dữ liệu chi phí OPS trong khoảng thời gian đã chọn</p>
  </div>
</div>
<?php else: ?>

<?php foreach ($shipmentsByOps as $opsId => $opsData):
    $opsShipments    = $opsData['shipments'];
    $opsName         = $opsData['ops_name'];
    $opsCosByGroup   = [];
    $opsUngrouped    = 0;
    $opsTotal        = 0;
    foreach ($costGroups as $cg) $opsCosByGroup[$cg['id']] = 0;

    foreach ($opsShipments as $s) {
        $sid = $s['id'];
        foreach ($costGroups as $cg) {
            $amt = (float)($costsByOpsShipment[$opsId][$sid][$cg['id']] ?? 0);
            $opsCosByGroup[$cg['id']] += $amt;
            $grandTotalByGroup[$cg['id']] += $amt;
        }
        $ug = (float)($costsByOpsShipment[$opsId][$sid]['ungrouped'] ?? 0);
        $opsUngrouped += $ug;
        $grandUngrouped += $ug;
        $rowTotal = array_sum(array_column($costGroups, 'id') ? [] : []) + $ug;
        foreach ($costGroups as $cg) {
            $rowTotal += (float)($costsByOpsShipment[$opsId][$sid][$cg['id']] ?? 0);
        }
        $opsTotal      += $rowTotal;
        $grandTotalAll += $rowTotal;
    }
?>
<div class="card mb-4" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-header py-2 px-4 d-flex align-items-center gap-2"
       style="background:#2F5496;color:#fff;border-radius:12px 12px 0 0">
    <span class="fw-bold">👷 <?= htmlspecialchars($opsName) ?></span>
    <span class="badge bg-light text-dark ms-1"><?= count($opsShipments) ?> lô</span>
  </div>
  <div class="card-body p-0">
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
          <?php $no = 1; foreach ($opsShipments as $s):
            $sid      = $s['id'];
            $rowTotal = 0;
            foreach ($costGroups as $cg) $rowTotal += (float)($costsByOpsShipment[$opsId][$sid][$cg['id']] ?? 0);
            $ug        = (float)($costsByOpsShipment[$opsId][$sid]['ungrouped'] ?? 0);
            $rowTotal += $ug;
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
              <?php $amt = (float)($costsByOpsShipment[$opsId][$sid][$cg['id']] ?? 0); ?>
              <?= $amt > 0 ? number_format($amt) : '<span class="text-muted">-</span>' ?>
            </td>
            <?php endforeach; ?>
            <?php if ($hasUngrouped): ?>
            <td class="text-end">
              <?= $ug > 0 ? number_format($ug) : '<span class="text-muted">-</span>' ?>
            </td>
            <?php endif; ?>
            <td class="text-end fw-semibold text-success">
              <?= $rowTotal > 0 ? number_format($rowTotal) : '<span class="text-muted">-</span>' ?>
            </td>
            <td class="text-muted small"><?= htmlspecialchars($s['remark'] ?? '') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot style="background:#D9E1F2;font-weight:700;font-size:0.82rem">
          <tr>
            <td colspan="7" class="text-end pe-3">Subtotal:</td>
            <?php foreach ($costGroups as $cg): ?>
            <td class="text-end text-primary"><?= number_format($opsCosByGroup[$cg['id']] ?? 0) ?></td>
            <?php endforeach; ?>
            <?php if ($hasUngrouped): ?>
            <td class="text-end text-primary"><?= number_format($opsUngrouped) ?></td>
            <?php endif; ?>
            <td class="text-end text-success"><?= number_format($opsTotal) ?></td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
<?php endforeach; ?>

<!-- Grand Total card -->
<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-header py-2 px-4"
       style="background:#1e3a5f;color:#fff;border-radius:12px 12px 0 0">
    <span class="fw-bold">📊 Grand Total — Tổng tất cả OPS</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle mb-0" style="font-size:0.82rem">
        <thead style="background:#1e3a5f;color:#fff;font-size:0.78rem">
          <tr>
            <th colspan="7" class="text-end pe-3">TỔNG CỘNG</th>
            <?php foreach ($costGroups as $cg): ?>
            <th class="text-end"><?= htmlspecialchars(strtoupper($cg['name'])) ?></th>
            <?php endforeach; ?>
            <?php if ($hasUngrouped): ?>
            <th class="text-end">OTHER FEE</th>
            <?php endif; ?>
            <th class="text-end">TOTAL</th>
            <th></th>
          </tr>
        </thead>
        <tbody style="font-weight:700">
          <tr>
            <td colspan="7" class="text-end pe-3">Grand Total:</td>
            <?php foreach ($costGroups as $cg): ?>
            <td class="text-end text-primary"><?= number_format($grandTotalByGroup[$cg['id']] ?? 0) ?></td>
            <?php endforeach; ?>
            <?php if ($hasUngrouped): ?>
            <td class="text-end text-primary"><?= number_format($grandUngrouped) ?></td>
            <?php endif; ?>
            <td class="text-end text-success"><?= number_format($grandTotalAll) ?></td>
            <td></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php endif; ?>

<!-- Hidden export form -->
<form id="exportForm" method="GET" action="" target="_blank">
  <input type="hidden" name="page"        value="report.ops_costs">
  <input type="hidden" name="export"      value="1">
  <input type="hidden" name="date_from"   id="expDateFrom">
  <input type="hidden" name="date_to"     id="expDateTo">
  <input type="hidden" name="month"       id="expMonth">
  <input type="hidden" name="ops_user_id" id="expOpsUserId">
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
  document.getElementById('expOpsUserId').value  = data.get('ops_user_id') || '';
  document.getElementById('exportForm').submit();
}
</script>
