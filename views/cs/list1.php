<?php
$statusOptions = [
    ''                 => 'Tất cả trạng thái',
    'pending_customs'  => 'Chờ tờ khai',
    'cleared'          => 'Đã thông quan',
    'waiting_pickup'   => 'Chờ lấy hàng',
    'in_transit'       => 'Đang giao',
    'delivered'        => 'Đã giao',
    'kt_reviewing'     => 'KT đang duyệt',
    'pending_approval' => 'Chờ KH duyệt',
    'rejected'         => 'KH từ chối',
    'debt'             => 'Công nợ',
    'invoiced'         => 'Đã xuất HĐ',
];
$statusColors = [
    'pending_customs'  => 'danger',
    'cleared'          => 'warning',
    'waiting_pickup'   => 'warning',
    'in_transit'       => 'primary',
    'delivered'        => 'success',
    'kt_reviewing'     => 'info',
    'pending_approval' => 'purple',
    'rejected'         => 'dark',
    'debt'             => 'secondary',
    'invoiced'         => 'success',
];
?>

<!-- Filters -->
<div class="card mb-4" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-body py-3">
    <form method="GET" action="<?= BASE_URL ?>/" class="row g-2 align-items-end">
      <input type="hidden" name="page" value="cs.list">

      <div class="col-md-3">
        <input type="text" name="search" class="form-control form-control-sm"
               placeholder="🔍 HAWB, MAWB, KH..."
               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <?php foreach ($statusOptions as $val => $label): ?>
          <option value="<?= $val ?>" <?= ($_GET['status'] ?? '') === $val ? 'selected' : '' ?>>
            <?= $label ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="customer_id" class="form-select form-select-sm">
          <option value="">Tất cả khách hàng</option>
          <?php foreach ($customers as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($_GET['customer_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['customer_code']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" name="date_from" class="form-control form-control-sm"
               value="<?= $_GET['date_from'] ?? '' ?>" placeholder="Từ ngày">
      </div>
      <div class="col-md-2">
        <input type="date" name="date_to" class="form-control form-control-sm"
               value="<?= $_GET['date_to'] ?? '' ?>" placeholder="Đến ngày">
      </div>
      <div class="col-md-1 d-flex gap-1">
        <button type="submit" class="btn btn-primary btn-sm px-3">Lọc</button>
        <a href="<?= BASE_URL ?>/?page=cs.list" class="btn btn-outline-secondary btn-sm">✕</a>
      </div>
    </form>
  </div>
</div>

<!-- Table -->
<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center"
       style="border-radius:12px 12px 0 0">
    <h6 class="mb-0 fw-bold">
      📦 Danh sách lô hàng
      <span class="badge bg-primary ms-2"><?= number_format($totalRows) ?></span>
    </h6>
    <a href="<?= BASE_URL ?>/?page=cs.upload" class="btn btn-primary btn-sm">+ Upload lô</a>
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
            <th>Khách hàng</th>
            <th>MAWB</th>
            <th>Flight / ETA</th>
            <th>Kiện / KG</th>
            <th>Active date</th>
            <th>Trạng thái</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($shipments as $s): ?>
          <tr>
            <td class="ps-4">
              <span class="fw-semibold text-primary"><?= htmlspecialchars($s['hawb']) ?></span>
              <?php if ($s['remark']): ?>
              <i class="bi bi-chat-left-text text-muted ms-1" title="<?= htmlspecialchars($s['remark']) ?>"></i>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge bg-light text-dark border"><?= htmlspecialchars($s['customer_code']) ?></span>
            </td>
            <td class="text-muted small"><?= htmlspecialchars($s['mawb'] ?? '-') ?></td>
            <td>
              <div><?= htmlspecialchars($s['flight_no'] ?? '-') ?></div>
              <small class="text-muted"><?= $s['eta'] ? date('d/m/Y', strtotime($s['eta'])) : '' ?></small>
            </td>
            <td>
              <span class="fw-semibold"><?= $s['packages'] ?></span> kiện /
              <span class="fw-semibold"><?= number_format($s['weight'], 1) ?></span> kg
            </td>
            <td><?= date('d/m/Y', strtotime($s['active_date'])) ?></td>
            <td>
              <?php
              $clr = $statusColors[$s['status']] ?? 'secondary';
              $lbl = $statusOptions[$s['status']] ?? $s['status'];
              if ($s['status'] === 'pending_approval'): ?>
              <span class="badge" style="background:#f3e8ff;color:#7e22ce"><?= $lbl ?></span>
              <?php else: ?>
              <span class="badge bg-<?= $clr ?>"><?= $lbl ?></span>
              <?php endif; ?>
            </td>
            <td class="pe-3">
              <?php if ($s['status'] === 'pending_customs'): ?>
              <a href="<?= BASE_URL ?>/?page=cs.customs_upload&shipment_id=<?= $s['id'] ?>"
                 class="btn btn-xs btn-outline-danger" style="font-size:0.75rem;padding:2px 8px">
                Upload TK
              </a>
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
        Hiển thị <?= ($page-1)*30+1 ?>–<?= min($page*30,$totalRows) ?> / <?= number_format($totalRows) ?> kết quả
      </small>
      <nav>
        <ul class="pagination pagination-sm mb-0">
          <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
          <li class="page-item <?= $i==$page?'active':'' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['p'=>$i])) ?>"><?= $i ?></a>
          </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>