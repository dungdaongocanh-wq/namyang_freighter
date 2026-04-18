<?php
$statusOptions = [
    ''                 => 'Tất cả',
    'pending_customs'  => 'Chờ tờ khai',
    'cleared'          => 'Đã thông quan',
    'waiting_pickup'   => 'Chờ lấy hàng',
    'in_transit'       => 'Đang giao',
    'delivered'        => 'Đã giao',
    'kt_reviewing'     => 'KT duyệt',
    'pending_approval' => 'Chờ tôi duyệt',
    'rejected'         => 'Đã từ chối',
    'debt'             => 'Công nợ',
    'invoiced'         => 'Đã xuất HĐ',
];
$statusBadge = function($status) {
    $map = [
        'pending_customs'  => ['Chờ tờ khai',   'danger'],
        'cleared'          => ['Đã thông quan',  'warning'],
        'waiting_pickup'   => ['Chờ lấy',        'warning'],
        'in_transit'       => ['Đang giao',       'primary'],
        'delivered'        => ['Đã giao',         'success'],
        'kt_reviewing'     => ['KT duyệt',        'info'],
        'pending_approval' => ['Chờ duyệt',       'purple'],
        'rejected'         => ['Từ chối',          'dark'],
        'debt'             => ['Công nợ',           'secondary'],
        'invoiced'         => ['Đã xuất HĐ',       'success'],
    ];
    [$lbl,$clr] = $map[$status] ?? [$status,'secondary'];
    if ($clr === 'purple') return "<span class='badge' style='background:#f3e8ff;color:#7e22ce'>$lbl</span>";
    return "<span class='badge bg-$clr'>$lbl</span>";
};
?>

<!-- Filters -->
<div class="card mb-4" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-body py-3">
    <form method="GET" action="<?= BASE_URL ?>/" class="row g-2 align-items-end">
      <input type="hidden" name="page" value="customer.shipment_list">
      <div class="col-md-3">
        <input type="text" name="search" class="form-control form-control-sm"
               placeholder="🔍 Tìm HAWB, MAWB..."
               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
          <?php foreach ($statusOptions as $v => $l): ?>
          <option value="<?= $v ?>" <?= ($_GET['status']??'')===$v?'selected':'' ?>>
            <?= $l ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="month" class="form-select form-select-sm">
          <option value="">Tất cả tháng</option>
          <?php foreach ($months as $m): ?>
          <option value="<?= $m ?>" <?= ($_GET['month']??'')===$m?'selected':'' ?>>
            <?= date('m/Y', strtotime($m.'-01')) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto d-flex gap-1">
        <button type="submit" class="btn btn-primary btn-sm px-4">Lọc</button>
        <a href="<?= BASE_URL ?>/?page=customer.shipment_list"
           class="btn btn-outline-secondary btn-sm">✕</a>
      </div>
    </form>
  </div>
</div>

<!-- Table -->
<?php
$role = $_SESSION['role'] ?? '';
$hasUngrouped = false;
$sumUngrouped = 0;
foreach ($shipments as $s) {
    $ug = (float)($costsByShipment[$s['id']]['ungrouped'] ?? 0);
    if ($ug > 0) { $hasUngrouped = true; $sumUngrouped += $ug; }
}
?>
<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-header bg-white py-3 px-4 d-flex align-items-center gap-2" style="border-radius:12px 12px 0 0">
    <h6 class="mb-0 fw-bold">
      📦 <?= in_array($role, ['cs', 'admin']) ? 'Tất cả lô hàng' : 'Lô hàng của tôi' ?>
    </h6>
    <span class="badge bg-primary ms-1"><?= number_format($totalRows) ?></span>
  </div>
  <div class="card-body p-0">
    <?php if (empty($shipments)): ?>
    <div class="text-center text-muted py-5">
      <div style="font-size:3rem">🔍</div>
      <p class="mt-2">Không tìm thấy lô hàng nào</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle mb-0" style="font-size:0.82rem">
        <thead style="background:#1e3a5f;color:#fff;font-size:0.78rem">
          <tr>
            <th class="text-center" style="width:40px">NO</th>
            <th style="width:85px">DATE</th>
            <?php if (in_array($role, ['cs', 'admin'])): ?>
            <th>CONSIGNEE</th>
            <?php endif; ?>
            <th>HAWB</th>
            <th>CD NO.</th>
            <th class="text-center" style="width:50px">PKG</th>
            <th class="text-end" style="width:70px">GW (KG)</th>
            <?php foreach ($costGroups as $cg): ?>
            <th class="text-end" style="min-width:100px"><?= htmlspecialchars(strtoupper($cg['name'])) ?></th>
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
          $no = 1;
          $sumByGroup = [];
          $sumTotal   = 0;
          foreach ($shipments as $s):
            $sid   = $s['id'];
            $total = 0;
            foreach ($costGroups as $cg) $total += (float)($costsByShipment[$sid][$cg['id']] ?? 0);
            $ug = (float)($costsByShipment[$sid]['ungrouped'] ?? 0);
            $total += $ug;
            foreach ($costGroups as $cg) {
                if (!isset($sumByGroup[$cg['id']])) $sumByGroup[$cg['id']] = 0;
                $sumByGroup[$cg['id']] += (float)($costsByShipment[$sid][$cg['id']] ?? 0);
            }
            $sumTotal += $total;
          ?>
          <tr style="cursor:pointer"
              onclick="location.href='<?= BASE_URL ?>/?page=customer.shipment_detail&id=<?= $s['id'] ?>'">
            <td class="text-center"><?= $no++ ?></td>
            <td><?= $s['active_date'] ? date('d/m/Y', strtotime($s['active_date'])) : '-' ?></td>
            <?php if (in_array($role, ['cs', 'admin'])): ?>
            <td class="fw-semibold"><?= htmlspecialchars($s['company_name'] ?? $s['cust_code'] ?? '-') ?></td>
            <?php endif; ?>
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
            <?php $colFixed = in_array($role, ['cs', 'admin']) ? 7 : 6; ?>
            <td colspan="<?= $colFixed ?>" class="text-end pe-3">TỔNG CỘNG:</td>
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

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top">
      <small class="text-muted">
        <?= ($page-1)*20+1 ?>–<?= min($page*20,$totalRows) ?> / <?= number_format($totalRows) ?>
      </small>
      <nav>
        <ul class="pagination pagination-sm mb-0">
          <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
          <li class="page-item <?= $i==$page?'active':'' ?>">
            <a class="page-link"
               href="?<?= http_build_query(array_merge($_GET,['p'=>$i])) ?>"><?= $i ?></a>
          </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>