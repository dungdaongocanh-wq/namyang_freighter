<?php
$statusLabel = [
    'cleared'        => ['Đã thông quan', '#fef9c3', '#92400e'],
    'waiting_pickup' => ['Chờ lấy hàng',  '#ffedd5', '#c2410c'],
    'in_transit'     => ['Đang giao',      '#dbeafe', '#1d4ed8'],
    'delivered'      => ['Đã giao',        '#dcfce7', '#15803d'],
];
?>

<!-- Stats row -->
<div class="row g-2 mb-3">
  <?php
  $statItems = [
      ['cleared',         '📋', 'Đã TQ',      '#fef9c3','#92400e'],
      ['waiting_pickup',  '📦', 'Chờ lấy',    '#ffedd5','#c2410c'],
      ['in_transit',      '🚚', 'Đang giao',  '#dbeafe','#1d4ed8'],
      ['delivered_today', '✅', 'Giao hôm nay','#dcfce7','#15803d'],
  ];
  foreach ($statItems as [$key,$icon,$label,$bg,$color]): ?>
  <div class="col-6">
    <div class="mobile-card card">
      <div class="card-body py-3 px-3">
        <div class="d-flex align-items-center gap-2">
          <span style="font-size:1.6rem"><?= $icon ?></span>
          <div>
            <div class="fw-bold" style="font-size:1.4rem;color:<?= $color ?>"><?= $stats[$key] ?></div>
            <div style="font-size:0.75rem;color:#64748b"><?= $label ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Quick actions -->
<div class="row g-2 mb-3">
  <div class="col-6">
    <a href="<?= BASE_URL ?>/?page=ops.download_customs"
       class="btn btn-mobile-primary btn-warning w-100 d-flex align-items-center justify-content-center gap-2">
      <span style="font-size:1.4rem">⬇️</span>
      <span>Tải tờ khai TQ</span>
    </a>
  </div>
  <div class="col-6">
    <a href="<?= BASE_URL ?>/?page=ops.trip"
       class="btn btn-mobile-primary btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
      <span style="font-size:1.4rem">🚚</span>
      <span>Tạo chuyến</span>
    </a>
  </div>
</div>

<!-- Lô chờ xử lý -->
<div class="mobile-card card">
  <div class="card-body pb-2">
    <h6 class="fw-bold mb-3">📦 Lô chờ xử lý (<?= count($pendingShipments) ?>)</h6>

    <?php if (empty($pendingShipments)): ?>
    <div class="text-center text-muted py-4">
      <div style="font-size:2.5rem">🎉</div>
      <p class="small mt-2">Không có lô nào chờ xử lý!</p>
    </div>
    <?php else: ?>
    <?php foreach ($pendingShipments as $s):
      $bg    = $statusLabel[$s['status']][1] ?? '#f1f5f9';
      $color = $statusLabel[$s['status']][2] ?? '#334155';
      $lbl   = $statusLabel[$s['status']][0] ?? $s['status'];
    ?>
    <a href="<?= BASE_URL ?>/?page=ops.detail&id=<?= $s['id'] ?>"
       class="text-decoration-none">
      <div class="d-flex align-items-center gap-3 p-2 mb-2 rounded-3"
           style="background:#f8fafc;border-left:4px solid <?= $color ?>">
        <div class="flex-grow-1">
          <div class="fw-semibold text-dark"><?= htmlspecialchars($s['hawb']) ?></div>
          <div style="font-size:0.78rem;color:#64748b">
            <?= htmlspecialchars($s['customer_code']) ?> ·
            <?= $s['packages'] ?> kiện · <?= number_format($s['weight'],1) ?> kg
          </div>
          <div style="font-size:0.72rem;color:#94a3b8">
            Active: <?= date('d/m', strtotime($s['active_date'])) ?>
          </div>
        </div>
        <div>
          <span class="badge" style="background:<?= $bg ?>;color:<?= $color ?>;font-size:0.7rem">
            <?= $lbl ?>
          </span>
          <div class="text-end mt-1" style="font-size:0.7rem;color:#94a3b8">›</div>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Chuyến hôm nay -->
<?php if (!empty($todayTrips)): ?>
<div class="mobile-card card mt-3">
  <div class="card-body">
    <h6 class="fw-bold mb-3">🚚 Chuyến hôm nay (<?= count($todayTrips) ?>)</h6>
    <?php foreach ($todayTrips as $t): ?>
    <div class="d-flex justify-content-between align-items-center p-2 mb-2 rounded-3"
         style="background:#f0f7ff">
      <div>
        <div class="fw-semibold text-primary small">Chuyến #<?= $t['id'] ?></div>
        <div style="font-size:0.75rem;color:#64748b">
          🧑‍✈️ <?= htmlspecialchars($t['driver_name'] ?? 'N/A') ?> · <?= $t['item_count'] ?> lô
        </div>
      </div>
      <span class="badge bg-<?= $t['status']==='completed'?'success':'primary' ?>">
        <?= $t['status']==='completed'?'Hoàn thành':'Đang đi' ?>
      </span>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>