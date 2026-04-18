<!-- Stats row -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['kt_reviewing',     '🔍', 'Cần xét duyệt',    'warning',   'accounting.review'],
    ['pending_approval', '⏳', 'Chờ KH duyệt',     'purple',    'accounting.review'],
    ['rejected',         '❌', 'KH từ chối',        'danger',    'accounting.rejected'],
    ['debt',             '💰', 'Công nợ (lô)',      'success',   'accounting.debt'],
  ];
  foreach ($cards as [$key, $icon, $label, $color, $page]): ?>
  <div class="col-6 col-md-3">
    <div class="card stat-card h-100"
         style="<?= $color==='purple'?'border-left:4px solid #7e22ce':'' ?>">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <p class="text-muted small mb-1"><?= $label ?></p>
            <h3 class="fw-bold mb-0 <?= $color==='purple'?'':'text-'.$color ?>">
              <?= $stats[$key] ?>
            </h3>
          </div>
          <span style="font-size:1.8rem"><?= $icon ?></span>
        </div>
        <a href="<?= BASE_URL ?>/?page=<?= $page ?>"
           class="btn btn-sm btn-outline-<?= $color==='purple'?'secondary':$color ?> mt-3 w-100">
          Xem →
        </a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Tổng công nợ -->
<div class="card mb-4" style="border:none;border-radius:12px;background:linear-gradient(135deg,#1e3a5f,#2d6a9f);color:#fff">
  <div class="card-body py-3 px-4">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <p class="mb-1 opacity-75 small">TỔNG CÔNG NỢ HIỆN TẠI</p>
        <h3 class="fw-bold mb-0">
          <?= number_format($stats['total_debt']) ?> đ
        </h3>
      </div>
      <span style="font-size:3rem;opacity:0.4">💳</span>
    </div>
  </div>
</div>

<!-- Danh sách cần xét duyệt -->
<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center"
       style="border-radius:12px 12px 0 0">
    <h6 class="mb-0 fw-bold">🔍 Chờ xét duyệt</h6>
    <a href="<?= BASE_URL ?>/?page=accounting.review" class="btn btn-sm btn-warning">
      Xem tất cả
    </a>
  </div>
  <div class="card-body p-0">
    <?php if (empty($reviewList)): ?>
    <div class="text-center text-muted py-5">
      <div style="font-size:2.5rem">🎉</div>
      <p class="mt-2 small">Không có lô nào chờ xét duyệt!</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle" style="font-size:0.85rem">
        <thead style="background:#f8fafc;color:#64748b;font-size:0.78rem">
          <tr>
            <th class="ps-4">HAWB</th>
            <th>Khách hàng</th>
            <th>Active date</th>
            <th class="text-end">Tổng chi phí</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reviewList as $s): ?>
          <tr data-id="<?= $s['id'] ?>" style="cursor:pointer">
            <td class="ps-4">
              <span class="fw-semibold text-primary"><?= htmlspecialchars($s['hawb']) ?></span>
            </td>
            <td><?= htmlspecialchars($s['customer_code']) ?></td>
            <td><?= date('d/m/Y', strtotime($s['active_date'])) ?></td>
            <td class="text-end fw-semibold text-success">
              <?= number_format($s['total_cost']) ?> đ
            </td>
            <td class="pe-3">
              <a href="<?= BASE_URL ?>/?page=accounting.review&id=<?= $s['id'] ?>"
                 class="btn btn-sm btn-warning">Xét duyệt</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>