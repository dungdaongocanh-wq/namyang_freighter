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
<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-header bg-white py-3 px-4" style="border-radius:12px 12px 0 0">
    <h6 class="mb-0 fw-bold">
      📦 <?= in_array($role ?? '', ['cs', 'admin']) ? 'Tất cả lô hàng' : 'Lô hàng của tôi' ?>
      <span class="badge bg-primary ms-1"><?= number_format($totalRows) ?></span>
    </h6>
  </div>
  <div class="card-body p-0">
    <?php if (empty($shipments)): ?>
    <div class="text-center text-muted py-5">
      <div style="font-size:3rem">🔍</div>
      <p class="mt-2">Không tìm thấy lô hàng nào</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle" style="font-size:0.85rem">
        <thead style="background:#f8fafc;color:#64748b;font-size:0.78rem">
          <tr>
            <th class="ps-4">HAWB</th>
            <?php if (in_array($role ?? '', ['cs', 'admin'])): ?>
            <th>Khách hàng</th>
            <?php endif; ?>
            <th>MAWB / Flight</th>
            <th>ETA</th>
            <th>Kiện / KG</th>
            <th>Active date</th>
            <th>Trạng thái</th>
            <th class="text-end pe-4">Chi phí</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($shipments as $s): ?>
          <tr style="cursor:pointer"
              onclick="location.href='<?= BASE_URL ?>/?page=customer.shipment_detail&id=<?= $s['id'] ?>'">
            <td class="ps-4 fw-semibold text-primary"><?= htmlspecialchars($s['hawb']) ?></td>
            <?php if (in_array($role ?? '', ['cs', 'admin'])): ?>
            <td>
              <div class="small"><?= htmlspecialchars($s['company_name'] ?? $s['cust_code'] ?? '-') ?></div>
            </td>
            <?php endif; ?>
            <td>
              <div class="small"><?= htmlspecialchars($s['mawb'] ?? '-') ?></div>
              <small class="text-muted"><?= htmlspecialchars($s['flight_no'] ?? '') ?></small>
            </td>
            <td class="small"><?= $s['eta'] ? date('d/m/Y', strtotime($s['eta'])) : '-' ?></td>
            <td><?= $s['packages'] ?> / <?= number_format($s['weight'],1) ?> kg</td>
            <td><?= date('d/m/Y', strtotime($s['active_date'])) ?></td>
            <td><?= $statusBadge($s['status']) ?></td>
            <td class="text-end pe-4">
              <?php if ($s['total_cost'] > 0): ?>
              <span class="fw-semibold text-success"><?= number_format($s['total_cost']) ?> đ</span>
              <?php else: ?>
              <span class="text-muted small">-</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
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